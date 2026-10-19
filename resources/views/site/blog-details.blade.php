@extends('lb.layouts.site')

@section('seo')
    <title>{{ $data->meta_title ?: $data->page_title }} | Libraro</title>
    <meta name="description" content="{{ $data->meta_description ?: ($data->excerpt ?: Str::limit(strip_tags($data->page_content), 155)) }}">
    <meta name="keywords" content="{{ $data->meta_keyword ?: (is_array($tagsArray) ? implode(',', $tagsArray) : '') }}">
    <link rel="canonical" href="{{ $data->canonical_url ?: route('blog-detail', ['slug' => $data->page_slug]) }}" />
    <meta name="robots" content="{{ $data->meta_robots ?: 'index, follow' }}">

    <!-- OpenGraph Tags -->
    <meta property="og:title" content="{{ $data->meta_title ?: $data->page_title }}" />
    <meta property="og:description" content="{{ $data->meta_description ?: ($data->excerpt ?: Str::limit(strip_tags($data->page_content), 155)) }}" />
    <meta property="og:type" content="article" />
    <meta property="og:url" content="{{ route('blog-detail', ['slug' => $data->page_slug]) }}" />
    <meta property="og:image" content="{{ $data->header_image_url }}" />
    <meta property="article:published_time" content="{{ $data->published_at ? $data->published_at->toIso8601String() : $data->created_at->toIso8601String() }}" />
    <meta property="article:author" content="{{ $data->author_name ?: 'Libraro Team' }}" />

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $data->meta_title ?: $data->page_title }}">
    <meta name="twitter:description" content="{{ $data->meta_description ?: ($data->excerpt ?: Str::limit(strip_tags($data->page_content), 155)) }}">
    <meta name="twitter:image" content="{{ $data->header_image_url }}">

    <!-- JSON-LD Structured Data Schema -->
    <script type="application/ld+json">
    {!! json_encode($jsonLdSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>

    <!-- BreadcrumbList Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "Home",
                "item": "{{ route('/') }}"
            },
            {
                "@type": "ListItem",
                "position": 2,
                "name": "Blog",
                "item": "{{ route('blog') }}"
            },
            {
                "@type": "ListItem",
                "position": 3,
                "name": "{{ $data->page_title }}",
                "item": "{{ route('blog-detail', ['slug' => $data->page_slug]) }}"
            }
        ]
    }
    </script>
@endsection

@php
    $readMins = (int) ($data->reading_time ?: max(1, (int) ceil(str_word_count(strip_tags($data->page_content ?? '')) / 200)));
    $author = $data->author_name ?: 'Libraro Team';
    $initials = collect(preg_split('/\s+/', trim($author)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');
    $postUrl = route('blog-detail', ['slug' => $data->page_slug]);
@endphp

@push('head')
    <link rel="stylesheet" href="{{ asset('public/css/lb-blog.css') }}?v={{ @filemtime(public_path('css/lb-blog.css')) }}">
@endpush

@section('body_class', 'lb-blog-page')

@section('content')
<div class="lb-progress" aria-hidden="true"><span id="lb-progress-bar"></span></div>

<article class="lb-article" itemscope itemtype="https://schema.org/BlogPosting">
    <nav class="lb-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('/') }}">Home</a><x-lb-icon name="chevron-right" />
        <a href="{{ route('blog') }}">Blog</a><x-lb-icon name="chevron-right" />
        <span aria-current="page">{{ \Illuminate\Support\Str::limit($data->page_title, 48) }}</span>
    </nav>

    <header class="lb-article-head">
        <div class="lb-article-meta-top">
            @if(isset($categories) && $categories->isNotEmpty())
                @foreach($categories as $cat)
                    <a href="{{ route('blog', ['category' => $cat->id]) }}" class="lb-pill"><x-lb-icon name="tag" />{{ $cat->name }}</a>
                @endforeach
            @endif
            <span class="lb-article-read"><x-lb-icon name="clock-3" />{{ $readMins }} min read</span>
        </div>
        <h1 itemprop="headline">{{ $data->page_title }}</h1>
        @if($data->excerpt)
            <p class="lb-article-dek" itemprop="description">{{ $data->excerpt }}</p>
        @endif
        <div class="lb-byline">
            <div class="lb-avatar" aria-hidden="true">{{ $initials ?: 'L' }}</div>
            <div class="lb-byline-text">
                <b itemprop="author">{{ $author }}</b>
                <span>
                    <time datetime="{{ optional($data->published_at ?? $data->created_at)->toDateString() }}" itemprop="datePublished">{{ $data->formatted_date }}</time>
                    · {{ number_format($data->views_count ?? 0) }} views
                </span>
            </div>
            <div class="lb-byline-actions">
                <button type="button" class="lb-icon-btn" data-lb-save aria-label="Save article" aria-pressed="false"><x-lb-icon name="bookmark" /></button>
                <button type="button" class="lb-icon-btn" data-lb-share aria-label="Share article"><x-lb-icon name="share-2" /></button>
            </div>
        </div>
    </header>

    <figure class="lb-article-cover">
        <img src="{{ $data->header_image_url }}" alt="{{ $data->image_alt ?: $data->page_title }}" itemprop="image" width="1200" height="675" fetchpriority="high" decoding="async" onerror="this.onerror=null;this.src='{{ asset('public/img/site/blog-cover-1600.webp') }}'">
        @if($data->image_alt)
            <figcaption>{{ $data->image_alt }}</figcaption>
        @endif
    </figure>

    <nav class="lb-toc" id="tocContainer" aria-labelledby="lb-toc-title" hidden>
        <div class="lb-toc-title" id="lb-toc-title">In this article</div>
        <ol id="tocList"></ol>
    </nav>

    <div class="lb-prose" id="postArticleBody" itemprop="articleBody">
        {!! $data->page_content !!}
    </div>

    <footer class="lb-article-foot">
        @if(!empty($tagsArray) && is_array($tagsArray))
            <div class="lb-tags">
                @foreach($tagsArray as $tag)
                    <a href="{{ route('blog', ['tag' => $tag]) }}">#{{ $tag }}</a>
                @endforeach
            </div>
        @endif

        <div class="lb-share-row">
            <span>Share this article</span>
            <div class="lb-share-btns">
                <a href="https://api.whatsapp.com/send?text={{ urlencode($data->page_title . ' - ' . $postUrl) }}" target="_blank" rel="noopener" aria-label="Share on WhatsApp" class="is-wa"><x-lb-brand name="whatsapp" /></a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($postUrl) }}" target="_blank" rel="noopener" aria-label="Share on Facebook"><x-lb-brand name="facebook" /></a>
                <a href="https://twitter.com/intent/tweet?text={{ urlencode($data->page_title) }}&url={{ urlencode($postUrl) }}" target="_blank" rel="noopener" aria-label="Share on X"><x-lb-brand name="x" /></a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($postUrl) }}" target="_blank" rel="noopener" aria-label="Share on LinkedIn"><x-lb-brand name="linkedin" /></a>
                <button type="button" data-lb-copy aria-label="Copy link"><x-lb-icon name="link" /></button>
            </div>
        </div>

        <div class="lb-helpful">
            <span>Was this helpful?</span>
            <button type="button" class="lb-btn lb-btn--ghost" data-lb-helpful><x-lb-icon name="thumbs-up" size="18" /><span>Yes</span></button>
        </div>

        <div class="lb-author-box">
            <div class="lb-avatar lb-avatar--lg" aria-hidden="true">{{ $initials ?: 'L' }}</div>
            <div>
                <small>Written by</small>
                <b>{{ $author }}</b>
                <p>EdTech specialist and content lead at Libraro. Helping library owners and study room entrepreneurs grow with simple technology.</p>
            </div>
        </div>
    </footer>
</article>

@if(isset($relatedBlogs) && $relatedBlogs->isNotEmpty())
    <section class="lb-related" aria-labelledby="lb-related-title">
        <div class="lb-related-head">
            <h2 id="lb-related-title">Read next</h2>
            <a href="{{ route('blog') }}">All articles <x-lb-icon name="chevron-right" size="16" /></a>
        </div>
        <div class="lb-related-track">
            @foreach($relatedBlogs as $rel)
                @php($firstCat = $rel->categories_models->first())
                <a href="{{ route('blog-detail', ['slug' => $rel->page_slug]) }}" class="lb-related-card">
                    <div class="lb-related-img"><img src="{{ $rel->header_image_url }}" alt="" loading="lazy" decoding="async" width="600" height="375" onerror="this.onerror=null;this.src='{{ asset('public/img/site/blog-inline-800.webp') }}'"></div>
                    <div class="lb-related-body">
                        @if($firstCat)<span class="lb-related-cat"><x-lb-icon name="tag" size="14" />{{ $firstCat->name }}</span>@endif
                        <h3>{{ \Illuminate\Support\Str::limit($rel->page_title, 70) }}</h3>
                        <span class="lb-related-date">{{ $rel->formatted_date }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif

<section class="lb-article-cta" aria-labelledby="lb-cta-title">
    <div>
        <h2 id="lb-cta-title">Put this to work in your library.</h2>
        <p>Bookings, fee reminders and seat reports, all in one place.</p>
        <a href="{{ route('register') }}" class="lb-btn lb-btn--light">Start free <x-lb-icon name="arrow-right" size="18" /></a>
    </div>
</section>

<div class="lb-actionbar" aria-label="Article actions">
    <button type="button" data-lb-save aria-label="Save article" aria-pressed="false"><x-lb-icon name="bookmark" /></button>
    <button type="button" data-lb-share aria-label="Share article"><x-lb-icon name="share-2" /></button>
    <span class="lb-actionbar-sep"></span>
    <button type="button" class="lb-actionbar-top" data-lb-top aria-label="Back to top"><span id="lb-progress-pct">0%</span><x-lb-icon name="arrow-up" size="16" /></button>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('public/js/lb-blog.js') }}?v={{ @filemtime(public_path('js/lb-blog.js')) }}" defer></script>
@endpush
