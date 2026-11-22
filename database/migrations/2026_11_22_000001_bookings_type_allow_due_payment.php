<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * bookings.type was an ENUM ('qr_renew','qr_seat_book','website_seat_book','demo-bookings'), so the
 * app booking type 'learner_book' and the new pay-link type 'qr_due_payment' (docs/QR_PAY_LINK.md)
 * could not be stored. A plain VARCHAR keeps every existing value as it is.
 * Live DB: db/libraro_live_db_changes_2026-11-22.sql, section 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('bookings', 'type')) {
            DB::statement('ALTER TABLE `bookings` MODIFY `type` VARCHAR(30) NULL DEFAULT NULL');
        }
    }

    public function down(): void
    {
        // Not narrowed back to the ENUM: rows of the new types would be lost.
    }
};
