<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The outcome of a call (Interested, No answer, ...), with the stage it moves the lead to. */
class CrmDisposition extends Model
{
    protected $guarded = [];

    protected $casts = ['is_connected' => 'boolean', 'requires_follow_up' => 'boolean', 'is_active' => 'boolean'];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(CrmStage::class, 'stage_id');
    }

    public function subStage(): BelongsTo
    {
        return $this->belongsTo(CrmStage::class, 'sub_stage_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true)->orderBy('sort');
    }
}
