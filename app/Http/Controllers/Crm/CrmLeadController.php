<?php

namespace App\Http\Controllers\Crm;

use App\Models\Crm\CrmLead;
use App\Models\Crm\CrmStage;
use App\Services\Crm\CrmLeadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CrmLeadController extends CrmBaseController
{
    public const QUEUE_TABS = [
        'due' => 'Due now',
        'new' => 'Never called',
        'notpaid' => 'Registered, not paid',
        'hot' => 'Hot',
        'all' => 'All open',
    ];

    /** The call queue: only open leads (never Enrolled / Not interested / Invalid). */
    public function queue(Request $request)
    {
        $tab = array_key_exists($request->get('tab'), self::QUEUE_TABS) ? $request->get('tab') : 'due';
        $base = fn () => $this->leads->visibleTo($this->user())->open();

        $counts = [];
        foreach (array_keys(self::QUEUE_TABS) as $t) {
            $counts[$t] = $this->queueTab($base(), $t)->count();
        }
        // the first visit lands on the first tab that has work in it
        if (! $request->has('tab') && ! $counts['due']) {
            $tab = $counts['new'] ? 'new' : ($counts['notpaid'] ? 'notpaid' : 'all');
        }

        $q = $this->queueTab($base(), $tab)
            ->when($request->filled('q'), fn ($q) => $this->search($q, $request->get('q')))
            ->with(['stage', 'subStage', 'agent', 'lastDisposition']);

        $q = match ($tab) {
            'due' => $q->orderBy('next_follow_up_at'),
            'new' => $q->orderByRaw("FIELD(priority,'hot','warm','cold')")->latest('id'),
            default => $q->orderByRaw('next_follow_up_at IS NULL')->orderBy('next_follow_up_at')->orderBy('last_called_at'),
        };

        return view('crm.queue', [
            'leads' => $q->paginate(20)->withQueryString(),
            'tab' => $tab,
            'counts' => $counts,
        ]);
    }

    private function queueTab(Builder $q, string $tab): Builder
    {
        return match ($tab) {
            'due' => $q->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now()->endOfDay()),
            'new' => $q->where('calls_count', 0),
            'notpaid' => $q->where('source', 'signup'),
            'hot' => $q->where('priority', 'hot'),
            default => $q,
        };
    }

    public function index(Request $request)
    {
        $leads = $this->filtered($request)
            ->with(['stage', 'subStage', 'agent', 'lastDisposition'])
            ->paginate((int) $request->get('per_page', 25))
            ->withQueryString();

        $active = collect($request->only(['stage', 'sub', 'source', 'agent', 'priority', 'follow', 'from', 'to']))->filter(fn ($v) => $v !== null && $v !== '')->count()
            + ($request->get('status', 'open') !== 'open' ? 1 : 0);

        return view('crm.leads.index', ['leads' => $leads, 'activeFilters' => $active]);
    }

    /** List filters, shared with the CSV export. */
    private function filtered(Request $request): Builder
    {
        $user = $this->user();
        $q = $this->leads->visibleTo($user);

        match ($request->get('status', 'open')) {
            'closed' => $q->closed(),
            'all' => null,
            default => $q->open(),
        };

        if ($request->filled('q')) {
            $this->search($q, $request->get('q'));
        }
        $q->when($request->filled('stage'), fn ($q) => $q->where('stage_id', $request->integer('stage')))
            ->when($request->filled('sub'), fn ($q) => $q->where('sub_stage_id', $request->integer('sub')))
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->get('source')))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->get('priority')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->get('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->get('to')));

        if ($request->filled('agent')) {
            $agent = $request->get('agent');
            $agent === 'none' ? $q->whereNull('assigned_to') : $q->where('assigned_to', $agent === 'me' ? $user->id : (int) $agent);
        }

        match ($request->get('follow')) {
            'overdue' => $q->where('next_follow_up_at', '<', now()),
            'today' => $q->whereBetween('next_follow_up_at', [now()->startOfDay(), now()->endOfDay()]),
            'upcoming' => $q->where('next_follow_up_at', '>', now()->endOfDay()),
            'none' => $q->whereNull('next_follow_up_at'),
            'stale' => $q->where('calls_count', '>', 0)->where('last_called_at', '<', now()->subDays(7)),
            'never' => $q->where('calls_count', 0),
            default => null,
        };

        match ($request->get('sort', 'latest')) {
            'follow' => $q->orderByRaw('next_follow_up_at IS NULL')->orderBy('next_follow_up_at'),
            'called' => $q->orderByRaw('last_called_at IS NULL DESC')->orderBy('last_called_at'),
            'name' => $q->orderBy('library_name'),
            default => $q->latest('id'),
        };

        return $q;
    }

    private function search(Builder $q, string $term): Builder
    {
        $term = trim($term);
        $digits = preg_replace('/\D/', '', $term);

        return $q->where(function ($w) use ($term, $digits) {
            $w->where('library_name', 'like', "%{$term}%")
                ->orWhere('contact_name', 'like', "%{$term}%")
                ->orWhere('city', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");
            if (strlen($digits) >= 4) {
                $w->orWhere('mobile', 'like', "%{$digits}%")->orWhere('alt_mobile', 'like', "%{$digits}%");
            }
        });
    }

    public function show(int $id)
    {
        $lead = $this->leads->findVisible($this->user(), $id)
            ->load(['stage', 'subStage', 'agent', 'library', 'lastDisposition']);

        return view('crm.leads.show', [
            'lead' => $lead,
            'activities' => $lead->activities()->with('user')->limit(200)->get(),
            'calls' => $lead->calls()->with(['agent', 'disposition'])->get(),
            'reminders' => $lead->reminders()->with('agent')->latest('remind_at')->get(),
        ]);
    }

    public function create()
    {
        return view('crm.leads.form', ['lead' => new CrmLead(['priority' => 'warm'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if (! CrmLeadService::can($this->user(), 'crm-assign-leads')) {
            $data['assigned_to'] = $this->user()->id; // agents add leads for themselves
        }
        $lead = $this->leads->create($data, $this->user(), $data['source'] ?? 'manual');

        return redirect()->route('crm.leads.show', $lead->id)->with('success', 'Lead added.');
    }

    public function edit(int $id)
    {
        return view('crm.leads.form', ['lead' => $this->leads->findVisible($this->user(), $id)]);
    }

    public function update(Request $request, int $id)
    {
        $lead = $this->leads->findVisible($this->user(), $id);
        $this->leads->update($lead, $this->validated($request, $lead), $this->user());

        return redirect()->route('crm.leads.show', $lead->id)->with('success', 'Lead updated.');
    }

    /** Status update: stage + sub-stage with a note. */
    public function stage(Request $request, int $id)
    {
        $data = $request->validate([
            'stage_id' => ['required', Rule::exists('crm_stages', 'id')->whereNull('parent_id')],
            'sub_stage_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string', 'max:2000'],
            'priority' => ['nullable', Rule::in(array_keys(CrmLead::PRIORITIES))],
        ]);
        $lead = $this->leads->findVisible($this->user(), $id);
        if (! empty($data['priority']) && $data['priority'] !== $lead->priority) {
            $lead->update(['priority' => $data['priority']]);
        }
        $this->leads->changeStage($lead, (int) $data['stage_id'], $data['sub_stage_id'] ?? null, $this->user(), $data['note'] ?? null);

        return back()->with('success', 'Status updated: '.CrmStage::find($data['stage_id'])->name.'.');
    }

    public function note(Request $request, int $id)
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);
        $this->leads->addNote($this->leads->findVisible($this->user(), $id), $this->user(), $data['note']);

        return back()->with('success', 'Note added.');
    }

    public function destroy(int $id)
    {
        $lead = $this->leads->findVisible($this->user(), $id);
        $lead->reminders()->pending()->update(['status' => 'cancelled']);
        $lead->delete();

        return redirect()->route('crm.leads.index')->with('success', 'Lead deleted.');
    }

    public function assign(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'agent_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $n = $this->leads->assign($data['ids'], $data['agent_id'] ?? null, $this->user());

        return back()->with('success', $n.' '.str('lead')->plural($n).' assigned.');
    }

    public function sync()
    {
        $r = $this->sync->sync();

        return back()->with('success', "Sync done: {$r['added']} new registered-not-paid leads, {$r['enrolled']} marked Enrolled.");
    }

    public function export(Request $request): StreamedResponse
    {
        $q = $this->filtered($request)->with(['stage', 'subStage', 'agent', 'lastDisposition']);

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Library', 'Contact', 'Mobile', 'Email', 'City', 'Source', 'Stage', 'Sub-stage', 'Priority', 'Agent', 'Calls', 'Connected', 'Last disposition', 'Last called', 'Next follow-up', 'Created']);
            $q->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $l) {
                    fputcsv($out, [
                        $l->library_name, $l->contact_name, $l->mobile, $l->email, $l->city,
                        CrmLead::SOURCES[$l->source] ?? $l->source, $l->stage?->name, $l->subStage?->name, $l->priority,
                        $l->agent?->name, $l->calls_count, $l->connected_count, $l->lastDisposition?->name,
                        $l->last_called_at?->format('Y-m-d H:i'), $l->next_follow_up_at?->format('Y-m-d H:i'), $l->created_at?->format('Y-m-d H:i'),
                    ]);
                }
            });
            fclose($out);
        }, 'leads-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function validated(Request $request, ?CrmLead $lead = null): array
    {
        return $request->validate([
            'library_name' => ['nullable', 'string', 'max:255', 'required_without:contact_name'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20'],
            'alt_mobile' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
            'source' => ['nullable', Rule::in(array_keys(CrmLead::SOURCES))],
            'priority' => ['required', Rule::in(array_keys(CrmLead::PRIORITIES))],
            'notes' => ['nullable', 'string', 'max:2000'],
            'stage_id' => [$lead ? 'prohibited' : 'nullable', Rule::exists('crm_stages', 'id')->whereNull('parent_id')],
            'sub_stage_id' => [$lead ? 'prohibited' : 'nullable', 'integer'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'follow_up_at' => ['nullable', 'date'],
        ], [], ['library_name' => 'library name', 'contact_name' => 'contact name']);
    }
}
