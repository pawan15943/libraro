<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\Learner;
use App\Services\LearnerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\Schema;

class LearnerAuthController extends Controller
{
    /**
     * Learner self-service login
     *
     * Identifies learner by UID (Learner No) or Email, authenticated with Mobile number.
     * Enforces single-device login by revoking previous tokens and requires device-type
     * and device-token in request headers.
     */
    public function login(Request $request)
    {
        $identifierInput = trim($request->input('identifier', $request->input('uid', '')));
        $passwordInput   = trim($request->input('password', $request->input('mobile', '')));

        if (empty($identifierInput) || empty($passwordInput)) {
            return response()->json([
                'status'  => false,
                'message' => 'Identifier (UID or Email) and Password (Mobile) are required.',
            ], 422);
        }

        // Support device credentials from headers or request body deviceInfo
        $deviceInfo  = $request->input('deviceInfo', []);
        $deviceType  = $request->header('X-Platform')
            ?? $request->header('Platform')
            ?? $request->header('device-type') 
            ?? $request->header('device_type') 
            ?? $request->header('Device-Type')
            ?? ($deviceInfo['osVersion'] ?? 'android');

        $deviceToken = $request->header('X-Device-Id')
            ?? $request->header('device-token') 
            ?? $request->header('device_token') 
            ?? $request->header('Device-Token')
            ?? ($deviceInfo['deviceId'] ?? ($deviceInfo['fcmToken'] ?? null));

        $encryptedUid    = encryptData($identifierInput);
        $encryptedMobile = encryptData($passwordInput);

        $learner = Learner::withoutGlobalScopes()
            ->where(function ($q) use ($passwordInput, $encryptedMobile) {
                $q->where('mobile', $encryptedMobile)
                  ->orWhere('mobile', $passwordInput);
            })
            ->where(function ($q) use ($identifierInput, $encryptedUid) {
                $q->where('learner_no', $identifierInput)
                  ->orWhere('email', $encryptedUid)
                  ->orWhere('email', $identifierInput);
            })
            ->first();

        // Fallback for bcrypt hashed password
        if (!$learner) {
            $candidate = Learner::withoutGlobalScopes()
                ->where(function ($q) use ($identifierInput, $encryptedUid) {
                    $q->where('learner_no', $identifierInput)
                      ->orWhere('email', $encryptedUid)
                      ->orWhere('email', $identifierInput);
                })
                ->first();

            if ($candidate && $candidate->password && Hash::check($passwordInput, $candidate->password)) {
                $learner = $candidate;
            }
        }

        if (!$learner) {
            return response()->json([
                'status'  => false,
                'message' => 'Sorry, we couldn’t find your record. Please verify your details and try again.'
            ], 200);
        }

        if ((int) $learner->status !== 1) {
            return response()->json([
                'status'  => false,
                'message' => 'Your seat is not active. Please contact your library.',
            ], 200);
        }

        // Single device login enforcement: revoke all previous tokens for this learner
        $learner->tokens()->delete();

        // Record or update device token mapping if device token provided
        if ($deviceToken) {
            \DB::table('device_tokens')->updateOrInsert(
                [
                    'user_id'   => $learner->id,
                    'user_type' => get_class($learner),
                    'guard_name'=> 'learner_api',
                ],
                [
                    'device_id'   => $deviceToken,
                    'device_type' => $deviceType,
                    'updated_at'  => now(),
                    'created_at'  => now(),
                ]
            );
        }

        $token = $learner->createToken('learner_token')->plainTextToken;

        $branch = \App\Models\Branch::where('id', $learner->branch_id)->select('id', 'name', 'library_address as address')->first();

        $nameParts = explode(' ', trim($learner->name ?? ''));
        $firstName = $nameParts[0] ?? $learner->name;

        return response()->json([
            'status'  => true,
            'message' => 'Login successful.',
            'token'   => $token,
            'data'    => [
                'accessToken' => $token,
                'tokenType'   => 'Bearer',
                'student'     => [
                    'id'              => (string) $learner->id,
                    'uid'             => $learner->learner_no ?? '',
                    'fullName'        => strtoupper($learner->name ?? ''),
                    'email'           => $learner->email ?? '',
                    'phone'           => $learner->mobile ?? '',
                    
                    'profileImageUrl' => $learner->profile_picture ? asset($learner->profile_picture) : null,
                    'status'          => (int) $learner->status === 1 ? 'ACTIVE' : 'INACTIVE',
                    'library'         => [
                        'id'      => (string) ($branch?->id ?? ''),
                        'name'    => $branch?->name ?? '',
                        'address' => $branch?->address ?? '',
                    ],
                ],
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
        $user = $request->user('learner_api');

        if ($user) {
            // Revoke all tokens across all devices
            $user->tokens()->delete();

            // Clear device token mapping
            \DB::table('device_tokens')
                ->where('user_id', $user->id)
                ->where('guard_name', 'learner_api')
                ->delete();
        }

        return response()->json([
            'status'  => true,
            'message' => 'Logged out from all devices successfully.',
        ], 200);
    }

    public function setting()
    {
        return response()->json([
            'status'  => true,
            'message' => 'Learner settings fetched successfully.',
            'data'    => [
                'android_version'      => (string) config('app.min_versions.android', '1.0.0'),
                'ios_version'          => (string) config('app.min_versions.ios', '1.0.0'),
                'force_update'         => (bool) config('app.force_update', false),
                'privacy_policy'       => 'https://www.libraro.in/privacy-policy',
                'terms_and_conditions' => 'https://www.libraro.in/terms-and-condition',
                'support_email'        => ['support@libraro.in'],
                'support_number'       => ['+91-8114479678'],
                'web_url'              => 'https://www.libraro.in',
                'youtube'              => 'https://www.youtube.com/@Libraroindia',
                'linkedin'             => 'https://www.linkedin.com/in/libraro/',
                'instagram'            => 'https://www.instagram.com/libraro.in/',
                'facebook'             => 'https://www.facebook.com/libraro.in',
                'whatsapp'             => 'https://wa.me/+918114479678',
                'isMaintenance'        => (bool) config('app.maintenance', env('APP_MAINTENANCE', false)),
            ]
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid'          => 'required',
            'mobile'       => 'required',
            'new_password' => 'required|min:6',
        ], [
            'uid.required'          => 'Username (UID or Email) is required.',
            'mobile.required'       => 'Current mobile number is required.',
            'new_password.required' => 'New password / mobile number is required.',
            'new_password.min'      => 'New password must be at least 6 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $uidInput      = trim($request->uid);
        $currentMobile = trim($request->mobile ?? $request->old_mobile);
        $newPassword   = trim($request->new_password ?? $request->password ?? $request->new_mobile);

        $encryptedUid    = encryptData($uidInput);
        $encryptedMobile = encryptData($currentMobile);

        $learner = Learner::withoutGlobalScopes()
            ->where('mobile', $encryptedMobile)
            ->where(function ($q) use ($uidInput, $encryptedUid) {
                $q->where('learner_no', $uidInput)
                  ->orWhere('email', $encryptedUid)
                  ->orWhere('email', $uidInput);
            })
            ->first();

        if (!$learner) {
            return response()->json([
                'status'  => false,
                'message' => 'No learner record found matching the provided UID/Email and Mobile number.',
            ], 200);
        }

        if ((int) $learner->status !== 1) {
            return response()->json([
                'status'  => false,
                'message' => 'Your account is not active. Please contact your library.',
            ], 200);
        }

        // Update learner password & mobile
        $learner->mobile   = encryptData($newPassword);
        $learner->password = Hash::make($newPassword);
        if (Schema::hasColumn('learners', 'original_password')) {
            $learner->original_password = $newPassword;
        }
        $learner->save();

        // Revoke all existing tokens for security
        $learner->tokens()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Password reset successfully. Please login with your new credentials.',
        ], 200);
    }
}
