<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

trait HasLibraryUserPermissions
{
    public function can($permission, $arguments = [])
    {
        $guard = $this->getGuardName();

        // Check for library_user guard
        if ($guard === 'library_user') {
            // Handle string and object input
            $permissionName = is_string($permission)
                ? $permission
                : ($permission instanceof Permission ? $permission->name : null);

            if (!$permissionName) {
                return false;
            }

            return DB::table('library_user_permissions')
                ->join('permissions', 'permissions.id', '=', 'library_user_permissions.permission_id')
                ->where('library_user_permissions.library_user_id', $this->id)
                ->where('permissions.name', $permissionName)
                ->exists();
        }

        // Fallback to default behavior for other guards
        return parent::can($permission, $arguments);
    }

    public function getGuardName()
    {
        return property_exists($this, 'guard_name') ? $this->guard_name : config('auth.defaults.guard');
    }
}
