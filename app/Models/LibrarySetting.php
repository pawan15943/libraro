<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\LibraryScope;
class LibrarySetting extends Model
{
    use HasFactory;
    protected $guarded = []; 
    protected static function booted()
    {
        
        static::addGlobalScope(new LibraryScope());
    }
    public function library()
    {
        return $this->belongsTo(Library::class); // Use User::class if linking to users
    }

}
