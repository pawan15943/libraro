<?php

namespace App\Services;

use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerTransaction;
use App\Models\LearnerTransactionActivity;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RenewTransactionDeleteService
{
    public const DELETE_WINDOW_DAYS = 5;

    public const VALIDATION_MESSAGE = 'This transaction cannot be deleted (valid only within 5 days of creation).';

    public function findLatestRenewTransaction(int $learnerId): ?LearnerTransaction
{
    $cutoff = Carbon::now()->subDays(self::DELETE_WINDOW_DAYS);

    // ✅ Step 1: Check renew (must have more than 1 entry)
    $details = LearnerDetail::where('learner_id', $learnerId)
        ->orderByDesc('id')
        ->get();

    if ($details->count() <= 1) {
        return null;
    }

    // ✅ Step 2: Get latest learner_detail (THIS IS RENEW)
    $latestDetail = $details->first();

    // ✅ Step 3: Fetch transaction linked to this detail
    return LearnerTransaction::withoutGlobalScopes()
        ->where('learner_id', $learnerId)
        ->where('learner_detail_id', $latestDetail->id) // 🔥 IMPORTANT
        ->where('branch_id', getCurrentBranch())
        ->where('created_at', '>=', $cutoff)
        ->latest()
        ->first();
}

    public function transactionIsRenew(LearnerTransaction $tx): bool
    {
        return LearnerTransactionActivity::withoutGlobalScopes()
            ->where('learner_id', $tx->learner_id)
            ->whereIn('payment_type', ['RENEW','UPGRADE'])
            ->where(function ($q) use ($tx) {
                $q->where('learner_transaction_id', $tx->id)
                    ->orWhere(function ($q2) use ($tx) {
                        $q2->whereNull('learner_transaction_id')
                            ->whereDate('created_at', $tx->created_at->toDateString())
                            ->whereRaw('ABS(amount - ?) < 0.01', [(float) $tx->paid_amount]);
                    });
            })
            ->exists();
    }

    /**
     * @return array{ok: bool, message: ?string}
     */
    public function validateDeletable(LearnerTransaction $tx): array
    {
        if (!$this->transactionIsRenew($tx)) {
            return ['ok' => false, 'message' => self::VALIDATION_MESSAGE];
        }

        $cutoff = Carbon::now()->subDays(self::DELETE_WINDOW_DAYS);
        if ($tx->created_at->lt($cutoff)) {
            return ['ok' => false, 'message' => self::VALIDATION_MESSAGE];
        }

        $detail = LearnerDetail::find($tx->learner_detail_id);
        if (!$detail) {
            return ['ok' => false, 'message' => 'Linked learner detail was not found.'];
        }

        $latestDetailId = LearnerDetail::where('learner_id', $tx->learner_id)
            ->orderByDesc('id')
            ->value('id');

        if ((int) $latestDetailId !== (int) $detail->id) {
            return [
                'ok' => false,
                'message' => 'This renew cannot be deleted because a newer plan or seat change exists.',
            ];
        }

        return ['ok' => true, 'message' => null];
    }

    public function deleteRenewTransaction(LearnerTransaction $tx): void
    {
        $validation = $this->validateDeletable($tx);
        if (!$validation['ok']) {
            throw new \RuntimeException($validation['message'] ?? 'Cannot delete this transaction.');
        }

        DB::transaction(function () use ($tx) {
            $tx = LearnerTransaction::withoutGlobalScopes()->lockForUpdate()->find($tx->id);
            if (!$tx) {
                throw new \RuntimeException('Transaction not found.');
            }

            $validation = $this->validateDeletable($tx);
            if (!$validation['ok']) {
                throw new \RuntimeException($validation['message'] ?? 'Cannot delete this transaction.');
            }

            $renewDetail = LearnerDetail::find($tx->learner_detail_id);
            if (!$renewDetail) {
                throw new \RuntimeException('Learner detail not found.');
            }

            $previousDetail = LearnerDetail::where('learner_id', $tx->learner_id)
                ->where('id', '<', $renewDetail->id)
                ->orderByDesc('id')
                ->first();

            if (!$previousDetail) {
                throw new \RuntimeException('Previous learner detail not found; cannot roll back this renew.');
            }

            LearnerTransactionActivity::withoutGlobalScopes()
                ->where('learner_transaction_id', $tx->id)
                ->delete();

            LearnerTransactionActivity::withoutGlobalScopes()
                ->where('learner_id', $tx->learner_id)
                ->where('payment_type', 'RENEW')
                ->whereNull('learner_transaction_id')
                ->whereDate('created_at', $tx->created_at->toDateString())
                ->whereRaw('ABS(amount - ?) < 0.01', [(float) $tx->paid_amount])
                ->delete();

            $tx->delete();
            $renewDetail->delete();

            $planStatus = getPlanStatusDetails($previousDetail->plan_end_date);
            $detailActive = $planStatus['class'] !== 'expired';

            $previousDetail->status = $detailActive ? 1 : 0;
            $previousDetail->save();

            $learner = Learner::withTrashed()->lockForUpdate()->find($tx->learner_id);
            if (!$learner) {
                throw new \RuntimeException('Learner not found.');
            }

            if ($learner->trashed()) {
                $learner->restore();
            }

            $learner->seat_no = $previousDetail->seat_no;
            $learner->hours = $previousDetail->hour;
            $learner->status = $detailActive ? 1 : 0;
            $learner->save();

            $updatedBy = Auth::guard('library_user')->check()
                ? Auth::guard('library_user')->id()
                : getLibraryId();

            $logPayload = [
                'learner_id' => $tx->learner_id,
                'learner_detail_id' => $previousDetail->id,
                'library_id' => getLibraryId(),
                'operation' => 'renewDelete',
                'field_updated' => 'renew_transaction',
                'old_value' => (string) $tx->id,
                'new_value' => 'deleted',
                'updated_by' => $updatedBy,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('learner_operations_log', 'branch_id')) {
                $logPayload['branch_id'] = getCurrentBranch();
            }
            if (Schema::hasColumn('learner_operations_log', 'summary')) {
                $logPayload['summary'] = 'Renew transaction deleted (Renew Delete)';
            }

            DB::table('learner_operations_log')->insert($logPayload);
        });
    }
}
