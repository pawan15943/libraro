<?php

namespace App\Services;

use App\Models\PlanType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * A branch's open – close time. `hour.hour` is how many hours the library is open and
 * `hour.open_time` when it opens, so it closes at open_time + hour (past midnight when
 * needed). A 24 hour branch, or one whose open time isn't set, has no window: nothing
 * is limited by the clock, only by the hour count (as before).
 *
 * Every shift must run inside this window: fixed shifts when they are saved, and the
 * advanced shifts (fixed hours / flexible hours / direct booking) when they are booked,
 * since they take the library hours as their time.
 */
class BranchHoursService
{
    private const DAY = 1440;

    // Fewest hours a day a library can be open (same as the operating hours forms)
    public const MIN_HOURS = 10;

    /** @var array<int, array{0: int, 1: int}|null> */
    private static array $cache = [];

    /**
     * Library window in minutes from midnight: [open, close], close > open (past 1440 when it
     * closes after midnight). Null when open 24 hours or the open time isn't set.
     *
     * @return array{0: int, 1: int}|null
     */
    public static function window(?int $branchId): ?array
    {
        if (!$branchId) {
            return null;
        }
        if (!array_key_exists($branchId, self::$cache)) {
            $row = DB::table('hour')->where('branch_id', $branchId)->whereNull('deleted_at')->first(['hour', 'open_time']);
            self::$cache[$branchId] = $row ? self::windowOf((int) $row->hour, $row->open_time) : null;
        }

        return self::$cache[$branchId];
    }

    public static function forget(?int $branchId = null): void
    {
        if ($branchId) {
            unset(self::$cache[$branchId]);
        } else {
            self::$cache = [];
        }
    }

    /** @return array{0: int, 1: int}|null */
    public static function windowOf(int $hour, ?string $openTime): ?array
    {
        $open = self::toMinutes($openTime);
        if ($hour <= 0 || $hour >= 24 || $open === null) {
            return null;
        }

        return [$open, $open + $hour * 60];
    }

    /** Whether the branch is open all day (24 hours). */
    public static function isAllDay(?int $branchId): bool
    {
        return $branchId && (int) DB::table('hour')->where('branch_id', $branchId)->whereNull('deleted_at')->value('hour') >= 24;
    }

    /** Open and close as "HH:MM", or null (see window()). */
    public static function times(?int $branchId): ?array
    {
        $w = self::window($branchId);

        return $w ? [self::fromMinutes($w[0]), self::fromMinutes($w[1])] : null;
    }

    /** "6 AM – 10 PM"; null when open 24 hours or not set. */
    public static function label(?int $branchId): ?string
    {
        $w = self::window($branchId);

        return $w ? self::windowLabel($w) : null;
    }

    /** @param array{0: int, 1: int} $w */
    public static function windowLabel(array $w): string
    {
        return self::timeLabel(self::fromMinutes($w[0])) . ' – ' . self::timeLabel(self::fromMinutes($w[1]));
    }

    /**
     * Whether a slot starting at $start (minutes from midnight) and $minutes long lies inside
     * the window, also when the window or the slot runs past midnight.
     *
     * @param array{0: int, 1: int} $window
     */
    public static function fits(array $window, int $start, int $minutes): bool
    {
        foreach ([0, self::DAY] as $offset) {
            $s = $start + $offset;
            if ($s >= $window[0] && $s + $minutes <= $window[1]) {
                return true;
            }
        }

        return false;
    }

    /** Start (minutes) and length of a stored "HH:MM" – "HH:MM" range; end ≤ start runs past midnight. */
    public static function span(?string $start, ?string $end): ?array
    {
        $s = self::toMinutes($start);
        $e = self::toMinutes($end);
        if ($s === null || $e === null) {
            return null;
        }

        return [$s, $e > $s ? $e - $s : $e + self::DAY - $s];
    }

    /**
     * Open / close time from a form: all_day (checkbox), open_time and close_time ("HH:MM").
     * The hour count is worked out from them. $canAllDay: the plan allows 24 hours.
     *
     * @return array{hour: int, open_time: ?string}|array{error: string, field: string}
     */
    public static function fromInput(array $input, bool $canAllDay): array
    {
        if (filter_var($input['all_day'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            if (!$canAllDay) {
                return ['error' => 'Your plan doesn\'t include a 24 hour library. Set the open and close time.', 'field' => 'open_time'];
            }

            return ['hour' => 24, 'open_time' => null];
        }

        $open = self::toMinutes($input['open_time'] ?? null);
        $close = self::toMinutes($input['close_time'] ?? null);
        if ($open === null) {
            return ['error' => 'Choose when the library opens.', 'field' => 'open_time'];
        }
        if ($close === null) {
            return ['error' => 'Choose when the library closes.', 'field' => 'close_time'];
        }
        if ($open === $close) {
            return ['error' => $canAllDay ? 'Open and close can\'t be the same time. Tick "Open 24 hours" for a library that never closes.' : 'Open and close can\'t be the same time.', 'field' => 'close_time'];
        }

        $minutes = $close > $open ? $close - $open : $close + self::DAY - $open;
        if ($minutes % 60 !== 0) {
            return ['error' => 'Keep the library open for full hours, e.g. 6:30 AM – 10:30 PM.', 'field' => 'close_time'];
        }
        $hour = intdiv($minutes, 60);
        if ($hour < self::MIN_HOURS) {
            return ['error' => 'The library must be open at least ' . self::MIN_HOURS . ' hours a day (now ' . $hour . ' hrs).', 'field' => 'close_time'];
        }

        return ['hour' => $hour, 'open_time' => self::fromMinutes($open)];
    }

    /**
     * Why the branch's shifts don't fit new open hours, or null when they all do. Checks the
     * shifts saved on the Shifts page and the timings learners are booked on now.
     */
    public function conflict(int $branchId, int $hour, ?string $openTime): ?string
    {
        if ($hour >= 24) {
            return null;
        }

        $window = self::windowOf($hour, $openTime);
        $outside = [];
        $tooLong = [];

        foreach ($this->shiftsToFit($branchId) as $shift) {
            if ($shift->isAnyTime()) {
                // takes the library hours: only its hours per day have to fit
                if ($shift->booking_mode !== PlanType::MODE_FREE && (float) $shift->slot_hours > $hour) {
                    $tooLong[] = $shift->name . ' (' . self::hours((float) $shift->slot_hours) . ' hrs)';
                }
                continue;
            }
            $span = self::span($shift->start_time, $shift->end_time);
            if (!$span) {
                continue;
            }
            $fits = $window ? self::fits($window, $span[0], $span[1]) : $span[1] <= $hour * 60;
            if (!$fits) {
                $outside[] = $shift->name . ' (' . self::timeLabel($shift->start_time) . ' – ' . self::timeLabel($shift->end_time) . ')';
            }
        }

        if ($outside) {
            return ($window ? 'These shifts run outside ' . self::windowLabel($window) : 'These shifts are longer than ' . $hour . ' hrs')
                . ': ' . self::listNames($outside) . '. Change them on the Shifts page first, or keep the library open around them.';
        }
        if ($tooLong) {
            return 'These shifts need more than ' . $hour . ' hrs a day: ' . self::listNames($tooLong) . '. Lower their hours on the Shifts page first.';
        }

        return null;
    }

    /**
     * Open time that fits every shift of a branch open $hour hours a day: the start of the
     * earliest shift of the tightest window. Null when there are no timed shifts or they
     * can't fit in $hour hours.
     */
    public function guessOpenTime(int $branchId, int $hour): ?string
    {
        if ($hour <= 0 || $hour >= 24) {
            return null;
        }

        $spans = $this->shiftsToFit($branchId)
            ->reject(fn (PlanType $s) => $s->isAnyTime())
            ->map(fn (PlanType $s) => self::span($s->start_time, $s->end_time))
            ->filter()
            ->values();
        if ($spans->isEmpty()) {
            return null;
        }

        $best = null;
        foreach ($spans->pluck(0)->unique()->sort() as $open) {
            $window = [$open, $open + $hour * 60];
            $end = $open;
            foreach ($spans as [$s, $len]) {
                if (!self::fits($window, $s, $len)) {
                    continue 2;
                }
                $end = max($end, ($s >= $open ? $s : $s + self::DAY) + $len);
            }
            if ($best === null || $end - $open < $best[1]) {
                $best = [$open, $end - $open];
            }
        }

        return $best ? self::fromMinutes($best[0]) : null;
    }

    /**
     * The shifts of a branch that must fit the library hours: every shift on the Shifts page,
     * and the hidden timing rows of advanced shifts that current learners are booked on.
     *
     * @return Collection<int, PlanType>
     */
    private function shiftsToFit(int $branchId): Collection
    {
        $all = PlanType::withoutGlobalScopes()->where('branch_id', $branchId)->whereNull('deleted_at')
            ->get(['id', 'name', 'start_time', 'end_time', 'slot_hours', 'booking_mode', 'parent_id', 'day_type_id']);

        $children = $all->whereNotNull('parent_id');
        if ($children->isNotEmpty()) {
            $booked = DB::table('learner_detail')->where('branch_id', $branchId)->where('status', 1)->whereNull('deleted_at')
                ->whereIn('plan_type_id', $children->pluck('id'))->pluck('plan_type_id')
                ->merge(DB::table('learner_detail_slots')
                    ->join('learner_detail', 'learner_detail.id', '=', 'learner_detail_slots.learner_detail_id')
                    ->where('learner_detail_slots.branch_id', $branchId)->whereNull('learner_detail_slots.deleted_at')
                    ->where('learner_detail.status', 1)->whereIn('learner_detail_slots.plan_type_id', $children->pluck('id'))
                    ->pluck('learner_detail_slots.plan_type_id'))
                ->flip();
            $children = $children->filter(fn ($c) => isset($booked[$c->id]));
        }

        return $all->whereNull('parent_id')->merge($children)->values();
    }

    private static function listNames(array $names): string
    {
        $more = count($names) - 3;

        return implode(', ', array_slice($names, 0, 3)) . ($more > 0 ? ' and ' . $more . ' more' : '');
    }

    private static function hours(float $h): string
    {
        return rtrim(rtrim(number_format($h, 2, '.', ''), '0'), '.');
    }

    public static function toMinutes(?string $time): ?int
    {
        if ($time === null || !preg_match('/^(\d{1,2}):(\d{2})/', trim($time), $m)) {
            return null;
        }
        $h = (int) $m[1];
        $i = (int) $m[2];
        if ($h > 24 || $i > 59 || ($h === 24 && $i > 0)) {
            return null;
        }

        return ($h * 60 + $i) % self::DAY;
    }

    public static function fromMinutes(int $minutes): string
    {
        $minutes %= self::DAY;

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    public static function timeLabel(?string $time): string
    {
        try {
            $c = Carbon::parse($time);

            return $c->format('i') === '00' ? $c->format('g A') : $c->format('g:i A');
        } catch (\Throwable $e) {
            return (string) $time;
        }
    }
}
