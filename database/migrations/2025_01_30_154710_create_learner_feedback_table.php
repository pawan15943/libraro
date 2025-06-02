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
        Schema::create('learner_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_id')->constrained();
            $table->foreignId('learner_id')->constrained();
            $table->unsignedTinyInteger('frequency')->nullable(); // Storing 1, 2, 3, 4
            $table->string('purpose')->nullable();
            $table->unsignedTinyInteger('resources')->nullable(); // Storing 1, 2, partially (use string if needed)
            $table->text('resource_suggestions')->nullable();
            $table->unsignedTinyInteger('rating')->nullable(); // Rating 1-5
            $table->unsignedTinyInteger('staff')->nullable(); // Storing 1, 2, sometimes (use string if needed)
            $table->text('comments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learner_feedback');
    }
};
