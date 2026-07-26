<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // learner_detail_id was stored as varchar, which forces a numeric cast on every
        // comparison against learner_detail.id (bigint) and prevents any index from being
        // used by the receipt_transaction_id / transaction lookups in LearnerService.
        DB::statement('ALTER TABLE learner_transactions MODIFY learner_detail_id BIGINT UNSIGNED NULL');

        Schema::table('learner_transactions', function (Blueprint $table) {
            $table->index('learner_detail_id', 'idx_lt_learner_detail_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learner_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_lt_learner_detail_id');
        });

        DB::statement('ALTER TABLE learner_transactions MODIFY learner_detail_id VARCHAR(255) NULL');
    }
};
