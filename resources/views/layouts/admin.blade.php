@php
    $axPage = \App\Support\AdminNav::page();
    $axCdn = 'https://cdnjs.cloudflare.com/ajax/libs';
    // Blade renders the page before its layout, so the page's HTML is known here: load the
    // heavy plugins (DataTables, SweetAlert, validation, Tagify …) only where they are used.
    // Bundles + markers: config/admin_assets.php, logic: App\Support\AdminAssets.
    $axAssets = ['css' => [], 'js' => [], 'includes' => [], 'rules' => null];
    try {
        \App\Support\AdminAssets::for(
            $__env->yieldContent('content').$__env->yieldContent('page_actions').$__env->yieldPushContent('styles').$__env->yieldPushContent('scripts'),
            $__env->yieldContent('admin_assets')
        );
        $axAssets = [
            'css' => \App\Support\AdminAssets::css(),
            'js' => \App\Support\AdminAssets::js(),
            'includes' => \App\Support\AdminAssets::includes(),
            'rules' => \App\Support\AdminAssets::speculationRules(),
        ];
    } catch (\Throwable $e) {
        // never take the admin portal down over asset picking: load every bundle, no prerendering
        report($e);
        foreach ((array) config('admin_assets.bundles', []) as $axBundle) {
            $axAssets['css'] = array_merge($axAssets['css'], $axBundle['css'] ?? []);
            foreach ($axBundle['js'] ?? [] as $axUrl) {
                $axAssets['js'][] = str_starts_with($axUrl, 'local:') ? asset('public/'.substr($axUrl, 6)) : $axUrl;
            }
            if (! empty($axBundle['include'])) {
                $axAssets['includes'][] = $axBundle['include'];
            }
        }
    }
@endphp
<!DOCTYPE html>
<html lang="en" translate="no">

<head>
    <meta charset="UTF-8">
    {{-- signed-in portal: never indexed or machine-translated; lp-ref traces a copied page to the account (portal:watermark) --}}
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="google" content="notranslate">
    <meta name="lp-ref" content="{{ portalWatermark() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="format-detection" content="telephone=no">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#f5f5f7" media="(prefers-color-scheme: light)" data-ax-theme-color>
    <meta name="theme-color" content="#000000" media="(prefers-color-scheme: dark)" data-ax-theme-color>
    {{-- Appearance (System / Light / Dark) is applied before the first paint, so there is no white flash --}}
    <script>
        (function () {
            var t = null;
            try { t = localStorage.getItem("axTheme"); } catch (e) {}
            if (t === "light" || t === "dark") {
                document.documentElement.setAttribute("data-theme", t);
                document.querySelectorAll("meta[data-ax-theme-color]").forEach(function (m) { m.setAttribute("content", t === "dark" ? "#000000" : "#f5f5f7"); });
            }
        })();
    </script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $axPage['title'] }} · Libraro Admin</title>
    <link rel="icon" href="{{ asset('public/img/favicon.ico') }}" type="image/x-icon">

    {{-- every always-on library comes from cdnjs: one connection (scripts) + one CORS connection (Font Awesome fonts) --}}
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{-- SF Pro is the system font on Apple devices; Inter (same shapes) for everyone else. Never blocks the first paint. --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400..700&display=swap" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400..700&display=swap"></noscript>
    <link rel="stylesheet" href="{{ $axCdn }}/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    {{-- Plugin CSS goes into the lowest cascade layer ("vendor"), so the design system and
         Tailwind utilities (layers theme/base/components/utilities in admin.min.css) always win. --}}
    <style>
        @layer vendor;
        @import url("{{ $axCdn }}/toastr.js/2.1.4/toastr.min.css") layer(vendor);
@foreach($axAssets['css'] as $css)
        @import url("{{ $css }}") layer(vendor);
@endforeach
    </style>
    {{-- Tailwind CSS v4 build: design system + shell + modules (resources/css/admin, bin/build-admin-css) --}}
    <link rel="stylesheet" href="{{ asset('public/css/admin.min.css') }}?v={{ @filemtime(public_path('css/admin.min.css')) }}">
    <style>
        /* page to page: sidebar, toolbar and tab bar stay still, the page cross-fades (Chrome, Edge, Safari 18.2+) */
        @view-transition { navigation: auto; }
        .ax-sidebar { view-transition-name: ax-sidebar; }
        .ax-toolbar { view-transition-name: ax-toolbar; }
        .ax-tabbar { view-transition-name: ax-tabbar; }
        ::view-transition-group(*) { animation-duration: .2s; animation-timing-function: cubic-bezier(.2, .8, .2, 1); }
        ::view-transition-old(ax-sidebar), ::view-transition-old(ax-toolbar), ::view-transition-old(ax-tabbar) { display: none; }
        ::view-transition-new(ax-sidebar), ::view-transition-new(ax-toolbar), ::view-transition-new(ax-tabbar) { animation: none; }
        ::view-transition-old(root) { animation: ax-vt-out .16s cubic-bezier(.4, 0, 1, 1) both; }
        ::view-transition-new(root) { animation: ax-vt-in .2s cubic-bezier(.2, .8, .2, 1) both; }
        @keyframes ax-vt-out { to { opacity: 0; } }
        @keyframes ax-vt-in { from { opacity: 0; transform: translateY(4px); } }
        html.ax-vt .ax-page { animation: none; } /* the transition already animates the page */
        @media (prefers-reduced-motion: reduce) {
            @view-transition { navigation: none; }
        }
    </style>

    <script src="{{ $axCdn }}/jquery/3.7.1/jquery.min.js"></script>
    <script>
        // remember the collapsed sidebar before first paint (no flash)
        try { if (window.innerWidth > 991 && localStorage.getItem('axSidebar') === 'collapsed') document.documentElement.classList.add('ax-pre-collapsed'); } catch (e) {}
        // a view transition animates this navigation: skip the page's own entrance animation
        window.addEventListener('pagereveal', function (e) {
            if (!e.viewTransition) return;
            var html = document.documentElement;
            html.classList.add('ax-vt');
            e.viewTransition.finished.finally(function () { html.classList.remove('ax-vt'); });
        });
        // Microsoft Clarity: after the page has loaded, never for a prerendered page nobody opened
        window.clarity = window.clarity || function () { (window.clarity.q = window.clarity.q || []).push(arguments); };
        (function () {
            function load() {
                var t = document.createElement('script');
                t.async = true;
                t.src = 'https://www.clarity.ms/tag/umcz2tm3mc';
                document.head.appendChild(t);
            }
            function later() { (window.requestIdleCallback || setTimeout)(load, { timeout: 3000 }); }
            function whenLoaded() { if (document.readyState === 'complete') later(); else window.addEventListener('load', later, { once: true }); }
            if (document.prerendering) document.addEventListener('prerenderingchange', whenLoaded, { once: true }); else whenLoaded();
        })();
    </script>

    {{-- plugin partials with their own CSS + deferred JS (e.g. the shared time picker) --}}
    @foreach($axAssets['includes'] as $include)
        @include($include)
    @endforeach
    @stack('styles')
</head>

<body class="ax @yield('body_class')" data-ref="{{ portalWatermark() }}">
    <div class="ax-progress" id="axProgress" role="progressbar" aria-hidden="true"><span></span></div>
    <script>if (document.documentElement.classList.contains('ax-pre-collapsed')) document.body.classList.add('ax-collapsed');</script>

    <div class="ax-app">
        @include('partials.admin.sidebar')

        <main class="ax-main" id="axMain">
            @include('partials.admin.toolbar')

            <div class="ax-page">
                <div class="ax-pagehead">
                    <div class="min-w-0">
                        @if($axPage['section'])
                            <p class="ax-eyebrow">{{ $axPage['section'] }}</p>
                        @endif
                        <h1 class="ax-title">{{ $axPage['title'] }}</h1>
                        @if($axPage['subtitle'])
                            <p class="ax-subtitle">{{ $axPage['subtitle'] }}</p>
                        @endif
                    </div>
                    {{-- page buttons; admin-ui.js also moves each page's own Back / Add buttons here --}}
                    <div class="ax-pagehead-actions" id="axPageActions">@yield('page_actions')</div>
                </div>

                <div class="ax-content" id="axContent">
                    @yield('content')
                </div>
            </div>

            <footer class="ax-footer">© {{ date('Y') }} Libraro · All rights reserved · Made in India for India's libraries · <a href="mailto:info@libraro.com">info@libraro.com</a></footer>
        </main>

        @include('partials.admin.tabbar')
    </div>

    <form id="axLogout" action="{{ route('logout', ['portal' => currentPortal()]) }}" method="POST" hidden>@csrf</form>

    {{-- Always: modal / dropdown / collapse / tooltip without Bootstrap (keeps the data-bs-* API), toasts,
         submit-button spinners. Then the plugins this page uses, then the shell. No defer: page scripts
         in @stack('scripts') below run right away and may call any of them. --}}
    <script src="{{ asset('public/js/ui-kit.js') }}?v={{ @filemtime(public_path('js/ui-kit.js')) }}"></script>
    <script src="{{ $axCdn }}/toastr.js/2.1.4/toastr.min.js"></script>
    <script src="{{ asset('public/js/main-scripts.js') }}?v={{ @filemtime(public_path('js/main-scripts.js')) }}"></script>
    @foreach($axAssets['js'] as $js)
        <script src="{{ $js }}"></script>
    @endforeach
    <script>
        window.axConfig = {
            // SweetAlert loads on first use on pages that did not need it up front
            swal: @json($axCdn.'/limonte-sweetalert2/11.26.25/sweetalert2.all.min.js'),
            // never prefetched / prerendered (logout, exports, downloads, GET actions that change data)
            noPrefetch: @json(config('admin_assets.no_prefetch', []))
        };
    </script>
    <script src="{{ asset('public/js/admin-ui.js') }}?v={{ @filemtime(public_path('js/admin-ui.js')) }}"></script>
    @if(Route::has('crm.reminders.due') && Gate::allows('has-permission', ['crm-view-leads']))
        {{-- follow-up reminders: badge + toast + browser notification on every admin page --}}
        <script src="{{ asset('public/js/admin-crm-notify.js') }}?v={{ @filemtime(public_path('js/admin-crm-notify.js')) }}" data-due-url="{{ route('crm.reminders.due') }}" defer></script>
    @endif

    {{-- instant page changes: Chrome / Edge prerender an admin link while the pointer rests on it --}}
    @if($axAssets['rules'])
        <script type="speculationrules">
            {!! json_encode($axAssets['rules'], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
        </script>
    @endif
    @stack('scripts')
</body>
</html>
