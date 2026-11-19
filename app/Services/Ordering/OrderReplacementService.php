<?php

namespace App\Services\Ordering;

use App\Models\Learner;
use App\Models\Ordering\Order;
use App\Models\Ordering\OrderActivity;
use App\Models\Ordering\OrderItem;
use App\Models\Ordering\OrderLine;
use App\Models\Ordering\OrderReplacement;
use App\Models\Ordering\OrderSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Item availability & replacement (spec §5). Staff mark a line unavailable, optionally with a
 * suggested item; the learner answers all open suggestions on one screen (accept / remove /
 * ask for another). Nothing is replaced or charged without the learner's yes. The order total and
 * payment status follow the change (cheaper → refund / credit, dearer → more to pay).
 */
class OrderReplacementService
{
    /** Order stages in which staff may still change items. */
    public const EDITABLE = [Order::NEW, Order::ATTENTION, Order::UNDER_REVIEW, Order::REPLACEMENT_PENDING, Order::CONFIRMED, Order::PREPARING];

    /** Messages from the last respond() call (e.g. "X ran out too"), for the page or the API. */
    public array $lastNotes = [];

    public function __construct(
        private OrderingSetupService $setup,
        private OrderPaymentService $payments,
    ) {}

    private function settings(Order $o): OrderSetting
    {
        return $this->setup->settings((int) $o->library_id, (int) $o->branch_id);
    }

    /** Items staff can suggest for a line: available, enabled, same branch, not the same item. */
    public function suggestions(Order $o, OrderLine $line)
    {
        return OrderItem::withoutGlobalScopes()->whereNull('deleted_at')->where('branch_id', $o->branch_id)
            ->where('is_enabled', true)->where('is_available', true)
            ->where('id', '<>', (int) $line->order_item_id)
            ->with(['category' => fn ($q) => $q->withoutGlobalScopes()])
            ->orderByRaw('order_category_id = ? DESC', [(int) $line->order_category_id])
            ->orderBy('name')->get()
            ->filter(fn ($i) => $i->category && $i->category->is_enabled && $i->allowsMode($o->mode))->values();
    }

    public function hasPending(Order $o): bool
    {
        return OrderReplacement::where('order_id', $o->id)->where('status', 'pending')->exists();
    }

    /**
     * Staff: a line ran out. $itemId = suggested item (null = no replacement, learner can only remove it).
     */
    public function markUnavailable(Order $order, int $lineId, ?int $itemId, ?int $qty = null, ?bool $markItemOut = true): Order
    {
        return DB::transaction(function () use ($order, $lineId, $itemId, $qty, $markItemOut) {
            $o = Order::withoutGlobalScopes()->lockForUpdate()->findOrFail($order->id);
            $s = $this->settings($o);
            if (! in_array($o->status, self::EDITABLE, true)) {
                throw ValidationException::withMessages(['line' => 'Items can no longer be changed: the order is "'.$o->statusLabel().'".']);
            }
            $line = OrderLine::where('order_id', $o->id)->lockForUpdate()->findOrFail($lineId);
            if (! in_array($line->fulfilment_status, [OrderLine::PENDING, OrderLine::UNAVAILABLE], true)) {
                throw ValidationException::withMessages(['line' => $line->item_name.' is already '.str_replace('_', ' ', $line->fulfilment_status).'.']);
            }
            if ($itemId && ! $s->replacement_enabled) {
                throw ValidationException::withMessages(['item' => 'Replacement suggestions are switched off in Order Settings.']);
            }

            $item = null;
            if ($itemId) {
                $item = $this->suggestions($o, $line)->firstWhere('id', $itemId);
                if (! $item) {
                    throw ValidationException::withMessages(['item' => 'Choose an available item to suggest.']);
                }
            }
            $qty = max(1, min(50, (int) ($qty ?: $line->quantity)));
            $price = $item ? round((float) $item->price * $qty, 2) : null;

            // an earlier open suggestion on this line is replaced by the new one (fresh approval needed)
            OrderReplacement::where('order_line_id', $line->id)->where('status', 'pending')->update(['status' => 'withdrawn', 'responded_at' => now()]);
            [$type, $id] = OrderActivity::actor();
            OrderReplacement::create([
                'order_id' => $o->id,
                'order_line_id' => $line->id,
                'suggested_item_id' => $item?->id,
                'suggested_name' => $item?->name,
                'suggested_price' => $item ? (float) $item->price : null,
                'quantity' => $qty,
                'price_diff' => $item ? round($price - (float) $line->line_total, 2) : round(-(float) $line->line_total, 2),
                'status' => 'pending',
                'deadline_at' => now()->addMinutes(max(1, (int) $s->replacement_timeout_minutes)),
                'suggested_by_type' => $type,
                'suggested_by_id' => $id,
            ]);
            $line->update(['fulfilment_status' => OrderLine::REPLACEMENT_PENDING]);

            // "out of stock" for everyone, so nobody else orders it meanwhile
            if ($markItemOut && $line->order_item_id) {
                OrderItem::withoutGlobalScopes()->where('id', $line->order_item_id)->update(['is_available' => false]);
            }

            $from = $o->status;
            // learner allowed partial fulfilment and staff already started → the rest goes ahead
            $keepGoing = $o->allow_partial && in_array($o->status, [Order::CONFIRMED, Order::PREPARING], true);
            if (! $keepGoing && $o->status !== Order::REPLACEMENT_PENDING) {
                $o->update(['status' => Order::REPLACEMENT_PENDING, 'version' => $o->version + 1]);
            } else {
                $o->update(['version' => $o->version + 1]);
            }
            OrderActivity::log($o, 'replacement_requested', $from, $o->status, [
                'line' => $line->item_name, 'suggested' => $item?->name, 'price_diff' => $item ? round($price - (float) $line->line_total, 2) : null,
            ]);

            return $o;
        });
    }

    /**
     * Learner answers every open suggestion at once. $choices: replacement id → accept | remove | other.
     */
    public function respond(Learner $learner, Order $order, array $choices): Order
    {
        return DB::transaction(function () use ($learner, $order, $choices) {
            $o = Order::withoutGlobalScopes()->where('learner_id', $learner->id)->lockForUpdate()->findOrFail($order->id);
            $s = $this->settings($o);
            $open = OrderReplacement::where('order_id', $o->id)->where('status', 'pending')->lockForUpdate()->get();
            if ($open->isEmpty()) {
                throw ValidationException::withMessages(['choices' => 'Nothing to answer — the library already handled it.']);
            }
            foreach ($open as $r) {
                $c = $choices[$r->id] ?? null;
                if (! in_array($c, ['accept', 'remove', 'other'], true) || ($c === 'accept' && ! $r->suggested_item_id) || ($c === 'other' && ! $s->replacement_multiple)) {
                    throw ValidationException::withMessages(['choices' => 'Choose what to do for every item that ran out.']);
                }
            }

            $notes = [];
            foreach ($open as $r) {
                $line = OrderLine::where('order_id', $o->id)->lockForUpdate()->find($r->order_line_id);
                $choice = $choices[$r->id];

                if ($choice === 'accept') {
                    $item = OrderItem::withoutGlobalScopes()->whereNull('deleted_at')->with(['category' => fn ($q) => $q->withoutGlobalScopes()])->find($r->suggested_item_id);
                    if (! $item || ! $item->is_available || ! $item->is_enabled) {
                        // suggested item ran out too → ask staff for another one
                        $r->update(['status' => 'withdrawn', 'responded_at' => now()]);
                        $line->update(['fulfilment_status' => OrderLine::UNAVAILABLE]);
                        $notes[] = $r->suggested_name.' ran out too — the library will suggest something else.';

                        continue;
                    }
                    OrderLine::create([
                        'order_id' => $o->id,
                        'order_item_id' => $item->id,
                        'order_category_id' => $item->order_category_id,
                        'category_name' => optional($item->category)->name,
                        'item_name' => $item->name,
                        'unit_price' => $r->suggested_price,
                        'quantity' => $r->quantity,
                        'line_total' => round((float) $r->suggested_price * $r->quantity, 2),
                        'replaced_line_id' => $line->id,
                        'note' => $line->note,
                    ]);
                    $line->update(['fulfilment_status' => OrderLine::REPLACED]);
                    $r->update(['status' => 'accepted', 'responded_at' => now()]);
                } elseif ($choice === 'remove') {
                    $line->update(['fulfilment_status' => OrderLine::REMOVED]);
                    $r->update(['status' => 'rejected', 'responded_at' => now()]);
                } else {
                    $line->update(['fulfilment_status' => OrderLine::UNAVAILABLE]);
                    $r->update(['status' => 'rejected', 'responded_at' => now()]);
                }
            }

            $o = $this->settle($o, 'replacement_answered', ['choices' => array_values(array_intersect_key($choices, $open->keyBy('id')->all()))]);

            $this->lastNotes = $notes;

            return $o;
        });
    }

    /**
     * Timeout (orders:sweep): no answer in time → library rule: remove the item, or leave it to staff.
     */
    public function expire(OrderReplacement $r): void
    {
        DB::transaction(function () use ($r) {
            $o = Order::withoutGlobalScopes()->lockForUpdate()->find($r->order_id);
            $r = OrderReplacement::lockForUpdate()->find($r->id);
            if (! $o || ! $r || $r->status !== 'pending') {
                return;
            }
            $s = $this->settings($o);
            $line = OrderLine::where('order_id', $o->id)->find($r->order_line_id);
            $r->update(['status' => 'expired', 'responded_at' => now()]);
            if ($s->replacement_timeout_action === 'remove') {
                $line?->update(['fulfilment_status' => OrderLine::REMOVED]);
                $this->settle($o, 'replacement_timeout', ['line' => $line?->item_name, 'rule' => 'removed']);
            } else {
                $line?->update(['fulfilment_status' => OrderLine::UNAVAILABLE]);
                $from = $o->status;
                if ($o->status === Order::REPLACEMENT_PENDING && ! $this->hasPending($o)) {
                    $o->update(['status' => Order::ATTENTION, 'version' => $o->version + 1]);
                } else {
                    $o->update(['version' => $o->version + 1]);
                }
                OrderActivity::log($o, 'replacement_timeout', $from, $o->status, ['line' => $line?->item_name, 'rule' => 'staff decides']);
            }
        });
    }

    /** Staff take an unavailable line off the order themselves (e.g. after a timeout with "staff decides"). */
    public function removeLine(Order $order, int $lineId): Order
    {
        return DB::transaction(function () use ($order, $lineId) {
            $o = Order::withoutGlobalScopes()->lockForUpdate()->findOrFail($order->id);
            if (! in_array($o->status, self::EDITABLE, true)) {
                throw ValidationException::withMessages(['line' => 'Items can no longer be changed: the order is "'.$o->statusLabel().'".']);
            }
            $line = OrderLine::where('order_id', $o->id)->lockForUpdate()->findOrFail($lineId);
            if (! in_array($line->fulfilment_status, [OrderLine::UNAVAILABLE, OrderLine::PENDING, OrderLine::REPLACEMENT_PENDING], true)) {
                throw ValidationException::withMessages(['line' => 'This item cannot be removed now.']);
            }
            OrderReplacement::where('order_line_id', $line->id)->where('status', 'pending')->update(['status' => 'withdrawn', 'responded_at' => now()]);
            $line->update(['fulfilment_status' => OrderLine::REMOVED]);

            return $this->settle($o, 'item_removed_by_staff', ['line' => $line->item_name]);
        });
    }

    /**
     * Recalculate the total from the lines still on the order, update the payment status, and move
     * the order on once nothing waits for the learner any more.
     */
    private function settle(Order $o, string $event, array $meta): Order
    {
        $active = OrderLine::where('order_id', $o->id)->whereIn('fulfilment_status', OrderLine::ACTIVE)->get();
        $from = $o->status;
        $oldTotal = (float) $o->total;

        if ($active->isEmpty()) {
            // every item is gone → the order is cancelled; money paid goes back
            $o->subtotal = 0;
            $o->total = 0;
            $o->status = Order::CANCELLED;
            $o->cancelled_at = now();
            $o->cancel_reason = 'None of the items were available';
        } else {
            $o->subtotal = round($active->sum(fn ($l) => (float) $l->line_total), 2);
            $o->total = round((float) $o->subtotal + (float) $o->delivery_charge, 2);
            $waiting = OrderReplacement::where('order_id', $o->id)->where('status', 'pending')->exists();
            $unresolved = $active->contains(fn ($l) => $l->fulfilment_status === OrderLine::UNAVAILABLE);
            if ($o->status === Order::REPLACEMENT_PENDING && ! $waiting) {
                // back to where staff were: unanswered "other" requests stay for staff to look at
                $o->status = $unresolved ? Order::UNDER_REVIEW : Order::CONFIRMED;
                $o->confirmed_at = $o->confirmed_at ?: ($unresolved ? null : now());
            }
        }
        $o->payment_status = $this->payments->paymentState($o);
        $o->version++;
        $o->save();

        OrderActivity::log($o, $event, $from, $o->status, $meta + ['old_total' => $oldTotal, 'new_total' => (float) $o->total]);
        if ($o->status === Order::CANCELLED && $from !== Order::CANCELLED) {
            OrderActivity::log($o, 'cancelled_no_items', $from, Order::CANCELLED);
        }

        return $o;
    }
}
