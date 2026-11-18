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

/** Learner portal (portal:learner): the student home page. Split out of the old DashboardController. */
class LearnerDashboardController extends Controller
{
    use LearnerQueryTrait;
    protected $libraryService;
    protected $learnerService;


    public function __construct(LibraryService $libraryService, LearnerService $learnerService)
    {
        
        $this->libraryService = $libraryService;
        $this->learnerService = $learnerService;
    }
    public function learnerDashboard(){
        $user=Auth::user();
       
        $learners = LearnerDetail::withoutGlobalScopes()->where('learner_id',$user->id)->leftJoin('plans','learner_detail.plan_id','=','plans.id')->leftJoin('plan_types','learner_detail.plan_type_id','=','plan_types.id')->select('learner_detail.*','plans.name as plan_name','plan_types.name as plan_type_name')->get();
       
       $library_name=Branch::where('id',Auth::user()->branch_id)->select('name as library_name','features')->first();
  
       $learner_request = DB::table('learner_request')->where('learner_id', getAuthenticatedUser()->id)->get();
       $featuresArray = $library_name->features ? (is_array($library_name->features) ? $library_name->features : json_decode($library_name->features, true)) : [];

       $features = Feature::whereIn('id', $featuresArray)->get();
       
        // The route is already behind auth:learner; without a role the page used to come back empty.
        // study corner: exam goal + countdown, stopwatch stats, daily GK quiz, library quizzes
        $study = app(\App\Services\StudyPortalService::class);
        $learner = Auth::guard('learner')->user();
        $goal = $study->goal($learner);
        $stats = $study->stats($learner);
        $gkAttempt = $study->todayAttempt($learner);
        $gkStreak = $study->gkStreak($learner);
        $quizOverview = $study->overview($learner);
        $libraryQuizzes = $study->libraryQuizzes($learner)->take(3);
        // the library's running global offer, shown as a banner (docs/GLOBAL_DISCOUNT.md)
        $offer = app(\App\Services\GlobalDiscountService::class)->active($learner->library_id);

        // hours spent in the library, from attendance punches
        $libHours = app(\App\Services\LearnerAttendanceService::class)->studyHours($learner);

        // No goal yet: show the step-by-step goal setup on a clean page first.
        // "Later" hides it for this login session.
        if (request()->query('goal') === 'later') {
            session(['learner_goal_later' => true]);
        }
        $showGoalSetup = ! $goal && ! session('learner_goal_later') && request()->query('goal') !== 'later';
        $exams = $showGoalSetup ? \App\Models\Exam::orderBy('name')->get(['id', 'name']) : collect();

        return view('dashboard.learner', compact('learners', 'library_name', 'features', 'goal', 'stats', 'gkAttempt', 'gkStreak', 'quizOverview', 'libraryQuizzes', 'offer', 'libHours', 'showGoalSetup', 'exams'));
    }
}
