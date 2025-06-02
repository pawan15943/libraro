<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        Permission::create(['name' => 'view dashboard']);
        Permission::create(['name' => 'user-create']);
        Permission::create(['name' => 'user-edit']);
        Permission::create(['name' => 'user-delete']);
        Permission::create(['name' => 'request-create']);
        Permission::create(['name' => 'request-show']);
        Permission::create(['name' => 'request-edit']);
        Permission::create(['name' => 'request-delete']);
        Permission::create(['name' => 'request-approved']);
        // Add more permissions as needed

        // Create roles and assign created permissions
        $superadminRole = Role::create(['name' => 'superadmin']);
        $superadminRole->givePermissionTo('view dashboard');
        $superadminRole->givePermissionTo('user-create');
        $superadminRole->givePermissionTo('user-edit');
        $superadminRole->givePermissionTo('user-delete');
        $superadminRole->givePermissionTo('request-create');
        $superadminRole->givePermissionTo('request-show');
        $superadminRole->givePermissionTo('request-edit');
        $superadminRole->givePermissionTo('request-delete');
        $superadminRole->givePermissionTo('request-approved');

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo('view dashboard');
        $adminRole->givePermissionTo('user-create');
        $adminRole->givePermissionTo('user-edit');
        $adminRole->givePermissionTo('user-delete');
        $adminRole->givePermissionTo('request-create');
        $adminRole->givePermissionTo('request-show');
        $adminRole->givePermissionTo('request-edit');
        $adminRole->givePermissionTo('request-delete');
        $adminRole->givePermissionTo('request-approved');

        // Add more roles and assign permissions as needed
    }
}
