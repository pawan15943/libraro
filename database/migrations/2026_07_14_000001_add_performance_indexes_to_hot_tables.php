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
        Schema::table('library_transactions', function (Blueprint $table) {
            $table->index('library_id', 'idx_library_transactions_library_id');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['branch_id', 'date'], 'idx_attendances_branch_date');
            $table->index('learner_id', 'idx_attendances_learner_id');
        });

        Schema::table('learner_operations_log', function (Blueprint $table) {
            $table->index(['branch_id', 'created_at'], 'idx_learner_operations_log_branch_created');
        });

        Schema::table('learner_transaction_activity', function (Blueprint $table) {
            $table->index(['branch_id', 'date'], 'idx_learner_transaction_activity_branch_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('library_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_library_transactions_library_id');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_attendances_branch_date');
            $table->dropIndex('idx_attendances_learner_id');
        });

        Schema::table('learner_operations_log', function (Blueprint $table) {
            $table->dropIndex('idx_learner_operations_log_branch_created');
        });

        Schema::table('learner_transaction_activity', function (Blueprint $table) {
            $table->dropIndex('idx_learner_transaction_activity_branch_date');
        });
    }
};
