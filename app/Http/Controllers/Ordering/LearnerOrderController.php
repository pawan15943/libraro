<?php

namespace App\Http\Controllers\Ordering;

use App\Http\Controllers\Controller;
use App\Models\Learner;
use App\Models\Ordering\Order;
use App\Services\Ordering\LearnerOrderingService;
use App\Services\Ordering\OrderPaymentService;
use App\Services\Ordering\OrderPlacementService;
use App\Services\Ordering\OrderReplacementService;
use App\Services\Ordering\OrderStatusService;
use App\Services\Ordering\PriceChangedException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Learner portal → Order: menu, combined cart, checkout, tracking, history (spec §4).
 * Every query is limited to the signed-in learner (the branch scope is off for the learner guard).
 */
class LearnerOrderController extends Controller
{
    public function __construct(
        private LearnerOrderingService $ordering,
        private OrderPlacementService $placement,
        private OrderPaymentService $payments,
        private OrderStatusService $statuses,
        private OrderReplacementService $replacements,
    ) {}

    private function learner(): Learner
    {
        return Auth::guard('learner')->user();
    }

    private function mine(string $orderNo): Order
    {
        return Order::withoutGlobalScopes()->where('learner_id', $this->learner()->id)->where('order_no', $orderNo)->firstOrFail();
    }

    public function index()
    {
        $learner = $this->learner();
        $ctx = $this->ordering->context($learner);
        $menu = $ctx['enabled'] ? $this->ordering->menu($learner) : collect();

        $current = Order::withoutGlobalScopes()->where('learner_id', $learner->id)
            ->whereIn('status', Order::OPEN)->orderByDesc('id')->limit(5)->get();

        return view('ordering.learner.index', [
            'ctx' => $ctx,
            'menu' => $menu,
            'menuData' => $this->ordering->menuData($menu),
            'current' => $current,
            'cartKey' => 'lr-cart:'.$learner->id.':'.$learner->branch_id,
        ]);
    }

    public function place(Request $request)
    {
        $request->validate([
            'mode' => 'required|string|max:10',
            'payment_method' => 'required|string|max:10',
            'idempotency_key' => 'required|string|max:64',
            'lines' => 'required|array|max:'.OrderPlacementService::MAX_LINES,
            'lines.*.item_id' => 'required|integer',
            'lines.*.qty' => 'required|integer|min:1|max:'.OrderPlacementService::MAX_QTY,
            'note' => 'nullable|string|max:500',
            'seat_no' => 'nullable|string|max:20',
            'order_table_id' => 'nullable|integer',
            'expected_total' => 'nullable|numeric',
        ], [
            'lines.required' => 'Your cart is empty.',
        ]);

        $files = [];
        foreach ((array) $request->file('lines', []) as $i => $f) {
            $files[(int) $i] = $f['file'] ?? null;
        }

        try {
            $order = $this->placement->place($this->learner(), $request->all() + ['allow_partial' => $request->boolean('allow_partial', true)], $files);
        } catch (PriceChangedException $e) {
            return response()->json(['success' => false, 'code' => 'price_changed', 'message' => $e->getMessage(),
                'total' => $e->total, 'prices' => $e->prices], 409);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first(), 'errors' => $e->errors()], 422);
        }

        return response()->json([
            'success' => true,
            'order_no' => $order->order_no,
            'redirect' => route('learner.orders.show', $order->order_no),
        ]);
    }

    public function history()
    {
        $orders = Order::withoutGlobalScopes()->where('learner_id', $this->learner()->id)
            ->withCount('lines')->orderByDesc('id')->paginate(20);

        $totals = Order::withoutGlobalScopes()->where('learner_id', $this->learner()->id)
            ->whereNotIn('status', [Order::CANCELLED, Order::REJECTED])
            ->selectRaw('COUNT(*) AS orders, COALESCE(SUM(total), 0) AS spent, COALESCE(SUM(GREATEST(total - paid_amount + refunded_amount, 0)), 0) AS due')
            ->first();

        return view('ordering.learner.history', compact('orders', 'totals'));
    }

    public function show(string $orderNo)
    {
        $order = $this->mine($orderNo)->load(['lines', 'payments']);
        $settings = $this->ordering->settings($this->learner());

        return view('ordering.learner.show', [
            'order' => $order,
            'canCancel' => $this->ordering->canCancel($order, $settings),
            'canPay' => $this->payments->canSendProof($order),
            'upi' => $this->payments->canSendProof($order) ? $this->payments->upi($order) : null,
            'pending' => $order->replacements()->where('status', 'pending')->with('line')->get(),
            'allowOther' => (bool) $settings->replacement_multiple,
        ]);
    }

    /** Small JSON the tracking page polls; the page reloads when `v` changes. */
    public function status(string $orderNo)
    {
        $order = $this->mine($orderNo);

        return response()->json([
            'status' => $order->status,
            'status_label' => $order->statusLabel(),
            'payment_status' => $order->payment_status,
            'payment_label' => $order->paymentLabel(),
            'v' => $order->version.'-'.optional($order->updated_at)->timestamp,
        ]);
    }

    public function pay(Request $request, string $orderNo)
    {
        $order = $this->mine($orderNo);
        $request->validate([
            'screenshot' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'utr' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9\s]+$/'],
        ], [
            'screenshot.required' => 'Attach the screenshot of your payment.',
            'utr.regex' => 'The UTR / reference has only letters and numbers.',
        ]);

        $this->payments->submitProof($order, $request->file('screenshot'), $request->input('utr'));

        return back()->with('success', 'Payment proof sent. The library will confirm it shortly.');
    }

    /** The learner answers every open replacement at once: accept | remove | other. */
    public function replacements(Request $request, string $orderNo)
    {
        $order = $this->mine($orderNo);
        $data = $request->validate(['choices' => 'required|array', 'choices.*' => 'required|in:accept,remove,other'], ['choices.required' => 'Choose what to do for every item that ran out.']);
        $order = $this->replacements->respond($this->learner(), $order, array_map('strval', $data['choices']));
        if ($this->replacements->lastNotes) {
            session()->flash('lo_notes', $this->replacements->lastNotes);
        }

        return redirect()->route('learner.orders.show', $order->order_no)->with('success', $order->status === Order::CANCELLED
            ? 'Order cancelled — none of the items were available.'
            : 'Thanks! New total ₹'.number_format((float) $order->total, 2).'.');
    }

    public function cancel(Request $request, string $orderNo)
    {
        $order = $this->mine($orderNo);
        $data = $request->validate(['reason' => 'nullable|string|max:255']);
        $this->statuses->cancelByLearner($this->learner(), $order, $data['reason'] ?? null);

        return redirect()->route('learner.orders.show', $order->order_no)->with('success', 'Order cancelled.');
    }
}
