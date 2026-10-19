@extends('lb.layouts.site')

{{-- About — SEO stays DB-driven (pages table, route "about-us") via lb.layouts.site. --}}

@php
    $img = fn ($n, $w = 1600) => asset('public/img/site/' . $n . '-' . $w . '.webp');
    $values = [
        ['sparkles', 'Powerful & easy to use', 'No outdated registers or complicated software. If you can use WhatsApp, you can use Libraro.'],
        ['layers', 'Made for your library', 'One room with 30 seats or many branches with hundreds of learners — Libraro fits your size and your shifts.'],
        ['clock-3', 'Saves time & money', 'Automatic reminders, receipts and reports cut down manual work, so you spend more time with your learners.'],
        ['shield-check', 'Secure & reliable', 'Learner phone numbers and emails are encrypted. Your data is safe and available whenever you need it.'],
        ['headset', 'Dedicated support', 'From setup to everyday questions, our team is a call or WhatsApp away.'],
        ['file-spreadsheet', 'One-click import', 'Bring your existing learners from Excel in one go. No typing everything again.'],
    ];
@endphp

@push('head')
    <link rel="stylesheet" href="{{ asset('public/css/lb-directory.css') }}?v={{ @filemtime(public_path('css/lb-directory.css')) }}">
@endpush

@section('content')
    @include('lb.partials.page-hero', [
        'heading' => 'Making study libraries simple.',
        'lead'    => 'Libraro helps library owners run their library with ease, and helps students find the right place to study.',
        'crumb'   => 'About us',
        'eyebrow' => 'About Libraro',
    ])

    {{-- Story --}}
    <section class="lb-about-intro" aria-label="Our story">
        <div class="lb-media"><img src="{{ $img('hero-library', 800) }}" srcset="{{ $img('hero-library', 800) }} 800w, {{ $img('hero-library') }} 1600w" sizes="(max-width: 760px) 100vw, 520px" alt="Students studying in a bright, modern library" width="800" height="600" fetchpriority="high" decoding="async"></div>
        <div>
            <p>At Libraro, we are dedicated to transforming how libraries operate by providing an intuitive, all-in-one solution for library management. Our goal is to make library operations as efficient and effortless as possible for library owners, helping them save time and resources while focusing on what truly matters — their learners.</p>
            <p>Library owners use Libraro to add and edit learner profiles, allot seats, track seat booking history, mark attendance, and keep an eye on expenses and monthly revenue. Reminders about membership expiry go out automatically, so nothing is overlooked.</p>
        </div>
    </section>

    {{-- Values --}}
    <section class="lb-section" aria-labelledby="lb-why-title">
        <div class="lb-center-text">
            <span class="lb-eyebrow">Why Libraro</span>
            <h2 class="lb-h2" id="lb-why-title" data-reveal>Built around real library life.</h2>
        </div>
        <div class="lb-values">
            @foreach($values as [$icon, $title, $text])
                <article class="lb-value"><i><x-lb-icon :name="$icon" /></i><h3>{{ $title }}</h3><p>{{ $text }}</p></article>
            @endforeach
        </div>
    </section>

    {{-- Who it's for --}}
    <section class="lb-section lb-section--flush-top" aria-label="Who Libraro is for">
        <div class="lb-who">
            <article class="lb-who-card lb-who-card--students">
                <h3>For students</h3>
                <ul>
                    <li><x-lb-icon name="search" />Find quiet study libraries near you</li>
                    <li><x-lb-icon name="list-tree" />Compare shifts, fees and facilities</li>
                    <li><x-lb-icon name="armchair" />Book a seat, call or WhatsApp in one tap</li>
                </ul>
                <a href="{{ route('find-my-library') }}" class="lb-btn lb-btn--light">Find my library <x-lb-icon name="arrow-right" size="18" /></a>
            </article>
            <article class="lb-who-card lb-who-card--owners">
                <h3>For library owners</h3>
                <ul>
                    <li><x-lb-icon name="armchair" />Live seat map for every shift</li>
                    <li><x-lb-icon name="indian-rupee" />Fees, dues and WhatsApp reminders</li>
                    <li><x-lb-icon name="chart-line" />Attendance, expenses and reports</li>
                </ul>
                <a href="{{ route('register') }}" class="lb-btn lb-btn--accent">Start free <x-lb-icon name="arrow-right" size="18" /></a>
            </article>
        </div>
    </section>

    {{-- Trust --}}
    <section class="lb-section lb-section--flush-top" aria-labelledby="lb-trust-title">
        <div class="lb-trust">
            <i><x-lb-icon name="building" /></i>
            <div>
                <h2 id="lb-trust-title">Why trust us?</h2>
                <p>With a passion for innovation and a dedication to quality, libraro.in is backed by Techito, a trusted IT company based in Kota, Rajasthan. We’ve been helping organizations across India modernize their operations with reliable and innovative solutions. When you choose Libraro, you’re choosing a team committed to excellence.</p>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="lb-final lb-on-dark" aria-labelledby="lb-about-cta">
        <h2 class="lb-h2" id="lb-about-cta" data-reveal>Let’s build a smarter library, together.</h2>
        <p class="lb-lead lb-lead--center">Schedule a free demo or talk to our team to see how Libraro can work for your library.</p>
        <div class="lb-actions">
            <a href="{{ url('/') }}#demo" class="lb-btn lb-btn--accent">Book a free demo</a>
            <a href="{{ route('contact-us') }}" class="lb-link-arrow">Contact us <x-lb-icon name="chevron-right" size="18" /></a>
        </div>
        <div class="lb-section lb-section--flush-top"></div>
    </section>
@endsection
