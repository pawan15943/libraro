@extends('lb.layouts.site')

{{-- Terms & Conditions — SEO stays DB-driven via lb.layouts.site. Policy text unchanged. --}}

@push('head')
    <link rel="stylesheet" href="{{ asset('public/css/lb-legal.css') }}?v={{ @filemtime(public_path('css/lb-legal.css')) }}">
@endpush

@section('content')
    @include('lb.partials.legal', [
        'active'  => 'terms',
        'heading' => 'Terms & Conditions',
        'lead'    => 'The rules for using Libraro, written to be fair to you and to us.',
        'glance'  => [
            ['user-round', 'Your account, your responsibility', 'Keep your login safe and tell us if someone else uses it.'],
            ['indian-rupee', 'Paid features', 'Some features need a subscription. Pay on time with correct details.'],
            ['ban', 'Use it fairly', 'No illegal use, no attempts to break into other accounts or our servers.'],
            ['scale', 'Indian law applies', 'Disputes go to the courts in Kota, Rajasthan.'],
        ],
        'intro'   => '<p>Welcome to Libraro, accessible via <a href="http://www.libraro.in">www.libraro.in</a>. By using our services, you agree to comply with the following terms and conditions. Please read them carefully before accessing or using our website.</p>',
        'sections' => [
            ['Acceptance of Terms', '<ul><li>By accessing or using Libraro, you agree to be bound by these Terms and Conditions and our Privacy Policy.</li><li>If you do not agree, you must not use our website or services.</li></ul>'],
            ['Use of Services', '<ul><li>Libraro provides library management tools for educational institutions and individuals.</li><li>You agree to use our services only for lawful purposes and in accordance with these Terms.</li></ul>'],
            ['User Accounts', '<ul><li>To access certain features, you may need to register and create an account.</li><li>You are responsible for maintaining the confidentiality of your account information and for all activities under your account.</li><li>Notify us immediately of any unauthorized access or use of your account.</li></ul>'],
            ['Payment and Subscriptions', '<ul><li>Some features of Libraro may require payment or subscription.</li><li>All fees are non-refundable unless stated otherwise.</li><li>You are responsible for providing accurate payment information and ensuring timely payment of fees.</li></ul>'],
            ['Intellectual Property', '<ul><li>All content, software, and materials on Libraro, including text, graphics, logos, and code, are the intellectual property of Libraro or its licensors.</li><li>You may not copy, reproduce, or distribute any content without prior written consent.</li></ul>'],
            ['Prohibited Activities', '<p>You may not:</p><ul class="lb-legal-x"><li>Use Libraro for any illegal or unauthorized purpose.</li><li>Interfere with or disrupt our website or servers.</li><li>Attempt to gain unauthorized access to other accounts or systems.</li></ul>'],
            ['Limitation of Liability', '<ul><li>Libraro is provided on an "as is" and "as available" basis. We make no warranties or representations regarding the accuracy, reliability, or availability of our services.</li><li>To the maximum extent permitted by law, Libraro and its affiliates are not liable for any damages arising from your use of our website or services.</li></ul>'],
            ['Termination', '<ul><li>We reserve the right to suspend or terminate your access to Libraro at any time for any reason, including violation of these Terms.</li><li>Upon termination, your right to use Libraro will immediately cease.</li></ul>'],
            ['Changes to Terms', '<ul><li>We may update these Terms and Conditions periodically. Changes will be posted on this page with an updated effective date.</li><li>Your continued use of Libraro after changes are made constitutes acceptance of the updated Terms.</li></ul>'],
            ['Governing Law', '<ul><li>These Terms are governed by the laws of India. Any disputes arising from these Terms will be subject to the exclusive jurisdiction of the courts in Kota, Rajasthan.</li></ul>'],
            ['Contact Us', '<p>For questions or concerns regarding these Terms, contact us at:</p><ul class="lb-legal-contact"><li>Email: <a href="mailto:support@libraro.in">support@libraro.in</a></li><li>Phone: <a href="tel:+91-8114479678">+91-8114479678</a></li><li>Address: H.No. 955 Vinoba Bhave Nagar Kota, Rajasthan 324005</li></ul>'],
        ],
        'outro'   => 'By using Libraro, you agree to these Terms and Conditions. Thank you for choosing our services.',
    ])
@endsection
