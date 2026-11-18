<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ordering & Services, step 3: "Order Board" first in the Orders sidebar group, for staff with
 * "Manage Orders" (the permission itself came with 2026_11_18_000002).
 */
return new class extends Migration
{
    public function up(): void
    {
        $parentId = DB::table('menus')->where('guard', 'library')->whereNull('parent_id')->where('name', 'Orders')->whereNull('deleted_at')->value('id');
        if (! $parentId) {
            return;
        }
        $exists = DB::table('menus')->where('guard', 'library')->where('parent_id', $parentId)->where('url', 'orders.board')->whereNull('deleted_at')->exists();
        if (! $exists) {
            DB::table('menus')->insert([
                'name' => 'Order Board', 'url' => 'orders.board', 'icon' => null, 'parent_id' => $parentId, 'order' => 1,
                'guard' => 'library', 'status' => 1, 'has_permissions' => 'Manage Orders', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('menus')->where('guard', 'library')->where('url', 'orders.board')->delete();
    }
};
