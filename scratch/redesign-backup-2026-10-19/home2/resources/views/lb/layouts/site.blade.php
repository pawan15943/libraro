<!DOCTYPE html>
<html lang="en-IN" data-csrf-refresh="{{ route('csrf.refresh') }}">
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
        <link rel="canonical" href="{{ $canonical_url ?? ($page->canonical ?? url()->current()) }}" />
        {!! !empty($page->meta_og) ? $page->meta_og : '' !!}
        @php
            $schema = !empty($page->page_schema) ? html_entity_decode($page->page_schema) : '';
        @endphp
        {!! $schema !!}
    @endif

    <link rel="stylesheet" href="{{ asset('public/css/lb-site.css') }}?v={{ @filemtime(public_path('css/lb-site.css')) }}">
    @stack('head')
</head>
<body class="lb lb-site @yield('body_class')">
    <a href="#lb-main" class="lb-skip">Skip to content</a>
    @include('lb.partials.site-nav')
    @include('sitelayouts.notification-banner')

    <main id="lb-main">
        @yield('content')
    </main>

    @include('lb.partials.site-footer')

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
