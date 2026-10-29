<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lets an existing seat-booking query also see the extra shifts of multi-shift bookings
 * (learner_detail_slots) without rewriting it.
 *
 * The slot variant of a query swaps the learner_detail table for a derived table with the
 * same columns, one row per active slot: dates, status, freeze and learner come from the
 * parent booking, seat_no and plan_type_id from the slot. Every where/join/scope the query
 * already has keeps working, so a consumer only changes `->get()` to SeatSlotQuery::get().
 *
 * See docs/ADVANCED_SHIFTS.md.
 */
class SeatSlotQuery
{
    private const SLOT_ROWS = 'SELECT d.id, d.library_id, d.branch_id, d.learner_id, d.join_date,'
        . ' d.plan_start_date, d.plan_end_date, d.plan_price_id, d.plan_id, s.plan_type_id, s.seat_no,'
        . ' spt.slot_hours AS hour, d.freeze_start_date, d.payment_mode, d.status, d.is_paid, d.flag,'
        . ' d.exam_id, d.created_at, d.updated_at, d.deleted_at, s.id AS slot_id'
        . ' FROM learner_detail_slots s'
        . ' JOIN learner_detail d ON d.id = s.learner_detail_id'
        . ' JOIN plan_types spt ON spt.id = s.plan_type_id'
        . ' WHERE s.deleted_at IS NULL';

    private static ?bool $enabled = null;

    /**
     * Rows of the query plus the matching slot rows.
     */
    public static function get($query, array $columns = ['*']): Collection
    {
        $rows = $query->get($columns);
        $slots = self::slotVariant($query);

        return $slots ? $rows->concat($slots->get($columns))->values() : $rows;
    }

    public static function exists($query): bool
    {
        if ($query->exists()) {
            return true;
        }
        $slots = self::slotVariant($query);

        return $slots ? $slots->exists() : false;
    }

    public static function count($query): int
    {
        $slots = self::slotVariant($query);

        return (int) $query->count() + ($slots ? (int) $slots->count() : 0);
    }

    /**
     * pluck($value, $key) of a grouped aggregate query, with the slot rows' values added per key.
     */
    public static function pluckSum($query, string $value, string $key): Collection
    {
        $out = $query->pluck($value, $key)->map(fn ($v) => (float) $v);
        $slots = self::slotVariant($query);
        if ($slots) {
            foreach ($slots->pluck($value, $key) as $k => $v) {
                $out[$k] = ($out[$k] ?? 0) + (float) $v;
            }
        }

        return $out;
    }

    /**
     * Hours booked by active slots per seat, the slot counterpart of summing learners.hours
     * by learners.seat_no.
     *
     * @return Collection<string, float> seat_no => hours
     */
    public static function activeHoursBySeat(int $libraryId, array $seatNos, ?int $excludeLearnerId = null): Collection
    {
        if (! self::enabled() || empty($seatNos)) {
            return collect();
        }

        return DB::table('learner_detail_slots as s')
            ->join('learner_detail as d', 'd.id', '=', 's.learner_detail_id')
            ->join('learners as l', 'l.id', '=', 'd.learner_id')
            ->join('plan_types as pt', 'pt.id', '=', 's.plan_type_id')
            ->whereNull('s.deleted_at')
            ->whereNull('d.deleted_at')
            ->whereNull('l.deleted_at')
            ->where('d.status', 1)
            ->where('l.status', 1)
            ->where('l.library_id', $libraryId)
            ->whereIn('s.seat_no', $seatNos)
            ->when($excludeLearnerId, fn ($q) => $q->where('l.id', '!=', $excludeLearnerId))
            ->groupBy('s.seat_no')
            ->selectRaw('s.seat_no, SUM(pt.slot_hours) AS h')
            ->pluck('h', 'seat_no')
            ->map(fn ($h) => (float) $h);
    }

    /**
     * A copy of the query that reads slot rows instead of learner_detail, or null when the
     * slots table does not exist yet.
     *
     * @template T of \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     * @param  T  $query
     * @return T|null
     */
    public static function slotVariant($query)
    {
        if (! self::enabled()) {
            return null;
        }

        $clone = clone $query;
        $base = $clone instanceof EloquentBuilder ? $clone->getQuery() : $clone;
        $replaced = false;

        if (is_string($base->from) && ($alias = self::aliasOf($base->from)) !== null) {
            $base->from = self::table($alias);
            $replaced = true;
        }

        // Join clauses are objects shared with the original query, so copy before changing
        $base->joins = array_map(function ($join) use (&$replaced) {
            $join = clone $join;
            if (is_string($join->table) && ($alias = self::aliasOf($join->table)) !== null) {
                $join->table = self::table($alias);
                $replaced = true;
            }

            return $join;
        }, (array) $base->joins) ?: null;

        if (! $replaced) {
            throw new \LogicException('SeatSlotQuery: the query does not read learner_detail.');
        }

        return $clone;
    }

    private static function aliasOf(string $table): ?string
    {
        if (preg_match('/^learner_detail(?:\s+as\s+(\w+))?$/i', trim($table), $m)) {
            return $m[1] ?? 'learner_detail';
        }

        return null;
    }

    private static function table(string $alias): Expression
    {
        return DB::raw('(' . self::SLOT_ROWS . ') as `' . $alias . '`');
    }

    private static function enabled(): bool
    {
        return self::$enabled ??= Schema::hasTable('learner_detail_slots');
    }
}
