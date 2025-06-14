<?php

namespace App\Services;

use App\Models\CustomerDetail;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\Plan;
use App\Models\PlanType;
use App\Models\Seat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LearnerService
{
    public function getRenewalStatus($customerId)
    {
        $today = Carbon::today()->format('Y-m-d');
        $futureDate = Carbon::today()->addDays(6)->format('Y-m-d');
       
       
        return LearnerDetail::where('learner_id', $customerId)
            ->whereBetween('plan_start_date', [$today, $futureDate])
            ->exists() ? 1 : 0;
    }

    public function getAvailableSeats()
    {
        $firstRecord = Hour::where('branch_id',getCurrentBranch())->first(); 

        if (!$firstRecord) return collect();

        $totalHour = $firstRecord->hour;
        $totalSeats = $firstRecord->seats;
       
        // Step 1: Get used hours for each seat
        $usedSeats = LearnerDetail::select('seat_no', DB::raw('SUM(hour) as used_hours'))
            ->whereNotNull('seat_no')
            ->groupBy('seat_no')
            ->pluck('used_hours', 'seat_no'); // [seat_no => used_hours]

        $availableSeats = collect();

        // Step 2: Loop through all seat numbers and apply logic
        for ($seatNo = 1; $seatNo <= $totalSeats; $seatNo++) {
            $usedHours = $usedSeats[$seatNo] ?? 0;

            if ($usedHours < $totalHour) {
                $availableSeats->push($seatNo);
            }
        }

        return $availableSeats;
    }

    public function getPlans()
    {
        return Plan::where('library_id', getLibraryData())->get();
    }

    public function getPlanTypes()
    {
        return PlanType::where('library_id', getLibraryData())->get();
    }

    public function getAvailableSeatsPlantype()
    {
        // Step 1: Get the total allowable hours for the current user’s library
        $firstRecord = Hour::where('library_id', getLibraryData())->first();
        $totalHour = $firstRecord ? $firstRecord->hour : null;
    
      
    
        // Initialize an array to hold seat numbers and their available plan types
        $seatPlanTypes = [];
         $total_seats=totalSeat();
        for($seatNo = 1; $seatNo <= $total_seats; $seatNo++) {
            // Step 3: Retrieve all bookings for the given seat
            $bookings =  Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
                ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
                ->where('learner_detail.seat_no', $seatNo)
                ->where('learners.status', 1)
                ->where('learner_detail.status', 1)
                ->where('learners.branch_id',getCurrentBranch())
                ->where('learner_detail.branch_id',getCurrentBranch())
                ->get(['learner_detail.plan_type_id', 'plan_types.start_time', 'plan_types.end_time', 'plan_types.slot_hours']);
    
            // Step 4: Retrieve all plan types
            $planTypes = PlanType::get();
           
    
            // Step 5: Initialize an array to store the plan_type_ids to be removed
            $planTypesRemovals = [];
    
            // Step 6: Calculate total booked hours for the seat
            $totalBookedHours = $bookings->sum('slot_hours');
            $nightseatBooked=LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.seat_no',$seatNo)->where('learner_detail.status',1)->where('plan_types.day_type_id',9)->exists();
            // Step 7: Determine conflicts based on plan_type_id and hours
            $planTypeId = null;
            if($totalBookedHours < 24){

                foreach ($bookings as $booking) {
                    foreach ($planTypes as $planType) {
                        if ($booking->start_time < $planType->end_time && $booking->end_time > $planType->start_time) {
                            $planTypesRemovals[] = $planType->id;
                        }
                    }
                }
            }
    
            if($totalBookedHours > 1){
                $planTypeId = PlanType::where('day_type_id', 8)->value('id') ?? 0;

            }
        
            if (!is_null($planTypeId)) {
                $planTypesRemovals[] = $planTypeId;
            
            }
            if($nightseatBooked){
                $planTypeid=LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.seat_no',$seatNo)->where('learner_detail.status',1)->where('plan_types.day_type_id',9)->value('plan_types.id') ?? 0;
                $planTypesRemovals[] = $planTypeid;
            }
            // Remove duplicate entries in planTypesRemovals
            $planTypesRemovals = array_unique($planTypesRemovals);
        
            // If total booked hours >= 16, all plan types should be removed
            $first_record = Hour::where('branch_id',getCurrentBranch())->first();
            $total_hour = $first_record ? $first_record->hour : null;

            if ($totalBookedHours >= $total_hour) {
                $planTypesRemovals = $planTypes->pluck('id')->toArray();
            }
          
            // Step 6: Filter out the plan_types that match the retrieved plan_type_ids
            $filteredPlanTypes = $planTypes->filter(function ($planType) use ($planTypesRemovals) {
                return !in_array($planType->id, $planTypesRemovals);
            })->map(function ($planType) {
                return ['id' => $planType->id, 'name' => $planType->name];
            })->values();
            // Step 10: Add the seat number and its available plan types to the array
            $seatPlanTypes[] = [
                'seat_no' => $seatNo,
                'seat_id' => $seatNo,
                'available_plan_types' => $filteredPlanTypes
            ];
        }
      
      
        // Return the seat numbers along with their available plan types as an array
        return $seatPlanTypes;
    }
    
    
}
