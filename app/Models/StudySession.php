<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudySession extends Model
{
    protected $guarded = [];

    protected $casts = ['study_date' => 'date'];
}
