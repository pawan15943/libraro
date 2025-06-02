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
        Schema::create('library_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_id')->constrained();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();;
            $table->integer('month')->nullable();;
            $table->string('transaction_id')->nullable();
            $table->string('payment_mode')->nullable();;
            $table->decimal('amount', 8, 2)->nullable();;
            $table->decimal('paid_amount', 8, 2)->nullable();;
            $table->date('transaction_date')->nullable();;
            $table->boolean('is_paid')->default(0);
            $table->timestamps();

            // Foreign key constraints
           

          
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        

        // Drop the table
        Schema::dropIfExists('library_transactions');
    }
};
