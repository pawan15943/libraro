<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddMessageTemplatesMenuItem extends Migration
{
    public function up()
    {
        $exists = DB::table('menus')->where('url', 'message.templates')->exists();

        if ($exists) {
            return;
        }

        $maxOrder = DB::table('menus')->where('parent_id', 2)->max('order') ?? 0;

        DB::table('menus')->insert([
            'parent_id' => 2,
            'name' => 'Message Templates',
            'url' => 'message.templates',
            'icon' => null,
            'guard' => 'library',
            'has_permissions' => 'WhatsApp Notification',
            'order' => $maxOrder + 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        DB::table('menus')->where('url', 'message.templates')->delete();
    }
}
