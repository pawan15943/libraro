<?php

namespace App\Services\Ordering;

use App\Models\Branch;
use App\Models\Learner;
use App\Models\Ordering\Order;
use App\Models\Ordering\OrderCategory;
use App\Models\Ordering\OrderItem;
use App\Models\Ordering\OrderSetting;
use App\Models\Ordering\OrderTable;
use App\Services\LearnerPortalService;
use Illuminate\Support\Collection;

/**
 * What one learner may do in Ordering & Services right now: is it on, are they eligible,
 * which modes / tables / payment methods, the menu. Used by the web page, placement and the API.
 */
class LearnerOrderingService
{
    /** Plan states that count as a member with a seat (LearnerPortalService::plan()). */
    private const SEATED = ['active', 'soon', 'grace'];

    /** Members who may order even without seat delivery (frozen = on hold but still a member). */
    private const MEMBER = ['active', 'soon', 'grace', 'frozen'];

    public function __construct(
        private OrderingSetupService $setup,
        private LearnerPortalService $portal,
    ) {}

    public function settings(Learner $learner): OrderSetting
    {
        return $this->setup->settings((int) $learner->library_id, (int) $learner->branch_id);
    }

    /**
     * Everything the learner page needs to decide what to show.
     *
     * @return array{settings: OrderSetting, enabled: bool, eligible: bool, reason: ?string, open: bool, opens_at: ?string,
     *               seat: ?string, seat_editable: bool, modes: array, tables: Collection, payments: array, has_upi: bool,
     *               open_orders: int, limit_reached: bool, pay_later_ok: bool, pay_later_room: ?float}
     */
    public function context(Learner $learner): array
    {
        $s = $this->settings($learner);
        $plan = $this->portal->summary($learner)['plan'] ?? null;
        $state = $plan['state'] ?? 'none';
        $seat = in_array($state, self::SEATED, true) ? ($plan['seat'] ?? null) : null;

        $reason = null;
        if (! $s->is_enabled) {
            $reason = 'Ordering is not available in your library right now.';
        } elseif (! in_array($state, self::MEMBER, true) && ! $s->expired_can_order) {
            $reason = 'Ordering is for members with an active plan. Renew your plan to order.';
        }

        $tables = $s->mode_table
            ? OrderTable::withoutGlobalScopes()->where('branch_id', $learner->branch_id)->where('is_active', true)->orderBy('sort_order')->orderBy('label')->get(['id', 'label'])
            : collect();

        $modes = [];
        if ($s->mode_seat && ($seat || $s->seat_change_allowed)) {
            $modes[] = 'seat';
        }
        if ($s->mode_table && $tables->isNotEmpty()) {
            $modes[] = 'table';
        }
        if ($s->mode_counter) {
            $modes[] = 'counter';
        }
        if (! $reason && ! $modes) {
            $reason = 'Ordering has no delivery option for you right now. Please ask the desk.';
        }

        $branch = Branch::withoutGlobalScopes()->find($learner->branch_id);
        $hasUpi = ! empty(optional($branch)->upi_id);

        $openOrders = Order::withoutGlobalScopes()->where('learner_id', $learner->id)->whereIn('status', Order::OPEN)->count();
        $limitReached = $s->max_open_orders > 0 && $openOrders >= $s->max_open_orders;

        [$payLaterOk, $room] = $this->payLater($learner, $s);

        $payments = [];
        foreach ($s->enabledPayments() as $p) {
            if ($p === 'online' && ! $hasUpi) {
                continue;
            }
            if ($p === 'pay_later' && ! $payLaterOk) {
                continue;
            }
            $payments[] = $p;
        }

        $now = now();

        return [
            'settings' => $s,
            'enabled' => (bool) $s->is_enabled,
            'eligible' => $reason === null,
            'reason' => $reason,
            'open' => $s->isOpenAt($now),
            'opens_at' => $s->opens_at ? substr($s->opens_at, 0, 5) : null,
            'closes_at' => $s->closes_at ? substr($s->closes_at, 0, 5) : null,
            'seat' => $seat,
            'seat_editable' => (bool) $s->seat_change_allowed,
            'modes' => $modes,
            'tables' => $tables,
            'payments' => $payments,
            'has_upi' => $hasUpi,
            'open_orders' => $openOrders,
            'limit_reached' => $limitReached,
            'pay_later_ok' => $payLaterOk,
            'pay_later_room' => $room,
        ];
    }

    /**
     * Pay later: on in settings, this learner allowed, and (with a limit) room left.
     *
     * @return array{0: bool, 1: ?float} [allowed, room left (null = no limit)]
     */
    public function payLater(Learner $learner, OrderSetting $s): array
    {
        if (! $s->pay_later) {
            return [false, null];
        }
        if ($s->pay_later_scope === 'selected' && ! in_array((int) $learner->id, array_map('intval', $s->pay_later_learner_ids ?? []), true)) {
            return [false, null];
        }
        if ((float) $s->pay_later_limit <= 0) {
            return [true, null];
        }
        $room = round((float) $s->pay_later_limit - $this->unpaid($learner), 2);

        return [$room > 0, max(0, $room)];
    }

    /** What the learner still owes on pay-later orders that were not cancelled / rejected. */
    public function unpaid(Learner $learner): float
    {
        return (float) Order::withoutGlobalScopes()->where('learner_id', $learner->id)
            ->where('payment_method', 'pay_later')
            ->whereNotIn('status', [Order::CANCELLED, Order::REJECTED])
            ->whereIn('payment_status', [Order::PAY_PENDING, Order::PAY_PARTIAL, Order::PAY_PROCESSING])
            ->selectRaw('COALESCE(SUM(total - paid_amount + refunded_amount), 0) AS due')
            ->value('due');
    }

    /** Visible categories with their visible items (unavailable ones included, shown greyed). */
    public function menu(Learner $learner): Collection
    {
        return OrderCategory::withoutGlobalScopes()->whereNull('deleted_at')
            ->where('branch_id', $learner->branch_id)->where('is_enabled', true)
            ->with(['items' => fn ($q) => $q->withoutGlobalScopes()->whereNull('deleted_at')->where('is_enabled', true)])
            ->orderBy('sort_order')->orderBy('name')->get()
            ->filter(fn ($c) => $c->items->isNotEmpty())->values();
    }

    /** Menu as plain data for the page script / API. */
    public function menuData(Collection $menu): array
    {
        return $menu->map(fn (OrderCategory $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'icon' => $c->icon ?: 'shapes',
            'service' => (bool) $c->is_service,
            'pay' => $c->allowedPayments(),
            'items' => $c->items->map(fn (OrderItem $i) => [
                'id' => $i->id,
                'name' => $i->name,
                'desc' => $i->description,
                'price' => (float) $i->price,
                'unit' => $i->price_unit,
                'img' => $i->imageUrl(),
                'avail' => (bool) $i->is_available,
                'featured' => (bool) $i->is_featured,
                'upload' => (bool) $i->needs_upload,
                'modes' => $i->modes,
            ])->values()->all(),
        ])->values()->all();
    }

    /** Can the learner cancel this order by themselves now? (settings → learner_cancel_until) */
    public function canCancel(Order $order, OrderSetting $s): bool
    {
        $allowed = match ($s->learner_cancel_until) {
            'confirmed' => [Order::NEW, Order::ATTENTION, Order::UNDER_REVIEW, Order::REPLACEMENT_PENDING, Order::CONFIRMED],
            'under_review' => [Order::NEW, Order::ATTENTION, Order::UNDER_REVIEW, Order::REPLACEMENT_PENDING],
            default => [Order::NEW, Order::ATTENTION, Order::REPLACEMENT_PENDING],
        };

        return in_array($order->status, $allowed, true);
    }
}
