<?php

namespace App\Http\Controllers\Ordering;

use App\Http\Controllers\Controller;
use App\Models\Learner;
use App\Models\LibraryUser;
use App\Models\Ordering\Order;
use App\Models\Ordering\OrderLine;
use App\Models\Ordering\OrderPayment;
use App\Models\Ordering\OrderReplacement;
use App\Services\Ordering\OrderConflictException;
use App\Services\Ordering\OrderingSetupService;
use App\Services\Ordering\OrderPaymentService;
use App\Services\Ordering\OrderReplacementService;
use App\Services\Ordering\OrderStatusService;
use App\Services\Ordering\OrderSweepService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Library panel → Orders → Order Board (spec §6): live queue for canteen staff, order page with
 * status steps, payments (verify UPI proof, take money at the counter, refunds), files, history.
 */
class OrderBoardController extends Controller
{
    /** Board tabs → order statuses (payments / done have their own rules). */
    public const TABS = [
        'new' => ['New', [Order::NEW, Order::ATTENTION, Order::UNDER_REVIEW, Order::REPLACEMENT_PENDING]],
        'kitchen' => ['Preparing', [Order::CONFIRMED, Order::PREPARING]],
        'handover' => ['Ready / on the way', [Order::READY, Order::OUT_FOR_DELIVERY, Order::DELIVERY_ATTEMPTED]],
        'payments' => ['Payments', []],
        'done' => ['Done today', []],
    ];

    public function __construct(
        private OrderStatusService $statuses,
        private OrderPaymentService $payments,
        private OrderingSetupService $setup,
        private OrderReplacementService $replacements,
        private OrderSweepService $sweep,
    ) {}

    private function order(int $id): Order
    {
        return Order::withoutGlobalScopes()->where('library_id', getLibraryId())->where('branch_id', getCurrentBranch())->findOrFail($id);
    }

    public function index()
    {
        $settings = $this->setup->current();

        return view('ordering.library.board', [
            'settings' => $settings,
            'tabs' => collect(self::TABS)->map(fn ($t) => $t[0])->all(),
        ]);
    }

    /** JSON the board polls every few seconds. */
    public function feed()
    {
        $branchId = (int) getCurrentBranch();
        $this->sweep->sweepSoon($branchId); // reminders / escalation / timeouts even without a cron
        $base = Order::withoutGlobalScopes()->where('library_id', getLibraryId())->where('branch_id', $branchId);

        $open = (clone $base)->whereIn('status', Order::OPEN)->orderBy('id')->limit(200)->get();
        $closedToday = (clone $base)->whereIn('status', [Order::DELIVERED, Order::COLLECTED, Order::COMPLETED, Order::CANCELLED, Order::REJECTED])
            ->where('updated_at', '>=', today())->orderByDesc('updated_at')->limit(100)->get();
        $money = (clone $base)->whereNotIn('id', $open->pluck('id'))
            ->where(fn ($q) => $q->where(fn ($w) => $w->whereNotIn('status', [Order::CANCELLED, Order::REJECTED])
                ->whereIn('payment_status', [Order::PAY_PENDING, Order::PAY_PROCESSING, Order::PAY_FAILED, Order::PAY_PARTIAL]))
                ->orWhereIn('payment_status', [Order::PAY_REFUND_PENDING, Order::PAY_PROCESSING, Order::PAY_PARTIALLY_REFUNDED]))
            ->orderByDesc('id')->limit(100)->get();

        $all = $open->concat($closedToday)->concat($money)->unique('id')->values();
        $all->load('lines');
        $waitingIds = OrderReplacement::whereIn('order_id', $all->pluck('id'))->where('status', 'pending')->pluck('order_id')->flip();
        $learners = Learner::withoutGlobalScopes()->whereIn('id', $all->pluck('learner_id')->unique())->get(['id', 'name', 'learner_no'])->keyBy('id');

        $rows = $all->map(function (Order $o) use ($learners, $waitingIds) {
            $tab = collect(self::TABS)->search(fn ($t) => in_array($o->status, $t[1], true));
            $needsMoney = in_array($o->payment_status, [Order::PAY_PROCESSING, Order::PAY_REFUND_PENDING, Order::PAY_PARTIALLY_REFUNDED], true)
                || (! in_array($o->status, [Order::CANCELLED, Order::REJECTED], true) && $o->dueAmount() > 0 && ! $o->isOpen());
            $learner = $learners->get($o->learner_id);

            return [
                'id' => $o->id,
                'no' => $o->order_no,
                'short' => Str::afterLast($o->order_no, '-'),
                'v' => (int) $o->version,
                'tab' => $tab ?: ($o->isOpen() ? 'new' : 'done'),
                'money' => $needsMoney,
                'done_today' => ! $o->isOpen() && $o->updated_at >= today(),
                'status' => $o->status,
                'status_label' => $o->statusLabel(),
                'mode' => $o->mode,
                'where' => $o->locationLabel(),
                'learner' => $learner ? $learner->name : 'Learner',
                'learner_no' => $learner->learner_no ?? null,
                'pay_method' => $o->payment_method,
                'pay_status' => $o->payment_status,
                'pay_label' => $o->paymentLabel(),
                'total' => (float) $o->total,
                'due' => $o->dueAmount(),
                'note' => $o->note,
                'waiting' => $waitingIds->has($o->id), // learner still has to answer a replacement
                'items' => $o->lines->whereNotIn('fulfilment_status', [OrderLine::REMOVED, OrderLine::REPLACED])
                    ->map(fn ($l) => ['q' => $l->quantity, 'n' => $l->item_name, 'f' => (bool) $l->upload_path, 's' => $l->fulfilment_status])->values(),
                'at' => $o->created_at->toIso8601String(),
                'mins' => (int) $o->created_at->diffInMinutes(now()),
                'actions' => $this->statuses->staffActions($o),
                'url' => route('orders.show', $o->id),
            ];
        });

        $counts = [];
        foreach (array_keys(self::TABS) as $t) {
            $counts[$t] = $rows->filter(fn ($r) => $t === 'payments' ? $r['money'] : ($t === 'done' ? $r['done_today'] : $r['tab'] === $t && ! in_array($r['status'], Order::CLOSED, true)))->count();
        }

        return response()->json([
            'orders' => $rows,
            'counts' => $counts,
            'attention' => $rows->where('status', Order::ATTENTION)->count(),
            'now' => now()->toIso8601String(),
        ]);
    }

    public function show(int $id)
    {
        $order = $this->statuses->markSeen($this->order($id));
        $order->load(['lines', 'payments', 'activities', 'replacements']);
        $learner = Learner::withoutGlobalScopes()->find($order->learner_id);

        $staffNames = LibraryUser::whereIn('id', $order->activities->where('actor_type', 'library_user')->pluck('actor_id')->unique())->pluck('name', 'id');

        return view('ordering.library.order', [
            'order' => $order,
            'learner' => $learner,
            'actions' => $this->statuses->staffActions($order),
            'refundable' => $this->payments->refundable($order),
            'methods' => OrderPaymentService::COUNTER_METHODS,
            'staffNames' => $staffNames,
            'isOwner' => OrderStatusService::isOwner(),
            'suggestions' => in_array($order->status, OrderReplacementService::EDITABLE, true)
                ? $order->lines->mapWithKeys(fn ($l) => [$l->id => $this->replacements->suggestions($order, $l)])
                : collect(),
            'settings' => $this->setup->current(),
        ]);
    }

    /** Status step from the board (JSON) or the order page (form). */
    public function act(Request $request, int $id)
    {
        $data = $request->validate([
            'action' => 'required|string|max:20',
            'v' => 'nullable|integer',
            'reason' => 'nullable|string|max:255',
        ]);
        $order = $this->order($id);

        try {
            $order = $this->statuses->apply($order, $data['action'], isset($data['v']) ? (int) $data['v'] : null, $data['reason'] ?? null);
        } catch (OrderConflictException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'status' => $e->order->status], 409);
            }

            return redirect()->route('orders.show', $id)->with('error', $e->getMessage());
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
            }
            throw $e;
        }

        $msg = 'Order #'.Str::afterLast($order->order_no, '-').' → '.$order->statusLabel().'.';

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $msg, 'status' => $order->status, 'v' => (int) $order->version])
            : redirect()->route('orders.show', $id)->with('success', $msg);
    }

    /** An item ran out: mark it, optionally suggest another (the learner must accept). */
    public function unavailable(Request $request, int $id)
    {
        $data = $request->validate([
            'line_id' => 'required|integer',
            'item_id' => 'nullable|integer',
            'qty' => 'nullable|integer|min:1|max:50',
            'keep_in_stock' => 'nullable|boolean',
        ]);
        $this->replacements->markUnavailable($this->order($id), (int) $data['line_id'], ! empty($data['item_id']) ? (int) $data['item_id'] : null,
            isset($data['qty']) ? (int) $data['qty'] : null, ! $request->boolean('keep_in_stock'));

        return back()->with('success', ! empty($data['item_id']) ? 'Suggestion sent to the learner.' : 'The learner is asked to remove the item.');
    }

    /** Staff take an unavailable item off the order (e.g. the learner did not answer). */
    public function removeLine(int $id, int $lineId)
    {
        $order = $this->replacements->removeLine($this->order($id), $lineId);

        return back()->with('success', 'Item removed. New total ₹'.number_format((float) $order->total, 2).'.');
    }

    public function reviewProof(Request $request, int $id, int $paymentId)
    {
        $data = $request->validate(['decision' => 'required|in:approve,reject', 'note' => 'nullable|string|max:255']);
        $order = $this->payments->reviewProof($this->order($id), $paymentId, $data['decision'] === 'approve', $data['note'] ?? null);

        return back()->with('success', $data['decision'] === 'approve' ? 'Payment approved. Status: '.$order->paymentLabel().'.' : 'Payment proof rejected. The learner can pay again.');
    }

    public function recordPayment(Request $request, int $id)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:99999',
            'method' => 'required|string|max:20',
            'ref' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:255',
        ]);
        $order = $this->payments->recordPayment($this->order($id), (float) $data['amount'], $data['method'], $data['ref'] ?? null, $data['note'] ?? null);

        return back()->with('success', 'Payment recorded. Status: '.$order->paymentLabel().'.');
    }

    public function recordRefund(Request $request, int $id)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:99999',
            'method' => 'required|string|max:20',
            'ref' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:255',
        ]);
        $order = $this->payments->recordRefund($this->order($id), (float) $data['amount'], $data['method'], $data['ref'] ?? null, $data['note'] ?? null);

        return back()->with('success', 'Refund recorded. Status: '.$order->paymentLabel().'.');
    }

    /** The learner's print file (private storage). */
    public function file(int $id, int $lineId)
    {
        $line = OrderLine::where('order_id', $this->order($id)->id)->findOrFail($lineId);
        abort_unless($line->upload_path && Storage::disk('local')->exists($line->upload_path), 404);

        return Storage::disk('local')->download($line->upload_path, $line->upload_name ?: basename($line->upload_path));
    }

    /** A payment screenshot (private storage), shown inline. */
    public function proof(int $id, int $paymentId)
    {
        $p = OrderPayment::withoutGlobalScopes()->where('order_id', $this->order($id)->id)->findOrFail($paymentId);
        abort_unless($p->screenshot && Storage::disk('local')->exists($p->screenshot), 404);

        return Storage::disk('local')->response($p->screenshot, null, ['Cache-Control' => 'private, max-age=600']);
    }
}
