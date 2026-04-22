<?php

namespace App\Services;

use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\PlanType;
use App\Traits\LearnerQueryTrait;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Shared swap-seat / overlap checks for web and API (see web getSeatStatus).
 */
class SeatAvailabilityService
{
    use LearnerQueryTrait;

    /**
     * @param  int|string  $newSeatId  Seat being checked (same as web: new_seat_id)
     * @param  int|string  $userId Learner id (same as web: user_id)
     * @param  int|string  $planTypeId  Plan type id (same as web: plan_type_id)
     * @return int 0 = not available, 1 = available to swap, 2 = future booking clash
     */
    public function getSwapSeatStatusCode($newSeatId, $userId, $planTypeId): int
    {
        $count = (int) $this->getLearnersByLibrary()
            ->where('learner_detail.seat_no', $newSeatId)
            ->where('learners.status', 1)
            ->where('learner_detail.status', 1)
            ->where('learner_detail.plan_type_id', $planTypeId)
            ->count();

        $customer = Learner::where('id', $userId)
            ->where('status', 1)
            ->first();

        if (! $customer) {
            return 0;
        }

        $firstRecord = Hour::first();
        $totalHour = $firstRecord ? $firstRecord->hour : null;

        $totalCustHour = (float) Learner::where('library_id', getLibraryId())
            ->where('seat_no', $newSeatId)
            ->where('status', 1)
            ->sum('hours');

        $bookings = $this->getLearnersByLibrary()
            ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->where('learner_detail.seat_no', $newSeatId)
            ->where('learners.status', 1)
            ->where('learner_detail.status', 1)
            ->get(['learner_detail.plan_type_id', 'plan_types.start_time', 'plan_types.end_time', 'plan_types.slot_hours']);

        $planType = PlanType::where('id', $planTypeId)->first();

        if (! $planType) {
            return 0;
        }

        $futurebookings = $this->getLearnersByLibrary()
            ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->where('learner_detail.seat_no', $newSeatId)
            ->where('learner_detail.plan_start_date', '>', date('Y-m-d'))
            ->get(['plan_start_date', 'plan_end_date', 'plan_types.start_time', 'plan_types.end_time']);

        $customerDetail = LearnerDetail::query()
            ->where('learner_id', $userId)
            ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->select('plan_start_date', 'plan_end_date', 'plan_types.start_time', 'plan_types.end_time')
            ->first();

        if (! $customerDetail) {
            return 0;
        }

        return $this->evaluateSwapSeatStatus(
            $customer,
            $planType,
            $customerDetail,
            $totalHour,
            $count,
            $totalCustHour,
            $bookings,
            $futurebookings
        );
    }

    /**
     * Same rules as {@see getSwapSeatStatusCode} but with batched DB reads (O(1) queries vs O(seats)).
     *
     * @return array<int, int> seat number (1..$totalSeats) => status code
     */
    public function getSwapSeatStatusCodesMap(int $userId, int $planTypeId, int $totalSeats): array
    {
        if ($totalSeats < 1) {
            return [];
        }

        $zeroMap = static function (int $n): array {
            return array_fill(1, $n, 0);
        };

        $customer = Learner::where('id', $userId)
            ->where('status', 1)
            ->first();

        if (! $customer) {
            return $zeroMap($totalSeats);
        }

        $planType = PlanType::where('id', $planTypeId)->first();

        if (! $planType) {
            return $zeroMap($totalSeats);
        }

        $customerDetail = LearnerDetail::query()
            ->where('learner_id', $userId)
            ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->select('plan_start_date', 'plan_end_date', 'plan_types.start_time', 'plan_types.end_time')
            ->first();

        if (! $customerDetail) {
            return $zeroMap($totalSeats);
        }

        $firstRecord = Hour::first();
        $totalHour = $firstRecord ? $firstRecord->hour : null;

        $seatNos = range(1, $totalSeats);

        $countBySeat = $this->getLearnersByLibrary()
            ->whereIn('learner_detail.seat_no', $seatNos)
            ->where('learner_detail.plan_type_id', $planTypeId)
            ->where('learners.status', 1)
            ->where('learner_detail.status', 1)
            ->selectRaw('learner_detail.seat_no as s, COUNT(*) as c')
            ->groupBy('learner_detail.seat_no')
            ->pluck('c', 's');
        $countBySeat = $countBySeat->mapWithKeys(fn ($c, $s) => [(int) $s => (int) $c]);

        $totalCustHoursBySeat = Learner::query()
            ->where('library_id', getLibraryId())
            ->whereIn('seat_no', $seatNos)
            ->where('status', 1)
            ->selectRaw('seat_no, SUM(hours) as h_sum')
            ->groupBy('seat_no')
            ->pluck('h_sum', 'seat_no');
        $totalCustHoursBySeat = $totalCustHoursBySeat->mapWithKeys(fn ($h, $s) => [(int) $s => (float) $h]);

        $allBookings = $this->getLearnersByLibrary()
            ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->whereIn('learner_detail.seat_no', $seatNos)
            ->where('learners.status', 1)
            ->where('learner_detail.status', 1)
            ->select(
                'learner_detail.seat_no as batch_seat_no',
                'learner_detail.plan_type_id',
                'plan_types.start_time',
                'plan_types.end_time',
                'plan_types.slot_hours'
            )
            ->get();
        $bookingsBySeat = $allBookings->groupBy(fn ($row) => (int) $row->batch_seat_no);

        $allFuture = $this->getLearnersByLibrary()
            ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->whereIn('learner_detail.seat_no', $seatNos)
            ->where('learner_detail.plan_start_date', '>', date('Y-m-d'))
            ->select(
                'learner_detail.seat_no as batch_seat_no',
                'learner_detail.plan_start_date',
                'learner_detail.plan_end_date',
                'plan_types.start_time',
                'plan_types.end_time'
            )
            ->get();
        $futureBySeat = $allFuture->groupBy(fn ($row) => (int) $row->batch_seat_no);

        $out = [];
        for ($seatNo = 1; $seatNo <= $totalSeats; $seatNo++) {
            $count = (int) $countBySeat->get($seatNo, 0);
            $totalCustHour = (float) $totalCustHoursBySeat->get($seatNo, 0.0);
            $bookings = $bookingsBySeat->get($seatNo) ?? new Collection;
            $future = $futureBySeat->get($seatNo) ?? new Collection;

            $out[$seatNo] = $this->evaluateSwapSeatStatus(
                $customer,
                $planType,
                $customerDetail,
                $totalHour,
                $count,
                $totalCustHour,
                $bookings,
                $future
            );
        }

        return $out;
    }

    /**
     * @param  mixed  $totalHour
     * @param  \Illuminate\Support\Collection<int, \stdClass>  $bookings
     * @param  \Illuminate\Support\Collection<int, \stdClass>  $futurebookings
     */
    private function evaluateSwapSeatStatus(
        Learner $customer,
        PlanType $planType,
        object $customerDetail,
        $totalHour,
        int $count,
        float $totalCustHour,
        Collection $bookings,
        Collection $futurebookings
    ): int {
        $newSeatRemaining = $totalHour - $totalCustHour;

        $statusArray = [];
        foreach ($bookings as $booking) {
            if ($booking->start_time < $planType->end_time && $booking->end_time > $planType->start_time) {
                $statusArray[] = 0;
            } else {
                $statusArray[] = 1;
            }
        }

        $customerStartDate = Carbon::parse($customerDetail->plan_start_date)->toDateString();
        $customerEndDate = Carbon::parse($customerDetail->plan_end_date)->toDateString();
        $customerStartTime = $customerDetail->start_time;
        $customerEndTime = $customerDetail->end_time;

        if ($customer->hours > $newSeatRemaining) {
            $status = 0;
        } elseif ($count == 1) {
            $status = 0;
        } elseif (in_array(0, $statusArray)) {
            $status = 0;
        } elseif ($count == 0) {
            $status = 1;
        } else {
            $status = 1;
        }

        foreach ($futurebookings as $fb) {
            $futureStartDate = Carbon::parse($fb->plan_start_date)->toDateString();
            $futureEndDate = Carbon::parse($fb->plan_end_date)->toDateString();

            $futureStartTime = $fb->start_time;
            $futureEndTime = $fb->end_time;

            $dateOverlap = (
                ($futureStartDate >= $customerStartDate && $futureStartDate <= $customerEndDate) ||
                ($futureEndDate >= $customerStartDate && $futureEndDate <= $customerEndDate) ||
                ($futureStartDate <= $customerStartDate && $futureEndDate >= $customerEndDate)
            );

            if (! $dateOverlap) {
                continue;
            }

            $timeOverlap = (
                $futureStartTime < $customerEndTime &&
                $futureEndTime > $customerStartTime
            );

            if ($timeOverlap) {
                $status = 2;
                break;
            }
        }

        return (int) $status;
    }
}
