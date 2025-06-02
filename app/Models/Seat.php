<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\LibraryScope;

class Seat extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'seats';

    protected static function booted()
    {
        
        static::addGlobalScope(new LibraryScope());
    }
    public function learners()
    {
        return $this->hasMany(Learner::class, 'seat_no', 'seat_no')
                    ->where('library_id', getAuthenticatedUser()->id);  // Learners specific to library
    }


}
