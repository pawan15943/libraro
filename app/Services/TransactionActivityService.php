<?php

namespace App\Services;

use App\Models\LearnerTransaction;
use App\Models\LearnerTransactionActivity;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TransactionActivityService
{
    public function learnerTransactionAddUpdate(array $data)
    {
        $billing = BillingAmountService::totals(
            (float) ($data['planPrice'] ?? 0),
            (float) ($data['locker'] ?? 0),
            (float) ($data['discount'] ?? 0),
            (float) ($data['paid_amount'] ?? 0)
        );
        $effectivePaid = $billing['total_amount'];

        Log::info('learnerTransactionAddUpdate: input', [
            'learner_id' => $data['learner_id'] ?? null,
            'branchId' => $data['branchId'] ?? null,
            'learner_detail_id' => $data['learner_detail_id'] ?? null,
            'payment_type' => $data['payment_type'] ?? null,
            'planPrice' => $data['planPrice'] ?? null,
            'locker' => $data['locker'] ?? null,
            'discount' => $data['discount'] ?? null,
            'paid_amount_in' => $data['paid_amount'] ?? null,
            'billing' => $billing,
            'effectivePaid' => $effectivePaid,
        ]);

        $transactionDate =
            $data['transaction_date']
            ?? (!empty($data['paid_date']) ? Carbon::parse($data['paid_date'])->format('Y-m-d') : null)
            ?? (!empty($data['start_date']) ? Carbon::parse($data['start_date'])->format('Y-m-d') : null)
            ?? date('Y-m-d');

        $pendingTransactions = LearnerTransaction::where('learner_id', $data['learner_id'])
            ->where('branch_id' , $data['branchId'])
            ->where('pending_amount', '>', 0)
            ->orderBy('id', 'asc')
            ->get();

        $oldPendingTotal = (float) $pendingTransactions->sum('pending_amount');
        $paidAmount = (float) ($data['paid_amount'] ?? 0);

        $pendingPaid = min($paidAmount, $oldPendingTotal);
        $remainingForNewPlan = $paidAmount - $pendingPaid;
        $remainingPendingPayment = $pendingPaid;

        Log::info('learnerTransactionAddUpdate: old pending found', [
            'old_pending_transaction_ids' => $pendingTransactions->pluck('id', 'pending_amount')->toArray(),
            'oldPendingTotal' => $oldPendingTotal,
            'paidAmount' => $paidAmount,
            'pendingPaid' => $pendingPaid,
            'remainingForNewPlan' => $remainingForNewPlan,
        ]);

        foreach ($pendingTransactions as $tran) {
            if ($remainingPendingPayment <= 0) {
                break;
            }

            $tranPending = (float) $tran->pending_amount;
            if ($remainingPendingPayment >= $tranPending) {
                $paidNow = $tranPending;
                $newPending = 0;
            } else {
                $paidNow = $remainingPendingPayment;
                $newPending = $tranPending - $paidNow;
            }

            $tran->update([
                'paid_amount' => (float) $tran->paid_amount + $paidNow,
                'pending_amount' => $newPending,
                'paid_date' => $transactionDate,
                'is_paid' => $newPending == 0 ? 1 : 0,
            ]);

            $remainingPendingPayment -= $paidNow;
        }

        $newPlanPaid = min(max(0, $remainingForNewPlan), $effectivePaid);
        $newPlanPending = max(0, $effectivePaid - $newPlanPaid);
        $newPlanExtra = max(0, $remainingForNewPlan - $effectivePaid);

        Log::info('learnerTransactionAddUpdate: new plan split', [
            'newPlanPaid' => $newPlanPaid,
            'newPlanPending' => $newPlanPending,
            'newPlanExtra' => $newPlanExtra,
        ]);

        $learnerTransaction = LearnerTransaction::create([
            'learner_id' => $data['learner_id'],
            'library_id' => $data['library_id'],
            'branch_id' => $data['branchId'],
            'learner_detail_id' => $data['learner_detail_id'],
            'total_amount' => $effectivePaid,
            'paid_amount' => $newPlanPaid,
            'pending_amount' => $newPlanPending,
            'refund' => $newPlanExtra,
            'locker_amount' => $data['locker'] ?? 0,
            'discount_amount' => $data['discount'] ?? 0,
            'paid_date' => $transactionDate,
            'is_paid' => $data['is_paid'] ?? 0,
            'due_date' => $data['due_date'] ?? null,
            'transaction_id' => transaction_id(),
        ]);

        Log::info('learnerTransactionAddUpdate: created new transaction row', [
            'new_transaction_id' => $learnerTransaction->id,
            'new_transaction_paid_amount' => $learnerTransaction->paid_amount,
            'new_transaction_pending_amount' => $learnerTransaction->pending_amount,
            'will_log_pending_activity' => $pendingPaid > 0,
            'will_log_new_plan_activity' => $remainingForNewPlan > 0,
        ]);

         // Pending payment activity

        if ($pendingPaid > 0) {
            $this->learnerTransactionActivity([
                'branchId' => $data['branchId'],
                'learner_id' => $data['learner_id'],
                'learner_transaction_id' => optional($pendingTransactions->first())->id,
                'particular' => $data['particular'] ?? 'Paid By Trans',
                'payment_type' => 'PENDING',
                'payment_mode' => $data['payment_mode'],
                'amount' => $pendingPaid,
                'dr_cr' => $data['dr_cr'] ?? 'Cr',
            ]);
        }
         // New plan payment activity
        if ($remainingForNewPlan > 0) {
            $this->learnerTransactionActivity([
                'branchId' => $data['branchId'],
                'learner_id' => $data['learner_id'],
                'learner_transaction_id' => $learnerTransaction->id,
                'particular' => $data['particular'] ?? 'Paid By Trans',
                'payment_type' => $data['payment_type'],
                'payment_mode' => $data['payment_mode'],
                'amount' => $remainingForNewPlan,
                'dr_cr' => $data['dr_cr'] ?? 'Cr',
            ]);
        } elseif ($pendingPaid <= 0 && (int) ($data['payment_mode'] ?? null) === 3) {
            // Paylater: nothing is actually collected now, so both amounts above are 0
            // and neither branch fires — leaving no audit trail for the booking. Still
            // log an activity (amount 0, PAYLATER) so the booking shows up, mirroring
            // the paylater handling already in LearnerOperationService::learnerTransactionUpdate().
            $this->learnerTransactionActivity([
                'branchId' => $data['branchId'],
                'learner_id' => $data['learner_id'],
                'learner_transaction_id' => $learnerTransaction->id,
                'particular' => $data['particular'] ?? 'Paid By Trans',
                'payment_type' => $data['payment_type'],
                'payment_mode' => $data['payment_mode'],
                'amount' => 0,
                'dr_cr' => $data['dr_cr'] ?? 'Cr',
            ]);
        }

        return $learnerTransaction;
    }

    public function learnerTransactionActivity(array $data): void
    {
        $learnerTransactionId = $data['learner_transaction_id'] ?? null;
        if (empty($learnerTransactionId) && !empty($data['learner_id'])) {
            $learnerTransactionId = LearnerTransaction::withoutGlobalScopes()
                ->where('learner_id', $data['learner_id'])
                ->orderByDesc('id')
                ->value('id');
        }

        $paymentMode = $this->normalizePaymentMode($data['payment_mode'] ?? null);

        LearnerTransactionActivity::create([
            'branch_id' => $data['branchId'] ?? getCurrentBranch(),
            'learner_id' => $data['learner_id'],
            'learner_transaction_id' => $learnerTransactionId,
            'date' => now()->format('Y-m-d'),
            'particular' => $data['particular'],
            'payment_type' => $data['payment_type'],
            'payment_mode' => $paymentMode,
            'amount' => $data['amount'] ?? 0,
            'dr_cr' => $data['dr_cr'] ?? 'Cr',
        ]);
    }

    private function normalizePaymentMode($rawMode): string
    {
        return match ((int) $rawMode) {
            3 => 'PAYLATER',
            2 => 'OFFLINE',
            default => 'ONLINE',
        };
    }
}
