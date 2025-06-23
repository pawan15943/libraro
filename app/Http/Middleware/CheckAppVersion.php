<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAppVersion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
   public function handle(Request $request, Closure $next)
    {
        $version = $request->header('App-Version');
        $platform = $request->header('Platform'); // e.g., ios or android

        $min = [
            'android' => '1.0.5',
            'ios' => '2.0.1',
        ];

        if (isset($min[$platform]) && version_compare($version, $min[$platform], '<')) {
            return response()->json([
                'force_update' => true,
                'message' => 'Please update your app.'
            ], 426);
        }

        return $next($request);
    }
}
