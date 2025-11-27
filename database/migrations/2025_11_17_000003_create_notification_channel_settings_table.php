<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationChannelSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('notification_channel_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->json('waba_template_id')->nullable();
            $table->json('text_template_id')->nullable();
            $table->json('email_template_id')->nullable();
            $table->timestamps();

            $table->index('branch_id');
           
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_channel_settings');
    }
}
