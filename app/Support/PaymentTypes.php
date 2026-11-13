<?php

namespace App\Support;

/**
 * One definition of what each learner_transaction_activity.payment_type counts as, so the web
 * dashboard, the daily / monthly reports and the app's dashboard (DashboardService) show the same
 * numbers.
 *
 *  - collection : money received for a plan (booking, renewal, reactivation, upgrade, non-expiry /
 *                 VIP / reserved seat, a refunded amount coming back on restore) plus the extra paid
 *                 when a plan is changed or edited (CHANGE PLAN / EDIT on the Cr side)
 *  - other      : token money and miscellaneous fees
 *  - pending    : old dues collected
 *  - refund     : money given back (REFUND, and CHANGE PLAN / EDIT on the Dr side)
 *  - expense    : library expenses
 * SETTLED rows are write-offs, not money, and count nowhere.
 */
class PaymentTypes
{
    /** counted as collection whatever the dr_cr (they are only ever Cr) */
    public const COLLECTION = ['SEAT ASSIGNMENT', 'RENEW', 'REACTIVE', 'UPGRADE', 'NON-EXPIRED', 'VIP', 'RESERVED', 'RESTORE'];

    /** collection when Cr, refund when Dr */
    public const ADJUSTMENTS = ['CHANGE PLAN', 'EDIT'];

    public const OTHER = ['TOKEN MONEY', 'MISCELLANEOUS'];

    public const PENDING = 'PENDING';

    public const REFUND = 'REFUND';

    public const EXPENSE = 'EXPENSE';

    /** Adds "is collection" to a query on learner_transaction_activity. */
    public static function whereCollection($query, string $table = '')
    {
        $c = $table !== '' ? $table . '.' : '';

        return $query->where(function ($q) use ($c) {
            $q->whereIn($c . 'payment_type', self::COLLECTION)
                ->orWhere(fn ($s) => $s->whereIn($c . 'payment_type', self::ADJUSTMENTS)->where($c . 'dr_cr', 'Cr'));
        });
    }

    /** Adds "is a refund" to a query on learner_transaction_activity. */
    public static function whereRefund($query, string $table = '')
    {
        $c = $table !== '' ? $table . '.' : '';

        return $query->where(function ($q) use ($c) {
            $q->where($c . 'payment_type', self::REFUND)
                ->orWhere(fn ($s) => $s->whereIn($c . 'payment_type', self::ADJUSTMENTS)->where($c . 'dr_cr', 'Dr'));
        });
    }

    /** SQL condition for SUM(CASE WHEN ... THEN amount ...) */
    public static function collectionSql(string $table = ''): string
    {
        $c = $table !== '' ? $table . '.' : '';

        return "({$c}payment_type IN (" . self::list(self::COLLECTION) . ") OR ({$c}payment_type IN (" . self::list(self::ADJUSTMENTS) . ") AND {$c}dr_cr = 'Cr'))";
    }

    public static function otherSql(string $table = ''): string
    {
        $c = $table !== '' ? $table . '.' : '';

        return "({$c}payment_type IN (" . self::list(self::OTHER) . ") AND {$c}dr_cr = 'Cr')";
    }

    public static function refundSql(string $table = ''): string
    {
        $c = $table !== '' ? $table . '.' : '';

        return "({$c}payment_type = '" . self::REFUND . "' OR ({$c}payment_type IN (" . self::list(self::ADJUSTMENTS) . ") AND {$c}dr_cr = 'Dr'))";
    }

    /** Types shown in a "collection" list of activity rows (Blade filters by type name). */
    public static function collectionTypeNames(): array
    {
        return array_merge(self::COLLECTION, self::ADJUSTMENTS);
    }

    /** Types shown in a "refund" list of activity rows. */
    public static function refundTypeNames(): array
    {
        return array_merge([self::REFUND], self::ADJUSTMENTS);
    }

    private static function list(array $types): string
    {
        return implode(', ', array_map(fn ($t) => "'" . $t . "'", $types));
    }
}
