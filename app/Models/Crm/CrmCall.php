<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmCall extends Model
{
    protected $guarded = [];

    protected $casts = ['is_connected' => 'boolean', 'called_at' => 'datetime', 'follow_up_at' => 'datetime'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id')->withTrashed();
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function disposition(): BelongsTo
    {
        return $this->belongsTo(CrmDisposition::class, 'disposition_id');
    }

    public function stageTo(): BelongsTo
    {
        return $this->belongsTo(CrmStage::class, 'stage_to_id');
    }

    public function getDurationLabelAttribute(): ?string
    {
        if (! $this->duration_seconds) {
            return null;
        }
        $m = intdiv($this->duration_seconds, 60);
        $s = $this->duration_seconds % 60;

        return $m ? $m.'m '.$s.'s' : $s.'s';
    }
}
