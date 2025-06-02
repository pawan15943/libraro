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
        Schema::create('monthly_expense', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_id')->constrained();

            $table->string('project')->nullable();
            $table->integer('month')->nullable();
            $table->integer('year')->nullable();
            $table->string('expense_id')->nullable();
            $table->string('amount')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_expense', function (Blueprint $table) {
            $table->unsignedBigInteger('library_id')->nullable(false)->change();
        });
    }
};
