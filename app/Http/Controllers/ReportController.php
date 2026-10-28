<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\CustomerDetail;
use App\Models\Customers;
use App\Models\Expense;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerOperationsLog;
use App\Models\LearnerTransaction;
use App\Models\LearnerTransactionActivity;
use App\Models\PlanType;
use App\Services\SeatAvailabilityService;
use Illuminate\Http\Request;
use DB;
use Auth;
use App\Services\LearnerService;
use Carbon\Carbon;
use App\Traits\LearnerQueryTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    use LearnerQueryTrait;
    protected $learnerService;
    public function __construct(LearnerService $learnerService)
    {
        $this->learnerService = $learnerService;
    }
    /** Profit & loss — month by month: money in, expenses, refunds and what is left. */
    public function monthlyReport(Request $request)
    {
        $year = $request->get('year');
        $now = Carbon::now()->startOfMonth();

        if ($year && preg_match('/^\d{4}$/', $year)) {
            $from = Carbon::create((int) $year, 1, 1);
            $to = Carbon::create((int) $year, 12, 1);
            if ($to->gt($now)) {
                $to = $now->copy();
            }
        } else {
            $year = null;
            $from = $now->copy()->subMonths(11);
            $to = $now->copy();
        }

        $branchIds = getCurrentBranch() != 0
            ? [getCurrentBranch()]
            : Branch::where('library_id', getLibraryId())->pluck('id')->all();

        $rows = LearnerTransactionActivity::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('date', [$from->toDateString(), $to->copy()->endOfMonth()->toDateString()])
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as ym,
                SUM(CASE WHEN dr_cr = 'Cr' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN dr_cr = 'Dr' AND payment_type = 'EXPENSE' THEN amount ELSE 0 END) as expense,
                SUM(CASE WHEN dr_cr = 'Dr' AND payment_type <> 'EXPENSE' THEN amount ELSE 0 END) as refunds,
                SUM(CASE WHEN dr_cr = 'Cr' THEN 1 ELSE 0 END) as receipts")
            ->groupBy('ym')->get()->keyBy('ym');

        $months = [];
        for ($c = $from->copy(); $c->lte($to); $c->addMonth()) {
            $r = $rows->get($c->format('Y-m'));
            $income = (float) optional($r)->income;
            $expense = (float) optional($r)->expense;
            $refunds = (float) optional($r)->refunds;
            $months[] = [
                'date' => $c->copy(),
                'income' => $income,
                'expense' => $expense,
                'refunds' => $refunds,
                'profit' => $income - $expense - $refunds,
                'receipts' => (int) optional($r)->receipts,
            ];
        }
        $months = collect($months);
        // in the rolling view, skip the empty months before the library started recording money
        if (!$year) {
            $firstActive = $months->search(fn ($m) => $m['income'] > 0 || $m['expense'] > 0 || $m['refunds'] > 0);
            if ($firstActive !== false && $firstActive > 0) {
                $months = $months->slice(min($firstActive, $months->count() - 3))->values();
            }
        }

        $totals = [
            'income' => $months->sum('income'),
            'expense' => $months->sum('expense'),
            'refunds' => $months->sum('refunds'),
            'profit' => $months->sum('profit'),
        ];
        $totals['margin'] = $totals['income'] > 0 ? round($totals['profit'] / $totals['income'] * 100) : null;
        $withData = $months->filter(fn ($m) => $m['income'] > 0 || $m['expense'] > 0);
        $best = $withData->sortByDesc('profit')->first();
        $worst = $withData->sortBy('profit')->first();
        $avgProfit = $withData->count() ? $totals['profit'] / $withData->count() : 0;

        $cur = $months->last();
        $prev = $months->count() > 1 ? $months->get($months->count() - 2) : null;

        $firstYear = (int) (LearnerTransactionActivity::withoutGlobalScopes()->whereIn('branch_id', $branchIds)->min(DB::raw('YEAR(date)')) ?: $now->year);
        $years = range($now->year, min($firstYear, $now->year));

        $data = compact('months', 'totals', 'best', 'worst', 'avgProfit', 'cur', 'prev', 'year', 'years');

        if ($request->ajax()) {
            return response()->json(['html' => view('report._pnl', $data)->render()]);
        }

        return view('report.monthly_report', $data);
    }


    // The old per-month expense form is replaced by the Expenses page (ledger-based).
    public function monthlyExpenseCreate($year, $month)
    {
        return redirect()->route('add.expense.list');
    }
    public function monthlyExpenseStore(Request $request, $id = null)
    {

        $validatedData = $request->validate([
            'year' => 'required',
            'month' => 'required',
            'expense_id' => 'required|array|min:1', 
            'expense_id.*' => 'required|integer|exists:expenses,id', 
            'amount' => 'required|array|min:1', 
            'amount.*' => 'required|numeric|min:0', 
        ]);

        $year = $validatedData['year'];
        $month = $validatedData['month'];
       
        $existingExpenseIds = DB::table('monthly_expense')->where('library_id',getLibraryId())->where('branch_id',getCurrentBranch())
            ->where('year', $year)
            ->where('month', $month)
            ->pluck('expense_id')
            ->toArray();

        $expenseIdsToDelete = array_diff($existingExpenseIds, $validatedData['expense_id']);
        if (!empty($expenseIdsToDelete)) {
            DB::table('monthly_expense')
                ->where('library_id', getLibraryId())
                ->where('branch_id', getCurrentBranch())
                ->where('year', $year)
                ->where('month', $month)
                ->whereIn('expense_id', $expenseIdsToDelete)
                ->delete();
        }
        foreach ($validatedData['expense_id'] as $index => $expenseId) {
            $amount = $validatedData['amount'][$index];

            DB::table('monthly_expense')->updateOrInsert(
                [
                    'library_id' =>getLibraryId(), 
                    'branch_id' =>getCurrentBranch(), 
                    'year' => $year,
                    'month' => $month,
                    'expense_id' => $expenseId,
                ],
                [
                    'amount' => $amount,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return redirect()->route('report.monthly')->with('success', 'Expenses recorded successfully!');
    }

    public function pendingPayment(Request $request){
        $plans = $this->learnerService->getPlans();
        $planTypes =$this->learnerService->getPlanTypes();
        $dates = getLearnerMonthsAndYears();
        $dynamicyears =$dates['years'];
        $dynamicmonths = $dates['months'];
        $filters = [
            'year' => $request->get('year'),
            'month' => $request->get('month'),
            'plan_id' => $request->get('plan_id'),
            'plan_type'  => $request->get('plan_type'),
            'search'  => $request->get('search'),
        ];

        $today = Carbon::today();
        
        $extend_day = getExtendDays();
       
        $fiveDaysbetween = $today->copy()->addDays(5);
        $query = LearnerDetail::where('library_id',getLibraryId())->with([ 'plan', 'planType', 'learner'])
            ->where('is_paid', 1)
            ->where('status', 1)
            ->where('plan_end_date', '<', $today->format('Y-m-d'))
            ->whereRaw("DATE_ADD(plan_end_date, INTERVAL ? DAY) >= CURDATE()", [$extend_day])
            ->whereNotExists(function ($subQuery) use ($fiveDaysbetween) {
                $subQuery->select(DB::raw(1))
                    ->from('learner_detail as ld2')
                    ->whereColumn('ld2.learner_id', 'learner_detail.learner_id') // Fully qualify `learner_detail.learner_id`
                    ->where('ld2.plan_end_date', '>', $fiveDaysbetween->format('Y-m-d'));
            });
           
            if (getCurrentBranch() != 0 ) {
                $query->where('learner_detail.branch_id', getCurrentBranch());
            }

     
       
        $learners = $this->fetchlearnerData($filters, $query);

        $metrics = [
            'total_pending' => count($learners),
            'in_extension' => 0,
            'settlement_count' => 0,
            'total_settlement_due' => 0,
            'overdue_expired' => 0,
        ];

        foreach ($learners as $val) {
            $endDate = Carbon::parse($val->plan_end_date);
            $diffDays = $today->diffInDays($endDate, false);
            $inextendDate = $endDate->copy()->addDays($extend_day);
            $diffExtendDay = $today->diffInDays($inextendDate, false);
            if ($diffDays <= 0 && $diffExtendDay >= 0) {
                $metrics['in_extension']++;
            } else {
                $metrics['overdue_expired']++;
            }

            $trx = learnerTransaction($val->learner_id, $val->id);
            if ($trx && $trx->pending_amount > 0) {
                $metrics['settlement_count']++;
                $metrics['total_settlement_due'] += (float)$trx->pending_amount;
            }
        }

        $extendDay = $extend_day;
        $metrics['renewal_value'] = (float) collect($learners)->sum(fn ($v) => (float) $v->plan_price_id);
        $learners = collect($learners)->sortBy('plan_end_date')->values();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('report._grace', compact('learners', 'metrics', 'extendDay', 'filters'))->render(),
            ]);
        }

        return view('report.pending_payment', compact('plans', 'planTypes', 'dynamicyears', 'dynamicmonths', 'learners', 'metrics', 'extendDay', 'filters'));
    }

   
    
    /** Members — the booking register: who is on which seat/plan, paid or not, for any month. */
    public function learnerReport(Request $request)
    {
        $ym = $request->get('ym');
        [$fy, $fm] = $ym && preg_match('/^(\d{4})-(\d{1,2})$/', $ym, $mm) ? [(int) $mm[1], (int) $mm[2]] : [null, null];
        $status = $request->get('status', '1');
        $filters = [
            'year' => $fy,
            'month' => $fm,
            'is_paid' => $request->get('is_paid'),
            'status' => $status === 'all' ? null : $status,
            'search' => $request->get('search'),
            'plan_type' => $request->get('plan_type'),
        ];

        $query = LearnerDetail::where('library_id', getLibraryId())
            ->with(['plan', 'planType', 'learner', 'transaction'])
            ->orderBy('plan_end_date', 'desc');
        if (getCurrentBranch() != 0) {
            $query->where('branch_id', getCurrentBranch());
        }
        $learners = $this->fetchlearnerData($filters, $query);

        // month picker: from the first booking to next month
        $range = LearnerDetail::where('library_id', getLibraryId())
            ->when(getCurrentBranch() != 0, fn ($q) => $q->where('branch_id', getCurrentBranch()));
        $first = (clone $range)->min('plan_start_date');
        $cursor = Carbon::now()->addMonth()->startOfMonth();
        $stop = $first ? Carbon::parse($first)->startOfMonth() : Carbon::now()->subYear()->startOfMonth();
        $months = [];
        while ($cursor->gte($stop) && count($months) < 36) {
            $months[$cursor->format('Y-n')] = $cursor->format('F Y');
            $cursor->subMonth();
        }

        $owed = fn ($d) => (float) optional($d->transaction)->pending_amount;
        $metrics = [
            'total' => $learners->count(),
            'members' => $learners->pluck('learner_id')->unique()->count(),
            'active' => $learners->where('status', 1)->count(),
            'expired' => $learners->where('status', 0)->count(),
            'unpaid' => $learners->where('is_paid', 0)->count(),
            'billed' => (float) $learners->sum(fn ($d) => (float) $d->plan_price_id),
            'pending' => (float) $learners->sum($owed),
            'by_shift' => $learners->groupBy(fn ($d) => optional($d->planType)->name ?: '—')->map->count()->sortDesc(),
        ];

        $planTypes = $this->learnerService->getPlanTypes();
        $data = compact('learners', 'months', 'metrics', 'filters', 'ym', 'status', 'planTypes');

        if ($request->ajax()) {
            return response()->json(['html' => view('report._members', $data)->render()]);
        }

        return view('report.learner_report', $data);
    }

    public function upcomingPayment(Request $request){
        $days = (int)($request->get('days') ?: 7);
        if ($days < 1) $days = 7;
        $search = $request->get('search');
        $plan_id = $request->get('plan_id');
        $plans = $this->learnerService->getPlans();

        $today = Carbon::now()->format('Y-m-d');
        $targetDate = Carbon::now()->addDays($days)->format('Y-m-d');
       
        $query = $this->getAllLearnersByLibrary()
        ->whereHas('learnerDetails', function ($q) use ($today, $targetDate, $plan_id) {
            $q->whereBetween('plan_end_date', [$today, $targetDate]);
            if (!empty($plan_id)) {
                $q->where('plan_id', $plan_id);
            }
        })
        ->whereNotExists(function ($subQuery) use ($targetDate) {
            $subQuery->select(DB::raw(1))
                ->from('learner_detail as ld2') 
                ->whereColumn('ld2.learner_id', 'learners.id') 
                ->where('ld2.plan_end_date', '>', $targetDate);
        })
        ->with(['learnerDetails' => function($q) use ($today, $targetDate, $plan_id) {
            $q->whereBetween('plan_end_date', [$today, $targetDate])
              ->with(['plan', 'planType']);
            if (!empty($plan_id)) {
                $q->where('plan_id', $plan_id);
            }
        }]);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('seat_no', 'like', "%{$search}%");
            });
        }

        $data = $query->get();
        if (getCurrentBranch() != 0 && getCurrentBranch() != null) {
            $data = $data->filter(function ($learner) {
                return optional($learner->learnerDetails->first())->branch_id == getCurrentBranch();
            });
        }

        $learners = $data;

        $todayStr = Carbon::today()->format('Y-m-d');
        $tomorrowStr = Carbon::tomorrow()->format('Y-m-d');
        $expiring_today = 0;
        $expiring_tomorrow = 0;
        $expiring_later = 0;

        foreach ($learners as $l) {
            foreach ($l->learnerDetails as $d) {
                $endDate = Carbon::parse($d->plan_end_date)->format('Y-m-d');
                if ($endDate === $todayStr) {
                    $expiring_today++;
                } elseif ($endDate === $tomorrowStr) {
                    $expiring_tomorrow++;
                } else {
                    $expiring_later++;
                }
            }
        }

        $learners = $learners->sortBy(fn ($l) => optional($l->learnerDetails->first())->plan_end_date)->values();

        $metrics = [
            'total_upcoming' => count($learners),
            'expiring_today' => $expiring_today,
            'expiring_tomorrow' => $expiring_tomorrow,
            'expiring_later' => $expiring_later,
            // what renewing everyone on their current plan would bring in
            'renewal_value' => (float) $learners->sum(fn ($l) => (float) optional($l->learnerDetails->first())->plan_price_id),
        ];

        $filters = [
            'days' => $days,
            'plan_id' => $plan_id,
            'search' => $search,
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('report._expiring', compact('learners', 'metrics', 'filters'))->render(),
            ]);
        }

        return view('report.upcoming_payment', compact('learners', 'metrics', 'plans', 'filters'));
    }

    /** Expired — members who left; ranked for win-back (recent + long-standing first). */
    public function expiredLearner(Request $request)
    {
        $window = $request->get('window') ?: '90';
        $search = $request->get('search');
        $today = Carbon::today();

        $query = LearnerDetail::where('library_id', getLibraryId())
            ->where('plan_start_date', '<=', $today->toDateString())
            ->with(['plan', 'planType', 'learner'])
            ->where('status', 0)
            ->whereHas('learner', fn ($q) => $q->where('status', 0));
        if (getCurrentBranch() != 0) {
            $query->where('branch_id', getCurrentBranch());
        }

        // one row per member: their last plan
        $details = $this->fetchlearnerData(['search' => $search], $query)
            ->sortByDesc('plan_end_date')->unique('learner_id')->values();

        $cut = match ($window) {
            '30' => $today->copy()->subDays(30),
            '90' => $today->copy()->subDays(90),
            '365' => $today->copy()->subDays(365),
            default => null,
        };
        $all = $details;
        $learners = $cut ? $details->filter(fn ($d) => Carbon::parse($d->plan_end_date)->gte($cut))->values() : $details;

        // how long each member stayed (first plan start → last plan end)
        $firstStart = $learners->isEmpty() ? collect() : LearnerDetail::withoutGlobalScopes()
            ->whereIn('learner_id', $learners->pluck('learner_id'))
            ->groupBy('learner_id')->selectRaw('learner_id, MIN(plan_start_date) as first_start, COUNT(*) as plans')
            ->get()->keyBy('learner_id');

        $metrics = [
            'lost' => $learners->count(),
            'last30' => $all->filter(fn ($d) => Carbon::parse($d->plan_end_date)->gte($today->copy()->subDays(30)))->count(),
            'monthly_value' => (float) $learners->sum(fn ($d) => (float) $d->plan_price_id),
            'loyal' => $learners->filter(fn ($d) => (int) optional($firstStart->get($d->learner_id))->plans >= 2)->count(),
            'total' => $all->count(),
        ];

        $data = compact('learners', 'metrics', 'window', 'search', 'firstStart');

        if ($request->ajax()) {
            return response()->json(['html' => view('report._expired', $data)->render()]);
        }

        return view('report.expired_learner', $data);
    }

    public function fetchlearnerData( $filters, $query){
  
        Log::info('Filters applied:', $filters);
        if (!empty($filters)) {
            if (!empty($filters['plan_id'])) {
                $query->where('plan_id', $filters['plan_id']);
            }
            if (!empty($filters['plan_type'])) {
                Log::info('Filter applied: plan type');
                $query->where('plan_type_id', $filters['plan_type']);
            }

            if (!empty($filters['expiredyear'])) {
                Log::info('Filter applied: expiredyear ');
                $query->whereYear('plan_end_date', $filters['expiredyear']);
            }
        
            if (!empty($filters['expiredmonth']) && !empty($filters['expiredyear'])) {
                Log::info('Filter applied: expiredyear and expiredmonth');
                $query->whereYear('plan_end_date', $filters['expiredyear'])
                      ->whereMonth('plan_end_date', $filters['expiredmonth']);
            }
           
            if (isset($filters['is_paid']) && $filters['is_paid'] !== '' && $filters['is_paid'] !== null) {
                Log::info('Filter applied: is_paid', ['is_paid' => $filters['is_paid']]);
                $query->where('is_paid', $filters['is_paid']);
            }

            // Apply the year & month filter if provided
            if (!empty($filters['year']) && !empty($filters['month'])) {
                $y = (int)$filters['year'];
                $m = (int)$filters['month'];
                Log::info('Filter applied: year and month', ['year' => $y, 'month' => $m]);
                $startOfMonth = Carbon::create($y, $m, 1)->startOfMonth()->toDateString();
                $endOfMonth = Carbon::create($y, $m, 1)->endOfMonth()->toDateString();
                $query->where('plan_start_date', '<=', $endOfMonth)
                      ->where('plan_end_date', '>=', $startOfMonth);
            } elseif (!empty($filters['year'])) {
                $y = (int)$filters['year'];
                Log::info('Filter applied: year', ['year' => $y]);
                $query->whereYear('plan_start_date', '<=', $y)
                      ->whereYear('plan_end_date', '>=', $y);
            } elseif (!empty($filters['month'])) {
                $m = (int)$filters['month'];
                Log::info('Filter applied: month', ['month' => $m]);
                $query->where(function($q) use ($m) {
                    $q->whereMonth('plan_start_date', $m)
                      ->orWhereMonth('plan_end_date', $m);
                });
            }

            // Apply the status filter if provided (1 = Active, 0 = Expired)
            if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== null) {
                $status = (int)$filters['status'];
                
                // If status = 0 (expired), filter based on the year and/or month, if provided
                if ($status === 0 && (!empty($filters['year']) || !empty($filters['month']))) {
                    Log::info('Filter applied: expired status with year and/or month', ['filters' => $filters]);
                    $query->where('learner_detail.status', 0);
                    if (!empty($filters['year'])) {
                        $query->whereYear('learner_detail.plan_end_date', $filters['year']);
                    }
                    if (!empty($filters['month'])) {
                        $query->whereMonth('learner_detail.plan_end_date', $filters['month']);
                    }
                } else {
                    Log::info('Filter applied: regular status', ['status' => $status]);
                    $query->where('status', $status);
                }
            }

            // Search by Name, Seat No, Mobile, or Email
            if (!empty($filters['search'])) {
                $searchTerm = trim($filters['search']);
                $encryptTerm = function_exists('encryptData') ? encryptData($searchTerm) : $searchTerm;
                $query->where(function ($q) use ($searchTerm, $encryptTerm) {
                    $q->where('seat_no', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhereHas('learner', function ($lq) use ($searchTerm, $encryptTerm) {
                          $lq->where('name', 'LIKE', '%' . $searchTerm . '%')
                            ->orWhere('seat_no', 'LIKE', '%' . $searchTerm . '%')
                            ->orWhere('mobile', 'LIKE', '%' . $encryptTerm . '%')
                            ->orWhere('email', $encryptTerm);
                      });
                });
            }
        }
       
        return $query->get();
    }
    // public function paymentCollection(Request $request)
    // {
    //     $dates = getLearnerMonthsAndYears();
    //     $dynamicyears = $dates['years'];
    //     $dynamicmonths = $dates['months'];
       
    //     $year = $request->get('year');
    //     $month = $request->get('month');

    //     // Base query
    //     $query = LearnerTransaction::where('library_id', getLibraryId())
    //         ->where('paid_amount', '!=', 0)
    //         ->with('learner');

    //     // Apply filters based on request
    //     if ($year && $month) {
    //         $query->whereYear('paid_date', $year)->whereMonth('paid_date', $month);
    //     } elseif ($year) {
    //         $query->whereYear('paid_date', $year);
    //     } elseif ($month) {
    //         $query->whereMonth('paid_date', $month);
    //     }

    //     // Filter by branch
    //     if (getCurrentBranch() != 0 && getCurrentBranch() != null) {
    //         $query->where('branch_id', getCurrentBranch());
    //     }

    //     $learners = $query->get();

    //     return view('report.payment_collection', compact('dynamicyears', 'dynamicmonths', 'learners'));
    // }

    /** Collections — "how much money came in, from whom and how". */
    public function paymentCollection(Request $request)
    {
        $preset = $request->get('preset') ?: 'this_month';
        $payment_mode = $request->get('payment_mode');
        $today = Carbon::today();

        [$start, $end] = match ($preset) {
            'today' => [$today->copy(), $today->copy()],
            'yesterday' => [$today->copy()->subDay(), $today->copy()->subDay()],
            'this_week' => [$today->copy()->startOfWeek(), $today->copy()],
            'last_month' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            'last_90' => [$today->copy()->subDays(89), $today->copy()],
            'custom' => [
                $request->filled('start_date') ? Carbon::parse($request->start_date) : $today->copy()->startOfMonth(),
                $request->filled('end_date') ? Carbon::parse($request->end_date) : $today->copy(),
            ],
            'all' => [null, null],
            default => [$today->copy()->startOfMonth(), $today->copy()],
        };
        if ($start && $end && $start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $base = function () use ($payment_mode) {
            $q = LearnerTransaction::where('library_id', getLibraryId())->where('paid_amount', '!=', 0);
            if (getCurrentBranch() != 0 && getCurrentBranch() != null) {
                $q->where('branch_id', getCurrentBranch());
            }
            if (!empty($payment_mode)) {
                $codes = ['online' => ['1', 'online'], 'offline' => ['2', 'offline', 'cash'], 'paylater' => ['3', 'paylater', 'pay later']][$payment_mode] ?? [$payment_mode];
                $q->where(function ($m) use ($codes) {
                    $m->whereHas('learnerDetail', fn ($d) => $d->whereIn(DB::raw('LOWER(payment_mode)'), $codes))
                      ->orWhere(fn ($x) => $x->whereDoesntHave('learnerDetail')
                          ->whereHas('learner', fn ($l) => $l->whereIn(DB::raw('LOWER(payment_mode)'), $codes)));
                });
            }
            return $q;
        };

        $query = $base()->with(['learner', 'learnerDetail.plan', 'learnerDetail.planType'])
            ->orderBy('paid_date', 'desc')->orderBy('id', 'desc');
        if ($start && $end) {
            $query->whereBetween('paid_date', [$start->toDateString(), $end->toDateString()]);
        }
        $rows = $query->get();

        // same-length window just before, for the trend line
        $prevSum = null;
        if ($start && $end) {
            $days = $start->diffInDays($end) + 1;
            $prevSum = (float) $base()->whereBetween('paid_date', [
                $start->copy()->subDays($days)->toDateString(),
                $start->copy()->subDay()->toDateString(),
            ])->sum('paid_amount');
        }

        $modeOf = fn ($t) => rptMode(optional($t->learnerDetail)->payment_mode ?? optional($t->learner)->payment_mode ?? $t->payment_mode);
        $byMode = $rows->groupBy($modeOf)->map(fn ($g) => (float) $g->sum('paid_amount'));

        $collected = (float) $rows->sum('paid_amount');
        $metrics = [
            'collected' => $collected,
            'count' => $rows->count(),
            'payers' => $rows->pluck('learner_id')->unique()->count(),
            'avg' => $rows->count() ? $collected / $rows->count() : 0,
            'pending' => (float) $rows->sum('pending_amount'),
            'discount' => (float) $rows->sum('discount_amount'),
            'online' => $byMode['Online'] ?? 0,
            'offline' => $byMode['Offline'] ?? 0,
            'paylater' => $byMode['Pay later'] ?? 0,
            'prev' => $prevSum,
            'change' => $prevSum ? round(($collected - $prevSum) / $prevSum * 100) : null,
            'best_day' => $rows->groupBy(fn ($t) => (string) $t->paid_date)->map(fn ($g) => (float) $g->sum('paid_amount'))->sortDesc()->take(1),
        ];

        $data = compact('rows', 'metrics', 'preset', 'start', 'end', 'payment_mode') + ['modeOf' => $modeOf];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['html' => view('report._collections', $data)->render()]);
        }

        return view('report.payment_collection', $data);
    }


    /** Dues — balances members still owe, oldest first, with one-tap reminder / collect. */
    public function partialPaymentCollection(Request $request)
    {
        $due_status = $request->get('due_status') ?: 'pending';
        $today = Carbon::today();

        $query = LearnerTransaction::withoutGlobalScopes()
            ->with(['learner' => fn ($q) => $q->withoutGlobalScopes(), 'learnerDetail' => fn ($q) => $q->withoutGlobalScopes()->with(['plan', 'planType'])])
            ->where('library_id', getLibraryId())
            ->whereNull('deleted_at');
        if (getCurrentBranch() != 0 && getCurrentBranch() != null) {
            $query->where('branch_id', getCurrentBranch());
        }

        $all = (clone $query)->where('pending_amount', '>', 0)->get();
        $dueOf = fn ($t) => $t->due_date ? Carbon::parse($t->due_date) : ($t->paid_date ? Carbon::parse($t->paid_date) : null);

        $rows = match ($due_status) {
            'overdue' => $all->filter(fn ($t) => ($d = $dueOf($t)) && $d->lt($today)),
            'week' => $all->filter(fn ($t) => ($d = $dueOf($t)) && $d->gte($today) && $d->lte($today->copy()->addDays(7))),
            'cleared' => (clone $query)->whereNotNull('due_date')->where('pending_amount', '<=', 0)
                ->where('updated_at', '>=', $today->copy()->subDays(30))->orderByDesc('updated_at')->get(),
            default => $all,
        };
        if ($due_status !== 'cleared') {
            $rows = $rows->sortBy(fn ($t) => optional($dueOf($t))->timestamp ?? PHP_INT_MAX)->values();
        }

        $overdue = $all->filter(fn ($t) => ($d = $dueOf($t)) && $d->lt($today));
        $week = $all->filter(fn ($t) => ($d = $dueOf($t)) && $d->gte($today) && $d->lte($today->copy()->addDays(7)));
        $oldest = $overdue->sortBy(fn ($t) => $dueOf($t)->timestamp)->first();

        $metrics = [
            'outstanding' => (float) $all->sum('pending_amount'),
            'owing' => $all->pluck('learner_id')->unique()->count(),
            'overdue' => (float) $overdue->sum('pending_amount'),
            'overdue_count' => $overdue->count(),
            'week' => (float) $week->sum('pending_amount'),
            'week_count' => $week->count(),
            'counts' => ['pending' => $all->count(), 'overdue' => $overdue->count(), 'week' => $week->count()],
            'oldest' => $oldest,
            'oldest_days' => $oldest ? $dueOf($oldest)->diffInDays($today) : 0,
        ];

        $data = compact('rows', 'metrics', 'due_status', 'dueOf');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['html' => view('report._dues', $data)->render()]);
        }

        return view('report.partial_payment_collection', $data);
    }

  

    // The activity log lives on the Activities page; keep old links working.
    public function activity(Request $request)
    {
        return redirect()->route('activities.all', array_filter($request->only('learner_id')));
    }

    public function attendanceReport(Request $request)
    {
        $bindings = [getLibraryId()];
        $branchFilterSql = '';
        if (getCurrentBranch() != 0) {
            $branchFilterSql = ' AND branch_id = ?';
            $bindings[] = getCurrentBranch();
        }

        $data = DB::select("
            SELECT DISTINCT
                YEAR(date) as year,
                MONTH(date) as month
            FROM attendances
            WHERE library_id = ?
            $branchFilterSql
            ORDER BY year DESC, month ASC
        ", $bindings);

        $collection = collect($data);

        $dynamicyears = $collection->pluck('year')->unique()->values();
        $dynamicmonths = $collection->pluck('month')->unique()->values();

        // Fallback years and months if empty
        if ($dynamicyears->isEmpty()) {
            $currentY = (int) date('Y');
            $dynamicyears = collect([$currentY, $currentY - 1, $currentY - 2]);
        }
        if ($dynamicmonths->isEmpty()) {
            $dynamicmonths = collect(range(1, 12));
        }

        $year = (int) ($request->year ?? date('Y'));
        $month = (int) ($request->month ?? date('m'));
        $daymonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        // Build days metadata for matrix headers
        $daysMeta = [];
        for ($day = 1; $day <= $daymonth; $day++) {
            $dateCarbon = Carbon::createFromDate($year, $month, $day);
            $daysMeta[$day] = [
                'day' => $day,
                'weekday' => $dateCarbon->format('D'),
                'short_weekday' => substr($dateCarbon->format('D'), 0, 1),
                'is_sunday' => $dateCarbon->isSunday(),
                'is_future' => $dateCarbon->isFuture(),
                'is_today' => $dateCarbon->isToday(),
                'date_str' => $dateCarbon->format('Y-m-d'),
                'formatted' => $dateCarbon->format('d M Y'),
            ];
        }

        $learnersQuery = Learner::where('library_id', getLibraryId())->where('status', 1);
        $attendanceQuery = Attendance::where('library_id', getLibraryId())
            ->whereYear('date', $year)
            ->whereMonth('date', $month);

        if (getCurrentBranch() != 0) {
            $learnersQuery->where('branch_id', getCurrentBranch());
            $attendanceQuery->where('branch_id', getCurrentBranch());
        }

        // Optional search filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $learnersQuery->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('mobile', 'LIKE', "%{$search}%")
                  ->orWhere('seat_no', 'LIKE', "%{$search}%");
            });
        }

        $learners = $learnersQuery->orderBy('seat_no', 'asc')->get();

        $attendanceRecords = $attendanceQuery
            ->get()
            ->groupBy('learner_id');

        $totalPresentDays = 0;
        $totalAbsentDays = 0;
        $highAttendanceCount = 0;

        $learnerAttendance = $learners->map(function ($learner) use ($attendanceRecords, $month, $year, $daymonth, &$totalPresentDays, &$totalAbsentDays, &$highAttendanceCount) {
            $records = $attendanceRecords[$learner->id] ?? collect();

            $daily = [];
            $present = 0;
            $absent = 0;

            for ($day = 1; $day <= $daymonth; $day++) {
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);

                $record = $records->first(function ($r) use ($dateStr) {
                    return Carbon::parse($r->date)->format('Y-m-d') === $dateStr;
                });

                if ($record) {
                    if ($record->attendance == 1) {
                        $daily[$day] = 'P';
                        $present++;
                    } else {
                        $daily[$day] = 'A';
                        $absent++;
                    }
                } else {
                    $daily[$day] = '-';
                }
            }

            $totalPresentDays += $present;
            $totalAbsentDays += $absent;

            $recordedDays = $present + $absent;
            $rate = ($recordedDays > 0) ? round(($present / $recordedDays) * 100, 1) : 0;
            if ($rate >= 75) {
                $highAttendanceCount++;
            }

            // the Learner model's accessor already decrypts the mobile number
            $learnerMobile = (string) ($learner->mobile ?? '');

            $seatDisplay = $learner->seat_no ? getSeatDisplayByMainNo($learner->seat_no) : 'General';
            if (empty($seatDisplay)) {
                $seatDisplay = 'General';
            }

            return [
                'learner' => $learner,
                'learner_id' => $learner->id,
                'seat_no' => $learner->seat_no ?? null,
                'seat_display' => $seatDisplay,
                'email' => $learner->email ?? '-',
                'mobile' => $learnerMobile,
                'name' => $learner->name ?? ('Learner #' . $learner->id),
                'present' => $present,
                'absent' => $absent,
                'rate' => $rate,
                'daily' => $daily,
            ];
        });

        $totalLearners = $learners->count();
        $totalRecordedMarks = $totalPresentDays + $totalAbsentDays;
        $avgRate = ($totalRecordedMarks > 0) ? round(($totalPresentDays / $totalRecordedMarks) * 100, 1) : 0;

        $metrics = [
            'total_learners' => $totalLearners,
            'total_present' => $totalPresentDays,
            'total_absent' => $totalAbsentDays,
            'avg_rate' => $avgRate,
            'high_attendance_count' => $highAttendanceCount,
        ];

        $hasCustomFilter = ($request->filled('year') && $request->year != date('Y')) ||
                           ($request->filled('month') && $request->month != date('m')) ||
                           $request->filled('search');

        $monthStart = Carbon::create($year, $month, 1);
        // attendance is only recorded on visits, so rate = days present / days elapsed this month
        $elapsed = $monthStart->isSameMonth(Carbon::today()) ? (int) date('j') : ($monthStart->isFuture() ? 0 : $daymonth);
        $learnerAttendance = $learnerAttendance->map(function ($r) use ($elapsed) {
            $r['rate'] = $elapsed ? min(100, round($r['present'] / $elapsed * 100)) : 0;
            return $r;
        })->sortByDesc('present')->values();
        $metrics['elapsed'] = $elapsed;
        $metrics['avg_rate'] = $learnerAttendance->count() ? round($learnerAttendance->avg('rate')) : 0;
        $metrics['high_attendance_count'] = $learnerAttendance->filter(fn ($r) => $r['rate'] >= 75)->count();
        $todayIn = $monthStart->isSameMonth(Carbon::today())
            ? $learnerAttendance->filter(fn ($r) => ($r['daily'][(int) date('j')] ?? '-') === 'P')->count()
            : null;
        $metrics['today_in'] = $todayIn;
        $metrics['never'] = $learnerAttendance->filter(fn ($r) => $r['present'] === 0)->count();
        $metrics['low'] = $learnerAttendance->filter(fn ($r) => $r['present'] > 0 && $r['rate'] < 50)->count();
        $prevMonth = $monthStart->copy()->subMonth();
        $nextMonth = $monthStart->copy()->addMonth();
        $isCurrent = $monthStart->isSameMonth(Carbon::today());

        $attData = compact('learnerAttendance', 'daymonth', 'year', 'month', 'daysMeta', 'metrics', 'prevMonth', 'nextMonth', 'isCurrent', 'monthStart');

        if ($request->ajax()) {
            return response()->json(['html' => view('report._attendance', $attData)->render()]);
        }

        return view('report.monthly_attendance', $attData + compact('dynamicyears', 'dynamicmonths', 'hasCustomFilter'));
    }

    public function monthlyPaymentCollection(Request $request)
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');

        // If date range selected
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $fromDate = Carbon::parse($request->start_date)->startOfDay();
            $toDate   = Carbon::parse($request->end_date)->endOfDay();
        }
        // If month + year selected
        elseif ($request->filled('month') && $request->filled('year')) {
            $fromDate = Carbon::create($request->year, $request->month, 1)->startOfMonth();
            $toDate   = Carbon::create($request->year, $request->month, 1)->endOfMonth();
        }
        // Default: current month
        else {
            $fromDate = Carbon::now()->startOfMonth();
            $toDate   = Carbon::now()->endOfMonth();
        }

        // Fetch transactions
        $query = LearnerTransactionActivity::withoutGlobalScopes()
            ->leftJoin(
                'learners',
                'learners.id',
                '=',
                'learner_transaction_activity.learner_id'
            )
            ->whereBetween(
                'learner_transaction_activity.date',
                [$fromDate->toDateString(), $toDate->toDateString()]
            )
            ->select(
                'learner_transaction_activity.*',
                'learners.name',
                'learners.seat_no',
                'learners.mobile'
            )
            ->when(getCurrentBranch() != 0, function ($q) {
                $q->where('learner_transaction_activity.branch_id', getCurrentBranch());
            }, function ($q) {
                $q->whereIn('learner_transaction_activity.branch_id', Branch::where('library_id', getLibraryId())->pluck('id'));
            });

        // Filter by flow / type
        if ($request->filled('flow') && $request->flow !== 'all') {
            $flow = strtolower($request->flow);
            if ($flow === 'credit' || $flow === 'cr') {
                $query->where('learner_transaction_activity.dr_cr', 'Cr');
            } elseif ($flow === 'debit' || $flow === 'dr') {
                $query->where('learner_transaction_activity.dr_cr', 'Dr');
            } elseif ($flow === 'expense') {
                $query->where('learner_transaction_activity.payment_type', 'EXPENSE');
            } elseif ($flow === 'refund') {
                $query->where('learner_transaction_activity.payment_type', 'REFUND');
            }
        }

        // Filter by payment_mode
        if ($request->filled('payment_mode') && $request->payment_mode !== 'all') {
            $query->where('learner_transaction_activity.payment_mode', 'LIKE', '%' . $request->payment_mode . '%');
        }

        // Filter by search query
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('learners.name', 'LIKE', "%{$search}%")
                  ->orWhere('learners.mobile', 'LIKE', "%{$search}%")
                  ->orWhere('learners.seat_no', 'LIKE', "%{$search}%")
                  ->orWhere('learner_transaction_activity.particular', 'LIKE', "%{$search}%")
                  ->orWhere('learner_transaction_activity.payment_type', 'LIKE', "%{$search}%")
                  ->orWhere('learner_transaction_activity.transaction_id', 'LIKE', "%{$search}%");
            });
        }

        $transactions = $query->orderBy('learner_transaction_activity.date', 'desc')
            ->orderBy('learner_transaction_activity.id', 'desc')
            ->get();

        $groupedTransactions = $transactions->groupBy(function ($row) {
            return Carbon::parse($row->date)->format('Y-m-d');
        });

        $totalCr = $transactions->where('dr_cr', 'Cr')->sum('amount');
        $totalDr = $transactions->where('dr_cr', 'Dr')->sum('amount');
        $grandBalance = $totalCr - $totalDr;

        $totalCollection = $transactions
            ->where('dr_cr', 'Cr')
            ->sum('amount');

        $totalExpense = $transactions
            ->where('dr_cr', 'Dr')
            ->where('payment_type', 'EXPENSE')
            ->sum('amount');

        $totalRevenue = $transactions
            ->where('dr_cr', 'Dr')
            ->where('payment_type', 'REFUND')
            ->sum('amount');

        $grandTotal = $totalCollection - $totalExpense - $totalRevenue;

        // Dynamic years & months for dropdown
        $minDate = LearnerTransactionActivity::withoutGlobalScopes()
            ->when(getCurrentBranch() != 0, function ($q) {
                $q->where('branch_id', getCurrentBranch());
            }, function ($q) {
                $q->whereIn('branch_id', Branch::where('library_id', getLibraryId())->pluck('id'));
            })
            ->min('date');

        $startYear = $minDate ? Carbon::parse($minDate)->year : Carbon::now()->subYears(2)->year;
        $endYear = Carbon::now()->year;
        $dynamicyears = range($endYear, max($startYear, $endYear - 3));
        $dynamicmonths = range(1, 12);

        $months = [];
        foreach ($dynamicyears as $y) {
            foreach ($dynamicmonths as $m) {
                $months[$y][$m] = Carbon::create($y, $m, 1)->format('F');
            }
        }

        $hasCustomFilter = $request->filled('start_date') || 
                           $request->filled('end_date') || 
                           ($request->filled('month') && $request->month != $currentMonth) || 
                           ($request->filled('year') && $request->year != $currentYear) ||
                           $request->filled('flow') || 
                           $request->filled('payment_mode') || 
                           $request->filled('search');

        $cashCollection = $transactions->where('dr_cr', 'Cr')->filter(function ($r) {
            $m = strtolower(trim((string)($r->payment_mode ?? '')));
            return str_contains($m, 'cash') || str_contains($m, 'offline');
        })->sum('amount');

        $onlineCollection = $transactions->where('dr_cr', 'Cr')->filter(function ($r) {
            $m = strtolower(trim((string)($r->payment_mode ?? '')));
            return !str_contains($m, 'cash') && !str_contains($m, 'offline');
        })->sum('amount');

        $inflowCount = $transactions->where('dr_cr', 'Cr')->count();
        $expenseCount = $transactions->where('dr_cr', 'Dr')->where('payment_type', 'EXPENSE')->count();
        $refundCount = $transactions->where('dr_cr', 'Dr')->where('payment_type', 'REFUND')->count();
        $cashCount = $transactions->filter(function ($r) {
            $m = strtolower(trim((string)($r->payment_mode ?? '')));
            return str_contains($m, 'cash') || str_contains($m, 'offline');
        })->count();
        $onlineCount = $transactions->filter(function ($r) {
            $m = strtolower(trim((string)($r->payment_mode ?? '')));
            return !str_contains($m, 'cash') && !str_contains($m, 'offline');
        })->count();

        $selectedYear = $request->filled('year') ? (int) $request->year : $fromDate->year;
        $selectedMonth = $request->filled('month') ? (int) $request->month : $fromDate->month;

        $prevMonthDate = (clone $fromDate)->subMonth();
        $nextMonthDate = (clone $fromDate)->addMonth();
        $isCurrentMonth = ($fromDate->format('Y-m') === Carbon::now()->format('Y-m'));
        $isFutureMonth = ($fromDate->greaterThan(Carbon::now()->endOfMonth()));

        $metrics = [
            'totalCollection'  => $totalCollection,
            'totalExpense'     => $totalExpense,
            'totalRevenue'     => $totalRevenue,
            'grandTotal'       => $grandTotal,
            'totalCr'          => $totalCr,
            'totalDr'          => $totalDr,
            'grandBalance'     => $grandBalance,
            'cashCollection'   => $cashCollection,
            'onlineCollection' => $onlineCollection,
            'inflowCount'      => $inflowCount,
            'expenseCount'     => $expenseCount,
            'refundCount'      => $refundCount,
            'cashCount'        => $cashCount,
            'onlineCount'      => $onlineCount,
            'count'            => $transactions->count(),
            'activeDays'       => $groupedTransactions->count(),
            'periodLabel'      => $fromDate->format('F Y'),
        ];

        $cashData = compact(
            'groupedTransactions', 'totalCr', 'totalDr', 'totalCollection', 'totalExpense', 'totalRevenue', 'grandTotal',
            'cashCollection', 'onlineCollection', 'inflowCount', 'expenseCount', 'refundCount', 'cashCount', 'onlineCount',
            'fromDate', 'toDate', 'metrics', 'prevMonthDate', 'nextMonthDate', 'isCurrentMonth', 'isFutureMonth', 'hasCustomFilter'
        );

        if ($request->ajax()) {
            return response()->json(['html' => view('report._cashbook', $cashData)->render()]);
        }

        return view('report.monthly_payment_collection', compact(
            'groupedTransactions',
            'totalCr',
            'totalDr',
            'totalCollection',
            'totalExpense',
            'totalRevenue',
            'grandTotal',
            'cashCollection',
            'onlineCollection',
            'inflowCount',
            'expenseCount',
            'refundCount',
            'cashCount',
            'onlineCount',
            'months',
            'dynamicyears',
            'dynamicmonths',
            'hasCustomFilter',
            'fromDate',
            'toDate',
            'metrics',
            'selectedYear',
            'selectedMonth',
            'prevMonthDate',
            'nextMonthDate',
            'isCurrentMonth',
            'isFutureMonth'
        ));
    }

    public function exportMonthlyPayment(Request $request)
    {
        // Filter logic
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $fromDate = Carbon::parse($request->start_date)->startOfDay();
            $toDate   = Carbon::parse($request->end_date)->endOfDay();
        } elseif ($request->filled('month') && $request->filled('year')) {
            $fromDate = Carbon::create($request->year, $request->month, 1)->startOfMonth();
            $toDate   = Carbon::create($request->year, $request->month, 1)->endOfMonth();
        } else {
            $fromDate = Carbon::now()->startOfMonth();
            $toDate   = Carbon::now()->endOfMonth();
        }

        $query = LearnerTransactionActivity::withoutGlobalScopes()
            ->leftJoin('learners', 'learners.id', '=', 'learner_transaction_activity.learner_id')
            ->whereBetween(
                'learner_transaction_activity.date',
                [$fromDate->toDateString(), $toDate->toDateString()]
            )
            ->when(getCurrentBranch() != 0, function ($q) {
                $q->where('learner_transaction_activity.branch_id', getCurrentBranch());
            }, function ($q) {
                $q->whereIn('learner_transaction_activity.branch_id', Branch::where('library_id', getLibraryId())->pluck('id'));
            })
            ->select(
                'learner_transaction_activity.*',
                'learners.name',
                'learners.seat_no',
                'learners.mobile'
            );

        // Filter by flow / type
        if ($request->filled('flow') && $request->flow !== 'all') {
            $flow = strtolower($request->flow);
            if ($flow === 'credit' || $flow === 'cr') {
                $query->where('learner_transaction_activity.dr_cr', 'Cr');
            } elseif ($flow === 'debit' || $flow === 'dr') {
                $query->where('learner_transaction_activity.dr_cr', 'Dr');
            } elseif ($flow === 'expense') {
                $query->where('learner_transaction_activity.payment_type', 'EXPENSE');
            } elseif ($flow === 'refund') {
                $query->where('learner_transaction_activity.payment_type', 'REFUND');
            }
        }

        // Filter by payment_mode
        if ($request->filled('payment_mode') && $request->payment_mode !== 'all') {
            $query->where('learner_transaction_activity.payment_mode', 'LIKE', '%' . $request->payment_mode . '%');
        }

        // Filter by search query
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('learners.name', 'LIKE', "%{$search}%")
                  ->orWhere('learners.mobile', 'LIKE', "%{$search}%")
                  ->orWhere('learners.seat_no', 'LIKE', "%{$search}%")
                  ->orWhere('learner_transaction_activity.particular', 'LIKE', "%{$search}%")
                  ->orWhere('learner_transaction_activity.payment_type', 'LIKE', "%{$search}%")
                  ->orWhere('learner_transaction_activity.transaction_id', 'LIKE', "%{$search}%");
            });
        }

        $transactions = $query->orderBy('learner_transaction_activity.date', 'desc')
            ->orderBy('learner_transaction_activity.id', 'desc')
            ->get();

        // Group by date
        $grouped = $transactions->groupBy(function ($row) {
            return Carbon::parse($row->date)->format('Y-m-d');
        });

        // Totals
        $totalCollection = $transactions->where('dr_cr', 'Cr')->sum('amount');

        $totalExpense = $transactions
            ->where('dr_cr', 'Dr')
            ->where('payment_type', 'EXPENSE')
            ->sum('amount');

        $totalRevenue = $transactions
            ->where('dr_cr', 'Dr')
            ->where('payment_type', 'REFUND')
            ->sum('amount');

        $grandTotal = $totalCollection - $totalExpense - $totalRevenue;

        $fileName = 'monthly_payment_report_' . date('Y_m_d_His') . '.csv';

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate",
            "Expires" => "0"
        ];

        $callback = function () use (
            $grouped,
            $totalCollection,
            $totalExpense,
            $totalRevenue,
            $grandTotal
        ) {
            $handle = fopen('php://output', 'w');

            // Header
            fputcsv($handle, [
                'Date',
                'Seat No',
                'Learner / Description',
                'Particulars',
                'Flow',
                'Amount',
                'Payment Mode',
                'Operator',
                'Day Net Balance'
            ]);

            foreach ($grouped as $date => $rows) {
                $dayCr = $rows->where('dr_cr', 'Cr')->sum('amount');
                $dayDr = $rows->where('dr_cr', 'Dr')->sum('amount');
                $dayBalance = $dayCr - $dayDr;

                foreach ($rows as $index => $row) {
                    $isExpense = ($row->payment_type == 'EXPENSE');
                    $isRefund = ($row->payment_type == 'REFUND');
                    $displayName = $isExpense ? ($row->particular ?: 'EXPENSE') : ($row->name ?: 'Learner #' . $row->learner_id);
                    $particular = $row->particular ?: ($isExpense ? 'Operational Expense' : ($isRefund ? 'Fee Refund' : 'Learner Fee'));
                    $type = $row->dr_cr == 'Cr' ? 'CREDIT' : 'DEBIT';
                    $amount = ($row->dr_cr == 'Cr' ? '+' : '-') . $row->amount;
                    $seat = $isExpense ? 'EXPENSE' : ($row->seat_no ?? 'GEN');
                    $operator = $row->created_by ? (DB::table('library_users')->where('id', $row->created_by)->value('name') ?? $row->created_by) : 'Admin';

                    fputcsv($handle, [
                        $index == 0 ? Carbon::parse($date)->format('d-m-Y') : '',
                        $seat,
                        $displayName,
                        $particular,
                        $type,
                        $amount,
                        $row->payment_mode,
                        $operator,
                        $index == 0 ? $dayBalance : ''
                    ]);
                }
            }

            // Empty row & summary rows
            fputcsv($handle, []);
            fputcsv($handle, ['', '', '', '', 'Total Collections (Inflow)', '+' . $totalCollection]);
            fputcsv($handle, ['', '', '', '', 'Total Expenses (Outflow)', '-' . $totalExpense]);
            fputcsv($handle, ['', '', '', '', 'Total Refunds', '-' . $totalRevenue]);
            fputcsv($handle, ['', '', '', '', 'Net Closing Total', ($grandTotal >= 0 ? '+' : '-') . abs($grandTotal)]);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /* =====================================================================
       Reports overview, seat occupancy and growth (new)
       ===================================================================== */

    private function reportBranchIds(): array
    {
        return getCurrentBranch() != 0
            ? [(int) getCurrentBranch()]
            : Branch::where('library_id', getLibraryId())->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /** Seat-level picture for one branch: booked / partly free / empty, per shift and lost revenue. */
    private function occupancyData(int $branchId): array
    {
        $hour = Hour::withoutGlobalScopes()->where('branch_id', $branchId)->first();
        $totalSeats = (int) optional($hour)->seats;
        $shifts = PlanType::withoutGlobalScopes()->whereNull('deleted_at')->where('branch_id', $branchId)
            ->with('price')->get();

        if ($totalSeats < 1) {
            return ['totalSeats' => 0, 'shifts' => collect(), 'seats' => collect(), 'full' => 0, 'partial' => 0, 'empty' => 0, 'potential' => 0, 'pct' => 0, 'active' => 0];
        }

        $map = app(SeatAvailabilityService::class)->getAvailablePlanTypesMap($branchId, $totalSeats, optional($hour)->hour);
        $extend = (int) (Branch::withoutGlobalScopes()->whereKey($branchId)->value('extend_days') ?? 0);
        $today = Carbon::today()->toDateString();

        $active = LearnerDetail::withoutGlobalScopes()
            ->join('learners', 'learners.id', '=', 'learner_detail.learner_id')
            ->where('learner_detail.branch_id', $branchId)
            ->where('learner_detail.status', 1)->where('learners.status', 1)
            ->whereNull('learner_detail.deleted_at')->whereNull('learners.deleted_at')
            ->where(fn ($q) => $q->where('learners.no_expiry', 1)->orWhereRaw('DATE_ADD(learner_detail.plan_end_date, INTERVAL ? DAY) >= ?', [$extend, $today]))
            ->select('learner_detail.seat_no', 'learner_detail.plan_type_id')->get();
        $bookedSeats = $active->pluck('seat_no')->filter()->map(fn ($v) => (int) $v)->unique()->flip();

        $price = fn ($t) => (float) optional($t->price)->price;
        $seats = collect(range(1, $totalSeats))->map(function ($n) use ($map, $bookedSeats, $price) {
            $avail = $map[$n] ?? collect();
            $state = $avail->isEmpty() ? 'full' : ($bookedSeats->has($n) ? 'partial' : 'empty');
            return ['no' => $n, 'state' => $state, 'free' => $avail, 'best' => (float) $avail->max(fn ($t) => $price($t))];
        });

        $shifts = $shifts->map(function ($t) use ($seats, $totalSeats, $price) {
            $free = $seats->filter(fn ($s) => $s['free']->contains('id', $t->id))->count();
            return [
                'id' => $t->id,
                'name' => $t->name,
                'start' => $t->start_time,
                'end' => $t->end_time,
                'price' => $price($t),
                'free' => $free,
                'booked' => $totalSeats - $free,
                'pct' => (int) round(($totalSeats - $free) / $totalSeats * 100),
            ];
        })->sortByDesc('pct')->values();

        $full = $seats->where('state', 'full')->count();
        $partial = $seats->where('state', 'partial')->count();
        $empty = $seats->where('state', 'empty')->count();

        return [
            'totalSeats' => $totalSeats,
            'shifts' => $shifts,
            'seats' => $seats,
            'full' => $full,
            'partial' => $partial,
            'empty' => $empty,
            'pct' => (int) round(($full + $partial * 0.5) / $totalSeats * 100),
            'potential' => (float) $seats->where('state', 'empty')->sum('best'),
            'active' => $active->count(),
        ];
    }

    /** Seat occupancy — how full is the library, which shifts sell, how much is left on the table. */
    public function occupancy(Request $request)
    {
        $branchId = (int) (getCurrentBranch() ?: Branch::where('library_id', getLibraryId())->value('id'));
        $occ = $this->occupancyData($branchId);
        $branchName = getCurrentBranchName();

        return view('report.occupancy', compact('occ', 'branchName'));
    }

    /** Growth & retention — joins, renewals and leavers per month, renewal rate. */
    public function growth(Request $request)
    {
        $span = (int) ($request->get('span') ?: 12);
        $span = in_array($span, [6, 12, 24], true) ? $span : 12;
        $branchIds = $this->reportBranchIds();
        $today = Carbon::today();
        $from = $today->copy()->startOfMonth()->subMonths($span - 1);

        $details = LearnerDetail::withoutGlobalScopes()
            ->whereIn('branch_id', $branchIds)->whereNull('deleted_at')
            ->select('id', 'learner_id', 'plan_start_date', 'plan_end_date', 'plan_price_id')
            ->orderBy('plan_start_date')->get()->groupBy('learner_id');

        $months = [];
        for ($c = $from->copy(); $c->lte($today); $c->addMonth()) {
            $months[$c->format('Y-m')] = ['date' => $c->copy(), 'new' => 0, 'renew' => 0, 'ended' => 0, 'renewed_of_ended' => 0, 'lost' => 0, 'active' => 0];
        }

        foreach ($details as $rows) {
            $rows = $rows->values();
            foreach ($rows as $i => $d) {
                if (!$d->plan_start_date) continue;
                $s = Carbon::parse($d->plan_start_date);
                $e = $d->plan_end_date ? Carbon::parse($d->plan_end_date) : null;
                $key = $s->format('Y-m');
                if (isset($months[$key])) {
                    $months[$key][$i === 0 ? 'new' : 'renew']++;
                }
                if ($e && $e->lt($today)) {
                    $ek = $e->format('Y-m');
                    if (isset($months[$ek])) {
                        $months[$ek]['ended']++;
                        $next = $rows->get($i + 1);
                        if ($next) {
                            $months[$ek]['renewed_of_ended']++;
                        } else {
                            $months[$ek]['lost']++;
                        }
                    }
                }
            }
        }
        // members holding a plan on the last day of each month (or today)
        foreach ($months as $k => &$m) {
            $at = $m['date']->copy()->endOfMonth()->min($today)->toDateString();
            $m['active'] = $details->filter(fn ($rows) => $rows->contains(fn ($d) => $d->plan_start_date <= $at && $d->plan_end_date >= $at))->count();
            $m['rate'] = $m['ended'] ? (int) round($m['renewed_of_ended'] / $m['ended'] * 100) : null;
        }
        unset($m);
        $months = collect($months)->values();
        $firstActive = $months->search(fn ($m) => $m['new'] || $m['renew'] || $m['ended'] || $m['active']);
        if ($firstActive !== false && $firstActive > 0) {
            $months = $months->slice(min($firstActive, $months->count() - 3))->values();
        }

        $tot = [
            'new' => $months->sum('new'),
            'renew' => $months->sum('renew'),
            'lost' => $months->sum('lost'),
            'ended' => $months->sum('ended'),
        ];
        $tot['rate'] = $tot['ended'] ? (int) round(($tot['ended'] - $tot['lost']) / $tot['ended'] * 100) : null;
        $first = $months->first();
        $last = $months->last();
        $tot['net'] = $last['active'] - $first['active'];

        // how long members stay (completed members only)
        $tenures = $details->map(function ($rows) {
            $s = Carbon::parse($rows->first()->plan_start_date);
            $e = Carbon::parse($rows->last()->plan_end_date);
            return max(1, (int) round($s->diffInDays($e) / 30));
        });
        $tot['avg_months'] = $tenures->count() ? round($tenures->avg(), 1) : 0;
        $tot['ltv'] = $details->count() ? (float) $details->sum(fn ($rows) => $rows->sum('plan_price_id')) / $details->count() : 0;

        $data = compact('months', 'tot', 'span');

        if ($request->ajax()) {
            return response()->json(['html' => view('report._growth', $data)->render()]);
        }

        return view('report.growth', $data);
    }

    /** Reports overview — today's numbers, what needs attention and every report in one place. */
    public function hub(Request $request)
    {
        $branchIds = $this->reportBranchIds();
        $today = Carbon::today();
        $mStart = $today->copy()->startOfMonth();
        $lmStart = $mStart->copy()->subMonthNoOverflow();
        $lmSameDay = $lmStart->copy()->addDays($today->day - 1)->min($lmStart->copy()->endOfMonth());

        $ledger = fn () => LearnerTransactionActivity::withoutGlobalScopes()->whereNull('deleted_at')->whereIn('branch_id', $branchIds);

        $in = (float) $ledger()->where('dr_cr', 'Cr')->whereBetween('date', [$mStart->toDateString(), $today->toDateString()])->sum('amount');
        $inLast = (float) $ledger()->where('dr_cr', 'Cr')->whereBetween('date', [$lmStart->toDateString(), $lmSameDay->toDateString()])->sum('amount');
        $out = (float) $ledger()->where('dr_cr', 'Dr')->whereBetween('date', [$mStart->toDateString(), $today->toDateString()])->sum('amount');
        $todayIn = (float) $ledger()->where('dr_cr', 'Cr')->whereDate('date', $today)->sum('amount');

        $dues = LearnerTransaction::withoutGlobalScopes()->whereNull('deleted_at')->whereIn('branch_id', $branchIds)->where('pending_amount', '>', 0);
        $duesSum = (float) (clone $dues)->sum('pending_amount');
        $duesCount = (clone $dues)->distinct('learner_id')->count('learner_id');

        $expiring = LearnerDetail::withoutGlobalScopes()->whereNull('deleted_at')->whereIn('branch_id', $branchIds)
            ->where('status', 1)->whereBetween('plan_end_date', [$today->toDateString(), $today->copy()->addDays(7)->toDateString()])
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('learner_detail as ld2')
                ->whereColumn('ld2.learner_id', 'learner_detail.learner_id')
                ->where('ld2.plan_end_date', '>', $today->copy()->addDays(7)->toDateString()));
        $expCount = (clone $expiring)->count();
        $expValue = (float) (clone $expiring)->sum('plan_price_id');

        $activeMembers = Learner::withoutGlobalScopes()->whereNull('deleted_at')->whereIn('branch_id', $branchIds)->where('status', 1)->count();
        $presentToday = Attendance::whereIn('branch_id', $branchIds)->whereDate('date', $today)->where('attendance', 1)->distinct('learner_id')->count('learner_id');

        $occ = count($branchIds) === 1 ? $this->occupancyData($branchIds[0]) : null;

        $newThisMonth = LearnerDetail::withoutGlobalScopes()->whereNull('deleted_at')->whereIn('branch_id', $branchIds)
            ->whereBetween('plan_start_date', [$mStart->toDateString(), $today->toDateString()])
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('learner_detail as ld0')
                ->whereColumn('ld0.learner_id', 'learner_detail.learner_id')
                ->whereColumn('ld0.plan_start_date', '<', 'learner_detail.plan_start_date'))
            ->count();

        $k = [
            'in' => $in,
            'in_change' => $inLast > 0 ? (int) round(($in - $inLast) / $inLast * 100) : null,
            'out' => $out,
            'net' => $in - $out,
            'today_in' => $todayIn,
            'dues' => $duesSum,
            'dues_count' => $duesCount,
            'exp_count' => $expCount,
            'exp_value' => $expValue,
            'active' => $activeMembers,
            'present' => $presentToday,
            'new' => $newThisMonth,
            'occ' => $occ,
        ];

        return view('report.hub', compact('k'));
    }
}
