<?php

namespace App\Models\Ordering;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Model;

/** Per-branch switches and rules of Ordering & Services (one row per branch). */
class OrderSetting extends Model
{
    use HasBranch;

    protected $guarded = ['id'];

    protected $casts = [
        'is_enabled' => 'boolean',
        'mode_seat' => 'boolean',
        'mode_table' => 'boolean',
        'mode_counter' => 'boolean',
        'seat_change_allowed' => 'boolean',
        'delivery_charge' => 'decimal:2',
        'pay_online' => 'boolean',
        'pay_offline' => 'boolean',
        'pay_later' => 'boolean',
        'pay_later_learner_ids' => 'array',
        'pay_later_limit' => 'decimal:2',
        'expired_can_order' => 'boolean',
        'sound_enabled' => 'boolean',
        'replacement_enabled' => 'boolean',
        'replacement_multiple' => 'boolean',
        'partial_fulfilment' => 'boolean',
    ];

    public const MODES = ['seat' => 'Seat delivery', 'table' => 'Cafeteria table', 'counter' => 'Counter pickup'];

    public const PAYMENTS = ['online' => 'Pay online (UPI QR)', 'offline' => 'Pay at counter', 'pay_later' => 'Pay later'];

    /** Order modes the branch has switched on, in display order. */
    public function enabledModes(): array
    {
        return array_values(array_filter(array_keys(self::MODES), fn ($m) => (bool) $this->{'mode_'.$m}));
    }

    /** Payment methods the branch has switched on (before the per-category limit). */
    public function enabledPayments(): array
    {
        return array_values(array_filter(array_keys(self::PAYMENTS), fn ($p) => (bool) $this->{'pay_'.($p === 'pay_later' ? 'later' : $p)}));
    }

    /** Inside today's ordering hours? Null hours = open all day; supports hours past midnight. */
    public function isOpenAt(\DateTimeInterface $at): bool
    {
        if (! $this->opens_at || ! $this->closes_at) {
            return true;
        }
        $t = $at->format('H:i:s');
        $open = strlen($this->opens_at) === 5 ? $this->opens_at.':00' : $this->opens_at;
        $close = strlen($this->closes_at) === 5 ? $this->closes_at.':00' : $this->closes_at;

        return $open <= $close ? ($t >= $open && $t < $close) : ($t >= $open || $t < $close);
    }

    public function uploadExtensions(): array
    {
        return array_values(array_filter(array_map(fn ($e) => strtolower(trim($e)), explode(',', (string) $this->upload_types))));
    }
}
