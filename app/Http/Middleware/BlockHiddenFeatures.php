<?php

namespace App\Http\Middleware;

use App\Support\FeatureToggles;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A page the library owner switched off on Show / Hide Options (App\Support\FeatureToggles) can't be
 * opened by typing its URL: pages go back to the dashboard with a note, other requests get a 403.
 * Only the route names listed in the registry are checked, so nothing else is affected.
 */
class BlockHiddenFeatures
{
    public function handle(Request $request, Closure $next): Response
    {
        $name = optional($request->route())->getName();
        if (! $name || ! FeatureToggles::routeHidden($name)) {
            return $next($request);
        }

        $message = 'This option is turned off for this branch (Settings → Show / Hide Options).';
        $isLearner = auth()->guard('learner')->check() && ! auth()->guard('library')->check() && ! auth()->guard('library_user')->check();
        if ($isLearner) {
            $message = 'This page is not available at your library.';
        }

        if ($request->expectsJson() || ! $request->isMethod('GET')) {
            return response()->json(['status' => false, 'message' => $message], 403);
        }

        $home = $isLearner ? 'learner.home' : 'library.home';

        return redirect()->route($home)->with('error', $message);
    }
}
