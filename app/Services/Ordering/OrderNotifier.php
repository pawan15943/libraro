<?php

namespace App\Services\Ordering;

use App\Models\Learner;
use App\Models\Library;
use App\Models\Ordering\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Order messages into the existing `notifications` table (same rows the learner app lists via
 * /api/v1/learner/notifications and the library bell shows). Live screens (learner order page,
 * staff board) poll on their own; this is the record people see later or in the app.
 * A failure here never breaks the order action (spec: "Notification fails → keep order in queue").
 */
class OrderNotifier
{
    /** event → [title, description] for the learner; :no, :where, :amount, :reason are filled in. */
    private const LEARNER = [
        'placed' => ['Order #:no placed', 'We sent your order to the library. You will see when they accept it.'],
        'accepted' => ['Order #:no accepted', 'The library accepted your order.'],
        'preparing' => ['Order #:no is being prepared', 'Your order is being prepared.'],
        'ready' => ['Order #:no is ready', 'Collect it at the counter.'],
        'out_for_delivery' => ['Order #:no is on the way', 'Coming to :where.'],
        'delivery_attempted' => ['We came to :where', 'You were not there. Please ask at the counter for order #:no.'],
        'delivered' => ['Order #:no delivered', 'Enjoy! :amount'],
        'collected' => ['Order #:no collected', 'Enjoy! :amount'],
        'rejected' => ['Order #:no not accepted', ':reason'],
        'cancelled_by_staff' => ['Order #:no cancelled', ':reason'],
        'replacement_requested' => ['Something ran out in order #:no', 'Please choose a replacement or remove the item.'],
        'payment_approved' => ['Payment received for order #:no', 'Thank you!'],
        'payment_rejected' => ['Payment not received for order #:no', 'The library could not find your payment. Please pay again.'],
        'payment_recorded' => ['Payment received for order #:no', 'Thank you!'],
        'refund_recorded' => ['Refund for order #:no', 'The library returned :amount.'],
        'pay_reminder' => ['Payment pending for order #:no', 'Please pay :amount.'],
    ];

    public function learner(Order $order, string $event, array $extra = []): void
    {
        if (! isset(self::LEARNER[$event])) {
            return;
        }
        [$title, $text] = self::LEARNER[$event];
        $vars = [
            ':no' => Str::afterLast($order->order_no, '-'),
            ':where' => $order->locationLabel(),
            ':amount' => $order->dueAmount() > 0 ? 'Please pay ₹'.number_format($order->dueAmount(), 2).'.' : '',
            ':reason' => $extra['reason'] ?? ($order->reject_reason ?: $order->cancel_reason ?: ''),
        ];
        if ($event === 'refund_recorded') {
            $vars[':amount'] = '₹'.number_format((float) ($extra['amount'] ?? 0), 2);
        }
        if ($event === 'pay_reminder') {
            $vars[':amount'] = '₹'.number_format($order->dueAmount(), 2);
        }
        $this->insert(Learner::class, (int) $order->learner_id, 'learner', strtr($title, $vars), trim(strtr($text, $vars)),
            route('learner.orders.show', $order->order_no, false));
    }

    /** Owner message (escalation): an order nobody accepted in time. */
    public function owner(Order $order, string $title, string $text): void
    {
        $this->insert(Library::class, (int) $order->library_id, 'library', $title, $text, route('orders.show', $order->id, false));
    }

    private function insert(string $type, int $id, string $guard, string $title, string $text, string $link): void
    {
        try {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\CustomNotification',
                'notifiable_type' => $type,
                'notifiable_id' => $id,
                'notification_type' => 'order',
                'data' => json_encode(['notification_type' => 'order', 'title' => $title, 'description' => $text, 'link' => $link, 'image' => null, 'guard' => $guard]),
                'guard' => $guard,
                'start_date' => today()->toDateString(),
                'end_date' => today()->addDays(7)->toDateString(),
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Order notification not saved: '.$e->getMessage());
        }
    }
}
