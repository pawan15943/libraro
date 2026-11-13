@extends('lb.layouts.site')
@php
    extract(require resource_path('views/lb/i18n/boot.php')); // website language: lb/i18n/boot.php
@endphp

{{--
    Find my library — student-first directory (route: find-my-library → SiteController@searchLibrary).
    SEO stays DB-driven via lb.layouts.site (pages table, route "find-my-library").
    Results come from the SAME endpoints as before: route('get-libraries') and route('getLibrariesLocations').
    Extra, read-only facts per library (fees from, shifts, online booking) are read here so students can
    filter by what they actually need. Nothing is written.
--}}

@php
    $lbIconFor = function ($name) {
        $n = ' ' . strtolower($name);
        foreach ([
            'wifi' => 'wifi', 'wi-fi' => 'wifi', 'internet' => 'wifi', 'air' => 'snowflake', ' ac' => 'snowflake', 'cool' => 'snowflake',
            'lamp' => 'lamp-desk', 'light' => 'lamp-desk', 'power' => 'zap', 'backup' => 'zap', 'invert' => 'zap', 'generator' => 'zap',
            'water' => 'droplet', 'ro ' => 'droplet', 'locker' => 'lock', 'cctv' => 'cctv', 'camera' => 'cctv', 'security' => 'cctv',
            'parking' => 'car', 'washroom' => 'bath', 'toilet' => 'bath', 'news' => 'newspaper', 'magazine' => 'newspaper',
            'charging' => 'plug-zap', 'charger' => 'plug-zap', 'silent' => 'volume-x', 'quiet' => 'volume-x',
            'tea' => 'coffee', 'coffee' => 'coffee', 'cafe' => 'coffee', 'book' => 'book-open', 'chair' => 'armchair',
            'seat' => 'armchair', 'cabin' => 'armchair', 'fan' => 'fan',
        ] as $k => $icon) { if (str_contains($n, $k)) return $icon; }
        return 'circle-check';
    };
    $lbFeatures = \Illuminate\Support\Facades\DB::table('features')->whereNull('deleted_at')->get(['id', 'name'])
        ->mapWithKeys(fn ($f) => [(string) $f->id => ['n' => $f->name, 'i' => $lbIconFor($f->name)]]);

    // Read-only facts per listed library: monthly fees from, shift types, online booking.
    $lbExtra = [];
    try {
        $lbFrom = \Illuminate\Support\Facades\DB::table('plan_prices as pp')
            ->join('plans as p', 'p.id', '=', 'pp.plan_id')
            ->whereNull('pp.deleted_at')->whereNull('p.deleted_at')->where('p.plan_id', 1)->where('pp.price', '>', 0)
            ->groupBy('pp.branch_id')->select('pp.branch_id', \Illuminate\Support\Facades\DB::raw('MIN(pp.price) as from_price'))
            ->pluck('from_price', 'branch_id');
        $lbShifts = \Illuminate\Support\Facades\DB::table('plan_types')->whereNull('deleted_at')->whereNotNull('branch_id')
            ->get(['branch_id', 'day_type_id'])->groupBy('branch_id')
            ->map(fn ($g) => $g->pluck('day_type_id')->map(fn ($v) => (int) $v)->unique()->values()->all());
        $lbBookable = \Illuminate\Support\Facades\DB::table('branches as b')
            ->join('hour as h', 'h.branch_id', '=', 'b.id')
            ->where('b.is_profile', 1)->whereNull('b.deleted_at')->whereNotNull('b.uuid')->where('h.seats', '>', 0)
            ->pluck('b.id');
        foreach ($lbShifts as $bid => $types) { $lbExtra[$bid]['s'] = $types; }
        foreach ($lbFrom as $bid => $price) { $lbExtra[$bid]['f'] = (int) round((float) $price); }
        foreach ($lbBookable as $bid) { if (!empty($lbExtra[$bid]['s'])) $lbExtra[$bid]['b'] = 1; }
    } catch (\Throwable $e) {
        report($e);
    }

    $lbTopCities = collect($cities)->take(8);
    $faqs = [
        [$t('How do I find a library near me?'), $t('Pick your city or type your area or a landmark in the search box. You can also tap "Near me" and allow location to see the closest libraries first.')],
        [$t('Is Libraro free for students?'), $t('Yes. Searching and comparing libraries on Libraro is completely free. You pay the library directly for your seat.')],
        [$t('How do I book a seat?'), $t('Open a library with "Online booking", pick a free seat and shift on the seat map, choose your plan and pay online or at the library. The library confirms your seat when you visit.')],
        [$t('What does "Verified" mean?'), $t('Verified libraries are active Libraro members. They manage seats, fees and attendance on Libraro, so their details are kept up to date.')],
        [$t('Can I see fees and timings before visiting?'), $t('Yes. Each library page shows its shifts, timings, fees and facilities when the library has added them.')],
        [$t('I own a library. How do I get listed?'), $t('Register your library on Libraro. Every plan includes a free listing on this page so students nearby can find you.')],
    ];
@endphp

@push('head')
    <link rel="stylesheet" href="{{ asset('public/css/lb-directory.css') }}?v={{ @filemtime(public_path('css/lb-directory.css')) }}">
    <link rel="stylesheet" href="{{ asset('public/css/lb-find.css') }}?v={{ @filemtime(public_path('css/lb-find.css')) }}">
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org', '@type' => 'FAQPage',
            'mainEntity' => collect($faqs)->map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]])->values(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
<div class="fd">

{{-- ===================== SEARCH HERO ===================== --}}
<section class="fd-hero" aria-labelledby="lb-find-title">
    <span class="fd-pill"><x-lb-icon name="sparkles" />{{ $t('Free for students · No booking fee') }}</span>
    <h1 id="lb-find-title">{{ $t('Find your perfect') }}<br><span>{{ $t('study seat.') }}</span></h1>
    <p class="lb-lead">{{ $t('Quiet study libraries near you. Compare fees, shifts and facilities, see free seats live and book in a minute.') }}</p>

    <form class="fd-search" id="lb-search" role="search" autocomplete="off" onsubmit="return false">
        <label class="fd-field fd-field--city" for="city-item">
            <x-lb-icon name="map-pin" />
            <span class="fd-field-text"><small>{{ $t('City') }}</small>
                <select id="city-item" aria-label="{{ $t('Select city') }}">
                    <option value="">{{ $t('All cities') }}</option>
                    @foreach($cities as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </span>
            <x-lb-icon name="chevron-down" class="fd-caret" />
        </label>
        <label class="fd-field fd-field--q" for="library-search">
            <x-lb-icon name="search" />
            <span class="fd-field-text"><small>{{ $t('Area, landmark or library') }}</small>
                <input type="search" id="library-search" placeholder="{{ $t('e.g. Talwandi, near Allen') }}" enterkeyhint="search" aria-autocomplete="list" aria-controls="lb-suggest" aria-expanded="false">
            </span>
            <button type="button" class="fd-clear" id="clear-search-btn" aria-label="{{ $t('Clear search') }}" hidden><x-lb-icon name="x" /></button>
        </label>
        <button type="submit" class="lb-btn lb-btn--lg fd-go" id="btn-trigger-search"><x-lb-icon name="search" size="18" /><span>{{ $t('Search') }}</span></button>
        <ul class="fd-suggest" id="lb-suggest" role="listbox" hidden></ul>
    </form>

    <div class="fd-needs" aria-label="{{ $t('What do you need?') }}">
        <span class="fd-needs-label">{{ $t('I need') }}</span>
        <div class="fd-needs-track">
            <button type="button" class="fd-need" id="lb-near"><x-lb-icon name="locate-fixed" />{{ $t('Near me') }}</button>
            <button type="button" class="fd-need" data-need="book"><x-lb-icon name="armchair" />{{ $t('Book online') }}</button>
            <button type="button" class="fd-need" data-need="budget:1000"><x-lb-icon name="indian-rupee" />{{ $t('Under ₹1,000') }}</button>
            <button type="button" class="fd-need" data-need="shift:morning"><x-lb-icon name="sunrise" />{{ $t('Morning shift') }}</button>
            <button type="button" class="fd-need" data-need="shift:evening"><x-lb-icon name="sunset" />{{ $t('Evening shift') }}</button>
            <button type="button" class="fd-need" data-need="shift:night"><x-lb-icon name="moon" />{{ $t('Night study') }}</button>
            <button type="button" class="fd-need" data-need="fac:snowflake"><x-lb-icon name="snowflake" />{{ $t('AC') }}</button>
            <button type="button" class="fd-need" data-need="fac:wifi"><x-lb-icon name="wifi" />{{ $t('Wi-Fi') }}</button>
            <button type="button" class="fd-need" data-need="verified"><x-lb-icon name="badge-check" />{{ $t('Verified') }}</button>
        </div>
    </div>

    @if(($library_count ?? 0) > 0)
        <div class="fd-stats" aria-label="{{ $t('Libraro in numbers') }}">
            <div><b>{{ number_format($library_count) }}+</b><span>{{ $t('Libraries') }}</span></div>
            @if(($city_count ?? 0) > 0)<div><b>{{ number_format($city_count) }}+</b><span>{{ $t('Cities') }}</span></div>@endif
            @if(($learner_counter ?? 0) > 0)<div><b>{{ number_format($learner_counter) }}+</b><span>{{ $t('Students studying') }}</span></div>@endif
        </div>
    @endif
</section>

{{-- ===================== PREMIUM CAROUSEL ===================== --}}
<section class="fd-premium" id="fd-premium" aria-labelledby="fd-premium-title" hidden>
    <div class="fd-sec-head">
        <div>
            <span class="lb-eyebrow">{{ $t('Handpicked') }}</span>
            <h2 id="fd-premium-title">{{ $t('Premium libraries') }}</h2>
            <p>{{ $t('Verified libraries with live seats, clear fees and online booking.') }}</p>
        </div>
        <div class="fd-arrows">
            <button type="button" class="fd-arrow" data-fd-prev="premium" aria-label="{{ $t('Previous') }}"><x-lb-icon name="chevron-left" /></button>
            <button type="button" class="fd-arrow" data-fd-next="premium" aria-label="{{ $t('Next') }}"><x-lb-icon name="chevron-right" /></button>
        </div>
    </div>
    <div class="fd-premium-track" id="fd-premium-track" data-fd-track="premium" tabindex="0" aria-label="{{ $t('Premium libraries') }}"></div>
</section>

{{-- ===================== RESULTS + FILTERS ===================== --}}
<section class="fd-results" id="directory-explore" aria-labelledby="lb-results-title">
    <div class="fd-layout">
        <aside class="fd-filters" id="fd-filters" aria-label="{{ $t('Filters') }}">
            <div class="fd-filters-head">
                <h2>{{ $t('Filters') }}</h2>
                <button type="button" class="fd-link" data-fd-clear>{{ $t('Clear all') }}</button>
                <button type="button" class="fd-close" data-fd-filters-close aria-label="{{ $t('Close filters') }}"><x-lb-icon name="x" /></button>
            </div>
            <div class="fd-filters-body">
                <div class="fd-fgroup">
                    <h3><x-lb-icon name="indian-rupee" />{{ $t('Monthly budget') }}</h3>
                    <div class="fd-chips" data-fd-group="budget" data-single>
                        <button type="button" data-v="0" aria-pressed="true">{{ $t('Any') }}</button>
                        <button type="button" data-v="500" aria-pressed="false">{{ $t('Under ₹500') }}</button>
                        <button type="button" data-v="1000" aria-pressed="false">{{ $t('Under ₹1,000') }}</button>
                        <button type="button" data-v="1500" aria-pressed="false">{{ $t('Under ₹1,500') }}</button>
                        <button type="button" data-v="2500" aria-pressed="false">{{ $t('Under ₹2,500') }}</button>
                    </div>
                </div>
                <div class="fd-fgroup">
                    <h3><x-lb-icon name="clock-3" />{{ $t('Shift I want') }}</h3>
                    <div class="fd-chips" data-fd-group="shift">
                        <button type="button" data-v="morning" aria-pressed="false"><x-lb-icon name="sunrise" />{{ $t('Morning') }}</button>
                        <button type="button" data-v="evening" aria-pressed="false"><x-lb-icon name="sunset" />{{ $t('Evening') }}</button>
                        <button type="button" data-v="fullday" aria-pressed="false"><x-lb-icon name="sun" />{{ $t('Full day') }}</button>
                        <button type="button" data-v="night" aria-pressed="false"><x-lb-icon name="moon" />{{ $t('Night') }}</button>
                        <button type="button" data-v="24x7" aria-pressed="false"><x-lb-icon name="clock-3" />24 × 7</button>
                    </div>
                </div>
                <div class="fd-fgroup">
                    <h3><x-lb-icon name="sparkles" />{{ $t('Facilities') }}</h3>
                    <div class="fd-chips" data-fd-group="fac" id="fd-fac-chips">
                        @foreach(collect($lbFeatures)->unique('i')->reject(fn ($f) => $f['i'] === 'circle-check')->take(10) as $f)
                            <button type="button" data-v="{{ $f['i'] }}" aria-pressed="false"><x-lb-icon :name="$f['i']" />{{ \Illuminate\Support\Str::limit($f['n'], 18) }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="fd-fgroup">
                    <h3><x-lb-icon name="armchair" />{{ $t('Library size') }}</h3>
                    <div class="fd-chips" data-fd-group="size" data-single>
                        <button type="button" data-v="" aria-pressed="true">{{ $t('Any') }}</button>
                        <button type="button" data-v="s" aria-pressed="false">{{ $t('Cosy · under 40') }}</button>
                        <button type="button" data-v="m" aria-pressed="false">{{ $t('40 – 100 seats') }}</button>
                        <button type="button" data-v="l" aria-pressed="false">{{ $t('100+ seats') }}</button>
                    </div>
                </div>
                <div class="fd-fgroup">
                    <h3><x-lb-icon name="badge-check" />{{ $t('More') }}</h3>
                    <div class="fd-toggles">
                        <label class="fd-toggle"><input type="checkbox" data-fd-flag="book"><span></span>{{ $t('Online seat booking') }}</label>
                        <label class="fd-toggle"><input type="checkbox" data-fd-flag="verified"><span></span>{{ $t('Verified libraries only') }}</label>
                        <label class="fd-toggle"><input type="checkbox" data-fd-flag="photos"><span></span>{{ $t('With photos') }}</label>
                    </div>
                </div>
            </div>
            <div class="fd-filters-foot">
                <button type="button" class="lb-btn lb-btn--block" data-fd-filters-close>{{ $t('Show') }} <span data-fd-count>0</span> <span data-fd-noun>{{ $t('libraries') }}</span></button>
            </div>
        </aside>

        <div class="fd-main">
            <div class="fd-results-head">
                <div>
                    <h2 id="lb-results-title">{{ $t('Libraries for you') }}</h2>
                    <p id="lb-results-sub" aria-live="polite">{{ $t('Loading libraries…') }}</p>
                </div>
                <div class="fd-tools">
                    <button type="button" class="fd-filter-btn" data-fd-filters-open aria-controls="fd-filters"><x-lb-icon name="list-tree" />{{ $t('Filters') }} <b data-fd-active hidden></b></button>
                    <label class="fd-sort"><span class="lb-sr-only">{{ $t('Sort by') }}</span>
                        <select id="fd-sort">
                            <option value="rec">{{ $t('Recommended') }}</option>
                            <option value="price">{{ $t('Fees: low to high') }}</option>
                            <option value="seats">{{ $t('Most seats') }}</option>
                            <option value="near" hidden>{{ $t('Nearest first') }}</option>
                        </select>
                        <x-lb-icon name="chevron-down" />
                    </label>
                </div>
            </div>
            <div class="fd-active" id="fd-active" hidden></div>
            <div id="lb-results" class="fd-grid" aria-busy="true">
                @for($i = 0; $i < 6; $i++)
                    <div class="fd-skel" aria-hidden="true"><div class="s-img"></div><div class="s-l"></div><div class="s-l w60"></div><div class="s-l w40"></div></div>
                @endfor
            </div>
            <div class="fd-more"><button type="button" class="lb-btn lb-btn--ghost" id="lb-load-more" hidden>{{ $t('Show more libraries') }}</button></div>
        </div>
    </div>
</section>

{{-- ===================== PROMO BANNERS ===================== --}}
<section class="fd-promo" aria-label="{{ $t('Offers and updates') }}">
    @include('lb.partials.promo-banners', ['variant' => 'wide'])
</section>

{{-- ===================== HOW IT WORKS (students) ===================== --}}
<section class="lb-section" aria-labelledby="lb-how-title">
    <div class="lb-center-text">
        <span class="lb-eyebrow">{{ $t('For students') }}</span>
        <h2 class="lb-h2" id="lb-how-title" data-reveal>{{ $t('Your seat in 3 easy steps.') }}</h2>
    </div>
    <div class="fd-steps">
        <div class="fd-step fd-step--1"><i><x-lb-icon name="search" /></i><small>{{ $t('Step 1') }}</small><h3>{{ $t('Search') }}</h3><p>{{ $t('Pick your city, tap "Near me", or filter by budget, shift and facilities.') }}</p></div>
        <div class="fd-step fd-step--2"><i><x-lb-icon name="armchair" /></i><small>{{ $t('Step 2') }}</small><h3>{{ $t('Pick a seat') }}</h3><p>{{ $t('Open a library, see free seats for your shift on the live seat map.') }}</p></div>
        <div class="fd-step fd-step--3"><i><x-lb-icon name="circle-check" /></i><small>{{ $t('Step 3') }}</small><h3>{{ $t('Book & visit') }}</h3><p>{{ $t('Pay online or at the library. Show your booking at the desk and start studying.') }}</p></div>
    </div>
</section>

{{-- ===================== MAP ===================== --}}
<section class="lb-section lb-section--flush-top" aria-labelledby="lb-map-title">
    <div class="lb-center-text">
        <h2 class="lb-h2" id="lb-map-title" data-reveal>{{ $t('Libraries on the map.') }}</h2>
        <p class="lb-lead">{{ $t('Tap a pin to open the library.') }}</p>
    </div>
    <div class="lb-mapbox fd-map">
        <div class="lb-map-ph" id="lb-map-ph">{{ $t('Loading map…') }}</div>
        <div id="lb-map" role="region" aria-label="{{ $t('Map of libraries') }}"></div>
    </div>
</section>

{{-- ===================== FAQ ===================== --}}
<section class="lb-section lb-section--flush-top" aria-labelledby="lb-faq-title">
    <div class="lb-center-text"><h2 class="lb-h2" id="lb-faq-title" data-reveal>{{ $t('Questions? Answers.') }}</h2></div>
    <div class="lb-faq fd-faq">
        @foreach($faqs as $i => [$q, $a])
            <details @if($i === 0) open @endif>
                <summary>{{ $q }}<i aria-hidden="true"><x-lb-icon name="plus" /></i></summary>
                <p>{{ $a }}</p>
            </details>
        @endforeach
    </div>
</section>

{{-- ===================== OWNER BAND ===================== --}}
<section class="lb-section lb-section--flush-top" aria-label="{{ $t('For library owners') }}">
    <div class="lb-owner-band">
        <div>
            <span class="lb-eyebrow lb-eyebrow--light">{{ $t('For library owners') }}</span>
            <h2>{{ $t('Own a study library? Get listed here for free.') }}</h2>
            <p>{{ $t('Manage seats, fees and attendance with Libraro, and let students nearby find you.') }}</p>
        </div>
        <div class="lb-actions">
            <a href="{{ route('register') }}" class="lb-btn lb-btn--accent">{{ $t('List my library') }}</a>
            <a href="{{ url('/') }}#demo" class="lb-link-arrow">{{ $t('Book a demo') }} <x-lb-icon name="chevron-right" size="18" /></a>
        </div>
    </div>
</section>
</div>
<div class="fd-scrim" data-fd-filters-close hidden></div>
@endsection

@push('scripts')
    <script>
        window.LB_DIR = {
            listUrl: @json(route('get-libraries')),
            mapUrl: @json(route('getLibrariesLocations')),
            base: @json(url('/')),
            assetBase: @json(asset('public')),
            features: @json($lbFeatures),
            extra: @json((object) $lbExtra),
            icons: @json(asset('public/img/lb-icons.svg') . '?v=' . @filemtime(public_path('img/lb-icons.svg'))),
            perPage: 12
        };
    </script>
    <script src="{{ asset('public/js/lb-find.js') }}?v={{ @filemtime(public_path('js/lb-find.js')) }}" defer></script>
@endpush
