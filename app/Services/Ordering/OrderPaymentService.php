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
