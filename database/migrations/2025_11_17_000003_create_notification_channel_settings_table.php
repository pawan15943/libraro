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
            $table->json('activity_ids')->nullable();
            $table->boolean('is_waba')->default(false);
            $table->boolean('is_text')->default(false);
            $table->boolean('is_email')->default(false);
            $table->unsignedBigInteger('waba_template_id')->nullable();
            $table->unsignedBigInteger('text_template_id')->nullable();
            $table->unsignedBigInteger('email_template_id')->nullable();
            $table->timestamps();

            $table->index('branch_id');
            $table->foreign('waba_template_id')->references('id')->on('notification_templates')->onDelete('set null');
            $table->foreign('text_template_id')->references('id')->on('notification_templates')->onDelete('set null');
            $table->foreign('email_template_id')->references('id')->on('notification_templates')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_channel_settings');
    }
}
