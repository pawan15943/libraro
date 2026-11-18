<?php

namespace App\Http\Controllers\Ordering;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Learner;
use App\Models\Ordering\OrderTable;
use App\Services\Ordering\OrderingSetupService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Library panel → Orders → Order Settings (spec §7): module on/off, modes, hours, payment
 * methods, pay-later eligibility, staff response timings, replacement rules, cafeteria tables.
 */
class OrderSettingsController extends Controller
{
    public function __construct(private OrderingSetupService $setup) {}

    public function index()
    {
        $settings = $this->setup->current();
        $branchId = (int) getCurrentBranch();
        $branch = Branch::find($branchId);

        $tables = OrderTable::where('library_id', getLibraryId())->where('branch_id', $branchId)
            ->orderBy('sort_order')->orderBy('label')->get();

        $payLaterLearners = collect();
        if (! empty($settings->pay_later_learner_ids)) {
            $payLaterLearners = Learner::where('library_id', getLibraryId())
                ->where('branch_id', $branchId)->whereIn('id', $settings->pay_later_learner_ids)
                ->orderBy('name')->get(['id', 'name', 'learner_no', 'seat_no']);
        }

        return view('ordering.library.settings', [
            'settings' => $settings,
            'tables' => $tables,
            'hasUpi' => ! empty(optional($branch)->upi_id),
            'payLaterLearners' => $payLaterLearners,
        ]);
    }

    public function save(Request $request)
    {
        $settings = $this->setup->current();

        $data = $request->validate([
            'opens_at' => 'nullable|date_format:H:i',
            'closes_at' => 'nullable|date_format:H:i|required_with:opens_at',
            'prep_minutes' => 'required|integer|min:0|max:240',
            'delivery_charge' => 'required|numeric|min:0|max:9999',
            'pay_later_scope' => ['required', Rule::in(['all', 'selected'])],
            'pay_later_learner_ids' => 'nullable|array|max:500',
            'pay_later_learner_ids.*' => 'integer',
            'pay_later_limit' => 'required|numeric|min:0|max:99999',
            'max_open_orders' => 'required|integer|min:0|max:20',
            'reminder_minutes' => 'required|integer|min:1|max:120',
            'escalation_minutes' => 'required|integer|min:1|max:240|gte:reminder_minutes',
            'replacement_timeout_minutes' => 'required|integer|min:1|max:240',
            'replacement_timeout_action' => ['required', Rule::in(['remove', 'escalate'])],
            'learner_cancel_until' => ['required', Rule::in(['new', 'under_review', 'confirmed'])],
            'upload_types' => ['required', 'string', 'max:100', 'regex:/^\s*[a-z0-9]{2,5}(\s*,\s*[a-z0-9]{2,5})*\s*$/i'],
            'upload_max_mb' => 'required|integer|min:1|max:25',
        ], [
            'escalation_minutes.gte' => 'Escalation must come after (or with) the first reminder.',
            'upload_types.regex' => 'List file types separated by commas, e.g. pdf, jpg, png.',
        ]);

        $flags = [];
        foreach (['is_enabled', 'mode_seat', 'mode_table', 'mode_counter', 'seat_change_allowed', 'pay_online', 'pay_offline',
            'pay_later', 'expired_can_order', 'sound_enabled', 'replacement_enabled', 'replacement_multiple', 'partial_fulfilment'] as $f) {
            $flags[$f] = $request->boolean($f);
        }

        if (! $flags['mode_seat'] && ! $flags['mode_table'] && ! $flags['mode_counter']) {
            throw ValidationException::withMessages(['mode_seat' => 'Keep at least one order mode on.']);
        }
        if (! $flags['pay_online'] && ! $flags['pay_offline'] && ! $flags['pay_later']) {
            throw ValidationException::withMessages(['pay_online' => 'Keep at least one payment method on.']);
        }

        // only this branch's learners can be on the pay-later list
        $ids = array_values(array_unique(array_map('intval', $data['pay_later_learner_ids'] ?? [])));
        if ($ids) {
            $ids = Learner::where('library_id', getLibraryId())
                ->where('branch_id', getCurrentBranch())->whereNull('deleted_at')->whereIn('id', $ids)->pluck('id')->all();
        }

        $types = implode(',', array_unique(array_map(fn ($t) => strtolower(trim($t)), explode(',', $data['upload_types']))));

        $settings->update($flags + [
            'opens_at' => $data['opens_at'] ?? null,
            'closes_at' => ! empty($data['opens_at']) ? ($data['closes_at'] ?? null) : null,
            'prep_minutes' => (int) $data['prep_minutes'],
            'delivery_charge' => round((float) $data['delivery_charge'], 2),
            'pay_later_scope' => $data['pay_later_scope'],
            'pay_later_learner_ids' => $ids ?: null,
            'pay_later_limit' => round((float) $data['pay_later_limit'], 2),
            'max_open_orders' => (int) $data['max_open_orders'],
            'reminder_minutes' => (int) $data['reminder_minutes'],
            'escalation_minutes' => (int) $data['escalation_minutes'],
            'replacement_timeout_minutes' => (int) $data['replacement_timeout_minutes'],
            'replacement_timeout_action' => $data['replacement_timeout_action'],
            'learner_cancel_until' => $data['learner_cancel_until'],
            'upload_types' => $types,
            'upload_max_mb' => (int) $data['upload_max_mb'],
        ]);

        return back()->with('success', $settings->is_enabled ? 'Settings saved. Learners can order now.' : 'Settings saved. Ordering is off for learners.');
    }

    /** Learner search for the pay-later list (this branch only). */
    public function learnerSearch(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }
        $learners = Learner::where('library_id', getLibraryId())->where('branch_id', getCurrentBranch())
            ->whereNull('deleted_at')
            ->where(fn ($w) => $w->where('name', 'like', '%'.$q.'%')->orWhere('learner_no', 'like', '%'.$q.'%')->orWhere('seat_no', $q))
            ->orderBy('name')->limit(10)->get(['id', 'name', 'learner_no', 'seat_no']);

        return response()->json($learners);
    }

    // ---- cafeteria tables -------------------------------------------------------------------

    public function tableStore(Request $request)
    {
        $branchId = (int) getCurrentBranch();
        $data = $request->validate([
            'labels' => 'required|string|max:500',
        ]);
        // "1-10" or "T1, T2, Window" — ranges and lists both work
        $labels = [];
        foreach (preg_split('/\s*,\s*/', trim($data['labels'])) as $part) {
            if (preg_match('/^(\d{1,3})\s*-\s*(\d{1,3})$/', $part, $m) && (int) $m[1] <= (int) $m[2] && (int) $m[2] - (int) $m[1] < 100) {
                foreach (range((int) $m[1], (int) $m[2]) as $n) {
                    $labels[] = (string) $n;
                }
            } elseif ($part !== '') {
                $labels[] = mb_substr($part, 0, 30);
            }
        }
        $labels = array_values(array_unique($labels));
        if (! $labels) {
            throw ValidationException::withMessages(['labels' => 'Enter table numbers, e.g. 1-10 or T1, T2.']);
        }

        $have = OrderTable::where('branch_id', $branchId)->pluck('label')->map(fn ($l) => mb_strtolower($l))->all();
        $next = (int) OrderTable::where('branch_id', $branchId)->max('sort_order');
        $added = 0;
        foreach ($labels as $label) {
            if (in_array(mb_strtolower($label), $have, true)) {
                continue;
            }
            OrderTable::create(['library_id' => getLibraryId(), 'branch_id' => $branchId, 'label' => $label, 'sort_order' => ++$next]);
            $added++;
        }

        return back()->with('success', $added ? $added.' table'.($added > 1 ? 's' : '').' added.' : 'Those tables are already there.');
    }

    public function tableToggle(int $id)
    {
        $table = OrderTable::where('library_id', getLibraryId())->where('branch_id', getCurrentBranch())->findOrFail($id);
        $table->update(['is_active' => ! $table->is_active]);

        return back()->with('success', 'Table '.$table->label.($table->is_active ? ' is open.' : ' is closed.'));
    }

    public function tableDestroy(int $id)
    {
        OrderTable::where('library_id', getLibraryId())->where('branch_id', getCurrentBranch())->findOrFail($id)->delete();

        return back()->with('success', 'Table removed.');
    }
}
