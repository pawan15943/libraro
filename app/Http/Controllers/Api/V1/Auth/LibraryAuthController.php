<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Library;
use App\Models\LibraryUser;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use DB;

class LibraryAuthController extends Controller
{

    public function setting()
    {
        return response()->json([
            'status' => true,
            'message' => 'Settings fetched successfully.',
            'data' => [
                'app_version' => 'v1',
                'force_update' => false,
                'youtube' => 'https://www.youtube.com/@Libraroindia',
                'linkedin' => 'https://www.linkedin.com/in/libraro/',
                'instagram' => 'https://www.instagram.com/libraro.in/',
                'facebook' => 'https://www.facebook.com/libraro.in',
                'master_sample' => url('public/sample/master.csv'),
                'learner_sample' => url('public/sample/learner.csv'),
                'privacy_policy' => 'https://www.libraro.in/privacy-policy',
                'terms_and_conditions' => 'https://www.libraro.in/terms-and-condition',
                'contact_number' => ['+91-8114479678'],
                'contact_email' => ['support@libraro.in'],
                'address' => '955, Vinoba Bhave Nagar, Kota, Landmark: New Balaji Computer Classes'
            ]
        ], 200);
    }

    public function libraryPlan()
    {
        $plans = Subscription::all();

        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => 'Subscription plans fetched successfully.',
            'subscription' => $plans
        ], 200);
    }

    public function register(Request $request)
    {
        //  smtp email check verify valid remaining
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:libraries,email',
            'mobile' => 'required|digits:10',
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


        $validated = $validator->validated();
        $otp = rand(100000, 999999);

        try {
            $library = Library::create([
                'library_name' => $validated['name'],
                'email' => $validated['email'],
                'library_mobile' => $validated['mobile'],
                'password' => Hash::make($validated['password']),
                'email_otp' => $otp,
            ]);

             $library->devices()->updateOrCreate(
                ['device_id' => $validated['device_id']],
                [
                    'device_type' => $validated['device_type'],
                    'guard_name' => 'library',
                ]
            );

            $data = [
                'name' => $library->library_name,
                'email' => $library->email,
                'otp' => $otp,
            ];

            Mail::send('email.verify-email', $data, function ($message) use ($data) {
                $message->to($data['email'], $data['name'])
                        ->subject('Verify Your Email Address');
            });

            return response()->json([
                'status' => true,
                'message' => 'OTP sent to registered email.',
                'data' => [
                    'library_id' => $library->id
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'code' => 500,
                'message' => 'Failed to register or send OTP email.',
                'error' => app()->environment('production') ? null : $e->getMessage(),
                
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
            'email' => 'required|email',
            'password' => 'required',
            'device_type' => 'required',
            'device_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'code' => 200,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
                'data' => (object)[]
            ], 200);
        }

        $user = Library::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'code' => 200,
                'message' => 'User Not Register',
               'registration' => 0
            ], 200);
        }

        if (is_null($user->email_verified_at)) {
            return response()->json([
                'status' => true,
                'code' => 200,
                'message' => 'Please verify your email before logging in.',
                'is_email_verified' => 0
            ], 200);
        }

        if (!$user->hasAnyRole(['admin', 'library'])) {
            $user->assignRole('library'); 
        }

        $token = $user->createToken('library_token')->plainTextToken;

        $user->devices()->updateOrCreate(
            ['device_id' => $request->device_id],
            [
                'device_type' => $request->device_type,
                'token' => $token,
                'guard_name' => 'library_api',
            ]
        );

        // registration=> 0=>user not register,1=> email not verify,2=>
        //suceess is_email_verified
        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => 'Login successful.',
            'is_email_verified' => 0,
            'token' => $token,
            // 'data' => [
            //     'token' => $token,
            //     // id, name, email,contact, role 
            //     'library' => cleanNull($user->toArray())
            // ],
           
        ], 200);
    }


    public function sendResetLinkEmail(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:libraries,email'
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
                'code' => 404,
                'message' => 'Email not found.',
                'data' => (object)[]
            ], 404);
        }

        $token = Str::random(60); 

        DB::table('password_resets')->updateOrInsert(
            ['email' => $user->email],
            ['token' => $token, 'created_at' => now()]
        );

        try {
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
                'message' => 'Reset token sent to your email.',
                'token'=>$token,
                'data' => (object)[]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'code' => 500,
                'message' => 'Failed to send email.',
                'error' => app()->environment('production') ? null : $e->getMessage(),
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
}
