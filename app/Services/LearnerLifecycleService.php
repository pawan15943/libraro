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
