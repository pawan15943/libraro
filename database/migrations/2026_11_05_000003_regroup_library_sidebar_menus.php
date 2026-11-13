<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Library sidebar regrouped (guard `library` only; urls and permission keys of existing
 * items are unchanged, rows are found by url so ids may differ between databases):
 *
 *   Setup Library        Branch, Shift, Floors
 *   Staff Management     Users, Role, Permission            (new group; Role and Permission are new view-only pages)
 *   Advanced Management  Plan, Plan Type, Plan Price, Expense Names, Exam Names, Show / Hide Options, Setup Library via CSV
 *   Subscription         Plan, Transactions                 (new group)
 *   Account              Profile, Setting, Referral, Feedback  (Setting and Feedback are new links to existing pages)
 *
 * Dashboard, Library, Attendance, Reports, Library Enquiry and Trxn Management stay as they are.
 */
class RegroupLibrarySidebarMenus extends Migration
{
    private function q()
    {
        return DB::table('menus')->where('guard', 'library')->whereNull('deleted_at');
    }

    /** The top level menu whose child has this url. */
    private function parentOf(string $childUrl)
    {
        $pid = $this->q()->where('url', $childUrl)->whereNotNull('parent_id')->value('parent_id');
        return $pid ? $this->q()->where('id', $pid)->first() : null;
    }

    private function child(?int $parentId, string $url, array $set): void
    {
        $this->q()->where('url', $url)->when($parentId, fn ($q) => $q->where('parent_id', $parentId), fn ($q) => $q->whereNotNull('parent_id'))
            ->update($set + ['updated_at' => now()]);
    }

    private function insert(array $row): int
    {
        return DB::table('menus')->insertGetId($row + ['guard' => 'library', 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function up()
    {
        // Setup Library (was Library Master Console): Branch, Shift, Floors
        $setup = $this->parentOf('branch.list');
        $advanced = $this->parentOf('plan.index');
        $account = $this->parentOf('profile');
        $usersRow = $this->q()->where('url', 'library-users.index')->whereNotNull('parent_id')->first();

        if ($setup) {
            DB::table('menus')->where('id', $setup->id)->update(['name' => 'Setup Library', 'url' => 'branch.list', 'order' => 5, 'updated_at' => now()]);
            $this->child($setup->id, 'branch.list', ['name' => 'Branch', 'order' => 1]);
            $this->child($setup->id, 'library.configration', ['name' => 'Shift', 'order' => 2]);
            $this->child(null, 'floor.index', ['parent_id' => $setup->id, 'name' => 'Floors', 'order' => 3]);
        }

        // Staff Management: Users, Role, Permission
        $staff = $this->insert(['name' => 'Staff Management', 'url' => 'library-users.index', 'icon' => 'fa fa-users', 'parent_id' => null, 'order' => 6, 'has_permissions' => 'Add User']);
        if ($usersRow) {
            DB::table('menus')->where('id', $usersRow->id)->update(['parent_id' => $staff, 'name' => 'Users', 'order' => 1, 'updated_at' => now()]);
        }
        $this->insert(['name' => 'Role', 'url' => 'library-users.roles', 'parent_id' => $staff, 'order' => 2, 'has_permissions' => 'Add User']);
        $this->insert(['name' => 'Permission', 'url' => 'library-users.permission-list', 'parent_id' => $staff, 'order' => 3, 'has_permissions' => 'Add User']);

        // Advanced Management (was Custom Management)
        if ($advanced) {
            DB::table('menus')->where('id', $advanced->id)->update(['name' => 'Advanced Management', 'order' => 7, 'updated_at' => now()]);
            foreach ([['plan.index', 'Plan', 1], ['plantype.index', 'Plan Type', 2], ['planPrice.index', 'Plan Price', 3],
                      ['expense.index', 'Expense Names', 4], ['exam.index', 'Exam Names', 5], ['toggle.feature', null, 6], ['library.master', null, 7]] as [$url, $name, $order]) {
                $this->child($advanced->id, $url, array_filter(['name' => $name, 'order' => $order]));
            }
        }

        // Subscription: Plan, Transactions (moved out of Account Settings)
        $sub = $this->insert(['name' => 'Subscription', 'url' => 'library.myplan', 'icon' => 'fa fa-gem', 'parent_id' => null, 'order' => 8, 'has_permissions' => 'My Plans']);
        $this->child(null, 'library.myplan', ['parent_id' => $sub, 'name' => 'Plan', 'order' => 1]);
        $this->child(null, 'library.transaction', ['parent_id' => $sub, 'name' => 'Transactions', 'order' => 2]);

        // Account (was Account Settings): Profile, Setting, Referral, Feedback
        if ($account) {
            DB::table('menus')->where('id', $account->id)->update(['name' => 'Account', 'url' => 'profile', 'order' => 9, 'updated_at' => now()]);
            $this->child($account->id, 'profile', ['name' => 'Profile', 'order' => 1]);
            $this->insert(['name' => 'Setting', 'url' => 'library.settings', 'parent_id' => $account->id, 'order' => 2, 'has_permissions' => 'Library Genral Settings']);
            $this->q()->where('url', 'referral.dashboard')->whereNull('parent_id')->update(['parent_id' => $account->id, 'name' => 'Referral', 'order' => 3, 'updated_at' => now()]);
            $this->insert(['name' => 'Feedback', 'url' => 'library.feedback', 'parent_id' => $account->id, 'order' => 4, 'has_permissions' => null]);
        }

        $this->q()->where('url', 'library.enquiry')->whereNull('parent_id')->update(['order' => 10, 'updated_at' => now()]);
        $this->q()->where('name', 'Trxn Management')->whereNull('parent_id')->update(['order' => 11, 'updated_at' => now()]);
    }

    public function down()
    {
        $setup = $this->parentOf('branch.list');
        $advanced = $this->parentOf('plan.index');
        $account = $this->parentOf('profile');
        $staff = $this->q()->where('name', 'Staff Management')->whereNull('parent_id')->first();
        $sub = $this->q()->where('name', 'Subscription')->whereNull('parent_id')->first();

        if ($setup) {
            DB::table('menus')->where('id', $setup->id)->update(['name' => 'Library Master Console', 'url' => 'library-users.index', 'order' => 5, 'updated_at' => now()]);
            $this->child($setup->id, 'branch.list', ['name' => 'Branches', 'order' => 2]);
            $this->child($setup->id, 'library.configration', ['name' => 'Shifts', 'order' => 3]);
            if ($advanced) {
                $this->child($setup->id, 'floor.index', ['parent_id' => $advanced->id, 'name' => 'Floors', 'order' => 3]);
            }
            if ($staff) {
                $this->child($staff->id, 'library-users.index', ['parent_id' => $setup->id, 'name' => 'Users', 'order' => 1]);
            }
        }
        if ($advanced) {
            DB::table('menus')->where('id', $advanced->id)->update(['name' => 'Custom Management', 'order' => 5, 'updated_at' => now()]);
            foreach ([['plan.index', 'Plans', 4], ['plantype.index', 'Plan Types', 5], ['planPrice.index', 'Plan Price', 6],
                      ['expense.index', 'Expenses', 7], ['exam.index', 'Exams', 8], ['toggle.feature', null, 9], ['library.master', null, 10]] as [$url, $name, $order]) {
                $this->child($advanced->id, $url, array_filter(['name' => $name, 'order' => $order]));
            }
        }
        if ($account) {
            DB::table('menus')->where('id', $account->id)->update(['name' => 'Account Settings', 'url' => 'library.myplan', 'order' => 6, 'updated_at' => now()]);
            $this->child($account->id, 'profile', ['name' => 'My Profile', 'order' => 2]);
            $this->child($account->id, 'referral.dashboard', ['parent_id' => null, 'name' => 'Referral', 'order' => 12]);
            if ($sub) {
                $this->child($sub->id, 'library.myplan', ['parent_id' => $account->id, 'name' => 'My Plan', 'order' => 1]);
                $this->child($sub->id, 'library.transaction', ['parent_id' => $account->id, 'name' => 'My Transactions', 'order' => 3]);
            }
            $this->q()->where('parent_id', $account->id)->whereIn('url', ['library.settings', 'library.feedback'])->delete();
        }
        if ($staff) {
            $this->q()->where('parent_id', $staff->id)->whereIn('url', ['library-users.roles', 'library-users.permission-list'])->delete();
            DB::table('menus')->where('id', $staff->id)->delete();
        }
        if ($sub) {
            DB::table('menus')->where('id', $sub->id)->delete();
        }
        $this->q()->where('url', 'library.enquiry')->whereNull('parent_id')->update(['order' => 7, 'updated_at' => now()]);
        $this->q()->where('name', 'Trxn Management')->whereNull('parent_id')->update(['order' => 10, 'updated_at' => now()]);
    }
}
