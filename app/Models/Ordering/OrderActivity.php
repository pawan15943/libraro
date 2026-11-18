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

    /**
     * Record an action. Actor = whoever is signed in (learner / library owner / staff), else "system".
     */
    public static function log(Order $order, string $action, ?string $from = null, ?string $to = null, array $meta = []): self
    {
        [$type, $id] = self::actor();

        return self::create([
            'order_id' => $order->id,
            'actor_type' => $type,
            'actor_id' => $id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'meta' => $meta ?: null,
        ]);
    }

    /** [type, id] of the signed-in user, checked in the order learner → staff → owner. */
    public static function actor(): array
    {
        foreach (['learner' => 'learner', 'library_user' => 'library_user', 'library' => 'library',
            'learner_api' => 'learner', 'library_user_api' => 'library_user', 'library_api' => 'library'] as $guard => $type) {
            try {
                if (\Illuminate\Support\Facades\Auth::guard($guard)->check()) {
                    $user = \Illuminate\Support\Facades\Auth::guard($guard)->user();
                    if ($guard === 'library_api' && $user instanceof \App\Models\LibraryUser) {
                        $type = 'library_user';
                    }

                    return [$type, $user->id];
                }
            } catch (\Throwable $e) {
                // guard not configured in this context
            }
        }

        return ['system', null];
    }
}
