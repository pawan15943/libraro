<?php
namespace App\Extensions;

use Spatie\Permission\PermissionRegistrar;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

class CustomPermissionRegistrar extends PermissionRegistrar
{
    public function hasPermissionTo(Authenticatable $user, $permission, $guardName = null): bool
    {
        // Support library_user guard using custom table
        if ($guardName === 'library_user' || $user instanceof \App\Models\LibraryUser) {
            $permissionName = is_string($permission) ? $permission : $permission->name;

            return DB::table('library_user_permissions')
                ->join('permissions', 'permissions.id', '=', 'library_user_permissions.permission_id')
                ->where('library_user_permissions.library_user_id', $user->id)
                ->where('permissions.name', $permissionName)
                ->exists();
        }

        // Fallback for other guards
        return parent::hasPermissionTo($user, $permission, $guardName);
    }
}
