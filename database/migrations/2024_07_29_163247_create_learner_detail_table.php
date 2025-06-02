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
        Schema::create('learner_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_id')->constrained();
            $table->date('join_date')->nullable();
            $table->date('plan_start_date')->nullable();
            $table->date('plan_end_date')->nullable();
            $table->foreignId('plan_price_id')->constrained();
            $table->foreignId('plan_id')->constrained();
            $table->foreignId('plan_type_id')->constrained();
            $table->foreignId('seat_id')->constrained();
            $table->integer('hours')->nullable();
            $table->boolean('status')->default(1);
            $table->boolean('is_paid')->default(0);
            $table->timestamps();
            $table->softDeletes();
            

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learner_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('library_id')->nullable(false)->change();
        });
    }
};
