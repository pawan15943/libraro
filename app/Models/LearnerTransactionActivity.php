<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class LearnerTransactionActivity extends Model
{
    use HasFactory;
     protected $table = 'learner_transaction_activity';
    protected $guarded = [];
      protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // If already set in some old code, do NOT override
            if (!empty($model->created_by)) {
                return;
            }

            // If library_user is logged in
            if (Auth::guard('library_user')->check()) {
                $model->created_by = Auth::guard('library_user')->id();
            }
            
        });

        static::updating(function ($model) {
            if (! Schema::hasColumn($model->getTable(), 'updated_by')) {
                return;
            }

            if (Auth::guard('library_user')->check()) {
                $model->updated_by = Auth::guard('library_user')->id();
            } elseif (Auth::guard('library')->check()) {
                $model->updated_by = Auth::guard('library')->id();
            }
        });
    }
    public function creator()
    {
        return $this->belongsTo(LibraryUser::class, 'created_by')->withDefault();
    }

    public function getCreatedByNameAttribute()
    {
        if (is_null($this->created_by)) {
            return "System User";
        }

        return $this->creator->name ?? "System User";
    }

     public function learner()
    {
        return $this->belongsTo(Learner::class, 'learner_id')->withTrashed();
    }
     public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id')->withTrashed();
    }
    protected static function booted()
    {
        static::addGlobalScope('branch', function ($builder) {
            if (getCurrentBranch()) {
                $builder->where('branch_id', getCurrentBranch());
            }
        });
    }

}
