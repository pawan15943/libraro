@extends('lb.layouts.site')

{{--
    Library details (public, students) — route: libraryDetail (/{slug}) → SiteController@libraryDetail.
    Data: $library (Branch + city, state, library), $features, $our_package, $total_seat, $operating,
          $learnerFeedback, $libraryplantype.
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
    if ($images->isEmpty()) {
        $images = collect([asset('public/img/site/slide-library-1600.webp')]);
        $isPlaceholderImg = true;
    }
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
    $shiftIcon = function ($name) {
        $n = strtolower($name ?? '');
        return str_contains($n, 'night') ? 'moon' : (str_contains($n, 'first') || str_contains($n, 'morning') ? 'sunrise' : (str_contains($n, 'second') || str_contains($n, 'evening') ? 'sunset' : 'sun'));
    };
    $fmtTime = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('g:i A') : null;

    $packages = collect($our_package ?? [])->filter(fn ($p) => $p->price !== null);
    $minPrice = $packages->min('price');
    $phone = substr(preg_replace('/\D/', '', (string) $library->mobile), -10);
    $phone = strlen($phone) === 10 ? $phone : null;
    $waText = rawurlencode('Hi, I found ' . $libName . ' on Libraro. Is a study seat available?');
    $canBook = !empty($library->uuid) && !empty($library->upi_id);
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
            'image' => empty($isPlaceholderImg) ? $images->all() : null,
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
    <link rel="stylesheet" href="{{ asset('public/css/lb-directory.css') }}?v={{ @filemtime(public_path('css/lb-directory.css')) }}">
@endpush

@section('content')
<div class="lb-lib-page lb-lib-page-pad">
    <nav class="lb-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('/') }}">Home</a><x-lb-icon name="chevron-right" />
        <a href="{{ route('find-my-library') }}">Find my library</a><x-lb-icon name="chevron-right" />
        <span aria-current="page">{{ $libName }}</span>
    </nav>

    {{-- ===== Photos ===== --}}
    <div class="lb-gallery-wrap">
        <div class="lb-gallery is-{{ min($images->count(), 3) }}" id="lb-gallery">
            @foreach($images->take(3) as $i => $src)
                <button type="button" data-open="{{ $i }}" aria-label="Open photo {{ $i + 1 }} of {{ $images->count() }}">
                    <img src="{{ $src }}" alt="{{ $libName }}{{ $cityName ? ', ' . $cityName : '' }} – photo {{ $i + 1 }}" @if($i === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async" width="1200" height="800" onerror="this.onerror=null;this.src='{{ asset('public/img/site/slide-library-800.webp') }}'">
                </button>
            @endforeach
            @if($images->count() > 1)
                <button type="button" class="lb-gallery-all" data-open="0"><x-lb-icon name="images" />See all {{ $images->count() }} photos</button>
            @endif
        </div>
        @if($images->count() > 1)<span class="lb-gallery-count" id="lb-gallery-count">1 / {{ $images->count() }}</span>@endif
    </div>

    <div class="lb-lib-layout">
        <div>
            {{-- ===== Title ===== --}}
            <header class="lb-lib-titlebar">
                <div class="lb-tags-row">
                    @if($isVerified)<span class="lb-tag lb-tag--ok"><x-lb-icon name="badge-check" />Verified on Libraro</span>@endif
                    @if($library->library_category)<span class="lb-tag"><x-lb-icon name="library-big" />{{ $library->library_category }} library</span>@endif
                    @if($avgRating)<span class="lb-tag"><x-lb-icon name="star" />{{ $avgRating }} · {{ $reviewCount }} {{ \Illuminate\Support\Str::plural('review', $reviewCount) }}</span>@endif
                </div>
                <h1>{{ $libName }}</h1>
                @if($addressLine)
                    <p class="lb-lib-where"><x-lb-icon name="map-pin" /><span>{{ $addressLine }}</span></p>
                @endif
            </header>

            {{-- ===== Quick facts ===== --}}
            <div class="lb-facts">
                @if($total_seat)<div class="lb-fact"><x-lb-icon name="armchair" /><span>Seats</span><b>{{ $total_seat }} seats</b></div>@endif
                @if($hours)<div class="lb-fact"><x-lb-icon name="clock-3" /><span>Open</span><b>{{ $hours }}</b></div>@endif
                @if($library->working_days)<div class="lb-fact"><x-lb-icon name="calendar-days" /><span>Days</span><b>{{ $library->working_days }}</b></div>@endif
                <div class="lb-fact"><x-lb-icon name="lock" /><span>Lockers</span><b>{{ (int) $library->locker_amount > 0 ? 'Available' : 'Not available' }}</b></div>
            </div>

            {{-- ===== About ===== --}}
            @if($library->description)
                <section class="lb-lib-sec" id="about" aria-labelledby="sec-about">
                    <h2 id="sec-about">About this library</h2>
                    <p>{{ $library->description }}</p>
                </section>
            @endif

            {{-- ===== Plans & timings ===== --}}
            <section class="lb-lib-sec" id="plans" aria-labelledby="sec-plans">
                <h2 id="sec-plans">Shifts &amp; fees <small>Monthly plans</small></h2>
                @if($packages->isNotEmpty())
                    <div class="lb-plans-list">
                        @foreach($packages as $p)
                            <div class="lb-plan-card">
                                <div class="lb-plan-shift"><i><x-lb-icon :name="$shiftIcon($p->plan_type_name)" /></i>{{ $p->plan_type_name }}</div>
                                @if($p->start_time && $p->end_time)
                                    <div class="lb-plan-time">{{ $fmtTime($p->start_time) }} – {{ $fmtTime($p->end_time) }}@if($p->slot_hours) · {{ $p->slot_hours }} hrs @endif</div>
                                @endif
                                <div class="lb-plan-amt">₹{{ number_format((float) $p->price) }} <small>/ month</small></div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p>Fees are not listed yet. Send an enquiry or call the library to know the latest fees.</p>
                @endif
            </section>

            {{-- ===== Facilities ===== --}}
            @if($libFeatures->isNotEmpty())
                <section class="lb-lib-sec" id="facilities" aria-labelledby="sec-fac">
                    <h2 id="sec-fac">Facilities <small>{{ $libFeatures->count() }} available</small></h2>
                    <div class="lb-facilities">
                        @foreach($libFeatures as $f)
                            @php($ic = $iconFor($f->name))
                            <div class="lb-facility">
                                <i>@if($ic === 'circle-check' && !empty($f->image))<img src="{{ asset('public/' . $f->image) }}" alt="" loading="lazy">@else<x-lb-icon :name="$ic" />@endif</i>
                                {{ $f->name }}
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ===== Location ===== --}}
            <section class="lb-lib-sec" id="location" aria-labelledby="sec-loc">
                <h2 id="sec-loc">Location</h2>
                <div class="lb-loc-card">
                    @if($mapEmbed)
                        <iframe src="{{ $mapEmbed }}" title="Map showing {{ $libName }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                    @endif
                    <div class="lb-loc-row">
                        <p><x-lb-icon name="map-pin" /><span>{{ $addressLine ?: 'Address not added yet' }}</span></p>
                        <a href="{{ $directions }}" target="_blank" rel="noopener" class="lb-btn lb-btn--ghost"><x-lb-icon name="navigation" size="16" />Get directions</a>
                    </div>
                </div>
            </section>

            {{-- ===== Reviews ===== --}}
            <section class="lb-lib-sec" id="reviews" aria-labelledby="sec-rev">
                <h2 id="sec-rev">Student reviews</h2>
                <div class="lb-rev-summary">
                    @if($reviewCount)
                        <b>{{ $avgRating }}</b>
                        <div>
                            <div class="lb-stars" role="img" aria-label="{{ $avgRating }} out of 5">@for($s = 1; $s <= 5; $s++)<x-lb-icon name="star" class="{{ $s > round($avgRating) ? 'is-off' : '' }}" />@endfor</div>
                            <span>{{ $reviewCount }} {{ \Illuminate\Support\Str::plural('review', $reviewCount) }}</span>
                        </div>
                    @else
                        <div><b class="lb-sr-only">No reviews</b><span>No reviews yet. Studied here? Be the first to help other students.</span></div>
                    @endif
                    <button type="button" class="lb-btn lb-btn--ghost" data-dialog="lb-review-dialog"><x-lb-icon name="pen-line" size="16" />Write a review</button>
                </div>
                @if($reviewCount)
                    <div class="lb-rev-list">
                        @foreach($reviews->sortByDesc('created_at')->take(10) as $r)
                            @php($rName = $r->learner ? $r->learner->name : ($r->name ?: 'Student'))
                            <article class="lb-rev">
                                <div class="lb-rev-top">
                                    <div class="lb-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($rName, 0, 1)) }}</div>
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
        <aside class="lb-book" aria-label="Book or contact">
            <div class="lb-book-price">
                @if($minPrice)
                    <span>Fees from</span>
                    <b>₹{{ number_format((float) $minPrice) }} <small>/ month</small></b>
                @else
                    <span>Fees</span>
                    <b>Ask the library</b>
                @endif
            </div>
            @if($canBook)
                <a href="{{ route('booking.form', $library->uuid) }}" class="lb-btn">Book a seat <x-lb-icon name="arrow-right" size="18" /></a>
                <button type="button" class="lb-btn lb-btn--ghost" data-dialog="lb-enquiry-dialog">Send an enquiry</button>
            @else
                <button type="button" class="lb-btn" data-dialog="lb-enquiry-dialog">Send an enquiry</button>
            @endif
            <div class="lb-book-row">
                @if($phone)
                    <a href="tel:+91{{ $phone }}"><x-lb-icon name="phone" />Call</a>
                    <a href="https://wa.me/91{{ $phone }}?text={{ $waText }}" target="_blank" rel="noopener"><x-lb-brand name="whatsapp" />WhatsApp</a>
                @endif
                <a href="{{ $directions }}" target="_blank" rel="noopener"><x-lb-icon name="navigation" />Directions</a>
            </div>
            <p class="lb-book-note"><x-lb-icon name="shield-check" />No booking fee from Libraro. You pay the library directly.</p>
        </aside>
    </div>
</div>

{{-- ===== Nearby libraries ===== --}}
<section class="lb-nearby" id="lb-nearby" aria-labelledby="sec-near" hidden>
    <h2 class="lb-h2 lb-h2--sm" id="sec-near">More libraries{{ $cityName ? ' in ' . $cityName : ' nearby' }}</h2>
    <div class="lb-lib-grid" id="lb-nearby-grid"></div>
</section>
<div class="lb-section lb-section--flush-top"></div>

{{-- ===== Mobile action bar ===== --}}
<div class="lb-mbar" aria-label="Book or contact">
    <div class="lb-mbar-price">
        <span>{{ $minPrice ? 'Fees from' : 'Fees' }}</span>
        <b>{{ $minPrice ? '₹' . number_format((float) $minPrice) . '/mo' : 'Ask library' }}</b>
    </div>
    @if($phone)<a class="lb-round" href="tel:+91{{ $phone }}" aria-label="Call library"><x-lb-icon name="phone" /></a>@endif
    @if($canBook)
        <a href="{{ route('booking.form', $library->uuid) }}" class="lb-btn">Book a seat</a>
    @else
        <button type="button" class="lb-btn" data-dialog="lb-enquiry-dialog">Enquire</button>
    @endif
</div>

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

{{-- ===== Photo lightbox ===== --}}
<dialog class="lb-lightbox" id="lb-lightbox" aria-label="Photos of {{ $libName }}">
    <button type="button" class="lb-lightbox-close" aria-label="Close photos"><x-lb-icon name="x" /></button>
    <span class="lb-lightbox-count" id="lb-lightbox-count"></span>
    @if($images->count() > 1)
        <button type="button" class="lb-lightbox-nav" data-dir="-1" aria-label="Previous photo"><x-lb-icon name="chevron-left" /></button>
        <button type="button" class="lb-lightbox-nav" data-dir="1" aria-label="Next photo"><x-lb-icon name="chevron-right" /></button>
    @endif
    <div class="lb-lightbox-track" id="lb-lightbox-track">
        @foreach($images as $i => $src)
            <figure><img src="{{ $src }}" alt="{{ $libName }} – photo {{ $i + 1 }}" loading="lazy" decoding="async"></figure>
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
            nearby: { city: @json($library->city_id), self: @json($library->id) }
        };
    </script>
    <script src="{{ asset('public/js/lb-directory.js') }}?v={{ @filemtime(public_path('js/lb-directory.js')) }}" defer></script>
    <script src="{{ asset('public/js/lb-library.js') }}?v={{ @filemtime(public_path('js/lb-library.js')) }}" defer></script>
@endpush
