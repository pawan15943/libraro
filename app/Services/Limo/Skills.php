<?php

namespace App\Services\Limo;

use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Limo's data layer: every answer comes from a read-only query on the signed-in library's own data
 * (library and current branch are fixed here, never taken from the question). Returns plain arrays.
 */
class Skills
{
    public function __construct(public readonly int $lib, public readonly int $branch, public readonly bool $owner)
    {
    }

    // ---------- shared pieces ----------

    /** members with a current plan in this branch */
    private function active()
    {
        return DB::table('learner_detail as d')->join('learners as l', 'l.id', '=', 'd.learner_id')
            ->where('d.library_id', $this->lib)->where('d.branch_id', $this->branch)->where('d.status', 1)->where('l.status', 1)
            ->whereNull('d.deleted_at')->whereNull('l.deleted_at');
    }

    private function learners()
    {
        return DB::table('learners as l')->where('l.library_id', $this->lib)->where('l.branch_id', $this->branch)->whereNull('l.deleted_at');
    }

    public function totalSeats(): int
    {
        return (int) DB::table('hour')->where('branch_id', $this->branch)->whereNull('deleted_at')->value('seats');
    }

    public function libraryName(): string
    {
        return (string) (DB::table('branches')->where('id', $this->branch)->value('display_name')
            ?: DB::table('branches')->where('id', $this->branch)->value('name')
            ?: DB::table('libraries')->where('id', $this->lib)->value('library_name') ?: 'your library');
    }

    // ---------- overview ----------

    public function overview(): array
    {
        $today = Carbon::today();
        $seats = $this->seats();
        $out = [
            'seats' => $seats,
            'active' => (int) $this->active()->count(),
            'ending_7' => (int) $this->active()->whereBetween('d.plan_end_date', [$today->toDateString(), $today->copy()->addDays(7)->toDateString()])->count(),
            'new_today' => (int) $this->learners()->whereDate('l.created_at', $today)->count(),
            'present_today' => $this->presentCount($today),
        ];
        if ($this->owner) {
            $out['collection_today'] = $this->collection($today, $today)['total'];
            $out['dues_total'] = $this->duesTotal()['total'];
        }

        return $out;
    }

    // ---------- money (owner only) ----------

    public function collection(Carbon $from, Carbon $to): array
    {
        $q = DB::table('learner_transactions as lt')->where('lt.library_id', $this->lib)->where('lt.branch_id', $this->branch)->whereNull('lt.deleted_at')
            ->whereBetween('lt.paid_date', [$from->toDateString(), $to->toDateString()]);

        $days = (clone $q)->selectRaw('lt.paid_date as d, SUM(lt.paid_amount) as amt, COUNT(*) as n')->groupBy('lt.paid_date')->orderBy('lt.paid_date', 'desc')->limit(31)->get();

        return [
            'total' => (float) (clone $q)->sum('lt.paid_amount'),
            'payments' => (int) (clone $q)->where('lt.paid_amount', '>', 0)->count(),
            'days' => $days->map(fn ($r) => ['date' => $r->d, 'amount' => (float) $r->amt, 'n' => (int) $r->n])->all(),
        ];
    }

    public function duesTotal(): array
    {
        $s = app(DashboardService::class)->pendingPaymentSummary($this->branch);
        $learners = (int) DB::table('learner_transactions as lt')->join('learners as l', 'l.id', '=', 'lt.learner_id')
            ->where('lt.branch_id', $this->branch)->where('l.status', 1)->whereNull('l.deleted_at')->whereNull('lt.deleted_at')->where('lt.pending_amount', '>', 0)
            ->distinct()->count('lt.learner_id');

        return ['total' => (float) ($s['total_pending_amount'] ?? 0), 'learners' => $learners, 'raw' => $s];
    }

    public function duesList(int $limit = 10): array
    {
        $rows = DB::table('learner_transactions as lt')->join('learners as l', 'l.id', '=', 'lt.learner_id')
            ->leftJoin('learner_detail as d', 'd.id', '=', 'lt.learner_detail_id')
            ->where('lt.branch_id', $this->branch)->where('l.status', 1)->whereNull('l.deleted_at')->whereNull('lt.deleted_at')->where('lt.pending_amount', '>', 0)
            ->groupBy('l.id', 'l.name', 'l.learner_no')
            ->selectRaw('l.id, l.name, l.learner_no, SUM(lt.pending_amount) as due, MIN(lt.due_date) as due_date, MAX(d.seat_no) as seat')
            ->orderByDesc('due')->limit($limit)->get();

        return $rows->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'no' => $r->learner_no, 'due' => (float) $r->due, 'due_date' => $r->due_date, 'seat' => $r->seat])->all();
    }

    public function expenses(Carbon $from, Carbon $to): array
    {
        $rows = DB::table('monthly_expense as m')->leftJoin('expenses as e', 'e.id', '=', 'm.expense_id')
            ->where('m.library_id', $this->lib)->where('m.branch_id', $this->branch)->whereNull('m.deleted_at')
            ->whereRaw('(m.year * 12 + m.month) BETWEEN ? AND ?', [$from->year * 12 + $from->month, $to->year * 12 + $to->month])
            ->groupBy('e.name')->selectRaw('COALESCE(e.name, "Other") as name, SUM(m.amount) as amt')->orderByDesc('amt')->get();

        return ['total' => (float) $rows->sum('amt'), 'rows' => $rows->map(fn ($r) => ['name' => $r->name, 'amount' => (float) $r->amt])->all()];
    }

    // ---------- members ----------

    public function expiring(Carbon $from, Carbon $to, int $limit = 15): array
    {
        $q = $this->active()->whereBetween('d.plan_end_date', [$from->toDateString(), $to->toDateString()]);

        return $this->memberRows($q, 'd.plan_end_date', 'asc', $limit);
    }

    /** plan end date already passed, still not renewed */
    public function ended(int $limit = 15): array
    {
        $q = $this->active()->where('d.plan_end_date', '<', Carbon::today()->toDateString());

        return $this->memberRows($q, 'd.plan_end_date', 'desc', $limit);
    }

    private function memberRows($q, string $orderCol, string $dir, int $limit): array
    {
        $total = (clone $q)->count();
        $rows = (clone $q)->leftJoin('plan_types as t', 't.id', '=', 'd.plan_type_id')->orderBy($orderCol, $dir)->limit($limit)
            ->get(['l.id', 'l.name', 'l.learner_no', 'l.mobile', 'd.seat_no', 'd.plan_end_date', 't.name as shift']);

        return ['total' => (int) $total, 'rows' => $rows->map(fn ($r) => [
            'id' => $r->id, 'name' => $r->name, 'no' => $r->learner_no, 'seat' => $r->seat_no, 'end' => $r->plan_end_date, 'shift' => $r->shift, 'mobile' => $this->owner ? $r->mobile : null,
        ])->all()];
    }

    public function members(): array
    {
        return [
            'active' => (int) $this->active()->count(),
            'total' => (int) $this->learners()->count(),
            'inactive' => (int) $this->learners()->where('l.status', 0)->count(),
            'frozen' => (int) $this->learners()->where('l.status', 1)->where('l.frozen_status', 1)->count(),
        ];
    }

    public function frozenList(int $limit = 15): array
    {
        return $this->learners()->where('l.status', 1)->where('l.frozen_status', 1)->orderBy('l.name')->limit($limit)->get(['l.id', 'l.name', 'l.learner_no'])
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'no' => $r->learner_no])->all();
    }

    public function admissions(Carbon $from, Carbon $to, int $limit = 15): array
    {
        $q = $this->learners()->whereBetween('l.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        return ['total' => (int) (clone $q)->count(), 'rows' => (clone $q)->orderByDesc('l.created_at')->limit($limit)->get(['l.id', 'l.name', 'l.learner_no', 'l.created_at'])
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'no' => $r->learner_no, 'on' => Carbon::parse($r->created_at)->toDateString()])->all()];
    }

    public function birthdays(Carbon $day): array
    {
        return $this->active()->whereRaw('MONTH(l.dob) = ? AND DAY(l.dob) = ?', [$day->month, $day->day])->orderBy('l.name')->limit(20)
            ->get(['l.id', 'l.name', 'l.learner_no'])->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'no' => $r->learner_no])->all();
    }

    public function byExam(): array
    {
        return $this->active()->join('exams as e', 'e.id', '=', 'd.exam_id')->groupBy('e.name')->selectRaw('e.name as name, COUNT(DISTINCT l.id) as n')->orderByDesc('n')->get()
            ->map(fn ($r) => ['name' => $r->name, 'n' => (int) $r->n])->all();
    }

    // ---------- seats and shifts ----------

    public function seats(): array
    {
        $total = $this->totalSeats();
        $occupied = (int) $this->active()->whereNotNull('d.seat_no')->where('d.seat_no', '!=', '')->distinct()->count('d.seat_no');

        return ['total' => $total, 'occupied' => min($occupied, $total ?: $occupied), 'vacant' => max(0, $total - $occupied),
            'pct' => $total > 0 ? round($occupied * 100 / $total, 1) : 0.0];
    }

    /** seat numbers nobody has booked at all */
    public function freeSeatNumbers(int $limit = 40): array
    {
        $total = $this->totalSeats();
        if ($total <= 0) {
            return [];
        }
        $used = $this->active()->whereNotNull('d.seat_no')->pluck('d.seat_no')->map(fn ($s) => (int) $s)->flip();
        $free = [];
        for ($i = 1; $i <= $total && count($free) < $limit; $i++) {
            if (! isset($used[$i])) {
                $free[] = $i;
            }
        }

        return $free;
    }

    public function shifts(): array
    {
        $rows = DB::table('plan_types as t')->where('t.branch_id', $this->branch)->whereNull('t.deleted_at')->whereNull('t.parent_id')->orderBy('t.start_time')
            ->get(['t.id', 't.name', 't.start_time', 't.end_time', 't.slot_hours']);
        $counts = $this->active()->groupBy('d.plan_type_id')->selectRaw('d.plan_type_id as id, COUNT(DISTINCT l.id) as n')->pluck('n', 'id');
        $fmt = fn ($t) => $t ? Carbon::parse($t)->format('g:i A') : null;

        return $rows->map(fn ($r) => [
            'name' => $r->name, 'from' => $fmt($r->start_time), 'to' => $fmt($r->end_time), 'hours' => $r->slot_hours, 'members' => (int) ($counts[$r->id] ?? 0),
        ])->all();
    }

    public function floors(): array
    {
        return DB::table('floors')->where('branch_id', $this->branch)->whereNull('deleted_at')->orderBy('floor_no')->get(['name', 'from_seat', 'to_seat', 'total_seats'])
            ->map(fn ($r) => ['name' => $r->name, 'from' => $r->from_seat, 'to' => $r->to_seat, 'seats' => $r->total_seats])->all();
    }

    // ---------- attendance ----------

    public function presentCount(Carbon $day): int
    {
        return (int) DB::table('attendances')->where('library_id', $this->lib)->where('branch_id', $this->branch)->whereDate('date', $day)
            ->where(fn ($w) => $w->whereNotNull('in_time')->orWhere('attendance', 1))->distinct()->count('learner_id');
    }

    public function attendanceOn(Carbon $day, int $limit = 15): array
    {
        $present = DB::table('attendances')->where('library_id', $this->lib)->where('branch_id', $this->branch)->whereDate('date', $day)
            ->where(fn ($w) => $w->whereNotNull('in_time')->orWhere('attendance', 1))->pluck('learner_id')->unique()->values();

        $due = $this->active()->where('d.plan_start_date', '<=', $day->toDateString())->where('d.plan_end_date', '>=', $day->toDateString());
        $expected = (int) (clone $due)->distinct()->count('l.id');
        $absentQ = (clone $due)->whereNotIn('l.id', $present);

        return [
            'present' => $present->count(), 'expected' => $expected,
            'absent' => max(0, $expected - (int) (clone $due)->whereIn('l.id', $present)->distinct()->count('l.id')),
            'absent_rows' => (clone $absentQ)->orderBy('l.name')->limit($limit)->get(['l.id', 'l.name', 'l.learner_no', 'd.seat_no'])
                ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'no' => $r->learner_no, 'seat' => $r->seat_no])->unique('id')->values()->all(),
        ];
    }

    // ---------- one student ----------

    /** @return array<int, array> up to 5 matches */
    public function findLearners(?string $name, ?string $mobile, ?string $no, ?int $seat): array
    {
        $q = $this->learners();
        if ($mobile) {
            $q->where(fn ($w) => $w->where('l.mobile', $mobile)->orWhere('l.alternate_mobile', $mobile));
        } elseif ($no) {
            $q->where('l.learner_no', $no);
        } elseif ($seat) {
            $q->whereIn('l.id', $this->active()->where('d.seat_no', (string) $seat)->pluck('l.id'));
        } elseif ($name) {
            foreach (preg_split('/\s+/u', trim($name)) as $part) {
                if (mb_strlen($part) >= 2) {
                    $q->where('l.name', 'like', '%' . str_replace(['%', '_'], ['\%', '\_'], $part) . '%');
                }
            }
        } else {
            return [];
        }

        return $q->orderByDesc('l.status')->orderBy('l.name')->limit(5)->pluck('l.id')->all();
    }

    public function learnerCard(int $id): ?array
    {
        $l = $this->learners()->where('l.id', $id)->first();
        if (! $l) {
            return null;
        }
        $d = DB::table('learner_detail as d')->leftJoin('plans as p', 'p.id', '=', 'd.plan_id')->leftJoin('plan_types as t', 't.id', '=', 'd.plan_type_id')->leftJoin('exams as e', 'e.id', '=', 'd.exam_id')
            ->where('d.learner_id', $id)->whereNull('d.deleted_at')->orderByDesc('d.status')->orderByDesc('d.plan_end_date')
            ->first(['d.seat_no', 'd.plan_start_date', 'd.plan_end_date', 'd.status', 'p.name as plan', 't.name as shift', 't.start_time', 't.end_time', 'e.name as exam']);
        $dues = (float) DB::table('learner_transactions')->where('learner_id', $id)->whereNull('deleted_at')->where('pending_amount', '>', 0)->sum('pending_amount');
        $last = DB::table('learner_transactions')->where('learner_id', $id)->whereNull('deleted_at')->where('paid_amount', '>', 0)->orderByDesc('paid_date')->orderByDesc('id')->first(['paid_date', 'paid_amount']);
        $month = Carbon::today();
        $att = DB::table('attendances')->where('learner_id', $id)->whereYear('date', $month->year)->whereMonth('date', $month->month)
            ->where(fn ($w) => $w->whereNotNull('in_time')->orWhere('attendance', 1))->distinct()->count('date');
        $lastVisit = DB::table('attendances')->where('learner_id', $id)->orderByDesc('date')->value('date');
        $fmt = fn ($t) => $t ? Carbon::parse($t)->format('g:i A') : null;

        return [
            'id' => $l->id, 'name' => $l->name, 'no' => $l->learner_no, 'active' => (int) $l->status === 1, 'frozen' => (int) $l->frozen_status === 1,
            'mobile' => $this->owner ? $l->mobile : null, 'father' => $l->father_name,
            'plan' => $d?->plan, 'shift' => $d?->shift, 'time' => $d && $d->start_time ? $fmt($d->start_time) . ' - ' . $fmt($d->end_time) : null,
            'seat' => $d?->seat_no, 'start' => $d?->plan_start_date, 'end' => $d?->plan_end_date, 'exam' => $d?->exam,
            'days_left' => $d && $d->plan_end_date ? (int) Carbon::today()->diffInDays(Carbon::parse($d->plan_end_date), false) : null,
            'dues' => $this->owner ? $dues : null,
            'last_payment' => $this->owner && $last ? ['date' => $last->paid_date, 'amount' => (float) $last->paid_amount] : null,
            'present_days' => $att, 'last_visit' => $lastVisit,
        ];
    }

    // ---------- other ----------

    public function complaints(int $limit = 5): array
    {
        if (! Schema::hasTable('complaints')) {
            return ['open' => 0, 'rows' => []];
        }
        $q = DB::table('complaints as c')->join('learners as l', 'l.id', '=', 'c.learner_id')->where('c.library_id', $this->lib)->whereNull('c.deleted_at')
            ->where(fn ($w) => $w->whereNull('c.status')->orWhere('c.status', 0));

        return ['open' => (int) (clone $q)->count(), 'rows' => (clone $q)->orderByDesc('c.id')->limit($limit)->get(['l.name', 'c.title', 'c.created_at'])
            ->map(fn ($r) => ['name' => $r->name, 'title' => $r->title, 'on' => Carbon::parse($r->created_at)->toDateString()])->all()];
    }

    public function enquiries(Carbon $from, Carbon $to, int $limit = 8): array
    {
        $q = DB::table('library_enquiry')->where('branch_id', $this->branch)->whereNull('deleted_at')->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        return ['total' => (int) (clone $q)->count(), 'rows' => (clone $q)->orderByDesc('id')->limit($limit)->get(['name', 'mobile', 'shift_time', 'created_at'])
            ->map(fn ($r) => ['name' => $r->name, 'mobile' => $this->owner ? $r->mobile : null, 'shift' => $r->shift_time, 'on' => Carbon::parse($r->created_at)->toDateString()])->all()];
    }

    /** Best matching entries of the how-to manual (table `how-to-use`) */
    public function howTo(array $words, int $limit = 3): array
    {
        $words = array_values(array_filter($words, fn ($w) => mb_strlen($w) >= 3));
        if (! $words) {
            return [];
        }
        $scored = [];
        foreach (DB::table('how-to-use')->get() as $r) {
            $hay = mb_strtolower($r->operation_name . ' ' . $r->hindi_question . ' ' . $r->usage_english . ' ' . $r->usage_hindi);
            $title = mb_strtolower($r->operation_name . ' ' . $r->hindi_question);
            $score = 0;
            foreach ($words as $w) {
                if (str_contains($title, $w)) {
                    $score += 3;
                } elseif (str_contains($hay, $w)) {
                    $score += 1;
                }
            }
            if ($score > 0) {
                $scored[] = ['score' => $score, 'row' => $r];
            }
        }
        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_map(fn ($s) => ['title' => $s['row']->operation_name, 'question_hi' => $s['row']->hindi_question, 'en' => trim((string) $s['row']->usage_english), 'hi' => trim((string) $s['row']->usage_hindi)], array_slice($scored, 0, $limit));
    }
}
