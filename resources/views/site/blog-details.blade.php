@extends('lb.layouts.site')
@php
    extract(require resource_path('views/lb/i18n/boot.php')); // website language: lb/i18n/boot.php

    // Language the article itself is shown in: Hindi only when this post has a Hindi version.
    $cl = $data->contentLang($lbLang);
    $hasHi = $data->hasHindi();
    $tr = fn ($f) => $data->tr($f, $cl);

    $postTitle = $tr('page_title');
    $seoTitle = $tr('meta_title') ?: $postTitle;
    if (stripos($seoTitle, 'libraro') === false) { $seoTitle .= ' | Libraro'; }
    $seoDesc = $tr('meta_description') ?: $data->summary($cl, 155);
    $cover = $data->headerImageUrlFor($cl);
    $coverAlt = $tr('image_alt') ?: $postTitle;
    $urlEn = $data->urlFor('en');
    $urlHi = $hasHi ? $data->urlFor('hi') : null;
    $postUrl = $cl === 'hi' ? $urlHi : $urlEn;
    // Hinglish UI, or Hindi asked for a post without Hindi, shows the English article: point search engines at it.
    $canonical = $data->canonical_url ?: $postUrl;
    $lbNoHreflang = true; // this page prints its own hreflang (only the languages the article really has)

    $readMins = $data->readingTimeFor($cl);
    $author = $data->author_name ?: 'Libraro Team';
    $initials = collect(preg_split('/\s+/', trim($author)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');
    $published = $data->published_at ?? $data->created_at;
    $updated = $data->updated_at ?? $published;
    $showUpdated = $updated && $published && $updated->gt($published->copy()->addDay());
    $primaryCat = $categories->first();
@endphp

@section('seo')
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDesc }}">
    <meta name="keywords" content="{{ $tr('meta_keyword') ?: implode(', ', $tagsArray ?? []) }}">
    <link rel="canonical" href="{{ $canonical }}" />
    <meta name="robots" content="{{ $data->meta_robots ?: 'index, follow' }}, max-image-preview:large">
    <link rel="alternate" hreflang="en-IN" href="{{ $urlEn }}">
    @if($urlHi)<link rel="alternate" hreflang="hi-IN" href="{{ $urlHi }}">@endif
    <link rel="alternate" hreflang="x-default" href="{{ $urlEn }}">

    <meta property="og:site_name" content="Libraro" />
    <meta property="og:locale" content="{{ $cl === 'hi' ? 'hi_IN' : 'en_IN' }}" />
    <meta property="og:title" content="{{ $tr('meta_title') ?: $postTitle }}" />
    <meta property="og:description" content="{{ $seoDesc }}" />
    <meta property="og:type" content="article" />
    <meta property="og:url" content="{{ $postUrl }}" />
    <meta property="og:image" content="{{ $cover }}" />
    <meta property="og:image:width" content="1600" />
    <meta property="og:image:height" content="900" />
    <meta property="og:image:alt" content="{{ $coverAlt }}" />
    <meta property="article:published_time" content="{{ optional($published)->toIso8601String() }}" />
    <meta property="article:modified_time" content="{{ optional($updated)->toIso8601String() }}" />
    @if($primaryCat)<meta property="article:section" content="{{ $primaryCat->name }}" />@endif
    @foreach(($tagsArray ?? []) as $tg)<meta property="article:tag" content="{{ $tg }}" />@endforeach

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $tr('meta_title') ?: $postTitle }}">
    <meta name="twitter:description" content="{{ $seoDesc }}">
    <meta name="twitter:image" content="{{ $cover }}">

    @foreach($data->jsonLdFor($cl) as $ld)
    <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endforeach
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => $t('Home'), 'item' => route('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $t('Blog'), 'item' => route('blog') . ($cl === 'hi' ? '?lang=hi' : '')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $postTitle, 'item' => $postUrl],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('public/css/lb-blog.css') }}?v={{ @filemtime(public_path('css/lb-blog.css')) }}">
@endpush

@section('body_class', 'lb-blog-page')

@section('content')
<div class="lb-progress" aria-hidden="true"><span id="lb-progress-bar"></span></div>

<div class="lb-post" lang="{{ $cl === 'hi' ? 'hi' : 'en' }}">
    <header class="lb-post-hero">
        <nav class="lb-crumbs" aria-label="{{ $t('Breadcrumb') }}">
            <a href="{{ route('/') }}">{{ $t('Home') }}</a><x-lb-icon name="chevron-right" />
            <a href="{{ route('blog') }}">{{ $t('Blog') }}</a><x-lb-icon name="chevron-right" />
            @if($primaryCat)
                <a href="{{ route('blog', ['category' => $primaryCat->id]) }}">{{ $t($primaryCat->name) }}</a>
            @else
                <span aria-current="page">{{ \Illuminate\Support\Str::limit($postTitle, 48) }}</span>
            @endif
        </nav>

        <div class="lb-article-meta-top">
            @foreach($categories as $cat)
                <a href="{{ route('blog', ['category' => $cat->id]) }}" class="lb-pill"><x-lb-icon name="tag" />{{ $t($cat->name) }}</a>
            @endforeach
            <span class="lb-article-read"><x-lb-icon name="clock-3" />{{ $t(':n min read', [':n' => $readMins]) }}</span>
            @if($hasHi)
                <a class="lb-lang-switch" href="{{ $cl === 'hi' ? $urlEn . '?lang=en' : $urlHi }}" hreflang="{{ $cl === 'hi' ? 'en' : 'hi' }}" lang="{{ $cl === 'hi' ? 'en' : 'hi' }}">
                    <x-lb-icon name="arrow-left-right" size="14" />{{ $cl === 'hi' ? 'Read in English' : 'हिंदी में पढ़ें' }}
                </a>
            @endif
        </div>

        <h1>{{ $postTitle }}</h1>
        @if($tr('excerpt'))
            <p class="lb-article-dek">{{ $tr('excerpt') }}</p>
        @endif

        <div class="lb-byline">
            <div class="lb-avatar" aria-hidden="true">{{ $initials ?: 'L' }}</div>
            <div class="lb-byline-text">
                <b>{{ $author }}</b>
                <span>
                    @if($showUpdated)
                        {{ $t('Updated') }} <time datetime="{{ $updated->toDateString() }}">{{ $updated->format('M d, Y') }}</time>
                    @else
                        <time datetime="{{ optional($published)->toDateString() }}">{{ $data->formatted_date }}</time>
                    @endif
                    · {{ $t(':n views', [':n' => number_format($data->views_count ?? 0)]) }}
                </span>
            </div>
            <div class="lb-byline-actions">
                <button type="button" class="lb-icon-btn" data-lb-save aria-label="{{ $t('Save article') }}" aria-pressed="false"><x-lb-icon name="bookmark" /></button>
                <button type="button" class="lb-icon-btn" data-lb-share aria-label="{{ $t('Share article') }}"><x-lb-icon name="share-2" /></button>
            </div>
        </div>
    </header>

    <figure class="lb-article-cover">
        <img src="{{ $cover }}" alt="{{ $coverAlt }}" width="1600" height="900" fetchpriority="high" decoding="async" onerror="this.onerror=null;this.src='{{ asset('public/img/site/blog-cover-1600.webp') }}'">
    </figure>

    <div class="lb-post-layout">
        <article class="lb-article">
            <nav class="lb-toc" id="tocContainer" aria-labelledby="lb-toc-title" hidden>
                <button type="button" class="lb-toc-title" id="lb-toc-title" aria-expanded="true" aria-controls="tocList">
                    <span><x-lb-icon name="list" size="16" />{{ $t('In this article') }}</span><x-lb-icon name="chevron-down" size="18" />
                </button>
                <ol id="tocList"></ol>
            </nav>

            <div class="lb-prose" id="postArticleBody" data-faq-title="{{ $t('Frequently asked questions') }}">
                {!! $tr('page_content') !!}
            </div>

            {{-- Mid-article call to action, moved into place by lb-blog.js (before the 3rd section). --}}
            <template id="lb-inline-cta">
                <aside class="lb-inline-cta" aria-label="{{ $t('Libraro') }}">
                    <div class="lb-inline-cta-ic"><x-lb-icon name="layout-dashboard" size="22" /></div>
                    <div>
                        <b>{{ $t('Run your library from your phone') }}</b>
                        <p>{{ $t('Seats, fees, WhatsApp reminders and QR attendance in one app. Plans from ₹49 a month.') }}</p>
                    </div>
                    <a href="{{ route('register') }}" class="lb-btn">{{ $t('Start free') }}</a>
                </aside>
            </template>

            <footer class="lb-article-foot">
                @if(!empty($tagsArray) && is_array($tagsArray))
                    <div class="lb-tags">
                        @foreach($tagsArray as $tag)
                            <a href="{{ route('blog', ['tag' => $tag]) }}">#{{ $tag }}</a>
                        @endforeach
                    </div>
                @endif

                <div class="lb-share-row">
                    <span>{{ $t('Share this article') }}</span>
                    <div class="lb-share-btns">
                        <a href="https://api.whatsapp.com/send?text={{ urlencode($postTitle . ' - ' . $postUrl) }}" target="_blank" rel="noopener" aria-label="{{ $t('Share on :site', [':site' => 'WhatsApp']) }}" class="is-wa"><x-lb-brand name="whatsapp" /></a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($postUrl) }}" target="_blank" rel="noopener" aria-label="{{ $t('Share on :site', [':site' => 'Facebook']) }}"><x-lb-brand name="facebook" /></a>
                        <a href="https://twitter.com/intent/tweet?text={{ urlencode($postTitle) }}&url={{ urlencode($postUrl) }}" target="_blank" rel="noopener" aria-label="{{ $t('Share on :site', [':site' => 'X']) }}"><x-lb-brand name="x" /></a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($postUrl) }}" target="_blank" rel="noopener" aria-label="{{ $t('Share on :site', [':site' => 'LinkedIn']) }}"><x-lb-brand name="linkedin" /></a>
                        <button type="button" data-lb-copy aria-label="{{ $t('Copy link') }}"><x-lb-icon name="link" /></button>
                    </div>
                </div>

                <div class="lb-helpful">
                    <span>{{ $t('Was this helpful?') }}</span>
                    <button type="button" class="lb-btn lb-btn--ghost" data-lb-helpful><x-lb-icon name="thumbs-up" size="18" /><span>{{ $t('Yes') }}</span></button>
                </div>

                <div class="lb-author-box">
                    <div class="lb-avatar lb-avatar--lg" aria-hidden="true">{{ $initials ?: 'L' }}</div>
                    <div>
                        <small>{{ $t('Written by') }}</small>
                        <b>{{ $author }}</b>
                        <p>{{ $t('The Libraro team builds software for study libraries and reading rooms across India, and works every day with owners running 20 to 500 seats.') }}</p>
                    </div>
                </div>
            </footer>
        </article>

        <aside class="lb-post-aside" aria-label="{{ $t('More from Libraro') }}">
            <div class="lb-aside-card lb-aside-card--brand">
                <x-lb-icon name="library-big" size="26" />
                <b>{{ $t('Own a study library?') }}</b>
                <p>{{ $t('Libraro handles seats, fees, reminders and attendance so you can focus on your students.') }}</p>
                <a href="{{ route('register') }}" class="lb-btn lb-btn--light">{{ $t('Start free') }} <x-lb-icon name="arrow-right" size="16" /></a>
            </div>
            <div class="lb-aside-card">
                <x-lb-icon name="map-pin" size="22" />
                <b>{{ $t('Looking for a library near you?') }}</b>
                <p>{{ $t('Compare study libraries by seats, timings and fees, and book online.') }}</p>
                <a href="{{ route('find-my-library') }}" class="lb-aside-link">{{ $t('Find my library') }} <x-lb-icon name="chevron-right" size="16" /></a>
            </div>
            @if(isset($popularBlogs) && $popularBlogs->isNotEmpty())
                <div class="lb-aside-card lb-aside-popular">
                    <b>{{ $t('Most read') }}</b>
                    <ol>
                        @foreach($popularBlogs->take(4) as $pop)
                            <li><a href="{{ $pop->urlFor($pop->contentLang($lbLang)) }}">{{ $pop->tr('page_title', $pop->contentLang($lbLang)) }}</a></li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </aside>
    </div>
</div>

@if(isset($relatedBlogs) && $relatedBlogs->isNotEmpty())
    <section class="lb-related" aria-labelledby="lb-related-title">
        <div class="lb-related-head">
            <h2 id="lb-related-title">{{ $t('Read next') }}</h2>
            <a href="{{ route('blog') }}">{{ $t('All articles') }} <x-lb-icon name="chevron-right" size="16" /></a>
        </div>
        <div class="lb-related-track">
            @foreach($relatedBlogs as $rel)
                @php($rl = $rel->contentLang($lbLang))
                @php($firstCat = $rel->categories_models->first())
                <a href="{{ $rel->urlFor($rl) }}" class="lb-related-card">
                    <div class="lb-related-img"><img src="{{ $rel->headerImageUrlFor($rl) }}" alt="" loading="lazy" decoding="async" width="800" height="450" onerror="this.onerror=null;this.src='{{ asset('public/img/site/blog-inline-800.webp') }}'"></div>
                    <div class="lb-related-body">
                        @if($firstCat)<span class="lb-related-cat"><x-lb-icon name="tag" size="14" />{{ $t($firstCat->name) }}</span>@endif
                        <h3>{{ \Illuminate\Support\Str::limit($rel->tr('page_title', $rl), 80) }}</h3>
                        <span class="lb-related-date">{{ $t(':n min read', [':n' => $rel->readingTimeFor($rl)]) }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif

<section class="lb-article-cta" aria-labelledby="lb-cta-title">
    <div>
        <span class="lb-cta-eyebrow">{{ $t('Built for Indian study libraries') }}</span>
        <h2 id="lb-cta-title">{{ $t('Put this to work in your library.') }}</h2>
        <p>{{ $t('Bookings, fee reminders and seat reports, all in one place.') }}</p>
        <div class="lb-cta-btns">
            <a href="{{ route('register') }}" class="lb-btn lb-btn--light">{{ $t('Start free') }} <x-lb-icon name="arrow-right" size="18" /></a>
            <a href="{{ route('find-my-library') }}" class="lb-btn lb-btn--outline-light">{{ $t('Find a library near me') }}</a>
        </div>
    </div>
</section>

<div class="lb-actionbar" aria-label="{{ $t('Article actions') }}">
    <button type="button" data-lb-save aria-label="{{ $t('Save article') }}" aria-pressed="false"><x-lb-icon name="bookmark" /></button>
    <button type="button" data-lb-share aria-label="{{ $t('Share article') }}"><x-lb-icon name="share-2" /></button>
    <span class="lb-actionbar-sep"></span>
    <button type="button" class="lb-actionbar-top" data-lb-top aria-label="{{ $t('Back to top') }}"><span id="lb-progress-pct">0%</span><x-lb-icon name="arrow-up" size="16" /></button>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('public/js/lb-blog.js') }}?v={{ @filemtime(public_path('js/lb-blog.js')) }}" defer></script>
@endpush
