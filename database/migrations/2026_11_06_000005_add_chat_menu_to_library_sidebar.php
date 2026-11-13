<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Library sidebar: "Student Chat" (owner only) as a top-level link. */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('menus')->where('guard', 'library')->where('url', 'chat.index')->whereNull('deleted_at')->exists()) {
            return;
        }
        DB::table('menus')->insert([
            'name' => 'Student Chat', 'url' => 'chat.index', 'icon' => 'fa fa-comments', 'parent_id' => null, 'order' => 12,
            'guard' => 'library', 'status' => 1, 'has_permissions' => null, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('menus')->where('guard', 'library')->where('url', 'chat.index')->delete();
    }
};
