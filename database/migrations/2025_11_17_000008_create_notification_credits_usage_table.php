<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationCreditsUsageTable extends Migration
{
    public function up()
    {
        Schema::create('notification_credits_usage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('library_id');
            $table->enum('channel', ['waba','text','email']);
            $table->date('date');
            $table->unsignedBigInteger('used')->default(0);
            $table->unsignedBigInteger('remaining')->nullable();
            $table->timestamps();

            $table->index(['library_id','channel','date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_credits_usage');
    }
}
