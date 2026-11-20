<?php

namespace App\Services\Ordering;

use App\Models\Ordering\Order;
use App\Models\Ordering\OrderPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Orders card on the library dashboard: orders and income, today and this month, for one branch.
 *
 *  - Orders = orders placed in the period, not cancelled / rejected.
 *  - Income = money actually received in the period (verified payments − refunds given), counted on the
 *    day it was received, so a pay-later order paid today counts today. Same money as Order Reports.
 */
class OrderDashboardService
{
    /** Should the card show for the signed-in library user? (plan + permission + owner's Show / Hide + set up) */
    public function visible(int $libraryId, int $branchId): bool
    {
        $user = getAuthenticatedUser();

        return $libraryId && $branchId
            && OrderingVisibility::planAllows($libraryId)
            && $user && $user->can('has-permission', OrderingVisibility::SUMMARY_PERMISSION)
            && ! OrderingVisibility::dashboardHidden()
            && $this->isSetUp($branchId);
    }

    /**
     * @return array{on: bool, today: array{orders: int, income: float}, month: array{orders: int, income: float},
     *               yesterday_income: float, open: int, to_collect: float, month_label: string}
     */
    public function summary(int $libraryId, int $branchId, ?Carbon $now = null): array
    {
        $now = $now ?: now();
        $today = [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
        $yesterday = [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()];
        $month = [$now->copy()->startOfMonth(), $now->copy()->endOfDay()];

        $orders = fn () => Order::withoutGlobalScopes()->where('library_id', $libraryId)->where('branch_id', $branchId);
        $placed = fn (array $range) => (int) $orders()->whereNotIn('status', [Order::CANCELLED, Order::REJECTED])
            ->whereBetween('created_at', $range)->count();

        $open = $orders()->whereIn('status', Order::OPEN)->count();
        $toCollect = (float) $orders()->whereNotIn('status', [Order::CANCELLED, Order::REJECTED])
            ->selectRaw('COALESCE(SUM(GREATEST(total - paid_amount + refunded_amount, 0)), 0) AS due')->value('due');

        return [
            'on' => (bool) DB::table('order_settings')->where('branch_id', $branchId)->value('is_enabled'),
            'today' => ['orders' => $placed($today), 'income' => $this->income($libraryId, $branchId, $today)],
            'month' => ['orders' => $placed($month), 'income' => $this->income($libraryId, $branchId, $month)],
            'yesterday_income' => $this->income($libraryId, $branchId, $yesterday),
            'open' => $open,
            'to_collect' => round($toCollect, 2),
            'month_label' => $now->format('F'),
        ];
    }

    private function income(int $libraryId, int $branchId, array $range): float
    {
        $row = OrderPayment::withoutGlobalScopes()
            ->where('library_id', $libraryId)->where('branch_id', $branchId)->where('status', 'verified')
            ->whereRaw('COALESCE(verified_at, created_at) BETWEEN ? AND ?', [$range[0], $range[1]])
            ->selectRaw("COALESCE(SUM(CASE WHEN kind = 'refund' THEN -amount ELSE amount END), 0) AS net")
            ->first();

        return round((float) ($row->net ?? 0), 2);
    }

    /** The branch has opened the module at least once (its settings row exists). */
    private function isSetUp(int $branchId): bool
    {
        try {
            return DB::table('order_settings')->where('branch_id', $branchId)->exists();
        } catch (\Throwable $e) {
            return false; // tables not migrated yet
        }
    }
}
