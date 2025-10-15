<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\LibraryScope;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasBranch;
use App\Traits\HasSeatType;
use Illuminate\Notifications\Notifiable;

class Learner extends Authenticatable
{
    use Notifiable;
    use HasRoles;
    use HasFactory,SoftDeletes;
    use HasBranch;
    use HasSeatType;

    
    protected $guarded = [];
    
     protected static function booted()
    {
        static::addGlobalScope('withTrashed', function ($builder) {
            $builder->withTrashed();
        });
    }
    
    public function planType()
    {
        return $this->belongsTo(PlanType::class, 'plan_type_id', 'id')
                    ->where('library_id', getLibraryId())->where('branch_id', getCurrentBranch());  // PlanType specific to library
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'id')
                    ->where('library_id', getLibraryId()); 
    }

    public function learnerDetails()
    {
        return $this->hasMany(LearnerDetail::class);
    }

    public function learnerTransactions()
    {
        return $this->hasMany(LearnerTransaction::class);
    }

    public function getEmailAttribute($value)
    {
        return decryptData($value);
    }

    public function getMobileAttribute($value)
    {
        return decryptData($value);
    }

  
}
