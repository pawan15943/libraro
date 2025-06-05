<?php
namespace App\Traits;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Services\CurrentBranch;
use Illuminate\Support\Facades\App;

trait HasBranch
{
    public static function bootHasBranch()
    {
        if(!Auth::guard('learner')->check()){
        static::addGlobalScope('branch', function (Builder $builder) {
            $branchId = null;
            
            // Loop through all guards and find the authenticated one
            foreach (array_keys(config('auth.guards')) as $guard) {
                
                if (Auth::guard($guard)->check()) {
                    $user = Auth::guard($guard)->user();

                    if (isset($user->current_branch)) {
                        $branchId = $user->current_branch;
                        break;
                    }
                }
            }

            // Apply branch filter if valid
            if ($branchId > 0) {
                $builder->where(
                    $builder->getModel()->getTable() . '.branch_id',
                    $branchId
                );
            }

            \Log::info('HasBranch Scope Applied', [
                'Model' => get_class($builder->getModel()),
                'BranchID' => $branchId,
            ]);
        });

        static::creating(function ($model) {
            $branchId = null;

            foreach (array_keys(config('auth.guards')) as $guard) {
                if (Auth::guard($guard)->check()) {
                    $user = Auth::guard($guard)->user();

                    if (isset($user->current_branch)) {
                        $branchId = $user->current_branch;
                        break;
                    }
                }
            }

            if ($branchId > 0 && empty($model->branch_id)) {
                $model->branch_id = $branchId;
            }
        });
        }
        
    }
}
