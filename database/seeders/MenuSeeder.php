<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Menu;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dashboard = Menu::create(['name' => 'Dashboard', 'route' => 'home', 'order' => 1]);
        $library = Menu::create(['name' => 'Manage Library', 'url' => 'library', 'order' => 2]);
        $libraryPlna = Menu::create(['name' => 'Library Plan', 'url' => 'subscriptions.choosePlan', 'order' => 2]);
        $users = Menu::create(['name' => 'User', 'route' => null, 'order' => 2]);
        $masters = Menu::create(['name' => 'Master', 'route' => null, 'order' => 3]);
        $suggestions = Menu::create(['name' => 'Suggestions', 'route' => null, 'order' => 8]);
        $feedback = Menu::create(['name' => 'Feedback', 'route' => null, 'order' => 9]);
        $settings = Menu::create(['name' => 'Library Settings', 'route' => null, 'order' => 10]);
        

        // Add submenus
        Menu::create(['name' => 'Role', 'route' => 'settings.general', 'parent_id' => $users->id, 'order' => 1]);
        Menu::create(['name' => 'Permission', 'route' => 'settings.profile', 'parent_id' => $users->id, 'order' => 2]);

        Menu::create(['name' => 'Library List', 'url' => 'library', 'parent_id' => $library->id, 'order' => 1]);
        Menu::create(['name' => 'Add Subscription', 'url' => 'subscriptions.permissions', 'parent_id' => $library->id, 'order' => 2]);
        Menu::create(['name' => 'Add Subscription', 'url' => 'subscriptions.choosePlan', 'parent_id' => $libraryPlna->id, 'order' => 1]);
    }
}
