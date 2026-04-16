<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Booking;
use App\Models\Hour;
use App\Models\LearnerDetail;
use App\Models\PlanType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Facades\DB;

class LearnerShiftSupport
{
    /**
     * Normalize plan_type_id from request: single value, array, or plan_type_id[].
     *
     * @return list<int>
     */
    public static function normalizePlanTypeIdsFromRequest(Request $request): array
    {
        $raw = $request->input('plan_type_id');
        if ($raw === null || $raw === '') {
            return [];
        }
        if (is_string($raw) && str_contains($raw, ',')) {
            $ids = array_map('intval', explode(',', $raw));

            return array_values(array_unique(array_filter($ids, fn ($id) => $id > 0)));
        }
        if (is_array($raw)) {
            $ids = array_map('intval', $raw);
        } else {
            $ids = [(int) $raw];
        }

        $ids = array_values(array_unique(array_filter($ids, fn ($id) => $id > 0)));

        return $ids;
    }

    /**
     * @param  mixed  $raw From form data array key plan_type_id
     * @return list<int>
     */
    public static function normalizePlanTypeIdsFromMixed($raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        if (is_array($raw)) {
            $ids = array_map('intval', $raw);
        } else {
            $ids = [(int) $raw];
        }

        return array_values(array_unique(array_filter($ids, fn ($id) => $id > 0)));
    }

    public static function combinedPlanPrice(int $planId, array $planTypeIds, $planStartDate, ?int $branchId): float
    {
        if (empty($planTypeIds)) {
            return 0.0;
        }

        $branchId = $branchId ?? getCurrentBranch();
        $start = $planStartDate ? Carbon::parse($planStartDate) : Carbon::today();

        $hasFixed = Branch::where('id', $branchId)->whereNotNull('fixed_billing_date')->exists();

        $sum = 0.0;
        foreach ($planTypeIds as $ptId) {
            if ($hasFixed) {
                $sum += (float) getBillingCyclePrice($planId, (int) $ptId, $start, $branchId);
            } else {
                $sum += (float) getPlanPrice($planId, (int) $ptId, $branchId);
            }
        }

        return round($sum);
    }

    public static function sumSlotHours(array $planTypeIds): int
    {
        if (empty($planTypeIds)) {
            return 0;
        }

        return (int) PlanType::withoutGlobalScopes()
            ->whereIn('id', $planTypeIds)
            ->sum('slot_hours');
    }

    /**
     * Branch "hour" from hours table = max slot hours per seat/day for this branch.
     */
    public static function branchDailyOperatingHours(?int $branchId): int
    {
        if (! $branchId) {
            return 0;
        }

        return (int) (Hour::withoutGlobalScopes()->where('branch_id', $branchId)->value('hour') ?? 0);
    }

    /**
     * @return string|null Error message if cap exceeded, otherwise null.
     */
    public static function slotHoursOverBranchCapMessage(array $planTypeIds, ?int $branchId): ?string
    {
        $cap = self::branchDailyOperatingHours($branchId);
        if ($cap <= 0) {
            return null;
        }
        $sum = self::sumSlotHours($planTypeIds);
        if ($sum <= $cap) {
            return null;
        }

        return "Selected shifts total {$sum} hours; this branch allows at most {$cap} hours per day. Remove a shift or choose shorter slots.";
    }

    public static function syncLearnerDetailPlanTypes(LearnerDetail $detail, array $planTypeIds): void
    {
        $planTypeIds = array_values(array_unique(array_filter($planTypeIds, fn ($id) => $id > 0)));
        if (empty($planTypeIds)) {
            return;
        }

        DB::table('learner_detail_plan_type')->where('learner_detail_id', $detail->id)->delete();

        $sort = 0;
        foreach ($planTypeIds as $ptId) {
            DB::table('learner_detail_plan_type')->insert([
                'learner_detail_id' => $detail->id,
                'plan_type_id' => $ptId,
                'sort_order' => $sort++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public static function syncBookingPlanTypes(Booking $booking, array $planTypeIds): void
    {
        $planTypeIds = array_values(array_unique(array_filter($planTypeIds, fn ($id) => $id > 0)));
        if (empty($planTypeIds)) {
            return;
        }

        DB::table('booking_plan_type')->where('booking_id', $booking->id)->delete();

        $sort = 0;
        foreach ($planTypeIds as $ptId) {
            DB::table('booking_plan_type')->insert([
                'booking_id' => $booking->id,
                'plan_type_id' => $ptId,
                'sort_order' => $sort++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @return list<int>
     */
    public static function planTypeIdsForLearnerDetail(LearnerDetail $detail): array
    {
        $fromPivot = DB::table('learner_detail_plan_type')
            ->where('learner_detail_id', $detail->id)
            ->orderBy('sort_order')
            ->pluck('plan_type_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (! empty($fromPivot)) {
            return array_values(array_unique($fromPivot));
        }

        if ($detail->plan_type_id) {
            return [(int) $detail->plan_type_id];
        }

        return [];
    }

    /**
     * @return list<int>
     */
    public static function planTypeIdsForBooking(Booking $booking): array
    {
        $fromPivot = DB::table('booking_plan_type')
            ->where('booking_id', $booking->id)
            ->orderBy('sort_order')
            ->pluck('plan_type_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (! empty($fromPivot)) {
            return array_values(array_unique($fromPivot));
        }

        if ($booking->plan_type_id) {
            return [(int) $booking->plan_type_id];
        }

        return [];
    }

    /**
     * Normalized time segments [{start,end,day_type_id}] for overlap checks.
     *
     * @return list<array{start:string,end:string,day_type_id:int|null}>
     */
    public static function timeSegmentsForPlanTypeIds(array $planTypeIds): array
    {
        $segments = [];
        foreach ($planTypeIds as $ptId) {
            $pt = PlanType::withoutGlobalScopes()->find($ptId);
            if (! $pt) {
                continue;
            }
            foreach (normalizeTimeRange($pt->start_time, $pt->end_time) as $range) {
                $segments[] = [
                    'start' => $range['start'],
                    'end' => $range['end'],
                    'day_type_id' => $pt->day_type_id,
                ];
            }
        }

        return $segments;
    }

    /**
     * @return list<array{start:string,end:string,day_type_id:int|null}>
     */
    public static function timeSegmentsForLearnerDetailId(int $learnerDetailId): array
    {
        $detail = LearnerDetail::withoutGlobalScopes()->find($learnerDetailId);
        if (! $detail) {
            return [];
        }

        return self::timeSegmentsForPlanTypeIds(self::planTypeIdsForLearnerDetail($detail));
    }

    /**
     * @return array{subscription: string, shift_timing: string}
     */
    public static function receiptLabelsForLearnerDetail(LearnerDetail $detail): array
    {
        $ids = self::planTypeIdsForLearnerDetail($detail);
        if ($ids === []) {
            return ['subscription' => 'NA', 'shift_timing' => ''];
        }

        $pts = PlanType::withoutGlobalScopes()->whereIn('id', $ids)->get()->keyBy('id');
        $names = [];
        $times = [];
        foreach ($ids as $id) {
            $pt = $pts->get($id);
            if ($pt) {
                $names[] = $pt->name;
                $times[] = date('h:i A', strtotime((string) $pt->start_time)).' to '.date('h:i A', strtotime((string) $pt->end_time));
            }
        }

        return [
            'subscription' => $names !== [] ? implode(', ', $names) : 'NA',
            'shift_timing' => $times !== [] ? implode(' | ', $times) : '',
        ];
    }

    /**
     * @return array<int, array{subscription: string, shift_timing: string}>
     */
    public static function bulkShiftLabelsForLearnerDetailIds(array $learnerDetailIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $learnerDetailIds))));
        if ($ids === []) {
            return [];
        }

        $pivotRows = DB::table('learner_detail_plan_type as lpt')
            ->whereIn('lpt.learner_detail_id', $ids)
            ->orderBy('lpt.learner_detail_id')
            ->orderBy('lpt.sort_order')
            ->join('plan_types as pt', 'pt.id', '=', 'lpt.plan_type_id')
            ->select('lpt.learner_detail_id', 'pt.name', 'pt.start_time', 'pt.end_time')
            ->get();

        $byDetailPivot = [];
        foreach ($pivotRows as $r) {
            $lid = (int) $r->learner_detail_id;
            $byDetailPivot[$lid][] = $r;
        }

        $details = DB::table('learner_detail')
            ->whereIn('id', $ids)
            ->select('id', 'plan_type_id')
            ->get()
            ->keyBy(fn ($d) => (int) $d->id);

        $fallbackPtIds = [];
        foreach ($ids as $lid) {
            if (empty($byDetailPivot[$lid])) {
                $dRow = $details->get($lid);
                $ptId = $dRow ? (int) ($dRow->plan_type_id ?? 0) : 0;
                if ($ptId > 0) {
                    $fallbackPtIds[] = $ptId;
                }
            }
        }

        $planTypesMap = [];
        if ($fallbackPtIds !== []) {
            $planTypesMap = PlanType::withoutGlobalScopes()
                ->whereIn('id', array_unique($fallbackPtIds))
                ->get()
                ->keyBy('id');
        }

        $out = [];
        foreach ($ids as $lid) {
            $names = [];
            $times = [];
            if (! empty($byDetailPivot[$lid])) {
                foreach ($byDetailPivot[$lid] as $r) {
                    $names[] = $r->name;
                    $times[] = date('h:i A', strtotime((string) $r->start_time)).' to '.date('h:i A', strtotime((string) $r->end_time));
                }
            } else {
                $dRow = $details->get($lid);
                $ptId = $dRow ? (int) ($dRow->plan_type_id ?? 0) : 0;
                if ($ptId > 0 && isset($planTypesMap[$ptId])) {
                    $pt = $planTypesMap[$ptId];
                    $names[] = $pt->name;
                    $times[] = date('h:i A', strtotime((string) $pt->start_time)).' to '.date('h:i A', strtotime((string) $pt->end_time));
                }
            }
            $out[$lid] = [
                'subscription' => $names !== [] ? implode(', ', $names) : 'NA',
                'shift_timing' => $times !== [] ? implode(' | ', $times) : '',
            ];
        }

        return $out;
    }

    public static function mergeShiftLabelsIntoObject(object $row, int $learnerDetailId): void
    {
        if ($learnerDetailId <= 0) {
            return;
        }
        $map = self::bulkShiftLabelsForLearnerDetailIds([$learnerDetailId]);
        if (! isset($map[$learnerDetailId])) {
            return;
        }
        $labels = $map[$learnerDetailId];
        if ($labels['subscription'] !== 'NA') {
            $row->plan_type_name = $labels['subscription'];
        }
        if ($labels['shift_timing'] !== '') {
            $row->shift_times_display = $labels['shift_timing'];
        }
    }

    /**
     * @param  \Illuminate\Support\Collection|\Illuminate\Pagination\AbstractPaginator  $rows
     */
    public static function applyBulkShiftLabelsToLearnerRows($rows, string $detailIdColumn = 'learner_detail_id'): void
    {
        if ($rows instanceof AbstractPaginator) {
            $items = $rows->getCollection();
        } elseif ($rows instanceof \Illuminate\Support\Collection) {
            $items = $rows;
        } else {
            return;
        }

        $ids = $items->pluck($detailIdColumn)->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        if ($ids === []) {
            return;
        }

        $map = self::bulkShiftLabelsForLearnerDetailIds($ids);
        foreach ($items as $row) {
            $lid = (int) ($row->{$detailIdColumn} ?? 0);
            if (! $lid || ! isset($map[$lid])) {
                continue;
            }
            $labels = $map[$lid];
            if ($labels['subscription'] !== 'NA') {
                $row->plan_type_name = $labels['subscription'];
            }
            if ($labels['shift_timing'] !== '') {
                $row->shift_times_display = $labels['shift_timing'];
            }
        }
    }
}
