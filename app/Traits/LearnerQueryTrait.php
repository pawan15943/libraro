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
        $query = Learner::where('library_id', getLibraryId())
                ->with([
                    'learnerDetails' => function($query) {
                        $query->with([ 'plan', 'planType']);
                    }
                ]);

        if (getCurrentBranch() != 0 && getCurrentBranch() !== null) {
            $query->where('branch_id', getCurrentBranch());
        }

        return $query;
    }
    
}
