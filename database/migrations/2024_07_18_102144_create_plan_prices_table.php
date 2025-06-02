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
        Schema::create('plan_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_id')->constrained();

            $table->foreignId('plan_id')->constrained();
            $table->foreignId('plan_type_id')->constrained();
            $table->string('price');
            $table->timestamps();
            $table->softDeletes();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_prices', function (Blueprint $table) {
            $table->unsignedBigInteger('library_id')->nullable(false)->change();
        });
    }
};
