@extends('lb.layouts.auth')

@section('seo')
    <title>Administrator Login | Libraro</title>
    <link rel="canonical" href="https://www.libraro.in/library/login" />
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
    @include('lb.partials.login-screen', [
        'userType'     => 'superadmin',
        'role'         => 'Administrator',
        'roleIcon'     => 'user-cog',
        'cardTitle'    => 'Administrator login',
        'cardSub'      => 'Restricted to the Libraro team.',
        'brandTitle'   => 'Libraro control centre.',
        'brandLead'    => 'Manage libraries, plans, permissions and website content from one secure place.',
        'points'       => [
            ['building', 'Every library and branch in one list'],
            ['layers', 'Plans, features and permissions'],
            ['shield-check', 'Role-based access for your team'],
        ],
        'badges'       => [['Secure', 'Encrypted session'], ['Audit', 'Request logging'], ['Roles', 'Granular access']],
        'idField'      => 'email',
        'showForgot'   => false,
        'showRegister' => false,
        'helpText'     => 'Access is limited to authorised Libraro staff.',
    ])
@endsection
