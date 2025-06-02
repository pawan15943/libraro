<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSeatType;


class Branch extends Model
{
    use HasFactory;
    use HasSeatType;
    protected $guarded = [];

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

}
