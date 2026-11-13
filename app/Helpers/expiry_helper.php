<?php

/*
|--------------------------------------------------------------------------
| Expiry windows: "about to expire" and "in extension"
|--------------------------------------------------------------------------
| One rule for every screen (dashboard lists + counts, learner list filter
| + tiles, monthly report list, sidebar count, learner API) so the numbers
| always match:
|
|  - About to expire: active plan (learner_detail.status = 1) that ends
|    today or within the next 5 days.
|  - In extension:    active plan that has already ended but is still inside
|    the branch's extend days (Branch::extend_days, see getExtendDays()).
|
| In both cases a learner who has already renewed (has a later plan) or is on a
| non-expiry seat is left out, and deleted learners / plans are ignored.
|
| The query must have the learner_detail table available under its own name
| (a LearnerDetail query, or a learners query joined to learner_detail).
*/

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

if (!function_exists('expiringWithinDays')) {
    /** Days ahead that count as "about to expire". */
    function expiringWithinDays(): int
    {
        return 5;
    }
}

if (!function_exists('applyExpiryWindowBase')) {
    function applyExpiryWindowBase($query)
    {
        return $query
            ->where('learner_detail.status', 1)
            ->whereNull('learner_detail.deleted_at')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('learners as ew_learner')
                    ->whereColumn('ew_learner.id', 'learner_detail.learner_id')
                    ->whereNull('ew_learner.deleted_at')
                    // non-expiry seats renew on their own, they never expire
                    ->where(fn ($n) => $n->where('ew_learner.no_expiry', 0)->orWhereNull('ew_learner.no_expiry'));
            })
            // already renewed: a later plan exists for the same learner
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('learner_detail as ew_next')
                    ->whereColumn('ew_next.learner_id', 'learner_detail.learner_id')
                    ->whereNull('ew_next.deleted_at')
                    ->whereColumn('ew_next.plan_end_date', '>', 'learner_detail.plan_end_date');
            });
    }
}

if (!function_exists('applyAboutToExpireScope')) {
    function applyAboutToExpireScope($query)
    {
        $today = Carbon::today();

        return applyExpiryWindowBase($query)->whereBetween('learner_detail.plan_end_date', [
            $today->format('Y-m-d'),
            $today->copy()->addDays(expiringWithinDays())->format('Y-m-d'),
        ]);
    }
}

if (!function_exists('applyExtensionScope')) {
    function applyExtensionScope($query, $branchId = null)
    {
        $extendDays = (int) getExtendDays($branchId);
        $today = Carbon::today();

        return applyExpiryWindowBase($query)
            ->where('learner_detail.plan_end_date', '<', $today->format('Y-m-d'))
            ->where('learner_detail.plan_end_date', '>=', $today->copy()->subDays($extendDays)->format('Y-m-d'));
    }
}

if (!function_exists('durationInHours')) {
    /** Hours between two "HH:MM" times; an end at or before the start runs past midnight. */
    function durationInHours($startTime, $endTime): float
    {
        if (empty($startTime) || empty($endTime)) {
            return 0.0;
        }
        $toMin = function ($t) {
            $parts = explode(':', trim($t));
            return (int) $parts[0] * 60 + (int) ($parts[1] ?? 0);
        };
        $s = $toMin($startTime);
        $e = $toMin($endTime);
        if ($e <= $s) {
            $e += 1440;
        }
        return round(($e - $s) / 60, 2);
    }
}
