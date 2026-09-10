<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckDeviceHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $deviceInfo  = $request->input('deviceInfo', []);

        $deviceType  = $request->header('X-Platform')
            ?? $request->header('Platform')
            ?? $request->header('device-type') 
            ?? $request->header('device_type') 
            ?? $request->header('Device-Type')
            ?? ($deviceInfo['osVersion'] ?? null);

        $deviceToken = $request->header('X-Device-Id')
            ?? $request->header('device-token') 
            ?? $request->header('device_token') 
            ?? $request->header('Device-Token')
            ?? ($deviceInfo['deviceId'] ?? ($deviceInfo['fcmToken'] ?? null));

        $normalizedPlatform = strtolower(trim((string) $deviceType));
        if (!$deviceType || !$deviceToken) {
            return response()->json([
                'status'     => false,
                'state_code' => 'API_SECURITY_FAILED',
                'error_code' => 'API_SECURITY_FAILED',
                'message'    => 'API request security validation failed: X-Platform and X-Device-Id (or device-token) headers are required.',
                'code'       => 403,
                'data'       => (object)[]
            ], 403);
        }

        if (!in_array($normalizedPlatform, ['android', 'ios'], true)) {
            return response()->json([
                'status'     => false,
                'state_code' => 'API_SECURITY_FAILED',
                'error_code' => 'API_SECURITY_FAILED',
                'message'    => "API request security validation failed: Invalid X-Platform. Supported platforms are 'android' and 'ios'.",
                'code'       => 403,
                'data'       => (object)[]
            ], 403);
        }

        return $next($request);
    }
}
