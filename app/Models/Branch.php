<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSeatType;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Branch extends Model
{
    use HasFactory;
    use HasSeatType;
    protected $guarded = [];
    use SoftDeletes;

    protected $casts = [
        'features' => 'array',
        'library_images' => 'array',
    ];
    public function learners()
    {
        return $this->hasMany(Learner::class, 'branch_id');
    }

    public function hour()
    {
        return $this->hasOne(Hour::class, 'branch_id'); // or hasMany if needed
    }

     public function library()
    {
        return $this->belongsTo(Library::class, 'library_id');
    }

     public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    // Library belongs to a City
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }
 
    protected static function boot()
    {
        parent::boot();

        // Fires only on creation
        static::creating(function ($branch) {
            if (empty($branch->uuid)) {
                $branch->uuid = (string) Str::uuid();
            }
        });
    }

   

}
