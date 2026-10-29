<?php

namespace App\Services;

use App\Models\LearnerDetail;
use App\Models\PlanPrice;
use App\Models\PlanType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Timed shift templates (booking_mode duration / free): the owner picks the real timing
 * at booking time and the booking is stored against a hidden child plan type carrying
 * that timing. Every existing seat check then works on the child as on any fixed shift.
 *
 * See docs/ADVANCED_SHIFTS.md.
 */
class ShiftTimingService
{
    private const DAY = 1440;

    // Shortest slot a "free" booking may take, in minutes
    private const MIN_FREE_MINUTES = 30;

    /**
     * Validates the timing entered for a template. Does not touch the database.
     *
     * @return array{error: bool, message?: string, start?: string, end?: string, minutes?: int}
     */
    public function validateTiming(PlanType $template, ?string $start, ?string $end = null): array
    {
        if (! $template->isTimedTemplate()) {
            return ['error' => true, 'message' => 'This shift does not take a custom timing.'];
        }

        $startMin = self::toMinutes($start);
        if ($startMin === null) {
            return ['error' => true, 'message' => 'Enter a valid shift start time.'];
        }

        if ($template->booking_mode === PlanType::MODE_DURATION) {
            $minutes = (int) round(((float) $template->slot_hours) * 60);
            if ($minutes <= 0) {
                return ['error' => true, 'message' => 'Shift hours are not configured for this shift.'];
            }
            $endMin = ($startMin + $minutes) % self::DAY;
        } else {
            $endMin = self::toMinutes($end);
            if ($endMin === null) {
                return ['error' => true, 'message' => 'Enter a valid shift end time.'];
            }
            $minutes = $endMin > $startMin ? $endMin - $startMin : $endMin + self::DAY - $startMin;
            if ($minutes < self::MIN_FREE_MINUTES) {
                return ['error' => true, 'message' => 'Shift must be at least ' . self::MIN_FREE_MINUTES . ' minutes long.'];
            }
        }

        [$winStart, $winEnd] = self::windowOf($template);
        $from = $startMin < $winStart ? $startMin + self::DAY : $startMin;
        if ($from < $winStart || $from + $minutes > $winEnd) {
            return [
                'error' => true,
                'message' => 'Shift timing must be within ' . self::label($template->start_time) . ' – ' . self::label($template->end_time) . '.',
            ];
        }

        return [
            'error' => false,
            'start' => self::fromMinutes($startMin),
            'end' => self::fromMinutes($endMin),
            'minutes' => $minutes,
        ];
    }

    /**
     * Finds or creates the hidden child plan type for a template + timing (+ price for "free").
     * The template's plan prices are copied to a new child; a "free" child also gets
     * $price for $planId.
     */
    public function resolveChild(PlanType $template, string $start, ?string $end, ?int $planId = null, ?float $price = null): PlanType
    {
        $timing = $this->validateTiming($template, $start, $end);
        if ($timing['error']) {
            throw new \InvalidArgumentException($timing['message']);
        }

        $isFree = $template->booking_mode === PlanType::MODE_FREE;

        return DB::transaction(function () use ($template, $timing, $isFree, $planId, $price) {
            $existing = PlanType::withoutGlobalScopes()
                ->where('parent_id', $template->id)
                ->where('start_time', $timing['start'])
                ->where('end_time', $timing['end'])
                ->whereNull('deleted_at')
                ->when($isFree && $planId, function ($q) use ($planId, $price) {
                    $q->whereExists(function ($sub) use ($planId, $price) {
                        $sub->select(DB::raw(1))
                            ->from('plan_prices')
                            ->whereColumn('plan_prices.plan_type_id', 'plan_types.id')
                            ->where('plan_prices.plan_id', $planId)
                            ->whereNull('plan_prices.deleted_at')
                            ->whereRaw('CAST(plan_prices.price AS DECIMAL(12,2)) = ?', [round((float) $price, 2)]);
                    });
                })
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $child = PlanType::withoutGlobalScopes()->create([
                'library_id' => $template->library_id,
                'branch_id' => $template->branch_id,
                'name' => $template->name . ' (' . self::label($timing['start']) . ' – ' . self::label($timing['end']) . ')',
                'start_time' => $timing['start'],
                'end_time' => $timing['end'],
                'slot_hours' => self::hoursString($timing['minutes']),
                'day_type_id' => $template->day_type_id,
                'booking_mode' => $template->booking_mode,
                'parent_id' => $template->id,
                'image' => $template->image,
            ]);

            $templatePrices = PlanPrice::withoutGlobalScopes()
                ->where('plan_type_id', $template->id)
                ->whereNull('deleted_at')
                ->get();

            foreach ($templatePrices as $row) {
                PlanPrice::withoutGlobalScopes()->create([
                    'library_id' => $row->library_id,
                    'branch_id' => $row->branch_id,
                    'plan_id' => $row->plan_id,
                    'plan_type_id' => $child->id,
                    'price' => ($isFree && $planId && (int) $row->plan_id === (int) $planId) ? (string) $price : $row->price,
                ]);
            }

            if ($isFree && $planId && ! $templatePrices->contains(fn ($row) => (int) $row->plan_id === (int) $planId)) {
                PlanPrice::withoutGlobalScopes()->create([
                    'library_id' => $template->library_id,
                    'branch_id' => $template->branch_id,
                    'plan_id' => $planId,
                    'plan_type_id' => $child->id,
                    'price' => (string) $price,
                ]);
            }

            return $child;
        });
    }

    /**
     * A duration template's price applies to its hidden timing rows (new bookings and
     * renewals). Free rows keep the price the owner entered at booking.
     */
    public function syncDurationChildPrices(PlanPrice $price): void
    {
        $childIds = PlanType::withoutGlobalScopes()
            ->where('parent_id', $price->plan_type_id)
            ->where('booking_mode', PlanType::MODE_DURATION)
            ->pluck('id');

        foreach ($childIds as $childId) {
            PlanPrice::withoutGlobalScopes()->updateOrCreate(
                ['plan_type_id' => $childId, 'plan_id' => $price->plan_id, 'branch_id' => $price->branch_id],
                ['library_id' => $price->library_id, 'price' => $price->price, 'deleted_at' => null]
            );
        }
    }

    /**
     * Time windows already taken on a seat for bookings whose dates overlap the requested
     * plan period, using the same booking set as checkAvailability().
     *
     * @return Collection<int, array{start: string, end: string, name: string}>
     */
    public function bookedWindows(int $branchId, int $seatNo, Carbon $startDate, Carbon $endDate, ?int $excludeLearnerId = null): Collection
    {
        $rows = SeatSlotQuery::get(LearnerDetail::withoutGlobalScopes()
            ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->where('learner_detail.branch_id', $branchId)
            ->where('learner_detail.seat_no', $seatNo)
            ->where('learner_detail.plan_end_date', '>=', now())
            ->whereNull('learner_detail.deleted_at')
            ->where('learner_detail.plan_start_date', '<=', $endDate->format('Y-m-d'))
            ->where('learner_detail.plan_end_date', '>=', $startDate->format('Y-m-d'))
            ->when($excludeLearnerId, fn ($q) => $q->where('learner_detail.learner_id', '!=', $excludeLearnerId)), ['plan_types.name', 'plan_types.start_time', 'plan_types.end_time', 'plan_types.start_time_2', 'plan_types.end_time_2']);

        return $rows->flatMap(fn ($row) => collect(self::rowWindows($row))->map(fn ($w) => [
            'start' => $w[0],
            'end' => $w[1],
            'name' => $row->name,
        ]))->values();
    }

    /**
     * Free gaps inside a template's allowed window, given the windows already booked.
     *
     * @param  iterable<array{start: string, end: string}>  $booked
     * @return array<int, array{start: string, end: string, minutes: int}>
     */
    public function freeGaps(PlanType $template, iterable $booked): array
    {
        [$winStart, $winEnd] = self::windowOf($template);

        $busy = [];
        foreach ($booked as $w) {
            $s = self::toMinutes($w['start']);
            $e = self::toMinutes($w['end']);
            if ($s === null || $e === null) {
                continue;
            }
            if ($e <= $s) {
                $e += self::DAY;
            }
            foreach ([-self::DAY, 0, self::DAY] as $offset) {
                $from = max($winStart, $s + $offset);
                $to = min($winEnd, $e + $offset);
                if ($from < $to) {
                    $busy[] = [$from, $to];
                }
            }
        }

        usort($busy, fn ($a, $b) => $a[0] <=> $b[0]);

        $gaps = [];
        $cursor = $winStart;
        foreach ($busy as [$from, $to]) {
            if ($from > $cursor) {
                $gaps[] = [$cursor, $from];
            }
            $cursor = max($cursor, $to);
        }
        if ($cursor < $winEnd) {
            $gaps[] = [$cursor, $winEnd];
        }

        return array_map(fn ($g) => [
            'start' => self::fromMinutes($g[0] % self::DAY),
            'end' => self::fromMinutes($g[1] % self::DAY),
            'minutes' => $g[1] - $g[0],
        ], $gaps);
    }

    /**
     * Whether a template can still fit on a seat whose current bookings are $bookings
     * (rows with start_time/end_time and optional start_time_2/end_time_2).
     */
    public function templateFits(PlanType $template, iterable $bookings): bool
    {
        $booked = [];
        foreach ($bookings as $row) {
            foreach (self::rowWindows($row) as $w) {
                $booked[] = ['start' => $w[0], 'end' => $w[1]];
            }
        }

        $need = $template->booking_mode === PlanType::MODE_DURATION
            ? (int) round(((float) $template->slot_hours) * 60)
            : self::MIN_FREE_MINUTES;

        foreach ($this->freeGaps($template, $booked) as $gap) {
            if ($gap['minutes'] >= $need) {
                return true;
            }
        }

        return false;
    }

    /**
     * Template's allowed booking window in minutes, end pushed past midnight when needed.
     *
     * @return array{0: int, 1: int}
     */
    private static function windowOf(PlanType $template): array
    {
        $start = self::toMinutes($template->start_time) ?? 0;
        $end = self::toMinutes($template->end_time) ?? 0;
        if ($end <= $start) {
            $end += self::DAY;
        }

        return [$start, $end];
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private static function rowWindows($row): array
    {
        $windows = [];
        if (! empty($row->start_time) && ! empty($row->end_time)) {
            $windows[] = [$row->start_time, $row->end_time];
        }
        if (! empty($row->start_time_2) && ! empty($row->end_time_2)) {
            $windows[] = [$row->start_time_2, $row->end_time_2];
        }

        return $windows;
    }

    private static function toMinutes(?string $time): ?int
    {
        if ($time === null || ! preg_match('/^(\d{1,2}):(\d{2})/', trim($time), $m)) {
            return null;
        }
        $h = (int) $m[1];
        $i = (int) $m[2];
        if ($h > 24 || $i > 59 || ($h === 24 && $i > 0)) {
            return null;
        }

        return ($h * 60 + $i) % self::DAY;
    }

    private static function fromMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private static function hoursString(int $minutes): string
    {
        return rtrim(rtrim(number_format($minutes / 60, 2, '.', ''), '0'), '.');
    }

    private static function label(?string $time): string
    {
        try {
            $c = Carbon::parse($time);

            return $c->format('i') === '00' ? $c->format('g A') : $c->format('g:i A');
        } catch (\Throwable $e) {
            return (string) $time;
        }
    }
}
