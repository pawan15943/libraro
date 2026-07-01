<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\RegisterLibrary;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseApiResource;
use App\Models\Branch;
use App\Models\Library;
use App\Models\LibraryTransaction;
use App\Models\LibraryUser;
use App\Models\LearnerDetail;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlanType;
use App\Models\Subscription;
use App\Services\LibraryConfigurationService;
use App\Services\LibraryPaymentService;
use App\Services\SubscriptionPermissionService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Events\LibraryRegistered;
use App\Models\Floor;
use App\Models\Hour;
use App\Models\TempOrder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;



class LibraryAuthController extends Controller
{
    protected function apiResponse($data, $message = 'Success', $code = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }
    public function getRazorpayCredentials()
    {
        return response()->json([
            'status'  => true,
            // 'code'    => 200,
            'message' => 'Razorpay credentials fetched successfully',
            'data'    => [
                'key_id' => config('services.razorpay.key'),
                // 'secret' => config('services.razorpay.secret')
            ]
        ]);
    }
    public function setting()
    {
       
        return response()->json([
            'status' => true,
            'message' => 'Settings fetched successfully.',
            'data' => [
                'app_version' => '1.0',
                'force_update' => false,
                'youtube' => 'https://www.youtube.com/@Libraroindia',
                'linkedin' => 'https://www.linkedin.com/in/libraro/',
                'instagram' => 'https://www.instagram.com/libraro.in/',
                'facebook' => 'https://www.facebook.com/libraro.in',
                'whatsapp'=>'https://wa.me/+918114479678',
                'master_sample' => url('public/sample/master.csv'),
                'learner_sample' => url('public/sample/learner.csv'),
                'privacy_policy' => 'https://www.libraro.in/privacy-policy',
                'terms_and_conditions' => 'https://www.libraro.in/terms-and-condition',
                'contact_number' => ['+91-8114479678'],
                'contact_email' => ['support@libraro.in'],
                'isMaintenance'=>false,
                // 'address' => '955, Vinoba Bhave Nagar, Kota, Landmark: New Balaji Computer Classes'
            ]
        ], 200);
    }

    public function register(Request $request,RegisterLibrary $action)
    {
        if (!$request->filled('referral_code') && $request->filled('ref')) {
            $request->merge(['referral_code' => $request->input('ref')]);
        }
       
        if (is_string($request->password)) {
            $request->merge(['password' => trim($request->password)]);
        }

        $validator = Validator::make($request->all(), [
            'library_name' => 'required|string|max:255',
            'email' => 'required|email|unique:libraries,email',
            'library_mobile' => 'required|digits:10',
            'password' => ['required', 'string', 'min:6', "regex:/^[^\\s'\"]/u"],
            'referral_code' => 'nullable|string|max:100',
            'referral_type' => 'nullable|in:code,qr,link',
        ], [
            'password.regex' => 'Password must not start with a space, single quote, or double quote.',
        ]);

        if ($validator->fails()) {
            $firstError = collect($validator->errors()->all())->first(); 

            return response()->json([
                'status' => false,
                'message' => $firstError,
                
            ], 200);
        }
        try {

            $library = $action->handle($validator->validated());

            return response()->json([
                'status'  => true,
                'message' => 'OTP sent to registered email.',
                'data'    => [
                    'library_id' => $library->id
                ]
            ], 200);

        } catch (\Throwable $e) {

            \Log::error('Library Registration Failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Failed to register.',
                'error'   => app()->environment('production') ? null : $e->getMessage(),
            ], 500);
        }
    }


    public function verifyEmailOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'library_id' => 'required|exists:libraries,id',
            'otp' => 'required|digits:6',
          
        ]);

        if ($validator->fails()) {
            $firstError = collect($validator->errors()->all())->first(); 

            return response()->json([
                'status' => false,
                'message' => $firstError,
                
            ], 200);
        }

        $library = Library::find($request->library_id);

        if ($library->email_verified_at) {
            return response()->json([
                'status' => false,
                'message' => 'Email already verified.',
                
            ], 200);
        }

        if ($library->email_otp !== $request->otp) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP. Please try again.',
                
            ], 200);
        }

        $library->email_verified_at = now();
        $library->save();
        $token = $library->createToken('library_token')->plainTextToken;

        $deviceType = request()->header('device-type'); 
        $deviceId   = request()->header('device-id');   

        if ($deviceId && $deviceType) {
            $library->devices()->updateOrCreate(
                ['device_id' => $deviceId],
                [
                    'device_type' => $deviceType,
                    'token' => $token,
                    'guard_name' => 'library_api',
                ]
            );
        }

       
      
         $is_last_step = 0;

        if ($library->is_paid) { // ⭐ CHANGED ($user -> $libraryRecord)
            $is_last_step = 1;
        }
        $libraryId = $library->id; // ⭐ ADDED

        if (Branch::where('library_id', $libraryId)->where('status', 1)->exists()) { // ⭐ CHANGED
            $is_last_step = 2;
        }


        return response()->json([
            'status' => true,
            'message' => 'Email verified successfully.',
            'token' => $token,
            'is_email_verified' => 1,
            'is_last_step'      => $is_last_step,
            'user_type'   => 'library',
            'data' => [
                'library_id' => $request->library_id
            ]
        ], 200);
    }

   
     public function resendEmailOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'library_id' => 'required|exists:libraries,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 200);
        }

        $library = Library::find($request->library_id);

        if ($library->email_verified_at) {
            return response()->json([
                'status' => false,
                'message' => 'Email already verified.',
            ], 200);
        }

        // Generate new OTP
        $otp = rand(100000, 999999);

        $library->email_otp = 123456;
        // $library->email_otp = $otp;
        $library->save();

        // Send email again
        event(new LibraryRegistered($library));

        return response()->json([
            'status' => true,
            'message' => 'OTP resent successfully.',
            'data' => [
                'library_id' => $library->id
            ]
        ], 200);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'       => 'required|email',
            'password'    => 'required',
           
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
                'data'    => (object)[]
            ],422);
        }

        $user = null;
        $userType = null;

        /*
        |---------------------------------------------------
        | 1️⃣ Check Library Owner
        |---------------------------------------------------
        */

        $library = Library::where('email', $request->email)->first();

        if ($library && Hash::check($request->password, $library->password)) {

            $user = $library;
            $userType = 'library';
        }

        /*
        |---------------------------------------------------
        | 2️⃣ Check Library Staff
        |---------------------------------------------------
        */

        if (!$user) {

            $libraryUser = LibraryUser::where('email', $request->email)->first();

            if ($libraryUser && Hash::check($request->password, $libraryUser->password)) {
                $user = $libraryUser;
                $userType = 'library_user';
            }
        }

        /*
        |---------------------------------------------------
        | 3️⃣ Invalid Credentials
        |---------------------------------------------------
        */

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid email or password',
            ],200); // ⭐ CHANGED (added response code)
        }

        /*
        |--------------------------------------------------------------------------
        | ⭐ ADDED : Determine Library Record For Both Login Types
        |--------------------------------------------------------------------------
        */

        $libraryRecord = $userType == 'library'
            ? $user
            : Library::find($user->library_id);

        $libraryId = $libraryRecord->id; // ⭐ ADDED
        if ($userType === 'library_user' && empty($user->current_branch)) {
            $assignedBranches = is_array($user->branch_id) ? $user->branch_id : [];
            $firstBranchId = (int) ($assignedBranches[0] ?? 0);

            if ($firstBranchId > 0) {
                $user->current_branch = $firstBranchId;
                $user->save();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Determine is_last_step
        |--------------------------------------------------------------------------
        */

        $is_last_step = 0;

        if ($libraryRecord->is_paid) { // ⭐ CHANGED ($user -> $libraryRecord)
            $is_last_step = 1;
        }

        if (Branch::where('library_id', $libraryId)->where('status', 1)->exists()) { // ⭐ CHANGED
            $is_last_step = 2;
        }

        $isPlanComplete =
            $libraryRecord->status == 1 && // ⭐ CHANGED
            Plan::where('library_id', $libraryId)->exists() && // ⭐ CHANGED
            PlanType::where('library_id', $libraryId)->exists() && // ⭐ CHANGED
            PlanPrice::where('library_id', $libraryId)->exists(); // ⭐ CHANGED

        if ($isPlanComplete) {
            $is_last_step = 3;
        }

        /*
        |--------------------------------------------------------------------------
        | ⭐ CHANGED : Email Verification Check (Works for both user types)
        |--------------------------------------------------------------------------
        */

        if (is_null($libraryRecord->email_verified_at)) {
            return response()->json([
                'status' => true,
                'message'=> 'Please verify your email before login',
                'is_email_verified' => 0,
                'is_last_step'      => $is_last_step,
                'data'    => [
                    'library_id' => $library->id
                ]
            ],200); // ⭐ CHANGED
        }

        /*
        |---------------------------------------------------
        | 4️⃣ Assign Role If Missing
        |---------------------------------------------------
        */

        if ($userType == 'library' && !$user->hasRole('library')) {
            $user->assignRole('admin'); // ⭐ CHANGED (role corrected)
        }

        if ($userType == 'library_user' && !$user->hasRole('admin_user')) {
            $user->assignRole('admin_user');
        }

        /*
        |---------------------------------------------------
        | 5️⃣ Remove Old Tokens
        |---------------------------------------------------
        */

        $user->tokens()->delete();

        /*
        |---------------------------------------------------
        | 6️⃣ Create Sanctum Token
        |---------------------------------------------------
        */

        $token = $user->createToken('library_token')->plainTextToken;

        /*
        |---------------------------------------------------
        | 7️⃣ Store Device Info
        |---------------------------------------------------
        */
        $deviceType = request()->header('device-type'); 
        $deviceId   = request()->header('device-id');   

        $user->devices()->updateOrCreate(
            ['device_id' => $deviceId],
            [
                'device_type' => $deviceType,
                'token'       => $token,
                'guard_name'  => 'library_api',
            ]
        );

        /*
        |---------------------------------------------------
        | 8️⃣ Get Branch Data (if Library)
        |---------------------------------------------------
        */

        $branches = [];

        if ($userType == 'library') {

            $branches = Branch::leftJoin('hour','branches.id','=','hour.branch_id')
                ->where('branches.library_id', $libraryId) // ⭐ CHANGED
                ->select(
                    'branches.id',
                    'branches.name',
                    'branches.uuid',
                    'branches.display_name',
                    'branches.status',
                    'branches.is_profile',
                    'hour.hour as operating_hour',
                    'hour.seats'
                )
                ->get();
        }

        /*
        |---------------------------------------------------
        | 9️⃣ Response
        |---------------------------------------------------
        */
        Log::info(['user_token'=>$token]);

        return response()->json([
            'status'      => true,
            'message'     => 'Login successful',
            'token'       => $token,
            'is_email_verified' => 1,
            'is_last_step'      => $is_last_step,
            'user_type'   => $userType,
            'data'    => [
                    'library_id' => $libraryId,
                    'branch'=>$branches,
                ]
            
        ],200);
    }

    public function appPermissions()
    {
        $user = auth('library_api')->user();

        if (! $user instanceof Library && ! $user instanceof LibraryUser) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
                'data' => (object) [],
            ], 200);
        }

        return response()->json([
            'status' => true,
            'data' => app(SubscriptionPermissionService::class)->permissionStatusMap($user),
        ], 200);
    }

   
   
    public function libraryPlan()
    {
        $subscriptions = Subscription::get();

        $features = DB::table('subscription_plan_features')
            ->where('feature_status', 1)
            ->whereNull('deleted_at')
            ->get();

        // All unique features (like your blade logic)
        $allFeatures = $features->pluck('name')->unique()->values();

        /*
        |--------------------------------------------------------------------------
        | Subscription Modes Mapping
        |--------------------------------------------------------------------------
        | NULL  => Deactivated
        | 0     => Free but Active
        | > 0   => Paid
        |--------------------------------------------------------------------------
        */

        $modesMap = [
            'monthly'       => ['monthly_fees', 'slash_price'],
            'three_monthly' => ['three_monthly_fees', 'three_monthly_slash_price'],
            'six_monthly'   => ['six_monthly_fees', 'six_monthly_slash_price'],
            'yearly'        => ['yearly_fees', 'yearly_slash_price'],
            'two_yearly'    => ['two_yearly_fees', 'two_yearly_slash_price'],
        ];

        $subscriptionPlans = [];

        foreach ($modesMap as $modeName => $columns) {

            [$feeColumn, $slashColumn] = $columns;

            $plans = [];

            foreach ($subscriptions as $subscription) {

                // 🚫 If NULL → Deactivated Mode
                if (is_null($subscription->$feeColumn)) {
                    continue;
                }

                $subscriptionFeatures = $features
                    ->where('subscription_id', $subscription->id)
                    ->pluck('name')
                    ->toArray();

                $featureList = $allFeatures->map(function ($featureName) use ($subscriptionFeatures) {
                    return [
                        'name'    => $featureName,
                        'enabled' => in_array($featureName, $subscriptionFeatures)
                    ];
                })->values();

                $plans[] = [
                    'id'=>$subscription->id,
                    'name'           => $subscription->name,
                     'price'          => (string) $subscription->$feeColumn,
                    'original_price' => (string) ($subscription->$slashColumn ?? '0'),
                    'features'       => $featureList
                ];
            }

            if (!empty($plans)) {
                $subscriptionPlans[] = [
                    'name'  => $modeName,
                    'plans' => $plans
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Build Subscription Type List
        |--------------------------------------------------------------------------
        */
        
        $modeIds = [
            'monthly'       => 1,
            'yearly'        => 2,
            'three_monthly' => 3,
            'six_monthly'   => 4,
            'two_yearly'    => 5,
        ];
        $subscriptionTypes = collect($subscriptionPlans)
        ->pluck('name')
        ->map(function ($name) use ($modeIds) {
            return [
                'id'   => $modeIds[$name] ?? null,
                'name' => $name
            ];
        })
        ->values();

     

        /*
        |--------------------------------------------------------------------------
        | Final Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'status'  => true,
            'message' => 'Plans fetched successfully',
            'data'    => [
                'subscription_type' => $subscriptionTypes,
                'subscription_plan' => $subscriptionPlans
            ]
        ],200);
    }


    public function sendResetLinkEmail(Request $request)
    {
        
         $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
              
            ], 422);
        }


        $user = Library::where('email', $request->email)
            ->select('library_name as name', 'email')
            ->first();
           
         if (!$user) {
            $user = LibraryUser::where('email', $request->email)
                ->select('name', 'email') 
                ->first();
        }
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'No user register with this email',
               
            ],200);
        }

        $token = Str::random(60); 

        DB::table('password_resets')->updateOrInsert(
            ['email' => $user->email],
            ['token' => $token, 'created_at' => now()]
        );

        try {
            \Log::info('Forgot email');
            Mail::send('email.forgot-password', [
                'token' => $token,
                'email' => $user->email,
                'name' => $user->name,
                'resetLink'=>'link'
            ], function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Reset Your Account Password');
            });

            return response()->json([
                'status' => true,
                'message' => 'Reset Password link has been sent to your email address.',
                'token'=>$token,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to send email from mail service down',
                
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
              
            ], 422);
        }

        $record = DB::table('password_resets')->where('email', $request->email)->first();

        if (!$record || !hash_equals($record->token, $request->token)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired token.',
              
            ], 401);
        }

        $user = Library::where('email', $request->email)->first()
            ?? LibraryUser::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.',
                
            ], 404);
        }

        $user->password = Hash::make($request->password);
        $user->original_password = $request->password;
        $user->save();

        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Password has been reset successfully.',
            'data' => [
                'user_id' => $user->id,
                'user_type' => $user instanceof Library ? 'library' : 'library_user'
            ]
        ], 200);
    }


    public function branchDetail(Request $request){
         $validator = Validator::make($request->all(), [
            'branch_id' => 'required|int',
       
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
                'data' => (object)[]
            ], 422);
        }

        $branch = Branch::where('id', $request->branch_id)->get();

          return response()->json([
            'status' => true,
            'message' => 'Branch detail fetched successfully.',
            'data' => [
                'branch' => $branch
            ]
        ], 200);
    }

    public function profile(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Profile fetched successfully.',
            'data' => $this->profilePayload($request->user()),
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if ($user instanceof Library) {
            $validator = Validator::make($request->all(), [
                'library_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'library_mobile' => 'required|digits:10',
                'library_owner' => 'required|string|max:255',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'mobile' => 'required|digits:10',
            ]);
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $emailExists = Library::where('email', $request->email)
            ->when($user instanceof Library, function ($query) use ($user) {
                $query->where('id', '!=', $user->id);
            })
            ->exists()
            || LibraryUser::where('email', $request->email)
                ->when($user instanceof LibraryUser, function ($query) use ($user) {
                    $query->where('id', '!=', $user->id);
                })
                ->exists();

        if ($emailExists) {
            return response()->json([
                'status' => false,
                'message' => 'Email already exists.',
            ], 200);
        }

        if ($user instanceof Library) {
            $data = [
                'library_name' => $request->library_name,
                'email' => $request->email,
                'library_mobile' => $request->library_mobile,
                'library_owner' => $request->library_owner,
            ];

            if (Schema::hasColumn('libraries', 'library_email')) {
                $data['library_email'] = $request->email;
            }

            if (Schema::hasColumn('libraries', 'is_profile')) {
                $data['is_profile'] = 1;
            }

            $user->update($data);
        } else {
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'mobile' => $request->mobile,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully.',
            'data' => $this->profilePayload($user->fresh()),
        ], 200);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Old password is incorrect.',
            ], 200);
        }

        $user->password = Hash::make($request->password);
        $user->original_password = $request->password;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Password updated successfully.',
        ], 200);
    }

    private function profilePayload($user)
    {
        if ($user instanceof Library) {
            return [
                'user_type' => 'library',
                'library_id' => $user->id,
                'library_name' => $user->library_name ?? '',
                'email' => $user->email ?? $user->library_email ?? '',
                'library_mobile' => $user->library_mobile ?? '',
                'library_owner' => $user->library_owner ?? '',
                'profile_image' => !empty($user->library_logo)
                    ? asset('public/'.$user->library_logo)
                    : asset('public/img/user.png'),
            ];
        }

        $library = Library::find($user->library_id);

        return [
            'user_type' => 'library_user',
            'library_id' => $user->library_id,
            'library_user_id' => $user->id,
            'library_name' => $library->library_name ?? '',
            'email' => $user->email ?? '',
            'library_mobile' => $user->mobile ?? '',
            'library_owner' => $user->name ?? '',
            'profile_image' => !empty($user->profile_picture)
                ? asset('public/'.$user->profile_picture)
                : asset('public/img/user.png'),
        ];
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function createOrderApi(Request $request, LibraryPaymentService $service)
    {
        
        $validated = $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'plan_mode'       => 'required|integer|in:1,2,3,4,5',
        ]);
       

        $libraryId = authLibraryId();

       try {
            $data = $service->razorpayPaymentCore(
                (int) $validated['subscription_id'],
                (int) $validated['plan_mode'],
                (int) $libraryId
            );

            if ($data['type'] === 'free') {

                DB::transaction(function () use ($service, $data) {
                    $service->finalize($data['transaction'], 'FREE');
                });

                $library = Library::find($libraryId);

                return response()->json([
                    'status' => true,
                    'message' => 'Free plan activated successfully',
                    'data' => [
                         'is_paid'=>true,         // if free then true otherwise false
                         'library_no' => $library->library_no ?? ''
                    ]
                    
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'order_id' => $data['order']['id'],
                    'amount' => (string) $data['order']['amount'],
                    'currency' => 'INR',
                    'transaction_id' => (string) $data['transaction']->id,
                    'key_id' => config('services.razorpay.key'),
                    'is_paid'=>false         // if free then true otherwise false
                ]
            ]);

        } catch (\Exception $e) {

            \Log::error('Create Order Failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Unable to create order'
            ], 500);
        }
    }

    public function verifyPaymentApi(Request $request, LibraryPaymentService $service)
    {
        $request->merge([
            'payment_status' => strtolower($request->payment_status)
        ]);

        $validated = $request->validate([
            'transaction_id'      => 'required|exists:library_transactions,id',
            'payment_status'      => 'required|in:success,failed',
            'razorpay_payment_id' => 'required_if:payment_status,success',
            'razorpay_order_id'   => 'required_if:payment_status,success',
            'razorpay_signature'  => 'required_if:payment_status,success',
            'payment_response'    => 'nullable'
        ]);

        // create temp order
        $tempOrder = TempOrder::create([
            'razorpay_order_id' => $validated['razorpay_order_id'] ?? null,
            'library_transaction_id' => $validated['transaction_id'],
            'payment_status' => 'pending'
        ]);

        // If payment failed
        if ($validated['payment_status'] === 'failed') {

            $tempOrder->update([
                'payment_status' => 'failed',
                'response' => $validated['payment_response'] ?? null
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Payment failed',
                'data' => [
                    'razorpay_payment_id' => $validated['razorpay_payment_id'] ?? null,
                    'razorpay_order_id'   => $validated['razorpay_order_id'] ?? null,
                ]
            ], 200);
        }

        DB::beginTransaction();

        try {

            $transaction = LibraryTransaction::where('id', $validated['transaction_id'])
                ->where('is_paid', 0)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaction->is_paid == 1) {
                throw new \Exception('Payment already processed');
            }

            if (!$service->verifySignature(
                $validated['razorpay_order_id'],
                $validated['razorpay_payment_id'],
                $validated['razorpay_signature']
            )) {
                throw new \Exception('Invalid payment signature');
            }

            $service->finalize(
                $transaction,
                $validated['razorpay_payment_id'],
                $validated['payment_response']
            );

            DB::commit();

            $library = Library::find($transaction->library_id);

            $response = [
                'status' => true,
                'message' => 'Your payment successful',
                'data' => [
                    'library_no' => $library->library_no ?? ''
                ]
            ];

            $tempOrder->update([
                'payment_status' => 'success',
                'response' => $validated['payment_response']
            ]);

            return response()->json($response, 200);

        } catch (\Exception $e) {

            DB::rollBack();

            \Log::error('Payment Verification Failed', [
                'error' => $e->getMessage()
            ]);

            $response = [
                'status' => false,
                'message' => $e->getMessage(),
                'data' => [
                    'razorpay_payment_id' => $validated['razorpay_payment_id'] ?? null,
                    'razorpay_order_id'   => $validated['razorpay_order_id'] ?? null,
                ]
            ];

            $tempOrder->update([
                'payment_status' => 'failed',
                'error_message'  => $e->getMessage(),
                'response'       => $validated['payment_response']
            ]);

            return response()->json($response, 200);
        }
    }

    public function configure(Request $request,LibraryConfigurationService $service) {
        $libraryId = authLibraryId();

        if ($request->has('branch_detail') || $request->has('branch_master')) {

            $normalized = [];

            /* ================= BRANCH DETAIL ================= */
            $detail = $request->branch_detail ?? [];

            $normalized['name']         = $detail['branch_name'] ?? null;
            $normalized['display_name'] = $detail['display_name'] ?? $detail['branch_name'] ?? null;
            $normalized['email']        = $detail['email'] ?? null;
            $normalized['mobile']       = $detail['contact_number'] ?? null;
            $normalized['founder_day']  = $detail['founded_date'] ?? null;
            $normalized['upi_id']       = $detail['upi_id'] ?? null;
            $normalized['library_logo']       = $detail['library_logo'] ?? null;

            /* ================= NEW FIELDS (ADD THIS) ================= */
            $normalized['library_category'] = isset($detail['library_category']) 
            ? strtolower($detail['library_category']) 
            : null;
             $normalized['working_days'] = $detail['working_days'] ?? null;

            $normalized['library_address']  = $detail['library_address'] ?? null;
            $normalized['library_zip']      = $detail['library_zip'] ?? null;

            $normalized['state_id'] = !empty($detail['state_id']) ? $detail['state_id'] : null;
            $normalized['city_id']  = !empty($detail['city_id']) ? $detail['city_id'] : null;

            $normalized['google_map']       = $detail['google_map'] ?? null;
            $normalized['description']      = $detail['description'] ?? null;

            $normalized['latitude']         = $detail['latitude'] ?? null;
            $normalized['longitude']        = $detail['longitude'] ?? null;

            $normalized['fixed_billing_date'] = $detail['fixed_billing_date'] ?? null;

            /* ================= FEATURES (IMPORTANT) ================= */
            $normalized['features'] = is_array($request->features) ? $request->features : [];
            /* ================= LIBRARY IMAGES ================= */
            
            $normalized['library_images'] =$detail['library_images'] ?? null;
            /* ================= MASTER ================= */
            $master = $request->branch_master ?? [];

            $normalized['seats']         = $master['total_seats'] ?? null;
            $normalized['hour']          = $master['operating_hours'] ?? null;
            $normalized['locker_amount'] = $master['locker_amount'] ?? null;
            $normalized['extend_days']   = $master['extend_days'] ?? null;
            $normalized['token_money']   = $master['token_money'] ?? null;

            /* ================= PLAN ================= */
            if ($request->has('plan')) {
                $plan = $request->plan;

                if (!empty($plan['plan_name'])) {
                    $normalized['plans'] = [$plan['plan_name']];
                }

                if (!empty($plan['days'])) {
                    $normalized['monthdays'] = $plan['days'];
                }
            }

            /* ================= FLOORS ================= */
            $normalized['floors'] = $request->floors ?? [];

            /* ================= BRANCH ID ================= */
            if ($request->has('branch_id')) {
                $normalized['branch_id'] = $request->branch_id;
            }

            /* ================= MERGE ================= */
            $request->merge($normalized);
        }


         $request->validate([
           
            'branch_id'  => 'nullable|exists:branches,id'
            
        ]);

          
        // $validation = branchCountValidation();

        // if ($validation['success']) {
        //     return response()->json([
        //         'status'  => false,
        //         'message' => $validation['message']
        //     ], 400);
        // }


        $branchId  = $request->branch_id ?? null;
       
       

        $planCount = Plan::where('library_id', $libraryId)->count();
        $hasOneMonthPlan = Plan::where('library_id', $libraryId)
            ->where('plan_id', 1)
            ->whereRaw('UPPER(type) = ?', ['MONTH'])
            ->exists();

        /* ================= BASE VALIDATION ================= */

        $rules = [
            'name'        => 'required|string|max:255',
            'email'       => 'required|email',
            'mobile'      => 'required|digits:10',
            'locker_amount' => 'nullable|integer',
            'token_money' => 'nullable|integer',
            'extend_days' => 'required|integer',
            'hour' => 'required|numeric|min:1|max:24',
            'seats'       => 'required|integer',
            'founder_day' => 'nullable|date',
            'plans'       => $planCount === 0 ? 'required|array|min:1' : 'nullable|array',
            'plans.*'     => 'string',
            'floors'      => 'nullable|array',
            'library_address' => 'nullable|string',
            'library_zip'     => 'nullable|string|max:6',
            'state_id'        => 'nullable|exists:states,id',
            'city_id'         => 'nullable|exists:cities,id',

            'latitude'        => 'nullable',
            'longitude'       => 'nullable',

            'google_map'      => 'nullable|string',
            'description'     => 'nullable|string',

            'library_category'=> 'nullable|string|in:public,private',
             'working_days' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $validDays = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

                    $days = array_map('trim', explode(',', $value));

                    foreach ($days as $day) {
                        if (!in_array($day, $validDays)) {
                            $fail("Invalid day: $day");
                        }
                    }
                }
            ],

            'fixed_billing_date' => 'nullable|integer|min:1|max:31',
            'features'   => 'nullable|array',
            'features.*' => 'integer',
            // 'library_images'   => 'nullable|array|max:4',
            // 'library_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            'library_logo'=>'nullable|string',
            'library_images' => 'nullable|array|max:4',
            'library_images.*' => [
                'nullable',
                function ($attribute, $value, $fail) {

                    // ✅ CASE 1: WEB (file)
                    if ($value instanceof \Illuminate\Http\UploadedFile) {

                        $ext = strtolower($value->getClientOriginalExtension());

                        if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
                            $fail('Only jpg, jpeg, png, webp allowed.');
                        }

                        if ($value->getSize() > 2048 * 1024) {
                            $fail('File size must be less than 2MB.');
                        }
                    }

                    // ✅ CASE 2: APP (URL or temp path)
                    elseif (is_string($value)) {

                        $path = $value;

                        // ✅ if URL → extract path
                        if (filter_var($value, FILTER_VALIDATE_URL)) {
                            $path = parse_url($value, PHP_URL_PATH);
                        }

                        // 🔥 CASE 1: TEMP IMAGE
                        if (str_contains($path, 'temp/')) {

                            $pos = strpos($path, 'temp/');
                            $tempPath = substr($path, $pos);

                            if (!Storage::disk('public')->exists($tempPath)) {
                                $fail('Temp image not found.');
                            }
                        }

                        // 🔥 CASE 2: PERMANENT IMAGE (uploads)
                        elseif (str_contains($path, 'uploads/')) {

                            // ✅ just allow (no fail)
                            return;
                        }

                        // ❌ INVALID
                        else {
                            $fail('Invalid image path.');
                        }
                    }

                    // ❌ invalid type
                    else {
                        $fail('Invalid image format.');
                    }
                }
            ],
        ];

        $validator = Validator::make($request->all(), $rules);
       
        // ✅ FIRST get validated data
        $validated = $validator->validated();

       // working_days
        if (!empty($validated['working_days'])) {
            $days = array_map('trim', explode(',', $validated['working_days']));
            $validated['working_days'] = implode(', ', $days);
        }

       

        $plans = $validated['plans'] ?? [];

        $slug = Str::slug($validated['name'].'-'.$libraryId);
   
        if($branchId){
              $existingBranch = Branch::where('library_id', $libraryId)->where('id', $branchId) ->first();
        }else{
             $existingBranch = Branch::where('library_id', $libraryId)
            ->where('slug', $slug)
            ->when($branchId, function ($query) use ($branchId) {
                $query->where('id', '!=', $branchId); // ignore current branch
            })
            ->first();
    
        }
      
   
        if (!$branchId && $existingBranch) {
            return response()->json([
                'status' => false,
                'message' => 'Branch with same name already exists'
            ], 200);
        }

        $branchCount = Branch::where('library_id', $libraryId)->count();

        /* Require 1 MONTH only when it does not already exist for the library. */
        if (!$hasOneMonthPlan) {
            $validator->after(function ($validator) use ($plans) {
                $hasMonthPlan = collect($plans)->contains(
                    fn ($plan) => strtoupper(trim($plan)) === '1 MONTH'
                );

                if (!$hasMonthPlan) {
                    $validator->errors()->add(
                        'plans',
                        '1 MONTH plan is required.'
                    );
                }
            });
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        $validated = $validator->validated();
        // $validated['library_images'] = $request->file('library_images') ?? null;
        $validated['features'] = $validated['features'] ?? [];
        $validated['google_map'] = $validated['google_map'] ?? null;

        /* ================= CALL GLOBAL SERVICE ================= */
     
        DB::beginTransaction();
        try {
            $response = $service->configure($request, $validated, $libraryId, $existingBranch, $branchCount,false);
            if (!$response['status']) {
                throw new \Exception($response['message']);
            }
            /* ================= SHIFT CONFIGURE ================= */

            if (!empty($request->shifts) && isset($response['branch_id']) && $response['status']) {

                $shiftData = [
                    'plan_types' => $request->shifts
                ];

                $shiftResponse =$service->shiftConfigure($shiftData, $response['branch_id'],false);
                if (!$shiftResponse['status']) {
                    throw new \Exception($shiftResponse['message']);
                }
            
            }
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Branch and shifts configured successfully.',
            ]);
       
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function shiftConfigure(Request $request,LibraryConfigurationService $shiftService) {
        $validator = Validator::make($request->all(), [
            'plan_types'                   => 'required|array|min:1',
            'plan_types.*.day_type_id'     => 'required',
            'plan_types.*.start_time'      => 'required|date_format:H:i',
            'plan_types.*.end_time'        => 'required|date_format:H:i',
            'plan_types.*.slot_hours'      => 'required|numeric|min:1',
            'plan_types.*.price'           => 'required|numeric|min:0',
            'plan_types.*.custom_plan_type'=> 'nullable|string|max:100',
            'branch_id'  => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $response = $shiftService->shiftConfigure($validator->validated(),$request->branch_id);

        return response()->json($response);
    }

          /*
    |--------------------------------------------------------------------------
    | Branch List API
    |--------------------------------------------------------------------------
    */

   public function branches()
   {
      $library = auth('library_api')->user();
       $libraryId = authLibraryId();
      $branches = Branch::where('library_id', authLibraryId())->withCount('learners')->with(['state','city'])
        ->select('id', 'name','mobile','email', 'library_address','library_zip', 'status','state_id','city_id','library_images','library_logo')
        ->get() 
        ->map(function ($branch) {
            // ✅ Decode JSON images
            $images = [];
            if (!empty($branch->library_images)) {
                $decodedImages = is_array($branch->library_images) 
                    ? $branch->library_images 
                    : json_decode($branch->library_images, true);

                if (is_array($decodedImages)) {
                    $images = array_map(function ($img) {
                        return asset('public/' . $img);
                    }, $decodedImages);
                }
            }

            return [
                'id' => $branch->id,
                'uuid'=>$branch->uuid ?? '',
                'name' => $branch->name,
                'display_name' => $branch->display_name ?? '',
                'mobile' => $branch->mobile,
                'email' => $branch->email,
                'address' => $branch->library_address ?? '',
                'state' => $branch->state->state_name ?? '',
                'city' => $branch->city->city_name ?? '',
                'zip_code' => $branch->library_zip ?? '',
                'status' => $branch->status == 1 ? 'Active' : 'Deactive',
                
                'library_logo' =>  !empty($branch->library_logo) ? asset('public/'.$branch->library_logo) : asset('public/img/user.png'),
                'library_images' => $images,
                // 🔥 main logic
                'can_delete' => $branch->learners_count == 0 ? true : false
            ];
        });


        return response()->json([
            'status' => true,
            'data' => $branches
        ]);
   }

    public function branchDetailEdit(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id'
        ]);
        $libraryId = authLibraryId();
        $branch = Branch::where('id', $request->branch_id)
            ->where('library_id', authLibraryId())
            ->with(['state','city'])
            ->first();

        if (!$branch) {
            return response()->json([
                'status' => false,
                'message' => 'Branch not found for this library'
            ], 404);
        }

        $hour = Hour::withoutGlobalScopes()
            ->where('branch_id', $branch->id)
            ->first();

        return response()->json([
            'status' => true,
           'data' => [
            'branch_id'=>$branch->id,
            'branch_uuid'=>$branch->uuid,

        
            'branch_name'   => $branch->name ?? '',
            'display_name'  => $branch->display_name ?? '',
            'email'         => $branch->email ?? '',
            'contact_number'=> $branch->mobile ?? '',
            'founded_date'  => $branch->founder_day ?? '',
            'upi_id'        => $branch->upi_id ?? '',

            'library_category' => $branch->library_category ?? '',

            'working_days' => $branch->working_days ?? null,

            'library_address' => $branch->library_address ?? '',
            'library_zip'     => $branch->library_zip ?? '',

            'state_id' => $branch->state_id ?? null,
            'city_id'  => $branch->city_id ?? null,

            'google_map' => $branch->google_map ?? '',
            'description'=> $branch->description ?? '',

            'latitude'  => $branch->latitude ?? '',
            'longitude' => $branch->longitude ?? '',
            'token_money' => $branch->token_money ?? '',
            'total_seats'      => $hour->seats ?? 0,
            'operating_hours'  => $hour->hour ?? 0,
            'locker_amount'    => $branch->locker_amount ?? 0,
            'extend_days'      => $branch->extend_days ?? 0,

            'fixed_billing_date' => $branch->fixed_billing_date ?? null,

            // ✅ keep only ONE logo here
            'library_logo' => !empty($branch->library_logo)
                ? asset('public/'.$branch->library_logo)
                : asset('public/img/user.png'),

                
            /* ================= EXTRA ================= */
            'state_name' => optional($branch->state)->state_name ?? '',
            'city_name'  => optional($branch->city)->city_name ?? '',
              

                /* ================= MASTER ================= */
                // 'branch_master' => [
                //     'total_seats'      => $hour->seats ?? 0,
                //     'operating_hours'  => $hour->hour ?? 0,
                //     'locker_amount'    => $branch->locker_amount ?? 0,
                //     'extend_days'      => $branch->extend_days ?? 0,
                //     'token_money'      => $branch->token_money ?? 0,
                // ],

                /* ================= FEATURES ================= */
                'features' => is_array($branch->features)
                    ? $branch->features
                    : (json_decode($branch->features, true) ?? []),

                /* ================= IMAGES ================= */
                'library_images' => !empty($branch->library_images)
                    ? collect(
                        is_array($branch->library_images)
                            ? $branch->library_images
                            : json_decode($branch->library_images, true)
                    )
                    ->map(fn($img) => asset('public/'.$img))
                    ->values()
                    : [],

            ]
        ]);
    }

    public function getConfigurePrice(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id'
        ]);

        $libraryId = authLibraryId();
        $branchId = $request->branch_id;

        // ✅ Cache key
        $cacheKey = "configure_price_{$libraryId}_{$branchId}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($libraryId, $branchId) {

            // ✅ Single branch check (light query)
            $branchExists = Branch::where('id', $branchId)
                ->where('library_id', $libraryId)
                ->exists();

            if (!$branchExists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Branch not found for this library'
                ], 404);
            }

            // ✅ Plan (minimal select)
            $plan = Plan::where('library_id', $libraryId)
                ->where('plan_id', 1)
                ->where('type', 'MONTH')
                ->select('id')
                ->first();

            if (!$plan) {
                throw new \Exception('No plan found');
            }

            $planId = $plan->id;

            // ✅ Plan type mapping
            $planTypes = [
                1 => 'Full Day',
                2 => 'First Half',
                3 => 'Second Half',
                8 => 'All Day',
                9 => 'Full Night',
                0 => 'Custom',
                10 => 'Reserved',
                11 => 'VIP',
            ];

            // ✅ Fetch everything in minimal queries
            $planTypesData = DB::table('plan_types')
                ->where('branch_id', $branchId)
                ->whereNull('deleted_at')
                ->select('id', 'name', 'day_type_id', 'start_time', 'end_time', 'slot_hours')
                ->get();

            $usedPlanTypes = LearnerDetail::whereIn('plan_type_id', $planTypesData->pluck('id')->toArray())
                ->pluck('plan_type_id')
                ->flip();

            $prices = DB::table('plan_prices')
                ->where('plan_id', $planId)
                ->pluck('price', 'plan_type_id');

            $hour = DB::table('hour')
                ->where('branch_id', $branchId)
                ->value('hour');

            // ✅ Format (no extra queries)
            $data = $planTypesData->map(function ($item) use ($planTypes, $prices, $usedPlanTypes) {
                return [
                    'id'               => $item->id,
                    'custom_plan_type' => $item->name ?? '',
                    'plan_type_name'   => $planTypes[$item->day_type_id] ?? '',
                    'day_type_id'      => $item->day_type_id ?? '',
                    'start_time'       => $item->start_time ?? '',
                    'end_time'         => $item->end_time ?? '',
                    'slot_hours'       => $item->slot_hours ?? '',
                    'price'            => (string) ($prices[$item->id] ?? 0),
                    'can_delete'       => !isset($usedPlanTypes[$item->id]),
                ];
            });


            Log::info(['shiftinfo'=>$data]);
            return [
                'status' => true,
                'data' => [
                    'shifts' => $data,
                    'operating_hours' => $hour ?? 0
                ]
            ];
        });
    }

//     public function branchDetailEdit(Request $request)
// {
//     // ✅ Validation
//     $request->validate([
//         'branch_id' => 'required|exists:branches,id'
//     ]);

//     $libraryId = authLibraryId();
//     $branchId  = $request->branch_id;

//     // ✅ Cache (optional but recommended 🔥)
//     $cacheKey = "branch_detail_{$branchId}_{$libraryId}";

//     $data = Cache::remember($cacheKey, 60, function () use ($branchId, $libraryId) {

//         // ✅ Single optimized query (JOIN)
//         $branch = DB::table('branches')
//             ->leftJoin('hour', 'hour.branch_id', '=', 'branches.id')
//             ->leftJoin('states', 'states.id', '=', 'branches.state_id')
//             ->leftJoin('cities', 'cities.id', '=', 'branches.city_id')
//             ->where('branches.id', $branchId)
//             ->where('branches.library_id', $libraryId)
//             ->select(
//                 'branches.*',
//                 'hour.seats as total_seats',
//                 'hour.hour as operating_hours',
//                 'states.state_name',
//                 'cities.city_name'
//             )
//             ->first();

//         if (!$branch) {
//             return null;
//         }

//         // ✅ Features optimize
//         $features = is_array($branch->features)
//             ? $branch->features
//             : json_decode($branch->features, true);

//         $features = $features ?? [];

//         // ✅ Images optimize
//         $images = [];
//         if (!empty($branch->library_images)) {
//             $decoded = is_array($branch->library_images)
//                 ? $branch->library_images
//                 : json_decode($branch->library_images, true);

//             if (is_array($decoded)) {
//                 foreach ($decoded as $img) {
//                     $images[] = asset('storage/' . $img);
//                 }
//             }
//         }

//         return [
//             'branch_id' => $branch->id,
//             'branch_uuid' => $branch->uuid,

//             'branch_name' => $branch->name ?? '',
//             'display_name' => $branch->display_name ?? '',
//             'email' => $branch->email ?? '',
//             'contact_number' => $branch->mobile ?? '',
//             'founded_date' => $branch->founder_day ?? '',
//             'upi_id' => $branch->upi_id ?? '',

//             'library_category' => $branch->library_category ?? '',
//             'working_days' => $branch->working_days ?? null,

//             'library_address' => $branch->library_address ?? '',
//             'library_zip' => $branch->library_zip ?? '',

//             'state_id' => $branch->state_id,
//             'city_id' => $branch->city_id,

//             'google_map' => $branch->google_map ?? '',
//             'description' => $branch->description ?? '',

//             'latitude' => $branch->latitude ?? '',
//             'longitude' => $branch->longitude ?? '',

//             'token_money' => $branch->token_money ?? '',
//             'total_seats' => $branch->total_seats ?? 0,
//             'operating_hours' => $branch->operating_hours ?? 0,
//             'locker_amount' => $branch->locker_amount ?? 0,
//             'extend_days' => $branch->extend_days ?? 0,

//             'fixed_billing_date' => $branch->fixed_billing_date ?? null,

//             'library_logo' => !empty($branch->library_logo)
//                 ? asset('storage/' . $branch->library_logo)
//                 : asset('public/img/user.png'),

//             'state_name' => $branch->state_name ?? '',
//             'city_name' => $branch->city_name ?? '',

//             'features' => $features,
//             'library_images' => $images,
//         ];
//     });

//     // ✅ Not found
//     if (!$data) {
//         return response()->json([
//             'status' => false,
//             'message' => 'Branch not found for this library'
//         ], 404);
//     }

//     return response()->json([
//         'status' => true,
//         'data' => $data
//     ]);
// }

   

    // public function branchShiftConfigure(Request $request){
    //     $branchId  =$request->branch_id;
    //   $libraryId = authLibraryId();

    //   // Branch details
    //   $branch = Branch::select(
    //      'name as branch_name',
    //      'founder_day as founded_date',
    //      'email',
    //      'mobile as contact_number',
    //      'upi_id',
    //      'extend_days',
    //      'locker_amount'
    //   )->where('id', $branchId)->first();

    //   // Branch master
    //   $branchMaster = Hour::where('branch_id', $branchId)
    //      ->select(
    //            'seats as total_seats',
    //            'hour as operating_hours'
    //      )->first();

    //   // Plans
    //   $plans = Plan::where('library_id', $libraryId)->select('id','name','monthdays')
    //      ->get();

    //   // Floors
    //   $floors = Floor::where('branch_id', $branchId)
    //      ->select(
    //            'floor_no',
    //            'name as floor_name',
    //            'from_seat',
    //            'to_seat',
    //            'total_seats'
    //      )->get();

    //   // Shifts
    //   $shifts = collect();

    //   if ($branchId) {
    //         // PlanPrice::leftJoin('plan_prices', 'plan_prices.plan_type_id', '=', 'plan_types.id')
    //      $shifts = PlanType::withoutGlobalScopes()
    //            ->where('branch_id', $branchId)
    //            ->select(
    //               'name',
    //               'day_type_id as type',
    //               'name as custom_name',
    //               'start_time',
    //               'end_time',
    //               'slot_hours as duration_hours',
    //            )
    //            ->get();
    //   }

    //   return response()->json([
    //      'status'  => true,
    //      'message' => 'Branch data fetched successfully',
    //      'data'    => [
    //            'branch_details' => $branch ?? [],

    //            'branch_master' => [
    //               'total_seats'      => $branchMaster->total_seats ?? 0,
    //               'operating_hours'  => $branchMaster->operating_hours ?? 0,
    //               'extend_days'      => $branch->extend_days ?? 0,
    //               'locker_amount'    => $branch->locker_amount ?? 0,
    //            ],

    //            'plan'   => $plans,
    //            'floors' => $floors,
    //            'shifts' => $shifts
    //      ]
    //   ]);

    // }

}
