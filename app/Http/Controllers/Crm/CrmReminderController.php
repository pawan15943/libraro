<?php

namespace App\Http\Controllers\Crm;

use App\Models\Crm\CrmReminder;
use App\Services\Crm\CrmLeadService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CrmReminderController extends CrmBaseController
{
    public const TABS = ['overdue' => 'Overdue', 'today' => 'Today', 'upcoming' => 'Upcoming', 'done' => 'Done'];

    public function index(Request $request)
    {
        $user = $this->user();
        $tab = array_key_exists($request->get('tab'), self::TABS) ? $request->get('tab') : 'today';
        $base = function () use ($user, $request) {
            $q = CrmReminder::query()->whereHas('lead', fn ($l) => $l->open());
            if (! CrmLeadService::seesAll($user)) {
                $q->where('user_id', $user->id);
            } elseif ($request->filled('agent')) {
                $q->where('user_id', (int) $request->get('agent'));
            }

            return $q;
        };
        $scope = fn ($q, $t) => match ($t) {
            'overdue' => $q->pending()->where('remind_at', '<', now()),
            'today' => $q->pending()->whereBetween('remind_at', [now()->startOfDay(), now()->endOfDay()]),
            'upcoming' => $q->pending()->where('remind_at', '>', now()->endOfDay()),
            default => $q->where('status', 'done')->where('done_at', '>=', now()->subDays(7)),
        };

        $counts = collect(self::TABS)->map(fn ($l, $t) => $scope($base(), $t)->count());
        if (! $request->has('tab') && $counts['overdue']) {
            $tab = 'overdue';
        }

        $reminders = $scope($base(), $tab)->with(['lead.stage', 'lead.subStage', 'agent'])
            ->orderBy($tab === 'done' ? 'done_at' : 'remind_at', $tab === 'done' ? 'desc' : 'asc')
            ->paginate(25)->withQueryString();

        return view('crm.reminders', compact('reminders', 'tab', 'counts'));
    }

    public function store(Request $request, int $id)
    {
        $data = $request->validate([
            'remind_at' => ['required', 'date', 'after:now'],
            'note' => ['nullable', 'string', 'max:255'],
        ], ['remind_at.after' => 'Pick a time in the future.']);
        $lead = $this->leads->findVisible($this->user(), $id);
        $at = Carbon::parse($data['remind_at']);
        $this->leads->addReminder($lead, $this->user(), $at, $data['note'] ?? null);

        $msg = 'Follow-up set for '.$at->format('j M, g:i A').'.';

        return $request->expectsJson() ? response()->json(['ok' => true, 'message' => $msg]) : back()->with('success', $msg);
    }

    public function update(Request $request, int $id, string $action)
    {
        $user = $this->user();
        $reminder = CrmReminder::pending()
            ->when(! CrmLeadService::seesAll($user), fn ($q) => $q->where('user_id', $user->id))
            ->findOrFail($id);
        $this->leads->completeReminder($reminder, $user, $action === 'done' ? 'done' : 'cancelled');

        return back()->with('success', $action === 'done' ? 'Marked as done.' : 'Follow-up cancelled.');
    }

    /** Polled by admin-crm-notify.js on every admin page: follow-ups that just became due. */
    public function due()
    {
        $user = $this->user();
        $base = CrmReminder::pending()->where('user_id', $user->id)->where('remind_at', '<=', now())
            ->whereHas('lead', fn ($q) => $q->open());

        $fresh = (clone $base)->whereNull('notified_at')->with('lead')->orderBy('remind_at')->limit(5)->get();
        if ($fresh->isNotEmpty()) {
            CrmReminder::whereIn('id', $fresh->pluck('id'))->update(['notified_at' => now()]);
        }

        return response()->json([
            'count' => (clone $base)->count(),
            'items' => $fresh->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->lead->display_name,
                'mobile' => $r->lead->mobile,
                'note' => $r->note,
                'at' => $r->remind_at->format('g:i A'),
                'url' => route('crm.leads.show', $r->lead_id),
            ]),
        ]);
    }
}
