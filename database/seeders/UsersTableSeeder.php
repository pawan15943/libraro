<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@admin.com', // Change this to the desired email
            'password' => bcrypt('password'), // Change this to a secure password
        ]);

        // Assign role to user
        $admin->assignRole('superadmin');
    }
}
