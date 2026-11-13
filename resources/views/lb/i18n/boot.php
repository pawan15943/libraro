<?php
/*
 * Website language bootstrap (English default, Hindi, Hinglish). Frontend only, no backend change.
 *
 * Top of a website page:   @php extract(require resource_path('views/lb/i18n/boot.php')); @endphp
 * Gives the page: $t('English text', [':name' => 'x']), $lbLang, $lbHtmlLang, $lbLangUrls, $lbSeoUrls,
 * $lbLangNames, $lbLangLabel, $lbJsDict — and shares them with every view rendered afterwards
 * (layout, header, footer, pagination). lb.layouts.site boots it itself if a page didn't.
 *
 * Language choice: ?lang=en|hi|hinglish in the URL, otherwise the lb_lang cookie (set by the layout
 * when a visitor picks a language), otherwise English. Translations: dict.php (page text) and js.php
 * (text built by the website scripts), as 'English text' => ['Hindi', 'Hinglish']. Anything missing
 * simply shows in English.
 */
return (function () {
    $langs = ['en', 'hi', 'hinglish'];
    $q = request()->query('lang');
    if (is_string($q) && in_array($q, $langs, true)) {
        $lang = $q;
    } else {
        $c = $_COOKIE['lb_lang'] ?? 'en';
        $lang = in_array($c, $langs, true) ? $c : 'en';
    }

    $dict = [];
    $jsDict = [];
    if ($lang !== 'en') {
        $col = $lang === 'hi' ? 0 : 1;
        foreach ((include __DIR__ . '/js.php') as $en => $tr) { if (!empty($tr[$col])) $jsDict[$en] = $tr[$col]; }
        foreach ((include __DIR__ . '/dict.php') as $en => $tr) { if (!empty($tr[$col])) $dict[$en] = $tr[$col]; }
        $dict += $jsDict;
    }
    $t = function ($s, array $r = []) use ($dict) {
        $out = $dict[$s] ?? $s;
        return $r ? strtr($out, $r) : $out;
    };

    $base = url()->current();
    $qs = request()->except('lang');
    $mk = function ($code, $explicitEn = true) use ($base, $qs) {
        $p = $qs;
        if ($code !== 'en' || $explicitEn) $p['lang'] = $code;
        $s = http_build_query($p);
        return $base . ($s !== '' ? '?' . $s : '');
    };

    $out = [
        't' => $t,
        'lbLang' => $lang,
        'lbHtmlLang' => ['en' => 'en-IN', 'hi' => 'hi-IN', 'hinglish' => 'hi-Latn-IN'][$lang],
        // Switcher links always carry ?lang (so "English" also overrides a remembered Hindi choice).
        'lbLangUrls' => ['en' => $mk('en'), 'hi' => $mk('hi'), 'hinglish' => $mk('hinglish')],
        // hreflang / canonical: English is the clean URL.
        'lbSeoUrls' => ['en' => $mk('en', false), 'hi' => $mk('hi'), 'hinglish' => $mk('hinglish')],
        'lbLangNames' => ['en' => 'English', 'hi' => 'हिंदी', 'hinglish' => 'Hinglish'],
        'lbLangLabel' => $t('Language'),
        'lbJsDict' => $jsDict,
    ];
    view()->share($out);
    return $out;
})();
