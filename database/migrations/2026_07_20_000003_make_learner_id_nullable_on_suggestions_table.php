<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('suggestions', function (Blueprint $table) {
            $table->dropForeign(['learner_id']);
        });

        DB::statement('ALTER TABLE `suggestions` MODIFY `learner_id` BIGINT UNSIGNED NULL');

        Schema::table('suggestions', function (Blueprint $table) {
            $table->foreign('learner_id')->references('id')->on('learners')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('suggestions', function (Blueprint $table) {
            $table->dropForeign(['learner_id']);
        });

        DB::statement('ALTER TABLE `suggestions` MODIFY `learner_id` BIGINT UNSIGNED NOT NULL');

        Schema::table('suggestions', function (Blueprint $table) {
            $table->foreign('learner_id')->references('id')->on('learners')->onDelete('cascade');
        });
    }
};
