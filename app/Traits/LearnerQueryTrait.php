<?php

namespace App\Traits;

use App\Models\Learner;

trait LearnerQueryTrait
{
    public function getLearnersByLibrary()
    {
        return Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
                      ->where('learners.branch_id', getCurrentBranch());
    }

    public function getAllLearnersByLibrary()
    {
        return Learner::where('branch_id', getCurrentBranch()) 
                ->with([
                    'learnerDetails' => function($query) {
                        $query->with([ 'plan', 'planType']);
                    }
                ]);
    }
    
}
