<?php

namespace App\Services;

use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerFeedback;
use App\Models\LearnerTransaction;
use App\Models\LearnerTransactionActivity;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\NotificationSentController;

class LearnerLifecycleService
{
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
                'next_due_date' => optional($transactions->firstWhere('pending_amount', '>', 0))->due_date,
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
        $refundActivityAmount = (float) $activities
            ->filter(fn ($activity) => strtoupper((string) $activity->payment_type) === 'REFUND')
            ->sum('amount');

        return [
            'learner' => $this->formatLearnerForTransactions($dashboard['learner'], $dashboard['currentDetail']),
            'overview' => [
                'total_amount_received' => $this->money((float) $transactions->sum('paid_amount') + $totalOtherPaid),
                'total_amount' => $this->money((float) $transactions->sum('total_amount') + $totalOtherPaid),
                'pending_amount' => $this->money((float) $transactions->sum('pending_amount')),
                'extra_amount' => $this->money((float) $transactions->sum('refund')),
                'refund_amount' => $this->money($refundActivityAmount),
                'next_due_date' => (string) ($dashboard['summary']['next_due_date'] ?? ''),
                'last_transactions' => $activities->take(3)->map(fn ($activity) => $this->formatActivity($activity))->values(),
            ],
            'subscription' => [
                'current_transaction' => $currentTransaction ? $this->formatSubscriptionTransaction($currentTransaction, 0, 0, 0, $firstTransactionId) : null,
                'summary' => [
                    'total_payment' => $this->money((float) $transactions->sum('total_amount')),
                    'received_amount' => $this->money((float) $transactions->sum('paid_amount')),
                    'pending_amount' => $this->money((float) $transactions->sum('pending_amount')),
                    'total_subscription_transactions' => $transactions->count(),
                ],
                'transactions' => $transactions->map(fn ($transaction, $index) => $this->formatSubscriptionTransaction($transaction, $index, 0, 0, $firstTransactionId))->values(),
            ],
            'other_payment' => [
                'summary' => [
                    'token_money' => $this->money((float) $transactions->sum('token_money')),
                    'miscellaneous' => $this->money((float) $transactions->sum('miscellaneous')),
                    'total_paid' => $this->money($totalOtherPaid),
                    'refund_pending' => $this->money((float) $transactions->sum('refund')),
                ],
                'payments' => $this->formatOtherPayments($transactions, $activities),
            ],
            'all_transaction' => $this->formatAllTransactions($transactions, $activities, $firstTransactionId),
            'transaction_activity' => $activities->map(fn ($activity) => $this->formatActivity($activity))->values(),
        ];
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

        return $transactions->map(function ($transaction, $index) use ($activities, $carryForwardById, $firstTransactionId) {
            $carry = $carryForwardById[$transaction->id]['carry_forward_amount'] ?? 0;
            $extraPaid = $carryForwardById[$transaction->id]['extra_paid_amount'] ?? 0;

            return $this->formatSubscriptionTransaction($transaction, $index, $carry, $extraPaid, $firstTransactionId) + [
                'activity' => $activities
                    ->where('learner_transaction_id', $transaction->id)
                    ->map(fn ($activity) => $this->formatActivity($activity))
                    ->values(),
            ];
        })->values();
    }

    private function formatSubscriptionTransaction($transaction, int $index, float $carryForwardAmount, float $extraPaidAmount = 0, int $firstTransactionId = 0): array
    {
        $detail = $transaction->learnerDetail;
        $planPrice = $this->planPriceFromTransaction($transaction);
        $totalAmount = (float) ($transaction->total_amount ?? 0);
        $finalPayable = max(0, $totalAmount + $carryForwardAmount - $extraPaidAmount);
        $pending = (float) ($transaction->pending_amount ?? 0);
        $extra = (float) ($transaction->refund ?? 0);

        return [
            'id' => (int) $transaction->id,
            'learner_detail_id' => (int) ($transaction->learner_detail_id ?? 0),
            'transaction_ref' => (string) ($transaction->transaction_id ?? ''),
            'transaction_type' => $this->transactionTypeLabel($transaction, $firstTransactionId),
            'plan' => $detail?->plan?->name ?? '',
            'plan_type' => $detail?->planType?->name ?? '',
            'plan_start_date' => (string) ($detail?->plan_start_date ?? ''),
            'plan_end_date' => (string) ($detail?->plan_end_date ?? ''),
            'paid_date' => (string) ($transaction->paid_date ?? ''),
            'due_date' => (string) ($transaction->due_date ?? ''),
            'payment_mode' => $this->paymentModeLabel($detail?->payment_mode),
            'plan_price' => $this->money($planPrice),
            'locker_amount' => $this->money((float) ($transaction->locker_amount ?? 0)),
            'discount_amount' => $this->money((float) ($transaction->discount_amount ?? 0)),
            'total_amount' => $this->money($totalAmount),
            'carry_forward_amount' => $this->money($carryForwardAmount),
            'extra_paid_amount' => $this->money($extraPaidAmount),
            'final_payable_amount' => $this->money($finalPayable),
            'total_paid_amount' => $this->money((float) ($transaction->paid_amount ?? 0)),
            'pending_amount' => $this->money($pending),
            'extra_amount' => $this->money($extra),
            'token_money' => $this->money((float) ($transaction->token_money ?? 0)),
            'miscellaneous' => $this->money((float) ($transaction->miscellaneous ?? 0)),
            'is_paid' => (int) ($transaction->is_paid ?? 0),
        ];
    }

    private function formatOtherPayments($transactions, $activities)
    {
        $rows = collect();

        foreach ($transactions as $transaction) {
            if ((float) ($transaction->token_money ?? 0) > 0) {
                $rows->push($this->formatOtherPaymentRow($transaction, 'TOKEN MONEY', (float) $transaction->token_money));
            }

            if ((float) ($transaction->miscellaneous ?? 0) > 0) {
                $rows->push($this->formatOtherPaymentRow($transaction, 'MISCELLANEOUS', (float) $transaction->miscellaneous));
            }
        }

        $activities
            ->filter(fn ($activity) => in_array(strtoupper((string) $activity->payment_type), ['TOKEN MONEY', 'MISCELLANEOUS', 'REFUND'], true))
            ->each(function ($activity) use ($rows) {
                $rows->push([
                    'id' => null,
                    'learner_transaction_id' => (int) ($activity->learner_transaction_id ?? 0),
                    'payment_type' => (string) ($activity->payment_type ?? ''),
                    'amount' => $this->money((float) ($activity->amount ?? 0)),
                    'payment_mode' => $this->paymentModeLabel($activity->payment_mode),
                    'paid_date' => (string) ($activity->date ?? ''),
                    'particular' => (string) ($activity->particular ?? ''),
                    'source' => 'activity',
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
            'source' => 'transaction',
        ];
    }

    private function formatActivity($activity): array
    {
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
            'added_by' => $activity->created_by_name,
            'updated_by' => $activity->created_by_name,
            'updated_date' => optional($activity->updated_at)->toDateTimeString() ?? '',
        ];
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
        return (int) $transaction->id === (int) $firstTransactionId ? 'BOOK SEAT' : 'RE-NEW SEAT';
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

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
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
            if (! $customer) {
                return ['ok' => false, 'message' => 'Learner must be soft-deleted before permanent delete.'];
            }

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
            'message'           => "Plan unfrozen successfully! Frozen days added: {$frozenDays}",
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

    // private function softDeleteLearnerDetails(int $learnerId, string $transactionScope, LearnerDetail $currentDetail): array
    // {
    //     if ($transactionScope === 'current') {
    //         $currentDetail->status = 0;
    //         $currentDetail->save();
    //         $detailId = (int) $currentDetail->id;
    //         $currentDetail->delete();

    //         return [$detailId];
    //     }

    //     $details = LearnerDetail::where('learner_id', $learnerId)
    //         ->whereNull('learner_detail.deleted_at')
    //         ->get();

    //     if ($details->isEmpty()) {
    //         throw new Exception("No active learner detail found");
    //     }

    //     $detailIds = [];
    //     foreach ($details as $detail) {
    //         $detail->status = 0;
    //         $detail->save();
    //         $detailIds[] = (int) $detail->id;
    //         $detail->delete();
    //     }

    //     return $detailIds;
    // }

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
}
