@extends('lb.layouts.site')

{{--
    Find my library — student-first directory (route: find-my-library → SiteController@searchLibrary).
    SEO stays DB-driven via lb.layouts.site (pages table, route "find-my-library").
    Data: $cities, $library_count, $learner_counter, $city_count (+ unused: $topLibraries, $happy_customers, $feedback_count).
    Results come from the SAME endpoints as before: route('get-libraries') and route('getLibrariesLocations').
--}}

@php
    $lbIconFor = function ($name) {
        $n = strtolower($name);
        $map = [
            'wifi' => 'wifi', 'wi-fi' => 'wifi', 'internet' => 'wifi',
            'air' => 'snowflake', ' ac' => 'snowflake', 'cool' => 'snowflake',
            'lamp' => 'lamp-desk', 'light' => 'lamp-desk',
            'power' => 'zap', 'backup' => 'zap', 'invert' => 'zap', 'generator' => 'zap',
            'water' => 'droplet', 'ro ' => 'droplet',
            'locker' => 'lock', 'cctv' => 'cctv', 'camera' => 'cctv', 'security' => 'cctv',
            'parking' => 'car', 'washroom' => 'bath', 'toilet' => 'bath',
            'news' => 'newspaper', 'magazine' => 'newspaper', 'charging' => 'plug-zap', 'charger' => 'plug-zap',
            'silent' => 'volume-x', 'quiet' => 'volume-x', 'tea' => 'coffee', 'coffee' => 'coffee', 'cafe' => 'coffee',
            'book' => 'book-open', 'chair' => 'armchair', 'seat' => 'armchair', 'cabin' => 'armchair', 'fan' => 'fan',
        ];
        foreach ($map as $k => $icon) { if (str_contains(' ' . $n, $k)) return $icon; }
        return 'circle-check';
    };
    $lbFeatures = \Illuminate\Support\Facades\DB::table('features')->whereNull('deleted_at')->get(['id', 'name'])
        ->mapWithKeys(fn ($f) => [(string) $f->id => ['n' => $f->name, 'i' => $lbIconFor($f->name)]]);
    $lbTopCities = collect($cities)->take(10);
    $faqs = [
        ['How do I find a library near me?', 'Pick your city or type your area or a landmark in the search box. You can also tap "Near me" and allow location to see the closest libraries first.'],
        ['Is Libraro free for students?', 'Yes. Searching and comparing libraries on Libraro is completely free. You pay the library directly for your seat.'],
        ['How do I book a seat?', 'Open a library and tap "Book a seat" if the library accepts online booking. Otherwise send an enquiry, call or WhatsApp the library, and they will confirm your seat.'],
        ['What does "Verified" mean?', 'Verified libraries are active Libraro members. They manage seats, fees and attendance on Libraro, so their details are kept up to date.'],
        ['Can I see fees and timings before visiting?', 'Yes. Each library page shows its shifts, timings, monthly fees and facilities when the library has added them.'],
        ['I own a library. How do I get listed?', 'Register your library on Libraro. Every plan includes a free listing on this page so students nearby can find you.'],
    ];
@endphp

@push('head')
    <link rel="stylesheet" href="{{ asset('public/css/lb-directory.css') }}?v={{ @filemtime(public_path('css/lb-directory.css')) }}">
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org', '@type' => 'FAQPage',
            'mainEntity' => collect($faqs)->map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]])->values(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')

{{-- ===================== SEARCH HERO ===================== --}}
<section class="lb-find-hero" aria-labelledby="lb-find-title">
    <span class="lb-eyebrow">Find my library</span>
    <h1 id="lb-find-title">Find a quiet study library near you.</h1>
    <p class="lb-lead">Search by city or area. Compare seats, facilities and fees, then call, WhatsApp or book in one tap. Free for students.</p>

    <form class="lb-search" id="lb-search" role="search" autocomplete="off" onsubmit="return false">
        <label class="lb-input" for="city-item">
            <x-lb-icon name="map-pin" />
            <span class="lb-sr-only">City</span>
            <select id="city-item" aria-label="Select city">
                <option value="">All cities</option>
                @foreach($cities as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
            <x-lb-icon name="chevron-down" />
        </label>
        <label class="lb-input" for="library-search">
            <x-lb-icon name="search" />
            <span class="lb-sr-only">Library name, area or landmark</span>
            <input type="search" id="library-search" placeholder="Library name, area or landmark" enterkeyhint="search" aria-autocomplete="list" aria-controls="lb-suggest" aria-expanded="false">
            <button type="button" class="lb-search-clear" id="clear-search-btn" aria-label="Clear search" hidden><x-lb-icon name="x" /></button>
        </label>
        <button type="submit" class="lb-btn" id="btn-trigger-search">Search</button>
        <ul class="lb-suggest" id="lb-suggest" role="listbox" hidden></ul>
    </form>

    <div class="lb-quick" aria-label="Quick filters">
        <button type="button" class="lb-near" id="lb-near"><x-lb-icon name="locate-fixed" />Near me</button>
        @if($lbTopCities->isNotEmpty())
            <span class="lb-quick-label">Popular:</span>
            @foreach($lbTopCities as $id => $name)
                <button type="button" class="lb-chip" data-city="{{ $id }}">{{ $name }}</button>
            @endforeach
        @endif
    </div>

    @if(($library_count ?? 0) > 0)
        <div class="lb-stats" aria-label="Libraro in numbers">
            <div><b>{{ number_format($library_count) }}+</b><span>Libraries</span></div>
            @if(($city_count ?? 0) > 0)<div><b>{{ number_format($city_count) }}+</b><span>Cities</span></div>@endif
            @if(($learner_counter ?? 0) > 0)<div><b>{{ number_format($learner_counter) }}+</b><span>Students studying</span></div>@endif
        </div>
    @endif
</section>

{{-- ===================== RESULTS ===================== --}}
<section class="lb-results" id="directory-explore" aria-labelledby="lb-results-title">
    <div class="lb-results-head">
        <div>
            <h2 id="lb-results-title">Libraries for you</h2>
            <p id="lb-results-sub" aria-live="polite">Loading libraries…</p>
        </div>
        <div class="lb-seg" role="group" aria-label="Filter">
            <button type="button" data-cat="all" aria-pressed="true">All</button>
            <button type="button" data-cat="premium" aria-pressed="false"><x-lb-icon name="badge-check" />Verified</button>
        </div>
    </div>
    <div id="lb-results" class="lb-lib-grid" aria-busy="true">
        @for($i = 0; $i < 8; $i++)
            <div class="lb-skel" aria-hidden="true"><div class="s-img"></div><div class="s-l"></div><div class="s-l w60"></div><div class="s-btn"></div></div>
        @endfor
    </div>
    <div class="lb-more"><button type="button" class="lb-btn lb-btn--ghost" id="lb-load-more" hidden>Show more libraries</button></div>
</section>

{{-- ===================== HOW IT WORKS (students) ===================== --}}
<section class="lb-section" aria-labelledby="lb-how-title">
    <div class="lb-center-text">
        <span class="lb-eyebrow">For students</span>
        <h2 class="lb-h2" id="lb-how-title" data-reveal>Your seat in 3 easy steps.</h2>
    </div>
    <div class="lb-steps3">
        <div class="lb-step3"><i><x-lb-icon name="search" /></i><small>Step 1</small><h3>Search</h3><p>Choose your city or tap "Near me" to see libraries close to you.</p></div>
        <div class="lb-step3"><i><x-lb-icon name="list-tree" /></i><small>Step 2</small><h3>Compare</h3><p>Check photos, shifts, timings, fees and facilities like AC, Wi-Fi and lockers.</p></div>
        <div class="lb-step3"><i><x-lb-icon name="armchair" /></i><small>Step 3</small><h3>Book or call</h3><p>Book a seat online, send an enquiry, or call and WhatsApp the library directly.</p></div>
    </div>
</section>

{{-- ===================== MAP ===================== --}}
<section class="lb-section lb-section--flush-top" aria-labelledby="lb-map-title">
    <div class="lb-center-text">
        <h2 class="lb-h2" id="lb-map-title" data-reveal>Libraries on the map.</h2>
        <p class="lb-lead">Tap a pin to open the library.</p>
    </div>
    <div class="lb-mapbox">
        <div class="lb-map-ph" id="lb-map-ph">Loading map…</div>
        <div id="lb-map" role="region" aria-label="Map of libraries"></div>
    </div>
</section>

{{-- ===================== FAQ ===================== --}}
<section class="lb-section lb-section--flush-top" aria-labelledby="lb-faq-title">
    <div class="lb-center-text"><h2 class="lb-h2" id="lb-faq-title" data-reveal>Questions? Answers.</h2></div>
    <div class="lb-faq">
        @foreach($faqs as $i => [$q, $a])
            <details @if($i === 0) open @endif>
                <summary>{{ $q }}<i aria-hidden="true"><x-lb-icon name="plus" /></i></summary>
                <p>{{ $a }}</p>
            </details>
        @endforeach
    </div>
</section>

{{-- ===================== OWNER BAND ===================== --}}
<section class="lb-section lb-section--flush-top" aria-label="For library owners">
    <div class="lb-owner-band">
        <div>
            <span class="lb-eyebrow lb-eyebrow--light">For library owners</span>
            <h2>Own a study library? Get listed here for free.</h2>
            <p>Manage seats, fees and attendance with Libraro, and let students nearby find you.</p>
        </div>
        <div class="lb-actions">
            <a href="{{ route('register') }}" class="lb-btn lb-btn--accent">List my library</a>
            <a href="{{ url('/') }}#demo" class="lb-link-arrow">Book a demo <x-lb-icon name="chevron-right" size="18" /></a>
        </div>
    </div>
</section>
@endsection

@push('scripts')
    <script>
        window.LB_DIR = {
            listUrl: @json(route('get-libraries')),
            mapUrl: @json(route('getLibrariesLocations')),
            base: @json(url('/')),
            assetBase: @json(asset('public')),
            features: @json($lbFeatures),
            icons: @json(asset('public/img/lb-icons.svg') . '?v=' . @filemtime(public_path('img/lb-icons.svg'))),
            perPage: 12
        };
    </script>
    <script src="{{ asset('public/js/lb-directory.js') }}?v={{ @filemtime(public_path('js/lb-directory.js')) }}" defer></script>
@endpush
