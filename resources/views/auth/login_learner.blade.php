@extends('lb.layouts.auth')

@section('seo')
    <title>Learner Login | Libraro</title>
    <meta name="description" content="Learners can log in to Libraro to see their seat, plan, attendance, fee receipts and library ID card.">
    <meta name="robots" content="noindex, follow">
@endsection

@section('content')
    @include('lb.partials.login-screen', [
        'userType'     => 'learner',
        'role'         => 'Learner',
        'roleIcon'     => 'graduation-cap',
        'cardTitle'    => 'Welcome back',
        'cardSub'      => 'Log in with the learner number from your library.',
        'brandTitle'   => 'Your seat, fees and attendance. All in one place.',
        'brandLead'    => 'Check your plan, download receipts and see your attendance anytime.',
        'points'       => [
            ['armchair', 'Your seat and plan details'],
            ['receipt', 'Fee receipts, ready to download'],
            ['id-card', 'Digital library ID card'],
        ],
        'badges'       => [['QR', 'Quick check-in'], ['24×7', 'On any phone'], ['Private', 'Your data is safe']],
        'idField'      => 'learner_no',
        'showForgot'   => false,
        'showRegister' => false,
        'helpText'     => 'Forgot your learner number or password? Ask your library desk.',
        'chip'         => 'Your library, in your pocket',
        'slides'       => [
            ['armchair', 'My seat', 'Your seat and plan, always with you.', 'See your seat number, shift and plan end date anytime.', 'slide-learner'],
            ['receipt', 'Fee receipts', 'Every receipt in one place.', 'Download your fee receipts whenever you need them.', 'slide-study'],
            ['qr-code', 'Attendance', 'Check in with one QR scan.', 'Scan at the door and track your attendance.', 'slide-library'],
            ['id-card', 'ID card', 'Your digital library ID card.', 'Show it at the desk. No paper card to carry.', 'hero-library'],
        ],
    ])
@endsection
