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
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use App\DTO\LearnerOperationDTO;
use App\Enums\LearnerOperation;
use App\Services\PlanService;
use App\Services\TransactionActivityService;
use Exception;
use Log;

class LearnerOperationService
{
    public function __construct(
        private TransactionActivityService $transactionActivityService,
        private LearnerOperationLogService $operationLogService
    )
    {
    }

    public function process($dto)
    {
        DB::beginTransaction();

        try {
           

            /* Load learner + last detail */
            [$customer,$lastDetail] = $this->loadLearnerData($dto);
            $beforeOperation = [
                'learner' => $customer->only([
                    'name', 'email', 'mobile', 'dob', 'father_name', 'address', 'remark',
                    'alternate_mobile', 'id_proof_name', 'id_proof_number', 'exam_id',
                    'no_expiry', 'locker_no', 'seat_no',
                ]),
                'detail' => $lastDetail->only([
                    'plan_id', 'plan_type_id', 'plan_price_id', 'plan_start_date',
                    'plan_end_date', 'seat_no',
                ]),
            ];

            if ($dto->operation == 'EDIT') {
                $this->fillEditDefaults($dto, $lastDetail);
            }
           
        

            /* Plan dates */
             if($dto->operation=='CHANGE PLAN'){
                 $start_date = Carbon::parse($lastDetail->plan_start_date);
                 
             }elseif($dto->operation=='REACTIVE'){
                $start_date =$start_date = $dto->start_date
                ? Carbon::parse($dto->start_date) : Carbon::now();
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

            if($dto->operation=='REACTIVE'){
                $seat=$dto->seat_no;    
            }else{
                $seat=$lastDetail->seat_no;
            }
            
            
            /* Seat check */
           
            $startDateBlocked = false;
            if($seat){
                $seatCheck=['error'=>false];
               
                 if(in_array($dto->operation,['RENEW','UPGRADE','REACTIVE','EDIT'])){
                    $seatCheck = checkAvailability($dto->branch_id,$seat,$dto->learner_id,$dto->plan_type_id,$dto->plan_id,$start_date);
                 }
                 if($dto->operation=='CHANGE PLAN'){
                    $seatCheck = checkSeatAvailability($seat,$dto->learner_id,$dto->plan_type_id,$start_date,$endDate,$lastDetail->id);
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
          
            \Log::info('Learner for end date', ['endDate' => $endDate]);
            /* Billing */

            $billing = $this->calculateBilling($dto,$customer,$start_date,app(PlanService::class));
          

            /* Status */

            [$status,$detailstatus] = $this->calculateStatus($customer,$lastDetail,$start_date,$endDate,$billing['is_paid'],$dto->branch_id,$dto->operation);

            /* Create / Update Detail */
 

            if(in_array($dto->operation,['RENEW','UPGRADE','REACTIVE'])){

                $detail = $this->createDetail($dto,$start_date,$endDate,$hours,$detailstatus,$billing['is_paid'],$lastDetail->join_date,$seat,$billing);
               
            }else{
                
               
                $detail = $this->updateDetail($dto,$start_date,$endDate,$hours,$detailstatus,$seat,$startDateBlocked,$billing);

            }

            /* Transaction */

            $this->createTransaction($dto,$detail,$billing,$start_date,app(PlanService::class));

            /* Update learner */

            $this->updateLearner($dto,$detail,$status,$seat);
            
            if ( in_array($dto->operation, ['RENEW', 'UPGRADE', 'REACTIVE'], true) && $detailstatus === 1 ) { 
                LearnerDetail::where('learner_id', $dto->learner_id) ->where('id', '!=', $detail->id) ->update(['status' => 0]); 
            }

            $this->logOperation($dto, $detail, $lastDetail, $seat, $beforeOperation);

            /*send reminder */
            $this->sendReminder($dto->operation,$dto->learner_id);

            DB::commit();

            return [
                'status'=>true,
                'start_date_blocked' => $startDateBlocked,
                'message'=>'Operation completed'
            ];

        } catch (\Throwable $e) {

            DB::rollBack();

            return [
                'status'=>false,
                'message'=>$e->getMessage()
            ];
        }
    }


    private function loadLearnerData($dto)
    {
        $customer = Learner::withTrashed()->findOrFail($dto->learner_id);

        if ($customer->trashed() && $dto->operation !== 'REACTIVE') {
            throw new Exception("Learner is deleted. Reactivate before performing this operation.");
        }

        if ($dto->operation === 'REACTIVE' && !$customer->trashed() && (int) $customer->status === 1) {
            throw new Exception("Learner is already active.");
        }

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

    private function fillEditDefaults($dto, $lastDetail): void
    {
        $dto->plan_id ??= $lastDetail->plan_id;
        $dto->plan_type_id ??= $lastDetail->plan_type_id;
        $dto->plan_price ??= $lastDetail->plan_price_id;
    }


    private function calculateBilling($dto,$customer,$start_date,PlanService $priceService)
    {
        $lockerAmount = $this->resolveLockerInput($dto, $customer);
        [$discountType, $discountAmount] = $this->resolveDiscountInput($dto, $customer);

        $result = $priceService->calculatePrice($dto->plan_id,$dto->plan_type_id,$start_date,$dto->branch_id,$lockerAmount,$discountType,$discountAmount,$dto->paid_amount ?? 0
          
        );
     
       
        $planPrice =$dto->plan_price;

        $locker = $result['locker_amount']  ?? 0;

        $discount =$result['discount_amount'] ;

       
        $effective = $planPrice+$locker-$discount;

        if($dto->operation=='CHANGE PLAN' || $dto->operation=='EDIT'){
            $learnerTransaction = LearnerTransaction::where('learner_id', $customer->id)->latest()->first();
        
             $old_price      = (float) ($learnerTransaction->paid_amount ?? 0);
             $old_pending      = (float) ($learnerTransaction->pending_amount ?? 0);
             $old_pending_refund      = (float) ($learnerTransaction->refund ?? 0);
            $old_total = (float) ($learnerTransaction->total_amount ?? 0);
            $total_difference = $effective - $old_total;
            $diff_amount = $dto->operation === 'EDIT' && $dto->diffrence_amount === null
                ? $total_difference
                : (float) ($dto->diffrence_amount ?? 0);
            $paid_amount = $dto->operation === 'EDIT'
                ? $old_price + (((int) $dto->payment_mode === 3) ? 0 : max(0, $diff_amount))
                : $old_price + $diff_amount;
            if ((int) $dto->payment_mode !== 3 && $diff_amount > $effective) {
                throw new Exception("Paid amount not valid");
            }
            $pending_amount =$effective-$paid_amount;
             if ($dto->payment_mode == 3) {
                // CHANGE PLAN and EDIT both modify the same already-billed transaction, so
                // paylater must be old_price-aware for both — not just EDIT. Otherwise a
                // CHANGE PLAN done via paylater forgets everything already collected and
                // treats the whole new total as freshly owed.
                $paid_amount = $old_price;
                $pending_amount = $effective;
            }
            $activityamount = 0;
            $pending_refund = $old_pending_refund;

            if ((int) $dto->payment_mode === 3) {
                $pending_amount = $effective - $old_price;
                if ($pending_amount < 0) {
                    $pending = 0;
                    $pending_refund = abs($pending_amount) + $pending_refund;
                    $dr_cr = 'Dr';
                } else {
                    $pending = $pending_amount;
                    $pending_refund = 0;
                    $dr_cr = 'Cr';
                }
                // Paylater defers settlement — no cash actually moves now, so the activity
                // amount is 0 (the real owed/refund figures live on the transaction's own
                // pending/refund columns). We still log an activity — with payment_mode
                // PAYLATER — whenever the edit actually changed something, so the audit
                // trail shows an adjustment happened even though nothing was collected.
                $activityamount = 0;
                // Use total_difference, not pending_amount, to detect a real change: if an
                // earlier edit created a refund/pending and this edit reverses it exactly
                // (back to old_price), pending_amount lands on 0 even though something
                // genuinely changed and should still be logged.
                $has_adjustment = $total_difference != 0.0;

            // Handle difference amount (refund vs pending)
            } elseif ($diff_amount < 0 || $pending_amount <0 || $total_difference < 0) {

                 // refund case: total owed back to the learner given the new (lower) total,
                // vs. how much of that is actually being handed back right now. The app can
                // split this — e.g. total owed 120, hand back 100 offline now, leave 20 as
                // pending_refund to settle later — by sending a smaller diffrence_amount than
                // the full difference. If it doesn't specify one, we settle the whole amount
                // now (same as it always has for a plain refund).
                $totalRefundOwed = abs($pending_amount);
                $refundedNow = abs($diff_amount ?: $total_difference);

                if ($refundedNow > $totalRefundOwed + 0.01) {
                    throw new Exception("Refund amount not valid");
                }

                $refundedNow = min($refundedNow, $totalRefundOwed);
                $paid_amount = $old_price - $refundedNow;
                $activityamount = $refundedNow;
                $pending_refund = ($totalRefundOwed - $refundedNow) + $old_pending_refund;
                $pending = 0;
                $dr_cr = 'Dr';
                $has_adjustment = true;
            } else {

              // extra payment (pending dues)
                $pending = $pending_amount ?? 0;
                $activityamount = $diff_amount;
                $pending_refund = 0;
                $dr_cr = 'Cr';
                $has_adjustment = $activityamount != 0.0;
            }


        }else{
            $paid_amount=$dto->paid_amount;
            if ($dto->payment_mode == 3) {
                // Paylater: nothing is actually collected now, so pending must reflect the
                // full effective amount — not effective minus whatever paid_amount the app
                // happened to send before the user picked paylater.
                $paid_amount = 0;
            }
            $pending = $effective - $paid_amount;
            $activityamount=$paid_amount;
            $pending_refund=0;
             $dr_cr = 'Cr';

            $oldPending = LearnerTransaction::where(
                'learner_id',$customer->id
            )->where('pending_amount','>',0)->sum('pending_amount');

            if((int) $dto->payment_mode !== 3 && $dto->paid_amount > ($effective+$oldPending)){
                throw new Exception("Paid amount not valid");
            }
        }

        

        if($pending>0 && empty($dto->due_date) && (int) $dto->payment_mode !== 3){
            throw new Exception("Due date required");
        }

        $is_paid = in_array($dto->payment_mode,[1,2]) ? 1 : 0;

        return [
            'price'=>$planPrice,
            'paid'=>$paid_amount,
            'total_amount'=>$effective,
            'pending'=>$pending,
            'discount'=>$discount,
            'locker'=>$locker,
            'is_paid'=>$is_paid,
            'activityamount'=>$activityamount,
            'has_adjustment'=>$has_adjustment ?? true,
            'pending_refund'=>$pending_refund,
            'dr_cr'=>$dr_cr
        ];
    }

    private function resolveLockerInput($dto, $customer): float
    {
        $lockerKeysPresent = ($dto->locker_present ?? false)
            || ($dto->locker_no_present ?? false)
            || ($dto->locker_amount_present ?? false);

        if (! $lockerKeysPresent && in_array($dto->operation, ['CHANGE PLAN', 'EDIT'])) {
            return (float) (LearnerTransaction::where('learner_id', $customer->id)
                ->latest()
                ->value('locker_amount') ?? 0);
        }

        return (float) ($dto->locker_amount ?? 0);
    }

    private function resolveDiscountInput($dto, $customer): array
    {
        $discountKeysPresent = ($dto->discount_type_present ?? false)
            || ($dto->discount_amount_present ?? false);

        if (! $discountKeysPresent && in_array($dto->operation, ['CHANGE PLAN', 'EDIT'])) {
            $oldDiscount = LearnerTransaction::where('learner_id', $customer->id)
                ->latest()
                ->value('discount_amount');

            return ($oldDiscount ?? 0) > 0
                ? ['amount', (float) $oldDiscount]
                : [null, 0.0];
        }

        return [$dto->discount_type ?: null, $dto->discount_amount ?? 0.0];
    }


    private function calculateStatus( $customer,$lastDetail,$start_date,$endDate,$is_paid,$branchId,$operation)
    {

        $extendDay = (int) getExtendDays($branchId);

        $today = Carbon::today();

        $startDate = Carbon::parse($start_date);

        $endDate = Carbon::parse($endDate);

        $oldEnd = Carbon::parse($lastDetail->plan_end_date);

        $oldExtendEnd = $oldEnd->copy()->addDays($extendDay);



        // --- learner_detail.status ---

        if (in_array($operation, ['RENEW', 'UPGRADE', 'REACTIVE'], true)) {



            // Future booking

            if ($startDate->gt($today)) {

                $detailstatus = 0;



            // Renew/upgrade from extension period

            } elseif ($oldEnd->lte($today) && $oldExtendEnd->gte($today) && $endDate->gt($today)) {

                $detailstatus = 1;



            // New row: end_date only (no extend on new end)

            } elseif ($startDate->lte($today) && $endDate->gte($today)) {

                $detailstatus = 1;



            } else {

                $detailstatus = 0;

            }



        } elseif ($operation === 'EDIT') {

            $editedExtendEnd = $endDate->copy()->addDays($extendDay);

            if ($startDate->lte($today) && $editedExtendEnd->gte($today)) {

                $detailstatus = 1;

            } else {

                $detailstatus = 0;

            }

        } elseif ($operation === 'CHANGE PLAN') {

            if ($startDate->lte($today) && $endDate->gte($today)) {

                $detailstatus = 1;

            } else {

                $detailstatus = 0;

            }



        } else {

            $detailstatus = 0;

        }



        // --- learners.status ---

        $activeDetailQuery = LearnerDetail::where('learner_id', $customer->id)

            ->where('status', 1)

            ->whereDate('plan_start_date', '<=', $today)

            ->whereRaw(

                'DATE_ADD(plan_end_date, INTERVAL ? DAY) >= ?',

                [$extendDay, $today->toDateString()]

            );



        if (in_array($operation, ['EDIT', 'CHANGE PLAN'], true)) {

            $activeDetailQuery->where('id', '!=', $lastDetail->id);

        }



        $hasActiveDetailToday = $activeDetailQuery->exists();



        if ($detailstatus === 1 || $hasActiveDetailToday) {

            $status = 1;

        } elseif ($startDate->gt($today)) {

            $status = $customer->status;

        } else {

            $status = 0;

        }



        return [$status, $detailstatus];

    }

    // private function calculateStatus( $customer,$lastDetail,$start_date,$endDate,$is_paid,$branchId){

    //     $extendDay = getExtendDays($branchId);

    //     $inextendDate = Carbon::parse($endDate)->addDays($extendDay);

    //     $today = Carbon::today();
    //     $hasActiveDetailToday = LearnerDetail::where('learner_id', $customer->id)
    //         ->where('status', 1)
    //         ->whereDate('plan_start_date', '<=', $today)
    //         ->whereDate('plan_end_date', '>=', $today)
    //         ->exists();

    //     if ($customer->status == 0 && ($start_date <= $today || $hasActiveDetailToday)) {
    //         $status = 1;
    //     } elseif ($start_date > $today) {
    //         // If renewal is queued for future but learner is currently active,
    //         // keep parent learner active.
    //         $status = $hasActiveDetailToday ? 1 : $customer->status;
    //     } else {
    //         $status = $customer->status;
    //     }

     
       
    //     if(Carbon::parse($lastDetail->plan_end_date) <= $today && $endDate > $today && $is_paid == 1){
           
    //         $detailstatus = 1;

    //     }elseif($inextendDate >= $today && $start_date <= $today){
          
    //         $detailstatus = 1;

    //     }else{
           
    //         $detailstatus = 0;
    //     }

    //     return [$status,$detailstatus];
    // }



    private function createDetail($dto,$start_date,$endDate, $hours,$detailstatus,$is_paid,$join_date,$seat,$billing){

        return LearnerDetail::create([

            'library_id'=>$dto->library_id,
            'branch_id'=>$dto->branch_id,
            'learner_id'=>$dto->learner_id,
            'plan_id'=>$dto->plan_id,
            'plan_type_id'=>$dto->plan_type_id,
            'plan_price_id'=>$billing['price'],
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
        $detailstatus,$seat,$startDateBlocked,$billing
    ){

        $detail = LearnerDetail::where(
            'learner_id',
            $dto->learner_id
        )->latest()->first();
        

        $detail->update([

            'plan_id'=>$dto->plan_id,
            'plan_type_id'=>$dto->plan_type_id,
            'plan_price_id'=>$billing['price'],

            'seat_no'=>$seat,
       
            'hour'=>$hours,

            // 'plan_end_date'=>$endDate,

            'status'=>$detailstatus,
            'is_paid' => $billing['is_paid'],
            // 'payment_mode' => $dto->payment_mode,
        ]);
       
        if($dto->operation == 'EDIT' && !$startDateBlocked ){
            $detail->plan_start_date = $start_date;
            $detail->plan_end_date = $endDate;
            $detail->save();
        }
        if($dto->operation == 'CHANGE PLAN' ){
            $detail->plan_end_date = $endDate;
            $detail->save();
        }

        return $detail;
    }

// private function editDetail(
//         $dto,
//         $start_date,
//         $endDate,
//         $hours,
//         $detailstatus,
//         $seat
//     ){
//         $detail = LearnerDetail::where('learner_id',$dto->learner_id)
//             ->latest()
//             ->first();

//         if(!$detail){
//             throw new Exception("Learner detail not found");
//         }

//         if($dto->exam_id){
//             $detail->exam_id = $dto->exam_id;
//         }

//         if($dto->plan_id){
//             $detail->plan_id = $dto->plan_id;
//         }

//         if($dto->plan_type_id){
//             $detail->plan_type_id = $dto->plan_type_id;
//         }

//         if($dto->plan_price){
//             $detail->plan_price_id = $dto->plan_price;
//         }

//         if($dto->start_date){
//             $detail->plan_start_date = $start_date;
//             $detail->plan_end_date   = $endDate;
//         }

//         if($seat){
//             $detail->seat_no = $seat;
//         }

//         $detail->hour = $hours;
//         $detail->status = $detailstatus;

//         $detail->save();

//         return $detail;
// }


    public function updateLearner($dto, $detail = null, $status = null, $seat = null)
    {
        Log::info('DTO', [
            'dto' => $dto,
        ]);
        
        $learner = Learner::findOrFail($dto->learner_id);

        if($learner->trashed()){
            $learner->restore();
        }

        $detail ??= LearnerDetail::withTrashed()
            ->where('learner_id', $dto->learner_id)
            ->latest('id')
            ->first();

        $seat ??= $dto->seat_no ?? $detail?->seat_no ?? $learner->seat_no;
        $status ??= $learner->status;

        $learner->seat_no = $seat;

        if ($detail) {
            $learner->hours = $detail->hour;
        }

        $learner->status = $status;

        if (($dto->locker_present ?? false) || ($dto->locker_no_present ?? false)) {
            $learner->locker_no = $dto->locker === 'no' ? null : $dto->locker_no;
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

  

       if ($dto->dob) {
            try {
                $learner->dob = \Carbon\Carbon::parse($dto->dob)->format('Y-m-d');
            } catch (\Exception $e) {
                throw new Exception('Invalid date format for dob.');
            }
        }

        if($dto->father_name !== null){
            $learner->father_name = $dto->father_name === '' ? null : $dto->father_name;
        }

        if($dto->alternate_mobile !== null){
            $learner->alternate_mobile = $dto->alternate_mobile === '' ? null : $dto->alternate_mobile;
        }

        if($dto->address !== null){
            $learner->address = $dto->address === '' ? null : $dto->address;
        }

        if($dto->remark !== null){
            $learner->remark = $dto->remark === '' ? null : $dto->remark ;
        }

        if ($dto->id_proof_name === '' || is_null($dto->id_proof_name)) {
            $learner->id_proof_name = null;
            $learner->id_proof_number = null;
            $learner->id_proof_file = null;
        } else {
            $learner->id_proof_name = $dto->id_proof_name;

            if ($dto->id_proof_number !== null) {
                $learner->id_proof_number = $dto->id_proof_number === ''
                    ? null
                    : $dto->id_proof_number;
            }

            if ($dto->id_proof_file === '' || is_null($dto->id_proof_file)) {
                $learner->id_proof_file = null;
            } elseif (!empty($dto->id_proof_file)) {
                $learner->id_proof_file = $this->moveTempFileToPublic(
                    $dto->id_proof_file,
                    'id_proof_file',
                    'upload/id_proof_file'
                );
            }
        }

      
        if ($dto->profile_picture === '' || is_null($dto->profile_picture)) {
        
            $learner->profile_picture = null;
        } else if (!empty($dto->profile_picture)) {
           
            $learner->profile_picture = $this->moveTempFileToPublic(
                $dto->profile_picture,
                'profile_picture',
                'upload/profile_picture'
            );
        }
        

       

        if ($dto->no_expiry !== null) {
            $learner->no_expiry = $dto->no_expiry;
        }

        if ($dto->sended_message_type !== null) {
            $learner->sended_message_type = $dto->sended_message_type;
        }

        $learner->save();

        if ($dto->exam_id_present ?? false) {
            LearnerDetail::withTrashed()
                ->where('learner_id', $dto->learner_id)
                ->latest('id')
                ->first()
                ?->update(['exam_id' => $dto->exam_id]);
        }
    }


    public function createTransaction($dto,$detail,$billing,$start_date){
        $data=[

            'planPrice' => $billing['price'],
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
            'due_date_present' => $dto->due_date_present ?? false,

            'branchId' => $dto->branch_id,
            'library_id' => $dto->library_id,

            'is_paid' => $billing['is_paid'],
            'dr_cr'=> $billing['dr_cr'],
            'activityamount'=>$billing['activityamount'],
            'has_adjustment'=>$billing['has_adjustment'] ?? true,
            'pending_refund'=>$billing['pending_refund'],
            'total_amount'=>$billing['total_amount'],
            'pending'=>$billing['pending'],

        ];
        if($dto->operation=='CHANGE PLAN' || $dto->operation=='EDIT'){
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
        if ($data['payment_type'] !== 'EDIT' || ($data['due_date_present'] ?? false)) {
            $learnerTransaction->due_date = $data['due_date'] ?? null;
        }
        $learnerTransaction->discount_amount = $data['discount'];
        $learnerTransaction->is_paid = $data['is_paid'];

        $learnerTransaction->save();

        // if ($data['payment_type'] === 'EDIT') {
        //     $this->updateOriginalTransactionActivity($learnerTransaction, $data);
        // }

        if ($data['payment_type'] === 'EDIT' && !($data['has_adjustment'] ?? true)) {
            return;
        }

         $activityData = [
                'learner_id'   => $data['learner_id'],
                'branchId'     => $data['branchId'] ?? null,
                'learner_transaction_id' => $learnerTransaction->id,
                'particular'   => $data['particular'] ?? 'Paid By Trans',
                'payment_type' => $data['payment_type'],
                'payment_mode' => $data['payment_mode'],
                'amount'       => $data['activityamount'],
                'dr_cr'        => $data['dr_cr'],
            ];
            $this->learnerTransactionActivity($activityData);
    }

    // private function updateOriginalTransactionActivity(LearnerTransaction $learnerTransaction, array $data): void
    // {
    //     $activity = LearnerTransactionActivity::withoutGlobalScopes()
    //         ->where('learner_id', $data['learner_id'] ?? $learnerTransaction->learner_id)
    //         ->where(function ($query) use ($learnerTransaction) {
    //             $query->where('learner_transaction_id', $learnerTransaction->id);

    //             if (! empty($learnerTransaction->transaction_id)) {
    //                 $query->orWhere('transaction_id', $learnerTransaction->transaction_id);
    //             }
    //         })
    //         ->whereNotIn('payment_type', ['TOKEN MONEY', 'MISCELLANEOUS', 'REFUND', 'SETTLED', 'EDIT'])
    //         ->orderBy('id')
    //             ->first();

    //     if (! $activity) {
    //         return;
    //     }

    //     $activity->payment_mode = $this->activityPaymentModeLabel($data['payment_mode'] ?? null);
    //     $activity->amount = (int) ($data['payment_mode'] ?? 0) === 3
    //         ? 0
    //         : (float) ($learnerTransaction->paid_amount ?? 0);

    //     $activity->save();
    // }

    // private function activityPaymentModeLabel($paymentMode): string
    // {
    //     return match ((int) $paymentMode) {
    //         3 => 'PAYLATER',
    //         2 => 'OFFLINE',
    //         default => 'ONLINE',
    //     };
    // }

    // public function learnerTransactionAddUpdate($data)
    // {
    //     // 1. Calculate new plan total
    //     $effectivePaid = $data['planPrice'] + $data['locker'] - $data['discount'];

    //     if (!empty($data['paid_date'])) {
    //         $transaction_date = Carbon::parse($data['paid_date'])->format('Y-m-d');
    //     } elseif ($data['start_date']) {
    //         $transaction_date = $data['start_date']->format('Y-m-d');
    //     } else {
    //         $transaction_date = date('Y-m-d');
    //     }

    //     // 2. Get old pending transactions
    //     $pendingTransactions = LearnerTransaction::where('learner_id', $data['learner_id'])
    //         ->where('pending_amount', '>', 0)
    //         ->orderBy('id', 'asc')
    //         ->get();

    //     $oldPendingTotal = $pendingTransactions->sum('pending_amount');

    //     $paidAmount = $data['paid_amount'];

    //     // 3. Apply payment to old pending first
    //     $pendingPaid = min($paidAmount, $oldPendingTotal);
    //     $remainingForNewPlan = $paidAmount - $pendingPaid;

    //     $remainingPendingPayment = $pendingPaid;

    //     foreach ($pendingTransactions as $tran) {
    //         if ($remainingPendingPayment <= 0) {
    //             break;
    //         }

    //         $tranPending = $tran->pending_amount;

    //         if ($remainingPendingPayment >= $tranPending) {
    //             // Fully clear this transaction
    //             $paidNow = $tranPending;
    //             $newPending = 0;
    //         } else {
    //             // Partially clear
    //             $paidNow = $remainingPendingPayment;
    //             $newPending = $tranPending - $paidNow;
    //         }
    //         $total_paid=$tran->paid_amount + $paidNow;

    //         $tran->update([
    //             'paid_amount'    => $total_paid,
    //             'pending_amount' => $newPending,
    //             'paid_date'      => $transaction_date,
    //         ]);

    //         $remainingPendingPayment -= $paidNow;
    //     }

    //     // 4. New plan payment
    //     $newPlanPaid = max(0, $remainingForNewPlan);
    //     $newPlanPending = $effectivePaid - $newPlanPaid;

    //     if ($newPlanPending < 0) {
    //         $newPlanPending = 0;
    //     }

    //     // 5. Create new plan transaction
    //     $learnerTransaction = LearnerTransaction::create([
    //         'learner_id'        => $data['learner_id'],
    //         'library_id'=>$data['library_id'],
    //         'branch_id'=>$data['branchId'],
    //         'learner_detail_id' => $data['learner_detail_id'],
    //         'total_amount'      => $effectivePaid,
    //         'paid_amount'       => $newPlanPaid,
    //         'pending_amount'    => $newPlanPending,
    //         'locker_amount'     => $data['locker'] ?? 0,
    //         'discount_amount'   => $data['discount'] ?? 0,
    //         'paid_date'         => $transaction_date,
    //         'is_paid'           => $data['is_paid'] ?? 0,
    //         'due_date'          => $data['due_date'],
    //         'transaction_id'    => transaction_id(),
    //     ]);

    //     // 6. Activity entries

    //     // Pending payment activity
    //     if ($pendingPaid > 0) {
    //         $activityData1 = [
    //             'learner_id'   => $data['learner_id'],
    //             'particular'   => $data['particular'] ?? 'Paid By Trans',
    //             'payment_type' => 'PENDING',
    //             'payment_mode' => $data['payment_mode'],
    //             'amount'       => $pendingPaid,
    //             'dr_cr'        => $data['dr_cr'],
    //         ];
    //         $this->learnerTransactionActivity($activityData1);
    //     }

    //     // New plan payment activity
    //     if ($newPlanPaid >= 0) {
    //         $activityData2 = [
    //             'learner_id'   => $data['learner_id'],
    //             'particular'   => $data['particular'] ?? 'Paid By Trans',
    //             'payment_type' => $data['payment_type'],
    //             'payment_mode' => $data['payment_mode'],
    //             'amount'       => $newPlanPaid,
    //             'dr_cr'        => $data['dr_cr'],
    //         ];
    //         $this->learnerTransactionActivity($activityData2);
    //     }

    //     return $learnerTransaction;
    // }


     public function learnerTransactionAddUpdate($data)
    {
        return $this->transactionActivityService->learnerTransactionAddUpdate($data);
    }

    public function learnerTransactionActivity($data)
    {
        $this->transactionActivityService->learnerTransactionActivity($data);
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

    private function logOperation($dto, $detail, $lastDetail, $seat, array $beforeOperation): void
    {
        $operations = [
            'EDIT' => ['edit', 'learner_and_plan'],
            'CHANGE PLAN' => ['changePlan', 'plan_type_id'],
            'RENEW' => ['renewSeat', 'plan_id'],
            'UPGRADE' => ['learnerUpgrade', 'plan_type_id'],
            'REACTIVE' => ['reactive', 'seat_no'],
        ];

        if (! isset($operations[$dto->operation])) {
            return;
        }

        [$operation, $field] = $operations[$dto->operation];
        [$oldValue, $newValue] = match ($dto->operation) {
            'CHANGE PLAN', 'UPGRADE' => [
                    ['plan_id'=>$lastDetail->plan_id , 'plan_type_id'=>$lastDetail->plan_type_id],
                    ['plan_id'=>$detail->plan_id , 'plan_type_id'=>$detail->plan_type_id]
                
                ],
            'RENEW' => [$lastDetail->plan_id, $detail->plan_id],
            'REACTIVE' => [$lastDetail->seat_no, $seat],
            default => [$beforeOperation, [
                'learner' => Learner::withTrashed()->findOrFail($dto->learner_id)->only([
                    'name', 'email', 'mobile', 'dob', 'father_name', 'address', 'remark',
                    'alternate_mobile', 'id_proof_name', 'id_proof_number', 'exam_id',
                    'no_expiry', 'locker_no', 'seat_no',
                ]),
                'detail' => $detail->only([
                    'plan_id', 'plan_type_id', 'plan_price_id', 'plan_start_date',
                    'plan_end_date', 'seat_no',
                ]),
            ]],
        };

        $this->operationLogService->log(
            (int) $dto->learner_id,
            (int) $detail->id,
            $operation,
            $field,
            $oldValue,
            $newValue,
            $dto->operation.' completed'
        );
    }

    /**
     * Same contract as StoreLearnerRequest::moveTempFileToPublic:
     * - UploadedFile: move into public/{folder}
     * - string URL: if path contains /temp/, move from storage/app/public to public/{folder}; if already permanent, return relative path
     *
     * @param  \Illuminate\Http\UploadedFile|string  $file
     */
    public function moveTempFileToPublic($file, string $filePrefix = 'file', string $folder = 'upload'): ?string
    {
        if ($file instanceof \Illuminate\Http\UploadedFile) {
            $fileName = $filePrefix.'_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
            $destinationFolder = public_path($folder);
            if (! File::exists($destinationFolder)) {
                File::makeDirectory($destinationFolder, 0777, true);
            }
            $file->move($destinationFolder, $fileName);

            return 'public/'.$folder.'/'.$fileName;
        }

        if (is_string($file)) {
            $path = parse_url($file, PHP_URL_PATH);

            if (str_contains($file, '/temp/')) {
                if (str_contains((string) $path, '/storage/')) {
                    $path = substr($path, strpos($path, '/storage/') + 9);
                }
                if (! str_starts_with((string) $path, 'temp/')) {
                    return null;
                }
                $sourcePath = storage_path('app/public/'.$path);
                if (File::exists($sourcePath)) {
                    $fileName = $filePrefix.'_'.time().'_'.uniqid().'.'.pathinfo((string) $path, PATHINFO_EXTENSION);
                    $destinationFolder = public_path($folder);
                    if (! File::exists($destinationFolder)) {
                        File::makeDirectory($destinationFolder, 0777, true);
                    }
                    $destinationPath = $destinationFolder.'/'.$fileName;
                    File::move($sourcePath, $destinationPath);

                    return 'public/'.$folder.'/'.$fileName;
                }
            }

            if (str_contains($file, '/upload/') || str_contains($file, 'upload/')) {
                $pos = strpos((string) $path, 'upload/');
                if ($pos !== false) {
                    return 'public/'.substr((string) $path, $pos);
                }
            }

            if (str_contains($file, '/uploade/') || str_contains($file, 'uploade/')) {
                $pos = strpos((string) $path, 'uploade/');
                if ($pos !== false) {
                    return 'public/'.substr((string) $path, $pos);
                }
            }

            if (str_contains($file, '/uploads/') || str_contains($file, 'uploads/')) {
                $pos = strpos((string) $path, 'uploads/');
                if ($pos !== false) {
                    return 'public/'.substr((string) $path, $pos);
                }
            }
        }

        return null;
    }

}
