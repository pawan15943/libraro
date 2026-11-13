@extends('lb.layouts.auth')

@section('seo')
    <title>Library Login | Access Libraro’s Dashboard & Manage Your Library</title>
    <meta name="description" content="Login to your Libraro dashboard and manage seat bookings, payments, and all your library operations in one place. Smart library software starts here.">
    <meta name="keywords" content="Library Software Login, Libraro Dashboard, Library Management Login, Seat Booking Login">
    <meta name="author" content="TECHITO India Pvt. Ltd.">
    <meta property="og:title" content="Library Login | Manage Your Library with Libraro" />
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebPage",
            "name": "Library Login",
            "url": "https://www.libraro.in/library/login",
            "description": "Login to Libraro to access your library dashboard and manage all library functions like bookings, expenses, and more."
        }
    </script>
@endsection

@section('content')
    @include('lb.partials.login-screen', [
        'userType'     => 'admin',
        'role'         => 'Library owner & staff',
        'roleIcon'     => 'library-big',
        'cardTitle'    => 'Welcome back',
        'cardSub'      => 'Log in to manage your library.',
        'brandTitle'   => 'Run your library. Not your paperwork.',
        'brandLead'    => 'Seats, fees, attendance and reports in one place. Built for study libraries across India.',
        'points'       => [
            ['armchair', 'Live seat map for every shift'],
            ['bell-ring', 'Fee reminders on WhatsApp, automatically'],
            ['shield-check', 'Learner phone and email stay private'],
        ],
        'badges'       => [['₹', 'UPI, cash & online'], ['24×7', 'Works on any phone'], ['7-day', 'Refund on any plan']],
        'idField'      => 'email',
        'showForgot'   => true,
        'showRegister' => true,
        'minimal'      => true,
        'helpText'     => 'Trouble logging in? Call <a href="tel:+918114479678">+91 81144 79678</a> or <a href="https://wa.me/918114479678" target="_blank" rel="noopener">WhatsApp us</a>.',
    ])
@endsection
