<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\Learner;
use App\Services\LearnerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LearnerAuthController extends Controller
{
    /**
     * Learner self-service login — same identify-by (dob/email/learner_no)
     * + mobile pattern as the web attendance self-verify flow
     * (AttendanceController::verifyLearner()), not the learner_no+password
     * web login.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
                'login_with' => 'required|in:dob,email,learner_no',
                'uid'        => 'required',  // learner_no
                'mobile'     => 'required|regex:/^[5-9]\d{9}$/'
            ], [
                'login_with.required' => 'Please choose login type',
                'uid.required'        => 'This field is required',
                'mobile.required'     => 'Mobile number is required',
                'mobile.regex'        => 'Enter valid a mobile number'
            ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }
         $dob = null;

        // Safe DOB conversion
        try {
            $dob = Carbon::createFromFormat('d/m/Y', $request->uid)->format('Y-m-d');
        } catch (\Exception $e) {
            $dob = null;
        }
            \Log::info('Attendqance dob', ['dob' => $dob]);

 
        $learner = Learner::withoutGlobalScopes()
        ->where('mobile', encryptData($request->mobile))
        ->when($request->login_with === 'learner_no' && $request->uid, function ($q) use ($request) {
            $q->where('learner_no', $request->uid);
        })
        ->when($request->login_with === 'dob' && $dob, function ($q) use ($dob) {
            
            $q->where('dob', $dob);
        })
        ->when(
            $request->login_with === 'email' &&
            filter_var($request->uid, FILTER_VALIDATE_EMAIL),
            function ($q) use ($request) {
                $q->where('email', encryptData($request->uid));
            }
        )->first();
       
        
        if (!$learner) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, we couldn’t find your record. Please verify your details and try again.'
            ], 200);
        }

       

        if ((int) $learner->status !== 1) {
            return response()->json([
                'status'  => false,
                'message' => 'Your seat is not active. Please contact your library.',
            ], 200);
        }

        $token = $learner->createToken('learner_token')->plainTextToken;

        return response()->json([
            'status'    => true,
            'message'   => 'Login successful.',
            'token'     => $token,
            'user_type' => 3,
            'data'      => [
                'learner_id' => $learner->id,
                'learner_no' => $learner->learner_no,
                'name'       => $learner->name,
                'branch_id'  => $learner->branch_id,
                'library_id' => $learner->library_id,
            ],
        ], 200);
    }

    public function profile(Request $request, LearnerService $service)
    {
        try {
            return response()->json([
                'status' => true,
                'data'   => $service->getLearnerDetails(auth('learner_api')->id()),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    public function logout(Request $request)
    {
        $request->user('learner_api')->currentAccessToken()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}
