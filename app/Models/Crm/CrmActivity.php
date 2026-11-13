<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One entry of a lead's timeline. */
class CrmActivity extends Model
{
    protected $guarded = [];

    protected $casts = ['meta' => 'array'];

    public const ICONS = [
        'created' => 'fa-solid fa-plus', 'import' => 'fa-solid fa-file-arrow-up', 'sync' => 'fa-solid fa-rotate',
        'call' => 'fa-solid fa-phone', 'stage' => 'fa-solid fa-flag', 'note' => 'fa-regular fa-note-sticky',
        'whatsapp' => 'fa-brands fa-whatsapp', 'sms' => 'fa-regular fa-comment-dots', 'assigned' => 'fa-solid fa-user-check',
        'reminder' => 'fa-regular fa-bell', 'edit' => 'fa-solid fa-pen', 'enrolled' => 'fa-solid fa-trophy',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getIconAttribute(): string
    {
        return self::ICONS[$this->type] ?? 'fa-solid fa-circle';
    }
}
