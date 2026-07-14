<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // bookings had no index at all on branch_id/type — DashboardService::onlineBookings()
        // (used by /library/dashboard) was doing a full table scan on every request.
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['branch_id', 'type', 'created_at'], 'idx_bookings_branch_type_created');
        });

        // Supports the "only latest plan record per learner" correlated subquery in
        // DashboardService::expiredMembers().
        Schema::table('learner_detail', function (Blueprint $table) {
            $table->index(['learner_id', 'plan_end_date'], 'idx_learner_detail_learner_plan_end');
        });

        // Supports the closeSeat whereNotExists correlated subquery in expiredMembers().
        Schema::table('learner_operations_log', function (Blueprint $table) {
            $table->index(['learner_detail_id', 'operation'], 'idx_ops_log_detail_operation');
        });

        // seatSummary() filters Hour by branch_id (only library_id was indexed before).
        Schema::table('hour', function (Blueprint $table) {
            $table->index('branch_id', 'idx_hour_branch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('idx_bookings_branch_type_created');
        });

        Schema::table('learner_detail', function (Blueprint $table) {
            $table->dropIndex('idx_learner_detail_learner_plan_end');
        });

        Schema::table('learner_operations_log', function (Blueprint $table) {
            $table->dropIndex('idx_ops_log_detail_operation');
        });

        Schema::table('hour', function (Blueprint $table) {
            $table->dropIndex('idx_hour_branch');
        });
    }
};
