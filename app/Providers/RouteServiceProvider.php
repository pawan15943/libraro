<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // library_api requests authenticate on the "library_api" guard, not the
        // default "web" guard, so $request->user() is always null for them and
        // throttle:60,1 falls back to keying by route+IP — meaning every library
        // account behind the same public/NAT IP shared one 60-req/min bucket,
        // causing frequent "Too Many Attempts" on high-traffic endpoints like
        // the dashboard and learner list. Key by the actual authenticated user.
        RateLimiter::for('library_api', function (Request $request) {
            return Limit::perMinute(120)->by(
                $request->user('library_api')?->id ?: $request->ip()
            );
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
