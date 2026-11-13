<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

/** WhatsApp / SMS text with {name}, {library}, {agent}, {mobile}, {login_link} placeholders. */
class CrmTemplate extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public const PLACEHOLDERS = ['{name}', '{library}', '{agent}', '{mobile}', '{login_link}'];

    public function scopeActive($q)
    {
        return $q->where('is_active', true)->orderBy('sort');
    }

    public function render(CrmLead $lead, ?string $agent = null): string
    {
        return strtr($this->body, [
            '{name}' => $lead->contact_name ?: ($lead->library_name ?: 'there'),
            '{library}' => $lead->library_name ?: 'your library',
            '{agent}' => $agent ?: 'Team',
            '{mobile}' => $lead->mobile,
            '{login_link}' => url('library/login'),
        ]);
    }
}
