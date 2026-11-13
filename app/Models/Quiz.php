<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quiz extends Model
{
    use SoftDeletes;

    public const MAX_QUESTIONS = 40;
    public const MAX_SUBJECTS = 4;

    protected $guarded = [];

    protected $casts = ['is_published' => 'boolean'];

    public function questions()
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('position');
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
