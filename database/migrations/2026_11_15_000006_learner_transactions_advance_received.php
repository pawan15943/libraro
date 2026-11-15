<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * learner_transactions.advance_received: money paid over this bill when it was paid (docs/LEARNER_ADVANCE.md).
 * `refund` is what is LEFT of it (it goes down as the advance is used / refunded); this column keeps how much
 * came in, so the dashboard's "Advance received" card and its list add up by day and month.
 * Backfill: bills that still hold an advance and were not a plan change / edit (whose `refund` is money owed back).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('learner_transactions', 'advance_received')) {
            Schema::table('learner_transactions', function (Blueprint $table) {
                $table->decimal('advance_received', 10, 2)->default(0)->after('refund');
            });
        }

        DB::statement("UPDATE learner_transactions t SET t.advance_received = t.refund
            WHERE t.refund > 0 AND t.advance_received = 0 AND t.deleted_at IS NULL
              AND NOT EXISTS (SELECT 1 FROM learner_transaction_activity a WHERE a.learner_transaction_id = t.id AND a.payment_type IN ('CHANGE PLAN', 'EDIT'))");
    }

    public function down(): void
    {
        if (Schema::hasColumn('learner_transactions', 'advance_received')) {
            Schema::table('learner_transactions', function (Blueprint $table) {
                $table->dropColumn('advance_received');
            });
        }
    }
};
