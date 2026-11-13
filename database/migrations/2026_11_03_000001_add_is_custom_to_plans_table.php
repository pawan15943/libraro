<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Custom plan: the owner picks the start and end date at booking time and the booking is
 * stored against a hidden plan row (type CUSTOM, plan_id = number of days). Existing rows
 * get is_custom = 0, so every current plan keeps behaving exactly as before.
 *
 * See docs/CUSTOM_PLAN.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'is_custom')) {
                $table->boolean('is_custom')->default(0)->after('monthdays');
            }
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->index(['library_id', 'is_custom', 'plan_id'], 'idx_plans_library_custom_days');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropIndex('idx_plans_library_custom_days');
        });

        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'is_custom')) {
                $table->dropColumn('is_custom');
            }
        });
    }
};
