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
        ['user-plus', 'Admit', 'Add a learner in 30 seconds with photo and ID proof.', 'admit'],
        ['armchair', 'Allot a seat', 'Pick a free seat from the live seat map, for any shift.', 'seats'],
        ['indian-rupee', 'Collect fees', 'Cash, UPI or online. The receipt goes out on WhatsApp.', 'fees'],
        ['qr-code', 'Mark attendance', 'Learners scan a QR code at the door. That\'s it.', 'qr'],
        ['bell-ring', 'Send reminders', 'Dues and seat expiry alerts go out on their own.', 'remind'],
        ['chart-line', 'Review the day', 'See money in, seats filled and dues pending.', 'report'],
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

    $reviews = collect($happy_customers ?? [])->take(6)->map(function ($v) {
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
    <link rel="preload" as="image" href="{{ $img('hero-library', 800) }}" imagesrcset="{{ $srcset('hero-library') }}" imagesizes="(max-width: 1200px) 100vw, 1200px" fetchpriority="high">
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
<section class="lb-hero" aria-labelledby="lb-hero-title">
    <h1 id="lb-hero-title">
        <span class="lb-eyebrow">Library management software</span>
        Run your library.<br>Not your paperwork.
    </h1>
    <p class="lb-lead">Seats, fees, attendance and reports in one place. Built for study libraries across India.</p>
    <div class="lb-actions">
        <a href="{{ route('register') }}" class="lb-btn">Start free</a>
        <a href="#pricing" class="lb-link-arrow">See plans <x-lb-icon name="chevron-right" size="18" /></a>
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

{{-- ===================== DASHBOARD (dark) ===================== --}}
<section id="features" class="lb-dash lb-on-dark" aria-labelledby="lb-dash-title">
    <span class="lb-eyebrow">Dashboard</span>
    <h2 class="lb-h2" id="lb-dash-title" data-reveal>Your whole library. One clear view.</h2>
    <p class="lb-lead lb-lead--center">See every seat, payment and learner at a glance, for every branch.</p>
    <div class="lb-media lb-dash-media">
        <img src="{{ $img('dashboard', 800) }}" srcset="{{ $srcset('dashboard') }}" sizes="(max-width: 1000px) 100vw, 1000px" alt="Libraro dashboard showing total, booked, available and expired seats with monthly revenue" width="1909" height="866" loading="lazy" decoding="async">
    </div>
</section>

{{-- ===================== FEATURE TILES ===================== --}}
<section class="lb-tiles" aria-label="Key features">
    <article class="lb-tile" data-reveal>
        <div class="lb-tile-icon"><x-lb-icon name="armchair" /></div>
        <h3>Seat booking</h3>
        <p>Allot, extend and track expired seats for every shift.</p>
        <div class="lb-media"><img src="{{ $img('tile-seats', 800) }}" alt="Learners studying at library desks" width="800" height="600" loading="lazy" decoding="async"></div>
    </article>
    <article class="lb-tile lb-tile--blue" data-reveal>
        <div class="lb-tile-icon"><x-lb-icon name="scan-face" /></div>
        <h3>Attendance &amp; ID cards</h3>
        <p>Mark entry in a tap. Print ID cards in seconds.</p>
        <div class="lb-media"><img src="{{ $img('tile-attendance', 800) }}" alt="Learner checking in on a phone" width="800" height="600" loading="lazy" decoding="async"></div>
    </article>
    <article class="lb-tile lb-tile--dark" data-reveal>
        <div class="lb-tile-icon"><x-lb-icon name="chart-no-axes-combined" /></div>
        <h3>Reports in seconds</h3>
        <p>Fees, dues, expenses and growth, ready to share.</p>
        <div class="lb-media"><img src="{{ $img('tile-reports', 800) }}" alt="Revenue and collection reports on a laptop" width="800" height="600" loading="lazy" decoding="async"></div>
    </article>
    <article class="lb-tile" data-reveal>
        <div class="lb-tile-icon"><x-lb-icon name="shield-check" /></div>
        <h3>Private by design</h3>
        <p>Learner phone numbers and emails stay encrypted.</p>
        <div class="lb-media"><img src="{{ $img('tile-privacy', 800) }}" alt="Library owner working at the front desk" width="800" height="600" loading="lazy" decoding="async"></div>
    </article>
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
        @foreach($ops as $i => [$icon, $title, $text, $mock])
            <article class="lb-op">
                <div class="lb-op-body">
                    <div class="lb-op-top">
                        <div class="lb-op-icon"><x-lb-icon :name="$icon" /></div>
                        <div class="lb-op-step">Step {{ $i + 1 }}</div>
                    </div>
                    <h3>{{ $title }}</h3>
                    <p>{{ $text }}</p>
                </div>
                <div class="lb-mock" aria-hidden="true">
                    @switch($mock)
                        @case('admit')
                            <div class="lb-mock-row"><div class="lb-mock-avatar">RK</div><div class="lb-mock-grow"><div class="lb-mock-line lb-w-70"></div><div class="lb-mock-line lb-w-45"></div></div></div>
                            <div class="lb-mock-field"><x-lb-icon name="smartphone" />98765 43210</div>
                            <div class="lb-mock-field"><x-lb-icon name="id-card" />Aadhaar uploaded</div>
                            <div class="lb-mock-btn">Admit learner</div>
                            @break
                        @case('seats')
                            <div class="lb-mock-seats">
                                @foreach([1,2,0,1,3,1, 0,1,1,0,1,2, 1,0,1,1,0,1] as $k => $st)
                                    <span class="{{ ['', 'is-booked', 'is-exp', 'is-pick'][$st] }}">{{ $k + 1 }}</span>
                                @endforeach
                            </div>
                            <div class="lb-mock-legend"><span><i class="lb-lg-pick"></i>Selected</span><span><i class="lb-lg-booked"></i>Booked</span><span><i class="lb-lg-exp"></i>Expiring</span></div>
                            @break
                        @case('fees')
                            <div class="lb-mock-row lb-mock-row--between"><span class="lb-mock-faint">Receipt #1042</span><span class="lb-mock-pill lb-mock-pill--ok"><x-lb-icon name="check" />Paid</span></div>
                            <div class="lb-mock-amount">₹1,200</div>
                            <div class="lb-mock-faint">Monthly plan · Seat 14 · UPI</div>
                            <div class="lb-mock-btn lb-mock-btn--wa">Sent on WhatsApp</div>
                            @break
                        @case('qr')
                            <div class="lb-mock-qr"><x-lb-icon name="qr-code" /></div>
                            <div class="lb-mock-center lb-mock-strong">Priya S. checked in</div>
                            <div class="lb-mock-center lb-mock-faint">Seat 07 · 9:02 AM</div>
                            @break
                        @case('remind')
                            <div class="lb-mock-chat">Hi Rahul, your seat fee of ₹800 is due on 5 Oct. Pay at the desk or via UPI. – Bright Study Point<small>9:30 AM ✓✓</small></div>
                            <div class="lb-mock-chat">Your plan ends in 3 days. Renew now to keep Seat 21.<small>9:31 AM ✓✓</small></div>
                            @break
                        @case('report')
                            <div class="lb-mock-stats"><div><b>₹18.4k</b><small>Collected</small></div><div><b>42/50</b><small>Seats</small></div><div><b>₹3.2k</b><small>Pending</small></div></div>
                            <div class="lb-mock-bars">@foreach([40,62,48,75,58,90,70] as $k => $h)<span class="{{ $k === 5 ? 'is-hi' : '' }}" style="height:{{ $h }}%"></span>@endforeach</div>
                            @break
                    @endswitch
                </div>
            </article>
        @endforeach
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
        @foreach($plansList as $i => $subscription)
            @php
                $included = $features->where('subscription_id', $subscription->id)->whereNull('deleted_at')->pluck('name')->unique()->values()->toArray();
                $excluded = array_values(array_diff($allFeatureNames, $included));
                $isPopular = $i === $popularIndex;
                $shownCount = 5;
                $k = 0;
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
                    Choose {{ \Illuminate\Support\Str::of($subscription->name)->replace(' Plan', '') }}
                </button>
                <div class="lb-plan-rule"></div>
                <div class="lb-plan-ftitle"><span>What's included</span><span>{{ count($included) }} features</span></div>
                <ul class="lb-plan-list">
                    @foreach($included as $f)
                        <li class="{{ $k++ >= $shownCount ? 'is-extra' : '' }}"><i><x-lb-icon name="check" /></i>{{ $f }}</li>
                    @endforeach
                    @foreach($excluded as $f)
                        <li class="is-off {{ $k++ >= $shownCount ? 'is-extra' : '' }}"><i><x-lb-icon name="minus" /></i><span><span class="lb-sr-only">Not included: </span>{{ $f }}</span></li>
                    @endforeach
                </ul>
                @if($k > $shownCount)
                    <button type="button" class="lb-plan-more" aria-expanded="false">
                        <x-lb-icon name="plus" class="lb-i-plus" /><x-lb-icon name="minus" class="lb-i-minus" />
                        <span class="lb-plan-more-label" data-more="Show all {{ $k }} features">Show all {{ $k }} features</span>
                    </button>
                @endif
            </article>
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
        <div class="lb-reviews">
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
