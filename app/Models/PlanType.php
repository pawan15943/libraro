<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Scopes\LibraryScope;

class PlanType extends Model
{
    use HasFactory,SoftDeletes;

    // booking_mode values (see docs/ADVANCED_SHIFTS.md)
    public const MODE_FIXED = 'fixed';
    public const MODE_DURATION = 'duration';
    public const MODE_FREE = 'free';
    public const MODE_FLEXIBLE = 'flexible';
    public const MODE_MULTI = 'multi';

    // Templates whose real timing is entered at booking time
    public const TIMED_TEMPLATE_MODES = [self::MODE_DURATION, self::MODE_FREE];

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
    public function scopeByBranch($query, $branchId)
    {
        return $query
            ->withoutGlobalScope('branch')
            ->where('branch_id', $branchId);
    }
    // Shifts that may appear in lists and dropdowns: hides the auto-created timing rows
    public function scopeListable($query)
    {
        return $query->whereNull($query->qualifyColumn('parent_id'));
    }

    // Shifts usable on a general (unassigned) seat or by flows that only know fixed timings
    public function scopeFixedMode($query)
    {
        return $query->listable()->where(function ($q) use ($query) {
            $q->whereNull($query->qualifyColumn('booking_mode'))
              ->orWhere($query->qualifyColumn('booking_mode'), self::MODE_FIXED);
        });
    }

    public function isTimedTemplate(): bool
    {
        return $this->parent_id === null && in_array($this->booking_mode, self::TIMED_TEMPLATE_MODES, true);
    }

    public function parent()
    {
        return $this->belongsTo(PlanType::class, 'parent_id')->withoutGlobalScopes()->withTrashed();
    }

    public function price()
    {
        return $this->hasOne(PlanPrice::class, 'plan_type_id')->withoutGlobalScope('branch');
    }

}
