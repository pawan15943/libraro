<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLibraryRequest;
use App\Models\City;
use App\Models\Complaint;
use App\Models\Feedback;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerFeedback;
use App\Models\LearnerTransaction;
use App\Models\Library;
use App\Models\LibraryEnquiry;
use App\Models\LibrarySetting;
use App\Models\LibraryTransaction;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlanType;
use App\Models\Seat;
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



class LibraryController extends Controller
{
    protected $libraryService;
    public function __construct(LibraryService $libraryService)
    {
        $this->libraryService = $libraryService;
    }
    
    public function index(Request $request)
    {
        $query = Library::leftJoin('library_transactions', 'libraries.id', '=', 'library_transactions.library_id')
           
            ->select(
                'libraries.id', 
                'libraries.library_type', 
                'libraries.status', 
                'libraries.library_name', 
                'libraries.library_mobile', 
                'libraries.email',
                DB::raw('MAX(library_transactions.id) as latest_transaction_id')
            )
            ->groupBy(
                'libraries.id', 
                'libraries.library_type', 
                'libraries.status', 
                'libraries.library_name', 
                'libraries.library_mobile', 
                'libraries.email'
            );
    
          


        // Filter by Plan
        if ($request->filled('plan_id')) {
            $query->where('libraries.library_type', $request->plan_id);
        }
    
        // Filter by Payment Status
        if ($request->filled('is_paid')) {
            $query->where('library_transactions.is_paid', $request->is_paid);
        }
    
        // Filter by Active/Expired
        if ($request->filled('status')) {
            if ($request->status == 'active') {
                $query->where('libraries.status', 1);
            } elseif ($request->status == 'expired') {
                $query->where('libraries.status', 0);
            }
        }
    
        // Search by Name, Mobile, or Email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('libraries.library_name', 'LIKE', "%{$search}%")
                  ->orWhere('libraries.library_mobile', 'LIKE', "%{$search}%")
                  ->orWhere('libraries.email', 'LIKE', "%{$search}%");
            });
        }
        
        $libraries = $query->get();
        $planslibrary = Subscription::get();
       
        return view('administrator.index', compact('libraries', 'planslibrary'));
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
                $library->save();
                
                $this->sendVerificationEmail($library);
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

    public function libraryStore(Request $request)
    {
        // Define validation rules
        $rules = [
            'library_name'       => 'required|string|max:255',
            'email'              => 'required|email|max:255|unique:libraries,email',
            'library_mobile'     => 'required|digits:10',
            'state_id'           => 'nullable|exists:states,id',
            'city_id'            => 'nullable|exists:cities,id',
            'library_address'    => 'nullable|string|max:500',
            'library_zip'        => 'nullable|digits:6',
            'library_type'       => 'nullable|string|max:255',
            'library_owner'      => 'nullable|string|max:255',
            'library_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:200|dimensions:width=250,height=250',
            'password'           => 'required|string|min:8',
            'library_owner_email'=> 'nullable|email|max:255',
            'library_owner_contact' => 'nullable|digits:10',
        ];

        // Perform validation
        $validatedData = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validatedData->fails()) {
            // Redirect back with validation errors
            return redirect()->back()->withErrors($validatedData)->withInput();
        }

        // Access validated data
        $validated = $validatedData->validated();

        // Hash the password
        $validated['password'] = Hash::make($validated['password']);
        $validated['original_password'] = $validated['password'];
        
        // Store the validated data in the Library model
        $library = Library::create($validated);

        // Redirect with success message
        return redirect()->route('library')->with('success', 'Library Created successfully processed.');
    }


    public function sendVerificationEmail($library)
    {
       
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
       
        // Validate the input
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
            // Mark email as verified
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

            // Redirect to dashboard or wherever you want
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
        
        return view('library.plan', compact('subscriptions','premiumSub'));
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
            ->with(['subscription', 'subscription.permissions'])
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
            return response()->json(['success' => false, 'error_url' => route('library.payment.error'), 'message' => 'Payment verification failed.']);
        }
    
        // Update the transactions table
        $transaction = LibraryTransaction::where('id', $libraryTransactionId)->first();
     
        if (!$transaction) {
            $tempOrder->update([
                'payment_status' => 'fail',
                'error_message' => 'Transaction not found.',
            ]);
            return response()->json(['success' => false, 'error_url' => route('library.payment.error'),'message' => 'Transaction not found.']);
        }
        try {
            if ($transaction) {
                
                $duration = $transaction->month ?? 0;

                if (LibraryTransaction::where('library_id', $transaction->library_id)->where('status', 1)->exists()) {
                    $library_tra = LibraryTransaction::where('library_id', $transaction->library_id)
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
                LibraryTransaction::where('id', $libraryTransactionId)->update([
                    'start_date' => $start_date->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'transaction_date' => now()->format('Y-m-d'),
                    'payment_mode'=>1,
                    'is_paid' => 1,
                    'status' => $status,
                    'transaction_id'=>$razorpayOrderId
                ]);

                // Update the corresponding library's `is_paid` status
                Library::where('id', $transaction->library_id)->update([
                    'is_paid' => 1,
                
                ]);

                // Update temp_order status
                $tempOrder->update([
                    'payment_status' => 'success',
                ]);
                $isProfile = Library::where('id', $transaction->library_id)->where('is_profile', 1)->exists();
                
                return response()->json(['success' => true, 'redirect_url' => $isProfile ? route('library.home') : route('profile')]);
            
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

    public function handleError(){
        return view('library.payment-error.blade.php');
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
        return view('library.video-recording');
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
        $datas=LibraryEnquiry::where('library_id',getLibraryId())->with('planType')->get();
      
        return view('library.enquery',compact('datas'));
    }


    public function emailVerification(){
        if(Auth::guard('web')->check()){
            return view('library.emailVerification');
        }else{
            return view('auth.verify');
        }
        
    }

   

}
