<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Scopes\LibraryScope;


class Plan extends Model
{
    use HasFactory,SoftDeletes;
    protected $guarded = []; 
    protected static function booted()
    {
        
        static::addGlobalScope(new LibraryScope());
    }
}
