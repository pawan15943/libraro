<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('library_reward_redeems', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('library_id');
            $table->integer('points')->default(30);
            $table->tinyInteger('redeem_no');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->string('remark')->nullable();
            $table->timestamps();

            $table->index('library_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_reward_redeems');
    }
};

