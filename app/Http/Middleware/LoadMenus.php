<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\Library;
use App\Models\LibrarySetting;
use App\Models\LibraryTransaction;
use App\Models\CustomNotificationTemplate;
use App\Models\Menu;
use App\Models\NotificationChannelSetting;
use App\Models\NotificationTemplate;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlanType;
use App\Models\Seat;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use DB;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class LoadMenus
{
    /**
     * Cache TTL (seconds) for the per-library dashboard data computed below.
     * Short enough that subscription/seat numbers never feel stale, long enough
     * to collapse the ~35 queries this middleware used to run on every single
     * request (every page, every AJAX call) down to roughly once per window.
     */
    const CACHE_TTL_SECONDS = 30;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    public function handle($request, Closure $next)
    {
        $checkSub = false;
        $ispaid = false;
        $iscomp = false;
        $isProfile = false;
        $isEmailVeri = false;

        $menus = collect();
        $guardName = null;

        if (Auth::guard('web')->check()) {
            $guardName = 'web';
            $user = Auth::guard('web')->user();
            $menus = Menu::where('status', 1)->where(function ($query) {
                $query->where('guard', 'web')
                    ->orWhereNull('guard');
            })->with('children')->orderBy('order')->get();
        } elseif (Auth::guard('library')->check() || Auth::guard('library_user')->check()) {
            $guardName = Auth::guard('library')->check() ? 'library' : 'library_user';
            $library = null;

            if (Auth::guard('library')->check()) {
                $library = Auth::guard('library')->user();
            } elseif (Auth::guard('library_user')->check()) {
                $library = Library::find(Auth::guard('library_user')->user()->library_id);
            }

            if ($library) {
                $today = Carbon::today()->toDateString();

                $showDailyPopup = $library->last_status_confirmed_date !== $today;
                 View::share('showDailyPopup', $showDailyPopup);
            }


            $menus = Menu::where('status', 1)->where(function ($query) {
                $query->where('guard', 'library')
                    ->orWhereNull('guard');
            })->with('children')->orderBy('order')->get();
        } elseif (Auth::guard('learner')->check()) {
            $guardName = 'learner';
            $user = Auth::guard('learner')->user();
            $menus = Menu::where('status', 1)->where(function ($query) {
                $query->where('guard', 'learner')
                    ->orWhereNull('guard');
            })->with('children')->orderBy('order')->get();
        }

        view()->share('menus', $menus);

        $authUser = getAuthenticatedUser();

        if ($authUser) {
            $cacheKey = sprintf(
                'load_menus:%s:%s:%s:%s',
                $guardName,
                $authUser->id,
                getLibraryId(),
                getCurrentBranch()
            );

            $shared = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($guardName, $authUser) {
                return $this->buildSharedData($guardName, $authUser);
            });

            foreach ($shared as $shareKey => $shareValue) {
                View::share($shareKey, $shareValue);
            }
        }

        if ($authUser && Auth::guard('library')->check()) {
            $user = $authUser;
            $request->attributes->set('library_name', $user->library_name);
        }

        return $next($request);
    }

    /**
     * Computes every per-library/per-learner value the layout and its child
     * views rely on via View::share(). Wrapped in a short-TTL cache by the
     * caller — everything here used to run fresh on every single request.
     */
    protected function buildSharedData(?string $guardName, $authUser): array
    {
        $today = date('Y-m-d');

        // Was 4 separate exists() round-trips to the same `library` row — now 1.
        $libraryFlags = Library::where('id', getLibraryId())->first(['email_verified_at', 'is_paid', 'status', 'is_profile']);
        $isEmailVeri = $libraryFlags && $libraryFlags->email_verified_at !== null;
        $ispaid = $libraryFlags && (int) $libraryFlags->is_paid === 1;
        $iscomp = $libraryFlags && (int) $libraryFlags->status === 1;
        $isProfile = $libraryFlags && (int) $libraryFlags->is_profile === 1;

        $checkSub = LibraryTransaction::withoutGlobalScopes()->where('library_id', getLibraryId())->where('status', 1)->exists();

        $anyTranLib = LibraryTransaction::withoutGlobalScopes()->where('library_id', getLibraryId())->where('is_paid', 1)->exists();
        $value = LibraryTransaction::withoutGlobalScopes()->where('library_id', getLibraryId())->where('is_paid', 1)->orderBy('id', 'desc')->first();

        // 24-09-2025 we made changes in this (we change start_date into end_date)
        $is_renew_comp = LibraryTransaction::withoutGlobalScopes()->where('library_id', getLibraryId())
            ->where('is_paid', 1)
            ->where('status', 1)
            ->where('end_date', '>=', date('Y-m-d'))->exists();

        $is_renew = LibraryTransaction::withoutGlobalScopes()->where('library_id', getLibraryId())
            ->where('is_paid', 1)
            ->where('status', 0)
            ->where('end_date', '>=', date('Y-m-d'))
            ->exists();

        // End changes

        $librarydiffInDays = 0;
        $is_expire = false;

        if ($value) {
            $today = Carbon::today();
            $endDate = Carbon::parse($value->end_date);

            $librarydiffInDays = $today->diffInDays($endDate, false);

            if ($librarydiffInDays <= 5) {
                $is_expire = true;
            }
        }

        if ($is_renew) {
            $is_renew_val = LibraryTransaction::withoutGlobalScopes()->where('library_id', getLibraryId())
                ->where('is_paid', 1)
                ->where('status', 0)
                ->where('start_date', '>', date('Y-m-d'))->first();
            $today = Carbon::today();
            if ($is_renew_val) {
                $start_date = Carbon::parse($is_renew_val->start_date);
                $upcomingdiffInDays = $today->diffInDays($start_date);
            } else {
                $upcomingdiffInDays = null;
            }
        } else {
            $upcomingdiffInDays = null;
        }

        $today_renew = LibraryTransaction::withoutGlobalScopes()->where('library_id', getLibraryId())
            ->where('is_paid', 1)
            ->where('status', 0)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>', date('Y-m-d'))
            ->exists();

        $primary_color = null;

        $library_setting = LibrarySetting::where('library_id', $authUser->id)->first();

        if ($library_setting) {
            $primary_color = $library_setting->library_primary_color;
        }

        // learner remaining days count — only meaningful for the `learner` guard;
        // previously ran for every guard (library owners included), querying
        // learner_detail with the library's own id as a bogus learner_id.
        if ($guardName === 'learner') {
            $leraner = LearnerDetail::withoutGlobalScopes()->where('learner_id', $authUser->id)->where('learner_detail.status', 1)->leftJoin('plans', 'learner_detail.plan_id', '=', 'plans.id')->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->select('learner_detail.*', 'plan_types.name as plan_type_name', 'plans.name as plan_name', 'plan_types.start_time', 'plan_types.end_time')->first();
            $learner_current_library_extend = Hour::withoutGlobalScopes()->where('library_id', $authUser->library_id)->first();
            if ($leraner && $learner_current_library_extend) {
                $today = Carbon::today();
                $endDate = Carbon::parse($leraner->plan_end_date);
                $diffInDays = $today->diffInDays($endDate, false);
                $inextendDate = $endDate->copy()->addDays($learner_current_library_extend->extend_days); // Preserving the original $endDate
                $diffExtendDay = $today->diffInDays($inextendDate, false);
            } else {
                $diffExtendDay = 0;
                $diffInDays = 0;
            }
            $learner_is_renew = LearnerDetail::withoutGlobalScopes()->where('learner_id', $authUser->id)->where('status', 0)
                ->where('plan_start_date', '>=', date('Y-m-d'))
                ->exists();
        } else {
            $diffExtendDay = 0;
            $diffInDays = 0;
            $learner_is_renew = false;
        }
        //learner remainig days count
        $first_record = Hour::where('branch_id', getCurrentBranch())->first();
        $total_seats = $first_record ? $first_record->seats : 0;
        $total_hour = $first_record ? $first_record->hour : 0;

        $learnerExtendText = 'Extend Days are Active Now & Remaining Days are';

        $booked_seats = getUnavailableSeatCount();
        $availble_seats = getAvailableSeatCount();
        $genral_seat = LearnerDetail::where('is_paid', 1)->whereNull('seat_no')->count();
        $active_seat_count = Learner::where('library_id', getLibraryId())->where('status', 1)
            ->distinct()
            ->count();
        $extend_days_data = Hour::where('library_id', getLibraryId())->first();
        $extend_day = $extend_days_data ? $extend_days_data->extend_days : 0;
        $extended_seats = LearnerDetail::where('learner_detail.is_paid', 1)
            ->where('learner_detail.status', 1)
            ->where('learner_detail.plan_end_date', '<', date('Y-m-d'))
            ->whereRaw("DATE_ADD(learner_detail.plan_end_date, INTERVAL ? DAY) >= CURDATE()", [$extend_day])
            ->count();
        $expired_seat = Learner::where('library_id', getLibraryId())->where('status', 0)->count();

        $planTypeCounts = [];

        $planTypes = PlanType::withTrashed()->get();

        $countsByPlanType = LearnerDetail::where('status', 1)
            ->whereIn('plan_type_id', $planTypes->pluck('id'))
            ->selectRaw('plan_type_id, COUNT(*) as aggregate')
            ->groupBy('plan_type_id')
            ->pluck('aggregate', 'plan_type_id');

        foreach ($planTypes as $planType) {
            // Count learners with active status assigned to this plan_type_id
            $count = $countsByPlanType[$planType->id] ?? 0;

            // Generate abbreviation like FD, FH, SH, HS1, etc.
            $words = explode(' ', $planType->name);
            $abbr = '';

            foreach ($words as $word) {
                if (is_numeric($word)) {
                    $abbr .= $word; // Keep numbers as-is (e.g. Slot 1 → S1)
                } else {
                    $abbr .= strtoupper(substr($word, 0, 1));
                }
            }

            $planTypeCounts[] = [
                'id' => $planType->id,
                'name' => $planType->name,
                'abbr' => $abbr,
                'count' => $count,
            ];
        }

        $extend_days = Branch::where('library_id', $authUser->id)->select('extend_days')->first();
        if ($extend_days) {
            $extendDay = $extend_days->extend_days;
        } else {
            $extendDay = 0;
        }

        $libraryupdates = DB::table('updates')->whereNull('deleted_at')->where('guard', 'library')->get();
        $learnerupdates = DB::table('updates')->whereNull('deleted_at')->where('guard', 'learner')->get();
        $plans = Plan::where('library_id', getLibraryId())->get();

        if (getCurrentBranch() != 0 || getCurrentBranch() != null) {
            $totalSeats = Hour::where('branch_id', getCurrentBranch())->value('seats');
            $totalHour = Hour::where('branch_id', getCurrentBranch())->value('hour');
        } else {
            $totalSeats = Hour::where('library_id', getLibraryId())->SUM('seats');
            $totalHour = Hour::where('library_id', getLibraryId())->SUM('hour');
        }
        $usedSeats = LearnerDetail::select('seat_no', DB::raw('SUM(hour) as used_hours'))
            ->whereNotNull('seat_no')
            ->groupBy('seat_no')->where('status', 1)
            ->pluck('used_hours', 'seat_no'); // [seat_no => used_hours]

        $availableSeats = collect();
        $allSeats = collect(generateSeatNumbers());

        // Step 2: Loop through all seat numbers and apply logic
        for ($seatNo = 1; $seatNo <= $totalSeats; $seatNo++) {
            $usedHours = $usedSeats[$seatNo] ?? 0;

            if ($usedHours < $totalHour) {
                $availableSeats->push($seatNo);
            }
        }
        $newAvailableSeats = collect();

        for ($seatNo = 1; $seatNo <= $totalSeats; $seatNo++) {
            $usedHours = $usedSeats[$seatNo] ?? 0;

            if ($usedHours < $totalHour) {
                $seatInfo = $allSeats->firstWhere('main', $seatNo);

                if ($seatInfo) {
                    $newAvailableSeats->push($seatInfo);
                } else {
                    $newAvailableSeats->push([
                        'main' => $seatNo,
                        'display' => $seatNo,
                    ]);
                }
            }
        }

        if ($guardName === 'library' || $guardName === 'library_user') {
            $lib_extenday = Library::where('id', $authUser->id)->value('extend_days') ?? 0;
            $lib_enddate = LibraryTransaction::withoutGlobalScopes()->where('library_id', $authUser->id)->where('is_paid', 1)->latest('end_date')->value('end_date') ?? 0;

            if ($lib_enddate) { // only if there is an end date
                $lib_planEndDateWithExtension = Carbon::parse($lib_enddate)->addDays($lib_extenday);
                $diffInExtensionDays = $today->diffInDays($lib_planEndDateWithExtension, false);
                $inExtension_lib = $librarydiffInDays < 0 && $diffInExtensionDays >= 0;
            } else {
                $diffInExtensionDays = null;
                $inExtension_lib = false;
            }

            $notificationSetting = NotificationChannelSetting::where('branch_id', getCurrentBranch())
                ->select('waba_template_id', 'text_template_id')
                ->first();

            // Fetch WABA templates with operation name
            $wabaTemplates = NotificationTemplate::where('type', 'waba')->where('is_custom', 0)
                ->join('operations', 'operations.id', '=', 'notification_templates.operation_id')
                ->select(
                    'notification_templates.id',
                    'notification_templates.operation_id',
                    'notification_templates.template_name',
                    'notification_templates.template_message',
                    'operations.name as operation_name'
                )
                ->get();

            // Fetch Text templates with operation name
            $textTemplates = NotificationTemplate::where('type', 'text')->where('is_custom', 0)
                ->join('operations', 'operations.id', '=', 'notification_templates.operation_id')
                ->select(
                    'notification_templates.id',
                    'notification_templates.operation_id',
                    'notification_templates.template_name',
                    'notification_templates.template_message',
                    'operations.name as operation_name'
                )
                ->get();

            // Whether a free (is_paid=0) reminder template has content for WhatsApp/Text,
            // used to decide which options the free "Send Reminders Via" dropdown offers
            // (operation 11 = "Expired Reminder", the operation the reminder icons use).
            $reminderOperationId = 11;

            $freeWabaMessage = CustomNotificationTemplate::where('library_id', getLibraryId())
                ->where('operation_id', $reminderOperationId)
                ->where('type', 'waba')
                ->value('template_message')
                ?? NotificationTemplate::where('operation_id', $reminderOperationId)
                    ->where('type', 'waba')
                    ->where('is_paid', '0')
                    ->where('is_active', 1)
                    ->value('template_message');

            $freeTextMessage = CustomNotificationTemplate::where('library_id', getLibraryId())
                ->where('operation_id', $reminderOperationId)
                ->where('type', 'text')
                ->value('template_message')
                ?? NotificationTemplate::where('operation_id', $reminderOperationId)
                    ->where('type', 'text')
                    ->where('is_paid', '0')
                    ->where('is_active', 1)
                    ->value('template_message');

            $hasFreeWaba = trim((string) $freeWabaMessage) !== '';
            $hasFreeText = trim((string) $freeTextMessage) !== '';

            // For the dropdown year/month filter: was pulling every learner_detail
            // row ever created (withTrashed, no limit) just to walk each row's own
            // start->end span in PHP. A single MIN/MAX aggregate + one range fill
            // gives the same dropdown (may include a few extra empty months where
            // the old per-row walk left gaps) without the full-table pull.
            $dateRange = LearnerDetail::withTrashed()
                ->selectRaw('MIN(plan_start_date) as min_start, MAX(plan_end_date) as max_end')
                ->first();

            $months = [];
            if ($dateRange && $dateRange->min_start && $dateRange->max_end) {
                $start = Carbon::parse($dateRange->min_start)->startOfMonth();
                $end = Carbon::parse($dateRange->max_end)->startOfMonth();

                while ($start <= $end) {
                    $months[$start->year][$start->month] = $start->format('F');
                    $start->addMonth();
                }
            }
        } else {
            $diffInExtensionDays = '';
            $inExtension_lib = '';
            $lib_extenday = '';
            $wabaTemplates = collect();
            $textTemplates = collect();
            $months = [];
            $hasFreeWaba = false;
            $hasFreeText = false;
        }

        $exams = DB::table('exams')->get();

        return [
            'primary_color' => $primary_color,
            'checkSub' => $checkSub,
            'anyTranLib' => $anyTranLib,
            'ispaid' => $ispaid,
            'isProfile' => $isProfile,
            'isEmailVeri' => $isEmailVeri,
            'iscomp' => $iscomp,
            'librarydiffInDays' => $librarydiffInDays,
            'is_renew' => $is_renew,
            'is_renew_comp' => $is_renew_comp,
            'is_expire' => $is_expire,
            'today_renew' => $today_renew,
            'upcomingdiffInDays' => $upcomingdiffInDays,
            'learnerExtendText' => $learnerExtendText,
            'total_seats' => $total_seats,
            'total_hour' => $total_hour,
            'active_seat_count' => $active_seat_count,
            'expired_seat' => $expired_seat,
            'availble_seats' => $availble_seats,
            'booked_seats' => $booked_seats,
            'planTypeCounts' => $planTypeCounts,
            'genral_seat' => $genral_seat,
            'learnerupdates' => $learnerupdates,
            'newAvailableSeats' => $newAvailableSeats,
            'availableseats' => $availableSeats,
            'totalSeats' => $totalSeats,
            'exams' => $exams,
            'plans' => $plans,
            'extended_seats' => $extended_seats,
            'extendDay' => $extendDay,
            'diffExtendDay' => $diffExtendDay,
            'learner_is_renew' => $learner_is_renew,
            'diffInDays' => $diffInDays,
            'libraryupdates' => $libraryupdates,
            'diffInExtensionDays' => $diffInExtensionDays,
            'inExtension_lib' => $inExtension_lib,
            'lib_extenday' => $lib_extenday,
            'wabaTemplates' => $wabaTemplates,
            'textTemplates' => $textTemplates,
            'months' => $months,
            'hasFreeWaba' => $hasFreeWaba,
            'hasFreeText' => $hasFreeText,
        ];
    }

    public function updateLibraryStatus()
    {

        \Log::info('Start library status');
        $today = Carbon::today();
        $hourexist = Hour::withoutGlobalScopes()->where('library_id', getLibraryId())->count();
        $extendexist = Branch::where('library_id',getLibraryId())->whereNotNull('extend_days')->count();

        $plan = Plan::count();
        $plantype = PlanType::where('library_id', getLibraryId())->count();
        $planPrice = PlanPrice::withoutGlobalScopes()->where('library_id', getLibraryId())->count();

        $is_active = LibraryTransaction::withoutGlobalScopes()->where('library_id', getLibraryId())->where('is_paid', 1)->where('end_date', '>', $today->format('Y-m-d'))->exists();
        if ($hourexist > 0 && $extendexist > 0 && $plan > 0 && $plantype >= 1 && $planPrice >= 1 && $is_active) {
            $id = getLibraryId();
            $library = Library::findOrFail($id);
            $transaction=LibraryTransaction::withoutGlobalScopes()->where('library_id', getLibraryId())->where('is_paid', 1)->where('end_date', '>', $today->format('Y-m-d'))->latest()->first();

            if ($library->status != 1) {
                $library->status = 1;
                $library->library_type=$transaction->subscription;
                $library->save();
            }
        }
    }

    public function statusInactive()
    {

        $userId = getAuthenticatedUser()->id;
        $today = Carbon::now('Asia/Kolkata')->startOfDay();

        $extenday=Library::where('id', $userId)->value('extend_days') ?? 0;
        $enddate= LibraryTransaction::withoutGlobalScopes()->where('library_id', $userId)->where('is_paid', 1)->latest()->value('end_date')??0;
        $planEndDateWithExtension = Carbon::parse($enddate)->addDays($extenday);


        $is_renew = LibraryTransaction::withoutGlobalScopes()->where('library_id', getLibraryId())->where('is_paid', 1)->where('end_date', '>', $planEndDateWithExtension->format('Y-m-d'))->exists();


        \Log::info([
        'planEndDateWithExtension' => $planEndDateWithExtension->toDateString(),
        'today' => $today->toDateString(),
        'is_renew' => $is_renew,
        'app_timezone' => config('app.timezone'),
        ]);
         if ($planEndDateWithExtension->lt($today) && !$is_renew) {
            \Log::info('Start library statusInactive condition');
            Library::where('id', $userId)
                ->where('status', 1)
                ->update(['status' => 0, 'is_paid' => 0]);

            // Mark the expired transaction status as inactive
           LibraryTransaction::withoutGlobalScopes()
            ->where('library_id', $userId)
            ->where('is_paid', 1)
            ->where('status', 1)
            ->where(function ($query) use ($planEndDateWithExtension, $today) {
                $query->whereDate('end_date', '=', $planEndDateWithExtension->format('Y-m-d'))
                    ->orWhereDate('end_date', '<', $planEndDateWithExtension->format('Y-m-d'));
            })
            ->update(['status' => 0]);

        }
    }

}
