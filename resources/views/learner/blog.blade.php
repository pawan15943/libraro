@extends('layouts.learner')

@section('title', 'Blog')

@section('content')
@php $posts = collect($data ?? []); @endphp

<div class="lr-head">
    <div>
        <h1>Blog</h1>
        <p>Study tips and news to read between sessions.</p>
    </div>
</div>

@if ($posts->isEmpty())
    <div class="lr-card">
        <div class="lr-empty">
            <div class="lr-empty-ic"><x-lb-icon name="newspaper" /></div>
            <b>No posts yet</b>
            <p>New articles will show here.</p>
        </div>
    </div>
@else
    <div class="lr-posts">
        @foreach ($posts as $post)
            <a href="{{ route('blog.detail.show', ['slug' => $post->page_slug]) }}" class="lr-post">
                <img src="{{ $post->header_image ? asset('public/' . $post->header_image) : asset('public/img/blog.png') }}" alt="" loading="lazy">
                <div style="min-width:0">
                    <b>{{ $post->page_title }}</b>
                    @if (!empty($post->meta_description ?? null))<p>{{ \Illuminate\Support\Str::limit(strip_tags($post->meta_description), 120) }}</p>@endif
                    <span>Read <x-lb-icon name="arrow-right" size="14" /></span>
                </div>
            </a>
        @endforeach
    </div>
@endif
@endsection
