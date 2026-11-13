<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmReminder extends Model
{
    protected $guarded = [];

    protected $casts = ['remind_at' => 'datetime', 'notified_at' => 'datetime', 'done_at' => 'datetime'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePending($q)
    {
        return $q->where('status', 'pending');
    }
}
