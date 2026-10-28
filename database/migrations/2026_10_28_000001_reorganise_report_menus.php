<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reports redesign: one "Reports" group with an overview, clear names and two new
 * reports (Growth & retention, Seat occupancy). Grace-period and expired lists now live
 * as tabs inside Renewals and the activity log on the Activities page, so their menu
 * items are hidden (status 0) rather than deleted — their routes still work.
 */
class ReorganiseReportMenus extends Migration
{
    private array $renames = [
        'reports.hub' => ['Overview', 1],
        'payment.collection.report' => ['Collections', 2],
        'monthly.payment.collection.report' => ['Cash book', 3],
        'report.monthly' => ['Profit & loss', 4],
        'partial.payment.collection.report' => ['Dues to collect', 5],
        'upcoming.payment.report' => ['Renewals', 6],
        'learner.report' => ['Members', 7],
        'reports.growth' => ['Growth & retention', 8],
        'reports.occupancy' => ['Seat occupancy', 9],
        'attendance.report' => ['Attendance', 10],
    ];

    private array $hidden = ['activity.report', 'pending.payment.report', 'expired.learner.report'];

    private array $original = [
        'payment.collection.report' => ['Payment Collection Report', 1],
        'partial.payment.collection.report' => ['Partial Payment Report', 2],
        'activity.report' => ['Library Activity Report', 3],
        'attendance.report' => ['Attendance Report', 4],
        'report.monthly' => ['Monthly Revenue Report', 5],
        'pending.payment.report' => ['Pending Payment Report', 6],
        'learner.report' => ['Learner Report', 7],
        'upcoming.payment.report' => ['Upcoming Payment Report', 8],
        'expired.learner.report' => ['Expired Learners Report', 9],
        'monthly.payment.collection.report' => ['Monthly Payment Report', 10],
    ];

    private function parentId(): ?int
    {
        return DB::table('menus')->whereNull('parent_id')->where('has_permissions', 'Manage Report')->value('id');
    }

    public function up()
    {
        $parent = $this->parentId();
        if (!$parent) {
            return;
        }

        DB::table('menus')->where('id', $parent)->update(['name' => 'Reports', 'url' => 'reports.hub', 'updated_at' => now()]);

        foreach (['reports.hub', 'reports.growth', 'reports.occupancy'] as $url) {
            if (!DB::table('menus')->where('parent_id', $parent)->where('url', $url)->exists()) {
                DB::table('menus')->insert([
                    'parent_id' => $parent,
                    'name' => $this->renames[$url][0],
                    'url' => $url,
                    'icon' => null,
                    'guard' => 'library',
                    'has_permissions' => 'Manage Report',
                    'order' => $this->renames[$url][1],
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        foreach ($this->renames as $url => [$name, $order]) {
            DB::table('menus')->where('parent_id', $parent)->where('url', $url)
                ->update(['name' => $name, 'order' => $order, 'status' => 1, 'updated_at' => now()]);
        }

        DB::table('menus')->where('parent_id', $parent)->whereIn('url', $this->hidden)
            ->update(['status' => 0, 'updated_at' => now()]);
    }

    public function down()
    {
        $parent = $this->parentId();
        if (!$parent) {
            return;
        }

        DB::table('menus')->where('id', $parent)->update(['name' => 'Manage Report', 'url' => 'payment.collection.report']);
        DB::table('menus')->where('parent_id', $parent)->whereIn('url', ['reports.hub', 'reports.growth', 'reports.occupancy'])->delete();

        foreach ($this->original as $url => [$name, $order]) {
            DB::table('menus')->where('parent_id', $parent)->where('url', $url)
                ->update(['name' => $name, 'order' => $order, 'status' => 1]);
        }
    }
}
