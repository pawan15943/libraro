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

class PlanService
{
   
    public function getAvailablePlanTypes($seatNo, $branchId){
        if ($seatNo) {


            // Step 1: Retrieve all bookings for the given seat
            $bookings =Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
                ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
                ->where('learner_detail.seat_no', $seatNo)
                ->where('learners.status', 1)
                ->where('learner_detail.status', 1)
                ->where('learners.branch_id', $branchId)
                ->where('learner_detail.branch_id', $branchId)
                ->get(['learner_detail.plan_type_id', 'plan_types.start_time', 'plan_types.end_time', 'plan_types.slot_hours']);

            // Step 2: Retrieve all plan types
            $planTypes = PlanType::byBranch($branchId)->get();

            // Step 3: Initialize an array to store the plan_type_ids to be removed
            $planTypesRemovals = [];

            // Step 4: Calculate total booked hours for the seat
            $totalBookedHours = $bookings->sum('slot_hours');

            $nightseatBooked = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->where('learner_detail.seat_no', $seatNo)->where('learner_detail.status', 1)
            ->where('plan_types.day_type_id', 9)->exists();

            // Step 5: Determine conflicts based on plan_type_id and hours
            $planTypeId = null;
            if ($totalBookedHours < 24) {

                foreach ($bookings as $booking) {
                    foreach ($planTypes as $planType) {
                        if ($booking->start_time < $planType->end_time && $booking->end_time > $planType->start_time) {
                            $planTypesRemovals[] = $planType->id;
                        }
                    }
                }
            }
            if ($totalBookedHours > 1) {
                $planTypeId = PlanType::byBranch($branchId)->where('day_type_id', 8)->value('id') ?? 0;
            }

            if (!is_null($planTypeId)) {
                $planTypesRemovals[] = $planTypeId;
            }
            if ($nightseatBooked) {
                $planTypeid = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
                ->where('learner_detail.seat_no', $seatNo)->where('learner_detail.status', 1)
                ->where('plan_types.day_type_id', 9)->value('plan_types.id') ?? 0;
                $planTypesRemovals[] = $planTypeid;
            }
            // Remove duplicate entries in planTypesRemovals
            $planTypesRemovals = array_unique($planTypesRemovals);

            // If total booked hours >= 16, all plan types should be removed
            $first_record = Hour::where('branch_id', $branchId)->first();
            $total_hour = $first_record ? $first_record->hour : null;

            if ($totalBookedHours >= $total_hour) {
                $planTypesRemovals = $planTypes->pluck('id')->toArray();
            }
            // ✅ Remove day_type_id 8 and 9 if total allowed hours < 24
            if ($total_hour < 24) {
                $dayTypePlanIds = PlanType::byBranch($branchId)->whereIn('day_type_id', [8, 9])->pluck('id')->toArray();
                $planTypesRemovals = array_merge($planTypesRemovals, $dayTypePlanIds);
            }
            // Step 6: Filter out the plan_types that match the retrieved plan_type_ids
            $filteredPlanTypes = $planTypes->filter(function ($planType) use ($planTypesRemovals) {
                return !in_array($planType->id, $planTypesRemovals);
            })->map(function ($planType) {
                return [
                    'id'          => $planType->id,
                    'name'        => $planType->name,
                    'start_time'  => $planType->start_time,
                    'end_time'    => $planType->end_time,
                    'slot_hours'  => (int) ($planType->slot_hours ?? 0),
                ];
            })->values(); // Ensure the keys are reset to a continuous numerical index
        } else {

            $first_record = Hour::where('branch_id', $branchId)->first();
            $total_hour = $first_record ? $first_record->hour : null;

            if ($total_hour < 24) {
                $filteredPlanTypes = PlanType::byBranch($branchId)->whereNotIn('day_type_id', [8, 9])
                ->select('id', 'name', 'start_time', 'end_time', 'slot_hours')
                    ->get();
            } else {
                $filteredPlanTypes = PlanType::byBranch($branchId)->select('id', 'name', 'start_time', 'end_time', 'slot_hours')->get();
            }
        }

        return $filteredPlanTypes;
    }

    public function calculatePrice(int $planId,int $planTypeId,?string $planStartDate,?int $branchId,float $lockerAmount = 0,?string $discountType = null,float $discountValue = 0,float $paidAmount = 0) {

        $startDate = Carbon::parse($planStartDate);
       

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

    $discountAmount = 0;

    if ($discountType === 'percentage') {
        $discountAmount = (($planPrice + $lockerAmount) * $discountValue) / 100;
    } elseif ($discountType === 'amount') {
        $discountAmount = $discountValue;
    }

    /* -----------------------------
       TOTAL PRICE
    ------------------------------*/

    $totalAmount = ($planPrice + $lockerAmount) - $discountAmount;

    /* -----------------------------
       PENDING AMOUNT
    ------------------------------*/

    $pendingAmount = $totalAmount - $paidAmount;

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

    /**
     * Same rules as calculatePrice, but base plan fee is the sum of per-shift prices.
     *
     * @param  list<int>  $planTypeIds
     */
    public function calculateMultiShiftPrice(
        int $planId,
        array $planTypeIds,
        ?string $planStartDate,
        ?int $branchId,
        float $lockerAmount = 0,
        ?string $discountType = null,
        float $discountValue = 0,
        float $paidAmount = 0
    ): array {
        $branch = Branch::select('fixed_billing_date')
            ->where('id', $branchId)
            ->first();

        $hasFixedBilling = ! empty($branch?->fixed_billing_date);

        $planPrice = \App\Support\LearnerShiftSupport::combinedPlanPrice(
            $planId,
            $planTypeIds,
            $planStartDate ?? Carbon::today()->toDateString(),
            $branchId
        );

        $discountAmount = 0;

        if ($discountType === 'percentage') {
            $discountAmount = (($planPrice + $lockerAmount) * $discountValue) / 100;
        } elseif ($discountType === 'amount') {
            $discountAmount = $discountValue;
        }

        $totalAmount = ($planPrice + $lockerAmount) - $discountAmount;
        $pendingAmount = $totalAmount - $paidAmount;

        return [
            'price' => (string) $planPrice,
            'locker_amount' => (string) $lockerAmount,
            'discount_amount' => (string) $discountAmount,
            'total_amount' => (string) $totalAmount,
            'paid_amount' => (string) $paidAmount,
            'pending_amount' => (string) $pendingAmount,
            'fixed_billing' => $hasFixedBilling,
        ];
    }
}
