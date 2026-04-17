<?php

namespace App\Http\Controllers;

use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerTransaction;
use App\Models\LearnerTransactionActivity;
use App\Services\RenewTransactionDeleteService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RenewDeleteController extends Controller
{

public function index(Request $request, RenewTransactionDeleteService $service)
{
    $search = $request->get('search');

    if (blank($search)) {
        return view('library_learner.learner-renew-delete', [
            'learners' => null,
        ]);
    }

    $encryptdata = encryptData($search);

    // ✅ Step 1: Search learners
    $learners = Learner::query()
        ->where(function ($q) use ($search, $encryptdata) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('mobile', 'LIKE', "%{$encryptdata}%")
              ->orWhere('seat_no', 'LIKE', "%{$search}%")
              ->orWhere('email', $encryptdata);
        })
        ->get();

    $result = [];

    foreach ($learners as $learner) {

        // ✅ Get all learner details
        $details = LearnerDetail::where('learner_id', $learner->id)
            ->orderByDesc('id')
            ->get();

        // ✅ Renew check
        if ($details->count() <= 1) {
            continue;
        }

        // ✅ Latest detail (renew)
        $latestDetail = $details->first();

        // ✅ Get transaction
        $transaction = $service->findLatestRenewTransaction($learner->id);

        if (!$transaction) {
            continue;
        }

        // ✅ Validation
        $isValid = $transaction->created_at >= now()->subDays($service::DELETE_WINDOW_DAYS);

        // ✅ Prepare row data (UI expects this)
        $row = (object) array_merge(
            $learner->toArray(),
            [
                'plan_name' => optional($latestDetail->plan)->name ?? '—',
                'plan_type_name' => optional($latestDetail->planType)->name ?? '—',
                'start_time' => optional($latestDetail->planType)->start_time ?? null,
                'end_time' => optional($latestDetail->planType)->end_time ?? null,
                'seat_no' => $latestDetail->seat_no ?? null,
                'plan_start_date' => $latestDetail->plan_start_date,
                'plan_end_date' => $latestDetail->plan_end_date,
                'plan_type_id' => $latestDetail->plan_type_id,
                'plan_id' => $latestDetail->plan_id,
                'plan_price_id' => $latestDetail->plan_price_id,
                'learner_detail_id' => $latestDetail->id,
                'payment_mode' => $latestDetail->payment_mode,
            ]
        );

        $result[] = [
            'row' => $row,
            'transaction' => $transaction,
            'validation' => [
                'ok' => $isValid,
                'message' => $isValid ? null : $service::VALIDATION_MESSAGE,
            ]
        ];
    }

    // ✅ Pagination
    $currentPage = LengthAwarePaginator::resolveCurrentPage();
    $perPage = 10;

    $pagedData = array_slice($result, ($currentPage - 1) * $perPage, $perPage);

    $paginator = new LengthAwarePaginator(
        $pagedData,
        count($result),
        $perPage,
        $currentPage,
        [
            'path' => request()->url(),
            'query' => request()->query()
        ]
    );

    return view('library_learner.learner-renew-delete', [
        'learners' => $paginator,
    ]);
}

  
   
    /**
     * Same token rules as LearnerController::fetchLearnerData search (name / mobile / seat / email).
     */
    // private function applyRenewDeleteNameMobileEmailSearch(Builder $query, string $search): void
    // {
    //     $search = trim($search);
    //     if ($search === '') {
    //         $query->whereRaw('1 = 0');

    //         return;
    //     }

    //     $encryptdata = encryptData($search);
    //     $tokens = array_values(array_filter(preg_split('/\s+/u', $search), static fn ($t) => $t !== ''));

    //     $query->where(function ($q) use ($search, $encryptdata, $tokens) {
    //         $q->where(function ($nameQ) use ($search, $tokens) {
    //             $nameQ->where('learners.name', 'LIKE', "%{$search}%");
    //             foreach ($tokens as $word) {
    //                 $nameQ->orWhere('learners.name', 'LIKE', "%{$word}%");
    //             }
    //         });

    //         $q->orWhere('learners.mobile', 'LIKE', "%{$encryptdata}%");
    //         foreach ($tokens as $token) {
    //             $digits = preg_replace('/\D+/', '', $token);
    //             if ($digits !== '' && strlen($digits) >= 4) {
    //                 $q->orWhere('learners.mobile', 'LIKE', '%' . encryptData($digits) . '%');
    //             }
    //         }

    //         $q->orWhere('learners.seat_no', 'LIKE', "%{$search}%");
    //         foreach ($tokens as $token) {
    //             if ($token !== '' && is_numeric($token)) {
    //                 $q->orWhere('learners.seat_no', 'LIKE', "%{$token}%");
    //             }
    //         }

    //         $q->orWhere('learners.email', $encryptdata);
    //         foreach ($tokens as $token) {
    //             if (str_contains($token, '@')) {
    //                 $q->orWhere('learners.email', encryptData($token));
    //             }
    //         }
    //     });
    // }

    // public function showTransaction(Request $request, int $learner, RenewTransactionDeleteService $service)
    // {
      

    //     $transaction = $service->findLatestRenewTransaction($learner);
    //     Log::info('transaction', ['transaction' => $transaction]);
    //     // if (!$transaction) {
    //     //     $days = RenewTransactionDeleteService::DELETE_WINDOW_DAYS;

    //     //     return redirect()
    //     //         ->route('create.renew.delete.index', $request->only(['search']))
    //     //         ->with('error', "No renew transaction found in the last {$days} days for this learner.");
    //     // }

    //     $validation = $service->validateDeletable($transaction);

    //     $detail = LearnerDetail::with(['plan', 'planType', 'learner'])
    //         ->find($transaction->learner_detail_id);

    //     $activities = LearnerTransactionActivity::withoutGlobalScopes()
    //         ->where('learner_id', $learner)
    //         ->orderByDesc('id')
    //         ->limit(200)
    //         ->get();

    //     $learnerRow = Learner::find($learner);

    //     return view('library_learner.learner-renew-delete-transaction', [
    //         'learner' => $learnerRow,
    //         'transaction' => $transaction,
    //         'detail' => $detail,
    //         'activities' => $activities,
    //         'canDelete' => $validation['ok'],
    //         'deleteBlockReason' => $validation['message'],
    //         'paymentModeLabel' => $this->displayPaymentModeLabel($detail->payment_mode ?? null),
    //         'transactionCreatedDisplay' => $transaction->created_at
    //             ? Carbon::parse($transaction->created_at)->format('j M Y, g:i A')
    //             : '—',
    //     ]);
    // }

    public function destroy(Request $request, int $transaction, RenewTransactionDeleteService $service)
    {
        $transactionModel = LearnerTransaction::withoutGlobalScopes()
            ->where('library_id', getLibraryId())
            ->when(getCurrentBranch(), fn ($q) => $q->where('branch_id', getCurrentBranch()))
            ->find($transaction);

        if (!$transactionModel) {
            return redirect()
                ->back()
                ->with('error', 'Transaction not found.');
        }

        $postedLearner = (int) $request->input('learner_id', 0);
        if ($postedLearner === 0 || $postedLearner !== (int) $transactionModel->learner_id) {
            return redirect()
                ->back()
                ->with('error', 'Learner does not match this transaction.');
        }

        $this->assertLearnerInScope($transactionModel->learner_id);

        try {
            $service->deleteRenewTransaction($transactionModel);
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('create.renew.delete.index', $request->only(['search']))
            ->with('success', 'Renew transaction deleted successfully.');
    }

    private function assertLearnerInScope(int $learnerId): void
    {
        $ok = Learner::query()
            ->where('id', $learnerId)
            ->where('library_id', getLibraryId())
            ->when(getCurrentBranch(), fn ($q) => $q->where('branch_id', getCurrentBranch()))
            ->exists();

        if (!$ok) {
            abort(404);
        }
    }

    private function displayPaymentModeLabel($value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '—';
        }

        $s = trim((string) $value);
        if (is_numeric($s)) {
            return match ((int) $s) {
                1 => 'ONLINE',
                2 => 'OFFLINE',
                3 => 'PAYLATER',
                default => $s,
            };
        }

        return $s;
    }
}
