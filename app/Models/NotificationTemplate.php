<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
     protected $guarded = []; 
    // protected $fillable = ['operation_id','type','template_name','template_message','is_active','created_by'];

    public function operation()
    {
        return $this->belongsTo(Operation::class);
    }
}
