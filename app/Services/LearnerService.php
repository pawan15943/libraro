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
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use Log;
use Auth;


class LearnerService
{
    private array $seatMapPrecomputed = [];

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
            | 0️⃣ HEAL SOFT-DELETED ROWS LEFT WITH A STALE status = 1
            |--------------------------------------------------------------------------
            | Deleting a learner/seat should always set status = 0, but a few code paths
            | historically soft-deleted the row without doing so first. Since deleted_at
            | is set, the row is invisible to normal Eloquent queries — but any raw SQL
            | or withoutGlobalScopes() query that checks status alone (without deleted_at)
            | would still treat it as active. Force it back to 0 here every run.
            |--------------------------------------------------------------------------
            */

            DB::statement("
                UPDATE learner_detail
                SET status = 0
                WHERE deleted_at IS NOT NULL
                AND status != 0
            ");

            DB::statement("
                UPDATE learners
                SET status = 0
                WHERE deleted_at IS NOT NULL
                AND status != 0
            ");

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
                AND ld.deleted_at IS NULL
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
                                AND DATE_ADD(ld2.plan_end_date, INTERVAL b2.extend_days DAY) >= ?
                                AND ld2.deleted_at IS NULL
                                AND l2.deleted_at IS NULL
                                AND NOT EXISTS (
                                    SELECT 1 FROM learner_operations_log closed_op
                                    WHERE closed_op.learner_detail_id = ld2.id
                                    AND closed_op.operation = 'closeSeat'
                                )
                                GROUP BY learner_id )
                                latest
                                ON latest.learner_id = ld1.learner_id AND latest.latest_start = ld1.plan_start_date
                WHERE ld1.deleted_at IS NULL
                AND NOT EXISTS (
                    SELECT 1 FROM learner_operations_log closed_op
                    WHERE closed_op.learner_detail_id = ld1.id
                    AND closed_op.operation = 'closeSeat'
                )
            ) active_ids ON active_ids.id = ld.id SET ld.status = 1 ",
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
                AND l.deleted_at IS NULL
                AND EXISTS (
                    SELECT 1 FROM learner_detail ld
                    WHERE ld.learner_id = l.id
                    AND ld.status = 1
                    AND ld.deleted_at IS NULL
                    AND NOT EXISTS (
                        SELECT 1 FROM learner_operations_log closed_op
                        WHERE closed_op.learner_detail_id = ld.id
                        AND closed_op.operation = 'closeSeat'
                    )
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
                    AND ld.deleted_at IS NULL
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

            $billing = BillingAmountService::calculate(
                $planPrice,
                $locker,
                $data['discount_type'] ?? null,
                (float) ($data['discount_amount'] ?? 0),
                $paid_amount
            );

            $discount = $billing['discount_amount'];
            $effectivePaid = $billing['total_amount'];
            $pending_amount = $billing['pending_amount'];
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
            

             if ( ($paid_amount > (($planPrice + $locker) + $oldTotalPending)) || ($paid_amount == 0 && $payment_mode != 3)) {
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
            Log::info([
                'seat_no'=>$seat_no
            ]);
            $start_date = Carbon::parse($data['start_date']);

            $endDate = getEndDate($plan_id, $start_date,$branchId);
            $learnerId=null;
            $planType = PlanType::findOrFail($plan_type_id);
            $hours = $planType->slot_hours;
            

            /* ---------------------------------------------------------
            | 4. Seat Availability
            ---------------------------------------------------------*/
             // future booking and non expiry seat check
            $noExpiry = (int) ($data['learner_data']['no_expiry'] ?? 0) === 1;

            if (($seat_no != 0 && !is_null($seat_no)) && seatHeldByFuture($branchId, $seat_no, $plan_type_id, $start_date, $endDate, null, $noExpiry)) {
                throw new \Exception($noExpiry
                    ? 'This seat is already assigned to a future booking and cannot be given a non-expiring plan.'
                    : 'This seat is already booked for the selected date and time.');
            }
            
           if (!empty($data['seat_no']) || $seat_no != 0) {
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

            if (BillingAmountService::discountExceedsPayable($planPrice, $locker, $data['discount_type'] ?? null, (float) ($data['discount_amount'] ?? 0))) {
                throw new \Exception('Discount cannot be more than the total payable amount.');
            }

            $billing = BillingAmountService::calculate(
                $planPrice,
                $locker,
                $data['discount_type'] ?? null,
                (float) ($data['discount_amount'] ?? 0),
                $paid_amount
            );

            $discount = $billing['discount_amount'];
            $effectivePaid = $billing['total_amount'];
            $pending_amount = $billing['pending_amount'];


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
           

             if ( ($paid_amount > ($planPrice + $locker)) ) {
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
           ->orderBy('id', 'desc')
            ->get();

        $transaction_all_activity= LearnerTransactionActivity::where('learner_id',$learnerId)->orderBy('id', 'desc')->get();

        $all_detail=LearnerDetail::with([
            'plan',
            'planType'
        ])
        ->where('learner_id',$learnerId)->orderBy('id', 'desc')->get();
        $currentDetail = LearnerDetail::where('learner_id', $learnerId)
            ->where('status', 1)
            ->select('plan_end_date')
            ->first();

        $operation = optional(getLearnerOperation($detail->id))->operation;    
        $planStatus =getPlanStatusDetails($detail->plan_end_date);
        $current_planStatus = $currentDetail
            ? getPlanStatusDetails($currentDetail->plan_end_date)
            : $planStatus;
       
        $isFirstLearnerDetail = (int) $detail->id === (int) LearnerDetail::withTrashed()
            ->where('learner_id', $learnerId)
            ->min('id');

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
        }elseif($isFirstLearnerDetail && !empty($detail->plan_start_date) && Carbon::parse($detail->plan_start_date)->isFuture()){
            $mainstatus='Upcoming';
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
                'seat_id' => $learner->seat_no !== null ? (int) $learner->seat_no : 0,
                'seat_no'=>$learner->seat_no ? (string)getSeatDisplayShortFloorName($learner->seat_no) : "GEN",
                'seat_with_floor'=>$learner->seat_no ? (string)getSeatDisplayShortFloorName($learner->seat_no) : "GEN",
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
                'next_plan'=>alreadyRenewed($learner->id) ? 1 : 0 ,
                'frozen_status'=>$learner->frozen_status,
                'freeze_date'=>$detail->freeze_start_date,
                'deleted_at'=>$learner->deleted_at ?? '',
                'locker'=>$learner->locker_no ? 'Yes' : 'No' ,
                'locker_no'=>(string)$learner->locker_no ?? '',
                'days_left'=>$planStatus['diff_in_days'],
                'extend_days_left'=>$planStatus['diff_extend_day'],
                'current_days_left'=>$current_planStatus['diff_in_days'],
                'current_extend_days_left'=>$current_planStatus['diff_extend_day'],
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
                'total_amount'=>(string) ($transaction->total_amount ?? '0'),
                'paid_amount'=>(string) ($transaction->paid_amount ?? '0'),
                'pending_amount'=>(string) ($transaction->pending_amount ?? '0'),
                'paid_date'=>$transaction->paid_date ?? '',
                'payment_mode'=>$detail->payment_mode ?? '',
                 'locker_amount'=>(string) ($transaction->locker_amount ?? '0'),
                'discount'=>$transaction->discount_amount ?? '0',
                'token_money'=>(string) ($transaction->token_money ?? '0'),
                'miscellaneous'=>(string) ($transaction->miscellaneous ?? '0'),
                'pending_refund'=>(string) ($transaction->refund ?? '0'),
                'due_date'=>$transaction->due_date ?? '',
                'transaction'=>$transaction->transaction_id ?? '',
                'transaction_id'=>$transaction->id ?? null,
                'download_receipt_url' => $this->downloadReceiptUrl($transaction),
               
            ],

            'other_details'=>[
                'alternate_mobile'=>$learner->alternate_mobile ?? '',
                'id_proof_id'=>$learner->id_proof_name ?? '',
                'id_proof_name'=>getIdProofName($learner->id_proof_name),
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
                $payment_mode = $ld?->payment_mode;

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
                    'download_receipt_url' => $this->downloadReceiptUrl($tx),
                    'seat_type' => $index === 0 ? 'BOOK SEAT' : 'RE-NEW SEAT',
                    'plan_start_date' => $ld?->plan_start_date ?? '',
                    'plan_end_date' => $ld?->plan_end_date ?? '',
                    'plan' => $ld?->plan?->name ?? '',
                    'plan_type' => $ld?->planType?->name ?? '',
                    'transaction_status' => $ld && (int) $ld->payment_mode === 3 ? 'Success' : 'Success',
                    'payment_mode'=>$payment_mode !== null ? (string) $payment_mode : '',
                ];
            }),

            'all_transaction_activity'=>$transaction_all_activity->map(function($txn){

                return [
                    'id'=>(int) $txn->id,
                    'transaction_id'=>$txn->transaction_id ?? '',
                    'amount'=>$txn->amount?? '',
                    'particular'=>$txn->particular,
                    'payment_type'=>$txn->payment_type ?? '',
                    'mode'=>$txn->payment_mode,
                    'date'=>$txn->date,
                    'dr_cr'=>$txn->dr_cr,
                    'download_receipt_url' => $this->otherPaymentReceiptUrl($txn),
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

    private function downloadReceiptUrl($transaction): string
    {
        if (! $transaction || (int) ($transaction->is_paid ?? 0) !== 1) {
            return '';
        }

        try {
            return app(ReceiptService::class)->receiptOpenLink((int) $transaction->id);
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function otherPaymentReceiptUrl($activity): string
    {
        if (! $activity || ! in_array(strtoupper((string) ($activity->payment_type ?? '')), ['TOKEN MONEY', 'MISCELLANEOUS'], true)) {
            return '';
        }

        try {
            return app(ReceiptService::class)->otherPaymentOpenLink((int) $activity->id);
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function getLearnersList($filters = [])
    {
        $branchId = getCurrentBranch();

       // LearnerDetail::withTrashed() already applies the HasBranch global scope
       // (learner_detail.branch_id = current branch), so no extra learner_id filter
       // is needed here — it would just re-derive the same branch-restricted set.
       $latestDetail = LearnerDetail::withTrashed()
       ->selectRaw('COALESCE(MAX(CASE WHEN status = 1 THEN id END), MAX(id)) as id')
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
                'no_expiry',
                'deleted_at','sended_message_type'
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

        $statusFilter = trim((string) ($filters['status'] ?? ''));
        $statusFilter = $statusFilter !== '' ? $statusFilter : 'active';

        if (!in_array($statusFilter, ['all', 'deleted'], true)) {
            $query->whereNull('learners.deleted_at');
        }

        if (!empty($statusFilter)) {

            switch ($statusFilter) {
                case 'all':
                break;

                    case 'active':
                        $extendDays = getExtendDays($branchId);

                        if (!empty($filters['is_expired_allowed']) && !empty($filters['search'])) {
                            // Only widen active-only to also include expired
                            // (learner_detail.status 0 or 1) when there's an
                            // actual search term — ignore the flag on a plain
                            // unfiltered listing request.
                            $query->whereIn('learner_detail.status', [0, 1]);
                        } else {
                            $query->where('learner_detail.status',1);
                                // ->whereDate(
                                //     'learner_detail.plan_end_date',
                                //     '>=',
                                //     now()->subDays($extendDays)
                                // );
                        }

                    break;

                case 'about_to_expire':

                    $query->where('learner_detail.status',1)->where('learners.no_expiry',0)
                        ->whereBetween('learner_detail.plan_end_date', [now()->startOfDay(), now()->addDays(5)]);

                break;

                case 'extended':

                    $extendDays = getExtendDays($branchId);

                    $query->whereDate('learner_detail.plan_end_date','<',now())->where('learners.no_expiry',0)
                        ->whereDate(
                            'learner_detail.plan_end_date',
                            '>=',
                            now()->subDays($extendDays)
                        )->whereNotExists(function ($sub) {
                            $sub->select(DB::raw(1))
                                ->from('learner_operations_log as closed_op')
                                ->whereColumn('closed_op.learner_detail_id', 'learner_detail.id')
                                ->where('closed_op.operation', 'closeSeat');
                        });

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

                    $query->whereDate('learner_detail.plan_end_date','<',now())
                        ->where('learners.status',0)
                        ->whereNotExists(function ($sub) {
                            $sub->select(DB::raw(1))
                                ->from('learner_operations_log as closed_op')
                                ->whereColumn('closed_op.learner_detail_id', 'learner_detail.id')
                                ->where('closed_op.operation', 'closeSeat');
                        });

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

                    $dueFilter = $filters['due_filter'] ?? 'all';
                    $today = Carbon::today()->toDateString();

                    switch ($dueFilter) {

                        case 'pending':
                            // Active learner with an outstanding due.
                            $query->where('learners.status', 1)
                                ->whereExists(function ($q) {
                                    $q->select(\DB::raw(1))
                                        ->from('learner_transactions')
                                        ->whereColumn('learner_transactions.learner_id', 'learners.id')
                                        ->where('learner_transactions.pending_amount', '>', 0);
                                });
                        break;

                        case 'expired':
                            // Expired learner with an outstanding due.
                            $query->where('learners.status', 0)
                                ->whereExists(function ($q) {
                                    $q->select(\DB::raw(1))
                                        ->from('learner_transactions')
                                        ->whereColumn('learner_transactions.learner_id', 'learners.id')
                                        ->where('learner_transactions.pending_amount', '>', 0);
                                });
                        break;

                        case 'overdue':
                            // Outstanding due whose due date has passed, active or expired.
                            $query->whereExists(function ($q) use ($today) {
                                $q->select(\DB::raw(1))
                                    ->from('learner_transactions')
                                    ->whereColumn('learner_transactions.learner_id', 'learners.id')
                                    ->where('learner_transactions.pending_amount', '>', 0)
                                    ->whereNotNull('learner_transactions.due_date')
                                    ->whereDate('learner_transactions.due_date', '<', $today);
                            });
                        break;

                        case 'adjusted':
                            // Learner with a settled/adjusted amount.
                            $query->whereExists(function ($q) {
                                $q->select(\DB::raw(1))
                                    ->from('learner_transactions')
                                    ->whereColumn('learner_transactions.learner_id', 'learners.id')
                                    ->where('learner_transactions.sattle_amount', '>', 0);
                            });
                        break;

                        case 'received':
                            // Learner who has had a pending due received/collected.
                            $query->whereExists(function ($q) {
                                $q->select(\DB::raw(1))
                                    ->from('learner_transaction_activity')
                                    ->whereColumn('learner_transaction_activity.learner_id', 'learners.id')
                                    ->where('learner_transaction_activity.payment_type', 'PENDING')
                                    ->where('learner_transaction_activity.dr_cr', 'Cr');
                            });
                        break;

                        case 'all':
                        default:
                            $query->whereExists(function ($q) {
                                $q->select(\DB::raw(1))
                                    ->from('learner_transactions')
                                    ->whereColumn('learner_transactions.learner_id', 'learners.id')
                                    ->where('learner_transactions.pending_amount', '>', 0);
                            });
                        break;
                    }

                break;

                case 'non_expiry':

                    $query->where('learners.no_expiry',1)->where('learners.status',1);

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
        JOIN DATE FILTER
        ------------------------------*/

        if (!empty($filters['from_date'])) {
            $query->whereDate('learner_detail.join_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('learner_detail.join_date', '<=', $filters['to_date']);
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
            'learners.no_expiry',
            'learners.status as learner_active_status',
            'learner_detail.freeze_start_date',
            'learners.sended_message_type',

            'learner_detail.seat_no',
            'learner_detail.plan_start_date',
            'learner_detail.plan_end_date',

            'plans.name as plan_name',
            'plans.id as plan_id',
            'plan_types.name as plan_type',
            'learner_detail.id as learner_detail_id',
            'learners.deleted_at',
            DB::raw('(
                SELECT MIN(first_detail.id)
                FROM learner_detail as first_detail
                WHERE first_detail.learner_id = learners.id
            ) as first_learner_detail_id'),
            DB::raw('(
                SELECT learner_transactions.id
                FROM learner_transactions
                WHERE learner_transactions.learner_id = learners.id
                ORDER BY learner_transactions.id DESC
                LIMIT 1
            ) as transaction_id'),
            DB::raw('(
                SELECT learner_transactions.id
                FROM learner_transactions
                WHERE learner_transactions.learner_detail_id = learner_detail.id
                ORDER BY learner_transactions.id DESC
                LIMIT 1
            ) as receipt_transaction_id')

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
                ->paginate(10);
        } elseif ($sortBy === 'gen') {
            // Dedicated general-seat sort (DB general seat = NULL).
            // asc: general first, desc: general last.
            if ($sortOrder === 'asc') {
                $learners = $query
                    ->orderByRaw('CASE WHEN learner_detail.seat_no IS NULL OR learner_detail.seat_no = "" THEN 0 ELSE 1 END ASC')
                    ->orderByRaw('CAST(learner_detail.seat_no AS UNSIGNED) ASC')
                    ->paginate(10);
            } else {
                $learners = $query
                    ->orderByRaw('CASE WHEN learner_detail.seat_no IS NULL OR learner_detail.seat_no = "" THEN 1 ELSE 0 END ASC')
                    ->orderByRaw('CAST(learner_detail.seat_no AS UNSIGNED) DESC')
                    ->paginate(10);
            }
        } else {
            $learners = $query
                ->orderBy($sortColumn, $sortOrder)
                ->paginate(10);
        }

        /* -----------------------------
        BATCH PRE-FETCH (avoids N+1 queries per row)
        ------------------------------*/

        $learnerCollection = $learners->getCollection();
        $learnerIds = $learnerCollection->pluck('id')->all();

        $latestOps = collect();
        $detailRowsByLearner = collect();
        $txByLearner = collect();
        $plansById = collect();
        $branchModel = null;
        $seatMap = [];
        $extendDay = 0;

        if (!empty($learnerIds)) {
            $latestOps = DB::table('learner_operations_log')
                ->select('learner_id', 'operation', 'created_at')
                ->whereIn('id', function ($sub) use ($learnerIds) {
                    $sub->selectRaw('MAX(id)')
                        ->from('learner_operations_log')
                        ->whereIn('learner_id', $learnerIds)
                        ->groupBy('learner_id');
                })
                ->get()
                ->keyBy('learner_id');

            $detailRowsByLearner = DB::table('learner_detail')
                ->leftJoin('plan_types', 'plan_types.id', '=', 'learner_detail.plan_type_id')
                ->whereIn('learner_detail.learner_id', $learnerIds)
                ->select(
                    'learner_detail.learner_id',
                    'learner_detail.status',
                    'learner_detail.plan_start_date',
                    'learner_detail.plan_end_date',
                    'plan_types.day_type_id'
                )
                ->get()
                ->groupBy('learner_id');

            $txByLearner = DB::table('learner_transactions')
                ->leftJoin('learner_detail as ld', 'ld.id', '=', 'learner_transactions.learner_detail_id')
                ->whereIn('learner_transactions.learner_id', $learnerIds)
                ->select(
                    'learner_transactions.learner_id',
                    'learner_transactions.pending_amount',
                    'learner_transactions.refund',
                    'learner_transactions.due_date',
                    'ld.payment_mode'
                )
                ->get()
                ->groupBy('learner_id');

            $planIds = $learnerCollection->pluck('plan_id')->filter()->unique()->values();
            if ($planIds->isNotEmpty()) {
                $plansById = Plan::whereIn('id', $planIds)
                    ->select('id', 'plan_id', 'type', 'monthdays')
                    ->get()
                    ->keyBy('id');
            }

            $branchModel = Branch::find($branchId);
            $extendDay = getExtendDays($branchId);
            $seatMap = generateSeatNumbers();
        }

        $today = Carbon::today();
        $now = now();
        $todayPlus5 = $today->copy()->addDays(5);

        $userStatusPrecomputed = [];
        $alreadyRenewedMap = [];

        foreach ($learnerIds as $lid) {
            $rows = $detailRowsByLearner->get($lid, collect());

            $hasFuturePlan = $rows->contains(function ($r) use ($todayPlus5) {
                return !empty($r->plan_end_date) && $r->plan_end_date > $todayPlus5->toDateString();
            });

            $hasPastPlan = $rows->contains(function ($r) use ($todayPlus5) {
                return !empty($r->plan_end_date) && $r->plan_end_date <= $todayPlus5->toDateString();
            });

            $futureUnpaidRows = $rows->filter(function ($r) use ($now) {
                return (int) $r->status === 0
                    && !empty($r->plan_start_date)
                    && Carbon::parse($r->plan_start_date)->startOfDay()->gt($now);
            });

            $hasFutureStart = $futureUnpaidRows->isNotEmpty();
            $startDetail = $futureUnpaidRows->first();
            $startFrom = $startDetail
                ? $today->diffInDays(Carbon::parse($startDetail->plan_start_date), false)
                : null;

            $isRenewUpdate = $rows->contains(function ($r) use ($today) {
                return (int) $r->status === 0
                    && !empty($r->plan_start_date)
                    && $r->plan_start_date > $today->toDateString();
            }) && $rows->count() > 1;

            $hasVip = $rows->contains(function ($r) {
                return (int) $r->day_type_id === 11 && (int) $r->status === 1;
            });

            $alreadyRenewedMap[$lid] = $isRenewUpdate;

            $userStatusPrecomputed[$lid] = [
                'extend_day' => $extendDay,
                'has_future_plan' => $hasFuturePlan,
                'has_past_plan' => $hasPastPlan,
                'is_renew_update' => $isRenewUpdate,
                'has_future_start' => $hasFutureStart,
                'start_from' => $startFrom,
                'has_vip' => $hasVip,
            ];
        }

        /* -----------------------------
        FORMAT RESPONSE
        ------------------------------*/

        $learners->getCollection()->transform(function($learner) use (
            $latestOps, $plansById, $branchModel, $seatMap, $extendDay, $userStatusPrecomputed, $alreadyRenewedMap, $txByLearner
        ){

            $daysLeft = \Carbon\Carbon::parse($learner->plan_end_date)->diffInDays(now(),false);

            $operation = $latestOps->get($learner->id);
            $operationName = $operation->operation ?? null;
            $planStatus =getPlanStatusDetails($learner->plan_end_date, $extendDay);
            if($operationName == 'closeSeat'){
                    $status='Closed';
            }elseif($operationName == 'deleteSeat' && $learner->deleted_at !=null){
                $status='Deleted';
            }else{
                    $statusPrecomputed = $userStatusPrecomputed[$learner->id] ?? null;
                    if ($statusPrecomputed !== null) {
                        $statusPrecomputed['frozen_status'] = (int) ($learner->frozen_status ?? 0) === 1;
                        $statusPrecomputed['no_expiry_active'] = (int) ($learner->no_expiry ?? 0) === 1
                            && (int) ($learner->learner_active_status ?? 0) === 1;
                    }

                    $status = strip_tags(
                    getUserStatusWithSpan($learner->plan_end_date,$learner->id, $statusPrecomputed)
                );
            }



            if($operationName == 'closeSeat'){
                $mainstatus='Closed';
            }elseif($operationName == 'deleteSeat' && $learner->deleted_at !=null){
                $mainstatus='Deleted';
            }elseif((int) ($learner->learner_detail_id ?? 0) === (int) ($learner->first_learner_detail_id ?? 0) && !empty($learner->plan_start_date) && Carbon::parse($learner->plan_start_date)->isFuture()){
                $mainstatus='Upcoming';
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
            $messageType = strtolower(trim((string) $learner->sended_message_type));
            if($messageType=='whatsapp'){
                        $sended_message_type=1;
            }elseif($messageType=='text'){
                    $sended_message_type=2;
            }elseif($messageType=='both'){
                $sended_message_type=3;
            }else{
                    $sended_message_type=0;
            }

            return [

                'id'=>$learner->id,
                'learner_no'=>$learner->learner_no,
                'name'=>$learner->name,
                'mobile'=>decryptData($learner->mobile),
                'dob'=>$learner->dob,
                'birth_status'=>$birthStatus,
                'seat_id' => $learner->seat_no !== null ? (int) $learner->seat_no : 0,
                'seat_no' => $learner->seat_no ? (string)getSeatDisplayShortFloorName($learner->seat_no, $seatMap) : "GEN",
                'seat_with_floor' => $learner->seat_no ? (string)getSeatDisplayShortFloorName($learner->seat_no, $seatMap) : "GEN",

                'profile_picture' => $learner->profile_picture 
                ? asset($learner->profile_picture) 
                : '',

                'plan'=>$learner->plan_name ?? '',
                'plan_type'=>$learner->plan_type ?? '',
                'plan_days' => getChargeableDays($learner->plan_id, $learner->plan_start_date, $learner->branch_id, $plansById->get($learner->plan_id), $branchModel)['chargeable_days'] ?? 0,
                'plan_end_date'=>$learner->plan_end_date ?? '',
                'plan_start_date'=>$learner->plan_start_date ?? '',

                'days_left'=>$planStatus['diff_in_days'],
                'extend_days_left'=>$planStatus['diff_extend_day'],
                'next_plan'=>alreadyRenewed($learner->id, $alreadyRenewedMap[$learner->id] ?? null) ? 1 : 0 ,
                'status'=>$status,
                'mainstatus'=>$mainstatus,
                'frozen_status'=>$learner->frozen_status,
                'freeze_date'=>$learner->freeze_start_date,
                'deleted_at'=>$learner->deleted_at ?? '',
                'transaction_id'=>$learner->transaction_id ?? '',
                'receipt_url' => $learner->receipt_transaction_id
                    ? URL::signedRoute('receipt.signed', ['transactionId' => $learner->receipt_transaction_id])
                    : '',

                'payment'=>learnerTransactionStatus($learner->id, $txByLearner->get($learner->id, collect())),
                'sended_message_type'=>$sended_message_type
                
                
                
            ];

        });

        return $learners;
    }

    /**
     * Batches the per-row lookups the web learner-list Blade view used to run
     * one-by-one via learnerTransaction()/totalPending()/paylater()/overdue()/
     * getLearnerOperation()/getUserStatusWithSpan() etc. (was ~20+ queries per
     * row). Keyed by learner_detail_id.
     */
    public function buildLearnerListRowContext($learnerRows): array
    {
        $learnerRows = collect($learnerRows);

        if ($learnerRows->isEmpty()) {
            return [];
        }

        $learnerIds = $learnerRows->pluck('id')->filter()->unique()->values();
        $detailIds = $learnerRows->pluck('learner_detail_id')->filter()->unique()->values();

        $transactions = LearnerTransaction::whereIn('learner_id', $learnerIds)->get();
        $txByLearner = $transactions->groupBy('learner_id');
        $txByDetail = $transactions->groupBy('learner_detail_id');

        $latestOps = $detailIds->isEmpty() ? collect() : DB::table('learner_operations_log')
            ->select('learner_detail_id', 'operation', 'created_at')
            ->whereIn('id', function ($sub) use ($detailIds) {
                $sub->selectRaw('MAX(id)')
                    ->from('learner_operations_log')
                    ->whereIn('learner_detail_id', $detailIds)
                    ->groupBy('learner_detail_id');
            })
            ->get()
            ->keyBy('learner_detail_id');

        $extendDay = getExtendDays();
        $today = Carbon::today();
        $futureThreshold = $today->copy()->addDays(5)->toDateString();
        $nowDateTime = now()->toDateTimeString();

        $allDetailRows = LearnerDetail::whereIn('learner_id', $learnerIds)
            ->select('learner_id', 'status', 'plan_start_date', 'plan_end_date')
            ->get()
            ->groupBy('learner_id');

        $vipLearnerIds = LearnerDetail::leftJoin('plan_types', 'plan_types.id', '=', 'learner_detail.plan_type_id')
            ->where('plan_types.day_type_id', 11)
            ->where('learner_detail.status', 1)
            ->whereIn('learner_detail.learner_id', $learnerIds)
            ->distinct()
            ->pluck('learner_detail.learner_id')
            ->flip();

        $context = [];

        foreach ($learnerRows as $row) {
            $detailId = $row->learner_detail_id;
            $learnerId = $row->id;

            $detailTx = $txByDetail->get($detailId, collect());
            $transaction = $detailTx->first();

            $rows = $allDetailRows->get($learnerId, collect());
            $hasFuturePlan = $rows->contains(fn ($r) => (int) $r->status === 0 && $r->plan_end_date > $futureThreshold);
            $hasPastPlan = $rows->contains(fn ($r) => $r->plan_end_date <= $futureThreshold);
            $futureStartRows = $rows->filter(fn ($r) => (int) $r->status === 0 && $r->plan_start_date > $nowDateTime);
            $startDetail = $futureStartRows->first();
            $isRenewUpdate = $futureStartRows->isNotEmpty() && $rows->count() > 1;

            $context[$detailId] = [
                'transaction' => $transaction,
                'total_pending' => $txByLearner->get($learnerId, collect())->sum('pending_amount'),
                'total_extra' => $txByLearner->get($learnerId, collect())->sum('refund'),
                'paylater' => $detailTx->contains(fn ($t) => (int) $t->is_paid === 0),
                'has_pending_amt' => $detailTx->contains(fn ($t) => $t->pending_amount > 0),
                'payble_refund' => $detailTx->sum(fn ($t) => (float) ($t->paid_amount ?? 0) + (float) ($t->token_money ?? 0) + (float) ($t->miscellaneous ?? 0)),
                'overdue' => $transaction && !empty($transaction->due_date) && Carbon::now()->gt(Carbon::parse($transaction->due_date)),
                'operation' => $latestOps->get($detailId),
                'is_renew_update' => $isRenewUpdate,
                'status_precomputed' => [
                    'extend_day' => $extendDay,
                    'has_future_plan' => $hasFuturePlan,
                    'has_past_plan' => $hasPastPlan,
                    'is_renew_update' => $isRenewUpdate,
                    'has_future_start' => $futureStartRows->isNotEmpty(),
                    'start_from' => $startDetail ? $today->diffInDays(Carbon::parse($startDetail->plan_start_date), false) : null,
                    'frozen_status' => (int) ($row->frozen_status ?? 0) === 1,
                    'no_expiry_active' => (int) ($row->no_expiry ?? 0) === 1 && (int) ($row->status ?? 0) === 1,
                    'has_vip' => $vipLearnerIds->has($learnerId),
                ],
            ];
        }

        return $context;
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
            ['id' => 8, 'name' => 'non expire', 'color' => '#c8009d'],
            ['id' => 9, 'name' => 'future', 'color' => '#FF8C00'],
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
            ->whereNull('deleted_at')
            ->whereHas('learner', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                    ->where('status', 1)
                    ->whereNull('deleted_at');
            })
            ->orderBy('seat_no')
            ->orderBy('plan_type_id')
            ->get();

        // Rows for bookings that haven't started yet (learner_detail.status = 0 until the
        // daily cron flips it on plan_start_date — see DailyStatusUpdateJob) are otherwise
        // invisible to $bookingDetails above, which only looks at status = 1. Without this,
        // a seat/plan-type with no *current* booking but a future one shows as plain
        // "available" even though it's already reserved from a future date.
        //
        // Note: learners.status is NOT required to be 1 here — runDailyUpdate() only sets a
        // learner's status to 1 once they have an *active* (status = 1) learner_detail row,
        // so a learner whose only booking is this future one is legitimately status = 0 until
        // plan_start_date arrives. Requiring status = 1 would exclude every genuine future
        // booking. We still skip rows explicitly closed via a 'closeSeat' operation, mirroring
        // the same exclusion the daily cron applies before reactivating a row.
        $futureBookingDetails = LearnerDetail::withTrashed()
            ->with(['learner', 'plan', 'planType'])
            ->where('branch_id', $branchId)
            ->where('status', 0)
            ->whereNull('deleted_at')
            ->where('plan_start_date', '>', now()->toDateTimeString())
            ->whereHas('learner', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                    ->whereNull('deleted_at');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('learner_operations_log')
                    ->whereColumn('learner_operations_log.learner_detail_id', 'learner_detail.id')
                    ->where('learner_operations_log.operation', 'closeSeat');
            })
            ->orderBy('plan_start_date')
            ->get();

        $futureBySeat = $futureBookingDetails
            ->filter(fn ($detail) => ! empty($detail->seat_no))
            ->groupBy(fn ($detail) => (int) $detail->seat_no);

        $futureGeneral = $futureBookingDetails
            ->filter(fn ($detail) => empty($detail->seat_no))
            ->values();

        // Future learners need to go through the same batched transaction/precompute lookups
        // as current ones so formatSeatLearner() can render their card (pending amount, "Plan
        // Starts in N days", VIP/frozen flags, etc.) without falling back to per-learner queries.
        $learnerIds = $bookingDetails->pluck('learner_id')
            ->merge($futureBookingDetails->pluck('learner_id'))
            ->filter()->unique()->values();

        $transactions = LearnerTransaction::withTrashed()
            ->whereIn('learner_id', $learnerIds)
            ->selectRaw('learner_id, SUM(pending_amount) as pending_amount, SUM(refund) as extra_amount ,MIN(due_date) as due_date')
            ->groupBy('learner_id')
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('learner_id');

        $this->seatMapPrecomputed = $this->buildSeatMapPrecomputed($learnerIds, $bookingDetails->concat($futureBookingDetails));

        $numberedDetails = $bookingDetails
            ->filter(fn ($detail) => ! empty($detail->seat_no))
            ->groupBy(fn ($detail) => (int) $detail->seat_no);

        $statusRow = collect($planTypeStatuses)->firstWhere('id', $planTypeStatus);
        $statusName = $statusRow['name'] ?? null;

        $numbered = $this->formatNumberedSeatMap($branchId, $planTypes, $numberedDetails, $transactions, $planTypeId, null, $futureBySeat);
        $general = $this->formatGeneralSeatMap($branchId, $planTypes, $bookingDetails, $transactions, $planTypeId, $statusName, $futureGeneral);

        if ($statusName) {
            $numbered = $this->sortSeatMapByPlanTypeStatus($numbered, $statusName);
            $general = $this->sortSeatMapByPlanTypeStatus($general, $statusName);
        }

        return [
            'plan_type_status' => $planTypeStatuses,
            'numbered' => $numbered,
            'general' => $general,
        ];
    }

    /**
     * Batches the per-learner lookups that getUserStatusWithSpan()/seatPlanTypeStatus()
     * would otherwise run one-by-one (was ~9 queries per occupied seat).
     */
    private function buildSeatMapPrecomputed($learnerIds, $bookingDetails): array
    {
        if ($learnerIds->isEmpty()) {
            return [];
        }

        $extendDay = getExtendDays();
        $today = Carbon::today();
        $futureThreshold = $today->copy()->addDays(5)->toDateString();
        $nowDateTime = now()->toDateTimeString();

        $allDetailRows = LearnerDetail::whereIn('learner_id', $learnerIds)
            ->select('learner_id', 'status', 'plan_start_date', 'plan_end_date')
            ->get()
            ->groupBy('learner_id');

        $vipLearnerIds = LearnerDetail::leftJoin('plan_types', 'plan_types.id', '=', 'learner_detail.plan_type_id')
            ->where('plan_types.day_type_id', 11)
            ->where('learner_detail.status', 1)
            ->whereIn('learner_detail.learner_id', $learnerIds)
            ->distinct()
            ->pluck('learner_detail.learner_id')
            ->flip();

        $learnerAttrs = $bookingDetails->groupBy('learner_id');

        $precomputed = [];

        foreach ($learnerIds as $learnerId) {
            $rows = $allDetailRows->get($learnerId, collect());

            $hasFuturePlan = $rows->contains(fn ($row) => (int) $row->status === 0
                && $row->plan_end_date > $futureThreshold);

            $hasPastPlan = $rows->contains(fn ($row) => $row->plan_end_date <= $futureThreshold);

            $futureStartRows = $rows->filter(fn ($row) => (int) $row->status === 0
                && $row->plan_start_date > $nowDateTime);

            $startDetail = $futureStartRows->first();

            $learner = optional($learnerAttrs->get($learnerId))->first()?->learner;

            $precomputed[$learnerId] = [
                'extend_day' => $extendDay,
                'has_future_plan' => $hasFuturePlan,
                'has_past_plan' => $hasPastPlan,
                'is_renew_update' => $futureStartRows->isNotEmpty() && $rows->count() > 1,
                'has_future_start' => $futureStartRows->isNotEmpty(),
                'start_from' => $startDetail ? $today->diffInDays(Carbon::parse($startDetail->plan_start_date), false) : null,
                'frozen_status' => (int) ($learner->frozen_status ?? 0) === 1,
                'no_expiry_active' => (int) ($learner->no_expiry ?? 0) === 1 && (int) ($learner->status ?? 0) === 1,
                'has_vip' => $vipLearnerIds->has($learnerId),
            ];
        }

        return $precomputed;
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

    private function formatNumberedSeatMap($branchId, $planTypes, $detailsBySeat, $transactions, ?int $planTypeId = null, ?string $planTypeStatus = null, $futureBySeat = null)
    {
        $futureBySeat = $futureBySeat ?? collect();
        $seatRows = collect(generateSeatNumbers2((int) $branchId));

        $floors = Floor::withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->whereNull('deleted_at')
            ->orderBy('floor_no')
            ->get()
            ->keyBy('floor_no');

        return $seatRows
            ->groupBy(fn ($seat) => $seat['floor_no'] ?? 0)
            ->map(function ($seats, $floorNo) use ($planTypes, $detailsBySeat, $transactions, $floors, $planTypeId, $futureBySeat) {
                $floor = $floors->get((int) $floorNo);

                $formattedSeats = $seats->map(function ($seat) use ($planTypes, $detailsBySeat, $transactions, $futureBySeat) {
                    $seatNo = (int) $seat['main'];
                    $displaySeatNo = !empty($seat['floor_name']) && !empty($seat['floor'])
                        ? (int) $seat['floor']
                        : $seatNo;
                    $seatDetails = $detailsBySeat->get($seatNo, collect());
                    $futureDetails = $futureBySeat->get($seatNo, collect());
                    $plantypes = $this->formatSeatPlanTypes($planTypes, $seatDetails, $transactions, null, $futureDetails);
                    // 'future' entries now carry a learner card too (so staff can see who/when),
                    // but that alone must not flip the seat to "booked" — only a *current*
                    // occupant (non-future status) should.
                    $isOccupied = collect($plantypes)->contains(fn ($item) => $item['learner'] !== null && ($item['plan_type_status'] ?? null) !== 'future');

                    return [
                        'seat_id' => $seatNo,
                        'seat_no' => 'Seat No. '.$displaySeatNo,
                        'seat_status' => $isOccupied ? 'booked' : 'available',
                        'seat_type' => 'regular',
                        'plantype' => $plantypes,
                    ];
                })
                    ->when($planTypeId, fn ($items) => $items->filter(fn ($seat) => ! empty($seat['plantype'])))
                    ->values();

                $occupiedSeats = $formattedSeats->filter(fn ($seat) => $seat['seat_status'] !== 'available')->count();
                $totalSeats = (int) ($floor->total_seats ?? $formattedSeats->count());

                // Seats left over after the configured floors' seat ranges are
                // exhausted (floor === null) only get the "Outside the floor"
                // label when floors actually exist for this branch — if the
                // branch has no floors configured at all, every seat lands in
                // this same unassigned bucket and should keep the empty string
                // it always has.
                $floorName = $floor->name ?? ($seats->first()['floor_name'] ?? '');
                if ($floor === null && $floors->isNotEmpty()) {
                    $floorName = 'Outside the floor';
                }

                return [
                    'floor_id' => $floor->id ?? 0,
                    'floor_name' => $floorName,
                    'total_seats' => $totalSeats,
                    'available_seats' => max(0, $totalSeats - $occupiedSeats),
                    'occupied_seats' => $occupiedSeats,
                    'seats' => $formattedSeats->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function formatGeneralSeatMap($branchId, $planTypes, $bookingDetails, $transactions, ?int $planTypeId = null, ?string $planTypeStatus = null, $futureGeneral = null)
    {
        $generalDetails = $bookingDetails
            ->filter(fn ($detail) => empty($detail->seat_no))
            ->when($planTypeId, fn ($details) => $details->filter(fn ($detail) => (int) $detail->plan_type_id === (int) $planTypeId))
            ->values();

        $firstFloor = Floor::withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->orderBy('floor_no')
            ->first();

        if ($generalDetails->isEmpty()) {
            $seats = [[
                'seat_id' => 0,
                'seat_no' => 'GEN',
                'seat_status' => 'available',
                'seat_type' => 'Regular',
                'plantype' => $this->formatGeneralSeatPlanTypes($planTypes, $generalDetails, $transactions, $planTypeStatus, $futureGeneral),
            ]];
        } else {
            $seats = $generalDetails
                ->values()
                ->map(function ($detail, $index) use ($transactions, $planTypeStatus) {
                    $status = $this->seatPlanTypeStatus($detail, $transactions);

                    return [
                        'seat_id' => 0,
                        'seat_no' => 'GEN '.($index + 1),
                        'seat_status' => 'occupied',
                        'seat_type' => 'Regular',
                        'plantype' => [[
                            'plan_type_id' => $detail->planType?->id ?? $detail->plan_type_id,
                            'plan_type_name' => $detail->planType?->name ?? '',
                            'plan_type_status' => $status,
                            'learner' => $this->formatSeatLearner($detail, $transactions),
                        ]],
                    ];
                })
                ->filter(function ($seat) use ($planTypeStatus) {
                    return ! $planTypeStatus || collect($seat['plantype'])->contains(
                        fn ($planType) => ($planType['plan_type_status'] ?? null) === $planTypeStatus
                    );
                })
                ->all();
        }

        if ($planTypeStatus) {
            $seats = collect($seats)
                ->map(function ($seat) use ($planTypeStatus) {
                    $seat['plantype'] = collect($seat['plantype'] ?? [])
                        ->filter(fn ($planType) => ($planType['plan_type_status'] ?? null) === $planTypeStatus)
                        ->values()
                        ->all();

                    return $seat;
                })
                ->filter(fn ($seat) => ! empty($seat['plantype']))
                ->values()
                ->all();
        }

        $occupiedSeats = collect($seats)->filter(fn ($seat) => $seat['seat_status'] !== 'available')->count();

        return [[
            'floor_id' => $firstFloor->id ?? 0,
            'floor_name' => '',
            'total_seats' => count($seats),
            'available_seats' => count($seats) - $occupiedSeats,
            'occupied_seats' => $occupiedSeats,
            'seats' => $seats,
        ]];
    }

    private function formatGeneralSeatPlanTypes($planTypes, $seatDetails, $transactions, ?string $planTypeStatus = null, $futureDetails = null)
    {
        $detailsByPlanType = collect($seatDetails)->groupBy('plan_type_id');
        $futureDetails = collect($futureDetails)->filter(fn ($detail) => $detail->planType !== null);

        return $planTypes->flatMap(function ($planType) use ($detailsByPlanType, $transactions, $futureDetails) {
            $details = $detailsByPlanType->get($planType->id, collect())->values();

            if ($details->isEmpty()) {
                $futureDetail = $this->firstOverlappingFutureDetail($planType, $futureDetails);

                return [[
                    'plan_type_id' => $planType->id,
                    'plan_type_name' => $planType->name,
                    'plan_type_status' => $futureDetail ? 'future' : 'available',
                    'learner' => $futureDetail ? $this->formatSeatLearner($futureDetail, $transactions) : null,
                ]];
            }

            return $details->map(function ($detail) use ($planType, $transactions) {
                return [
                    'plan_type_id' => $planType->id,
                    'plan_type_name' => $planType->name,
                    'plan_type_status' => $this->seatPlanTypeStatus($detail, $transactions),
                    'learner' => $this->formatSeatLearner($detail, $transactions),
                ];
            })->all();
        })
            ->when($planTypeStatus, fn ($items) => $items->filter(fn ($planType) => ($planType['plan_type_status'] ?? null) === $planTypeStatus))
            ->values()
            ->all();
    }

    private function formatSeatPlanTypes($planTypes, $seatDetails, $transactions, ?string $planTypeStatus = null, $futureDetails = null)
    {
        $detailsByPlanType = collect($seatDetails)->keyBy('plan_type_id');
        $bookedPlanTypes = collect($seatDetails)
            ->pluck('planType')
            ->filter();
        $futureDetails = collect($futureDetails)->filter(fn ($detail) => $detail->planType !== null);

        return $planTypes->filter(function ($planType) use ($detailsByPlanType, $bookedPlanTypes) {
            $detail = $detailsByPlanType->get($planType->id);

            if ($detail) {
                return true;
            }

            return ! $this->planTypeOverlapsAnyBookedPlanType($planType, $bookedPlanTypes);
        })->map(function ($planType) use ($detailsByPlanType, $transactions, $futureDetails) {
            $detail = $detailsByPlanType->get($planType->id);
            $futureDetail = null;

            if ($detail) {
                $status = $this->seatPlanTypeStatus($detail, $transactions);
            } else {
                $futureDetail = $this->firstOverlappingFutureDetail($planType, $futureDetails);
                $status = $futureDetail ? 'future' : 'available';
            }

            return [
                'plan_type_id' => $planType->id,
                'plan_type_name' => $planType->name,
                'plan_type_status' => $status,
                'learner' => $detail
                    ? $this->formatSeatLearner($detail, $transactions)
                    : ($futureDetail ? $this->formatSeatLearner($futureDetail, $transactions) : null),
            ];
        })
            ->when($planTypeStatus, fn ($items) => $items->filter(fn ($planType) => ($planType['plan_type_status'] ?? null) === $planTypeStatus))
            ->values()
            ->all();
    }

    /**
     * Earliest future booking (already ordered by plan_start_date at the query in
     * getSeatMapDetails) whose plan type's daily time range overlaps $planType.
     */
    private function firstOverlappingFutureDetail($planType, $futureDetails)
    {
        foreach ($futureDetails as $detail) {
            if ($this->planTypeTimesOverlap($planType, $detail->planType)) {
                return $detail;
            }
        }

        return null;
    }

    private function planTypeOverlapsAnyBookedPlanType($planType, $bookedPlanTypes): bool
    {
        foreach ($bookedPlanTypes as $bookedPlanType) {
            if ($this->planTypeTimesOverlap($planType, $bookedPlanType)) {
                return true;
            }
        }

        return false;
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

    private function seatPlanTypeStatus($detail, $transactions)
    {
        $transaction = $transactions->get($detail->learner_id);
        $pendingAmount = (float) ($transaction->pending_amount ?? 0);
        $extraAmount = (float) ($transaction->extra_amount ?? 0);
        $extendDay = $this->seatMapPrecomputed[$detail->learner_id]['extend_day'] ?? null;
        $planStatus = getPlanStatusDetails($detail->plan_end_date, $extendDay);
        // learner is already eager-loaded on $detail (LearnerDetail::with('learner')) — no query needed.
        $isNonExpiry = (int) ($detail->learner->no_expiry ?? 0) === 1
            && (int) ($detail->learner->status ?? 0) === 1;

        if ($isNonExpiry) {
            return 'non expire';
        }

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
        $precomputed = $this->seatMapPrecomputed[$detail->learner_id] ?? null;
        $planStatus = getPlanStatusDetails($detail->plan_end_date, $precomputed['extend_day'] ?? null);

        return [
            'learner_id' => $learner->id,
            'learner_no' => $learner->learner_no,
            'name' => $learner->name,
            'plan' => $detail->plan->name ?? '',
            'profile_image' => $learner->profile_picture ? asset($learner->profile_picture) : '',
            'plan_start_date' => $detail->plan_start_date,
            'plan_end_date' => $detail->plan_end_date,
            'status' => strip_tags(getUserStatusWithSpan($detail->plan_end_date, $learner->id, $precomputed)),
            'frozen_status'=>$learner->frozen_status,
            'freeze_date'=>$detail->freeze_start_date ?? '',
            'pending_amount' => (string) ($transaction->pending_amount ?? 0),
           'pending_payment_overdue' => $transaction->due_date && $transaction->due_date < date('Y-m-d'),
           'due_date'=>$transaction->due_date,
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
        if($amount >0){
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
}
