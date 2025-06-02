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
        Schema::create('library_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('library_id'); // Add foreign key column
            $table->foreign('library_id')->references('id')->on('libraries')->onDelete('cascade'); // Replace 'libraries' with 'users' if it references the users table
            $table->string('library_favicon')->nullable();
            $table->string('library_title');
            $table->text('library_meta_description');
            $table->string('library_primary_color', 7);
            $table->string('library_language');
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('library_settings', function (Blueprint $table) {
            $table->dropForeign(['library_id']);
        });
        Schema::dropIfExists('library_settings');
    }
};
