<?php

namespace App\Services\Ordering;

use Illuminate\Support\Facades\DB;

/**
 * Who sees Ordering & Services, in two layers (docs/ORDERING.md "Plan, permissions, show / hide"):
 *
 *  1. Plan: the library's subscription must include "Manage Orders" (Pro by default). Without it the
 *     learner side is off as well, even if the branch had switched ordering on under an older plan.
 *  2. Owner choice: Settings → Show / Hide Options, category "Orders" (toggle_features rows, saved per
 *     branch in branches.hide_field). Rows are looked up by name, so their ids may differ per database.
 */
class OrderingVisibility
{
    /** The plan permission that switches the whole module on. */
    public const PLAN_PERMISSION = 'Manage Orders';

    /** Dashboard card with today's / this month's orders and income. */
    public const SUMMARY_PERMISSION = 'Orders Summary';

    public const TOGGLE_CATEGORY = 'Orders';

    public const HIDE_MENU = 'Hide Orders Menu';

    public const HIDE_REPORTS = 'Hide Order Reports Menu';

    public const HIDE_DASHBOARD = 'Hide Orders Summary on Dashboard';

    /** [name, description] in the order they appear on Show / Hide Options. */
    public const TOGGLES = [
        [self::HIDE_MENU, 'Hide the whole Orders menu (Order Board, Menu & Items, Order Settings, Order Reports) and the dashboard summary. Learners can still order while ordering is on.'],
        [self::HIDE_REPORTS, 'Hide Orders → Order Reports from the menu'],
        [self::HIDE_DASHBOARD, 'Hide the Orders card (orders and income, today and this month) on the dashboard'],
    ];

    /** Sidebar route names of the module. */
    public const MENU_URLS = ['orders.board', 'ordering.items', 'ordering.settings', 'orders.reports'];

    /** Lookups are kept for one request only (tests and queue workers run many requests in one process). */
    private static array $cache = [];

    private static function remember(string $key, callable $fn)
    {
        $scope = app()->bound('request') ? spl_object_id(app('request')) : 0;
        if (! isset(self::$cache[$scope])) {
            self::$cache = [$scope => []];
        }
        if (! array_key_exists($key, self::$cache[$scope])) {
            self::$cache[$scope][$key] = $fn();
        }

        return self::$cache[$scope][$key];
    }

    /** Does the library's plan include the module? */
    public static function planAllows(int $libraryId): bool
    {
        return self::remember('plan:'.$libraryId, function () use ($libraryId) {
            try {
                return DB::table('libraries')
                    ->join('subscription_permission', 'subscription_permission.subscription_id', '=', 'libraries.library_type')
                    ->join('permissions', 'permissions.id', '=', 'subscription_permission.permission_id')
                    ->where('libraries.id', $libraryId)
                    ->where('permissions.guard_name', 'library')
                    ->where('permissions.name', self::PLAN_PERMISSION)
                    ->exists();
            } catch (\Throwable $e) {
                return false;
            }
        });
    }

    /** Has the owner switched this Show / Hide option on for the current branch? */
    public static function hidden(string $toggle): bool
    {
        $id = self::toggleIds()[$toggle] ?? null;

        return $id && in_array((string) $id, self::branchHidden(), true);
    }

    /** Sidebar links the owner has hidden for this branch. */
    public static function hiddenMenuUrls(): array
    {
        if (self::hidden(self::HIDE_MENU)) {
            return self::MENU_URLS;
        }

        return self::hidden(self::HIDE_REPORTS) ? ['orders.reports'] : [];
    }

    public static function dashboardHidden(): bool
    {
        return self::hidden(self::HIDE_MENU) || self::hidden(self::HIDE_DASHBOARD);
    }

    /** For tests: forget the lookups of this request. */
    public static function flush(): void
    {
        self::$cache = [];
    }

    /** Ids switched on in Show / Hide Options for the current branch (branches.hide_field). */
    private static function branchHidden(): array
    {
        $branchId = (int) (function_exists('getCurrentBranch') ? getCurrentBranch() : 0);

        return self::remember('hidden:'.$branchId, function () use ($branchId) {
            if (! $branchId) {
                return [];
            }
            $raw = DB::table('branches')->where('id', $branchId)->value('hide_field');

            return array_map('strval', (array) (json_decode((string) $raw, true) ?: []));
        });
    }

    private static function toggleIds(): array
    {
        return self::remember('toggles', function () {
            try {
                return DB::table('toggle_features')->where('category', self::TOGGLE_CATEGORY)->pluck('id', 'name')->all();
            } catch (\Throwable $e) {
                return [];
            }
        });
    }
}
