<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Scopes\LibraryScope;


class PlanPrice extends Model
{
    use HasFactory,SoftDeletes;
    protected $guarded = []; 
    protected static function booted()
    {
        if (auth()->check()) {
            static::addGlobalScope('branch', function ($builder) {
                $builder->where('branch_id', getCurrentBranch());
            });
        }
    }
    

     // Relationship to the Plan model
     public function plan()
     {
         return $this->belongsTo(Plan::class, 'plan_id');
     }
 
     // Relationship to the PlanType model
     public function planType()
     {
         return $this->belongsTo(PlanType::class, 'plan_type_id');
     }
}
