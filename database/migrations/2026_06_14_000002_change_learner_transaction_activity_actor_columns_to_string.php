<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('learner_transaction_activity', 'created_by')) {
            DB::statement('ALTER TABLE learner_transaction_activity MODIFY created_by VARCHAR(255) NULL');
        }

        if (Schema::hasColumn('learner_transaction_activity', 'updated_by')) {
            DB::statement('ALTER TABLE learner_transaction_activity MODIFY updated_by VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('learner_transaction_activity', 'created_by')) {
            DB::statement('ALTER TABLE learner_transaction_activity MODIFY created_by BIGINT UNSIGNED NULL');
        }

        if (Schema::hasColumn('learner_transaction_activity', 'updated_by')) {
            DB::statement('ALTER TABLE learner_transaction_activity MODIFY updated_by BIGINT UNSIGNED NULL');
        }
    }
};
