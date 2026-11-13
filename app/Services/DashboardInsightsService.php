<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Read-only data for the owner dashboard (dashboard/admin.blade.php):
 * live attendance, collections, fee series, highlights and member KPIs.
 * Every query is scoped explicitly by branch so it can also be served as JSON.
 */
class DashboardInsightsService
{
    /** Payment modes that are not real money received. */
    private const NON_MONEY_MODES = ['PAYLATER'];

    /*
    |--------------------------------------------------------------------------
    | Live attendance (today)
    |--------------------------------------------------------------------------
    */

    public function liveAttendance(int $branchId, ?Carbon $now = null): array
    {
        $now = $now ?: now();
        $today = $now->toDateString();
        $seatLabel = $this->seatLabeler($branchId);

        // Members who hold a plan today (one row per learner, earliest shift first): also the ones
        // whose plan ended but are still in its grace (extension) days, and the ones about to expire
        $graceFrom = $now->copy()->startOfDay()->subDays(max(0, (int) getExtendDays($branchId)))->toDateString();
        $rows = DB::table('learner_detail as ld')
            ->join('learners as l', 'l.id', '=', 'ld.learner_id')
            ->leftJoin('plan_types as pt', 'pt.id', '=', 'ld.plan_type_id')
            ->where('ld.branch_id', $branchId)
            ->where('ld.status', 1)
            ->whereNull('ld.deleted_at')
            ->whereNull('l.deleted_at')
            ->where('l.status', 1)
            ->where('ld.plan_start_date', '<=', $today)
            ->where(function ($q) use ($graceFrom) {
                $q->where('ld.plan_end_date', '>=', $graceFrom)->orWhere('l.no_expiry', 1);
            })
            ->orderBy('pt.start_time')
            ->select('l.id', 'l.name', 'l.profile_picture', 'l.mobile', 'l.no_expiry', 'ld.seat_no', 'ld.plan_type_id', 'ld.plan_end_date', 'pt.name as shift', 'pt.start_time', 'pt.end_time')
            ->get()
            ->unique('id')
            ->values();

        $ids = $rows->pluck('id')->all();

        $punches = $ids ? DB::table('learner_attendance_logs')
            ->where('branch_id', $branchId)
            ->whereIn('learner_id', $ids)
            ->whereDate('punch_datetime', $today)
            ->orderBy('punch_datetime')
            ->get(['learner_id', 'punch_datetime'])
            ->groupBy('learner_id') : collect();

        // Older records may only exist in `attendances` (no punch log).
        $legacy = $ids ? DB::table('attendances')
            ->where('branch_id', $branchId)
            ->whereIn('learner_id', $ids)
            ->whereDate('date', $today)
            ->where('attendance', 1)
            ->get(['learner_id', 'in_time', 'out_time'])
            ->keyBy('learner_id') : collect();

        // learners whose fee is overdue (a pending amount past its due date, or with no due date)
        $overdue = $ids ? DB::table('learner_transactions')
            ->whereIn('learner_id', $ids)
            ->whereNull('deleted_at')
            ->where('pending_amount', '>', 0)
            ->where(fn ($q) => $q->whereNull('due_date')->orWhere('due_date', '<', $today))
            ->pluck('learner_id')->flip() : collect();

        $people = [];
        $shifts = [];
        foreach ($rows as $r) {
            $times = collect($punches->get($r->id, []))->map(fn ($p) => Carbon::parse($p->punch_datetime)->timestamp)->values()->all();
            if (!$times && ($a = $legacy->get($r->id)) && $a->in_time) {
                $times[] = Carbon::parse($a->in_time)->timestamp;
                if ($a->out_time && $a->out_time !== $a->in_time) {
                    $times[] = Carbon::parse($a->out_time)->timestamp;
                }
            }

            $n = count($times);
            $state = $n === 0 ? 'late' : ($n % 2 === 1 ? 'in' : 'out');

            [$start, $end] = $this->shiftWindow($now, $r->start_time, $r->end_time);
            $shiftKey = (string) ($r->plan_type_id ?? 0);
            $shifts[$shiftKey] = [
                'key' => $shiftKey,
                'name' => $r->shift ?: 'General',
                'time' => $this->timeRange($r->start_time, $r->end_time),
                'start' => $start ? $start->timestamp : null,
                'end' => $end ? $end->timestamp : null,
            ];

            // plan status shown on the card: in grace (extension) or ending within 5 days
            $daysLeft = (!$r->no_expiry && $r->plan_end_date) ? $now->copy()->startOfDay()->diffInDays(Carbon::parse($r->plan_end_date)->startOfDay(), false) : null;
            $plan = $daysLeft === null ? null : ($daysLeft < 0 ? ['tone' => 'ext', 'label' => 'In extension'] : ($daysLeft <= 5 ? ['tone' => 'exp', 'label' => $daysLeft === 0 ? 'Ends today' : 'Ends in ' . $daysLeft . ($daysLeft === 1 ? ' day' : ' days')] : null));

            $seat = $seatLabel($r->seat_no);
            $people[] = [
                'id' => (int) $r->id,
                'name' => (string) $r->name,
                'initials' => $this->initials($r->name),
                'img' => $r->profile_picture ? asset($r->profile_picture) : '',
                'seat' => $seat['label'],
                'floor' => $seat['floor'],
                'shift' => $shiftKey,
                'shiftEnd' => $end ? $end->timestamp : null,
                'state' => $state,
                'punches' => $times,
                'url' => route('attendance.summary', $r->id),
                'plan' => $plan,
                // blinking dot on the card: fee overdue, or the plan has expired (in its grace days)
                'alert' => isset($overdue[$r->id]) ? 'due' : (($daysLeft !== null && $daysLeft < 0) ? 'ext' : null),
            ];
        }

        $inside = array_values(array_filter($people, fn ($p) => $p['state'] === 'in'));
        $floors = collect($inside)->groupBy('floor')->map(fn ($g, $f) => ['name' => $f ?: 'General', 'n' => $g->count()])->values()->all();

        $last = null;
        foreach ($people as $p) {
            foreach ($p['punches'] as $i => $t) {
                if (!$last || $t > $last['t']) {
                    $last = ['t' => $t, 'name' => $p['name'], 'kind' => $i % 2 === 0 ? 'IN' : 'OUT'];
                }
            }
        }

        return [
            'now' => $now->timestamp,
            'people' => $people,
            'shifts' => array_values($shifts),
            'floors' => $floors,
            'counts' => [
                'all' => count($people),
                'in' => count($inside),
                'out' => count(array_filter($people, fn ($p) => $p['state'] === 'out')),
                'late' => count(array_filter($people, fn ($p) => $p['state'] === 'late')),
                'punches' => array_sum(array_map(fn ($p) => count($p['punches']), $people)),
            ],
            'last' => $last,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Collections (money actually received)
    |--------------------------------------------------------------------------
    */

    public function collections(int $branchId, int $days = 30): array
    {
        $today = Carbon::today();
        $from = $today->copy()->subDays($days - 1);
        $seatLabel = $this->seatLabeler($branchId);

        $rows = DB::table('learner_transaction_activity as a')
            ->leftJoin('learners as l', 'l.id', '=', 'a.learner_id')
            ->where('a.branch_id', $branchId)
            ->whereNull('a.deleted_at')
            ->where('a.dr_cr', 'Cr')
            ->where('a.amount', '>', 0)
            ->whereNotIn('a.payment_mode', self::NON_MONEY_MODES)
            ->whereBetween('a.date', [$from->toDateString(), $today->toDateString()])
            ->orderByDesc('a.date')
            ->orderByDesc('a.created_at')
            ->select('a.id', 'a.date', 'a.created_at', 'a.amount', 'a.payment_mode', 'a.payment_type', 'a.particular', 'a.transaction_id', 'l.name', 'l.profile_picture', 'l.seat_no')
            ->get();

        $items = $rows->map(function ($r) use ($today, $seatLabel) {
            $d = Carbon::parse($r->date);
            return [
                'd' => $today->diffInDays($d),
                'date' => $d->toDateString(),
                'time' => $r->created_at ? Carbon::parse($r->created_at)->format('g:i A') : '',
                'amt' => (float) $r->amount,
                'mode' => $this->modeGroup($r->payment_mode),
                'modeRaw' => ucwords(strtolower((string) $r->payment_mode)),
                'type' => ucwords(strtolower(str_replace('_', ' ', (string) $r->payment_type))),
                'ref' => $r->transaction_id ? '#' . $r->transaction_id : '#' . $r->id,
                'name' => (string) ($r->name ?? 'Walk-in'),
                'initials' => $this->initials($r->name ?? 'W'),
                'img' => $r->profile_picture ? asset($r->profile_picture) : '',
                'seat' => $seatLabel($r->seat_no)['label'],
            ];
        })->values();

        $sum = fn ($c) => (float) $c->sum('amt');
        $todayItems = $items->where('d', 0);

        return [
            'items' => $items->all(),
            'today' => ['total' => $sum($todayItems), 'count' => $todayItems->count()],
            'yesterday' => ['total' => $sum($items->where('d', 1)), 'count' => $items->where('d', 1)->count()],
            'period' => ['total' => $sum($items), 'count' => $items->count(), 'from' => $from->format('j M'), 'to' => $today->format('j M'), 'days' => $days],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Fees collected — daily (7), weekly (4), monthly (6)
    |--------------------------------------------------------------------------
    */

    public function feesSeries(int $branchId): array
    {
        $today = Carbon::today();
        $from = $today->copy()->subMonths(5)->startOfMonth();

        $byDay = DB::table('learner_transaction_activity')
            ->where('branch_id', $branchId)
            ->whereNull('deleted_at')
            ->where('dr_cr', 'Cr')
            ->where('amount', '>', 0)
            ->whereNotIn('payment_mode', self::NON_MONEY_MODES)
            ->whereBetween('date', [$from->toDateString(), $today->toDateString()])
            ->groupBy('date')
            ->selectRaw('date, SUM(amount) as total')
            ->pluck('total', 'date')
            ->map(fn ($v) => (float) $v);

        $range = function (Carbon $a, Carbon $b) use ($byDay) {
            $t = 0;
            foreach ($byDay as $date => $v) {
                if ($date >= $a->toDateString() && $date <= $b->toDateString()) {
                    $t += $v;
                }
            }
            return $t;
        };

        $daily = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $today->copy()->subDays($i);
            $daily[] = [$i === 0 ? 'Today' : $d->format('D'), (float) ($byDay[$d->toDateString()] ?? 0)];
        }

        $weekly = [];
        for ($w = 3; $w >= 0; $w--) {
            $b = $today->copy()->subDays($w * 7);
            $a = $b->copy()->subDays(6);
            $weekly[] = [$w === 0 ? 'This wk' : $a->format('j M'), $range($a, $b)];
        }

        $monthly = [];
        for ($m = 5; $m >= 0; $m--) {
            $a = $today->copy()->subMonths($m)->startOfMonth();
            $b = $m === 0 ? $today->copy() : $a->copy()->endOfMonth();
            $monthly[] = [$m === 0 ? 'This mo' : $a->format('M'), $range($a, $b)];
        }

        return ['Daily' => $daily, 'Weekly' => $weekly, 'Monthly' => $monthly];
    }

    /*
    |--------------------------------------------------------------------------
    | Highlights — festivals, birthdays, founder's day
    |--------------------------------------------------------------------------
    */

    public function highlights(int $branchId): array
    {
        $today = Carbon::today();
        $seatLabel = $this->seatLabeler($branchId);

        $birthdays = DB::table('learners')
            ->where('branch_id', $branchId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->whereNotNull('dob')
            ->whereMonth('dob', $today->month)
            ->whereDay('dob', $today->day)
            ->get(['id', 'name', 'profile_picture', 'seat_no', 'mobile'])
            ->map(fn ($l) => [
                'name' => (string) $l->name,
                'first' => explode(' ', trim((string) $l->name))[0],
                'initials' => $this->initials($l->name),
                'img' => $l->profile_picture ? asset($l->profile_picture) : '',
                'seat' => $seatLabel($l->seat_no)['label'],
            ])->all();

        $festivals = DB::table('india_festivals')
            ->whereBetween('festival_date', [$today->toDateString(), $today->copy()->addDays(45)->toDateString()])
            ->orderBy('festival_date')
            ->limit(3)
            ->get(['festival_name', 'festival_date', 'description'])
            ->map(function ($f) use ($today) {
                $d = Carbon::parse($f->festival_date);
                $in = $today->diffInDays($d);
                return [
                    'name' => (string) $f->festival_name,
                    'desc' => (string) ($f->description ?? ''),
                    'in' => $in,
                    'when' => ($in === 0 ? 'Today' : ($in === 1 ? 'Tomorrow' : 'In ' . $in . ' days')) . ' · ' . $d->format('D, j M'),
                ];
            })->all();

        $founder = null;
        $fd = DB::table('branches')->where('id', $branchId)->value('founder_day');
        if ($fd) {
            $f = Carbon::parse($fd);
            if ($f->month === $today->month && $f->day === $today->day) {
                $founder = ['years' => max(1, $today->year - $f->year)];
            }
        }

        return ['birthdays' => $birthdays, 'festivals' => $festivals, 'founder' => $founder];
    }

    /*
    |--------------------------------------------------------------------------
    | Member KPIs
    |--------------------------------------------------------------------------
    */

    public function memberKpis(int $branchId): array
    {
        $today = Carbon::today()->toDateString();
        $monthStart = Carbon::today()->startOfMonth()->toDateString();

        $active = DB::table('learner_detail as ld')
            ->join('learners as l', 'l.id', '=', 'ld.learner_id')
            ->where('ld.branch_id', $branchId)
            ->where('ld.status', 1)
            ->whereNull('ld.deleted_at')
            ->whereNull('l.deleted_at')
            ->where('ld.plan_start_date', '<=', $today)
            ->where(fn ($q) => $q->where('ld.plan_end_date', '>=', $today)->orWhere('l.no_expiry', 1))
            ->distinct()
            ->count('ld.learner_id');

        $joined = DB::table('learners')
            ->where('branch_id', $branchId)
            ->whereNull('deleted_at')
            ->where('created_at', '>=', $monthStart)
            ->count();

        return ['active' => $active, 'joined' => $joined];
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function seatLabeler(int $branchId): \Closure
    {
        $map = collect(generateSeatNumbers2($branchId))->keyBy('main');
        return function ($no) use ($map) {
            if ($no === null || $no === '' || (int) $no === 0) {
                return ['label' => 'General', 'floor' => 'General'];
            }
            $s = $map->get((int) $no);
            if ($s && !empty($s['floor']) && !empty($s['floor_name'])) {
                return ['label' => 'Seat ' . $s['floor'] . ' (' . $s['floor_name'] . ')', 'floor' => $s['floor_name']];
            }
            return ['label' => 'Seat ' . $no, 'floor' => 'Main'];
        };
    }

    /** Today's [start, end] for a shift; end is null for overnight / round-the-clock shifts. */
    private function shiftWindow(Carbon $now, ?string $start, ?string $end): array
    {
        if (!$start || !$end) {
            return [null, null];
        }
        try {
            $s = $now->copy()->setTimeFromTimeString($start);
            $e = $now->copy()->setTimeFromTimeString($end);
        } catch (\Throwable $ex) {
            return [null, null];
        }
        return $e->gt($s) ? [$s, $e] : [$s, null];
    }

    private function timeRange(?string $start, ?string $end): string
    {
        $f = function ($t) {
            if (!$t) return '';
            try { $c = Carbon::parse($t); return $c->format('i') === '00' ? $c->format('g A') : $c->format('g:i A'); } catch (\Throwable $e) { return (string) $t; }
        };
        if (!$start) return '';
        return ($end && $f($start) === $f($end)) ? '24 hours from ' . $f($start) : $f($start) . ' – ' . $f($end);
    }

    private function modeGroup(?string $mode): string
    {
        $m = strtoupper((string) $mode);
        if ($m === 'CASH' || $m === 'OFFLINE') return 'Cash';
        if (in_array($m, ['UPI', 'PHONE PAY', 'GOOGLE PAY', 'BHARAT PAY', 'PAYTM', 'AMAZON PAY', 'WHATSAPP PAY'], true)) return 'UPI';
        if (in_array($m, ['ONLINE', 'ONLINE BANKING', 'BANK TRANSFER'], true)) return 'Online';
        return 'Other';
    }

    private function initials(?string $name): string
    {
        $parts = array_filter(explode(' ', trim((string) $name)));
        $out = '';
        foreach (array_slice($parts, 0, 2) as $p) {
            $out .= mb_strtoupper(mb_substr($p, 0, 1));
        }
        return $out ?: '?';
    }
}
