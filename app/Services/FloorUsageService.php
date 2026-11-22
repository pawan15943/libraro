<?php

namespace App\Services;

use App\Models\Floor;
use App\Models\Hour;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tells whether learners sit on a floor, so the floor can't be deleted or deactivated
 * under them.
 *
 * learner_detail.seat_no is the branch-wide ("main") seat number. Active floors take
 * consecutive main numbers in floor_no order, exactly like generateSeatNumbers().
 */
class FloorUsageService
{
    public const IN_USE_MESSAGE = 'Learners are assigned to seats on this floor, so it cannot be deleted or deactivated. Shift or remove those learners first.';

    /**
     * Main seat numbers that belong to the floor. A deactivated floor owns no seats.
     *
     * @return int[]
     */
    public function mainSeatNumbers(Floor $floor): array
    {
        if ($floor->trashed()) {
            return [];
        }

        $totalSeats = (int) Hour::withoutGlobalScopes()
            ->where('branch_id', $floor->branch_id)
            ->whereNull('deleted_at')
            ->value('seats');

        $floors = Floor::withoutGlobalScopes()
            ->where('branch_id', $floor->branch_id)
            ->whereNull('deleted_at')
            ->orderBy('floor_no')
            ->get(['id', 'from_seat', 'to_seat']);

        $main = 1;
        foreach ($floors as $f) {
            $count = max(0, (int) $f->to_seat - (int) ($f->from_seat ?? 1) + 1);
            $start = $main;
            $end = min($main + $count - 1, $totalSeats);

            if ((int) $f->id === (int) $floor->id) {
                return $end >= $start ? range($start, $end) : [];
            }

            $main += $count;
            if ($main > $totalSeats) {
                break;
            }
        }

        return [];
    }

    /**
     * True when a running or upcoming booking (including extra shifts of multi-shift
     * bookings) uses a seat of this floor.
     */
    public function hasLearners(Floor $floor): bool
    {
        $seats = $this->mainSeatNumbers($floor);
        if (empty($seats)) {
            return false;
        }

        $query = DB::table('learner_detail')
            ->join('learners', 'learners.id', '=', 'learner_detail.learner_id')
            ->where('learner_detail.branch_id', $floor->branch_id)
            ->whereIn('learner_detail.seat_no', array_map('strval', $seats))
            ->whereNull('learner_detail.deleted_at')
            ->whereNull('learners.deleted_at')
            ->where(function ($q) {
                $q->where(fn ($a) => $a->where('learner_detail.status', 1)->where('learners.status', 1))
                    ->orWhereDate('learner_detail.plan_start_date', '>=', Carbon::today());
            });

        return SeatSlotQuery::exists($query);
    }
}
