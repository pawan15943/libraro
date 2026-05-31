<?php

namespace App\Services;

use App\Models\LearnerTransaction;
use App\Models\LearnerTransactionActivity;
use Carbon\Carbon;

class TransactionActivityService
{
    public function learnerTransactionAddUpdate(array $data)
    {
        $effectivePaid = $data['planPrice'] + $data['locker'] - $data['discount'];

        $transactionDate =
            $data['transaction_date']
            ?? (!empty($data['paid_date']) ? Carbon::parse($data['paid_date'])->format('Y-m-d') : null)
            ?? (!empty($data['start_date']) ? Carbon::parse($data['start_date'])->format('Y-m-d') : null)
            ?? date('Y-m-d');

        $pendingTransactions = LearnerTransaction::where('learner_id', $data['learner_id'])
            ->where('pending_amount', '>', 0)
            ->orderBy('id', 'asc')
            ->get();

        $oldPendingTotal = (float) $pendingTransactions->sum('pending_amount');
        $paidAmount = (float) ($data['paid_amount'] ?? 0);

        $pendingPaid = min($paidAmount, $oldPendingTotal);
        $remainingForNewPlan = $paidAmount - $pendingPaid;
        $remainingPendingPayment = $pendingPaid;

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
                'is_paid' => 1,
            ]);

            $remainingPendingPayment -= $paidNow;
        }

        $newPlanPaid = max(0, $remainingForNewPlan);
        $newPlanPending = max(0, $effectivePaid - $newPlanPaid);

        $learnerTransaction = LearnerTransaction::create([
            'learner_id' => $data['learner_id'],
            'library_id' => $data['library_id'],
            'branch_id' => $data['branchId'],
            'learner_detail_id' => $data['learner_detail_id'],
            'total_amount' => $effectivePaid,
            'paid_amount' => $newPlanPaid,
            'pending_amount' => $newPlanPending,
            'locker_amount' => $data['locker'] ?? 0,
            'discount_amount' => $data['discount'] ?? 0,
            'paid_date' => $transactionDate,
            'is_paid' => $data['is_paid'] ?? 0,
            'due_date' => $data['due_date'] ?? null,
            'transaction_id' => transaction_id(),
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
        if ($newPlanPaid >= 0) {
            $this->learnerTransactionActivity([
                'branchId' => $data['branchId'],
                'learner_id' => $data['learner_id'],
                'learner_transaction_id' => $learnerTransaction->id,
                'particular' => $data['particular'] ?? 'Paid By Trans',
                'payment_type' => $data['payment_type'],
                'payment_mode' => $data['payment_mode'],
                'amount' => $newPlanPaid,
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
            'transaction_id' => transaction_id(),
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
