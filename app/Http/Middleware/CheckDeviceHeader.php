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

        $deviceType  = $request->header('device-type') 
            ?? $request->header('device_type') 
            ?? $request->header('Device-Type')
            ?? ($deviceInfo['osVersion'] ?? $request->header('X-Platform'));

        $deviceToken = $request->header('device-token') 
            ?? $request->header('device_token') 
            ?? $request->header('Device-Token')
            ?? ($deviceInfo['deviceId'] ?? ($deviceInfo['fcmToken'] ?? $request->header('X-Device-Id')));

        if (!$deviceType || !$deviceToken) {
            return response()->json([
                'status'  => false,
                'message' => 'device-type and device-token headers are required in header',
                'data'    => (object)[]
            ], 400);
        }

        return $next($request);
    }
}
