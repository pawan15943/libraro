<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LearnerTransactionActivity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Monthly Revenue (Payment Collection) Report API
    |--------------------------------------------------------------------------
    | Mirrors the web "Monthly Revenue Report" (ReportController@monthlyPaymentCollection
    | / resources/views/report/monthly_payment_collection.blade.php) for the app,
    | grouped by date with per-day balance and overall summary totals.
    */
    public function monthlyPaymentCollection(Request $request)
    {
        if ($denied = $this->denyWithoutAppPermission('Monthly Revenue Report')) {
            return $denied;
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'month'      => 'nullable|integer|between:1,12',
            'year'       => 'nullable|integer|digits:4',
            'page'       => 'nullable|integer|min:1',
            'per_page'   => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $validated = $validator->validated();

        if (!empty($validated['start_date']) && !empty($validated['end_date'])) {
            $fromDate = Carbon::parse($validated['start_date'])->startOfDay();
            $toDate   = Carbon::parse($validated['end_date'])->endOfDay();
        } elseif (!empty($validated['month']) && !empty($validated['year'])) {
            $fromDate = Carbon::create($validated['year'], $validated['month'], 1)->startOfMonth();
            $toDate   = Carbon::create($validated['year'], $validated['month'], 1)->endOfMonth();
        } else {
            $fromDate = Carbon::now()->startOfMonth();
            $toDate   = Carbon::now()->endOfMonth();
        }

        $transactions = LearnerTransactionActivity::withoutGlobalScopes()
            ->leftJoin('learners', 'learners.id', '=', 'learner_transaction_activity.learner_id')
            ->whereBetween(
                'learner_transaction_activity.date',
                [$fromDate->toDateString(), $toDate->toDateString()]
            )
            ->where('learner_transaction_activity.branch_id', getCurrentBranch())
            ->select(
                'learner_transaction_activity.*',
                'learners.name',
                'learners.seat_no'
            )
            ->orderBy('learner_transaction_activity.date', 'asc')
            ->orderBy('learner_transaction_activity.id', 'asc')
            ->get();

        $groupedTransactions = $transactions->groupBy(function ($row) {
            return Carbon::parse($row->date)->format('Y-m-d');
        });

        $totalCollection = $transactions->where('dr_cr', 'Cr')->sum('amount');

        $totalExpense = $transactions
            ->where('dr_cr', 'Dr')
            ->where('payment_type', 'EXPENSE')
            ->sum('amount');

        $totalRefund = $transactions
            ->where('dr_cr', 'Dr')
            ->where('payment_type', 'REFUND')
            ->sum('amount');

        $netProfitLoss = $totalCollection - $totalExpense - $totalRefund;

        $report = $groupedTransactions->map(function ($rows, $date) {
            $dayCollection = $rows->where('dr_cr', 'Cr')->sum('amount');
            $dayDebit      = $rows->where('dr_cr', 'Dr')->sum('amount');
            $dayBalance    = $dayCollection - $dayDebit;

            return [
                'date'         => $date,
                'date_display' => Carbon::parse($date)->format('d/m/Y'),
                'day_balance'  => (string) $dayBalance,
                'transactions' => $rows->values()->map(function ($row) {
                    $isExpense = $row->payment_type === 'EXPENSE';

                    return [
                        'seat_no'      => $isExpense ? 'EXPENSE' : ($row->seat_no ?? 'GEN'),
                        'name'         => $isExpense ? 'EXPENSE' : ($row->name ?? ''),
                        'amount'       => (string) $row->amount,
                        'dr_cr'        => $row->dr_cr,
                        'transaction'  => $isExpense ? $row->particular : $row->payment_type,
                        'payment_type' => $row->payment_type,
                        'payment_mode' => $row->payment_mode,
                    ];
                })->values(),
            ];
        })->values();

        $page    = max(1, (int) ($validated['page'] ?? 1));
        $perPage = max(1, (int) ($validated['per_page'] ?? 20));
        $total   = $report->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page    = min($page, $lastPage);

        $paginatedReport = $report->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'status'  => true,
            'message' => 'Monthly revenue report fetched successfully',
            'data'    => [
                'filters' => [
                    'start_date' => $fromDate->toDateString(),
                    'end_date'   => $toDate->toDateString(),
                ],
                'summary' => [
                    'total_collection' => (string) $totalCollection,
                    'total_expense'    => (string) $totalExpense,
                    'total_refund'     => (string) $totalRefund,
                    'net_profit_loss'  => (string) $netProfitLoss,
                ],
                'report' => $paginatedReport,
                'pagination' => [
                    'current_page' => $page,
                    'per_page'     => $perPage,
                    'total'        => $total,
                    'last_page'    => $lastPage,
                    'has_more'     => $page < $lastPage,
                ],
            ],
        ]);
    }
}
