@extends('layouts.library')

@section('title', 'Video Tutorials')

@section('content')

<link rel="stylesheet" href="{{ asset('public/css/library-video-training.css') }}?v={{ time() }}">

<div class="library-video-training-module">
    @if(count($video_list) > 0)
    <!-- Top Action Bar: Right-aligned controls (no extra heading) -->
    <div class="video-toolbar-bar">
        <div class="video-search-wrap">
            <i class="fa-solid fa-magnifying-glass video-search-icon"></i>
            <input type="text" 
                   class="video-search-input" 
                   id="videoSearchInput" 
                   placeholder="Search tutorials..." 
                   autocomplete="off">
        </div>

        <span class="video-count-pill" id="videoCountPill">
            <i class="fa-solid fa-circle-play"></i> {{ count($video_list) }} Video Guides
        </span>
    </div>

    <!-- Video Cards Grid -->
    <div class="row g-4" id="videoCardsGrid">
        @foreach ($video_list as $video)
        <div class="col-xl-4 col-lg-6 col-md-6 col-12 video-card-item" 
             data-title="{{ strtolower($video->video_titel ?? 'untitled video') }}">
            <div class="video-card">
                <div class="video-frame-container">
                    <span class="video-tag-badge">
                        <i class="fa-solid fa-circle-play"></i> Tutorial #{{ $loop->iteration }}
                    </span>
                    <span class="video-format-pill">MP4 HD</span>

                    @if(!empty($video->video))
                    <video controls preload="metadata" playsinline>
                        <source src="{{ asset('public/uploade/' . $video->video) }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    @else
                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                        <i class="fa-solid fa-video-slash me-2"></i> Video source not available
                    </div>
                    @endif
                </div>

                <div class="video-content-body">
                    <h5 class="video-title-text" title="{{ $video->video_titel ?? 'Untitled Tutorial Video' }}">
                        {{ $video->video_titel ?? 'Untitled Tutorial Video' }}
                    </h5>

                    <div class="video-meta-row">
                        <span class="video-type-label">
                            <i class="fa-solid fa-graduation-cap"></i> Step-by-Step Guide
                        </span>
                        <span class="video-date-label">
                            <i class="fa-regular fa-calendar"></i> 
                            {{ !empty($video->created_at) ? \Carbon\Carbon::parse($video->created_at)->format('M Y') : 'Official' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- No Search Results Found Alert -->
    <div class="video-empty-card" id="noSearchResults" style="display: none;">
        <div class="video-empty-icon-box">
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>
        <h4>No matching tutorials found</h4>
        <p>We couldn't find any video guides matching your search query. Try searching with different keywords.</p>
    </div>

    @else
    <!-- Empty State -->
    <div class="video-empty-card">
        <div class="video-empty-icon-box">
            <i class="fa-solid fa-video-slash"></i>
        </div>
        <h4>No Training Videos Available Yet</h4>
        <p>Tutorial walkthroughs and step-by-step feature guides will appear here once published by the administration.</p>
    </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('videoSearchInput');
        const cards = document.querySelectorAll('.video-card-item');
        const noResults = document.getElementById('noSearchResults');
        const countPill = document.getElementById('videoCountPill');
        const totalCards = cards.length;

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.trim().toLowerCase();
                let visibleCount = 0;

                cards.forEach(card => {
                    const title = card.getAttribute('data-title') || '';
                    if (title.includes(query)) {
                        card.style.display = '';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                if (noResults) {
                    noResults.style.display = (visibleCount === 0 && query !== '') ? 'block' : 'none';
                }

                if (countPill) {
                    if (query !== '') {
                        countPill.innerHTML = `<i class="fa-solid fa-filter"></i> ${visibleCount} of ${totalCards} Guides`;
                    } else {
                        countPill.innerHTML = `<i class="fa-solid fa-circle-play"></i> ${totalCards} Video Guides`;
                    }
                }
            });
        }
    });
</script>

@include('library.script')
@endsection