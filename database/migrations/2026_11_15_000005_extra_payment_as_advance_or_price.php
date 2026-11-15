<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Extra payment (docs/LEARNER_ADVANCE.md): when a learner pays more than the bill, the library picks
 * whether the extra is an advance (used on the next renewal) or a higher price for this bill.
 *
 *  - branches.allow_higher_price: the branch switch that shows the choice (off = always advance).
 *  - Permission "Charge Above Plan Price" (Learner Operations) in Basic (1) and Pro (3); owners get it
 *    from the plan, staff when the owner grants it.
 *  - Plan feature line for the pricing / choose plan lists.
 */
return new class extends Migration
{
    private const PERMISSION = 'Charge Above Plan Price';
    private const FEATURE = 'Extra payment as advance or a higher price';
    private const PLANS = [1, 3];

    public function up(): void
    {
        if (! Schema::hasColumn('branches', 'allow_higher_price')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->boolean('allow_higher_price')->default(false)->after('fixed_billing_date');
            });
        }

        $now = now();
        $cat = DB::table('permission_categories')->where('name', 'Learner Operations')->value('id');
        $id = DB::table('permissions')->where('guard_name', 'library')->where('name', self::PERMISSION)->value('id')
            ?? DB::table('permissions')->insertGetId([
                'name' => self::PERMISSION, 'slug' => Str::slug(self::PERMISSION), 'guard_name' => 'library',
                'permission_category_id' => $cat, 'description' => 'Take a payment above the bill as a higher price instead of an advance',
                'created_at' => $now, 'updated_at' => $now,
            ]);

        foreach (self::PLANS as $plan) {
            if (! DB::table('subscription_permission')->where('subscription_id', $plan)->where('permission_id', $id)->exists()) {
                DB::table('subscription_permission')->insert(['subscription_id' => $plan, 'permission_id' => $id, 'created_at' => $now]);
            }
            if (! DB::table('subscription_plan_features')->whereNull('deleted_at')->where('subscription_id', $plan)->where('name', self::FEATURE)->exists()) {
                DB::table('subscription_plan_features')->insert([
                    'name' => self::FEATURE, 'subscription_id' => $plan, 'feature_status' => 1, 'category' => 'Payments',
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        try {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where('guard_name', 'library')->where('name', self::PERMISSION)->value('id');
        if ($id) {
            DB::table('subscription_permission')->where('permission_id', $id)->delete();
            DB::table('library_user_permissions')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('id', $id)->delete();
        }
        DB::table('subscription_plan_features')->whereNull('deleted_at')->where('name', self::FEATURE)->delete();
        if (Schema::hasColumn('branches', 'allow_higher_price')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropColumn('allow_higher_price');
            });
        }
    }
};
