<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ordering & Services, step 6: "Order Reports" in the Orders sidebar group (permission Manage Orders).
 */
return new class extends Migration
{
    public function up(): void
    {
        $parentId = DB::table('menus')->where('guard', 'library')->whereNull('parent_id')->where('name', 'Orders')->whereNull('deleted_at')->value('id');
        if (! $parentId) {
            return;
        }
        $exists = DB::table('menus')->where('guard', 'library')->where('parent_id', $parentId)->where('url', 'orders.reports')->whereNull('deleted_at')->exists();
        if (! $exists) {
            DB::table('menus')->insert([
                'name' => 'Order Reports', 'url' => 'orders.reports', 'icon' => null, 'parent_id' => $parentId, 'order' => 4,
                'guard' => 'library', 'status' => 1, 'has_permissions' => 'Manage Orders', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('menus')->where('guard', 'library')->where('url', 'orders.reports')->delete();
    }
};
