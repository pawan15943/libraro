<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationLogsTable extends Migration
{
    public function up()
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('unique_id')->nullable();
            $table->unsignedBigInteger('library_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('learner_id')->nullable();
            $table->string('seat_no')->nullable();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->unsignedBigInteger('operation_id')->nullable();
            $table->enum('channel', ['waba','text','email']);
            $table->unsignedBigInteger('template_id')->nullable();
            $table->text('message_content');
            $table->enum('message_status', ['queued','sent','failed','delivered','bounced','read'])->default('queued');
            $table->text('delivery_status')->nullable();
            $table->text('error_message')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['learner_id', 'operation_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_logs');
    }
}
