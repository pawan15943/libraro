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
        $deviceType = $request->header('device-type');
        $deviceId   = $request->header('device-id');

        if (!$deviceType || !$deviceId) {
            return response()->json([
                'status'  => false,
                'message' => 'Device type and device id are required in header',
                'data'    => (object)[]
            ], 400);
        }

        return $next($request);
    }
}
