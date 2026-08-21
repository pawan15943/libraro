@extends('sitelayouts.layout')

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

@section('content')
<!-- Theme Aligned Custom CSS (Fixed Title & Breadcrumbs) -->
<style>
    .blog-page-section {
        background-color: #f8fafc !important;
        padding-top: 3.5rem !important;
        padding-bottom: 5rem !important;
    }

    /* Override bootstrap primary utilities inside blog */
    .blog-theme-container .text-primary,
    .blog-theme-hero .text-primary,
    .blog-theme-breadcrumb .text-primary {
        color: #34939F !important;
    }

    .btn-primary {
        background-color: #18225f !important;
        border-color: #18225f !important;
        color: #ffffff !important;
        font-family: "Outfit", sans-serif !important;
        font-weight: 600 !important;
    }
    .btn-primary:hover {
        background-color: #34939F !important;
        border-color: #34939F !important;
    }

    .btn-outline-primary {
        color: #18225f !important;
        border-color: #18225f !important;
        font-family: "Outfit", sans-serif !important;
        font-weight: 600 !important;
    }
    .btn-outline-primary:hover {
        background-color: #34939F !important;
        border-color: #34939F !important;
        color: #ffffff !important;
    }

    /* Theme Header Hero */
    .blog-theme-hero {
        background: linear-gradient(135deg, #18225f 0%, #0d143a 100%) !important;
        color: #ffffff !important;
        padding: 4.5rem 1rem 3.5rem !important;
        text-align: center !important;
    }

    .blog-theme-hero h1 {
        font-family: "Outfit", sans-serif !important;
        font-size: 2.8rem !important;
        font-weight: 700 !important;
        color: #ffffff !important;
        margin-bottom: 0.75rem !important;
        letter-spacing: -0.5px !important;
    }

    .blog-theme-hero p {
        font-family: "Mulish", sans-serif !important;
        font-size: 1.15rem !important;
        color: #cbd5e1 !important;
        max-width: 650px !important;
        margin: 0 auto !important;
        font-weight: 400 !important;
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

    /* Blog Card Design */
    .theme-blog-card {
        background: #ffffff !important;
        border-radius: 16px !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 4px 20px rgba(24, 34, 95, 0.05) !important;
        overflow: hidden !important;
        height: 100% !important;
        display: flex !important;
        flex-direction: column !important;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .theme-blog-card:hover {
        transform: translateY(-6px) !important;
        box-shadow: 0 12px 30px rgba(24, 34, 95, 0.12) !important;
        border-color: #cbd5e1 !important;
    }

    .theme-blog-card-img-wrap {
        position: relative !important;
        height: 220px !important;
        width: 100% !important;
        overflow: hidden !important;
        background: #f1f5f9 !important;
    }

    .theme-blog-card-img {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        transition: transform 0.5s ease !important;
    }

    .theme-blog-card:hover .theme-blog-card-img {
        transform: scale(1.06) !important;
    }

    .theme-blog-card-badge {
        position: absolute !important;
        top: 14px !important;
        left: 14px !important;
        background: #18225f !important;
        color: #ffffff !important;
        padding: 5px 14px !important;
        border-radius: 50px !important;
        font-family: "Outfit", sans-serif !important;
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15) !important;
        z-index: 2 !important;
    }

    .theme-blog-card-body {
        padding: 1.5rem !important;
        display: flex !important;
        flex-direction: column !important;
        flex-grow: 1 !important;
    }

    .theme-blog-card-title {
        font-family: "Outfit", sans-serif !important;
        font-size: 1.25rem !important;
        font-weight: 700 !important;
        line-height: 1.35 !important;
        color: #18225f !important;
        margin-bottom: 0.75rem !important;
    }

    .theme-blog-card-title a {
        font-family: "Outfit", sans-serif !important;
        color: #18225f !important;
        text-decoration: none !important;
        transition: color 0.2s ease !important;
    }

    .theme-blog-card-title a:hover {
        color: #34939F !important;
    }

    .theme-blog-card-text {
        font-family: "Mulish", sans-serif !important;
        font-size: 0.92rem !important;
        font-weight: 400 !important;
        color: #475569 !important;
        line-height: 1.6 !important;
        margin-bottom: 1.25rem !important;
        flex-grow: 1 !important;
        display: -webkit-box !important;
        -webkit-line-clamp: 3 !important;
        -webkit-box-orient: vertical !important;
        overflow: hidden !important;
    }

    .theme-blog-card-footer {
        padding-top: 1rem !important;
        border-top: 1px solid #f1f5f9 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
    }

    .theme-blog-readmore {
        font-family: "Outfit", sans-serif !important;
        font-weight: 700 !important;
        color: #18225f !important;
        text-decoration: none !important;
        font-size: 0.9rem !important;
        transition: color 0.2s ease !important;
    }

    .theme-blog-readmore:hover {
        color: #34939F !important;
    }

    /* Centered Featured Post Banner */
    .theme-featured-card {
        background: #ffffff !important;
        border-radius: 20px !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 10px 30px rgba(24, 34, 95, 0.08) !important;
        overflow: hidden !important;
        margin-bottom: 3.5rem !important;
    }

    .theme-featured-img {
        width: 100% !important;
        height: 100% !important;
        min-height: 350px !important;
        object-fit: cover !important;
    }

    /* Pagination */
    .pagination-wrapper {
        margin-top: 3.5rem !important;
        display: flex !important;
        justify-content: center !important;
    }

    .pagination {
        display: flex !important;
        gap: 6px !important;
        padding-left: 0 !important;
        margin-bottom: 0 !important;
        list-style: none !important;
    }

    .pagination .page-item .page-link {
        border-radius: 10px !important;
        padding: 8px 16px !important;
        color: #18225f !important;
        font-family: "Outfit", sans-serif !important;
        font-weight: 600 !important;
        border: 1px solid #cbd5e1 !important;
        background: #ffffff !important;
        text-decoration: none !important;
    }

    .pagination .page-item.active .page-link {
        background: #18225f !important;
        border-color: #18225f !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(24, 34, 95, 0.25) !important;
    }

    .pagination svg {
        width: 1.2rem !important;
        height: 1.2rem !important;
    }
</style>

<!-- CLEAN THEME HERO -->
<section class="blog-theme-hero">
    <div class="container">
        <h1>Blog & Knowledge Hub</h1>
        <p>Insights, guides, and trends to help you manage modern libraries & study spaces.</p>
    </div>
</section>

<!-- FIXED BREADCRUMB -->
<section class="blog-theme-breadcrumb">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('/') }}"><i class="fa fa-home me-1"></i> Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Blog</li>
            </ol>
        </nav>
    </div>
</section>

<!-- CENTERED MAIN CONTENT -->
<section class="blog-page-section blog-theme-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-11">

                <!-- FEATURED BANNER POST (Centered 12-col card on page 1) -->
                @if(isset($featuredBlog) && $data->currentPage() == 1)
                    <div class="theme-featured-card">
                        <div class="row g-0 align-items-center">
                            <div class="col-lg-7">
                                <a href="{{ route('blog-detail', ['slug' => $featuredBlog->page_slug]) }}">
                                    <img src="{{ $featuredBlog->header_image_url }}" 
                                         alt="{{ $featuredBlog->image_alt ?: $featuredBlog->page_title }}" 
                                         onerror="this.onerror=null; this.src='https://placehold.co/800x450/18225f/ffffff?text=Libraro+Blog';"
                                         class="theme-featured-img">
                                </a>
                            </div>
                            <div class="col-lg-5 p-4 p-md-5">
                                <span class="badge bg-danger mb-3 px-3 py-2 rounded-pill fw-bold" style="font-family: 'Outfit', sans-serif;"><i class="fa fa-star me-1"></i> Featured Article</span>
                                <h2 style="font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 1.75rem; line-height: 1.35;" class="mb-3">
                                    <a href="{{ route('blog-detail', ['slug' => $featuredBlog->page_slug]) }}" style="color: #18225f;" class="text-decoration-none hover-teal">
                                        {{ $featuredBlog->page_title }}
                                    </a>
                                </h2>
                                <p class="text-secondary mb-4" style="font-size: 0.95rem; line-height: 1.6; font-family: 'Mulish', sans-serif; font-weight: 400;">
                                    {{ $featuredBlog->excerpt ?: Str::limit(strip_tags($featuredBlog->page_content), 140) }}
                                </p>
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="text-muted small" style="font-family: 'Mulish', sans-serif; font-weight: 500;"><i class="fa fa-calendar-alt text-primary me-1"></i> {{ $featuredBlog->formatted_date }}</span>
                                    <a href="{{ route('blog-detail', ['slug' => $featuredBlog->page_slug]) }}" class="btn btn-outline-primary btn-sm rounded-pill px-4" style="font-family: 'Outfit', sans-serif; font-weight: 600;">
                                        Read Article <i class="fa fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- CENTERED GRID OF ARTICLES (3 Columns Layout) -->
                @if($data->isNotEmpty())
                    <div class="row g-4">
                        @foreach($data as $value)
                            <div class="col-md-6 col-lg-4">
                                <div class="theme-blog-card">
                                    <div class="theme-blog-card-img-wrap">
                                        <a href="{{ route('blog-detail', ['slug' => $value->page_slug]) }}">
                                            <img src="{{ $value->header_image_url }}" 
                                                 alt="{{ $value->image_alt ?: $value->page_title }}" 
                                                 onerror="this.onerror=null; this.src='https://placehold.co/600x400/18225f/ffffff?text=Libraro+Blog';"
                                                 class="theme-blog-card-img" loading="lazy">
                                        </a>
                                        @php $firstCat = $value->categories_models->first(); @endphp
                                        @if($firstCat)
                                            <span class="theme-blog-card-badge">
                                                {{ $firstCat->name }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="theme-blog-card-body">
                                        <div class="text-muted small mb-2" style="font-family: 'Mulish', sans-serif; font-weight: 500;">
                                            <i class="fa fa-calendar-alt text-primary me-1"></i> {{ $value->formatted_date }}
                                        </div>

                                        <h5 class="theme-blog-card-title">
                                            <a href="{{ route('blog-detail', ['slug' => $value->page_slug]) }}">
                                                {{ $value->page_title }}
                                            </a>
                                        </h5>

                                        <p class="theme-blog-card-text">
                                            {{ $value->excerpt ?: Str::limit(strip_tags($value->page_content), 110) }}
                                        </p>

                                        <div class="theme-blog-card-footer">
                                            <span class="text-secondary small" style="font-family: 'Mulish', sans-serif; font-weight: 500;">
                                                <i class="fa fa-user-circle text-primary me-1"></i> {{ $value->author_name ?: 'Libraro Team' }}
                                            </span>
                                            <a href="{{ route('blog-detail', ['slug' => $value->page_slug]) }}" class="theme-blog-readmore">
                                                Read Article <i class="fa fa-chevron-right ms-1" style="font-size: 10px;"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Clean Centered Pagination -->
                    <div class="pagination-wrapper">
                        {{ $data->links('pagination::bootstrap-5') }}
                    </div>
                @else
                    <div class="text-center py-5 bg-white rounded-4 border shadow-xs">
                        <i class="fa fa-newspaper fa-3x text-muted mb-3"></i>
                        <h4 class="fw-bold text-dark mb-2" style="font-family: 'Outfit', sans-serif;">No Articles Found</h4>
                        <p class="text-muted mb-4" style="font-family: 'Mulish', sans-serif; font-weight: 400;">Check back later for new blog updates and insights.</p>
                        <a href="{{ route('/') }}" class="btn btn-primary rounded-pill px-4" style="font-family: 'Outfit', sans-serif;">Back to Home</a>
                    </div>
                @endif

            </div>
        </div>
    </div>
</section>
@endsection