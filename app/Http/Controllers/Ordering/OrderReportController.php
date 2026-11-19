<?php

namespace App\Http\Controllers\Ordering;

use App\Http\Controllers\Controller;
use App\Models\Learner;
use App\Models\LibraryUser;
use App\Models\Ordering\Order;
use App\Models\Ordering\OrderActivity;
use App\Models\Ordering\OrderLine;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Library panel → Orders → Order Reports (spec §13): sales, payments, items, staff, timings. */
class OrderReportController extends Controller
{
    public const RANGES = ['today' => 'Today', '7d' => 'Last 7 days', '30d' => 'Last 30 days', 'month' => 'This month', 'custom' => 'Custom'];

    public function index(Request $request)
    {
        $range = array_key_exists($request->query('range'), self::RANGES) ? $request->query('range') : 'today';
        [$from, $to] = $this->period($range, $request);

        $base = fn () => Order::withoutGlobalScopes()->where('library_id', getLibraryId())->where('branch_id', getCurrentBranch())
            ->whereBetween('created_at', [$from, $to]);
        $closed = [Order::CANCELLED, Order::REJECTED];
        $handed = [Order::DELIVERED, Order::COLLECTED, Order::COMPLETED];

        $orders = $base()->get(['id', 'learner_id', 'order_no', 'status', 'payment_method', 'payment_status', 'total', 'paid_amount', 'refunded_amount',
            'created_at', 'confirmed_at', 'preparing_at', 'ready_at', 'delivered_at', 'estimated_ready_at', 'handled_by_type', 'handled_by_id', 'mode']);
        $live = $orders->whereNotIn('status', $closed);

        $kpi = [
            'orders' => $orders->count(),
            'completed' => $orders->whereIn('status', $handed)->count(),
            'cancelled' => $orders->whereIn('status', $closed)->count(),
            'open' => $orders->whereIn('status', Order::OPEN)->count(),
            'sales' => round($live->sum(fn ($o) => (float) $o->total), 2),
            'received' => round($orders->sum(fn ($o) => (float) $o->paid_amount - (float) $o->refunded_amount), 2),
            'due' => round($live->sum(fn ($o) => $o->dueAmount()), 2),
            'refunded' => round($orders->sum(fn ($o) => (float) $o->refunded_amount), 2),
            'refund_due' => round($orders->sum(fn ($o) => max(0, (float) $o->paid_amount - (float) $o->refunded_amount - (in_array($o->status, $closed, true) ? 0 : (float) $o->total))), 2),
        ];
        $kpi['avg_order'] = $live->count() ? round($kpi['sales'] / $live->count(), 2) : 0;

        $methods = [];
        foreach (['online' => 'Online (UPI)', 'offline' => 'At counter', 'pay_later' => 'Pay later'] as $k => $label) {
            $set = $live->where('payment_method', $k);
            $methods[] = ['label' => $label, 'orders' => $set->count(), 'amount' => round($set->sum(fn ($o) => (float) $o->total), 2),
                'paid' => round($set->sum(fn ($o) => (float) $o->paid_amount - (float) $o->refunded_amount), 2), 'due' => round($set->sum(fn ($o) => $o->dueAmount()), 2)];
        }

        $liveIds = $live->pluck('id');
        $lines = OrderLine::whereIn('order_id', $liveIds)->whereIn('fulfilment_status', OrderLine::ACTIVE);
        $categories = (clone $lines)->selectRaw('COALESCE(category_name, "Other") AS name, COUNT(DISTINCT order_id) AS orders, SUM(quantity) AS qty, SUM(line_total) AS amount')
            ->groupBy('name')->orderByDesc('amount')->get();
        $items = (clone $lines)->selectRaw('item_name AS name, category_name AS cat, SUM(quantity) AS qty, SUM(line_total) AS amount')
            ->groupBy('item_name', 'category_name')->orderByDesc('qty')->limit(15)->get();

        // staff: who accepted / handed over how many
        $acts = OrderActivity::whereIn('order_id', $orders->pluck('id'))->whereIn('actor_type', ['library', 'library_user'])
            ->whereIn('action', ['accepted', 'delivered', 'collected', 'rejected', 'cancelled_by_staff', 'payment_recorded', 'payment_approved'])
            ->get(['actor_type', 'actor_id', 'action']);
        $names = LibraryUser::whereIn('id', $acts->where('actor_type', 'library_user')->pluck('actor_id')->unique())->pluck('name', 'id');
        $staff = $acts->groupBy(fn ($a) => $a->actor_type.':'.$a->actor_id)->map(fn ($g, $key) => [
            'name' => str_starts_with($key, 'library:') ? 'Owner' : ($names[(int) substr($key, 13)] ?? 'Staff'),
            'accepted' => $g->where('action', 'accepted')->count(),
            'handed' => $g->whereIn('action', ['delivered', 'collected'])->count(),
            'payments' => $g->whereIn('action', ['payment_recorded', 'payment_approved'])->count(),
            'rejected' => $g->whereIn('action', ['rejected', 'cancelled_by_staff'])->count(),
        ])->sortByDesc('accepted')->values();

        $mins = fn ($a, $b) => ($a && $b) ? max(0, $a->diffInSeconds($b)) / 60 : null;
        $avg = function ($vals) {
            $vals = collect($vals)->filter(fn ($v) => $v !== null);

            return $vals->isEmpty() ? null : round($vals->avg(), 1);
        };
        $timing = [
            'accept' => $avg($orders->map(fn ($o) => $mins($o->created_at, $o->confirmed_at))),
            'prepare' => $avg($orders->map(fn ($o) => $mins($o->confirmed_at, $o->ready_at))),
            'deliver' => $avg($orders->map(fn ($o) => $mins($o->created_at, $o->delivered_at))),
        ];
        $withEta = $orders->filter(fn ($o) => $o->ready_at && $o->estimated_ready_at);
        $timing['on_time'] = $withEta->count() ? (int) round($withEta->filter(fn ($o) => $o->ready_at <= $o->estimated_ready_at->copy()->addMinutes(5))->count() / $withEta->count() * 100) : null;

        // money still to collect / give back (any date): what staff should chase today
        $dueList = Order::withoutGlobalScopes()->where('library_id', getLibraryId())->where('branch_id', getCurrentBranch())
            ->whereNotIn('status', $closed)->whereIn('payment_status', [Order::PAY_PENDING, Order::PAY_PARTIAL, Order::PAY_FAILED, Order::PAY_PROCESSING])
            ->whereRaw('total - paid_amount + refunded_amount > 0')->orderBy('created_at')->limit(50)->get();
        $refundList = Order::withoutGlobalScopes()->where('library_id', getLibraryId())->where('branch_id', getCurrentBranch())
            ->whereIn('payment_status', [Order::PAY_REFUND_PENDING, Order::PAY_PARTIALLY_REFUNDED])->orderBy('created_at')->limit(50)->get();
        $people = Learner::withoutGlobalScopes()->whereIn('id', $dueList->pluck('learner_id')->merge($refundList->pluck('learner_id'))->unique())->pluck('name', 'id');

        return view('ordering.library.reports', compact('range', 'from', 'to', 'kpi', 'methods', 'categories', 'items', 'staff', 'timing', 'dueList', 'refundList', 'people') + [
            'ranges' => self::RANGES,
        ]);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function period(string $range, Request $request): array
    {
        $now = now();

        return match ($range) {
            '7d' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            '30d' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
            'custom' => $this->custom($request),
            default => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
        };
    }

    private function custom(Request $request): array
    {
        try {
            $from = Carbon::parse((string) $request->query('from'))->startOfDay();
            $to = Carbon::parse((string) $request->query('to'))->endOfDay();
        } catch (\Throwable $e) {
            return [now()->startOfDay(), now()->endOfDay()];
        }
        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }
        if ($from->diffInDays($to) > 366) {
            $from = $to->copy()->subDays(366)->startOfDay();
        }

        return [$from, $to];
    }
}
