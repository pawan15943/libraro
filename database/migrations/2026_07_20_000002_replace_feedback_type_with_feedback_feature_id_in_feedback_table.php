<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->unsignedBigInteger('feedback_feature_id')->nullable()->after('library_id');
            $table->foreign('feedback_feature_id')->references('id')->on('feedback_features')->onDelete('set null');
        });

        Schema::table('feedback', function (Blueprint $table) {
            $table->dropColumn('feedback_type');
        });
    }

    public function down()
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->string('feedback_type')->nullable()->after('library_id');
        });

        Schema::table('feedback', function (Blueprint $table) {
            $table->dropForeign(['feedback_feature_id']);
            $table->dropColumn('feedback_feature_id');
        });
    }
};
