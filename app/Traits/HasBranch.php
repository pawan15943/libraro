<?php
namespace App\Traits;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Services\CurrentBranch;
use Illuminate\Support\Facades\App;
use Log;


trait HasBranch
{
    private static bool $isResolvingBranchScope = false;

    public static function bootHasBranch()
    {
        if (!Auth::guard('learner')->check()) {
            static::addGlobalScope('branch', function (Builder $builder) {
                if (static::$isResolvingBranchScope) {
                    return;
                }

                static::$isResolvingBranchScope = true;

                try {
                    $branchId = null;
                    
                    // 1. Check guards that already have a resolved user
                    foreach (array_keys(config('auth.guards')) as $guard) {
                        if (Auth::guard($guard)->hasUser()) {
                            $user = Auth::guard($guard)->user();
                            if ($user && isset($user->current_branch)) {
                                $branchId = $user->current_branch;
                                break;
                            }
                        }
                    }

                    // 2. If no pre-resolved user, check staff guards safely (excluding learner guards to prevent auth recursion)
                    if (!$branchId) {
                        foreach (['library', 'library_user', 'web', 'library_api', 'library_user_api'] as $guard) {
                            if (Auth::guard($guard)->check()) {
                                $user = Auth::guard($guard)->user();

                                if ($user && isset($user->current_branch)) {
                                    $branchId = $user->current_branch;
                                    break;
                                }
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
                } finally {
                    static::$isResolvingBranchScope = false;
                }
            });

            static::creating(function ($model) {
                if (static::$isResolvingBranchScope) {
                    return;
                }

                static::$isResolvingBranchScope = true;

                try {
                    $branchId = null;

                    foreach (array_keys(config('auth.guards')) as $guard) {
                        if (Auth::guard($guard)->hasUser()) {
                            $user = Auth::guard($guard)->user();

                            if ($user && isset($user->current_branch)) {
                                $branchId = $user->current_branch;
                                break;
                            }
                        }
                    }

                    if ($branchId > 0 && empty($model->branch_id)) {
                        $model->branch_id = $branchId;
                    }
                } finally {
                    static::$isResolvingBranchScope = false;
                }
            });
        }
    }
}
