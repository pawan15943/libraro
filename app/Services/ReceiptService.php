<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerTransaction;
use App\Models\LearnerTransactionActivity;
use App\Models\Library;
use App\Models\LibraryTransaction;
use App\Models\Subscription;
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
            // ->where('is_paid', 1)
            ->first();

        if (! $transaction) {
            abort(404, 'Receipt not found');
        }

        return $transaction;
    }

    /**
     * @return array<string, mixed>
     */

    // public function buildViewData(LearnerTransaction $transaction): array
    // {
    //     if (! $transaction->is_paid) {
    //         abort(404, 'Receipt not found');
    //     }

    //     $user = Learner::find($transaction->learner_id);

    //     if (! $user) {
    //         abort(404, 'Learner not found');
    //     }

    //     $learnerDetail = LearnerDetail::withoutGlobalScopes()
    //         ->with(['plan', 'planType'])
    //         ->find($transaction->learner_detail_id);

    //     if (! $learnerDetail) {
    //         abort(404, 'Learner detail not found');
    //     }

    //     $library = Library::leftJoin('branches', 'libraries.id', '=', 'branches.library_id')
    //         ->where('libraries.id', $learnerDetail->library_id)
    //         ->select(
    //             'libraries.library_name',
    //             'libraries.email',
    //             'libraries.library_mobile',
    //             'branches.library_address'
    //         )
    //         ->first();

    //     $branch_logo = Branch::where('id', $transaction->branch_id)->value('library_logo');
    //     $branch_slug = Branch::where('id', $transaction->branch_id)->value('slug');

    //     $tran = LearnerTransactionActivity::where('learner_id', $transaction->learner_id)
    //         ->value('transaction_id');

    //     $planType = $learnerDetail->planType;
    //     $start = $planType ? date('h:i A', strtotime($planType->start_time)) : 'NA';
    //     $end = $planType ? date('h:i A', strtotime($planType->end_time)) : 'NA';
    //     $shift_timing = $start . ' to ' . $end;

    //     return [
    //         'branch_logo' => $branch_logo ?? '',
    //         'subscription' => $planType->name ?? 'NA',
    //         'name' => $user->name ?? 'NA',
    //         'email' => $user->email ?? 'NA',
    //         'transactiondate' => $transaction->paid_date ?? 'NA',
    //         'paid_amount' => $transaction->paid_amount ?? 'NA',
    //         'payment_mode' => $learnerDetail->payment_mode ?? 'NA',
    //         'invoice_ref_no' => $tran ?? 'NA',
    //         'total_amount' => $transaction->total_amount ?? 'NA',
    //         'start_date' => $learnerDetail->plan_start_date ?? 'NA',
    //         'end_date' => $learnerDetail->plan_end_date ?? 'NA',
    //         'monthly_amount' => $transaction->total_amount ?? 'NA',
    //         'month' => optional($learnerDetail->plan)->plan_id ?? 'NA',
    //         'currency' => 'Rs.',
    //         'library_name' => $library->library_name ?? '',
    //         'library_email' => $library->email ?? '',
    //         'library_mobile' => $library->library_mobile ?? '',
    //         'library_address' => $library->library_address ?? '',
    //         'branch_slug' => $branch_slug ?? '',
    //         'shift_timing' => $shift_timing,
    //     ];
    // }



    public function buildViewData(LearnerTransaction $transaction): array
    {
        if (! $transaction->is_paid) {
            abort(404, 'Receipt not found');
        }

        return learnerReceiptPayloadByTransactionId((int) $transaction->id);
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

    public function findPaidLibraryTransaction(int $transactionId): LibraryTransaction
    {
        $transaction = LibraryTransaction::withoutGlobalScopes()
            ->where('id', $transactionId)
            ->where('is_paid', 1)
            ->first();

        if (! $transaction) {
            abort(404, 'Library receipt not found');
        }

        return $transaction;
    }

    public function libraryReceiptOpenLink(int $transactionId): string
    {
        $transaction = $this->findPaidLibraryTransaction($transactionId);

        return URL::signedRoute('receipt.library.signed', ['transactionId' => $transaction->id]);
    }

    public function libraryDownloadResponse(LibraryTransaction $transaction): Response
    {
        Log::info('Library receipt download', ['transaction_id' => $transaction->id]);
        $path = 'receipts/library_txn_' . $transaction->id . '.pdf';
        $disk = Storage::disk('public');

        $pdf = $this->libraryPdf($transaction)->output();
        $disk->put($path, $pdf);

        return response()->file(storage_path('app/public/' . $path));
    }

    public function libraryPdf(LibraryTransaction $transaction)
    {
        return Pdf::loadView('recieptPdf', $this->libraryReceiptPayload($transaction));
    }

    public function libraryReceiptPayload(LibraryTransaction $transaction): array
    {
        $library = Library::withoutGlobalScopes()->find($transaction->library_id);

        if (! $library) {
            abort(404, 'Library receipt data not found');
        }

        $branch = Branch::withoutGlobalScopes()
            ->where('library_id', $library->id)
            ->where('status', 1)
            ->orderBy('id')
            ->first();

        $subscription = Subscription::where('id', $transaction->subscription)
            ->value('name');

        return [
            'logo' => '',
            'subscription' => $subscription ?? 'NA',
            'name' => $library->library_owner ?? $library->library_name ?? 'NA',
            'email' => $library->email ?? 'NA',
            'transactiondate' => $transaction->transaction_date ?? optional($transaction->updated_at)->format('Y-m-d') ?? 'NA',
            'paid_amount' => $transaction->paid_amount ?? 'NA',
            'payment_mode' => $transaction->payment_mode ?? 1,
            'invoice_ref_no' => $transaction->transaction_id ?? ('LIB_' . $transaction->id),
            'total_amount' => $transaction->amount ?? $transaction->paid_amount ?? 'NA',
            'start_date' => $transaction->start_date ?? 'NA',
            'end_date' => $transaction->end_date ?? 'NA',
            'monthly_amount' => $transaction->amount ?? $transaction->paid_amount ?? 'NA',
            'month' => $transaction->month ?? 'NA',
            'currency' => 'Rs.',
            'library_name' => $library->library_name ?? '',
            'library_email' => $library->email ?? '',
            'library_mobile' => $library->library_mobile ?? '',
            'library_address' => $branch->library_address ?? '',
            'branch_logo' => $branch->library_logo ?? null,
            'branch_slug' => $branch->slug ?? null,
            'shift_timing' => null,
        ];
    }

    public function otherPaymentOpenLink(int $activityId): string
    {
        $activity = $this->findOtherPaymentActivity($activityId);

        return URL::signedRoute('receipt.other-payment.signed', ['activityId' => $activity->id]);
    }

    public function findOtherPaymentActivity(int $activityId): LearnerTransactionActivity
    {
        $activity = LearnerTransactionActivity::withoutGlobalScopes()
            ->where('id', $activityId)
            ->whereIn('payment_type', ['TOKEN MONEY', 'MISCELLANEOUS'])
            ->first();

        if (! $activity) {
            abort(404, 'Other payment receipt not found');
        }

        return $activity;
    }

    public function otherPaymentDownloadResponse(LearnerTransactionActivity $activity): Response
    {
        Log::info('Other payment receipt download', ['activity_id' => $activity->id]);
        $path = 'receipts/other_payment_' . $activity->id . '.pdf';
        $disk = Storage::disk('public');

        $pdf = $this->otherPaymentPdf($activity)->output();
        $disk->put($path, $pdf);

        return response()->file(storage_path('app/public/' . $path));
    }

    public function otherPaymentPdf(LearnerTransactionActivity $activity)
    {
        return Pdf::loadView('other_payment_receipt_final', $this->otherPaymentPayload($activity));
    }

    public function otherPaymentPayload(LearnerTransactionActivity $activity): array
    {
        $paymentType = strtoupper((string) $activity->payment_type);
        if (! in_array($paymentType, ['TOKEN MONEY', 'MISCELLANEOUS'], true)) {
            abort(404, 'Other payment receipt not found');
        }

        $transaction = LearnerTransaction::withoutGlobalScopes()
            ->with('learnerDetail.plan', 'learnerDetail.planType')
            ->find($activity->learner_transaction_id);
        $learner = Learner::withoutGlobalScopes()->find($activity->learner_id);
        $detail = $transaction?->learnerDetail
            ?: LearnerDetail::withoutGlobalScopes()->with(['plan', 'planType'])->find($transaction?->learner_detail_id);

        if (! $transaction || ! $learner || ! $detail) {
            abort(404, 'Other payment receipt data not found');
        }

        $branch = Branch::withoutGlobalScopes()->find($activity->branch_id ?: $transaction->branch_id);
        $branchTokenMoney = (float) ($branch->token_money ?? 0);
        $currentAmount = (float) ($activity->amount ?? 0);
        $paidToDate = 0.0;

        $history = LearnerTransactionActivity::withoutGlobalScopes()
            ->where('learner_id', $activity->learner_id)
            ->where('payment_type', $paymentType)
            ->where('id', '<=', $activity->id)
            ->where(function ($query) {
                $query->whereNull('dr_cr')->orWhere('dr_cr', 'Cr');
            })
            ->orderBy('id')
            ->get()
            ->map(function ($row) use ($paymentType, $branchTokenMoney, &$paidToDate) {
                $amount = (float) ($row->amount ?? 0);
                $paidToDate += $amount;

                return [
                    'learner_transaction_id' => (int) ($row->learner_transaction_id ?? 0),
                    'payment_type' => (string) ($row->payment_type ?? ''),
                    'particular' => (string) ($row->particular ?? ''),
                    'amount' => $amount,
                    'dr_cr' => (string) ($row->dr_cr ?? ''),
                    'payment_mode' => $this->paymentModeLabel($row->payment_mode),
                    'transaction_id' => (string) ($row->transaction_id ?? ''),
                    'date' => $row->date
                        ? \Carbon\Carbon::parse($row->date)->format('d-m-Y')
                        : optional($row->created_at)->format('d-m-Y'),
                    'due_after' => $paymentType === 'TOKEN MONEY'
                        ? max(0, $branchTokenMoney - $paidToDate)
                        : 0,
                ];
            })
            ->values()
            ->toArray();

        $pendingAmount = $paymentType === 'TOKEN MONEY'
            ? max(0, $branchTokenMoney - $paidToDate)
            : 0;
        $expectedAmount = $paymentType === 'TOKEN MONEY' && $branchTokenMoney > 0
            ? $branchTokenMoney
            : null;

        return [
            'branch_logo' => receiptTemplateLogoPath($branch->library_logo ?? ''),
            'library_name' => $branch?->library?->library_name ?? '',
            'library_email' => $branch?->library?->email ?? '',
            'library_mobile' => $branch?->library?->library_mobile ?? '',
            'library_address' => $branch->library_address ?? '',
            'library_no' => $branch?->library?->library_no ?? '',
            'invoice_ref_no' => $activity->transaction_id ?: ('OTHER_' . $activity->id),
            'invoice_date' => $activity->date ?? optional($activity->created_at)->toDateString() ?? now()->toDateString(),
            'learner_no' => $learner->learner_no ?? '',
            'learner_name' => $learner->name ?? '',
            'seat_no' => receiptSeatDisplay($detail->seat_no),
            'plan_name' => optional($detail->plan)->name ?? '',
            'plan_type' => optional($detail->planType)->name ?? '',
            'plan_start_date' => $detail->plan_start_date ?? '',
            'plan_end_date' => $detail->plan_end_date ?? '',
            'payment_type' => $paymentType,
            'payment_mode' => $this->paymentModeLabel($activity->payment_mode),
            'expected_amount' => $expectedAmount,
            'paid_amount' => $paidToDate,
            'pending_amount' => $expectedAmount !== null ? $pendingAmount : null,
            'current_paid' => $currentAmount,
            'activities' => $history,
            'current_activity_id' => (int) $activity->id,
        ];
    }

    private function paymentModeLabel($value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '';
        }

        if (is_numeric($value)) {
            return match ((int) $value) {
                1 => 'ONLINE',
                2 => 'OFFLINE',
                3 => 'PAYLATER',
                default => (string) $value,
            };
        }

        return strtoupper((string) $value);
    }
}
