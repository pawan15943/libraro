<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gives every web portal (admin, library, learner) its own session cookie.
 *
 * Runs first in the "web" group, before the session starts. A route joins a
 * portal with the "portal:<name>" middleware; "portal:auto" is for the few
 * shared endpoints (login, logout, CSRF refresh) and reads the portal from the
 * request. Routes without a tag use the default portal (library).
 *
 * Remember-me cookies of guards from other portals are dropped from the
 * request, so a guard can only ever sign in inside its own portal.
 */
class UsePortalSession
{
    public const ATTRIBUTE = 'portal';

    /** user_type sent by the login forms → portal */
    private const LOGIN_TYPES = [
        'superadmin' => 'admin',
        'admin' => 'library',
        'learner' => 'learner',
    ];

    public function handle(Request $request, Closure $next, ?string $portal = null): Response
    {
        // The "portal:x" alias on a route runs this class a second time; the
        // first run (in the web group) already did the work.
        if ($request->attributes->has(self::ATTRIBUTE)) {
            return $next($request);
        }

        $portal = $this->resolve($request);
        $request->attributes->set(self::ATTRIBUTE, $portal);

        $cookie = self::cookieName($portal);
        config(['session.cookie' => $cookie]);
        // The store may already exist (guards are built by global middleware),
        // so rename it rather than building a new one.
        app('session')->driver()->setName($cookie);

        $this->dropForeignRememberCookies($request, $portal);

        return $next($request);
    }

    public static function portals(): array
    {
        return config('session.portals', []);
    }

    public static function defaultPortal(): string
    {
        return config('session.default_portal', 'library');
    }

    public static function exists(?string $portal): bool
    {
        return $portal !== null && array_key_exists($portal, self::portals());
    }

    public static function cookieName(string $portal): string
    {
        $base = self::portals()[self::defaultPortal()]['cookie'] ?? 'laravel_session';
        $name = self::portals()[$portal]['cookie'] ?? null;

        // Never let two portals share a cookie, even if the .env says so.
        if ($portal !== self::defaultPortal() && (! $name || $name === $base)) {
            return $base.'_'.$portal;
        }

        return $name ?: $base;
    }

    public static function guards(string $portal): array
    {
        return self::portals()[$portal]['guards'] ?? [];
    }

    public static function portalOfGuard(string $guard): ?string
    {
        foreach (self::portals() as $name => $portal) {
            if (in_array($guard, $portal['guards'] ?? [], true)) {
                return $name;
            }
        }

        return null;
    }

    private function resolve(Request $request): string
    {
        $tag = self::tagOf($request->route());

        if ($tag === 'auto') {
            $tag = $this->fromRequest($request);
        }

        return self::exists($tag) ? $tag : self::defaultPortal();
    }

    /** The "portal:<name>" tag of a route, if any. */
    public static function tagOf($route): ?string
    {
        if (! $route instanceof Route) {
            return null;
        }

        $tag = null;
        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'portal:')) {
                $tag = substr($middleware, 7); // the innermost tag wins
            }
        }

        return $tag;
    }

    /** Shared endpoints: explicit ?portal=, then the login user_type, then the page that sent the request. */
    private function fromRequest(Request $request): ?string
    {
        $asked = $request->query('portal', $request->input('portal'));
        if (is_string($asked) && self::exists($asked)) {
            return $asked;
        }

        $type = $request->input('user_type');
        if (is_string($type) && isset(self::LOGIN_TYPES[$type])) {
            return self::LOGIN_TYPES[$type];
        }

        return $this->fromReferer($request);
    }

    private function fromReferer(Request $request): ?string
    {
        $referer = (string) $request->headers->get('referer', '');
        if ($referer === '') {
            return null;
        }

        $root = rtrim($request->root(), '/');
        if (! str_starts_with($referer, $root.'/') && $referer !== $root) {
            return null; // another site
        }

        $path = '/'.ltrim((string) parse_url(substr($referer, strlen($root)) ?: '/', PHP_URL_PATH), '/');

        try {
            $route = app('router')->getRoutes()->match(Request::create($path, 'GET'));
        } catch (\Throwable $e) {
            return null;
        }

        $tag = self::tagOf($route);

        return $tag === 'auto' ? null : $tag;
    }

    private function dropForeignRememberCookies(Request $request, string $portal): void
    {
        $own = self::guards($portal);

        foreach (self::portals() as $name => $config) {
            foreach ($config['guards'] ?? [] as $guard) {
                if (! in_array($guard, $own, true)) {
                    $request->cookies->remove('remember_'.$guard.'_'.sha1(SessionGuard::class));
                }
            }
        }
    }
}
