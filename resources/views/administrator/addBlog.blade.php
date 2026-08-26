@extends('layouts.admin')

@section('content')

<!-- Tagify CSS -->
<link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet">

<!-- Theme Aligned Custom Admin CSS (#18225f Dark Navy & #34939F Teal) -->
<style>
    .admin-blog-card {
        background: #ffffff !important;
        border-radius: 16px !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 4px 20px rgba(24, 34, 95, 0.05) !important;
        margin-bottom: 1.75rem !important;
        overflow: visible !important;
    }

    .admin-blog-card-header {
        background: #18225f !important;
        color: #ffffff !important;
        padding: 0.9rem 1.25rem !important;
        font-family: "Outfit", sans-serif !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
    }

    .admin-blog-card-header i {
        color: #34939F !important;
    }

    .admin-blog-card-body {
        padding: 1.25rem 1.5rem !important;
    }

    /* Small Input Labels matching Screenshot (13px Font-Weight 700) */
    .form-label {
        font-family: "Outfit", sans-serif !important;
        font-weight: 700 !important;
        color: #334155 !important;
        font-size: 0.83rem !important;
        margin-bottom: 0.35rem !important;
        letter-spacing: 0.2px !important;
    }

    .form-control, .form-select {
        font-family: "Mulish", sans-serif !important;
        border-radius: 8px !important;
        border: 1px solid #cbd5e1 !important;
        padding: 0.55rem 0.85rem !important;
        font-size: 0.9rem !important;
        color: #1e293b !important;
    }

    .form-control:focus, .form-select:focus {
        border-color: #34939F !important;
        box-shadow: 0 0 0 0.25rem rgba(52, 147, 159, 0.18) !important;
    }

    /* Tagify Tag Field Height Auto & Seamless Flow */
    .tagify {
        background-color: #ffffff !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        height: auto !important;
        min-height: 44px !important;
        padding: 6px 8px !important;
        font-family: "Mulish", sans-serif !important;
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        gap: 4px !important;
    }

    .tagify:hover {
        border-color: #34939F !important;
    }

    .tagify.tagify--focus {
        border-color: #34939F !important;
        box-shadow: 0 0 0 0.25rem rgba(52, 147, 159, 0.18) !important;
    }

    .tagify__tag {
        background-color: #34939F !important;
        border-radius: 50px !important;
        margin: 2px !important;
        padding: 3px 10px !important;
        height: auto !important;
    }

    .tagify__tag > div {
        color: #ffffff !important;
        font-weight: 600 !important;
        font-family: "Outfit", sans-serif !important;
        font-size: 0.8rem !important;
        padding: 0 !important;
    }

    .tagify__tag > div::before {
        box-shadow: none !important;
        background: transparent !important;
    }

    .tagify__tag__removeBtn {
        color: #ffffff !important;
        margin-left: 6px !important;
        font-size: 11px !important;
    }

    .tagify__tag__removeBtn:hover {
        background: rgba(255, 255, 255, 0.2) !important;
        color: #ffffff !important;
    }

    .tagify__input {
        margin: 2px !important;
        padding: 2px !important;
        font-family: "Mulish", sans-serif !important;
        font-size: 0.88rem !important;
    }

    /* Modern Drag & Drop Image Upload Zone */
    .image-upload-dropzone {
        border: 2px dashed #cbd5e1 !important;
        background-color: #f8fafc !important;
        border-radius: 14px !important;
        cursor: pointer !important;
        transition: all 0.2s ease-in-out !important;
        padding: 1.75rem 1rem !important;
    }
    .image-upload-dropzone:hover {
        border-color: #34939F !important;
        background-color: #f1f5f9 !important;
    }

    /* CKEditor Custom Editable Styling */
    .ck-editor {
        display: block !important;
        width: 100% !important;
        position: relative !important;
        z-index: 5 !important;
        border-radius: 12px !important;
        overflow: hidden !important;
    }

    .ck-editor__editable,
    .ck-editor__editable_inline,
    .ck-content {
        min-height: 420px !important;
        font-family: "Mulish", sans-serif !important;
        font-size: 1.05rem !important;
        line-height: 1.75 !important;
        color: #334155 !important;
        padding: 1.25rem !important;
        pointer-events: auto !important;
        cursor: text !important;
        user-select: text !important;
        -webkit-user-select: text !important;
        background-color: #ffffff !important;
    }

    .ck.ck-toolbar {
        background: #f8fafc !important;
        border-color: #cbd5e1 !important;
        padding: 6px 10px !important;
    }

    /* SERP Box Styling & Toggle Buttons */
    .serp-box {
        background: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 14px !important;
        padding: 1.25rem !important;
    }

    .active-serp-toggle {
        background-color: #18225f !important;
        color: #ffffff !important;
        box-shadow: 0 2px 6px rgba(24, 34, 95, 0.2) !important;
    }

    .badge-seo-good {
        background-color: #2e7d32 !important;
        color: #ffffff !important;
    }
    .badge-seo-warn {
        background-color: #ed6c02 !important;
        color: #ffffff !important;
    }

    /* Clean Category Checkbox Box */
    .category-scroll-box {
        border: 1px solid #cbd5e1 !important;
        border-radius: 10px !important;
        background-color: #ffffff !important;
        max-height: 170px !important;
        overflow-y: auto !important;
        padding: 0.8rem 1rem !important;
    }
</style>

<div class="container-fluid py-2">
    <!-- Top Action Bar -->
    <div class="d-flex justify-content-end align-items-center mb-3">
        <a href="{{ route('blogs') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-bold">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to All Blogs
        </a>
    </div>

    <!-- Main Form -->
    <form action="{{ isset($data) ? route('blog.store', $data->id) : route('blog.store') }}" method="POST" enctype="multipart/form-data" id="blogForm">
        @csrf

        <div class="row g-4">
            <!-- LEFT MAIN CONTENT COLUMN (8 Cols) -->
            <div class="col-lg-8">
                <!-- Main Details Card -->
                <div class="admin-blog-card">
                    <div class="admin-blog-card-header">
                        <span><i class="fa-solid fa-pen-to-square me-2"></i> Post Content & Main Details</span>
                    </div>
                    <div class="admin-blog-card-body">
                        <!-- Blog Title (With Real-Time Instant Slug Generation) -->
                        <div class="mb-3">
                            <label for="page_title" class="form-label">Post Title <span class="text-danger">*</span></label>
                            <input 
                                type="text" 
                                id="page_title" 
                                name="page_title" 
                                class="form-control form-control-lg @error('page_title') is-invalid @enderror" 
                                value="{{ old('page_title', $data->page_title ?? '') }}" 
                                placeholder="e.g. 10 Best Library Automation Software Solutions in India (2026)" required>
                            @error('page_title')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <!-- Slug (Automatically Generated Real-Time) -->
                        <div class="mb-3">
                            <label for="page_slug" class="form-label text-muted small">Permalink URL Slug <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted" style="font-size: 0.85rem;">{{ url('blog') }}/</span>
                                <input 
                                    type="text" 
                                    id="page_slug" 
                                    name="page_slug" 
                                    class="form-control @error('page_slug') is-invalid @enderror" 
                                    value="{{ old('page_slug', $data->page_slug ?? '') }}" required>
                            </div>
                            @error('page_slug')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <!-- Excerpt / Short Description -->
                        <div class="mb-4">
                            <label for="excerpt" class="form-label">Excerpt / Summary Description (SEO)</label>
                            <textarea 
                                id="excerpt" 
                                name="excerpt" 
                                rows="3" 
                                class="form-control @error('excerpt') is-invalid @enderror" 
                                placeholder="Write a short engaging overview for search engines and post previews...">{{ old('excerpt', $data->excerpt ?? '') }}</textarea>
                            <small class="text-muted" style="font-size: 11px;">Shown as post lead paragraph and in search result snippets.</small>
                            @error('excerpt')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <!-- Post Body Content Editor (CKEditor 5) -->
                        <div class="mb-2">
                            <label for="editor" class="form-label">Post Body Content <span class="text-danger">*</span></label>
                            <textarea 
                                id="editor" 
                                name="page_content" 
                                class="form-control @error('page_content') is-invalid @enderror">{{ old('page_content', $data->page_content ?? '') }}</textarea>
                            @error('page_content')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- YOAST / RANKMATH STYLE LIVE SEO SUITE CARD -->
                <div class="admin-blog-card">
                    <div class="admin-blog-card-header">
                        <span><i class="fa-solid fa-magnifying-glass me-2"></i> Yoast & RankMath SEO Suite</span>
                        <span class="badge badge-seo-good px-3 py-2 rounded-pill" id="seoOverallScore">SEO Health: Good</span>
                    </div>
                    <div class="admin-blog-card-body">
                        <!-- Focus Keyword input -->
                        <div class="mb-4">
                            <label for="focus_keyword" class="form-label">Focus Target Keyword</label>
                            <input 
                                type="text" 
                                id="focus_keyword" 
                                name="focus_keyword" 
                                class="form-control" 
                                value="{{ old('focus_keyword', $data->focus_keyword ?? '') }}" 
                                placeholder="e.g. library automation software india">
                            <small class="text-muted" style="font-size: 11px;">Target search term you want this post to rank for on Google.</small>
                        </div>

                        <!-- Live SERP Preview Container (Upgraded Google Search Result Simulator) -->
                        <div class="serp-box mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="d-inline-flex align-items-center justify-content-center bg-white rounded-circle shadow-xs border" style="width: 28px; height: 28px;">
                                        <i class="fa-brands fa-google text-primary" style="font-size: 14px; color: #4285F4 !important;"></i>
                                    </span>
                                    <span class="fw-bold text-dark small" style="font-family: 'Outfit', sans-serif;">Live Google SERP Snippet Preview</span>
                                </div>
                                <div class="btn-group btn-group-sm p-1 bg-light rounded-pill border">
                                    <button type="button" class="btn btn-xs rounded-pill px-3 py-1 fw-bold active-serp-toggle" id="btnPreviewDesktop" onclick="setPreviewMode('desktop')">
                                        <i class="fa-solid fa-desktop me-1"></i> Desktop
                                    </button>
                                    <button type="button" class="btn btn-xs rounded-pill px-3 py-1 text-muted" id="btnPreviewMobile" onclick="setPreviewMode('mobile')">
                                        <i class="fa-solid fa-mobile-screen me-1"></i> Mobile
                                    </button>
                                </div>
                            </div>

                            <!-- Real Google Search Result Card Simulator -->
                            <div id="serpPreviewBox" class="p-3 bg-white border rounded-3 shadow-xs" style="max-width: 600px; font-family: Arial, sans-serif; transition: all 0.3s ease;">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <img src="{{ asset('public/img/favicon.ico') }}" alt="Icon" style="width: 16px; height: 16px;" onerror="this.src='https://google.com/favicon.ico'">
                                    <div class="serp-url text-truncate" id="serpUrlDisplay" style="color: #202124; font-size: 0.82rem; font-family: Arial, sans-serif;">
                                        {{ url('blog') }} › <span id="serpSlugSpan">{{ $data->page_slug ?? 'your-post-slug' }}</span>
                                    </div>
                                </div>
                                <h5 class="serp-title mb-1" id="serpTitleDisplay" style="color: #1a0dab; font-size: 1.15rem; font-weight: 500; font-family: Arial, sans-serif; cursor: pointer;">
                                    {{ $data->meta_title ?? ($data->page_title ?? 'Your Post Title Will Appear Here') }}
                                </h5>
                                <div class="serp-desc text-secondary" id="serpDescDisplay" style="color: #4d5156; font-size: 0.88rem; line-height: 1.58; font-family: Arial, sans-serif;">
                                    {{ $data->meta_description ?? ($data->excerpt ?? 'Provide a descriptive meta snippet to attract user clicks on Google search results page.') }}
                                </div>
                            </div>
                        </div>

                        <!-- SEO Health Checklist -->
                        <h6 class="fw-bold mb-3" style="font-family: 'Outfit', sans-serif; color: #18225f;"><i class="fa-solid fa-circle-check text-success me-1"></i> Real-time SEO Analysis Checklist</h6>
                        <ul class="list-group list-group-flush mb-4 border rounded-3 overflow-hidden" id="seoChecklist">
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2" id="chkFocusKeyword">
                                <span style="font-size: 0.88rem;">Focus Keyword Defined</span>
                                <span class="badge bg-secondary">Check</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2" id="chkTitleKeyword">
                                <span style="font-size: 0.88rem;">Focus Keyword in Post Title</span>
                                <span class="badge bg-secondary">Check</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2" id="chkTitleLength">
                                <span style="font-size: 0.88rem;">Title Length Optimal (40-65 chars)</span>
                                <span class="badge bg-secondary"><span id="lblTitleLen">0</span> chars</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2" id="chkDescLength">
                                <span style="font-size: 0.88rem;">Meta Description Length Optimal (110-165 chars)</span>
                                <span class="badge bg-secondary"><span id="lblDescLen">0</span> chars</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2" id="chkSlugKeyword">
                                <span style="font-size: 0.88rem;">Focus Keyword in Permalink Slug</span>
                                <span class="badge bg-secondary">Check</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2" id="chkImgAlt">
                                <span style="font-size: 0.88rem;">Featured Image Alt Tag Present</span>
                                <span class="badge bg-secondary">Check</span>
                            </li>
                        </ul>

                        <!-- Meta Title & Meta Description Inputs -->
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="meta_title" class="form-label">Custom SEO Meta Title</label>
                                <input 
                                    type="text" 
                                    id="meta_title" 
                                    name="meta_title" 
                                    class="form-control" 
                                    value="{{ old('meta_title', $data->meta_title ?? '') }}" 
                                    placeholder="Custom title tag for search engine result pages...">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="meta_description" class="form-label">Custom SEO Meta Description</label>
                                <textarea 
                                    id="meta_description" 
                                    name="meta_description" 
                                    rows="2" 
                                    class="form-control" 
                                    placeholder="Custom snippet description for search engines...">{{ old('meta_description', $data->meta_description ?? '') }}</textarea>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="canonical_url" class="form-label">Canonical URL</label>
                                <input 
                                    type="url" 
                                    id="canonical_url" 
                                    name="canonical_url" 
                                    class="form-control" 
                                    value="{{ old('canonical_url', $data->canonical_url ?? '') }}" 
                                    placeholder="Leave blank to default to post URL">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="meta_robots" class="form-label">Meta Robots Indexing</label>
                                <select name="meta_robots" id="meta_robots" class="form-select">
                                    <option value="index, follow" {{ old('meta_robots', $data->meta_robots ?? '') == 'index, follow' ? 'selected' : '' }}>Index, Follow (Recommended)</option>
                                    <option value="noindex, follow" {{ old('meta_robots', $data->meta_robots ?? '') == 'noindex, follow' ? 'selected' : '' }}>Noindex, Follow</option>
                                    <option value="index, nofollow" {{ old('meta_robots', $data->meta_robots ?? '') == 'index, nofollow' ? 'selected' : '' }}>Index, Nofollow</option>
                                    <option value="noindex, nofollow" {{ old('meta_robots', $data->meta_robots ?? '') == 'noindex, nofollow' ? 'selected' : '' }}>Noindex, Nofollow</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="schema_type" class="form-label">Structured Schema Type</label>
                                <select name="schema_type" id="schema_type" class="form-select">
                                    <option value="BlogPosting" {{ old('schema_type', $data->schema_type ?? '') == 'BlogPosting' ? 'selected' : '' }}>BlogPosting (Default)</option>
                                    <option value="Article" {{ old('schema_type', $data->schema_type ?? '') == 'Article' ? 'selected' : '' }}>Article</option>
                                    <option value="NewsArticle" {{ old('schema_type', $data->schema_type ?? '') == 'NewsArticle' ? 'selected' : '' }}>NewsArticle</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="meta_keyword" class="form-label">Meta Keywords</label>
                                <input 
                                    type="text" 
                                    id="meta_keyword" 
                                    name="meta_keyword" 
                                    class="form-control" 
                                    value="{{ old('meta_keyword', $data->meta_keyword ?? '') }}" 
                                    placeholder="Comma separated keywords...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT SIDEBAR COLUMN (4 Cols) -->
            <div class="col-lg-4">
                <!-- Publishing Controls Card -->
                <div class="admin-blog-card">
                    <div class="admin-blog-card-header">
                        <span><i class="fa-solid fa-paper-plane me-2"></i> Publish Settings</span>
                    </div>
                    <div class="admin-blog-card-body">
                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Publication Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="published" {{ old('status', $data->status ?? 'published') == 'published' ? 'selected' : '' }}>Published</option>
                                <option value="draft" {{ old('status', $data->status ?? '') == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="scheduled" {{ old('status', $data->status ?? '') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                            </select>
                        </div>

                        <!-- Publish Date -->
                        <div class="mb-3">
                            <label for="published_at" class="form-label">Publish Date & Time</label>
                            <input 
                                type="datetime-local" 
                                id="published_at" 
                                name="published_at" 
                                class="form-control" 
                                value="{{ old('published_at', isset($data->published_at) ? $data->published_at->format('Y-m-d\TH:i') : '') }}">
                        </div>

                        <!-- Author Name -->
                        <div class="mb-3">
                            <label for="author_name" class="form-label">Author Name</label>
                            <input 
                                type="text" 
                                id="author_name" 
                                name="author_name" 
                                class="form-control" 
                                value="{{ old('author_name', $data->author_name ?? 'Libraro Team') }}">
                        </div>

                        <!-- Featured Checkbox -->
                        <div class="form-check form-switch mb-3">
                            <input 
                                class="form-check-input" 
                                type="checkbox" 
                                id="is_featured" 
                                name="is_featured" 
                                value="1" 
                                {{ old('is_featured', $data->is_featured ?? 0) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="is_featured" style="color: #18225f; font-size: 0.85rem;">Set as Featured Post</label>
                        </div>

                        <hr>

                        <!-- Action Buttons UI -->
                        <div class="d-grid gap-2">
                            <button type="submit" name="post_action" value="published" class="btn btn-primary button py-2 font-outfit fw-bold rounded-3">
                                <i class="fa-solid fa-paper-plane me-1"></i> {{ isset($data) ? 'Update Post' : 'Publish Blog Post' }}
                            </button>
                            <button type="submit" name="post_action" value="draft" class="btn btn-outline-secondary py-2 font-outfit fw-bold rounded-3">
                                <i class="fa-solid fa-file-pen me-1"></i> Save as Draft
                            </button>
                        </div>
                    </div>
                </div>

                <!-- FEATURED HEADER IMAGE CARD (Sleek Preview & Upload) -->
                <div class="admin-blog-card">
                    <div class="admin-blog-card-header">
                        <span><i class="fa-solid fa-image me-2"></i> Featured Header Image</span>
                    </div>
                    <div class="admin-blog-card-body">
                        <label class="form-label">Post Image File</label>

                        <!-- Hidden File Input -->
                        <input 
                            type="file" 
                            id="header_image" 
                            name="header_image" 
                            class="d-none" 
                            onchange="readImageURL(this);" 
                            accept="image/*">
                        
                        <!-- Drag & Drop Upload Zone (Shown when NO image is selected) -->
                        <div class="image-upload-dropzone text-center mb-3 {{ isset($data) && $data->header_image ? 'd-none' : '' }}" id="dropzoneBox" onclick="document.getElementById('header_image').click();">
                            <i class="fa-solid fa-cloud-arrow-up fa-2x text-primary mb-2" style="color: #34939F !important;"></i>
                            <h6 class="fw-bold text-dark mb-1" style="font-family: 'Outfit', sans-serif; font-size: 0.92rem;">Click or Drag & Drop Header Image</h6>
                            <p class="text-muted small mb-0" style="font-size: 11px; font-family: 'Mulish', sans-serif;">PNG, JPG, WebP (Recommended: 1200x630px)</p>
                        </div>

                        <!-- Sleek Full Image Preview Container (Shown when an image IS selected) -->
                        <div id="previewWrapper" class="position-relative rounded-3 overflow-hidden border shadow-xs mb-3 {{ isset($data) && $data->header_image ? '' : 'd-none' }}">
                            <img src="{{ isset($data) && $data->header_image ? asset('public/' . $data->header_image) : '' }}" 
                                 alt="Header Preview" 
                                 id="headerImgPreview" 
                                 class="w-100 d-block" 
                                 style="height: 190px; object-fit: cover;">
                            
                            <div class="position-absolute bottom-0 start-0 end-0 p-2 d-flex justify-content-center gap-2" style="background: linear-gradient(to top, rgba(15, 23, 42, 0.85), transparent);">
                                <button type="button" class="btn btn-sm btn-light rounded-pill px-3 py-1 fw-bold shadow-sm" style="font-size: 12px; font-family: 'Outfit', sans-serif; color: #18225f;" onclick="document.getElementById('header_image').click();">
                                    <i class="fa-solid fa-arrows-rotate me-1" style="color: #34939F;"></i> Change Image
                                </button>
                                <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 py-1 fw-bold shadow-sm" style="font-size: 12px; font-family: 'Outfit', sans-serif;" onclick="removeImage();">
                                    <i class="fa-solid fa-trash me-1"></i> Remove
                                </button>
                            </div>
                        </div>

                        @error('header_image')
                            <div class="text-danger small mb-2" style="font-size: 11px;"><strong>{{ $message }}</strong></div>
                        @enderror

                        <div class="mb-2">
                            <label for="image_alt" class="form-label">Image Alt Text (SEO)</label>
                            <input 
                                type="text" 
                                id="image_alt" 
                                name="image_alt" 
                                class="form-control" 
                                value="{{ old('image_alt', $data->image_alt ?? '') }}" 
                                placeholder="Describe image for accessibility & search engines...">
                        </div>
                    </div>
                </div>

                <!-- Taxonomies (Clean Native Categories & Tagify Tags Card) -->
                <div class="admin-blog-card">
                    <div class="admin-blog-card-header">
                        <span><i class="fa-solid fa-tags me-2"></i> Taxonomies</span>
                    </div>
                    <div class="admin-blog-card-body">
                        <!-- Clean Scrollable Category Checkbox List (Zero Dependency) -->
                        <div class="mb-3">
                            <label class="form-label">Categories</label>
                            @php
                                $selectedCats = old('categories_id', isset($data->categories_id) ? (is_array($data->categories_id) ? $data->categories_id : json_decode($data->categories_id, true)) : []);
                            @endphp
                            <div class="category-scroll-box">
                                @foreach($categories as $category)
                                    <div class="form-check mb-2">
                                        <input 
                                            class="form-check-input" 
                                            type="checkbox" 
                                            name="categories_id[]" 
                                            id="cat_{{ $category->id }}" 
                                            value="{{ $category->id }}" 
                                            {{ in_array($category->id, $selectedCats ?? []) ? 'checked' : '' }}>
                                        <label class="form-check-label text-dark fw-semibold" for="cat_{{ $category->id }}" style="font-size: 0.88rem; font-family: 'Mulish', sans-serif;">
                                            {{ $category->name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Check one or more categories for this post.</small>
                        </div>

                        <!-- Tagify Tags Input -->
                        <div class="mb-3">
                            <label for="tags" class="form-label">Tags</label>
                            @php
                                $tagsVal = old('tags');
                                if(!$tagsVal && isset($data->tags)) {
                                    $tagsVal = is_array($data->tags) ? implode(',', $data->tags) : implode(',', json_decode($data->tags, true) ?? []);
                                }
                            @endphp
                            <input 
                                id="tags" 
                                name="tags" 
                                class="form-control" 
                                value="{{ $tagsVal }}"
                                placeholder="Type tag and press enter">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- JS CDN Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
<script src="https://cdn.ckeditor.com/ckeditor5/34.2.0/classic/ckeditor.js"></script>

<script>
    let editorInstance = null;

    document.addEventListener("DOMContentLoaded", function() {
        // 1. Tagify Tags Init (Auto Height)
        if (document.querySelector('#tags')) {
            new Tagify(document.querySelector('#tags'), {
                placeholder: "Type tag and press enter..."
            });
        }

        // 2. Instant Real-Time Auto Slug Generator on Input Event
        const pageTitleInput = document.getElementById('page_title');
        const pageSlugInput = document.getElementById('page_slug');

        if (pageTitleInput && pageSlugInput) {
            pageTitleInput.addEventListener('input', function() {
                let title = this.value;
                let slug = title
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');

                pageSlugInput.value = slug;
                updateSeoPreview();
            });

            pageSlugInput.addEventListener('input', function() {
                updateSeoPreview();
            });
        }

        // Event Listeners for SEO Inputs
        ['meta_title', 'meta_description', 'excerpt', 'focus_keyword', 'image_alt'].forEach(id => {
            let elem = document.getElementById(id);
            if (elem) {
                elem.addEventListener('input', function() {
                    updateSeoPreview();
                });
            }
        });

        // 3. CKEditor 5 Initialization with Multi-Global Fallback
        const editorTarget = document.querySelector('#editor');
        if (editorTarget) {
            let createClassicEditor = null;
            if (typeof ClassicEditor !== 'undefined') {
                createClassicEditor = ClassicEditor;
            } else if (typeof CKEDITOR !== 'undefined' && CKEDITOR.ClassicEditor) {
                createClassicEditor = CKEDITOR.ClassicEditor;
            }

            if (createClassicEditor) {
                createClassicEditor
                    .create(editorTarget, {
                        placeholder: 'Compose rich blog article content here (Type paragraphs, headings, tables, quotes, code blocks)...'
                    })
                    .then(editor => {
                        editorInstance = editor;
                        editor.model.document.on('change:data', () => {
                            editorTarget.value = editor.getData();
                            runSeoCheck();
                        });
                    })
                    .catch(error => {
                        console.error("CKEditor initialization error:", error);
                        editorTarget.style.display = 'block';
                        editorTarget.style.minHeight = '350px';
                    });
            } else {
                editorTarget.style.display = 'block';
                editorTarget.style.minHeight = '350px';
            }
        }

        // Form Submit Sync Guarantee
        const blogForm = document.getElementById('blogForm');
        if (blogForm) {
            blogForm.addEventListener('submit', function() {
                if (editorInstance) {
                    document.querySelector('#editor').value = editorInstance.getData();
                }
            });
        }

        updateSeoPreview();
        runSeoCheck();
    });

    function readImageURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('headerImgPreview').src = e.target.result;
                let previewWrapper = document.getElementById('previewWrapper');
                let dropzoneBox = document.getElementById('dropzoneBox');
                if (previewWrapper) previewWrapper.classList.remove('d-none');
                if (dropzoneBox) dropzoneBox.classList.add('d-none');
                if (typeof runSeoCheck === 'function') runSeoCheck();
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function removeImage() {
        document.getElementById('header_image').value = '';
        document.getElementById('headerImgPreview').src = '';
        let previewWrapper = document.getElementById('previewWrapper');
        let dropzoneBox = document.getElementById('dropzoneBox');
        if (previewWrapper) previewWrapper.classList.add('d-none');
        if (dropzoneBox) dropzoneBox.classList.remove('d-none');
        if (typeof runSeoCheck === 'function') runSeoCheck();
    }

    function setPreviewMode(mode) {
        let box = document.getElementById('serpPreviewBox');
        let btnDesk = document.getElementById('btnPreviewDesktop');
        let btnMob = document.getElementById('btnPreviewMobile');

        if (mode === 'mobile') {
            box.style.maxWidth = '360px';
            btnMob.className = 'btn btn-xs rounded-pill px-3 py-1 fw-bold active-serp-toggle';
            btnDesk.className = 'btn btn-xs rounded-pill px-3 py-1 text-muted';
        } else {
            box.style.maxWidth = '600px';
            btnDesk.className = 'btn btn-xs rounded-pill px-3 py-1 fw-bold active-serp-toggle';
            btnMob.className = 'btn btn-xs rounded-pill px-3 py-1 text-muted';
        }
    }

    function updateSeoPreview() {
        let titleVal = document.getElementById('meta_title').value || document.getElementById('page_title').value || 'Your Post Title Will Appear Here';
        let descVal = document.getElementById('meta_description').value || document.getElementById('excerpt').value || 'Provide a descriptive meta snippet to attract user clicks on Google search results page.';
        let slugVal = document.getElementById('page_slug').value || 'your-post-slug';

        document.getElementById('serpTitleDisplay').innerText = titleVal;
        document.getElementById('serpDescDisplay').innerText = descVal;
        
        let slugSpan = document.getElementById('serpSlugSpan');
        if (slugSpan) slugSpan.innerText = slugVal;

        document.getElementById('lblTitleLen').innerText = titleVal.length;
        document.getElementById('lblDescLen').innerText = descVal.length;

        runSeoCheck();
    }

    function runSeoCheck() {
        let keyword = (document.getElementById('focus_keyword').value || '').toLowerCase().trim();
        let title = (document.getElementById('meta_title').value || document.getElementById('page_title').value || '').toLowerCase();
        let slug = (document.getElementById('page_slug').value || '').toLowerCase();
        let desc = (document.getElementById('meta_description').value || document.getElementById('excerpt').value || '').toLowerCase();
        let imgAlt = (document.getElementById('image_alt').value || '').trim();

        // 1. Focus Keyword set
        setBadge('chkFocusKeyword', keyword.length > 0);

        // 2. Keyword in Title
        setBadge('chkTitleKeyword', keyword.length > 0 && title.includes(keyword));

        // 3. Title length
        let titleLen = title.length;
        setBadge('chkTitleLength', titleLen >= 40 && titleLen <= 65);

        // 4. Desc length
        let descLen = desc.length;
        setBadge('chkDescLength', descLen >= 110 && descLen <= 165);

        // 5. Keyword in Slug
        setBadge('chkSlugKeyword', keyword.length > 0 && slug.includes(keyword.replace(/\s+/g, '-')));

        // 6. Image Alt
        setBadge('chkImgAlt', imgAlt.length > 0);
    }

    function setBadge(elemId, isSuccess) {
        let li = document.getElementById(elemId);
        if (!li) return;
        let badge = li.querySelector('.badge');
        if (isSuccess) {
            badge.className = 'badge bg-success';
            badge.innerHTML = '<i class="fa-solid fa-check"></i> Good';
        } else {
            badge.className = 'badge bg-warning text-dark';
            badge.innerHTML = 'Needs Improvement';
        }
    }
</script>
@endsection
