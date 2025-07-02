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
            'app_version' => 'v1',
            'force_update' => false,
            'social_login_url' => ['youtub'=>'https://www.youtube.com/@Libraroindia','linkedin'=>'https://www.linkedin.com/in/libraro/','instagram'=>'https://www.instagram.com/libraro.in/','facebook'=>'https://www.facebook.com/libraro.in'],
            'upload_csv_url' => ['master'=>'/public/sample/master.csv','learner'=>'/public/sample/learner.csv'],
            'privacy_policy' => 'https://www.libraro.in/privacy-policy',
            'terms_and_conditions' => 'https://www.libraro.in/terms-and-condition',
            'contact_number' => ['1'=>'91-8114479678'],
            'email' => ['1'=>'support@libraro.in','2'=>'support@libraro.in'],
            'address' => '955, Vinoba Bhave Nagar, Kota,Landmark: New Balaji Computer Classes',
        ]);
    }
    public function libraryPlan(){
        $plans = Subscription::get();
       

        return response()->json([
            'status'=>true,
            'subscription' => $plans,
            
        ]);
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
                'message' => 'Validation errors',
                'errors' => $validator->errors()
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
                'message' => 'OTP sent to email.',
                'library_id' => $library->id
            ]);

        } catch (\Exception $e) {
             return response()->json([
                'status' => false,
                'message' => 'Failed to send OTP email. Please try again later.',
                'error' => app()->environment('production') ? null : $e->getMessage(), // show message only in dev
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
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $library = Library::find($request->library_id);

        if ($library->email_verified_at) {
            return response()->json([
            'status' => false,
            'message' => 'Already verified',
            'data' => (object)[]
        ], 400);

        }

        if ($library->email_otp != $request->otp) {
            return response()->json([
            'status' => false,
            'message' => 'Invalid OTP',
            'data' => (object)[]
        ], 401);

        }

        $library->email_verified_at = now();
        $library->save();

       return response()->json([
            'status' => true,
            'message' => 'Email verified successfully',
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
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Library::where('email', $request->email)->first();

         if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                    'status' => false,
                    'message' => 'Invalid credentials',
                    'data' => (object)[]
                ], 401);
            }

         if (is_null($user->email_verified_at)) {
            return response()->json([
                'status' => false,
                'message' => 'Please verify your email first.',
                'data' => (object)[]
            ], 403);
        }

        if (!$user->hasRole('admin', 'library')) {
            $user->assignRole('admin', 'library');
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
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'user' => $user
            ]
        ]);
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
