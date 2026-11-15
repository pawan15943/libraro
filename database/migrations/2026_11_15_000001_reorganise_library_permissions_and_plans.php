<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Library permissions and Libraro plans, cleaned up (guard `library` only; admin and CRM permissions
 * are not touched). Rows are found by name, so ids may differ between databases.
 *
 *  1. Names: trailing spaces removed ("Add Locker Amount ", "Reminder Notification ").
 *  2. Missing permissions the panel already checks are added: Export Learners, Library Settings
 *     (the Library Settings menu asked for "Library Genral Settings", which never existed).
 *  3. Permissions nothing checks any more are removed (a copy is kept in
 *     `permission_cleanup_backups`, so down() brings them back with their plan and staff links).
 *  4. Categories get clear names and every permission sits in the right one.
 *  5. Two plans instead of three: Smart is removed and its libraries move to Pro.
 *     Basic = everyday operations and menus; Pro = every permission.
 *  6. The plan feature list (pricing, choose plan) is rebuilt for Basic and Pro.
 */
return new class extends Migration
{
    private const GUARD = 'library';
    private const BASIC = 1;
    private const SMART = 2;
    private const PRO = 3;

    /** Category id => [new name, old name] (ids kept: staff screens group by them). */
    private const CATEGORIES = [
        1 => ['Dashboard', 'Dashboard Permission'],
        2 => ['Menus', 'Menu Permission'],
        3 => ['Learner Operations', 'Operation Permission'],
        4 => ['Branch Settings', 'Branch Operation Pemission'],
        5 => ['Shifts', 'Seat Plan Permission'],
        6 => ['Reports', 'Report Permission'],
        7 => ['Advanced Features', 'Advanced Permission'],
        8 => ['Notifications', 'Notification Permission'],
        9 => ['Masters', 'Master Permissions'],
    ];
    private const NEW_CATEGORY = 'Payments & Expenses';

    /** Permissions no page, menu, API or Limo check uses. */
    private const REMOVE = [
        'Welcome Banner', 'Delate Seat Count', 'Total Seats',
        'Library Register', 'Learners History', 'Future Bookings', 'Library Master Console', 'Library Corner',
        'Library configuration', 'Student Corner', 'Show / Hide Options', 'Video Tutorial', 'Video Training',
        'Suggestions', 'Feedback', 'Complaints', 'Book Category', 'Reminder Notification', 'Download Receipt',
        'Library Activity Report', 'Activity Report', 'Attendence Report',
        'Learner Login', 'Library filter', 'Extended Seat Highlighted', 'Expired Seat highlight',
        'Pay Later Seat Highlight', 'Pending Payment Highlight', 'Overdue Payment Highlight',
        'Email Notification',
    ];

    /** Added: the panel checks these names already. [name, category id or NEW_CATEGORY, description] */
    private const ADD = [
        ['Export Learners', 7, 'Download the learner list (CSV / PDF)'],
        ['Library Settings', 2, 'Library settings page (website favicon, colour, title, description)'],
    ];

    /** Permission => category (by name); anything not listed keeps its category. */
    private const MOVE = [
        'QR Seat Booking' => 3,
        'Download Payment Receipt' => self::NEW_CATEGORY,
        'Receipt Generation' => self::NEW_CATEGORY,
        'Pending Payment' => self::NEW_CATEGORY,
        'Add Misllaneous Payment' => self::NEW_CATEGORY,
        'Add Daily Expense' => self::NEW_CATEGORY,
        'Monthly Report' => 6,
        'Import Learners' => 7,
        'Import Library Settings' => 7,
        'Manage Feature Toggles' => 7,
        'User management' => 7,
        'Add User' => 7,
        'Add User Master' => 9,
    ];

    /** Pro only; Basic gets every other library permission. */
    private const PRO_ONLY = [
        // dashboard insights
        'Monthly Financial Overview', 'Year and Month Filter', 'Library Analytics', 'Plan wise count',
        // online booking and messages
        'QR Seat Booking', 'WhatsApp Notification',
        // advanced learner operations
        'Freez Days', 'Gift Days',
        // advanced shifts
        'Hourly Slot 1', 'Hourly Slot 2', 'Hourly Slot 3', 'Hourly Slot 4', 'Custom Plan',
        // deeper reports
        'Monthly Revenue Report', 'Monthly Report', 'Attendance Report', 'Expired Learners Report', 'Learner Report',
        // staff accounts
        'Add User', 'Add User Master', 'User management',
        // import / export and settings
        'Import Learners', 'Import Student', 'Import Library Settings', 'Export Learners', 'Manage Feature Toggles',
    ];

    /** Plan feature list: [category, feature, in Basic?] — Pro has every row. */
    private const FEATURES = [
        ['Seat Booking', 'Seat map with direct booking', true],
        ['Seat Booking', 'General seats (no seat number)', true],
        ['Seat Booking', 'Future bookings', true],
        ['Seat Booking', 'Locker, discount and token money', true],
        ['Seat Booking', 'Custom plan dates (own start and end date)', false],
        ['Seat Booking', 'More than one shift in a booking', false],
        ['Seat Booking', 'Advanced shifts: hourly, flexible and direct', false],
        ['Seat Booking', 'Online seat booking with QR code', false],
        ['Seat Booking', 'Demo / trial bookings', false],
        ['Learners', 'Learner list, search and profile', true],
        ['Learners', 'Renew, upgrade, change plan and swap seat', true],
        ['Learners', 'Close, delete, restore and reactivate', true],
        ['Learners', 'ID cards and payment receipts', true],
        ['Learners', 'Freeze plan and gift days', false],
        ['Learners', 'Import learners from Excel / CSV', false],
        ['Learners', 'Export learner data', false],
        ['Learners', 'Student quizzes', false],
        ['Payments', 'Part payment and pay later', true],
        ['Payments', 'Dues collection and settlement', true],
        ['Payments', 'Daily expenses and other payments', true],
        ['Payments', 'Global discount offers', false],
        ['Attendance', 'QR attendance and manual marking', true],
        ['Attendance', 'Attendance summary', true],
        ['Reports & Dashboard', "Dashboard with today's money and seats", true],
        ['Reports & Dashboard', 'Reports: collections, dues, renewals, profit & loss, growth', false],
        ['Reports & Dashboard', 'Monthly analytics with date filters', false],
        ['Team & Setup', 'One branch with floors and shifts', true],
        ['Team & Setup', 'More than one branch', false],
        ['Team & Setup', 'Plans, plan types, prices, expense and exam names', false],
        ['Team & Setup', 'Staff logins with their own permissions', false],
        ['Team & Setup', 'Show / hide options per branch', false],
        ['Messages', 'Free WhatsApp reminders', true],
        ['Messages', 'Automatic WhatsApp & SMS (buy credits)', false],
        ['Support', 'Limo AI assistant and video tutorials', true],
        ['Support', 'Works on mobile and desktop', true],
    ];

    private function perm(string $name)
    {
        return DB::table('permissions')->where('guard_name', self::GUARD)->where('name', $name)->first();
    }

    private function backup(string $key, $value): void
    {
        DB::table('permission_cleanup_backups')->updateOrInsert(['key' => $key], ['value' => json_encode($value), 'created_at' => now()]);
    }

    private function restored(string $key)
    {
        $v = DB::table('permission_cleanup_backups')->where('key', $key)->value('value');

        return $v === null ? null : json_decode($v, true);
    }

    public function up(): void
    {
        if (!Schema::hasTable('permission_cleanup_backups')) {
            Schema::create('permission_cleanup_backups', function (Blueprint $t) {
                $t->string('key', 191)->primary();
                $t->longText('value');
                $t->timestamp('created_at')->nullable();
            });
        }
        $now = now();

        // 1. names without trailing spaces
        foreach (DB::table('permissions')->where('guard_name', self::GUARD)->get(['id', 'name']) as $p) {
            // MySQL ignores trailing spaces in "=", so the lookup finds this same row: compare ids
            $same = $this->perm(trim($p->name));
            if ($p->name !== trim($p->name) && (!$same || (int) $same->id === (int) $p->id)) {
                DB::table('permissions')->where('id', $p->id)->update(['name' => trim($p->name), 'updated_at' => $now]);
            }
        }

        // 4a. categories: names, and the new one
        $this->backup('category_names', DB::table('permission_categories')->whereIn('id', array_keys(self::CATEGORIES))->pluck('name', 'id'));
        foreach (self::CATEGORIES as $id => [$name]) {
            DB::table('permission_categories')->where('id', $id)->update(['name' => $name, 'updated_at' => $now]);
        }
        $newCat = DB::table('permission_categories')->where('name', self::NEW_CATEGORY)->value('id')
            ?? DB::table('permission_categories')->insertGetId(['name' => self::NEW_CATEGORY, 'created_at' => $now, 'updated_at' => $now]);
        $cat = fn ($c) => $c === self::NEW_CATEGORY ? $newCat : $c;

        // 2. permissions the panel checks but that did not exist
        foreach (self::ADD as [$name, $c, $desc]) {
            if (!$this->perm($name)) {
                DB::table('permissions')->insert([
                    'name' => $name, 'slug' => \Illuminate\Support\Str::slug($name), 'guard_name' => self::GUARD,
                    'permission_category_id' => $cat($c), 'description' => $desc, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
        DB::table('menus')->where('has_permissions', 'Library Genral Settings')->update(['has_permissions' => 'Library Settings', 'updated_at' => $now]);

        // 3. remove unused permissions (copy kept for down())
        $gone = DB::table('permissions')->where('guard_name', self::GUARD)->whereIn('name', self::REMOVE)->get();
        if ($gone->isNotEmpty()) {
            $ids = $gone->pluck('id');
            $this->backup('removed_permissions', [
                'permissions' => $gone,
                'subscription_permission' => DB::table('subscription_permission')->whereIn('permission_id', $ids)->get(),
                'library_user_permissions' => DB::table('library_user_permissions')->whereIn('permission_id', $ids)->get(),
            ]);
            DB::table('subscription_permission')->whereIn('permission_id', $ids)->delete();
            DB::table('library_user_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }

        // 4b. every permission in the right category
        $this->backup('permission_categories_of', DB::table('permissions')->where('guard_name', self::GUARD)->pluck('permission_category_id', 'name'));
        foreach (self::MOVE as $name => $c) {
            DB::table('permissions')->where('guard_name', self::GUARD)->where('name', $name)->update(['permission_category_id' => $cat($c), 'updated_at' => $now]);
        }

        // 5. two plans: Smart's libraries move to Pro, Smart is removed
        $this->backup('smart_libraries', DB::table('libraries')->where('library_type', self::SMART)->pluck('id'));
        $this->backup('plan_permissions', DB::table('subscription_permission')->whereIn('subscription_id', [self::BASIC, self::SMART, self::PRO])->get(['subscription_id', 'permission_id']));
        DB::table('libraries')->where('library_type', self::SMART)->update(['library_type' => self::PRO, 'updated_at' => $now]);
        DB::table('subscriptions')->where('id', self::SMART)->whereNull('deleted_at')->update(['deleted_at' => $now, 'updated_at' => $now]);

        // Basic: everyday permissions; Pro: all of them
        $all = DB::table('permissions')->where('guard_name', self::GUARD)->whereNull('deleted_at')->pluck('id', 'name');
        $basic = $all->except(self::PRO_ONLY);
        DB::table('subscription_permission')->whereIn('subscription_id', [self::BASIC, self::SMART, self::PRO])->delete();
        $rows = [];
        foreach ($basic as $id) {
            $rows[] = ['subscription_id' => self::BASIC, 'permission_id' => $id, 'created_at' => $now];
        }
        foreach ($all as $id) {
            $rows[] = ['subscription_id' => self::PRO, 'permission_id' => $id, 'created_at' => $now];
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('subscription_permission')->insert($chunk);
        }

        // 6. plan feature list for Basic and Pro (the old rows are kept, soft-deleted)
        $this->backup('feature_ids', DB::table('subscription_plan_features')->whereNull('deleted_at')->pluck('id'));
        DB::table('subscription_plan_features')->whereNull('deleted_at')->update(['deleted_at' => $now]);
        $features = [];
        foreach (self::FEATURES as [$category, $name, $inBasic]) {
            $features[] = ['name' => $name, 'subscription_id' => self::BASIC, 'feature_status' => $inBasic ? 1 : 0, 'category' => $category, 'created_at' => $now, 'updated_at' => $now];
            $features[] = ['name' => $name, 'subscription_id' => self::PRO, 'feature_status' => 1, 'category' => $category, 'created_at' => $now, 'updated_at' => $now];
        }
        DB::table('subscription_plan_features')->insert($features);

        $this->forgetPermissionCache();
    }

    public function down(): void
    {
        if (!Schema::hasTable('permission_cleanup_backups')) {
            return;
        }
        $now = now();

        // 6. features
        DB::table('subscription_plan_features')->whereNull('deleted_at')->delete();
        if ($ids = $this->restored('feature_ids')) {
            DB::table('subscription_plan_features')->whereIn('id', $ids)->update(['deleted_at' => null]);
        }

        // 5. plans
        DB::table('subscriptions')->where('id', self::SMART)->update(['deleted_at' => null, 'updated_at' => $now]);
        if ($ids = $this->restored('smart_libraries')) {
            DB::table('libraries')->whereIn('id', $ids)->update(['library_type' => self::SMART, 'updated_at' => $now]);
        }
        DB::table('subscription_permission')->whereIn('subscription_id', [self::BASIC, self::SMART, self::PRO])->delete();

        // 3. removed permissions come back with their old ids and links
        if ($r = $this->restored('removed_permissions')) {
            foreach ($r['permissions'] as $p) {
                DB::table('permissions')->insertOrIgnore($p);
            }
            foreach (array_chunk($r['library_user_permissions'], 200) as $chunk) {
                DB::table('library_user_permissions')->insertOrIgnore($chunk);
            }
            // their plan links (the plan backup below was taken after they were removed)
            foreach (array_chunk($r['subscription_permission'], 200) as $chunk) {
                DB::table('subscription_permission')->insert($chunk);
            }
        }
        if ($rows = $this->restored('plan_permissions')) {
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('subscription_permission')->insert(array_map(fn ($x) => $x + ['created_at' => $now], $chunk));
            }
        }

        // 4. categories
        if ($of = $this->restored('permission_categories_of')) {
            foreach ($of as $name => $c) {
                DB::table('permissions')->where('guard_name', self::GUARD)->where('name', $name)->update(['permission_category_id' => $c]);
            }
        }
        if ($names = $this->restored('category_names')) {
            foreach ($names as $id => $name) {
                DB::table('permission_categories')->where('id', $id)->update(['name' => $name]);
            }
        }
        DB::table('permission_categories')->where('name', self::NEW_CATEGORY)->delete();

        // 2. added permissions
        DB::table('menus')->where('has_permissions', 'Library Settings')->update(['has_permissions' => 'Library Genral Settings']);
        $added = DB::table('permissions')->where('guard_name', self::GUARD)->whereIn('name', array_column(self::ADD, 0))->pluck('id');
        DB::table('subscription_permission')->whereIn('permission_id', $added)->delete();
        DB::table('library_user_permissions')->whereIn('permission_id', $added)->delete();
        DB::table('permissions')->whereIn('id', $added)->delete();

        Schema::dropIfExists('permission_cleanup_backups');
        $this->forgetPermissionCache();
    }

    private function forgetPermissionCache(): void
    {
        try {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // cache not writable from the CLI; the has-permission gate refreshes it on first use
        }
    }
};
