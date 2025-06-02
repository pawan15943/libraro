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

        if (Auth::guard('library')->check()) {
            // For library guard using subscription_permissions
            return $user->subscription &&
                   $user->subscription->permissions()->where('name', $permissionName)->exists();
        }

        if (Auth::guard('library_user')->check()) {
            // For library_user guard using library_user_permission table
            return DB::table('library_user_permissions')
                     ->join('permissions', 'permissions.id', '=', 'library_user_permissions.permission_id')
                     ->where('library_user_permissions.library_user_id', $user->id)
                     ->where('permissions.name', $permissionName)
                     ->exists();
        }

        return false;
    });
    }
}
