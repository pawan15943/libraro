<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\DB;
use App\Models\Permission; 


class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
         $this->registerPolicies();

    Gate::define('has-permission', function ($user, $permissionName) {
        if (empty($permissionName)) {
            return true;
        }

        if (Auth::guard('web')->check() || $user instanceof \App\Models\User) {
            if (method_exists($user, 'hasRole') && ($user->hasRole('superadmin') || $user->hasRole('Super Admin'))) {
                return true;
            }

            // Direct check in admin_user_permissions table for admin users
            $hasAdminPerm = DB::table('admin_user_permissions')
                ->join('permissions', 'permissions.id', '=', 'admin_user_permissions.permission_id')
                ->where('admin_user_permissions.user_id', $user->id)
                ->where('permissions.name', $permissionName)
                ->exists();

            if ($hasAdminPerm) {
                return true;
            }

            // Fallback to Spatie permission check if roles used
            if (method_exists($user, 'hasPermissionTo')) {
                try {
                    return $user->hasPermissionTo($permissionName);
                } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
                    // The cached permission list is older than the permission (e.g. added by a
                    // migration): refresh the cache once and ask again; unknown → not allowed.
                    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
                    try {
                        return $user->fresh()->hasPermissionTo($permissionName);
                    } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
                        return false;
                    }
                }
            }

            return false;
        }

        if (Auth::guard('library')->check() || Auth::guard('library_user')->check()) {
            return in_array(trim((string) $permissionName), self::libraryPermissionNames($user), true);
        }

        return false;
    });
    }

    /**
     * Permission names a library owner or a staff member holds, read once per request (a dashboard
     * asks dozens of times). Owner: everything in the library's plan. Staff: what the owner gave them,
     * and only inside the library's plan — a permission the plan does not include never works.
     */
    public static function libraryPermissionNames($user): array
    {
        if (!$user) {
            return [];
        }
        $key = 'lib_perm_names:' . get_class($user) . ':' . $user->getKey();
        $request = request();
        if ($request && $request->attributes->has($key)) {
            return $request->attributes->get($key);
        }

        $planId = $user instanceof \App\Models\LibraryUser
            ? DB::table('libraries')->where('id', $user->library_id)->value('library_type')
            : ($user->library_type ?? null);

        $names = [];
        if ($planId) {
            $query = DB::table('subscription_permission')
                ->join('permissions', 'permissions.id', '=', 'subscription_permission.permission_id')
                ->where('subscription_permission.subscription_id', $planId)
                ->whereNull('permissions.deleted_at');
            if ($user instanceof \App\Models\LibraryUser) {
                $query->whereExists(function ($q) use ($user) {
                    $q->select(DB::raw(1))->from('library_user_permissions')
                        ->whereColumn('library_user_permissions.permission_id', 'permissions.id')
                        ->where('library_user_permissions.library_user_id', $user->id);
                });
            }
            $names = $query->distinct()->pluck('permissions.name')->map(fn ($n) => trim($n))->all();
        }

        if ($request) {
            $request->attributes->set($key, $names);
        }

        return $names;
    }
}
