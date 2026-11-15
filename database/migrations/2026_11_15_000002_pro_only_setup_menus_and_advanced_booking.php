<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pro-only features of the library panel (Basic plan = id 1, Pro = id 3; guard `library`).
 *
 *  - New permissions for things that had none: Advanced Shifts (duration / free / flexible shift
 *    systems), Custom Plan Dates (booking with own start / end date), Multiple Shifts (extra shifts in
 *    one booking), Global Discount, Student Quiz, Demo Inquiry. Their menus now ask for them.
 *  - Pro only: those, the Setup menus Plans / Plan Types / Plan Prices / Expense Names / Exam Names
 *    (and their master buttons), Reports (the Reports menu and pages, `Manage Report`).
 *    The report permissions themselves stay in Basic: the dashboard and Limo use them for dues /
 *    collection answers; the report pages are closed by the route guard (library.can:Manage Report).
 *  - Basic keeps one branch: subscriptions.max_branches = 1 (already enforced by branchCountValidation).
 */
return new class extends Migration
{
    private const GUARD = 'library';
    private const BASIC = 1;
    private const PRO = 3;

    /** [name, category name, description, menu url that should ask for it] */
    private const NEW = [
        ['Advanced Shifts', 'Shifts', 'Duration, direct and flexible shift systems', null],
        ['Custom Plan Dates', 'Learner Operations', 'Book with your own start and end date', null],
        ['Multiple Shifts', 'Learner Operations', 'Book extra shifts on the same seat in one booking', null],
        ['Global Discount', 'Advanced Features', 'One library-wide offer applied to every booking', 'global-discount.index'],
        ['Student Quiz', 'Advanced Features', 'Quizzes for students in their portal', 'quiz.index'],
        ['Demo Inquiry', 'Menus', 'Demo / trial bookings', 'demo-users.index'],
    ];

    /** Taken out of Basic (Pro keeps them). */
    private const PRO_ONLY = [
        'Add Plan', 'Add Plan Type', 'Add Plan Price', 'Add Expense', 'Add Exam',
        'Add Plan Master', 'Add Plan Type Master', 'Add Plan Price Master', 'Add Expense Master', 'Add Exam Master',
        'Manage Report',
    ];

    public function up(): void
    {
        $now = now();
        $catId = fn (string $name) => DB::table('permission_categories')->where('name', $name)->value('id');

        foreach (self::NEW as [$name, $cat, $desc, $menuUrl]) {
            $id = DB::table('permissions')->where('guard_name', self::GUARD)->where('name', $name)->value('id')
                ?? DB::table('permissions')->insertGetId([
                    'name' => $name, 'slug' => Str::slug($name), 'guard_name' => self::GUARD,
                    'permission_category_id' => $catId($cat), 'description' => $desc, 'created_at' => $now, 'updated_at' => $now,
                ]);
            // Pro has every permission
            $this->link(self::PRO, $id);
            if ($menuUrl) {
                DB::table('menus')->where('guard', self::GUARD)->where('url', $menuUrl)->update(['has_permissions' => $name, 'updated_at' => $now]);
            }
        }

        $proOnly = DB::table('permissions')->where('guard_name', self::GUARD)->whereIn('name', self::PRO_ONLY)->pluck('id');
        DB::table('subscription_permission')->where('subscription_id', self::BASIC)->whereIn('permission_id', $proOnly)->delete();
        foreach ($proOnly as $id) {
            $this->link(self::PRO, $id);
        }

        DB::table('subscriptions')->where('id', self::BASIC)->update(['max_branches' => 1, 'updated_at' => $now]);

        $this->forgetPermissionCache();
    }

    public function down(): void
    {
        $now = now();
        $proOnly = DB::table('permissions')->where('guard_name', self::GUARD)->whereIn('name', self::PRO_ONLY)->pluck('id');
        foreach ($proOnly as $id) {
            $this->link(self::BASIC, $id);
        }

        // menus back to what they asked for before
        DB::table('menus')->where('guard', self::GUARD)->where('url', 'demo-users.index')->update(['has_permissions' => 'Search Learner']);
        DB::table('menus')->where('guard', self::GUARD)->whereIn('url', ['global-discount.index', 'quiz.index'])->update(['has_permissions' => null]);

        $new = DB::table('permissions')->where('guard_name', self::GUARD)->whereIn('name', array_column(self::NEW, 0))->pluck('id');
        DB::table('subscription_permission')->whereIn('permission_id', $new)->delete();
        DB::table('library_user_permissions')->whereIn('permission_id', $new)->delete();
        DB::table('permissions')->whereIn('id', $new)->delete();

        $this->forgetPermissionCache();
    }

    /** subscription_permission has no unique key: add the link only when it is not there. */
    private function link(int $planId, int $permissionId): void
    {
        $exists = DB::table('subscription_permission')->where('subscription_id', $planId)->where('permission_id', $permissionId)->exists();
        if (!$exists) {
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
