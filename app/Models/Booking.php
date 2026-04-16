<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;
    protected $guarded = [];
     public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
                 
    }
       public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function planType()
    {
        return $this->belongsTo(PlanType::class, 'plan_type_id');
    }

    public function planTypes()
    {
        return $this->belongsToMany(PlanType::class, 'booking_plan_type', 'booking_id', 'plan_type_id')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }
}
