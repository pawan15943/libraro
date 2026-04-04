<?php

namespace App\Services;

use App\Http\Controllers\NotificationSentController;
use App\Models\Branch;
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
use App\DTO\LearnerOperationDTO;
use App\Enums\LearnerOperation;
use Exception;
use Log;

class LearnerOperationService
{

    public function process($dto)
    {
        DB::beginTransaction();

        try {

            /* Load learner + last detail */
            [$customer,$lastDetail] = $this->loadLearnerData($dto);
           
         

            /* Plan dates */
             if($dto->operation=='CHANGE PLAN'){
                 $start_date = Carbon::parse($lastDetail->plan_start_date);
                 
             } elseif($dto->operation == 'EDIT'){
                $start_date = $dto->start_date
                    ? Carbon::parse($dto->start_date)
                    : Carbon::parse($lastDetail->plan_start_date);
            }else{
                if($dto->start_date){
                     $start_date = Carbon::parse($dto->start_date);
                }else{
                     $start_date = Carbon::parse($lastDetail->plan_end_date)->addDay();
                }
                
                
             }
           
          

            $endDate = getEndDate($dto->plan_id,$start_date,$dto->branch_id);
           

            /* Plan type hours */

            $planType = PlanType::findOrFail($dto->plan_type_id);
            $hours = $planType->slot_hours;
            if(in_array($dto->operation,['REACTIVE','EDIT'])){
                $seat=$dto->seat_no;
            }else{
                $seat=$lastDetail->seat_no;
            }
            
            
            
            /* Seat check */
           
            $startDateBlocked = false;
            if($seat){
               
                 if(in_array($dto->operation,['RENEW','UPGRADE','REACTIVE','EDIT'])){
                    $seatCheck = checkAvailability($dto->branch_id,$seat,$dto->learner_id,$dto->plan_type_id,$dto->plan_id,$start_date);
                 }
                 if($dto->operation=='CHANGE PLAN'){
                    $seatCheck = checkSeatAvailability($seat,$dto->learner_id,$dto->plan_type_id,$start_date,$endDate);
                 }

                if($seatCheck['error']){
                   if ($dto->operation == 'EDIT') {
                        // Only block start date update
                        $startDateBlocked = true;

                        // revert start date to previous
                        $start_date = Carbon::parse($lastDetail->plan_start_date);
                        $endDate = Carbon::parse($lastDetail->plan_end_date);

                    } else {
                        return [
                            'success' => false,
                            'message' => $seatCheck['message']
                        ];
                    }
                }
            }

            /* Billing */

            $billing = $this->calculateBilling($dto,$customer);
          

            /* Status */

            [$status,$detailstatus] = $this->calculateStatus($customer,$lastDetail,$start_date,$endDate,$billing['is_paid'],$dto->branch_id);

            /* Create / Update Detail */
  

            if(in_array($dto->operation,['RENEW','UPGRADE','REACTIVE'])){

                $detail = $this->createDetail($dto,$start_date,$endDate,$hours,$detailstatus,$billing['is_paid'],$lastDetail->join_date,$seat);
               
            }else{
                
               
                $detail = $this->updateDetail($dto,$start_date,$endDate,$hours,$detailstatus,$seat,$startDateBlocked);

            }

            /* Transaction */

            $this->createTransaction($dto,$detail,$billing,$start_date);

            /* Update learner */

            $this->updateLearner($dto,$detail,$status,$seat);

            /*send reminder */
            $this->sendReminder($dto->operation,$dto->learner_id);

            DB::commit();

            return [
                'success'=>true,
                'start_date_blocked' => $startDateBlocked,
                'message'=>'Operation completed'
            ];

        } catch (\Throwable $e) {

            DB::rollBack();

            return [
                'success'=>false,
                'message'=>$e->getMessage()
            ];
        }
    }


    private function loadLearnerData($dto)
    {
        $customer = Learner::withTrashed()->findOrFail($dto->learner_id);

        if(alreadyRenewed($customer->id)){
            throw new Exception("Already have plan in queue");
        }

        $lastDetail = LearnerDetail::withTrashed()->where('learner_id',$customer->id)
            ->latest('id')
            ->first();

        if(!$lastDetail){
            throw new Exception("Learner detail not found");
        }

        return [$customer,$lastDetail];
    }


    private function calculateBilling($dto,$customer)
    {

        $planPrice = $dto->plan_price;

        $locker = $dto->locker_amount ?? 0;

        $discount = 0;

        if($dto->discount_type=='amount'){
            $discount = $dto->discount_amount;
        }

        if($dto->discount_type=='percentage'){
            $discount =($planPrice+$locker)*$dto->discount_amount/100;
        }

        $effective = $planPrice+$locker-$discount;

        if($dto->operation=='CHANGE PLAN'){
            $learnerTransaction = LearnerTransaction::where('learner_id', $customer->id)->latest()->first();
        
             $old_price      = $learnerTransaction->paid_amount ?? 0;
             $old_pending      = $learnerTransaction->pending_amount ?? 0;
             $old_pending_refund      = $learnerTransaction->refund ?? 0;
            $diff_amount= $dto->diffrence_amount;
            $paid_amount= $old_price + $diff_amount;
            $pending_amount =$effective-$paid_amount;
             if ($dto->payment_mode == 3) {
                $pending_amount = $paid_amount;
                $paid_amount    = 0;
            }
            $activityamount = 0;
            $pending_refund = $old_pending_refund;

            
            // Handle difference amount (refund vs pending)
            if ($diff_amount < 0) {

                // refund case
                $activityamount = abs($diff_amount);
                $pending_refund = abs($pending_amount) + $pending_refund;
                $pending = 0;
                $dr_cr = 'Dr';
            } else {

              // extra payment (pending dues)
                $pending = $pending_amount ?? 0;
                $activityamount = $diff_amount;
                $pending_refund = 0;
                $dr_cr = 'Cr';
            }


        }else{
            $pending = $effective-$dto->paid_amount;
            $paid_amount=$dto->paid_amount;
            $activityamount=$paid_amount;
            $pending_refund=0;
             $dr_cr = 'Cr';

            $oldPending = LearnerTransaction::where(
                'learner_id',$customer->id
            )->where('pending_amount','>',0)->sum('pending_amount');

            if($dto->paid_amount > ($effective+$oldPending)){
                throw new Exception("Paid amount not valid");
            }
        }

        

        if($pending>0 && empty($dto->due_date)){
            throw new Exception("Due date required");
        }

        $is_paid = in_array($dto->payment_mode,[1,2]) ? 1 : 0;

        return [
            'paid'=>$paid_amount,
            'total_amount'=>$effective,
            'pending'=>$pending,
            'discount'=>$discount,
            'locker'=>$locker,
            'is_paid'=>$is_paid,
            'activityamount'=>$activityamount,
            'pending_refund'=>$pending_refund,
            'dr_cr'=>$dr_cr
        ];
    }


    private function calculateStatus( $customer,$lastDetail,$start_date,$endDate,$is_paid,$branchId){

        $extendDay = getExtendDays($branchId);

        $inextendDate = Carbon::parse($endDate)->addDays($extendDay);

        $today = Carbon::today();

        if($customer->status==0 && ($start_date <= $today)){
            $status = 1;
        }else{
            $status = $customer->status;
        }
       
       
        if(Carbon::parse($lastDetail->plan_end_date) < $today && $endDate > $today && $is_paid == 1){
           
            $detailstatus = 1;

        }elseif($inextendDate > $today && $start_date <= $today){
          
            $detailstatus = 1;

        }else{
           
            $detailstatus = 0;
        }

        return [$status,$detailstatus];
    }


    private function createDetail($dto,$start_date,$endDate, $hours,$detailstatus,$is_paid,$join_date,$seat){

        return LearnerDetail::create([

            'library_id'=>$dto->library_id,
            'branch_id'=>$dto->branch_id,
            'learner_id'=>$dto->learner_id,
            'plan_id'=>$dto->plan_id,
            'plan_type_id'=>$dto->plan_type_id,
            'plan_price_id'=>$dto->plan_price,
            'plan_start_date'=>$start_date,
            'plan_end_date'=>$endDate,
            'hour'=>$hours,
            'seat_no'=>$seat,
            'status'=>$detailstatus,
            'is_paid' => $is_paid,
            'payment_mode'=> $dto->payment_mode,
            'join_date' => $join_date,
        ]);
    }


    private function updateDetail(
        $dto,$start_date,
        $endDate,
        $hours,
        $detailstatus,$seat,$startDateBlocked
    ){

        $detail = LearnerDetail::where(
            'learner_id',
            $dto->learner_id
        )->latest()->first();

        $detail->update([

            'plan_id'=>$dto->plan_id,
            'plan_type_id'=>$dto->plan_type_id,
            'plan_price_id'=>$dto->plan_price,

            'seat_no'=>$seat,
       
            'hour'=>$hours,

            // 'plan_end_date'=>$endDate,

            'status'=>$detailstatus
        ]);
        if($dto->operation == 'EDIT' && !$startDateBlocked){
            $detail->plan_start_date = $start_date;
            $detail->plan_end_date = $endDate;
        }

        return $detail;
    }

   private function editDetail(
        $dto,
        $start_date,
        $endDate,
        $hours,
        $detailstatus,
        $seat
    ){
        $detail = LearnerDetail::where('learner_id',$dto->learner_id)
            ->latest()
            ->first();

        if(!$detail){
            throw new Exception("Learner detail not found");
        }

        if($dto->exam_id){
            $detail->exam_id = $dto->exam_id;
        }

        if($dto->plan_id){
            $detail->plan_id = $dto->plan_id;
        }

        if($dto->plan_type_id){
            $detail->plan_type_id = $dto->plan_type_id;
        }

        if($dto->plan_price){
            $detail->plan_price_id = $dto->plan_price;
        }

        if($dto->start_date){
            $detail->plan_start_date = $start_date;
            $detail->plan_end_date   = $endDate;
        }

        if($seat){
            $detail->seat_no = $seat;
        }

        $detail->hour = $hours;
        $detail->status = $detailstatus;

        $detail->save();

        return $detail;
    }


    private function updateLearner($dto,$detail,$status,$seat)
    {

        $learner = Learner::findOrFail($dto->learner_id);

        if($learner->trashed()){
            $learner->restore();
        }

        $learner->seat_no = $seat;

        $learner->hours = $detail->hour;

        $learner->status = $status;

        if($dto->locker_no){
            $learner->locker_no = $dto->locker_no;
        }
        
            // Optional profile fields
        if($dto->name){
            $learner->name = $dto->name;
        }

        if($dto->email){
            $learner->email = encryptData($dto->email);
        }

        if($dto->mobile){
            $learner->mobile = encryptData($dto->mobile);
        }

        if($dto->dob){
            $learner->dob = $dto->dob;
        }

        if($dto->father_name){
            $learner->father_name = $dto->father_name;
        }

        if($dto->address){
            $learner->address = $dto->address;
        }

        if($dto->remark){
            $learner->remark = $dto->remark;
        }


        $learner->save();
    }


    public function createTransaction($dto,$detail,$billing,$start_date){
        $data=[

            'planPrice' => $dto->plan_price,
            'paid_amount' => $billing['paid'],
            'locker' => $billing['locker'],
            'discount' => $billing['discount'],

            'start_date' => $start_date,

            'paid_date' => $dto->paid_date,

            'learner_detail_id' => $detail->id,
            'learner_id' => $dto->learner_id,

            'payment_type' => $dto->operation,

            'payment_mode' => $dto->payment_mode,

            'due_date' => $dto->due_date,

            'branchId' => $dto->branch_id,
            'library_id' => $dto->library_id,

            'is_paid' => $billing['is_paid'],
            'dr_cr'=> $billing['dr_cr'],
            'activityamount'=>$billing['activityamount'],
            'pending_refund'=>$billing['pending_refund'],
            'total_amount'=>$billing['total_amount'],
            'pending'=>$billing['pending'],

        ];
        if($dto->operation=='CHANGE PLAN'){
            return $this->learnerTransactionUpdate($data);
        }else{
            return $this->learnerTransactionAddUpdate($data);
        }

        
    }

    public function learnerTransactionUpdate($data){
        $learnerTransaction = LearnerTransaction::where('learner_detail_id', $data['learner_detail_id'])->latest()->first();
        $learnerTransaction->locker_amount =$data['locker'];
        $learnerTransaction->total_amount   = $data['total_amount'];
        $learnerTransaction->paid_amount    = $data['paid_amount'];
        $learnerTransaction->pending_amount = $data['pending'];
        $learnerTransaction->refund         = $data['pending_refund'];  
        $learnerTransaction->due_date       = $data['due_date'] ?? null;
        $learnerTransaction->discount_amount = $data['discount'];

        $learnerTransaction->save();
         $activityData = [
                'learner_id'   => $data['learner_id'],
                'particular'   => $data['particular'] ?? 'Paid By Trans',
                'payment_type' => $data['payment_type'],
                'payment_mode' => $data['payment_mode'],
                'amount'       => $data['activityamount'],
                'dr_cr'        => $data['dr_cr'],
            ];
            $this->learnerTransactionActivity($activityData);
    }

     public function learnerTransactionAddUpdate($data)
    {
        // 1. Calculate new plan total
        $effectivePaid = $data['planPrice'] + $data['locker'] - $data['discount'];

        if (!empty($data['paid_date'])) {
            $transaction_date = Carbon::parse($data['paid_date'])->format('Y-m-d');
        } elseif ($data['start_date']) {
            $transaction_date = $data['start_date']->format('Y-m-d');
        } else {
            $transaction_date = date('Y-m-d');
        }

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
            $total_paid=$tran->paid_amount + $paidNow;

            $tran->update([
                'paid_amount'    => $total_paid,
                'pending_amount' => $newPending,
                'paid_date'      => $transaction_date,
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
            'library_id'=>$data['library_id'],
            'branch_id'=>$data['branchId'],
            'learner_detail_id' => $data['learner_detail_id'],
            'total_amount'      => $effectivePaid,
            'paid_amount'       => $newPlanPaid,
            'pending_amount'    => $newPlanPending,
            'locker_amount'     => $data['locker'] ?? 0,
            'discount_amount'   => $data['discount'] ?? 0,
            'paid_date'         => $transaction_date,
            'is_paid'           => $data['is_paid'] ?? 0,
            'due_date'          => $data['due_date'],
            'transaction_id'    => transaction_id(),
        ]);

        // 6. Activity entries

        // Pending payment activity
        if ($pendingPaid > 0) {
            $activityData1 = [
                'learner_id'   => $data['learner_id'],
                'particular'   => $data['particular'] ?? 'Paid By Trans',
                'payment_type' => 'PENDING',
                'payment_mode' => $data['payment_mode'],
                'amount'       => $pendingPaid,
                'dr_cr'        => $data['dr_cr'],
            ];
            $this->learnerTransactionActivity($activityData1);
        }

        // New plan payment activity
        if ($newPlanPaid >= 0) {
            $activityData2 = [
                'learner_id'   => $data['learner_id'],
                'particular'   => $data['particular'] ?? 'Paid By Trans',
                'payment_type' => $data['payment_type'],
                'payment_mode' => $data['payment_mode'],
                'amount'       => $newPlanPaid,
                'dr_cr'        => $data['dr_cr'],
            ];
            $this->learnerTransactionActivity($activityData2);
        }

        return $learnerTransaction;
    }

    public function learnerTransactionActivity($data)
    {


        LearnerTransactionActivity::create([
            'branch_id'      => getCurrentBranch(),
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

    public function sendReminder($operation,$learnerId){
        if ($operation == 'CHANGE PLAN') {

            try {

                $noti = new NotificationSentController;

                // WABA Notification
                if (autowabaNotificationActive()) {
                    \Log::info('autowabaNotificationActive');
                    $noti->autoMessage($learnerId, 'waba', 'change-plan-waba');
                }

                // TEXT Notification
                if (autotextNotificationActive()) {
                    \Log::info('autotextNotificationActive');
                    $noti->autoMessage($learnerId, 'text', 'change-plan-sms');
                }
            } catch (\Throwable $e) {

                // Log the error (won't break your main code)
                \Log::error('Notification sending failed: ' . $e->getMessage(), [

                    'exception' => $e
                ]);
            }
        }

        if ($operation == 'UPGRADE') {

            try {

                $noti = new NotificationSentController;

                // WABA Notification
                if (autowabaNotificationActive()) {
                    \Log::info('autowabaNotificationActive');
                    $noti->autoMessage($learnerId, 'waba', 'upgrade-waba');
                }

                // TEXT Notification
                if (autotextNotificationActive()) {
                    \Log::info('autotextNotificationActive');
                    $noti->autoMessage($learnerId, 'text', 'upgrade-sms');
                }
            } catch (\Throwable $e) {

                // Log the error (won't break your main code)
                \Log::error('Notification sending failed: ' . $e->getMessage(), [

                    'exception' => $e
                ]);
            }
        }
        if ($operation == "RENEW") {
            $noti = new NotificationSentController;

            if (autowabaNotificationActive()) {
                \Log::info('autowabaNotificationActive');
                $noti->autoMessage($learnerId, 'waba', 'renew-waba');
            } else {
                \Log::info('nowaba seond part RENEW');
            }
            if (autotextNotificationActive()) {
                \Log::info('autotextNotificationActive');
                $noti->autoMessage($learnerId, 'text', 'renew-sms');
            } else {
                \Log::info('no text seond part RENEW');
            }
        }
    }

}