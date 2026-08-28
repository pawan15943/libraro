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
        $timestamp  = $request->header('X-Timestamp');
        $nonce      = $request->header('X-Nonce');
        $signature  = $request->header('X-Signature');
        $appVersion = $request->header('X-App-Version');
        $platform   = $request->header('X-Platform');
        $deviceId   = $request->header('X-Device-Id') ?? $request->header('device-token') ?? $request->header('device_token');

        // 1. Missing Required Headers Check
        if (!$timestamp || !$nonce || !$signature || !$appVersion || !$platform || !$deviceId) {
            return response()->json([
                'status'  => false,
                'message' => 'Missing required security headers (X-Timestamp, X-Nonce, X-Signature, X-App-Version, X-Platform, X-Device-Id).',
            ], 400);
        }

        // 2. Timestamp Window Check (Prevent Old Replayed Requests — max 5 minutes / 300,000 ms)
        $currentTimeMs = (int) (microtime(true) * 1000);
        $requestTimeMs = (int) $timestamp;

        if (abs($currentTimeMs - $requestTimeMs) > 300000) {
            return response()->json([
                'status'  => false,
                'message' => 'Request timestamp is outside the acceptable 5-minute security window.',
            ], 401);
        }

        // 3. Replay Attack Check (Nonce uniqueness within 5 minutes)
        $cacheKey = "api_nonce:{$nonce}";
        if (Cache::has($cacheKey)) {
            return response()->json([
                'status'  => false,
                'message' => 'Duplicate request detected (Nonce replay attack).',
            ], 401);
        }

        // 4. HMAC-SHA256 Signature Verification
        $secret = config('app.hmac_secret', config('app.api_key', 'libraro_secret_key_2026'));
        $httpMethod = strtoupper($request->method());
        $path = $request->getRequestUri();
        $body = $request->getContent();

        // Data payload string: METHOD|PATH|TIMESTAMP|NONCE|BODY
        $signaturePayload = "{$httpMethod}|{$path}|{$timestamp}|{$nonce}|{$body}";
        $expectedSignature = hash_hmac('sha256', $signaturePayload, $secret);

        if (!hash_equals($expectedSignature, strtolower($signature))) {
            return response()->json([
                'status'  => false,
                'message' => 'HMAC signature verification failed. Request may have been tampered with.',
            ], 401);
        }

        // Lock nonce for 5 minutes
        Cache::put($cacheKey, true, 300);

        return $next($request);
    }
}
