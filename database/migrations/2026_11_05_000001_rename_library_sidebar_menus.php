<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Shorter library sidebar labels. Only the names change; urls and has_permissions
 * (the permission keys) stay as they are. Admin (web guard) menus are not touched.
 */
class RenameLibrarySidebarMenus extends Migration
{
    // url => [old name, new name]
    private array $names = [
        'library'             => ['Manage Library', 'Library'],
        'seats'               => ['Seat Assignment', 'Seat Map'],
        'demo-users.index'    => ['Daily Demo Inquiries', 'Demo Inquiry'],
        'library.upload.form' => ['Import Learners', 'Import Data'],
    ];

    public function up()
    {
        foreach ($this->names as $url => [$old, $new]) {
            DB::table('menus')->where('guard', 'library')->where('url', $url)->update(['name' => $new, 'updated_at' => now()]);
        }
    }

    public function down()
    {
        foreach ($this->names as $url => [$old, $new]) {
            DB::table('menus')->where('guard', 'library')->where('url', $url)->update(['name' => $old, 'updated_at' => now()]);
        }
    }
}
