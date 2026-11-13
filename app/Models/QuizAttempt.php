<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    protected $guarded = [];

    protected $casts = ['answers' => 'array', 'by_subject' => 'array', 'quiz_date' => 'date'];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
}
