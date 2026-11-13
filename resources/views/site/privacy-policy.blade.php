@extends('lb.layouts.site')
@php
    extract(require resource_path('views/lb/i18n/boot.php')); // website language: lb/i18n/boot.php
@endphp

{{-- Privacy Policy — SEO stays DB-driven via lb.layouts.site. Policy text unchanged. --}}

@push('head')
    <link rel="stylesheet" href="{{ asset('public/css/lb-legal.css') }}?v={{ @filemtime(public_path('css/lb-legal.css')) }}">
@endpush

@section('content')
    @include('lb.partials.legal', [
        'active'  => 'privacy',
        'heading' => $t('Privacy Policy'),
        'lead'    => $t('What we collect, why we collect it, and how we keep your library’s data safe.'),
        'updated' => $t('Effective date: 01-01-2025'),
        'glance'  => [
            ['ban', $t('We never sell your data'), $t('Your data is not sold or rented to anyone.')],
            ['lock', $t('Kept secure'), $t('Industry-standard security protects your information.')],
            ['share-2', $t('Shared only to serve you'), $t('Only with trusted providers like payment processors, or when the law requires.')],
            ['user-round-check', $t('You stay in control'), $t('Ask us to access, update or delete your data, and opt out of promotions anytime.')],
        ],
        'intro'   => $t('<p>At Libraro, we are committed to protecting your privacy. This policy explains how we collect, use, and safeguard your information.</p>'),
        'sections' => [
            [$t('Information We Collect'), $t('<ul><li><b>Personal Information:</b> Includes name, email, phone number, billing address, and payment details collected during registration or transactions.</li><li><b>Usage Data:</b> Non-personal data such as IP address, browser type, and interaction with our platform.</li><li><b>Cookies:</b> We use cookies to improve your experience and analyze website performance.</li></ul>')],
            [$t('How We Use Your Information'), $t('<ul><li>To provide and improve our services.</li><li>To process transactions and manage your account.</li><li>To send updates, notifications, or promotional content (you can opt out).</li><li>To ensure compliance with legal obligations and prevent fraud.</li></ul>')],
            [$t('Data Sharing'), $t('<ul><li>We do not sell or rent your personal data.</li><li>Data is shared with trusted third-party service providers (e.g., payment processors) to deliver services.</li><li>Information may be disclosed if required by law or to protect our rights.</li></ul>')],
            [$t('Data Security'), $t('<ul><li>We use industry-standard security measures to protect your information.</li><li>While we strive for robust security, no method of data transmission or storage is completely secure.</li></ul>')],
            [$t('Your Rights'), $t('<ul><li>Access, update, or delete your personal data by contacting us at support@librarymanager.in.</li><li>Opt out of promotional communications at any time.</li></ul>')],
            [$t('Children’s Privacy'), $t('<ul><li>Libraro is not intended for children under 13. We do not knowingly collect data from children. Contact us if you believe we have.</li></ul>')],
            [$t('Changes to This Policy'), $t('<ul><li>We may update this Privacy Policy periodically. Changes will be posted on this page with an updated effective date.</li></ul>')],
            [$t('Contact Us'), $t('<p>For questions or concerns, contact us at:</p><ul class="lb-legal-contact"><li>Email: <a href="mailto:support@libraro.in">support@libraro.in</a></li><li>Phone: <a href="tel:+91-8114479678">+91-8114479678</a></li><li>Address: H.No. 955 Vinoba Bhave Nagar Kota, Rajasthan 324005</li></ul>')],
        ],
        'outro'   => $t('By using Libraro, you acknowledge and agree to this Privacy Policy.'),
    ])
@endsection
