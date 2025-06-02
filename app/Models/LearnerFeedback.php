<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearnerFeedback extends Model
{
    use HasFactory;
    protected $guarded = []; 
    public function library()
    {
        return $this->belongsTo(Library::class, 'library_id');
    }

    public function learner()
    {
        return $this->belongsTo(Learner::class, 'learner_id');
    }
}
