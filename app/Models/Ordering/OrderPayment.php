<?php

namespace App\Models\Ordering;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Model;

/** Money in (payment) or out (refund) for an order. txn_ref is unique per order and kind. */
class OrderPayment extends Model
{
    use HasBranch;

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
