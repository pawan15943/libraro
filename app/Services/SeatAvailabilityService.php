<?php

namespace App\Services;

use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\PlanType;
use App\Traits\LearnerQueryTrait;
use Carbon\Carbon;

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
        $count = $this->getLearnersByLibrary()
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

        $first_record = Hour::first();
        $total_hour = $first_record ? $first_record->hour : null;

        $total_cust_hour = Learner::where('library_id', getLibraryId())
            ->where('seat_no', $newSeatId)
            ->where('status', 1)
            ->sum('hours');
        $new_seat_remaining = $total_hour - $total_cust_hour;

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

        $status_array = [];

        foreach ($bookings as $booking) {
            if ($booking->start_time < $planType->end_time && $booking->end_time > $planType->start_time) {
                $status_array[] = 0;
            } else {
                $status_array[] = 1;
            }
        }

        $futurebookings = $this->getLearnersByLibrary()
            ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->where('learner_detail.seat_no', $newSeatId)
            ->where('learner_detail.plan_start_date', '>', date('Y-m-d'))
            ->get(['plan_start_date', 'plan_end_date', 'plan_types.start_time', 'plan_types.end_time']);

        $customer_detail = LearnerDetail::where('learner_id', $userId)
            ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->select('plan_start_date', 'plan_end_date', 'plan_types.start_time', 'plan_types.end_time')
            ->first();

        if (! $customer_detail) {
            return 0;
        }

        $customerStartDate = Carbon::parse($customer_detail->plan_start_date)->toDateString();
        $customerEndDate = Carbon::parse($customer_detail->plan_end_date)->toDateString();
        $customerStartTime = $customer_detail->start_time;
        $customerEndTime = $customer_detail->end_time;

        if ($customer->hours > $new_seat_remaining) {
            $status = 0;
        } elseif ($count == 1) {
            $status = 0;
        } elseif (in_array(0, $status_array)) {
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
