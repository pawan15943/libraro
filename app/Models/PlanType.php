<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Scopes\LibraryScope;

class PlanType extends Model
{
    use HasFactory,SoftDeletes;
    protected $guarded = []; 
    protected static function booted()
    {
        
        static::addGlobalScope(new LibraryScope());
        static::addGlobalScope('branch', function ($builder) {
            $branchId = getCurrentBranch();

            if (!empty($branchId)) {
                $builder->where('branch_id', $branchId);
            }
        });

        
    }
    public function price()
    {
        return $this->hasOne(PlanPrice::class, 'plan_type_id');
    }
   
}
