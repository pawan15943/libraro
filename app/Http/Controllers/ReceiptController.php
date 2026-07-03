<?php

namespace App\Http\Controllers;

use App\Services\ReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function link(Request $request, ReceiptService $receiptService): JsonResponse
    {
        $validated = $request->validate([
            'transaction_id' => 'required|integer',
        ]);

        $transactionId = (int) $validated['transaction_id'];
        $openLink = $receiptService->receiptOpenLink($transactionId);
        $mobileOpenLink = $receiptService->receiptMobileLink($transactionId);

        return response()->json([
            'status' => true,
            'message' => 'Receipt links',
            'data' => [
                'receipt_url' => $openLink,
                'receipt_mobile_url' => $mobileOpenLink,
            ],
        ]);
    }

    public function learnerDownload(Request $request, ReceiptService $receiptService)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
            'learner_id' => 'nullable|integer',
        ]);

        $txnId = (int) $validated['id'];
        $transaction = \App\Models\LearnerTransaction::withoutGlobalScopes()
            ->where('id', $txnId)
            ->first();

        if (! $transaction && !empty($validated['learner_id'])) {
            $transaction = \App\Models\LearnerTransaction::withoutGlobalScopes()
                ->where('learner_id', (int) $validated['learner_id'])
                ->orderByDesc('id')
                ->first();
        }

        if (! $transaction) {
            return back()->with('error', 'Receipt transaction not found.');
        }

        return $receiptService->downloadResponse($transaction);
    }

    public function mobile(int $transactionId, ReceiptService $receiptService)
    {
        $transaction = $receiptService->findPaidTransaction($transactionId);

        return $receiptService->downloadResponse($transaction);
    }

    public function library(int $transactionId, ReceiptService $receiptService)
    {
        $transaction = $receiptService->findPaidLibraryTransaction($transactionId);

        return $receiptService->libraryDownloadResponse($transaction);
    }

    public function otherPayment(int $activityId, ReceiptService $receiptService)
    {
        $activity = $receiptService->findOtherPaymentActivity($activityId);

        return $receiptService->otherPaymentDownloadResponse($activity);
    }
}
