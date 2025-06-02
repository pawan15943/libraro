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
        Schema::create('libraries', function (Blueprint $table) {
            $table->id();
            $table->string('library_name');
            $table->string('library_email')->nullable();
            $table->string('library_mobile')->nullable();
            $table->string('state_id')->nullable();
            $table->string('city_id')->nullable();
            $table->string('library_address')->nullable();
            $table->string('library_zip')->nullable();
            $table->string('library_logo')->nullable();
            $table->string('library_type')->nullable();
            $table->string('library_owner')->nullable();
            $table->boolean('status')->default(1);
            $table->boolean('is_paid')->default(0);
            $table->integer('library_no')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       

        // Now drop the libraries table
        Schema::dropIfExists('libraries');
    }
};
