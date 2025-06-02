<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Log; 

class LoggerMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            if (!in_array($request->method(), ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'])) {
                return response()->json(['error' => 'Method not allowed'], 405);
            }
            $user = Auth::user();
            $response = $next($request);

            if ($user) {
                $this->logAction($request, $response, $user);
            }

            $this->logSuccess($request, $response);

            return $response;
        } catch (\Exception $ex) {
            $this->logFailure($request, $ex);
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred.',
                    'error' => $ex->getMessage()
                ], 500);
            }
            throw $ex;
        }
    }

    protected function logAction($request, $response, $user)
    {
        Log::info('logAction method triggered'); // Debugging line

        try {
            DB::table('library_activity_log')->insert([
                'library_id' => $user->id,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'status_code' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 1, // Use getStatusCode()
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'action' => 'edit', // Adjust action as necessary
                'request_body' => json_encode($request->all()),
            ]);

            Log::info('Action successfully logged');
        } catch (\Exception $e) {
            Log::error('Failed to log action: ' . $e->getMessage());
        }
    }

    protected function logSuccess($request, $response)
    {
        Log::info('Action successful from middleware', [
            'user_id' => Auth::id(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status_code' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 1, // Use getStatusCode()
        ]);
    }

    protected function logFailure($request, $exception)
    {
        Log::error('Action failed from middleware', [
            'user_id' => Auth::id(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'error_message' => $exception->getMessage(),
        ]);
    }
}

