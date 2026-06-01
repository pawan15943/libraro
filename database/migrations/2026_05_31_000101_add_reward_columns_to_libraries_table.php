<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('referral_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('library_id')->unique();
            $table->integer('total_earned_points')->default(0);
            $table->integer('redeemed_points')->default(0);
            $table->integer('available_points')->default(0);
            $table->integer('max_cap_points')->default(90);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_wallets');
    }
};
