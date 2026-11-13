<?php

namespace App\Http\Controllers\Crm;

use App\Models\Crm\CrmCall;
use App\Models\Crm\CrmLead;
use App\Services\Crm\CrmLeadService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CrmCallController extends CrmBaseController
{
    /** Log a call with its disposition (from the call sheet). */
    public function store(Request $request, int $id)
    {
        $data = $request->validate([
            'disposition_id' => ['required', Rule::exists('crm_dispositions', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:36000'],
            'stage_id' => ['nullable', Rule::exists('crm_stages', 'id')->whereNull('parent_id')],
            'sub_stage_id' => ['nullable', 'integer'],
            'follow_up_at' => ['nullable', 'date', 'after:now'],
            'follow_up_note' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', Rule::in(array_keys(CrmLead::PRIORITIES))],
        ], ['follow_up_at.after' => 'Pick a follow-up time in the future.']);

        $lead = $this->leads->findVisible($this->user(), $id);
        $call = $this->leads->logCall($lead, $this->user(), $data);
        $lead->refresh();

        $msg = 'Call logged: '.$call->disposition->name.'.'.($lead->next_follow_up_at ? ' Next call '.$lead->next_follow_up_at->format('j M, g:i A').'.' : '');
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    /** Call log with filters. Agents see their own calls. */
    public function index(Request $request)
    {
        $user = $this->user();
        $q = CrmCall::with(['lead.stage', 'agent', 'disposition'])->latest('called_at');

        if (! CrmLeadService::seesAll($user)) {
            $q->where('user_id', $user->id);
        } elseif ($request->filled('agent')) {
            $q->where('user_id', (int) $request->get('agent'));
        }

        $range = $this->range($request->get('range', 'today'), $request->get('from'), $request->get('to'));
        $q->whereBetween('called_at', $range)
            ->when($request->filled('disposition'), fn ($q) => $q->where('disposition_id', (int) $request->get('disposition')))
            ->when($request->get('connected') === '1', fn ($q) => $q->where('is_connected', true))
            ->when($request->get('connected') === '0', fn ($q) => $q->where('is_connected', false));

        $summary = (clone $q)->reorder()->selectRaw('COUNT(*) calls, COALESCE(SUM(is_connected),0) connected, COALESCE(SUM(duration_seconds),0) talk, COUNT(DISTINCT lead_id) leads')->first();

        return view('crm.calls', ['calls' => $q->paginate(30)->withQueryString(), 'summary' => $summary]);
    }

    /**
     * What happened with this lead so far, for the "Log call" sheet: every earlier call
     * (result, notes, who, when, how long, stage it led to) plus notes and messages.
     */
    public function history(int $id)
    {
        $lead = $this->leads->findVisible($this->user(), $id);

        $calls = $lead->calls()->with(['agent:id,name', 'disposition:id,name,color', 'stageTo:id,name,color'])->limit(100)->get()
            ->map(fn (CrmCall $c) => [
                'kind' => 'call',
                'at' => $c->called_at->toIso8601String(),
                'when' => $c->called_at->format('j M, g:i A'),
                'ago' => $c->called_at->diffForHumans(),
                'result' => $c->disposition->name ?? 'Call',
                'color' => $c->disposition->color ?? '#64748b',
                'connected' => $c->is_connected,
                'duration' => $c->duration_label,
                'notes' => $c->notes,
                'agent' => $c->agent->name ?? null,
                'stage' => $c->stageTo->name ?? null,
                'next' => $c->follow_up_at?->format('j M, g:i A'),
            ]);

        $other = $lead->activities()->whereIn('type', ['note', 'whatsapp', 'sms', 'stage'])->with('user:id,name')->limit(60)->get()
            ->filter(fn ($a) => $a->type !== 'stage' || $a->body) // stage changes only when someone wrote why
            ->map(fn ($a) => [
                'kind' => $a->type,
                'at' => $a->created_at->toIso8601String(),
                'when' => $a->created_at->format('j M, g:i A'),
                'ago' => $a->created_at->diffForHumans(),
                'result' => $a->title,
                'notes' => $a->body,
                'agent' => $a->user->name ?? null,
            ]);

        return response()->json([
            'summary' => [
                'calls' => $lead->calls_count,
                'connected' => $lead->connected_count,
                'last' => $lead->last_called_at?->diffForHumans(),
                'stage' => trim(($lead->stage->name ?? '').($lead->subStage ? ' › '.$lead->subStage->name : '')),
                'lead_notes' => $lead->notes,
            ],
            'items' => $calls->concat($other)->sortByDesc('at')->values(),
        ]);
    }

    public static function range(string $key, ?string $from = null, ?string $to = null): array
    {
        return match ($key) {
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            '30' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            'custom' => [\Carbon\Carbon::parse($from ?: now())->startOfDay(), \Carbon\Carbon::parse($to ?: now())->endOfDay()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };
    }
}
