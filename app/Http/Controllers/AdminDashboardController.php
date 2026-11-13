<?php

namespace App\Http\Controllers;

use App\Helpers\HelperService;
use App\Models\CustomerDetail;
use App\Models\Customers;
use App\Models\Hour;
use App\Models\LearnerDetail;
use App\Models\Library;
use App\Models\LibraryTransaction;
use App\Models\Seat;
use App\Models\Student;
use Illuminate\Http\Request;
use Auth;
use DB;
use Illuminate\Support\Carbon;
use App\Services\LibraryService;
use App\Services\LearnerService;
use App\Services\DashboardInsightsService;
use App\Traits\LearnerQueryTrait;
use App\Http\Middleware\LoadMenus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Feature;
use App\Models\Learner;
use App\Models\LearnerOperationsLog;
use App\Models\LearnerTransaction;
use App\Models\LearnerTransactionActivity;
use App\Models\Plan;
use App\Models\PlanType;
use App\Models\Subscription;
use Log;
 use App\Models\Scopes\LibraryScope;

/** Admin portal (portal:admin): platform home, library counts and the library highlights chart. Split out of the old DashboardController. */
class AdminDashboardController extends Controller
{
    use LearnerQueryTrait;
    protected $libraryService;
    protected $learnerService;


    public function __construct(LibraryService $libraryService, LearnerService $learnerService)
    {
        
        $this->libraryService = $libraryService;
        $this->learnerService = $learnerService;
    }
    public function index()
    {
        $user = Auth::user() ?? Auth::guard('web')->user();

        if ($user && ($user->hasRole('superadmin') || $user->hasRole('admin'))) {
            $totalregistration = Library::count();
            $paidregistration = Library::where('is_paid', 1)->count();
            $unpaidregistration = Library::where('is_paid', 0)->count();
            $renewCount = Library::leftJoin('library_transactions', 'libraries.id', '=', 'library_transactions.library_id')
                ->where('libraries.is_paid', 1)
                ->where('end_date', '<=', date('Y-m-d'))
                ->count();

            // Overall Total Revenue from paid library transactions
            $overallRevenue = DB::table('library_transactions')
                ->where('is_paid', 1)
                ->sum('paid_amount');

            // 1. Plan-wise Revenue & Registration Summary
            $subscriptions = Subscription::withCount([
                'libraries' => function ($query) {
                    $query->whereNull('deleted_at');
                }
            ])->get();

            $planwiseData = [];
            foreach ($subscriptions as $plan) {
                $planRevenue = DB::table('libraries')
                    ->join('library_transactions', 'libraries.id', '=', 'library_transactions.library_id')
                    ->where('libraries.library_type', $plan->id)
                    ->where('library_transactions.is_paid', 1)
                    ->sum('library_transactions.paid_amount');

                $activeLibs = Library::where('library_type', $plan->id)->where('status', 1)->count();
                $paidLibs = Library::where('library_type', $plan->id)->where('is_paid', 1)->count();

                $planwiseData[] = [
                    'plan'                => $plan,
                    'total_registrations' => $plan->libraries_count,
                    'active_libraries'    => $activeLibs,
                    'paid_libraries'      => $paidLibs,
                    'revenue'             => $planRevenue,
                ];
            }

            // 2. Monthly Registration Progress for Selected / Current Year
            $selectedYear = request('year', date('Y'));
            $monthlyProgressData = DB::table('libraries')
                ->selectRaw('MONTH(created_at) as month_num, COUNT(*) as count')
                ->whereYear('created_at', $selectedYear)
                ->groupBy('month_num')
                ->pluck('count', 'month_num')
                ->toArray();

            $monthlyProgress = [];
            $maxMonthlyCount = max(array_values($monthlyProgressData) ?: [1]);
            for ($m = 1; $m <= 12; $m++) {
                $monthName = Carbon::create()->month($m)->format('F');
                $count = $monthlyProgressData[$m] ?? 0;
                $percentage = $maxMonthlyCount > 0 ? round(($count / $maxMonthlyCount) * 100) : 0;
                $monthlyProgress[] = [
                    'month_num'  => $m,
                    'month_name' => $monthName,
                    'count'      => $count,
                    'percentage' => $percentage,
                ];
            }

            $today = Carbon::now()->format('Y-m-d');
            $tenDaysLater = Carbon::now()->addDays(10)->format('Y-m-d');
            $upcoming_registration = Library::with(['library_transactions', 'subscription'])
                ->whereHas('library_transactions', function ($query) use ($today, $tenDaysLater) {
                    $query->whereBetween('end_date', [$today, $tenDaysLater]);
                })->take(5)->get();

            $recent_registrations = Library::with(['subscription'])->orderBy('id', 'desc')->take(5)->get();

            return view('dashboard.administrator', compact(
                'totalregistration',
                'paidregistration',
                'unpaidregistration',
                'renewCount',
                'overallRevenue',
                'planwiseData',
                'monthlyProgress',
                'selectedYear',
                'upcoming_registration',
                'recent_registrations'
            ));
        }

        if ($user->hasRole('learner')) {
            return view('dashboard.learner');
        }

        // Admin users without the superadmin/admin role (e.g. Blog Manager): open the first
        // admin page they may use instead of an empty response.
        if (Auth::guard('web')->check()) {
            foreach (\App\Support\AdminNav::visible() as $section) {
                foreach ($section['items'] as $item) {
                    if ($item['route'] !== 'home') {
                        return redirect()->route($item['route']);
                    }
                }
            }
        }

        abort(403);
    }

    public function libraryView(Request $request){
        // The old count list queried a "library" table that does not exist; send each
        // count to the filtered library list instead.
        $filters = [
            'paid_registration' => ['is_paid' => 1],
            'unpaid_registration' => ['is_paid' => 0],
            'pending_renew' => ['status' => 'expired'],
        ];

        return redirect()->route('library', $filters[$request->get('type')] ?? []);
    }

    public function libraryGetData(Request $request){
        if ($request->filled('year') && $request->filled('month')) {
            $year = $request->year;
            $month = $request->month;
        } elseif ($request->filled('year') && !$request->filled('month')) {
            $year = $request->year;
            $month = date('m'); 
        } elseif (!$request->filled('year') && $request->filled('month')) {
            $year = date('Y');
            $month = $request->month;
        } else {
            $year = date('Y');
            $month = date('m');
        }
        $startOfGivenMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfGivenMonth = Carbon::create($year, $month, 1)->endOfMonth();
        $total_revenue=DB::table('library_transactions') ->where('is_paid', 1)
        ->where(function ($subQuery) use ($startOfGivenMonth, $endOfGivenMonth) {
            $subQuery->where('start_date', '<=', $endOfGivenMonth)
                ->Where('end_date', '>=', $startOfGivenMonth);
        })->selectRaw('SUM(paid_amount / month) as total_revenue')->value('total_revenue');
      
        $new_registration = DB::table('libraries')
        ->leftJoin('subscriptions', 'libraries.library_type', '=', 'subscriptions.id') // Join subscriptions table
        ->select('libraries.*', 'subscriptions.name as subscription_name') // Select library fields and subscription name
        ->whereExists(function ($query) use ($month, $year) {
            $query->select(DB::raw(1))
                ->from('library_transactions')
                ->whereRaw('library_transactions.library_id = libraries.id')
                ->where('is_paid', 1)
                ->whereMonth('start_date', $month)
                ->whereYear('start_date', $year);
        })
        ->groupBy('libraries.id')
        ->get();
     
    
        $plan_wise_booking =Library::leftJoin('library_transactions','libraries.id','=','library_transactions.library_id')->groupBy('library_type')
        ->selectRaw('COUNT(DISTINCT library_id) as booking, library_type')
        ->whereNotNull('library_type')
        ->where(function ($subQuery) use ($startOfGivenMonth, $endOfGivenMonth) {
            $subQuery->where('start_date', '<=', $endOfGivenMonth)
                ->Where('end_date', '>=', $startOfGivenMonth);
        })
        ->with('subscription') 
        ->get();
        $data = [];
        foreach ($plan_wise_booking as $booking) {
            $data[] = [
                'subscription_id' => $booking->library_type,
                'booking' => $booking->booking,
                'subscription_name' => $booking->library_type ? $booking->subscription->name : 'Unknown' // Get the plan type name
            ];
        }  

        return response()->json([
            'highlights' => [
                'total_revenue' => $total_revenue,
                'new_registration' => $new_registration, 
             
            ],
            'plan_wise_booking' => $data,
      
        ]);
    }
}
