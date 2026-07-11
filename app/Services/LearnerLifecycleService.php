<?php

namespace App\Services;

use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerFeedback;
use App\Models\LearnerTransaction;
use App\Models\LearnerTransactionActivity;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\NotificationSentController;
use Illuminate\Support\Facades\Log;

class LearnerLifecycleService
{
    public function __construct(private LearnerOperationLogService $operationLogService)
    {
    }

    public function transactionDashboard(int $learnerId): array
    {
        if (! $this->learnerBelongsToCurrentContext($learnerId)) {
            throw new Exception('Learner not found.');
        }

        $learner = Learner::withTrashed()->findOrFail($learnerId);

        $currentDetail = LearnerDetail::withTrashed()
            ->with(['plan', 'planType'])
            ->where('learner_id', $learnerId)
            ->orderByDesc('id')
            ->first();

        $transactions = LearnerTransaction::withTrashed()
            ->with(['learnerDetail.plan', 'learnerDetail.planType'])
            ->where('learner_id', $learnerId)
            ->orderByDesc('id')
            ->get();

        $activities = LearnerTransactionActivity::withoutGlobalScopes()
            ->with('creator')
            ->where('learner_id', $learnerId)
            ->when(getCurrentBranch(), fn ($q) => $q->where('branch_id', getCurrentBranch()))
            ->orderByDesc('id')
            ->get();

        $subscriptionActivities = $activities->filter(function ($activity) {
            return in_array(strtoupper((string) $activity->payment_type), ['SUBSCRIPTION', 'RENEW', 'UPGRADE', 'CHANGEPLAN', 'PENDING'], true);
        })->values();

        $otherActivities = $activities->filter(function ($activity) {
            return in_array(strtoupper((string) $activity->payment_type), ['TOKEN MONEY', 'MISCELLANEOUS', 'REFUND', 'SETTLED', 'RESTORE'], true);
        })->values();

        $totalAmount = (float) $transactions->sum('total_amount');
        $receivedAmount = (float) $transactions->sum('paid_amount')
            + (float) $transactions->sum('token_money')
            + (float) $transactions->sum('miscellaneous');
        $pendingAmount = (float) $transactions->sum('pending_amount');
        $extraAmount = max(0, (float) $transactions->sum('refund'));
        $refundAmount = $activities->where('payment_type', 'REFUND')->sum('amount');

        $latestTransaction = $transactions->first();

        return [
            'learner' => $learner,
            'currentDetail' => $currentDetail,
            'latestTransaction' => $latestTransaction,
            'transactions' => $transactions,
            'activities' => $activities,
            'subscriptionActivities' => $subscriptionActivities,
            'otherActivities' => $otherActivities,
            'summary' => [
                'total_amount' => $totalAmount,
                'received_amount' => $receivedAmount,
                'pending_amount' => $pendingAmount,
                'extra_amount' => $extraAmount,
                'refund_amount' => (float) $refundAmount,
                'next_due_date' => $currentDetail?->plan_end_date,
            ],
        ];
    }

    public function transactionTabs(int $learnerId): array
    {
        $dashboard = $this->transactionDashboard($learnerId);
        $transactions = $dashboard['transactions'];
        $activities = $dashboard['activities'];
        $currentTransaction = $dashboard['latestTransaction'];
        $firstTransactionId = (int) ($transactions->sortBy('id')->first()?->id ?? 0);

        $totalOtherPaid = (float) $transactions->sum('token_money') + (float) $transactions->sum('miscellaneous');
        $configuredTokenMoney = (float) token_money();
        $paidTokenMoney = (float) $transactions->sum('token_money');
        $tokenPayment = $configuredTokenMoney == 0.0 ? $paidTokenMoney : $configuredTokenMoney;
        $miscellaneousPayment = (float) $transactions->sum('miscellaneous');
        $totalOtherPayment = $tokenPayment + $miscellaneousPayment;
        $totalPendingAmount = (float) $transactions->sum('pending_amount');
        $totalExtraAmount = (float) $transactions->sum('refund');
        $currentSubscriptionAmount = (float) ($currentTransaction?->total_amount ?? 0);
        $isRenew = $this->shouldShowRenewOption($learnerId);
        $refundActivityAmount = (float) $activities
            ->filter(fn ($activity) => strtoupper((string) $activity->payment_type) === 'REFUND')
            ->sum('amount');
        $overviewUsedActivityIds = [];
        $currentTransactionActivities = $currentTransaction
            ? $this->activitiesForTransaction($currentTransaction, $activities, $overviewUsedActivityIds)
                ->reject(fn ($activity) => $this->isOtherPaymentActivity($activity))
                ->map(fn ($activity) => $this->formatActivity($activity))
                ->values()
            : collect();

        return [
            'learner' => $this->formatLearnerForTransactions($dashboard['learner'], $dashboard['currentDetail']),
            'overview' => [
                'total_amount_received' => $this->money((float) $transactions->sum('paid_amount') + $totalOtherPaid),
                'total_amount' => $this->money((float) $transactions->sum('total_amount') + $totalOtherPaid),
                'pending_amount' => $this->money((float) $transactions->sum('pending_amount')),
                'extra_amount' => $this->money((float) $transactions->sum('refund')),
                'refund_amount' => $this->money($refundActivityAmount),
                'next_due_date' => (string) ($dashboard['summary']['next_due_date'] ?? ''),
                'next_plan'=>alreadyRenewed($learnerId) ? 1 : 0 ,
                'is_renew' => $isRenew,
                'next_due_amount' => $this->money(max(0, $currentSubscriptionAmount + $totalPendingAmount - $totalExtraAmount)),
                'added_by_name' => $currentTransaction ? $this->transactionAddedByName($currentTransaction) : '',
                'last_transactions' => $currentTransaction ? (
                    $this->formatSubscriptionTransaction($currentTransaction, 0, 0, 0, $firstTransactionId) + [
                        'activity' => $currentTransactionActivities,
                    ]
                ) : null,
            ],
            'subscription' => [
                $currentTransaction ? $this->formatSubscriptionTransaction($currentTransaction, 0, 0, 0, $firstTransactionId) : null,
            ],
            'other_payment' => [
                  'summary' => [
                        'total_payment' => (string) $this->money($totalOtherPayment),

                        'received_amount' => (string) $this->money($totalOtherPaid),

                        'pending_amount' => (string) $this->money($totalOtherPayment - $totalOtherPaid),
                    ],
                
                'payments' => $this->formatOtherPayments($transactions, $activities),
            ],
            'all_transaction' => $this->formatAllTransactions($transactions, $activities, $firstTransactionId),
            'transaction_activity' => $activities->map(fn ($activity) => $this->formatActivity($activity))->values(),
        ];
    }

    private function shouldShowRenewOption(int $learnerId): bool
    {
        if (alreadyRenewed($learnerId)) {
            return false;
        }

        $today = Carbon::today();

        return LearnerDetail::where('learner_id', $learnerId)
            ->where('status', 1)
            ->whereDate('plan_start_date', '<=', $today)
            ->whereBetween('plan_end_date', [
                $today->toDateString(),
                $today->copy()->addDays(7)->toDateString(),
            ])
            ->exists();
    }

    public function transactionDetail(int $transactionId): array
    {
        $transaction = $this->transactionInCurrentContext($transactionId);

        return $this->formatSubscriptionTransaction(
            $transaction,
            0,
            0,
            0,
            (int) LearnerTransaction::withTrashed()
                ->where('learner_id', $transaction->learner_id)
                ->orderBy('id')
                ->value('id')
        );
    }

    public function updateTransaction(int $transactionId, array $data): array
    {
        $transaction = $this->transactionInCurrentContext($transactionId);

        DB::transaction(function () use ($transaction, $data) {
            $oldValues = $transaction->only(['paid_amount', 'pending_amount', 'paid_date', 'due_date']);
            $oldPaymentMode = $transaction->learnerDetail?->payment_mode;
            $paidAmount = array_key_exists('paid_amount', $data)
                ? (float) $data['paid_amount']
                : (float) ($transaction->paid_amount ?? 0);
            $settleAmount = (float) ($transaction->sattle_amount ?? 0);
            $refundAmount = (float) ($transaction->refund ?? 0);
            $totalAmount = (float) ($transaction->total_amount ?? 0);

            if ($paidAmount > $totalAmount) {
                throw new Exception('Paid amount cannot be greater than total amount.');
            }

            $oldPaidAmount = (float) ($transaction->paid_amount ?? 0);
            $update = [];

            if (array_key_exists('paid_amount', $data)) {
                $update['paid_amount'] = $paidAmount;
                $update['pending_amount'] = max(0, $totalAmount - ($paidAmount + $settleAmount + $refundAmount));
            }
            
            if (array_key_exists('pending_amount', $data)) {
                $update['pending_amount'] = max(0, $totalAmount - ($paidAmount + $settleAmount + $refundAmount));
            }

            if (array_key_exists('payment_mode', $data) && $this->paymentModeId($data['payment_mode']) === 3) {
                $update['paid_amount'] = 0;
                $update['pending_amount'] = max(
                    0,
                    (float) ($transaction->pending_amount ?? 0) + (float) ($transaction->paid_amount ?? 0)
                );
                $update['is_paid'] = 0;
            }

            if (array_key_exists('paid_date', $data)) {
                $update['paid_date'] = $data['paid_date'];
            }

            if (array_key_exists('due_date', $data)) {
                $update['due_date'] = $data['due_date'];
            }

            if (! empty($update)) {
                $update['is_paid'] ??= ((float) ($update['pending_amount'] ?? $transaction->pending_amount ?? 0)) <= 0 ? 1 : 0;
                $update = array_merge($update, $this->updatedByPayload($transaction->getTable()));
                $transaction->update($update);
                $transaction->refresh();
            }

            if (array_key_exists('payment_mode', $data) && $transaction->learnerDetail) {
                $transaction->learnerDetail->payment_mode = $this->paymentModeId($data['payment_mode']);
                $transaction->learnerDetail->save();
            }
            
            $activity = LearnerTransactionActivity::withoutGlobalScopes()
                ->where('learner_transaction_id', $transaction->id)
                ->first();

            if ($activity) {
                if (array_key_exists('paid_amount', $data)) {
                    $diffrence = $paidAmount - $oldPaidAmount;
                    $activity->amount = (float) $activity->amount + $diffrence;
                }

                if (array_key_exists('paid_date', $data)) {
                    $activity->date = $data['paid_date'];
                }

                if (array_key_exists('payment_mode', $data)) {
                    $activity->payment_mode = $this->paymentModeValue($data['payment_mode']);

                    if ($this->paymentModeId($data['payment_mode']) === 3) {
                        $activity->amount = 0;
                    }
                }

                $this->setUpdatedBy($activity);
                $activity->save();
            }

            $newValues = $transaction->fresh()->only(['paid_amount', 'pending_amount', 'paid_date', 'due_date']);
            $newValues['payment_mode'] = $transaction->learnerDetail?->fresh()?->payment_mode;
            $oldValues['payment_mode'] = $oldPaymentMode;
            $this->operationLogService->log(
                (int) $transaction->learner_id,
                $transaction->learner_detail_id ? (int) $transaction->learner_detail_id : null,
                'transactionUpdate',
                implode(',', array_keys($data)),
                $oldValues,
                $newValues,
                'Transaction #'.$transaction->id.' updated'
            );

        });

        return $this->transactionDetail($transactionId);
    }

    public function deleteTransaction(int $transactionId): void
    {
        $transaction = $this->transactionInCurrentContext($transactionId);

        DB::transaction(function () use ($transaction) {
            $learnerId = (int) $transaction->learner_id;
            $detailId = (int) $transaction->learner_detail_id;
            $oldValues = $transaction->only(['id', 'total_amount', 'paid_amount', 'pending_amount', 'paid_date', 'due_date']);

            $activities = LearnerTransactionActivity::withoutGlobalScopes()
                ->where('learner_id', $learnerId)
                ->when(getCurrentBranch(), fn ($q) => $q->where('branch_id', getCurrentBranch()))
                ->get();

            $usedActivityIds = [];
            $this->activitiesForTransaction($transaction, $activities, $usedActivityIds)
                ->each(function ($activity) {
                    $this->setUpdatedBy($activity);
                    $activity->save();
                    $activity->delete();
                });

            $this->setUpdatedBy($transaction);
            $transaction->save();
            $transaction->delete();

            if ($detailId) {
                LearnerDetail::withTrashed()
                    ->where('id', $detailId)
                    ->where('learner_id', $learnerId)
                    ->delete();
            }

            $this->syncLearnerStatusAfterTransactionDelete($learnerId);
            $this->operationLogService->log(
                $learnerId,
                $detailId ?: null,
                'transactionDelete',
                'learner_transaction_id',
                $oldValues,
                'deleted',
                'Transaction #'.$transaction->id.' deleted'
            );
        });
    }

    public function transactionActivityDetail(int $activityId): array
    {
        $activity = $this->activityInCurrentContext($activityId);

        return $this->formatActivity($activity);
    }

    public function updateTransactionActivity(int $activityId, array $data): array
    {
        $activity = $this->activityInCurrentContext($activityId);

        DB::transaction(function () use (&$activity, $activityId, $data) {
            $oldValues = $activity->only(['amount', 'date', 'payment_mode', 'payment_type']);
            $update = [];

            if (array_key_exists('paid_amount', $data)) {
                $this->learnerActivityAmountManage($activityId, (float) $data['paid_amount']);
                $activity = $this->activityInCurrentContext($activityId);
            }
            if (array_key_exists('payment_date', $data)) {
                $update['date'] = $data['payment_date'];
            }
            if (array_key_exists('payment_mode', $data)) {
                $update['payment_mode'] = $this->paymentModeValue($data['payment_mode']);
            }

            if (! empty($update)) {
                $update = array_merge($update, $this->updatedByPayload($activity->getTable()));
                $activity->update($update);
            }

            $activity->refresh();
            $this->operationLogService->log(
                (int) $activity->learner_id,
                $activity->learner_detail_id ? (int) $activity->learner_detail_id : null,
                'transactionActivityUpdate',
                implode(',', array_keys($data)),
                $oldValues,
                $activity->only(['amount', 'date', 'payment_mode', 'payment_type']),
                'Transaction activity #'.$activityId.' updated'
            );
        });

        return $this->transactionActivityDetail($activityId);
    }

    public function deleteTransactionActivity(int $activityId): void
    {
        $activity = $this->activityInCurrentContext($activityId);

        DB::transaction(function () use ($activity) {
            $transactionId = (int) ($activity->learner_transaction_id ?? 0);
            $oldValues = $activity->only(['id', 'amount', 'date', 'payment_mode', 'payment_type', 'dr_cr']);

            if (! $transactionId) {
                throw new Exception('No activity found for this transaction.');
            }

            $activityCount = LearnerTransactionActivity::withoutGlobalScopes()
                ->where('learner_transaction_id', $transactionId)
                ->count();

            $paymentType = strtoupper((string) ($activity->payment_type ?? ''));

            if ($activityCount <= 1 || in_array($paymentType, ['SEAT ASSIGNMENT', 'RENEW','REACTIVE'], true)) {
                throw new Exception('Cannot delete the only activity for this transaction. Delete the transaction instead.');
            }

            $this->reverseActivityAmount($activity);
            $this->setUpdatedBy($activity);
            $activity->save();
            $activity->delete();
            $this->operationLogService->log(
                (int) $activity->learner_id,
                $activity->learner_detail_id ? (int) $activity->learner_detail_id : null,
                'transactionActivityDelete',
                'learner_transaction_activity_id',
                $oldValues,
                'deleted',
                'Transaction activity #'.$activity->id.' deleted'
            );
        });
    }

    private function transactionInCurrentContext(int $transactionId): LearnerTransaction
    {
        $transaction = LearnerTransaction::withTrashed()
            ->with(['learnerDetail.plan', 'learnerDetail.planType'])
            ->where('id', $transactionId)
            ->where('library_id', getLibraryId())
            ->when(getCurrentBranch(), fn ($q) => $q->where('branch_id', getCurrentBranch()))
            ->first();

        if (! $transaction) {
            throw new Exception('Transaction not found.');
        }

        return $transaction;
    }

    private function activityInCurrentContext(int $activityId): LearnerTransactionActivity
    {
        $activity = LearnerTransactionActivity::withoutGlobalScopes()
           
            ->where('id', $activityId)
            ->when(getCurrentBranch(), fn ($q) => $q->where('branch_id', getCurrentBranch()))
            ->first();

        if (! $activity) {
            throw new Exception('Transaction activity not found.');
        }
       
        return $activity;
    }

    private function syncSubscriptionActivity(LearnerTransaction $transaction, array $data): void
    {
        if (! array_key_exists('paid_amount', $data) && ! array_key_exists('payment_mode', $data) && ! array_key_exists('paid_date', $data)) {
            return;
        }

        $activity = LearnerTransactionActivity::withoutGlobalScopes()
            ->where('learner_transaction_id', $transaction->id)
            ->whereNotIn('payment_type', ['TOKEN MONEY', 'MISCELLANEOUS', 'REFUND', 'SETTLED'])
            ->orderBy('id')
            ->first();

        if (! $activity) {
            throw new Exception('No activity found for this transaction.');
        }

        if (array_key_exists('paid_amount', $data)) {
            $this->learnerActivityAmountManage((int) $activity->id, (float) $data['paid_amount']);
            $activity->refresh();
            $transaction->refresh();
        }

        $payload = [
            
            'learner_transaction_id' => $transaction->id,
            'date' => $data['paid_date'] ?? $transaction->paid_date ?? now()->format('Y-m-d'),
            'particular' => $activity->particular,
            'payment_type' => $activity->payment_type,
            'payment_mode' => $this->paymentModeLabel($data['payment_mode'] ?? $transaction->learnerDetail?->payment_mode),
            
        ];
        $payload = array_merge($payload, $this->updatedByPayload($activity->getTable()));

        $activity->update($payload);
    }

    private function updatedByPayload(string $table): array
    {
        if (! Schema::hasColumn($table, 'updated_by')) {
            return [];
        }

        $updatedBy = $table === 'learner_transaction_activity'
            ? $this->currentActorName()
            : $this->currentUpdatedBy();

        return $updatedBy ? ['updated_by' => $updatedBy] : [];
    }

    private function setUpdatedBy($model): void
    {
        if (! Schema::hasColumn($model->getTable(), 'updated_by')) {
            return;
        }

        $updatedBy = $model->getTable() === 'learner_transaction_activity'
            ? $this->currentActorName()
            : $this->currentUpdatedBy();

        if ($updatedBy) {
            $model->updated_by = $updatedBy;
        }
    }

    private function currentUpdatedBy(): ?int
    {
        if (Auth::guard('library_user')->check()) {
            return (int) Auth::guard('library_user')->id();
        }

        if (Auth::guard('library')->check()) {
            return (int) Auth::guard('library')->id();
        }

        if (Auth::guard('library_api')->check()) {
            return (int) Auth::guard('library_api')->id();
        }

        if (Auth::check()) {
            return (int) Auth::id();
        }

        return null;
    }

    private function currentActorName(): ?string
    {
        if (Auth::guard('library_user')->check()) {
            return $this->actorDisplayName(Auth::guard('library_user')->user());
        }

        if (Auth::guard('library')->check()) {
            return $this->actorDisplayName(Auth::guard('library')->user());
        }

        if (Auth::guard('library_api')->check()) {
            return $this->actorDisplayName(Auth::guard('library_api')->user());
        }

        if (Auth::check()) {
            return $this->actorDisplayName(Auth::user());
        }

        return null;
    }

    private function actorDisplayName($user): ?string
    {
        if (! $user) {
            return null;
        }

        return $user->name
            ?? $user->library_name
            ?? $user->email
            ?? $user->mobile
            ?? null;
    }

    private function updatedByName($updatedBy): string
    {
        if (! $updatedBy) {
            return '';
        }

        if (! is_numeric($updatedBy)) {
            return (string) $updatedBy;
        }

        $name = DB::table('library_users')->where('id', $updatedBy)->value('name');
        if ($name) {
            return (string) $name;
        }

        return (string) (DB::table('libraries')->where('id', $updatedBy)->value('library_name') ?? '');
    }

    private function syncLearnerStatusAfterTransactionDelete(int $learnerId): void
    {
        $status = LearnerDetail::withTrashed()
            ->where('learner_id', $learnerId)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->value('status');

        Learner::withTrashed()
            ->where('id', $learnerId)
            ->update(['status' => $status !== null ? (int) $status : 0]);
    }

    private function reverseActivityAmount(LearnerTransactionActivity $activity): void
    {
        if (! $activity->learner_transaction_id) {
            return;
        }

        $tran = LearnerTransaction::withTrashed()->where('id', $activity->learner_transaction_id)->first();
        if (! $tran) {
            return;
        }

        $amount = (float) ($activity->amount ?? 0);

        if ($activity->dr_cr == 'Cr') {
            $tran->paid_amount = max(0, (float) $tran->paid_amount - $amount);
            $tran->pending_amount = max(0, (float) $tran->pending_amount + $amount);
        } elseif ($activity->dr_cr == 'Dr') {
            $tran->paid_amount = (float) $tran->paid_amount + $amount;
        } elseif ($activity->dr_cr == 'Settle') {
            $tran->paid_amount = (float) $tran->paid_amount + $amount;
            $tran->sattle_amount = max(0, (float) $tran->sattle_amount - $amount);
        }

        $tran->is_paid = (float) $tran->pending_amount <= 0 ? 1 : 0;
        $this->setUpdatedBy($tran);
        $tran->save();
    }

    private function formatAllTransactions($transactions, $activities, int $firstTransactionId)
    {
        $runningBalance = 0.0;
        $carryForwardById = [];

        foreach ($transactions->sortBy('id') as $transaction) {
            $carryForwardById[$transaction->id] = [
                'carry_forward_amount' => max($runningBalance, 0),
                'extra_paid_amount' => max(-$runningBalance, 0),
            ];

            $runningBalance += (float) ($transaction->pending_amount ?? 0);
            $runningBalance -= (float) ($transaction->refund ?? 0);
        }

        $usedActivityIds = [];

        return $transactions->map(function ($transaction, $index) use ($activities, $carryForwardById, $firstTransactionId, &$usedActivityIds) {
            $carry = $carryForwardById[$transaction->id]['carry_forward_amount'] ?? 0;
            $extraPaid = $carryForwardById[$transaction->id]['extra_paid_amount'] ?? 0;
            $transactionActivities = $this->activitiesForTransaction($transaction, $activities, $usedActivityIds);

            return $this->formatSubscriptionTransaction($transaction, $index, $carry, $extraPaid, $firstTransactionId) + [
                'activity' => $transactionActivities
                    ->reject(fn ($activity) => $this->isOtherPaymentActivity($activity))
                    ->map(fn ($activity) => $this->formatActivity($activity))
                    ->values(),
                'other_payment_activity' => $transactionActivities
                    ->filter(fn ($activity) => $this->isOtherPaymentActivity($activity))
                    ->map(fn ($activity) => $this->formatActivity($activity))
                    ->values(),
            ];
        })->values();
    }

    private function activitiesForTransaction($transaction, $activities, array &$usedActivityIds)
    {
        $exact = $activities
            ->filter(fn ($activity) => (int) ($activity->learner_transaction_id ?? 0) === (int) $transaction->id)
            ->values();

        foreach ($exact as $activity) {
            $usedActivityIds[(int) $activity->id] = true;
        }

        $legacy = $activities
            ->filter(function ($activity) use ($transaction, $usedActivityIds) {
                if (isset($usedActivityIds[(int) $activity->id])) {
                    return false;
                }

                if ((int) ($activity->learner_transaction_id ?? 0) > 0) {
                    return false;
                }

                return $this->legacyActivityMatchesTransaction($activity, $transaction);
            })
            ->values();

        foreach ($legacy as $activity) {
            $usedActivityIds[(int) $activity->id] = true;
        }

        return $exact->merge($legacy)->values();
    }

    private function isOtherPaymentActivity($activity): bool
    {
        return in_array(strtoupper((string) ($activity->payment_type ?? '')), [
            'TOKEN MONEY',
            'MISCELLANEOUS',
        ], true);
    }

    private function legacyActivityMatchesTransaction($activity, $transaction): bool
    {
        if ((string) ($activity->transaction_id ?? '') !== ''
            && (string) ($activity->transaction_id ?? '') === (string) ($transaction->transaction_id ?? '')) {
            return true;
        }

        $amount = round((float) ($activity->amount ?? 0), 2);
        $candidateAmounts = [
            (float) ($transaction->paid_amount ?? 0),
            (float) ($transaction->pending_amount ?? 0),
            (float) ($transaction->token_money ?? 0),
            (float) ($transaction->miscellaneous ?? 0),
            (float) ($transaction->refund ?? 0),
        ];

        foreach ($candidateAmounts as $candidate) {
            if ($amount > 0 && round($candidate, 2) === $amount) {
                return true;
            }
        }

        return false;
    }

    private function formatSubscriptionTransaction($transaction, int $index, float $carryForwardAmount, float $extraPaidAmount = 0, int $firstTransactionId = 0): array
    {
        $detail = $transaction->learnerDetail;
        $planPrice = $this->planPriceFromTransaction($transaction);
        $totalAmount = (float) ($transaction->total_amount ?? 0);
        $finalPayable = max(0, $totalAmount + $carryForwardAmount - $extraPaidAmount);
        $pending = (float) ($transaction->pending_amount ?? 0) + $carryForwardAmount - $extraPaidAmount;
        $extra = (float) ($transaction->refund ?? 0);
        $learner = null;
        if($detail){
            $learner=Learner::where('id',$detail->learner_id)->select('status','locker_no')->first();
        }
        $addedByName = $this->transactionAddedByName($transaction);
        $updatedByName = $this->updatedByName($transaction->updated_by ?? null) ?: $addedByName;
        

        return [
            'id' => (int) $transaction->id,
            'status'=>$learner?->status==1 ? 'Active' : 'Inactive',
            'learner_detail_id' => (int) ($transaction->learner_detail_id ?? 0),
            'transaction_ref' => (string) ($transaction->transaction_id ?? ''),
            'transaction_type' => $this->transactionTypeLabel($transaction, $firstTransactionId),
            'transaction_date'=>optional($transaction->created_at)->toDateString() ?? '',
            'plan' => $detail?->plan?->name ?? '',
            'plan_type' => $detail?->planType?->name ?? '',
            'plan_start_date' => (string) ($detail?->plan_start_date ?? ''),
            'plan_end_date' => (string) ($detail?->plan_end_date ?? ''),
            'paid_date' => (string) ($transaction->paid_date ?? ''),
            'due_date' => (string) ($transaction->due_date ?? ''),
            'payment_mode' => $this->paymentModeLabel($this->subscriptionPaymentMode($transaction, $detail)),
            'plan_price' => $this->money($planPrice),
            'locker_amount' => $this->money((float) ($transaction->locker_amount ?? 0)),
            'locker_no'=>$learner?->locker_no ?? '',
            'locker'=>$transaction->locker_amount > 0 ? 'Yes' : 'No',
            'discount_amount' => $this->money((float) ($transaction->discount_amount ?? 0)),
            'total_amount' => $this->money($totalAmount),
            'carry_forward_amount' => $this->money($carryForwardAmount),
            'extra_paid_amount' => $this->money($extraPaidAmount),
            'final_payable_amount' => $this->money($finalPayable),
            'total_paid_amount' => $this->money((float) ($transaction->paid_amount ?? 0)),
            'pending_amount' => $this->money($pending),
            'extra_amount' => $this->money($extra),
            // 'token_money' => $this->money((float) ($transaction->token_money ?? 0)),
            // 'miscellaneous' => $this->money((float) ($transaction->miscellaneous ?? 0)),
            'updated_by' => (string) ($transaction->updated_by ?? ''),
            'updated_by_name' => $updatedByName,
            'added_by_name' => $addedByName,
            'updated_date'=> optional($transaction->updated_at)->toDateTimeString() ?? '',

            'is_paid' => (int) ($transaction->is_paid ?? 0),
            'subscription_download_receipt_link' => (int) ($transaction->is_paid ?? 0) === 1 ? route('receipt.view', ['transactionId' => $transaction->id]) : '',
            'edit_url' => url('api/v1/library/learners/transactions/detail'),
            'delete_url' => url('api/v1/library/learners/transactions/delete'),
        ];
    }

    private function formatOtherPayments($transactions, $activities)
    {
        $rows = collect();
        $activityPaymentTypes = $activities
            ->filter(fn ($activity) => in_array(strtoupper((string) $activity->payment_type), ['TOKEN MONEY', 'MISCELLANEOUS'], true))
            ->pluck('payment_type')
            ->map(fn ($type) => strtoupper((string) $type))
            ->unique();

        foreach ($transactions as $transaction) {
            if ((float) ($transaction->token_money ?? 0) > 0 && ! $activityPaymentTypes->contains('TOKEN MONEY')) {
                $rows->push($this->formatOtherPaymentRow($transaction, 'TOKEN MONEY', (float) $transaction->token_money));
            }

            if ((float) ($transaction->miscellaneous ?? 0) > 0 && ! $activityPaymentTypes->contains('MISCELLANEOUS')) {
                $rows->push($this->formatOtherPaymentRow($transaction, 'MISCELLANEOUS', (float) $transaction->miscellaneous));
            }
        }

        $activities
            ->filter(fn ($activity) => in_array(strtoupper((string) $activity->payment_type), ['TOKEN MONEY', 'MISCELLANEOUS'], true))
            ->each(function ($activity) use ($rows) {
                $rows->push([
                    'id' => (int) $activity->id,
                    'learner_transaction_id' => (int) ($activity->learner_transaction_id ?? 0),
                    'payment_type' => (string) ($activity->payment_type ?? ''),
                    'amount' => $this->money((float) ($activity->amount ?? 0)),
                    'payment_mode' => $this->paymentModeLabel($activity->payment_mode),
                    'paid_date' => (string) ($activity->date ?? ''),
                    'particular' => (string) ($activity->particular ?? ''),
                    'source' => 'activity',
                    'added_by_name' => $this->activityAddedByDisplay($activity->created_by ?? null),
                    'download_receipt_url' => $this->otherPaymentReceiptUrl($activity),
                ]);
            });

        return $rows->values();
    }

    private function formatOtherPaymentRow($transaction, string $type, float $amount): array
    {
        return [
            'id' => (int) $transaction->id,
            'learner_transaction_id' => (int) $transaction->id,
            'payment_type' => $type,
            'amount' => $this->money($amount),
            'payment_mode' => $this->paymentModeLabel($transaction->learnerDetail?->payment_mode),
            'paid_date' => (string) ($transaction->paid_date ?? ''),
            'particular' => $type,
            'added_by_name' => $this->transactionAddedByName($transaction, [$type]),
            'download_receipt_url' => '',
            'edit_url' => route('learner.other.payment', $transaction->learner_detail_id),
            'delete_url' => '',
        ];
    }

    private function transactionAddedByName($transaction, array $paymentTypes = []): string
    {
        if (! $transaction || empty($transaction->id)) {
            return '';
        }

        try {
            $activityQuery = LearnerTransactionActivity::withoutGlobalScopes()
                ->where('learner_transaction_id', $transaction->id)
                ->whereNotNull('created_by');

            if (! empty($paymentTypes)) {
                $activityQuery->whereIn('payment_type', $paymentTypes);
            } else {
                $activityQuery->whereNotIn('payment_type', ['TOKEN MONEY', 'MISCELLANEOUS']);
            }

            $activity = $activityQuery->orderBy('id')->first();

            if ($activity) {
                return $this->activityAddedByDisplay($activity->created_by ?? null);
            }
        } catch (\Throwable $e) {
            return $this->updatedByName($transaction->updated_by ?? null);
        }

        return $this->updatedByName($transaction->updated_by ?? null);
    }

    private function formatActivity($activity): array
    {
        $addedBy = $this->activityAddedByDisplay($activity->created_by ?? null);
        $updatedByName = $this->updatedByName($activity->updated_by ?? null) ?: $addedBy;

        return [
            'id' => (int) $activity->id,
            'learner_transaction_id' => (int) ($activity->learner_transaction_id ?? 0),
            'transaction_id' => (string) ($activity->transaction_id ?? ''),
            'transaction_date' => (string) ($activity->date ?? ''),
            'paid_amount' => $this->money((float) ($activity->amount ?? 0)),
            'payment_type' => (string) ($activity->payment_type ?? ''),
            'payment_mode' => $this->paymentModeLabel($activity->payment_mode),
            'particular' => (string) ($activity->particular ?? ''),
            'dr_cr' => (string) ($activity->dr_cr ?? ''),
            'added_by' => $addedBy,
            'added_by_name' => $addedBy,
            'updated_by' => (string) ($activity->updated_by ?? ''),
            'updated_by_name' => $updatedByName,
            'updated_date' => optional($activity->updated_at)->toDateTimeString() ?? '',
            'download_receipt_url' => $this->otherPaymentReceiptUrl($activity),
            'edit_url' => url('api/v1/library/learners/transactions/activity/detail'),
            'delete_url' => url('api/v1/library/learners/transactions/activity/delete'),
        ];
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

    private function activityAddedByDisplay($createdBy): string
    {
        if ($createdBy === null || trim((string) $createdBy) === '') {
            return '';
        }

        if (is_numeric($createdBy)) {
            $userName = DB::table('library_users')->where('id', $createdBy)->value('name');
            if ($userName) {
                return strtoupper(substr(trim((string) $userName), 0, 1));
            }

            if (DB::table('libraries')->where('id', $createdBy)->exists()) {
                return 'Admin';
            }

            return '';
        }

        $name = trim((string) $createdBy);
        if (strtolower($name) === 'admin') {
            return 'Admin';
        }

        $libraryName = DB::table('libraries')->where('id', getLibraryId())->value('library_name');
        if ($libraryName && strcasecmp($name, trim((string) $libraryName)) === 0) {
            return 'Admin';
        }

        return strtoupper(substr($name, 0, 1));
    }

    private function formatLearnerForTransactions($learner, $detail): array
    {
        return [
            'id' => (int) $learner->id,
            'learner_no' => (string) ($learner->learner_no ?? ''),
            'name' => (string) ($learner->name ?? ''),
            'mobile' => (string) ($learner->mobile ?? ''),
            'profile_picture' => $learner->profile_picture ? asset($learner->profile_picture) : '',
            'seat_no' => $detail?->seat_no ? getSeatDisplayByMainNo($detail->seat_no) : 'GEN',
            'status' => (int) ($learner->status ?? 0) === 1 ? 'Active' : 'Inactive',
        ];
    }

    private function planPriceFromTransaction($transaction): float
    {
        return max(0, (float) ($transaction->total_amount ?? 0)
            - (float) ($transaction->locker_amount ?? 0)
            + (float) ($transaction->discount_amount ?? 0));
    }

    private function transactionTypeLabel($transaction, int $firstTransactionId): string
    {
        $paymentType = LearnerTransactionActivity::withoutGlobalScopes()
            ->where('learner_transaction_id', $transaction->id)
            ->whereNotNull('payment_type')
            ->whereNotIn(DB::raw('UPPER(payment_type)'), ['PENDING', 'TOKEN MONEY', 'MISCELLANEOUS', 'REFUND', 'SETTLED'])
            ->orderByDesc('id')
            ->value('payment_type');

        if ($paymentType) {
            return match (strtoupper((string) $paymentType)) {
                'SEAT ASSIGNMENT', 'SUBSCRIPTION' ,'NON-EXPIRED','EDIT'=> 'BOOK SEAT',
                'RENEW' => 'RE-NEW SEAT',
                'REACTIVE' => 'REACTIVE SEAT',
                'UPGRADE' => 'UPGRADE SEAT',
                'CHANGEPLAN', 'CHANGE PLAN' => 'CHANGE PLAN',
                default => strtoupper((string) $paymentType),
            };
        }

        return (int) $transaction->id === (int) $firstTransactionId ? 'BOOK SEAT' : 'RE-NEW SEAT';
    }

    private function subscriptionPaymentMode($transaction, $detail)
    {
        return $detail?->payment_mode;
    }

    private function paymentModeLabel($value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '';
        }

        if (is_numeric($value)) {
            return match ((int) $value) {
                1 => 'ONLINE',
                2 => 'OFFLINE',
                3 => 'PAYLATER',
                default => (string) $value,
            };
        }

        return strtoupper((string) $value);
    }

    private function paymentModeValue($value)
    {
        $mode = strtoupper((string) $value);

        return match ($mode) {
            '1', 'ONLINE' => 'ONLINE',
            '2', 'OFFLINE' => 'OFFLINE',
            '3', 'PAYLATER', 'PAY LATER' => 'PAYLATER',
            default => $mode ?: 'ONLINE',
        };
    }

    private function paymentModeId($value): int
    {
        $mode = strtoupper(trim((string) $value));

        return match ($mode) {
            '2', 'OFFLINE' => 2,
            '3', 'PAYLATER', 'PAY LATER' => 3,
            default => 1,
        };
    }

    private function money(float $amount): string
    {
        return number_format($amount, 0, '.', '');
    }

    /**
     * @param  array{with_revenue?: bool, learner_detail_id?: int|null}  $options
     * @return array{ok: bool, message: string}
     */
    public function run(string $operation, int $learnerId, array $options = []): array
    {
        return match ($operation) {
            'restore' => $this->restore($learnerId, $options['learner_detail_id'] ?? null),
            'permanent_delete' => $this->permanentDelete($learnerId, (bool) ($options['with_revenue'] ?? true)),
            'freeze' => $this->freezeOrUnfreeze($learnerId, true, $options['learner_detail_id'] ?? null),
            'unfreeze' => $this->freezeOrUnfreeze($learnerId, false, $options['learner_detail_id'] ?? null),
            default => ['ok' => false, 'message' => 'Invalid operation.'],
        };
    }

    /**
     * Restore: use latest trashed learner_detail for this learner when detail id not given.
     *
     * @return array{ok: bool, message: string}
     */
    public function restore(int $learnerId, ?int $learnerDetailId = null): array
    {
        try {
            $learnerDetail = $learnerDetailId !== null
                ? LearnerDetail::withTrashed()->find($learnerDetailId)
                : LearnerDetail::onlyTrashed()
                    ->where('learner_id', $learnerId)
                    ->orderByDesc('id')
                    ->first();

            if (! $learnerDetail) {
                return ['ok' => false, 'message' => 'Learner detail not found.'];
            }

            if ((int) $learnerDetail->learner_id !== $learnerId) {
                return ['ok' => false, 'message' => 'Learner detail does not match learner.'];
            }

            if (! $this->learnerBelongsToCurrentContext($learnerId)) {
                return ['ok' => false, 'message' => 'Learner not found.'];
            }

            if ($learnerDetail->plan_end_date && $learnerDetail->plan_end_date < now()->toDateString()) {
                return ['ok' => false, 'message' => 'Cannot restore learner. Plan has expired.'];
            }

            DB::transaction(function () use ($learnerDetail) {
                $learnerDetail->restore();
                $learnerDetail->status = 1;
                $learnerDetail->save();

                if ($learnerDetail->learner_id) {
                    $learner = Learner::withTrashed()->find($learnerDetail->learner_id);
                    if ($learner) {
                        $learner->restore();
                        $learner->status = 1;
                        $learner->save();
                    }

                    $refundExist = LearnerTransactionActivity::where('learner_id', $learnerDetail->learner_id)
                        ->where('payment_type', 'REFUND')
                        ->where('amount', '>', 0)
                        ->orderByDesc('id')
                        ->exists();

                    if ($refundExist) {
                        $refund = LearnerTransactionActivity::where('learner_id', $learnerDetail->learner_id)
                            ->orderByDesc('id')
                            ->first();

                        $this->logTransactionActivity([
                            'learner_id'   => $learnerDetail->learner_id,
                            'particular'   => 'Restore Seat',
                            'payment_type' => 'RESTORE',
                            'payment_mode' => 1,
                            'amount'       => $refund->amount ?? 0,
                            'dr_cr'        => 'Cr',
                        ]);
                    }

                    $trans = LearnerTransaction::withTrashed()
                        ->where('learner_id', $learnerDetail->learner_id)
                        ->orderByDesc('id')
                        ->first();

                    if ($trans && $trans->refund > 0) {
                        LearnerTransaction::withTrashed()
                            ->where('learner_id', $learnerDetail->learner_id)
                            ->update(['refund' => null]);
                    }

                    LearnerTransaction::withTrashed()
                        ->where('learner_id', $learnerDetail->learner_id)
                        ->restore();
                } else {
                    LearnerTransaction::withTrashed()
                        ->where('learner_detail_id', $learnerDetail->id)
                        ->restore();
                }
            });

            return ['ok' => true, 'message' => 'Learner, learner details, and transactions restored successfully.'];
        } catch (Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Match legacy destroy permanence, plus wipe transactions, details, and gift days.
     *
     * @return array{ok: bool, message: string}
     */
    public function permanentDelete(int $learnerId, bool $deleteAllActivities = true): array
    {
        try {
            if (! $this->learnerBelongsToCurrentContext($learnerId)) {
                return ['ok' => false, 'message' => 'Learner not found.'];
            }

            $lastTrashedDetail = LearnerDetail::where('learner_id', $learnerId)
                ->orderByDesc('id')
                ->first();

            if (! $lastTrashedDetail) {
                return ['ok' => false, 'message' => 'No learner detail found. Delete the seat first before permanent remove.'];
            }

            $customer = Learner::onlyTrashed()->where('id', $learnerId)->first();
         
            Log::info([
                'isclose'=>isCloseSeat($learnerId)

            ]);
            if ($customer || isCloseSeat($learnerId)) {

                DB::transaction(function () use ($learnerId, $deleteAllActivities, $customer) {
                    if ($deleteAllActivities) {
                        LearnerTransactionActivity::where('learner_id', $learnerId)->forceDelete();
                    } else {
                        LearnerTransactionActivity::where('learner_id', $learnerId)->update(['learner_id' => null]);
                    }

                    LearnerFeedback::where('learner_id', $learnerId)->forceDelete();
                    DB::table('learner_operations_log')->where('learner_id', $learnerId)->delete();
                    DB::table('learner_request')->where('learner_id', $learnerId)->delete();
                    DB::table('learner_gift_days')->where('learner_id', $learnerId)->delete();

                    LearnerTransaction::withTrashed()->where('learner_id', $learnerId)->forceDelete();
                    LearnerDetail::withTrashed()->where('learner_id', $learnerId)->forceDelete();

                    $customer->forceDelete();
                });
            }else{
                 return [
                    'ok' => false,
                    'message' => 'Permanent delete is allowed only for soft-deleted or closed learners.',
                ];
            }

            return ['ok' => true, 'message' => 'Learner and all related data permanently removed.'];
        } catch (Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Uses latest non-deleted learner detail when $learnerDetailId is null.
     *
     * @return array{ok: bool, message: string, frozen_days?: int}
     */
    public function freezeOrUnfreeze(int $learnerId, bool $freeze, ?int $learnerDetailId = null): array
    {
        if (! $this->learnerBelongsToCurrentContext($learnerId)) {
            return ['ok' => false, 'message' => 'Learner not found.'];
        }

        $detail = $learnerDetailId !== null
            ? LearnerDetail::where('id', $learnerDetailId)->where('learner_id', $learnerId)->first()
            : $this->latestActiveLearnerDetail($learnerId);

        if (! $detail) {
            return ['ok' => false, 'message' => 'No active learner detail found.'];
        }

        if ((int) $detail->status === 0) {
            return ['ok' => false, 'message' => 'Plan Expired'];
        }

        if ($freeze) {
            $detail->freeze_start_date = now();
            $detail->save();
            Learner::where('id', $detail->learner_id)->update(['frozen_status' => 1]);

            return ['ok' => true, 'message' => 'Plan frozen successfully!'];
        }

        if (empty($detail->freeze_start_date)) {
            return ['ok' => false, 'message' => 'Plan is not frozen.'];
        }

        $freezeStart = Carbon::parse($detail->freeze_start_date);
        $frozenDays = $freezeStart->diffInDays(Carbon::today());

        if ($frozenDays > 0) {
            $detail->plan_end_date = Carbon::parse($detail->plan_end_date)->addDays($frozenDays);
        }

        $detail->freeze_start_date = null;
        $detail->save();
        Learner::where('id', $detail->learner_id)->update(['frozen_status' => 2]);

        return [
            'ok'                => true,
           'message' => $frozenDays > 0
            ? "Plan unfrozen successfully! {$frozenDays} frozen day(s) added."
            : "Plan unfrozen successfully.",
            'frozen_days'      => $frozenDays,
        ];
    }

    private function latestActiveLearnerDetail(int $learnerId): ?LearnerDetail
    {
        return LearnerDetail::query()
            ->where('learner_id', $learnerId)
            ->whereNull('learner_detail.deleted_at')
            ->orderByDesc('id')
            ->first();
    }

    private function learnerBelongsToCurrentContext(int $learnerId): bool
    {
        $q = Learner::withTrashed()->where('id', $learnerId);
        $branchId = getCurrentBranch();
        if ($branchId) {
            $q->where('branch_id', $branchId);
        }

        return $q->exists();
    }

    private function logTransactionActivity(array $data): void
    {
        if (($data['payment_mode'] ?? 1) == 1) {
            $paymentMode = 'ONLINE';
        } elseif (($data['payment_mode'] ?? 1) == 2) {
            $paymentMode = 'OFFLINE';
        } else {
            $paymentMode = 'PAYLATER';
        }

        $payload = [
            'branch_id'              => getCurrentBranch(),
            'learner_id'             => $data['learner_id'],
            'learner_transaction_id' => $data['learner_transaction_id'] ?? null,
            'date'                   => now()->format('Y-m-d'),
            'transaction_id'         => transaction_id(),
            'particular'             => $data['particular'],
            'payment_type'           => $data['payment_type'],
            'payment_mode'           => $paymentMode,
            'amount'                 => $data['amount'] ?? 0,
            'dr_cr'                  => $data['dr_cr'],
        ];

        if (! empty($data['learner_transaction_id'])) {
            $payload['learner_transaction_id'] = $data['learner_transaction_id'];
        }

        LearnerTransactionActivity::create($payload);
    }

    public function closedelete(array $validated): array
    {
        $learnerId = (int) $validated['learner_id'];
        $operation = $validated['operation'];
        $isRefund = (bool) ($validated['isRefund'] ?? false);
        $transactionScope = $validated['transaction'] ?? 'all';

        if (! $this->learnerBelongsToCurrentContext($learnerId)) {
            return ['ok' => false, 'message' => 'Learner not found.'];
        }

        try {
            return DB::transaction(function () use ($validated, $learnerId, $operation, $isRefund, $transactionScope) {
                $this->settlmentCheck($learnerId, $operation, $operation === 'delete' ? $transactionScope : 'all');

                $learner = Learner::where('id', $learnerId)->firstOrFail();
                $detail = $this->latestActiveLearnerDetail($learnerId);

                if (! $detail) {
                    return ['ok' => false, 'message' => 'No active learner detail found.'];
                }

                // if ((int) $detail->status === 0) {
                //     return ['ok' => false, 'message' => 'This seat is already closed.'];
                // }

                if ($isRefund) {
                    $refundAmount = (float) ($validated['refund_amount'] ?? 0);
                    $pendingRefund = (float) ($validated['pendind_refund'] ?? 0);

                    $this->refundSettle([
                        'learner_id' => $learnerId,
                        'refund_amount' => $refundAmount,
                        'pendind_refund' => $pendingRefund,
                        'transaction' =>'current',
                    ]);

                    $this->logTransactionActivity([
                        'learner_id'   => $learnerId,
                        'particular'   => $operation === 'delete' ? 'Delete Seat' : 'Close Seat',
                        'payment_type' => 'REFUND',
                        'payment_mode' => $validated['payment_mode'],
                        'amount'       => $refundAmount,
                        'dr_cr'        => 'Dr',
                    ]);
                }

                if (! empty($validated['remark'])) {
                    $learner->remark = $validated['remark'];
                }

                if ($operation === 'delete') {
                    // $deletedDetailIds = $this->softDeleteLearnerDetails($learnerId, $transactionScope, $detail);
                    // $this->softDeleteTransactions($learnerId, $transactionScope);

                    // if ($transactionScope === 'all') {
                    //     $this->logLearnerOperation($learnerId, null, 'deleteSeat', [
                    //         'field_updated' => 'deleted_at',
                    //         'old_value' => 'deleteSeat',
                    //         'new_value' => now()->toISOString(),
                    //     ]);
                    // } else {
                    //     $this->logLearnerOperation($learnerId, (int) null, 'deleteSeat', [
                    //         'field_updated' => 'deleted_at',
                    //         'old_value' => 'deleteSeat',
                    //         'new_value' => now()->toISOString(),
                    //     ]);
                    // }
                    $detail->update([
                        'status'=>0
                    ]);
                    $detail->delete();
                    $this->logLearnerOperation($learnerId,null, 'deleteSeat', [
                            'field_updated' => 'deleted_at',
                            'old_value' => 'deleteSeat',
                            'new_value' => now()->toISOString(),
                        ]);
                } else {
                    $today = now()->format('Y-m-d');
                    $update = [
                        'plan_end_date' => $today,
                        'status' => 0,
                    ];

                    if ($detail->plan_start_date > $today) {
                        $update['plan_start_date'] = $today;
                    }

                    $detail->update($update);

                    $this->logLearnerOperation($learnerId, (int) $detail->id, 'closeSeat', [
                        'field_updated' => 'status',
                        'old_value' => '1',
                        'new_value' => '0',
                    ]);
                }

                $learner->status = 0;
                $learner->save();

                if ($operation === 'delete') {
                    $learner->delete();
                }

                if ($operation === 'close') {
                    $this->sendCloseNotification($learnerId);
                }

                return [
                    'ok' => true,
                    'message' => $operation === 'delete'
                        ? 'Learner deleted successfully.'
                        : 'Learner closed successfully.',
                   
                ];
            });
        } catch (Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function settlmentCheck($learnerId, $operation, string $transactionScope = 'all')
    {
        $txList = $this->transactionSelectionQuery((int) $learnerId, $transactionScope)
            ->orderBy('id', 'asc')
            ->get();

        if ($txList->isEmpty()) {
            throw new Exception("No transactions found");
        }

        $pending = $txList->sum('pending_amount');
        $extra = $txList->sum('refund'); // advance/refund column
        $netAmount = $pending - $extra;

        if ($netAmount > 0) {
            throw new Exception("This member has a pending amount({$netAmount}). Please settle it before {$operation}");
        } elseif ($netAmount < 0) {
            throw new Exception("This member has an extra amount(".abs($netAmount)."). Please settle it before {$operation}");
        }
    }

    private function refundSettle($data)
    {
        $txList = $this->transactionSelectionQuery((int) $data['learner_id'], $data['transaction'] ?? 'current')
            ->orderByDesc('id')
            ->get();

        if ($txList->isEmpty()) {
            throw new Exception("No transactions found");
        }

        $refundAmount = (float) $data['refund_amount'];
        $lastTransaction = LearnerTransaction::where('learner_id', (int) $data['learner_id'])
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->first();

        if (! $lastTransaction) {
            throw new Exception("No transactions found");
        }

        if ($refundAmount > (float) ($lastTransaction->paid_amount + $lastTransaction->miscellaneous + $lastTransaction->token_money)) {
            throw new Exception("Refund amount cannot exceed last transaction paid amount");
        }

        $remainingRefund = $refundAmount;
        foreach ($txList as $tx) {
            if ($remainingRefund <= 0) {
                break;
            }

            $deducted = min((float) $tx->paid_amount, $remainingRefund);
            $tx->paid_amount = (float) $tx->paid_amount - $deducted;
            $tx->refund = (float) ($data['pendind_refund'] ?? 0);
            $tx->save();

            $remainingRefund -= $deducted;
        }
    }

 

    private function logLearnerOperation(int $learnerId, ?int $learnerDetailId, string $operation, array $changes): void
    {
        $createdAt = now();
        while (DB::table('learner_operations_log')
            ->where('learner_id', $learnerId)
            ->where('operation', $operation)
            ->where('created_at', $createdAt->format('Y-m-d H:i:s'))
            ->exists()) {
            $createdAt = $createdAt->copy()->addSecond();
        }

        DB::table('learner_operations_log')->insert([
            'learner_id' => $learnerId,
            'learner_detail_id' => $learnerDetailId,
            'library_id' => getLibraryId(),
            'field_updated' => $changes['field_updated'],
            'old_value' => $changes['old_value'],
            'new_value' => $changes['new_value'],
            'updated_by' => getLibraryId(),
            'operation' => $operation,
            'branch_id' => getCurrentBranch(),
            'created_at' => $createdAt,
        ]);
    }

    private function softDeleteTransactions(int $learnerId, string $transactionScope): void
    {
        $transactions = $this->transactionSelectionQuery($learnerId, $transactionScope)
            ->orderByDesc('id')
            ->get();

        if ($transactions->isEmpty()) {
            throw new Exception("No transactions found");
        }

        foreach ($transactions as $transaction) {
            $transaction->delete();
        }
    }

    private function transactionSelectionQuery(int $learnerId, string $transactionScope)
    {
        $query = LearnerTransaction::where('learner_id', $learnerId)
            ->whereNull('deleted_at');

        if ($transactionScope === 'current') {
            $latestId = (clone $query)->orderByDesc('id')->value('id');
            $query->where('id', $latestId);
        }

        return $query;
    }

    private function sendCloseNotification(int $learnerId): void
    {
        try {
            $noti = new NotificationSentController;

            if (autowabaNotificationActive()) {
                $noti->autoMessage($learnerId, 'waba', 'close-waba');
            }

            if (autotextNotificationActive()) {
                $noti->autoMessage($learnerId, 'text', 'close-sms');
            }
        } catch (\Throwable $e) {
            \Log::error('Close notification sending failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }

     public function handleLearnerOtherPayment($request)
    {
        $transaction = LearnerTransaction::withTrashed()
            ->where('learner_id', $request->learner_id)
            ->first();

        if (!$transaction) {
            return [
                'status' => false,
                'message' => 'Learner transaction record not found.'
            ];
        }

        // Payment Logic
        if ($request->payment_type === 'token_money') {
            $transaction->token_money = $request->fees;
            $payment_type = 'TOKEN MONEY';
            $dr_cr = 'Cr';

        } elseif ($request->payment_type === 'miscellaneous') {
            $transaction->miscellaneous = ($transaction->miscellaneous ?? 0) + $request->fees;
            $payment_type = 'MISCELLANEOUS';
            $dr_cr = 'Cr';

        } elseif ($request->payment_type === 'pending_refund') {
            $transaction->refund = ($transaction->refund ?? 0) - $request->fees;
            $payment_type = 'REFUND';
            $dr_cr = 'Dr';
        }

        $transaction->save();

        // Activity Log Data
        $data = [
            'learner_id'   => $request->learner_id,
            'learner_transaction_id' => $transaction->id,
            'particular'   => 'Paid By Trans',
            'payment_type' => $payment_type,
            'payment_mode' => $request->payment_mode ?? 1,
            'amount'       => $request->fees,
            'dr_cr'        => $dr_cr,
        ];

      $this->logTransactionActivity($data);

        // Refund Notification
        if ($payment_type === 'REFUND') {
            try {
                $noti = new NotificationSentController;

                if (autowabaNotificationActive()) {
                    $noti->autoMessage($data['learner_id'], 'waba', 'refund-waba');
                }

                if (autotextNotificationActive()) {
                    $noti->autoMessage($data['learner_id'], 'text', 'refund-sms');
                }

            } catch (\Throwable $e) {
                Log::error('Notification failed: ' . $e->getMessage());
            }
        }

        return [
            'status' => true,
            'payment_type' => $payment_type,
            'message' => $payment_type === 'REFUND'
                ? 'Refund Processed Successfully.'
                : 'Payment successfully recorded.'
        ];
    }

    public function learnerActivityAmountManage($activityId,$reqAmount){
        $activity=LearnerTransactionActivity::withoutGlobalScopes()->where('id',$activityId)->select('id','dr_cr','amount','payment_type','learner_transaction_id')->first();
        if (!$activity || !$activity->learner_transaction_id) {
            return;
        }

        $tran=LearnerTransaction::withTrashed()->where('id',$activity->learner_transaction_id)->first();
        if (!$tran) {
            return;
        }
        $paymentType = strtoupper((string) ($activity->payment_type ?? ''));
        $diffrence=(float) $reqAmount - (float) $activity->amount; 

        if($paymentType === 'TOKEN MONEY'){
            $tran->token_money=max(0, (float) $tran->token_money+$diffrence);
        }elseif($paymentType === 'MISCELLANEOUS'){
            $tran->miscellaneous=max(0, (float) $tran->miscellaneous+$diffrence);
        }else{
             // not for other payment and expense  
        
            if($activity->dr_cr=='Cr'){
                //negative or positive according
                $tran->paid_amount=max(0, (float) $tran->paid_amount + $diffrence);
                $tran->pending_amount=max(0, (float) $tran->pending_amount - $diffrence);

            }elseif($activity->dr_cr=='Dr'){
                //refund negative or positive according
                $tran->paid_amount=max(0, (float) $tran->paid_amount - $diffrence);

            }elseif($activity->dr_cr=='Settle'){
                //settlement
                $tran->paid_amount=max(0, (float) $tran->paid_amount - $diffrence);
                $tran->sattle_amount=max(0, (float) $tran->sattle_amount + $diffrence);
                

            }
        }

        $tran->is_paid = (float) $tran->pending_amount <= 0 ? 1 : 0;
        $activity->amount=(float) $reqAmount;
        $this->setUpdatedBy($activity);
        $this->setUpdatedBy($tran);
        $activity->save();
        $tran->save();
    }
}
