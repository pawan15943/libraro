<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Ordering\OrderBoardController;
use App\Models\Learner;
use App\Models\Ordering\Order;
use App\Services\Ordering\OrderConflictException;
use App\Services\Ordering\OrderPaymentService;
use App\Services\Ordering\OrderReplacementService;
use App\Services\Ordering\OrderStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Library / staff app → Order Board (docs/API.md "Order board (staff)"). Same services and rules as
 * the panel board; orders are limited to the signed-in library and its current branch.
 */
class OrderBoardApiController extends Controller
{
    public function __construct(
        private OrderStatusService $statuses,
        private OrderPaymentService $payments,
        private OrderReplacementService $replacements,
    ) {}

    private function order(int $id): ?Order
    {
        return Order::withoutGlobalScopes()->where('library_id', getLibraryId())->where('branch_id', getCurrentBranch())->find($id);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['status' => false, 'message' => 'Order not found.'], 404);
    }

    private function run(callable $fn): JsonResponse
    {
        try {
            $order = $fn();
        } catch (OrderConflictException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage(), 'data' => $this->detail($e->order->fresh())], 409);
        } catch (ValidationException $e) {
            return response()->json(['status' => false, 'message' => collect($e->errors())->flatten()->first(), 'errors' => $e->errors()], 422);
        }

        return response()->json(['status' => true, 'message' => 'Order #'.Str::afterLast($order->order_no, '-').' → '.$order->statusLabel().' · '.$order->paymentLabel().'.', 'data' => $this->detail($order->fresh())]);
    }

    /** Same payload as the panel board feed (orders with allowed actions, counts, attention). */
    public function feed(): JsonResponse
    {
        $feed = app(OrderBoardController::class)->feed()->getData(true);

        return response()->json(['status' => true, 'message' => 'Order board fetched successfully.', 'data' => $feed]);
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->order($id);
        if (! $order) {
            return $this->notFound();
        }
        $order = $this->statuses->markSeen($order);

        return response()->json(['status' => true, 'message' => 'Order fetched successfully.', 'data' => $this->detail($order)]);
    }

    public function act(Request $request, int $id): JsonResponse
    {
        $order = $this->order($id);
        if (! $order) {
            return $this->notFound();
        }
        $data = $request->validate(['action' => 'required|string|max:20', 'v' => 'nullable|integer', 'reason' => 'nullable|string|max:255']);

        return $this->run(fn () => $this->statuses->apply($order, $data['action'], isset($data['v']) ? (int) $data['v'] : null, $data['reason'] ?? null));
    }

    public function unavailable(Request $request, int $id): JsonResponse
    {
        $order = $this->order($id);
        if (! $order) {
            return $this->notFound();
        }
        $data = $request->validate(['line_id' => 'required|integer', 'item_id' => 'nullable|integer', 'qty' => 'nullable|integer|min:1|max:50', 'keep_in_stock' => 'nullable|boolean']);

        return $this->run(fn () => $this->replacements->markUnavailable($order, (int) $data['line_id'], ! empty($data['item_id']) ? (int) $data['item_id'] : null,
            isset($data['qty']) ? (int) $data['qty'] : null, ! $request->boolean('keep_in_stock')));
    }

    public function removeLine(int $id, int $lineId): JsonResponse
    {
        $order = $this->order($id);

        return $order ? $this->run(fn () => $this->replacements->removeLine($order, $lineId)) : $this->notFound();
    }

    public function reviewProof(Request $request, int $id, int $paymentId): JsonResponse
    {
        $order = $this->order($id);
        if (! $order) {
            return $this->notFound();
        }
        $data = $request->validate(['decision' => 'required|in:approve,reject', 'note' => 'nullable|string|max:255']);

        return $this->run(fn () => $this->payments->reviewProof($order, $paymentId, $data['decision'] === 'approve', $data['note'] ?? null));
    }

    public function recordPayment(Request $request, int $id): JsonResponse
    {
        $order = $this->order($id);
        if (! $order) {
            return $this->notFound();
        }
        $data = $request->validate(['amount' => 'required|numeric|min:0.01|max:99999', 'method' => 'required|string|max:20', 'ref' => 'nullable|string|max:100', 'note' => 'nullable|string|max:255']);

        return $this->run(fn () => $this->payments->recordPayment($order, (float) $data['amount'], $data['method'], $data['ref'] ?? null, $data['note'] ?? null));
    }

    public function recordRefund(Request $request, int $id): JsonResponse
    {
        $order = $this->order($id);
        if (! $order) {
            return $this->notFound();
        }
        $data = $request->validate(['amount' => 'required|numeric|min:0.01|max:99999', 'method' => 'required|string|max:20', 'ref' => 'nullable|string|max:100', 'note' => 'nullable|string|max:255']);

        return $this->run(fn () => $this->payments->recordRefund($order, (float) $data['amount'], $data['method'], $data['ref'] ?? null, $data['note'] ?? null));
    }

    private function detail(Order $o): array
    {
        $o->loadMissing(['lines', 'payments', 'replacements', 'activities']);
        $learner = Learner::withoutGlobalScopes()->find($o->learner_id);

        return [
            'id' => $o->id,
            'order_no' => $o->order_no,
            'short_no' => Str::afterLast($o->order_no, '-'),
            'v' => (int) $o->version,
            'status' => $o->status,
            'status_label' => $o->statusLabel(),
            'mode' => $o->mode,
            'location' => $o->locationLabel(),
            'learner' => $learner ? ['id' => $learner->id, 'name' => $learner->name, 'learner_no' => $learner->learner_no, 'mobile' => $learner->mobile] : null,
            'note' => $o->note,
            'allow_partial' => (bool) $o->allow_partial,
            'payment_method' => $o->payment_method,
            'payment_status' => $o->payment_status,
            'payment_label' => $o->paymentLabel(),
            'subtotal' => (float) $o->subtotal,
            'delivery_charge' => (float) $o->delivery_charge,
            'total' => (float) $o->total,
            'paid_amount' => (float) $o->paid_amount,
            'refunded_amount' => (float) $o->refunded_amount,
            'due_amount' => $o->dueAmount(),
            'refundable' => $this->payments->refundable($o),
            'created_at' => $o->created_at?->toIso8601String(),
            'estimated_ready_at' => $o->estimated_ready_at?->toIso8601String(),
            'reject_reason' => $o->reject_reason,
            'cancel_reason' => $o->cancel_reason,
            'items' => $o->lines->map(fn ($l) => [
                'id' => $l->id, 'name' => $l->item_name, 'category' => $l->category_name, 'qty' => (int) $l->quantity,
                'unit_price' => (float) $l->unit_price, 'line_total' => (float) $l->line_total, 'status' => $l->fulfilment_status,
                'options' => $l->options, 'file_name' => $l->upload_name,
                'file_url' => $l->upload_path ? route('orders.file', [$o->id, $l->id]) : null,
            ])->values(),
            'replacements' => $o->replacements->map(fn ($r) => [
                'id' => $r->id, 'line_id' => $r->order_line_id, 'suggested_name' => $r->suggested_name, 'suggested_price' => $r->suggested_price !== null ? (float) $r->suggested_price : null,
                'quantity' => (int) $r->quantity, 'price_diff' => (float) $r->price_diff, 'status' => $r->status, 'deadline_at' => $r->deadline_at?->toIso8601String(),
            ])->values(),
            'payments' => $o->payments->map(fn ($p) => [
                'id' => $p->id, 'kind' => $p->kind, 'method' => $p->method, 'amount' => (float) $p->amount, 'status' => $p->status,
                'txn_ref' => $p->txn_ref, 'has_screenshot' => (bool) $p->screenshot, 'created_at' => $p->created_at?->toIso8601String(),
            ])->values(),
            'actions' => $this->statuses->staffActions($o),
            'history' => $o->activities->sortByDesc('id')->map(fn ($a) => ['action' => $a->action, 'by' => $a->actor_type, 'from' => $a->from_status, 'to' => $a->to_status, 'meta' => $a->meta, 'at' => $a->created_at?->toIso8601String()])->values(),
        ];
    }
}
