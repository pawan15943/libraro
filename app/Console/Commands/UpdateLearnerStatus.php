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
        $allbranch=Branch::get();
        foreach($allbranch as $branch){

        
            $extend_day = $branch ? $branch->extend_days : 0;
        
        // ---- Case 1: Renewed Learners ----
        $renewedLearners = LearnerDetail::where('branch_id',$branch->id)->select('learner_id')
            ->groupBy('learner_id')
            ->havingRaw('
                SUM(CASE WHEN plan_end_date <= ? THEN 1 ELSE 0 END) > 0 
                AND 
                SUM(CASE WHEN plan_end_date > ? AND status = 0 THEN 1 ELSE 0 END) > 0
            ', [$today->copy()->addDays(5), $today->copy()->addDays(5)])
            ->pluck('learner_id');

        // ---- Case 2: Expired Learners ----
        $expiredLearners = LearnerDetail::where('branch_id',$branch->id)->whereDate(
                DB::raw("DATE_ADD(plan_end_date, INTERVAL $extend_day DAY)"),
                '<=',
                $today
            )
            ->pluck('learner_id');

        // ---- Case 3: Active Future Booked Learners ----
        $futureLearners = LearnerDetail::where('branch_id',$branch->id)->where('status', 0)
            ->where('plan_start_date', '<=', $today)
            ->where('plan_end_date', '>', $today)
            ->pluck('learner_id');

        // ---- Merge All Unique Learners ----
        $learnerIds = $renewedLearners
            ->merge($expiredLearners)
            ->merge($futureLearners)
            ->unique();

       
        $customerdatas = LearnerDetail::whereIn('learner_id', $learnerIds)->get();

        foreach ($customerdatas as $customerdata) {
           

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

}
