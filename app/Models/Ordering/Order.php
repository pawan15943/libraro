<?php

namespace App\Models\Ordering;

use App\Models\Branch;
use App\Models\Learner;
use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Model;

/**
 * A learner's order. `status` (the order's progress) and `payment_status` (money) are independent;
 * each line also has its own `fulfilment_status`.
 */
class Order extends Model
{
    use HasBranch;

    protected $guarded = ['id'];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'allow_partial' => 'boolean',
        'estimated_ready_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'preparing_at' => 'datetime',
        'ready_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'reminded_at' => 'datetime',
        'escalated_at' => 'datetime',
    ];

    // order status
    public const NEW = 'new';

    public const UNDER_REVIEW = 'under_review';

    public const ATTENTION = 'attention';

    public const REPLACEMENT_PENDING = 'replacement_pending';

    public const CONFIRMED = 'confirmed';

    public const PREPARING = 'preparing';

    public const READY = 'ready';

    public const OUT_FOR_DELIVERY = 'out_for_delivery';

    public const DELIVERY_ATTEMPTED = 'delivery_attempted';

    public const DELIVERED = 'delivered';

    public const COLLECTED = 'collected';

    public const COMPLETED = 'completed';

    public const REJECTED = 'rejected';

    public const CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::NEW => 'New',
        self::UNDER_REVIEW => 'Under review',
        self::ATTENTION => 'Attention required',
        self::REPLACEMENT_PENDING => 'Replacement pending',
        self::CONFIRMED => 'Confirmed',
        self::PREPARING => 'Preparing',
        self::READY => 'Ready for pickup',
        self::OUT_FOR_DELIVERY => 'Out for delivery',
        self::DELIVERY_ATTEMPTED => 'Delivery attempted',
        self::DELIVERED => 'Delivered',
        self::COLLECTED => 'Collected',
        self::COMPLETED => 'Completed',
        self::REJECTED => 'Rejected',
        self::CANCELLED => 'Cancelled',
    ];

    /** Orders still being worked on (count toward the learner's open-order limit). */
    public const OPEN = [self::NEW, self::UNDER_REVIEW, self::ATTENTION, self::REPLACEMENT_PENDING, self::CONFIRMED,
        self::PREPARING, self::READY, self::OUT_FOR_DELIVERY, self::DELIVERY_ATTEMPTED];

    /** Finished: no further edits (business rule 14). */
    public const CLOSED = [self::COMPLETED, self::REJECTED, self::CANCELLED];

    // payment status
    public const PAY_PENDING = 'pending';

    public const PAY_PROCESSING = 'processing';

    public const PAY_PAID = 'paid';

    public const PAY_FAILED = 'failed';

    public const PAY_PARTIAL = 'partially_paid';

    public const PAY_REFUND_PENDING = 'refund_pending';

    public const PAY_REFUNDED = 'refunded';

    public const PAY_PARTIALLY_REFUNDED = 'partially_refunded';

    public const PAYMENT_LABELS = [
        self::PAY_PENDING => 'Pending',
        self::PAY_PROCESSING => 'Verifying',
        self::PAY_PAID => 'Paid',
        self::PAY_FAILED => 'Failed',
        self::PAY_PARTIAL => 'Partially paid',
        self::PAY_REFUND_PENDING => 'Refund pending',
        self::PAY_REFUNDED => 'Refunded',
        self::PAY_PARTIALLY_REFUNDED => 'Partially refunded',
    ];

    public function lines()
    {
        return $this->hasMany(OrderLine::class)->orderBy('id');
    }

    public function replacements()
    {
        return $this->hasMany(OrderReplacement::class)->orderBy('id');
    }

    public function payments()
    {
        return $this->hasMany(OrderPayment::class)->orderBy('id');
    }

    public function activities()
    {
        return $this->hasMany(OrderActivity::class)->orderBy('id');
    }

    public function learner()
    {
        return $this->belongsTo(Learner::class)->withoutGlobalScopes();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function paymentLabel(): string
    {
        return self::PAYMENT_LABELS[$this->payment_status] ?? ucfirst(str_replace('_', ' ', $this->payment_status));
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    public function locationLabel(): string
    {
        return match ($this->mode) {
            'seat' => 'Seat '.($this->seat_no ?: '—'),
            'table' => 'Table '.($this->table_label ?: '—'),
            default => 'Counter pickup',
        };
    }

    /** Amount still to collect from the learner. */
    public function dueAmount(): float
    {
        return max(0, round((float) $this->total - (float) $this->paid_amount + (float) $this->refunded_amount, 2));
    }
}
