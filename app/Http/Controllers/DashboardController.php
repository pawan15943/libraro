<?php

namespace App\Http\Controllers;

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
use App\Traits\LearnerQueryTrait;
use App\Http\Middleware\LoadMenus;
use App\Models\Branch;
use App\Models\Feature;
use App\Models\Learner;
use App\Models\LearnerOperationsLog;
use App\Models\LearnerTransaction;
use App\Models\PlanType;
use App\Models\Subscription;
use Log;




class DashboardController extends Controller
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
        
        $user=Auth::user();
      
        if ($user->hasRole('superadmin')) {
           
           $totalregistration=Library::count();
           $paidregistration=Library::where('is_paid',1)->count();
           $unpaidregistration=Library::where('is_paid',0)->count();
           $renewCount=Library::leftJoin('library_transactions','libraries.id','=','library_transactions.library_id')->where('libraries.is_paid',1)->where('end_date','<=',date('Y-m-d'))->count();
           $plansWithCount = Subscription::withCount([
            'libraries' => function ($query) {
                $query->where('status', 1); // Filter active libraries
                }
            ])->get();
            $today = Carbon::now()->format('Y-m-d');
            $tenDaysLater = Carbon::now()->addDays(10)->format('Y-m-d');
            $upcoming_registration=Library::with(['library_transactions', 'subscription'])
            ->whereHas('library_transactions', function($query) use ($today, $tenDaysLater) {
                $query->whereBetween('end_date', [$today, $tenDaysLater]);
            })->get();
            return view('dashboard.administrator',compact('totalregistration','paidregistration','unpaidregistration','renewCount','plansWithCount','upcoming_registration'));
        }if ($user->hasRole('admin')) {
           
            return view('dashboard.admin');
        }if ($user->hasRole('learner')) {
           
        
            return view('dashboard.learner');
        }
       
    }

    public function libraryDashboard(Request $request)
    {
   
        $user = getAuthenticatedUser();
   
            //load menus status function call for status update
            $middleware = app(LoadMenus::class);
            $middleware->statusInactive();
            $value = LibraryTransaction::withoutGlobalScopes()->where('library_id',  getLibraryId())
            ->where('status', 1)
            ->first();
            $today = Carbon::today();
            if ($value) {
               
                $endDate = Carbon::parse($value->end_date);
                $diffInDays = $today->diffInDays($endDate, false);
                    if ($diffInDays < 0){
                        $library = Library::where('id', getLibraryId())->first();
                        if ($library) {
                            $library->is_paid = 0;
                            $library->save(); 
                        }
                    }
                 
                    if ($diffInDays == -5) {
                    // Update the transaction status to inactive
                    $value->status = 0;
                    $value->save();

                    $library = Library::where('id', getLibraryId())->first();
                    if ($library) {
                    $library->status = 0;
                    $library->save(); 
                    }
                }
            }

            // redirect check library  
            $iscomp = Library::where('id', getLibraryId())->where('status', 1)->exists();
            $redirectUrl = $this->libraryService->checkLibraryStatus();
            $check = LibraryTransaction::withoutGlobalScopes()->where('library_id',  getLibraryId())->where('is_paid',1)->orderBy('id','desc')->first();
           
            $is_expire=false;
            if ($check) {
                $today = Carbon::today();
                $endDate = Carbon::parse($check->end_date);
                $librarydiffInDays = $today->diffInDays($endDate, false);
                if($librarydiffInDays <= 0){
                    $is_expire=true;
                }
                
            }

            $available_seats=$this->learnerService->getAvailableSeatsPlantype();
            
            $extend_day = getExtendDays();
            $fiveDaysbetween = $today->copy()->addDays(5);
           
            $renewSeats = $this->getLearnersByLibrary()
            ->whereBetween('learner_detail.plan_end_date', [$today->format('Y-m-d'), $fiveDaysbetween->format('Y-m-d')])
            ->where('learner_detail.status', 1)
            ->whereNotExists(function ($query) use ($fiveDaysbetween) {
                $query->select(DB::raw(1))
                    ->from('learner_detail as ld')
                    ->whereColumn('ld.learner_id', 'learner_detail.learner_id') // match same learner
                    ->where('ld.plan_end_date', '>', DB::raw('learner_detail.plan_end_date')) ;
                   
            })
            ->with('planType')
            ->get();
        
         
            $extend_sets = $this->getLearnersByLibrary()
            ->where('learner_detail.is_paid', 1) 
            ->where('learner_detail.status', 1)  
            ->where('learner_detail.plan_end_date', '<', $today->format('Y-m-d')) 
            ->whereRaw("DATE_ADD(learner_detail.plan_end_date, INTERVAL ? DAY) >= CURDATE()", [$extend_day]) 
            ->whereNotExists(function ($query) use ($fiveDaysbetween) {
                $query->select(DB::raw(1))
                ->from('learner_detail as ld')
                ->whereColumn('ld.learner_id', 'learner_detail.learner_id') // match same learner
                ->where('ld.plan_end_date', '>', DB::raw('learner_detail.plan_end_date')) ;
            })
            ->with('planType') // Eager load related planType
            ->get();
          
            $threeMonthsAgo = $today->copy()->subMonths(2)->startOfMonth(); // Start of 3 months ago
            $endOfLastMonth = $today->copy()->subMonth()->endOfMonth(); // End of last month
           

            // Fetch revenue for the last three months
            $data = Library::where('id', getLibraryId())
            ->with('subscription.permissions')  
            ->first();
            $plan=Subscription::where('id',$data->library_type)->first();
            if($plan){
                $features_count=DB::table('subscription_permission')->where('subscription_id',$plan->id)->count();

            }else{
                $features_count=0;
            }
           
            $startOfYear = Carbon::now()->startOfYear();
            $endOfYear = Carbon::now()->endOfYear();
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
            $startDate = $startDate->format('Y-m-d');
            $endDate = $endDate->format('Y-m-d');
            $plans = $this->learnerService->getPlans();
         
            $plan_wise_booking = LearnerDetail::with('planType')
                ->whereBetween('join_date', [$startDate, $endDate])
                ->where('is_paid', 1)
                ->groupBy('plan_type_id')
                ->selectRaw('COUNT(id) as booking, plan_type_id')
                ->get();

            $bookinglabels = $plan_wise_booking->map(function ($booking) {
                return $booking->planType?->name ?? 'Unknown Plan';
            })->toArray();

            $bookingcount = $plan_wise_booking->pluck('booking')->toArray(); 
           
          
            // for dropdown year and month
            $dates = LearnerDetail::select('plan_start_date', 'plan_end_date')->get();

            $months = [];
            foreach ($dates as $date) {
                $start = Carbon::parse($date->plan_start_date)->startOfMonth();
                $end = Carbon::parse($date->plan_end_date)->startOfMonth();
        
                // Loop through the months within the start and end date range
                while ($start <= $end) {
                    $year = $start->year;
                    $monthNumber = $start->month;
                    $monthName = $start->format('F');
        
                    // Add month to the respective year in the months array
                    $months[$year][$monthNumber] = $monthName;
        
                    $start->addMonth();
                }
            }

            $recent_activitys=DB::table('learner_operations_log')->where('library_id',getLibraryId())->where('created_at', '>=', Carbon::now()->subDays(5))->get();

            if($is_expire && $user->hasRole('admin')){
            
                return redirect()->route('library.myplan');
            }elseif($iscomp){
               
                return view('dashboard.admin',compact('plans','available_seats','renewSeats','plan','features_count','check','extend_sets','bookingcount','bookinglabels','months','recent_activitys'));
            }else{
              
                return redirect($redirectUrl);
            }
           
          
      
      
       
    }

    public function librar_UserDashboard(Request $request){
        
        return view('dashboard.library_user');

    }
    public function learnerDashboard(){
        $user=Auth::user();
       
        $learners = LearnerDetail::withoutGlobalScopes()->where('learner_id',$user->id)->leftJoin('plans','learner_detail.plan_id','=','plans.id')->leftJoin('plan_types','learner_detail.plan_type_id','=','plan_types.id')->select('learner_detail.*','plans.name as plan_name','plan_types.name as plan_type_name')->get();
       
       $library_name=Branch::where('id',Auth::user()->branch_id)->select('name as library_name','features')->first();
  
       $learner_request = DB::table('learner_request')->where('learner_id', getAuthenticatedUser()->id)->get();
       $featuresArray = $library_name->features ? (is_array($library_name->features) ? $library_name->features : json_decode($library_name->features, true)) : [];

       $features = Feature::whereIn('id', $featuresArray)->get();
       
        if ($user->hasRole('learner')) {
          
            return view('dashboard.learner',compact('learners','library_name','features'));
        }
    }
    public function getData(Request $request)
    {
       
        // Library other highlights
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
        $lastDateOfGivenMonth = Carbon::create($year, $month, 1)->endOfMonth();
        
        
        $today = Carbon::now()->format('Y-m-d');
        $extend_day = getExtendDays();
       
        $fiveDaysLater = Carbon::now()->addDays(5)->format('Y-m-d');
        $expired_in_five  = $this->getAllLearnersByLibrary()
            ->whereHas('learnerDetails', function($query) use ($today, $fiveDaysLater) {
                $query->whereBetween('plan_end_date', [$today, $fiveDaysLater]);
            })->count();
       

        $extended_seats = $this->getLearnersByLibrary()
        ->where('learner_detail.is_paid',1)
        ->where('learners.status',1)
        ->where('learner_detail.status',1)
        ->where('learner_detail.plan_end_date', '<', date('Y-m-d'))
        ->whereRaw("DATE_ADD(learner_detail.plan_end_date, INTERVAL ? DAY) >= CURDATE()", [$extend_day])
        ->count();       
       
        //total seats
      
        $total_seats = totalSeat();
      
        //booked total seat
        $query =LearnerDetail::where('is_paid',1);
       
        if ($request->filled('year') && !$request->filled('month')) {
            // Check for year only
            $givenYear = $request->year;
        
            $query->whereYear('plan_start_date', '<=', $givenYear)
                ->whereYear('plan_end_date', '>=', $givenYear);
        } elseif ($request->filled('year') && $request->filled('month')) {
            // Check for year and month
            $givenYear = $request->year;
            $givenMonth = $request->month;
        
            $startOfGivenMonth = Carbon::create($givenYear, $givenMonth, 1)->startOfMonth();
            $endOfGivenMonth = Carbon::create($givenYear, $givenMonth, 1)->endOfMonth();
        
            $query->where(function ($subQuery) use ($startOfGivenMonth, $endOfGivenMonth) {
                $subQuery->where('plan_start_date', '<=', $endOfGivenMonth)
                    ->where('plan_end_date', '>=', $startOfGivenMonth);
            });
        }
        $booked_seats=$query->distinct('seat_no')->count('seat_no');

        // available slot
        if($total_seats!=0){
            $availble_seats=$total_seats-$booked_seats; 
        }else{
            $availble_seats=0;
        }
       
        
        // till today total slots
        
       
        $query_total = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
        ->where('learners.library_id', getLibraryId())
        ->where(function ($subQuery) use ($startOfGivenMonth, $endOfGivenMonth) {
            $subQuery->where('plan_start_date', '<=', $endOfGivenMonth)
                ->Where('plan_end_date', '>=', $startOfGivenMonth);
        }) ->groupBy('learner_detail.learner_id')
        ->selectRaw('COUNT(*) as total_count')
        ->get();
       
        $total_booking=$query_total->count(); 
       
                                                    
         // till today expired slots
         $startDateOfGivenMonth = Carbon::create($request->year, $request->month, 1)->startOfMonth();
         $expired_query = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
         ->where('learners.library_id', getLibraryId())
         ->where('learner_detail.is_paid', 1)
         ->where('learner_detail.plan_end_date', '>=', $startDateOfGivenMonth)
         ->where('learners.status', 0);
         
         if ($request->filled('year') && !$request->filled('month')) {
             // Filter by year only
             $expired_query->whereRaw(
                 "YEAR(DATE_ADD(learner_detail.plan_end_date, INTERVAL ? DAY)) <= ?", 
                 [$extend_day, $request->year]
             );
         } elseif ($request->filled('year') && $request->filled('month')) {
             // Filter by year and month
             $lastDateOfGivenMonth = Carbon::create($request->year, $request->month, 1)->endOfMonth();
            
           
             $expired_query->whereRaw(
                 "DATE_ADD(learner_detail.plan_end_date, INTERVAL ? DAY) <= ?", 
                 [$extend_day, $lastDateOfGivenMonth->toDateString()]
             );
         }
        
         $expired_seats = $expired_query->count();
         

         // till today Active slots
         if($total_booking!=0){
            $active_booking=$total_booking-$expired_seats;
         }else{
            $active_booking=0;
         }
        

       

        // till prevoues month total slots
        $prevMonth = $month - 1; // Calculate the previous month
        $prevYear = $year;
        
        if ($month == 1) { // Handle January case
            $prevMonth = 12; // Previous month is December
            $prevYear = $year - 1; // Move to the previous year
        }
        
        $firstDayOfPrevMonth = Carbon::create($prevYear, $prevMonth, 1)->startOfMonth()->toDateString(); // First day of the previous month
        $lastDayOfPrevMonth = Carbon::create($prevYear, $prevMonth, 1)->endOfMonth()->toDateString();   // Last day of the previous month
        
        $till_previous_month = Learner::selectRaw('learners.id AS learner_id, MAX(ld.plan_start_date) AS plan_start_date, MAX(ld.plan_end_date) AS plan_end_date')
        ->leftJoin('learner_detail AS ld', 'ld.learner_id', '=', 'learners.id')
        ->where('learners.library_id', getLibraryId())
        ->where(function ($query) use ($firstDayOfPrevMonth, $lastDayOfPrevMonth) {
            $query->whereDate('ld.plan_start_date', '<=', $lastDayOfPrevMonth)
                  ->whereDate('ld.plan_end_date', '>=', $firstDayOfPrevMonth);
        })
        ->groupBy('learners.id')->get();
      
        $previous_month = $till_previous_month->count();
       
      
        // this month booked slot

        $thismonth_booking = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
        ->where('learners.library_id', getLibraryId())->where('learner_detail.is_paid',1)
        ->where(function ($subQuery) use ( $month , $year) {
            $subQuery->whereYear('plan_start_date', $year)
            ->whereMonth('plan_start_date', $month);
           
        });
      
        $month_total_active_book = $thismonth_booking->count();


        // this month expired
      
        $thisexpired_query =Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
        ->where('learners.library_id', getLibraryId())
        ->where('learner_detail.is_paid', 1)
        ->where('learners.status', 0);

        if ($request->filled('year') && !$request->filled('month')) {
            // Filter by year, considering the extended days
            $thisexpired_query->where(function ($query) use ($request, $extend_day) {
                $query->whereYear(DB::raw("DATE_ADD(learner_detail.plan_end_date, INTERVAL $extend_day DAY)"), $request->year);
            });
        } elseif ($request->filled('year') && $request->filled('month')) {
            // Filter by year and month, considering the extended days
            $thisexpired_query->where(function ($query) use ($request, $extend_day) {
                $query->whereYear(DB::raw("DATE_ADD(learner_detail.plan_end_date, INTERVAL $extend_day DAY)"), $request->year)
                    ->whereMonth(DB::raw("DATE_ADD(learner_detail.plan_end_date, INTERVAL $extend_day DAY)"), $request->month);
            });
        }

        $month_all_expired = $thisexpired_query->count();

   

        // this month total slot
        $thismonth_total_book=$month_all_expired+$month_total_active_book;

        // Define the base query for learner_operations_log with common filters applied
        $baseQuery = DB::table('learner_operations_log')
        ->select(DB::raw('COUNT(*) as total_renew_count'))
        ->where('library_id', getLibraryId())
        ->when($request->filled('year') && !$request->filled('month'), function ($query) use ($request) {
            return $query->whereYear('created_at', $request->year);
        })
        ->when($request->filled('year') && $request->filled('month'), function ($query) use ($request) {
            return $query->whereYear('created_at', $request->year)
                        ->whereMonth('created_at', $request->month);
        }) ->groupBy('learner_id', DB::raw('DATE(created_at)'));

        // Clone the base query and apply specific filters for each operation
        $swap_seat = (clone $baseQuery)
        ->where('operation', 'swapseat')
        ->get()
        ->count();

        $learnerUpgrade = (clone $baseQuery)
        ->where('operation', 'learnerUpgrade')
        ->get()
        ->count();

        $reactive = (clone $baseQuery)
        ->where('operation', 'reactive')
        ->get()
        ->count();

        $renew = (clone $baseQuery)
        ->where('operation', 'renewSeat')
        ->get()
        ->count();

        $close_seat = (clone $baseQuery)
        ->where('operation', 'closeSeat')
        ->get()
        ->count();

        $delete_seat = (clone $baseQuery)
        ->where('operation', 'deleteSeat')
        ->get()
        ->count();
        $paidQuery=Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
        ->where('learners.library_id',getLibraryId())->where('learner_detail.is_paid',1)
        ->where(function ($subQuery) use ( $month , $year) {
            $subQuery->whereYear('plan_start_date', $year)
            ->whereMonth('plan_start_date', $month);
           
        });
        // Clone the base query for each payment mode count
        $online_paid = (clone $paidQuery)->where('learner_detail.payment_mode', 1)->count();
        $offline_paid = (clone $paidQuery)->where('learner_detail.payment_mode', 2)->count();
        $other_paid =(clone $paidQuery)->where('learner_detail.payment_mode', 3)->count();
       
      
        $plan_wise_booking = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
        ->where('learners.library_id', getLibraryId())
        ->where(function ($subQuery) use ($startOfGivenMonth, $endOfGivenMonth) {
            $subQuery->where('plan_start_date', '<=', $endOfGivenMonth)
                ->where('plan_end_date', '>=', $startOfGivenMonth);
        })
        ->whereRaw(
            "NOT DATE_ADD(learner_detail.plan_end_date, INTERVAL ? DAY) <= ?", 
            [$extend_day, $lastDateOfGivenMonth->toDateString()]
        )
        ->selectRaw('
            learner_detail.plan_type_id,
            COUNT(DISTINCT learner_detail.learner_id) as booking,
            MAX(plan_start_date) as max_plan_start_date,
            MAX(plan_end_date) as max_plan_end_date
        ')
        ->groupBy('learner_detail.plan_type_id')
        ->orderBy('max_plan_start_date', 'asc')
        ->with('planType') 
        ->get();

         $data = [];
         foreach ($plan_wise_booking as $booking) {
             $data[] = [
                 'plan_type_id' => $booking->plan_type_id,
                 'booking' => $booking->booking,
                 'plan_type_name' => $booking->planType ? $booking->planType->name : 'Unknown' 
             ];
         }

        
           //plantype wise revenue
         $planTypeWiseRevenue = LearnerDetail::leftJoin('plans', 'plans.id', '=', 'learner_detail.plan_id')
         ->where('learner_detail.is_paid', 1)
         ->where('learner_detail.library_id', getLibraryId())
         ->when($request->filled('year') && !$request->filled('month'), function ($query) use ($request) {
             // Filter by year only
                 $year = $request->year;
                 return $query->where(function ($q) use ($year) {
                     $q->whereYear('plan_start_date', '<=', $year)
                     ->whereYear('plan_end_date', '>=', $year);
                 });
            
         })
         ->when($request->filled('year') && $request->filled('month'), function ($query) use ($request) {
            
            $year = $request->year;
             $month = $request->month;
             $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
             $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
         
                 return $query->where(function ($q) use ($startOfMonth, $endOfMonth) {
                     $q->where('plan_start_date', '<=', $endOfMonth)
                     ->where('plan_end_date', '>=', $startOfMonth);
                 });
            
         })
         
         ->groupBy('plan_type_id')
         ->selectRaw('ROUND(SUM(learner_detail.plan_price_id / plans.plan_id), 2) as revenue, learner_detail.plan_type_id')
         ->with('planType')
         ->get();
    
        // Prepare data for response for graph
   
        $bookinglabels = $plan_wise_booking->map(function ($booking) {
            return $booking->planType->name ?? 'N/A'; // or any default value
        })->toArray();
 
       
        $bookingcount = $plan_wise_booking->pluck('booking')->toArray(); 


        // Prepare labels and data for revenue
        $revenueLabels = $planTypeWiseRevenue->pluck('planType.name')->toArray();
        $revenueData = $planTypeWiseRevenue->pluck('revenue')->toArray();

        //recenue expense div

        $expense_query = DB::table('monthly_expense')
        ->where('library_id',getLibraryId());
        
        if ($request->filled('year') && !$request->filled('month')) {
            // If year is selected, filter by year
            $expense_query->whereYear('year', $request->year);
        } elseif ($request->filled('year') && $request->filled('month')) {
            // If year and month are selected, filter by both year and month
            $expense_query->whereYear('year', $request->year)
                ->whereMonth('month', $request->month);
        }
        
        $expenses = $expense_query->selectRaw('year, month, SUM(amount) as total_expense')
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy(function ($expense) {
                return "{$expense->year}-{$expense->month}";
            });
        
        $revenue_query = LearnerDetail::withoutGlobalScopes()
            ->leftJoin('plans', 'plans.id', '=', 'learner_detail.plan_id')
            ->where('learner_detail.is_paid', 1)
            ->where('learner_detail.library_id',getLibraryId());
        
        if ($request->filled('year') && !$request->filled('month')) {
            // If year is selected, fetch records that overlap within the year
            $startOfYear = Carbon::create($request->year, 1, 1);
            $endOfYear = Carbon::create($request->year, 12, 31);
            $revenue_query->where(function ($query) use ($startOfYear, $endOfYear) {
                $query->whereBetween('plan_start_date', [$startOfYear, $endOfYear])
                    ->orWhereBetween('plan_end_date', [$startOfYear, $endOfYear]);
            });
        } elseif ($request->filled('year') && $request->filled('month')) {
            // If year and month are selected, fetch records that overlap within the month
            $startOfMonth = Carbon::create($request->year, $request->month, 1);
            $endOfMonth = $startOfMonth->copy()->endOfMonth();
            $revenue_query->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->where('plan_start_date', '<=', $endOfMonth)
                    ->where('plan_end_date', '>=', $startOfMonth);
            });
        }
        
        $learners = $revenue_query->select('plan_start_date', 'plan_end_date', 'plan_price_id', 'plans.plan_id as planId')->get();
        // Calculate Revenue
        $revenues = [];
        foreach ($learners as $learner) {
            $start_date = Carbon::parse($learner->plan_start_date);
            $end_date = Carbon::parse($learner->plan_end_date);
        
            $monthly_revenue = $learner->plan_price_id /  $learner->planId; // planID is a month duration.
        
            while ($start_date <= $end_date) {
                $year = $start_date->year;
                $month = $start_date->month;
        
                // Filter based on selected year and month
                if ($request->filled('year') && $request->filled('month')) {
                    if ($year == $request->year && $month == $request->month) {
                        $key = "{$year}-{$month}";
        
                        if (!isset($revenues[$key])) {
                            $revenues[$key] = [
                                'year' => $year,
                                'month' => $month,
                                'monthly_revenue' => 0,
                                'total_revenue' => 0,
                            ];
                        }
        
                        $revenues[$key]['monthly_revenue'] += $monthly_revenue;
                       

                        // $revenues[$key]['total_revenue'] += $learner->plan_price_id;
                    }
                } elseif ($request->filled('year') && !$request->filled('month')) {
                    // If only year is selected, filter by year
                    if ($year == $request->year) {
                        $key = "{$year}-{$month}";
        
                        if (!isset($revenues[$key])) {
                            $revenues[$key] = [
                                'year' => $year,
                                'month' => $month,
                                'monthly_revenue' => 0,
                                'total_revenue' => 0,
                            ];
                        }
        
                        $revenues[$key]['monthly_revenue'] += $monthly_revenue;
                       
                        // $revenues[$key]['total_revenue'] += $learner->plan_price_id;
                    }
                }
                
                $start_date->addMonth();
            }
        }
        
        // Combine Revenue and Expense
        $revenu_expense = [];
        foreach ($revenues as $key => $revenue) {
            [$year, $month] = explode('-', $key);
        
            $expense = $expenses->get($key);
            $totalExpense = $expense ? $expense->total_expense : 0;
        
            $monthlyRevenue = round($revenue['monthly_revenue'], 2);
            $trans = LearnerTransaction::whereYear('paid_date', $year)
            ->whereMonth('paid_date', $month)
            ->selectRaw('SUM(paid_amount) as total_revenue')
            ->groupByRaw('YEAR(paid_date), MONTH(paid_date)')
            ->first();
        
            $monthly_total_revenue = $trans->total_revenue ?? 0;
        

            $totalRevenue = round($monthly_total_revenue, 2);
            $netProfit = round($monthlyRevenue - $totalExpense, 2);
        
            $revenu_expense[] = [
                'year' => $year,
                'month' => Carbon::create($year, $month, 1)->format('F'),
                'totalRevenue' => $totalRevenue,
                'monthlyRevenue' => $monthlyRevenue,
                'totalExpense' => $totalExpense,
                'netProfit' => $netProfit,
            ];
        }
        
        
        return response()->json([
            'highlights' => [
                //first div
                'total_seat' => $total_seats,
                'booked_seat' => $booked_seats,
                'available_seat'=>$availble_seats,
                //second div
                'total_booking' => $total_booking,
                'active_booking' => $active_booking,
                'previous_month' => $previous_month,
                'expired_seats' => $expired_seats,

                // third div
                'thismonth_total_book'=>$thismonth_total_book,
                'month_all_expired'=>$month_all_expired,
                'month_total_active_book'=>$month_total_active_book,

                'expired_in_five' => $expired_in_five,
                'extended_seats' => $extended_seats,
                'delete_seat' => $delete_seat,
                'close_seat'=>$close_seat,

                'online_paid' => $online_paid,
                'offline_paid' => $offline_paid,
                'other_paid' => $other_paid,

                'renew_seat' => $renew,
                'swap_seat' => $swap_seat,
                'learnerUpgrade' => $learnerUpgrade,
                'reactive' => $reactive,
              
            ],
        
            'plan_wise_booking' => $data,
            'planTypeWiseRevenue' => [
                'labels' => $revenueLabels,
                'data' => $revenueData,
            ],
            'planTypeWiseCount' => [
                'labels' => $bookinglabels,
                'data' => $bookingcount,
            ],

            'revenu_expense' => $revenu_expense,
        ]);
    }

  

    public function viewSeats(Request $request)
    {
        $type = $request->get('type');
        $dateRange = $request->get('date_range');

        $extend_days_data = Hour::where('library_id',getLibraryId())->first();
        $extend_day = $extend_days_data ? $extend_days_data->extend_days : 0;
        $extend_days=Hour::select('extend_days')->first();
        if($extend_days){
            $extendDay=$extend_days->extend_days;
        }else{
            $extendDay=0;
        }
        
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
       
        $today = Carbon::now()->format('Y-m-d');
        $fiveDaysLater = Carbon::now()->addDays(5)->format('Y-m-d');

        $query = LearnerDetail::with(['plan', 'planType', 'seat', 'learner']);
    
        if ($request->filled('year') && !$request->filled('month')) {
            // Check for year only
            $givenYear = $request->year;
        
            $query->whereYear('plan_start_date', '<=', $givenYear)
                ->whereYear('plan_end_date', '>=', $givenYear);
        } elseif ($request->filled('year') && $request->filled('month')) {
            // Check for year and month
            $givenYear = $request->year;
            $givenMonth = $request->month;
        
            $startOfGivenMonth = Carbon::create($givenYear, $givenMonth, 1)->startOfMonth();
            $endOfGivenMonth = Carbon::create($givenYear, $givenMonth, 1)->endOfMonth();
        
            $query->where(function ($subQuery) use ($startOfGivenMonth, $endOfGivenMonth) {
                $subQuery->where('plan_start_date', '<=', $endOfGivenMonth)
                    ->where('plan_end_date', '>=', $startOfGivenMonth);
            });
        }

        $query_total =$this->getLearnersByLibrary()
       
        ->distinct('learner_detail.learner_id')->with(['plan', 'planType', 'learnerDetails']);
       
        if ($request->filled('year') && !$request->filled('month')) {
            // Check for year only
            $givenYear = $request->year;
        
            $query_total->whereYear('plan_start_date', '<=', $givenYear)
                ->whereYear('plan_end_date', '>=', $givenYear);
        } elseif ($request->filled('year') && $request->filled('month')) {
            // Check for year and month
            $givenYear = $request->year;
            $givenMonth = $request->month;
        
            $startOfGivenMonth = Carbon::create($givenYear, $givenMonth, 1)->startOfMonth();
            $endOfGivenMonth = Carbon::create($givenYear, $givenMonth, 1)->endOfMonth();
        
            $query_total->where(function ($subQuery) use ($startOfGivenMonth, $endOfGivenMonth) {
                $subQuery->where('plan_start_date', '<=', $endOfGivenMonth)
                    ->where('plan_end_date', '>=', $startOfGivenMonth);
            });
        }
        $startDateOfGivenMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $startOfGivenMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $expired_query = $this->getLearnersByLibrary()
        ->where('learner_detail.is_paid', 1)
        ->where('learners.status', 0)
        ->where('learner_detail.plan_end_date', '>=', $startDateOfGivenMonth)
        ->with(['plan', 'planType', 'learnerDetails']);
        
        if ($request->filled('year') && !$request->filled('month')) {
            // Filter by year only
            $expired_query->whereRaw(
                "YEAR(DATE_ADD(learner_detail.plan_end_date, INTERVAL ? DAY)) <= ?", 
                [$extend_day, $request->year]
            );
        } elseif ($request->filled('year') && $request->filled('month')) {
            // Filter by year and month
            $lastDateOfGivenMonth = Carbon::create($request->year, $request->month, 1)->endOfMonth();
        
            $expired_query->whereRaw(
                "DATE_ADD(learner_detail.plan_end_date, INTERVAL ? DAY) <= ?", 
                [$extend_day, $lastDateOfGivenMonth]
            );
        }
            
        $baseQuery = DB::table('learner_operations_log')
        ->select(
            'learner_id',
            DB::raw('MIN(learner_detail_id) as learner_detail_id'),
            DB::raw('MIN(library_id) as library_id'),
            DB::raw('DATE(created_at) as operation_date'),
            DB::raw('GROUP_CONCAT(DISTINCT operation) as operation')
        )
        ->where('library_id',getLibraryId())
        ->when($request->filled('year') && !$request->filled('month'), function ($query) use ($request) {
            return $query->whereYear('created_at', $request->year);
        })
        ->when($request->filled('year') && $request->filled('month'), function ($query) use ($request) {
            return $query->whereYear('created_at', $request->year)
                        ->whereMonth('created_at', $request->month);
        })
        ->groupBy('learner_id', DB::raw('DATE(created_at)'));

        $thismonth_booking  = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
        ->where('learners.library_id', getLibraryId())->where('learner_detail.is_paid',1)
        ->where(function ($subQuery) use ( $month , $year) {
            $subQuery->whereYear('plan_start_date', $year)
            ->whereMonth('plan_start_date', $month);
           
        });

        $thisexpired_query =$this->getLearnersByLibrary()
        ->where('learner_detail.is_paid', 1)
        ->where('learners.status', 0)
        ->where('learner_detail.plan_end_date', '>=', $startDateOfGivenMonth)
        ->with(['plan', 'planType', 'learnerDetails']);

        if ($request->filled('year') && !$request->filled('month')) {
            // Filter by year, considering the extended days
            $thisexpired_query->where(function ($query) use ($request, $extend_day) {
                $query->whereYear(DB::raw("DATE_ADD(learner_detail.plan_end_date, INTERVAL $extend_day DAY)"), $request->year);
            });
        } elseif ($request->filled('year') && $request->filled('month')) {
            // Filter by year and month, considering the extended days
            $thisexpired_query->where(function ($query) use ($request, $extend_day) {
                $query->whereYear(DB::raw("DATE_ADD(learner_detail.plan_end_date, INTERVAL $extend_day DAY)"), $request->year)
                    ->whereMonth(DB::raw("DATE_ADD(learner_detail.plan_end_date, INTERVAL $extend_day DAY)"), $request->month);
            });
        }
          
        switch ($type) {
            case 'total_booking':
                // till total slot
                // $query_total->groupBy('learner_detail.learner_id')
                // ->selectRaw('
                //     learner_detail.learner_id,
                //     learners.*,
                //     MAX(plan_start_date) as max_plan_start_date,
                //     MAX(plan_end_date) as max_plan_end_date
                // ')
                // ->orderBy('max_plan_start_date', 'asc');
                // $result=$query_total->get();
                $query_total = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
                ->with(['plan', 'planType', 'learnerDetails'])
                ->where('learners.library_id', getLibraryId())
                ->where(function ($subQuery) use ($startOfGivenMonth, $endOfGivenMonth) {
                    $subQuery->where('plan_start_date', '<=', $endOfGivenMonth)
                             ->where('plan_end_date', '>=', $startOfGivenMonth);
                })
                ->selectRaw('
                    learner_detail.learner_id,
                    learners.id,
                    learners.name,
                    learners.email,
                    learners.seat_no,
                    learners.dob,
                    learners.mobile,
                    MAX(plan_start_date) as max_plan_start_date,
                    MAX(plan_end_date) as max_plan_end_date
                ')
               
                ->groupBy('learner_detail.learner_id', 'learners.id', 'learners.name', 'learners.email','learners.seat_no','learners.dob','learners.mobile') // Add all required columns here
                ->orderBy('max_plan_start_date', 'asc');
               
               $result=$query_total->get();
            
                break;
            
            case 'expired_seats':
                //till expired slots
                $result = $expired_query->get();
                
                break;
            case 'active_booking':
                // till active slot
                $totalLearners =  $query_total
                ->groupBy('learner_detail.learner_id', 'learners.id', 'learners.name', 'learners.email','learners.seat_no','learners.dob','learners.mobile') 
                ->selectRaw('
                    learner_detail.learner_id,
                    learners.id,
                    learners.name,
                    learners.email,
                    learners.seat_no,
                    learners.dob,
                    learners.mobile,
                    MAX(plan_start_date) as max_plan_start_date,
                    MAX(plan_end_date) as max_plan_end_date
                ')
                ->orderBy('max_plan_start_date', 'asc')->get(); // Total bookings
                $expiredLearners = $expired_query->get(); // Expired bookings
                $result =$totalLearners->diff($expiredLearners);
                break;
            case 'booing_slot':
                    // this month bookes
                $result = $thismonth_booking->get();
                break;
            case 'expire_booking_slot':
                // this month expire
                
                $result = $thisexpired_query->get();
                break;

            case 'thisbooking_slot':
                // this month total
                $thisMonthBooking = $thismonth_booking->get(); // Collection of this month's bookings
                $thisExpiredQuery = $thisexpired_query->get(); // Collection of this month's expired bookings
      
                
                $result = $thisMonthBooking->merge($thisExpiredQuery);
                
                break;
            case 'till_previous_book':
                // till previous month active
                $till_previous_month = $this->getLearnersByLibrary()
                    ->distinct('learner_detail.learner_id')->with(['plan', 'planType', 'learnerDetails']);
                
                if ($request->filled('year') && !$request->filled('month')) {
                    // If only the year is provided
                    $givenYear = $request->year;
                    $currentMonth = Carbon::now()->month;
                    $currentYear = Carbon::now()->year;
                
                    if ($givenYear == $currentYear) {
                        // For the current year, calculate the start and end of the previous month
                        $startOfPreviousMonth = Carbon::create($givenYear, $currentMonth, 1)->subMonth()->startOfMonth();
                        $endOfPreviousMonth = Carbon::create($givenYear, $currentMonth, 1)->subMonth()->endOfMonth();
                    } else {
                        // For past years, consider December as the "previous month"
                        $startOfPreviousMonth = Carbon::create($givenYear, 12, 1)->startOfMonth();
                        $endOfPreviousMonth = Carbon::create($givenYear, 12, 1)->endOfMonth();
                    }
                
                    $till_previous_month->where(function ($query) use ($startOfPreviousMonth, $endOfPreviousMonth, $givenYear) {
                        $currentMonth = Carbon::now()->month;
                        $query->where('plan_start_date', '<=', $endOfPreviousMonth)
                            ->where('plan_end_date', '>=', $startOfPreviousMonth)
                            ->whereRaw("DATE_FORMAT(plan_end_date, '%Y-%m') != ?", [sprintf('%04d-%02d', $givenYear, $currentMonth)]);
                    });
                } elseif ($request->filled('year') && $request->filled('month')) {
                    // If both year and month are provided
                    $givenYear = $request->year;
                    $givenMonth = $request->month;
                
                    $startOfPreviousMonth = Carbon::create($givenYear, $givenMonth, 1)->subMonth()->startOfMonth();
                    $endOfPreviousMonth = Carbon::create($givenYear, $givenMonth, 1)->subMonth()->endOfMonth();
                
                    $till_previous_month->where(function ($query) use ($startOfPreviousMonth, $endOfPreviousMonth, $givenYear, $givenMonth) {
                        $query->where('plan_start_date', '<=', $endOfPreviousMonth)
                            ->where('plan_end_date', '>=', $startOfPreviousMonth)
                            ->whereRaw("DATE_FORMAT(plan_end_date, '%Y-%m') != ?", [sprintf('%04d-%02d', $givenYear, $givenMonth)]);
                    });
                }

                $previous_month = $till_previous_month->get();
                
            case 'online_paid':
                $result = (clone $thismonth_booking)->where('learner_detail.payment_mode', 1)->get();
                break;

            case 'offline_paid':
                $result = (clone $thismonth_booking)->where('learner_detail.payment_mode', 2)->get();
                break;

            case 'other_paid':
                //pay later
                $result = (clone $query)->where('learner_detail.payment_mode', 3)->get();
                break;
            case 'expired_in_five':
               
           
                $result = Learner::join('learner_detail', 'learner_detail.learner_id', '=', 'learners.id') // Join the learner_detail table
                ->whereHas('learnerDetails', function ($query) use ($today, $fiveDaysLater) {
                    $query->whereBetween('plan_end_date', [$today, $fiveDaysLater]);
                })
                
                ->selectRaw('
                learner_detail.learner_id,
                learners.id,
                learners.name,
                learners.email,
                learners.seat_no,
                learners.dob,
                learners.mobile,
                MAX(plan_start_date) as max_plan_start_date,
                MAX(plan_end_date) as max_plan_end_date
            ')
           
            ->groupBy('learner_detail.learner_id', 'learners.id', 'learners.name', 'learners.email','learners.seat_no','learners.dob','learners.mobile') 
                ->orderBy('max_plan_start_date', 'asc')
                ->get();

                break;
            case 'extended_seat':
                $result = $this->getLearnersByLibrary()
                ->where('learner_detail.is_paid',1)
                ->where('learners.status',1)
                ->where('learner_detail.status',1)
                ->where('learner_detail.plan_end_date', '<', date('Y-m-d'))
                ->whereRaw("DATE_ADD(learner_detail.plan_end_date, INTERVAL ? DAY) >= CURDATE()", [$extend_day])
                ->with(['plan', 'planType', 'learnerDetails']);
                break;
            case 'swap_seat':
                $result = (clone $baseQuery)->where('operation', 'swapseat')->get();
                break;
            case 'learnerUpgrade':
                $result = (clone $baseQuery)->where('operation', 'learnerUpgrade')->get();
                break;
            case 'reactive_seat':
                $result = (clone $baseQuery)->where('operation', 'reactive')->get();
                break;
            case 'renew_seat':
                $result = (clone $baseQuery)->where('operation', 'renewSeat')->get();
                break;
            case 'close_seat':
                $result = (clone $baseQuery)->where('operation', 'closeSeat')->get();
                break;
            case 'delete_seat':
                $result = (clone $baseQuery)->where('operation', 'deleteSeat')->get();
                break;
            
        }
       
        
        return view('learner.list-view', compact('result', 'type','extendDay'));
        
    }

    public function libraryView(Request $request){
        $type = $request->get('type');
        switch ($type) {
            case 'total':
               
                $result=Library::get();

            break;
            case 'paid_registration':
                $result=Library::leftJoin('library_transactions','library.id','=','library_transactions.library_id')->where('library.is_paid',1)->get();
             
            break;
            case 'unpaid_registration':
                $result=Library::leftJoin('library_transactions','library.id','=','library_transactions.library_id')->where('library.is_paid',0)->get();
            
            break;
            case 'pending_renew':
                $result=Library::leftJoin('library_transactions','libraries.id','=','library_transactions.library_id')->where('libraries.is_paid',1)->where('end_date','<=',date('Y-m-d'))->get();

            break;
        }
        return view('library.count-list', compact('result', 'type'));
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
