<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('library_reward_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('library_id');
            $table->unsignedBigInteger('referral_id')->nullable();
            $table->integer('points');
            $table->enum('type', ['credit', 'debit']);
            $table->enum('source', ['referral_register', 'redeem']);
            $table->string('remark')->nullable();
            $table->timestamps();

            $table->index(['library_id', 'source']);
            $table->index(['referral_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_reward_points');
    }
};

