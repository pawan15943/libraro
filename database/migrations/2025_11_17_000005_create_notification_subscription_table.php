<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationSubscriptionTable extends Migration
{
    public function up()
    {
        Schema::create('notification_subscription', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('library_id');
            $table->unsignedBigInteger('waba')->nullable(); // that channel subscription id
            $table->unsignedBigInteger('text')->nullable(); // that channel subscription id
            $table->unsignedBigInteger('email')->nullable();
            $table->date('waba_end_date')->nullable();
            $table->date('text_end_date')->nullable();
            $table->date('email_end_date')->nullable();
            $table->decimal('total_paid_amount', 12, 2)->default(0);
            $table->string('order_id');
            $table->timestamps();

            $table->index('library_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_subscription');
    }
}
