<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The superadmin role gets every admin-portal (guard "web") permission, including
 * the crm-* ones. The has-permission gate already lets superadmin through; this
 * also makes the role page and Spatie checks show the full set.
 */
return new class extends Migration
{
    public function up(): void
    {
        $roleId = DB::table('roles')->where('name', 'superadmin')->where('guard_name', 'web')->value('id');
        if (! $roleId) {
            return;
        }

        $rows = DB::table('permissions')->where('guard_name', 'web')->whereNull('deleted_at')->pluck('id')
            ->map(fn ($id) => ['permission_id' => $id, 'role_id' => $roleId])->all();
        DB::table('role_has_permissions')->insertOrIgnore($rows);

        try {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // cache not writable from the CLI; the has-permission gate refreshes it on first use
        }
    }

    public function down(): void
    {
        // nothing to undo safely: the role may have had these permissions before
    }
};
