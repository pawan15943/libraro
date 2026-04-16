<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\LibraryScope;
use App\Traits\HasBranch;

use Illuminate\Database\Eloquent\SoftDeletes;
class LearnerDetail extends Model
{
    use HasFactory,SoftDeletes;
    use HasBranch;
    protected $guarded = []; 
    protected $table = 'learner_detail';
     protected static function booted()
    {
        static::addGlobalScope('withTrashed', function ($builder) {
            $builder->withTrashed();
        });
    }
    // protected static function booted()
    // {
        
    //     static::addGlobalScope(new LibraryScope());
    // }
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
        return $this->belongsToMany(PlanType::class, 'learner_detail_plan_type', 'learner_detail_id', 'plan_type_id')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }
    public function learner()
    {
        return $this->belongsTo(Learner::class)->withTrashed();
    }

 
}
