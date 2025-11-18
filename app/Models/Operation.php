<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operation extends Model
{
     protected $guarded = []; 
    // protected $fillable = ['name','code'];

    public function templates()
    {
        return $this->hasMany(NotificationTemplate::class, 'operation_id');
    }
}
