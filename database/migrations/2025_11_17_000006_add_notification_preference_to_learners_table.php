<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotificationPreferenceToLearnersTable extends Migration
{
    public function up()
    {
        Schema::table('learners', function (Blueprint $table) {
            $table->tinyInteger('notification_preference')->default(3)->after('email');
        });
    }

    public function down()
    {
        Schema::table('learners', function (Blueprint $table) {
            $table->dropColumn('notification_preference');
        });
    }
}
