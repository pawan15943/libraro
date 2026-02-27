<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LibraroFeature extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'libraro_features';
    public function subscription()
{
    return $this->belongsTo(Subscription::class, 'subscription_id');
}
    
}
