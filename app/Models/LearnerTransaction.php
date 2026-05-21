<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Scopes\LibraryScope;
use App\Traits\HasBranch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class LearnerTransaction extends Model
{
    use HasFactory,SoftDeletes;
    use HasBranch;
    protected $table = 'learner_transactions';
    protected $guarded = [];
     protected static function booted()
    {
        static::addGlobalScope('withTrashed', function ($builder) {
            $builder->withTrashed();
        });
        static::addGlobalScope(new LibraryScope());

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
  

    public function learner()
    {
        return $this->belongsTo(Learner::class, 'learner_id')->withTrashed();
    }
    public function learnerDetail()
    {
        return $this->belongsTo(LearnerDetail::class, 'learner_detail_id')->withTrashed();
    }

}
