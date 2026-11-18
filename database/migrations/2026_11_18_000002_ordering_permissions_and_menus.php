<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ordering & Services: two library permissions and the "Orders" sidebar group.
 *
 *  - Ordering Setup  → categories, items, cafeteria tables, settings (owner / manager)
 *  - Manage Orders   → the live order board (canteen staff); its menu is added with the board
 *
 * Both are in the Pro plan (Pro = every library permission). Rows are found by name, never by id.
 */
return new class extends Migration
{
    private const GUARD = 'library';

    private const PRO = 3;

    private const CATEGORY = 'Ordering & Services';

    private const PERMISSIONS = [
        ['Ordering Setup', 'Menu, items, cafeteria tables and settings of Ordering & Services'],
        ['Manage Orders', 'Receive, prepare and deliver learner orders; record their payments'],
    ];

    public function up(): void
    {
        $now = now();

        $catId = DB::table('permission_categories')->where('name', self::CATEGORY)->value('id')
            ?? DB::table('permission_categories')->insertGetId(['name' => self::CATEGORY, 'created_at' => $now, 'updated_at' => $now]);

        foreach (self::PERMISSIONS as [$name, $desc]) {
            $id = DB::table('permissions')->where('guard_name', self::GUARD)->where('name', $name)->value('id')
                ?? DB::table('permissions')->insertGetId([
                    'name' => $name, 'slug' => Str::slug($name), 'guard_name' => self::GUARD,
                    'permission_category_id' => $catId, 'description' => $desc, 'created_at' => $now, 'updated_at' => $now,
                ]);
            if (! DB::table('subscription_permission')->where('subscription_id', self::PRO)->where('permission_id', $id)->exists()) {
                DB::table('subscription_permission')->insert(['subscription_id' => self::PRO, 'permission_id' => $id, 'created_at' => $now]);
            }
        }

        // Sidebar: "Orders" group right after Student Chat, with Menu & Items and Order Settings
        $parentId = DB::table('menus')->where('guard', self::GUARD)->whereNull('parent_id')->where('name', 'Orders')->whereNull('deleted_at')->value('id')
            ?? DB::table('menus')->insertGetId([
                'name' => 'Orders', 'url' => 'ordering.items', 'icon' => 'fa fa-mug-hot', 'parent_id' => null, 'order' => 4,
                'guard' => self::GUARD, 'status' => 1, 'has_permissions' => null, 'created_at' => $now, 'updated_at' => $now,
            ]);
        foreach ([['Menu & Items', 'ordering.items', 2], ['Order Settings', 'ordering.settings', 3]] as [$name, $url, $order]) {
            if (! DB::table('menus')->where('guard', self::GUARD)->where('parent_id', $parentId)->where('url', $url)->whereNull('deleted_at')->exists()) {
                DB::table('menus')->insert([
                    'name' => $name, 'url' => $url, 'icon' => null, 'parent_id' => $parentId, 'order' => $order,
                    'guard' => self::GUARD, 'status' => 1, 'has_permissions' => 'Ordering Setup', 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        $this->forgetPermissionCache();
    }

    public function down(): void
    {
        $parentId = DB::table('menus')->where('guard', self::GUARD)->whereNull('parent_id')->where('name', 'Orders')->value('id');
        if ($parentId) {
            DB::table('menus')->where('parent_id', $parentId)->delete();
            DB::table('menus')->where('id', $parentId)->delete();
        }

        $ids = DB::table('permissions')->where('guard_name', self::GUARD)->whereIn('name', array_column(self::PERMISSIONS, 0))->pluck('id');
        DB::table('subscription_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('library_user_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        DB::table('permission_categories')->where('name', self::CATEGORY)->delete();

        $this->forgetPermissionCache();
    }

    private function forgetPermissionCache(): void
    {
        try {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
        }
    }
};
