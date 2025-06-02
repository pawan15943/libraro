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
        Schema::create('hour', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_id')->constrained();
            $table->integer('hour')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hour', function (Blueprint $table) {
            $table->unsignedBigInteger('library_id')->nullable(false)->change();
        });
    }
};
