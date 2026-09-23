<?php
namespace App\Services;

use App\Models\Branch;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use Illuminate\Support\Facades\Auth;
use App\Models\Library;
use App\Models\LibraryTransaction;
use App\Models\PlanType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\BillingAmountService;

class PlanService
{
   
    public function getAvailablePlanTypes($seatNo, $branchId){
        $first_record = Hour::where('branch_id', $branchId)->first();
        $total_hour = $first_record ? (int)$first_record->hour : 24;

        if ($seatNo) {
            // Step 1: Retrieve all active bookings for the given seat
            $bookings = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
                ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
                ->where('learner_detail.seat_no', $seatNo)
                ->where('learners.status', 1)
                ->where('learner_detail.status', 1)
                ->where('learners.branch_id', $branchId)
                ->where('learner_detail.branch_id', $branchId)
                ->whereNull('learner_detail.deleted_at')
                ->get([
                    'learner_detail.plan_type_id',
                    'plan_types.day_type_id',
                    'plan_types.start_time',
                    'plan_types.end_time',
                    'plan_types.slot_hours'
                ]);

            $bookedDayTypeIds = $bookings->pluck('day_type_id')->map(fn($v) => (int)$v)->toArray();
            $bookedPlanTypeIds = $bookings->pluck('plan_type_id')->map(fn($v) => (int)$v)->toArray();

            $hasAnyDaytimeBooked = false;
            foreach ($bookedDayTypeIds as $dt) {
                if (in_array($dt, [1, 2, 3, 4, 5, 6, 7])) {
                    $hasAnyDaytimeBooked = true;
                    break;
                }
            }

            // 1. Seat is fully booked if:
            // - 24-hr shift (All Day 8, Reserved 10, VIP 11) is booked
            // - Both Full Day (1) and Full Night (9) are booked
            // - Both Half shifts (2 & 3) and Full Night (9) are booked
            // - Branch is < 24 hrs and Full Day (1) is booked
            // - Total booked hours >= branch open hours
            $totalBookedHours = (float) $bookings->sum('slot_hours');
            $isFullyBooked = (
                in_array(8, $bookedDayTypeIds) ||
                in_array(10, $bookedDayTypeIds) ||
                in_array(11, $bookedDayTypeIds) ||
                (in_array(1, $bookedDayTypeIds) && in_array(9, $bookedDayTypeIds)) ||
                (in_array(2, $bookedDayTypeIds) && in_array(3, $bookedDayTypeIds) && in_array(9, $bookedDayTypeIds)) ||
                ($total_hour < 24 && in_array(1, $bookedDayTypeIds)) ||
                ($total_hour > 0 && $totalBookedHours >= $total_hour)
            );

            if ($isFullyBooked) {
                return collect();
            }

            // Step 2: Retrieve all branch plan types
            $planTypes = PlanType::byBranch($branchId)->get();

            // Step 3: Filter plan types strictly according to shift rules & time overlap
            $filteredPlanTypes = $planTypes->filter(function ($planType) use ($bookings, $bookedDayTypeIds, $bookedPlanTypeIds, $hasAnyDaytimeBooked, $total_hour) {
                // Cannot book the exact same plan type already booked on this seat
                if (in_array($planType->id, $bookedPlanTypeIds)) {
                    return false;
                }

                // Cannot book the same day_type already booked (unless custom day_type 0)
                if ($planType->day_type_id != 0 && in_array($planType->day_type_id, $bookedDayTypeIds)) {
                    return false;
                }

                // 24-hour shifts (All Day 8, Reserved 10, VIP 11) require seat to be completely unbooked
                if ($bookings->isNotEmpty() && in_array($planType->day_type_id, [8, 10, 11])) {
                    return false;
                }

                // If branch open hours < 24, All Day (8) and Full Night (9) are not allowed
                if ($total_hour < 24 && in_array($planType->day_type_id, [8, 9])) {
                    return false;
                }

                // If any daytime shift is booked (Full Day 1, Half shifts 2/3), Full Day (1) cannot be booked
                if ($planType->day_type_id == 1 && $hasAnyDaytimeBooked) {
                    return false;
                }

                // If Full Day (1) is booked, daytime half/hourly shifts (2, 3, 4, 5, 6, 7) cannot be booked
                if (in_array(1, $bookedDayTypeIds) && in_array($planType->day_type_id, [2, 3, 4, 5, 6, 7])) {
                    return false;
                }

                // Time slot overlap check against all currently booked shifts on the seat
                foreach ($bookings as $booking) {
                    if ($this->planTypeTimesOverlap($booking, $planType)) {
                        return false;
                    }
                }

                return true;
            })->map(function ($planType) {
                return [
                    'id'          => $planType->id,
                    'name'        => $planType->name,
                    'day_type_id' => $planType->day_type_id,
                    'start_time'  => $planType->start_time,
                    'end_time'    => $planType->end_time,
                ];
            })->values();

        } else {
            // General / Unassigned seat: all plan types suitable for branch open hours
            if ($total_hour < 24) {
                $filteredPlanTypes = PlanType::byBranch($branchId)
                    ->whereNotIn('day_type_id', [8, 9])
                    ->select('id', 'name', 'day_type_id', 'start_time', 'end_time')
                    ->get();
            } else {
                $filteredPlanTypes = PlanType::byBranch($branchId)
                    ->select('id', 'name', 'day_type_id', 'start_time', 'end_time')
                    ->get();
            }
        }

        return $filteredPlanTypes;
    }

    private function planTypeTimesOverlap($firstPlanType, $secondPlanType): bool
    {
        $firstIntervals = $this->timeIntervals($firstPlanType->start_time, $firstPlanType->end_time);
        $secondIntervals = $this->timeIntervals($secondPlanType->start_time, $secondPlanType->end_time);

        foreach ($firstIntervals as $first) {
            foreach ($secondIntervals as $second) {
                if ($first[0] < $second[1] && $second[0] < $first[1]) {
                    return true;
                }
            }
        }

        return false;
    }

    private function timeIntervals($startTime, $endTime): array
    {
        $start = $this->timeToMinutes($startTime);
        $end = $this->timeToMinutes($endTime);

        if ($start === null || $end === null) {
            return [];
        }

        if ($start === $end) {
            return [[0, 1440]];
        }

        if ($end > $start) {
            return [[$start, $end]];
        }

        return [[$start, 1440], [0, $end]];
    }

    private function timeToMinutes($time): ?int
    {
        if ($time === null || $time === '') {
            return null;
        }

        $parts = explode(':', (string) $time);
        if (count($parts) < 2) {
            return null;
        }

        return ((int) $parts[0] * 60) + (int) $parts[1];
    }

    public function calculatePrice(int $planId,int $planTypeId,?string $planStartDate,?int $branchId,float $lockerAmount = 0,?string $discountType = null,float $discountValue = 0,float $paidAmount = 0) {

        $startDate = Carbon::parse($planStartDate);
       $day_type_id=PlanType::where('id',$planTypeId)->select('day_type_id')->first();

        $branch = Branch::select('fixed_billing_date')
            ->where('id', $branchId)
            ->first();

        $hasFixedBilling = !empty($branch?->fixed_billing_date);

        if ($hasFixedBilling) {

            $planPrice = getBillingCyclePrice(
                $planId,
                $planTypeId,
                $startDate,
                $branchId
            );

        } else {

            $planPrice = getPlanPrice(
                $planId,
                $planTypeId,
                $branchId
            );
        }
         /* -----------------------------
       DISCOUNT CALCULATION
    ------------------------------*/

    $billing = BillingAmountService::calculate(
        (float) $planPrice,
        (float) $lockerAmount,
        $discountType,
        (float) $discountValue,
        (float) $paidAmount
    );

    $discountAmount = $billing['discount_amount'];
    $totalAmount = $billing['total_amount'];
    $pendingAmount = $totalAmount - (float) $paidAmount;

     if(($day_type_id->day_type_id ?? null)==11){
        $totalAmount=0;
        $pendingAmount=0;
        
    }

        return [
            'price' => (string)  $planPrice,
            'locker_amount'   => (string) $lockerAmount,
            'discount_amount' => (string) $discountAmount,
            'total_amount'    => (string) $totalAmount,
            'paid_amount'     => (string) $paidAmount,
            'pending_amount'  => (string) $pendingAmount,
            'fixed_billing'   => $hasFixedBilling
        ];
    }
}
