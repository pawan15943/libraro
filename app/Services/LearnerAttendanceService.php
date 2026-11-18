<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\PlanType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Attendance numbers for the learner portal (month calendar + single day).
 * Same rules as the library panel's attendance pages (AttendanceController::summary / logsPage):
 * present = in and out punched, half day = no out punch (counted till shift end), absent = no punch;
 * overstay = time beyond the shift length. Read-only; the library pages are not touched.
 */
class LearnerAttendanceService
{
    private function joiningDate(Learner $learner): Carbon
    {
        $raw = LearnerDetail::withoutGlobalScopes()->withTrashed()->where('learner_id', $learner->id)->min('plan_start_date')
            ?: LearnerDetail::withoutGlobalScopes()->withTrashed()->where('learner_id', $learner->id)->min('join_date')
            ?: $learner->created_at;
        $d = $raw ? Carbon::parse($raw)->startOfDay() : Carbon::today();

        return $d->gt(Carbon::today()) ? Carbon::today() : $d;
    }

    private function details(Learner $learner)
    {
        return LearnerDetail::withoutGlobalScopes()->withTrashed()
            ->with(['plan' => fn ($q) => $q->withoutGlobalScopes(), 'planType' => fn ($q) => $q->withoutGlobalScopes()->withTrashed()])
            ->where('learner_id', $learner->id)->orderByDesc('id')->get();
    }

    /** Allowed minutes + text for the shift of a plan type / detail. */
    private function shift(?LearnerDetail $detail, string $unit = 'h'): array
    {
        $type = $detail?->planType;
        $slot = ! empty($type?->slot_hours) ? (float) $type->slot_hours : (! empty($detail?->hour) ? (float) $detail->hour : 0);
        $minutes = 0;
        $text = '';
        if ($slot > 0) {
            $minutes = (int) round($slot * 60);
            $text = rtrim(rtrim(number_format($slot, 1), '0'), '.') . ' Hrs';
        } elseif (! empty($type?->start_time) && ! empty($type?->end_time)) {
            $s = Carbon::parse($type->start_time);
            $e = Carbon::parse($type->end_time);
            if ($e->lt($s)) {
                $e->addDay();
            }
            $minutes = $s->diffInMinutes($e);
            $text = trim((intdiv($minutes, 60) ? intdiv($minutes, 60) . 'h ' : '') . ($minutes % 60 ? ($minutes % 60) . 'm' : ''));
        }

        return [$minutes, $text];
    }

    private function overstay(int $spent, int $allowed): array
    {
        if ($allowed > 0 && $allowed < 1440 && $spent > $allowed) {
            $m = $spent - $allowed;
            $h = intdiv($m, 60);
            $r = $m % 60;

            return [$m, $h && $r ? "{$h}h {$r}m" : ($h ? "{$h}h" : "{$r}m")];
        }

        return [0, ''];
    }

    /** Header card data shared by both pages. */
    public function member(Learner $learner, ?LearnerDetail $detail = null): array
    {
        $detail ??= $this->details($learner)->firstWhere('status', 1) ?? $this->details($learner)->first();
        $branch = $learner->branch_id ? DB::table('branches')->where('id', $learner->branch_id)->value('name') : null;
        $branch = $branch ?: ($learner->library_id ? DB::table('libraries')->where('id', $learner->library_id)->value('library_name') : null) ?: 'Library';
        $seat = $detail?->seat_no ?: $learner->seat_no;
        $type = $detail?->planType?->name;
        $photo = ($learner->profile_picture ?? null) && file_exists(public_path($learner->profile_picture)) ? asset($learner->profile_picture) : null;
        $w = preg_split('/\s+/', trim((string) $learner->name)) ?: [];
        $initials = strtoupper(count($w) > 1 ? mb_substr($w[0], 0, 1) . mb_substr($w[1], 0, 1) : mb_substr($w[0] ?? 'L', 0, 2));

        return [
            'name' => $learner->name, 'no' => $learner->learner_no, 'initials' => $initials ?: 'L', 'photo' => $photo, 'branch' => $branch,
            'tag' => $seat ? 'Seat ' . $seat . ($type ? ' · ' . $type : '') : ($type ?: 'Member'),
        ];
    }

    public function month(Learner $learner, Carbon $requested): array
    {
        $today = Carbon::today();
        $joining = $this->joiningDate($learner);
        $min = $joining->copy()->startOfMonth();
        $max = $today->copy()->startOfMonth();
        $month = $requested->copy()->startOfMonth();
        $month = $month->lt($min) ? $min->copy() : ($month->gt($max) ? $max->copy() : $month);

        $details = $this->details($learner);
        $active = $details->firstWhere('status', 1) ?? $details->first();
        $rows = Attendance::withoutGlobalScopes()->where('learner_id', $learner->id)
            ->whereYear('date', $month->year)->whereMonth('date', $month->month)->get()
            ->keyBy(fn ($r) => Carbon::parse($r->date)->format('Y-m-d'));

        $startWeekday = $month->copy()->startOfMonth()->dayOfWeek;
        $cells = (int) (ceil(($startWeekday + $month->daysInMonth) / 7) * 7);
        $calendar = [];
        $logs = [];
        $present = $half = $absent = $totalMinutes = 0;

        for ($i = 0; $i < $cells; $i++) {
            $d = $month->copy()->startOfMonth()->addDays($i - $startWeekday);
            if ($d->month !== $month->month) {
                $calendar[] = ['day' => $d->day, 'date' => null, 'in_month' => false, 'in_range' => false, 'status' => 'none', 'hours' => null];
                continue;
            }
            $ds = $d->format('Y-m-d');
            $inRange = $d->between($joining, $today);
            $status = 'none';
            $hours = null;
            $in = $out = null;
            $implied = $inside = false;
            $ot = [0, ''];

            $detail = $details->first(fn ($ld) => ! empty($ld->plan_start_date) && ! empty($ld->plan_end_date)
                && $ds >= Carbon::parse($ld->plan_start_date)->format('Y-m-d') && $ds <= Carbon::parse($ld->plan_end_date)->format('Y-m-d')) ?? $active;
            [$allowed] = $this->shift($detail);
            $type = $detail?->planType;
            $endTime = $type?->end_time ?: $active?->planType?->end_time;

            if ($inRange) {
                $row = $rows->get($ds);
                $minutes = 0;
                if ($row && $row->in_time) {
                    $in = Carbon::parse($row->in_time);
                    if (! empty($row->out_time) && ! Carbon::parse($row->out_time)->eq($in)) {
                        $out = Carbon::parse($row->out_time);
                        if ($out->lt($in)) {
                            $out->addDay();
                        }
                        $status = 'full';
                        $minutes = max(0, $in->diffInMinutes($out));
                    } else {
                        $status = 'half';
                        if ($d->isToday()) {
                            $inside = true;
                            $minutes = max(0, $in->diffInMinutes(Carbon::now()));
                        } else {
                            $implied = true;
                            if ($type?->booking_mode === PlanType::MODE_FLEXIBLE && $allowed > 0) {
                                $minutes = $allowed;
                            } elseif ($endTime) {
                                $out = Carbon::parse($ds . ' ' . Carbon::parse($endTime)->format('H:i:s'));
                                if ($out->lt($in)) {
                                    $out->addDay();
                                }
                                $minutes = max(0, $in->diffInMinutes($out));
                            } else {
                                $minutes = $allowed;
                            }
                        }
                    }
                    if ($minutes > 0) {
                        $hours = round($minutes / 60, 1);
                        $totalMinutes += $minutes;
                        $ot = $this->overstay($minutes, $allowed);
                    }
                } else {
                    $status = 'absent';
                }
                match ($status) { 'full' => $present++, 'half' => $half++, 'absent' => $absent++, default => null };
            }

            $calendar[] = ['day' => $d->day, 'date' => $ds, 'in_month' => true, 'in_range' => $inRange, 'status' => $status, 'hours' => $hours];
            if ($inRange) {
                $logs[] = [
                    'date' => $ds, 'day_name' => $d->format('D'), 'day_num' => $d->format('d'), 'status' => $status,
                    'check_in' => $in?->format('h:i A'), 'check_out' => $out?->format('h:i A'), 'implied_out' => $implied, 'is_inside' => $inside,
                    'hours' => $hours, 'has_overtime' => $ot[0] > 0, 'overtime_text' => $ot[1],
                ];
            }
        }
        usort($logs, fn ($a, $b) => strcmp($b['date'], $a['date']));

        return [
            'month' => $month, 'can_prev' => $month->gt($min), 'can_next' => $month->lt($max),
            'calendar' => $calendar, 'logs' => $logs, 'present' => $present, 'half' => $half, 'absent' => $absent,
            'hours_label' => sprintf('%d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60),
            'today' => $today->format('Y-m-d'), 'member' => $this->member($learner, $active),
        ];
    }

    /**
     * Hours spent in the library (from attendance punches) for the dashboard:
     * last 7 days as bars, this month's total, days present and average per visit.
     * Uses month() so the numbers match the attendance page exactly.
     */
    public function studyHours(Learner $learner): array
    {
        $today = Carbon::today();
        $cur = $this->month($learner, $today);
        $byDate = collect($cur['calendar'])->filter(fn ($c) => $c['date'])->keyBy('date');
        if ($today->day < 7) {
            $prev = $this->month($learner, $today->copy()->subMonthNoOverflow());
            if ($prev['month']->lt($cur['month'])) {
                $byDate = collect($prev['calendar'])->filter(fn ($c) => $c['date'])->keyBy('date')->merge($byDate);
            }
        }

        $week = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $today->copy()->subDays($i);
            $c = $byDate->get($d->format('Y-m-d'));
            $week[] = [
                'label' => $d->format('D'),
                'date' => $d->format('Y-m-d'),
                'hours' => (float) ($c['hours'] ?? 0),
                'status' => $c['status'] ?? 'none',
                'today' => $i === 0,
            ];
        }

        [$h, $m] = array_map('intval', explode(':', $cur['hours_label']));
        $monthMinutes = $h * 60 + $m;
        $visits = $cur['present'] + $cur['half'];
        $weekHours = array_sum(array_column($week, 'hours'));

        return [
            'week' => $week,
            'week_hours' => round($weekHours, 1),
            'week_max' => max(1, max(array_column($week, 'hours'))),
            'month_label' => $cur['month']->format('F'),
            'month_hours' => round($monthMinutes / 60, 1),
            'present' => $cur['present'],
            'half' => $cur['half'],
            'absent' => $cur['absent'],
            'visits' => $visits,
            'avg_hours' => $visits ? round($monthMinutes / 60 / $visits, 1) : 0,
        ];
    }

    public function day(Learner $learner, Carbon $date): array
    {
        $today = Carbon::today();
        $joining = $this->joiningDate($learner);
        $ds = $date->format('Y-m-d');

        $row = Attendance::withoutGlobalScopes()->where('learner_id', $learner->id)->whereDate('date', $ds)->first();
        $logRows = DB::table('learner_attendance_logs')->where('learner_id', $learner->id)->whereDate('punch_datetime', $ds)->orderBy('punch_datetime')->get();

        $punches = [];
        $mk = fn ($type, $dt, $src) => [
            'type' => $type, 'title' => $type === 'IN' ? 'Punch In' : 'Punch Out', 'time' => Carbon::parse($dt)->format('h:i A'),
            'source' => (! empty($src) && strtoupper($src) !== 'MANUAL') ? $src : 'Main Gate', 'raw_datetime' => Carbon::parse($dt)->format('Y-m-d H:i:s'), 'session_duration' => null,
        ];
        if ($logRows->isNotEmpty()) {
            foreach ($logRows as $i => $r) {
                $punches[] = $mk($i % 2 === 0 ? 'IN' : 'OUT', $r->punch_datetime, $r->source ?? null);
            }
        } elseif ($row) {
            if (! empty($row->in_time) && (int) ($row->attendance ?? 1) === 1) {
                $punches[] = $mk('IN', $row->in_time, null);
            }
            if (! empty($row->out_time)) {
                $punches[] = $mk('OUT', $row->out_time, null);
            }
        }
        for ($i = 1; $i < count($punches); $i++) {
            if ($punches[$i]['type'] === 'OUT' && $punches[$i - 1]['type'] === 'IN') {
                $a = Carbon::parse($punches[$i - 1]['raw_datetime']);
                $b = Carbon::parse($punches[$i]['raw_datetime']);
                if ($b->lt($a)) {
                    $b->addDay();
                }
                $m = $a->diffInMinutes($b);
                $punches[$i]['session_duration'] = (intdiv($m, 60) ? intdiv($m, 60) . 'h ' : '') . ($m % 60 ? ($m % 60) . 'm' : (intdiv($m, 60) ? '' : '0m'));
            }
        }

        $firstIn = collect($punches)->firstWhere('type', 'IN');
        $lastOut = collect($punches)->where('type', 'OUT')->last();
        $hasIn = $firstIn !== null;
        $hasOut = $lastOut !== null;
        $isToday = $date->isToday();
        $inside = $hasIn && ! $hasOut && $isToday;

        $durationText = '0h 0m';
        $durationSub = 'Not attended';
        $spent = null;
        if ($hasIn) {
            $a = Carbon::parse($firstIn['raw_datetime']);
            if ($hasOut) {
                $b = Carbon::parse($lastOut['raw_datetime']);
                if ($b->lt($a)) {
                    $b->addDay();
                }
                $spent = $a->diffInMinutes($b);
                $durationSub = 'Total duration';
            } elseif ($isToday) {
                $spent = max(0, $a->diffInMinutes(Carbon::now()));
                $durationSub = 'Till now';
            } else {
                $durationText = '—';
                $durationSub = 'Not checked out';
            }
            if ($spent !== null) {
                $durationText = intdiv($spent, 60) . 'h ' . ($spent % 60) . 'm';
            }
        }

        $detail = LearnerDetail::withoutGlobalScopes()->withTrashed()
            ->with(['plan' => fn ($q) => $q->withoutGlobalScopes(), 'planType' => fn ($q) => $q->withoutGlobalScopes()->withTrashed()])
            ->where('learner_id', $learner->id)->where('plan_start_date', '<=', $ds)->where('plan_end_date', '>=', $ds)->latest('id')->first()
            ?? $this->details($learner)->first();
        [$allowed, $shiftText] = $this->shift($detail);
        [$otMin, $otText] = $spent !== null ? $this->overstay($spent, $allowed) : [0, ''];

        return [
            'date' => $ds, 'date_obj' => $date, 'is_today' => $isToday, 'inside' => $inside, 'missing_out' => $hasIn && ! $hasOut && ! $isToday,
            'has_prev' => $date->copy()->subDay()->gte($joining), 'has_next' => $date->copy()->addDay()->lte($today),
            'prev' => $date->copy()->subDay()->format('Y-m-d'), 'next' => $date->copy()->addDay()->format('Y-m-d'),
            'min' => $joining->format('Y-m-d'), 'max' => $today->format('Y-m-d'),
            'has_in' => $hasIn, 'in_text' => $firstIn['time'] ?? '—', 'in_loc' => $firstIn['source'] ?? 'Main Gate',
            'has_out' => $hasOut, 'out_text' => $lastOut['time'] ?? '—', 'out_loc' => $lastOut['source'] ?? 'Main Gate',
            'duration' => $durationText, 'duration_sub' => $durationSub,
            'status' => $hasIn ? 'Present' : 'Absent', 'status_sub' => $hasIn ? ($hasOut ? 'Session ended' : ($isToday ? 'In library now' : 'No exit recorded')) : 'Not attended',
            'absent' => ! $hasIn, 'punches' => $punches,
            'has_overtime' => $otMin > 0, 'overtime_text' => $otText, 'shift_text' => $shiftText ?: '—', 'shift_name' => $detail?->planType?->name ?: 'Standard',
            'member' => $this->member($learner, $detail), 'joining' => $joining,
        ];
    }

    public function minDate(Learner $learner): Carbon
    {
        return $this->joiningDate($learner);
    }
}
