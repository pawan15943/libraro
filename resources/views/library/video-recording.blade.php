@extends('layouts.library')

@section('title', 'Video Tutorials')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
@endpush

@section('content')

<div class="lpg">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><span>Video tutorials</span></div>
            <h1>Learn Libraro</h1>
            <p>Short videos that walk you through booking seats, collecting fees, attendance and more.</p>
        </div>
    </header>

    @if(count($video_list) > 0)
        <div class="lpg-tools">
            <label class="lpg-search">
                <x-lb-icon name="search" />
                <input type="search" id="videoSearchInput" placeholder="Search {{ count($video_list) }} tutorials" autocomplete="off">
            </label>
        </div>

        <div class="lvt-grid" id="videoCardsGrid">
            @foreach($video_list as $video)
                @php $vTitle = $video->video_titel ?: 'Untitled tutorial'; @endphp
                <article class="lvt-card video-card-item" data-title="{{ strtolower($vTitle) }}">
                    <div class="lvt-media">
                        <span class="lvt-no"><x-lb-icon name="circle-play" />{{ $loop->iteration }}</span>
                        @if(!empty($video->video))
                            <video controls preload="metadata" playsinline>
                                <source src="{{ asset('public/uploade/' . $video->video) }}" type="video/mp4">
                            </video>
                        @else
                            <div class="lvt-none"><x-lb-icon name="video" />Video not available</div>
                        @endif
                    </div>
                    <div class="lvt-body">
                        <h2 title="{{ $vTitle }}">{{ $vTitle }}</h2>
                        <div class="lvt-meta"><x-lb-icon name="graduation-cap" />Step-by-step guide @if(!empty($video->created_at))· {{ \Carbon\Carbon::parse($video->created_at)->format('M Y') }}@endif</div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="lpg-empty" id="noSearchResults" hidden style="margin-top:14px">
            <span><x-lb-icon name="search" /></span>
            <b>No tutorials match</b>
            <p>Try a simpler word, like “renew”, “attendance” or “payment”.</p>
        </div>
    @else
        <div class="lpg-empty">
            <span><x-lb-icon name="video" /></span>
            <b>No tutorials yet</b>
            <p>Feature walkthroughs will appear here as soon as they’re published.</p>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var input = document.getElementById('videoSearchInput');
        if (!input) return;
        var cards = document.querySelectorAll('.video-card-item');
        var none = document.getElementById('noSearchResults');

        input.addEventListener('input', function () {
            var q = this.value.trim().toLowerCase();
            var n = 0;
            cards.forEach(function (c) {
                var hit = (c.getAttribute('data-title') || '').indexOf(q) !== -1;
                c.hidden = !hit;
                if (hit) n++;
            });
            none.hidden = n > 0;
        });

        // Only one video plays at a time
        document.addEventListener('play', function (e) {
            if (e.target.tagName !== 'VIDEO') return;
            document.querySelectorAll('.lvt-card video').forEach(function (v) { if (v !== e.target) v.pause(); });
        }, true);
    });
</script>

@include('library.script')
@endsection
