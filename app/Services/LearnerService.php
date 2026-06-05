<?php

namespace App\Services;

use App\Http\Controllers\NotificationSentController;
use App\Models\Branch;
use App\Models\CustomerDetail;
use App\Models\Exam;
use App\Models\Floor;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerTransaction;
use App\Models\LearnerTransactionActivity;
use App\Models\Plan;
use App\Models\PlanType;
use App\Models\Seat;
use App\Services\LearnerGiftDaysService;
use App\Services\TransactionActivityService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Log;
use Auth;


class LearnerService
{
    public function __construct(private TransactionActivityService $transactionActivityService)
    {
    }
    public function runDailyUpdate()
    {
        \Log::info('Learner Daily Status Cron Ran');
        $today = Carbon::today()->format('Y-m-d');

        DB::transaction(function () use ($today) {

            /*
            |--------------------------------------------------------------------------
            | 1️⃣ DEACTIVATE ONLY EXPIRED NORMAL DETAILS
            |--------------------------------------------------------------------------
            */
            
            DB::statement("
                UPDATE learner_detail ld
                JOIN learners l ON l.id = ld.learner_id
                SET ld.status = 0
                WHERE l.no_expiry = 0
                AND ld.status = 1
            ");


            /*
            |--------------------------------------------------------------------------
            | 2️⃣ ACTIVATE ONLY THE LATEST VALID PLAN PER LEARNER
            |--------------------------------------------------------------------------
            | Covers:
            | - Renewal overrides extension
            | - Future booking activates when start date matches
            | - Only ONE active per learner
            |--------------------------------------------------------------------------
            */
           DB::statement(" 
           UPDATE learner_detail ld 
           JOIN ( 
            SELECT ld1.id FROM learner_detail ld1 
                JOIN branches b ON b.id = ld1.branch_id 
                    JOIN ( 
                        SELECT learner_id, MAX(plan_start_date) as latest_start 
                            FROM learner_detail ld2 
                                JOIN learners l2 ON l2.id = ld2.learner_id 
                                JOIN branches b2 ON b2.id = ld2.branch_id WHERE l2.no_expiry = 0 
                                AND ld2.plan_start_date <= ?
                                AND DATE_ADD(ld2.plan_end_date, INTERVAL b2.extend_days DAY) > ? 
                                GROUP BY learner_id ) 
                                latest 
                                ON latest.learner_id = ld1.learner_id AND latest.latest_start = ld1.plan_start_date ) active_ids ON active_ids.id = ld.id SET ld.status = 1 ",
                                 [$today, $today]);

          

            /*
            |--------------------------------------------------------------------------
            | 3️⃣ SYNC LEARNERS TABLE
            |--------------------------------------------------------------------------
            */

            

            DB::statement("
                UPDATE learners l
                SET l.status = 1
                WHERE l.no_expiry = 0
                AND EXISTS (
                    SELECT 1 FROM learner_detail ld
                    WHERE ld.learner_id = l.id
                    AND ld.status = 1
                )
            ");

            DB::statement("
               UPDATE learners l
                SET l.status = 0
                WHERE l.no_expiry = 0
                AND NOT EXISTS (
                    SELECT 1 FROM learner_detail ld
                    WHERE ld.learner_id = l.id
                    AND ld.status = 1
                )
            ");

            

        });

        /*
        |--------------------------------------------------------------------------
        | 4️⃣ PROCESS VIP / NON-EXPIRED OUTSIDE MAIN TRANSACTION
        |--------------------------------------------------------------------------
        */

        $this->processNonExpired();

        return true;
    }

    private function processNonExpired()
    {
        $today = Carbon::today();

        Learner::where('no_expiry', 1)
            ->select('id')
            ->chunkById(1000, function ($learners) use ($today) {

                foreach ($learners as $learner) {

                    // Only check latest detail
                    $lastDetail = LearnerDetail::where('learner_id', $learner->id)
                        ->orderByDesc('plan_start_date')
                        ->first();

                    if (!$lastDetail) {
                        continue;
                    }

                    // 🔥 IMPORTANT CONDITION
                    if ($lastDetail->status != 1) {
                        continue; // manually inactive, skip
                    }

                    $endDate = Carbon::parse($lastDetail->plan_end_date);

                    if ($endDate->lt($today)) {
                        $this->generateNextCycle($lastDetail, $today);
                    }
                }
            });
    }

    private function generateNextCycle($detail, $today)
    {
        $branchId = $detail->branch_id;

        $startDate = Carbon::parse($detail->plan_end_date)->addDay();

        $newEndDate = getEndDate($detail->plan_id, $startDate, $branchId);

        DB::transaction(function () use ($detail, $startDate, $newEndDate) {

            // deactivate old
            LearnerDetail::where('id', $detail->id)
                ->update(['status' => 0]);

            // create new active cycle
           $learner_detail = LearnerDetail::create([
                'library_id'      => $detail->library_id,
                'branch_id'       => $detail->branch_id,
                'learner_id'      => $detail->learner_id,
                'plan_id'         => $detail->plan_id,
                'plan_type_id'    => $detail->plan_type_id,
                'plan_price_id'   => $detail->plan_price_id,
                'plan_start_date' => $startDate->format('Y-m-d'),
                'plan_end_date'   => $newEndDate->format('Y-m-d'),
                'join_date'       => $detail->join_date,
                'hour'            => $detail->hour,
                'seat_no'         => $detail->seat_no,
                'payment_mode'    => 3,
                'status'          => 1,
                'is_paid'         => 0,
            ]);

             $lastTransaction = LearnerTransaction::where('learner_detail_id', $detail->id)
                    ->latest()
                    ->first();
                // price Get
                $hasFixedBilling = Branch::where('id', $learner_detail->branch_id)
                    ->whereNotNull('fixed_billing_date')
                    ->exists();

                if ($hasFixedBilling) {

                    $planPrice= getBillingCyclePrice($detail->plan_id,$detail->plan_type_id,$startDate,$learner_detail->branch_id);

                } else {

                    $planPrice= getPlanPrice($detail->plan_id,$detail->plan_type_id,$learner_detail->branch_id);
                }
                 $effectivePaid = $planPrice + $lastTransaction->locker_amount - $lastTransaction->discount_amount;

                // add new transaction entry
                LearnerTransaction::create([
                    'learner_id'        => $lastTransaction->learner_id,
                    'library_id'        => $lastTransaction->library_id,
                    'branch_id'         => $lastTransaction->branch_id,
                    'learner_detail_id' => $learner_detail->id,
                    'total_amount'      => $effectivePaid,
                    'paid_amount'       => 0 ,
                    'pending_amount'    => $effectivePaid,
                    'locker_amount'     => $lastTransaction->locker_amount ?? 0,
                    'discount_amount'   => $lastTransaction->discount_amount ?? 0,
                    'is_paid'           => 0,
                    'due_date'          => date('Y-m-d'),
                    'transaction_id'    => transaction_id(),
                    'paid_date'          => date('Y-m-d'),
                ]);
        });
    }

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
        $this->transactionActivityService->learnerTransactionActivity($data);
    }

     public function learnerTransactionAddUpdate($data)
    {
        return $this->transactionActivityService->learnerTransactionAddUpdate($data);
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
            
            $start_date = Carbon::parse($data['start_date'] ?? $lastDetail->plan_end_date)->addDay();

            $endDate = getEndDate($plan_id, $start_date,$branchId);
            $learnerId=$customer->id;
            $planType = PlanType::findOrFail($plan_type_id);
            $hours = $planType->slot_hours;
            

            /* ---------------------------------------------------------
            | 4. Seat Availability
            ---------------------------------------------------------*/
            
            if (!empty($data['seat_no'])) {
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

            if($customer->status==0 && ($start_date<=$today)){
                 $status = 1;
            }else{
                 $status = $customer->status;
            }
 
            

            if (Carbon::parse($lastDetail->plan_end_date) < $today && $endDate->gt($today) && $is_paid == 1) {
                
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
            if ($detailstatus == 1) {
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
                // 'learner_detail_id' => $learner_detail->id
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function processLearnerStore(array $data)
    {
       
        DB::beginTransaction();

        try {

           

            /* ---------------------------------------------------------
            | 3. Plan Dates
            ---------------------------------------------------------*/
            $branchId=$data['branchId'];
            $plan_id = $data['plan_id'];
            $plan_type_id = $data['plan_type_id'];
            $seat_no = $data['seat_no'];
            
            $start_date = Carbon::parse($data['start_date']);

            $endDate = getEndDate($plan_id, $start_date,$branchId);
            $learnerId=null;
            $planType = PlanType::findOrFail($plan_type_id);
            $hours = $planType->slot_hours;
            

            /* ---------------------------------------------------------
            | 4. Seat Availability
            ---------------------------------------------------------*/
             // future booking and non expired seat check
            $exists_future = LearnerDetail::join('plan_types as existing_pt', 'learner_detail.plan_type_id', '=', 'existing_pt.id')
                ->where('learner_detail.branch_id', $branchId)
                ->where('learner_detail.seat_no', $seat_no)
                ->where('learner_detail.plan_start_date', '>', date('Y-m-d'))
                ->where('learner_detail.plan_start_date', '<=', $endDate->format('Y-m-d'))
                ->where('learner_detail.plan_end_date', '>=', $start_date->format('Y-m-d'))
                ->where(function ($query) use ($plan_type_id) {

                    $query->whereExists(function ($sub) use ($plan_type_id) {

                        $sub->select(\DB::raw(1))
                            ->from('plan_types as new_pt')
                            ->where('new_pt.id', $plan_type_id)
                            ->whereRaw('existing_pt.start_time < new_pt.end_time')
                            ->whereRaw('existing_pt.end_time > new_pt.start_time');
                    });

                })
                ->exists();

            if ($exists_future && $data['learner_data']['no_expiry'] == 1) {
                throw new \Exception('This seat already has a future booking that overlaps with the selected time.');
            }
            
           if (!empty($data['seat_no'])) {
                $result = checkAvailability($branchId,$seat_no,$learnerId,$plan_type_id, $plan_id, $start_date);

                 if ($result['error']) {
                    throw new \Exception($result['message']);
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
           

            if($start_date<=$today){
                 $status = 1;
            }else{
                 $status = 0;
            }
           

            if ($endDate->gt($today) && $start_date <= $today && $is_paid == 1) {
               
                $detailstatus = 1;
            } elseif ($inextendDate >= $today && $start_date <= $today) {
               
                $detailstatus = 1;
            } else {
               
                $detailstatus = 0;
            }
          

            if($start_date->lt($today) && ($detailstatus == 0 || $status == 0) && $data['learner_data']['no_expiry']==1){
                throw new \Exception('You can only select a back date within your plan duration.');
            }
           

             if ( ($paid_amount > ($effectivePaid)) ) {
                 throw new \Exception('Paid amount is not valid');
            
            }
            if (($pending_amount > 0) &&  empty($due_date)  && $payment_mode != 3) {
                    throw new \Exception('Due date is required');
            }
            
           if ($data['payment_type']) {
                $payment_type = $data['payment_type'];
            } 
            

            /* ---------------------------------------------------------
            | 1. Find Learner
            ---------------------------------------------------------*/
            $customer = Learner::create($data['learner_data']);
             if (!$customer) {
                return [
                    'success' => false,
                    'message' => 'Learner not found.'
                ];
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
                'join_date' => $start_date->format('Y-m-d'),
                'hour' => $hours,
                'seat_no' => $seat_no,
                'payment_mode' => $payment_mode,
                'status' => $detailstatus,
                'is_paid' => $is_paid,
                'exam_id'=>$data['exam_id'] ?? null,
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
            
            if ($detailstatus == 1) {
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
            | 12. Notofication Sent
            ---------------------------------------------------------*/

             try {
                if($data['particular']=='Website book form'){
                    $noti = new NotificationSentController;

                    if (autowabaNotificationActive()) {
                        \Log::info('autowabaNotificationActive');
                        $noti->autoMessage($customer->id, 'waba', 'book-waba');
                    } else {
                        \Log::info('nowaba seond part swap');
                    }
                    if (autotextNotificationActive()) {
                        \Log::info('autotextNotificationActive');
                        $noti->autoMessage($customer->id, 'text', 'book-sms');
                    } else {
                        \Log::info('no text seond part swap');
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
                'message' => 'Learner created successfully!',
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
    
    
    public function getLearnerDetails($learnerId)
    {

    
        $branchId = getCurrentBranch();

        $learner = Learner::withTrashed()->find($learnerId);

        if(!$learner){
            throw new \Exception("Learner not found");
        }

        $detail = LearnerDetail::withTrashed()->with([
            'plan',
            'planType'
        ])
        ->where('learner_id',$learnerId)
        ->latest()
        ->first();

        if (!$detail) {
            throw new \Exception("Learner detail not found");
        }

        $transaction = LearnerTransaction::withTrashed()->where('learner_detail_id',$detail->id)
            ->latest()
            ->first();

        $transaction_all = LearnerTransaction::withTrashed()->where('learner_id', $learnerId)
            ->with([
                'learnerDetail.plan',
                'learnerDetail.planType',
            ])
            ->orderBy('id')
            ->get();

        $transaction_all_activity= LearnerTransactionActivity::where('learner_id',$learnerId)->get();

        $all_detail=LearnerDetail::with([
            'plan',
            'planType'
        ])
        ->where('learner_id',$learnerId)->get();


        $operation = optional(getLearnerOperation($detail->id))->operation;    
        $planStatus =getPlanStatusDetails($detail->plan_end_date);
        if($operation == 'closeSeat'){
            $status='Closed';
        }elseif($operation == 'deleteSeat' && $learner->deleted_at !=null){
            $status='Deleted';
        }else{
            $status = strip_tags(
                getUserStatusWithSpan($detail->plan_end_date,$learner->id)
            );
        }
    
        
    
        if($operation == 'closeSeat'){
            $mainstatus='Closed';
        }elseif($operation == 'deleteSeat' && $learner->deleted_at !=null){
            $mainstatus='Deleted';
        }elseif($planStatus['diff_extend_day'] < 0){
            $mainstatus='Expired';
        }else{
            $mainstatus='Active';
        }

        // $fetchPlanType=PlanType::where('id',$detail->planType->id)->select('id','name','start_time','end_time')->first();
        $fetchPlanType = $detail->planType
        ? [
            'id' => $detail->planType->id,
            'name' => $detail->planType->name,
            'start_time' => $detail->planType->start_time,
            'end_time' => $detail->planType->end_time,
        ]
        : null;
       $total_overall = $this->amountSatelment($learnerId);

        if($detail->exam_id){
            $exam=Exam::where('id',$detail->exam_id)->select('id','name')->first();
        }

        $birthStatus = false;
        if (!empty($learner->dob)) {
            try {
                $dob = \Carbon\Carbon::parse($learner->dob);
                $today = now();
                $birthStatus = (int) $dob->month === (int) $today->month
                    && (int) $dob->day === (int) $today->day;
            } catch (\Throwable $e) {
                $birthStatus = false;
            }
        }
        

        return [

            'personal_info'=>[
                'learner_no'=>$learner->learner_no,
                'seat_no'=>(string)$learner->seat_no ?? "GEN",
                'seat_with_floor'=>$learner->seat_no ? (string)getSeatDisplayByMainNo($learner->seat_no) : "GEN",
                'name'=>$learner->name,
                'mobile'=>$learner->mobile,
                'email'=>$learner->email ? $learner->email : '',
                'birth_status'=>$birthStatus,
                'dob'=>$learner->dob ?? '',
                'father_name'=>$learner->father_name ?? '',
                'profile_picture'=>$learner->profile_picture 
                                ? asset($learner->profile_picture) 
                                : '',
               
               
                
            ],

            'detail_info'=>[
                'plan'=>$detail->plan->name ?? '',
                'plan_type'=>$detail->planType->name ?? '',
                'plan_id' => optional($detail->plan)->id ?? '',
                'plan_type_id' => optional($detail->planType)->id ?? null,
                
                'price'=>$detail->plan_price_id,
                'monthdays'=>$detail->plan->monthdays ?? 'Calendar wise',
                'start_date'=>$detail->plan_start_date,
                'end_date'=>$detail->plan_end_date,
                'start_time'=>$detail->planType->start_time ?? '',
                'end_time'=>$detail->planType->end_time ?? '',
                'status'=>$status,
                'mainstatus'=>$mainstatus,
                'frozen_status'=>$learner->frozen_status,
                'deleted_at'=>$learner->deleted_at ?? '',
                'locker'=>$learner->locker_no ? 'Yes' : 'No' ,
                'locker_no'=>(string)$learner->locker_no ?? '',
                'days_left'=>$planStatus['diff_in_days'],
                'extend_days_left'=>$planStatus['diff_extend_day'],
                'plan_days' => $detail->plan
                    ? getChargeableDays(
                        $detail->plan->id,
                        $detail->plan_start_date,
                        $branchId
                    )['chargeable_days'] ?? 0
                    : 0,               
                'plantype_detail'=>$fetchPlanType ?? null,
                'total_gift_days' => app(LearnerGiftDaysService::class)->getTotalGiftDays((int) $learnerId),
               
            ],

            'payment_information'=>[
                'total_amount'=>(string) $transaction->total_amount,
                'paid_amount'=>(string) $transaction->paid_amount,
                'pending_amount'=>(string) $transaction->pending_amount,
                'paid_date'=>$transaction->paid_date ?? '',
                'payment_mode'=>$detail->payment_mode,
                 'locker_amount'=>(string) $transaction->locker_amount,
                'discount'=>$transaction->discount_amount ?? '0',
                'token_money'=>(string) $transaction->token_money ?? '0',
                'miscellaneous'=>(string) $transaction->miscellaneous ?? '0',
                'pending_refund'=>(string) $transaction->refund ?? '0',
                'due_date'=>$transaction->due_date ?? '',
                'transaction'=>$transaction->transaction_id ?? '',
               
            ],

            'other_details'=>[
                'alternate_mobile'=>$learner->alternate_mobile ?? '',
                'id_proof_name'=>$learner->id_proof_name,
                'id_proof_image'=> $learner->id_proof_file 
                                ? asset($learner->id_proof_file) 
                                : '',
                'id_proof_no'=>$learner->id_proof_number ?? '',
               
                'address'=>$learner->address ?? '',
                'remark'=>$learner->remark ?? '',
                'no_expiry'=>$learner->no_expiry ?? 0,
                'sended_message_type'=>$learner->sended_message_type ?? 'no',
                'exam_id'=>$detail->exam_id ?? '',
                'exam_name'=>$exam->name ?? ''
            ],

            'setlment_amount' => [
                'overall_total_amt'     => (string)$total_overall->overall_total_amt,
                'overall_paid_amount'   => (string)$total_overall->overall_paid_amount,
                'overall_pending_sum'   => (string)$total_overall->overall_pending_sum,
                'total_refund_pending'  => (string)$total_overall->total_refund_pending,
            ],

            'all_transaction' => $transaction_all->values()->map(function ($tx, $index) {
                $ld = $tx->learnerDetail;

                return [
                    'total_amount' => (string) ($tx->total_amount ?? '0'),
                    'paid_amount' => (string) ($tx->paid_amount ?? '0'),
                    'pending_amount' => (string) ($tx->pending_amount ?? '0'),
                    'paid_date' => $tx->paid_date ?? '',
                    'locker_amount' => (string) ($tx->locker_amount ?? '0'),
                    'discount' => $tx->discount_amount ?? '0',
                    'token_money' => (string) ($tx->token_money ?? '0'),
                    'miscellaneous' => (string) ($tx->miscellaneous ?? '0'),
                    'pending_refund' => (string) ($tx->refund ?? '0'),
                    'due_date' => $tx->due_date ?? '',
                    'transaction' => $tx->transaction_id ?? '',
                    'seat_type' => $index === 0 ? 'BOOK SEAT' : 'RE-NEW SEAT',
                    'plan_start_date' => $ld?->plan_start_date ?? '',
                    'plan_end_date' => $ld?->plan_end_date ?? '',
                    'plan' => $ld?->plan?->name ?? '',
                    'plan_type' => $ld?->planType?->name ?? '',
                    'transaction_status' => $ld && (int) $ld->payment_mode === 3 ? 'Paylater' : 'Success',
                ];
            }),

            'all_transaction_activity'=>$transaction_all_activity->map(function($txn){

                return [
                    'transaction_id'=>$txn->transaction_id ?? '',
                    'amount'=>$txn->amount?? '',
                    'particular'=>$txn->particular,
                    'mode'=>$txn->payment_mode,
                    'date'=>$txn->date,
                    'dr_cr'=>$txn->dr_cr
                ];

            }),
            'all_detail'=>$all_detail->map(function($all_deatil){

                return [
                    'plan'=>$all_deatil->plan->name ?? '',
                    'plan_type'=>$all_deatil->planType->name ?? '',
                    'price'=>$all_deatil->plan_price_id,
                    'duration'=>$all_deatil->plan->monthdays ?? '',
                    'start_date'=>$all_deatil->plan_start_date,
                    'end_date'=>$all_deatil->plan_end_date,
                    'start_time'=>$all_deatil->planType->start_time ?? '',
                    'end_time'=>$all_deatil->planType->end_time ?? '',
                  
                ];

            })

          

        ];
    }

    public function getLearnersList($filters = [])
    {
        $branchId = getCurrentBranch();

        $latestDetail = LearnerDetail::withTrashed()
            ->selectRaw('MAX(id) as id')
            ->groupBy('learner_id');

        $learners = Learner::withTrashed()
            ->select([
                'id',
                'learner_no',
                'name',
                'mobile',
                'email',
                'dob',
                'profile_picture',
                'branch_id',
                'frozen_status',
                'status',
                'deleted_at',
            ]);

        $query = LearnerDetail::withTrashed()

            ->joinSub($latestDetail,'latest',function($join){
                $join->on('learner_detail.id','=','latest.id');
            })

            ->joinSub($learners,'learners',function($join){
                $join->on('learners.id','=','learner_detail.learner_id');
            })

            ->leftJoin('plans','plans.id','=','learner_detail.plan_id')

            ->leftJoin('plan_types','plan_types.id','=','learner_detail.plan_type_id')

            ->where('learner_detail.branch_id',$branchId);

        /* -----------------------------
        SEARCH
        ------------------------------*/

        if (!empty($filters['search'])) {

            $search = $filters['search'];
            $encryptdata = encryptData($search);

            $query->where(function ($q) use ($search, $encryptdata) {

                $q->where('learners.name','LIKE',"%{$search}%")
                ->orWhere('learners.mobile','LIKE',"%{$encryptdata}%")
                ->orWhere('learners.learner_no','LIKE',"%{$search}%")
                ->orWhere('learner_detail.seat_no','LIKE',"%{$search}%")
                ->orWhere('learners.email',$encryptdata);

            });
        }

        /* -----------------------------
        STATUS FILTER
        ------------------------------*/

        $statusFilter = $filters['status'] ?? 'all';

        if (!in_array($statusFilter, ['all', 'deleted'], true)) {
            $query->whereNull('learners.deleted_at');
        }

        if (!empty($filters['status'])) {

            switch ($statusFilter) {
                case 'all':
                break;

                case 'active':
                    $extendDays = getExtendDays($branchId);

                    $query->where('learner_detail.status',1)
                        ->whereDate(
                            'learner_detail.plan_end_date',
                            '>=',
                            now()->subDays($extendDays)
                        );

                break;

                case 'about_to_expire':

                    $query->where('learner_detail.status',1)
                        ->whereBetween('learner_detail.plan_end_date',[now(), now()->addDays(5)]);

                break;

                case 'extended':

                    $extendDays = getExtendDays($branchId);

                    $query->whereDate('learner_detail.plan_end_date','<',now())
                        ->whereDate(
                            'learner_detail.plan_end_date',
                            '>=',
                            now()->subDays($extendDays)
                        );

                break;

                case 'future':

                    $extendDays = getExtendDays($branchId);

                    $query->whereDate('learner_detail.plan_start_date','>',now())
                        ->whereNotExists(function ($sub) use ($branchId, $extendDays) {
                            $sub->select(DB::raw(1))
                                ->from('learner_detail as active_detail')
                                ->whereColumn('active_detail.learner_id', 'learner_detail.learner_id')
                                ->where('active_detail.branch_id', $branchId)
                                ->where('active_detail.status', 1)
                                ->whereDate('active_detail.plan_start_date', '<=', now())
                                ->whereDate('active_detail.plan_end_date', '>=', now()->subDays($extendDays));
                        });

                break;

                case 'expired':

                    $query->whereDate('learner_detail.plan_end_date','<',now())->where('learners.status',0);

                break;

                case 'closed':

                    $query->where('learner_detail.status',0)->join('learner_operations_log as op', function ($join) {
                            $join->on('op.learner_detail_id', '=', 'learner_detail.id');
                        })
                        ->where('op.operation', 'closeSeat');

                break;

                case 'deleted':

                    $query->whereNotNull('learners.deleted_at');

                break;

                case 'pending_payment':

                    $query->whereExists(function ($q) {
                        $q->select(\DB::raw(1))
                            ->from('learner_transactions')
                            ->whereColumn('learner_transactions.learner_id', 'learners.id')
                            ->where('learner_transactions.pending_amount', '>', 0);
                    });

                break;

            }
        }

        if (!empty($filters['plan_type_id']) && is_array($filters['plan_type_id'])) {
            $planTypeIds = array_values(array_filter(array_map('intval', $filters['plan_type_id'])));
            if (!empty($planTypeIds)) {
                $query->whereIn('learner_detail.plan_type_id', $planTypeIds);
            }
        }

        /* -----------------------------
        SELECT
        ------------------------------*/

        $query->select([

            'learners.id',
            'learners.learner_no',
            'learners.name',
            'learners.mobile',
            'learners.dob',
            'learners.profile_picture',
            'learners.branch_id',
            'learners.frozen_status',

            'learner_detail.seat_no',
            'learner_detail.plan_start_date',
            'learner_detail.plan_end_date',

            'plans.name as plan_name',
            'plans.id as plan_id',
            'plan_types.name as plan_type',
            'learner_detail.id as learner_detail_id',
            'learners.deleted_at',
            DB::raw('(
                SELECT learner_transactions.transaction_id
                FROM learner_transactions
                WHERE learner_transactions.learner_id = learners.id
                ORDER BY learner_transactions.id DESC
                LIMIT 1
            ) as transaction_id')

        ]);

        $sortBy = $filters['sort_by'] ?? 'seat_no';
        $sortOrder = strtolower($filters['sort_order'] ?? 'asc');
        $sortOrder = in_array($sortOrder, ['asc', 'desc'], true) ? $sortOrder : 'asc';

        $sortableColumns = [
            'seat_no' => 'learner_detail.seat_no',
            'name' => 'learners.name',
            'expire_date' => 'learner_detail.plan_end_date',
            'gen' => 'learner_detail.seat_no',
        ];

        $sortColumn = $sortableColumns[$sortBy] ?? 'learner_detail.seat_no';

        if ($sortBy === 'seat_no') {
            // Keep null seats (general) at the end for both asc/desc and sort seat numbers numerically.
            $learners = $query
                ->orderByRaw('CASE WHEN learner_detail.seat_no IS NULL OR learner_detail.seat_no = "" THEN 1 ELSE 0 END ASC')
                ->orderByRaw('CAST(learner_detail.seat_no AS UNSIGNED) '.$sortOrder)
                ->paginate(20);
        } elseif ($sortBy === 'gen') {
            // Dedicated general-seat sort (DB general seat = NULL).
            // asc: general first, desc: general last.
            if ($sortOrder === 'asc') {
                $learners = $query
                    ->orderByRaw('CASE WHEN learner_detail.seat_no IS NULL OR learner_detail.seat_no = "" THEN 0 ELSE 1 END ASC')
                    ->orderByRaw('CAST(learner_detail.seat_no AS UNSIGNED) ASC')
                    ->paginate(20);
            } else {
                $learners = $query
                    ->orderByRaw('CASE WHEN learner_detail.seat_no IS NULL OR learner_detail.seat_no = "" THEN 1 ELSE 0 END ASC')
                    ->orderByRaw('CAST(learner_detail.seat_no AS UNSIGNED) DESC')
                    ->paginate(20);
            }
        } else {
            $learners = $query
                ->orderBy($sortColumn, $sortOrder)
                ->paginate(20);
        }

        /* -----------------------------
        FORMAT RESPONSE
        ------------------------------*/

        $learners->getCollection()->transform(function($learner){

            $daysLeft = \Carbon\Carbon::parse($learner->plan_end_date)->diffInDays(now(),false);

            $operation = optional(getLearnerOperation($learner->learner_detail_id))->operation;    
            $planStatus =getPlanStatusDetails($learner->plan_end_date);
            if($operation == 'closeSeat'){
                    $status='Closed';
            }elseif($operation == 'deleteSeat' && $learner->deleted_at !=null){
                $status='Deleted';
            }else{
                    $status = strip_tags(
                    getUserStatusWithSpan($learner->plan_end_date,$learner->id)
                );
            }
        
            
        
            if($operation == 'closeSeat'){
                $mainstatus='Closed';
            }elseif($operation == 'deleteSeat' && $learner->deleted_at !=null){
                $mainstatus='Deleted';
            }elseif($planStatus['diff_extend_day'] < 0){
                $mainstatus='Expired';
            }else{
                $mainstatus='Active';
            }

            $birthStatus = false;
            if (!empty($learner->dob)) {
                try {
                    $dob = \Carbon\Carbon::parse($learner->dob);
                    $today = now();
                    $birthStatus = (int) $dob->month === (int) $today->month
                        && (int) $dob->day === (int) $today->day;
                } catch (\Throwable $e) {
                    $birthStatus = false;
                }
            }

            return [

                'id'=>$learner->id,
                'learner_no'=>$learner->learner_no,
                'name'=>$learner->name,
                'mobile'=>decryptData($learner->mobile),
                'dob'=>$learner->dob,
                'birth_status'=>$birthStatus,
                'seat_no' => $learner->seat_no !== null ? (int) $learner->seat_no : 0,

                'profile_picture' => $learner->profile_picture 
                ? asset($learner->profile_picture) 
                : '',

                'plan'=>$learner->plan_name ?? '',
                'plan_type'=>$learner->plan_type ?? '',
                'plan_days' => getChargeableDays($learner->plan_id, $learner->plan_start_date, $learner->branch_id)['chargeable_days'] ?? 0,
                'plan_end_date'=>$learner->plan_end_date ?? '',

                'days_left'=>$planStatus['diff_in_days'],
                'extend_days_left'=>$planStatus['diff_extend_day'],

                'status'=>$status,
                'mainstatus'=>$mainstatus,
                'frozen_status'=>$learner->frozen_status,
                'deleted_at'=>$learner->deleted_at ?? '',
                'transaction_id'=>$learner->transaction_id ?? '',
                'payment'=>learnerTransactionStatus($learner->id),
                
                
                
            ];

        });

        return $learners;
    }

    public function getSeatMapDetails(?int $branchId = null, ?int $planTypeId = null, ?int $planTypeStatus = null)
    {
        $branchId = $branchId ?: getCurrentBranch();
        
        $planTypeStatuses = [
            ['id' => 1, 'name' => 'booked', 'color' => '#006E89'],
            ['id' => 2, 'name' => 'available', 'color' => '#60B03E'],
            ['id' => 3, 'name' => 'about to expire', 'color' => '#FF0000'],
            ['id' => 4, 'name' => 'extended', 'color' => '#AB0000'],
            ['id' => 5, 'name' => 'pending payment', 'color' => '#2E3ECD'],
            ['id' => 6, 'name' => 'paylater', 'color' => '#073B5B'],
            ['id' => 7, 'name' => 'extra paid', 'color' => '#00A1C8'],
        ];

        if ($planTypeId) {
            $planTypes = PlanType::withoutGlobalScopes()
                ->where('id', $planTypeId)
                ->whereNull('deleted_at')
                ->get(['id', 'name', 'start_time', 'end_time']);

            if ($planTypes->isEmpty()) {
                throw new \Exception('selected shift not avialable');
            }
        } else {
            $planTypes = PlanType::withoutGlobalScopes()
                ->where('branch_id', $branchId)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->get(['id', 'name', 'start_time', 'end_time']);
        }

        $bookingDetails = LearnerDetail::withTrashed()
            ->with(['learner', 'plan', 'planType'])
            ->where('branch_id', $branchId)
            ->where('status', 1)
            ->whereHas('learner', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                    ->where('status', 1)
                    ->whereNull('deleted_at');
            })
            ->orderBy('seat_no')
            ->orderBy('plan_type_id')
            ->get();

        $transactions = LearnerTransaction::withTrashed()
            ->whereIn('learner_id', $bookingDetails->pluck('learner_id')->filter()->unique()->values())
            ->selectRaw('learner_id, SUM(pending_amount) as pending_amount, SUM(refund) as extra_amount')
            ->groupBy('learner_id')
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('learner_id');

        $numberedDetails = $bookingDetails
            ->filter(fn ($detail) => ! empty($detail->seat_no))
            ->groupBy(fn ($detail) => (int) $detail->seat_no);

        $numbered = $this->formatNumberedSeatMap($branchId, $planTypes, $numberedDetails, $transactions, $planTypeId, $planTypeStatus);
        $general = $this->formatGeneralSeatMap($branchId, $planTypes, $bookingDetails, $transactions, $planTypeId, $planTypeStatus);
        $sortStatusRow = collect($planTypeStatuses)->firstWhere('id', $planTypeStatus);
        $sortStatus = $sortStatusRow['name'] ?? null;

        if ($sortStatus) {
            $numbered = $this->sortSeatMapByPlanTypeStatus($numbered, $sortStatus);
            $general = $this->sortSeatMapByPlanTypeStatus($general, $sortStatus);
        }

        return [
            'plan_type_status' => $planTypeStatuses,
            'numbered' => $numbered,
            'general' => $general,
        ];
    }

    private function sortSeatMapByPlanTypeStatus(array $floors, string $status): array
    {
        return collect($floors)
            ->map(function ($floor) use ($status) {
                $floor['seats'] = collect($floor['seats'] ?? [])
                    ->values()
                    ->map(function ($seat) use ($status) {
                        $seat['plantype'] = collect($seat['plantype'] ?? [])
                            ->values()
                            ->map(function ($planType, $planTypeIndex) use ($status) {
                                $planType['_sort_index'] = $planTypeIndex;
                                $planType['_sort_status'] = ($planType['plan_type_status'] ?? null) === $status ? 0 : 1;

                                return $planType;
                            })
                            ->sortBy([
                                ['_sort_status', 'asc'],
                                ['_sort_index', 'asc'],
                            ])
                            ->map(function ($planType) {
                                unset($planType['_sort_status'], $planType['_sort_index']);

                                return $planType;
                            })
                            ->values()
                            ->all();

                        return $seat;
                    })
                    ->values()
                    ->all();

                return $floor;
            })
            ->values()
            ->all();
    }

    private function formatNumberedSeatMap($branchId, $planTypes, $detailsBySeat, $transactions, ?int $planTypeId = null, ?string $planTypeStatus = null)
    {
        $seatRows = collect(generateSeatNumbers2((int) $branchId));

        $floors = Floor::withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->orderBy('floor_no')
            ->get()
            ->keyBy('floor_no');

        return $seatRows
            ->groupBy(fn ($seat) => $seat['floor_no'] ?? 0)
            ->map(function ($seats, $floorNo) use ($planTypes, $detailsBySeat, $transactions, $floors) {
                $floor = $floors->get((int) $floorNo);

                $formattedSeats = $seats->map(function ($seat) use ($planTypes, $detailsBySeat, $transactions) {
                    $seatNo = (int) $seat['main'];
                    $seatDetails = $detailsBySeat->get($seatNo, collect());
                    $plantypes = $this->formatSeatPlanTypes($planTypes, $seatDetails, $transactions);
                    $isOccupied = collect($plantypes)->contains(fn ($item) => $item['learner'] !== null);

                    return [
                        'seat_id' => $seatNo,
                        'seat_no' => 'Seat No. '.($seat['floor'] ?? $seatNo),
                        'seat_status' => $isOccupied ? 'booked' : 'available',
                        'seat_type' => 'regular',
                        'plantype' => $plantypes,
                    ];
                })->values();

                $occupiedSeats = $formattedSeats->filter(fn ($seat) => $seat['seat_status'] !== 'available')->count();

                return [
                    'floor_id' => $floor->id ?? 0,
                    'floor_name' => $floor->name ?? ($seats->first()['floor_name'] ?? ''),
                    'total_seats' => $formattedSeats->count(),
                    'available_seats' => $formattedSeats->count() - $occupiedSeats,
                    'occupied_seats' => $occupiedSeats,
                    'seats' => $formattedSeats->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function formatGeneralSeatMap($branchId, $planTypes, $bookingDetails, $transactions, ?int $planTypeId = null, ?string $planTypeStatus = null)
    {
        $generalDetails = $bookingDetails
            ->filter(fn ($detail) => empty($detail->seat_no))
            ->values();

        $firstFloor = Floor::withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->orderBy('floor_no')
            ->first();

        $plantypes = $this->formatSeatPlanTypes($planTypes, $generalDetails, $transactions);
        $isOccupied = collect($plantypes)->contains(fn ($item) => $item['learner'] !== null);

        return [[
            'floor_id' => $firstFloor->id ?? 0,
            'floor_name' => $firstFloor->name ?? '',
            'total_seats' => 1,
            'available_seats' => $isOccupied ? 0 : 1,
            'occupied_seats' => $isOccupied ? 1 : 0,
            'seats' => [[
                'seat_id' => 0,
                'seat_no' => 'GEN',
                'seat_status' => $isOccupied ? 'occupied' : 'available',
                'seat_type' => 'Regular',
                'plantype' => $plantypes,
            ]],
        ]];
    }

    private function formatSeatPlanTypes($planTypes, $seatDetails, $transactions, ?string $planTypeStatus = null)
    {
        $detailsByPlanType = collect($seatDetails)->keyBy('plan_type_id');

        return $planTypes->map(function ($planType) use ($detailsByPlanType, $transactions, $planTypeStatus) {
            $detail = $detailsByPlanType->get($planType->id);

            return [
                'plan_type_id' => $planType->id,
                'plan_type_name' => $planType->name,
                'plan_type_status' => $detail ? $this->seatPlanTypeStatus($detail, $transactions) : 'available',
                'learner' => $detail ? $this->formatSeatLearner($detail, $transactions) : null,
            ];
        })->values()->all();
    }

    private function seatPlanTypeStatus($detail, $transactions)
    {
        $transaction = $transactions->get($detail->learner_id);
        $pendingAmount = (float) ($transaction->pending_amount ?? 0);
        $extraAmount = (float) ($transaction->extra_amount ?? 0);
        $planStatus = getPlanStatusDetails($detail->plan_end_date);

        if ((int) $detail->payment_mode === 3) {
            return 'paylater';
        }

        if ($pendingAmount > 0) {
            return 'pending payment';
        }

        if ($extraAmount > 0) {
            return 'extra paid';
        }

        if ($planStatus['status'] === 'About to Expire') {
            return 'about to expire';
        }

        if ($planStatus['status'] === 'In Extension') {
            return 'extended';
        }

        return 'booked';
    }

    private function formatSeatLearner($detail, $transactions)
    {
        $learner = $detail->learner;
        $transaction = $transactions->get($detail->learner_id);
        $planStatus = getPlanStatusDetails($detail->plan_end_date);

        return [
            'learner_id' => $learner->id,
            'learner_no' => $learner->learner_no,
            'name' => $learner->name,
            'plan' => $detail->plan->name ?? '',
            'profile_image' => $learner->profile_picture ? asset($learner->profile_picture) : '',
            'plan_start_date' => $detail->plan_start_date,
            'plan_end_date' => $detail->plan_end_date,
            'status' => strip_tags(getUserStatusWithSpan($detail->plan_end_date, $learner->id)),
            'pending_amount' => (string) ($transaction->pending_amount ?? 0),
            'extra_amount' => (string) ($transaction->extra_amount ?? 0),
            'pay_later' => (int) $detail->payment_mode === 3 ? (string) ($transaction->pending_amount ?? 0) : '0',
            'days_left' => $planStatus['diff_in_days'],
            'extend_days_left' => $planStatus['diff_extend_day'],
        ];
    }

   public function amountSatelment($learnerId)
    {
        $total_overall = LearnerTransaction::where('learner_id', $learnerId)
            ->selectRaw('
                SUM(total_amount) as total_amount_sum,
                SUM(paid_amount) as paid_amount_sum,
                SUM(pending_amount) as pending_amount_sum,
                SUM(refund) as pending_refund_sum,
                SUM(sattle_amount) as sattle_amount_sum
            ')
            ->first();

        $total  = $total_overall->total_amount_sum ?? 0;
        $paid   = $total_overall->paid_amount_sum ?? 0;
        $refund = $total_overall->pending_refund_sum ?? 0;
        $sattle = $total_overall->sattle_amount_sum ?? 0;
        $pending = $total_overall->pending_amount_sum ?? 0;


        return (object)[
            'overall_total_amt'     => (string) $total,
            'overall_paid_amount'   => (string) $paid,
            'overall_pending_sum'   => (string) $pending>$refund ? $pending-$refund : 0,
            'total_refund_pending'  => (string) $refund>$pending ? $refund-$pending : 0,
            'overall_sattle_amount' => (string) $sattle,
        ];
    }


    public function settlement($data)
    {
        return DB::transaction(function () use ($data) {
            $learnerId = (int) $data->learner_id;
            $pendingPay = (float) ($data->pending_amount ?? 0);
            $refundPay = (float) ($data->refund_amount ?? 0);
            $adjust = (bool) ($data->adjust ?? false);

            $transactions = LearnerTransaction::where('learner_id', $learnerId)
                ->orderBy('id', 'asc')
                ->get();

            if ($transactions->isEmpty()) {
                throw new \Exception('No transactions found.');
            }

            if ($pendingPay > 0 && $refundPay > 0) {
                throw new \Exception('Settle pending amount or refund amount one at a time.');
            }

            $summary = $this->amountSatelment($learnerId);
            $overallPending = (float) $summary->overall_pending_sum;
            $overallRefund = (float) $summary->total_refund_pending;

            
            if ($pendingPay > 0) {
                if ($pendingPay > $overallPending) {
                    throw new \Exception('Pending pay amount exceeds total pending amount.');
                }

                $this->adjustExtraAgainstPending($learnerId);
                $transactions = LearnerTransaction::where('learner_id', $learnerId)
                    ->orderBy('id', 'asc')
                    ->get();

                $appliedPayments = $this->payPendingAmount($transactions, $pendingPay);

                foreach ($appliedPayments as $payment) {
                    $this->logSettlementActivity(
                        $learnerId,
                        'PENDING',
                        $payment['amount'],
                        $data->payment_mode ?? 1,
                        'Cr',
                        $payment['transaction_id']
                    );
                }

                if ($adjust) {
                    $this->adjustRemainingPending($learnerId);
                } else {
                    $this->moveRemainingPendingToLastTransaction($learnerId);
                }
            }

            if ($refundPay > 0) {
                if ($refundPay > $overallRefund) {
                    throw new \Exception('Refund amount exceeds total extra/refund amount.');
                }

                $this->payRefundAmount($learnerId, $refundPay);
                $this->logSettlementActivity($learnerId, 'REFUND', $refundPay, $data->payment_mode ?? 1, 'Dr');

                if ($adjust) {
                    $this->adjustRemainingRefund($learnerId);
                } else {
                    $this->moveRemainingRefundToLastTransaction($learnerId);
                }
            }

            if ($pendingPay <= 0 && $refundPay <= 0 && $adjust) {
                if ($overallPending > 0) {
                    $this->adjustRemainingPending($learnerId);
                } elseif ($overallRefund > 0) {
                    $this->adjustRemainingRefund($learnerId);
                }
            }

            return [
                'success' => true,
                'message' => 'Settlement completed successfully.',
                'settlement' => $this->amountSatelment($learnerId),
            ];
        });
    }

    private function adjustExtraAgainstPending(int $learnerId): void
    {
        $extraTransactions = LearnerTransaction::where('learner_id', $learnerId)
            ->where('refund', '>', 0)
            ->orderBy('id', 'asc')
            ->get();

        if ($extraTransactions->isEmpty()) {
            return;
        }

        $pendingTransactions = LearnerTransaction::where('learner_id', $learnerId)
            ->where('pending_amount', '>', 0)
            ->orderBy('id', 'asc')
            ->get();

        if ($pendingTransactions->isEmpty()) {
            return;
        }

        foreach ($extraTransactions as $extraTransaction) {
            $remainingExtra = (float) $extraTransaction->refund;

            foreach ($pendingTransactions as $pendingTransaction) {
                if ($remainingExtra <= 0) {
                    break;
                }

                $pendingTransaction->refresh();
                $pending = (float) $pendingTransaction->pending_amount;

                if ($pending <= 0) {
                    continue;
                }

                $used = min($pending, $remainingExtra);
                $newPending = $pending - $used;

                $pendingTransaction->update([
                    'paid_amount' => (float) $pendingTransaction->paid_amount + $used,
                    'pending_amount' => $newPending,
                    'is_paid' => $newPending == 0 ? 1 : 0,
                    'paid_date' => now()->format('Y-m-d'),
                ]);

                $remainingExtra -= $used;
            }

            $extraTransaction->update([
                'refund' => $remainingExtra,
            ]);
        }
    }

    private function payPendingAmount($transactions, float $amount): array
    {
        $remaining = $amount;
        $appliedPayments = [];

        foreach ($transactions as $transaction) {
            if ($remaining <= 0) {
                break;
            }

            $pending = (float) $transaction->pending_amount;
            if ($pending <= 0) {
                continue;
            }

            $paidNow = min($pending, $remaining);
            $newPending = $pending - $paidNow;

            $transaction->update([
                'paid_amount' => (float) $transaction->paid_amount + $paidNow,
                'pending_amount' => $newPending,
                'is_paid' => $newPending == 0 ? 1 : 0,
                'paid_date' => now()->format('Y-m-d'),
            ]);

            if ($paidNow > 0) {
                $appliedPayments[] = [
                    'transaction_id' => $transaction->id,
                    'amount' => $paidNow,
                ];
            }

            $remaining -= $paidNow;
        }

        return $appliedPayments;
    }

    private function adjustRemainingPending(int $learnerId): void
    {
        $transactions = LearnerTransaction::where('learner_id', $learnerId)
            ->where('pending_amount', '>', 0)
            ->orderBy('id', 'asc')
            ->get();
        $totalSettledAmount = 0;

        foreach ($transactions as $transaction) {
            $pending = (float) $transaction->pending_amount;

            $transaction->update([
                'sattle_amount' => (float) ($transaction->sattle_amount ?? 0) + $pending,
                'pending_amount' => 0,
                'is_paid' => 1,
            ]);
            // current adjusted amount only
            $totalSettledAmount += $pending;

            
        }
        $this->logSettlementActivity($learnerId, 'SETTLED', $totalSettledAmount, 3, 'Settle');
    }

    private function moveRemainingPendingToLastTransaction(int $learnerId): void
    {
        $transactions = LearnerTransaction::where('learner_id', $learnerId)
            ->where('pending_amount', '>', 0)
            ->orderBy('id', 'asc')
            ->get();

        if ($transactions->isEmpty()) {
            return;
        }

        $remainingPending = (float) $transactions->sum('pending_amount');
        $lastTransaction = LearnerTransaction::where('learner_id', $learnerId)
            ->orderByDesc('id')
            ->first();

        LearnerTransaction::where('learner_id', $learnerId)->update([
            'pending_amount' => 0,
            'is_paid' => 1,
        ]);

        $lastTransaction->update([
            'pending_amount' => $remainingPending,
            'is_paid' => $remainingPending > 0 ? 0 : 1,
        ]);
    }

    private function payRefundAmount(int $learnerId, float $amount): void
    {
        $remaining = $amount;
      

        $futureRefunds = LearnerTransaction::where('learner_id', $learnerId)
            ->where('refund', '>', 0)
            ->orderByDesc('id')
            ->get();

        foreach ($futureRefunds as $transaction) {
            if ($remaining <= 0) {
                return;
            }

            $refund = (float) $transaction->refund;
            $paidNow = min($refund, $remaining);

            $transaction->update([
                'refund' => $refund - $paidNow,
            ]);

            $remaining -= $paidNow;
        }

        $paidTransactions = LearnerTransaction::where('learner_id', $learnerId)
            ->where('paid_amount', '>', 0)
            ->orderByDesc('id')
            ->get();

        foreach ($paidTransactions as $transaction) {
            if ($remaining <= 0) {
                break;
            }

            $paid = (float) $transaction->paid_amount;
            $deduct = min($paid, $remaining);

            $transaction->update([
                'paid_amount' => $paid - $deduct,
            ]);

            $remaining -= $deduct;
        }
    }

    private function adjustRemainingRefund(int $learnerId): void
    {
        $remainingRefund = (float) $this->amountSatelment($learnerId)->total_refund_pending;
        if ($remainingRefund <= 0) {
            return;
        }

        $lastTransaction = LearnerTransaction::where('learner_id', $learnerId)
            ->orderByDesc('id')
            ->first();

        $lastTransaction->update([
            'sattle_amount' => (float) ($lastTransaction->sattle_amount ?? 0) - $remainingRefund,
        ]);

        LearnerTransaction::where('learner_id', $learnerId)->update(['refund' => 0]);
        $this->logSettlementActivity($learnerId, 'SETTLED', $remainingRefund, 3, 'Settle');
    }

    private function moveRemainingRefundToLastTransaction(int $learnerId): void
    {
        $remainingRefund = (float) $this->amountSatelment($learnerId)->total_refund_pending;
        if ($remainingRefund <= 0) {
            return;
        }

        $lastTransaction = LearnerTransaction::where('learner_id', $learnerId)
            ->orderByDesc('id')
            ->first();

        $this->rebalancePaidAmountForFutureRefund($learnerId);
        LearnerTransaction::where('learner_id', $learnerId)->update(['refund' => 0]);

        $lastTransaction->refresh();
        $lastTransaction->update([
            'refund' => (float) $lastTransaction->refund + $remainingRefund,
        ]);
    }

    private function rebalancePaidAmountForFutureRefund(int $learnerId): void
    {
        $totalAmount = (float) LearnerTransaction::where('learner_id', $learnerId)->sum('total_amount');
        $paidAmount = (float) LearnerTransaction::where('learner_id', $learnerId)->sum('paid_amount');
        $targetPaidAmount = $totalAmount;
        $remaining = max($paidAmount - $targetPaidAmount, 0);

        if ($remaining <= 0) {
            return;
        }

        $transactions = LearnerTransaction::where('learner_id', $learnerId)
            ->where('paid_amount', '>', 0)
            ->orderByDesc('id')
            ->get();

        foreach ($transactions as $transaction) {
            if ($remaining <= 0) {
                break;
            }

            $paid = (float) $transaction->paid_amount;
            $deduct = min($paid, $remaining);

            $transaction->update([
                'paid_amount' => $paid - $deduct,
            ]);

            $remaining -= $deduct;
        }
    }

    private function logSettlementActivity(int $learnerId, string $paymentType, float $amount, $paymentMode, string $drCr, ?int $learnerTransactionId = null): void
    {
        $mode = match ((int) $paymentMode) {
            3 => 'PAYLATER',
            2 => 'OFFLINE',
            default => 'ONLINE',
        };

        LearnerTransactionActivity::create([
            'branch_id' => getCurrentBranch(),
            'learner_id' => $learnerId,
            'learner_transaction_id' => $learnerTransactionId,
            'date' => now()->format('Y-m-d'),
            'transaction_id' => transaction_id(),
            'particular' => 'SETTLEMENT',
            'payment_type' => $paymentType,
            'payment_mode' => $mode,
            'amount' => $amount,
            'dr_cr' => $drCr,
        ]);
    }
}
