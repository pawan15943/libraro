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
        ]);

        $transaction = $receiptService->findPaidTransaction((int) $validated['id']);

        return $receiptService->downloadResponse($transaction);
    }

    public function mobile(int $transactionId, ReceiptService $receiptService)
    {
        $transaction = $receiptService->findPaidTransaction($transactionId);
        $data = $receiptService->buildViewData($transaction);

        return view('recieptPdf', $data);
    }
}
