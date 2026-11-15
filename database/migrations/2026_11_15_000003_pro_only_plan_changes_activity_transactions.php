<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * More Pro-only learner options (Basic plan = id 1, Pro = id 3; guard `library`):
 * Change Plan, Upgrade Plan, Edit Plan, the learner's Activity and Transactions.
 *
 *  - New permissions for what had none: Edit Plan (was under Edit Seat), Learner Activity,
 *    Learner Transactions. Pro gets them; Basic does not.
 *  - Change Plan and Upgrade Seat Plan leave Basic.
 *  - Staff kept what they could do: every existing staff account gets Learner Activity and Learner
 *    Transactions (these were open to all), and Edit Plan where it had Edit Seat. A Basic library's
 *    staff still can't use them — the plan limits staff too.
 */
return new class extends Migration
{
    private const GUARD = 'library';
    private const BASIC = 1;
    private const PRO = 3;

    /** [name, description, staff who had it before: 'all' | permission name] */
    private const NEW = [
        ['Edit Plan', 'Edit a booking\'s plan dates and price', 'Edit Seat'],
        ['Learner Activity', 'See a learner\'s activity log', 'all'],
        ['Learner Transactions', 'See and edit a learner\'s payments', 'all'],
    ];

    private const PRO_ONLY = ['Change Plan', 'Upgrade Seat Plan'];

    /** Plan feature list rows (Pro only). */
    private const FEATURES = [
        ['Learners', 'Upgrade, change plan and edit plan'],
        ['Learners', 'Learner activity and payment history'],
    ];

    public function up(): void
    {
        $now = now();
        $cat = DB::table('permission_categories')->where('name', 'Learner Operations')->value('id');

        foreach (self::NEW as [$name, $desc, $before]) {
            $id = DB::table('permissions')->where('guard_name', self::GUARD)->where('name', $name)->value('id')
                ?? DB::table('permissions')->insertGetId([
                    'name' => $name, 'slug' => Str::slug($name), 'guard_name' => self::GUARD,
                    'permission_category_id' => $cat, 'description' => $desc, 'created_at' => $now, 'updated_at' => $now,
                ]);
            $this->link(self::PRO, $id);

            // staff keep what they could do
            $staff = $before === 'all'
                ? DB::table('library_users')->whereNull('deleted_at')->pluck('id')
                : DB::table('library_user_permissions as lup')->join('permissions as p', 'p.id', '=', 'lup.permission_id')
                    ->where('p.guard_name', self::GUARD)->where('p.name', $before)->distinct()->pluck('lup.library_user_id');
            $has = DB::table('library_user_permissions')->where('permission_id', $id)->pluck('library_user_id')->flip();
            $rows = [];
            foreach ($staff as $uid) {
                if (!isset($has[$uid])) {
                    $rows[] = ['library_user_id' => $uid, 'permission_id' => $id, 'created_at' => $now];
                }
            }
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('library_user_permissions')->insert($chunk);
            }
        }

        $proOnly = DB::table('permissions')->where('guard_name', self::GUARD)->whereIn('name', self::PRO_ONLY)->pluck('id');
        DB::table('subscription_permission')->where('subscription_id', self::BASIC)->whereIn('permission_id', $proOnly)->delete();
        foreach ($proOnly as $id) {
            $this->link(self::PRO, $id);
        }

        // plan feature list (pricing / choose plan): the combined line splits into Basic and Pro parts
        DB::table('subscription_plan_features')->whereNull('deleted_at')->where('name', 'Renew, upgrade, change plan and swap seat')
            ->update(['name' => 'Renew and swap seat', 'updated_at' => $now]);
        foreach (self::FEATURES as [$category, $name]) {
            foreach ([self::BASIC => 0, self::PRO => 1] as $planId => $status) {
                $exists = DB::table('subscription_plan_features')->whereNull('deleted_at')->where('subscription_id', $planId)->where('name', $name)->exists();
                if (!$exists) {
                    DB::table('subscription_plan_features')->insert(['name' => $name, 'subscription_id' => $planId, 'feature_status' => $status, 'category' => $category, 'created_at' => $now, 'updated_at' => $now]);
                }
            }
        }

        $this->forgetPermissionCache();
    }

    public function down(): void
    {
        DB::table('subscription_plan_features')->whereNull('deleted_at')->whereIn('name', array_column(self::FEATURES, 1))->delete();
        DB::table('subscription_plan_features')->whereNull('deleted_at')->where('name', 'Renew and swap seat')
            ->update(['name' => 'Renew, upgrade, change plan and swap seat']);
        foreach (DB::table('permissions')->where('guard_name', self::GUARD)->whereIn('name', self::PRO_ONLY)->pluck('id') as $id) {
            $this->link(self::BASIC, $id);
        }
        $new = DB::table('permissions')->where('guard_name', self::GUARD)->whereIn('name', array_column(self::NEW, 0))->pluck('id');
        DB::table('subscription_permission')->whereIn('permission_id', $new)->delete();
        DB::table('library_user_permissions')->whereIn('permission_id', $new)->delete();
        DB::table('permissions')->whereIn('id', $new)->delete();
        $this->forgetPermissionCache();
    }

    /** subscription_permission has no unique key: add the link only when it is not there. */
    private function link(int $planId, int $permissionId): void
    {
        if (!DB::table('subscription_permission')->where('subscription_id', $planId)->where('permission_id', $permissionId)->exists()) {
            DB::table('subscription_permission')->insert(['subscription_id' => $planId, 'permission_id' => $permissionId, 'created_at' => now()]);
        }
    }

    private function forgetPermissionCache(): void
    {
        try {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
        }
    }
};
