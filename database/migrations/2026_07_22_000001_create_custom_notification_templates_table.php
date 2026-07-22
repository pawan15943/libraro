<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomNotificationTemplatesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('custom_notification_templates')) {
            return;
        }

        Schema::create('custom_notification_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('library_id');
            $table->foreignId('operation_id')->constrained('operations')->onDelete('cascade');
            $table->enum('type', ['waba', 'text', 'email']);
            $table->string('template_name')->nullable();
            $table->string('template_code')->nullable();
            $table->text('template_message');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_custom')->default(true);
            $table->enum('is_paid', ['0', '1'])->default('0');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('library_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('custom_notification_templates');
    }
}
