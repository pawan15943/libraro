<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When a branch opens. With `hour` (how many hours it stays open) it gives the library's
 * open – close window: closes at open_time + hour (past midnight when needed). NULL for a
 * branch open 24 hours, or one whose time isn't set yet.
 * Existing branches are filled from their shifts: php artisan branches:fill-open-time
 */
class AddOpenTimeToHourTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('hour', 'open_time')) {
            Schema::table('hour', function (Blueprint $table) {
                $table->time('open_time')->nullable()->after('hour');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('hour', 'open_time')) {
            Schema::table('hour', function (Blueprint $table) {
                $table->dropColumn('open_time');
            });
        }
    }
}
