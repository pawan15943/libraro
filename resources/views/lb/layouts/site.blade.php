@php
    /* Website language (English / Hindi / Hinglish). Pages normally boot it themselves; this covers any that don't. */
    if (!isset($t) || !($t instanceof \Closure) || !isset($lbSeoUrls)) { extract(require resource_path('views/lb/i18n/boot.php')); }
@endphp
<!DOCTYPE html>
<html lang="{{ $lbHtmlLang ?? 'en-IN' }}" data-csrf-refresh="{{ route('csrf.refresh', ['portal' => currentPortal()]) }}">
<head>
    @php
        $current_route = Route::currentRouteName();
        $page = App\Models\Page::where('route', $current_route)->first();
    @endphp
    @include('lb.partials.head-common')

    {{-- SEO: identical logic to sitelayouts/layout.blade.php (DB-driven per route, or the page's own @section('seo')) --}}
    @yield('seo')
    @if(!View::hasSection('seo'))
        <title>{{ $meta_title ?? ($page->meta_title ?? 'Libraro - Library Management System') }}</title>
        <meta name="description" content="{{ $meta_description ?? ($page->meta_description ?? '') }}">
        <meta name="keywords" content="{{ $meta_keywords ?? ($page->meta_keyword ?? '') }}">
        <link rel="canonical" href="{{ $lbLang !== 'en' ? $lbSeoUrls[$lbLang] : ($canonical_url ?? ($page->canonical ?? url()->current())) }}" />
        {!! !empty($page->meta_og) ? $page->meta_og : '' !!}
        @php
            $schema = !empty($page->page_schema) ? html_entity_decode($page->page_schema) : '';
        @endphp
        {!! $schema !!}
    @endif
    @unless(!empty($lbNoHreflang))
        <link rel="alternate" hreflang="en-IN" href="{{ $lbSeoUrls['en'] }}">
        <link rel="alternate" hreflang="hi-IN" href="{{ $lbSeoUrls['hi'] }}">
        <link rel="alternate" hreflang="hi-Latn-IN" href="{{ $lbSeoUrls['hinglish'] }}">
        <link rel="alternate" hreflang="x-default" href="{{ $lbSeoUrls['en'] }}">
    @endunless
    <script>
        /* Remember the visitor's language choice (lb_lang cookie) so every website page opens in it. */
        (function () { try { var m = /[?&]lang=(en|hi|hinglish)(?:&|$)/.exec(location.search); if (m) document.cookie = 'lb_lang=' + m[1] + ';path=/;max-age=31536000;SameSite=Lax'; } catch (e) {} })();
        window.LB_LANG = @json($lbLang);
        window.LB_I18N = @json((object) ($lbJsDict ?? []));
        window.LB_T = function (s, r) { var o = (window.LB_I18N && window.LB_I18N[s]) || s; if (r) { for (var k in r) { o = o.split(k).join(r[k]); } } return o; };
    </script>

    <link rel="stylesheet" href="{{ asset('public/css/lb-site.css') }}?v={{ @filemtime(public_path('css/lb-site.css')) }}">
    @stack('head')
</head>
<body class="lb lb-site @yield('body_class')">
    <a href="#lb-main" class="lb-skip">{{ $t('Skip to content') }}</a>
    @include('lb.partials.site-nav')
    @unless(View::hasSection('hero_notices'))
        @include('lb.partials.notices', ['variant' => 'bar'])
    @endunless

    <main id="lb-main">
        @yield('content')
    </main>

    @include('lb.partials.site-footer')
    @include('lb.partials.quick-connect')

    <script>
        window.LB_SITE = {
            priceUrl: @json(route('subscriptions.getSubscriptionPrice')),
            selectPlanUrl: @json(route('store.selected.plan')),
            registerUrl: @json(route('register')),
            demoUrl: @json(route('demo-request'))
        };
    </script>
    <script src="{{ asset('public/js/lb-core.js') }}?v={{ @filemtime(public_path('js/lb-core.js')) }}" defer></script>
    <script src="{{ asset('public/js/lb-site.js') }}?v={{ @filemtime(public_path('js/lb-site.js')) }}" defer></script>
    @stack('scripts')
</body>
</html>
