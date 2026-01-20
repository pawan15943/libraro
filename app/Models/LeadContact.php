<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadContact extends Model
{
    use HasFactory;
    protected $guarded = [];

     /* Latest comment accessor */
    public function getLatestCommentAttribute()
    {
        if (empty($this->comments)) {
            return null;
        }
        return end($this->comments);
    }
}
