<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerTransaction;
use App\Models\LearnerTransactionActivity;
use App\Models\Library;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ReceiptService
{
    public function findPaidTransaction(int $transactionId): LearnerTransaction
    {
        $transaction = LearnerTransaction::withoutGlobalScopes()
            ->where('id', $transactionId)
            ->where('is_paid', 1)
            ->first();

        if (! $transaction) {
            abort(404, 'Receipt not found');
        }

        return $transaction;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildViewData(LearnerTransaction $transaction): array
    {
        if (! $transaction->is_paid) {
            abort(404, 'Receipt not found');
        }

        $user = Learner::find($transaction->learner_id);

        if (! $user) {
            abort(404, 'Learner not found');
        }

        $learnerDetail = LearnerDetail::withoutGlobalScopes()
            ->with(['plan', 'planType'])
            ->find($transaction->learner_detail_id);

        if (! $learnerDetail) {
            abort(404, 'Learner detail not found');
        }

        $library = Library::leftJoin('branches', 'libraries.id', '=', 'branches.library_id')
            ->where('libraries.id', $learnerDetail->library_id)
            ->select(
                'libraries.library_name',
                'libraries.email',
                'libraries.library_mobile',
                'branches.library_address'
            )
            ->first();

        $branch_logo = Branch::where('id', $transaction->branch_id)->value('library_logo');
        $branch_slug = Branch::where('id', $transaction->branch_id)->value('slug');

        $tran = LearnerTransactionActivity::where('learner_id', $transaction->learner_id)
            ->value('transaction_id');

        $planType = $learnerDetail->planType;
        $start = $planType ? date('h:i A', strtotime($planType->start_time)) : 'NA';
        $end = $planType ? date('h:i A', strtotime($planType->end_time)) : 'NA';
        $shift_timing = $start . ' to ' . $end;

        return [
            'branch_logo' => $branch_logo ?? '',
            'subscription' => $planType->name ?? 'NA',
            'name' => $user->name ?? 'NA',
            'email' => $user->email ?? 'NA',
            'transactiondate' => $transaction->paid_date ?? 'NA',
            'paid_amount' => $transaction->paid_amount ?? 'NA',
            'payment_mode' => $learnerDetail->payment_mode ?? 'NA',
            'invoice_ref_no' => $tran ?? 'NA',
            'total_amount' => $transaction->total_amount ?? 'NA',
            'start_date' => $learnerDetail->plan_start_date ?? 'NA',
            'end_date' => $learnerDetail->plan_end_date ?? 'NA',
            'monthly_amount' => $transaction->total_amount ?? 'NA',
            'month' => optional($learnerDetail->plan)->plan_id ?? 'NA',
            'currency' => 'Rs.',
            'library_name' => $library->library_name ?? '',
            'library_email' => $library->email ?? '',
            'library_mobile' => $library->library_mobile ?? '',
            'library_address' => $library->library_address ?? '',
            'branch_slug' => $branch_slug ?? '',
            'shift_timing' => $shift_timing,
        ];
    }

    public function pdf(LearnerTransaction $transaction)
    {
        $data = learnerReceiptPayloadByTransactionId((int) $transaction->id);
        return Pdf::loadView('html-library_receipt_final', $data);
    }

    public function downloadResponse(LearnerTransaction $transaction): Response
    {
        Log::info('Receipt download', ['transaction_id' => $transaction->id]);
        $path = 'receipts/learner_txn_' . $transaction->id . '.pdf';
        $disk = Storage::disk('public');

        $pdf = $this->pdf($transaction)->output();
        $disk->put($path, $pdf);

        return response()->file(storage_path('app/public/' . $path));
    }

    public function receiptOpenLink(int $transactionId): string
    {
        $transaction = $this->findPaidTransaction($transactionId);

        return URL::signedRoute('receipt.signed', ['transactionId' => $transaction->id]);
    }

    public function receiptMobileLink(int $transactionId): string
    {
        $transaction = $this->findPaidTransaction($transactionId);

        return URL::signedRoute('receipt.mobile.signed', ['transactionId' => $transaction->id]);
    }
}
