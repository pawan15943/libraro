<?php

namespace App\Services\Ordering;

use App\Models\Learner;
use App\Models\Ordering\Order;
use App\Models\Ordering\OrderActivity;
use App\Models\Ordering\OrderLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Order status changes. Every change locks the row, checks it is still in the expected state
 * (two people acting on one order → only the first wins) and writes the activity log.
 * Staff transitions are added in step 3.
 */
class OrderStatusService
{
    public function __construct(private LearnerOrderingService $ordering) {}

    public function cancelByLearner(Learner $learner, Order $order, ?string $reason): Order
    {
        $settings = $this->ordering->settings($learner);

        return DB::transaction(function () use ($order, $settings, $reason) {
            $order = Order::withoutGlobalScopes()->lockForUpdate()->findOrFail($order->id);
            if (! $this->ordering->canCancel($order, $settings)) {
                throw ValidationException::withMessages(['order' => $order->isOpen()
                    ? 'The library has started on this order. Please ask the desk to cancel it.'
                    : 'This order is already '.strtolower($order->statusLabel()).'.']);
            }

            $from = $order->status;
            // money already paid (or being verified) goes back as per the library's refund rule
            $payment = $order->payment_status;
            if ((float) $order->paid_amount > 0 || $payment === Order::PAY_PROCESSING) {
                $payment = Order::PAY_REFUND_PENDING;
            }

            $order->update([
                'status' => Order::CANCELLED,
                'payment_status' => $payment,
                'cancelled_at' => now(),
                'cancel_reason' => $reason ? mb_substr($reason, 0, 255) : 'Cancelled by learner',
                'version' => $order->version + 1,
            ]);
            OrderLine::where('order_id', $order->id)->whereNotIn('fulfilment_status', [OrderLine::REPLACED])->update(['fulfilment_status' => OrderLine::REMOVED]);
            OrderActivity::log($order, 'cancelled_by_learner', $from, Order::CANCELLED, ['reason' => $reason]);

            return $order;
        });
    }
}
