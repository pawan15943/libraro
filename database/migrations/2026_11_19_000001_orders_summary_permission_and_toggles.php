<?php

use App\Services\Ordering\OrderingVisibility;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ordering & Services, plan and owner control:
 *
 *  - permission "Orders Summary" (Pro plan): the Orders card on the dashboard (orders + income, today / month)
 *  - Show / Hide Options, category "Orders": hide the Orders menu, the Order Reports menu, the dashboard card
 *
 * Rows are found by name, never by id. Live DB: db/libraro_live_db_changes_2026-11-19.sql.
 */
return new class extends Migration
{
    private const PRO = 3;

    private const CATEGORY = 'Ordering & Services';

    private const RESERVED_TOGGLE_ID = 35;

    public function up(): void
    {
        $now = now();

        $catId = DB::table('permission_categories')->where('name', self::CATEGORY)->value('id')
            ?? DB::table('permission_categories')->insertGetId(['name' => self::CATEGORY, 'created_at' => $now, 'updated_at' => $now]);

        $name = OrderingVisibility::SUMMARY_PERMISSION;
        $id = DB::table('permissions')->where('guard_name', 'library')->where('name', $name)->value('id')
            ?? DB::table('permissions')->insertGetId([
                'name' => $name, 'slug' => Str::slug($name), 'guard_name' => 'library', 'permission_category_id' => $catId,
                'description' => 'Dashboard card: orders and income from Ordering & Services, today and this month',
                'created_at' => $now, 'updated_at' => $now,
            ]);
        if (! DB::table('subscription_permission')->where('subscription_id', self::PRO)->where('permission_id', $id)->exists()) {
            DB::table('subscription_permission')->insert(['subscription_id' => self::PRO, 'permission_id' => $id, 'created_at' => $now]);
        }

        // ids up to 35 are used by code (35 = privacy / contact masking, which may exist only on some databases),
        // so new rows always go after max(id, 35)
        foreach (OrderingVisibility::TOGGLES as [$toggle, $desc]) {
            if (! DB::table('toggle_features')->where('name', $toggle)->exists()) {
                $next = max((int) DB::table('toggle_features')->max('id'), self::RESERVED_TOGGLE_ID) + 1;
                DB::table('toggle_features')->insert(['id' => $next, 'name' => $toggle, 'category' => OrderingVisibility::TOGGLE_CATEGORY, 'description' => $desc, 'created_at' => $now]);
            }
        }

        try {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        DB::table('toggle_features')->whereIn('name', array_column(OrderingVisibility::TOGGLES, 0))->delete();

        $ids = DB::table('permissions')->where('guard_name', 'library')->where('name', OrderingVisibility::SUMMARY_PERMISSION)->pluck('id');
        DB::table('subscription_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('library_user_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
