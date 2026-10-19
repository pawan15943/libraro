@extends('sitelayouts.layout')

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

@section('content')
<!-- Theme Aligned Custom CSS (Fixed Title & Breadcrumbs) -->
<style>
    .article-detail-bg {
        background-color: #ffffff !important;
        padding-top: 3rem !important;
        padding-bottom: 5rem !important;
    }

    .article-detail-bg .text-primary,
    .blog-theme-breadcrumb .text-primary {
        color: #34939F !important;
    }

    /* Fixed Article Title H1 Typography */
    .article-title {
        font-family: "Outfit", sans-serif !important;
        font-size: 2.5rem !important;
        font-weight: 700 !important;
        line-height: 1.3 !important;
        color: #18225f !important;
        margin-bottom: 1.25rem !important;
        letter-spacing: -0.5px !important;
    }

    @media (max-width: 768px) {
        .article-title {
            font-size: 1.85rem !important;
            line-height: 1.35 !important;
        }
    }

    /* Fixed & Styled Breadcrumbs */
    .blog-theme-breadcrumb {
        background: #ffffff !important;
        border-bottom: 1px solid #e2e8f0 !important;
        padding: 0.85rem 0 !important;
    }

    .blog-theme-breadcrumb .breadcrumb {
        margin-bottom: 0 !important;
        font-family: "Mulish", sans-serif !important;
        font-size: 0.9rem !important;
        font-weight: 500 !important;
        display: flex !important;
        align-items: center !important;
    }

    .blog-theme-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
        content: "›" !important;
        color: #94a3b8 !important;
        font-size: 1.1rem !important;
        padding: 0 8px !important;
        line-height: 1 !important;
    }

    .blog-theme-breadcrumb a {
        color: #64748b !important;
        text-decoration: none !important;
        transition: color 0.2s ease !important;
    }

    .blog-theme-breadcrumb a:hover {
        color: #34939F !important;
    }

    .blog-theme-breadcrumb .breadcrumb-item.active {
        color: #18225f !important;
        font-weight: 700 !important;
    }

    /* Article Body Content Formatted Typography */
    .article-body-content {
        font-family: "Mulish", sans-serif !important;
        font-size: 1.125rem !important;
        line-height: 1.85 !important;
        color: #334155 !important;
        font-weight: 400 !important;
    }
    .article-body-content h1,
    .article-body-content h2,
    .article-body-content h3,
    .article-body-content h4,
    .article-body-content h5,
    .article-body-content h6 {
        font-family: "Outfit", sans-serif !important;
        font-weight: 700 !important;
        color: #18225f !important;
        margin-top: 2.25rem !important;
        margin-bottom: 1rem !important;
    }
    .article-body-content h2 { font-size: 1.7rem !important; }
    .article-body-content h3 { font-size: 1.4rem !important; }
    .article-body-content p {
        margin-bottom: 1.5rem !important;
        font-weight: 400 !important;
    }
    .article-body-content blockquote {
        border-left: 4px solid #34939F !important;
        padding: 1.25rem 1.5rem !important;
        margin: 2rem 0 !important;
        background-color: #f8fafc !important;
        border-radius: 0 12px 12px 0 !important;
        font-style: italic !important;
        color: #18225f !important;
        font-weight: 500 !important;
    }

    /* Tables in Article Body */
    .article-body-content table {
        width: 100% !important;
        margin: 1.75rem 0 !important;
        border-collapse: collapse !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 12px !important;
        overflow: hidden !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02) !important;
    }
    .article-body-content table th {
        background-color: #18225f !important;
        color: #ffffff !important;
        padding: 12px 16px !important;
        font-family: "Outfit", sans-serif !important;
        font-weight: 700 !important;
        text-align: left !important;
    }
    .article-body-content table td {
        padding: 12px 16px !important;
        border-bottom: 1px solid #e2e8f0 !important;
        font-family: "Mulish", sans-serif !important;
        font-weight: 400 !important;
    }
    .article-body-content table tr:nth-child(even) {
        background-color: #f8fafc !important;
    }

    /* Rich Figures & Images */
    .article-body-content figure {
        margin: 1.75rem 0 !important;
        text-align: center !important;
    }
    .article-body-content figure.image img,
    .article-body-content img {
        max-width: 100% !important;
        height: auto !important;
        border-radius: 14px !important;
        box-shadow: 0 4px 20px rgba(24, 34, 95, 0.08) !important;
        margin: 1rem 0 !important;
    }
    .article-body-content figure figcaption {
        font-family: "Mulish", sans-serif !important;
        font-size: 0.88rem !important;
        color: #64748b !important;
        margin-top: 0.5rem !important;
        font-style: italic !important;
        font-weight: 400 !important;
    }

    /* Code Blocks & Inline Code */
    .article-body-content pre {
        background: #0f172a !important;
        color: #f8fafc !important;
        padding: 1.25rem !important;
        border-radius: 12px !important;
        overflow-x: auto !important;
        font-family: "Courier New", Courier, monospace !important;
        font-size: 0.95rem !important;
        margin: 1.75rem 0 !important;
        font-weight: 400 !important;
    }
    .article-body-content code {
        background: #f1f5f9 !important;
        color: #18225f !important;
        padding: 2px 8px !important;
        border-radius: 6px !important;
        font-family: monospace !important;
        font-size: 0.9em !important;
        font-weight: 500 !important;
    }

    /* Videos & Media Embeds */
    .article-body-content iframe,
    .article-body-content .media {
        width: 100% !important;
        min-height: 380px !important;
        border-radius: 14px !important;
        border: none !important;
        margin: 1.75rem 0 !important;
    }

    /* Lists */
    .article-body-content ul,
    .article-body-content ol {
        margin-bottom: 1.5rem !important;
        padding-left: 1.75rem !important;
    }
    .article-body-content li {
        margin-bottom: 0.5rem !important;
        line-height: 1.75 !important;
        font-weight: 400 !important;
    }

    .article-featured-img {
        width: 100% !important;
        max-height: 480px !important;
        object-fit: cover !important;
        border-radius: 20px !important;
        margin-bottom: 2rem !important;
        box-shadow: 0 10px 30px rgba(24, 34, 95, 0.08) !important;
    }
    .theme-category-pill {
        background-color: #18225f !important;
        color: #ffffff !important;
        font-family: "Outfit", sans-serif !important;
        font-weight: 700 !important;
        font-size: 0.82rem !important;
        padding: 6px 16px !important;
        border-radius: 50px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        text-decoration: none !important;
    }
    .theme-tag-pill {
        background-color: #f1f5f9 !important;
        color: #18225f !important;
        border: 1px solid #cbd5e1 !important;
        font-family: "Mulish", sans-serif !important;
        font-weight: 600 !important;
        font-size: 0.85rem !important;
        padding: 6px 16px !important;
        border-radius: 50px !important;
        text-decoration: none !important;
        transition: all 0.2s ease !important;
    }
    .theme-tag-pill:hover {
        background-color: #34939F !important;
        color: #ffffff !important;
        border-color: #34939F !important;
    }

    /* Table of Contents Styling */
    .toc-box {
        background: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 16px !important;
        padding: 1.5rem !important;
        margin-bottom: 2rem !important;
    }

    .toc-styled-list {
        font-family: "Mulish", sans-serif !important;
        color: #475569 !important;
        padding-left: 1.25rem !important;
    }
    .toc-styled-list li {
        margin-bottom: 0.5rem !important;
        line-height: 1.5 !important;
        font-weight: 400 !important;
    }
    .toc-styled-list li a {
        color: #18225f !important;
        text-decoration: none !important;
        font-weight: 600 !important;
        transition: color 0.2s ease, padding-left 0.2s ease !important;
        display: inline-block !important;
    }
    .toc-styled-list li a:hover {
        color: #34939F !important;
        padding-left: 4px !important;
    }
    .toc-styled-list li.toc-sub-item {
        margin-left: 1.25rem !important;
        font-size: 0.92rem !important;
        list-style-type: circle !important;
    }

    /* Slider Card Styling */
    .theme-blog-card {
        background: #ffffff !important;
        border-radius: 16px !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 4px 20px rgba(24, 34, 95, 0.05) !important;
        overflow: hidden !important;
        display: flex !important;
        flex-direction: column !important;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    .theme-blog-card:hover {
        transform: translateY(-6px) !important;
        box-shadow: 0 12px 30px rgba(24, 34, 95, 0.12) !important;
    }
    .theme-blog-card-img-wrap {
        position: relative !important;
        height: 170px !important;
        width: 100% !important;
        overflow: hidden !important;
        background: #f1f5f9 !important;
    }
    .theme-blog-card-img {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
    }
    .theme-blog-card-badge {
        position: absolute !important;
        top: 10px !important;
        left: 10px !important;
        background: #18225f !important;
        color: #ffffff !important;
        padding: 4px 10px !important;
        border-radius: 50px !important;
        font-family: "Outfit", sans-serif !important;
        font-size: 0.7rem !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        z-index: 2 !important;
    }
    .theme-blog-card-title {
        font-family: "Outfit", sans-serif !important;
        font-size: 1.05rem !important;
        font-weight: 700 !important;
        line-height: 1.35 !important;
        color: #18225f !important;
    }
    .theme-blog-card-title a {
        color: #18225f !important;
        text-decoration: none !important;
    }
    .theme-blog-card-title a:hover {
        color: #34939F !important;
    }
    .theme-blog-readmore {
        font-family: "Outfit", sans-serif !important;
        font-weight: 700 !important;
        color: #18225f !important;
        text-decoration: none !important;
    }
    .theme-blog-readmore:hover {
        color: #34939F !important;
    }

    /* Owl Carousel Custom Arrows */
    .owl-nav {
        margin-top: 15px !important;
        text-align: center !important;
    }
    .owl-nav button.owl-prev,
    .owl-nav button.owl-next {
        width: 40px !important;
        height: 40px !important;
        border-radius: 50% !important;
        background: #18225f !important;
        color: #ffffff !important;
        font-size: 18px !important;
        margin: 0 5px !important;
        transition: background 0.2s !important;
    }
    .owl-nav button.owl-prev:hover,
    .owl-nav button.owl-next:hover {
        background: #34939F !important;
    }
</style>

<!-- FIXED BREADCRUMB BAR -->
<section class="blog-theme-breadcrumb">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('/') }}"><i class="fa fa-home me-1"></i> Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('blog') }}">Blog</a></li>
                <li class="breadcrumb-item active text-truncate" style="max-width: 380px;" aria-current="page">{{ $data->page_title }}</li>
            </ol>
        </nav>
    </div>
</section>

<!-- CENTERED MAIN ARTICLE SECTION -->
<section class="article-detail-bg">
    <div class="container">
        <div class="row justify-content-center">
            <!-- CENTERED ARTICLE CONTAINER (Col 9 Centered) -->
            <div class="col-lg-9 col-xl-8">
                <article itemscope itemtype="https://schema.org/BlogPosting">
                    
                    <!-- Categories Displayed Clearly at Top -->
                    @if(isset($categories) && $categories->isNotEmpty())
                        <div class="mb-3 d-flex flex-wrap gap-2">
                            @foreach($categories as $cat)
                                <a href="{{ route('blog', ['category' => $cat->id]) }}" class="theme-category-pill">
                                    {{ $cat->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <!-- Fixed Article Title H1 -->
                    <h1 class="article-title" itemprop="headline">
                        {{ $data->page_title }}
                    </h1>

                    <!-- Author & Date Meta Info Bar -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 py-3 border-top border-bottom mb-4 text-muted small" style="font-family: 'Mulish', sans-serif; font-weight: 500;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-user-circle fa-2x text-primary me-2"></i>
                                <div>
                                    <span class="fw-bold text-dark d-block" itemprop="author" style="font-family: 'Outfit', sans-serif;">{{ $data->author_name ?: 'Libraro Team' }}</span>
                                    <span class="extra-small text-muted" style="font-size: 11px;">Author</span>
                                </div>
                            </div>
                            <div class="border-end ps-3" style="height: 30px;"></div>
                            <div>
                                <span class="d-block fw-semibold text-dark"><i class="fa fa-calendar-alt text-primary me-1"></i> {{ $data->formatted_date }}</span>
                                <span class="extra-small text-muted" style="font-size: 11px;">Published Date</span>
                            </div>
                        </div>
                        <div>
                            <span class="badge bg-light text-dark border px-3 py-2" style="font-weight: 600;">
                                <i class="fa fa-eye text-primary me-1"></i> {{ number_format($data->views_count ?? 0) }} views
                            </span>
                        </div>
                    </div>

                    <!-- Featured Header Image -->
                    <div class="mb-4 text-center">
                        <img src="{{ $data->header_image_url }}" 
                             alt="{{ $data->image_alt ?: $data->page_title }}" 
                             onerror="this.onerror=null; this.src='https://placehold.co/1200x600/18225f/ffffff?text=Libraro+Blog';"
                             class="article-featured-img" 
                             itemprop="image">
                        @if($data->image_alt)
                            <small class="text-muted d-block mt-2 fst-italic" style="font-family: 'Mulish', sans-serif; font-weight: 400;">{{ $data->image_alt }}</small>
                        @endif
                    </div>

                    <!-- Excerpt / Lead Box -->
                    @if($data->excerpt)
                        <div class="lead p-4 bg-light border-start border-4 rounded-3 mb-4" style="font-size: 1.15rem; color: #18225f; border-left-color: #34939F !important; font-family: 'Mulish', sans-serif; font-weight: 400;">
                            {{ $data->excerpt }}
                        </div>
                    @endif

                    <!-- Table of Contents Container -->
                    <div id="tocContainer" class="toc-box d-none">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="fw-bold mb-0 d-flex align-items-center gap-2" style="font-family: 'Outfit', sans-serif; color: #18225f; font-size: 1.1rem;">
                                <i class="fa fa-list-ul" style="color: #34939F;"></i> Table of Contents
                            </h6>
                        </div>
                        <ol id="tocList" class="toc-styled-list mb-0"></ol>
                    </div>

                    <!-- Article Body Content -->
                    <div class="article-body-content mb-5" itemprop="articleBody" id="postArticleBody">
                        {!! $data->page_content !!}
                    </div>

                    <!-- Article Tags Displayed Clearly -->
                    @if(!empty($tagsArray) && is_array($tagsArray))
                        <div class="p-4 bg-light rounded-3 mb-4 border">
                            <h6 class="fw-bold mb-3" style="font-family: 'Outfit', sans-serif; color: #18225f;"><i class="fa fa-tags text-primary me-2"></i> Post Tags:</h6>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($tagsArray as $tag)
                                    <a href="{{ route('blog', ['tag' => $tag]) }}" class="theme-tag-pill">
                                        #{{ $tag }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Social Share Buttons -->
                    <div class="p-4 bg-light rounded-3 mb-5 d-flex flex-wrap align-items-center justify-content-between gap-3 border">
                        <span class="fw-bold" style="font-family: 'Outfit', sans-serif; color: #18225f;"><i class="fa fa-share-alt text-primary me-2"></i> Share This Article:</span>
                        <div class="d-flex gap-2">
                            <a href="https://api.whatsapp.com/send?text={{ urlencode($data->page_title . ' - ' . url()->current()) }}" target="_blank" class="btn btn-sm btn-success rounded-circle" style="width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;" title="Share on WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" class="btn btn-sm btn-primary rounded-circle" style="width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; background-color: #18225f !important; border-color: #18225f !important;" title="Share on Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?text={{ urlencode($data->page_title) }}&url={{ urlencode(url()->current()) }}" target="_blank" class="btn btn-sm btn-dark rounded-circle" style="width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;" title="Share on Twitter/X">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(url()->current()) }}" target="_blank" class="btn btn-sm text-white rounded-circle" style="width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; background-color: #34939F !important;" title="Share on LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <button type="button" onclick="copyPostLink()" class="btn btn-sm btn-secondary rounded-circle" style="width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;" title="Copy Link">
                                <i class="fa fa-link"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Author Bio Box -->
                    <div class="card border-0 shadow-sm rounded-3 p-4 mb-5 bg-light">
                        <div class="d-flex align-items-center gap-3">
                            <div class="flex-shrink-0">
                                <i class="fa fa-user-circle fa-4x text-primary"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1" style="font-family: 'Outfit', sans-serif; color: #18225f;">{{ $data->author_name ?: 'Libraro Team' }}</h5>
                                <p class="text-muted small mb-0" style="font-family: 'Mulish', sans-serif; font-weight: 400;">EdTech Specialist and Content Lead at Libraro. Empowering library owners and study room entrepreneurs with tech automation solutions.</p>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- RELATED ARTICLES SLIDER (Owl Carousel) -->
                @if(isset($relatedBlogs) && $relatedBlogs->isNotEmpty())
                    <div class="mt-5 pt-4 border-top">
                        <h3 class="fw-bold mb-4 text-center" style="font-family: 'Outfit', sans-serif; color: #18225f;">
                            <i class="fa fa-newspaper text-primary me-2"></i> Related Articles
                        </h3>
                        
                        <div id="relatedBlogsSlider" class="owl-carousel owl-theme">
                            @foreach($relatedBlogs as $rel)
                                <div class="item py-2">
                                    <div class="theme-blog-card h-100">
                                        <div class="theme-blog-card-img-wrap">
                                            <a href="{{ route('blog-detail', ['slug' => $rel->page_slug]) }}">
                                                <img src="{{ $rel->header_image_url }}" 
                                                     alt="{{ $rel->page_title }}" 
                                                     onerror="this.onerror=null; this.src='https://placehold.co/600x400/18225f/ffffff?text=Libraro+Blog';"
                                                     class="theme-blog-card-img">
                                            </a>
                                            @php $firstCat = $rel->categories_models->first(); @endphp
                                            @if($firstCat)
                                                <span class="theme-blog-card-badge">
                                                    {{ $firstCat->name }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="p-3 d-flex flex-column flex-grow-1">
                                            <div class="text-muted small mb-2" style="font-family: 'Mulish', sans-serif; font-size: 0.78rem; font-weight: 500;">
                                                <i class="fa fa-calendar-alt text-primary me-1"></i> {{ $rel->formatted_date }}
                                            </div>
                                            <h6 class="theme-blog-card-title mb-3" style="font-size: 0.98rem;">
                                                <a href="{{ route('blog-detail', ['slug' => $rel->page_slug]) }}">
                                                    {{ Str::limit($rel->page_title, 48) }}
                                                </a>
                                            </h6>
                                            <a href="{{ route('blog-detail', ['slug' => $rel->page_slug]) }}" class="theme-blog-readmore small mt-auto">
                                                Read Article <i class="fa fa-chevron-right ms-1" style="font-size: 10px;"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

<!-- Auto Table of Contents, Copy Link & Owl Carousel Slider JS -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Robust Table of Contents Generator (Ignores Empty Headings & &nbsp;)
        const articleBody = document.getElementById('postArticleBody');
        const tocContainer = document.getElementById('tocContainer');
        const tocList = document.getElementById('tocList');

        if (articleBody && tocContainer && tocList) {
            const headings = articleBody.querySelectorAll('h2, h3');
            const validHeadings = Array.from(headings).filter(h => {
                const text = h.innerText.replace(/\s+/g, ' ').trim();
                return text.length > 0 && text !== '&nbsp;';
            });

            if (validHeadings.length >= 2) {
                tocList.innerHTML = '';
                tocContainer.classList.remove('d-none');
                
                validHeadings.forEach((heading, index) => {
                    const id = 'toc-heading-' + index;
                    heading.setAttribute('id', id);

                    const li = document.createElement('li');
                    if (heading.tagName.toLowerCase() === 'h3') {
                        li.className = 'toc-sub-item';
                    }

                    const a = document.createElement('a');
                    a.href = '#' + id;
                    a.innerText = heading.innerText.trim();
                    a.addEventListener('click', function(e) {
                        e.preventDefault();
                        const target = document.getElementById(id);
                        if (target) {
                            const offset = 90; // sticky header clearance
                            const bodyRect = document.body.getBoundingClientRect().top;
                            const elementRect = target.getBoundingClientRect().top;
                            const elementPosition = elementRect - bodyRect;
                            const offsetPosition = elementPosition - offset;

                            window.scrollTo({
                                top: offsetPosition,
                                behavior: 'smooth'
                            });
                        }
                    });

                    li.appendChild(a);
                    tocList.appendChild(li);
                });
            } else {
                tocContainer.classList.add('d-none');
            }
        }

        // Initialize Owl Carousel Slider for Related Blogs
        if (typeof $ !== 'undefined' && $('#relatedBlogsSlider').length) {
            $('#relatedBlogsSlider').owlCarousel({
                loop: true,
                margin: 20,
                nav: true,
                dots: true,
                autoplay: true,
                autoplayTimeout: 4000,
                autoplayHoverPause: true,
                responsive: {
                    0: { items: 1 },
                    576: { items: 2 },
                    992: { items: 3 }
                }
            });
        }
    });

    function copyPostLink() {
        navigator.clipboard.writeText(window.location.href).then(() => {
            if (typeof toastr !== 'undefined') {
                toastr.success('Article link copied to clipboard!');
            } else {
                alert('Article link copied to clipboard!');
            }
        });
    }
</script>
@endsection