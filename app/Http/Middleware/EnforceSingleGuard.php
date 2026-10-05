<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnforceSingleGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
   public function handle($request, Closure $next, string $currentGuard)
    {
        // 🚫 Skip enforcement on auth routes to prevent loop
        if ($request->routeIs(
            'login.*',
            'verification.*',
            'password.*'
        )) {
            return $next($request);
        }

        logoutOtherGuards($currentGuard);

        session(['active_guard' => $currentGuard]);

        return $next($request);
    }
}
