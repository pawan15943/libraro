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
        // Covers SeatAvailabilityService::getAvailablePlanTypesMap's
        // join+where(branch_id)+whereIn(seat_no)+where(status) scan.
        Schema::table('learner_detail', function (Blueprint $table) {
            $table->index(['branch_id', 'seat_no', 'status'], 'idx_ld_branch_seat_status');
        });

        // Covers SeatAvailabilityService::getSwapSeatStatusCodesMap's
        // per-seat plan_type_id count (whereIn seat_no, where plan_type_id, where status).
        Schema::table('learner_detail', function (Blueprint $table) {
            $table->index(['seat_no', 'plan_type_id', 'status'], 'idx_ld_seat_plantype_status');
        });

        // Covers the "future booked seats" query in MasterController::getSeat
        // (branch_id + plan_start_date > today).
        Schema::table('learner_detail', function (Blueprint $table) {
            $table->index(['branch_id', 'plan_start_date'], 'idx_ld_branch_start');
        });

        // Covers the correlated "latest non-deleted detail per learner" subquery
        // in MasterController::getSeat.
        Schema::table('learner_detail', function (Blueprint $table) {
            $table->index(['learner_id', 'deleted_at'], 'idx_ld_learner_deleted');
        });

        // learners.status=1 is filtered alongside branch scoping on almost every query.
        Schema::table('learners', function (Blueprint $table) {
            $table->index(['branch_id', 'status'], 'idx_learners_branch_status');
        });

        // Covers Learner::where('seat_no', ...)->where('status', 1)->sum('hours').
        Schema::table('learners', function (Blueprint $table) {
            $table->index(['seat_no', 'status'], 'idx_learners_seat_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learner_detail', function (Blueprint $table) {
            $table->dropIndex('idx_ld_branch_seat_status');
            $table->dropIndex('idx_ld_seat_plantype_status');
            $table->dropIndex('idx_ld_branch_start');
            $table->dropIndex('idx_ld_learner_deleted');
        });

        Schema::table('learners', function (Blueprint $table) {
            $table->dropIndex('idx_learners_branch_status');
            $table->dropIndex('idx_learners_seat_status');
        });
    }
};
