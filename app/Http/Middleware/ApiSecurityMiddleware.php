<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ApiSecurityMiddleware
{
    /**
     * Handle an incoming request.
     * Validates global security headers (X-Timestamp, X-Nonce, X-Signature, X-Platform, X-App-Version, X-Device-Id)
     * and prevents replay attacks via HMAC-SHA256 signature verification & Nonce caching.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey     = $request->header('X-API-KEY') 
            ?? $request->header('x-api-key') 
            ?? $request->header('X-Api-Key') 
            ?? $request->header('api-key')
            ?? $request->header('api_key');
        $timestamp  = $request->header('X-Timestamp');
        $nonce      = $request->header('X-Nonce');
        $signature  = $request->header('X-Signature');
        $appVersion = $request->header('X-App-Version') ?? $request->header('App-Version');
        $platform   = strtolower(trim((string) ($request->header('X-Platform') ?? $request->header('Platform') ?? '')));
        $deviceId   = $request->header('X-Device-Id') ?? $request->header('device-token') ?? $request->header('device_token');

        // 1. API Key Validation
        $expectedKey = config('app.api_key');
        if (!$apiKey || (!empty($expectedKey) && trim($apiKey) !== trim($expectedKey))) {
            return response()->json([
                'status'     => false,
                'state_code' => 'API_SECURITY_FAILED',
                'error_code' => 'API_SECURITY_FAILED',
                'message'    => 'API request security validation failed: Invalid or missing X-API-KEY header.',
                'code'       => 403,
            ], 403);
        }

        // 2. Strict Platform Validation (Only 'android' and 'ios' allowed)
        if (!$platform || !in_array($platform, ['android', 'ios'], true)) {
            return response()->json([
                'status'     => false,
                'state_code' => 'API_SECURITY_FAILED',
                'error_code' => 'API_SECURITY_FAILED',
                'message'    => "API request security validation failed: Invalid or missing X-Platform header. Supported platforms are 'android' and 'ios'.",
                'code'       => 403,
            ], 403);
        }

        // 3. Force Update & App Version Validation (Exempt app-settings routes so client can fetch initial settings)
        $isSettingsRoute = $request->is('api/v1/learner/app-settings', 'api/v1/library/app-settings', '*/app-settings');
        $forceUpdateEnabled = (bool) config('app.force_update', false);
        if ($forceUpdateEnabled && !$isSettingsRoute) {
            if (!$appVersion) {
                return response()->json([
                    'status'     => false,
                    'state_code' => 'API_SECURITY_FAILED',
                    'error_code' => 'API_SECURITY_FAILED',
                    'message'    => 'API request security validation failed: X-App-Version header is required when force update is enabled.',
                    'code'       => 403,
                ], 403);
            }

            $minVersion = config("app.min_versions.{$platform}", '1.0.0');
            if (version_compare($appVersion, $minVersion, '<')) {
                return response()->json([
                    'status'       => false,
                    'force_update' => true,
                    'state_code'   => 'APP_UPDATE_REQUIRED',
                    'error_code'   => 'APP_UPDATE_REQUIRED',
                    'message'      => 'Application update required. Please update your app to continue.',
                    'code'         => 426,
                    'data'         => [
                        'current_version' => $appVersion,
                        'min_version'     => $minVersion,
                        'platform'        => $platform,
                    ],
                ], 426);
            }
        }

        // 4. Missing Required Security Headers Check (X-Timestamp, X-Nonce, X-Signature, X-Device-Id)
        if (!$timestamp || !$nonce || !$signature || !$deviceId) {
            return response()->json([
                'status'     => false,
                'state_code' => 'API_SECURITY_FAILED',
                'error_code' => 'API_SECURITY_FAILED',
                'message'    => 'API request security validation failed: Missing required security headers (X-Timestamp, X-Nonce, X-Signature, X-Device-Id).',
                'code'       => 403,
            ], 403);
        }

        // 3. Timestamp Window Check (Prevent Old / Future Replayed Requests — max 5 minutes / 300,000 ms)
        $currentTimeMs = (int) (microtime(true) * 1000);
        $requestTimeMs = (int) $timestamp;

        if (abs($currentTimeMs - $requestTimeMs) > 300000) {
            return response()->json([
                'status'     => false,
                'state_code' => 'API_SECURITY_FAILED',
                'error_code' => 'API_SECURITY_FAILED',
                'message'    => 'API request security validation failed: Request timestamp is outside the acceptable 5-minute security window.',
                'code'       => 403,
            ], 403);
        }

        // 4. Replay Attack Check (Nonce uniqueness within 5 minutes)
        $cacheKey = "api_nonce:{$nonce}";
        if (Cache::has($cacheKey)) {
            return response()->json([
                'status'     => false,
                'state_code' => 'API_SECURITY_FAILED',
                'error_code' => 'API_SECURITY_FAILED',
                'message'    => 'API request security validation failed: Duplicate request detected (Nonce replay attack).',
                'code'       => 403,
            ], 403);
        }

        // 5. HMAC-SHA256 Signature Verification
        $secret = config('app.hmac_secret', config('app.api_key', 'libraro_secret_key_2026'));
        $httpMethod = strtoupper($request->method());
        $path = $request->getRequestUri();
        $body = $request->getContent();

        // Data payload string: METHOD|PATH|TIMESTAMP|NONCE|BODY
        $signaturePayload = "{$httpMethod}|{$path}|{$timestamp}|{$nonce}|{$body}";
        $expectedSignature = hash_hmac('sha256', $signaturePayload, $secret);

        if (!hash_equals($expectedSignature, strtolower($signature))) {
            return response()->json([
                'status'     => false,
                'state_code' => 'API_SECURITY_FAILED',
                'error_code' => 'API_SECURITY_FAILED',
                'message'    => 'API request security validation failed: HMAC signature verification failed. Request may have been tampered with.',
                'code'       => 403,
            ], 403);
        }

        // Lock nonce for 5 minutes
        Cache::put($cacheKey, true, 300);

        return $next($request);
    }
}
