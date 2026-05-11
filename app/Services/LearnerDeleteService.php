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
    //     $refundInput    = (float) $request->refund_amount;
    //     $payInput       = (float) $request->pay_amount;
    //     $detailIds      = array_filter((array) ($request->learner_detail_ids ?? []));

    //     $txQuery = LearnerTransaction::where('learner_id', $learnerId)
    //         ->when(! empty($detailIds), fn ($query) => $query->whereIn('learner_detail_id', $detailIds));

    //     $txList = (clone $txQuery)
    //         ->orderBy('id', 'asc')
    //         ->get();

    //     if ($txList->isEmpty()) {
    //         throw new Exception("No transactions found");
    //     }

    //     $total   = $txList->sum('total_amount');
    //     $paid    = $txList->sum('paid_amount');
    //     $pending = $txList->sum('pending_amount');
    //     $extra   = $txList->sum('refund'); // advance/refund column

    //     $lastTx = $txList->last();

    //     $paymentMode = match ((int) $request->payment_mode) {
    //         3 => 'PAYLATER',
    //         2 => 'OFFLINE',
    //         default => 'ONLINE'
    //     };


    //     /**
    //      * ======================================
    //      * ✅ STEP 1: ADJUST EXTRA FIRST (CRITICAL FIX)
    //      * ======================================
    //      */
    //     if ($extra > 0 && $pending > 0) {

    //         foreach ($txList as $extraTx) {
    //             $remainingExtra = (float) $extraTx->refund;

    //             if ($remainingExtra <= 0) continue;

    //             foreach ($txList as $pendingTx) {
    //                 if ($remainingExtra <= 0) break;
    //                 if ($pendingTx->pending_amount <= 0) continue;

    //                 $use = min($pendingTx->pending_amount, $remainingExtra);
    //                 $newPending = $pendingTx->pending_amount - $use;

    //                 $pendingTx->update([
    //                     'paid_amount' => $pendingTx->paid_amount + $use,
    //                     'pending_amount' => $newPending,
    //                     'is_paid' => $newPending == 0 ? 1 : 0,
    //                 ]);

    //                 $remainingExtra -= $use;
    //                 $pendingTx->refresh();
    //             }

    //             $extraTx->update(['refund' => $remainingExtra]);
    //         }

    //         // Refresh values after adjustment
    //         $pending = (clone $txQuery)->sum('pending_amount');
    //         $extra   = (clone $txQuery)->sum('refund');
    //     }

    //     /**
    //      * ======================================
    //      * ✅ STEP 2: REFUND LOGIC
    //      * ======================================
    //      */
    //     if ($request->is_refund == 1 && $refundInput > 0) {

    //         if ($refundInput > ($paid + $extra)) {
    //             throw new Exception("Refund cannot exceed available extra amount");
    //         }

    //         $remainingRefund = $refundInput;

    //         // Pay existing extra/refund first.
    //         foreach ($txList->reverse() as $tx) {

    //             if ($remainingRefund <= 0) break;

    //             if ($tx->refund <= 0) continue;

    //             $deduct = min($tx->refund, $remainingRefund);

    //             $tx->update([
    //                 'refund' => $tx->refund - $deduct
    //             ]);

    //             $remainingRefund -= $deduct;
    //         }

    //         // Deduct from PAID if refund is more than stored extra.
    //         foreach ($txList->reverse() as $tx) {

    //             if ($remainingRefund <= 0) break;

    //             if ($tx->paid_amount <= 0) continue;

    //             $deduct = min($tx->paid_amount, $remainingRefund);

    //             $tx->update([
    //                 'paid_amount' => $tx->paid_amount - $deduct
    //             ]);

    //             $remainingRefund -= $deduct;
    //         }

    //         // Handle remaining refund (future / adjust)
    //         if ($remainingRefund > 0) {

    //             if ($settlementMode === 'refund_pending_future') {
    //                 $lastTx->update(['refund' => $remainingRefund]);
    //             }

    //             if ($settlementMode === 'adjust') {
    //                 $lastTx->increment('discount_amount', $remainingRefund);
    //             }
    //         }

    //         if ($settlementMode === 'adjust') {
    //             foreach ($txList as $tx) {
    //                 $tx->refresh();

    //                 if ($tx->refund <= 0) continue;

    //                 $tx->update([
    //                     'sattle_amount' => (float) ($tx->sattle_amount ?? 0) - (float) $tx->refund,
    //                     'refund' => 0,
    //                 ]);
    //             }
    //         }

    //         // Activity
    //         $this->logActivity($learnerId, 'REFUND', $refundInput, $paymentMode, 'Dr');
    //     }

    //     /**
    //      * ======================================
    //      * ✅ STEP 3: PAY PENDING (FIFO)
    //      * ======================================
    //      */
    //     if ($payInput > 0) {

    //         if ($payInput > $pending) {
    //             throw new Exception("Pay exceeds pending amount");
    //         }

    //         $remainingPay = $payInput;

    //         foreach ($txList as $tx) {

    //             if ($remainingPay <= 0) break;
    //             if ($tx->pending_amount <= 0) continue;

    //             $use = min($tx->pending_amount, $remainingPay);

    //             $tx->update([
    //                 'paid_amount'    => $tx->paid_amount + $use,
    //                 'pending_amount' => $tx->pending_amount - $use,
    //                 'is_paid'        => ($tx->pending_amount - $use) == 0 ? 1 : 0,
    //             ]);

    //             $remainingPay -= $use;
    //         }

    //         // Remaining pending
    //         $remainingPending = (clone $txQuery)
    //             ->sum('pending_amount');

    //         if ($remainingPending > 0 && $settlementMode === 'adjust') {

    //             $remainingAdjust = $remainingPending;

    //             foreach ($txList as $tx) {

    //                 if ($remainingAdjust <= 0) break;
    //                 if ($tx->pending_amount <= 0) continue;

    //                 $use = min($tx->pending_amount, $remainingAdjust);

    //                 $tx->update([
    //                     'pending_amount'  => $tx->pending_amount - $use,
    //                     'discount_amount' => $tx->discount_amount + $use,
    //                     'is_paid'         => ($tx->pending_amount - $use) == 0 ? 1 : 0,
    //                 ]);

    //                 $remainingAdjust -= $use;
    //             }
    //         }

    //         // Activity
    //         $this->logActivity($learnerId, 'PENDING', $payInput, $paymentMode, 'Cr');
    //     }

    //     /**
     
    //     * ======================================
    //     * ✅ STEP 4: ONLY ADJUST (NO PAY / REFUND)
    //     * ======================================
    //     */
    //     if (!$request->is_refund && !$payInput && $settlementMode === 'adjust') {

    //         $remainingAdjust = $pending;

    //         foreach ($txList as $tx) {

    //             if ($remainingAdjust <= 0) break;
    //             if ($tx->pending_amount <= 0) continue;

    //             $use = min($tx->pending_amount, $remainingAdjust);

    //             $tx->update([
    //                 'pending_amount'  => $tx->pending_amount - $use,
    //                 'discount_amount' => $tx->discount_amount + $use,
    //                 'is_paid'         => ($tx->pending_amount - $use) == 0 ? 1 : 0,
    //             ]);

    //             $remainingAdjust -= $use;
    //         }
    //     }

    //     return true;
    // }

    // /**
    //  * FINAL DELETE
    //  */
    // public function executeDelete($request, $learnerId)
    // {
    //     $learner = Learner::findOrFail($learnerId);

    //     $isFull = $request->full_learner_delete == 1;

    //     $detailIds = $isFull
    //         ? LearnerDetail::where('learner_id', $learnerId)->pluck('id')->toArray()
    //         : ($request->learner_detail_ids ?? []);
        
        


    //     DB::transaction(function () use ($detailIds, $learner, $isFull, $request) {

    //         foreach ($detailIds as $id) {

    //             $detail = LearnerDetail::find($id);

    //             if (!$detail) continue;

    //             /**
    //              * DELETE TRANSACTION
    //              */
    //             LearnerTransaction::where('learner_detail_id', $id)->delete();

    //             /**
    //              * DELETE DETAIL
    //              */
    //             $detail->delete();
    //         }

    //         /**
    //          * FULL DELETE
    //          */
    //         if ($isFull) {
    //             $learner->update([
    //                 'status'=>0
    //             ]);
    //             $learner->delete();
    //         }
    //     });

    //     return [
    //         'deleted_details' => count($detailIds),
    //         'full_delete' => $isFull
    //     ];
    // }


    // private function logActivity($learnerId, $type, $amount, $mode, $drcr)
    // {
    //     LearnerTransactionActivity::create([
    //         'branch_id' => getCurrentBranch(),
    //         'learner_id' => $learnerId,
    //         'date' => now(),
    //         'transaction_id' => transaction_id(),
    //         'particular' => 'SETTLEMENT',
    //         'payment_type' => $type,
    //         'payment_mode' => $mode,
    //         'amount' => $amount,
    //         'dr_cr' => $drcr,
    //     ]);
    // }
}
