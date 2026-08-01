<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\Learner;
use App\Services\LearnerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LearnerAuthController extends Controller
{
    /**
     * Learner self-service login — same credential pair as the web learner
     * login (learner_no + password), see Auth\LoginController::login().
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'learner_no' => 'required|string',
            'password'   => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $learner = Learner::where('learner_no', $request->learner_no)->first();

        if (! $learner || ! Hash::check($request->password, $learner->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid learner number or password',
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
            'user_type' => 'learner',
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
