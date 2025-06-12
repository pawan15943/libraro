<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\Library;
use App\Models\LibrarySetting;
use App\Models\LibraryTransaction;
use App\Models\Menu;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlanType;
use App\Models\Seat;
use Closure;
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

        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            $menus = Menu::where('status', 1)->where(function ($query) {
                $query->where('guard', 'web')
                    ->orWhereNull('guard');
            })->with('children')->orderBy('order')->get();
        } elseif (Auth::guard('library')->check() || Auth::guard('library_user')->check()) {


           
            $menus = Menu::where('status', 1)->where(function ($query) {
                $query->where('guard', 'library')
                    ->orWhereNull('guard');
            })->with('children')->orderBy('order')->get();
        } elseif (Auth::guard('learner')->check()) {
            $user = Auth::guard('learner')->user();
            $menus = Menu::where('status', 1)->where(function ($query) {
                $query->where('guard', 'learner')
                    ->orWhereNull('guard');
            })->with('children')->orderBy('order')->get();
        }
      
        view()->share('menus', $menus);
       
        if (getAuthenticatedUser()) {
            $today = date('Y-m-d');
            // for library use variable

            $isEmailVeri = Library::where('id', getLibraryId())->whereNotNull('email_verified_at')->exists();
            $checkSub = LibraryTransaction::withoutGlobalScopes()->where('library_id', getLibraryId())->where('status', 1)->exists();
            $ispaid = Library::where('id', getLibraryId())->where('is_paid', 1)->exists();
            $iscomp = Library::where('id', getLibraryId())->where('status', 1)->exists();
            $isProfile = Library::where('id', getLibraryId())->where('is_profile', 1)->exists();
            
            $value = LibraryTransaction::withoutGlobalScopes()->where('library_id',  getLibraryId())->where('is_paid', 1)->orderBy('id', 'desc')->first();

            $is_renew_comp = LibraryTransaction::withoutGlobalScopes()->where('library_id', getLibraryId())
                ->where('is_paid', 1)
                ->where('status', 1)
                ->where('start_date', '>=', date('Y-m-d'))->exists();
            $is_renew = LibraryTransaction::withoutGlobalScopes()->where('library_id', getLibraryId())
                ->where('is_paid', 1)
                ->where('status', 0)
                ->where('start_date', '>=', date('Y-m-d'))
                ->exists();
                
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
                $is_renew_val = LibraryTransaction::withoutGlobalScopes()->where('library_id',getLibraryId())
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
            
            $library_setting = LibrarySetting::where('library_id', getAuthenticatedUser()->id)->first();

            if ($library_setting) {
                $primary_color = $library_setting->library_primary_color;
            }
            
            // for library use variable
            //learner remainig days count

            $leraner=LearnerDetail::withoutGlobalScopes()->where('learner_id',getAuthenticatedUser()->id)->where('learner_detail.status',1)->leftJoin('plans','learner_detail.plan_id','=','plans.id')->leftJoin('plan_types','learner_detail.plan_type_id','=','plan_types.id')->select('learner_detail.*','plan_types.name as plan_type_name','plans.name as plan_name','plan_types.start_time','plan_types.end_time')->first();
            $learner_current_library_extend=Hour::withoutGlobalScopes()->where('library_id',getAuthenticatedUser()->library_id)->first();
            if($leraner && $learner_current_library_extend){
                $today = Carbon::today();
                $endDate = Carbon::parse($leraner->plan_end_date);
                $diffInDays = $today->diffInDays($endDate, false);
                $inextendDate = $endDate->copy()->addDays($learner_current_library_extend->extend_days); // Preserving the original $endDate
                $diffExtendDay = $today->diffInDays($inextendDate, false);
            }else{
                $diffExtendDay=0;
                $diffInDays = 0; 
            }
            $learner_is_renew=LearnerDetail::withoutGlobalScopes()->where('learner_id',getAuthenticatedUser()->id) ->where('status', 0)
            ->where('plan_start_date', '>=', date('Y-m-d'))
            ->exists();
            //learner remainig days count
            $first_record = Hour::where('branch_id',getCurrentBranch())->first(); 
            $total_seats = $first_record ? $first_record->seats : 0;
            $total_hour=$first_record ? $first_record->hour : 0;
           
            $this->statusInactive();
            $this->updateLibraryStatus();
            // $this->dataUpdate();
           

            $learnerExtendText = 'Extend Days are Active Now & Remaining Days are';
            
            $booked_seats = getUnavailableSeatCount();
            $availble_seats = getAvailableSeatCount();
            
            $active_seat_count =  Learner::where('library_id', getLibraryId())->where('status', 1)
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

            foreach ($planTypes as $planType) {
                // Count learners with active status assigned to this plan_type_id
                $count = LearnerDetail::where('status', 1)
                    ->where('plan_type_id', $planType->id)
                    ->count();

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

            $extend_days = Branch::where('library_id',getAuthenticatedUser()->id)->select('extend_days')->first();
            if ($extend_days) {
                $extendDay = $extend_days->extend_days;
            } else {
                $extendDay = 0;
            }

          

           
        $libraryupdates=DB::table('updates')->whereNull('deleted_at')->where('guard','library')->get();
        $learnerupdates=DB::table('updates')->whereNull('deleted_at')->where('guard','learner')->get();
        $plans =Plan::where('library_id', getLibraryId())->get();
     
         if(getCurrentBranch() !=0 || getCurrentBranch() !=null){
            $totalSeats =  Hour::where('branch_id',getCurrentBranch())->value('seats');
            $totalHour=Hour::where('branch_id',getCurrentBranch())->value('hour');
        }else{
            $totalSeats =  Hour::where('library_id',getLibraryId())->SUM('seats');
             $totalHour=Hour::where('library_id',getLibraryId())->SUM('hour');
        } 
        $usedSeats = LearnerDetail::select('seat_no', DB::raw('SUM(hour) as used_hours'))
                    ->whereNotNull('seat_no')
                    ->groupBy('seat_no')
                    ->pluck('used_hours', 'seat_no'); // [seat_no => used_hours]

        $availableSeats = collect();

        // Step 2: Loop through all seat numbers and apply logic
        for ($seatNo = 1; $seatNo <= $totalSeats; $seatNo++) {
            $usedHours = $usedSeats[$seatNo] ?? 0;

            if ($usedHours < $totalHour) {
                $availableSeats->push($seatNo);
            }
        }

         
        $exams=DB::table('exams')->get();
            View::share('primary_color', $primary_color);
            View::share('checkSub', $checkSub);
            View::share('checkSub', $checkSub);
            View::share('ispaid', $ispaid);
            View::share('isProfile', $isProfile);
            View::share('isEmailVeri', $isEmailVeri);
            View::share('iscomp', $iscomp);
            View::share('librarydiffInDays', $librarydiffInDays);
            View::share('is_renew', $is_renew);
            View::share('is_renew_comp', $is_renew_comp);
            View::share('is_expire', $is_expire);
            View::share('today_renew', $today_renew);
            View::share('upcomingdiffInDays', $upcomingdiffInDays);
            View::share('learnerExtendText', $learnerExtendText);
            View::share('total_seats', $total_seats);
            View::share('total_hour', $total_hour);
            View::share('active_seat_count', $active_seat_count);
            View::share('expired_seat', $expired_seat);
            View::share('availble_seats', $availble_seats);
            View::share('booked_seats', $booked_seats);
            View::share('planTypeCounts', $planTypeCounts);
            // View::share('fullday_count', $fullday_count);
            // View::share('firstHalfCount', $firstHalfCount);
            // View::share('secondHalfCount', $secondHalfCount);
            View::share('availableseats', $availableSeats);
            View::share('totalSeats', $totalSeats);
            View::share('exams', $exams);
            View::share('plans', $plans);
            View::share('extended_seats', $extended_seats);
            View::share('extendDay', $extendDay);
            View::share('diffExtendDay', $diffExtendDay);
            View::share('learner_is_renew', $learner_is_renew);
            View::share('diffInDays', $diffInDays);
            View::share('libraryupdates', $libraryupdates);
            View::share('learnerupdates', $learnerupdates);
        }
        if (getAuthenticatedUser() && Auth::guard('library')->check()) {
            $user = getAuthenticatedUser();
            $request->attributes->set('library_name', $user->library_name);
           
        }


        return $next($request);
    }

    public function updateLibraryStatus()
    {
        
            \Log::info('Start library status');
        
      
        $today = Carbon::today();
        $hourexist = Hour::withoutGlobalScopes()->where('library_id', getLibraryId())->count();
        $extendexist = Branch::where('library_id',getLibraryId())->whereNotNull('extend_days')->count();
       
        $plan = Plan::count();
        $plantype = PlanType::where('library_id', getLibraryId())
            ->where(function ($query) {
                $query->where('day_type_id', 1)
                    ->orWhere('day_type_id', 2)
                    ->orWhere('day_type_id', 3);
            })
            ->count();
        $planPrice = PlanPrice::withoutGlobalScopes()->where('library_id', getLibraryId())->count();
        $is_active = LibraryTransaction::withoutGlobalScopes()->where('library_id', getLibraryId())->where('is_paid', 1)->where('end_date', '>', $today->format('Y-m-d'))->exists();
        if ($hourexist > 0 && $extendexist > 0 && $plan > 0 && $plantype >= 3 && $planPrice >= 3 && $is_active) {
            $id = getLibraryId();
            $library = Library::findOrFail($id);
           
            if ($library->status != 1) {
                $library->status = 1;
                $library->save();
            }
        }
    }

    public function statusInactive()
    {
        $userId = getAuthenticatedUser()->id;
        $today = Carbon::today();
        $yesterday = $today->subDay();
        $statuscheck = LibraryTransaction::withoutGlobalScopes()->where('library_id',  getLibraryData())->where('is_paid', 1)->where('end_date', '<=', $yesterday->format('Y-m-d'))->exists();
        $is_renew = LibraryTransaction::withoutGlobalScopes()->where('library_id', getLibraryData())->where('is_paid', 1)->where('end_date', '>', $today->format('Y-m-d'))->exists();
        if ($statuscheck && ($is_renew == false)) {
            Library::where('id', $userId)
                ->where('status', 1)
                ->update(['status' => 0, 'is_paid' => 0]);

            // Mark the expired transaction status as inactive
            LibraryTransaction::withoutGlobalScopes()->where('library_id', $userId)
                ->where('is_paid', 1)
                ->where('status', 1)
                ->whereDate('end_date', '=', $yesterday->format('Y-m-d'))
                ->orWhere('end_date', '<', $today->format('Y-m-d'))
                ->update(['status' => 0]);
        }
    }
    // public function dataUpdate()
    // {
    //     $userUpdates = Learner::where('library_id',getLibraryId())->where('status', 1)->get();

    //     foreach ($userUpdates as $userUpdate) {
    //         $today = date('Y-m-d');
    //         $customerdatas = LearnerDetail::where('learner_id', $userUpdate->id)->where('status', 1)->get();

    //         $extend_days_data = Hour::where('library_id', getLibraryId())->first();
    //         $extend_day = $extend_days_data ? $extend_days_data->extend_days : 0;
    //         foreach ($customerdatas as $customerdata) {
    //             $planEndDateWithExtension = Carbon::parse($customerdata->plan_end_date)->addDays($extend_day);

    //             $current_date = Carbon::today();
    //             $hasFuturePlan = LearnerDetail::where('learner_id', $userUpdate->id)
    //                 ->where('plan_end_date', '>', $current_date->copy()->addDays(5))->where('status', 0)
    //                 ->exists();
    //             $hasPastPlan = LearnerDetail::where('learner_id', $userUpdate->id)
    //                 ->where('plan_end_date', '<', $current_date->copy()->addDays(5))
    //                 ->exists();


    //             $isRenewed = $hasFuturePlan && $hasPastPlan;
    //             if ($planEndDateWithExtension->lte($today)) {
    //                 $userUpdate->update(['status' => 0]);
    //                 $customerdata->update(['status' => 0]);
    //             } elseif ($isRenewed) {
    //                 LearnerDetail::where('learner_id', $userUpdate->id)->where('plan_start_date', '<=', $today)->where('plan_end_date', '>', $current_date->copy()->addDays(5))->update(['status' => 1]);
    //                 LearnerDetail::where('learner_id', $userUpdate->id)->where('plan_end_date', '<', $today)->update(['status' => 0]);
    //             } else {
    //                 $userUpdate->update(['status' => 1]);
    //                 LearnerDetail::where('learner_id', $userUpdate->learner_id)->where('status', 0)->where('plan_start_date', '<=', $today)->where('plan_end_date', '>', $today)->update(['status' => 1]);
    //             }
    //         }
    //     }

      
    // }
}
