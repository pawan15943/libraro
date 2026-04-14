<?php

use App\Http\Controllers\LearnerController;
use App\Models\Branch;
use App\Models\Floor;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerTransaction;
use App\Models\LearnerTransactionActivity;
use Illuminate\Support\Facades\Log;
use App\Models\Library;
use App\Models\Subscription;
use App\Models\LibraryTransaction;
use App\Models\LibraryUser;
use App\Models\NotificationChannelSetting;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Models\PlanPrice;
use App\Models\Plan;
use App\Models\PlanType;
use App\Models\Setting;
use Carbon\Carbon;

if (!function_exists('alreadyRenewed')) {
    function alreadyRenewed($learnerId)
    {
        return LearnerDetail::where('learner_id', $learnerId)
            ->where('status', 0) // future booking
            ->whereDate('plan_start_date', '>', now())
            ->exists();
    }
}


if (!function_exists('generateLibraryCode')) {
    function generateLibraryCode() {
        $prefix = "LB";
        $lastLibrary = Library::orderBy('id', 'DESC')
                              ->whereNotNull('library_no')
                              ->first();
                              
        if ($lastLibrary) {
            
            $lastNumber = intval(substr($lastLibrary->library_no, 2)); 
            $newNumber = $lastNumber + 1;
            $randomNumber = str_pad($newNumber, 6, '0', STR_PAD_LEFT); 
        } else {
            $randomNumber = '000001';
        }
    
        return $prefix . $randomNumber;
    }
}
if (!function_exists('logoutOtherGuards')) {
    function logoutOtherGuards(string $currentGuard): void
    {
        foreach (array_keys(config('auth.guards')) as $guard) {
            if ($guard !== $currentGuard && Auth::guard($guard)->check()) {
                Auth::guard($guard)->logout();
            }
        }
    }
}

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
        // \Log::info("Decryption Successful: " . $data . " → " . $decrypted);
        return $decrypted;
    }
}
if (!function_exists('getBranch')) {
    function getBranch()
    {
        return Branch::where('id', getCurrentBranch())->first();
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
        // \Log::info("selected_library_id " . $id);
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

if (!function_exists('operatingHour')) {

    function operatingHour($branchId)
    {
        $hour =Hour::withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->value('hour');

        if (!$hour) {
            throw new \Exception('Library hours not configured.');
        }

        return $hour;
    }
}

if (!function_exists('learnerTransaction')) {
    function learnerTransaction($id, $detail_id)
    {
        $transaction = LearnerTransaction::withTrashed()->where('learner_id', $id)->where('learner_detail_id', $detail_id)->first();
        return  $transaction;
    }
}
if (!function_exists('totalPending')) {
    function totalPending($id)
    {
        $transaction = LearnerTransaction::withTrashed()->where('learner_id', $id)->sum('pending_amount');
        return  $transaction;
    }
}

if (!function_exists('learnerTransactionStatus')) {

    function learnerTransactionStatus($learnerId)
    {
        $today = now()->toDateString();

        $transactions = LearnerTransaction::withTrashed()
            ->leftJoin('learner_detail as ld', 'ld.id', '=', 'learner_transactions.learner_detail_id')
            ->where('learner_transactions.learner_id', $learnerId)
            ->where('learner_transactions.pending_amount', '>', 0)
            ->select(
                'learner_transactions.pending_amount',
                'learner_transactions.refund',
                'learner_transactions.due_date',
                'ld.payment_mode'
            )
            ->get();

        // Totals
        $totalPending = $transactions->sum('pending_amount');
        $totalRefund  = $transactions->sum('refund');

        $status = 'paid';
        $dueDate = null;

        if ($transactions->isNotEmpty()) {

            // 🔴 1. Overdue
            $overdueTxn = $transactions->first(function ($txn) use ($today) {
                return !empty($txn->due_date) && $txn->due_date < $today;
            });

            if ($overdueTxn) {
                $status = 'overdue';
                $dueDate = $overdueTxn->due_date;
            }

            // 🟡 2. Paylater (ANY learner_detail has payment_mode = 3)
            elseif ($transactions->contains('payment_mode', 3)) {
                $status = 'paylater';
                $dueDate = optional($transactions->sortBy('due_date')->first())->due_date;
            }

            // 🟠 3. Pending
            else {
                $status = 'pending';
                $dueDate = optional($transactions->sortBy('due_date')->first())->due_date;
            }
        }

        return [
            'status' => $status, // overdue | paylater | pending | paid
            'pending_amount' =>(string)$totalPending,
            'due_date' => $dueDate ?? '',
            'pending_refund' =>(string)$totalRefund
        ];
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

        } elseif (Auth::guard('learner')->check()) {
            $library_id = Auth::guard('learner')->user()->library_id;

        } elseif (Auth::guard('library_api')->check()) {

            $user = Auth::guard('library_api')->user();

            if ($user instanceof \App\Models\Library) {
                $library_id = $user->id;
            }

            if ($user instanceof \App\Models\LibraryUser) {
                $library_id = $user->library_id;
            }
        }

        return $library_id;
    }
}

if (!function_exists('getCurrentBranch')) {
    function getCurrentBranch()
    {
        $currentBranch = null;

        if (Auth::guard('library')->check()) {
            $currentBranch = Auth::guard('library')->user()->current_branch;

        } elseif (Auth::guard('library_user')->check()) {
            $currentBranch = Auth::guard('library_user')->user()->current_branch;

        } elseif (Auth::guard('learner')->check()) {
            $currentBranch = Auth::guard('learner')->user()->branch_id;

        } elseif (Auth::guard('library_api')->check()) {

            $user = Auth::guard('library_api')->user();

            if ($user instanceof \App\Models\Library) {
                $currentBranch = $user->current_branch;
            }

            if ($user instanceof \App\Models\LibraryUser) {
                $currentBranch = $user->current_branch;
            }
        }

        return $currentBranch;
    }
}
if (!function_exists('generateLearnerCode')) {
 function generateLearnerCode()
    {
        $prefix = "LN";
        $lastlearner = Learner::withoutGlobalScopes()
            ->whereNotNull('learner_no')
            ->orderBy('id', 'DESC')
            ->first();

        if ($lastlearner) {

            $lastNumber = intval(substr($lastlearner->learner_no, 2));
            $newNumber = $lastNumber + 1;
            $randomNumber = str_pad($newNumber, 6, '0', STR_PAD_LEFT);
        } else {
            $randomNumber = '000001';
        }

        return $prefix . $randomNumber;
    }
}
if (!function_exists('getPlanPrice')) {
    function getPlanPrice($plan_id, $plan_type_id, $branch_id = null)
    {


        if ($branch_id) {
          
            $branchId  = $branch_id;
            $libraryId = Branch::where('id', $branchId)->value('library_id');
        } else {
              
            $branchId  = getCurrentBranch();
            $libraryId = getLibraryId();
        }

        $alreadyPrice = PlanPrice::withoutGlobalScopes()
            ->where('plan_id', $plan_id)
            ->where('plan_type_id', $plan_type_id)
            ->where('library_id', $libraryId)
            ->where('branch_id', $branchId)
            ->select('price')->first();

        if ($alreadyPrice) {
           
            return round($alreadyPrice->price);
        } else {
          

            $plan_price_all = PlanPrice::withoutGlobalScopes()
                ->leftJoin('plans', function ($join) use ($libraryId) {
                    $join->on('plan_prices.plan_id', '=', 'plans.id')
                        ->where('plans.library_id', $libraryId);
                })

                ->where('plans.plan_id', 1)
                ->where('plans.type', 'MONTH')
                ->where('plan_prices.plan_type_id', $plan_type_id)
                ->where('plan_prices.library_id', $libraryId)
                ->where('plan_prices.branch_id', $branchId)
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
                    if ($plan->monthdays) {
                        $PlanpPrice = ($plan_price_all->price / $plan->monthdays) * $plan->plan_id;
                    } else {
                        $PlanpPrice = ($plan_price_all->price / 30) * $plan->plan_id;
                    }
                }

                return round($PlanpPrice);
            }

            return 0; // or null or handle if price or plan not found
        }
    }
}



if (!function_exists('getEndDate')) {

    function getEndDate($plan_id, $planStartDate,$branch_id = null)
    {
        $planStartDate = \Carbon\Carbon::parse($planStartDate);

        $planData = Plan::where('id', $plan_id)
            ->select('plan_id', 'type', 'monthdays')
            ->first();

        if (!$planData) {
            return $planStartDate;
        }

        $type      = strtoupper($planData->type);
        $duration  = (int) ($planData->plan_id ?? 1);
        $monthdays = $planData->monthdays;

        $branchId  = $branch_id ?? getCurrentBranch();

        $branch = Branch::find($branchId);
       

        /*
        |--------------------------------------------------------------------------
        | 🔹 CASE 1: FIXED BILLING DATE ENABLED (FINAL FIX)
        |--------------------------------------------------------------------------
        */
       if ($branch && !empty($branch->fixed_billing_date)) {

            $fixedDay = (int) $branch->fixed_billing_date;

            switch ($type) {

                case 'DAY':
                    return $planStartDate->copy()->addDays($duration);

                case 'WEEK':
                    return $planStartDate->copy()->addWeeks($duration);

                case 'MONTH':
                case 'YEAR':

                    $cycles = ($type === 'YEAR') ? $duration * 12 : $duration;

                    $billingMonth = $planStartDate->copy()->startOfMonth()->addMonth();

                    if ($cycles > 1) {
                        $billingMonth->addMonths($cycles - 1);
                    }

                    if ($fixedDay === 1) {
                        return $billingMonth->copy()->subMonth()->endOfMonth();
                    }

                    return $billingMonth->day(
                        min($fixedDay - 1, $billingMonth->daysInMonth)
                    );

                default:
                    return $planStartDate;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 🔹 CASE 2: NORMAL PLAN LOGIC (UNCHANGED)
        |--------------------------------------------------------------------------
        */
        switch ($type) {

            case 'DAY':
                return $planStartDate->copy()
                    ->addDays($duration);
                    
            case 'WEEK':
                return $planStartDate->copy()
                    ->addWeeks($duration);
                    

            case 'MONTH':
                if (!empty($monthdays)) {
                    return $planStartDate->copy()
                        ->addDays($monthdays)
                        ->subDay();
                }
                return $planStartDate->copy()
                    ->addMonths($duration)
                    ->subDay();

            case 'YEAR':
                return $planStartDate->copy()
                    ->addYears($duration)
                    ->subDay();

            default:
                return $planStartDate;
        }

    }
}


if (!function_exists('getBillingCyclePrice')) {
    function getBillingCyclePrice($plan_id, $plan_type_id,$planStartDate,$branch_id = null)
    {
       
        $branchId = $branch_id ?? getCurrentBranch();
        $startDate = Carbon::parse($planStartDate);
              
        /* -------------------------------
         | 1. Get base plan price
         --------------------------------*/
        $planPrice = getPlanPrice($plan_id, $plan_type_id ,$branchId);
    
        if ($planPrice <= 0) {
           
            return 0;
        }
       

        /* -------------------------------
         | 2. Get plan details
         --------------------------------*/
        $plan = Plan::select('plan_id', 'type', 'monthdays')
            ->where('id', $plan_id)
            ->first();

        if (!$plan) {
            return 0;
        }

        $planType = strtoupper($plan->type);
        $duration = (int) $plan->plan_id;
        $monthdays = $plan->monthdays;

        /* -------------------------------
         | 3. Calculate END DATE
         --------------------------------*/
        $endDate = getEndDate($plan_id, $startDate,$branchId);
        

        /* -------------------------------
         | 4. Calculate USED DAYS (inclusive)
         --------------------------------*/
        $usedDays = $startDate->diffInDays($endDate) + 1;

        /* -------------------------------
         | 5. Decide TOTAL DAYS (price base)
         --------------------------------*/
        switch ($planType) {

            case 'DAY':
                $totalDays = $duration;
                break;

            case 'WEEK':
                $totalDays = 7 * $duration;
                break;

            case 'MONTH':
                if (!empty($monthdays)) {
                  
                    $totalDays = $monthdays;
                } else {
                     
                    // Days in joining month
                    $totalDays = 30 * $duration;
                }
                break;

            case 'YEAR':
                $totalDays = 365 * $duration;
                break;

            default:
                return round($planPrice, 0);
        }

        /* -------------------------------
         | 6. Safety checks
         --------------------------------*/
        if ($usedDays <= 0 || $totalDays <= 0) {
           
            return round($planPrice, 0);
        }
       

        /* -------------------------------
         | 7. Calculate FINAL PRICE
         --------------------------------*/
       
        $perDayPrice = $planPrice / $totalDays;
        
       
        $finalPrice = $perDayPrice * $usedDays;
            
        return round($finalPrice, 0);
    }
}
if (!function_exists('getChargeableDays')) {
    function getChargeableDays($plan_id, $planStartDate, $branch_id = null)
    {
        if (!$planStartDate) {
            return null;
        }

        $branchId  = $branch_id ?? getCurrentBranch();
        $startDate = Carbon::parse($planStartDate);

        // Calculate end date (handles fixed billing internally)
        $endDate = getEndDate($plan_id, $startDate ,$branchId);

        if (!$endDate) {
            return null;
        }

        // Chargeable days (inclusive)
        $chargeableDays = $startDate->diffInDays($endDate) + 1;

        $branch = Branch::select('fixed_billing_date')
        ->where('id', $branchId)
        ->first();

        $fixedBillingDate = $branch?->fixed_billing_date;

        return [
            'chargeable_days' => $chargeableDays,
            'start_date'      => $startDate->toDateString(),
            'end_date'        => $endDate->toDateString(),
            'fixedBillingDate' => $fixedBillingDate ? 'true' : 'false',
        ];
    }
}
if (!function_exists('getLockerPrice')) {
    function getLockerPrice(?int $planId = null,?int $planTypeId = null)
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
        if( PlanType::where('id',$planTypeId)->where('day_type_id',11)->exists()){
            $locker_amount = 0;
        }
       



        return $locker_amount;
    }
}
if (!function_exists('getExtendDays')) {
    function getExtendDays($branch_id = null)
    {
        $branchId = $branch_id ?? getCurrentBranch();
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
//in top
if (!function_exists('getUserStatusWithSpan')) {
    function getUserStatusWithSpan($plan_end_date, $learner_id)
    {
        $extendDay = getExtendDays();
        $today = Carbon::today();
        $endDate = Carbon::parse($plan_end_date);

        $diffInDays = $today->diffInDays($endDate, false);
        $inextendDate = $endDate->copy()->addDays($extendDay);
        $diffExtendDay = $today->diffInDays($inextendDate, false);

        $hasFuturePlan = LearnerDetail::where('learner_id', $learner_id)
            ->where('plan_end_date', '>', $today->copy()->addDays(5))->where('status', 0)
            ->exists();
        $hasPastPlan = LearnerDetail::where('learner_id', $learner_id)
            ->where('plan_end_date', '<=', $today->copy()->addDays(5))
            ->exists();

        $is_renew_update = alreadyRenewed($learner_id); 
        $start_date = LearnerDetail::where('learner_id', $learner_id)
            ->where('plan_start_date',  '>', now())->where('status', 0)
            ->exists();
        $isfuture_booking = $start_date && !$hasPastPlan;
        $startDetail = LearnerDetail::where('learner_id', $learner_id)
            ->where('plan_start_date',  '>', now())->where('status', 0)->select('plan_start_date')->first();
        if ($startDetail) {
            $start_date = Carbon::parse($startDetail->plan_start_date);

            $startfrom = $today->diffInDays($start_date, false);
        } else {
            $startfrom = null;
        }

        if (Learner::where('id', $learner_id)->where('frozen_status', 1)->exists()) {
            return '<span class="text-success">Frozen</span>';
        }elseif(has_vip($learner_id)){
            return '<span class="text-success">VIP</span>';
        } elseif ($diffInDays > 0 && !$isfuture_booking && !$is_renew_update) {
            return '<span class="text-success">Plan Expires in ' . $diffInDays . ' days</span>';
        } elseif ($is_renew_update && $diffInDays==0) {
            return '<span class="text-success">Expires today (1 plan queued,active tomorrow)</span>';

        }elseif ($is_renew_update && $diffInDays!=0) {
            return '<span class="text-success"> Expires in '.($diffInDays).' days. (1 plan queued) </span>';
        } elseif ($isfuture_booking) {
            return '<span style="color: purple; ">Plan Starts in ' . $startfrom . ' Days</span>';
        } elseif ($diffInDays < 0 && $diffExtendDay > 0) {
            return '<span class="text-danger fs-10 d-block">Extension: ' . abs($diffExtendDay) . ' days left.</span>';
        } elseif (($diffInDays < 0 && $diffExtendDay == 0)) {
            return ' <span class="text-warning fs-10 d-block">Plan Expires today</span>';
        } elseif ($diffInDays == 0) {
            return '<span class="text-warning fs-10 d-block">Plan Expires today</span>';
        } else {
            return '<span class="text-danger fs-10 d-block">Plan Expired ' . abs($diffInDays) . ' days ago</span>';
        }
    }
}
//with name
if (!function_exists('getPlanStatusDetails')) {

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
        $status = 'Active';
        $class = 'actives';

        if ($diffInDays < 0 && $diffExtendDay >=0) {
            $status = 'In Extension';
            $class = 'extedned';
        } elseif ($diffInDays <= 5 && $diffInDays >= 0) {

            $status = 'About to Expire';
            $class = 'aboutToExpire';
        } elseif ($diffExtendDay < 0) {
            $status = 'Expired';
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
            return '<span class="actives">Plan is Active (' . $diffInDays . ' Days Left)</span>';
        } elseif ($diffInDays < 0 && $diffExtendDay > 0) {
            return '<span class="extended">Extension active! (' . abs($diffExtendDay) . ' Days Left)</span>';
        } elseif (($diffInDays < 0 && $diffExtendDay == 0)) {
            return '<span class="extended">Expired today</span>';
        } elseif ($diffInDays == 0) {
            return '<span class="extended">Expired today</span>';
        } else {
            return '<span class="extended">Expired ' . abs($diffInDays) . ' days ago</span>';
        }
    }
}
if (!function_exists('has_vip')) {
    function has_vip($learner_id)
    {
        return LearnerDetail::leftJoin('plan_types', 'plan_types.id', '=', 'learner_detail.plan_type_id')
            ->where('plan_types.day_type_id', 11)  
            ->where('learner_detail.status', 1)
            ->where('learner_detail.learner_id', $learner_id)
            ->exists();
    }
}

if (!function_exists('has_reserved')) {
    function has_reserved($learner_id)
    {
        return LearnerDetail::leftJoin('plan_types', 'plan_types.id', '=', 'learner_detail.plan_type_id')
            ->where('plan_types.day_type_id', 10)   
            ->where('learner_detail.status', 1)
            ->where('learner_detail.learner_id', $learner_id)
            ->exists();
    }
}
if (!function_exists('has_non_expired')) {
    function has_non_expired($learner_id)
    {
               return Learner::where('id',$learner_id)->where('no_expiry',1)->exists();
    }
}

if (!function_exists('myLearner')) {
    function myLearner($learner_id)
    {
        $learner = Learner::withTrashed()->where('id', $learner_id)->first();
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


if (!function_exists('getUnavailableSeatCount')) {
    function getUnavailableSeatCount()
    {
        $totalHour = Hour::where('branch_id', getCurrentBranch())->value('hour');

        return LearnerDetail::select('seat_no', DB::raw('SUM(hour) as used_hours'))
            ->whereNotNull('seat_no')
            ->where('is_paid', 1)
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

if (!function_exists('getAvailableHoursSum')) {

    function getAvailableHoursSum()
    {
        $totalHour = Hour::where('branch_id', getCurrentBranch())->value('hour');
        $totalSeats = Hour::where('branch_id', getCurrentBranch())->value('seats') ?? 0;

        return Learner::select('seat_no', DB::raw('SUM(hours) as used_hours'))
            ->whereNotNull('seat_no')
            ->where('status', 1)
            ->where('branch_id', getCurrentBranch())
            ->groupBy('seat_no')
            ->get()
            ->sum(function ($row) use ($totalHour) {
                return max(0, $totalHour - $row->used_hours);
            });
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
        $data = LearnerTransaction::withTrashed()->where('learner_detail_id', $learner_detail)->first();
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
        $exists = DB::table('learner_transactions')->where('learner_id', $learner_id)->where('pending_amount', $pending_amt)->first();
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
        return LearnerTransaction::where('learner_detail_id', $learner_detail_id)->where('pending_amount', '>', 0)->exists();
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


if (!function_exists('branchCountValidation')) {
    function branchCountValidation()
    {
        $library = Library::findOrFail(getLibraryId());
        $branch_count = Branch::where('library_id', getLibraryId())->count();
        $message = "You cannot add more branches. You already have $branch_count branches.";
        $limits = [1 => 1, 2 => 2, 3 => 4]; // library_type => max branches
        $maxAllowed = $limits[$library->library_type] ?? 0;

        return [
            'success' => $branch_count > 0 && $branch_count >= $maxAllowed,
            'branch_count' => $branch_count,
            'max_allowed' => $maxAllowed,
            'message' => $message
        ];
    }
}

if (!function_exists('getProfileCompletionPercentage')) {
    function getProfileCompletionPercentage()
    {
        $branch = Branch::find(getCurrentBranch());
        $fields = [
            'name',
            'display_name',
            'email',
            'mobile',
            'library_address',
            'state_id',
            'city_id',
            'library_zip',
            'google_map',
            'extend_days',
            'locker_amount',
            'library_category',
            'working_days',
            'longitude',
            'latitude',
            'description',
            'library_logo',
            'library_images'

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

if (!function_exists('cleanNull')) {

    function cleanNull($array)
    {
        return array_map(function ($value) {
            return $value === null ? (is_numeric($value) ? 0 : '') : $value;
        }, $array);
    }
}

if (!function_exists('videoGet')) {

    function videoGet()
    {
        $video = Setting::first();
        return $video;
    }
}

if (!function_exists('toggleHideField')) {
    function toggleHideField()
    {
        $branch = Branch::where('id', getCurrentBranch())->select('hide_field')->first();
        $hiddenFields = [];

        if ($branch && $branch->hide_field) {
            $hiddenFields = json_decode($branch->hide_field, true);
        }

        return $hiddenFields;
    }
}

if (!function_exists('token_money')) {
    function token_money()
    {
        $token_money = 0;

        $branch = Branch::where('id', getCurrentBranch())->select('token_money')->first();

        if ($branch) {
            $token_money = $branch->token_money;
        }
        return $token_money;
    }
}

if (!function_exists('paybleRefund')) {
    function paybleRefund($learner_detail_id)
    {
        $query = LearnerTransaction::where('learner_detail_id', $learner_detail_id);

        return $query->sum('paid_amount')
            + $query->sum('token_money')
            + $query->sum('miscellaneous');
    }
}
if (!function_exists('getLearnerOperation')) {
    function getLearnerOperation($learnerDetailId)
    {
        $operation = DB::table('learner_operations_log')
            ->where('learner_detail_id', $learnerDetailId)
            ->orderByDesc('id') // latest operation
            ->select('operation', 'created_at')
            ->first();

        return $operation ?? null;
    }
}
if (!function_exists('getCurrentBranchName')) {
    function getCurrentBranchName(): ?string
    {
        $user = Auth::guard('library')->user() ?? Auth::guard('library_user')->user();

        if (!$user || !$user->current_branch) {
            return null; // safer fallback if user or branch is not set
        }

        return Branch::find($user->current_branch)?->display_name ?? null;
    }
}

if (!function_exists('refund')) {
    function refund($learner_id)
    {
        $data = LearnerTransactionActivity::where('learner_id', $learner_id)
            ->where('payment_type', 'REFUND')
            ->sum('amount');
        return $data ?? null;
    }
}




if (!function_exists('generateSeatNumbers')) {

    function generateSeatNumbers()
    {
        $result = [];
        $mainSeatNo = 1;

        // Get total seats dynamically
        $first_record = Hour::where('branch_id', getCurrentBranch())->first();
        $totalSeats = $first_record ? $first_record->seats : 0;

        if ($totalSeats <= 0) {
            return $result;
        }

        // Get floors ordered by floor number
        $floors = Floor::where('branch_id', getCurrentBranch())->orderBy('floor_no')->get();

        // Loop through all floors
        if (!empty($floors)) {
            foreach ($floors as $floor) {
                $startSeat = $floor->from_seat ?? 1;
                $endSeat   = $floor->to_seat ?? 0;



                for ($seatNo = $startSeat; $seatNo <= $endSeat && $mainSeatNo <= $totalSeats; $seatNo++) {
                    $result[] = [
                        'main'       => $mainSeatNo,
                        'floor'      => $seatNo,                  // FIXED: seatNo instead of floorSeatNo
                        'floor_name' => $floor->name,
                        'floor_no'   => $floor->floor_no,
                        'display'    => 'Seat No - ' . $seatNo . ' (' . $floor->name . ')'
                    ];

                    $mainSeatNo++;
                }
            }
        }

        // Add remaining seats (unassigned to any floor)
        while ($mainSeatNo <= $totalSeats) {
            $result[] = [
                'main' => $mainSeatNo,
                'floor' => null,
                'floor_name' => null,
                'display' => 'Seat No - ' . $mainSeatNo
            ];
            $mainSeatNo++;
        }

        return $result;
    }
}

if (!function_exists('getSeatDisplayByMainNo')) {
    function getSeatDisplayByMainNo($mainSeatNo)
    {
        if (empty($mainSeatNo)) {
            return null;
        }

        $seatMap = collect(generateSeatNumbers());
        $seat = $seatMap->firstWhere('main', $mainSeatNo);

        if (!$seat) {
            return null;
        }

        // If floor name and floor seat number exist
        if (!empty($seat['floor_name']) && !empty($seat['floor'])) {
           

            return  $seat['floor']   . ' (' . $seat['floor_name'] . ')'; // e.g. F1-3
        }

        // Fallback if no floor info exists
        return  $seat['main'];
    }
}

if (!function_exists('getSeatDisplayShortFloor')) {
    function getSeatDisplayShortFloor($mainSeatNo)
    {
        if (empty($mainSeatNo)) {
            return null;
        }

        $seatMap = collect(generateSeatNumbers());
        $seat = $seatMap->firstWhere('main', $mainSeatNo);

        if (!$seat) {
            return null;
        }

        // If floor name and floor seat number exist
        if (!empty($seat['floor_name']) && !empty($seat['floor'])) {


            return  $seat['floor']   . ' Floor (' . $seat['floor_no'] . ')'; // e.g. F1-3
        }

        // Fallback if no floor info exists
        return  $seat['main'];
    }
}

if (!function_exists('getSeatDisplayByMainNo2')) {

    function getSeatDisplayByMainNo2($mainSeatNo, $branchId)
    {
        if (empty($mainSeatNo)) {
            return null;
        }

        $seatMap = collect(generateSeatNumbers2($branchId));
        $seat = $seatMap->firstWhere('main', $mainSeatNo);

        if (!$seat) {
            return null;
        }

        // If floor name and floor seat number exist
        if (!empty($seat['floor_name']) && !empty($seat['floor'])) {
           

            return  $seat['floor']   . ' (' . $seat['floor_name'] . ')'; // e.g. F1-3
        }

        // Fallback if no floor info exists
        return  $seat['main'];
    }
}
if (!function_exists('generateSeatNumbers2')) {

    function generateSeatNumbers2($branchId)
    {
            $result = [];
        $mainSeatNo = 1;

        // Get total seats dynamically
        $first_record = Hour::withoutGlobalScopes()->where('branch_id',$branchId)->first();
        $totalSeats = $first_record ? $first_record->seats : 0;

        if ($totalSeats <= 0) {
            return $result;
        }

        // Get floors ordered by floor number
        $floors = Floor::withoutGlobalScopes()->where('branch_id',$branchId)->orderBy('floor_no')->get();

        // Loop through all floors
        if (!empty($floors)) {
            foreach ($floors as $floor) {
                $startSeat = $floor->from_seat ?? 1;
                $endSeat   = $floor->to_seat ?? 0;



                for ($seatNo = $startSeat; $seatNo <= $endSeat && $mainSeatNo <= $totalSeats; $seatNo++) {
                    $result[] = [
                        'main'       => $mainSeatNo,
                        'floor'      => $seatNo,                  // FIXED: seatNo instead of floorSeatNo
                        'floor_name' => $floor->name,
                        'floor_no'   => $floor->floor_no,
                        'display'    => 'Seat No - ' . $seatNo . ' (' . $floor->name . ')'
                    ];

                    $mainSeatNo++;
                }
            }
        }

        // Add remaining seats (unassigned to any floor)
        while ($mainSeatNo <= $totalSeats) {
            $result[] = [
                'main' => $mainSeatNo,
                'floor' => null,
                'floor_name' => null,
                'display' => 'Seat No - ' . $mainSeatNo
            ];
            $mainSeatNo++;
        }

        return $result;
    }
}

if (!function_exists('getFloor')) {
    function getFloor()
    {
        $floors = Floor::where('branch_id', getCurrentBranch())->orderBy('floor_no')->get();
        return $floors ?? null;
    }
}

if (!function_exists('transaction_id')) {
    function transaction_id()
    {

        // Fixed year
        $year = "2025";

        // Get last transaction (only for 2025 IDs)
        $last = LearnerTransactionActivity::where('transaction_id', 'like', $year . '000%')
            ->orderBy('id', 'desc')
            ->first();

        if ($last && !empty($last->transaction_id)) {
            // extract last sequence (last 4 digits)
            $lastSeq = (int)substr($last->transaction_id, -4);
            $newSeq = str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
        } else {
            // first transaction
            $newSeq = "0001";
        }

        // Build transaction ID
        return  $year . "0000" . $newSeq;
    }
}
//for menual messages
if (!function_exists('notificationActive')) {

    function notificationActive()
    {
        // Step 1: Check if library has ANY remaining credits
        return $hasCredits = DB::table('notification_credits_usage')
            ->where('library_id', getLibraryId())
            ->where('remaining', '>', 0)
            ->exists();
    }
}
if (!function_exists('wabaNotificationActive')) {

    function wabaNotificationActive()
    {
        // Step 1: Check if library has ANY remaining credits
        return $hasCredits = DB::table('notification_credits_usage')
            ->where('library_id', getLibraryId())
            ->where('channel', 'waba')
            ->where('remaining', '>', 0)
            ->exists();
    }
}
if (!function_exists('textNotificationActive')) {

    function textNotificationActive()
    {
        // Step 1: Check if library has ANY remaining credits
        return $hasCredits = DB::table('notification_credits_usage')
            ->where('library_id', getLibraryId())
            ->where('channel', 'text')
            ->where('remaining', '>', 0)
            ->exists();
    }
}
if (!function_exists('autowabaNotificationActive')) {

    function autowabaNotificationActive()
    {
        // Step 1: Check if library has ANY remaining credits
        $hasCredits = DB::table('notification_credits_usage')
            ->where('library_id', getLibraryId())
            ->where('channel', 'waba')
            ->where('remaining', '>', 0)
            ->exists();

        if (!$hasCredits) {
            return false;
        }

        // Step 2: Check template settings for the current branch
        return NotificationChannelSetting::where('branch_id', getCurrentBranch())->whereNotNull('waba_template_id')->exists();
    }
}
if (!function_exists('autotextNotificationActive')) {

    function autotextNotificationActive()
    {
        // Step 1: Check if library has ANY remaining credits
        $hasCredits = DB::table('notification_credits_usage')
            ->where('library_id', getLibraryId())
            ->where('channel', 'text')
            ->where('remaining', '>', 0)
            ->exists();

        if (!$hasCredits) {
            return false;
        }

        // Step 2: Check template settings for the current branch
        return NotificationChannelSetting::where('branch_id', getCurrentBranch())->whereNotNull('text_template_id')->exists();
    }
}


if (!function_exists('filterPlantypeFromseat')) {
    function filterPlantypeFromseat($seat_no, $customerId = null)
    {

        $bookings = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
            ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->where('learner_detail.seat_no', $seat_no)
            ->when($customerId, function ($q) use ($customerId) {
                $q->where('learner_detail.learner_id', '!=', $customerId);
            })
            ->where('learners.status', 1)
            ->where('learner_detail.status', 1)
            ->where('learners.branch_id', getCurrentBranch())
            ->where('learner_detail.branch_id', getCurrentBranch())
            ->get([
                'learner_detail.plan_type_id',
                'plan_types.start_time',
                'plan_types.end_time',
                'plan_types.slot_hours'
            ]);

        // Step 2: Retrieve all plan types
        $planTypes = PlanType::get();

        // Step 3: Initialize removal array
        $planTypesRemovals = [];

        // Step 4: Calculate total booked hours
        $totalBookedHours = $bookings->sum('slot_hours');

        $nightseatBooked = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->where('learner_detail.seat_no', $seat_no)
            ->when($customerId, function ($q) use ($customerId) {
                $q->where('learner_detail.learner_id', '!=', $customerId);
            })
            ->where('learner_detail.status', 1)
            ->where('plan_types.day_type_id', 9)
            ->exists();

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
        $first_record = Hour::where('branch_id', getCurrentBranch())->select('hour')->first();
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

//check seat availability without learner id

if (!function_exists('checkPlanTypeSeatWise')) {

    function checkPlanTypeSeatWise(
        $seatNo,
        $requestPlanTypeId,
        $startDate,
        $endDate,
        $learnerId = null
    ) {
        $planType = PlanType::find($requestPlanTypeId);
        if (!$planType) {
            return false;
        }

        $startTime = $planType->start_time;
        $endTime   = $planType->end_time;

        // Fetch TODAY + FUTURE bookings (status ignored intentionally)
        $futureBookings = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->where('learner_detail.branch_id', getCurrentBranch())
            ->where('learner_detail.seat_no', $seatNo)
            ->whereDate('learner_detail.plan_start_date', '>=', Carbon::today())
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where('learner_detail.plan_start_date', '<=', $endDate)
                    ->where('learner_detail.plan_end_date', '>=', $startDate);
            })
            ->when($learnerId, function ($q) use ($learnerId) {
                // Ignore current learner when editing
                $q->where('learner_detail.learner_id', '!=', $learnerId);
            })
            ->get([
                'plan_types.start_time',
                'plan_types.end_time',
                'plan_types.day_type_id'
            ]);

        // ALL-DAY or NIGHT future booking blocks everything
        if ($futureBookings->whereIn('day_type_id', [8, 9])->count() > 0) {
            return false;
        }

        // Time overlap check
        foreach ($futureBookings as $booking) {
            if (
                $startTime < $booking->end_time &&
                $endTime > $booking->start_time
            ) {
                return false;
            }
        }

        return true; // ✅ Safe
    }
}


//check seat availability with learner id and without learner id

if (!function_exists('checkSeatAvailability')) {

    function checkSeatAvailability($seat_no, $learnerId, $planTypeId, $startDate, $endDate)
    {

        $planType = PlanType::find($planTypeId);
        if (!$planType) {
            return ['error' => true, 'message' => 'Invalid plan type'];
        }

        $startTime = $planType->start_time;
        $endTime   = $planType->end_time;
        $hours     = $planType->slot_hours;

        // Total allowed hours (16 / 24)
        $totalAllowedHours = Hour::where('branch_id', getCurrentBranch())->value('hour') ?? 0;
        if ($totalAllowedHours <= 0) {
            return ['error' => true, 'message' => 'Library hours not configured'];
        }
       

        // ACTIVE + FUTURE bookings (status ignored)
        $bookings = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->where('learner_detail.branch_id', getCurrentBranch())
            ->where('learner_detail.seat_no', $seat_no)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where('learner_detail.plan_start_date', '<=', $endDate)
                    ->where('learner_detail.plan_end_date', '>=', $startDate);
            })
            // ✅ IGNORE expired plans
            ->whereDate('learner_detail.plan_end_date', '>=', Carbon::today())
            ->when($learnerId, function ($q) use ($learnerId) {
                // Ignore own booking in edit mode
                $q->where('learner_detail.learner_id', '!=', $learnerId);
            })
            ->get([
                'learner_detail.learner_id',
                'plan_types.start_time',
                'plan_types.end_time',
                'plan_types.slot_hours',
                'plan_types.day_type_id'
            ]);
          

        // 1️⃣ All-day / Night block
        if ($bookings->whereIn('day_type_id', [8, 9])->count() > 0) {
            return [
                'error' => true,
                'message' => 'Seat already booked for full day or night'
            ];
        }

        // 2️⃣ Hour capacity check
        $alreadyBookedHours = $bookings
            ->groupBy('learner_id')
            ->map(fn($rows) => $rows->sum('slot_hours'))
            ->sum();
       

        // 3️⃣ Time overlap check
        foreach ($bookings as $booking) {
            if (
                $startTime < $booking->end_time &&
                $endTime > $booking->start_time
            ) {
                return [
                    'error' => true,
                    'message' => 'Time slot overlaps with existing booking'
                ];
            }
        }

        // 4️⃣ FINAL future check (mandatory as per your requirement)
        if (
            !checkPlanTypeSeatWise(
                $seat_no,
                $planTypeId,
                $startDate,
                $endDate,
                $learnerId
            )
        ) {
            return [
                'error' => true,
                'message' => 'This plan conflicts with a future booking'
            ];
        }

        return ['error' => false];
    }
}

if (!function_exists('normalizeTimeRange')) {
    function normalizeTimeRange($startTime, $endTime)
    {
        if ($startTime < $endTime) {
            // Normal same-day range
            return [[
                'start' => $startTime,
                'end'   => $endTime
            ]];
        }

        // Cross-midnight (Night / Custom)
        return [
            ['start' => $startTime, 'end' => '23:59:59'],
            ['start' => '00:00:00', 'end' => $endTime]
        ];
    }
}

//for all operation book,edit,renew,upgrade,changeplan/platype,reactive

if (!function_exists('checkAvailability')) {
    function checkAvailability(int $branchId,?int $seatNo,?int $learnerId,int $planTypeId,string $planId,string $startDate): array {


        // ✅ Seat not selected → always allowed
        if (empty($seatNo)) {
            return ['error' => false];
        }

        $planType = PlanType::find($planTypeId);
        if (!$planType) {
            return ['error' => true, 'message' => 'Invalid plan type'];
        }
        $startDate = Carbon::parse($startDate);

        $endDate = getEndDate($planId, $startDate,$branchId);

        $requestedRanges = normalizeTimeRange(
            $planType->start_time,
            $planType->end_time
        );
       
        

        $bookings = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->where('learner_detail.branch_id', $branchId)
            ->where('learner_detail.seat_no', $seatNo)
            ->where('learner_detail.plan_end_date', '>=', now())
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where('plan_start_date', '<=', $endDate)
                  ->where('plan_end_date', '>=', $startDate);
            })
            ->when($learnerId, fn ($q) =>
                $q->where('learner_detail.learner_id', '!=', $learnerId)
            )
            ->get([
                'plan_types.start_time',
                'plan_types.end_time',
                'plan_types.day_type_id'
            ]);

       
        
        foreach ($bookings as $booking) {

            $existingRanges = normalizeTimeRange(
                $booking->start_time,
                $booking->end_time
            );

            foreach ($requestedRanges as $req) {
                foreach ($existingRanges as $exist) {
                    if (
                        $req['start'] < $exist['end'] &&
                        $req['end'] > $exist['start']
                    ) {
                        // if(LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
                        // ->where('learner_detail.branch_id', $branchId)
                        // ->where('learner_detail.seat_no', $seatNo)->where('learner_detail.plan_start_date', '>', date('Y-m-d'))->exists()){
                        //     return [
                        //     'error' => true,
                        //     'message' => 'This seat already have a future booking'
                        //     ];
                        // }else{
                        //     return [
                        //     'error' => true,
                        //     'message' => 'Time slot overlaps with existing booking'
                        //     ];
                        // }

                         return [
                            'error' => true,
                            'message' => 'Time slot overlaps with existing booking'
                            ];
                      
                        
                    }
                }
            }
        }

        // future booking protection
        $futureBooking = LearnerDetail::where('branch_id', $branchId)
            ->where('seat_no', $seatNo)
            ->whereDate('plan_start_date', '>', today())
            ->when($learnerId, fn ($q) =>
                $q->where('learner_id', '!=', $learnerId)
            )
            ->exists();

        if ($futureBooking) {
            return [
                'error' => true,
                'message' => 'Seat already has a future booking'
            ];
        }


        return ['error' => false];
    }
}

if (!function_exists('getStatusFromBranch')) {
    function getStatusFromBranch($plan_end_date, $learner_id, $branchId)
    {

        $extendDay = 0;

        if ($branchId) {
            $branch = Branch::where('id', $branchId)->select('extend_days')->first();

            if ($branch) {
                $extendDay = $branch->extend_days;
            }
        }


        $today = Carbon::today();
        $endDate = Carbon::parse($plan_end_date);

        $diffInDays = $today->diffInDays($endDate, false);
        $inextendDate = $endDate->copy()->addDays($extendDay);
        $diffExtendDay = $today->diffInDays($inextendDate, false);

        $hasFuturePlan = LearnerDetail::where('learner_id', $learner_id)
            ->where('plan_end_date', '>', $today->copy()->addDays(5))->where('status', 0)
            ->exists();
        $hasPastPlan = LearnerDetail::where('learner_id', $learner_id)
            ->where('plan_end_date', '<=', $today->copy()->addDays(5))
            ->exists();

        $is_renew_update = alreadyRenewed($learner_id);
        $start_date = LearnerDetail::where('learner_id', $learner_id)
            ->where('plan_start_date',  '>', now())->where('status', 0)
            ->exists();
        $isfuture_booking = $start_date && !$hasPastPlan;
        $startDetail = LearnerDetail::where('learner_id', $learner_id)
            ->where('plan_start_date',  '>', now())->where('status', 0)->select('plan_start_date')->first();
        if ($startDetail) {
            $start_date = Carbon::parse($startDetail->plan_start_date);

            $startfrom = $today->diffInDays($start_date, false);
        } else {
            $startfrom = null;
        }

        if (Learner::where('id', $learner_id)->where('frozen_status', 1)->exists()) {
            return '<span class="text-success">Frozen</span>';
        } elseif ($diffInDays > 0 && !$isfuture_booking) {
            return '<span class="text-success">Plan Expires in ' . $diffInDays . ' days</span>';
        } elseif ($is_renew_update) {
            return '<span class="text-success"> 1 Plan in Queue</span>';
        } elseif ($isfuture_booking) {
            return '<span style="color: purple; ">Plan Starts in ' . $startfrom . ' Days</span>';
        } elseif ($diffInDays < 0 && $diffExtendDay > 0) {
            return '<span class="text-danger fs-10 d-block">Extension active! ' . abs($diffExtendDay) . ' days left.</span>';
        }elseif ($diffInDays < 0 && $diffExtendDay == 0) {
            return '<span class="text-danger fs-10 d-block">Extension Expires today. </span>';
        } elseif (($diffInDays < 0 && $diffExtendDay == 0)) {
            return ' <span class="text-warning fs-10 d-block">Plan Expires today</span>';
        } elseif ($diffInDays == 0) {
            return '<span class="text-warning fs-10 d-block">Plan Expires today</span>';
        } else {
            return '<span class="text-danger fs-10 d-block">Plan Expired ' . abs($diffInDays) . ' days ago</span>';
        }
    }
}
if (!function_exists('whatsappReceiptMessage')) {
    function whatsappReceiptMessage($learner)
    {
        $transaction = learnerTransaction($learner->id, $learner->learner_detail_id);
        $receiptUrl = null;
        if ($transaction) {
            $receiptUrl = route('receipt.view', $transaction->id);
        }
        
        $shortUrl   = makeTinyUrl($receiptUrl);
        return rawurlencode(
            "Dear {$learner->name},\n\n"
                . "Welcome to " . getCurrentBranchName() . ". We are pleased to have you with us.\n\n"

                . "*Learner Information*\n"
                . "Name: {$learner->name}\n"
                . "Member ID: {$learner->learner_no}\n"
                . "Seat No: " . ($learner->seat_no ? getSeatDisplayByMainNo($learner->seat_no) : 'GEN') . "\n\n"

                . "*Plan Details*\n"
                . "Plan Name: {$learner->plan_name}\n"
                . "Plan Type: {$learner->plan_type_name}\n"
                . "Start Date: " . Carbon::parse($learner->plan_start_date)->format('d M Y') . "\n"
                . "End Date: " . Carbon::parse($learner->plan_end_date)->format('d M Y') . "\n\n"

                . "*Payment Summary*\n"
                . "Amount Paid: ₹{$transaction->paid_amount}/-\n"
                . "Pending Amount: ₹{$transaction->pending_amount}/-\n\n"

                . "*Download Payment Receipt*\n"
                . "{$shortUrl}\n\n"

                . "For any assistance, please feel free to contact us.\n\n"
                . "Regards,\n"
                . getCurrentBranchName()
        );
    }
}
if (!function_exists('makeTinyUrl')) {
function makeTinyUrl($longUrl)
{
    try {
        if (!$longUrl) {
            return null;
        }

        $apiUrl = 'https://tinyurl.com/api-create.php?url=' . urlencode($longUrl);

        $context = stream_context_create([
            'http' => [
                'timeout' => 5 // ⏱ avoid hanging request
            ]
        ]);

        $shortUrl = @file_get_contents($apiUrl, false, $context);

        return $shortUrl ?: $longUrl; // fallback to original URL
    } catch (\Exception $e) {
        \Log::error('TinyURL Error', [
            'url' => $longUrl,
            'error' => $e->getMessage()
        ]);

        return $longUrl; // fallback
    }
}
}

if (!function_exists('calculatePlanDays')) {

    function calculatePlanDays(
        int $number,
        string $type,
        ?int $baseMonthDays = null
    ): ?int {

        $type = strtoupper($type);

        if ($type === 'MONTH') {
            return $baseMonthDays
                ? $baseMonthDays * $number
                : null; // safety fallback
        }

        if ($type === 'WEEK') {
            return $number * 7;
        }

        if ($type === 'DAY') {
            return $number;
        }

        return null;
    }
}

if (!function_exists('authLibraryId')) {

    function authLibraryId()
    {
        $user = auth('library_api')->user();

        if ($user instanceof \App\Models\Library) {
            return $user->id;
        }

        if ($user instanceof \App\Models\LibraryUser) {
            return $user->library_id;
        }

        return null;
    }
}

if (!function_exists('changeFormate')) {
    function changeFormate($date){
            return \Carbon\Carbon::parse($date)->format('d-m-Y');
    }
}

require_once __DIR__ . '/privacy_helper.php';

