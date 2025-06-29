<?php

use App\Http\Controllers\LearnerController;
use App\Models\Branch;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerTransaction;
use Illuminate\Support\Facades\Log;
use App\Models\Library;
use App\Models\Subscription;
use App\Models\LibraryTransaction;
use App\Models\LibraryUser;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Models\PlanPrice;
use App\Models\Plan;
use App\Models\PlanType;
use Carbon\Carbon;

if (!function_exists('getAuthenticatedUser')) {
    function getAuthenticatedUser()
    {
        foreach (['library', 'library_user', 'web', 'learner'] as $guard) {
            if (Auth::guard($guard)->check()) {
                return Auth::guard($guard)->user();
            }
        }

        return null;
    }
}
if (!function_exists('encryptData')) {
    function encryptData($data)
    {
        $key = "gingerth1nksaasT";
        $cipher = "AES-128-CBC";
        $iv_size = openssl_cipher_iv_length($cipher);
        $IV = substr(md5($key), 0, $iv_size);
        $encrypted = openssl_encrypt($data, $cipher, $key, 0, $IV);
        return str_replace(["+", "/"], [" ", "*"], $encrypted);
    }
}

if (!function_exists('decryptData')) {
    function decryptData($data)
    {
        $key = "gingerth1nksaasT";
        $cipher = "AES-128-CBC";
        $iv_size = openssl_cipher_iv_length($cipher);
        $IV = substr(md5($key), 0, $iv_size);
        $data = str_replace([" ", "*"], ["+", "/"], $data);
        $decrypted = openssl_decrypt($data, $cipher, $key, 0, $IV);
        \Log::info("Decryption Successful: " . $data . " → " . $decrypted);
        return $decrypted;
    }
}
if (!function_exists('getLibrary')) {
    function getLibrary()
    {
        return Library::where('id', getLibraryId())->first();
    }
}

if (!function_exists('getLibraryData')) {
    function getLibraryData()
    {
        $id = Session::get('selected_library_id');
        \Log::info("selected_library_id " . $id);
        if (!$id) {
            return null; // No library selected
        }

        $library = Library::find($id);
        if (!$library) {
            return null; // Invalid library ID
        }

        $plan = Subscription::where('id', $library->library_type)->with('permissions')->first();
        $library_transaction = LibraryTransaction::withoutGlobalScopes()->where('library_id', $library->id)
            ->where('is_paid', 1)
            ->orderBy('created_at', 'DESC')
            ->with('subscription')
            ->first();
        $library_all_transaction = LibraryTransaction::withoutGlobalScopes()->where('library_id', $library->id)->with('subscription')->get();

        return (object) [
            'library' => $library,
            'plan' => $plan,
            'latest_transaction' => $library_transaction,
            'all_transactions' => $library_all_transaction,
        ];
    }
}

if (!function_exists('getLibraryDataFromId')) {
    function getLibraryDataFromId($id)
    {
      

        $library = Library::find($id);
        if (!$library) {
            return null; // Invalid library ID
        }

        $plan = Subscription::where('id', $library->library_type)->with('permissions')->first();
        $library_transaction = LibraryTransaction::withoutGlobalScopes()->where('library_id', $library->id)
            ->where('is_paid', 1)
            ->orderBy('created_at', 'DESC')
            ->with('subscription')
            ->first();
        $library_all_transaction = LibraryTransaction::withoutGlobalScopes()->where('library_id', $library->id)->with('subscription')->get();

        return (object) [
            'library' => $library,
            'plan' => $plan,
            'latest_transaction' => $library_transaction,
            'all_transactions' => $library_all_transaction,
        ];
    }
}
if (!function_exists('learnerTransaction')) {
    function learnerTransaction($id, $detail_id)
    {
        $transaction = LearnerTransaction::where('learner_id', $id)->where('learner_detail_id', $detail_id)->first();
        return  $transaction;
    }
}

if (!function_exists('getLibraryId')) {
    function getLibraryId()
    {
        $library_id = null;

        if (Auth::guard('library')->check()) {
            $library_id = Auth::guard('library')->user()->id;
        } elseif (Auth::guard('library_user')->check()) {
            $library_id = Auth::guard('library_user')->user()->library_id;
        }

        return $library_id;
    }
}

if (!function_exists('getCurrentBranch')) {
    function getCurrentBranch()
    {
        $currentBranch = null;

        if (Auth::guard('library')->check()) {
            $user = Auth::guard('library')->user();
            $currentBranch = $user->current_branch;
        } elseif (Auth::guard('library_user')->check()) {
            $user = Auth::guard('library_user')->user();
            $currentBranch = $user->current_branch;
        }

        return $currentBranch;
    }
}
if (!function_exists('getPlanPrice')) {
    function getPlanPrice($plan_id, $plan_type_id)
    {
        $libraryId = getLibraryId();
        $branchId = getCurrentBranch();

        $plan_price_all = PlanPrice::withoutGlobalScopes()
            ->leftJoin('plans', function ($join) {
                $join->on('plan_prices.plan_id', '=', 'plans.id')
                    ->where('plans.library_id', getLibraryId());
            })
            ->where('plans.plan_id', 1)
            ->where('plans.type', 'MONTH')
            ->where('plan_prices.plan_type_id', $plan_type_id)
            ->where('plan_prices.library_id', getLibraryId())
            ->where('plan_prices.branch_id', getCurrentBranch())
            ->select('plan_prices.price')
            ->first();

        $plan = Plan::where('id', $plan_id)->first();

        if ($plan_price_all && $plan) {

            if ($plan->type == 'MONTH') {
                $PlanpPrice = $plan_price_all->price * $plan->plan_id;
            } elseif ($plan->type == 'YEAR') {
                $PlanpPrice = $plan_price_all->price * $plan->plan_id * 12;
            } elseif ($plan->type == 'WEEK') {
                $PlanpPrice = ($plan_price_all->price / 4) * $plan->plan_id;
            } else {
                $PlanpPrice = ($plan_price_all->price / 30) * $plan->plan_id;
            }

            return round($PlanpPrice, 2);
        }

        return 0; // or null or handle if price or plan not found
    }
}


if (!function_exists('getLockerPrice')) {
    function getLockerPrice(?int $planId = null)
    {
        $branchId = getCurrentBranch();
        if ($branchId && $planId) {
            $plan = Plan::find($planId);

            $branch = Branch::where('id', $branchId)->select('locker_amount')->first();
            if ($plan->type == 'YEAR') {
                $locker_amount = $branch->locker_amount * 12 * $plan->plan_id;
            } elseif ($plan->type == 'WEEK') {
                $locker_amount = ($branch->locker_amount / 30 * 7) * $plan->plan_id;
            } elseif ($plan->type == 'DAY') {
                $locker_amount = ($branch->locker_amount / 30) * $plan->plan_id;
            } elseif ($plan->type == 'MONTH') {

                $locker_amount = ($branch->locker_amount) * $plan->plan_id;
            } else {
                $locker_amount = 0;
            }
        } else if ($branchId && !$planId) {
            $branch = Branch::where('id', $branchId)->select('locker_amount')->first();
            $locker_amount = $branch->locker_amount;
        } else {
            $locker_amount = 0;
        }


        return $locker_amount;
    }
}
if (!function_exists('getExtendDays')) {
    function getExtendDays()
    {
        $branchId = getCurrentBranch();
        $extend_days = 0;

        if ($branchId) {
            $branch = Branch::where('id', $branchId)->select('extend_days')->first();

            if ($branch) {
                $extend_days = $branch->extend_days;
            }
        }

        return $extend_days;
    }
}


if (!function_exists('getPlanStatusDetails')) {
    // $today = Carbon::today();
    // $endDate = Carbon::parse($user->plan_end_date);
    // $diffInDays = $today->diffInDays($endDate, false);
    // $inextendDate = $endDate->copy()->addDays($extendDay);
    // $diffExtendDay= $today->diffInDays($inextendDate, false);
    // $class='';
    // if($diffInDays < 0 && $diffExtendDay>0){
    //     $class='extedned';
    // }
    // if($diffInDays <=5 && $diffInDays>=0){
    //     $class='expired';
    // }
    // for learner Plan detail
    function getPlanStatusDetails($plan_end_date)
    {
        $extendDay = getExtendDays(); // assume integer
        $today = Carbon::today();
        $endDate = Carbon::parse($plan_end_date);

        $diffInDays = $today->diffInDays($endDate, false);
        if ($extendDay > 0) {
            $inextendDate = $endDate->copy()->addDays($extendDay);
        } else {
            $inextendDate = $endDate; // fallback to original end date
        }
        $diffExtendDay = $today->diffInDays($inextendDate, false);

        // Default status & class
        $status = 'active';
        $class = '';

        if ($diffInDays < 0 && $diffExtendDay > 0) {
            $status = 'extended';
            $class = 'extedned';
        } elseif ($diffInDays <= 5 && $diffInDays >= 0) {
            $status = 'expiring';
            $class = 'expired';
        } elseif ($diffExtendDay < 0) {
            $status = 'expired';
            $class = 'expired';
        }

        return [
            'status' => $status,
            'class' => $class,
            'diff_in_days' => $diffInDays,
            'diff_extend_day' => $diffExtendDay,
            'extend_days' => $extendDay
        ];
    }
}

if (!function_exists('getSeatType')) {
    function getSeatType()
    {
        $branchId = getCurrentBranch();
        if ($branchId) {
            $branch = Branch::where('id', $branchId)->select('seat_type')->first();
            $seat_type = $branch->seat_type;
        } else {
            $seat_type = null;
        }

        return $seat_type;
    }
}
if (!function_exists('countWithoutSeatNo')) {
    function countWithoutSeatNo()
    {
        $branchId = getCurrentBranch();
        if ($branchId) {
            $count = Learner::where('branch_id', $branchId)->whereNull('seat_no')->count();
        }

        return $count ?? 0;
    }
}
if (!function_exists('getUserStatusDetails')) {
    function getUserStatusDetails($plan_end_date)
    {
        $extendDay = getExtendDays(); // assume this returns an integer like 3 or 7
        $today = Carbon::today();
        $endDate = Carbon::parse($plan_end_date);

        $diffInDays = $today->diffInDays($endDate, false); // negative if expired
        $inextendDate = $endDate->copy()->addDays($extendDay);
        $diffExtendDay = $today->diffInDays($inextendDate, false); // negative if beyond extension

        if ($diffInDays > 0) {
            return '<small class="text-success">Plan Expires in ' . $diffInDays . ' days</small>';
        } elseif ($diffInDays < 0 && $diffExtendDay > 0) {
            // <span class="text-danger fs-10 d-block">{{$learnerExtendText}} {{ abs($customer->diffExtendDay) }} days.</span>
            return '<small class="text-danger fs-10 d-block">Extension active! ' . abs($diffExtendDay) . ' days left.</small>';
        } elseif (($diffInDays < 0 && $diffExtendDay == 0)) {
            return ' <span class="text-warning fs-10 d-block">Plan Expires today</span>';
        } elseif ($diffInDays == 0) {
            return '<small class="text-warning fs-10 d-block">Plan Expires today</small>';
        } else {
            return '<small class="text-danger fs-10 d-block">Plan Expired ' . abs($diffInDays) . ' days ago</small>';
        }
    }
}

if (!function_exists('myLearner')) {
    function myLearner($learner_id)
    {
        $learner = Learner::where('id', $learner_id)->first();
        return $learner ? $learner : null;
    }
}

if (!function_exists('myPlan')) {
    function myPlan($plan_id)
    {
        $plan = Plan::where('id', $plan_id)->first();
        return $plan ? $plan : null;
    }
}

if (!function_exists('myPlanType')) {
    function myPlanType($plan_id)
    {
        $plan = PlanType::where('id', $plan_id)->first();
        return $plan ? $plan : null;
    }
}
if (!function_exists('myPlanPrice')) {
    function myPlanPrice($learnerDeatilId)
    {
        $price = LearnerDetail::where('id', $learnerDeatilId)->value('plan_price_id') ?? 0;
        return $price ? $price : 0;
    }
}

if (!function_exists('countBranch')) {
    function countBranch()
    {
        $count = Branch::where('library_id', getLibraryId())->count();
        return $count;
    }
}

if (!function_exists('getUserStatusWithSpan')) {
    function getUserStatusWithSpan($plan_end_date)
    {
        $extendDay = getExtendDays();
        $today = Carbon::today();
        $endDate = Carbon::parse($plan_end_date);

        $diffInDays = $today->diffInDays($endDate, false);
        $inextendDate = $endDate->copy()->addDays($extendDay);
        $diffExtendDay = $today->diffInDays($inextendDate, false);

        if ($diffInDays > 0) {
            return '<span class="text-success">Plan Expires in ' . $diffInDays . ' days</span>';
        } elseif ($diffInDays < 0 && $diffExtendDay > 0) {
            return '<span class="text-danger fs-10 d-block">Extension active! ' . abs($diffExtendDay) . ' days left.</span>';
        } elseif (($diffInDays < 0 && $diffExtendDay == 0)) {
            return ' <span class="text-warning fs-10 d-block">Plan Expires today</span>';
        } elseif ($diffInDays == 0) {
            return '<span class="text-warning fs-10 d-block">Plan Expires today</span>';
        } else {
            return '<span class="text-danger fs-10 d-block">Plan Expired ' . abs($diffInDays) . ' days ago</span>';
        }
    }
}
if (!function_exists('getUnavailableSeatCount')) {
    function getUnavailableSeatCount()
    {
        $totalHour = Hour::where('branch_id', getCurrentBranch())->value('hour');

        return LearnerDetail::select('seat_no', DB::raw('SUM(hour) as used_hours'))
            ->whereNotNull('seat_no')
            ->where('is_paid',1)
            ->distinct('seat_no')
            // ->havingRaw('SUM(hour) >= ?', [$totalHour])
            ->count('seat_no');
    }
}


if (!function_exists('getAvailableSeatCount')) {
    function getAvailableSeatCount()
    {
        $totalHour =  Hour::where('branch_id', getCurrentBranch())->value('hour');
        $totalSeats =  Hour::where('branch_id', getCurrentBranch())->value('seats') ?? 0;

        $unavailable = getUnavailableSeatCount();

        return $totalSeats - $unavailable;
    }
}

if (!function_exists('seatRemainingHour')) {
    function seatRemainingHour($seat)
    {
        $totalHour =  Hour::where('branch_id', getCurrentBranch())->value('hour');

        // If total hour is not set, return 0
        if (!$totalHour) {
            return 0;
        }

        $usedHours = LearnerDetail::where('seat_no', $seat)
            ->sum('hour');

        return max(0, $totalHour - $usedHours); // Ensure no negative value
    }
}

if (!function_exists('currentTransaction')) {
    function currentTransaction($learner_detail)
    {
        $data = LearnerTransaction::where('learner_detail_id', $learner_detail)->first();
        return $data ?? null;
    }
}

if (!function_exists('totalSeat')) {
    function totalSeat()
    {
        if (getCurrentBranch() != 0 || getCurrentBranch() != null) {
            $totalSeats =  Hour::where('branch_id', getCurrentBranch())->value('seats');
        } else {
            $totalSeats =  Hour::where('library_id', getLibraryId())->SUM('seats');
        }



        return $totalSeats;
    }
}

if (!function_exists('getLearnerMonthsAndYears')) {
    function getLearnerMonthsAndYears()
    {
        $data = DB::select("
            SELECT DISTINCT 
                YEAR(plan_start_date) as year, 
                MONTH(plan_start_date) as month 
            FROM learner_detail
            WHERE plan_start_date IS NOT NULL 
            ORDER BY year DESC, month ASC
        ");

        $collection = collect($data);

        $years = $collection->pluck('year')->unique()->values();
        $months = $collection->pluck('month')->unique()->values();

        return [
            'years' => $years,
            'months' => $months,
        ];
    }
}

if (!function_exists('overdue')) {
    function overdue($learner_id, $pending_amt)
    {
        $exists = DB::table('learner_pending_transaction')->where('learner_id', $learner_id)->where('status', 0)->where('pending_amount', $pending_amt)->first();
        if ($exists && \Carbon\Carbon::now()->gt(\Carbon\Carbon::parse($exists->due_date))) {
            return true;
        } else {
            return false;
        }
    }
}

if (!function_exists('paylater')) {
    function paylater($learner_detail_id)
    {
        return LearnerTransaction::where('learner_detail_id', $learner_detail_id)->where('is_paid', 0)->exists();
    }
}

if (!function_exists('pending_amt')) {
    function pending_amt($learner_detail_id)
    {
        return LearnerTransaction::where('learner_detail_id', $learner_detail_id)->where('is_paid', 1)->where('pending_amount', '>', 0)->exists();
    }
}

if (!function_exists('is_locker')) {
    function is_locker()
    {
        $data = Branch::where('id', getCurrentBranch())->select('locker_amount')->first();

        if (!$data || $data->locker_amount == 0 || $data->locker_amount === null) {
            return false;
        }

        return true;
    }
}

if (!function_exists('filterPlantypeFromseat')) {
    function filterPlantypeFromseat($seat_no,$customerId)
    {
        
       $bookings = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
                ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
                ->where('learner_detail.seat_no', $seat_no)
                ->where('learner_detail.learner_id', '!=', $customerId)
                ->where('learners.status', 1)
                ->where('learner_detail.status', 1)
                ->where('learners.branch_id', getCurrentBranch())
                ->where('learner_detail.branch_id', getCurrentBranch())
                ->get(['learner_detail.plan_type_id', 'plan_types.start_time', 'plan_types.end_time', 'plan_types.slot_hours']);

            // Step 2: Retrieve all plan types
            $planTypes = PlanType::get();

            // Step 3: Initialize an array to store the plan_type_ids to be removed
            $planTypesRemovals = [];

            // Step 4: Calculate total booked hours for the seat
            $totalBookedHours = $bookings->sum('slot_hours');
        
            $nightseatBooked = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.seat_no', $seat_no)->where('learner_detail.learner_id', '!=', $customerId)->where('learner_detail.status', 1)->where('plan_types.day_type_id', 9)->exists();

            // Step 5: Determine conflicts based on plan_type_id and hours
            $planTypeId = null;
            if ($totalBookedHours <= 24) {

                foreach ($bookings as $booking) {
                    foreach ($planTypes as $planType) {
                        if ($booking->start_time < $planType->end_time && $booking->end_time > $planType->start_time) {
                            $planTypesRemovals[] = $planType->id;
                        }
                    }
                }
            }
            if ($totalBookedHours > 1) {
                $planTypeId = PlanType::where('day_type_id', 8)->value('id') ?? 0;
            }

            if (!is_null($planTypeId)) {
                $planTypesRemovals[] = $planTypeId;
            }

            if ($nightseatBooked) {
                $planTypeid = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.seat_no', $seat_no)->where('learner_detail.status', 1)->where('plan_types.day_type_id', 9)->value('plan_types.id') ?? 0;
                $planTypesRemovals[] = $planTypeid;
            }
            // Remove duplicate entries in planTypesRemovals
            $planTypesRemovals = array_unique($planTypesRemovals);

            // If total booked hours >= 16, all plan types should be removed
            $first_record = Hour::where('branch_id', getCurrentBranch())->first();
            $total_hour = $first_record ? $first_record->hour : null;

            if ($totalBookedHours >= $total_hour) {
                $planTypesRemovals = $planTypes->pluck('id')->toArray();
            }

            // Step 6: Filter out the plan_types that match the retrieved plan_type_ids
          return  $filteredPlanTypes = $planTypes->filter(function ($planType) use ($planTypesRemovals) {
                return !in_array($planType->id, $planTypesRemovals);
            })->map(function ($planType) {
                return ['id' => $planType->id, 'name' => $planType->name];
            })->values();
    }
}

if (!function_exists('branchCountValidation')) {
    function branchCountValidation()
    {
        $library=Library::findOrFail(getLibraryId());
        $branch_count=Branch::where('library_id',getLibraryId())->count();
        $message="You cannot add more branches. You already have $branch_count branches.";
        $limits = [1 => 1, 2 => 2, 3 => 4]; // library_type => max branches
        $maxAllowed = $limits[$library->library_type] ?? 0;

        return [
            'success' => $branch_count >= $maxAllowed,
            'branch_count' => $branch_count,
            'max_allowed' => $maxAllowed,
            'message' =>$message
        ];
     
    }
}

if (!function_exists('getProfileCompletionPercentage')) {
    function getProfileCompletionPercentage()
    {
        $branch=Branch::find(getCurrentBranch());
        $fields = [
            'name','display_name',
            'email',
            'mobile',
            'library_address',
            'state_id','city_id',
            'library_zip','google_map',
            'extend_days','locker_amount','library_category','working_days','longitude',
            'latitude','description','library_logo','library_images'
          
        ];

        $filled = 0;
        foreach ($fields as $field) {
            if (!empty($branch->$field)) {
                $filled++;
            }
        }

        $percentage = ($filled / count($fields)) * 100;
        return round($percentage, 2);
    }
}


