<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Library sidebar simplified: 12 top-level menus → 9, and Reports becomes one link (the
 * Reports hub already has a card for every report). Guard `library` only; urls and permission
 * keys of the links are unchanged, rows are found by url so ids may differ between databases.
 *
 *   Dashboard
 *   Learners      Seat Map, Learners, Demo Inquiry, Enquiries           (was "Library" + top-level "Library Enquiry")
 *   Attendance    Via QR / Scanner, Manual, Summary
 *   Student Chat
 *   Reports       one link to the hub (its sub-links are switched off)
 *   Setup         Branch, Shift, Floors, Plans, Plan Types, Plan Prices, Global Discount
 *   Staff         Users, Role, Permission
 *   Settings      Library Settings, Message Templates, Expense Names, Exam Names, Student Quiz,
 *                 Show / Hide Options, Import Learners, Setup via CSV, Delete Renewed Trxn
 *                 (was "Advanced Management" + "Trxn Management")
 *   Account       Profile, My Plan, Transactions, Referral, Feedback     (absorbs "Subscription")
 *
 * Setup's own permission is cleared so a staff member sees it for the links they hold
 * (the sidebar hides a group that has no link left for the viewer).
 */
class SimplifyLibrarySidebarMenus extends Migration
{
    /** Report links that were on before this migration (down() turns these back on). */
    private const REPORT_LINKS = [
        'reports.hub', 'payment.collection.report', 'monthly.payment.collection.report', 'report.monthly',
        'partial.payment.collection.report', 'upcoming.payment.report', 'learner.report', 'reports.growth',
        'reports.occupancy', 'attendance.report',
    ];

    private function q()
    {
        return DB::table('menus')->where('guard', 'library')->whereNull('deleted_at');
    }

    private function topIds(): array
    {
        return $this->q()->whereNull('parent_id')->pluck('id')->all();
    }

    /** The live top-level menu whose child has this url. */
    private function parentOf(string $childUrl)
    {
        $pid = DB::table('menus')->whereNull('deleted_at')->where('url', $childUrl)
            ->whereIn('parent_id', $this->topIds())->value('parent_id');

        return $pid ? DB::table('menus')->where('id', $pid)->first() : null;
    }

    private function topByUrl(string $url)
    {
        return $this->q()->whereNull('parent_id')->where('url', $url)->first();
    }

    private function set(?object $row, array $values): void
    {
        if ($row) {
            DB::table('menus')->where('id', $row->id)->update($values + ['updated_at' => now()]);
        }
    }

    /** Move/rename the child with this url, wherever it sits under a live top-level menu. */
    private function child(string $url, ?object $parent, string $name, int $order, array $from = []): void
    {
        if (!$parent) {
            return;
        }
        DB::table('menus')->whereNull('deleted_at')->where('url', $url)
            ->whereIn('parent_id', $from ?: $this->topIds())
            ->update(['parent_id' => $parent->id, 'name' => $name, 'order' => $order, 'status' => 1, 'updated_at' => now()]);
    }

    public function up()
    {
        $learners = $this->parentOf('seats');
        $attendance = $this->parentOf('attendance.apply');
        $reports = $this->topByUrl('reports.hub');
        $setup = $this->parentOf('branch.list');
        $staff = $this->parentOf('library-users.roles');
        $settings = $this->parentOf('plan.index');
        $account = $this->parentOf('profile');
        $subscription = $this->parentOf('library.transaction');
        $trxn = $this->parentOf('create.renew.delete.index');
        $enquiry = $this->topByUrl('library.enquiry');

        $this->set($this->topByUrl('library.home'), ['order' => 1]);

        // Learners (enquiry moves in from the top level)
        $this->set($learners, ['name' => 'Learners', 'order' => 2]);
        $this->child('seats', $learners, 'Seat Map', 1);
        $this->child('learners', $learners, 'Learners', 2);
        $this->child('demo-users.index', $learners, 'Demo Inquiry', 3);
        if ($learners && $enquiry) {
            $this->set($enquiry, ['parent_id' => $learners->id, 'name' => 'Enquiries', 'order' => 4]);
        }

        $this->set($attendance, ['order' => 3]);
        $this->child('attendance.apply', $attendance, 'Via QR / Scanner', 1);
        $this->child('attendance', $attendance, 'Manual', 2);
        $this->child('get.learner.attendance', $attendance, 'Summary', 3);

        $this->set($this->topByUrl('chat.index'), ['order' => 4]);

        // Reports: one link; the hub lists every report
        if ($reports) {
            $this->set($reports, ['order' => 5]);
            DB::table('menus')->where('parent_id', $reports->id)->update(['status' => 0, 'updated_at' => now()]);
        }

        // Setup: the library's structure and prices
        $this->set($setup, ['name' => 'Setup', 'order' => 6, 'has_permissions' => null]);
        $this->child('branch.list', $setup, 'Branch', 1);
        $this->child('library.configration', $setup, 'Shift', 2);
        $this->child('floor.index', $setup, 'Floors', 3);
        $this->child('plan.index', $setup, 'Plans', 4);
        $this->child('plantype.index', $setup, 'Plan Types', 5);
        $this->child('planPrice.index', $setup, 'Plan Prices', 6);
        $this->child('global-discount.index', $setup, 'Global Discount', 7);

        $this->set($staff, ['name' => 'Staff', 'order' => 7]);

        // Settings (was Advanced Management) + Trxn Management + library settings / import / templates
        $this->set($settings, ['name' => 'Settings', 'order' => 8]);
        $this->child('library.settings', $settings, 'Library Settings', 1);
        $this->child('message.templates', $settings, 'Message Templates', 2);
        $this->child('expense.index', $settings, 'Expense Names', 3);
        $this->child('exam.index', $settings, 'Exam Names', 4);
        $this->child('quiz.index', $settings, 'Student Quiz', 5);
        $this->child('toggle.feature', $settings, 'Show / Hide Options', 6);
        $this->child('library.upload.form', $settings, 'Import Learners', 7);
        $this->child('library.master', $settings, 'Setup via CSV', 8);
        $this->child('create.renew.delete.index', $settings, 'Delete Renewed Trxn', 9);

        // Account absorbs Subscription
        $this->set($account, ['order' => 9]);
        $this->child('profile', $account, 'Profile', 1);
        $this->child('library.myplan', $account, 'My Plan', 2);
        $this->child('library.transaction', $account, 'Transactions', 3);
        $this->child('referral.dashboard', $account, 'Referral', 4);
        $this->child('library.feedback', $account, 'Feedback', 5);

        $this->set($subscription, ['status' => 0]);
        $this->set($trxn, ['status' => 0]);
    }

    public function down()
    {
        $learners = $this->parentOf('seats');
        $attendance = $this->parentOf('attendance.apply');
        $reports = $this->topByUrl('reports.hub');
        $setup = $this->parentOf('branch.list');
        $staff = $this->parentOf('library-users.roles');
        $settings = $this->parentOf('expense.index');
        $account = $this->parentOf('profile');
        $subscription = $this->q()->whereNull('parent_id')->where('name', 'Subscription')->first();
        $trxn = $this->q()->whereNull('parent_id')->where('name', 'Trxn Management')->first();
        $ids = fn (...$rows) => array_values(array_filter(array_map(fn ($r) => $r->id ?? null, $rows)));
        $fromSettings = $ids($settings);
        $fromSetup = $ids($setup);
        $fromAccount = $ids($account);

        $this->set($subscription, ['status' => 1]);
        $this->set($trxn, ['status' => 1]);

        $this->set($learners, ['name' => 'Library', 'order' => 2]);
        $this->child('demo-users.index', $learners, 'Demo Inquiry', 2);
        $this->child('learners', $learners, 'Learner List', 3);
        $this->child('library.upload.form', $learners, 'Import Data', 7, $fromSettings);
        $this->child('message.templates', $learners, 'Message Templates', 13, $fromSettings);
        $enquiry = $this->q()->where('url', 'library.enquiry')->where('parent_id', $learners->id ?? 0)->first();
        $this->set($enquiry, ['parent_id' => null, 'name' => 'Library Enquiry', 'order' => 10]);

        $this->set($attendance, ['order' => 3]);
        $this->child('attendance.apply', $attendance, 'Via QR / Scanner', 0);
        $this->child('attendance', $attendance, 'Manual', 1);
        $this->child('get.learner.attendance', $attendance, 'Summary', 1);

        $this->set($this->topByUrl('chat.index'), ['order' => 12]);

        if ($reports) {
            $this->set($reports, ['order' => 4]);
            DB::table('menus')->where('parent_id', $reports->id)->whereIn('url', self::REPORT_LINKS)
                ->update(['status' => 1, 'updated_at' => now()]);
        }

        $this->set($setup, ['name' => 'Setup Library', 'order' => 5, 'has_permissions' => 'Library Master Console']);
        $this->set($staff, ['name' => 'Staff Management', 'order' => 6]);

        $this->set($settings, ['name' => 'Advanced Management', 'order' => 7]);
        $this->child('plan.index', $settings, 'Plan', 1, $fromSetup);
        $this->child('plantype.index', $settings, 'Plan Type', 2, $fromSetup);
        $this->child('planPrice.index', $settings, 'Plan Price', 3, $fromSetup);
        $this->child('expense.index', $settings, 'Expense Names', 4);
        $this->child('exam.index', $settings, 'Exam Names', 5);
        $this->child('toggle.feature', $settings, 'Show / Hide Options', 6);
        $this->child('quiz.index', $settings, 'Student Quiz', 6);
        $this->child('library.master', $settings, 'Setup Library via CSV', 7);
        $this->child('global-discount.index', $settings, 'Global Discount', 7, $fromSetup);
        $this->child('create.renew.delete.index', $trxn, 'Delete Re-Newed Trxn', 1, $fromSettings);

        $this->set($account, ['order' => 9]);
        $this->child('library.settings', $account, 'Setting', 2, $fromSettings);
        $this->child('referral.dashboard', $account, 'Referral', 3);
        $this->child('library.feedback', $account, 'Feedback', 4);
        $this->child('library.myplan', $subscription, 'Plan', 1, $fromAccount);
        $this->child('library.transaction', $subscription, 'Transactions', 2, $fromAccount);
    }
}
