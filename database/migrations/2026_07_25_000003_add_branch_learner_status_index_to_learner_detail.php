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
        // Supports LearnerService::getLearnersList's "latest detail per learner" derived
        // table, which groups over every learner_detail row for the branch on every
        // /learners/list request.
        Schema::table('learner_detail', function (Blueprint $table) {
            $table->index(['branch_id', 'learner_id', 'status', 'id'], 'idx_ld_branch_learner_status_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learner_detail', function (Blueprint $table) {
            $table->dropIndex('idx_ld_branch_learner_status_id');
        });
    }
};
