<?php

namespace App\Models\Ordering;

use Illuminate\Database\Eloquent\Model;

/** One item of an order; price and names are a snapshot from the time of ordering. */
class OrderLine extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'options' => 'array',
    ];

    public const PENDING = 'pending';

    public const UNAVAILABLE = 'unavailable';

    public const REPLACEMENT_PENDING = 'replacement_pending';

    public const REPLACED = 'replaced';

    public const REMOVED = 'removed';

    public const FULFILLED = 'fulfilled';

    /** Lines that still count toward the order (not replaced away or removed). */
    public const ACTIVE = [self::PENDING, self::UNAVAILABLE, self::REPLACEMENT_PENDING, self::FULFILLED];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function item()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id')->withTrashed();
    }

    public function replacements()
    {
        return $this->hasMany(OrderReplacement::class);
    }
}
