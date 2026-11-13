<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearnerGoal extends Model
{
    protected $guarded = [];

    protected $casts = ['exam_date' => 'date'];

    /** Syllabus topics, one per line. */
    public function topics(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $this->syllabus))));
    }
}
