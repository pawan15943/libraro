<?php

namespace App\Services\Ordering;

use App\Models\Learner;
use App\Models\Ordering\Order;
use App\Models\Ordering\OrderActivity;
use App\Models\Ordering\OrderLine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Order status changes (spec §6, §8). Every change locks the row, checks the order is still in a
 * state that allows the action (and, when the caller sends one, still at the version it saw), then
 * writes the activity log. So a double click, or two staff acting at once, never applies twice.
 */
class OrderStatusService
{
    /** Staff are still deciding: accept or reject from here. */
    public const INCOMING = [Order::NEW, Order::ATTENTION, Order::UNDER_REVIEW];

    /** Past this point only the owner may cancel (spec: "staff cancels after preparation starts → admin approval"). */
    public const STARTED = [Order::PREPARING, Order::READY, Order::OUT_FOR_DELIVERY, Order::DELIVERY_ATTEMPTED];

    /** action → [allowed from, to, activity name, button label] */
    public const MOVES = [
        'accept' => [self::INCOMING, Order::CONFIRMED, 'accepted', 'Accept'],
        'prepare' => [[Order::CONFIRMED], Order::PREPARING, 'preparing', 'Start preparing'],
        'ready' => [[Order::CONFIRMED, Order::PREPARING], Order::READY, 'ready', 'Ready for pickup'],
        'dispatch' => [[Order::CONFIRMED, Order::PREPARING, Order::DELIVERY_ATTEMPTED], Order::OUT_FOR_DELIVERY, 'out_for_delivery', 'Out for delivery'],
        'deliver' => [[Order::OUT_FOR_DELIVERY, Order::DELIVERY_ATTEMPTED], Order::DELIVERED, 'delivered', 'Delivered'],
        'attempted' => [[Order::OUT_FOR_DELIVERY], Order::DELIVERY_ATTEMPTED, 'delivery_attempted', 'Not at seat'],
        'collect' => [[Order::READY], Order::COLLECTED, 'collected', 'Collected'],
        'complete' => [[Order::DELIVERED, Order::COLLECTED], Order::COMPLETED, 'completed', 'Close order'],
    ];

    /** Owner correction of a wrong "delivered / collected" tap (spec §10E: admin correction with audit log). */
    private const UNDO = [Order::DELIVERED => Order::OUT_FOR_DELIVERY, Order::COLLECTED => Order::READY];

    public function __construct(private LearnerOrderingService $ordering) {}

    public static function isOwner(): bool
    {
        return Auth::guard('library')->check();
    }

    /**
     * Actions the signed-in library user can take on this order now, in display order.
     *
     * @return array<int, array{key: string, label: string, tone: string}>
     */
    public function staffActions(Order $order): array
    {
        $out = [];
        foreach (self::MOVES as $key => [$from, , , $label]) {
            if (! in_array($order->status, $from, true)) {
                continue;
            }
            // pickup orders become "ready", delivery orders go "out for delivery"
            if ($key === 'ready' && $order->mode !== 'counter') {
                continue;
            }
            if ($key === 'dispatch' && $order->mode === 'counter') {
                continue;
            }
            // close only once the money is settled (pay later stays "delivered" until paid)
            if ($key === 'complete' && $order->dueAmount() > 0) {
                continue;
            }
            $out[] = ['key' => $key, 'label' => $label, 'tone' => in_array($key, ['attempted'], true) ? 'ghost' : 'go'];
        }
        if (in_array($order->status, self::INCOMING, true)) {
            $out[] = ['key' => 'reject', 'label' => 'Reject', 'tone' => 'bad'];
        }
        if ($order->isOpen() && ! in_array($order->status, self::INCOMING, true) && $this->canStaffCancel($order)) {
            $out[] = ['key' => 'cancel', 'label' => 'Cancel order', 'tone' => 'bad'];
        }
        if (self::isOwner() && isset(self::UNDO[$order->status])) {
            $out[] = ['key' => 'undo', 'label' => 'Undo "'.$order->statusLabel().'"', 'tone' => 'ghost'];
        }

        return $out;
    }

    public function canStaffCancel(Order $order): bool
    {
        return $order->isOpen() && (self::isOwner() || ! in_array($order->status, self::STARTED, true));
    }

    /** Staff opened a new / waiting order: it is now "under review" (stops the reminders). */
    public function markSeen(Order $order): Order
    {
        if (! in_array($order->status, [Order::NEW, Order::ATTENTION], true)) {
            return $order;
        }

        return DB::transaction(function () use ($order) {
            $o = Order::withoutGlobalScopes()->lockForUpdate()->findOrFail($order->id);
            if (! in_array($o->status, [Order::NEW, Order::ATTENTION], true)) {
                return $o;
            }
            $from = $o->status;
            $o->update(['status' => Order::UNDER_REVIEW, 'reviewed_at' => now(), 'version' => $o->version + 1] + $this->handler($o));
            OrderActivity::log($o, 'opened', $from, Order::UNDER_REVIEW);

            return $o;
        });
    }

    /**
     * Run a staff action. $version = the version the staff member saw (null = do not check).
     *
     * @throws OrderConflictException when the order changed meanwhile or the action is no longer allowed
     */
    public function apply(Order $order, string $action, ?int $version = null, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $action, $version, $reason) {
            $o = Order::withoutGlobalScopes()->lockForUpdate()->findOrFail($order->id);
            if ($version !== null && $version !== (int) $o->version) {
                throw new OrderConflictException($o, 'Someone else just updated this order. It is now "'.$o->statusLabel().'".');
            }
            $allowed = array_column($this->staffActions($o), 'key');
            if (! in_array($action, $allowed, true)) {
                throw new OrderConflictException($o, $o->isOpen()
                    ? 'This step is not possible now. The order is "'.$o->statusLabel().'".'
                    : 'This order is already '.strtolower($o->statusLabel()).'.');
            }

            return match ($action) {
                'reject' => $this->close($o, Order::REJECTED, 'rejected', $this->reason($reason, 'reject')),
                'cancel' => $this->close($o, Order::CANCELLED, 'cancelled_by_staff', $this->reason($reason, 'cancel')),
                'undo' => $this->undo($o, $this->reason($reason, 'undo')),
                default => $this->move($o, $action),
            };
        });
    }

    private function move(Order $o, string $action): Order
    {
        [, $to, $event] = self::MOVES[$action];
        $from = $o->status;
        $now = now();
        $fields = ['status' => $to, 'version' => $o->version + 1];
        $stamp = [
            Order::CONFIRMED => 'confirmed_at', Order::PREPARING => 'preparing_at', Order::READY => 'ready_at',
            Order::OUT_FOR_DELIVERY => 'ready_at', Order::DELIVERED => 'delivered_at', Order::COLLECTED => 'delivered_at',
            Order::COMPLETED => 'completed_at',
        ][$to] ?? null;
        if ($stamp && ! ($stamp === 'ready_at' && $o->ready_at)) {
            $fields[$stamp] = $now;
        }
        if ($to === Order::CONFIRMED) {
            $fields += $this->handler($o, true);
            $fields['reviewed_at'] = $o->reviewed_at ?: $now;
        }
        $o->update($fields);

        if (in_array($to, [Order::DELIVERED, Order::COLLECTED], true)) {
            OrderLine::where('order_id', $o->id)->whereIn('fulfilment_status', [OrderLine::PENDING])->update(['fulfilment_status' => OrderLine::FULFILLED]);
        }
        OrderActivity::log($o, $event, $from, $to);

        // handed over and nothing left to pay → closed by itself
        if (in_array($to, [Order::DELIVERED, Order::COLLECTED], true) && $o->dueAmount() <= 0) {
            $o->update(['status' => Order::COMPLETED, 'completed_at' => $now, 'version' => $o->version + 1]);
            OrderActivity::log($o, 'completed', $to, Order::COMPLETED, ['auto' => true]);
        }

        return $o;
    }

    private function close(Order $o, string $to, string $event, string $reason): Order
    {
        $from = $o->status;
        $payment = $o->payment_status;
        if ((float) $o->paid_amount - (float) $o->refunded_amount > 0 || $payment === Order::PAY_PROCESSING) {
            $payment = Order::PAY_REFUND_PENDING;
        }
        $o->update([
            'status' => $to,
            'payment_status' => $payment,
            'cancelled_at' => now(),
            $to === Order::REJECTED ? 'reject_reason' : 'cancel_reason' => mb_substr($reason, 0, 255),
            'version' => $o->version + 1,
        ] + $this->handler($o));
        OrderLine::where('order_id', $o->id)->whereNotIn('fulfilment_status', [OrderLine::REPLACED])->update(['fulfilment_status' => OrderLine::REMOVED]);
        OrderActivity::log($o, $event, $from, $to, ['reason' => $reason]);

        return $o;
    }

    private function undo(Order $o, string $reason): Order
    {
        $from = $o->status;
        $to = self::UNDO[$from];
        $o->update(['status' => $to, 'delivered_at' => null, 'version' => $o->version + 1]);
        OrderLine::where('order_id', $o->id)->where('fulfilment_status', OrderLine::FULFILLED)->update(['fulfilment_status' => OrderLine::PENDING]);
        OrderActivity::log($o, 'status_corrected', $from, $to, ['reason' => $reason]);

        return $o;
    }

    private function reason(?string $reason, string $what): string
    {
        $reason = trim((string) $reason);
        if (mb_strlen($reason) < 3) {
            throw ValidationException::withMessages(['reason' => match ($what) {
                'reject' => 'Tell the learner why the order is rejected.',
                'undo' => 'Write why the status is corrected.',
                default => 'Tell the learner why the order is cancelled.',
            }]);
        }

        return $reason;
    }

    /** Who handles the order: set once, by the first person who opens / accepts it. */
    private function handler(Order $o, bool $force = false): array
    {
        if ($o->handled_by_id && ! $force) {
            return [];
        }
        [$type, $id] = OrderActivity::actor();
        if (! in_array($type, ['library', 'library_user'], true)) {
            return [];
        }

        return $o->handled_by_id && $force ? [] : ['handled_by_type' => $type, 'handled_by_id' => $id];
    }

    // ---- learner -------------------------------------------------------------------------------

    public function cancelByLearner(Learner $learner, Order $order, ?string $reason): Order
    {
        $settings = $this->ordering->settings($learner);

        return DB::transaction(function () use ($order, $settings, $reason) {
            $order = Order::withoutGlobalScopes()->lockForUpdate()->findOrFail($order->id);
            if (! $this->ordering->canCancel($order, $settings)) {
                throw ValidationException::withMessages(['order' => $order->isOpen()
                    ? 'The library has started on this order. Please ask the desk to cancel it.'
                    : 'This order is already '.strtolower($order->statusLabel()).'.']);
            }

            return $this->close($order, Order::CANCELLED, 'cancelled_by_learner', $reason ? mb_substr($reason, 0, 255) : 'Cancelled by learner');
        });
    }
}
