<?php

namespace App\Console\Commands;

use App\Services\BranchHoursService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fills hour.open_time for existing branches from their shifts (BranchHoursService::guessOpenTime):
 * the library opens at the start of its earliest shift, in the tightest window of `hour` hours
 * that holds every shift. 24 hour branches need no open time. Branches with no timed shifts,
 * or shifts that don't fit their hours, are listed for the owner to set on the Operating hours page.
 */
class FillBranchOpenTimeCommand extends Command
{
    protected $signature = 'branches:fill-open-time {--dry-run : Show what would be filled without saving}';

    protected $description = 'Fill the library open time of existing branches from their shifts';

    public function handle(BranchHoursService $hours)
    {
        $rows = DB::table('hour')
            ->join('branches', 'branches.id', '=', 'hour.branch_id')
            ->whereNull('hour.deleted_at')
            ->whereNull('hour.open_time')
            ->where('hour.hour', '<', 24)
            ->orderBy('hour.branch_id')
            ->get(['hour.id', 'hour.branch_id', 'hour.hour', 'branches.name']);

        $filled = [];
        $left = [];
        foreach ($rows as $row) {
            $open = $hours->guessOpenTime((int) $row->branch_id, (int) $row->hour);
            if ($open === null) {
                $hasShifts = DB::table('plan_types')->where('branch_id', $row->branch_id)->whereNull('parent_id')->whereNull('deleted_at')->exists();
                $left[] = [$row->branch_id, $row->name, $row->hour . ' hrs', $hasShifts
                    ? 'Shifts spread over more than ' . $row->hour . ' hrs of the day: fix the shifts, then set the time'
                    : 'No shifts yet: set on the Operating hours page'];
                continue;
            }
            $window = BranchHoursService::windowOf((int) $row->hour, $open);
            $filled[] = [$row->branch_id, $row->name, $row->hour . ' hrs', BranchHoursService::windowLabel($window)];
            if (!$this->option('dry-run')) {
                DB::table('hour')->where('id', $row->id)->update(['open_time' => $open, 'updated_at' => now()]);
            }
        }

        $this->info(($this->option('dry-run') ? 'Would fill ' : 'Filled ') . count($filled) . ' branch(es):');
        if ($filled) {
            $this->table(['Branch', 'Name', 'Hours', 'Open – close'], $filled);
        }
        if ($left) {
            $this->warn(count($left) . ' branch(es) left for the owner:');
            $this->table(['Branch', 'Name', 'Hours', 'Why'], $left);
        }

        return self::SUCCESS;
    }
}
