<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationAmountTable extends Migration
{
    public function up()
    {
        Schema::create('notification_amount', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->unsignedBigInteger('amount');
            $table->decimal('price', 12, 2)->default(0);
            $table->enum('channel', ['waba','text','email'])->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_amount');
    }
}
