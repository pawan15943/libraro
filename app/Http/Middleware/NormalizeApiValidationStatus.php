<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NormalizeApiValidationStatus
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! $request->is('api/v1/*') || ! $response instanceof JsonResponse || $response->getStatusCode() !== 422) {
            return $response;
        }

        $data = $response->getData(true);
        $data['status'] = false;

        if (empty($data['message'])) {
            $data['message'] = $this->firstErrorMessage($data['errors'] ?? null) ?? 'Validation failed';
        }

        return response()->json($data, 200);
    }

    private function firstErrorMessage($errors): ?string
    {
        if (! is_array($errors)) {
            return null;
        }

        foreach ($errors as $error) {
            if (is_array($error)) {
                return $error[0] ?? null;
            }

            if (is_string($error)) {
                return $error;
            }
        }

        return null;
    }
}
