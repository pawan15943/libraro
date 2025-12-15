<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AuthenticateLibraryOrUser
{
   
   public function handle($request, Closure $next)
    {
        $activeGuard = null;

        // 1️⃣ Detect active guard (priority order)
        if (Auth::guard('library')->check()) {
            $activeGuard = 'library';
        } elseif (Auth::guard('library_user')->check()) {
            $activeGuard = 'library_user';
        }

        // 2️⃣ If no guard authenticated → unauthenticated
        if (!$activeGuard) {

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return $this->redirectTo($request);
        }

        // 3️⃣ 🔥 ENFORCE SINGLE GUARD (CORE FIX)
        foreach (array_keys(config('auth.guards')) as $guard) {
            if ($guard !== $activeGuard && Auth::guard($guard)->check()) {
                Auth::guard($guard)->logout();
            }
        }

        // 4️⃣ Tell Laravel which guard to use
        Auth::shouldUse($activeGuard);

        // Optional (debug / audit)
        session(['active_guard' => $activeGuard]);

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
