<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-KEY') 
            ?? $request->header('x-api-key') 
            ?? $request->header('X-Api-Key') 
            ?? $request->header('api-key')
            ?? $request->header('api_key');

        $expectedKey = config('app.api_key');

        if (!$apiKey || empty($expectedKey) || trim($apiKey) !== trim($expectedKey)) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized access (Invalid or missing X-API-KEY)',
                'code'    => 401
            ], 401);
        }

        return $next($request);
    }
}
