<?php

namespace App\Http\Controllers\Api\V1\Learner;

use App\Http\Controllers\Controller;
use App\Models\Learner;
use App\Models\Ordering\Order;
use App\Models\Ordering\OrderLine;
use App\Services\Ordering\LearnerOrderingService;
use App\Services\Ordering\OrderPaymentService;
use App\Services\Ordering\OrderPlacementService;
use App\Services\Ordering\OrderReplacementService;
use App\Services\Ordering\OrderStatusService;
use App\Services\Ordering\PriceChangedException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Learner app → Ordering & Services (docs/API.md "Learner ordering"). Same services and rules as
 * the learner web portal (LearnerOrderController); every order is limited to the signed-in learner.
 */
class LearnerOrderingController extends Controller
{
    public function __construct(
        private LearnerOrderingService $ordering,
        private OrderPlacementService $placement,
        private OrderPaymentService $payments,
        private OrderStatusService $statuses,
        private OrderReplacementService $replacements,
    ) {}

    private function learner(): ?Learner
    {
        return auth('learner_api')->user();
    }

    private function unauth(): JsonResponse
    {
        return response()->json(['status' => false, 'message' => 'Unauthenticated learner session.'], 401);
    }

    private function fail(ValidationException $e): JsonResponse
    {
        return response()->json(['status' => false, 'message' => collect($e->errors())->flatten()->first(), 'errors' => $e->errors()], 422);
    }

    private function mine(Learner $learner, string $orderNo): ?Order
    {
        return Order::withoutGlobalScopes()->where('learner_id', $learner->id)->where('order_no', $orderNo)->first();
    }

    /** Ordering screen: can the learner order, where to, how to pay, the menu, orders in progress. */
    public function home(): JsonResponse
    {
        $learner = $this->learner();
        if (! $learner) {
            return $this->unauth();
        }
        $ctx = $this->ordering->context($learner);
        $s = $ctx['settings'];
        $menu = $ctx['enabled'] ? $this->ordering->menuData($this->ordering->menu($learner)) : [];
        $current = Order::withoutGlobalScopes()->where('learner_id', $learner->id)->whereIn('status', Order::OPEN)->orderByDesc('id')->limit(5)->get();

        return response()->json([
            'status' => true,
            'message' => 'Ordering data fetched successfully.',
            'data' => [
                'enabled' => $ctx['enabled'],
                'eligible' => $ctx['eligible'],
                'reason' => $ctx['reason'],
                'open_now' => $ctx['open'],
                'opens_at' => $ctx['opens_at'],
                'closes_at' => $ctx['closes_at'],
                'prep_minutes' => (int) $s->prep_minutes,
                'delivery_charge' => (float) $s->delivery_charge,
                'seat' => $ctx['seat'],
                'seat_editable' => $ctx['seat_editable'],
                'modes' => $ctx['modes'],
                'tables' => $ctx['tables']->map(fn ($t) => ['id' => $t->id, 'label' => $t->label])->values(),
                'payments' => $ctx['payments'],
                'pay_later_limit_left' => $ctx['pay_later_room'],
                'limit_reached' => $ctx['limit_reached'],
                'partial_fulfilment' => (bool) $s->partial_fulfilment,
                'upload_types' => $s->uploadExtensions(),
                'upload_max_mb' => (int) $s->upload_max_mb,
                'menu' => $menu,
                'current_orders' => $current->map(fn ($o) => $this->summary($o))->values(),
            ],
        ]);
    }

    public function place(Request $request): JsonResponse
    {
        $learner = $this->learner();
        if (! $learner) {
            return $this->unauth();
        }
        try {
            $request->validate([
                'mode' => 'required|string|max:10',
                'payment_method' => 'required|string|max:10',
                'idempotency_key' => 'required|string|max:64',
                'lines' => 'required|array|max:'.OrderPlacementService::MAX_LINES,
                'lines.*.item_id' => 'required|integer',
                'lines.*.qty' => 'required|integer|min:1|max:'.OrderPlacementService::MAX_QTY,
                'lines.*.copies' => 'nullable|integer|min:1|max:50',
                'lines.*.sides' => 'nullable|in:single,double',
                'note' => 'nullable|string|max:500',
                'seat_no' => 'nullable|string|max:20',
                'order_table_id' => 'nullable|integer',
                'expected_total' => 'nullable|numeric',
                'allow_partial' => 'nullable|boolean',
            ]);
            $files = [];
            foreach ((array) $request->file('lines', []) as $i => $f) {
                $files[(int) $i] = $f['file'] ?? null;
            }
            $order = $this->placement->place($learner, $request->all() + ['allow_partial' => $request->boolean('allow_partial', true)], $files);
        } catch (PriceChangedException $e) {
            return response()->json(['status' => false, 'code' => 'price_changed', 'message' => $e->getMessage(), 'data' => ['total' => $e->total, 'prices' => $e->prices]], 409);
        } catch (ValidationException $e) {
            return $this->fail($e);
        }

        return response()->json(['status' => true, 'message' => 'Order placed.', 'data' => $this->detail($order->fresh())]);
    }

    public function index(Request $request): JsonResponse
    {
        $learner = $this->learner();
        if (! $learner) {
            return $this->unauth();
        }
        $limit = max(1, min(50, (int) $request->input('limit', 20)));
        $page = Order::withoutGlobalScopes()->where('learner_id', $learner->id)->orderByDesc('id')->paginate($limit, ['*'], 'page', max(1, (int) $request->input('page', 1)));

        return response()->json([
            'status' => true,
            'message' => 'Orders fetched successfully.',
            'data' => [
                'orders' => collect($page->items())->map(fn ($o) => $this->summary($o))->values(),
                'pagination' => ['page' => $page->currentPage(), 'limit' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()],
                'unpaid_pay_later' => $this->ordering->unpaid($learner),
            ],
        ]);
    }

    public function show(string $orderNo): JsonResponse
    {
        $learner = $this->learner();
        if (! $learner) {
            return $this->unauth();
        }
        $order = $this->mine($learner, $orderNo);
        if (! $order) {
            return response()->json(['status' => false, 'message' => 'Order not found.'], 404);
        }

        return response()->json(['status' => true, 'message' => 'Order fetched successfully.', 'data' => $this->detail($order)]);
    }

    public function pay(Request $request, string $orderNo): JsonResponse
    {
        $learner = $this->learner();
        if (! $learner) {
            return $this->unauth();
        }
        $order = $this->mine($learner, $orderNo);
        if (! $order) {
            return response()->json(['status' => false, 'message' => 'Order not found.'], 404);
        }
        try {
            $request->validate([
                'screenshot' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
                'utr' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9\s]+$/'],
            ]);
            $this->payments->submitProof($order, $request->file('screenshot'), $request->input('utr'));
        } catch (ValidationException $e) {
            return $this->fail($e);
        }

        return response()->json(['status' => true, 'message' => 'Payment proof sent. The library will confirm it shortly.', 'data' => $this->detail($order->fresh())]);
    }

    public function replacements(Request $request, string $orderNo): JsonResponse
    {
        $learner = $this->learner();
        if (! $learner) {
            return $this->unauth();
        }
        $order = $this->mine($learner, $orderNo);
        if (! $order) {
            return response()->json(['status' => false, 'message' => 'Order not found.'], 404);
        }
        try {
            $data = $request->validate(['choices' => 'required|array', 'choices.*' => 'required|in:accept,remove,other']);
            $order = $this->replacements->respond($learner, $order, array_map('strval', $data['choices']));
        } catch (ValidationException $e) {
            return $this->fail($e);
        }

        return response()->json(['status' => true, 'message' => 'Thanks! Your choices are saved.', 'data' => $this->detail($order->fresh()) + ['notes' => $this->replacements->lastNotes]]);
    }

    public function cancel(Request $request, string $orderNo): JsonResponse
    {
        $learner = $this->learner();
        if (! $learner) {
            return $this->unauth();
        }
        $order = $this->mine($learner, $orderNo);
        if (! $order) {
            return response()->json(['status' => false, 'message' => 'Order not found.'], 404);
        }
        try {
            $data = $request->validate(['reason' => 'nullable|string|max:255']);
            $order = $this->statuses->cancelByLearner($learner, $order, $data['reason'] ?? null);
        } catch (ValidationException $e) {
            return $this->fail($e);
        }

        return response()->json(['status' => true, 'message' => 'Order cancelled.', 'data' => $this->detail($order->fresh())]);
    }

    // ---- shapes ---------------------------------------------------------------------------------

    private function summary(Order $o): array
    {
        return [
            'order_no' => $o->order_no,
            'short_no' => Str::afterLast($o->order_no, '-'),
            'status' => $o->status,
            'status_label' => $o->statusLabel(),
            'payment_method' => $o->payment_method,
            'payment_status' => $o->payment_status,
            'payment_label' => $o->paymentLabel(),
            'mode' => $o->mode,
            'location' => $o->locationLabel(),
            'total' => (float) $o->total,
            'due_amount' => $o->dueAmount(),
            'items_count' => $o->lines()->whereNotIn('fulfilment_status', [OrderLine::REMOVED, OrderLine::REPLACED])->count(),
            'created_at' => $o->created_at?->toIso8601String(),
        ];
    }

    private function detail(Order $o): array
    {
        $o->loadMissing(['lines', 'replacements.line']);
        $settings = $this->ordering->settings($this->learner());
        $canPay = $this->payments->canSendProof($o);
        $upi = $canPay ? $this->payments->upi($o) : null;

        return $this->summary($o) + [
            'seat_no' => $o->seat_no,
            'table_label' => $o->table_label,
            'subtotal' => (float) $o->subtotal,
            'delivery_charge' => (float) $o->delivery_charge,
            'paid_amount' => (float) $o->paid_amount,
            'refunded_amount' => (float) $o->refunded_amount,
            'note' => $o->note,
            'allow_partial' => (bool) $o->allow_partial,
            'estimated_ready_at' => $o->estimated_ready_at?->toIso8601String(),
            'reject_reason' => $o->reject_reason,
            'cancel_reason' => $o->cancel_reason,
            'items' => $o->lines->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->item_name,
                'category' => $l->category_name,
                'qty' => (int) $l->quantity,
                'unit_price' => (float) $l->unit_price,
                'line_total' => (float) $l->line_total,
                'status' => $l->fulfilment_status,
                'replaced_line_id' => $l->replaced_line_id,
                'options' => $l->options,
                'file_name' => $l->upload_name,
            ])->values(),
            'replacements_pending' => $o->replacements->where('status', 'pending')->map(fn ($r) => [
                'id' => $r->id,
                'line_id' => $r->order_line_id,
                'item' => $r->line?->item_name,
                'item_total' => (float) ($r->line?->line_total ?? 0),
                'suggested_item_id' => $r->suggested_item_id,
                'suggested_name' => $r->suggested_name,
                'suggested_price' => $r->suggested_price !== null ? (float) $r->suggested_price : null,
                'quantity' => (int) $r->quantity,
                'price_diff' => (float) $r->price_diff,
                'deadline_at' => $r->deadline_at?->toIso8601String(),
                'choices' => array_values(array_filter([
                    $r->suggested_item_id ? 'accept' : null,
                    'remove',
                    $r->suggested_item_id && $settings->replacement_multiple ? 'other' : null,
                ])),
            ])->values(),
            'can_cancel' => $this->ordering->canCancel($o, $settings),
            'can_pay_online' => $canPay && $upi !== null,
            'upi' => $upi ? ['upi_id' => $upi['upi_id'], 'payee' => $upi['payee'], 'amount' => $upi['amount'], 'link' => $upi['link']] : null,
        ];
    }
}
