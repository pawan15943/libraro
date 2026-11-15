<?php

namespace App\Services;

use App\Models\LearnerTransaction;
use App\Models\LearnerTransactionActivity;

/**
 * A learner's advance: money paid over a bill (e.g. ₹1000 for an ₹800 booking, "keep the rest for
 * next month"). It sits in learner_transactions.refund — the same column the settlement page calls
 * "extra" — so nothing new is stored. docs/LEARNER_ADVANCE.md.
 *
 *  - apply():  a renewal / reactivation / upgrade uses it — the advance pays the learner's dues
 *              (oldest first), each source row's refund goes down by what it gave.
 *  - settle(): the learner leaves (close / delete seat): what is left is refunded or waived.
 *
 * Activity rows: "Advance adjusted" / "Advance waived" are SETTLED·Settle (not money, so reports
 * don't count them twice); "Advance refunded" is REFUND·Dr (money given back, a refund in reports).
 */
class LearnerAdvanceService
{
    public const ADJUSTED = 'Advance adjusted';
    public const REFUNDED = 'Advance refunded';
    public const WAIVED = 'Advance waived';

    /** Advance the learner has now (₹). */
    public function balance(int $learnerId): float
    {
        return round((float) $this->rows($learnerId)->where('refund', '>', 0)->sum('refund'), 2);
    }

    /** What the learner still owes on earlier bills (₹). */
    public function pending(int $learnerId): float
    {
        return round((float) $this->rows($learnerId)->where('pending_amount', '>', 0)->sum('pending_amount'), 2);
    }

    /**
     * The advance pays the learner's dues, oldest bill first. Returns the amount used.
     * $logOnTxId: the bill the activity row is shown on (the new renewal); defaults to the first one paid.
     */
    public function apply(int $learnerId, ?int $logOnTxId = null, ?int $branchId = null): float
    {
        $sources = $this->rows($learnerId)->where('refund', '>', 0)->orderBy('id')->lockForUpdate()->get();
        $dues = $this->rows($learnerId)->where('pending_amount', '>', 0)->orderBy('id')->lockForUpdate()->get();
        if ($sources->isEmpty() || $dues->isEmpty()) {
            return 0.0;
        }
        $branchId = $branchId ?: (int) $sources->first()->branch_id;

        $used = 0.0;
        foreach ($sources as $src) {
            $left = (float) $src->refund;
            foreach ($dues as $due) {
                $owed = (float) $due->pending_amount;
                if ($left <= 0.009) {
                    break;
                }
                if ($owed <= 0.009) {
                    continue;
                }
                $take = round(min($owed, $left), 2);
                $due->paid_amount = round((float) $due->paid_amount + $take, 2);
                $due->pending_amount = round($owed - $take, 2);
                $due->is_paid = $due->pending_amount <= 0.009 ? 1 : 0;
                $due->save();
                $left = round($left - $take, 2);
                $used = round($used + $take, 2);
                $logOnTxId ??= (int) $due->id;
            }
            if ($left !== (float) $src->refund) {
                $src->refund = max(0, $left);
                $src->save();
            }
        }

        if ($used > 0) {
            $this->log($learnerId, $logOnTxId, self::ADJUSTED, 'SETTLED', 'Settle', $used, 'PAYLATER', $branchId);
        }

        return $used;
    }

    /**
     * The learner leaves: the advance first pays any dues, the rest is refunded (cash / online)
     * or waived. Returns ['adjusted' => ₹, 'refunded' => ₹, 'waived' => ₹].
     *
     * @param  string  $action  'refund' | 'waive'
     * @param  int|string|null  $paymentMode  1 online, 2 offline (refund only)
     */
    public function settle(int $learnerId, string $action, $paymentMode = null, ?int $branchId = null): array
    {
        $adjusted = $this->apply($learnerId, null, $branchId);
        $left = $this->balance($learnerId);
        $out = ['adjusted' => $adjusted, 'refunded' => 0.0, 'waived' => 0.0];
        if ($left <= 0.009) {
            return $out;
        }

        $sources = $this->rows($learnerId)->where('refund', '>', 0)->orderByDesc('id')->lockForUpdate()->get();
        $logOn = (int) $sources->first()->id;
        $branchId = $branchId ?: (int) $sources->first()->branch_id;
        foreach ($sources as $src) {
            $src->refund = 0;
            $src->save();
        }

        if ($action === 'refund') {
            $mode = (int) $paymentMode === 2 ? 'OFFLINE' : 'ONLINE';
            $this->log($learnerId, $logOn, self::REFUNDED, 'REFUND', 'Dr', $left, $mode, $branchId);
            $out['refunded'] = $left;
        } else {
            $this->log($learnerId, $logOn, self::WAIVED, 'SETTLED', 'Settle', $left, 'PAYLATER', $branchId);
            $out['waived'] = $left;
        }

        return $out;
    }

    /** True for the activity rows written here (they can't be deleted on their own). */
    public static function isAdvanceRow($activity): bool
    {
        return in_array((string) ($activity->particular ?? ''), [self::ADJUSTED, self::WAIVED], true);
    }

    private function rows(int $learnerId)
    {
        // learner ids are unique across libraries: no tenant / branch scope needed (works the same from the API)
        return LearnerTransaction::withoutGlobalScopes()->where('learner_id', $learnerId)->whereNull('deleted_at');
    }

    private function log(int $learnerId, ?int $txId, string $particular, string $type, string $drCr, float $amount, string $mode, ?int $branchId): void
    {
        LearnerTransactionActivity::create([
            'branch_id' => $branchId ?: getCurrentBranch(),
            'learner_id' => $learnerId,
            'learner_transaction_id' => $txId,
            'date' => now()->format('Y-m-d'),
            'transaction_id' => transaction_id(),
            'particular' => $particular,
            'payment_type' => $type,
            'payment_mode' => $mode,
            'amount' => round($amount, 2),
            'dr_cr' => $drCr,
        ]);
    }
}
