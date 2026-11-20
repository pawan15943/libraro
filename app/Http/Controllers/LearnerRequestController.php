<?php

namespace App\Http\Controllers;

use App\Services\LearnerRequestService;
use Illuminate\Http\Request;

/** Library panel → Learners → Requests: what learners asked for (renew, change plan, swap seat …) and the answer. */
class LearnerRequestController extends Controller
{
    public const TABS = ['pending' => 'Waiting', 'done' => 'Answered', 'all' => 'All'];

    public function __construct(private LearnerRequestService $requests) {}

    public function index(Request $request)
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'pending';

        $rows = $this->requests->forLibrary()
            ->when($tab === 'pending', fn ($q) => $q->where('r.request_status', LearnerRequestService::PENDING))
            ->when($tab === 'done', fn ($q) => $q->where('r.request_status', '!=', LearnerRequestService::PENDING))
            ->select('r.*', 'l.name as learner_name', 'l.learner_no', 'l.mobile', 'l.seat_no', 'l.status as learner_status')
            ->orderByRaw('r.request_status = 0 DESC')->orderByDesc('r.id')
            ->paginate(30)->withQueryString();

        $waiting = (clone $this->requests->forLibrary())->where('r.request_status', LearnerRequestService::PENDING)->count();

        return view('library.learner-requests', [
            'rows' => $rows,
            'tab' => $tab,
            'tabs' => self::TABS,
            'waiting' => $waiting,
            'types' => LearnerRequestService::TYPES,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'decision' => 'required|in:done,reject',
            'reply' => 'nullable|string|max:1000',
        ]);
        $this->requests->decide($id, $data['decision'] === 'done', $data['reply'] ?? null);

        return back()->with('success', $data['decision'] === 'done' ? 'Marked as done. The learner can see it.' : 'The learner is told it is not possible.');
    }
}
