<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages behind a login (library, staff, learner, admin portals):
 *  - never indexed, archived or machine-translated (X-Robots-Tag);
 *  - an account that opens pages faster than a person can (a bot or an AI agent copying the portal)
 *    is logged and slowed down. Only full page loads count — AJAX, JSON, files and form posts do not.
 */
class ProtectPortalPages
{
    /** Full page loads one account may open in a minute (a person rarely goes past 15). */
    public const PER_MINUTE = 40;

    /** Page loads in an hour after which the account is logged for review (not blocked). */
    public const PER_HOUR_ALERT = 500;

    public function handle(Request $request, Closure $next): Response
    {
        $viewer = portalViewer();

        // the limit must never break a page: if the cache store fails, the page just opens
        $blocked = $viewer && $this->isPageLoad($request) ? rescue(fn () => $this->limit($request, $viewer), null, false) : null;
        if ($blocked) {
            return $blocked;
        }

        $response = $next($request);

        // signed-in pages: never in search results, caches or translators
        if ($viewer || portalViewer()) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet, notranslate');
        }

        return $response;
    }

    /** Counts the page load; returns the "please wait" response when the account is over the limit. */
    private function limit(Request $request, array $viewer): ?Response
    {
        $who = $viewer[0] . ':' . $viewer[1];

        RateLimiter::hit('portal-pages-hour:' . $who, 3600);
        if (RateLimiter::attempts('portal-pages-hour:' . $who) > self::PER_HOUR_ALERT
            && Cache::add('portal-pages-hour-logged:' . $who, 1, 3600)) {
            $this->alert('Portal: many pages opened in an hour (possible copying / scraping)', $request, $viewer);
        }

        if (RateLimiter::tooManyAttempts('portal-pages:' . $who, self::PER_MINUTE)) {
            if (Cache::add('portal-pages-logged:' . $who, 1, 600)) {
                $this->alert('Portal: pages opened faster than a person can (possible bot / AI agent)', $request, $viewer);
            }
            $wait = RateLimiter::availableIn('portal-pages:' . $who);

            return response(
                '<!doctype html><meta charset="utf-8"><meta name="robots" content="noindex"><title>Please wait</title>'
                . '<div style="font:16px/1.5 system-ui,sans-serif;max-width:460px;margin:15vh auto;padding:0 20px;text-align:center">'
                . '<h1 style="font-size:22px">Too many pages opened too quickly</h1>'
                . '<p>Please wait ' . max(1, $wait) . ' seconds and try again.</p></div>',
                429
            )->header('Retry-After', (string) max(1, $wait))
             ->header('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
        }
        RateLimiter::hit('portal-pages:' . $who, 60);

        return null;
    }

    /** A browser opening a page (not AJAX, JSON, a file or a form post). */
    private function isPageLoad(Request $request): bool
    {
        if (!$request->isMethod('GET') || $request->ajax() || $request->expectsJson() || $request->prefetch()) {
            return false;
        }

        return str_contains((string) $request->header('Accept'), 'text/html');
    }

    /** Logs for review; a log that can't be written must not stop the limit. */
    private function alert(string $message, Request $request, array $viewer): void
    {
        rescue(fn () => Log::warning($message, $this->context($request, $viewer)), null, false);
    }

    private function context(Request $request, array $viewer): array
    {
        return [
            'guard' => $viewer[0],
            'id' => $viewer[1],
            'library_id' => rescue(fn () => getLibraryId(), null, false),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 200),
        ];
    }
}
