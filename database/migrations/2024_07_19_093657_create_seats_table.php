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
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_id')->constrained();

            $table->integer('seat_no');
            $table->boolean('is_available')->default(1);
            $table->boolean('status')->default(1);
            $table->integer('total_hours')->default(0);
           
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seats', function (Blueprint $table) {
            $table->unsignedBigInteger('library_id')->nullable(false)->change();
        });
    }
};
