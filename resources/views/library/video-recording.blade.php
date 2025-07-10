@extends('layouts.library')

@section('title', 'Admin Dashboard')

@section('content')
<style>
    .card-title {
        font-size: 1.25rem;
        color: #1a237e;
    }

    .video-container {
        position: relative;
        padding-bottom: 56.25%;
        /* 16:9 ratio */
        padding-top: 25px;
        height: 0;
    }

    .video-container video {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
</style>
@if (session('error'))
<div class="alert alert-danger">
    {{ session('error') }}
</div>
@endif

@if (session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif


    <div class="row">

       @forelse ($video_list as $video)
            <div class="col-lg-4">
                <div class="p-3 bg-white shadow-sm border-0 rounded-4 overflow-hidden h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title mb-3 text-primary fw-semibold">
                            {{ $video->video_titel ?? 'Untitled Video' }}
                        </h5>
                        <div class="ratio ratio-16x9 mb-3">
                            <video controls>
                                <source src="{{ asset('public/uploade/' . $video->video) }}" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center mt-4">
                <div class="alert alert-warning rounded-3">
                    No videos Uploaded.
                </div>
            </div>
        @endforelse

    </div>


@include('library.script')
@endsection