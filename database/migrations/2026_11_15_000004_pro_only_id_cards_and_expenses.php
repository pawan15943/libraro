<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ID cards (single and bulk) and daily expenses become Pro plan features (Basic = 1, Pro = 3).
 * Permissions `Genrate ID Card` and `Add Daily Expense` leave Basic; the plan feature list splits
 * "ID cards and payment receipts" and "Daily expenses and other payments" so Basic keeps receipts and
 * other payments.
 */
return new class extends Migration
{
    private const BASIC = 1;
    private const PRO = 3;

    private const PRO_ONLY = ['Genrate ID Card', 'Add Daily Expense'];

    /** [old combined line, [category, Basic part] , [category, Pro part]] */
    private const SPLIT = [
        ['ID cards and payment receipts', ['Learners', 'Payment receipts'], ['Learners', 'ID cards (single and bulk print)']],
        ['Daily expenses and other payments', ['Payments', 'Other payments'], ['Payments', 'Daily expenses']],
    ];

    public function up(): void
    {
        $now = now();
        $ids = DB::table('permissions')->where('guard_name', 'library')->whereIn('name', self::PRO_ONLY)->pluck('id');
        DB::table('subscription_permission')->where('subscription_id', self::BASIC)->whereIn('permission_id', $ids)->delete();
        foreach ($ids as $id) {
            if (!DB::table('subscription_permission')->where('subscription_id', self::PRO)->where('permission_id', $id)->exists()) {
                DB::table('subscription_permission')->insert(['subscription_id' => self::PRO, 'permission_id' => $id, 'created_at' => $now]);
            }
        }

        foreach (self::SPLIT as [$old, [$basicCat, $basicName], [$proCat, $proName]]) {
            DB::table('subscription_plan_features')->whereNull('deleted_at')->where('name', $old)
                ->update(['name' => $basicName, 'category' => $basicCat, 'updated_at' => $now]);
            foreach ([self::BASIC => 0, self::PRO => 1] as $plan => $status) {
                $exists = DB::table('subscription_plan_features')->whereNull('deleted_at')->where('subscription_id', $plan)->where('name', $proName)->exists();
                if (!$exists) {
                    DB::table('subscription_plan_features')->insert(['name' => $proName, 'subscription_id' => $plan, 'feature_status' => $status, 'category' => $proCat, 'created_at' => $now, 'updated_at' => $now]);
                }
            }
        }

        try {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        $now = now();
        foreach (DB::table('permissions')->where('guard_name', 'library')->whereIn('name', self::PRO_ONLY)->pluck('id') as $id) {
            if (!DB::table('subscription_permission')->where('subscription_id', self::BASIC)->where('permission_id', $id)->exists()) {
                DB::table('subscription_permission')->insert(['subscription_id' => self::BASIC, 'permission_id' => $id, 'created_at' => $now]);
            }
        }
        foreach (self::SPLIT as [$old, [, $basicName], [, $proName]]) {
            DB::table('subscription_plan_features')->whereNull('deleted_at')->where('name', $proName)->delete();
            DB::table('subscription_plan_features')->whereNull('deleted_at')->where('name', $basicName)->update(['name' => $old, 'updated_at' => $now]);
        }
    }
};
