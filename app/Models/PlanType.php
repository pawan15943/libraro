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

    // An advanced shift (duration / free / flexible) takes the library's open – close time as its
    // window: stored as 00:00 – 00:00, read through bookingTimes() (BranchHoursService)
    public const ANY_TIME = '00:00';
    public const WINDOW_MODES = [self::MODE_DURATION, self::MODE_FREE, self::MODE_FLEXIBLE];

    // What the owner's pages call the advanced shifts
    public const MODE_LABELS = [self::MODE_DURATION => 'Fixed hours', self::MODE_FLEXIBLE => 'Flexible hours', self::MODE_FREE => 'Direct booking'];

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

    // Shifts offered on a general seat by the owner booking form: fixed, plus flexible (any time, N hours)
    public function scopeGeneralSeatModes($query)
    {
        return $query->listable()->where(function ($q) use ($query) {
            $q->whereNull($query->qualifyColumn('booking_mode'))
              ->orWhereIn($query->qualifyColumn('booking_mode'), [self::MODE_FIXED, self::MODE_FLEXIBLE]);
        });
    }

    /** Advanced shift without its own time window: booked within the library hours. */
    public function isAnyTime(): bool
    {
        return $this->parent_id === null
            && in_array($this->booking_mode, self::WINDOW_MODES, true)
            && substr((string) $this->start_time, 0, 5) === self::ANY_TIME
            && substr((string) $this->end_time, 0, 5) === self::ANY_TIME;
    }

    /**
     * When this shift can be booked, as "HH:MM" start and end: its own time, or the library
     * hours for an advanced shift without one. 00:00 – 00:00 = any time of the day (a 24 hour
     * library, or one whose open time isn't set).
     *
     * @return array{0: string, 1: string}
     */
    public function bookingTimes(): array
    {
        if (!$this->isAnyTime()) {
            return [substr((string) $this->start_time, 0, 5), substr((string) $this->end_time, 0, 5)];
        }

        return \App\Services\BranchHoursService::times($this->branch_id) ?? [self::ANY_TIME, self::ANY_TIME];
    }

    /** "6 AM – 10 PM" (library hours for an advanced shift without its own time), or "Any time". */
    public function timeLabel(): string
    {
        [$s, $e] = $this->bookingTimes();
        if (!$s || !$e) {
            return '';
        }
        if ($s === self::ANY_TIME && $e === self::ANY_TIME && $this->isAnyTime()) {
            return 'Any time';
        }

        return \App\Services\BranchHoursService::timeLabel($s) . ' – ' . \App\Services\BranchHoursService::timeLabel($e);
    }

    /** Leaves out any-time advanced shifts: they don't tie up any hours of the day. */
    public function scopeWithTimeWindow($query)
    {
        return $query->where(function ($q) use ($query) {
            $q->whereNull($query->qualifyColumn('booking_mode'))
              ->orWhereNotIn($query->qualifyColumn('booking_mode'), self::WINDOW_MODES)
              ->orWhereRaw('LEFT(' . $query->qualifyColumn('start_time') . ', 5) <> ?', [self::ANY_TIME])
              ->orWhereRaw('LEFT(' . $query->qualifyColumn('end_time') . ', 5) <> ?', [self::ANY_TIME]);
        });
    }

    public function isFlexible(): bool
    {
        return $this->parent_id === null && $this->booking_mode === self::MODE_FLEXIBLE;
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
