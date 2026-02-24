<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
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
           $totals_monthly = LearnerTransactionActivity::selectRaw("
                SUM(CASE WHEN dr_cr = 'Cr' THEN amount ELSE 0 END) as total_cr,
                SUM(CASE WHEN dr_cr = 'Dr' THEN amount ELSE 0 END) as total_dr
            ")
            ->where('branch_id', getCurrentBranch())
            ->whereYear('date', $monthlyRevenue->year)->whereMonth('date', $monthlyRevenue->month)
            ->first();
            

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
        $library_revenue = LearnerDetail::withoutGlobalScopes()
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
        ->groupBy('year', 'month')
        ->first();
           
     
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

     
       
        $learners = $this->fetchlearnerData( $filters,$query);
            
        return view('report.pending_payment', compact('plans','planTypes','dynamicyears', 'dynamicmonths', 'learners'));

    }

   
    
    public function learnerReport(Request $request){
     

        $filters = [
            'year' => $request->get('year'),
            'month' => $request->get('month'),
            'is_paid' => $request->get('is_paid'),
            'status'  => $request->get('status'),
            'search'  => $request->get('search'),
        ];
       
        $query = LearnerDetail::where('library_id',getLibraryId())->with(['plan', 'planType','learner']);
        if(getCurrentBranch() !=0){
            $query->where('branch_id',getCurrentBranch());
        }
         
        $learners = $this->fetchlearnerData( $filters,$query);
       // Get the unique years and month
       $minStartDate =LearnerDetail::min('plan_start_date');
       $maxEndDate =LearnerDetail::max('plan_end_date');
   
       $start = Carbon::parse($minStartDate)->startOfMonth();
       $end = Carbon::parse($maxEndDate)->startOfMonth();
   
       $months = [];
       while ($start <= $end) {
           $year = $start->year;
           $month = $start->format('F');
           $months[$year][$start->month] = $month;
           $start->addMonth();
       }
        return view('report.learner_report',compact('learners', 'months'));
    }

    public function upcomingPayment(){
        $today = Carbon::now()->format('Y-m-d');
        $fiveDaysLater = Carbon::now()->addDays(5)->format('Y-m-d');
       
        $data = $this->getAllLearnersByLibrary()
        ->whereHas('learnerDetails', function ($query) use ($today, $fiveDaysLater) {
            $query->whereBetween('plan_end_date', [$today, $fiveDaysLater]);
        })
        ->whereNotExists(function ($subQuery) use ($fiveDaysLater) {
            $subQuery->select(DB::raw(1))
                ->from('learner_detail as ld2') 
                ->whereColumn('ld2.learner_id', 'learners.id') 
                ->where('ld2.plan_end_date', '>', $fiveDaysLater);
        })
        ->get();
       if (getCurrentBranch() != 0 && getCurrentBranch() != null) {
            $data = $data->filter(function ($learner) {
                return optional($learner->learnerDetails->first())->branch_id == getCurrentBranch();
            });

        }

        $learners = $data;


        return view('report.upcoming_payment',compact('learners'));
    }

    public function expiredLearner(Request $request){
       
        $dates = getLearnerMonthsAndYears();
        $dynamicyears =$dates['years'];
        $dynamicmonths = $dates['months'];
        $filters = [
            'expiredyear' => $request->get('expiredyear'),
            'expiredmonth' => $request->get('expiredmonth'),
        ];
        $query = LearnerDetail::where('library_id',getLibraryId())->where('plan_start_date', '<=', date('Y-m-d'))->with(['plan', 'planType','learner'])->where('status', 0)
        ->whereHas('learner', function($query) {
            $query->where('status', 0);
        });

        if(getCurrentBranch() !=0 ){
            $query->where('branch_id',getCurrentBranch());
        }
       
        $learners = $this->fetchlearnerData( $filters,$query);
        
       
        return view('report.expired_learner', compact('dynamicyears', 'dynamicmonths', 'learners'));

    }

    public function fetchlearnerData( $filters,$query){
  
        Log::info('Filters applied:', $filters);
        if (!empty($filters)) {
            $year = $filters['year'] ?? date('Y');
            $month = $filters['month'] ?? null;

            if (!empty($filters['plan_id'])) {
                    $query->where('plan_id', $filters['plan_id']);
            }
            if (!empty($filters['plan_type'])) {
                Log::info('Filter applied: plan type');
               
                $query->where('plan_type_id', $filters['plan_type']);
            
            }

            if (!empty($filters['expiredyear'])) {
                Log::info('Filter applied: expiredyear ');
                $year = $filters['expiredyear'];
                $query->whereYear('plan_end_date', $year);
               
            }
        
            if (!empty($filters['expiredmonth']) && !empty($filters['expiredyear'])) {
                Log::info('Filter applied: expiredyear and expiredmonth');
                $year = $filters['expiredyear'];
                $month = $filters['expiredmonth'];
               
                $query->whereYear('plan_end_date', $year)->whereMonth('plan_end_date', $month);
               
            }
           
            if (isset($filters['is_paid'])) {
                Log::info('Filter applied: unpaid');
               
                $query->where('is_paid', $filters['is_paid']);
               
            }

                // Apply the year filter if provided
            if (!empty($filters['year'])) {
                Log::info('Filter applied: year');
                $year = $filters['year'];
                
                // Adjust query to cover plan dates within the given year
                $query->whereYear('plan_start_date', '<=', $year)
                ->whereYear('plan_end_date', '>=', $year);
            }
        
            // Apply the month filter if provided (year should be set either by filter or default)
            if (!empty($filters['month'])) {
               
               
                Log::info('Filter applied: year and month',['year' => $year,'month' => $month,]);
                $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
                $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
                $query->where('plan_start_date', '<=', $endOfMonth)->where('plan_end_date', '>=', $startOfMonth);
             
            }

                // Apply the status filter if provided
            if (isset($filters['status'])) {
                $status = $filters['status'];
                
                // If status = 0 (expired), filter based on the year and/or month, if provided
                if ($status == 0 && ($filters['year'] || $filters['month'])) {
                    
                    Log::info('Filter applied: expired status with year and/or month', ['year' => $year,'month' => $month,'status' => $status,]);
                    
                    $query->where('learner_detail.status', $status)
                    ->whereYear('learner_detail.plan_end_date', $year);
                
                    if ($month) {
                        Log::info('in month');
                        $query->whereMonth('learner_detail.plan_end_date', $month);
                    }
                } else {
                    // Apply regular status filter if not expired with specific year/month
                    Log::info('not expired with specific year/month');
                   
                        $query->where('status', $status);
                   
                }
            }

            // Search by Name, Mobile, or Email
           if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('mobile', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('email', 'LIKE', '%' . $searchTerm . '%');
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

        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');

        $query = LearnerTransaction::where('library_id', getLibraryId())
            ->where('paid_amount', '!=', 0)
            ->with('learner');

        if ($start_date && $end_date) {
            $query->whereBetween('paid_date', [$start_date, $end_date]);
        } elseif ($start_date) {
            $query->whereDate('paid_date', $start_date);
        } elseif ($end_date) {
            $query->whereDate('paid_date', $end_date);
        }

        if (getCurrentBranch() != 0 && getCurrentBranch() != null) {
            $query->where('branch_id', getCurrentBranch());
        }

        $learners = $query->get();

        return view('report.payment_collection', compact('dynamicyears', 'dynamicmonths', 'learners'));
    }


    public function partialPaymentCollection(Request $request)
    {
        $dates = getLearnerMonthsAndYears();
        $dynamicyears = $dates['years'];
        $dynamicmonths = $dates['months'];

        $year = $request->get('year');
        $month = $request->get('month');

        $query = LearnerTransaction::withoutGlobalScopes()
            ->leftJoin('learners', 'learner_transactions.learner_id', '=', 'learners.id')
           ->where('learner_transactions.branch_id', getCurrentBranch())
           ->whereNotNull('learner_transactions.due_date')
            ->select(
                'learner_transactions.due_date',
                'learner_transactions.pending_amount',
                'learners.name',
                'learners.email',
                'learners.mobile',
                'learners.seat_no'
            );

        // Apply filters
        if ($year && $month) {
            $query->whereYear('learner_transactions.due_date', $year)
                ->whereMonth('learner_transactions.due_date', $month);
        } elseif ($year) {
            $query->whereYear('learner_transactions.due_date', $year);
        } elseif ($month) {
            $query->whereMonth('learner_transactions.due_date', $month);
        }

       

        $learners = $query->get();


        return view('report.partial_payment_collection', compact('dynamicyears', 'dynamicmonths', 'learners'));
    }

  

   public function activity(Request $request)
    {
        $filters = [
            'year' => $request->get('year'),
            'month' => $request->get('month'),
            'operation' => $request->get('operation'),
            'status' => $request->get('status'),
            'search' => $request->get('search'),
        ];
        $data = DB::select("
            SELECT DISTINCT 
                YEAR(created_at) as year, 
                MONTH(created_at) as month 
            FROM learner_operations_log
            WHERE branch_id = ?
            ORDER BY year DESC, month ASC
        ", [getCurrentBranch()]);

        $collection = collect($data);

        $years = $collection->pluck('year')->unique()->values();
        $months = $collection->pluck('month')->unique()->values();


        $query = LearnerOperationsLog::where('branch_id', getCurrentBranch())
            ->with('learner');
       

        // Filter by operation from the logs table
        if (!empty($filters['operation'])) {
            $query->where('operation', $filters['operation']);
        }

        // Filter by year from logs' created_at
        if (!empty($filters['year'])) {
            $query->whereYear('created_at', $filters['year']);
        }

        // Filter by month from logs' created_at
        if (!empty($filters['month'])) {
            $query->whereMonth('created_at', $filters['month']);
        }

        // Filter by status from the related learner table
        if (!empty($filters['status'])) {
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

        $learners = $query->latest()->get();
       
        return view('report.learner_activity', compact('learners','years','months'));
    }

    public function attendanceReport(Request $request){
        $data = DB::select("
            SELECT DISTINCT 
                YEAR(date) as year, 
                MONTH(date) as month 
            FROM attendances
            WHERE branch_id = ?
            ORDER BY year DESC, month ASC
        ", [getCurrentBranch()]);

        $collection = collect($data);

        $dynamicyears = $collection->pluck('year')->unique()->values();
        $dynamicmonths = $collection->pluck('month')->unique()->values();
       
        $year = $request->year ?? date('Y');
        $month = $request->month ?? date('m');
        $daymonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $learners = Learner::where('branch_id',getCurrentBranch())->get();

       $attendanceRecords = Attendance::where('branch_id', getCurrentBranch())
        ->where('branch_id', getCurrentBranch())
        ->whereYear('date', $year)
        ->whereMonth('date', $month)
        ->get()
        ->groupBy('learner_id');
        

       $learnerAttendance = $learners->map(function ($learner) use ($attendanceRecords, $month, $year, $daymonth) {
        $records = $attendanceRecords[$learner->id] ?? collect();

        $daily = [];
        $present = 0;
        $absent = 0;

        for ($day = 1; $day <= $daymonth; $day++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);

            $record = $records->first(function ($r) use ($dateStr) {
                return \Carbon\Carbon::parse($r->date)->format('Y-m-d') === $dateStr;
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

        return [
            'learner' => $learner,
            'seat_no' => $learner->seat_no ?? 'G',
            'email' => $learner->email ?? '-',
            'mobile' => $learner->mobile ?? '-',
            'name' => $learner->name ?? '-',
            'present' => $present,
            'absent' => $absent,
              'daily' => $daily ?? [],
        ];
    });

      

        return view('report.monthly_attendance', compact('dynamicyears', 'dynamicmonths', 'learnerAttendance','daymonth'));

    }

    
    public function monthlyPaymentCollection(Request $request)
    {
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
       $transactions = LearnerTransactionActivity::withoutGlobalScopes()
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
                'learners.seat_no'
            )
            ->where('learner_transaction_activity.branch_id', getCurrentBranch())
            ->orderBy('learner_transaction_activity.date', 'asc')
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

      
        return view('report.monthly_payment_collection', compact( 'groupedTransactions','totalCr','totalDr', 'totalCollection','totalExpense','totalRevenue','grandTotal'));
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

    $fileName = 'monthly_payment_report.csv';

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
            'Name',
            'Amount',
            'Transaction',
            'Type',
            'Mode',
            'Day Balance'
        ]);

        foreach ($grouped as $date => $rows) {

            $dayCr = $rows->where('dr_cr', 'Cr')->sum('amount');
            $dayDr = $rows->where('dr_cr', 'Dr')->sum('amount');
            $dayBalance = $dayCr - $dayDr;

            foreach ($rows as $index => $row) {

                $type = $row->dr_cr == 'Cr' ? 'CREDIT' : 'DEBIT';
                $amount = ($row->dr_cr == 'Cr' ? '+' : '-') . $row->amount;

                fputcsv($handle, [
                    $index == 0 ? $date : '',
                    $row->seat_no ?? 'GEN',
                    $row->name,
                    $amount,
                    $row->payment_type,
                    $type,
                    $row->payment_mode,
                    $index == 0 ? $dayBalance : ''
                ]);
            }
        }

        // Empty row
        fputcsv($handle, []);

        // Summary rows
        fputcsv($handle, ['Total Collection', '', '', $totalCollection]);
        fputcsv($handle, ['Total Expense', '', '', $totalExpense]);
        fputcsv($handle, ['Total Revenue (Refund)', '', '', $totalRevenue]);
        fputcsv($handle, ['Grand Total', '', '', $grandTotal]);

        fclose($handle);
    };

    return response()->stream($callback, 200, $headers);
}


    
}
