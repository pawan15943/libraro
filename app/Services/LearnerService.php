<?php

namespace App\Services;

use App\Http\Controllers\NotificationSentController;
use App\Models\CustomerDetail;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerTransaction;
use App\Models\LearnerTransactionActivity;
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
            ->groupBy('seat_no')->where('status',1)
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


     public function learnerTransactionActivity($data)
    {


        LearnerTransactionActivity::create([
            'branch_id'      => $data['branchId'],
            'learner_id'     => $data['learner_id'],
            'date'           => now()->format('Y-m-d'),
            'transaction_id' => transaction_id(),
            'particular'     => $data['particular'],
            'payment_type'   => $data['payment_type'],
            'payment_mode'   => $data['payment_mode'] == 1 ? 'CASH' : 'OTHER',
            'amount'         => $data['amount'] ?? 0,
            'dr_cr'          => $data['dr_cr'],
        ]);
    }

     public function learnerTransactionAddUpdate($data)
    {
        // 1. Calculate new plan total
        $effectivePaid = $data['planPrice'] + $data['locker'] - $data['discount'];

        $transaction_date =
            $data['transaction_date']
            ?? optional($data['paid_date'])->format('Y-m-d')
            ?? optional($data['start_date'])->format('Y-m-d')
            ?? date('Y-m-d');


        // 2. Get old pending transactions
        $pendingTransactions = LearnerTransaction::where('learner_id', $data['learner_id'])
            ->where('pending_amount', '>', 0)
            ->orderBy('id', 'asc')
            ->get();

        $oldPendingTotal = $pendingTransactions->sum('pending_amount');

        $paidAmount = $data['paid_amount'];

        // 3. Apply payment to old pending first
        $pendingPaid = min($paidAmount, $oldPendingTotal);
        $remainingForNewPlan = $paidAmount - $pendingPaid;

        $remainingPendingPayment = $pendingPaid;

        foreach ($pendingTransactions as $tran) {
            if ($remainingPendingPayment <= 0) {
                break;
            }

            $tranPending = $tran->pending_amount;

            if ($remainingPendingPayment >= $tranPending) {
                // Fully clear this transaction
                $paidNow = $tranPending;
                $newPending = 0;
            } else {
                // Partially clear
                $paidNow = $remainingPendingPayment;
                $newPending = $tranPending - $paidNow;
            }

            $tran->update([
                'paid_amount'    => $tran->paid_amount + $paidNow,
                'pending_amount' => $newPending,
                'paid_date'      => $transaction_date,
                'is_paid'       =>1
            ]);

            $remainingPendingPayment -= $paidNow;
        }

        // 4. New plan payment
        $newPlanPaid = max(0, $remainingForNewPlan);
        $newPlanPending = $effectivePaid - $newPlanPaid;

        if ($newPlanPending < 0) {
            $newPlanPending = 0;
        }

        // 5. Create new plan transaction
        $learnerTransaction = LearnerTransaction::create([
            'learner_id'        => $data['learner_id'],
            'library_id'        => $data['library_id'],
            'branch_id'         => $data['branchId'],
            'learner_detail_id' => $data['learner_detail_id'],
            'total_amount'      => $effectivePaid,
            'paid_amount'       => $newPlanPaid,
            'pending_amount'    => $newPlanPending,
            'locker_amount'     => $data['locker'] ?? 0,
            'discount_amount'   => $data['discount'] ?? 0,
            'paid_date'         => $transaction_date,
            'is_paid'           => $data['is_paid'] ?? 0,
            'branch_id'         => $data['branchId'],
            'due_date'          => $data['due_date'],
            'transaction_id'    => transaction_id(),
        ]);

        // 6. Activity entries

        // Pending payment activity
        if ($pendingPaid > 0) {
            $activityData1 = [
                'branchId'    =>$data['branchId'],
                'learner_id'   => $data['learner_id'],
                'particular'   => $data['particular'] ?? 'Paid By Trans',
                'payment_type' => 'PENDING',
                'payment_mode' => $data['payment_mode'],
                'amount'       => $pendingPaid,
                'dr_cr'        => 'Cr',
            ];
            $this->learnerTransactionActivity($activityData1);
        }

        // New plan payment activity
        if ($newPlanPaid >=0) {
            $activityData2 = [
                'branchId'    =>$data['branchId'],
                'learner_id'   => $data['learner_id'],
                'particular'   => $data['particular'] ?? 'Paid By Trans',
                'payment_type' => $data['payment_type'],
                'payment_mode' => $data['payment_mode'],
                'amount'       => $newPlanPaid,
                'dr_cr'        => 'Cr',
            ];
            $this->learnerTransactionActivity($activityData2);
        }

        return $learnerTransaction;
    }

    public function processPlan(array $data)
    {
        DB::beginTransaction();

        try {

            /* ---------------------------------------------------------
            | 1. Find Learner
            ---------------------------------------------------------*/
            $customer = Learner::findOrFail($data['learner_id']);
             if (!$customer) {
                return [
                    'success' => false,
                    'message' => 'Learner not found.'
                ];
            }

            if (alreadyRenewed($customer->id)) {
                return [
                    'success' => false,
                    'message' => 'Already have plan in queue'
                ];
            }

            /* ---------------------------------------------------------
            | 2. Get last learner detail
            ---------------------------------------------------------*/
            $lastDetail = LearnerDetail::where('learner_id', $customer->id)
                ->orderBy('id', 'DESC')
                ->first();

            if (!$lastDetail) {
                throw new \Exception('Learner detail not found.');
            }

            /* ---------------------------------------------------------
            | 3. Plan Dates
            ---------------------------------------------------------*/
            $branchId=$data['branchId'];
            $plan_id = $data['plan_id'];
            $plan_type_id = $data['plan_type_id'];
            $seat_no = $data['seat_no'];
            $start_date = $data['start_date'] ? Carbon::parse($data['start_date']) :Carbon::parse($lastDetail->plan_end_date)->addDay();
            $endDate = getEndDate($plan_id, $start_date,$branchId);
            $learnerId=$customer->id;
            $planType = PlanType::findOrFail($plan_type_id);
            $hours = $planType->slot_hours;
            

            /* ---------------------------------------------------------
            | 4. Seat Availability
            ---------------------------------------------------------*/
            
            if ($customer->seat_no) {
                $result = checkAvailability($branchId,$seat_no,$learnerId,$plan_type_id, $plan_id, $start_date);

                if ($result['error']) {
                    return [
                        'success' => false,
                        'message' => $result['message']
                    ];
                }
            }

          
            

            /* ---------------------------------------------------------
            | 6. Payment Calculation
            ---------------------------------------------------------*/
            $planPrice = (float) ($data['plan_price'] ?? 0);
            $locker = (float) ($data['locker_amount'] ?? 0);
            $paid_amount = (float) ($data['paid_amount'] ?? 0);

            $discount = 0;
            if (($data['discount_type'] ?? null) == 'amount') {
                $discount = $data['discount_amount'];
            } elseif (($data['discount_type'] ?? null) == 'percentage') {
                $total = $planPrice + $locker;
                $discount = ($total * $data['discount_amount']) / 100;
            }

            $effectivePaid = $planPrice + $locker - $discount;
            $pending_amount = $effectivePaid - $paid_amount;
            $oldTotalPending=LearnerTransaction::where('learner_id',$customer->id)->where('pending_amount','>',0)->sum('pending_amount');

            $payment_mode = $data['payment_mode'];

            if ($payment_mode == 3) {
                $pending_amount = $paid_amount;
                $paid_amount = 0;
            }

            $is_paid = in_array($payment_mode, [1, 2]) ? 1 : 0;
            

            $transaction_date= $data['paid_date'] ?? null;
            $due_date = $data['due_date'] ?? null;

            /* ---------------------------------------------------------
            | 7. Status Calculation
            ---------------------------------------------------------*/
            $extendDay = getExtendDays($branchId);
            $inextendDate = Carbon::parse($endDate)->addDays($extendDay);
            $today = Carbon::today();

            if($customer->status==0 && ($start_date==$today)){
                 $status = 1;
            }else{
                 $status = $customer->status;
            }

            if ($lastDetail->plan_end_date < $today && $endDate->gt($today) && $is_paid == 1) {
                $detailstatus = 1;
            } elseif ($inextendDate > $today && $start_date <= $today) {
                $detailstatus = 1;
            } else {
                $detailstatus = 0;
            }

             if ( ($paid_amount > ($effectivePaid+$oldTotalPending)) || ($paid_amount == 0 && $payment_mode != 3)) {
                 return [
                        'success' => false,
                        'message' => 'Paid amount is not valid',
                    ];
            
            }
            if (($pending_amount > 0) &&  empty($due_date)  && $payment_mode != 3) {
                    return [
                        'success' => false,
                        'message' => 'Due date is required',
                    ];
            }
            
           if ($data['payment_type']) {
                $payment_type = $data['payment_type'];
            } 

           

            /* ---------------------------------------------------------
            | 8. Create Learner Detail
            ---------------------------------------------------------*/
            $learner_detail = LearnerDetail::create([
                'library_id' => $customer->library_id,
                'branch_id' => $branchId,
                'learner_id' => $customer->id,
                'plan_id' => $plan_id,
                'plan_type_id' => $plan_type_id,
                'plan_price_id' => $planPrice,
                'plan_start_date' => $start_date->format('Y-m-d'),
                'plan_end_date' => $endDate->format('Y-m-d'),
                'join_date' => $lastDetail->join_date,
                'hour' => $hours,
                'seat_no' => $seat_no,
                'payment_mode' => $payment_mode,
                'status' => $detailstatus,
                'is_paid' => $is_paid,
            ]);

            /* ---------------------------------------------------------
            | 9. Add Transaction AND Transaction Activity
            ---------------------------------------------------------*/
            $transactionData = [
                'planPrice' => $planPrice,
                'paid_amount' => $paid_amount,
                'locker' => $locker,
                'discount' => $discount,
                'start_date' => $start_date,
                'paid_date' => $data['paid_date'] ?? null,
                'is_paid' => $is_paid,
                'learner_detail_id' => $learner_detail->id,
                'learner_id' => $customer->id,
                'payment_type' => $data['payment_type'],
                'payment_mode' => $payment_mode,
                'due_date' => $data['due_date'] ?? null,
                'particular' => $data['particular'] ?? 'System',
                'library_id'=>$customer->library_id,
                'branchId'    =>$branchId,
            ];

           
            $this->learnerTransactionAddUpdate($transactionData);

            /* ---------------------------------------------------------
            | 10. Update Learner and Learner Detail Status
            ---------------------------------------------------------*/
             if ($customer->trashed()) {
                $customer->restore();
            }
            if ($status == 1) {
                $customer->hours = $hours;
                $customer->seat_no = $seat_no;
                $customer->status = $status;
                LearnerDetail::where('learner_id', $customer->id)
                    ->where('id', '!=', $learner_detail->id)
                    ->update(['status' => 0]);
            }

            if (!empty($data['locker_no'])) {
                $customer->locker_no = $data['locker_no'];
            }
           

            $customer->save();
            /* ---------------------------------------------------------
            | 11. Operation log add
            ---------------------------------------------------------*/
            if($data['payment_type']=='REACTIVE') {
                DB::table('learner_operations_log')->insert([
                    'learner_id' => $customer->id,
                    'learner_detail_id' => $learner_detail->id,
                    'library_id' => $customer->library_id,
                    'field_updated' => 'seat_no',
                    'old_value' =>$data['old_value'] ?? null,
                    'new_value' => $data['seat_no'] ?? null,
                    'updated_by' => $customer->library_id,
                    'branch_id' =>  $branchId,
                    'operation' => 'reactive',
                    'created_at' => now(),
                ]);
            }
             /* ---------------------------------------------------------
            | 12. Notofication Sent
            ---------------------------------------------------------*/

             try {
                if ($payment_type == "RENEW") {
                    $noti = new NotificationSentController;

                    if (autowabaNotificationActive()) {
                        \Log::info('autowabaNotificationActive');
                        $noti->autoMessage($customer->id, 'waba', 'renew-waba');
                    } else {
                        \Log::info('nowaba seond part RENEW');
                    }
                    if (autotextNotificationActive()) {
                        \Log::info('autotextNotificationActive');
                        $noti->autoMessage($customer->id, 'text', 'renew-sms');
                    } else {
                        \Log::info('no text seond part RENEW');
                    }
                }
                if ($payment_type == "UPGRADE") {
                    $noti = new NotificationSentController;

                    if (autowabaNotificationActive()) {
                        \Log::info('autowabaNotificationActive');
                        $noti->autoMessage($customer->id, 'waba', 'upgrade-waba');
                    } else {
                        \Log::info('nowaba seond part upgrade');
                    }
                    if (autotextNotificationActive()) {
                        \Log::info('autotextNotificationActive');
                        $noti->autoMessage($customer->id, 'text', 'upgrade-sms');
                    } else {
                        \Log::info('no text seond part upgrade');
                    }
                }
            } catch (\Throwable $e) {
                // Log the error (won't break your main code)
                \Log::error('Notification sending failed: ' . $e->getMessage(), [

                    'exception' => $e
                ]);
            }


            DB::commit();

            return [
                'success' => true,
                'message' => 'Plan processed successfully',
                'learner_detail_id' => $learner_detail->id
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    
}
