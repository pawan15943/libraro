<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Table was originally created by hand on some servers — only create it where missing.
        if (Schema::hasTable('guidelines')) {
            return;
        }

        Schema::create('guidelines', function (Blueprint $table) {
            $table->id();
            $table->string('guideline_type')->nullable()->index();
            $table->text('question');
            $table->longText('answer')->nullable();
            $table->text('hindi_question')->nullable();
            $table->longText('hindi_answer')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('guidelines');
    }
};
