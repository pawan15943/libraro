<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadContact extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $casts = [
        'comments' => 'array',
    ];

    /* Latest comment accessor */
    public function getLatestCommentAttribute()
    {
        $comments = $this->comments; // copy first

        if (!is_array($comments) || empty($comments)) {
            return null;
        }

        return end($comments);
    }
}
