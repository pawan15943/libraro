<?php

namespace App\Services\Ordering;

use App\Models\Ordering\Order;
use App\Models\Ordering\OrderActivity;
use App\Models\Ordering\OrderReplacement;
use App\Models\Ordering\OrderSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Time-based rules (spec §6 "Staff busy", §5 F "Learner does not respond"):
 *  - a New order nobody opened after `reminder_minutes` → reminder (board rings again, logged)
 *  - still unopened after `escalation_minutes` → "Attention required" + owner notification
 *    (orders are never rejected just because nobody looked at them)
 *  - a replacement nobody answered before its deadline → the library's timeout rule
 * Runs from `php artisan orders:sweep` (scheduler, every minute) and, so it also works where
 * no cron is set up, from the order board feed at most every 30 s per branch.
 */
class OrderSweepService
{
    public function __construct(
        private OrderReplacementService $replacements,
        private OrderNotifier $notifier,
    ) {}

    /** Sweep one branch at most every $seconds (called by the board feed). */
    public function sweepSoon(int $branchId, int $seconds = 30): void
    {
        if (Cache::add('orders:sweep:'.$branchId, 1, $seconds)) {
            $this->sweep($branchId);
        }
    }

    /** @return array{reminded: int, escalated: int, expired: int} */
    public function sweep(?int $branchId = null): array
    {
        $done = ['reminded' => 0, 'escalated' => 0, 'expired' => 0];
        $now = now();

        $settings = OrderSetting::withoutGlobalScopes()->where('is_enabled', true)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get()->keyBy('branch_id');
        if ($branchId && ! $settings->has($branchId)) {
            // a switched-off branch can still have orders from before: use its row anyway
            $row = OrderSetting::withoutGlobalScopes()->where('branch_id', $branchId)->first();
            if ($row) {
                $settings->put($branchId, $row);
            }
        }

        foreach ($settings as $bid => $s) {
            $waiting = Order::withoutGlobalScopes()->where('branch_id', $bid)->where('status', Order::NEW)->get();
            foreach ($waiting as $o) {
                $age = $o->created_at->diffInMinutes($now);
                if ($age >= (int) $s->escalation_minutes && ! $o->escalated_at) {
                    if ($this->escalate($o)) {
                        $done['escalated']++;
                    }
                } elseif ($age >= (int) $s->reminder_minutes && ! $o->reminded_at) {
                    if ($this->remind($o)) {
                        $done['reminded']++;
                    }
                }
            }
        }

        $expired = OrderReplacement::where('status', 'pending')->where('deadline_at', '<=', $now)
            ->when($branchId, fn ($q) => $q->whereIn('order_id', Order::withoutGlobalScopes()->where('branch_id', $branchId)->select('id')))
            ->limit(200)->get();
        foreach ($expired as $r) {
            $this->replacements->expire($r);
            $done['expired']++;
        }

        return $done;
    }

    private function remind(Order $order): bool
    {
        return DB::transaction(function () use ($order) {
            $o = Order::withoutGlobalScopes()->lockForUpdate()->find($order->id);
            if (! $o || $o->status !== Order::NEW || $o->reminded_at) {
                return false;
            }
            $o->update(['reminded_at' => now()]);
            OrderActivity::log($o, 'staff_reminded', Order::NEW, Order::NEW);

            return true;
        });
    }

    private function escalate(Order $order): bool
    {
        return DB::transaction(function () use ($order) {
            $o = Order::withoutGlobalScopes()->lockForUpdate()->find($order->id);
            if (! $o || $o->status !== Order::NEW || $o->escalated_at) {
                return false;
            }
            $o->update(['status' => Order::ATTENTION, 'escalated_at' => now(), 'reminded_at' => $o->reminded_at ?: now(), 'version' => $o->version + 1]);
            OrderActivity::log($o, 'escalated', Order::NEW, Order::ATTENTION);
            $this->notifier->owner($o, 'Order #'.\Illuminate\Support\Str::afterLast($o->order_no, '-').' is waiting',
                'Nobody has accepted it yet ('.$o->locationLabel().', ₹'.number_format((float) $o->total, 2).'). Please open the Order Board.');

            return true;
        });
    }
}
