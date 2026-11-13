<link rel="preload" href="{{ asset('public/fonts/geist/Geist-Variable.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="{{ asset('public/css/lb-font.css') }}?v={{ @filemtime(public_path('css/lb-font.css')) }}">
{{-- Shared <head> for the new Libraro design (website + auth). --}}
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#fbfbfa" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#000000" media="(prefers-color-scheme: dark)">
<meta name="format-detection" content="telephone=no">
<link rel="icon" href="{{ asset('public/img/favicon.ico') }}" type="image/x-icon">
<script>
    /* Apply saved / system theme before first paint (no flash). */
    (function () {
        try {
            var t = localStorage.getItem('lb-theme');
            if (t !== 'light' && t !== 'dark') {
                t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-theme', t);
        } catch (e) {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    })();
</script>
<link rel="preload" href="{{ asset('public/fonts/geist/Geist-Variable.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="{{ asset('public/css/lb-base.css') }}?v={{ @filemtime(public_path('css/lb-base.css')) }}">
