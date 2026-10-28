<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Advanced shifts: a plan type can be a template whose real timing is chosen at
 * booking time. Existing rows default to booking_mode = 'fixed' and parent_id = NULL,
 * so every current shift keeps behaving exactly as before.
 *
 * booking_mode : fixed | duration | free | flexible | multi
 * parent_id    : template a hidden, auto-created timing row belongs to (NULL = normal shift)
 * start_time_2 / end_time_2 : second time window of a multi shift (NULL = single window)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_types', function (Blueprint $table) {
            if (!Schema::hasColumn('plan_types', 'booking_mode')) {
                $table->string('booking_mode', 20)->default('fixed')->after('day_type_id');
            }
            if (!Schema::hasColumn('plan_types', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('booking_mode');
            }
            if (!Schema::hasColumn('plan_types', 'start_time_2')) {
                $table->string('start_time_2', 191)->nullable()->after('end_time');
            }
            if (!Schema::hasColumn('plan_types', 'end_time_2')) {
                $table->string('end_time_2', 191)->nullable()->after('start_time_2');
            }
        });

        Schema::table('plan_types', function (Blueprint $table) {
            $table->index(['parent_id', 'start_time', 'end_time'], 'idx_plan_types_parent_timing');
        });
    }

    public function down(): void
    {
        Schema::table('plan_types', function (Blueprint $table) {
            $table->dropIndex('idx_plan_types_parent_timing');
        });

        Schema::table('plan_types', function (Blueprint $table) {
            foreach (['end_time_2', 'start_time_2', 'parent_id', 'booking_mode'] as $column) {
                if (Schema::hasColumn('plan_types', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
