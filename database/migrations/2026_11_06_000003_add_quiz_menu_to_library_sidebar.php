<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Library sidebar: "Student Quiz" link under Advanced Management (owner only page). */
return new class extends Migration
{
    public function up(): void
    {
        $parent = DB::table('menus')->where('guard', 'library')->whereNull('deleted_at')
            ->where('url', 'exam.index')->whereNotNull('parent_id')->value('parent_id');
        if (! $parent) {
            return;
        }
        $exists = DB::table('menus')->where('guard', 'library')->where('url', 'quiz.index')->whereNull('deleted_at')->exists();
        if ($exists) {
            return;
        }
        DB::table('menus')->insert([
            'name' => 'Student Quiz', 'url' => 'quiz.index', 'icon' => null, 'parent_id' => $parent, 'order' => 6,
            'guard' => 'library', 'status' => 1, 'has_permissions' => null, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('menus')->where('guard', 'library')->where('url', 'quiz.index')->delete();
    }
};
