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

class LearnerOperationService
{

    public function process($dto)
    {
        DB::beginTransaction();

        try {

            /* Load learner + last detail */
            [$customer,$lastDetail] = $this->loadLearnerData($dto);
           
           

            /* Plan dates */

            $start_date = Carbon::parse(
                $dto->start_date ?? $lastDetail->plan_end_date
            )->addDay();
            

            $endDate = getEndDate($dto->plan_id,$start_date,$dto->branch_id);
           

            /* Plan type hours */

            $planType = PlanType::findOrFail($dto->plan_type_id);
            $hours = $planType->slot_hours;
             

            /* Seat check */

            if($dto->seat_no){
                
                $seatCheck = checkAvailability(
                    $dto->branch_id,
                    $dto->seat_no,
                    $dto->learner_id,
                    $dto->plan_type_id,
                    $dto->plan_id,
                    $start_date
                );
               
                if($seatCheck['error']){
                    return $seatCheck;
                }
            }

            /* Billing */

            $billing = $this->calculateBilling($dto,$customer);
          

            /* Status */

            [$status,$detailstatus] = $this->calculateStatus($customer,$lastDetail,$start_date,$endDate,$billing['is_paid'],$dto->branch_id);

            /* Create / Update Detail */
  

            if(in_array($dto->operation,['RENEW','UPGRADE','REACTIVE'])){

                $detail = $this->createDetail($dto,$start_date,$endDate,$hours,$detailstatus,$billing['is_paid'],$lastDetail->join_date);
               
            }else{
                

                $detail = $this->updateDetail($dto,$endDate,$hours,$detailstatus);

            }

            /* Transaction */

            $this->createTransaction($dto,$detail,$billing,$start_date);

            /* Update learner */

            $this->updateLearner($dto,$detail,$status);

            DB::commit();

            return [
                'success'=>true,
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
        $customer = Learner::findOrFail($dto->learner_id);

        if(alreadyRenewed($customer->id)){
            throw new Exception("Already have plan in queue");
        }

        $lastDetail = LearnerDetail::where('learner_id',$customer->id)
            ->latest()
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
            $discount =
            ($planPrice+$locker)*$dto->discount_amount/100;
        }

        $effective = $planPrice+$locker-$discount;

        $pending = $effective-$dto->paid_amount;

        $oldPending = LearnerTransaction::where(
            'learner_id',$customer->id
        )->where('pending_amount','>',0)->sum('pending_amount');

        if($dto->paid_amount > ($effective+$oldPending)){
            throw new Exception("Paid amount not valid");
        }

        if($pending>0 && empty($dto->due_date)){
            throw new Exception("Due date required");
        }

        $is_paid = in_array($dto->payment_mode,[1,2]) ? 1 : 0;

        return [
            'paid'=>$dto->paid_amount,
            'pending'=>$pending,
            'discount'=>$discount,
            'locker'=>$locker,
            'is_paid'=>$is_paid
        ];
    }


    private function calculateStatus( $customer,$lastDetail,$start_date,$endDate,$is_paid,$branchId){

        $extendDay = getExtendDays($branchId);

        $inextendDate = Carbon::parse($endDate)
            ->addDays($extendDay);

        $today = Carbon::today();

        if($customer->status==0 && ($start_date <= $today)){
            $status = 1;
        }else{
            $status = $customer->status;
        }

        if(
            Carbon::parse($lastDetail->plan_end_date) < $today
            && $endDate > $today
            && $is_paid == 1
        ){
            $detailstatus = 1;

        }elseif(
            $inextendDate > $today
            && $start_date <= $today
        ){
            $detailstatus = 1;

        }else{
            $detailstatus = 0;
        }

        return [$status,$detailstatus];
    }


    private function createDetail($dto,$start_date,$endDate, $hours,$detailstatus,$is_paid,$join_date){

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
            'seat_no'=>$dto->seat_no,
            'status'=>$detailstatus,
            'is_paid' => $is_paid,
            'payment_mode'=> $dto->payment_mode,
            'join_date' => $join_date,
        ]);
    }


    private function updateDetail(
        $dto,
        $endDate,
        $hours,
        $detailstatus
    ){

        $detail = LearnerDetail::where(
            'learner_id',
            $dto->learner_id
        )->latest()->first();

        $detail->update([

            'plan_id'=>$dto->plan_id,
            'plan_type_id'=>$dto->plan_type_id,
            'plan_price_id'=>$dto->plan_price,

            'seat_no'=>$dto->seat_no,

            'hour'=>$hours,

            'plan_end_date'=>$endDate,

            'status'=>$detailstatus
        ]);

        return $detail;
    }


    private function updateLearner($dto,$detail,$status)
    {

        $learner = Learner::findOrFail($dto->learner_id);

        if($learner->trashed()){
            $learner->restore();
        }

        $learner->seat_no = $detail->seat_no;

        $learner->hours = $detail->hour;

        $learner->status = $status;

        if($dto->locker_no){
            $learner->locker_no = $dto->locker_no;
        }

        $learner->save();
    }


    public function createTransaction($dto,$detail,$billing,$start_date){

        return $this->learnerTransactionAddUpdate([

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

            'is_paid' => $billing['is_paid']
        ]);
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

            $tran->update([
                'paid_amount'    => $tran->paid_amount + $paidNow,
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
                'dr_cr'        => 'Cr',
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
                'dr_cr'        => 'Cr',
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

}