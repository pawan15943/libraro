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
        $staff = auth('library_user_api')->user();
        if ($staff instanceof LibraryUser) {
            $parentLibrary = \App\Models\Library::find($staff->library_id);
            if (!$staff->status || $staff->deleted_at !== null || !$parentLibrary || $parentLibrary->deleted_at !== null || !$parentLibrary->status) {
                $staff->currentAccessToken()?->delete();

                return response()->json([
                    'status'  => false,
                    'message' => 'Account deactivated or deleted.',
                ], 401);
            }
        }

        $owner = auth('library_api')->user();
        if ($owner instanceof \App\Models\Library) {
            if (!$owner->status || $owner->deleted_at !== null) {
                $owner->currentAccessToken()?->delete();

                return response()->json([
                    'status'  => false,
                    'message' => 'Account deactivated or deleted.',
                ], 401);
            }
        }

        return $next($request);
    }
}
