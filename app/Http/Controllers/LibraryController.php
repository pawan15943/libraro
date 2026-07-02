<?php

namespace App\Http\Controllers;

use App\Actions\RegisterLibrary;
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
use App\Services\LibraryConfigurationService;
use App\Services\LibraryLifecycleService;
use App\Services\ReferralRewardService;
use Illuminate\Validation\ValidationException;

class LibraryController extends Controller
{
    protected $libraryService;
    protected $referralRewardService;

    public function __construct(LibraryService $libraryService, ReferralRewardService $referralRewardService)
    {
        $this->libraryService = $libraryService;
        $this->referralRewardService = $referralRewardService;
    }

     public function dataUpdateStatus()
    {
        $today = Carbon::today();
        $futureCheckDate = $today->copy()->addDays(5);
        $extend_day = getExtendDays();

        $this->nonExpiredUpdate();
        // ---- Case 1: Renewed Learners ----
        $renewedLearners = LearnerDetail::select('learner_id')
            ->groupBy('learner_id')
            ->havingRaw('
                SUM(CASE WHEN plan_end_date <= ? THEN 1 ELSE 0 END) > 0 
                AND 
                SUM(CASE WHEN plan_end_date > ? AND status = 0 THEN 1 ELSE 0 END) > 0
            ', [$futureCheckDate, $futureCheckDate])
            ->pluck('learner_id');

        // ---- Case 2: Expired Learners ----
        $expiredLearners = LearnerDetail::whereDate(
            DB::raw("DATE_ADD(plan_end_date, INTERVAL $extend_day DAY)"),
            '<=',
            $today
        )
            ->pluck('learner_id');

        // ---- Case 3: Active Future Booked Learners ----
        $futureLearners = LearnerDetail::where('status', 0)
            ->where('plan_start_date', '<=', $today)
            ->where('plan_end_date', '>', $today)
            ->pluck('learner_id');

        // ---- Merge All Unique Learners ----
        $learnerIds = $renewedLearners
            ->merge($expiredLearners)
            ->merge($futureLearners)
            ->unique()->values();

        if ($learnerIds->isEmpty()) {
            return true;
        }
        $customerdatas = LearnerDetail::whereIn('learner_id', $learnerIds)
        ->orderBy('learner_id')
        ->orderBy('plan_start_date')
        ->get()
        ->groupBy('learner_id');
       foreach ($customerdatas as $learnerId => $details) {

            $activeDetail = null;

            foreach ($details as $detail) {

                $branchId = $detail->branch_id;
                $branch = $branchId ? Branch::find($branchId) : null;
                $extend_day = $branch ? $branch->extend_days : 0;

                $planEndDateWithExtension = Carbon::parse($detail->plan_end_date)
                    ->addDays($extend_day);

                // Check if this detail is active today
                if (
                    $detail->plan_start_date <= $today &&
                    $planEndDateWithExtension > $today
                ) {
                    $activeDetail = $detail;
                    break;
                }
            }

            if ($activeDetail) {

                // Activate learner
                Learner::where('id', $learnerId)
                    ->where('status', '!=', 1)
                    ->update(['status' => 1]);

                // Deactivate all details
                LearnerDetail::where('learner_id', $learnerId)
                    ->update(['status' => 0]);

                // Activate correct detail
                $activeDetail->update(['status' => 1]);

            } else {

                // No active plan
                Learner::where('id', $learnerId)
                    ->where('status', '!=', 0)
                    ->update(['status' => 0]);

                LearnerDetail::where('learner_id', $learnerId)
                    ->update(['status' => 0]);
            }
        }


        return true;
    }

    public function nonExpiredUpdate()
    {
        $today = Carbon::today();
        $yesterday=Carbon::today()->subDay();

       $nonExpiredLearners = LearnerDetail::join('learners', 'learners.id', '=', 'learner_detail.learner_id')
        ->where('learner_detail.status', 1)
        ->whereDate('learner_detail.plan_end_date', $yesterday)
        ->where('learners.no_expiry', 1)
        ->select('learner_detail.*')
        ->get();
         $branchId  = $branch_id ?? getCurrentBranch();

        $branch = Branch::find($branchId);

        foreach ($nonExpiredLearners as $detail) {
            DB::transaction(function () use ($detail, $branchId) {
                // New end date
                $start_date = Carbon::parse($detail->plan_end_date)->addDay();
                $newEndDate = getEndDate($detail->plan_id, $start_date, $branchId);

                $lastTransaction = LearnerTransaction::where('learner_detail_id', $detail->id)
                    ->latest()
                    ->first();
                // price Get
                $hasFixedBilling = Branch::where('id', $branchId)
                    ->whereNotNull('fixed_billing_date')
                    ->exists();

                if ($hasFixedBilling) {

                    $planPrice= getBillingCyclePrice($detail->plan_id,$detail->plan_type_id,$start_date);

                } else {

                    $planPrice= getPlanPrice($detail->plan_id,$detail->plan_type_id);
                }
                 $effectivePaid = $planPrice + $lastTransaction->locker_amount - $lastTransaction->discount_amount;
                
                // Add same detail record
                $learner_detail = LearnerDetail::create([
                    'library_id' => $detail->library_id,
                    'branch_id' =>  $detail->branch_id,
                    'learner_id' =>  $detail->learner_id,
                    'plan_id' => $detail->plan_id,
                    'plan_type_id' => $detail->plan_type_id,
                    'plan_price_id' => $planPrice,
                    'plan_start_date' => $start_date->format('Y-m-d'),
                    'plan_end_date' => $newEndDate->format('Y-m-d'),
                    'join_date' => $detail->join_date,
                    'hour' => $detail->hour,
                    'seat_no' => $detail->seat_no,
                    'payment_mode' => 3,
                    'status' => 1,
                    'is_paid' => 0,
                
                ]);
                
                // add new transaction entry
                LearnerTransaction::create([
                    'learner_id'        => $lastTransaction->learner_id,
                    'library_id'        => $lastTransaction->library_id,
                    'branch_id'         => $lastTransaction->branch_id,
                    'learner_detail_id' => $learner_detail->id,
                    'total_amount'      => $effectivePaid,
                    'paid_amount'       => 0 ,
                    'pending_amount'    => $effectivePaid,
                    'locker_amount'     => $lastTransaction->locker_amount ?? 0,
                    'discount_amount'   => $lastTransaction->discount_amount ?? 0,
                    'is_paid'           => 0,
                    'due_date'          => date('Y-m-d'),
                    'transaction_id'    => transaction_id(),
                    'paid_date'          => date('Y-m-d'),
                ]);
            
                $detail->update([
                        'status' => 0
                ]);
            });
        }
    }
    public function confirmDailyPopup()
    {
        $library = Auth::guard('library')->check()
            ? Auth::guard('library')->user()
            : Library::find(Auth::guard('library_user')->user()->library_id);

         $updateStatus= $this->dataUpdateStatus();
      
         if($updateStatus){
            $library->update([
                'last_status_confirmed_date' => Carbon::today()->toDateString()
            ]);
         }

        return redirect()->back();

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
            'referral_code' => 'nullable|string|max:100',
            'referral_type' => 'nullable|in:code,qr,link',
        ];
        

        return Validator::make($request->all(), $rules);
    }

   public function sendVerificationEmail($library)
{
    try {

        $data = [
            'name'  => $library->library_name,
            'email' => $library->email,
            'otp'   => $library->email_otp,
        ];

        Mail::send('email.verify-email', $data, function ($message) use ($data) {
            $message->to($data['email'], $data['name'])
                    ->subject('Verify Your Email Address');
        });

    } catch (\Throwable $e) {

        \Log::error('Mail sending failed inside method', [
            'message' => $e->getMessage()
        ]);

        // IMPORTANT: do not throw
    }
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
        
        
      
        $premiumSub=Subscription::orderBy('id','DESC')->first();
        $features=DB::table('subscription_plan_features')->where('feature_status',1)->get();
        $library=getLibrary();
        
        if($library->library_type){
              $subscriptions = Subscription::where('id','>=',$library->library_type)->with('permissions')->get();
        }else{
              $subscriptions = Subscription::with('permissions')->get();
        }

        if($library->library_type==2 || $library->library_type==1){
               $month=[2=>'1 YEARLY',5=>'2 YEARLY'];
        }else{
            $month=[1=>'1 MONTHLY',3=>'3 MONTHLY',4=>'6 MONTHLY',2=>'1 YEARLY',5=>'2 YEARLY'];
        }
       
      
         if($library->is_paid==1 && (Branch::where('library_id',$library->id)->count()==0)){
             $redirectUrl = $this->libraryService->checkLibraryStatus();
         
            return redirect($redirectUrl);
        }
        return view('register.plan', compact('subscriptions','premiumSub','features','month'));
    }

    // public function store(Request $request,RegisterLibrary $action)
    // {
      
    //     // Validate the request
    //     $validatedData = $this->libraryValidation($request);
        
    //     if ($validatedData->fails()) {
    //         return redirect()->back()->withErrors($validatedData)->withInput();
    //     }

    //     $validated = $validatedData->validated();
    //     unset($validated['terms']);
    //     $validated['original_password'] = $validated['password'];

    //     $validated['password'] = bcrypt($validated['password']);
    //     $validated['slug']=Str::slug($validated['library_name']);
    //     try {
    //         $library = Library::create($validated);

    //         if ($library) {
               
    //             $otp = Str::random(6); 
    //             $library->email_otp = $otp;
    //             $library->referral_code = ReferralHelper::generateLibraryReferralCode($library->id);
    //             $library->save();
                
    //             if ($request->referral_code) {
    //                 $referrer = Library::where('referral_code', $request->referral_code)->first();

    //                 if ($referrer && $referrer->id !== $library->id) {

    //                     $library->referred_by = $referrer->id;
    //                     $library->save();

    //                     LibraryReferral::create([
    //                         'referrer_library_id' => $referrer->id,
    //                         'referred_library_id' => $library->id,
    //                         'referral_code' => $request->referral_code,
    //                         'referral_type' => $request->has('qr') ? 'qr' : 'code',
    //                         'status' => 'pending'
    //                     ]);
    //                 }
    //             }
                
                
    //             try {
                 
    //                 $this->sendVerificationEmail($library);
    //                 \Log::info('sendVerificationEmail success', [
    //                     'library_id' => $library->id ?? null,
    //                 ]);

    //             } catch (\Throwable $e) {

    //                 \Log::error('sendVerificationEmail failed', [
    //                     'library_id' => $library->id ?? null,
    //                     'email'      => $library->email ?? null,
    //                     'message'    => $e->getMessage(),
    //                     'file'       => $e->getFile(),
    //                     'line'       => $e->getLine(),
    //                 ]);

    //                 // ✔ Continue execution (do nothing else)
    //             }
                                
    //             session(['library_email' => $library->email]);

    //             return redirect()->route('verification.notice')
    //                 ->with('message', 'Please verify your email to continue.');
    //         } else {
    //             return response()->json(['error' => 'Library creation failed.'], 500);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    //     }
    // }
     public function store(Request $request,RegisterLibrary $action)
    {
      
        // Validate the request
        $validatedData = $this->libraryValidation($request);
        
        if ($validatedData->fails()) {
            return redirect()->back()->withErrors($validatedData)->withInput();
        }

        $validated = $validatedData->validated();
        unset($validated['terms']);
        $validated['referral_type'] = $request->input(
            'referral_type',
            $request->has('qr') ? 'qr' : 'code'
        );
      
        try {

            $library = $action->handle($validated);
          
            session(['library_email' => $library->email]);

            return redirect()
                ->route('verification.notice')
                ->with('message', 'Please verify your email to continue.');

        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {

            return back()->with('error', 'Something went wrong.');
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

        $transaction = LibraryTransaction::create([
            'library_id'  => $libraryId,
           'subscription' => $subscriptionId,
                'amount'       => $amount,
                'paid_amount'  => $finalAmount,
                'month'        => $month,
                'gst'          => $gst,
                'discount'     => $discount,
                'is_paid'    => 0,
            
        ]);
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
          
        } else {
            $start = now();
          
        }

        $end = $start->copy()->addMonths($duration);

       if ($start->copy()->startOfDay() <= now()->startOfDay()) {
            $status = 1;
        } else {
            $status = 0;
        }
       
     

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

                // ✅ Always mark paid
        $this->markLibraryPaidAndAssignNo($transaction->library_id);

        if ($status == 1) {

            $hasBranch = Branch::where('library_id', $transaction->library_id)->exists();

            $updateData = [
                'library_type' => $transaction->subscription,
            ];

            // ✅ Only set status if branch exists
            if ($hasBranch) {
                $updateData['status'] = 1;
            }

            // ✅ Single update query
            Library::where('id', $transaction->library_id)->update($updateData);

            // ✅ Deactivate all OTHER active transactions
            LibraryTransaction::where('library_id', $transaction->library_id)
                ->where('id', '!=', $transaction->id)
                ->where('status', 1)
                ->update(['status' => 0]);
        }
       
    }

    private function markLibraryPaidAndAssignNo(int $libraryId): void
    {
        DB::transaction(function () use ($libraryId) {
            $library = Library::where('id', $libraryId)->lockForUpdate()->firstOrFail();

            $library->is_paid = 1;

            if (empty($library->library_no)) {
                $library->library_no = generateLibraryCode();
            }

            $library->save();
        });
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

    private function handleReffrel($transaction, $status)
    {
        if ((int) $status === 1) {
            $this->referralRewardService->completePendingReferralForLibrary(
                (int) $transaction->library_id
            );
        }
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
//    public function configrationStore(Request $request)
// {
//     /* =========================
//        FILTER EMPTY ROWS
//     ========================= */
//     $planTypes = collect($request->plan_types ?? [])
//         ->filter(function ($row) {
//             return isset($row['day_type_id']) &&
//                    ($row['day_type_id'] !== '' ||
//                     !empty($row['start_time']) ||
//                     !empty($row['end_time']));
//         })
//         ->values()
//         ->toArray();

//     $request->merge(['plan_types' => $planTypes]);

//     /* =========================
//        VALIDATION
//     ========================= */
//     $validator = Validator::make($request->all(), [
//         'plan_types'                   => 'required|array|min:1',
//         'plan_types.*.day_type_id'     => 'required',
//         'plan_types.*.start_time'      => 'required|date_format:H:i',
//         'plan_types.*.end_time'        => 'required|date_format:H:i',
//         'plan_types.*.slot_hours'      => 'required|numeric|min:1',
//         'plan_types.*.price'           => 'required|numeric|min:0',
//         'plan_types.*.custom_plan_type'=> 'nullable|string|max:100',
//     ]);

//     $validator->after(function ($validator) use ($request) {
//         foreach ($request->plan_types as $index => $row) {
//             if (
//                 isset($row['day_type_id']) &&
//                 (int)$row['day_type_id'] === 0 &&
//                 empty($row['custom_plan_type'])
//             ) {
//                 $validator->errors()->add(
//                     "plan_types.$index.custom_plan_type",
//                     'Custom Plan Type Name is required'
//                 );
//             }
//         }
//     });

//     if ($validator->fails()) {
//         return response()->json([
//             'status' => false,
//             'errors' => $validator->errors()
//         ], 422);
//     }

//     /* =========================
//        BRANCH CHECK
//     ========================= */
//     $branchCount = Branch::where('library_id', getLibraryId())->count();
//     if ($branchCount < 1) {
//         return response()->json([
//             'status' => false,
//             'message' => 'No configuration required in your branch'
//         ], 400);
//     }

//     $branch = Branch::where('id', getCurrentBranch())->first();

//     $plan = Plan::where('library_id', getLibraryId())
//         ->where('plan_id', 1)
//         ->where('type', 'MONTH')
//         ->first();

//     if (!$plan) {
//         return response()->json([
//             'status' => false,
//             'message' => 'Ops, System not found any plan to proceed for shifts.'
//         ], 400);
//     }

//     $branchRecord = Hour::where('branch_id', getCurrentBranch())->first();

//     $existingPlanTypeCount = PlanType::where('branch_id', getCurrentBranch())->count();
//     $isFirstTimeSetup = $existingPlanTypeCount === 0;

//     DB::beginTransaction();

//     try {
//         $finalShiftIds = [];

       
//         /* =========================
//            GLOBAL SHIFT COVERAGE CHECK
//         ========================= */
//         $coveredMinutes = [];

//         foreach ($request->plan_types as $row) {

//             $start = Carbon::parse($row['start_time']);
//             $end   = Carbon::parse($row['end_time']);

//             if ($end->lessThanOrEqualTo($start)) {
//                 $end->addDay();
//             }

//             while ($start < $end) {
//                 $coveredMinutes[$start->format('H:i')] = true;
//                 $start->addMinute();
//             }
//         }

//         $totalCoveredHours = count($coveredMinutes) / 60;

//         if ($branchRecord->hour != 24 && $totalCoveredHours > $branchRecord->hour) {
//             throw new \Exception('Shift timing exceeds library hours.');
//         }

//         /* =========================
//            CREATE / UPDATE SHIFTS
//         ========================= */
//         $timePairs = [];
//         $isCreating = false;
//         $isUpdating = false;
//         $planTypesss = $request->plan_types;

//         foreach ($planTypesss as $index => $row) {


//             if ($row['slot_hours'] > $branchRecord->hour && $branchRecord->hour != 24) {
//                 throw new \Exception('Selected hours exceed the library’s available hours.');
//             }

//             $start = Carbon::parse($row['start_time']);
//             $end   = Carbon::parse($row['end_time']);

//             if ($end->lessThanOrEqualTo($start)) {
//                 $end->addDay();
//             }

//             $actualHours = $start->diffInHours($end);

//             if ($row['slot_hours'] != $actualHours) {
//                 throw new \Exception(
//                     "Slot hours must match shift time ({$actualHours} hours)."
//                 );
//             }

//             /* =========================
//             VIP / RESERVED VALIDATION
//             ========================= */
//             $dayTypeId = (int) $row['day_type_id'];

//             if (in_array($dayTypeId, [10, 11])) {

//                 // Must match branch full-day hours
//                 if ($actualHours != $branchRecord->hour) {
//                     $shiftName = $dayTypeId == 11 ? 'VIP' : 'Reserved';

//                     throw new \Exception(
//                         "{$shiftName} shift must match library timing ({$branchRecord->hour} hours)."
//                     );
//                 }
//             }

//             /* Duplicate check inside request */
        
//             $rowId = $row['plan_type_id'] ?? 'new';
//             $currentDayType = (int) $row['day_type_id'];

//             if ($currentDayType === 0) {
//                 // CUSTOM SHIFT → check by time range
//                 $pairKey = $row['start_time'] . '-' . $row['end_time'];

//                 if (isset($timePairs['custom'][$pairKey])) {
//                     throw new \Exception(
//                         'Duplicate custom shift detected for same time range.'
//                     );
//                 }

//                 $timePairs['custom'][$pairKey] = true;

//             } else {
//                 // NON-CUSTOM SHIFT → check by day_type_id
//                 if (isset($timePairs['non_custom'][$currentDayType])) {
//                     throw new \Exception(
//                         'Duplicate shift detected.'
//                     );
//                 }

//                 $timePairs['non_custom'][$currentDayType] = true;
//             }


//            /* Duplicate check in DB */

//            $currentId = $row['plan_type_id'] ?? null;

//             if ($row['day_type_id'] == 0) {

//                 // CUSTOM → check by time range
//                 $existing = PlanType::where('branch_id', $branch->id)
//                     ->where('day_type_id', 0)
//                     ->where('start_time', $row['start_time'])
//                     ->where('end_time', $row['end_time'])
//                     ->first();

//                 if ($existing) {

//                     // if editing same shift without ID → treat as update
//                     if (!$currentId) {
//                         $planTypesss[$index]['plan_type_id'] = $existing->id;
//                         $row['plan_type_id'] = $existing->id; 
//                         $currentId = $existing->id;
//                     }

//                     // if different record → block
//                     elseif ($existing->id != $currentId) {
//                         throw new \Exception(
//                             'Custom shift already exists for this time range.'
//                         );
//                     }
//                 }

//             } else {

//                 // NON-CUSTOM → check by day_type_id
//                 $existing = PlanType::where('branch_id', $branch->id)
//                     ->where('day_type_id', $row['day_type_id'])
//                     ->first();

//                 if ($existing) {

//                    if (!$currentId) {
//                         $planTypesss[$index]['plan_type_id'] = $existing->id;
//                         $row['plan_type_id'] = $existing->id;
//                         $currentId = $existing->id;
//                     }


//                     elseif ($existing->id != $currentId) {
//                         throw new \Exception(
//                             'This shift type already exists.'
//                         );
//                     }
//                 }
//             }




//             /* Plan name logic */
//             $dayTypeId = (int) $row['day_type_id'];

//             $planTypeName = match ($dayTypeId) {
//                 1 => 'Full Day',
//                 2 => 'First Half',
//                 3 => 'Second Half',
//                 8 => 'All Day',
//                 9 => 'Full Night',
//                 10 => 'Reserved',
//                 11 => 'VIP',
//                 0 => $row['custom_plan_type'],
//                 default => 'Custom',
//             };

         
//             $shiftId = $currentId;
//             /* CREATE or UPDATE */
//             if ($shiftId) {
//                 $isUpdating = true;

//                 $planType = PlanType::where('id', $shiftId)
//                     ->where('branch_id', $branch->id)
//                     ->first();

//                 if (!$planType) {
//                     throw new \Exception('Invalid shift selected.');
//                 }

//                 $planType->update([
//                     'library_id'  => getLibraryId(),
//                     'branch_id'   => $branch->id,
//                     'day_type_id' => $row['day_type_id'],
//                     'name'        => $planTypeName,
//                     'start_time'  => $row['start_time'],
//                     'end_time'    => $row['end_time'],
//                     'slot_hours'  => $row['slot_hours'],
//                     'image'       => 'public/img/booked.png',
//                 ]);

//             } else {

//                 $isCreating = true;

//                 $planType = PlanType::create([
//                     'library_id'  => getLibraryId(),
//                     'branch_id'   => $branch->id,
//                     'day_type_id' => $row['day_type_id'],
//                     'name'        => $planTypeName,
//                     'start_time'  => $row['start_time'],
//                     'end_time'    => $row['end_time'],
//                     'slot_hours'  => $row['slot_hours'],
//                     'image'       => 'public/img/booked.png',
//                 ]);
//             }
//             $finalShiftIds[] = $planType->id;

//             if ($dayTypeId == 11) {
//                 $row['price'] = 0;
//             }
//             /* Price */
//             if (!empty($row['plan_price_id'])) {
//                 PlanPrice::where('id', $row['plan_price_id'])->update([
//                     'price'        => $row['price'],
//                     'plan_type_id' => $planType->id,
//                 ]);
//             } else {
//                 PlanPrice::create([
//                     'library_id'   => getLibraryId(),
//                     'branch_id'    => $branch->id,
//                     'plan_id'      => $plan->id,
//                     'plan_type_id' => $planType->id,
//                     'price'        => $row['price'],
//                 ]);
//             }
//         }

//         /* =========================
//         DELETE REMOVED SHIFTS (SAFE)
//         ========================= */
//         $existingShifts = PlanType::where('branch_id', getCurrentBranch())->get();

//         foreach ($existingShifts as $planType) {

//             if (!in_array($planType->id, $finalShiftIds)) {

//                 $exists = LearnerDetail::where('plan_type_id', $planType->id)->exists();

//                 if ($exists) {
//                     throw new \Exception(
//                         "Shift '{$planType->name}' cannot be deleted because learners are enrolled."
//                     );
//                 }

//                 PlanPrice::where('plan_type_id', $planType->id)->forcedelete();
//                 $planType->forcedelete();
//             }
//         }




//         /* =========================
//            LIBRARY CODE GENERATION
//         ========================= */
//         // library subscription update
//         $library = Library::where('id', getAuthenticatedUser()->id)->first();
    
//         if (empty($library->library_no)) {
//             $libraryCode = generateLibraryCode();
//             $library->library_no = $libraryCode;
//             $library->save();
//             DB::commit();

//                 try {
//                     \Log::info('sendSuccessfulEmail');
//                     $this->sendSuccessfulEmail($library);
//                 } catch (\Throwable $e) {
//                     Log::error('Success email sending FAILED', [
//                         'library_id' => $library->id ?? null,
//                         'email'      => $library->email ?? null,
//                         'error'      => $e->getMessage(),
//                     ]);
//                 }

//         }

//         /* =========================
//            SETUP REDIRECT
//         ========================= */
//         $setup    = '';
//         $redirect = null;

//         if ($isFirstTimeSetup && $isCreating && !$isUpdating) {
//             $setup    = 'completed';
//             $redirect = route('library.home', ['setup' => $setup]);
//         }

//         DB::commit();

//         return response()->json([
//             'status'   => true,
//             'redirect' => $redirect,
//             'message'  => 'Library shifts saved successfully.',
//             'setup'    => $setup
//         ]);

//     } catch (\Exception $e) {
//         DB::rollBack();

//         return response()->json([
//             'status' => false,
//             'message' => $e->getMessage()
//         ], 400);
//     }
// }

   public function configrationStore(Request $request,LibraryConfigurationService $shiftService)
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
            'plan_types.*.plan_type_id'    => 'nullable|integer',
            'plan_types.*.id'              => 'nullable|integer',
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
         /* =========================
       CALL SERVICE
     ========================= */

        $response = $shiftService->shiftConfigure(
            $validator->validated(),
            getCurrentBranch()
        );
        if (!$response['status']) {
            return response()->json($response, 400);
        }
    
        /* =========================
        LIBRARY CODE GENERATION
        ========================= */
        // library subscription update
        $library = Library::where('id', getAuthenticatedUser()->id)->first();
    
        $sendSetupEmail = empty($library->library_no);

        if ($sendSetupEmail) {
            $libraryCode = generateLibraryCode();
            $library->library_no = $libraryCode;
        }

        // Referred libraries can already have a library number. Completing the
        // shift setup must activate them as well, otherwise the dashboard sends
        // them straight back to this configuration page.
        $library->status = 1;
        $library->save();

        if ($sendSetupEmail) {
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
        $redirect = route('library.home', ['setup' => 'completed']);
      

        return response()->json([
            'status'   => true,
            'redirect' => $redirect,
            'message'  => $response['message'],
            'setup'    => $response['setup']
        ]);

        
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
       
        $update=$library->update($validated);
      
        if ($update) {
            $library->update(['is_profile' => 1]);
            if (empty($library->library_no)) {
                $libraryCode = generateLibraryCode();
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

            $this->markLibraryPaidAndAssignNo($library_transaction_id->library_id);
          
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
        $data = Expense::all();
        $expences = $this->expenseListBaseQuery($request)->paginate(10);
        $showEmptyState = $this->expenseListShouldShowEmptyState($request, $expences);

        return view('master.expense-list', compact('expences', 'data', 'showEmptyState'));
    }

    /**
     * Full #expensePageDynamic HTML for AJAX (no full page load).
     */
    public function expenceListPage(Request $request)
    {
        $data = Expense::all();
        $expences = $this->expenseListBaseQuery($request)->paginate(10);
        $showEmptyState = $this->expenseListShouldShowEmptyState($request, $expences);

        if ($showEmptyState) {
            $html = view('master.partials.expense-list-empty-state', compact('data'))->render();
        } else {
            $html = view('master.partials.expense-list-non-empty-body', compact('expences', 'data'))->render();
        }

        return response()->json(['html' => $html]);
    }

    protected function expenseListShouldShowEmptyState(Request $request, $expences): bool
    {
        $hasFilters = $request->filled('expense') || $request->filled('from') || $request->filled('to');

        return $expences->isEmpty() && ! $hasFilters;
    }

    public function expenceListFragment(Request $request)
    {
        $expences = $this->expenseListBaseQuery($request)->paginate(10);

        return response()->json([
            'html' => view('master.partials.expense-list-entries', compact('expences'))->render(),
        ]);
    }

    protected function expenseListBaseQuery(Request $request)
    {
        $query = LearnerTransactionActivity::where('payment_type', 'EXPENSE');

        if ($request->filled('expense')) {
            $query->where('particular', $request->expense);
        }

        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->to);
        }

        return $query->orderBy('date', 'desc');
    }

    public function expenceStore(Request $request, LibraryLifecycleService $service)
{
    $validator = $request->validate([

        'id'           => 'nullable|exists:learner_transaction_activity,id',

        'expense_id'   => 'required|integer|exists:expenses,id',

        'amount'       => 'required|numeric|min:1',

        'date'         => 'required|date',

        'payment_mode' => 'required',

        'remark'       => 'nullable|string',
    ]);

    try {

        $expense = $service->storeExpense($request);

        return response()->json([

            'success' => true,

            'message' => $expense['message'],
        ]);

    } catch (\Throwable $e) {

        return response()->json([

            'success' => false,

            'message' => $e->getMessage(),

        ], 500);
    }
}

    // public function expenceStore(Request $request)
    // {
      
    //     $validator = Validator::make($request->all(), [
    //         'date' => 'required|date',
    //         'name' => 'required|string|max:255',
    //         'amount' => 'required|numeric|min:0',
    //         'payment_mode' => 'required',
    //         'remark' => 'nullable|string',
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'errors'  => $validator->errors(),
    //         ], 422);
    //     }
    //     if($request->name=='other' && $request->remark){
    //         $particular=$request->remark ;
    //     }elseif($request->name=='other' && !$request->remark){
    //         $particular='other' ;
    //     }else{
    //         $particular=$request->name ;
    //     }
    //     if($request->payment_mode==1){
    //         $mode='ONLINE';
    //     }elseif($request->payment_mode==2){
    //          $mode='OFFLINE';
    //     }elseif($request->payment_mode==3){
    //          $mode='PAYLATER';
    //     }else{
    //          $mode='OTHER';
    //     }
        
    //      // Fixed year
    //     $year = "2025";

    //     // Get last transaction (only for 2025 IDs)
    //     $last = LearnerTransactionActivity::where('transaction_id', 'like', $year . '000%')
    //         ->orderBy('id', 'desc')
    //         ->first();
    //     if ($last && !empty($last->transaction_id)) {
    //         // extract last sequence (last 4 digits)
    //         $lastSeq = (int)substr($last->transaction_id, -4);
    //         $newSeq = str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
    //     } else {// first transaction
    //         $newSeq = "0001";
    //     }

    //     // Build transaction ID
    //     $transactionId = $year . "0000" . $newSeq;

    //     $data=[
    //        'branch_id' => getCurrentBranch(),
    //        'learner_id'=>null,
    //        'date'=>$request->date ?? date('Y-m-d'),
    //         'particular'=>$particular,
    //         'payment_type'=>'EXPENSE',
    //         'payment_mode'=>$mode,
    //         'amount'=>$request->amount,
    //         'dr_cr'=>'Dr',
    //         'transaction_id'=>$transactionId
           
    //     ];


    //     $expenseId = $request->input('id', $request->input('expense_id'));

    //     if ($expenseId) {
    //         $expense = LearnerTransactionActivity::findOrFail($expenseId);
    //         $expense->update($data);
    //         $message = 'Expense updated successfully';
    //     } else {
    //         $expense = LearnerTransactionActivity::create($data);
    //         $message = 'Expense created successfully';
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => $message,
    //     ]);
    // }

    public function expencedestroy($id)
    {
        LearnerTransactionActivity::findOrFail($id)->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Expense deleted successfully!',
            ]);
        }

        return redirect()->back()->with('success', 'Expense deleted successfully!');
    }


   

}
