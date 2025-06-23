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
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
             // Polymorphic user fields
        $table->unsignedBigInteger('user_id');
        $table->string('user_type'); // Model class (e.g., App\Models\Library)
        
        $table->string('guard_name'); // NEW: tracks which guard is used
        $table->string('device_id');
        $table->string('device_type'); // e.g., android, ios, web
        $table->string('token')->nullable();       // Sanctum token (optional to store)
        $table->timestamps();

         $table->unique(['user_id', 'user_type', 'device_id']); // Prevent duplicate device logins per model
        });

       
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
