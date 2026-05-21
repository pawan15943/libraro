<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('learner_transactions', 'updated_by')) {
            Schema::table('learner_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('updated_at');
            });
        }

        if (! Schema::hasColumn('learner_transaction_activity', 'updated_by')) {
            Schema::table('learner_transaction_activity', function (Blueprint $table) {
                $table->unsignedBigInteger('updated_by')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('learner_transactions', 'updated_by')) {
            Schema::table('learner_transactions', function (Blueprint $table) {
                $table->dropColumn('updated_by');
            });
        }

        if (Schema::hasColumn('learner_transaction_activity', 'updated_by')) {
            Schema::table('learner_transaction_activity', function (Blueprint $table) {
                $table->dropColumn('updated_by');
            });
        }
    }
};
