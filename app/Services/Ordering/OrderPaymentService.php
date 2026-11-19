<?php

namespace App\Services\Ordering;

use App\Models\Branch;
use App\Models\Ordering\Order;
use App\Models\Ordering\OrderActivity;
use App\Models\Ordering\OrderPayment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Money of an order. Payment status is independent of the order status (spec §8).
 * Online = the branch UPI QR: the learner pays in any UPI app and sends the screenshot (+ UTR);
 * the order is "Verifying" until staff check it (step 3). A UTR is counted once per order.
 */
class OrderPaymentService
{
    /** upi:// link and inline SVG QR for the amount still due, or null when the branch has no UPI ID. */
    public function upi(Order $order): ?array
    {
        $branch = Branch::withoutGlobalScopes()->find($order->branch_id);
        if (empty(optional($branch)->upi_id)) {
            return null;
        }
        $amount = number_format($order->dueAmount(), 2, '.', '');
        $link = 'upi://pay?pa='.rawurlencode($branch->upi_id).'&pn='.rawurlencode($branch->display_name ?: ($branch->name ?: 'Library'))
            .'&am='.$amount.'&cu=INR&tn='.rawurlencode('Order '.$order->order_no);

        return [
            'upi_id' => $branch->upi_id,
            'payee' => $branch->display_name ?: ($branch->name ?: 'Library'),
            'amount' => (float) $amount,
            'link' => $link,
            'qr' => (string) QrCode::size(220)->margin(1)->generate($link),
        ];
    }

    /** Can the learner send a screenshot for this order now? */
    public function canSendProof(Order $order): bool
    {
        return $order->payment_method === 'online'
            && ! in_array($order->status, [Order::CANCELLED, Order::REJECTED], true)
            && in_array($order->payment_status, [Order::PAY_PENDING, Order::PAY_FAILED, Order::PAY_PARTIAL], true)
            && $order->dueAmount() > 0;
    }

    /** Offline payment ways staff can record at the counter. */
    public const COUNTER_METHODS = ['cash' => 'Cash', 'upi_counter' => 'UPI at counter', 'card' => 'Card', 'other' => 'Other'];

    /**
     * Staff checked a learner's UPI screenshot: approve → money counted, reject → learner asked to pay again.
     */
    public function reviewProof(Order $order, int $paymentId, bool $approve, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $paymentId, $approve, $note) {
            $o = Order::withoutGlobalScopes()->lockForUpdate()->findOrFail($order->id);
            $p = OrderPayment::withoutGlobalScopes()->where('order_id', $o->id)->where('kind', 'payment')->lockForUpdate()->findOrFail($paymentId);
            if ($p->status !== 'pending') {
                throw ValidationException::withMessages(['payment' => 'This payment was already '.($p->status === 'verified' ? 'approved' : 'rejected').'.']);
            }
            [$type, $id] = OrderActivity::actor();
            $p->update(['status' => $approve ? 'verified' : 'failed', 'verified_at' => now(), 'note' => $note ? mb_substr($note, 0, 255) : $p->note,
                'recorded_by_type' => $p->recorded_by_type ?: $type, 'recorded_by_id' => $p->recorded_by_id ?: $id]);

            $from = $o->payment_status;
            if ($approve) {
                $o->paid_amount = round((float) $o->paid_amount + (float) $p->amount, 2);
            }
            $o->payment_status = $this->paymentState($o, $approve ? null : Order::PAY_FAILED);
            $o->version++;
            $o->save();
            OrderActivity::log($o, $approve ? 'payment_approved' : 'payment_rejected', $from, $o->payment_status,
                ['amount' => (float) $p->amount, 'utr' => $p->txn_ref, 'note' => $note]);
            $this->autoComplete($o);

            return $o;
        });
    }

    /** Staff took money at the counter (cash / UPI / card). Never more than what is due. */
    public function recordPayment(Order $order, float $amount, string $method, ?string $ref = null, ?string $note = null): Order
    {
        if (! isset(self::COUNTER_METHODS[$method])) {
            throw ValidationException::withMessages(['method' => 'Choose how the learner paid.']);
        }
        $ref = $ref !== null ? (trim($ref) ?: null) : null;

        return DB::transaction(function () use ($order, $amount, $method, $ref, $note) {
            $o = Order::withoutGlobalScopes()->lockForUpdate()->findOrFail($order->id);
            if (in_array($o->status, [Order::CANCELLED, Order::REJECTED], true)) {
                throw ValidationException::withMessages(['amount' => 'This order is '.strtolower($o->statusLabel()).'; nothing to collect.']);
            }
            $due = $o->dueAmount();
            $amount = round($amount, 2);
            if ($due <= 0) {
                throw ValidationException::withMessages(['amount' => 'This order is already paid.']);
            }
            if ($amount <= 0 || $amount > $due + 0.001) {
                throw ValidationException::withMessages(['amount' => 'Enter an amount from ₹1 to ₹'.number_format($due, 2).'.']);
            }
            if ($ref && OrderPayment::withoutGlobalScopes()->where('order_id', $o->id)->where('kind', 'payment')->where('txn_ref', $ref)->exists()) {
                throw ValidationException::withMessages(['ref' => 'This reference is already recorded for this order.']);
            }
            [$type, $id] = OrderActivity::actor();
            OrderPayment::withoutGlobalScopes()->create([
                'library_id' => $o->library_id, 'branch_id' => $o->branch_id, 'order_id' => $o->id, 'kind' => 'payment',
                'method' => $method, 'amount' => $amount, 'status' => 'verified', 'txn_ref' => $ref, 'verified_at' => now(),
                'note' => $note ? mb_substr($note, 0, 255) : null, 'recorded_by_type' => $type, 'recorded_by_id' => $id,
            ]);
            $from = $o->payment_status;
            $o->paid_amount = round((float) $o->paid_amount + $amount, 2);
            $o->payment_status = $this->paymentState($o);
            $o->version++;
            $o->save();
            OrderActivity::log($o, 'payment_recorded', $from, $o->payment_status, ['amount' => $amount, 'method' => $method, 'ref' => $ref]);
            $this->autoComplete($o);

            return $o;
        });
    }

    /** Money given back (cancelled / rejected order, or a cheaper replacement). */
    public function recordRefund(Order $order, float $amount, string $method, ?string $ref = null, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $amount, $method, $ref, $note) {
            $o = Order::withoutGlobalScopes()->lockForUpdate()->findOrFail($order->id);
            $refundable = $this->refundable($o);
            $amount = round($amount, 2);
            if ($refundable <= 0) {
                throw ValidationException::withMessages(['amount' => 'Nothing to refund on this order.']);
            }
            if ($amount <= 0 || $amount > $refundable + 0.001) {
                throw ValidationException::withMessages(['amount' => 'Enter an amount from ₹1 to ₹'.number_format($refundable, 2).'.']);
            }
            [$type, $id] = OrderActivity::actor();
            OrderPayment::withoutGlobalScopes()->create([
                'library_id' => $o->library_id, 'branch_id' => $o->branch_id, 'order_id' => $o->id, 'kind' => 'refund',
                'method' => isset(self::COUNTER_METHODS[$method]) ? $method : 'other', 'amount' => $amount, 'status' => 'verified',
                'txn_ref' => $ref ? mb_substr(trim($ref), 0, 100) : null, 'verified_at' => now(),
                'note' => $note ? mb_substr($note, 0, 255) : null, 'recorded_by_type' => $type, 'recorded_by_id' => $id,
            ]);
            $from = $o->payment_status;
            $o->refunded_amount = round((float) $o->refunded_amount + $amount, 2);
            $o->payment_status = $this->refundable($o) > 0 ? Order::PAY_PARTIALLY_REFUNDED : Order::PAY_REFUNDED;
            $o->version++;
            $o->save();
            OrderActivity::log($o, 'refund_recorded', $from, $o->payment_status, ['amount' => $amount, 'method' => $method, 'ref' => $ref]);

            return $o;
        });
    }

    /** Money the library holds beyond what the order now costs (everything, if it was cancelled). */
    public function refundable(Order $o): float
    {
        $net = (float) $o->paid_amount - (float) $o->refunded_amount;
        $keep = in_array($o->status, [Order::CANCELLED, Order::REJECTED], true) ? 0.0 : (float) $o->total;

        return max(0, round($net - $keep, 2));
    }

    /** Payment status from the amounts (a refund in progress wins). */
    public function paymentState(Order $o, ?string $failed = null): string
    {
        $closed = in_array($o->status, [Order::CANCELLED, Order::REJECTED], true);
        if ($this->refundable($o) > 0 && ($closed || (float) $o->paid_amount > (float) $o->total)) {
            return Order::PAY_REFUND_PENDING;
        }
        $pendingProof = OrderPayment::withoutGlobalScopes()->where('order_id', $o->id)->where('kind', 'payment')->where('status', 'pending')->exists();
        if ($closed) {
            return $pendingProof ? Order::PAY_PROCESSING : ((float) $o->paid_amount > 0 ? Order::PAY_REFUNDED : ($failed ?: Order::PAY_PENDING));
        }
        if ($o->dueAmount() <= 0) {
            return Order::PAY_PAID;
        }
        if ($pendingProof) {
            return Order::PAY_PROCESSING;
        }
        if ((float) $o->paid_amount > 0) {
            return Order::PAY_PARTIAL;
        }

        return $failed ?: Order::PAY_PENDING;
    }

    /** Delivered / collected and now fully paid → the order closes by itself. */
    private function autoComplete(Order $o): void
    {
        if (in_array($o->status, [Order::DELIVERED, Order::COLLECTED], true) && $o->dueAmount() <= 0) {
            $from = $o->status;
            $o->update(['status' => Order::COMPLETED, 'completed_at' => now(), 'version' => $o->version + 1]);
            OrderActivity::log($o, 'completed', $from, Order::COMPLETED, ['auto' => true]);
        }
    }

    public function submitProof(Order $order, UploadedFile $screenshot, ?string $utr): OrderPayment
    {
        $utr = $utr !== null ? strtoupper(preg_replace('/\s+/', '', $utr)) : null;
        $utr = $utr === '' ? null : $utr;

        return DB::transaction(function () use ($order, $screenshot, $utr) {
            $order = Order::withoutGlobalScopes()->lockForUpdate()->findOrFail($order->id);
            if (! $this->canSendProof($order)) {
                throw ValidationException::withMessages(['screenshot' => $order->payment_status === Order::PAY_PROCESSING
                    ? 'Your payment proof is already sent. The library is checking it.'
                    : 'This order does not need an online payment now.']);
            }
            if ($utr && OrderPayment::withoutGlobalScopes()->where('order_id', $order->id)->where('kind', 'payment')->where('txn_ref', $utr)->exists()) {
                throw ValidationException::withMessages(['utr' => 'This UTR was already sent for this order.']);
            }

            $path = $screenshot->store('ordering/'.$order->branch_id.'/payments', 'local');
            try {
                $payment = OrderPayment::withoutGlobalScopes()->create([
                    'library_id' => $order->library_id,
                    'branch_id' => $order->branch_id,
                    'order_id' => $order->id,
                    'kind' => 'payment',
                    'method' => 'upi_qr',
                    'amount' => $order->dueAmount(),
                    'status' => 'pending',
                    'txn_ref' => $utr,
                    'screenshot' => $path,
                    'recorded_by_type' => 'learner',
                    'recorded_by_id' => $order->learner_id,
                ]);
            } catch (\Throwable $e) {
                Storage::disk('local')->delete($path);
                throw $e;
            }

            $from = $order->payment_status;
            $order->update(['payment_status' => Order::PAY_PROCESSING, 'payment_screenshot' => $path, 'payment_ref' => $utr, 'version' => $order->version + 1]);
            OrderActivity::log($order, 'payment_proof_sent', $from, Order::PAY_PROCESSING, ['amount' => (float) $payment->amount, 'utr' => $utr]);

            return $payment;
        });
    }
}
