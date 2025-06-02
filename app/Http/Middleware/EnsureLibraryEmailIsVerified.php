<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;


class EnsureLibraryEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
         if (Auth::guard('library')->check()) {
            $user = Auth::guard('library')->user();

            if (! $user || ! $user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }
        }

        return $next($request);
    }
}
