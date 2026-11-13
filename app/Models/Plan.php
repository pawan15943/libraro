<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Scopes\LibraryScope;


class Plan extends Model
{
    use HasFactory,SoftDeletes;

    // Hidden plan created for a booking with owner-picked dates; plan_id = days (docs/CUSTOM_PLAN.md)
    public const TYPE_CUSTOM = 'CUSTOM';

    protected $guarded = [];
    protected static function booted()
    {

        static::addGlobalScope(new LibraryScope());
    }

    // Plans that may appear in lists, dropdowns and counts: hides the auto-created custom rows
    public function scopeListable($query)
    {
        return $query->where(function ($q) use ($query) {
            $q->whereNull($query->qualifyColumn('is_custom'))
              ->orWhere($query->qualifyColumn('is_custom'), 0);
        });
    }

    public function isCustom(): bool
    {
        return (bool) $this->is_custom;
    }
}
