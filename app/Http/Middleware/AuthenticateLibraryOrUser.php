<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
class AuthenticateLibraryOrUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        // if (Auth::guard('library')->check() || Auth::guard('library_user')->check()) {
        //     return $next($request);
        // }

        if (Auth::guard('library')->check()) {
        Auth::shouldUse('library'); // 👈 sets default guard for Gate and Auth::user()
    } elseif (Auth::guard('library_user')->check()) {
        Auth::shouldUse('library_user'); // 👈 sets default guard
    } else {
        abort(403, 'Unauthorized');
    }

    return $next($request);

        return redirect()->route('login')->with('error', 'Please login to access this page.');
    }
}
