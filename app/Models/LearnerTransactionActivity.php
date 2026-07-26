<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\LearnerTransaction;

class LearnerTransactionActivity extends Model
{
    use HasFactory;
     protected $table = 'learner_transaction_activity';
    protected $guarded = [];
      protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->created_by)) {
                $createdBy = static::currentActorName();
                if ($createdBy) {
                    $model->created_by = $createdBy;
                }
            }

            // Global fallback (web + API): if learner_transaction_id is missing,
            // attach latest transaction of this learner.
            if (empty($model->learner_transaction_id) && !empty($model->learner_id)) {
                $latestTxnId = LearnerTransaction::withoutGlobalScopes()
                    ->where('learner_id', $model->learner_id)
                    ->orderByDesc('id')
                    ->value('id');

                if (!empty($latestTxnId)) {
                    $model->learner_transaction_id = $latestTxnId;
                }
            }

            if (!empty($model->learner_transaction_id)) {
                $learnerTransaction = LearnerTransaction::withoutGlobalScopes()
                    ->where('id', $model->learner_transaction_id)
                    ->first();

                $transactionId = $learnerTransaction?->transaction_id;

                if (empty($transactionId) && $learnerTransaction) {
                    $transactionId = transaction_id();
                    $learnerTransaction->transaction_id = $transactionId;
                    $learnerTransaction->save();
                }

                if (!empty($transactionId)) {
                    $model->transaction_id = $transactionId;
                }
            }

            if (empty($model->transaction_id)) {
                $model->transaction_id = transaction_id();
            }
        });

        static::updating(function ($model) {
            if (! Schema::hasColumn($model->getTable(), 'updated_by')) {
                return;
            }

            $updatedBy = static::currentActorName();
            if ($updatedBy) {
                $model->updated_by = $updatedBy;
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

        if (is_numeric($this->created_by)) {
            return static::legacyActorName($this->created_by) ?: "System User";
        }

        return (string) $this->created_by;
    }

    public function getUpdatedByNameAttribute()
    {
        if (is_null($this->updated_by)) {
            return "";
        }

        if (is_numeric($this->updated_by)) {
            return static::legacyActorName($this->updated_by);
        }

        return (string) $this->updated_by;
    }

     public function learner()
    {
        return $this->belongsTo(Learner::class, 'learner_id')->withTrashed();
    }
     public function learnerTransaction()
    {
        return $this->belongsTo(LearnerTransaction::class, 'learner_transaction_id')->withTrashed();
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

    private static function currentActorName(): ?string
    {
        if (Auth::guard('library_user')->check()) {
            return static::displayName(Auth::guard('library_user')->user());
        }

        if (Auth::guard('library')->check()) {
            return static::displayName(Auth::guard('library')->user());
        }

        if (Auth::guard('library_api')->check()) {
            return static::displayName(Auth::guard('library_api')->user());
        }

        if (Auth::check()) {
            return static::displayName(Auth::user());
        }

        return null;
    }

    private static function displayName($user): ?string
    {
        if (! $user) {
            return null;
        }

        return $user->name
            ?? $user->library_name
            ?? $user->email
            ?? $user->mobile
            ?? null;
    }

    private static function legacyActorName($actorId): string
    {
        $name = DB::table('library_users')->where('id', $actorId)->value('name');
        if ($name) {
            return (string) $name;
        }

        return (string) (DB::table('libraries')->where('id', $actorId)->value('library_name') ?? '');
    }

}
