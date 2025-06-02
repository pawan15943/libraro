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
        Schema::create('learner_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_id')->constrained();
            $table->foreignId('learner_id')->constrained();

           
            $table->decimal('total_amount', 8, 2);
            $table->decimal('paid_amount', 8, 2);
            $table->decimal('pending_amount', 8, 2);
            $table->date('paid_date');
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
        Schema::dropIfExists('learner_transactions');
    }
};
