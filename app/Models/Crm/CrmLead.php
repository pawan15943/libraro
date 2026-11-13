<?php

namespace App\Models\Crm;

use App\Models\Library;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmLead extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'next_follow_up_at' => 'datetime',
        'last_called_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public const SOURCES = [
        'manual' => 'Added by hand',
        'import' => 'Uploaded',
        'signup' => 'Registered – not paid',
        'website' => 'Website',
        'demo' => 'Demo request',
        'inquiry' => 'Contact inquiry',
        'referral' => 'Referral',
        'old-leads' => 'Old leads page',
    ];

    public const PRIORITIES = ['hot' => 'Hot', 'warm' => 'Warm', 'cold' => 'Cold'];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(CrmStage::class, 'stage_id');
    }

    public function subStage(): BelongsTo
    {
        return $this->belongsTo(CrmStage::class, 'sub_stage_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class, 'library_id');
    }

    public function lastDisposition(): BelongsTo
    {
        return $this->belongsTo(CrmDisposition::class, 'last_disposition_id');
    }

    public function calls(): HasMany
    {
        return $this->hasMany(CrmCall::class, 'lead_id')->latest('called_at');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(CrmReminder::class, 'lead_id')->orderBy('remind_at');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmActivity::class, 'lead_id')->latest('id');
    }

    /** Leads still being worked: not Enrolled, Not interested or Invalid. */
    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereNotIn('stage_id', CrmStage::closedIds() ?: [0]);
    }

    public function scopeClosed(Builder $q): Builder
    {
        return $q->whereIn('stage_id', CrmStage::closedIds() ?: [0]);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->library_name ?: ($this->contact_name ?: '+91 '.$this->mobile);
    }

    public function getIsClosedAttribute(): bool
    {
        return in_array($this->stage_id, CrmStage::closedIds(), true);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->next_follow_up_at && $this->next_follow_up_at->isPast();
    }

    public function getWhatsappNumberAttribute(): string
    {
        return '91'.$this->mobile;
    }

    /** Data for the call / message / follow-up sheets (read by public/js/admin-crm.js). */
    public function sheetData(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->display_name,
            'contact' => $this->contact_name,
            'library' => $this->library_name,
            'mobile' => $this->mobile,
            'stage' => $this->stage_id,
            'sub' => $this->sub_stage_id,
            'priority' => $this->priority,
            'source' => $this->source,
            'urls' => [
                'call' => route('crm.calls.store', $this->id),
                'history' => route('crm.calls.history', $this->id),
                'message' => route('crm.messages.store', $this->id),
                'reminder' => route('crm.reminders.store', $this->id),
                'stage' => route('crm.leads.stage', $this->id),
                'show' => route('crm.leads.show', $this->id),
            ],
        ];
    }

    public static function normaliseMobile(?string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $raw);
        $digits = strlen($digits) > 10 ? substr($digits, -10) : $digits;

        return preg_match('/^[6-9]\d{9}$/', $digits) ? $digits : null;
    }
}
