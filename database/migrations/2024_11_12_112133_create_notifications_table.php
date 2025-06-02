<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();           // Unique identifier for the notification
            $table->string('type');                  // Notification class/type (e.g., NewNotification)
            $table->morphs('notifiable');            // Polymorphic relation fields (notifiable type and id)
            $table->text('data');                    // JSON data payload for the notification
            $table->string('guard')->nullable();     // Guard for the intended user (e.g., 'web', 'library', 'learner')
            $table->timestamp('read_at')->nullable(); // Timestamp for when the notification was read
            $table->timestamps();                    // Created and updated timestamps
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
