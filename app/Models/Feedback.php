<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\LibraryScope;

class Feedback extends Model
{
    use HasFactory;
    protected $guarded = []; 
    protected static function booted()
    {
        
        static::addGlobalScope(new LibraryScope());
    }
}
