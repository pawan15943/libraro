<?php

namespace App\Console\Commands;

use App\Models\Branch;
use Illuminate\Console\Command;
use App\Models\Learner;
use App\Models\LearnerDetail;
use Carbon\Carbon;

class UpdateLearnerStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-learner-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update learner status every morning';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $futureCheckDate = $today->copy()->addDays(5);

        $customerdatas = LearnerDetail::where('status', 1)->get();

        foreach ($customerdatas as $customerdata) {
            $branchId = $customerdata->branch_id;
            $branch = $branchId ? Branch::find($branchId) : null;
            $extend_day = $branch ? $branch->extend_days : 0;

            $planEndDateWithExtension = Carbon::parse($customerdata->plan_end_date)->addDays($extend_day);

            $hasFuturePlan = LearnerDetail::where('learner_id', $customerdata->learner_id)
                ->where('plan_end_date', '>', $futureCheckDate)
                ->where('status', 0)
                ->exists();

            $hasPastPlan = LearnerDetail::where('learner_id', $customerdata->learner_id)
                ->where('plan_end_date', '<', $futureCheckDate)
                ->exists();

            $isRenewed = $hasFuturePlan && $hasPastPlan;

            if ($planEndDateWithExtension->lte($today)) {
                Learner::where('id', $customerdata->learner_id)
                    ->where('status', '!=', 0)
                    ->update(['status' => 0]);

                $customerdata->update(['status' => 0]);
            } elseif ($isRenewed) {
                LearnerDetail::where('learner_id', $customerdata->learner_id)
                    ->where('plan_start_date', '<=', $today)
                    ->where('plan_end_date', '>', $futureCheckDate)
                    ->update(['status' => 1]);

                LearnerDetail::where('learner_id', $customerdata->learner_id)
                    ->where('plan_end_date', '<', $today)
                    ->update(['status' => 0]);
            } else {
                Learner::where('id', $customerdata->learner_id)
                    ->where('status', '!=', 1)
                    ->update(['status' => 1]);

                LearnerDetail::where('learner_id', $customerdata->learner_id)
                    ->where('status', 0)
                    ->where('plan_start_date', '<=', $today)
                    ->where('plan_end_date', '>', $today)
                    ->update(['status' => 1]);
            }
            
        }
    }

}
