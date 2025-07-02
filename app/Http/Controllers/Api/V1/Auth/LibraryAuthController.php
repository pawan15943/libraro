<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\Library;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class LibraryAuthController extends Controller
{

    public function setting()
    {
        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => 'Settings fetched successfully.',
            'data' => [
                'app_version' => 'v1',
                'force_update' => false,
                'social_login_url' => [
                    'youtube' => 'https://www.youtube.com/@Libraroindia',
                    'linkedin' => 'https://www.linkedin.com/in/libraro/',
                    'instagram' => 'https://www.instagram.com/libraro.in/',
                    'facebook' => 'https://www.facebook.com/libraro.in'
                ],
                'upload_csv_url' => [
                    'master' => url('public/sample/master.csv'),
                    'learner' => url('public/sample/learner.csv')
                ],
                'privacy_policy' => 'https://www.libraro.in/privacy-policy',
                'terms_and_conditions' => 'https://www.libraro.in/terms-and-condition',
                'contact_number' => ['+91-8114479678'],
                'email' => [
                    'support1' => 'support@libraro.in',
                    'support2' => 'support@libraro.in'
                ],
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
            'data' => [
                'subscription' => $plans
            ]
        ], 200);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:libraries,email',
            'mobile' => 'required|digits:10',
            'password' => 'required|min:6|confirmed',
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
                'code' => 200,
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
                'data' => (object)[]
            ], 500);
        }
    }


    public function verifyEmailOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'library_id' => 'required|exists:libraries,id',
            'otp' => 'required|digits:6'
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

        $library = Library::find($request->library_id);

        if ($library->email_verified_at) {
            return response()->json([
                'status' => false,
                'code' => 400,
                'message' => 'Email already verified.',
                'data' => (object)[]
            ], 400);
        }

        if ($library->email_otp !== $request->otp) {
            return response()->json([
                'status' => false,
                'code' => 401,
                'message' => 'Invalid OTP. Please try again.',
                'data' => (object)[]
            ], 401);
        }

        $library->email_verified_at = now();
        $library->save();

        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => 'Email verified successfully.',
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
                'code' => 422,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
                'data' => (object)[]
            ], 422);
        }

        $user = Library::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'code' => 401,
                'message' => 'Invalid email or password.',
                'data' => (object)[]
            ], 401);
        }

        if (is_null($user->email_verified_at)) {
            return response()->json([
                'status' => false,
                'code' => 403,
                'message' => 'Please verify your email before logging in.',
                'data' => (object)[]
            ], 403);
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

        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => 'Login successful.',
            'data' => [
                'token' => $token,
                'user' => $user
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
