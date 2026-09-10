<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAppVersion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('api/v1/learner/app-settings', 'api/v1/library/app-settings', '*/app-settings')) {
            return $next($request);
        }

        $version = $request->header('X-App-Version') ?? $request->header('App-Version');
        $platform = strtolower(trim((string) ($request->header('X-Platform') ?? $request->header('Platform') ?? '')));

        $forceUpdate = (bool) config('app.force_update', false);
        if ($forceUpdate && !empty($version)) {
            $minVersion = config("app.min_versions.{$platform}", '1.0.1');
            if ($minVersion && version_compare($version, $minVersion, '<')) {
                return response()->json([
                    'status'       => false,
                    'force_update' => true,
                    'state_code'   => 'APP_UPDATE_REQUIRED',
                    'error_code'   => 'APP_UPDATE_REQUIRED',
                    'message'      => 'Please update your app to the latest version.',
                    'code'         => 426,
                    'data'         => [
                        'current_version' => $version,
                        'min_version'     => $minVersion,
                        'platform'        => $platform,
                    ]
                ], 426);
            }
        }

        return $next($request);
    }
}
