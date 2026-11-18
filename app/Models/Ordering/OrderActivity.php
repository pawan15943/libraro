<?php

namespace App\Models\Ordering;

use Illuminate\Database\Eloquent\Model;

/** Audit trail of an order: who did what, when. Append-only. */
class OrderActivity extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
