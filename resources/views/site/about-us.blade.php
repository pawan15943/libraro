@extends('lb.layouts.site')
@php
    extract(require resource_path('views/lb/i18n/boot.php')); // website language: lb/i18n/boot.php
@endphp

{{-- About — SEO stays DB-driven (pages table, route "about-us") via lb.layouts.site. --}}

@php
    $img = fn ($n, $w = 1600) => asset('public/img/site/' . $n . '-' . $w . '.webp');
    $values = [
        ['sparkles', $t('Powerful & easy to use'), $t('No outdated registers or complicated software. If you can use WhatsApp, you can use Libraro.')],
        ['layers', $t('Made for your library'), $t('One room with 30 seats or many branches with hundreds of learners — Libraro fits your size and your shifts.')],
        ['clock-3', $t('Saves time & money'), $t('Automatic reminders, receipts and reports cut down manual work, so you spend more time with your learners.')],
        ['shield-check', $t('Secure & reliable'), $t('Learner phone numbers and emails are encrypted. Your data is safe and available whenever you need it.')],
        ['headset', $t('Dedicated support'), $t('From setup to everyday questions, our team is a call or WhatsApp away.')],
        ['file-spreadsheet', $t('One-click import'), $t('Bring your existing learners from Excel in one go. No typing everything again.')],
    ];
@endphp

@push('head')
    <link rel="stylesheet" href="{{ asset('public/css/lb-directory.css') }}?v={{ @filemtime(public_path('css/lb-directory.css')) }}">
@endpush

@section('content')
    @include('lb.partials.page-hero', [
        'heading' => $t('Making study libraries simple.'),
        'lead'    => $t('Libraro helps library owners run their library with ease, and helps students find the right place to study.'),
        'crumb'   => $t('About us'),
        'eyebrow' => $t('About Libraro'),
    ])

    {{-- Story --}}
    <section class="lb-about-intro" aria-label="{{ $t('Our story') }}">
        <div class="lb-media"><img src="{{ $img('hero-library', 800) }}" srcset="{{ $img('hero-library', 800) }} 800w, {{ $img('hero-library') }} 1600w" sizes="(max-width: 760px) 100vw, 520px" alt="{{ $t('Students studying in a bright, modern library') }}" width="800" height="600" fetchpriority="high" decoding="async"></div>
        <div>
            <p>{{ $t('At Libraro, we are dedicated to transforming how libraries operate by providing an intuitive, all-in-one solution for library management. Our goal is to make library operations as efficient and effortless as possible for library owners, helping them save time and resources while focusing on what truly matters — their learners.') }}</p>
            <p>{{ $t('Library owners use Libraro to add and edit learner profiles, allot seats, track seat booking history, mark attendance, and keep an eye on expenses and monthly revenue. Reminders about membership expiry go out automatically, so nothing is overlooked.') }}</p>
        </div>
    </section>

    {{-- Values --}}
    <section class="lb-section" aria-labelledby="lb-why-title">
        <div class="lb-center-text">
            <span class="lb-eyebrow">{{ $t('Why Libraro') }}</span>
            <h2 class="lb-h2" id="lb-why-title" data-reveal>{{ $t('Built around real library life.') }}</h2>
        </div>
        <div class="lb-values">
            @foreach($values as [$icon, $title, $text])
                <article class="lb-value"><i><x-lb-icon :name="$icon" /></i><h3>{{ $title }}</h3><p>{{ $text }}</p></article>
            @endforeach
        </div>
    </section>

    {{-- Who it's for --}}
    <section class="lb-section lb-section--flush-top" aria-label="{{ $t('Who Libraro is for') }}">
        <div class="lb-who">
            <article class="lb-who-card lb-who-card--students">
                <h3>{{ $t('For students') }}</h3>
                <ul>
                    <li><x-lb-icon name="search" />{{ $t('Find quiet study libraries near you') }}</li>
                    <li><x-lb-icon name="list-tree" />{{ $t('Compare shifts, fees and facilities') }}</li>
                    <li><x-lb-icon name="armchair" />{{ $t('Book a seat, call or WhatsApp in one tap') }}</li>
                </ul>
                <a href="{{ route('find-my-library') }}" class="lb-btn lb-btn--light">{{ $t('Find my library') }} <x-lb-icon name="arrow-right" size="18" /></a>
            </article>
            <article class="lb-who-card lb-who-card--owners">
                <h3>{{ $t('For library owners') }}</h3>
                <ul>
                    <li><x-lb-icon name="armchair" />{{ $t('Live seat map for every shift') }}</li>
                    <li><x-lb-icon name="indian-rupee" />{{ $t('Fees, dues and WhatsApp reminders') }}</li>
                    <li><x-lb-icon name="chart-line" />{{ $t('Attendance, expenses and reports') }}</li>
                </ul>
                <a href="{{ route('register') }}" class="lb-btn lb-btn--accent">{{ $t('Start free') }} <x-lb-icon name="arrow-right" size="18" /></a>
            </article>
        </div>
    </section>

    {{-- Trust --}}
    <section class="lb-section lb-section--flush-top" aria-labelledby="lb-trust-title">
        <div class="lb-trust">
            <i><x-lb-icon name="building" /></i>
            <div>
                <h2 id="lb-trust-title">{{ $t('Why trust us?') }}</h2>
                <p>{{ $t('With a passion for innovation and a dedication to quality, libraro.in is backed by Techito, a trusted IT company based in Kota, Rajasthan. We’ve been helping organizations across India modernize their operations with reliable and innovative solutions. When you choose Libraro, you’re choosing a team committed to excellence.') }}</p>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="lb-final lb-on-dark" aria-labelledby="lb-about-cta">
        <h2 class="lb-h2" id="lb-about-cta" data-reveal>{{ $t('Let’s build a smarter library, together.') }}</h2>
        <p class="lb-lead lb-lead--center">{{ $t('Schedule a free demo or talk to our team to see how Libraro can work for your library.') }}</p>
        <div class="lb-actions">
            <a href="{{ url('/') }}#demo" class="lb-btn lb-btn--accent">{{ $t('Book a free demo') }}</a>
            <a href="{{ route('contact-us') }}" class="lb-link-arrow">{{ $t('Contact us') }} <x-lb-icon name="chevron-right" size="18" /></a>
        </div>
        <div class="lb-section lb-section--flush-top"></div>
    </section>
@endsection
