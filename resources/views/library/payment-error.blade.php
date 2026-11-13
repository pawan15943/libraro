<!DOCTYPE html>
<html>
<head>
    <link rel="preload" href="{{ asset('public/fonts/geist/Geist-Variable.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('public/css/lb-font.css') }}?v={{ @filemtime(public_path('css/lb-font.css')) }}">
    <title>Payment Error</title>
</head>
<body>
    <h1>Payment Error</h1>
    <p>There was an issue processing your payment. Please try again later or contact support for assistance.</p>
    <a href="{{ route('home') }}">Go to Home</a>
</body>
</html>
