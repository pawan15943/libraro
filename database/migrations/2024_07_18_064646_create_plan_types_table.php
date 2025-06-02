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
        Schema::create('plan_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_id')->constrained();


            $table->string('name');
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->string('slot_hours')->nullable();
            $table->integer('day_type_id')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_types', function (Blueprint $table) {
            $table->unsignedBigInteger('library_id')->nullable(false)->change();
        });
    }
};
