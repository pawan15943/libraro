<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\RegisterLibrary;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseApiResource;
use App\Models\Branch;
use App\Models\Library;
use App\Models\LibraryUser;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlanType;
use App\Models\Subscription;
use App\Services\LibraryConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use DB;

class LibraryAuthController extends Controller
{
    protected function apiResponse($data, $message = 'Success', $code = 200)
    {
        return response()->json([
            'status' => true,
            'code' => $code,
            'message' => $message,
            'data' => $data
        ], $code);
    }
    public function getRazorpayCredentials()
    {
        return response()->json([
            'status'  => true,
            'code'    => 200,
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
       
        //  smtp email check verify valid remaining
        $validator = Validator::make($request->all(), [
            'library_name' => 'required|string|max:255',
            'email' => 'required|email|unique:libraries,email',
            'library_mobile' => 'required|digits:10',
            'password' => 'required|min:6',
            'device_type' => 'required',
            'device_id' => 'required',
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
            'device_type' => 'required',
            'device_id' => 'required',
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

        if ($request->device_id && $request->device_type) {
            $library->devices()->updateOrCreate(
                ['device_id' => $request->device_id],
                [
                    'device_type' => $request->device_type,
                    'token' => $token,
                    'guard_name' => 'library_api',
                ]
            );
        }
        return response()->json([
            'status' => true,
            'message' => 'Email verified successfully.',
            'token' => $token,
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
            'device_type' => 'required',
            'device_id'   => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'code'    => 200,
                'message' => $validator->errors()->first(),
                'data'    => (object)[]
            ], 200);
        }

        $user = Library::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'code'    => 200,
                'message' => 'No Library registered with this email',
            ], 200);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status'  => false,
                'code'    => 200,
                'message' => 'Password is invalid',
            ], 200);
        }

        if (is_null($user->email_verified_at)) {
            return response()->json([
                'status'             => true,
                'code'               => 200,
                'message'            => 'Please verify your email before logging in.',
                'is_email_verified'  => 0,
                'is_last_step'       => 0
            ], 200);
        }

        // Assign role if not exists
        if (!$user->hasAnyRole(['admin', 'library'])) {
            $user->assignRole('library');
        }

        // Revoke old tokens (optional but recommended)
        $user->tokens()->delete();

        $token = $user->createToken('library_token')->plainTextToken;

        $user->devices()->updateOrCreate(
            ['device_id' => $request->device_id],
            [
                'device_type' => $request->device_type,
                'token'       => $token,
                'guard_name'  => 'library_api',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Determine is_last_step Properly is_last_step=1 paid,is_last_step=2 branch added, is_last_step=3 shift add and comleted
        |--------------------------------------------------------------------------
        */

        $is_last_step = 0;

        if ($user->is_paid) {
            $is_last_step = 1;
        }

        if (Branch::where('library_id', $user->id)->where('status', 1)->exists()) {
            $is_last_step = 2;
        }

        $isPlanComplete =
            $user->status == 1 &&
            Plan::where('library_id', $user->id)->exists() &&
            PlanType::where('library_id', $user->id)->exists() &&
            PlanPrice::where('library_id', $user->id)->exists();

        if ($isPlanComplete) {
            $is_last_step = 3;
        }

        $branches = Branch::leftJoin('hour','branches.id','=','hour.branch_id')
            ->where('branches.library_id', $user->id)
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

        return response()->json([
            'status'            => true,
            'code'              => 200,
            'message'           => 'Login successful.',
            'is_email_verified' => 1,
            'is_last_step'      => $is_last_step,
            'token'             => $token,
            'library_id'        => $user->id,
            'data'              => $branches ?? [],
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
                    'name'           => $subscription->name,
                    'price'          => (int) $subscription->$feeColumn,
                    'original_price' => (int) ($subscription->$slashColumn ?? 0),
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

        $subscriptionTypes = collect($subscriptionPlans)
            ->pluck('name')
            ->map(function ($name) {
                return ['name' => $name];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Final Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => 'Plans fetched successfully',
            'data'    => [
                'subscription_type' => $subscriptionTypes,
                'subscription_plan' => $subscriptionPlans
            ]
        ]);
    }


    public function sendResetLinkEmail(Request $request)
    {
        
         $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'code'    => 422,
                'message' => $validator->errors()->first(),
                'data'    => (object)[]
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
                'code' => 200,
                'message' => 'No user register with this email',
                'data' => (object)[]
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
                'code' => 200,
                'message' => 'Reset Password link has been sent to your email address.',
                'token'=>$token,
                'data' => (object)[]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'code' => 500,
                'message' => 'Failed to send email from mail service down',
                
                'data' => (object)[]
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
                'code' => 422,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
                'data' => (object)[]
            ], 422);
        }

        $record = DB::table('password_resets')->where('email', $request->email)->first();

        if (!$record || !hash_equals($record->token, $request->token)) {
            return response()->json([
                'status' => false,
                'code' => 401,
                'message' => 'Invalid or expired token.',
                'data' => (object)[]
            ], 401);
        }

        $user = Library::where('email', $request->email)->first()
            ?? LibraryUser::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'code' => 404,
                'message' => 'User not found.',
                'data' => (object)[]
            ], 404);
        }

        $user->password = Hash::make($request->password);
        $user->original_password = $request->password;
        $user->save();

        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json([
            'status' => true,
            'code' => 200,
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
                'code' => 422,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
                'data' => (object)[]
            ], 422);
        }

        $branch = Branch::where('id', $request->branch_id)->get();

          return response()->json([
            'status' => true,
            'code' => 200,
            'message' => 'Branch detail fetched successfully.',
            'data' => [
                'branch' => $branch
            ]
        ], 200);
    }

    public function profile(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    // public function paymentApi(Request $request)
    // {
    //     $validated = $request->validate([
    //         'library_id'      => 'required|exists:libraries,id',
    //         'subscription_id' => 'required|exists:subscriptions,id',
    //         'plan_mode'       => 'required|integer|in:1,2,3,4,5',
    //     ]);

    //     try {
    //          $data = $this->razorpayPaymentCore(
    //             (int) $validated['subscription_id'],
    //             (int) $validated['plan_mode'],
    //             (int) $validated['library_id']
    //         );
            
    //         return response()->json([
    //             'status'  => true,
    //             'code'    => 200,
    //             'message' => 'Order created successfully',
    //             'data'    => [
    //                 'order'       => $data['order'],
    //                 'amount'         => $data['amount'],
    //                 'currency'       => 'INR',
    //                 'transaction_id' => $data['transaction']->id,
    //             ]
    //         ]);

           

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => $e->getMessage(),
    //         ], 400);
    //     }
    // }

    public function configure(Request $request,LibraryConfigurationService $service) {
         $validated = $request->validate([
            'library_id'      => 'required|exists:libraries,id',
            
        ]);

        $validation = branchCountValidation();

        if ($validation['success']) {
            return response()->json([
                'status'  => false,
                'message' => $validation['message']
            ], 400);
        }

        $libraryId =$request->library_id;

        $planCount = Plan::where('library_id', $libraryId)->count();

        /* ================= BASE VALIDATION ================= */

        $rules = [
            'name'        => 'required|string|max:255',
            'email'       => 'required|email',
            'mobile'      => 'required|digits:10',
            'locker_amount' => 'required',
            'extend_days' => 'required',
            'hour'        => 'required',
            'seats'       => 'required',
            'founder_day' => 'required',
            'plans'       => $planCount === 0 ? 'required|array|min:1' : 'nullable|array',
            'plans.*'     => 'string',
            'floors'      => 'nullable|array',
        ];

        $validator = Validator::make($request->all(), $rules);

        $plans = $request->input('plans', []);

        $slug = Str::slug($request->name.'-'.$libraryId);

        $existingBranch = Branch::where('slug', $slug)
            ->where('library_id', $libraryId)
            ->first();

        $branchCount = Branch::where('library_id', $libraryId)->count();

        /* ================= KEEP YOUR 1 MONTH RULE ================= */

        if ($existingBranch || $branchCount == 0) {

            $validator->after(function ($validator) use ($plans) {

                $hasMonthPlan = false;

                foreach ($plans ?? [] as $plan) {
                    if (strtoupper($plan) === '1 MONTH') {
                        $hasMonthPlan = true;
                        break;
                    }
                }

                if ($hasMonthPlan == false) {
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
        $validated['logo'] = null;
        $validated['library_images'] = [];
        $validated['features'] = $request->features ?? null;
        $validated['google_map'] = $request->google_map ?? null;

        /* ================= CALL GLOBAL SERVICE ================= */

        $response = $service->configure(
           $request,$validated,
            $libraryId,
            $existingBranch,
            $branchCount
        );

        return response()->json($response);
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

}
