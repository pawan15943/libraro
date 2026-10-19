@extends('lb.layouts.site')

@section('seo')
    <title>Blog & Articles | Libraro</title>
    <meta name="description" content="Explore expert articles, guides, and updates on library management, study room operations, and educational tech solutions.">
    <meta name="keywords" content="library management blog, study room software, reading hall tips, libraro updates">
    <link rel="canonical" href="{{ route('blog') }}" />
    <meta property="og:title" content="Blog & Articles | Libraro" />
    <meta property="og:description" content="Explore expert articles, guides, and updates on library management, study room operations, and educational tech solutions." />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ route('blog') }}" />
    <meta property="og:image" content="{{ asset('public/img/blog.png') }}" />
@endsection

@php
    $activeCat = (int) request('category');
    $search = request('search');
    $tag = request('tag');
    $showFeatured = isset($featuredBlog) && $featuredBlog && $data->currentPage() == 1 && !$search && !$activeCat && !$tag;
@endphp

@push('head')
    <link rel="stylesheet" href="{{ asset('public/css/lb-blog.css') }}?v={{ @filemtime(public_path('css/lb-blog.css')) }}">
@endpush

@section('content')
    @include('lb.partials.page-hero', [
        'eyebrow' => 'Libraro journal',
        'heading' => 'Blog & Knowledge Hub',
        'lead'    => 'Insights, guides, and trends to help you manage modern libraries & study spaces.',
        'crumb'   => 'Blog',
    ])

    <div class="lb-bloglist">
        <form class="lb-blog-tools" method="GET" action="{{ route('blog') }}" role="search">
            <div class="lb-input lb-blog-search">
                <x-lb-icon name="search" />
                <input type="search" name="search" value="{{ $search }}" placeholder="Search articles" aria-label="Search articles">
                @if($activeCat)<input type="hidden" name="category" value="{{ $activeCat }}">@endif
            </div>
            @if(isset($categories) && $categories->isNotEmpty())
                <div class="lb-chip-row" aria-label="Categories">
                    <a href="{{ route('blog', array_filter(['search' => $search])) }}" class="lb-chip {{ !$activeCat ? 'is-on' : '' }}">All</a>
                    @foreach($categories as $cat)
                        <a href="{{ route('blog', array_filter(['category' => $cat->id, 'search' => $search])) }}" class="lb-chip {{ $activeCat === (int) $cat->id ? 'is-on' : '' }}">{{ $cat->name }}</a>
                    @endforeach
                </div>
            @endif
        </form>

        @if($tag)
            <p class="lb-filter-note">Showing articles tagged <b>#{{ $tag }}</b> · <a href="{{ route('blog') }}">Clear</a></p>
        @endif

        @if($showFeatured)
            @php($fCat = $featuredBlog->categories_models->first())
            <a href="{{ route('blog-detail', ['slug' => $featuredBlog->page_slug]) }}" class="lb-featured" data-reveal>
                <div class="lb-featured-img"><img src="{{ $featuredBlog->header_image_url }}" alt="{{ $featuredBlog->image_alt ?: $featuredBlog->page_title }}" width="1200" height="675" fetchpriority="high" decoding="async" onerror="this.onerror=null;this.src='{{ asset('public/img/site/blog-cover-1600.webp') }}'"></div>
                <div class="lb-featured-body">
                    <span class="lb-pill"><x-lb-icon name="star" />Featured{{ $fCat ? ' · ' . $fCat->name : '' }}</span>
                    <h2>{{ $featuredBlog->page_title }}</h2>
                    <p>{{ $featuredBlog->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($featuredBlog->page_content), 140) }}</p>
                    <span class="lb-featured-meta">{{ $featuredBlog->formatted_date }} · {{ $featuredBlog->author_name ?: 'Libraro Team' }}</span>
                </div>
            </a>
        @endif

        @if($data->isNotEmpty())
            <div class="lb-post-grid">
                @foreach($data as $value)
                    @php($firstCat = $value->categories_models->first())
                    <a href="{{ route('blog-detail', ['slug' => $value->page_slug]) }}" class="lb-post-card">
                        <div class="lb-related-img"><img src="{{ $value->header_image_url }}" alt="{{ $value->image_alt ?: $value->page_title }}" loading="lazy" decoding="async" width="600" height="375" onerror="this.onerror=null;this.src='{{ asset('public/img/site/blog-inline-800.webp') }}'"></div>
                        <div class="lb-related-body">
                            @if($firstCat)<span class="lb-related-cat"><x-lb-icon name="tag" size="14" />{{ $firstCat->name }}</span>@endif
                            <h3>{{ $value->page_title }}</h3>
                            <p>{{ $value->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($value->page_content), 110) }}</p>
                            <span class="lb-related-date">{{ $value->formatted_date }} · {{ $value->author_name ?: 'Libraro Team' }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
            {{ $data->links('lb.partials.pagination') }}
        @else
            <div class="lb-empty">
                <div class="lb-ok"><x-lb-icon name="book-open" size="28" /></div>
                <h2>No articles found</h2>
                <p>Try a different search or category.</p>
                <a href="{{ route('blog') }}" class="lb-btn">See all articles</a>
            </div>
        @endif
    </div>
@endsection
