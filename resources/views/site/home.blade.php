@extends('lb.layouts.site')

{{--
    Home: premium redesign (Oct 2026) for multi-branch library owners.
    Languages: English (default), Hindi (?lang=hi), Hinglish (?lang=hinglish). Copy lives in site/i18n/home.php.
    Live data kept from SiteController@home: $subscriptions + $features (plans; prices via AJAX in lb-site.js),
    Buy Now -> store.selected.plan -> register, $happy_customers (reviews). Videos come from the
    admin "Video Upload" list (settings table, youtube_link). Real portal screenshots: public/img/site/shots/.
    No backend or login change.
--}}

@php
    /* ---------- Language ---------- */
    /* Shared website language (?lang=, else the visitor's remembered choice, else English): lb/i18n/boot.php */
    extract(require resource_path('views/lb/i18n/boot.php'));
    $lbNoHreflang = true; // this page prints its own hreflang tags below
    $lbAllCopy = include resource_path('views/site/i18n/home.php');
    $T = $lbAllCopy[$lbLang];
    $I = $lbAllCopy['_icons'];
    $lbNavT = $T['nav'];
    $lbLangLabel = $T['lang_label'];
    $tr = fn ($s, $map) => strtr($s, $map);

    /* ---------- SEO (English can still be overridden from the admin "pages" table) ---------- */
    $seoPage = null;
    try { $seoPage = App\Models\Page::where('route', '/')->first(); } catch (\Throwable $e) {}
    $seoTitle = ($lbLang === 'en' && !empty($seoPage->meta_title)) ? $seoPage->meta_title : $T['meta']['title'];
    $seoDesc = ($lbLang === 'en' && !empty($seoPage->meta_description)) ? $seoPage->meta_description : $T['meta']['description'];
    $seoCanonical = $lbSeoUrls[$lbLang];

    /* ---------- Images ---------- */
    $img = fn ($n, $w = 1600) => asset('public/img/site/' . $n . '-' . $w . '.webp');
    $shot = function ($name, $fallback) {
        foreach (['webp', 'png', 'jpg'] as $ext) {
            $p = 'img/site/shots/' . $name . '.' . $ext;
            if (file_exists(public_path($p))) return asset('public/' . $p) . '?v=' . @filemtime(public_path($p));
        }
        return $fallback;
    };
    $deskFallback = $img('dashboard', 1600);
    $showTabs = [];
    foreach ($T['show']['tabs'] as $key => [$label, $desc]) {
        $showTabs[] = ['key' => $key, 'label' => $label, 'desc' => $desc, 'src' => $shot($key, $deskFallback)];
    }
    $showIcons = ['dashboard' => 'layout-dashboard', 'learners' => 'users-round', 'attendance' => 'calendar-check', 'fees' => 'indian-rupee', 'reports' => 'chart-line'];

    /* Operation screens (light + dark) for the lower laptop */
    $opsTabs = [];
    $opsIcons = ['profile' => 'user-round', 'swap' => 'arrow-left-right', 'plan' => 'refresh-cw', 'attend' => 'calendar-check', 'wallet' => 'wallet'];
    foreach ($T['ops']['tabs'] as $key => [$label, $desc]) {
        $light = $shot('op-' . $key, $deskFallback);
        $opsTabs[] = ['key' => $key, 'label' => $label, 'desc' => $desc, 'light' => $light, 'dark' => $shot('op-' . $key . '-dark', $light)];
    }

    /* A day at your library (photo cards) */
    $dayMeta = [['user-plus', 'admit', 'A new learner registering at the library front desk'], ['armchair', 'seats', 'Learners studying at their seats in a study library'], ['indian-rupee', 'fees', 'Paying library fees by scanning a UPI QR code'], ['qr-code', 'qr', 'A learner scanning a QR code on her phone to check in'], ['bell-ring', 'remind', 'A learner reading a fee reminder on her phone'], ['chart-line', 'report', 'Library owner reviewing the day’s collection on a laptop']];

    /* Libraro Shorts: every mp4 in public/videos/shorts (+ its -preview.mp4 and .webp poster) */
    $shorts = [];
    foreach (glob(public_path('videos/shorts/*.mp4')) ?: [] as $file) {
        $name = basename($file, '.mp4');
        if (str_ends_with($name, '-preview')) continue;
        $base = 'videos/shorts/' . $name;
        $num = preg_match('~^mentorship-(\d+)$~', $name, $mm) ? (int) $mm[1] : null;
        $title = ($num && isset($T['videos']['items'][$num - 1])) ? $T['videos']['items'][$num - 1] : \Illuminate\Support\Str::of($name)->replace(['-', '_'], ' ')->title();
        $shorts[] = [
            'title' => (string) $title,
            'src' => asset('public/' . $base . '.mp4'),
            'preview' => file_exists(public_path($base . '-preview.mp4')) ? asset('public/' . $base . '-preview.mp4') : asset('public/' . $base . '.mp4'),
            'poster' => file_exists(public_path($base . '.webp')) ? asset('public/' . $base . '.webp') : '',
        ];
    }

    /* ---------- Plans ---------- */
    $plansList = $subscriptions->values();
    $popularIndex = $plansList->count() >= 3 ? 1 : ($plansList->count() - 1);

    /* ---------- Videos (admin > Video Upload) ---------- */
    $ytId = function ($url) {
        if (!$url) return null;
        if (preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/))([A-Za-z0-9_-]{11})~', $url, $m)) return $m[1];
        return preg_match('~^[A-Za-z0-9_-]{11}$~', trim($url)) ? trim($url) : null;
    };
    $videos = collect();
    try {
        $videos = App\Models\Setting::whereNotNull('youtube_link')->latest()->take(12)->get()
            ->map(fn ($v) => ['id' => $ytId($v->youtube_link), 'title' => $v->video_titel ?? 'Libraro'])
            ->filter(fn ($v) => !empty($v['id']))->values();
    } catch (\Throwable $e) {}
    $ytChannel = 'https://www.youtube.com/@Libraroindia';
    $shorts = array_slice($shorts, 0, 10);
    $videos = $videos->take(max(0, 10 - count($shorts)));
    $reelCount = count($shorts) + $videos->count();

    /* ---------- Reviews ---------- */
    $reviews = collect($happy_customers ?? [])->take(12)->map(function ($v) use ($T) {
        $name = !empty($v->library_owner) ? $v->library_owner : (!empty($v->library_name) ? $v->library_name : 'Library partner');
        return [
            'text' => $v->description,
            'name' => $name,
            'library' => $v->library_name ?? '',
            'city' => $v->city_name ?? '',
            'rating' => max(1, min(5, (int) ($v->rating ?? 5))),
        ];
    });
    if ($reviews->isEmpty()) {
        $reviews = collect([[
            'text' => 'We have been using Libraro for over a year now, and it has exceeded all our expectations. The analytics and reporting features give us real insight. An all-in-one solution for modern library management.',
            'name' => 'Sandeep Rathor', 'library' => 'Libraro partner library', 'city' => '', 'rating' => 5,
        ]]);
    }

    $wa = 'https://wa.me/918114479678?text=' . rawurlencode('Hi Libraro, I run a library and want to know how Libraro can help me.');
    $accent = ['navy', 'teal', 'violet', 'green', 'orange', 'pink', 'indigo', 'blue', 'amber', 'red'];
@endphp

@section('seo')
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDesc }}">
    @if($lbLang === 'en' && !empty($seoPage->meta_keyword))<meta name="keywords" content="{{ $seoPage->meta_keyword }}">@endif
    <link rel="canonical" href="{{ $seoCanonical }}">
    <link rel="alternate" hreflang="en-IN" href="{{ $lbSeoUrls['en'] }}">
    <link rel="alternate" hreflang="hi-IN" href="{{ $lbSeoUrls['hi'] }}">
    <link rel="alternate" hreflang="hi-Latn-IN" href="{{ $lbSeoUrls['hinglish'] }}">
    <link rel="alternate" hreflang="x-default" href="{{ $lbSeoUrls['en'] }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Libraro">
    <meta property="og:title" content="{{ $T['meta']['og_title'] }}">
    <meta property="og:description" content="{{ $seoDesc }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $showTabs[0]['src'] }}">
    <meta property="og:locale" content="{{ str_replace('-', '_', $lbHtmlLang === 'hi-Latn-IN' ? 'hi-IN' : $lbHtmlLang) }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $T['meta']['og_title'] }}">
    <meta name="twitter:description" content="{{ $seoDesc }}">
    <meta name="twitter:image" content="{{ $showTabs[0]['src'] }}">
    @if($lbLang === 'en' && !empty($seoPage->page_schema)){!! html_entity_decode($seoPage->page_schema) !!}@endif
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'SoftwareApplication',
                    'name' => 'Libraro',
                    'applicationCategory' => 'BusinessApplication',
                    'operatingSystem' => 'Web, Android, iOS',
                    'description' => $seoDesc,
                    'url' => url('/'),
                    'inLanguage' => $lbHtmlLang,
                    'offers' => ['@type' => 'AggregateOffer', 'priceCurrency' => 'INR', 'lowPrice' => (float) ($plansList->min('monthly_fees') ?? 0), 'offerCount' => $plansList->count()],
                ],
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => collect($T['faq']['items'])->map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]])->values(),
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endsection

@section('body_class', 'lh-page lh-lang-' . $lbLang)

@push('head')
    <link rel="stylesheet" href="{{ asset('public/css/lb-home.css') }}?v={{ @filemtime(public_path('css/lb-home.css')) }}">
    <link rel="preload" as="image" href="{{ $showTabs[0]['src'] }}" fetchpriority="high">
    <script>window.LB_SITE_I18N = @json($T['pricing']['js']);</script>
    @if($lbLang === 'hi')
        <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;500;600;700&display=swap">
    @endif
@endpush

@section('hero_notices', '1')

@section('content')

{{-- ===================== 1. HERO ===================== --}}
<section class="lh-hero" aria-labelledby="lh-hero-title">
    <div class="lh-hero-orb lh-hero-orb--1" aria-hidden="true"></div>
    <div class="lh-hero-orb lh-hero-orb--2" aria-hidden="true"></div>
    <div class="lh-wrap lh-hero-center">
        <span class="lh-chip lh-chip--glass"><x-lb-icon name="crown" />{{ $T['hero']['eyebrow'] }}</span>
        <h1 id="lh-hero-title">{!! preg_replace('~<br>(.*)$~u', '<br><span class="lh-grad">$1</span>', $T['hero']['title']) !!}</h1>
        <p class="lh-hero-lead">{{ $T['hero']['lead'] }}</p>
        <div class="lh-hero-actions">
            <a href="{{ route('register') }}" class="lb-btn lb-btn--lg lb-btn--accent lh-btn-main">{{ $T['hero']['cta'] }}<x-lb-icon name="arrow-right" size="18" /></a>
            <button type="button" class="lb-btn lb-btn--lg lh-btn-glass" data-lb-qc-open><x-lb-icon name="phone-call" size="18" />{{ $T['hero']['call'] }}</button>
        </div>
        <div class="lh-hero-proof">
            <a href="#refund" class="lh-refund-badge">
                <span class="lh-refund-seal" aria-hidden="true"><b>7</b><small>DAYS</small></span>
                <span><b>{{ $T['hero']['refund'] }}</b><small>{{ $T['hero']['refund_sub'] }}</small></span>
            </a>
            <ul class="lh-hero-trust">
                @foreach($T['hero']['trust'] as $tx)<li><x-lb-icon name="circle-check" />{{ $tx }}</li>@endforeach
            </ul>
        </div>
    </div>

    <div class="lh-wrap lh-hero-stage">
        <div class="lh-stage-glow" aria-hidden="true"></div>
        @php $byKey = collect($showTabs)->keyBy('key'); @endphp
        <figure class="lh-win lh-win--l" aria-hidden="true"><span class="lh-win-bar"><i></i><i></i><i></i></span><img src="{{ $byKey['attendance']['src'] }}" alt="" width="1600" height="1000" loading="lazy" decoding="async"></figure>
        <figure class="lh-win lh-win--r" aria-hidden="true"><span class="lh-win-bar"><i></i><i></i><i></i></span><img src="{{ $byKey['learners']['src'] }}" alt="" width="1600" height="1000" loading="lazy" decoding="async"></figure>
        <div class="lh-mac lh-mac--hero" data-lh-hero-rotator>
            <div class="lh-mac-screen">
                <div class="lh-mac-notch" aria-hidden="true"></div>
                @foreach($showTabs as $k => $tab)
                    <img src="{{ $tab['src'] }}" alt="Libraro {{ $tab['label'] }}: {{ $tab['desc'] }}" width="1600" height="1000" class="lh-rot-img {{ $k === 0 ? 'is-on' : '' }}" @if($k === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async">
                @endforeach
            </div>
            <div class="lh-mac-base" aria-hidden="true"><i></i></div>
        </div>
        <div class="lh-rot-pills" role="tablist" aria-label="{{ $T['show']['eyebrow'] }}">
            @foreach($showTabs as $k => $tab)
                <button type="button" role="tab" aria-selected="{{ $k === 0 ? 'true' : 'false' }}" data-lh-rot="{{ $k }}"><x-lb-icon :name="$showIcons[$tab['key']]" />{{ $tab['label'] }}<span class="lh-rot-bar" aria-hidden="true"></span></button>
            @endforeach
        </div>
        <div class="lh-float lh-float--a" aria-hidden="true"><i><x-lb-icon name="indian-rupee" /></i><div><b>{{ $T['hero']['float1'][0] }}</b><span>{{ $T['hero']['float1'][1] }}</span></div></div>
        <div class="lh-float lh-float--b" aria-hidden="true"><i><x-lb-icon name="armchair" /></i><div><b>{{ $T['hero']['float2'][0] }}</b><span>{{ $T['hero']['float2'][1] }}</span></div></div>
        <div class="lh-float lh-float--c" aria-hidden="true"><i><x-lb-icon name="bell-ring" /></i><div><b>WhatsApp</b><span>{{ $T['hero']['float3'] }}</span></div></div>
        <div class="lh-float lh-float--d" aria-hidden="true"><i><x-lb-icon name="building" /></i><div><b>{{ $T['hero']['stats'][0][1] }}</b><span class="lh-dots"><em></em><em></em><em></em></span></div></div>
    </div>

    <div class="lh-wrap">
        <dl class="lh-hero-stats">
            @foreach($T['hero']['stats'] as [$n, $l])<div><dt>{{ $n }}</dt><dd>{{ $l }}</dd></div>@endforeach
            <div><dt>7 {{ $lbLang === 'hi' ? 'दिन' : ($lbLang === 'hinglish' ? 'din' : 'days') }}</dt><dd>{{ $T['hero']['refund'] }}</dd></div>
        </dl>
    </div>
</section>

{{-- ===================== MARQUEE RIBBON ===================== --}}
<div class="lh-marquee" aria-hidden="true">
    <div class="lh-marquee-track">
        @for($rep = 0; $rep < 2; $rep++)
            @foreach($T['hero']['marquee'] as $mi => $word)
                <span><i class="lh-mq-dot lh-mq-dot--{{ ($mi % 6) + 1 }}"></i>{{ $word }}</span>
            @endforeach
        @endfor
    </div>
</div>

{{-- ===================== 2. PROBLEM SLIDER ===================== --}}
<section class="lh-deco lh-section lh-pains" id="problems" aria-labelledby="lh-pains-title">
    <span class="lh-wm" aria-hidden="true" data-lh-wm><b>REALITY</b></span>
    <div class="lh-bgimg" aria-hidden="true" style="--lh-bg: url('{{ $img('slide-study', 1600) }}')"></div>
    <div class="lh-wrap">
        <div class="lh-head lh-head--row">
            <div>
                <span class="lh-eyebrow lh-eyebrow--red">{{ $T['pains']['eyebrow'] }}</span>
                <h2 class="lh-h2" id="lh-pains-title" data-reveal>{{ $T['pains']['title'] }}</h2>
                <p class="lh-lead">{{ $T['pains']['lead'] }}</p>
            </div>
            <div class="lh-arrows">
                <button type="button" class="lb-round-btn" data-lb-prev="pains" aria-label="{{ $T['pains']['prev'] }}"><x-lb-icon name="chevron-left" /></button>
                <button type="button" class="lb-round-btn" data-lb-next="pains" aria-label="{{ $T['pains']['next'] }}"><x-lb-icon name="chevron-right" /></button>
            </div>
        </div>
    </div>
    <div class="lh-slider" data-lb-track="pains" data-lh-autoplay tabindex="0" aria-label="{{ $T['pains']['title'] }}">
        @foreach($T['pains']['items'] as $i => [$title, $text])
            <article class="lh-pain lh-tone-{{ ($i % 8) + 1 }}" data-lh-tilt>
                <span class="lh-pain-ghost" aria-hidden="true"><x-lb-icon :name="$I['pains'][$i]" /></span>
                <span class="lh-tilt-glare" aria-hidden="true"></span>
                <div class="lh-pain-top">
                    <span class="lh-pain-ico"><x-lb-icon :name="$I['pains'][$i]" /></span>
                    <span class="lh-pain-num">{{ $T['pains']['label'] }} {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                </div>
                <h3>{{ $title }}</h3>
                <p>{{ $text }}</p>
            </article>
        @endforeach
    </div>
    <div class="lh-wrap"><div class="lh-progress" data-lh-progress="pains" aria-hidden="true"><i></i></div></div>
</section>

{{-- ===================== 3. WHY A MANAGEMENT TOOL ===================== --}}
<section class="lh-deco lh-section lh-why" aria-labelledby="lh-why-title">
    <span class="lh-wm" aria-hidden="true" data-lh-wm><b>SCALE</b></span>
    <div class="lh-bgimg" aria-hidden="true" style="--lh-bg: url('{{ $img('hero-library', 1600) }}')"></div>
    <div class="lh-wrap">
        <div class="lh-head lh-head--center">
            <span class="lh-eyebrow">{{ $T['why']['eyebrow'] }}</span>
            <h2 class="lh-h2" id="lh-why-title" data-reveal>{{ $T['why']['title'] }}</h2>
            <p class="lh-lead">{{ $T['why']['lead'] }}</p>
        </div>
        <div class="lh-why-grid">
            @foreach($T['why']['cards'] as [$icon, $title, $text])
                <article class="lh-why-card">
                    <span class="lh-why-ico"><x-lb-icon :name="$icon" /></span>
                    <h3>{{ $title }}</h3>
                    <p>{{ $text }}</p>
                    <span class="lh-why-bad"><x-lb-icon name="circle-x" />{{ $T['why']['bad'] }}</span>
                </article>
            @endforeach
        </div>
        <blockquote class="lh-why-quote" data-reveal><x-lb-icon name="quote" />{{ $T['why']['quote'] }}</blockquote>
    </div>
</section>

{{-- ===================== 4. HOW LIBRARO SOLVES IT ===================== --}}
<section class="lh-deco lh-section lh-solve" aria-labelledby="lh-solve-title">
    <span class="lh-wm" aria-hidden="true" data-lh-wm><b>SOLVED</b></span>
    <div class="lh-wrap">
        <div class="lh-head lh-head--center">
            <span class="lh-eyebrow lh-eyebrow--teal">{{ $T['solve']['eyebrow'] }}</span>
            <h2 class="lh-h2" id="lh-solve-title" data-reveal>{{ $T['solve']['title'] }}</h2>
            <p class="lh-lead">{{ $T['solve']['lead'] }}</p>
        </div>
        <ol class="lh-solve-list">
            @foreach($T['solve']['items'] as $i => [$fix, $text])
                <li class="lh-solve-row">
                    <div class="lh-solve-before">
                        <small>{{ $T['solve']['before'] }}</small>
                        <span><x-lb-icon :name="$I['pains'][$i]" />{{ $T['pains']['items'][$i][0] }}</span>
                    </div>
                    <span class="lh-solve-arrow" aria-hidden="true"><x-lb-icon name="arrow-right" /></span>
                    <div class="lh-solve-after">
                        <span class="lh-solve-ico"><x-lb-icon :name="$I['fixes'][$i]" /></span>
                        <div><small>{{ $T['solve']['after'] }}</small><h3>{{ $fix }}</h3><p>{{ $text }}</p></div>
                    </div>
                </li>
            @endforeach
        </ol>
        <div class="lh-center-actions">
            <a href="{{ route('register') }}" class="lb-btn lb-btn--accent">{{ $T['hero']['cta'] }}<x-lb-icon name="arrow-right" size="18" /></a>
            <span class="lh-mini-refund"><x-lb-icon name="shield-check" />{{ $T['pricing']['refund'] }}</span>
        </div>
    </div>
</section>

{{-- ===================== 5. OPERATIONS SHOWCASE (MacBook, light + dark) ===================== --}}
<section class="lh-deco lh-section lh-show" id="product" aria-labelledby="lh-show-title" data-lh-ops>
    <span class="lh-wm" aria-hidden="true" data-lh-wm><b>OPERATIONS</b></span>
    <div class="lh-wrap">
        <div class="lh-head lh-head--center">
            <span class="lh-eyebrow">{{ $T['ops']['eyebrow'] }}</span>
            <h2 class="lh-h2" id="lh-show-title" data-reveal>{{ $T['ops']['title'] }}</h2>
            <p class="lh-lead">{{ $T['ops']['lead'] }}</p>
        </div>
        <div class="lh-show-bar">
            <div class="lh-show-tabs" role="tablist" aria-label="{{ $T['ops']['eyebrow'] }}" data-lh-tabs>
                @foreach($opsTabs as $k => $tab)
                    <button type="button" role="tab" aria-controls="lh-show-screen" aria-selected="{{ $k === 0 ? 'true' : 'false' }}" tabindex="{{ $k === 0 ? '0' : '-1' }}" data-light="{{ $tab['light'] }}" data-dark="{{ $tab['dark'] }}" data-desc="{{ $tab['desc'] }}" data-alt="{{ $tab['label'] }}">
                        <x-lb-icon :name="$opsIcons[$tab['key']]" />{{ $tab['label'] }}
                    </button>
                @endforeach
            </div>
            <div class="lh-theme-seg" role="group" aria-label="Portal theme" data-lh-theme-seg>
                <button type="button" data-mode="light" aria-pressed="true"><x-lb-icon name="sun" />{{ $T['ops']['light'] }}</button>
                <button type="button" data-mode="dark" aria-pressed="false"><x-lb-icon name="moon" />{{ $T['ops']['dark'] }}</button>
            </div>
        </div>
        <div class="lh-mac lh-mac--big lh-mode-light" id="lh-show-screen" role="tabpanel" aria-live="polite" data-lh-show-mac>
            <div class="lh-mac-screen">
                <div class="lh-mac-notch" aria-hidden="true"></div>
                <img src="{{ $opsTabs[0]['light'] }}" alt="Libraro {{ $opsTabs[0]['label'] }}" width="1600" height="1000" loading="lazy" decoding="async" class="lh-shot lh-shot--light" data-lh-show-img="light">
                <img src="{{ $opsTabs[0]['dark'] }}" alt="Libraro {{ $opsTabs[0]['label'] }} (dark mode)" width="1600" height="1000" loading="lazy" decoding="async" class="lh-shot lh-shot--dark" data-lh-show-img="dark">
            </div>
            <div class="lh-mac-base" aria-hidden="true"><i></i></div>
        </div>
        <p class="lh-show-desc" data-lh-show-desc>{{ $opsTabs[0]['desc'] }}</p>
    </div>
</section>

{{-- ===================== 6. DESKTOP + MOBILE ===================== --}}
<section class="lh-section lh-mobile" aria-labelledby="lh-mobile-title">
    <div class="lh-wrap">
        <div class="lh-mobile-band">
            <div class="lh-mobile-copy">
                <span class="lh-eyebrow">{{ $T['mobile']['eyebrow'] }}</span>
                <h2 class="lh-h2" id="lh-mobile-title" data-reveal>{{ $T['mobile']['title'] }}</h2>
                <p class="lh-lead">{{ $T['mobile']['lead'] }}</p>
                <a href="{{ route('register') }}" class="lb-btn">{{ $T['hero']['cta'] }}<x-lb-icon name="arrow-right" size="18" /></a>
            </div>
            <ul class="lh-mobile-tiles">
                @foreach($T['mobile']['points'] as [$icon, $text])<li><i><x-lb-icon :name="$icon" /></i><b>{{ $text }}</b></li>@endforeach
            </ul>
        </div>
    </div>
</section>

{{-- ===================== 7. A DAY AT YOUR LIBRARY ===================== --}}
<section id="operations" class="lh-deco lb-ops" aria-labelledby="lb-ops-title">
    <span class="lh-wm" aria-hidden="true" data-lh-wm><b>EVERY DAY</b></span>
    <div class="lh-bgimg" aria-hidden="true" style="--lh-bg: url('{{ $img('slide-library', 1600) }}')"></div>
    <div class="lb-ops-head">
        <div>
            <span class="lb-eyebrow">{{ $T['day']['eyebrow'] }}</span>
            <h2 class="lb-h2" id="lb-ops-title" data-reveal>{{ $T['day']['title'] }}</h2>
        </div>
        <div class="lb-round-btns">
            <button type="button" class="lb-round-btn" data-lb-prev="ops" aria-label="{{ $T['day']['prev'] }}"><x-lb-icon name="chevron-left" /></button>
            <button type="button" class="lb-round-btn" data-lb-next="ops" aria-label="{{ $T['day']['next'] }}"><x-lb-icon name="chevron-right" /></button>
        </div>
    </div>
    <div class="lb-ops-track" data-lb-track="ops" tabindex="0" aria-label="{{ $T['day']['track'] }}">
        @foreach($T['day']['items'] as $i => [$title, $text, $time])
            @php [$icon, $mock, $alt] = $dayMeta[$i]; @endphp
            <article class="lb-op lb-op--{{ ($i % 6) + 1 }}">
                <div class="lb-op-photo">
                    <img src="{{ $img('op-' . $mock, 800) }}" alt="{{ $alt }}" width="800" height="600" loading="lazy" decoding="async">
                    <span class="lb-op-time"><x-lb-icon name="clock-3" />{{ $time }}</span>
                </div>
                <div class="lb-op-body">
                    <div class="lb-op-top">
                        <div class="lb-op-icon"><x-lb-icon :name="$icon" /></div>
                        <div class="lb-op-step">{{ $T['day']['step'] }} {{ $i + 1 }}</div>
                    </div>
                    <h3>{{ $title }}</h3>
                    <p>{{ $text }}</p>
                </div>
            </article>
        @endforeach
    </div>
</section>

{{-- ===================== 9. BENEFITS ===================== --}}
<section class="lh-deco lh-section lh-benefits" aria-labelledby="lh-ben-title">
    <span class="lh-wm" aria-hidden="true" data-lh-wm><b>GROWTH</b></span>
    <div class="lh-bgimg" aria-hidden="true" style="--lh-bg: url('{{ $img('study-girl', 1600) }}')"></div>
    <div class="lh-wrap">
        <div class="lh-head lh-head--center">
            <span class="lh-eyebrow">{{ $T['benefits']['eyebrow'] }}</span>
            <h2 class="lh-h2" id="lh-ben-title" data-reveal>{{ $T['benefits']['title'] }}</h2>
            <p class="lh-lead">{{ $T['benefits']['lead'] }}</p>
        </div>
        <div class="lh-ben-grid">
            @foreach($T['benefits']['items'] as $i => [$title, $text])
                <article class="lh-ben">
                    <span class="lh-ben-num" aria-hidden="true">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="lh-ben-ico"><x-lb-icon :name="$I['benefits'][$i]" /></span>
                    <h3>{{ $title }}</h3>
                    <p>{{ $text }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- ===================== 10. PRICING ===================== --}}
<section id="pricing" class="lh-deco lb-pricing lh-pricing" aria-labelledby="lh-pricing-title">
    <span class="lh-wm" aria-hidden="true" data-lh-wm><b>PREMIUM</b></span>
    <div class="lb-center-text">
        <span class="lh-eyebrow lh-eyebrow--gold">{{ $T['pricing']['eyebrow'] }}</span>
        <h2 class="lb-h2" id="lh-pricing-title" data-reveal>{{ $T['pricing']['title'] }}</h2>
        <p class="lb-lead">{{ $T['pricing']['lead'] }}</p>
        <div class="lb-billing" role="group" aria-label="Billing period">
            <button type="button" data-billing="1" aria-pressed="true">{{ $T['pricing']['monthly'] }}</button>
            <button type="button" data-billing="2" aria-pressed="false">{{ $T['pricing']['yearly'] }} <small>{{ $T['pricing']['save'] }}</small></button>
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
            <article class="lb-plan lh-plan lh-plan--{{ $i + 1 }} {{ $isPopular ? 'lb-plan--featured' : '' }}" data-plan-id="{{ $subscription->id }}">
                <div class="lb-plan-head">
                    <h3 class="lb-plan-name"><span class="lh-plan-dot" aria-hidden="true"></span>{{ $subscription->name }}</h3>
                    @if($isPopular)<span class="lb-plan-badge"><x-lb-icon name="star" size="12" />{{ $T['pricing']['popular'] }}</span>@endif
                </div>
                <p class="lb-plan-tag">{{ $T['pricing']['taglines'][(int) $subscription->id] ?? ($subscription->plan_description ?? '') }}</p>
                <div class="lb-plan-price" aria-live="polite">
                    <strong data-price-for="{{ $subscription->id }}">₹{{ number_format((float) ($subscription->monthly_fees ?? 0)) }}</strong>
                    <span data-per-for="{{ $subscription->id }}">{{ $T['pricing']['per_month'] }}</span>
                </div>
                <div class="lb-plan-note" data-note-for="{{ $subscription->id }}">{{ $T['pricing']['billed'] }}</div>
                <button type="button" class="lb-btn {{ $isPopular ? 'lb-btn--accent' : '' }} buy-now-btn" data-id="{{ $subscription->id }}">
                    {{ $T['pricing']['choose'] }} {{ $shortName }}
                </button>
                <div class="lh-plan-refund"><x-lb-icon name="shield-check" />{{ $T['pricing']['refund'] }}</div>
                <div class="lb-plan-rule"></div>
                <div class="lb-plan-delta">{{ $showDelta ? $tr($T['pricing']['plus'], [':p' => $prevName]) : $T['pricing']['key'] }}</div>
                <ul class="lb-plan-list">
                    @foreach($top as $f)<li><i><x-lb-icon name="check" /></i>{{ $f }}</li>@endforeach
                </ul>
                @if(count($included) > count($top))
                    <button type="button" class="lb-plan-all-btn" data-lb-plan-all="{{ $subscription->id }}" aria-haspopup="dialog">
                        <x-lb-icon name="list-checks" />{{ $tr($T['pricing']['all'], [':n' => count($included)]) }}
                    </button>
                    <template data-lb-plan-tpl="{{ $subscription->id }}" data-name="{{ $subscription->name }}" data-count="{{ count($included) }}" data-new-label="{{ $showDelta ? $T['pricing']['dialog']['new'] . ' · ' . $shortName : '' }}">
                        @foreach($included as $f)
                            <li class="{{ $showDelta && in_array($f, $delta, true) ? 'is-new' : '' }}"><i><x-lb-icon name="check" /></i><span>{{ $f }}</span>@if($showDelta && in_array($f, $delta, true))<em>{{ $T['pricing']['dialog']['new'] }}</em>@endif</li>
                        @endforeach
                    </template>
                @endif
            </article>
            @php $prevIncluded = $included; $prevName = $shortName; @endphp
        @endforeach
    </div>
    <p class="lb-pricing-foot">{{ $T['pricing']['gst'] }} <a href="#compare">{{ $T['pricing']['compare'] }} <x-lb-icon name="chevron-right" size="15" /></a></p>
</section>

{{-- ===================== 11. COMPARISON TABLE ===================== --}}
<section id="compare" class="lb-section lh-compare" aria-labelledby="lh-compare-title">
    <div class="lb-compare">
        <div class="lb-center-text">
            <h2 class="lb-h2" id="lh-compare-title" data-reveal>{{ $T['compare']['title'] }}</h2>
            <p class="lb-lead">{{ $T['compare']['lead'] }}</p>
        </div>
        <div class="lb-cmp-card">
            <div class="lb-cmp-head" aria-hidden="true">
                <div></div>
                @foreach($plansList->take(3) as $pi => $p)
                    <div class="{{ $pi === $popularIndex ? 'is-pop' : '' }}"><b>{{ \Illuminate\Support\Str::of($p->name)->replace(' Plan', '') }}</b><span data-cmp-price-for="{{ $p->id }}">₹{{ number_format((float) ($p->monthly_fees ?? 0)) }}/mo</span></div>
                @endforeach
            </div>
            @foreach($T['compare']['groups'] as $gi => [$gTitle, $rows])
                <button type="button" class="lb-cmp-group" aria-expanded="{{ $gi < 2 ? 'true' : 'false' }}" aria-controls="lh-cmp-{{ $gi }}">
                    <span>{{ $gTitle }}</span>
                    <i><x-lb-icon name="plus" class="lb-i-plus" /><x-lb-icon name="minus" class="lb-i-minus" /></i>
                </button>
                <div class="lb-cmp-rows" id="lh-cmp-{{ $gi }}" role="table" aria-label="{{ $gTitle }}" @if($gi >= 2) hidden @endif>
                    @foreach($rows as $row)
                        <div class="lb-cmp-row" role="row">
                            <div role="rowheader">{{ $row[0] }}</div>
                            @foreach(array_slice($row, 1) as $cell)
                                <div role="cell">
                                    @if($cell === true)
                                        <span class="lb-yes"><x-lb-icon name="check" /><span class="lb-sr-only">{{ $T['compare']['yes'] }}</span></span>
                                    @elseif($cell === false)
                                        <span class="lb-no"><x-lb-icon name="minus" /><span class="lb-sr-only">{{ $T['compare']['no'] }}</span></span>
                                    @else
                                        {{ $cell }}
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
        <div class="lb-cmp-actions">
            <button type="button" class="lb-btn lb-btn--ghost" data-cmp="expand"><x-lb-icon name="plus" size="16" />{{ $T['compare']['expand'] }}</button>
            <button type="button" class="lb-btn lb-btn--ghost" data-cmp="collapse"><x-lb-icon name="minus" size="16" />{{ $T['compare']['collapse'] }}</button>
        </div>
    </div>
</section>

{{-- ===================== REFUND (signature highlight) ===================== --}}
<section id="refund" class="lh-refund-wrap" aria-labelledby="lh-refund-title">
    <div class="lh-wrap">
        <div class="lh-refund">
            <div class="lh-refund-big" aria-hidden="true">
                <svg viewBox="0 0 200 200" class="lh-refund-ring"><defs><path id="lh-ring-path" d="M100,100 m-78,0 a78,78 0 1,1 156,0 a78,78 0 1,1 -156,0"/></defs><text><textPath href="#lh-ring-path" textLength="486" lengthAdjust="spacing">100% MONEY BACK • NO QUESTIONS ASKED • 7-DAY REFUND •</textPath></text></svg>
                <b>7</b><span>{{ $T['refund']['day'] }}</span>
            </div>
            <div class="lh-refund-copy">
                <h2 id="lh-refund-title" data-reveal>{{ $T['refund']['title'] }}</h2>
                <p>{{ $T['refund']['text'] }}</p>
                <div class="lh-refund-chips">
                    @foreach($T['refund']['chips'] as [$icon, $label])<span><x-lb-icon :name="$icon" />{{ $label }}</span>@endforeach
                </div>
                <div class="lh-refund-actions">
                    <a href="{{ route('register') }}" class="lb-btn lb-btn--accent">{{ $T['hero']['cta'] }}<x-lb-icon name="arrow-right" size="18" /></a>
                    <a href="{{ route('refund-policy') }}" class="lb-link-arrow">{{ $T['refund']['link'] }} <x-lb-icon name="chevron-right" size="16" /></a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===================== 12. REVIEWS ===================== --}}
<section id="feedback" class="lh-deco lh-section lh-reviews" aria-labelledby="lh-rev-title">
    <span class="lh-wm" aria-hidden="true" data-lh-wm><b>TRUSTED</b></span>
    <div class="lh-bgimg" aria-hidden="true" style="--lh-bg: url('{{ $img('slide-library', 1600) }}')"></div>
    <div class="lh-wrap">
        <div class="lh-head lh-head--row">
            <div>
                <span class="lh-eyebrow">{{ $T['reviews']['eyebrow'] }}</span>
                <h2 class="lh-h2" id="lh-rev-title" data-reveal>{{ $T['reviews']['title'] }}</h2>
                <p class="lh-lead">{{ $T['reviews']['lead'] }}</p>
            </div>
            @if($reviews->count() > 1)
                <div class="lh-arrows">
                    <button type="button" class="lb-round-btn" data-lb-prev="rv" aria-label="{{ $T['reviews']['prev'] }}"><x-lb-icon name="chevron-left" /></button>
                    <button type="button" class="lb-round-btn" data-lb-next="rv" aria-label="{{ $T['reviews']['next'] }}"><x-lb-icon name="chevron-right" /></button>
                </div>
            @endif
        </div>
    </div>
    <div class="lh-slider lh-slider--reviews" data-lb-track="rv" tabindex="0" aria-label="{{ $T['reviews']['title'] }}">
        @foreach($reviews as $r)
            <figure class="lh-review">
                <div class="lb-stars" role="img" aria-label="{{ $tr($T['reviews']['stars'], [':n' => $r['rating']]) }}">
                    @for($s = 1; $s <= 5; $s++)<x-lb-icon name="star" class="{{ $s > $r['rating'] ? 'is-off' : '' }}" />@endfor
                </div>
                <blockquote class="lh-review-text">“{{ $r['text'] }}”</blockquote>
                @if(mb_strlen($r['text']) > 220)
                    <button type="button" class="lb-review-more" data-text="{{ $r['text'] }}" data-name="{{ $r['name'] }}" data-sub="{{ trim($r['library'] . ($r['city'] ? ' · ' . $r['city'] : ''), ' ·') }}">{{ $T['reviews']['more'] }} <x-lb-icon name="chevron-right" size="14" /></button>
                @endif
                <figcaption class="lh-review-who">
                    <span class="lh-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($r['name'], 0, 1)) }}</span>
                    <div>
                        <b>{{ $r['name'] }}</b>
                        <span>{{ $r['library'] ?: $T['reviews']['owner'] }}</span>
                        @if($r['city'])<span class="lh-review-city"><x-lb-icon name="map-pin" size="12" />{{ $r['city'] }}</span>@endif
                    </div>
                </figcaption>
            </figure>
        @endforeach
    </div>
</section>

{{-- ===================== FAQ ===================== --}}
<section id="faq" class="lb-section lh-faq-wrap" aria-labelledby="lh-faq-title">
    <div class="lb-center-text">
        <h2 class="lb-h2" id="lh-faq-title" data-reveal>{{ $T['faq']['title'] }}</h2>
    </div>
    <div class="lb-faq">
        @foreach($T['faq']['items'] as $i => [$q, $a])
            <details @if($i === 0) open @endif>
                <summary>{{ $q }}<i aria-hidden="true"><x-lb-icon name="plus" /></i></summary>
                <p>{{ $a }}</p>
            </details>
        @endforeach
    </div>
</section>

{{-- ===================== 13. FINAL CTA ===================== --}}
<section class="lh-final" aria-labelledby="lh-final-title">
    <div class="lh-wrap">
        <div class="lh-final-card">
            <div class="lh-hero-glow" aria-hidden="true"></div>
            <span class="lh-wm lh-wm--card" aria-hidden="true" data-lh-wm><b>LIBRARO</b></span>
            <h2 id="lh-final-title" data-reveal>{{ $T['final']['title'] }}</h2>
            <p>{{ $T['final']['lead'] }}</p>
            <div class="lh-final-actions">
                <a href="{{ route('register') }}" class="lb-btn lb-btn--lg lb-btn--accent">{{ $T['final']['cta'] }}<x-lb-icon name="arrow-right" size="18" /></a>
                <a href="tel:+918114479678" class="lb-btn lb-btn--lg lh-btn-glass"><x-lb-icon name="phone" size="18" />{{ $T['final']['call'] }}</a>
                <a href="{{ $wa }}" target="_blank" rel="noopener" class="lb-btn lb-btn--lg lh-btn-glass lh-btn-wa"><x-lb-brand name="whatsapp" />{{ $T['final']['wa'] }}</a>
            </div>
            <div class="lh-final-refund"><x-lb-icon name="shield-check" />{{ $T['hero']['refund'] }} · {{ $T['hero']['refund_sub'] }}</div>
        </div>
    </div>
</section>

{{-- ===================== THANK-YOU NOTE ===================== --}}
<section class="lh-love" id="thanks" aria-label="A note from Libraro">
    <p class="lh-love-line" data-lh-love>{!! str_replace(':heart', '<svg class="lh-heart" viewBox="0 0 24 24" aria-label="love" role="img"><path d="M12 21s-7.6-4.6-9.8-9.3C.7 8.4 2.6 4.5 6.4 4.1c2.1-.2 3.9.9 5.6 2.9 1.7-2 3.5-3.1 5.6-2.9 3.8.4 5.7 4.3 4.2 7.6C19.6 16.4 12 21 12 21z"/></svg>', e($T['love'][0])) !!}</p>
    <p class="lh-love-sub" data-lh-love>{{ $T['love'][1] }}</p>
</section>

{{-- Mobile sticky CTA --}}
<div class="lh-sticky" data-lh-sticky hidden>
    <a href="tel:+918114479678" class="lh-sticky-call" aria-label="{{ $T['nav']['call'] }}"><x-lb-icon name="phone" /></a>
    <a href="{{ route('register') }}" class="lb-btn lb-btn--accent">{{ $T['hero']['cta'] }}</a>
</div>

<dialog class="lb-dialog lb-plan-dialog" id="lb-plan-dialog" aria-labelledby="lb-pd-name">
    <div class="lb-dialog-body">
        <button type="button" class="lb-dialog-close" aria-label="Close"><x-lb-icon name="x" size="18" /></button>
        <span class="lb-eyebrow">{{ $T['pricing']['dialog']['eyebrow'] }}</span>
        <h2 class="lb-pd-name" id="lb-pd-name" data-pd-name></h2>
        <div class="lb-pd-price"><strong data-pd-price></strong><span data-pd-per></span></div>
        <p class="lb-pd-sub" data-pd-sub></p>
        <ul class="lb-pd-list" data-pd-list></ul>
        <div class="lb-pd-foot">
            <button type="button" class="lb-btn lb-btn--block" data-pd-choose>{{ $T['pricing']['dialog']['choose'] }}</button>
            <p>{{ $T['pricing']['dialog']['foot'] }}</p>
        </div>
    </div>
</dialog>

<dialog class="lb-dialog" id="lb-review-dialog" aria-labelledby="lb-dialog-name">
    <div class="lb-dialog-body">
        <button type="button" class="lb-dialog-close" aria-label="Close"><x-lb-icon name="x" size="18" /></button>
        <div class="lb-review-who"><i aria-hidden="true" data-r-initial></i><div><b id="lb-dialog-name" data-r-name></b><span data-r-sub></span></div></div>
        <p data-r-text></p>
    </div>
</dialog>
@endsection

@push('scripts')
    <script src="{{ asset('public/js/lb-home.js') }}?v={{ @filemtime(public_path('js/lb-home.js')) }}" defer></script>
    <script type="module" src="{{ asset('public/js/lb-home-motion.js') }}?v={{ @filemtime(public_path('js/lb-home-motion.js')) }}"></script>
@endpush
