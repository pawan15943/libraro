<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLibraryRequest;
use App\Models\City;
use App\Models\Complaint;
use App\Models\Expense;
use App\Models\Feedback;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerFeedback;
use App\Models\LearnerTransaction;
use App\Models\LearnerTransactionActivity;
use App\Models\Library;
use App\Models\LibraryEnquiry;
use App\Models\LibrarySetting;
use App\Models\LibraryTransaction;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlanType;
use App\Models\Seat;
use App\Models\Setting;
use App\Models\State;
use App\Models\Subscription;
use App\Models\Suggestion;
use App\Models\TempOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Notifications\VerifyEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Services\LibraryService;
use Auth;
use DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use App\Helpers\ReferralHelper;
use App\Models\Branch;
use App\Models\LibraryReferral;

class LibraryController extends Controller
{
    protected $libraryService;
    public function __construct(LibraryService $libraryService)
    {
        $this->libraryService = $libraryService;
    }
    
 
    public function create(){
        $states=State::where('is_active',1)->get();
        return view('library.create',compact('states'));
    }

    protected function libraryValidation(Request $request)
    {
        $rules = [
            'library_name'   => 'required|string|max:255',
            'email'  => [
            'required',
            'email',
            'max:255',
            'unique:libraries,email',
            function ($attribute, $value, $fail) {
                $library = \App\Models\Library::where('email', $value)->first();
                if ($library) {
                    if (!$library->email_verified_at) {
                        $fail('You are already registered with us. Your email verification is pending. Please use the login option to complete it.');
                    } else {
                        $fail('Email already exists.');
                    }
                }
            }
        ],
            'library_mobile' => 'required|digits:10',
            'state_id'       => 'nullable|exists:states,id',
            'city_id'        => 'nullable|exists:cities,id',
            'library_address'=> 'nullable|string|max:500',
            'library_zip'    => 'nullable|digits:6',
            'library_type'   => 'nullable|string|max:255',
            'library_owner'  => 'nullable|string|max:255',
            'library_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:200',
            'password'       => 'required|string|min:8',
            'terms'          => 'accepted',
            'library_owner_email'=> 'nullable|email|max:255',
            'library_owner_contact' => 'nullable|digits:10',
        ];
        

        return Validator::make($request->all(), $rules);
    }

    public function sendVerificationEmail($library)
    {
        \Log::info('sendVerificationEmail');
        // Prepare the data to send to the email view
        $data = [
            'name' => $library->library_name,
            'email' => $library->email,
            'otp' => $library->email_otp,
        ];

        Mail::send('email.verify-email', $data, function($message) use ($data) {
            $message->to($data['email'], $data['name'])->subject('Verify Your Email Address');
        });
    }

    public function verifyOtp(Request $request)
    {
       
        // Validate the input  login detail all
        $request->validate([
            'email' => 'required|email',
            'email_otp' => 'required',
        ]);

        // Find the library by email
        $library = Library::where('email', $request->email)->first();
     

        if (!$library) {
            return redirect()->back()->withErrors(['email' => 'Library not found']);
        }
        
        // Check if the OTP matches
        if ($library->email_otp == $request->email_otp) {
           
            $library->email_verified_at = now();
            $library->save();

             
            // Log the user in (assuming you're using Laravel's built-in auth)
            Auth::guard('library')->login($library);
            
            // Now that the user is logged in, you can access their role
            $user = Auth::guard('library')->user();
            if ($user && !$user->hasRole('admin', 'library')) {
                // Assign the 'admin' role to the user under the 'library' guard
                $user->assignRole('admin');
            }

            
            return redirect()->route('library.home')->with('success', 'Email verified and logged in successfully.');
        } else {
            return redirect()->back()->withErrors(['email_otp' => 'Invalid OTP. Please try again.']);
        }
    }

    public function sidebarRedirect(){
        $redirectUrl = $this->libraryService->checkLibraryStatus();
       
            if ($redirectUrl) {
                return redirect($redirectUrl);
            }
    }
    public function choosePlan()
    {
        
        $subscriptions = Subscription::with('permissions')->get();
        $premiumSub=Subscription::orderBy('id','DESC')->first();
        $features=DB::table('subscription_plan_features')->where('feature_status',1)->get();
        return view('register.plan', compact('subscriptions','premiumSub','features'));
    }

    public function store(Request $request)
    {
      
        // Validate the request
        $validatedData = $this->libraryValidation($request);
        
        if ($validatedData->fails()) {
            return redirect()->back()->withErrors($validatedData)->withInput();
        }

        $validated = $validatedData->validated();
        unset($validated['terms']);
        $validated['original_password'] = $validated['password'];

        $validated['password'] = bcrypt($validated['password']);
        $validated['slug']=Str::slug($validated['library_name']);
        try {
            $library = Library::create($validated);

            if ($library) {
               
                $otp = Str::random(6); 
                $library->email_otp = $otp;
                $library->referral_code = ReferralHelper::generateLibraryReferralCode($library->id);
                $library->save();
                
                if ($request->referral_code) {
                    $referrer = Library::where('referral_code', $request->referral_code)->first();

                    if ($referrer && $referrer->id !== $library->id) {

                        $library->referred_by = $referrer->id;
                        $library->save();

                        LibraryReferral::create([
                            'referrer_library_id' => $referrer->id,
                            'referred_library_id' => $library->id,
                            'referral_code' => $request->referral_code,
                            'referral_type' => $request->has('qr') ? 'qr' : 'code',
                            'status' => 'pending'
                        ]);
                    }
                }
                
                
                try {
                 
                    $this->sendVerificationEmail($library);
                    \Log::info('sendVerificationEmail success', [
                        'library_id' => $library->id ?? null,
                    ]);

                } catch (\Throwable $e) {

                \Log::error('sendVerificationEmail failed', [
                        'library_id' => $library->id ?? null,
                        'email'      => $library->email ?? null,
                        'message'    => $e->getMessage(),
                        'file'       => $e->getFile(),
                        'line'       => $e->getLine(),
                    ]);

                    // ✔ Continue execution (do nothing else)
                }
                                
                session(['library_email' => $library->email]);

                return redirect()->route('verification.notice')
                    ->with('message', 'Please verify your email to continue.');
            } else {
                return response()->json(['error' => 'Library creation failed.'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function payment(Request $request)
    {
        
        if(session('selected_plan_id') && session('selected_plan_mode')){
           
                $subscriptionId = session('selected_plan_id');
                $planMode = session('selected_plan_mode');
        }else{
     
              $request->validate([
                'library_id'      => 'required',
                'subscription_id'=> 'required',
                'plan_mode'       => 'required',
            ]);

            
            $subscriptionId = $request->subscription_id;
            $planMode       = $request->plan_mode;
        }
        
      
        if ($request->library_id) {
            $libraryId = $request->library_id;
        } elseif (Auth::check()) { 
            $libraryId = getAuthenticatedUser()->id;
        } else {
            return redirect()->back()->with('error', 'Library ID not provided.');
        }

         // ✅ ONLY redirect when plan is MISSING
        if (!$subscriptionId || !$planMode) {
            return redirect('subscriptions.choosePlan')
                ->with('error', 'Plan not selected');
        }
      
        $subscription = Subscription::findOrFail($subscriptionId);
        if (!$subscription) {
            return redirect('subscriptions.choosePlan')->with('error', 'No valid subscription selected');

        }
      
        
       
        try {
            
             $data = $this->razorpayPaymentCore((int) $subscriptionId,(int) $planMode,(int) $libraryId);
            
        
             if ($data['type'] === 'free') {
              
                $this->finalizeTransaction($data['transaction'], 'free');

                return redirect()
                    ->route('library.home')
                    ->with('success', 'Free plan activated successfully');
            }
           
            return view('library.razorpay-checkout', [
                'key'        => config('services.razorpay.key'),
                'order_id'   => $data['order']['id'],
                'amount'     => $data['order']['amount'],
                'currency'   => $data['order']['currency'],
                'library_transaction_id' => $data['transaction']->id,
                'name'       => 'Library Payment',
                'description'=> 'Library Payment',
            ]);

        } catch (\Exception $e) {
                    // 🔥 ANY CRASH → HOME PAGE
                \Log::error('Payment crash', [
                    'message' => $e->getMessage(),
                ]);

                return redirect()->route('library.home')
                    ->with('error', 'Something went wrong. Please try again.');
            
        }

    }

   
    private function razorpayPaymentCore(int $subscriptionId, int $planMode, int $libraryId): array
    {
        /* ---------------- PLAN & AMOUNT ---------------- */

        $subscription = Subscription::findOrFail($subscriptionId);

        match ($planMode) {
            1 => [$month, $amount] = [1,  $subscription->monthly_fees],
            2 => [$month, $amount] = [12, $subscription->yearly_fees],
            3 => [$month, $amount] = [3,  $subscription->three_monthly_fees],
            4 => [$month, $amount] = [6,  $subscription->six_monthly_fees],
            5 => [$month, $amount] = [24, $subscription->two_yearly_fees],
            default => throw new \Exception('Invalid plan mode'),
        };

        /* ---------------- GST & DISCOUNT ---------------- */

        $gstRow   = DB::table('gst_discount')->first();
        $gst      = $gstRow->gst ?? 0;
        $discount = $gstRow->discount ?? 0;

        $discountAmount = $amount * ($discount / 100);
        $finalAmount    = ($amount - $discountAmount) * (1 + ($gst / 100));

        /* ---------------- TRANSACTION ---------------- */

        $transaction = LibraryTransaction::updateOrCreate(
            [
                'library_id' => $libraryId,
                'is_paid'    => 0,
            ],
            [
                'subscription' => $subscriptionId,
                'amount'       => $amount,
                'paid_amount'  => $finalAmount,
                'month'        => $month,
                'gst'          => $gst,
                'discount'     => $discount,
            ]
        );
        /* ---------------- For Free Plan ---------------- */
        if ($finalAmount <= 0) {
            return [
                'type'        => 'free',
                'transaction' => $transaction,
            ];
        }
        
       

        /* ---------------- RAZORPAY ORDER ---------------- */

        $response = Http::withBasicAuth(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        )
        
        ->timeout(20)
        ->retry(2, 200)
        ->post('https://api.razorpay.com/v1/orders', [
            'amount'   => (int) ($finalAmount * 100),
            'currency' => 'INR',
            'receipt'  => 'TXN_'.$transaction->id,
            'payment_capture' => 1,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Razorpay order creation failed');
        }

        return [
            "type" => "paid",
            'transaction' => $transaction,
            'order'       => $response->json(),
            'amount'      => $finalAmount,
            'currency'    => 'INR',
        ];
    }

    private function finalizeTransaction($transaction, $paymentId)
    {
      
       
        $duration = $transaction->month ?? 0;

        if (LibraryTransaction::where('library_id', $transaction->library_id)
            ->where('status', 1)->exists()) {

            $last = LibraryTransaction::where('library_id', $transaction->library_id)
                ->where('status', 1)
                ->latest()
                ->first();

            $start = Carbon::parse($last->end_date)->addDay();
            $status = 0;
        } else {
            $start = now();
            $status = 1;
        }

        $end = $start->copy()->addMonths($duration);

        $transaction->update([
            'start_date'       => $start,
            'end_date'         => $end,
            'transaction_date' => now(),
            'payment_mode'     => 1,
            'is_paid'          => 1,
            'status'           => $status,
            'transaction_id'   => $paymentId,
        ]);

        $this->handleReffrel($transaction, $status);

        Library::where('id', $transaction->library_id)
            ->update(['is_paid' => 1]);

            if($status==1){
                Library::where('id', $transaction->library_id)->update([
                    'library_type' => $transaction->subscription,
                ]);
            }
       
    }

    
    public function handleSuccess(Request $request)
    {
        $razorpayPaymentId = $request->input('razorpay_payment_id');
        $razorpayOrderId = $request->input('razorpay_order_id');
        $razorpaySignature = $request->input('razorpay_signature');
        $libraryTransactionId = $request->input('library_transaction_id');

       
        $tempOrder = TempOrder::create([
            'razorpay_order_id' => $razorpayOrderId,
            'library_transaction_id' => $libraryTransactionId,
            'payment_status' => 'pending',                                                                                          
        ]);
        // Check if necessary data is available
        if (!$razorpayPaymentId || !$razorpayOrderId || !$razorpaySignature || !$libraryTransactionId) {
            $tempOrder->update([
                'payment_status' => 'fail',
                'error_message' => 'Invalid payment data.',
                
            ]);
            // throw new \Exception('Invalid payment data.');
            return response()->json(['success' => false, 'error_url' => route('library.payment.error'),'message' => 'Invalid payment data.']);
        }
    
        // Verify the payment signature
        $keySecret =  config('services.razorpay.secret');
        $generatedSignature = hash_hmac('sha256', $razorpayOrderId . "|" . $razorpayPaymentId, $keySecret);
    
        if ($generatedSignature !== $razorpaySignature) {
            $tempOrder->update([
                'payment_status' => 'fail',
                'error_message' => 'Payment verification failed.',
            ]);
            // throw new \Exception('Payment verification failed.');
            return response()->json(['success' => false, 'error_url' => route('library.payment.error'), 'message' => 'Payment verification failed.']);
        }
    
        // Update the transactions table
        $transaction = LibraryTransaction::where('id', $libraryTransactionId)->first();
     
        if (!$transaction) {
            $tempOrder->update([
                'payment_status' => 'fail',
                'error_message' => 'Transaction not found.',
            ]);
            // throw new \Exception('Transaction not found.');
            return response()->json(['success' => false, 'error_url' => route('library.payment.error'),'message' => 'Transaction not found.']);
        }
        try {
            if ($transaction) {
                
                $this->finalizeTransaction($transaction, $razorpayOrderId);

                // Update temp_order status
                $tempOrder->update([
                    'payment_status' => 'success',
                ]);
               
                
                return response()->json(['success' => true, 'redirect_url' => route('library.home')]);
            
            }
        } catch (\Exception $e) {
            // Log the exception for debugging
            \Log::error('Transaction Processing Error: ' . $e->getMessage());
        
            // Update temp_order status to failed
            if (isset($tempOrder)) {
                $tempOrder->update([
                    'payment_status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        
            return response()->json(['success' => false, 'message' => 'An error occurred during payment processing. Please try again.']);
        }
    
        
        
    }

    private function handleReffrel($transaction,$status){
         $activePaidPlanCount = LibraryTransaction::where('library_id', $transaction->library_id)
                    ->where('status', 1)
                    ->where('is_paid', 1)
                    ->count();

                $pendingReferralExists = LibraryReferral::where('referred_library_id', $transaction->library_id)
                    ->where('status', 'pending')
                    ->exists();

                /* 🔍 Log all condition values */
                Log::info('Referral condition check', [
                    'library_id'             => $transaction->library_id,
                    'status_is_active'       => $status,
                    'active_paid_plan_count' => $activePaidPlanCount,
                    'pending_referral_exist' => $pendingReferralExists,
                ]);

                if ($status == 1 && ($activePaidPlanCount==1) && $pendingReferralExists) {
                    Log::info('Referral marked as completed', [
                        'library_id' => $transaction->library_id
                    ]);
                    LibraryReferral::where('referred_library_id', $transaction->library_id)
                        ->where('status', 'pending')
                        ->update([
                            'status' => 'completed'
                        ]);
                }
                Log::warning('Referral condition failed', [
                    'library_id' => $transaction->library_id,
                    'reason' => [
                        'status_check_failed'       => !$status,
                        'plan_count_not_one'        => $activePaidPlanCount !== 1,
                        'no_pending_referral'       => !$pendingReferralExists,
                    ]
                ]);
    }

    public function handleError(){
        return view('library.payment-error');
    }

    public function masterConfigration(Request $request){
        
        $operatingHour=Hour::select('hour')->first();

         $plan = Plan::where('library_id', getLibraryId())
        ->where('plan_id', 1)
        ->where('type', 'MONTH')
        ->first();
        $branch=getCurrentBranch();
       $planTypes = [];

        if ($branch && $plan) {
            $planTypes = PlanType::with(['price' => function ($q) use ($plan) {
                    $q->where('plan_id', $plan->id);
                }])
                ->where('branch_id', $branch)
                ->get();
        }
      
        return view('register.library-confrigration',compact('operatingHour','planTypes'));
    }
   public function configrationStore(Request $request)
{
    /* =========================
       FILTER EMPTY ROWS
    ========================= */
    $planTypes = collect($request->plan_types ?? [])
        ->filter(function ($row) {
            return isset($row['day_type_id']) &&
                   ($row['day_type_id'] !== '' ||
                    !empty($row['start_time']) ||
                    !empty($row['end_time']));
        })
        ->values()
        ->toArray();

    $request->merge(['plan_types' => $planTypes]);

    /* =========================
       VALIDATION
    ========================= */
    $validator = Validator::make($request->all(), [
        'plan_types'                   => 'required|array|min:1',
        'plan_types.*.day_type_id'     => 'required',
        'plan_types.*.start_time'      => 'required|date_format:H:i',
        'plan_types.*.end_time'        => 'required|date_format:H:i',
        'plan_types.*.slot_hours'      => 'required|numeric|min:1',
        'plan_types.*.price'           => 'required|numeric|min:0',
        'plan_types.*.custom_plan_type'=> 'nullable|string|max:100',
    ]);

    $validator->after(function ($validator) use ($request) {
        foreach ($request->plan_types as $index => $row) {
            if (
                isset($row['day_type_id']) &&
                (int)$row['day_type_id'] === 0 &&
                empty($row['custom_plan_type'])
            ) {
                $validator->errors()->add(
                    "plan_types.$index.custom_plan_type",
                    'Custom Plan Type Name is required'
                );
            }
        }
    });

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    /* =========================
       BRANCH CHECK
    ========================= */
    $branchCount = Branch::where('library_id', getLibraryId())->count();
    if ($branchCount < 1) {
        return response()->json([
            'status' => false,
            'message' => 'No configuration required in your branch'
        ], 400);
    }

    $branch = Branch::where('id', getCurrentBranch())->first();

    $plan = Plan::where('library_id', getLibraryId())
        ->where('plan_id', 1)
        ->where('type', 'MONTH')
        ->first();

    if (!$plan) {
        return response()->json([
            'status' => false,
            'message' => 'Ops, System not found any plan to proceed for shifts.'
        ], 400);
    }

    $branchRecord = Hour::where('branch_id', getCurrentBranch())->first();

    $existingPlanTypeCount = PlanType::where('branch_id', getCurrentBranch())->count();
    $isFirstTimeSetup = $existingPlanTypeCount === 0;

    DB::beginTransaction();

    try {

        /* =========================
           DELETE REMOVED SHIFTS
        ========================= */
        $submittedIds = collect($request->plan_types)
            ->pluck('plan_type_id')
            ->filter()
            ->toArray();

        $existingShifts = PlanType::where('branch_id', getCurrentBranch())->get();

        foreach ($existingShifts as $planType) {

            if (!in_array($planType->id, $submittedIds)) {

                $exists = LearnerDetail::where('plan_type_id', $planType->id)->exists();

                if ($exists) {
                    throw new \Exception(
                        "Shift '{$planType->name}' cannot be deleted because learners are enrolled."
                    );
                }

                PlanPrice::where('plan_type_id', $planType->id)->forcedelete();
                $planType->forcedelete();
            }
        }

        /* =========================
           GLOBAL SHIFT COVERAGE CHECK
        ========================= */
        $coveredMinutes = [];

        foreach ($request->plan_types as $row) {

            $start = Carbon::parse($row['start_time']);
            $end   = Carbon::parse($row['end_time']);

            if ($end->lessThanOrEqualTo($start)) {
                $end->addDay();
            }

            while ($start < $end) {
                $coveredMinutes[$start->format('H:i')] = true;
                $start->addMinute();
            }
        }

        $totalCoveredHours = count($coveredMinutes) / 60;

        if ($branchRecord->hour != 24 && $totalCoveredHours > $branchRecord->hour) {
            throw new \Exception('Shift timing exceeds library hours.');
        }

        /* =========================
           CREATE / UPDATE SHIFTS
        ========================= */
        $timePairs = [];
        $isCreating = false;
        $isUpdating = false;

        foreach ($request->plan_types as $row) {

            if ($row['slot_hours'] > $branchRecord->hour && $branchRecord->hour != 24) {
                throw new \Exception('Selected hours exceed the library’s available hours.');
            }

            $start = Carbon::parse($row['start_time']);
            $end   = Carbon::parse($row['end_time']);

            if ($end->lessThanOrEqualTo($start)) {
                $end->addDay();
            }

            $actualHours = $start->diffInHours($end);

            if ($row['slot_hours'] != $actualHours) {
                throw new \Exception(
                    "Slot hours must match shift time ({$actualHours} hours)."
                );
            }
             /* Plan name logic */
            $dayTypeId = (int) $row['day_type_id'];

            /* Duplicate check inside request */
            $pairKey = $row['start_time'] . '-' . $row['end_time'];
            $rowId   = $row['plan_type_id'] ?? 'new';

            if (isset($timePairs[$pairKey]) && $timePairs[$pairKey] != $rowId && $dayTypeId!=0) {
                throw new \Exception(
                    'Duplicate shift detected with same start and end time.'
                );
            }

            $timePairs[$pairKey] = $rowId;

            if($dayTypeId != 0)  {          
                /* Duplicate check in DB */
                $query = PlanType::where('branch_id', $branch->id)
                    ->where('start_time', $row['start_time'])
                    ->where('end_time', $row['end_time'])->where('day_type_id', '!=', 0);
                

                if (!empty($row['plan_type_id'])) {
                    $query->where('id', '!=', $row['plan_type_id']);
                }
        

                if ($query->exists()) {
                    throw new \Exception('A shift with the same time range already exists.');
                }

            }
            if($dayTypeId != 0)  {          
                /* Duplicate check in DB */
                $query = PlanType::where('branch_id', $branch->id)->where('day_type_id', '!=', 0)->where('day_type_id', $dayTypeId);
                

                if (!empty($row['plan_type_id'])) {
                    $query->where('id', '!=', $row['plan_type_id']);
                }
        

                if ($query->exists()) {
                    throw new \Exception('A shift with the same name already exists.');
                }

            }

            $planTypeName = match ($dayTypeId) {
                1 => 'Full Day',
                2 => 'First Half',
                3 => 'Second Half',
                8 => 'All Day',
                9 => 'Full Night',
                0 => $row['custom_plan_type'],
                default => 'Custom',
            };

            /* CREATE or UPDATE */
            if (!empty($row['plan_type_id'])) {

                $isUpdating = true;

                $planType = PlanType::where('id', $row['plan_type_id'])
                    ->where('branch_id', $branch->id)
                    ->first();

                if (!$planType) {
                    throw new \Exception('Invalid shift selected.');
                }

                $planType->update([
                    'library_id'  => getLibraryId(),
                    'branch_id'   => $branch->id,
                    'day_type_id' => $row['day_type_id'],
                    'name'        => $planTypeName,
                    'start_time'  => $row['start_time'],
                    'end_time'    => $row['end_time'],
                    'slot_hours'  => $row['slot_hours'],
                    'image'       => 'public/img/booked.png',
                ]);

            } else {

                $isCreating = true;

                $planType = PlanType::create([
                    'library_id'  => getLibraryId(),
                    'branch_id'   => $branch->id,
                    'day_type_id' => $row['day_type_id'],
                    'name'        => $planTypeName,
                    'start_time'  => $row['start_time'],
                    'end_time'    => $row['end_time'],
                    'slot_hours'  => $row['slot_hours'],
                    'image'       => 'public/img/booked.png',
                ]);
            }

            /* Price */
            if (!empty($row['plan_price_id'])) {
                PlanPrice::where('id', $row['plan_price_id'])->update([
                    'price'        => $row['price'],
                    'plan_type_id' => $planType->id,
                ]);
            } else {
                PlanPrice::create([
                    'library_id'   => getLibraryId(),
                    'branch_id'    => $branch->id,
                    'plan_id'      => $plan->id,
                    'plan_type_id' => $planType->id,
                    'price'        => $row['price'],
                ]);
            }
        }

        /* =========================
           LIBRARY CODE GENERATION
        ========================= */
        // library subscription update
        $library = Library::where('id', getAuthenticatedUser()->id)->first();
    
        if (empty($library->library_no)) {
            $libraryCode = generateLibraryCode();
            $library->library_no = $libraryCode;
            $library->save();
            DB::commit();

                try {
                    \Log::info('sendSuccessfulEmail');
                    $this->sendSuccessfulEmail($library);
                } catch (\Throwable $e) {
                    Log::error('Success email sending FAILED', [
                        'library_id' => $library->id ?? null,
                        'email'      => $library->email ?? null,
                        'error'      => $e->getMessage(),
                    ]);
                }

        }

        /* =========================
           SETUP REDIRECT
        ========================= */
        $setup    = '';
        $redirect = null;

        if ($isFirstTimeSetup && $isCreating && !$isUpdating) {
            $setup    = 'completed';
            $redirect = route('library.home', ['setup' => $setup]);
        }

        DB::commit();

        return response()->json([
            'status'   => true,
            'redirect' => $redirect,
            'message'  => 'Library shifts saved successfully.',
            'setup'    => $setup
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], 400);
    }
}

    public function profile()
    {
        if( session('selected_plan_id') && session('selected_plan_mode')){
            session()->forget(['selected_plan_id', 'selected_plan_mode']);

        }
        $library = Library::where('id', getAuthenticatedUser()->id)->first();  
        
        $states=State::where('is_active',1)->get();
        $citis=City::where('is_active',1)->get();
        $features=DB::table('features')->whereNull('deleted_at')->get();
        
        return view('library.profile', compact('library', 'states','citis','features'));
    }

    public function updateProfile(Request $request)
    {
       
        $validated = $request->validate([
            'library_owner' => 'required|string|max:255',
           
        ]);
        
      
        $library = Library::where('id', getAuthenticatedUser()->id)->first();
        $libraryCode = $this->generateLibraryCode();
       
        $update=$library->update($validated);
      
        if ($update) {
            $library->update(['is_profile' => 1]);
            if (empty($library->library_no)) {
                $libraryCode = $this->generateLibraryCode();
                $library->library_no = $libraryCode;
                $library->save();
                 \Log::info('sendSuccessfulEmail');
                 $this->sendSuccessfulEmail($library);
            }
        }
        

        return redirect()->route('library.master')->with('success', 'Profile updated successfully!');
    }


   

    public function transaction(){
        $data = Library::where('id', getAuthenticatedUser()->id)
        ->with('subscription.permissions')  // Fetch associated subscription and permissions
        ->first();
        $plan=Subscription::where('id',$data->library_type)->first();
        $transaction=LibraryTransaction::where('library_id',getAuthenticatedUser()->id)->where('is_paid',1)->get();
        return view('library.transaction',compact('transaction','plan','data'));
    }
    public function myplan(){
        $data = Library::where('id', getAuthenticatedUser()->id)
        ->with('subscription.permissions')  // Fetch associated subscription and permissions
        ->first();
        $month=LibraryTransaction::where('library_id',getAuthenticatedUser()->id)->where('is_paid',1) ->orderBy('id', 'desc')
        ->first();
        
        $plan=Subscription::where('id',$data->library_type)->first();
       
        return view('library.my-plan',compact('data','month','plan'));
    }

    // from superadmin side
    public function showLibrary($id){
      
        $library=Library::findOrFail($id);
        $plan=Subscription::where('id',$library->library_type)->with('permissions')->first();
        
        $library_transaction=LibraryTransaction::withoutGlobalScopes()->where('library_id',$id)->where('is_paid',1)->first();
        $library_all_transaction=LibraryTransaction::withoutGlobalScopes()->where('library_id',$id)->get();
       
        return view('administrator.library-view',compact('library','plan','library_transaction','library_all_transaction'));
    }

    public function destroyLearners($id)
    {
        $libraryId = $id;
    
        try {
            DB::transaction(function () use ($libraryId) {
                // Step 1: Delete learner transactions manually (still needed if not cascaded)
                LearnerTransaction::withoutGlobalScopes()
                    ->where('library_id', $libraryId)
                    ->delete();
    
                // Step 2: Force delete the learners — now their learner_detail will auto-delete
                Learner::where('library_id', $libraryId)
                    ->withTrashed()
                    ->forceDelete();
    
            });
    
            return response()->json(['message' => 'All learners and related data have been successfully deleted.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error occurred: ' . $e->getMessage()], 500);
        }
    }
    


    public function destroyAllMasters($id)
    {
        $libraryId = $id;

        // Check if there are any learners associated with this library
        $learnerCount = Learner::where('library_id', $libraryId)->count();
        $learnerDetailCount = LearnerDetail::withoutGlobalScopes()->where('library_id', $libraryId)->count();
        $learnerTransCount = LearnerTransaction::withoutGlobalScopes()->where('library_id', $libraryId)->count();

        if ($learnerCount == 0 && $learnerDetailCount == 0 && $learnerTransCount == 0) {
            DB::beginTransaction();
            try {
                // Step 1: Delete records from PlanPrice
                $deletedPricesCount = PlanPrice::withoutGlobalScopes()
                    ->where('library_id', $libraryId)
                    ->withTrashed()
                    ->forceDelete();

                // Log the count of deleted prices
                if ($deletedPricesCount > 0) {
                    Log::info("$deletedPricesCount plan prices deleted.");
                } else {
                    Log::info("No plan prices to delete.");
                }

                // Step 2: Delete records from PlanType
                $deletedTypesCount = PlanType::withoutGlobalScopes()
                    ->where('library_id', $libraryId)
                    ->withTrashed()
                    ->forceDelete();

                // Log the count of deleted types
                if ($deletedTypesCount > 0) {
                    Log::info("$deletedTypesCount plan types deleted.");
                } else {
                    Log::info("No plan types deleted.");
                }

                // Step 3: Delete records from Plan
                $deletedPlansCount = Plan::withoutGlobalScopes()
                    ->where('library_id', $libraryId)
                    ->withTrashed()
                    ->forceDelete();

                // Log the count of deleted plans
                if ($deletedPlansCount > 0) {
                    Log::info("$deletedPlansCount plans deleted.");
                } else {
                    Log::info("No plans deleted.");
                }

             
                // Step 5: Delete records from Hour
                Hour::withoutGlobalScopes()
                    ->where('library_id', $libraryId)
                    ->withTrashed()
                    ->forceDelete();

                DB::commit();

                return response()->json(['message' => 'All master records have been successfully deleted.']);
            } catch (\Exception $e) {
                // Rollback the transaction in case of any error
                DB::rollBack();
                return response()->json(['message' => 'Error occurred: ' . $e->getMessage()], 500);
            }
        } else {
            return response()->json(['message' => 'Cannot delete masters because learners are associated with this library.'], 400);
        }
    }

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
    
    public function getSubscriptionPrice(Request $request)
    {
        
        if($request->plan_mode==1){
            $subscription_prices = Subscription::with('permissions')->select('monthly_fees as fees','id','slash_price','plan_description')->get();
        }elseif($request->plan_mode==2){
            $subscription_prices = Subscription::with('permissions')->select('yearly_fees as fees','id','yearly_slash_price as slash_price','plan_description')->get();

        }elseif($request->plan_mode==3){
            $subscription_prices = Subscription::with('permissions')->select('three_monthly_fees as fees','id','three_monthly_slash_price as slash_price','plan_description')->get();

        }elseif($request->plan_mode==4){
            $subscription_prices = Subscription::with('permissions')->select('six_monthly_fees as fees','id','six_monthly_slash_price as slash_price','plan_description')->get();

        }elseif($request->plan_mode==5){
            $subscription_prices = Subscription::with('permissions')->select('two_yearly_fees as fees','id','two_yearly_slash_price as slash_price','plan_description')->get();

        }
        
        return response()->json([
            'subscription_prices' => $subscription_prices,
            
        ]);
    }

    public function paymentProcess(Request $request)
    {
      
        if(session('selected_plan_id') && session('selected_plan_mode')){
            $planId = session('selected_plan_id');
            $planMode = session('selected_plan_mode');
        }elseif($request){
            $planId=$request->subscription_id;
            $planMode=$request->plan_mode;
        }
        if($planId && $planMode){
            $subscription_id=$planId;
            $sub_data=Subscription::where('id',$planId)->first();
            if($planMode==1){
                $month=1;
                $amount=$sub_data->monthly_fees;
            }elseif($planMode==2){
                $month=12;
                $amount=$sub_data->yearly_fees;
            }elseif($planMode==3){
                $month=3;
                $amount=$sub_data->three_monthly_fees;
            }elseif($planMode==4){
                $month=6;
                $amount=$sub_data->six_monthly_fees;
            }elseif($planMode==5){
                $month=24;
                $amount=$sub_data->two_yearly_fees;
            }
            
           
        }else{
           
            return redirect('subscriptions.choosePlan')->with('error', 'Plan not selected');
            
        }
        
       
        if ($request->library_id) {
            $library_id = $request->library_id;
        } elseif (Auth::check()) { 
            $library_id = getAuthenticatedUser()->id;
        } else {
            return redirect()->back()->with('error', 'Library ID not provided.');
        }

        
        if (!$library_id) {
            return redirect()->back()->with('error', 'Library ID is missing.');
        }
        
        $today = date('Y-m-d');
        $existingTransaction = LibraryTransaction::where('library_id', $library_id)
            ->where(function($query) use ($today) {
                $query->where('is_paid', 0)
                    ->where(function($subQuery) use ($today) {
                        $subQuery->whereNull('end_date')
                                ->orWhere('end_date', '>=', $today);
                    });
            })
            ->exists();
           
        $gst_discount = DB::table('gst_discount')->first(); 

        if ($gst_discount) {
            $gst = $gst_discount->gst ?? 0;       
            $discount = $gst_discount->discount ?? 0; 
        } else {
            $gst = 0;
            $discount = 0;
        }
       
        //First Apply Discount, Then GST
        $discount_amount=$amount*($discount/100);
        $price_after_discount=$amount-$discount_amount;
        $gst_amount=$price_after_discount*($gst/100);
        $final_price=$price_after_discount+$gst_amount;
       
           
        if (isset($subscription_id) && !is_null($subscription_id)) {
          
            Library::where('id', $library_id)->update([
                'library_type' => $subscription_id,
            ]);
        
            $transactionId = null;
        
            if ($existingTransaction) {
               
                LibraryTransaction::where('library_id', $library_id)
                    ->where(function($query) use ($today) {
                        $query->where('is_paid', 0)
                              ->where(function($subQuery) use ($today) {
                                  $subQuery->whereNull('end_date')
                                           ->orWhere('end_date', '>=', $today);
                              });
                    })
                    ->update([
                        'amount'       => $amount,
                        'paid_amount'  => $final_price,
                        'month'        => $month,
                        'subscription' => $subscription_id,
                        'gst'          => $gst,
                        'discount'     => $discount,
                    ]);
                
                // Get the last updated ID
                $transactionId = LibraryTransaction::where('library_id', $library_id)
                    ->where('is_paid', 0)
                    ->where(function($query) use ($today) {
                        $query->whereNull('end_date')->orWhere('end_date', '>=', $today);
                    })
                    ->latest('id')
                    ->value('id');
            } else {
                
                $transaction = LibraryTransaction::create([
                    'library_id'   => $library_id,
                    'amount'       => $amount,
                    'paid_amount'  => $final_price,
                    'month'        => $month,
                    'subscription' => $subscription_id,
                    'gst'          => $gst,
                    'discount'     => $discount,
                ]);
                $transactionId = $transaction->id;
            }
        
            
        } else {
           
            return redirect()->back()->with('error', 'No valid subscription selected.');
        }
        
     

        // Retrieve the most recent transaction
        $data = Library::where('id', $library_id)
        ->with('subscription.permissions')  
        ->first();
        $plan = Subscription::where('id', $data->library_type)->first();
       
        $month = LibraryTransaction::where('id', $transactionId)
            ->orderBy('id', 'desc')
            ->first();
      $all_transaction = LibraryTransaction::where('library_id', $library_id)
            ->where('is_paid', 1)
            ->with(['subscriptionPlan', 'subscriptionPlan.permissions'])
            ->get();
         
        return view('library.payment', [
            'transactionId' => $transactionId,
            'month'         => $month,
            'plan'          => $plan,
            'data'          => $data,
            'all_transaction' => $all_transaction,
            'discount_amount'  =>$discount_amount,
            'gst_amount'  =>$gst_amount,
        ]);
    }

    
    public function paymentStore(Request $request)
    {
       
        $this->validate($request, [
            'payment_method' => 'required',
           
        ]);
        $library_transaction_id = LibraryTransaction::where('id', $request->library_transaction_id)->first();

        if ($request->payment_method == '2') {
            LibraryTransaction::where('id', $request->library_transaction_id)->update([
                'transaction_id' => $request->transaction_id ?? mt_rand(10000000, 99999999),
            ]);
        } elseif($request->payment_method=='1'){
           
            $key=config('services.razorpay.key');
            $secret = config('services.razorpay.secret');
          
            $amountInPaise = intval($library_transaction_id->paid_amount * 100);
            \Log::info('Razorpay Order Request Parameters', [
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'receipt' => $request->transaction_id,
                'payment_capture' => 1
            ]);

            $response = Http::withBasicAuth($key, $secret)
            ->timeout(30)
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'receipt' => $request->transaction_id,
                'payment_capture' => 1,
            ]);
           \Log::info('Razorpay API Response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            if ($response->successful()) {
                $order = $response->json();

                // Redirect to Razorpay Checkout
                return view('library.razorpay-checkout', [
                    'key' => $key,
                    'order_id' => $order['id'],
                    'amount' => $order['amount'],
                    'currency' => $order['currency'],
                    'library_transaction_id' => $library_transaction_id->id,
                    'name' => 'Library Payment',
                    'description' => 'Library Payment',
                ]);
            }

            return back()->with('error', 'Unable to create Razorpay order.');
        }

        

        if ($library_transaction_id) {
           
            $duration = $library_transaction_id->month ?? 0;

            if (LibraryTransaction::where('library_id', $library_transaction_id->library_id)->where('status', 1)->exists()) {
                $library_tra = LibraryTransaction::where('library_id', $library_transaction_id->library_id)
                                                 ->where('status', 1)
                                                 ->orderBy('id', 'desc')
                                                 ->first();
            
                $start_date = Carbon::parse($library_tra->end_date)->addDay(1);
                $endDate = $start_date->copy()->addMonths($duration);
                $status = 0;
                
            } else {
                
                $start_date = now(); 
                $endDate = $start_date->copy()->addMonths($duration);
                $status = 1;
            }
            
            
           
            // Update the transaction details
            LibraryTransaction::where('id', $request->library_transaction_id)->update([
                'start_date' => $start_date->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'transaction_date' => now()->format('Y-m-d'),
                'payment_mode'=>$request->payment_method,
                'is_paid' => 1,
                'status' => $status,
            ]);

            // Update the corresponding library's `is_paid` status
            Library::where('id', $library_transaction_id->library_id)->update([
                'is_paid' => 1,
               
            ]);
          
            if( session('selected_plan_id') && session('selected_plan_mode')){
                session()->forget(['selected_plan_id', 'selected_plan_mode']);

            }


            $isProfile = Library::where('id', $library_transaction_id->library_id)->where('is_profile', 1)->exists();
            if($isProfile){
                
                return redirect()->route('library.home')->with('success', 'Payment successfully processed.');
            }else{
                return redirect()->route('profile')->with('success', 'Payment successfully processed.');
            }
           
           
        }
        return redirect()->back()->with('error', 'Transaction not found.');
    }
    // Library Setting
    public function librarySetting()
    {
        $library=LibrarySetting::where('library_id',getLibraryId())->first();
        return view('library.settings',compact('library')); // Adjust the view path as needed
    }
    public function libraryfeedback()
    {
        $is_feedback=Feedback::where('library_id', getLibraryId())->exists();
        return view('library.feedback',compact('is_feedback')); // Adjust the view path as needed
    }

    public function sendSuccessfulEmail($library)
    {
        // Prepare the data to send to the email view
         \Log::info('sendSccessfulEmail');
        $data = [
            'name' => $library->library_name,
            'email' => $library->email,
            'library_no' => $library->library_no,
        ];

        Mail::send('email.successful-lib-regi', $data, function($message) use ($data) {
            $message->to($data['email'], $data['name'])->subject('Library Registration Successful');
        });
    }

    public function feedbackStore(Request $request)
    {
     
        $validatedData = $request->validate([
            'feedback_type' => 'required',
            'rating' => 'required|integer|min:1|max:5',
            'description' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'recommend' => 'required|string',
        ]);

        $validatedData['recommend'] = $validatedData['recommend'] === 'Yes' ? 1 : 0;
      
        $validatedData['library_id'] = getAuthenticatedUser()->id;

        if (!Feedback::where('library_id', getLibraryId())->exists()) {
            Feedback::create($validatedData);
            return redirect()->back()->with('success', 'Feedback submitted successfully.');
        } else {
            return redirect()->back()->with('error', 'Feedback already submitted.');
        }
    }
  

    public function SettingStore(Request $request)
    {
        $validatedData = $request->validate([
            'library_favicon' => 'nullable|file|mimes:jpg,jpeg,png,ico|max:2048',
            'library_title' => 'required|string|max:255',
            'library_meta_description' => 'required|string',
            'library_primary_color' => 'required|string|max:7',
            'library_language' => 'required|string',
        ]);

        // Handle file upload
        if ($request->hasFile('library_favicon')) {
            $file = $request->file('library_favicon');
            $filePath = $file->store('library_favicons', 'public');
            $validatedData['library_favicon'] = $filePath;
        }

        // Include library_id
        $validatedData['library_id'] = auth()->id(); // or replace with the relevant library ID source

        // Save data to the database
        LibrarySetting::updateOrCreate(
            ['library_id' => $validatedData['library_id']], // Update existing entry or create new one
            $validatedData
        );

        return redirect()->back()->with('success', 'Library settings saved successfully.');
    }

    public function videoTraining(){
        $video_list=Setting::get();
        return view('library.video-recording',compact('video_list'));
    }

    public function learnerComplaints(){
        $data=Complaint::where('complaints.library_id',getLibraryId())->leftJoin('learners','complaints.learner_id','=','learners.id')->select('learners.name as learner_name','complaints.*')->get();
        
        return view('library.complaint',compact('data'));
    }

    public function learnerSuggestions(){
        $data=Suggestion::where('suggestions.library_id',getLibraryId())->leftJoin('learners','suggestions.learner_id','=','learners.id')->select('learners.name as learner_name','suggestions.*')->get();
        return view('library.suggestion',compact('data'));
    }

    public function learnerFeedback(){
        $data=LearnerFeedback::where('learner_feedback.library_id',getLibraryId())->leftJoin('learners','learner_feedback.learner_id','=','learners.id')->select('learners.name as learner_name','learner_feedback.*')->get();
        return view('library.learner-feedback',compact('data'));
    }

    public function clarificationStatus(Request $request){
       
        $validated = $request->validate([
            'row_id' => 'required|integer|exists:complaints,id',
            'status' => 'required',
            'remark' => 'nullable|string',
        ]);

        // Find the complaint using the row_id and update status and remark
        $complaint = Complaint::find($request->row_id);

        if (!$complaint) {
            return response()->json(['error' => 'Complaint not found.'], 404);
        }

      
        $complaint->update([
            'status' => $validated['status'],
            'response' => $validated['remark'] ?? null, 
        ]);

        return response()->json([
            'success'=>200,
            'message' => 'Complaint status updated successfully.'
        ]);
    }


    public function getEnquiry(){
        $datas=LibraryEnquiry::where('branch_id',getCurrentBranch())->with('planType')->get();
        return view('library.enquery',compact('datas'));
    }


    public function emailVerification(){
        if(Auth::guard('web')->check()){
            return view('library.emailVerification');
        }else{
            return view('auth.verify');
        }
        
    }

    public function expenceList(Request $request)
    {
        // Get expense master list for dropdown
        $data = Expense::all();

        // Base query
        $query = LearnerTransactionActivity::where('payment_type', 'EXPENSE');

        // Apply filters if present
        if ($request->filled('expense')) {
            $query->where('particular', $request->expense);
        }

        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->to);
        }

        // Paginate results
        $expences = $query->orderBy('date', 'desc')->paginate(10);

        return view('master.expense-list', compact('expences', 'data'));
    }

    public function expenceStore(Request $request)
    {
      
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'payment_mode' => 'required',
            'remark' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }
        if($request->name=='other' && $request->remark){
            $particular=$request->remark ;
        }elseif($request->name=='other' && !$request->remark){
            $particular='other' ;
        }else{
            $particular=$request->name ;
        }
        if($request->payment_mode==1){
            $mode='UPI';
        }elseif($request->payment_mode==2){
             $mode='CASH';
        }else{
             $mode='OTHER';
        }
        
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
        } else {// first transaction
            $newSeq = "0001";
        }

        // Build transaction ID
        $transactionId = $year . "0000" . $newSeq;

        $data=[
           'branch_id' => getCurrentBranch(),
           'learner_id'=>null,
           'date'=>$request->date ?? date('Y-m-d'),
            'particular'=>$particular,
            'payment_type'=>'EXPENSE',
            'payment_mode'=>$mode,
            'amount'=>$request->amount,
            'dr_cr'=>'Dr',
            'amount'=>$request->amount,
            'transaction_id'=>$transactionId
           
        ];


        if ($request->id) {
            // Update
            $expense = LearnerTransactionActivity::findOrFail($request->id);
            $expense->update($data);
            $message = 'Expense updated successfully';
        } else {
            // Create
            LearnerTransactionActivity::create($data);
            $message = 'Expense created successfully';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            
        ]);
    }

    public function expencedestroy($id)
    {
        LearnerTransactionActivity::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Expense deleted successfully!');
    }


   

}
