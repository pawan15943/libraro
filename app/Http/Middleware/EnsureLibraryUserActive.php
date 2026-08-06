<?php

namespace App\Http\Middleware;

use App\Models\LibraryUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLibraryUserActive
{
    /**
     * A library_user deactivated by the library owner mid-session must stop
     * being able to call the API immediately, not just fail at their next login.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('library_user_api')->user();

        if ($user instanceof LibraryUser && ! $user->status) {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return $next($request);
    }
}
