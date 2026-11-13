<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/** A lead stage (parent_id null) or one of its sub-stages. */
class CrmStage extends Model
{
    protected $guarded = [];

    protected $casts = ['is_closed' => 'boolean', 'is_won' => 'boolean', 'is_active' => 'boolean'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    public function scopeMain($q)
    {
        return $q->whereNull('parent_id')->orderBy('sort');
    }

    /** Stages with their active sub-stages, in order (cached per request). */
    public static function tree(): Collection
    {
        static $tree = null;

        return $tree ??= self::main()->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->get();
    }

    /** ids of stages whose leads never show in the call queue (Enrolled, Not interested, Invalid) */
    public static function closedIds(): array
    {
        static $ids = null;

        return $ids ??= self::whereNull('parent_id')->where('is_closed', true)->pluck('id')->all();
    }
}
