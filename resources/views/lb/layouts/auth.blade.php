<!DOCTYPE html>
<html lang="en" data-csrf-refresh="{{ route('csrf.refresh', ['portal' => currentPortal()]) }}">
<head>
    @include('lb.partials.head-common')
    @yield('seo')
    <link rel="stylesheet" href="{{ asset('public/css/lb-auth.css') }}?v={{ @filemtime(public_path('css/lb-auth.css')) }}">
    @stack('head')
</head>
<body class="lb lb-auth @yield('body_class')" data-bfcache-reload>
    <a href="#lb-main" class="lb-skip">Skip to content</a>
    @yield('content')
    <script src="{{ asset('public/js/lb-core.js') }}?v={{ @filemtime(public_path('js/lb-core.js')) }}" defer></script>
    <script src="{{ asset('public/js/lb-auth.js') }}?v={{ @filemtime(public_path('js/lb-auth.js')) }}" defer></script>
    @stack('scripts')
</body>
</html>
