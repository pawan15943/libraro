<?php

namespace App\Models;

use App\Models\Scopes\LibraryScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A library-wide offer that every booking form applies by itself between start_date and end_date (docs/GLOBAL_DISCOUNT.md). */
class GlobalDiscount extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'discount_value' => 'float',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_enabled' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new LibraryScope());
    }

    /** Switched on and today (or $on) is between its start and end date, both days included. */
    public function scopeRunningOn(Builder $query, $on = null): Builder
    {
        $day = Carbon::parse($on ?? today())->toDateString();

        return $query->where('is_enabled', true)->whereDate('start_date', '<=', $day)->whereDate('end_date', '>=', $day);
    }

    /** running | scheduled | ended | off */
    public function state($on = null): string
    {
        $day = Carbon::parse($on ?? today())->startOfDay();
        if (! $this->is_enabled) {
            return 'off';
        }
        if ($day->lt($this->start_date)) {
            return 'scheduled';
        }

        return $day->gt($this->end_date) ? 'ended' : 'running';
    }

    /** "10% off" / "₹200 off" */
    public function label(): string
    {
        $v = rtrim(rtrim(number_format($this->discount_value, 2, '.', ''), '0'), '.');

        return $this->discount_type === 'percentage' ? $v.'% off' : '₹'.$v.' off';
    }
}
