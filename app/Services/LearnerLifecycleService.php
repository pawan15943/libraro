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

class LearnerLifecycleService
{
    /**
     * @param  array{delete_all?: bool, learner_detail_id?: int|null}  $options
     * @return array{ok: bool, message: string}
     */
    public function run(string $operation, int $learnerId, array $options = []): array
    {
        return match ($operation) {
            'restore' => $this->restore($learnerId, $options['learner_detail_id'] ?? null),
            'permanent_delete' => $this->permanentDelete($learnerId, (bool) ($options['delete_all'] ?? true)),
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

            $lastTrashedDetail = LearnerDetail::onlyTrashed()
                ->where('learner_id', $learnerId)
                ->orderByDesc('id')
                ->first();

            if (! $lastTrashedDetail) {
                return ['ok' => false, 'message' => 'No soft-deleted learner detail found. Delete the seat first before permanent remove.'];
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
}
