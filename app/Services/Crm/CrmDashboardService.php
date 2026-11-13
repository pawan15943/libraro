<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmActivity;
use App\Models\Crm\CrmCall;
use App\Models\Crm\CrmDisposition;
use App\Models\Crm\CrmLead;
use App\Models\Crm\CrmReminder;
use App\Models\Crm\CrmStage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Numbers for the lead dashboard. Managers (crm-view-all-leads) see the whole
 * team, optionally one agent; agents see their own work.
 */
class CrmDashboardService
{
    /** points used to rank performers */
    public const SCORE = ['call' => 1, 'connected' => 2, 'enrolled' => 25];

    public function __construct(private CrmLeadService $leads) {}

    public function build(User $user, Carbon $from, Carbon $to, ?int $agentId = null): array
    {
        $agentId = CrmLeadService::seesAll($user) ? $agentId : $user->id;

        $leadQ = fn () => $agentId ? CrmLead::where('assigned_to', $agentId) : CrmLead::query();
        $callQ = fn () => CrmCall::whereBetween('called_at', [$from, $to])->when($agentId, fn ($q) => $q->where('user_id', $agentId));

        $calls = $callQ()->count();
        $connected = $callQ()->where('is_connected', true)->count();
        $talk = (int) $callQ()->sum('duration_seconds');
        $enrolled = $this->enrolledQuery($from, $to, $agentId)->count();
        $newLeads = $leadQ()->whereBetween('created_at', [$from, $to])->count();
        $leadsCalled = $callQ()->distinct('lead_id')->count('lead_id');

        $remQ = fn () => CrmReminder::pending()->when($agentId, fn ($q) => $q->where('user_id', $agentId))
            ->whereHas('lead', fn ($q) => $q->open());

        return [
            'kpis' => [
                'open' => $leadQ()->open()->count(),
                'new' => $newLeads,
                'calls' => $calls,
                'connected' => $connected,
                'connect_rate' => $calls ? round($connected / $calls * 100) : 0,
                'leads_called' => $leadsCalled,
                'talk_minutes' => (int) round($talk / 60),
                'enrolled' => $enrolled,
                'conversion_rate' => $leadsCalled ? round($enrolled / $leadsCalled * 100, 1) : 0,
                'due_today' => $remQ()->whereBetween('remind_at', [now()->startOfDay(), now()->endOfDay()])->count(),
                'overdue' => $remQ()->where('remind_at', '<', now())->count(),
                'never_called' => $leadQ()->open()->where('calls_count', 0)->count(),
                'stale' => $leadQ()->open()->where('calls_count', '>', 0)->where('last_called_at', '<', now()->subDays(7))->count(),
                'not_paid' => $leadQ()->open()->where('source', 'signup')->count(),
            ],
            'top_week' => $this->leaderboard(now()->startOfWeek(), now()->endOfWeek()),
            'top_month' => $this->leaderboard(now()->startOfMonth(), now()->endOfMonth()),
            'funnel' => $this->funnel($agentId),
            'dispositions' => $this->dispositions($from, $to, $agentId),
            'daily' => $this->daily($agentId),
            'hours' => $this->hours($from, $to, $agentId),
            'sources' => $this->sources($agentId),
            'agents' => CrmLeadService::seesAll($user) ? $this->agentTable($from, $to) : collect(),
        ];
    }

    /** Top performers in a period, ranked by calls, connected calls and enrolments. */
    public function leaderboard(Carbon $from, Carbon $to, int $limit = 5): Collection
    {
        $calls = CrmCall::whereBetween('called_at', [$from, $to])
            ->select('user_id', DB::raw('COUNT(*) calls'), DB::raw('SUM(is_connected) connected'), DB::raw('COALESCE(SUM(duration_seconds),0) talk'))
            ->groupBy('user_id')->get()->keyBy('user_id');

        $wins = $this->enrolledQuery($from, $to)->select('user_id', DB::raw('COUNT(*) n'))->groupBy('user_id')->pluck('n', 'user_id');

        $ids = $calls->keys()->merge($wins->keys())->filter()->unique();
        $users = User::whereIn('id', $ids)->get()->keyBy('id');

        return $ids->map(function ($id) use ($calls, $wins, $users) {
            $c = $calls[$id] ?? null;
            $row = [
                'user' => $users[$id] ?? null,
                'calls' => (int) ($c->calls ?? 0),
                'connected' => (int) ($c->connected ?? 0),
                'talk_minutes' => (int) round(($c->talk ?? 0) / 60),
                'enrolled' => (int) ($wins[$id] ?? 0),
            ];
            $row['score'] = $row['calls'] * self::SCORE['call'] + $row['connected'] * self::SCORE['connected'] + $row['enrolled'] * self::SCORE['enrolled'];
            $row['connect_rate'] = $row['calls'] ? round($row['connected'] / $row['calls'] * 100) : 0;

            return $row;
        })->filter(fn ($r) => $r['user'])->sortByDesc('score')->take($limit)->values();
    }

    private function enrolledQuery(Carbon $from, Carbon $to, ?int $agentId = null)
    {
        return CrmActivity::where('type', 'enrolled')->whereBetween('created_at', [$from, $to])
            ->when($agentId, fn ($q) => $q->where('user_id', $agentId));
    }

    /** Leads per stage with sub-stage split. */
    private function funnel(?int $agentId): Collection
    {
        $counts = CrmLead::when($agentId, fn ($q) => $q->where('assigned_to', $agentId))
            ->select('stage_id', 'sub_stage_id', DB::raw('COUNT(*) n'))->groupBy('stage_id', 'sub_stage_id')->get();
        $total = max(1, $counts->sum('n'));

        return CrmStage::tree()->map(function ($stage) use ($counts, $total) {
            $n = (int) $counts->where('stage_id', $stage->id)->sum('n');

            return [
                'stage' => $stage,
                'count' => $n,
                'percent' => round($n / $total * 100),
                'subs' => $stage->children->map(fn ($s) => ['name' => $s->name, 'count' => (int) $counts->where('sub_stage_id', $s->id)->sum('n')])
                    ->filter(fn ($s) => $s['count'] > 0)->values(),
            ];
        });
    }

    private function dispositions(Carbon $from, Carbon $to, ?int $agentId): Collection
    {
        $rows = CrmCall::whereBetween('called_at', [$from, $to])->when($agentId, fn ($q) => $q->where('user_id', $agentId))
            ->select('disposition_id', DB::raw('COUNT(*) n'))->groupBy('disposition_id')->pluck('n', 'disposition_id');
        $total = max(1, $rows->sum());

        return CrmDisposition::orderBy('sort')->get()
            ->map(fn ($d) => ['d' => $d, 'count' => (int) ($rows[$d->id] ?? 0), 'percent' => round(($rows[$d->id] ?? 0) / $total * 100)])
            ->filter(fn ($r) => $r['count'] > 0)->sortByDesc('count')->values();
    }

    /** Calls per day for the last 14 days (total and connected). */
    private function daily(?int $agentId): Collection
    {
        $start = now()->subDays(13)->startOfDay();
        $rows = CrmCall::where('called_at', '>=', $start)->when($agentId, fn ($q) => $q->where('user_id', $agentId))
            ->select(DB::raw('DATE(called_at) d'), DB::raw('COUNT(*) calls'), DB::raw('SUM(is_connected) connected'))
            ->groupBy('d')->get()->keyBy('d');
        $max = max(1, (int) $rows->max('calls'));

        return collect(range(0, 13))->map(function ($i) use ($start, $rows, $max) {
            $day = $start->copy()->addDays($i);
            $r = $rows[$day->toDateString()] ?? null;

            return [
                'day' => $day,
                'calls' => (int) ($r->calls ?? 0),
                'connected' => (int) ($r->connected ?? 0),
                'h' => round(($r->calls ?? 0) / $max * 100),
                'hc' => round(($r->connected ?? 0) / $max * 100),
            ];
        });
    }

    /** Best time to call: connect rate per hour of day. */
    private function hours(Carbon $from, Carbon $to, ?int $agentId): Collection
    {
        $rows = CrmCall::whereBetween('called_at', [$from, $to])->when($agentId, fn ($q) => $q->where('user_id', $agentId))
            ->select(DB::raw('HOUR(called_at) h'), DB::raw('COUNT(*) calls'), DB::raw('SUM(is_connected) connected'))
            ->groupBy('h')->get()->keyBy('h');

        return collect(range(9, 20))->map(fn ($h) => [
            'hour' => $h,
            'calls' => (int) ($rows[$h]->calls ?? 0),
            'rate' => ($rows[$h]->calls ?? 0) ? round($rows[$h]->connected / $rows[$h]->calls * 100) : null,
        ]);
    }

    private function sources(?int $agentId): Collection
    {
        $won = CrmStage::whereNull('parent_id')->where('is_won', true)->pluck('id');

        return CrmLead::when($agentId, fn ($q) => $q->where('assigned_to', $agentId))
            ->select('source', DB::raw('COUNT(*) total'), DB::raw('SUM(CASE WHEN stage_id IN ('.($won->implode(',') ?: '0').') THEN 1 ELSE 0 END) won'), DB::raw('SUM(calls_count = 0) untouched'))
            ->groupBy('source')->orderByDesc('total')->get()
            ->map(fn ($r) => [
                'label' => CrmLead::SOURCES[$r->source] ?? ucfirst($r->source),
                'total' => (int) $r->total,
                'won' => (int) $r->won,
                'untouched' => (int) $r->untouched,
                'rate' => $r->total ? round($r->won / $r->total * 100, 1) : 0,
            ]);
    }

    /** One row per agent: workload, activity in the period and what is waiting. */
    private function agentTable(Carbon $from, Carbon $to): Collection
    {
        $calls = CrmCall::whereBetween('called_at', [$from, $to])
            ->select('user_id', DB::raw('COUNT(*) calls'), DB::raw('SUM(is_connected) connected'), DB::raw('COALESCE(AVG(NULLIF(duration_seconds,0)),0) avg_dur'), DB::raw('MAX(called_at) last_call'), DB::raw('COUNT(DISTINCT lead_id) leads'))
            ->groupBy('user_id')->get()->keyBy('user_id');
        $open = CrmLead::open()->whereNotNull('assigned_to')->select('assigned_to', DB::raw('COUNT(*) n'))->groupBy('assigned_to')->pluck('n', 'assigned_to');
        $overdue = CrmReminder::pending()->where('remind_at', '<', now())->whereHas('lead', fn ($q) => $q->open())
            ->select('user_id', DB::raw('COUNT(*) n'))->groupBy('user_id')->pluck('n', 'user_id');
        $wins = $this->enrolledQuery($from, $to)->select('user_id', DB::raw('COUNT(*) n'))->groupBy('user_id')->pluck('n', 'user_id');

        return $this->leads->agents()->map(function (User $u) use ($calls, $open, $overdue, $wins) {
            $c = $calls[$u->id] ?? null;

            return [
                'user' => $u,
                'open' => (int) ($open[$u->id] ?? 0),
                'calls' => (int) ($c->calls ?? 0),
                'leads' => (int) ($c->leads ?? 0),
                'connected' => (int) ($c->connected ?? 0),
                'rate' => ($c->calls ?? 0) ? round($c->connected / $c->calls * 100) : 0,
                'avg' => (int) round($c->avg_dur ?? 0),
                'enrolled' => (int) ($wins[$u->id] ?? 0),
                'overdue' => (int) ($overdue[$u->id] ?? 0),
                'last_call' => ! empty($c?->last_call) ? Carbon::parse($c->last_call) : null,
            ];
        })->sortByDesc('calls')->values();
    }
}
