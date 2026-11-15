<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard for plan features of the library panel: `library.can:Student Quiz`.
 * Uses the same rule as the has-permission gate (owner = plan, staff = own permissions inside the plan).
 * Without it: JSON 403 for AJAX, otherwise back to the dashboard with a message.
 */
class LibraryPlanPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        foreach ($permissions as $permission) {
            if (libraryPlanAllows(trim($permission, " '\""))) {
                return $next($request);
            }
        }

        $message = 'This feature is part of the Pro plan. Upgrade your plan to use it.';
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => false, 'success' => false, 'message' => $message], 403);
        }

        return redirect()->route('library.home')->with('error', $message);
    }
}
