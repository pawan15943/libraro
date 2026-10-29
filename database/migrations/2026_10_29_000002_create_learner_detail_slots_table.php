<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multiple shifts in one booking: every extra shift (seat + shift) of a booking is a row
 * here. Dates, status, freeze and the transaction stay on the parent learner_detail row,
 * so a slot always follows its booking. See docs/ADVANCED_SHIFTS.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('learner_detail_slots')) {
            return;
        }

        Schema::create('learner_detail_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('library_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('learner_id');
            $table->unsignedBigInteger('learner_detail_id');
            $table->string('seat_no', 255);
            $table->unsignedBigInteger('plan_type_id');
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('learner_detail_id', 'idx_lds_detail');
            $table->index(['branch_id', 'seat_no'], 'idx_lds_branch_seat');
            $table->index('learner_id', 'idx_lds_learner');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learner_detail_slots');
    }
};
