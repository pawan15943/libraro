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
        Schema::create('temp_orders', function (Blueprint $table) {
            $table->id();
            $table->string('razorpay_order_id')->nullable();
            $table->string('library_transaction_id')->nullable();
            $table->string('payment_status')->default('pending'); // pending, success, fail
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_orders');
    }
};
