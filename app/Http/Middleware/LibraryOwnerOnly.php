<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LibraryOwnerOnly
{
    /**
     * Blocks the library_user (staff) guard from routes reserved for the
     * library owner, regardless of any staff permission grant.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isStaff = auth('library_user')->check() || auth('library_user_api')->check();

        if ($isStaff) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to perform this action.',
                ], 403);
            }

            return redirect()->back()->with('error', 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
