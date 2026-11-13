@extends('lb.layouts.site')
@php
    extract(require resource_path('views/lb/i18n/boot.php')); // website language: lb/i18n/boot.php

    $activeCat = (int) request('category');
    $search = request('search');
    $tag = request('tag');
    $isFiltered = $search || $activeCat || $tag || $data->currentPage() > 1;
    $showFeatured = isset($featuredBlog) && $featuredBlog && !$isFiltered;
    $activeCatModel = $activeCat ? $categories->firstWhere('id', $activeCat) : null;
    $L = fn ($b) => $b->contentLang($lbLang); // language a card is shown in

    $seoTitle = $activeCatModel
        ? $t($activeCatModel->name) . ' – ' . $t('Guides for study library owners') . ' | Libraro'
        : $t('Library Management Blog: Guides for Study Library Owners | Libraro');
    $seoDesc = $t('Practical guides on running a study library in India: seat management, fee collection, QR attendance, library software and how to start a library business.');
    $blogUrl = route('blog');
    $selfUrl = $blogUrl . ($lbLang === 'hi' ? '?lang=hi' : '');
    // Filtered and paged lists are thin duplicates of the hub: keep them out of the index, follow the links.
    $robots = ($search || $tag || $data->currentPage() > 1) ? 'noindex, follow' : 'index, follow, max-image-preview:large';
    $lbNoHreflang = true;
@endphp

@section('seo')
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDesc }}">
    <meta name="robots" content="{{ $robots }}">
    <link rel="canonical" href="{{ $activeCatModel ? $blogUrl . '?category=' . $activeCat . ($lbLang === 'hi' ? '&lang=hi' : '') : $selfUrl }}" />
    <link rel="alternate" hreflang="en-IN" href="{{ $blogUrl }}">
    <link rel="alternate" hreflang="hi-IN" href="{{ $blogUrl }}?lang=hi">
    <link rel="alternate" hreflang="x-default" href="{{ $blogUrl }}">
    <meta property="og:site_name" content="Libraro" />
    <meta property="og:locale" content="{{ $lbLang === 'hi' ? 'hi_IN' : 'en_IN' }}" />
    <meta property="og:title" content="{{ $seoTitle }}" />
    <meta property="og:description" content="{{ $seoDesc }}" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ $selfUrl }}" />
    <meta property="og:image" content="{{ asset('public/img/site/blog-cover-1600.webp') }}" />
    <meta name="twitter:card" content="summary_large_image">
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Blog',
        '@id' => $blogUrl . '#blog',
        'name' => $t('Libraro Blog'),
        'description' => $seoDesc,
        'url' => $selfUrl,
        'inLanguage' => $lbLang === 'hi' ? 'hi-IN' : 'en-IN',
        'publisher' => ['@type' => 'Organization', 'name' => 'Libraro', 'url' => route('/')],
        'blogPost' => $data->getCollection()->map(fn ($b) => [
            '@type' => 'BlogPosting',
            'headline' => \Illuminate\Support\Str::limit($b->tr('page_title', $L($b)), 110, ''),
            'url' => $b->urlFor($L($b)),
            'image' => $b->headerImageUrlFor($L($b)),
            'datePublished' => optional($b->published_at ?? $b->created_at)->toIso8601String(),
        ])->values(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @if($data->previousPageUrl())<link rel="prev" href="{{ $data->previousPageUrl() }}">@endif
    @if($data->nextPageUrl())<link rel="next" href="{{ $data->nextPageUrl() }}">@endif
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('public/css/lb-blog.css') }}?v={{ @filemtime(public_path('css/lb-blog.css')) }}">
@endpush

@section('content')
    <header class="lb-blog-hero">
        <nav class="lb-crumbs-c" aria-label="{{ $t('Breadcrumb') }}">
            <a href="{{ route('/') }}">{{ $t('Home') }}</a><x-lb-icon name="chevron-right" />
            <span aria-current="page">{{ $t('Blog') }}</span>
        </nav>
        <span class="lb-eyebrow">{{ $t('Libraro journal') }}</span>
        <h1>
            @if($activeCatModel)
                {{ $t($activeCatModel->name) }}
            @else
                {{ $t('Grow your study library with') }} <span class="lb-blog-hero-mark">{{ $t('practical guides') }}</span>
            @endif
        </h1>
        <p class="lb-lead">{{ $t('Seat management, fee collection, QR attendance and starting a library business, written for library owners across India.') }}</p>

        <form class="lb-blog-search lb-input" method="GET" action="{{ route('blog') }}" role="search">
            <x-lb-icon name="search" />
            <input type="search" name="search" value="{{ $search }}" placeholder="{{ $t('Search guides, e.g. seat booking') }}" aria-label="{{ $t('Search articles') }}">
            @if($activeCat)<input type="hidden" name="category" value="{{ $activeCat }}">@endif
            @if($lbLang !== 'en')<input type="hidden" name="lang" value="{{ $lbLang }}">@endif
            <button type="submit" class="lb-btn">{{ $t('Search') }}</button>
        </form>

        <ul class="lb-blog-stats" aria-label="{{ $t('About this blog') }}">
            <li><x-lb-icon name="newspaper" size="16" />{{ $t(':n guides', [':n' => $totalPosts ?? $data->total()]) }}</li>
            <li><x-lb-icon name="arrow-left-right" size="16" />English · हिंदी</li>
            <li><x-lb-icon name="badge-check" size="16" />{{ $t('Written with real library owners') }}</li>
        </ul>
    </header>

    <div class="lb-bloglist">
        @if(isset($categories) && $categories->isNotEmpty())
            <nav class="lb-topic-row" aria-label="{{ $t('Topics') }}">
                <a href="{{ route('blog', array_filter(['search' => $search])) }}" class="lb-chip {{ !$activeCat ? 'is-on' : '' }}" @if(!$activeCat) aria-current="page" @endif>{{ $t('All topics') }}</a>
                @foreach($categories as $cat)
                    <a href="{{ route('blog', array_filter(['category' => $cat->id, 'search' => $search])) }}" class="lb-chip {{ $activeCat === (int) $cat->id ? 'is-on' : '' }}" @if($activeCat === (int) $cat->id) aria-current="page" @endif>{{ $t($cat->name) }}</a>
                @endforeach
            </nav>
        @endif

        @if($tag || $search)
            <p class="lb-filter-note">
                @if($search){{ $t('Results for') }} <b>“{{ $search }}”</b>@endif
                @if($tag){{ $t('Showing articles tagged') }} <b>#{{ $tag }}</b>@endif
                · <a href="{{ route('blog') }}">{{ $t('Clear') }}</a>
            </p>
        @endif

        @if($showFeatured)
            @php($fl = $L($featuredBlog))
            @php($fCat = $featuredBlog->categories_models->first())
            <a href="{{ $featuredBlog->urlFor($fl) }}" class="lb-featured" data-reveal>
                <div class="lb-featured-img"><img src="{{ $featuredBlog->headerImageUrlFor($fl) }}" alt="{{ $featuredBlog->tr('image_alt', $fl) ?: $featuredBlog->tr('page_title', $fl) }}" width="1600" height="900" fetchpriority="high" decoding="async"></div>
                <div class="lb-featured-body">
                    <span class="lb-pill"><x-lb-icon name="star" />{{ $t('Start here') }}{{ $fCat ? ' · ' . $t($fCat->name) : '' }}</span>
                    <h2>{{ $featuredBlog->tr('page_title', $fl) }}</h2>
                    <p>{{ $featuredBlog->summary($fl, 170) }}</p>
                    <span class="lb-featured-meta"><x-lb-icon name="clock-3" size="14" />{{ $t(':n min read', [':n' => $featuredBlog->readingTimeFor($fl)]) }} · {{ $featuredBlog->formatted_date }}</span>
                    <span class="lb-featured-go">{{ $t('Read the guide') }} <x-lb-icon name="arrow-right" size="16" /></span>
                </div>
            </a>
        @endif

        @if($data->isNotEmpty())
            <div class="lb-post-grid">
                @foreach($data as $i => $value)
                    @continue($showFeatured && $value->id === $featuredBlog->id)
                    @php($vl = $L($value))
                    @php($firstCat = $value->categories_models->first())
                    <a href="{{ $value->urlFor($vl) }}" class="lb-post-card" lang="{{ $vl }}">
                        <div class="lb-related-img"><img src="{{ $value->headerImageUrlFor($vl) }}" alt="{{ $value->tr('image_alt', $vl) ?: $value->tr('page_title', $vl) }}" loading="lazy" decoding="async" width="800" height="450"></div>
                        <div class="lb-related-body">
                            <div class="lb-card-meta">
                                @if($firstCat)<span class="lb-related-cat">{{ $t($firstCat->name) }}</span>@endif
                                @if($value->hasHindi() && $vl === 'en')<span class="lb-card-lang" title="{{ $t('Also in Hindi') }}">हिंदी</span>@endif
                            </div>
                            <h3>{{ $value->tr('page_title', $vl) }}</h3>
                            <p>{{ $value->summary($vl, 120) }}</p>
                            <span class="lb-related-date"><x-lb-icon name="clock-3" size="14" />{{ $t(':n min read', [':n' => $value->readingTimeFor($vl)]) }}</span>
                        </div>
                    </a>
                    @if($loop->iteration === 4 && !$isFiltered)
                        <aside class="lb-grid-cta" aria-label="Libraro">
                            <x-lb-icon name="sparkles" size="24" />
                            <b>{{ $t('Stop chasing fees on WhatsApp.') }}</b>
                            <p>{{ $t('Libraro sends reminders and receipts for you, and shows every free seat by shift.') }}</p>
                            <a href="{{ route('register') }}" class="lb-btn lb-btn--light">{{ $t('Start free') }}</a>
                        </aside>
                    @endif
                @endforeach
            </div>
            {{ $data->links('lb.partials.pagination') }}
        @else
            <div class="lb-empty">
                <div class="lb-ok"><x-lb-icon name="book-open" size="28" /></div>
                <h2>{{ $t('No articles found') }}</h2>
                <p>{{ $t('Try a different search or category.') }}</p>
                <a href="{{ route('blog') }}" class="lb-btn">{{ $t('See all articles') }}</a>
            </div>
        @endif

        <section class="lb-blog-paths" aria-labelledby="lb-paths-title">
            <h2 id="lb-paths-title">{{ $t('What brings you here?') }}</h2>
            <div class="lb-paths-grid">
                <a href="{{ route('register') }}" class="lb-path">
                    <x-lb-icon name="library-big" size="26" />
                    <b>{{ $t('I run a study library') }}</b>
                    <span>{{ $t('Manage seats, fees and attendance in one app.') }}</span>
                </a>
                <a href="{{ route('blog', ['search' => 'start']) }}" class="lb-path">
                    <x-lb-icon name="lightbulb" size="26" />
                    <b>{{ $t('I want to start a library') }}</b>
                    <span>{{ $t('Costs, setup, pricing and getting your first 50 students.') }}</span>
                </a>
                <a href="{{ route('find-my-library') }}" class="lb-path">
                    <x-lb-icon name="map-pin" size="26" />
                    <b>{{ $t('I am looking for a library') }}</b>
                    <span>{{ $t('Find study libraries near you and book a seat online.') }}</span>
                </a>
            </div>
        </section>
    </div>
@endsection
