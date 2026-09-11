<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\LearnerTransactionActivity;
use App\Models\Library;

$library = Library::where('status', 1)->first();
if (!$library) {
    echo "No active library found.\n";
    exit;
}

Auth::guard('library')->setUser($library);
session(['library_id' => $library->id]);
$branch = Branch::where('library_id', $library->id)->first();
if ($branch) {
    session(['branch_id' => $branch->id]);
    $library->update(['current_branch' => $branch->id]);
}

echo "=== VERIFYING TRANSACTION AGGREGATE EQUALITY ===\n\n";

$branchId = getCurrentBranch();
$todayDate = now()->toDateString();

// Original 7 Queries
$origCollection = LearnerTransactionActivity::where('branch_id', $branchId)->whereDate('date', $todayDate)
    ->where(function($q) {
        $q->whereIn('payment_type', ['SEAT ASSIGNMENT', 'RENEW', 'REACTIVE','UPGRADE'])
        ->orWhere(function($sub) {
            $sub->where('payment_type', 'CHANGE PLAN')->where('dr_cr', 'Cr');
        });
    })->sum('amount');

$origOther = LearnerTransactionActivity::where('branch_id', $branchId)->whereDate('date', $todayDate)
    ->whereIn('payment_type', ['TOKEN MONEY', 'MISCELLANEOUS'])->where('dr_cr', 'Cr')->sum('amount');

$origExpense = LearnerTransactionActivity::where('branch_id', $branchId)->whereDate('date', $todayDate)
    ->where('payment_type', 'EXPENSE')->sum('amount');

$origPending = LearnerTransactionActivity::where('branch_id', $branchId)->whereDate('date', $todayDate)
    ->where('payment_type', 'PENDING')->sum('amount');

$origRefund = LearnerTransactionActivity::where('branch_id', $branchId)->whereDate('date', $todayDate)
    ->where(function($q) {
        $q->where('payment_type', 'REFUND')
        ->orWhere(function($sub) {
            $sub->where('payment_type', 'CHANGE PLAN')->where('dr_cr', 'Dr');
        });
    })->sum('amount');

$origTotalCr = LearnerTransactionActivity::where('branch_id', $branchId)->whereDate('date', $todayDate)
    ->where('dr_cr', 'Cr')->sum('amount');

$origTotalDr = LearnerTransactionActivity::where('branch_id', $branchId)->whereDate('date', $todayDate)
    ->where('dr_cr', 'Dr')->sum('amount');

$origBalance = $origTotalCr - $origTotalDr;

// Optimized Single Query
$dailyStats = LearnerTransactionActivity::where('branch_id', $branchId)
    ->whereDate('date', $todayDate)
    ->selectRaw("
        SUM(CASE WHEN payment_type IN ('SEAT ASSIGNMENT', 'RENEW', 'REACTIVE', 'UPGRADE') OR (payment_type = 'CHANGE PLAN' AND dr_cr = 'Cr') THEN amount ELSE 0 END) as today_collection,
        SUM(CASE WHEN payment_type IN ('TOKEN MONEY', 'MISCELLANEOUS') AND dr_cr = 'Cr' THEN amount ELSE 0 END) as today_other_amt,
        SUM(CASE WHEN payment_type = 'EXPENSE' THEN amount ELSE 0 END) as today_expense,
        SUM(CASE WHEN payment_type = 'PENDING' THEN amount ELSE 0 END) as today_pending,
        SUM(CASE WHEN payment_type = 'REFUND' OR (payment_type = 'CHANGE PLAN' AND dr_cr = 'Dr') THEN amount ELSE 0 END) as today_refund,
        SUM(CASE WHEN dr_cr = 'Cr' THEN amount ELSE 0 END) as total_cr,
        SUM(CASE WHEN dr_cr = 'Dr' THEN amount ELSE 0 END) as total_dr
    ")
    ->first();

$optCollection = (float) ($dailyStats->today_collection ?? 0);
$optOther      = (float) ($dailyStats->today_other_amt ?? 0);
$optExpense    = (float) ($dailyStats->today_expense ?? 0);
$optPending    = (float) ($dailyStats->today_pending ?? 0);
$optRefund     = (float) ($dailyStats->today_refund ?? 0);
$optTotalCr    = (float) ($dailyStats->total_cr ?? 0);
$optTotalDr    = (float) ($dailyStats->total_dr ?? 0);
$optBalance    = $optTotalCr - $optTotalDr;

$matches = (
    (float)$origCollection === (float)$optCollection &&
    (float)$origOther === (float)$optOther &&
    (float)$origExpense === (float)$optExpense &&
    (float)$origPending === (float)$optPending &&
    (float)$origRefund === (float)$optRefund &&
    (float)$origTotalCr === (float)$optTotalCr &&
    (float)$origTotalDr === (float)$optTotalDr &&
    (float)$origBalance === (float)$optBalance
);

if ($matches) {
    echo "[SUCCESS] Daily Transaction Aggregates are 100% IDENTICAL!\n";
    echo "Collection: $optCollection, Other: $optOther, Expense: $optExpense, Pending: $optPending, Refund: $optRefund, Balance: $optBalance\n";
} else {
    echo "[WARNING] Mismatch detected in transaction calculations!\n";
}
