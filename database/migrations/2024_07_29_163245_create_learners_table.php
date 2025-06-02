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
        Schema::create('learners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_id')->constrained();

            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('username')->nullable();
            $table->string('mobile')->nullable();
            $table->date('dob')->nullable();
            $table->date('address')->nullable();
            $table->string('id_proof_name')->nullable();
            $table->string('id_proof_file')->nullable();
            $table->integer('hours')->default(0);
            $table->unsignedBigInteger('seat_no');
            $table->integer('payment_mode')->nullable();
            $table->foreign('seat_no')->references('id')->on('seats')->onDelete('cascade');
            $table->boolean('status')->default(1);
            $table->timestamps();
            $table->softDeletes();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learners', function (Blueprint $table) {
            $table->unsignedBigInteger('library_id')->nullable(false)->change();
        });
    }
};
