<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Subscription;
use App\Models\Library;
use App\Models\LibraryTransaction;

class LibraryPaymentService
{
    /*
    |--------------------------------------------------------------------------
    | Create Razorpay Order
    |--------------------------------------------------------------------------
    */

    public function razorpayPaymentCore(int $subscriptionId, int $planMode, int $libraryId): array
    {
        
        $subscription = Subscription::findOrFail($subscriptionId);

        match ($planMode) {
            1 => [$month, $amount] = [1,  $subscription->monthly_fees],
            2 => [$month, $amount] = [12, $subscription->yearly_fees],
            3 => [$month, $amount] = [3,  $subscription->three_monthly_fees],
            4 => [$month, $amount] = [6,  $subscription->six_monthly_fees],
            5 => [$month, $amount] = [24, $subscription->two_yearly_fees],
            default => throw new \Exception('Invalid plan mode'),
        };

        $gstRow   = DB::table('gst_discount')->first();
        $gst      = $gstRow->gst ?? 0;
        $discount = $gstRow->discount ?? 0;

        $discountAmount = $amount * ($discount / 100);
        $finalAmount    = ($amount - $discountAmount) * (1 + ($gst / 100));

        // Always create new transaction
        $transaction = LibraryTransaction::create([
            'library_id' => $libraryId,
            'subscription' => $subscriptionId,
            'amount' => $amount,
            'paid_amount' => $finalAmount,
            'month' => $month,
            'gst' => $gst,
            'discount' => $discount,
            'is_paid' => 0
        ]);

        if ($finalAmount <= 0) {
            return [
                'type' => 'free',
                'transaction' => $transaction
            ];
        }

        $response = Http::withBasicAuth(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        )
        ->timeout(20)
        ->retry(2, 200)
        ->post('https://api.razorpay.com/v1/orders', [
            'amount' => (int) ($finalAmount * 100),
            'currency' => 'INR',
            'receipt' => 'TXN_'.$transaction->id,
            'payment_capture' => 1,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Razorpay order creation failed');
        }

        return [
            'type' => 'paid',
            'transaction' => $transaction,
            'order' => $response->json(),
            'amount' => $finalAmount,
            'currency'    => 'INR',
        ];
    }

    

    /*
    |--------------------------------------------------------------------------
    | Verify Razorpay Signature
    |--------------------------------------------------------------------------
    */

    public function verifySignature($orderId, $paymentId, $signature): bool
    {
        $generatedSignature = hash_hmac(
            'sha256',
            $orderId . "|" . $paymentId,
            config('services.razorpay.secret')
        );

        return hash_equals($generatedSignature, $signature);
    }

    /*
    |--------------------------------------------------------------------------
    | Finalize Transaction
    |--------------------------------------------------------------------------
    */

    // public function finalize(LibraryTransaction $transaction, string $paymentId,$response = null): void
    // {
    //     $duration = $transaction->month ?? 0;

    //     if (LibraryTransaction::where('library_id', $transaction->library_id)
    //         ->where('status', 1)
    //         ->exists()) {

    //         $last = LibraryTransaction::where('library_id', $transaction->library_id)
    //             ->where('status', 1)
    //             ->latest()
    //             ->first();

    //         $start = Carbon::parse($last->end_date)->addDay();
    //         $status = 0;
    //     } else {
    //         $start = now();
    //         $status = 1;
    //     }

    //     $end = $start->copy()->addMonths($duration);

    //     $transaction->update([
    //         'start_date'       => $start,
    //         'end_date'         => $end,
    //         'transaction_date' => now(),
    //         'payment_mode'     => 1,
    //         'is_paid'          => 1,
    //         'status'           => $status,
    //         'transaction_id'   => $paymentId,
    //     ]);

    //     Library::where('id', $transaction->library_id)
    //         ->update([
    //             'is_paid' => 1,
    //             'library_type' => $transaction->subscription
    //         ]);
    // }

    public function finalize(LibraryTransaction $transaction, string $paymentId,$response = null): void
    {
        $duration = $transaction->month ?? 0;
        $today = Carbon::today();

        $lastPaidTransaction = LibraryTransaction::withoutGlobalScopes()
            ->where('library_id', $transaction->library_id)
            ->where('id', '!=', $transaction->id)
            ->where('is_paid', 1)
            ->whereNotNull('end_date')
            ->orderByDesc('end_date')
            ->orderByDesc('id')
            ->first();

        if ($lastPaidTransaction && Carbon::parse($lastPaidTransaction->end_date)->startOfDay()->gte($today)) {
            $start = Carbon::parse($lastPaidTransaction->end_date)->addDay();
            $status = 0;
        } else {
            $start = now();
            $status = 1;
        }

        $end = $start->copy()->addMonths($duration);

        $transaction->update([
            'start_date'       => $start,
            'end_date'         => $end,
            'transaction_date' => now(),
            'payment_mode'     => 1,
            'is_paid'          => 1,
            'status'           => $status,
            'transaction_id'   => $paymentId,
        ]);

        Library::where('id', $transaction->library_id)
            ->update([
                'is_paid' => 1,
            ]);

        if ($status === 1) {
            Library::where('id', $transaction->library_id)
                ->update([
                    'library_type' => $transaction->subscription
                ]);

            LibraryTransaction::withoutGlobalScopes()
                ->where('library_id', $transaction->library_id)
                ->where('id', '!=', $transaction->id)
                ->where('status', 1)
                ->update(['status' => 0]);
        }
    }
}
