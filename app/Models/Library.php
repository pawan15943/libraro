<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Scopes\LibraryScope;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\Notifications\ResetPassword;

class Library extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory , Notifiable;
    use HasRoles;
   
    protected $guarded = []; 
   
    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
        'email_verified_at' => 'datetime',
        'is_app_verified' => 'boolean',
    ];

    public function isYearlyProPlan(): bool
    {
        if (!$this->is_paid) {
            return false;
        }

        // Determine subscription ID (1 = Basic, 2 = Smart, 3 = Pro)
        $subId = (int) ($this->library_type ?? 0);

        // Active transaction check
        $activeTx = $this->library_transactions()
            ->where('is_paid', 1)
            ->where('status', 1)
            ->latest('id')
            ->first();

        if ($activeTx && !empty($activeTx->subscription)) {
            $subId = (int) $activeTx->subscription;
        }

        // Fetch Subscription details
        $sub = $this->subscription ?? ($subId ? Subscription::find($subId) : null);
        $subName = strtolower($sub->name ?? '');

        // Plan 1 = Basic, Plan 2 = Smart, Plan 3 = Pro Plan
        $isProPlan = ($subId === 3) || (str_contains($subName, 'pro') && !str_contains($subName, 'basic') && !str_contains($subName, 'smart'));

        if (!$isProPlan) {
            return false;
        }

        // Must be Yearly duration (month = 12, 24, 2, or 5)
        if ($activeTx) {
            return in_array((int) $activeTx->month, [12, 24, 2, 5]);
        }

        return true;
    }

    protected $guard_name = ['library', 'library_api'];
    
    public function library_transactions()
    {
        return $this->hasMany(LibraryTransaction::class, 'library_id', 'id'); 
        // Adjust the foreign key and local key if necessary
    }
    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'library_type', 'id'); // Assuming 'library_type' in libraries matches 'id' in subscriptions
    }
    
    // public function state()
    // {
    //     return $this->belongsTo(State::class, 'state_id');
    // }

    // // Library belongs to a City
    // public function city()
    // {
    //     return $this->belongsTo(City::class, 'city_id');
    // }

    public function branches()
    {
        return $this->hasMany(Branch::class, 'library_id');
    }

    public function isNotGeneralBranch()
    {
        $branchId = session('branch_id', 0); // session branch_id

        // If specific branch is selected
        if ($branchId > 0) {
            $branch = $this->branches->where('id', $branchId)->first();
            return $branch && $branch->seat_type != 'general';
        }

        // If "All Branches" selected (branch_id = 0)
        // Check if ANY branch is not 'general'
        return $this->branches->contains(function($branch) {
            return $branch->seat_type != 'general';
        });
    }

    public function devices()
    {
        return $this->morphMany(\App\Models\DeviceToken::class, 'user');
    }
 
        // public function sendPasswordResetNotification($token)
        // {
        //     $this->notify(new ResetPassword($token));
        // }
    
}
