<?php

namespace App\Models\Ordering;

use Illuminate\Database\Eloquent\Model;

/** Staff's suggestion for an unavailable line; the learner must accept it (spec §5). */
class OrderReplacement extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'suggested_price' => 'decimal:2',
        'price_diff' => 'decimal:2',
        'deadline_at' => 'datetime',
        'responded_at' => 'datetime',
        'reminded_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function line()
    {
        return $this->belongsTo(OrderLine::class, 'order_line_id');
    }

    public function suggestedItem()
    {
        return $this->belongsTo(OrderItem::class, 'suggested_item_id')->withTrashed();
    }
}
