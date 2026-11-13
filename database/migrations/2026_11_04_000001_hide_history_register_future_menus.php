<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Members list redesign: past bookings (expired, closed, deleted) and future bookings are
 * status filters of the Learner List, so the "Learner History" and "Future Bookings" menu
 * items are hidden, and so is "Library Register" (seat history). Hidden (status 0), not
 * deleted — their routes still work (history and future redirect to the filtered list).
 */
class HideHistoryRegisterFutureMenus extends Migration
{
    private array $urls = ['learnerHistory', 'future.bookings', 'seats.history'];

    public function up()
    {
        DB::table('menus')->whereIn('url', $this->urls)->update(['status' => 0, 'updated_at' => now()]);
    }

    public function down()
    {
        DB::table('menus')->whereIn('url', $this->urls)->update(['status' => 1, 'updated_at' => now()]);
    }
}
