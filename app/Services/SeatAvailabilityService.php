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
     * @param  int|string  $userId  Learner id (same as web: user_id)
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
     * A seat is "available" for a given plan type (shift) when it has no currently active
     * booking (learner_detail.status = 1, learner.status = 1) whose plan type's time range
     * overlaps that shift, and the seat still has free daily hour capacity left.
     *
     * @return array<int, Collection<int, PlanType>> seat number (1..$totalSeats) => available plan types for that seat
     */
    public function getAvailablePlanTypesMap(int $branchId, int $totalSeats): array
    {
        if ($totalSeats < 1) {
            return [];
        }

        $totalHour = Hour::withoutGlobalScopes()->where('branch_id', $branchId)->value('hour');
        $planTypes = PlanType::get();

        $seatNos = range(1, $totalSeats);

        $bookings = LearnerDetail::withoutGlobalScopes()
            ->join('learners', 'learners.id', '=', 'learner_detail.learner_id')
            ->join('plan_types', 'plan_types.id', '=', 'learner_detail.plan_type_id')
            ->where('learner_detail.branch_id', $branchId)
            ->where('learners.status', 1)
            ->where('learner_detail.status', 1)
            ->whereNull('learner_detail.deleted_at')
            ->whereIn('learner_detail.seat_no', $seatNos)
            ->select('learner_detail.seat_no as batch_seat_no', 'learner_detail.hour', 'plan_types.start_time', 'plan_types.end_time')
            ->get();

        $bookingsBySeat = $bookings->groupBy(fn ($row) => (int) $row->batch_seat_no);

        $map = [];

        foreach ($seatNos as $seatNo) {
            $seatBookings = $bookingsBySeat->get($seatNo) ?? new Collection;

            if ($totalHour !== null && $seatBookings->sum('hour') >= $totalHour) {
                $map[$seatNo] = new Collection;

                continue;
            }

            $map[$seatNo] = $planTypes->filter(function ($planType) use ($seatBookings) {
                foreach ($seatBookings as $booking) {
                    if (self::rangesOverlap($booking->start_time, $booking->end_time, $planType->start_time, $planType->end_time)) {
                        return false;
                    }
                }

                return true;
            })->values();
        }

        return $map;
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
            if (self::rangesOverlap($booking->start_time, $booking->end_time, $planType->start_time, $planType->end_time)) {
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

            if (self::rangesOverlap($futureStartTime, $futureEndTime, $customerStartTime, $customerEndTime)) {
                $status = 2;
                break;
            }
        }

        return (int) $status;
    }

    /**
     * Converts a "H:i" (or "H:i:s") time string to minutes since midnight.
     */
    private static function toMinutes(string $time): int
    {
        $parts = explode(':', $time);

        return ((int) ($parts[0] ?? 0)) * 60 + ((int) ($parts[1] ?? 0));
    }

    /**
     * Normalizes a [start, end) shift range to minutes since midnight, pushing end past
     * midnight (+1440) whenever end <= start — e.g. a "20:00"-"00:00" or "20:00"-"08:00"
     * overnight shift — so the range keeps its real duration instead of collapsing to zero
     * or negative width.
     *
     * @return array{0: int, 1: int}
     */
    private static function normalizeRange(string $start, string $end): array
    {
        $startMinutes = self::toMinutes($start);
        $endMinutes = self::toMinutes($end);

        if ($endMinutes <= $startMinutes) {
            $endMinutes += 1440;
        }

        return [$startMinutes, $endMinutes];
    }

    /**
     * Whether two daily-recurring shift ranges overlap, correctly handling ranges that cross
     * midnight (e.g. a night shift "20:00"-"08:00" against a "20:00"-"00:00" booking) by
     * comparing the normalized range against the other range shifted by a full day in both
     * directions.
     */
    private static function rangesOverlap(string $startA, string $endA, string $startB, string $endB): bool
    {
        [$startA, $endA] = self::normalizeRange($startA, $endA);
        [$startB, $endB] = self::normalizeRange($startB, $endB);

        foreach ([-1440, 0, 1440] as $offset) {
            if ($startA < $endB + $offset && $endA > $startB + $offset) {
                return true;
            }
        }

        return false;
    }
}
