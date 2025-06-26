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
        if (Auth::guard('library')->check()) {
            Auth::shouldUse('library');
            
        } elseif (Auth::guard('library_user')->check()) {
            Auth::shouldUse('library_user');
        } else {
            // If the request expects JSON (e.g. from AJAX)
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            // Redirect to appropriate login route
            return $this->redirectTo($request);
        }

        return $next($request);
    }

    protected function redirectTo(Request $request)
    {
        if ($request->is('administrator/*')) {
            return redirect()->route('login.administrator');
        } elseif ($request->is('library/*')) {

            // return redirect()->route('login.library');
            return redirect()->route('login.library')->with('info', 'Your session has expired. Please login again.');

        } elseif ($request->is('learner/*')) {
            return redirect()->route('login.learner');
        }

        return redirect()->route('login.learner');
    }
}
