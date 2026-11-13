<?php

namespace App\Support;

/**
 * Which on-demand plugin bundles (config/admin_assets.php) the current admin page needs.
 *
 * The layout calls AdminAssets::for($html) in <head> with the page's already rendered
 * sections (Blade renders the child view before its layout), then prints css() in <head>
 * and js() / includes() at the end of <body>.
 */
class AdminAssets
{
    /** @var array<string, array>|null bundles picked for this request */
    private static ?array $picked = null;

    /**
     * Pick the bundles for this page's HTML. $requested is the optional
     * comma list from @section('admin_assets', 'datatables,swal').
     */
    public static function for(string $html, string $requested = ''): array
    {
        $bundles = config('admin_assets.bundles', []);
        $haystack = $html.self::linkedScripts($html);
        $wanted = array_filter(array_map('trim', explode(',', $requested)));

        foreach (config('admin_assets.routes', []) as $pattern => $names) {
            if (request()->routeIs($pattern)) {
                $wanted = array_merge($wanted, (array) $names);
            }
        }

        $picked = [];
        foreach ($bundles as $name => $bundle) {
            // the page links this plugin itself: never load it twice
            if (self::containsAny($html, $bundle['self'] ?? [])) {
                continue;
            }
            if (in_array($name, $wanted, true) || self::containsAny($haystack, $bundle['markers'] ?? [])) {
                $picked[$name] = $bundle;
            }
        }

        return self::$picked = $picked;
    }

    /** bundle names picked for this request (for debugging / tests) */
    public static function names(): array
    {
        return array_keys(self::$picked ?? []);
    }

    /** @return string[] stylesheet URLs */
    public static function css(): array
    {
        return self::urls('css');
    }

    /** @return string[] script URLs, in config order */
    public static function js(): array
    {
        return self::urls('js');
    }

    /** @return string[] Blade views to @include at the end of <body> */
    public static function includes(): array
    {
        return array_values(array_filter(array_map(fn ($b) => $b['include'] ?? null, self::$picked ?? [])));
    }

    /**
     * Speculation Rules: prerender a same-origin admin link once the pointer rests on it
     * (eagerness "moderate" ≈ 200 ms hover, or pointerdown on touch), except the links
     * listed in admin_assets.no_prefetch and links that are not plain navigations.
     */
    public static function speculationRules(): array
    {
        return [
            'prerender' => [[
                'source' => 'document',
                'where' => ['and' => [
                    ['href_matches' => '/*'],
                    ['not' => ['selector_matches' => self::noPrefetchSelector()]],
                ]],
                'eagerness' => 'moderate',
            ]],
        ];
    }

    public static function noPrefetchSelector(): string
    {
        $skip = ['[target]:not([target="_self"])', '[download]', '[data-bs-toggle]', '[data-no-prefetch]', '[onclick]', '[href^="#"]', '[href^="javascript"]'];
        foreach (config('admin_assets.no_prefetch', []) as $word) {
            $skip[] = 'a[href*="'.addcslashes($word, '"\\').'" i]';
        }

        return implode(', ', $skip);
    }

    private static function urls(string $kind): array
    {
        $out = [];
        foreach (self::$picked ?? [] as $bundle) {
            foreach ($bundle[$kind] ?? [] as $url) {
                if (str_starts_with($url, 'local:')) {
                    $path = substr($url, 6);
                    $url = asset('public/'.$path).'?v='.@filemtime(public_path($path));
                }
                $out[] = $url;
            }
        }

        return $out;
    }

    /** contents of the local public/js files the page links (their code may use a plugin too) */
    private static function linkedScripts(string $html): string
    {
        if (! preg_match_all('#public/js/([A-Za-z0-9_\-/]+\.js)#', $html, $m)) {
            return '';
        }
        $code = '';
        foreach (array_unique($m[1]) as $file) {
            $path = public_path('js/'.$file);
            if (is_file($path) && filesize($path) < 512 * 1024) {
                $code .= "\n".file_get_contents($path);
            }
        }

        return $code;
    }

    private static function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
