<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('library_id'); // Add library_id column for foreign key
            $table->string('feedback_type');
            $table->unsignedTinyInteger('rating'); // Store rating as a number between 1-5
            $table->text('description');
            $table->string('attachment')->nullable(); // Optional file path
            $table->boolean('recommend'); // Store as boolean: 1 for Yes, 0 for No
            $table->timestamps();

            // Add the foreign key constraint
            $table->foreign('library_id')->references('id')->on('libraries')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropForeign(['library_id']);
        });
        Schema::dropIfExists('feedback');
    }
};
