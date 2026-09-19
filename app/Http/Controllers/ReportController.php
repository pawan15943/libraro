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
    public function monthlyReport()
    {
       $query = LearnerDetail::withoutGlobalScopes()
        ->leftJoin('learner_transactions', 'learner_detail.id', '=', 'learner_transactions.learner_detail_id')
        ->leftJoin('plans', 'plans.id', '=', 'learner_detail.plan_id')
        ->where('learner_detail.is_paid', 1)
        ->where('learner_detail.library_id',getLibraryId())
        ->selectRaw('
            YEAR(join_date) as year,
            MONTH(join_date) as month,
            SUM(paid_amount / plans.plan_id) as monthly_revenue
        ')
        ->groupBy('year', 'month');

      if (getCurrentBranch() != 0) {
            $query->where('learner_detail.branch_id', getCurrentBranch());
        }
    \Log::info($query->toSql(), $query->getBindings());
    $monthlyRevenues = $query->get();
       

    // Initialize an array to hold the final report data
    $reportData = [];
   
    if ($monthlyRevenues->isNotEmpty()) {
      
   
        foreach ($monthlyRevenues as $monthlyRevenue) {
           $totalsQuery = LearnerTransactionActivity::selectRaw("
                SUM(CASE WHEN dr_cr = 'Cr' THEN amount ELSE 0 END) as total_cr,
                SUM(CASE WHEN dr_cr = 'Dr' THEN amount ELSE 0 END) as total_dr
            ")
            ->whereYear('date', $monthlyRevenue->year)->whereMonth('date', $monthlyRevenue->month);

            if (getCurrentBranch() != 0) {
                $totalsQuery->where('branch_id', getCurrentBranch());
            } else {
                $totalsQuery->whereIn('branch_id', Branch::where('library_id', getLibraryId())->pluck('id'));
            }

            $totals_monthly = $totalsQuery->first();


            // Prepare the report data
            $reportData[] = [
                'year' => $monthlyRevenue->year,
                'month' => $monthlyRevenue->month,
                'total_revenue' => $totals_monthly->total_cr,
                'total_expenses' => $totals_monthly->total_dr ?? 0, 
                'monthly_revenue' => $monthlyRevenue->monthly_revenue, 
                
            ];
        }

     } else {
          $reportData[] = [
                'year' =>date("Y"),
                'month' => date("m"),
                'total_revenue' => null,
                'total_expenses' =>  0, 
                'monthly_revenue' => 0, 
                
            ];
    }


        return view('report.monthly_report', ['reportData' => $reportData]);
    }


    public function monthlyExpenseCreate($year, $month)
    {
        $monthlyExpenses = DB::table('monthly_expense')->leftJoin('expenses','monthly_expense.expense_id','=','expenses.id')->where('monthly_expense.library_id',getLibraryId())
            ->where('monthly_expense.year',  $year)
            ->where('monthly_expense.month', $month)
            ->get();
        $library_revenue_query = LearnerDetail::withoutGlobalScopes()
        ->leftJoin('plans', 'plans.id', '=', 'learner_detail.plan_id')
        ->where('learner_detail.is_paid', 1)
        ->where('learner_detail.library_id',getLibraryId())
        ->whereYear('join_date', $year)
        ->whereMonth('join_date',$month)
        ->selectRaw('
            YEAR(join_date) as year,
            MONTH(join_date) as month,
            SUM(plan_price_id) as total_revenue,
            SUM(plan_price_id / plans.plan_id) as monthly_revenue
        ')
        ->groupBy('year', 'month');

        if (getCurrentBranch() != 0) {
            $library_revenue_query->where('learner_detail.branch_id', getCurrentBranch());
        }

        $library_revenue = $library_revenue_query->first();
           
     
        $expenses = Expense::get();
        $revenue_expense = DB::table('monthly_expense')
            ->join('expenses', 'monthly_expense.expense_id', '=', 'expenses.id')
            ->where('monthly_expense.library_id', getLibraryId()) // Optimized to use getLibraryId()
           
            ->select('monthly_expense.*', 'expenses.name as expense_name')
            ->get();

        return view('report.expense', compact('library_revenue', 'expenses', 'monthlyExpenses', 'year', 'month', 'revenue_expense'));
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

        if ($request->ajax()) {
            return response()->json([
                'html' => view('report.partials.pending_payment_table', [
                    'learners' => $learners,
                    'extendDay' => $extend_day,
                ])->render(),
                'metrics' => $metrics,
                'total_count' => count($learners),
            ]);
        }

        $extendDay = $extend_day;
        return view('report.pending_payment', compact('plans', 'planTypes', 'dynamicyears', 'dynamicmonths', 'learners', 'metrics', 'extendDay', 'filters'));
    }

   
    
    public function learnerReport(Request $request){
        $filters = [
            'year' => $request->get('year'),
            'month' => $request->get('month'),
            'is_paid' => $request->get('is_paid'),
            'status'  => $request->get('status'),
            'search'  => $request->get('search'),
        ];
       
        $query = LearnerDetail::where('library_id', getLibraryId())
            ->with(['plan', 'planType', 'learner', 'transaction'])
            ->orderBy('id', 'desc');

        if (getCurrentBranch() != 0) {
            $query->where('branch_id', getCurrentBranch());
        }
         
        $learners = $this->fetchlearnerData($filters, $query);

        // Get the unique years and month
        $rangeQuery = LearnerDetail::where('library_id', getLibraryId());
        if (getCurrentBranch() != 0) {
            $rangeQuery->where('branch_id', getCurrentBranch());
        }
        $minStartDate = (clone $rangeQuery)->min('plan_start_date');
        $maxEndDate = (clone $rangeQuery)->max('plan_end_date');
    
        $start = $minStartDate ? Carbon::parse($minStartDate)->startOfMonth() : Carbon::now()->startOfYear();
        $end = $maxEndDate ? Carbon::parse($maxEndDate)->startOfMonth() : Carbon::now()->endOfYear();
    
        $months = [];
        while ($start <= $end) {
            $year = $start->year;
            $month = $start->format('F');
            $months[$year][$start->month] = $month;
            $start->addMonth();
        }

        // Daily & Monthly Financial Intelligence
        $todayDate = Carbon::today()->toDateString();
        $currYear = Carbon::today()->year;
        $currMonth = Carbon::today()->month;

        // 1. Today's Collection
        $todayTxQuery = LearnerTransaction::where('library_id', getLibraryId())
            ->whereDate('paid_date', $todayDate);
        if (getCurrentBranch() != 0) {
            $todayTxQuery->where('branch_id', getCurrentBranch());
        }
        $todayCollection = (float) $todayTxQuery->sum('paid_amount');
        $todayCount = $todayTxQuery->count();

        // 2. Selected Month / Current Month Collection
        $filterYear = !empty($filters['year']) ? $filters['year'] : $currYear;
        $filterMonth = !empty($filters['month']) ? $filters['month'] : $currMonth;

        $monthTxQuery = LearnerTransaction::where('library_id', getLibraryId())
            ->whereYear('paid_date', $filterYear)
            ->whereMonth('paid_date', $filterMonth);
        if (getCurrentBranch() != 0) {
            $monthTxQuery->where('branch_id', getCurrentBranch());
        }
        $monthlyCollection = (float) $monthTxQuery->sum('paid_amount');
        $monthlyCount = $monthTxQuery->count();

        $monthlyOnline = (float) (clone $monthTxQuery)->where(function($modeQ) {
            $modeQ->whereHas('learnerDetail', function($q) {
                $q->where('payment_mode', '1')
                  ->orWhere('payment_mode', 1)
                  ->orWhereRaw('LOWER(payment_mode) = ?', ['online']);
            })->orWhereHas('learner', function($q) {
                $q->where('payment_mode', '1')
                  ->orWhere('payment_mode', 1)
                  ->orWhereRaw('LOWER(payment_mode) = ?', ['online']);
            });
        })->sum('paid_amount');

        $monthlyOffline = (float) (clone $monthTxQuery)->where(function($modeQ) {
            $modeQ->whereHas('learnerDetail', function($q) {
                $q->where('payment_mode', '2')
                  ->orWhere('payment_mode', 2)
                  ->orWhereRaw('LOWER(payment_mode) = ?', ['offline']);
            })->orWhereHas('learner', function($q) {
                $q->where('payment_mode', '2')
                  ->orWhere('payment_mode', 2)
                  ->orWhereRaw('LOWER(payment_mode) = ?', ['offline']);
            });
        })->sum('paid_amount');

        // 3. Pending Dues
        $pendingTxQuery = LearnerTransaction::where('library_id', getLibraryId())
            ->where('pending_amount', '>', 0);
        if (getCurrentBranch() != 0) {
            $pendingTxQuery->where('branch_id', getCurrentBranch());
        }
        $totalPendingDue = (float) $pendingTxQuery->sum('pending_amount');
        $pendingDuesCount = $pendingTxQuery->count();

        // 4. Status counts from loaded learners
        $active_count = 0;
        $expired_count = 0;
        $paid_count = 0;
        $unpaid_count = 0;

        foreach ($learners as $val) {
            if ($val->status == 1) {
                $active_count++;
            } else {
                $expired_count++;
            }
            if ($val->is_paid == 1) {
                $paid_count++;
            } else {
                $unpaid_count++;
            }
        }

        $metrics = [
            'total_learners' => count($learners),
            'active_count' => $active_count,
            'expired_count' => $expired_count,
            'paid_count' => $paid_count,
            'unpaid_count' => $unpaid_count,
            'today_collection' => $todayCollection,
            'today_count' => $todayCount,
            'monthly_collection' => $monthlyCollection,
            'monthly_count' => $monthlyCount,
            'monthly_online' => $monthlyOnline,
            'monthly_offline' => $monthlyOffline,
            'total_pending_due' => $totalPendingDue,
            'pending_dues_count' => $pendingDuesCount,
            'filter_year' => $filterYear,
            'filter_month' => $filterMonth,
            'filter_month_name' => Carbon::create()->month((int)$filterMonth)->format('F'),
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('report.partials.learner_report_table', compact('learners'))->render(),
                'metrics' => $metrics,
                'total_count' => count($learners),
            ]);
        }

        return view('report.learner_report', compact('learners', 'months', 'metrics', 'filters'));
    }

    public function upcomingPayment(Request $request){
        $days = (int)($request->get('days') ?: 5);
        if ($days < 1) $days = 5;
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

        $metrics = [
            'total_upcoming' => count($learners),
            'expiring_today' => $expiring_today,
            'expiring_tomorrow' => $expiring_tomorrow,
            'expiring_later' => $expiring_later,
        ];

        $filters = [
            'days' => $days,
            'plan_id' => $plan_id,
            'search' => $search,
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('report.partials.upcoming_payment_table', compact('learners'))->render(),
                'metrics' => $metrics,
                'total_count' => count($learners),
            ]);
        }

        return view('report.upcoming_payment', compact('learners', 'metrics', 'plans', 'filters'));
    }

    public function expiredLearner(Request $request){
       
        $dates = getLearnerMonthsAndYears();
        $dynamicyears =$dates['years'];
        $dynamicmonths = $dates['months'];
        $filters = [
            'expiredyear' => $request->get('expiredyear'),
            'expiredmonth' => $request->get('expiredmonth'),
            'search' => $request->get('search'),
        ];
        $query = LearnerDetail::where('library_id',getLibraryId())->where('plan_start_date', '<=', date('Y-m-d'))->with(['plan', 'planType','learner'])->where('status', 0)
        ->whereHas('learner', function($query) {
            $query->where('status', 0);
        });

        if(getCurrentBranch() !=0 ){
            $query->where('branch_id',getCurrentBranch());
        }
       
        $learners = $this->fetchlearnerData( $filters,$query);

        $now = Carbon::now();
        $thisMonthStr = $now->format('Y-m');
        $thisYearStr = $now->format('Y');
        $expired_this_month = 0;
        $expired_this_year = 0;
        $recent_expired_90d = 0;

        foreach ($learners as $val) {
            $endDate = Carbon::parse($val->plan_end_date);
            if ($endDate->format('Y-m') === $thisMonthStr) {
                $expired_this_month++;
            }
            if ($endDate->format('Y') === $thisYearStr) {
                $expired_this_year++;
            }
            if ($endDate->diffInDays($now) <= 90) {
                $recent_expired_90d++;
            }
        }

        $metrics = [
            'total_expired' => count($learners),
            'expired_this_month' => $expired_this_month,
            'expired_this_year' => $expired_this_year,
            'retention_leads' => $recent_expired_90d,
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('report.partials.expired_learner_table', compact('learners'))->render(),
                'metrics' => $metrics,
                'total_count' => count($learners),
            ]);
        }
        
        return view('report.expired_learner', compact('dynamicyears', 'dynamicmonths', 'learners', 'metrics', 'filters'));
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

    public function paymentCollection(Request $request)
    {
        $dates = getLearnerMonthsAndYears();
        $dynamicyears = $dates['years'];
        $dynamicmonths = $dates['months'];

        $preset = $request->get('preset', '');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $payment_mode = $request->get('payment_mode');

        // Default to This Month if no dates or preset are specified
        if (!$request->has('start_date') && !$request->has('end_date') && empty($preset)) {
            $start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
            $end_date = Carbon::now()->format('Y-m-d');
            $preset = 'this_month';
        }

        $query = LearnerTransaction::where('library_id', getLibraryId())
            ->where('paid_amount', '!=', 0)
            ->with(['learner', 'learnerDetail.plan', 'learnerDetail.planType'])
            ->orderBy('paid_date', 'desc')
            ->orderBy('id', 'desc');

        if ($preset !== 'all') {
            if ($start_date && $end_date) {
                $query->whereBetween('paid_date', [$start_date, $end_date]);
            } elseif ($start_date) {
                $query->whereDate('paid_date', $start_date);
            } elseif ($end_date) {
                $query->whereDate('paid_date', $end_date);
            }
        }

        if (!empty($payment_mode)) {
            $pmStr = strtolower(trim((string)$payment_mode));
            if (in_array($pmStr, ['1', 'online'])) {
                $query->where(function($modeQ) {
                    $modeQ->whereHas('learnerDetail', function($q) {
                        $q->where('payment_mode', '1')
                          ->orWhere('payment_mode', 1)
                          ->orWhereRaw('LOWER(payment_mode) = ?', ['online']);
                    })->orWhereHas('learner', function($q) {
                        $q->where('payment_mode', '1')
                          ->orWhere('payment_mode', 1)
                          ->orWhereRaw('LOWER(payment_mode) = ?', ['online']);
                    });
                });
            } elseif (in_array($pmStr, ['2', 'offline'])) {
                $query->where(function($modeQ) {
                    $modeQ->whereHas('learnerDetail', function($q) {
                        $q->where('payment_mode', '2')
                          ->orWhere('payment_mode', 2)
                          ->orWhereRaw('LOWER(payment_mode) = ?', ['offline']);
                    })->orWhereHas('learner', function($q) {
                        $q->where('payment_mode', '2')
                          ->orWhere('payment_mode', 2)
                          ->orWhereRaw('LOWER(payment_mode) = ?', ['offline']);
                    });
                });
            } elseif (in_array($pmStr, ['3', 'paylater', 'pay later'])) {
                $query->where(function($modeQ) {
                    $modeQ->whereHas('learnerDetail', function($q) {
                        $q->where('payment_mode', '3')
                          ->orWhere('payment_mode', 3)
                          ->orWhereRaw('LOWER(payment_mode) LIKE ?', ['%pay%']);
                    })->orWhereHas('learner', function($q) {
                        $q->where('payment_mode', '3')
                          ->orWhere('payment_mode', 3)
                          ->orWhereRaw('LOWER(payment_mode) LIKE ?', ['%pay%']);
                    });
                });
            } else {
                $query->where(function($modeQ) use ($payment_mode) {
                    $modeQ->whereHas('learnerDetail', function($q) use ($payment_mode) {
                        $q->where('payment_mode', $payment_mode);
                    })->orWhereHas('learner', function($q) use ($payment_mode) {
                        $q->where('payment_mode', $payment_mode);
                    });
                });
            }
        }

        if (getCurrentBranch() != 0 && getCurrentBranch() != null) {
            $query->where('branch_id', getCurrentBranch());
        }

        $learners = $query->get();

        $onlineSum = $learners->filter(function($item) {
            $mode = strtolower(trim((string)($item->learnerDetail->payment_mode ?? $item->learner->payment_mode ?? $item->payment_mode ?? '')));
            return in_array($mode, ['1', 'online']);
        })->sum('paid_amount');

        $offlineSum = $learners->filter(function($item) {
            $mode = strtolower(trim((string)($item->learnerDetail->payment_mode ?? $item->learner->payment_mode ?? $item->payment_mode ?? '')));
            return in_array($mode, ['2', 'offline']);
        })->sum('paid_amount');

        $paylaterSum = $learners->filter(function($item) {
            $mode = strtolower(trim((string)($item->learnerDetail->payment_mode ?? $item->learner->payment_mode ?? $item->payment_mode ?? '')));
            return in_array($mode, ['3', 'pay later', 'paylater']) || str_contains($mode, 'pay');
        })->sum('paid_amount');

        $metrics = [
            'total_collected' => (float) $learners->sum('paid_amount'),
            'total_invoiced' => (float) $learners->sum('total_amount'),
            'total_pending' => (float) $learners->sum('pending_amount'),
            'total_discount' => (float) $learners->sum('discount_amount'),
            'total_count' => $learners->count(),
            'online_amount' => (float) $onlineSum,
            'offline_amount' => (float) $offlineSum,
            'paylater_amount' => (float) $paylaterSum,
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => true,
                'metrics' => [
                    'total_collected' => '₹ ' . number_format($metrics['total_collected'] ?? 0, 2),
                    'total_invoiced' => '₹ ' . number_format($metrics['total_invoiced'] ?? 0, 2),
                    'total_pending' => '₹ ' . number_format($metrics['total_pending'] ?? 0, 2),
                    'total_count' => number_format($metrics['total_count'] ?? 0),
                    'online_amount' => '₹' . number_format($metrics['online_amount'] ?? 0, 0),
                    'offline_amount' => '₹' . number_format($metrics['offline_amount'] ?? 0, 0),
                ],
                'html' => view('report.partials.collection_cards', compact('learners'))->render(),
                'count' => $learners->count(),
                'start_date' => $start_date,
                'end_date' => $end_date,
                'preset' => $preset,
                'payment_mode' => $payment_mode,
            ]);
        }

        return view('report.payment_collection', compact(
            'dynamicyears',
            'dynamicmonths',
            'learners',
            'start_date',
            'end_date',
            'preset',
            'payment_mode',
            'metrics'
        ));
    }


    public function partialPaymentCollection(Request $request)
    {
        $dates = getLearnerMonthsAndYears();
        $dynamicyears = $dates['years'];
        $dynamicmonths = $dates['months'];

        $year = $request->get('year');
        $month = $request->get('month');
        $due_status = $request->get('due_status', 'all');

        $query = LearnerTransaction::withoutGlobalScopes()
            ->leftJoin('learners', 'learner_transactions.learner_id', '=', 'learners.id')
            ->where('learner_transactions.library_id', getLibraryId())
            ->whereNotNull('learner_transactions.due_date')
            ->select(
                'learner_transactions.id as transaction_id',
                'learner_transactions.id',
                'learner_transactions.learner_id',
                'learner_transactions.learner_detail_id',
                'learner_transactions.due_date',
                'learner_transactions.pending_amount',
                'learner_transactions.paid_amount',
                'learner_transactions.total_amount',
                'learner_transactions.paid_date',
                'learner_transactions.is_paid',
                'learner_transactions.created_at as transaction_created_at',
                'learners.id as learner_table_id',
                'learners.name',
                'learners.email',
                'learners.mobile',
                'learners.seat_no',
                'learners.payment_mode',
                'learners.status as learner_status'
            );

        // Apply Year & Month filters
        if (!empty($year) && $year !== 'all') {
            $query->whereYear('learner_transactions.due_date', $year);
        }
        if (!empty($month) && $month !== 'all') {
            $query->whereMonth('learner_transactions.due_date', $month);
        }

        // Apply Due Status filter
        $todayDate = Carbon::today()->format('Y-m-d');
        if ($due_status === 'overdue') {
            $query->whereDate('learner_transactions.due_date', '<', $todayDate)
                  ->where('learner_transactions.pending_amount', '>', 0);
        } elseif ($due_status === 'today') {
            $query->whereDate('learner_transactions.due_date', $todayDate);
        } elseif ($due_status === 'upcoming') {
            $query->whereDate('learner_transactions.due_date', '>', $todayDate);
        } elseif ($due_status === 'pending') {
            $query->where('learner_transactions.pending_amount', '>', 0);
        }

        if (getCurrentBranch() != 0 && getCurrentBranch() != null) {
            $query->where('learner_transactions.branch_id', getCurrentBranch());
        }

        $learners = $query->orderBy('learner_transactions.due_date', 'asc')
                          ->orderBy('learner_transactions.id', 'desc')
                          ->get();

        // Calculate KPI Summary Metrics
        $today = Carbon::today();
        $totalPending = (float) $learners->sum('pending_amount');

        $overdueLearners = $learners->filter(function($item) use ($today) {
            $dueDate = !empty($item->due_date) ? Carbon::parse($item->due_date) : null;
            $isPaid = ($item->is_paid == 1 || (float)$item->pending_amount <= 0);
            return !$isPaid && $dueDate && $dueDate->lt($today);
        });

        $overdueAmount = (float) $overdueLearners->sum('pending_amount');
        $overdueCount = $overdueLearners->count();
        $totalCount = $learners->count();

        $metrics = [
            'total_pending' => $totalPending,
            'overdue_amount' => $overdueAmount,
            'overdue_count' => $overdueCount,
            'total_count' => $totalCount,
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => true,
                'metrics' => [
                    'total_pending' => '₹ ' . number_format($metrics['total_pending'] ?? 0, 2),
                    'overdue_amount' => '₹ ' . number_format($metrics['overdue_amount'] ?? 0, 2),
                    'overdue_count' => number_format($metrics['overdue_count'] ?? 0),
                    'total_count' => number_format($metrics['total_count'] ?? 0),
                ],
                'html' => view('report.partials.partial_collection_cards', compact('learners'))->render(),
                'count' => $learners->count(),
                'year' => $year,
                'month' => $month,
                'due_status' => $due_status,
            ]);
        }

        return view('report.partial_payment_collection', compact(
            'dynamicyears',
            'dynamicmonths',
            'learners',
            'year',
            'month',
            'due_status',
            'metrics'
        ));
    }

  

    public function activity(Request $request)
    {
        $filters = [
            'year' => $request->get('year'),
            'month' => $request->get('month'),
            'operation' => $request->get('operation'),
            'status' => $request->get('status'),
            'search' => $request->get('search'),
            'learner_id' => $request->get('learner_id'),
        ];
        $bindings = [getLibraryId()];
        $branchFilterSql = '';
        if (getCurrentBranch() != 0) {
            $branchFilterSql = ' AND branch_id = ?';
            $bindings[] = getCurrentBranch();
        }

        $data = DB::select("
            SELECT DISTINCT
                YEAR(created_at) as year,
                MONTH(created_at) as month
            FROM learner_operations_log
            WHERE library_id = ?
            $branchFilterSql
            ORDER BY year DESC, month ASC
        ", $bindings);

        $collection = collect($data);

        $years = $collection->pluck('year')->filter()->unique()->values();
        $months = $collection->pluck('month')->filter()->unique()->values();

        // Fallback to standard dynamic years & months if logs table has few records
        $defaultDates = getLearnerMonthsAndYears();
        if ($years->isEmpty()) {
            $years = collect($defaultDates['years']);
        }
        if ($months->isEmpty()) {
            $months = collect($defaultDates['months']);
        }

        $query = LearnerOperationsLog::query()
            ->with(['learner' => fn ($q) => $q->withoutGlobalScopes()]);

        if (getCurrentBranch() != 0 && getCurrentBranch() != null) {
            $query->where('branch_id', getCurrentBranch());
        }

        // Filter by operation from the logs table
        if (!empty($filters['operation']) && $filters['operation'] !== 'all') {
            $query->where('operation', $filters['operation']);
        }

        // Filter by year from logs' created_at
        if (!empty($filters['year']) && $filters['year'] !== 'all') {
            $query->whereYear('created_at', $filters['year']);
        }

        // Filter by month from logs' created_at
        if (!empty($filters['month']) && $filters['month'] !== 'all') {
            $query->whereMonth('created_at', $filters['month']);
        }

        // Filter by status from the related learner table
        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== 'all') {
            $query->whereHas('learner', function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            });
        }

        // Search by name, email, or mobile in the related learner table
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->whereHas('learner', function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('mobile', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('email', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        // Scope to a single learner
        if (!empty($filters['learner_id'])) {
            $query->where('learner_id', $filters['learner_id']);
        }

        $learners = $query->latest('id')->get();

        $filterLearnerId = $filters['learner_id'];
        $filterLearnerName = !empty($filterLearnerId)
            ? \App\Models\Learner::withTrashed()->where('id', $filterLearnerId)->value('name')
            : null;

        // Calculate KPI Summary Metrics
        $totalActivities = $learners->count();
        $swapCount = $learners->where('operation', 'swapseat')->count();
        $planChangeCount = $learners->filter(function($item) {
            return in_array($item->operation, ['changePlan', 'learnerUpgrade']);
        })->count();
        $renewCount = $learners->filter(function($item) {
            return in_array($item->operation, ['renewSeat', 'reactive', 'giftDays']);
        })->count();

        $metrics = [
            'total_activities' => $totalActivities,
            'swap_count' => $swapCount,
            'plan_change_count' => $planChangeCount,
            'renew_count' => $renewCount,
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => true,
                'metrics' => [
                    'total_activities' => number_format($metrics['total_activities']),
                    'swap_count' => number_format($metrics['swap_count']),
                    'plan_change_count' => number_format($metrics['plan_change_count']),
                    'renew_count' => number_format($metrics['renew_count']),
                ],
                'html' => view('report.partials.activity_cards', compact('learners'))->render(),
                'count' => $learners->count(),
                'filters' => $filters,
            ]);
        }

        return view('report.learner_activity', compact(
            'learners',
            'years',
            'months',
            'filterLearnerId',
            'filterLearnerName',
            'filters',
            'metrics'
        ));
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

            // Safely decrypt mobile number
            $learnerMobile = '';
            if (!empty($learner->mobile)) {
                try {
                    $learnerMobile = decryptData($learner->mobile);
                } catch (\Exception $e) {
                    $learnerMobile = $learner->mobile;
                }
            }

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

        // AJAX response for seamless dynamic filtering
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('report.partials.attendance_matrix', compact('learnerAttendance', 'daymonth', 'year', 'month', 'daysMeta'))->render(),
                'metrics' => $metrics,
                'daymonth' => $daymonth,
                'filters' => [
                    'year' => $year,
                    'month' => $month,
                    'search' => $request->search ?? '',
                ],
            ]);
        }

        return view('report.monthly_attendance', compact(
            'dynamicyears',
            'dynamicmonths',
            'learnerAttendance',
            'daymonth',
            'year',
            'month',
            'daysMeta',
            'metrics',
            'hasCustomFilter'
        ));
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

        $metrics = [
            'totalCollection' => $totalCollection,
            'totalExpense'    => $totalExpense,
            'totalRevenue'    => $totalRevenue,
            'grandTotal'      => $grandTotal,
            'totalCr'         => $totalCr,
            'totalDr'         => $totalDr,
            'grandBalance'    => $grandBalance,
            'count'           => $transactions->count(),
            'activeDays'      => $groupedTransactions->count(),
        ];

        // AJAX response
        if ($request->ajax()) {
            return response()->json([
                'html' => view('report.partials.monthly_payment_table', compact(
                    'groupedTransactions',
                    'totalCr',
                    'totalDr',
                    'totalCollection',
                    'totalExpense',
                    'totalRevenue',
                    'grandTotal'
                ))->render(),
                'metrics' => $metrics,
                'count' => $transactions->count(),
            ]);
        }

        return view('report.monthly_payment_collection', compact(
            'groupedTransactions',
            'totalCr',
            'totalDr',
            'totalCollection',
            'totalExpense',
            'totalRevenue',
            'grandTotal',
            'months',
            'dynamicyears',
            'dynamicmonths',
            'hasCustomFilter',
            'fromDate',
            'toDate',
            'metrics'
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
}
