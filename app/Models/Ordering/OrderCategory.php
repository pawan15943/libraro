<?php

namespace App\Models\Ordering;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderCategory extends Model
{
    use HasBranch, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_service' => 'boolean',
        'allow_online' => 'boolean',
        'allow_offline' => 'boolean',
        'allow_pay_later' => 'boolean',
    ];

    /** Starter categories offered to a branch the first time (spec §2): [name, icon, is_service, pay later allowed]. */
    public const DEFAULTS = [
        ['Food', 'flame', false, true],
        ['Snacks', 'gift', false, true],
        ['Beverages', 'coffee', false, true],
        ['Water', 'droplet', false, true],
        ['Stationery', 'notebook-pen', false, false],
        ['Printing', 'printer', false, true],
        ['Medicines', 'circle-plus', false, false],
        ['Personal Care', 'sparkles', false, true],
        ['Study Essentials', 'bookmark', false, true],
        ['Other Services', 'wrench', true, true],
    ];

    /** Icons the owner can pick for a category (names from public/img/lb-icons.svg). */
    public const ICONS = ['flame', 'coffee', 'droplet', 'gift', 'notebook-pen', 'printer', 'circle-plus', 'sparkles',
        'bookmark', 'wrench', 'plug-zap', 'lock', 'book-open', 'shapes', 'star', 'tag', 'scan-line', 'file-text'];

    public function items()
    {
        return $this->hasMany(OrderItem::class)->orderBy('sort_order')->orderBy('name');
    }

    /** Payment method keys this category accepts. */
    public function allowedPayments(): array
    {
        return array_keys(array_filter([
            'online' => $this->allow_online,
            'offline' => $this->allow_offline,
            'pay_later' => $this->allow_pay_later,
        ]));
    }
}
