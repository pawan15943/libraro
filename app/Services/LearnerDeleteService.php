<?php
namespace App\Services;

use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerTransaction;
use App\Models\LearnerTransactionActivity;
use Illuminate\Support\Facades\DB;
use Exception;

class LearnerDeleteService
{
    
    // public function processSettlement($request, $learnerId)
    // {
    //     $settlementMode = $request->settlement_mode;

    //     $detailIds = $request->learner_detail_ids ?? [];


    //     $details = LearnerDetail::whereIn('id', $detailIds)->get();
    //     $lastTx=LearnerTransaction::where('learner_id', $learnerId)->orderBy('DESC')->first();

    //     $tx = LearnerTransaction::query()
    //                 ->where('learner_id', $learnerId)
    //                 ->get();

    //     $total = $tx->sum('total_amount');
    //     $paid = $tx->sum('paid_amount');
    //     $pending = $tx->sum('pending_amount');
    //     $extra = $tx->sum('refund');

    //     $net = $pending - $extra;

    //     $ispending=false ;
    //     $isextra=false ;
    //     if($net > 0){
    //         $ispending=true ;

    //     }
    //     if($net < 0){
    //         $isextra=true;
    //     }

    //     if($request->payment_mode== 1){
    //         $paymentmode='ONLINE';
    //     }elseif($request->payment_mode== 2){
    //         $paymentmode='OFFLINE';
    //     }else{
    //         $paymentmode='PAYLATER';
    //     }



    //     $pay = (float) $request->pay_amount;

       
    //     if($request->is_refund==1 && $request->refund_amount ){
    //          $refund = (float) $request->refund_amount;

    //          if ($ispending==true) {
    //             throw new Exception("You have already pending amount");
    //         }


    //         $remainingRefund = $net-$refund;

    //         LearnerTransaction::where('learner_id', $learnerId)->update([
    //                 'refund'=>0,
    //             ]);
    //         if($remainingRefund < 0){
    //              $remaining_paid=$paid-$remainingRefund;
                
               
    //             LearnerTransaction::where('id',$lastTx->id)->update([
    //                 'paid_amount'=>$remaining_paid,
    //             ]);
    //         }
            
    //         if($remainingRefund > 0){
    //             if($settlementMode === 'refund_pending_future'){
    //                  LearnerTransaction::where('id',$lastTx->id)->update([
    //                     'refund'=>$remaining_paid,
    //                 ]);
    //             }
    //             if($settlementMode === 'adjust'){
    //                 $adddiscount=$lastTx->discount_amount + $remainingRefund;
    //                 LearnerTransaction::where('id',$lastTx->id)->update([
    //                     'discount_amount'=>$adddiscount,
    //                 ]);
    //             }

    //         }

    //         $type  = 'REFUND';
    //         $parti = 'SATTELED';
    //         $dr_cr='Dr';

    //         $payload = [
    //             'branch_id'      => getCurrentBranch(),
    //             'learner_id'     => $learnerId,
    //             'learner_transaction_id' => null,
    //             'date'           => now()->format('Y-m-d'),
    //             'transaction_id' => transaction_id(),
    //             'particular'     => $parti,
    //             'payment_type'   => $type,
    //             'payment_mode'   => $paymentmode,
    //             'amount'         => $refund ?? 0,
    //             'dr_cr'          => $dr_cr,
    //         ];


    //         LearnerTransactionActivity::create($payload);


    //     }else if($pay){
    //         if ($pay > $net) {
    //             throw new Exception("Pay amount exceeds pending");
    //         }

          

    //             // Get old pending transactions
    //         $pendingTransactions = LearnerTransaction::where('learner_id', $learnerId)
    //             ->where('pending_amount', '>', 0)
    //             ->orderBy('id', 'asc')
    //             ->get();

    //         $oldPendingTotal = $pendingTransactions->sum('pending_amount');

    //         // Apply payment to old pending first
    //         $pendingPaid = min($pay, $oldPendingTotal);
    //         $remainingForNewPlan = $pay - $pendingPaid;

    //         $remainingPendingPayment = $pendingPaid;

    //         foreach ($pendingTransactions as $tran) {
    //             if ($remainingPendingPayment <= 0) {
    //                 break;
    //             }

    //             $tranPending = $tran->pending_amount;

    //             if ($remainingPendingPayment >= $tranPending) {
    //                 // Fully clear this transaction
    //                 $paidNow = $tranPending;
    //                 $newPending = 0;
    //             } else {
    //                 // Partially clear
    //                 $paidNow = $remainingPendingPayment;
    //                 $newPending = $tranPending - $paidNow;
    //             }

    //             $updateData = [
    //                 'paid_amount'    => $tran->paid_amount + $paidNow,
    //                 'pending_amount' => $newPending,
    //                 'is_paid'        => $newPending == 0 ? 1 : 0,
    //             ];

                

    //             $tran->update($updateData);

    //             $remainingPendingPayment -= $paidNow;
    //         }

    //         $afterpayremaing=LearnerTransaction::where('learner_id', $learnerId)
    //             ->where('pending_amount', '>', 0)->get();
    //         $afterpayRemain=$afterpayremaing ?? $afterpayremaing->sum('pending_amount');
    //         if($settlementMode === 'adjust'){
    //              $adddiscount=$lastTx->discount_amount + $afterpayRemain;
    //                 LearnerTransaction::where('id',$lastTx->id)->update([
    //                     'discount_amount'=>$adddiscount,
    //                 ]);
    //         }

    //         $type  = 'PENDING';
    //         $parti = 'SATTELED';
    //         $dr_cr='Cr';

    //         $payload = [
    //             'branch_id'      => getCurrentBranch(),
    //             'learner_id'     => $learnerId,
    //             'learner_transaction_id' => null,
    //             'date'           => now()->format('Y-m-d'),
    //             'transaction_id' => transaction_id(),
    //             'particular'     => $parti,
    //             'payment_type'   => $type,
    //             'payment_mode'   => $paymentmode,
    //             'amount'         => $pay ?? 0,
    //             'dr_cr'          => $dr_cr,
    //         ];


    //         LearnerTransactionActivity::create($payload);

           
    //     }else{
           

    //         if($settlementMode === 'adjust'){
    //             if($ispending==true){
    //                 $adddiscount=$lastTx->discount_amount + $net;
    //                 LearnerTransaction::where('id',$lastTx->id)->update([
    //                     'discount_amount'=>$adddiscount,
    //                 ]);
    //             }
    //             if($isextra==true){
    //                  LearnerTransaction::where('id',$lastTx->id)->update([
    //                     'total_amount'=>$lastTx->total_amount+$net,
    //                     'paid_amount'=>$lastTx->paid_amount+$net,
    //                 ]);
    //             }
                
    //         }

    //     }


      


        

    //     return true ;
    // }

    public function processSettlement($request, $learnerId)
{
    $settlementMode = $request->settlement_mode;
    $refundInput    = (float) $request->refund_amount;
    $payInput       = (float) $request->pay_amount;

    $txList = LearnerTransaction::where('learner_id', $learnerId)
        ->orderBy('id', 'asc')
        ->get();

    if ($txList->isEmpty()) {
        throw new Exception("No transactions found");
    }

    $total   = $txList->sum('total_amount');
    $paid    = $txList->sum('paid_amount');
    $pending = $txList->sum('pending_amount');
    $extra   = $txList->sum('refund'); // advance/refund column

    $lastTx = $txList->last();

    $paymentMode = match ($request->payment_mode) {
        3 => 'PAYLATER',
        2 => 'OFFLINE',
        default => 'ONLINE'
    };


    /**
     * ======================================
     * ✅ STEP 1: ADJUST EXTRA FIRST (CRITICAL FIX)
     * ======================================
     */
    if ($extra > 0) {

        $remainingExtra = $extra;

        foreach ($txList as $tx) {

            if ($remainingExtra <= 0) break;
            if ($tx->pending_amount <= 0) continue;

            $use = min($tx->pending_amount, $remainingExtra);

            $tx->update([
                'pending_amount' => $tx->pending_amount - $use,
                'is_paid' => ($tx->pending_amount - $use) == 0 ? 1 : 0,
            ]);

            $remainingExtra -= $use;
        }

        // Reset extra after adjustment
        LearnerTransaction::where('learner_id', $learnerId)
            ->update(['refund' => 0]);

        // Refresh values after adjustment
        $pending = LearnerTransaction::where('learner_id', $learnerId)->sum('pending_amount');
        $extra   = 0;
    }

    /**
     * ======================================
     * ✅ STEP 2: REFUND LOGIC
     * ======================================
     */
    if ($request->is_refund == 1 && $refundInput > 0) {

        if ($refundInput > $paid) {
            throw new Exception("Refund cannot exceed total paid amount");
        }

        $remainingRefund = $refundInput;

        // Deduct from PAID (reverse order - latest first)
        foreach ($txList->reverse() as $tx) {

            if ($remainingRefund <= 0) break;

            if ($tx->paid_amount <= 0) continue;

            $deduct = min($tx->paid_amount, $remainingRefund);

            $tx->update([
                'paid_amount' => $tx->paid_amount - $deduct
            ]);

            $remainingRefund -= $deduct;
        }

        // Handle remaining refund (future / adjust)
        if ($remainingRefund > 0) {

            if ($settlementMode === 'refund_pending_future') {
                $lastTx->update(['refund' => $remainingRefund]);
            }

            if ($settlementMode === 'adjust') {
                $lastTx->increment('discount_amount', $remainingRefund);
            }
        }

        // Activity
        $this->logActivity($learnerId, 'REFUND', $refundInput, $paymentMode, 'Dr');
    }

    /**
     * ======================================
     * ✅ STEP 3: PAY PENDING (FIFO)
     * ======================================
     */
    if ($payInput > 0) {

        if ($payInput > $pending) {
            throw new Exception("Pay exceeds pending amount");
        }

        $remainingPay = $payInput;

        foreach ($txList as $tx) {

            if ($remainingPay <= 0) break;
            if ($tx->pending_amount <= 0) continue;

            $use = min($tx->pending_amount, $remainingPay);

            $tx->update([
                'paid_amount'    => $tx->paid_amount + $use,
                'pending_amount' => $tx->pending_amount - $use,
                'is_paid'        => ($tx->pending_amount - $use) == 0 ? 1 : 0,
            ]);

            $remainingPay -= $use;
        }

        // Remaining pending
        $remainingPending = LearnerTransaction::where('learner_id', $learnerId)
            ->sum('pending_amount');

        if ($remainingPending > 0 && $settlementMode === 'adjust') {

            $remainingAdjust = $remainingPending;

            foreach ($txList as $tx) {

                if ($remainingAdjust <= 0) break;
                if ($tx->pending_amount <= 0) continue;

                $use = min($tx->pending_amount, $remainingAdjust);

                $tx->update([
                    'pending_amount'  => $tx->pending_amount - $use,
                    'discount_amount' => $tx->discount_amount + $use,
                    'is_paid'         => ($tx->pending_amount - $use) == 0 ? 1 : 0,
                ]);

                $remainingAdjust -= $use;
            }
        }

        // Activity
        $this->logActivity($learnerId, 'PENDING', $payInput, $paymentMode, 'Cr');
    }

    /**
  
    * ======================================
    * ✅ STEP 4: ONLY ADJUST (NO PAY / REFUND)
    * ======================================
    */
    if (!$request->is_refund && !$payInput && $settlementMode === 'adjust') {

        $remainingAdjust = $pending;

        foreach ($txList as $tx) {

            if ($remainingAdjust <= 0) break;
            if ($tx->pending_amount <= 0) continue;

            $use = min($tx->pending_amount, $remainingAdjust);

            $tx->update([
                'pending_amount'  => $tx->pending_amount - $use,
                'discount_amount' => $tx->discount_amount + $use,
                'is_paid'         => ($tx->pending_amount - $use) == 0 ? 1 : 0,
            ]);

            $remainingAdjust -= $use;
        }
    }

    return true;
}

    /**
     * FINAL DELETE
     */
    public function executeDelete($request, $learnerId)
    {
        $learner = Learner::findOrFail($learnerId);

        $isFull = $request->full_learner_delete == 1;

        $detailIds = $isFull
            ? LearnerDetail::where('learner_id', $learnerId)->pluck('id')->toArray()
            : ($request->learner_detail_ids ?? []);
        
        


        DB::transaction(function () use ($detailIds, $learner, $isFull, $request) {

            foreach ($detailIds as $id) {

                $detail = LearnerDetail::find($id);

                if (!$detail) continue;

                /**
                 * DELETE TRANSACTION
                 */
                LearnerTransaction::where('learner_detail_id', $id)->delete();

                /**
                 * DELETE DETAIL
                 */
                $detail->delete();
            }

            /**
             * FULL DELETE
             */
            if ($isFull) {
                $learner->update([
                    'status'=>0
                ]);
                $learner->delete();
            }
        });

        return [
            'deleted_details' => count($detailIds),
            'full_delete' => $isFull
        ];
    }


    private function logActivity($learnerId, $type, $amount, $mode, $drcr)
{
    LearnerTransactionActivity::create([
        'branch_id' => getCurrentBranch(),
        'learner_id' => $learnerId,
        'date' => now(),
        'transaction_id' => transaction_id(),
        'particular' => 'SETTLEMENT',
        'payment_type' => $type,
        'payment_mode' => $mode,
        'amount' => $amount,
        'dr_cr' => $drcr,
    ]);
}
}