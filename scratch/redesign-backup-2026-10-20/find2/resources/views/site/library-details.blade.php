@extends('lb.layouts.site')

{{--
    Library details (public, students) — route: libraryDetail (/{slug}) → SiteController@libraryDetail.
    Data from the controller: $library (Branch + city, state, library), $features, $our_package, $total_seat,
    $operating, $learnerFeedback, $libraryplantype.
    Seat map + booking reuse the EXISTING QR booking backend, read-only here:
      seats   → QrEntryController::generateSeatNumbers() (floors) + SeatAvailabilityService::getAvailablePlanTypesMap()
      price   → POST route('get.plan.price')            (same as the QR booking form)
      booking → POST route('booking.store', uuid)       (same fields as the QR booking form)
      result  → signed booking.payment.qr / booking.offline.details pages (read for the QR / confirmation)
    Forms use the SAME endpoints: submit.library.inquiry (branch_id) and submit.review.
--}}

@php
    $libName = $library->display_name ?: $library->name;
    $cityName = optional($library->city)->city_name;
    $stateName = optional($library->state)->state_name;
    $place = collect([$cityName, $stateName])->filter()->implode(', ');
    $addressLine = trim(collect([$library->library_address, $place])->filter()->implode(', ') . ($library->library_zip ? ' - ' . $library->library_zip : ''));

    $toArr = fn ($v) => is_array($v) ? $v : (is_string($v) ? (json_decode($v, true) ?: []) : []);
    $imgPath = fn ($p) => preg_match('#^https?://#i', $p) ? $p : asset('public/' . ltrim(preg_replace('#^/?public/#', '', $p), '/'));
    $images = collect($toArr($library->library_images))->filter()->map($imgPath)->values();
    $isPlaceholderImg = false;
    if ($images->isEmpty()) {
        $images = collect([asset('public/img/site/slide-library-1600.webp')]);
        $isPlaceholderImg = true;
    }
    $fallbackImg = asset('public/img/site/slide-library-800.webp');
    $selectedFeatures = collect($toArr($library->features))->map(fn ($x) => (string) $x)->all();
    $libFeatures = collect($features ?? [])->filter(fn ($f) => in_array((string) $f->id, $selectedFeatures, true))->values();
    $iconFor = function ($name) {
        $n = ' ' . strtolower($name);
        foreach ([
            'wifi' => 'wifi', 'wi-fi' => 'wifi', 'internet' => 'wifi', 'air' => 'snowflake', ' ac' => 'snowflake', 'cool' => 'snowflake',
            'lamp' => 'lamp-desk', 'light' => 'lamp-desk', 'power' => 'zap', 'backup' => 'zap', 'invert' => 'zap', 'generator' => 'zap',
            'water' => 'droplet', 'locker' => 'lock', 'cctv' => 'cctv', 'camera' => 'cctv', 'security' => 'cctv', 'parking' => 'car',
            'washroom' => 'bath', 'toilet' => 'bath', 'news' => 'newspaper', 'magazine' => 'newspaper', 'charging' => 'plug-zap', 'charger' => 'plug-zap',
            'silent' => 'volume-x', 'quiet' => 'volume-x', 'tea' => 'coffee', 'coffee' => 'coffee', 'cafe' => 'coffee',
            'book' => 'book-open', 'chair' => 'armchair', 'seat' => 'armchair', 'cabin' => 'armchair', 'fan' => 'fan',
        ] as $k => $icon) { if (str_contains($n, $k)) return $icon; }
        return 'circle-check';
    };
    $shiftIcon = function ($name, $dayType = null) {
        $dayType = (int) $dayType;
        if (in_array($dayType, [8, 10, 11], true)) return 'clock-3';
        if ($dayType === 9) return 'moon';
        if ($dayType === 2) return 'sunrise';
        if ($dayType === 3) return 'sunset';
        $n = strtolower($name ?? '');
        return str_contains($n, 'night') ? 'moon' : (str_contains($n, 'first') || str_contains($n, 'morning') ? 'sunrise' : (str_contains($n, 'second') || str_contains($n, 'evening') ? 'sunset' : 'sun'));
    };
    $fmtTime = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('g:i A') : null;
    $fmtShort = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format(\Carbon\Carbon::parse($t)->minute ? 'g:i A' : 'g A') : null;

    $packages = collect($our_package ?? [])->filter(fn ($p) => $p->price !== null);
    $minPrice = $packages->min('price');
    $phone = substr(preg_replace('/\D/', '', (string) $library->mobile), -10);
    $phone = strlen($phone) === 10 ? $phone : null;
    $waText = rawurlencode('Hi, I found ' . $libName . ' on Libraro. Is a study seat available?');
    $lat = is_numeric($library->latitude ?? null) ? (float) $library->latitude : null;
    $lng = is_numeric($library->longitude ?? null) ? (float) $library->longitude : null;
    $directions = ($lat && $lng)
        ? 'https://www.google.com/maps/dir/?api=1&destination=' . $lat . ',' . $lng
        : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($libName . ' ' . $addressLine);
    $mapEmbed = (!empty($library->google_map) && \Illuminate\Support\Str::startsWith($library->google_map, 'https://www.google.com/maps/embed?')) ? $library->google_map : null;

    $reviews = collect($learnerFeedback ?? []);
    $reviewCount = $reviews->count();
    $avgRating = $reviewCount ? round($reviews->avg('rating'), 1) : null;
    $hours = $operating ? $fmtTime($operating->start_time) . ' – ' . $fmtTime($operating->end_time) : null;
    $isVerified = (int) optional($library->library)->is_paid === 1;

    /* ---------- Seat map + booking data (read-only, existing services) ---------- */
    $bkTypes = collect(); $bkPlans = collect(); $bkPriceMap = []; $bkSeatsOut = []; $bkFloors = [];
    $bkTotalSeats = (int) ($total_seat ?? 0);
    try {
        $bkHourRow = \App\Models\Hour::withoutGlobalScopes()->where('branch_id', $library->id)->first();
        $bkTotalSeats = (int) ($bkHourRow->seats ?? 0);
        $bkTotalHour = (int) ($bkHourRow->hour ?? 0);
        $bkTypes = \App\Models\PlanType::withoutGlobalScopes()->where('branch_id', $library->id)->whereNull('deleted_at')
            ->orderBy('start_time')->get(['id', 'name', 'start_time', 'end_time', 'slot_hours', 'day_type_id']);
        $bkPlans = \App\Models\Plan::withoutGlobalScopes()->where('library_id', $library->library_id)->whereNull('deleted_at')
            ->orderBy('plan_id')->orderBy('id')->get(['id', 'name', 'plan_id']);
        foreach (\App\Models\PlanPrice::withoutGlobalScopes()->where('branch_id', $library->id)->whereNull('deleted_at')->get(['plan_id', 'plan_type_id', 'price']) as $pp) {
            if ($pp->price !== null && (float) $pp->price > 0) $bkPriceMap[(int) $pp->plan_id][(int) $pp->plan_type_id] = (float) $pp->price;
        }
        $bkPlans = $bkPlans->filter(fn ($p) => !empty($bkPriceMap[(int) $p->id]))->values();
        if ($bkTotalSeats > 0 && $bkTypes->isNotEmpty()) {
            $bkSeatList = app(\App\Http\Controllers\QrEntryController::class)->generateSeatNumbers($library->id);
            $bkAvail = app(\App\Services\SeatAvailabilityService::class)->getAvailablePlanTypesMap((int) $library->id, $bkTotalSeats, $bkTotalHour ?: null);
            foreach ($bkSeatList as $s) {
                $main = (int) $s['main'];
                $floorName = $s['floor_name'] ?? null;
                $bkSeatsOut[] = [
                    'n' => $main,
                    'l' => (string) ($s['floor'] ?? $main),
                    'g' => $floorName ?: '',
                    'a' => collect($bkAvail[$main] ?? [])->pluck('id')->map(fn ($v) => (int) $v)->values()->all(),
                ];
                if ($floorName && !in_array($floorName, $bkFloors, true)) $bkFloors[] = $floorName;
            }
        }
    } catch (\Throwable $e) {
        report($e);
        $bkSeatsOut = [];
    }
    $bkTypesOut = $bkTypes->map(fn ($t) => [
        'id' => (int) $t->id, 'name' => $t->name, 'd' => (int) $t->day_type_id,
        'from' => $fmtShort($t->start_time), 'to' => $fmtShort($t->end_time),
        'h' => $t->slot_hours ? (float) $t->slot_hours : null, 'icon' => $shiftIcon($t->name, $t->day_type_id),
    ])->values();
    $bkFreeSeats = collect($bkSeatsOut)->filter(fn ($s) => count($s['a']) > 0)->count();
    $canBook = !empty($library->uuid) && count($bkSeatsOut) > 0 && $bkTypes->isNotEmpty() && $bkPlans->isNotEmpty();
    $hasUpi = !empty($library->upi_id);

    // Fees table: every plan × shift that has a price
    $feeRows = $bkTypes->map(function ($t) use ($bkPlans, $bkPriceMap) {
        $prices = $bkPlans->map(fn ($p) => ['plan' => $p->name, 'price' => $bkPriceMap[(int) $p->id][(int) $t->id] ?? null])->filter(fn ($x) => $x['price'] !== null)->values();
        return ['type' => $t, 'prices' => $prices];
    })->filter(fn ($r) => $r['prices']->isNotEmpty())->values();
    if ($bkPriceMap) {
        $minPrice = collect($bkPlans)->filter(fn ($p) => (int) $p->plan_id === 1)->flatMap(fn ($p) => array_values($bkPriceMap[(int) $p->id] ?? []))->min() ?? $minPrice;
    }

    $metaDesc = \Illuminate\Support\Str::limit(trim(($library->description ? strip_tags($library->description) . ' ' : '') . 'Seats, timings, monthly fees and facilities of ' . $libName . ($place ? ' in ' . $place : '') . '. Call, WhatsApp or book your seat on Libraro.'), 158);
@endphp

@section('seo')
    <title>{{ $libName }}{{ $cityName ? ' – Study Library in ' . $cityName : '' }} | Libraro</title>
    <meta name="description" content="{{ $metaDesc }}">
    <meta name="keywords" content="{{ collect([$libName, $cityName ? 'library in ' . $cityName : null, $cityName ? 'study library ' . $cityName : null, 'self study library', 'reading room'])->filter()->implode(', ') }}">
    <link rel="canonical" href="{{ url()->current() }}" />
    <meta property="og:type" content="business.business" />
    <meta property="og:title" content="{{ $libName }}{{ $cityName ? ' – ' . $cityName : '' }} | Libraro" />
    <meta property="og:description" content="{{ $metaDesc }}" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:image" content="{{ $images->first() }}" />
    <meta name="twitter:card" content="summary_large_image">
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Library',
            'name' => $libName,
            'description' => $library->description ?: null,
            'url' => url()->current(),
            'image' => $isPlaceholderImg ? null : $images->all(),
            'telephone' => $phone ? '+91' . $phone : null,
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $library->library_address ?: null,
                'addressLocality' => $cityName,
                'addressRegion' => $stateName,
                'postalCode' => $library->library_zip ?: null,
                'addressCountry' => 'IN',
            ]),
            'geo' => ($lat && $lng) ? ['@type' => 'GeoCoordinates', 'latitude' => $lat, 'longitude' => $lng] : null,
            'aggregateRating' => $reviewCount ? ['@type' => 'AggregateRating', 'ratingValue' => $avgRating, 'reviewCount' => $reviewCount] : null,
            'priceRange' => $minPrice ? '₹' . number_format($minPrice) . '+ per month' : null,
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    <script type="application/ld+json">
        {!! json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Find my library', 'item' => route('find-my-library')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $libName, 'item' => url()->current()],
        ]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('public/css/lb-find.css') }}?v={{ @filemtime(public_path('css/lb-find.css')) }}">
@endpush

@section('content')
<div class="dp">
    <nav class="dp-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('/') }}">Home</a><x-lb-icon name="chevron-right" />
        <a href="{{ route('find-my-library') }}">Find my library</a><x-lb-icon name="chevron-right" />
        <span aria-current="page">{{ $libName }}</span>
    </nav>

    {{-- ===== Photos ===== --}}
    <div class="dp-gallery is-{{ min($images->count(), 5) }}" id="dp-gallery">
        @foreach($images->take(5) as $i => $src)
            <button type="button" class="dp-ph" data-photo="{{ $i }}" aria-label="Open photo {{ $i + 1 }} of {{ $images->count() }}">
                <img src="{{ $src }}" alt="{{ $libName }}{{ $cityName ? ', ' . $cityName : '' }} – photo {{ $i + 1 }}" @if($i === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async" width="1200" height="800" onerror="this.onerror=null;this.src='{{ $fallbackImg }}'">
            </button>
        @endforeach
        <div class="dp-gallery-ui">
            @if($images->count() > 1)
                <span class="dp-gallery-count" id="dp-gallery-count">1 / {{ $images->count() }}</span>
                <button type="button" class="dp-allphotos" data-allphotos><x-lb-icon name="images" />View all {{ $images->count() }} photos</button>
            @endif
        </div>
    </div>

    <div class="dp-layout">
        <div class="dp-main">
            {{-- ===== Title ===== --}}
            <header class="dp-title">
                <div class="dp-tags">
                    @if($isVerified)<span class="dp-tag dp-tag--ok"><x-lb-icon name="badge-check" />Verified on Libraro</span>@endif
                    @if($canBook)<span class="dp-tag dp-tag--book"><x-lb-icon name="armchair" />Online seat booking</span>@endif
                    @if($library->library_category)<span class="dp-tag"><x-lb-icon name="library-big" />{{ $library->library_category }} library</span>@endif
                    @if($avgRating)<a href="#reviews" class="dp-tag dp-tag--star"><x-lb-icon name="star" />{{ $avgRating }} · {{ $reviewCount }} {{ \Illuminate\Support\Str::plural('review', $reviewCount) }}</a>@endif
                </div>
                <h1>{{ $libName }}</h1>
                @if($addressLine)
                    <p class="dp-where"><x-lb-icon name="map-pin" /><span>{{ $addressLine }}</span></p>
                @endif
                <div class="dp-quick">
                    @if($phone)
                        <a href="tel:+91{{ $phone }}" class="dp-qbtn"><x-lb-icon name="phone" />Call</a>
                        <a href="https://wa.me/91{{ $phone }}?text={{ $waText }}" target="_blank" rel="noopener" class="dp-qbtn dp-qbtn--wa"><x-lb-brand name="whatsapp" />WhatsApp</a>
                    @endif
                    <a href="{{ $directions }}" target="_blank" rel="noopener" class="dp-qbtn"><x-lb-icon name="navigation" />Directions</a>
                    <button type="button" class="dp-qbtn" data-share><x-lb-icon name="share-2" />Share</button>
                </div>
            </header>

            {{-- ===== Section tabs ===== --}}
            <nav class="dp-tabs" aria-label="On this page">
                <div class="dp-tabs-track">
                    <a href="#overview" class="is-active">Overview</a>
                    @if(count($bkSeatsOut))<a href="#seats">Seats</a>@endif
                    <a href="#fees">Fees &amp; shifts</a>
                    @if($libFeatures->isNotEmpty())<a href="#facilities">Facilities</a>@endif
                    <a href="#location">Location</a>
                    <a href="#reviews">Reviews</a>
                </div>
            </nav>

            {{-- ===== Overview ===== --}}
            <section class="dp-sec" id="overview" aria-labelledby="sec-over">
                <h2 id="sec-over" class="lb-sr-only">Overview</h2>
                <div class="dp-facts">
                    @if($bkTotalSeats)<div class="dp-fact dp-fact--1"><i><x-lb-icon name="armchair" /></i><span>Total seats</span><b>{{ $bkTotalSeats }}</b></div>@endif
                    @if(count($bkSeatsOut))<div class="dp-fact dp-fact--2"><i><x-lb-icon name="circle-check" /></i><span>Seats with a free shift</span><b>{{ $bkFreeSeats }}</b></div>@endif
                    @if($hours)<div class="dp-fact dp-fact--3"><i><x-lb-icon name="clock-3" /></i><span>Open</span><b>{{ $hours }}</b></div>@endif
                    @if($library->working_days)<div class="dp-fact dp-fact--4"><i><x-lb-icon name="calendar-days" /></i><span>Days</span><b>{{ $library->working_days }}</b></div>@endif
                    <div class="dp-fact dp-fact--5"><i><x-lb-icon name="lock" /></i><span>Lockers</span><b>{{ (int) $library->locker_amount > 0 ? 'Available' : 'Not available' }}</b></div>
                    @if($minPrice)<div class="dp-fact dp-fact--6"><i><x-lb-icon name="indian-rupee" /></i><span>Fees from</span><b>₹{{ number_format((float) $minPrice) }}/mo</b></div>@endif
                </div>
                @if($library->description)
                    <div class="dp-about">
                        <h3>About this library</h3>
                        <p class="dp-about-text" id="dp-about-text">{{ $library->description }}</p>
                        <button type="button" class="dp-more" data-more="dp-about-text" hidden>Read more</button>
                    </div>
                @endif
            </section>

            {{-- ===== Seat map ===== --}}
            @if(count($bkSeatsOut))
                <section class="dp-sec dp-seats" id="seats" aria-labelledby="sec-seats">
                    <div class="dp-sec-head">
                        <div>
                            <h2 id="sec-seats">Pick your seat</h2>
                            <p>Live seat map. Swipe a seat to see each shift — tap <b>Book</b> on a free one.</p>
                        </div>
                        <div class="sm-legend" aria-hidden="true">
                            <span><i class="is-free"></i>All shifts free</span>
                            <span><i class="is-part"></i>Some free</span>
                            <span><i class="is-full"></i>Full</span>
                        </div>
                    </div>

                    <div class="sm-bar">
                        <div class="sm-shiftfilter" role="group" aria-label="Show seats free for">
                            <button type="button" data-sm-shift="" aria-pressed="true"><x-lb-icon name="layers" />All shifts</button>
                            @foreach($bkTypesOut as $t)
                                <button type="button" data-sm-shift="{{ $t['id'] }}" aria-pressed="false"><x-lb-icon :name="$t['icon']" />{{ $t['name'] }}<small data-sm-free="{{ $t['id'] }}"></small></button>
                            @endforeach
                        </div>
                        <div class="sm-bar-row">
                            @if(count($bkFloors) > 1)
                                <div class="sm-floors" role="group" aria-label="Floor">
                                    <button type="button" data-sm-floor="" aria-pressed="true">All floors</button>
                                    @foreach($bkFloors as $fl)
                                        <button type="button" data-sm-floor="{{ $fl }}" aria-pressed="false">{{ $fl }}</button>
                                    @endforeach
                                </div>
                            @endif
                            <label class="sm-find"><x-lb-icon name="search" /><span class="lb-sr-only">Find seat number</span><input type="number" inputmode="numeric" min="1" id="sm-find" placeholder="Seat no."></label>
                            <label class="fd-toggle sm-onlyfree"><input type="checkbox" id="sm-onlyfree"><span></span>Only free seats</label>
                        </div>
                    </div>

                    <div class="sm-grid" id="sm-grid" aria-live="polite"></div>
                    <div class="sm-foot">
                        <p class="sm-count" id="sm-count"></p>
                        <button type="button" class="lb-btn lb-btn--ghost" id="sm-more" hidden>Show all seats</button>
                    </div>
                    @unless($canBook)
                        <p class="dp-note"><x-lb-icon name="info" />Online booking isn't switched on for this library yet. Send an enquiry or call to reserve a seat.</p>
                    @endunless
                </section>
            @endif

            {{-- ===== Fees ===== --}}
            <section class="dp-sec" id="fees" aria-labelledby="sec-fees">
                <div class="dp-sec-head"><div><h2 id="sec-fees">Fees &amp; shifts</h2><p>Pick a shift that fits your study routine.</p></div></div>
                @if($feeRows->isNotEmpty())
                    <div class="dp-fees">
                        @foreach($feeRows as $k => $r)
                            @php($t = $r['type'])
                            <article class="dp-fee dp-fee--{{ ($k % 4) + 1 }}">
                                <div class="dp-fee-head">
                                    <i><x-lb-icon :name="$shiftIcon($t->name, $t->day_type_id)" /></i>
                                    <div><h3>{{ $t->name }}</h3>
                                        @if($t->start_time && $t->end_time)<span>{{ $fmtTime($t->start_time) }} – {{ $fmtTime($t->end_time) }}@if($t->slot_hours) · {{ (float) $t->slot_hours }} hrs @endif</span>@endif
                                    </div>
                                </div>
                                <ul>
                                    @foreach($r['prices'] as $pr)
                                        <li><span>{{ $pr['plan'] }}</span><b>₹{{ number_format($pr['price']) }}</b></li>
                                    @endforeach
                                </ul>
                                @if($canBook)<button type="button" class="dp-fee-cta" data-sm-jump="{{ $t->id }}">See free seats <x-lb-icon name="arrow-right" /></button>@endif
                            </article>
                        @endforeach
                    </div>
                @elseif($packages->isNotEmpty())
                    <div class="dp-fees">
                        @foreach($packages as $k => $p)
                            <article class="dp-fee dp-fee--{{ ($k % 4) + 1 }}">
                                <div class="dp-fee-head"><i><x-lb-icon :name="$shiftIcon($p->plan_type_name)" /></i>
                                    <div><h3>{{ $p->plan_type_name }}</h3>@if($p->start_time && $p->end_time)<span>{{ $fmtTime($p->start_time) }} – {{ $fmtTime($p->end_time) }}</span>@endif</div>
                                </div>
                                <ul><li><span>{{ $p->plan_name }}</span><b>₹{{ number_format((float) $p->price) }}</b></li></ul>
                            </article>
                        @endforeach
                    </div>
                @else
                    <p class="dp-note"><x-lb-icon name="info" />Fees are not listed yet. Send an enquiry or call the library to know the latest fees.</p>
                @endif
            </section>

            {{-- ===== Facilities ===== --}}
            @if($libFeatures->isNotEmpty())
                <section class="dp-sec" id="facilities" aria-labelledby="sec-fac">
                    <div class="dp-sec-head"><div><h2 id="sec-fac">Facilities</h2><p>{{ $libFeatures->count() }} facilities for a comfortable study day.</p></div></div>
                    <div class="dp-facs">
                        @foreach($libFeatures as $k => $f)
                            @php($ic = $iconFor($f->name))
                            <div class="dp-facility dp-facility--{{ ($k % 6) + 1 }}">
                                <i>@if($ic === 'circle-check' && !empty($f->image))<img src="{{ asset('public/' . $f->image) }}" alt="" loading="lazy">@else<x-lb-icon :name="$ic" />@endif</i>
                                <span>{{ $f->name }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ===== Location ===== --}}
            <section class="dp-sec" id="location" aria-labelledby="sec-loc">
                <div class="dp-sec-head"><div><h2 id="sec-loc">Location</h2></div></div>
                <div class="dp-loc">
                    @if($mapEmbed)
                        <iframe src="{{ $mapEmbed }}" title="Map showing {{ $libName }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                    @endif
                    <div class="dp-loc-row">
                        <i><x-lb-icon name="map-pin" /></i>
                        <p><b>{{ $libName }}</b><span>{{ $addressLine ?: 'Address not added yet' }}</span></p>
                        <a href="{{ $directions }}" target="_blank" rel="noopener" class="lb-btn"><x-lb-icon name="navigation" size="16" />Get directions</a>
                    </div>
                </div>
            </section>

            {{-- ===== Reviews ===== --}}
            <section class="dp-sec" id="reviews" aria-labelledby="sec-rev">
                <div class="dp-sec-head"><div><h2 id="sec-rev">Student reviews</h2></div></div>
                <div class="dp-rev-summary">
                    @if($reviewCount)
                        <b>{{ $avgRating }}</b>
                        <div>
                            <div class="lb-stars" role="img" aria-label="{{ $avgRating }} out of 5">@for($s = 1; $s <= 5; $s++)<x-lb-icon name="star" class="{{ $s > round($avgRating) ? 'is-off' : '' }}" />@endfor</div>
                            <span>{{ $reviewCount }} {{ \Illuminate\Support\Str::plural('review', $reviewCount) }}</span>
                        </div>
                    @else
                        <div><span>No reviews yet. Studied here? Be the first to help other students.</span></div>
                    @endif
                    <button type="button" class="lb-btn lb-btn--ghost" data-dialog="lb-review-dialog"><x-lb-icon name="pen-line" size="16" />Write a review</button>
                </div>
                @if($reviewCount)
                    <div class="dp-revs">
                        @foreach($reviews->sortByDesc('created_at')->take(10) as $r)
                            @php($rName = $r->learner ? $r->learner->name : ($r->name ?: 'Student'))
                            <article class="dp-rev">
                                <div class="dp-rev-top">
                                    <div class="dp-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($rName, 0, 1)) }}</div>
                                    <div>
                                        <b>{{ $rName }}</b>
                                        <div class="lb-stars" role="img" aria-label="{{ (int) $r->rating }} out of 5">@for($s = 1; $s <= 5; $s++)<x-lb-icon name="star" class="{{ $s > (int) $r->rating ? 'is-off' : '' }}" />@endfor</div>
                                    </div>
                                    @if($r->created_at)<time datetime="{{ $r->created_at->toDateString() }}">{{ $r->created_at->format('M Y') }}</time>@endif
                                </div>
                                <p>{{ $r->comments }}</p>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        {{-- ===== Sticky booking panel (desktop) ===== --}}
        <aside class="dp-side" aria-label="Book or contact">
            <div class="dp-book">
                <div class="dp-book-price">
                    @if($minPrice)
                        <span>Fees from</span>
                        <b>₹{{ number_format((float) $minPrice) }}<small> / month</small></b>
                    @else
                        <span>Fees</span><b>Ask the library</b>
                    @endif
                </div>
                @if(count($bkSeatsOut))
                    <div class="dp-book-live"><i></i><span><b>{{ $bkFreeSeats }}</b> of {{ count($bkSeatsOut) }} seats have a free shift</span></div>
                @endif
                @if($canBook)
                    <a href="#seats" class="lb-btn lb-btn--lg lb-btn--block" data-sm-jump=""><x-lb-icon name="armchair" size="18" />Pick a seat &amp; book</a>
                    <button type="button" class="lb-btn lb-btn--ghost lb-btn--block" data-dialog="lb-enquiry-dialog">Ask a question</button>
                @else
                    <button type="button" class="lb-btn lb-btn--lg lb-btn--block" data-dialog="lb-enquiry-dialog">Send an enquiry</button>
                @endif
                <div class="dp-book-row">
                    @if($phone)
                        <a href="tel:+91{{ $phone }}"><x-lb-icon name="phone" />Call</a>
                        <a href="https://wa.me/91{{ $phone }}?text={{ $waText }}" target="_blank" rel="noopener"><x-lb-brand name="whatsapp" />WhatsApp</a>
                    @endif
                    <a href="{{ $directions }}" target="_blank" rel="noopener"><x-lb-icon name="navigation" />Map</a>
                </div>
                <ul class="dp-book-points">
                    <li><x-lb-icon name="shield-check" />No booking fee from Libraro</li>
                    <li><x-lb-icon name="indian-rupee" />Pay online or at the library</li>
                    <li><x-lb-icon name="circle-check" />Library confirms when you visit</li>
                </ul>
            </div>
        </aside>
    </div>
</div>

{{-- ===== Nearby libraries ===== --}}
<section class="dp-nearby" id="lb-nearby" aria-labelledby="sec-near" hidden>
    <div class="fd-sec-head"><div><h2 id="sec-near">More libraries{{ $cityName ? ' in ' . $cityName : ' nearby' }}</h2></div>
        <div class="fd-arrows"><button type="button" class="fd-arrow" data-dp-prev aria-label="Previous"><x-lb-icon name="chevron-left" /></button><button type="button" class="fd-arrow" data-dp-next aria-label="Next"><x-lb-icon name="chevron-right" /></button></div>
    </div>
    <div class="dp-nearby-track" id="lb-nearby-grid"></div>
</section>
<div class="lb-section lb-section--flush-top"></div>

{{-- ===== Mobile action bar ===== --}}
<div class="dp-mbar" aria-label="Book or contact">
    <div class="dp-mbar-price">
        <span>{{ $minPrice ? 'Fees from' : 'Fees' }}</span>
        <b>{{ $minPrice ? '₹' . number_format((float) $minPrice) . '/mo' : 'Ask library' }}</b>
    </div>
    @if($phone)<a class="fd-round" href="tel:+91{{ $phone }}" aria-label="Call library"><x-lb-icon name="phone" /></a>@endif
    @if($canBook)
        <a href="#seats" class="lb-btn" data-sm-jump="">Book a seat</a>
    @else
        <button type="button" class="lb-btn" data-dialog="lb-enquiry-dialog">Enquire</button>
    @endif
</div>

{{-- ===== Booking modal ===== --}}
@if($canBook)
<dialog class="bk" id="bk" aria-labelledby="bk-title">
    <div class="bk-sheet">
        <header class="bk-head">
            <div class="bk-head-l">
                <span class="bk-seatchip" id="bk-seatchip"><x-lb-icon name="armchair" /><b data-bk="seat"></b></span>
                <h2 id="bk-title">Book your seat</h2>
                <p>{{ $libName }}</p>
            </div>
            <button type="button" class="bk-x" data-bk-close aria-label="Close"><x-lb-icon name="x" /></button>
        </header>
        <ol class="bk-steps" aria-label="Booking steps">
            <li class="is-on" data-step-dot="1"><i>1</i><span>Plan</span></li>
            <li data-step-dot="2"><i>2</i><span>Your details</span></li>
            <li data-step-dot="3"><i>3</i><span>Payment</span></li>
        </ol>

        <form class="bk-body" id="bk-form" novalidate>
            {{-- Step 1: shift, duration, start --}}
            <div class="bk-step" data-step="1">
                <h3 class="bk-q">Which shift?</h3>
                <div class="bk-shifts" id="bk-shifts" role="radiogroup" aria-label="Shift"></div>
                <h3 class="bk-q">For how long?</h3>
                <div class="bk-plans" id="bk-plans" role="radiogroup" aria-label="Plan"></div>
                <h3 class="bk-q">Starting from</h3>
                <div class="bk-dates" role="radiogroup" aria-label="Start date">
                    <button type="button" data-date="0" aria-pressed="true"><small>Today</small><b data-bk-day="0"></b></button>
                    <button type="button" data-date="1" aria-pressed="false"><small>Tomorrow</small><b data-bk-day="1"></b></button>
                    <label class="bk-date-pick"><small>Pick a date</small><input type="date" id="bk-date" aria-label="Pick a start date"></label>
                </div>
            </div>

            {{-- Step 2: details --}}
            <div class="bk-step" data-step="2" hidden>
                <h3 class="bk-q">Who is studying?</h3>
                <p class="bk-hint">The library uses these details to confirm your seat.</p>
                <div class="lb-field">
                    <label class="lb-label" for="bk-name">Full name</label>
                    <div class="lb-input"><x-lb-icon name="user-round" /><input type="text" id="bk-name" name="name" class="char-only" maxlength="191" autocomplete="name" placeholder="Your full name" required></div>
                    <div class="lb-error" data-err="name" hidden></div>
                </div>
                <div class="lb-field">
                    <label class="lb-label" for="bk-mobile">Mobile number</label>
                    <div class="lb-input"><span class="bk-cc">+91</span><input type="tel" id="bk-mobile" name="mobile" class="digit-only" data-maxlength="10" minlength="10" pattern="[0-9]{10}" inputmode="numeric" autocomplete="tel-national" placeholder="98765 43210" required></div>
                    <div class="lb-error" data-err="mobile" hidden></div>
                </div>
                <div class="lb-field">
                    <label class="lb-label" for="bk-email">Email <small>(optional)</small></label>
                    <div class="lb-input"><x-lb-icon name="mail" /><input type="email" id="bk-email" name="email" autocomplete="email" placeholder="you@example.com"></div>
                    <div class="lb-error" data-err="email" hidden></div>
                </div>
            </div>

            {{-- Step 3: payment --}}
            <div class="bk-step" data-step="3" hidden>
                <h3 class="bk-q">How will you pay?</h3>
                <div class="bk-pay" role="radiogroup" aria-label="Payment mode">
                    <label class="bk-payopt">
                        <input type="radio" name="payment_mode" value="offline" checked>
                        <span class="bk-payopt-in">
                            <i><x-lb-icon name="building" /></i>
                            <span><b>Pay at the library</b><small>Cash or UPI at the desk when you visit</small></span>
                            <em class="bk-radio"></em>
                        </span>
                    </label>
                    @if($hasUpi)
                        <label class="bk-payopt">
                            <input type="radio" name="payment_mode" value="online">
                            <span class="bk-payopt-in">
                                <i><x-lb-icon name="qr-code" /></i>
                                <span><b>Pay online now</b><small>Scan the library's UPI QR and share the screenshot</small></span>
                                <em class="bk-radio"></em>
                            </span>
                        </label>
                    @endif
                </div>
                <div class="bk-next">
                    <h4>What happens next</h4>
                    <ol>
                        <li><i><x-lb-icon name="send" /></i><span><b>We send your request</b>to {{ $libName }} right away.</span></li>
                        <li><i><x-lb-icon name="navigation" /></i><span><b>Visit on your start date</b>with a photo ID. Pay at the desk if you chose that.</span></li>
                        <li><i><x-lb-icon name="badge-check" /></i><span><b>The library confirms your seat</b>and you can start studying.</span></li>
                    </ol>
                </div>
                <label class="lb-check bk-terms">
                    <input type="checkbox" id="bk-agree" required>
                    <span>I agree to the <a href="{{ route('term-and-condition') }}" target="_blank" rel="noopener">Terms</a> and the library's rules.</span>
                </label>
                <div class="lb-error" data-err="agree" hidden></div>
                <div class="bk-alert" id="bk-alert" role="alert" hidden></div>
            </div>
        </form>

        {{-- Online payment screen --}}
        <div class="bk-screen" data-screen="pay" hidden>
            <div class="bk-payqr">
                <span class="bk-amount-label">Pay to {{ $libName }}</span>
                <b class="bk-amount" data-bk="amount"></b>
                <div class="bk-qrbox" id="bk-qrbox"></div>
                <a class="lb-btn lb-btn--block bk-upi" id="bk-upi" href="#"><x-lb-icon name="smartphone" size="18" />Open a UPI app</a>
                <p class="bk-hint">After paying, upload the payment screenshot so the library can match it.</p>
                <label class="bk-upload">
                    <input type="file" id="bk-shot" accept="image/png,image/jpeg,image/webp">
                    <i><x-lb-icon name="images" /></i>
                    <span><b id="bk-shot-name">Choose screenshot</b><small>PNG or JPG, up to 2 MB</small></span>
                </label>
                <div class="bk-alert" id="bk-pay-alert" role="alert" hidden></div>
            </div>
        </div>

        {{-- Success screen --}}
        <div class="bk-screen bk-done" data-screen="done" hidden>
            <div class="bk-check"><svg viewBox="0 0 52 52" aria-hidden="true"><circle cx="26" cy="26" r="24" /><path d="M15 27l7 7 15-15" /></svg></div>
            <h3 id="bk-done-title">Seat request sent!</h3>
            <p id="bk-done-sub">Show this at the library desk on your start date.</p>
            <div class="bk-ticket">
                <div class="bk-ticket-top"><span>{{ $libName }}</span><b data-bk="status">Pending confirmation</b></div>
                <dl>
                    <div><dt>Seat</dt><dd data-bk="seat"></dd></div>
                    <div><dt>Shift</dt><dd data-bk="shift"></dd></div>
                    <div><dt>Plan</dt><dd data-bk="plan"></dd></div>
                    <div><dt>Starts</dt><dd data-bk="start"></dd></div>
                    <div><dt>Amount</dt><dd data-bk="amount"></dd></div>
                    <div><dt>Payment</dt><dd data-bk="pay"></dd></div>
                </dl>
            </div>
            <ul class="bk-remember">
                <li><x-lb-icon name="id-card" />Carry a photo ID (Aadhaar, college ID or similar).</li>
                <li><x-lb-icon name="clock-3" />Reach on your start date during library hours{{ $hours ? ' (' . $hours . ')' : '' }}.</li>
                <li><x-lb-icon name="badge-check" />Your seat is final once the library confirms it.</li>
            </ul>
            <a href="#" class="lb-btn lb-btn--block bk-paylink" id="bk-paylink" target="_blank" rel="noopener" hidden><x-lb-icon name="qr-code" size="18" />Open payment page</a>
            <div class="bk-done-act">
                <a href="{{ $directions }}" target="_blank" rel="noopener" class="lb-btn lb-btn--ghost"><x-lb-icon name="navigation" size="16" />Directions</a>
                @if($phone)<a href="#" target="_blank" rel="noopener" class="lb-btn lb-btn--ghost" id="bk-wa"><x-lb-brand name="whatsapp" />Tell the library</a>@endif
            </div>
        </div>

        <footer class="bk-foot" id="bk-foot">
            <div class="bk-total"><small>Total</small><b data-bk="amount">—</b><span data-bk="summary"></span></div>
            <button type="button" class="lb-btn lb-btn--ghost bk-back" id="bk-back" hidden><x-lb-icon name="arrow-left" size="18" /><span class="lb-sr-only">Back</span></button>
            <button type="button" class="lb-btn lb-btn--lg bk-nextbtn" id="bk-next"><span>Continue</span><x-lb-icon name="arrow-right" size="18" /></button>
        </footer>
    </div>
</dialog>
@endif

{{-- ===== Enquiry dialog (same endpoint: submit.library.inquiry) ===== --}}
<dialog class="lb-dialog" id="lb-enquiry-dialog" aria-labelledby="enq-title">
    <div class="lb-dialog-body" data-lb-form-card>
        <button type="button" class="lb-dialog-close" aria-label="Close"><x-lb-icon name="x" size="18" /></button>
        <h3 id="enq-title">Ask {{ $libName }}</h3>
        <p class="lb-dialog-sub">The library will call you back about seats and fees.</p>
        <form id="submitlibraryEnquiry" class="lb-form" data-lb-ajax="{{ route('submit.library.inquiry') }}" novalidate>
            @csrf
            <input type="hidden" name="branch_id" value="{{ $library->id }}">
            <div class="lb-field">
                <label class="lb-label" for="enq_name">Your name</label>
                <div class="lb-input"><x-lb-icon name="user-round" /><input type="text" id="enq_name" name="name" class="char-only" maxlength="255" placeholder="Full name" autocomplete="name" required></div>
            </div>
            <div class="lb-field">
                <label class="lb-label" for="enq_mobile">Mobile number</label>
                <div class="lb-input"><x-lb-icon name="smartphone" /><input type="tel" id="enq_mobile" name="mobile" class="digit-only" data-maxlength="10" minlength="10" pattern="[0-9]{10}" title="Enter a valid 10-digit mobile number." inputmode="numeric" placeholder="98765 43210" autocomplete="tel-national" required></div>
            </div>
            @if(!empty($libraryplantype) && count($libraryplantype))
                <div class="lb-field">
                    <label class="lb-label" for="enq_shift">Preferred shift</label>
                    <div class="lb-input"><x-lb-icon name="clock-3" />
                        <select id="enq_shift" name="shift_time" class="no-validate">
                            <option value="">Any shift</option>
                            @foreach($libraryplantype as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif
            <div class="lb-field">
                <label class="lb-label" for="enq_msg">Message</label>
                <div class="lb-input lb-input--area"><textarea id="enq_msg" name="enquiry" rows="3" maxlength="1000" required>Hi, I want a seat. Please share availability and fees.</textarea></div>
            </div>
            <button type="submit" class="lb-btn lb-btn--lg lb-btn--block" data-loading-text="Sending…"><span class="lb-btn-label">Send enquiry</span><x-lb-icon name="send" size="18" /></button>
        </form>
        <div class="lb-demo-success lb-form-success" hidden>
            <div class="lb-ok"><x-lb-icon name="circle-check-big" size="30" /></div>
            <h3>Enquiry sent!</h3>
            <p>{{ $libName }} will contact you soon.</p>
        </div>
    </div>
</dialog>

{{-- ===== Review dialog (same endpoint: submit.review) ===== --}}
<dialog class="lb-dialog" id="lb-review-dialog" aria-labelledby="rev-title">
    <div class="lb-dialog-body" data-lb-form-card>
        <button type="button" class="lb-dialog-close" aria-label="Close"><x-lb-icon name="x" size="18" /></button>
        <h3 id="rev-title">Review {{ $libName }}</h3>
        <p class="lb-dialog-sub">Your honest review helps other students choose.</p>
        <form id="reviewForm" class="lb-form" data-lb-ajax="{{ route('submit.review') }}" novalidate>
            @csrf
            {{-- Reviews are shown by library (library_id), so they are saved with the same id. --}}
            <input type="hidden" name="library_id" value="{{ $library->library_id }}">
            <div class="lb-field">
                <span class="lb-label" id="rate-label">Your rating</span>
                <div class="lb-rate" role="radiogroup" aria-labelledby="rate-label">
                    @for($s = 5; $s >= 1; $s--)
                        <input type="radio" name="rating" id="rate{{ $s }}" value="{{ $s }}" data-msg="Please choose a rating." @if($s === 5) required @endif>
                        <label for="rate{{ $s }}" aria-label="{{ $s }} star{{ $s > 1 ? 's' : '' }}"><x-lb-icon name="star" /></label>
                    @endfor
                </div>
            </div>
            <div class="lb-field">
                <label class="lb-label" for="rev_name">Your name</label>
                <div class="lb-input"><x-lb-icon name="user-round" /><input type="text" id="rev_name" name="name" class="char-only" maxlength="255" placeholder="Full name" autocomplete="name" required></div>
            </div>
            <div class="lb-field">
                <label class="lb-label" for="rev_text">Your review</label>
                <div class="lb-input lb-input--area"><textarea id="rev_text" name="comments" rows="4" placeholder="How is the seating, AC, Wi-Fi, cleanliness and silence?" required></textarea></div>
            </div>
            <button type="submit" class="lb-btn lb-btn--lg lb-btn--block" data-loading-text="Posting…"><span class="lb-btn-label">Post review</span><x-lb-icon name="send" size="18" /></button>
        </form>
        <div class="lb-demo-success lb-form-success" hidden>
            <div class="lb-ok"><x-lb-icon name="circle-check-big" size="30" /></div>
            <h3>Thank you!</h3>
            <p>Your review has been posted. Refresh the page to see it.</p>
        </div>
    </div>
</dialog>

{{-- ===== All photos (grid) + lightbox ===== --}}
<dialog class="dp-photos" id="dp-photos" aria-labelledby="dp-photos-title">
    <header class="dp-photos-head">
        <button type="button" class="fd-arrow" data-photos-close aria-label="Close photos"><x-lb-icon name="arrow-left" /></button>
        <div><h2 id="dp-photos-title">{{ $libName }}</h2><span>{{ $images->count() }} {{ \Illuminate\Support\Str::plural('photo', $images->count()) }}</span></div>
    </header>
    <div class="dp-photos-grid">
        @foreach($images as $i => $src)
            <button type="button" data-photo="{{ $i }}" aria-label="Open photo {{ $i + 1 }}"><img src="{{ $src }}" alt="{{ $libName }} – photo {{ $i + 1 }}" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='{{ $fallbackImg }}'"></button>
        @endforeach
    </div>
</dialog>
<dialog class="dp-lightbox" id="dp-lightbox" aria-label="Photo viewer">
    <button type="button" class="dp-lb-close" data-lb-close aria-label="Close"><x-lb-icon name="x" /></button>
    <span class="dp-lb-count" id="dp-lb-count"></span>
    @if($images->count() > 1)
        <button type="button" class="dp-lb-nav" data-lb-dir="-1" aria-label="Previous photo"><x-lb-icon name="chevron-left" /></button>
        <button type="button" class="dp-lb-nav" data-lb-dir="1" aria-label="Next photo"><x-lb-icon name="chevron-right" /></button>
    @endif
    <div class="dp-lb-track" id="dp-lb-track">
        @foreach($images as $i => $src)
            <figure><img src="{{ $src }}" alt="{{ $libName }} – photo {{ $i + 1 }}" loading="lazy" decoding="async"></figure>
        @endforeach
    </div>
    <div class="dp-lb-thumbs" id="dp-lb-thumbs">
        @foreach($images as $i => $src)
            <button type="button" data-lb-go="{{ $i }}" aria-label="Photo {{ $i + 1 }}"><img src="{{ $src }}" alt="" loading="lazy"></button>
        @endforeach
    </div>
</dialog>
@endsection

@push('scripts')
    <script>
        window.LB_DIR = {
            listUrl: @json(route('get-libraries')),
            base: @json(url('/')),
            assetBase: @json(asset('public')),
            icons: @json(asset('public/img/lb-icons.svg') . '?v=' . @filemtime(public_path('img/lb-icons.svg'))),
            features: @json(collect($features ?? [])->mapWithKeys(fn ($f) => [(string) $f->id => ['n' => $f->name, 'i' => $iconFor($f->name)]])),
            extra: {},
            nearby: { city: @json($library->city_id), self: @json($library->id) }
        };
        window.LB_SEATS = {
            canBook: @json($canBook),
            branchId: @json($library->id),
            storeUrl: @json($canBook ? route('booking.store', $library->uuid) : null),
            priceUrl: @json(route('get.plan.price')),
            csrf: @json(csrf_token()),
            hasUpi: @json($hasUpi),
            name: @json($libName),
            phone: @json($phone),
            types: @json($bkTypesOut),
            plans: @json($bkPlans->map(fn ($p) => ['id' => (int) $p->id, 'name' => $p->name])->values()),
            prices: @json((object) $bkPriceMap),
            seats: @json($bkSeatsOut),
            pageSize: 24
        };
    </script>
    <script src="{{ asset('public/js/lb-find.js') }}?v={{ @filemtime(public_path('js/lb-find.js')) }}" defer></script>
    <script src="{{ asset('public/js/lb-libpage.js') }}?v={{ @filemtime(public_path('js/lb-libpage.js')) }}" defer></script>
@endpush
