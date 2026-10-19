@extends('lb.layouts.site')

{{--
    Home — redesigned (Apple style). SEO title/description/canonical/schema still come
    from the `pages` table for route "/" via lb.layouts.site (same logic as before).
    Live data kept: $subscriptions + $features (plans), prices via AJAX (monthly/yearly),
    Buy Now → store.selected.plan → register, $happy_customers (reviews), demo request form.
--}}

@php
    $img = fn ($n, $w = 1600) => asset('public/img/site/' . $n . '-' . $w . '.webp');
    $srcset = fn ($n) => asset('public/img/site/' . $n . '-800.webp') . ' 800w, ' . asset('public/img/site/' . $n . '-1600.webp') . ' 1600w';

    $plansList = $subscriptions->values();
    $popularIndex = $plansList->count() >= 3 ? 1 : -1;
    $allFeatureNames = $features->whereNull('deleted_at')->pluck('name')->unique()->values()->toArray();
    $planTagline = fn ($s) => match ((int) $s->id) {
        1 => 'Up to 100 seats & 1 branch with essential features',
        2 => 'Up to 200 seats & 2 branches with smart features',
        3 => 'Unlimited seats & 3 branches with all pro features',
        default => $s->plan_description ?? '',
    };

    $ops = [
        ['user-plus', 'Admit', 'Add a learner in 30 seconds with photo and ID proof.', 'admit', '8:00 AM', 'A new learner registering at the library front desk'],
        ['armchair', 'Allot a seat', 'Pick a free seat from the live seat map, for any shift.', 'seats', '8:05 AM', 'Learners studying at their seats in a study library'],
        ['indian-rupee', 'Collect fees', 'Cash, UPI or online. The receipt goes out on WhatsApp.', 'fees', '10:30 AM', 'Paying library fees by scanning a UPI QR code'],
        ['qr-code', 'Mark attendance', 'Learners scan a QR code at the door. That\'s it.', 'qr', 'All day', 'A learner scanning a QR code on her phone to check in'],
        ['bell-ring', 'Send reminders', 'Dues and seat expiry alerts go out on their own.', 'remind', '6:00 PM', 'A learner reading a fee reminder on her phone'],
        ['chart-line', 'Review the day', 'See money in, seats filled and dues pending.', 'report', '9:00 PM', 'Library owner reviewing the day’s collection on a laptop'],
    ];

    $pains = [
        ['notebook-pen', 'Registers and Excel sheets everywhere', 'One dashboard for seats, learners, fees and expenses, on your phone and laptop.'],
        ['indian-rupee', 'Chasing dues every month', 'Automatic WhatsApp and SMS reminders go out before the due date.'],
        ['armchair', 'Two learners, one seat', 'A live seat map for every shift, so a seat can never be double-booked.'],
        ['qr-code', '“Who came today?”', 'Learners scan a QR code at the door. Attendance is marked by itself.'],
        ['users-round', 'Not sure what staff collected', 'Staff get their own login with only the access you allow, and every payment is recorded.'],
        ['armchair', 'Empty seats, few new learners', 'A free listing on Find my library and a QR booking page bring in walk-ins.'],
    ];

    $suite = [
        ['seats', 'armchair', 'Seats & shifts', 'Your whole floor, live.', 'Fill every seat in every shift, without a register.', [
            'Live seat map for each shift and floor',
            'Custom shifts: morning, evening, full day, night',
            'Seat swap, extend and freeze days in a tap',
            'Expiring and expired seats listed for you',
        ], 'tile-seats', ['84%', 'seats filled today']],
        ['admit', 'user-plus', 'Admissions', 'Admit a learner in 30 seconds.', 'Photo, ID proof, plan and seat on one simple screen.', [
            'Learner profile with photo and ID proof',
            'Import all learners from Excel in one go',
            'QR booking page so learners can join themselves',
            'Print ID cards one by one or in bulk',
        ], 'slide-learner', ['30 sec', 'to admit a learner']],
        ['fees', 'indian-rupee', 'Fees & payments', 'Get paid on time, every month.', 'Cash, UPI or online. Every rupee is tracked.', [
            'Collect by cash, UPI or online payment',
            'Receipts sent on WhatsApp and as PDF',
            'Dues list with one-tap reminders',
            'Plan renewals and upgrades in seconds',
        ], 'tile-reports', ['₹0', 'dues forgotten']],
        ['attend', 'qr-code', 'Attendance', 'Check-in with one QR scan.', 'Know who is in the library, right now.', [
            'QR check-in at the entrance',
            'Daily and monthly attendance reports',
            'See who is inside at any moment',
            'Works on any learner’s phone',
        ], 'tile-attendance', ['1 scan', 'and they are in']],
        ['remind', 'bell-ring', 'WhatsApp & reminders', 'Reminders that send themselves.', 'No more calling every learner one by one.', [
            'Fee due and seat expiry reminders',
            'WhatsApp and SMS templates you can edit',
            'Send a notice to all learners at once',
            'Receipts and welcome messages on WhatsApp',
        ], 'slide-study', ['Auto', 'every month']],
        ['reports', 'chart-line', 'Reports & expenses', 'Know your numbers in seconds.', 'Money in, money out and profit, always ready.', [
            'Daily and monthly collection reports',
            'Expense manager and balance sheet',
            'Seats filled vs empty, by shift',
            'Export to Excel / CSV anytime',
        ], 'dashboard', ['1 tap', 'for any report']],
        ['branches', 'building', 'Branches & staff', 'Many branches. One login.', 'Grow to more locations without losing control.', [
            'Switch between branches in one click',
            'Staff logins with permissions you choose',
            'Separate seats, plans and money per branch',
            'Learner data stays private and encrypted',
        ], 'owner', ['3+', 'branches, one app']],
        ['grow', 'trending-up', 'Get more learners', 'Get found by learners nearby.', 'Your library online, at no extra cost.', [
            'Free listing on Find my library',
            'Enquiries come straight to you',
            'Reviews from your learners build trust',
            'Refer other owners and earn rewards',
        ], 'reader', ['Free', 'listing in every plan']],
    ];

    $vsRows = [
        ['Seat allotment', 'Register, guesswork, double booking', 'Live seat map for every shift'],
        ['Fee dues', 'Calling learners one by one', 'Automatic WhatsApp reminders'],
        ['Receipts', 'Handwritten slips', 'WhatsApp + PDF receipts'],
        ['Attendance', 'Sign-in sheet', 'QR scan at the door'],
        ['Monthly accounts', 'Hours of adding up', 'Ready in one tap'],
        ['Staff & cash', 'Trust and hope', 'Logins, permissions and full history'],
        ['New learners', 'Word of mouth only', 'Free online listing + QR booking'],
    ];

    $cmpGroups = [
        ['Capacity & limits', [
            ['Learner capacity', 'Up to 100 seats', 'Up to 200 seats', 'Unlimited seats'],
            ['Branch support', '1 branch', '2 branches', '3+ branches'],
            ['Custom shifts', 'Up to 3', 'Up to 6', 'Unlimited'],
            ['Floor & room setup', 'Single floor', 'Multi-floor', 'Multi-floor'],
        ]],
        ['Seats & admission', [
            ['Live visual seat map', true, true, true],
            ['QR code digital admission', true, true, true],
            ['Seat swap & day freezing', true, true, true],
            ['Student ID cards', 'Single print', 'Bulk print', 'All batch print'],
        ]],
        ['WhatsApp & reminders', [
            ['Automated fee due reminders', 'SMS / add-on', 'Included', 'Priority'],
            ['WhatsApp fee receipts', true, true, true],
            ['Expired & extended learner list', true, true, true],
        ]],
        ['Revenue & reports', [
            ['Daily & monthly collection reports', 'Standard', 'Advanced', 'Full suite'],
            ['Expense manager & balance sheet', true, true, true],
            ['Excel / CSV export', true, true, true],
        ]],
        ['Security & support', [
            ['Learner data privacy', 'Encrypted', 'Encrypted', 'Encrypted'],
            ['Data migration help', 'Self import', 'Assisted', 'Dedicated'],
            ['Customer support', 'Email', 'Phone & WhatsApp', '24/7 priority'],
            ['Money-back guarantee', '7 days', '7 days', '7 days'],
        ]],
    ];

    $faqs = [
        ['What is Libraro and how does it work?', 'Libraro is library management software for study libraries and reading rooms. You set up your seats, shifts and plans once. After that, admissions, seat allotment, fee collection, reminders, attendance and reports all happen from one dashboard.'],
        ['Who can use Libraro?', 'Any self-study library, reading room or public library in India, from a single 30-seat room to owners running several branches.'],
        ['Can I move my existing data from Excel?', 'Yes. Download our sample sheet, paste your learner list and upload it. Libraro imports everything in one go, so you don\'t type it all again.'],
        ['Does it work on my phone?', 'Yes. Libraro works on desktops, laptops, tablets and smartphones, so you can check seats and collections from anywhere.'],
        ['Is my library\'s data safe?', 'Yes. Learner mobile numbers and emails are encrypted, and only authorised users of your library can see them. Staff get only the permissions you give them.'],
        ['How does the 7-day refund work?', 'Use any plan for up to 7 days. If it\'s not right for your library, ask for a refund and we return the full amount. No questions asked.'],
        ['What is the free library listing?', 'Every plan lists your library on Libraro\'s "Find my library" directory, so learners nearby can find you and send enquiries.'],
        ['How do I get help if I face an issue?', 'Call or WhatsApp us at +91 81144 79678 or +91 77379 18848, or email support@libraro.in. We also help you with setup on a call.'],
    ];

    $reviews = collect($happy_customers ?? [])->take(10)->map(function ($v) {
        $name = !empty($v->library_owner) ? $v->library_owner : (!empty($v->library_name) ? $v->library_name : 'Library partner');
        $sub = trim(($v->library_name ?? '') . (!empty($v->city_name) ? ' · ' . $v->city_name : ''), ' ·');
        return ['text' => $v->description, 'name' => $name, 'sub' => $sub ?: 'Library owner', 'rating' => max(1, min(5, (int) ($v->rating ?? 5)))];
    });
    if ($reviews->isEmpty()) {
        $reviews = collect([
            ['text' => 'We’ve been using Libraro for over a year now, and it has exceeded all our expectations. The analytics and reporting features provide valuable insights. It’s an all-in-one solution for modern library management!', 'name' => 'Sandeep Rathor', 'sub' => 'Libraro Manager', 'rating' => 5],
        ]);
    }
@endphp

@push('head')
    <link rel="stylesheet" href="{{ asset('public/css/lb-home.css') }}?v={{ @filemtime(public_path('css/lb-home.css')) }}">
    <link rel="preload" as="image" href="{{ $img('hero-library', 800) }}" imagesrcset="{{ $srcset('hero-library') }}" imagesizes="100vw" fetchpriority="high">
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($faqs)->map(fn ($f) => [
                '@type' => 'Question',
                'name' => $f[0],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
            ])->values(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')

{{-- ===================== HERO ===================== --}}
@section('hero_notices', '1')
<section class="lb-hero" aria-labelledby="lb-hero-title">
    @include('lb.partials.notices', ['variant' => 'hero'])
    <h1 id="lb-hero-title">
        <span class="lb-eyebrow">Library management software for India</span>
        Run your library.<br>Not your paperwork.
    </h1>
    <p class="lb-lead">Seats, fees, attendance, WhatsApp reminders and reports. Everything your study library needs, in one simple app.</p>
    <div class="lb-actions">
        <a href="{{ route('register') }}" class="lb-btn">Start free</a>
        <button type="button" class="lb-btn lb-btn--ghost" data-lb-qc-open><x-lb-icon name="phone-call" size="18" />Get a call back</button>
    </div>
    <div class="lb-hero-trust">
        <span><x-lb-icon name="circle-check" />7-day refund</span>
        <span><x-lb-icon name="circle-check" />Free setup help</span>
        <span><x-lb-icon name="circle-check" />Works on any phone</span>
    </div>
    <div class="lb-media lb-hero-media">
        <img src="{{ $img('hero-library', 800) }}" srcset="{{ $srcset('hero-library') }}" sizes="(max-width: 1200px) 100vw, 1200px" alt="A bright, modern study library with learners at their desks" width="1376" height="768" fetchpriority="high" decoding="async">
    </div>
</section>

{{-- ===================== TRUST STRIP ===================== --}}
<section class="lb-truststrip" aria-label="Why owners trust Libraro">
    <ul>
        <li><i><x-lb-icon name="library" /></i><div><b>Made only for study libraries</b><span>Reading rooms &amp; self-study centres</span></div></li>
        <li><i><x-lb-icon name="lock" /></i><div><b>Learner data encrypted</b><span>Phone numbers &amp; emails stay private</span></div></li>
        <li><i><x-lb-icon name="headset" /></i><div><b>Real people on call</b><span>Phone &amp; WhatsApp support</span></div></li>
        <li><i><x-lb-icon name="rotate-ccw" /></i><div><b>7-day full refund</b><span>No questions asked</span></div></li>
    </ul>
</section>

{{-- ===================== PAIN → FIX ===================== --}}
<section class="lb-section lb-pains" aria-labelledby="lb-pains-title">
    <div class="lb-center-text">
        <span class="lb-eyebrow">Sound familiar?</span>
        <h2 class="lb-h2" id="lb-pains-title" data-reveal>Every library has these problems.<br>Libraro solves all of them.</h2>
    </div>
    <div class="lb-pain-grid">
        @foreach($pains as $k => [$icon, $pain, $fix])
            <article class="lb-pain" data-reveal>
                <div class="lb-pain-q"><i><x-lb-icon name="circle-x" /></i><span>{{ $pain }}</span></div>
                <div class="lb-pain-a"><i><x-lb-icon name="circle-check" /></i><p>{{ $fix }}</p></div>
            </article>
        @endforeach
    </div>
</section>

{{-- ===================== DASHBOARD (dark) ===================== --}}
<section id="features" class="lb-dash lb-on-dark" aria-labelledby="lb-dash-title">
    <span class="lb-eyebrow">Dashboard</span>
    <h2 class="lb-h2" id="lb-dash-title" data-reveal>Your whole library. One clear view.</h2>
    <p class="lb-lead lb-lead--center">See every seat, payment and learner at a glance, for every branch.</p>
    <div class="lb-media lb-dash-media">
        <img src="{{ $img('dashboard', 800) }}" srcset="{{ $srcset('dashboard') }}" sizes="(max-width: 1000px) 100vw, 1000px" alt="Libraro dashboard showing total, booked, available and expired seats with monthly revenue" width="1909" height="866" loading="lazy" decoding="async">
    </div>
</section>

{{-- ===================== EVERYTHING YOU NEED (feature explorer) ===================== --}}
<section id="all-features" class="lb-section lb-suite" aria-labelledby="lb-suite-title">
    <div class="lb-center-text">
        <span class="lb-eyebrow">One app. Every job.</span>
        <h2 class="lb-h2" id="lb-suite-title" data-reveal>Everything your library needs.</h2>
        <p class="lb-lead">Tap a topic to see exactly what Libraro does for you.</p>
    </div>
    <div class="lb-suite-tabs" role="tablist" aria-label="Libraro features">
        @foreach($suite as $k => [$id, $icon, $label])
            <button type="button" role="tab" id="lb-tab-{{ $id }}" aria-controls="lb-panel-{{ $id }}" aria-selected="{{ $k === 0 ? 'true' : 'false' }}" tabindex="{{ $k === 0 ? '0' : '-1' }}"><x-lb-icon :name="$icon" />{{ $label }}</button>
        @endforeach
    </div>
    <div class="lb-suite-panels">
        @foreach($suite as $k => [$id, $icon, $label, $head, $sub, $points, $pic, $stat])
            <div class="lb-suite-panel" role="tabpanel" id="lb-panel-{{ $id }}" aria-labelledby="lb-tab-{{ $id }}" tabindex="0" @if($k) hidden @endif>
                <div class="lb-suite-copy">
                    <div class="lb-suite-icon"><x-lb-icon :name="$icon" /></div>
                    <h3>{{ $head }}</h3>
                    <p>{{ $sub }}</p>
                    <ul>
                        @foreach($points as $pt)<li><i><x-lb-icon name="check" /></i>{{ $pt }}</li>@endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="lb-link-arrow lb-link-arrow--flush">Start free <x-lb-icon name="chevron-right" size="18" /></a>
                </div>
                <div class="lb-suite-media">
                    <div class="lb-media"><img src="{{ $img($pic, 800) }}" srcset="{{ $srcset($pic) }}" sizes="(max-width: 860px) 100vw, 560px" alt="" width="800" height="600" loading="lazy" decoding="async"></div>
                    <div class="lb-suite-stat"><b>{{ $stat[0] }}</b><span>{{ $stat[1] }}</span></div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="lb-suite-all">
        <span>Also included:</span>
        @foreach(['Learner app login', 'ID cards', 'Seat swap', 'Day freezing', 'Expense manager', 'Excel import', 'Online payments', 'Referral rewards', 'Library reviews', 'Branch switch'] as $chip)
            <em><x-lb-icon name="check" />{{ $chip }}</em>
        @endforeach
    </div>
</section>

{{-- ===================== MIGRATION ===================== --}}
<section class="lb-section" aria-labelledby="lb-migrate-title">
    <div class="lb-split2">
        <div>
            <span class="lb-eyebrow">Switch in minutes</span>
            <h2 class="lb-h2" id="lb-migrate-title" data-reveal>Bring your data over in one click.</h2>
            <p class="lb-lead">Move your learner list from Excel or any old software. No typing it all again.</p>
            <ul class="lb-bullets">
                <li><x-lb-icon name="file-spreadsheet" />Upload a CSV with all your learners</li>
                <li><x-lb-icon name="headset" />Our team helps you set up on a call</li>
                <li><x-lb-icon name="sparkles" />Seats, plans and dues are ready the same day</li>
            </ul>
            <div class="lb-actions lb-actions--start">
                <a href="{{ url('public/sample/learners-data-sample.csv') }}" class="lb-link-arrow lb-link-arrow--flush" download>Download sample sheet <x-lb-icon name="chevron-right" size="18" /></a>
            </div>
        </div>
        <div class="lb-media"><img src="{{ $img('migrate', 800) }}" srcset="{{ $srcset('migrate') }}" sizes="(max-width: 760px) 100vw, 520px" alt="Laptop showing a spreadsheet being imported" width="800" height="800" loading="lazy" decoding="async"></div>
    </div>
</section>

{{-- ===================== A DAY AT YOUR LIBRARY ===================== --}}
<section id="operations" class="lb-ops" aria-labelledby="lb-ops-title">
    <div class="lb-ops-head">
        <div>
            <span class="lb-eyebrow">How it works</span>
            <h2 class="lb-h2" id="lb-ops-title" data-reveal>A day at your library.</h2>
        </div>
        <div class="lb-round-btns">
            <button type="button" class="lb-round-btn" data-lb-prev="ops" aria-label="Previous"><x-lb-icon name="chevron-left" /></button>
            <button type="button" class="lb-round-btn" data-lb-next="ops" aria-label="Next"><x-lb-icon name="chevron-right" /></button>
        </div>
    </div>
    <div class="lb-ops-track" data-lb-track="ops" tabindex="0" aria-label="Daily workflow steps">
        @foreach($ops as $i => [$icon, $title, $text, $mock, $time, $alt])
            <article class="lb-op lb-op--{{ ($i % 6) + 1 }}">
                <div class="lb-op-photo">
                    <img src="{{ $img('op-' . $mock, 800) }}" alt="{{ $alt }}" width="800" height="600" loading="lazy" decoding="async">
                    <span class="lb-op-time"><x-lb-icon name="clock-3" />{{ $time }}</span>
                </div>
                <div class="lb-op-body">
                    <div class="lb-op-top">
                        <div class="lb-op-icon"><x-lb-icon :name="$icon" /></div>
                        <div class="lb-op-step">Step {{ $i + 1 }}</div>
                    </div>
                    <h3>{{ $title }}</h3>
                    <p>{{ $text }}</p>
                </div>
            </article>
        @endforeach
    </div>
</section>

{{-- ===================== REGISTER vs LIBRARO ===================== --}}
<section class="lb-section lb-vs-wrap" aria-labelledby="lb-vs-title">
    <div class="lb-center-text">
        <span class="lb-eyebrow">Why switch</span>
        <h2 class="lb-h2" id="lb-vs-title" data-reveal>The old way vs the Libraro way.</h2>
    </div>
    <div class="lb-vs" role="table" aria-label="Register and Excel compared with Libraro">
        <div class="lb-vs-row lb-vs-head" role="row">
            <span role="columnheader"></span>
            <span role="columnheader"><x-lb-icon name="notebook-pen" />Register &amp; Excel</span>
            <span role="columnheader" class="lb-vs-us">@include('lb.partials.logo', ['height' => 22])</span>
        </div>
        @foreach($vsRows as [$what, $old, $new])
            <div class="lb-vs-row" role="row">
                <b role="rowheader">{{ $what }}</b>
                <span role="cell" class="lb-vs-old"><x-lb-icon name="x" />{{ $old }}</span>
                <span role="cell" class="lb-vs-new"><x-lb-icon name="check" />{{ $new }}</span>
            </div>
        @endforeach
    </div>
    <div class="lb-actions">
        <a href="{{ route('register') }}" class="lb-btn">Switch to Libraro</a>
        <button type="button" class="lb-link-arrow" data-lb-qc-open>Talk to an expert <x-lb-icon name="chevron-right" size="18" /></button>
    </div>
</section>

{{-- ===================== PRICING (dark) ===================== --}}
<section id="pricing" class="lb-pricing lb-on-dark" aria-labelledby="lb-pricing-title">
    <div class="lb-center-text">
        <span class="lb-eyebrow">Libraro plans &amp; pricing</span>
        <h2 class="lb-h2" id="lb-pricing-title" data-reveal>Pick your plan.</h2>
        <p class="lb-lead">Every plan includes a free library listing and a 7-day refund.</p>
        <div class="lb-billing" role="group" aria-label="Billing period">
            <button type="button" data-billing="1" aria-pressed="true">Monthly</button>
            <button type="button" data-billing="2" aria-pressed="false">Yearly <small>Save more</small></button>
        </div>
    </div>

    <div class="lb-plans">
        @php $prevIncluded = []; $prevName = null; @endphp
        @foreach($plansList as $i => $subscription)
            @php
                $included = $features->where('subscription_id', $subscription->id)->whereNull('deleted_at')->pluck('name')->unique()->values()->toArray();
                $delta = $prevName ? array_values(array_diff($included, $prevIncluded)) : $included;
                if ($prevName && empty($delta)) { $delta = $included; $showDelta = false; } else { $showDelta = (bool) $prevName; }
                $top = array_slice($delta, 0, 5);
                $isPopular = $i === $popularIndex;
                $shortName = (string) \Illuminate\Support\Str::of($subscription->name)->replace(' Plan', '');
            @endphp
            <article class="lb-plan {{ $isPopular ? 'lb-plan--featured' : '' }}" data-plan-id="{{ $subscription->id }}">
                <div class="lb-plan-head">
                    <h3 class="lb-plan-name">{{ $subscription->name }}</h3>
                    @if($isPopular)<span class="lb-plan-badge">Most popular</span>@endif
                </div>
                <p class="lb-plan-tag">{{ $planTagline($subscription) }}</p>
                <div class="lb-plan-price" aria-live="polite">
                    <strong data-price-for="{{ $subscription->id }}">₹{{ number_format((float) ($subscription->monthly_fees ?? 0)) }}</strong>
                    <span data-per-for="{{ $subscription->id }}">/month</span>
                </div>
                <div class="lb-plan-note" data-note-for="{{ $subscription->id }}">Billed monthly</div>
                <button type="button" class="lb-btn {{ $isPopular ? 'lb-btn--ink' : '' }} buy-now-btn" data-id="{{ $subscription->id }}">
                    Choose {{ $shortName }}
                </button>
                <div class="lb-plan-rule"></div>
                @if($showDelta)
                    <div class="lb-plan-delta">Everything in {{ $prevName }}, plus:</div>
                @else
                    <div class="lb-plan-delta">Key features</div>
                @endif
                <ul class="lb-plan-list">
                    @foreach($top as $f)
                        <li><i><x-lb-icon name="check" /></i>{{ $f }}</li>
                    @endforeach
                </ul>
                @if(count($included) > count($top))
                    <details class="lb-plan-all">
                        <summary><x-lb-icon name="plus" />See all {{ count($included) }} features</summary>
                        <ul class="lb-plan-list">
                            @foreach($included as $f)
                                <li><i><x-lb-icon name="check" /></i>{{ $f }}</li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </article>
            @php $prevIncluded = $included; $prevName = $shortName; @endphp
        @endforeach
    </div>
    <p class="lb-pricing-foot">GST extra as applicable. <a href="#compare">Compare all features <x-lb-icon name="chevron-right" size="15" /></a></p>
</section>

{{-- ===================== COMPARE ===================== --}}
<section id="compare" class="lb-section" aria-labelledby="lb-compare-title">
    <div class="lb-compare">
        <div class="lb-center-text">
            <h2 class="lb-h2" id="lb-compare-title" data-reveal>Compare plans.</h2>
            <p class="lb-lead">See what each plan includes.</p>
        </div>
        <div class="lb-cmp-head" aria-hidden="true">
            <div></div>
            @foreach($plansList->take(3) as $p)
                <div><b>{{ \Illuminate\Support\Str::of($p->name)->replace(' Plan', '') }}</b><span data-cmp-price-for="{{ $p->id }}">₹{{ number_format((float) ($p->monthly_fees ?? 0)) }}/mo</span></div>
            @endforeach
        </div>
        @foreach($cmpGroups as $gi => [$gTitle, $rows])
            <button type="button" class="lb-cmp-group" aria-expanded="true" aria-controls="lb-cmp-{{ $gi }}">
                <span>{{ $gTitle }}</span>
                <i><x-lb-icon name="plus" class="lb-i-plus" /><x-lb-icon name="minus" class="lb-i-minus" /></i>
            </button>
            <div class="lb-cmp-rows" id="lb-cmp-{{ $gi }}" role="table" aria-label="{{ $gTitle }}">
                @foreach($rows as $row)
                    <div class="lb-cmp-row" role="row">
                        <div role="rowheader">{{ $row[0] }}</div>
                        @foreach(array_slice($row, 1) as $ci => $cell)
                            <div role="cell">
                                @if($cell === true)
                                    <span class="lb-yes"><x-lb-icon name="check" /><span class="lb-sr-only">Included</span></span>
                                @elseif($cell === false)
                                    <span class="lb-no"><x-lb-icon name="minus" /><span class="lb-sr-only">Not included</span></span>
                                @else
                                    {{ $cell }}
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endforeach
        <div class="lb-cmp-actions">
            <button type="button" class="lb-btn lb-btn--ghost" data-cmp="expand"><x-lb-icon name="plus" size="16" />Expand all</button>
            <button type="button" class="lb-btn lb-btn--ghost" data-cmp="collapse"><x-lb-icon name="minus" size="16" />Collapse all</button>
        </div>
    </div>
</section>

{{-- ===================== REFUND ===================== --}}
<section id="refund" class="lb-refund-wrap" aria-labelledby="lb-refund-title">
    <div class="lb-refund">
        <div class="lb-refund-big" aria-hidden="true">
            <b>7</b>
            <span><x-lb-icon name="shield-check" />day refund</span>
        </div>
        <div>
            <h2 id="lb-refund-title" data-reveal>Not happy? Get your money back. No questions asked.</h2>
            <p>Try any plan for 7 days. If it's not right for your library, ask for a refund and we'll send back the full amount. <a href="{{ route('refund-policy') }}">Read the refund policy</a>.</p>
            <div class="lb-chips">
                <span><x-lb-icon name="rotate-ccw" />Full refund</span>
                <span><x-lb-icon name="layers" />Any plan</span>
                <span><x-lb-icon name="badge-check" />No reason needed</span>
            </div>
        </div>
    </div>
</section>

{{-- ===================== FOUNDER VOICE ===================== --}}
<section class="lb-section lb-voice" aria-label="A note from our founder">
    <div class="lb-split2">
        <div class="lb-media"><img src="{{ $img('slide-library', 800) }}" srcset="{{ $srcset('slide-library') }}" sizes="(max-width: 760px) 100vw, 520px" alt="Inside a modern, well-lit study library" width="800" height="1000" loading="lazy" decoding="async"></div>
        <blockquote>
            <x-lb-icon name="quote" class="lb-i-quote" />
            <p>“I built Libraro to make running a library simple: automatic bookings, fees on time and clear reports. Everything a library owner needs, in one place.”</p>
            <cite><b>Pawan Rathore</b><span>Founder &amp; Director, Libraro</span></cite>
        </blockquote>
    </div>
</section>

{{-- ===================== REVIEWS (dark) ===================== --}}
<section id="feedback" class="lb-section lb-on-dark" aria-labelledby="lb-reviews-title">
    <div class="lb-container lb-container--flush">
        <div class="lb-center-text">
            <h2 class="lb-h2" id="lb-reviews-title" data-reveal>Loved by library owners.</h2>
            <p class="lb-lead">What owners say after switching to Libraro.</p>
        </div>
        <div class="lb-rv-wrap" data-lb-rv>
            <div class="lb-rv-track" data-lb-track="rv" tabindex="0" aria-label="Reviews from library owners">
            @foreach($reviews as $r)
                <figure class="lb-review">
                    <div class="lb-review-top">
                        <div class="lb-stars" aria-label="{{ $r['rating'] }} out of 5 stars" role="img">
                            @for($s = 1; $s <= 5; $s++)<x-lb-icon name="star" class="{{ $s > $r['rating'] ? 'is-off' : '' }}" />@endfor
                        </div>
                        <x-lb-icon name="quote" class="lb-i-quote" />
                    </div>
                    <blockquote class="lb-review-text">“{{ $r['text'] }}”</blockquote>
                    <button type="button" class="lb-review-more" data-text="{{ $r['text'] }}" data-name="{{ $r['name'] }}" data-sub="{{ $r['sub'] }}" hidden>Read more <x-lb-icon name="chevron-right" size="14" /></button>
                    <figcaption class="lb-review-who">
                        <i aria-hidden="true">{{ mb_strtoupper(mb_substr($r['name'], 0, 1)) }}</i>
                        <div><b>{{ $r['name'] }}</b><span>{{ $r['sub'] }}</span></div>
                    </figcaption>
                </figure>
            @endforeach
            </div>
            @if($reviews->count() > 1)
                <div class="lb-rv-ctrl">
                    <button type="button" class="lb-round-btn" data-lb-prev="rv" aria-label="Previous review"><x-lb-icon name="chevron-left" /></button>
                    <div class="lb-rv-dots" data-lb-dots="rv"></div>
                    <button type="button" class="lb-round-btn" data-lb-next="rv" aria-label="Next review"><x-lb-icon name="chevron-right" /></button>
                </div>
            @endif
        </div>
    </div>
</section>

{{-- ===================== FAQ ===================== --}}
<section id="faq" class="lb-section" aria-labelledby="lb-faq-title">
    <div class="lb-center-text">
        <h2 class="lb-h2" id="lb-faq-title" data-reveal>Questions? Answers.</h2>
    </div>
    <div class="lb-faq">
        @foreach($faqs as $i => [$q, $a])
            <details @if($i === 0) open @endif>
                <summary>{{ $q }}<i aria-hidden="true"><x-lb-icon name="plus" /></i></summary>
                <p>{{ $a }}</p>
            </details>
        @endforeach
    </div>
</section>

{{-- ===================== DEMO REQUEST ===================== --}}
<section id="demo" class="lb-section lb-section--flush-top" aria-labelledby="lb-demo-title">
    <div class="lb-demo">
        <div>
            <span class="lb-eyebrow">Free demo</span>
            <h2 class="lb-h2" id="lb-demo-title" data-reveal>See Libraro on a call.</h2>
            <p class="lb-lead">Pick a time and our team will show you how it works for your library.</p>
            <ul class="lb-contact-list">
                <li><a href="tel:+918114479678"><i><x-lb-icon name="phone" /></i>+91 81144 79678</a></li>
                <li><a href="https://wa.me/918114479678" target="_blank" rel="noopener"><i><x-lb-brand name="whatsapp" /></i>Chat on WhatsApp</a></li>
                <li><a href="mailto:info@libraro.in"><i><x-lb-icon name="mail" /></i>info@libraro.in</a></li>
            </ul>
        </div>
        <div class="lb-demo-card" data-lb-form-card>
            <form id="demoRequest" class="lb-form" data-lb-ajax="{{ route('demo-request') }}" novalidate>
                @csrf
                <div class="lb-field lb-field--full">
                    <label class="lb-label" for="full_name">Full name</label>
                    <div class="lb-input"><x-lb-icon name="user-round" /><input type="text" name="full_name" id="full_name" class="char-only" placeholder="Your name" autocomplete="name" minlength="2" required></div>
                </div>
                <div class="lb-field">
                    <label class="lb-label" for="demo_mobile">Mobile number</label>
                    <div class="lb-input"><x-lb-icon name="smartphone" /><input type="tel" name="mobile_number" id="demo_mobile" class="digit-only" data-maxlength="10" minlength="10" pattern="[0-9]{10}" title="Enter a valid 10-digit mobile number." inputmode="numeric" placeholder="98765 43210" autocomplete="tel-national" required></div>
                </div>
                <div class="lb-field">
                    <label class="lb-label" for="demo_email">Email</label>
                    <div class="lb-input"><x-lb-icon name="mail" /><input type="email" name="email" id="demo_email" placeholder="you@example.com" autocomplete="email" required></div>
                </div>
                <div class="lb-field">
                    <label class="lb-label" for="preferred_date">Preferred date</label>
                    <div class="lb-input"><x-lb-icon name="calendar" /><input type="date" name="preferred_date" id="preferred_date" min="{{ date('Y-m-d') }}" required></div>
                </div>
                <div class="lb-field">
                    <label class="lb-label" for="timeSlot">Preferred time</label>
                    <div class="lb-input"><x-lb-icon name="clock-3" />
                        <select id="timeSlot" name="preferred_time" class="no-validate">
                            <option value="">Any time</option>
                            @php($t = \Carbon\Carbon::createFromTime(7, 0))
                            @while($t->hour < 19 || ($t->hour === 19 && $t->minute === 0))
                                @php($slot = $t->format('g:i A') . ' - ' . $t->copy()->addMinutes(30)->format('g:i A'))
                                <option value="{{ $slot }}">{{ $slot }}</option>
                                @php($t->addMinutes(30))
                            @endwhile
                        </select>
                    </div>
                </div>
                <div class="lb-field lb-field--full">
                    <label class="lb-check" for="demo_terms">
                        <input type="checkbox" name="terms" id="demo_terms" required>
                        <span>I agree to the Libraro <a href="{{ route('term-and-condition') }}" target="_blank" rel="noopener">Terms and Conditions</a>.</span>
                    </label>
                </div>
                <div class="lb-field--full">
                    <button type="submit" class="lb-btn lb-btn--lg lb-btn--block" data-loading-text="Booking…"><span class="lb-btn-label">Book my free demo</span><x-lb-icon name="arrow-right" size="18" /></button>
                </div>
            </form>
            <div class="lb-demo-success lb-form-success" hidden>
                <div class="lb-ok"><x-lb-icon name="circle-check-big" size="30" /></div>
                <h3>Demo booked!</h3>
                <p>Thank you. Our team will call you at your chosen time.</p>
            </div>
        </div>
    </div>
</section>

{{-- ===================== FINAL CTA (dark) ===================== --}}
<section class="lb-final lb-on-dark" aria-labelledby="lb-final-title">
    <h2 class="lb-h2" id="lb-final-title" data-reveal>Your library, listed and ready.</h2>
    <p class="lb-lead lb-lead--center">Get a free listing so learners nearby can find you.</p>
    <div class="lb-actions">
        <a href="{{ route('register') }}" class="lb-btn lb-btn--accent">Get started</a>
        <a href="#demo" class="lb-link-arrow">Talk to us <x-lb-icon name="chevron-right" size="18" /></a>
    </div>
    <div class="lb-media">
        <img src="{{ $img('cta-wide', 800) }}" srcset="{{ $srcset('cta-wide') }}" sizes="(max-width: 1100px) 100vw, 1100px" alt="Team working together on laptops" width="1600" height="1067" loading="lazy" decoding="async">
    </div>
</section>

<dialog class="lb-dialog" id="lb-review-dialog" aria-labelledby="lb-dialog-name">
    <div class="lb-dialog-body">
        <button type="button" class="lb-dialog-close" aria-label="Close"><x-lb-icon name="x" size="18" /></button>
        <div class="lb-review-who"><i aria-hidden="true" data-r-initial></i><div><b id="lb-dialog-name" data-r-name></b><span data-r-sub></span></div></div>
        <p data-r-text></p>
    </div>
</dialog>
@endsection
