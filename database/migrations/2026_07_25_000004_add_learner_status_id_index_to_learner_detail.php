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
        // Supports the "latest active-or-most-recent learner_detail row per learner"
        // correlated subquery used by fetchCustomerData() and fetchLearnerData()
        // (ORDER BY status = 1 DESC, id DESC LIMIT 1 per learner_id).
        Schema::table('learner_detail', function (Blueprint $table) {
            $table->index(['learner_id', 'status', 'id'], 'idx_ld_learner_status_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learner_detail', function (Blueprint $table) {
            $table->dropIndex('idx_ld_learner_status_id');
        });
    }
};
