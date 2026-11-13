@extends('layouts.learner')

@section('title', 'Blog')

@section('content')
<div style="max-width:720px;margin:0 auto">
    <a href="{{ route('learner.blog') }}" class="lr-back"><x-lb-icon name="arrow-left" size="16" /> All posts</a>

    <div class="lr-head" style="margin-top:0">
        <div>
            <h1>{{ $data->page_title }}</h1>
            @if ($data->created_at)<p>{{ $data->created_at->format('j M Y') }}</p>@endif
        </div>
    </div>

    @if ($categories->isNotEmpty())
        <div class="lr-tags" style="margin-bottom:14px">
            @foreach ($categories as $category)
                <span class="lr-chip acc no-dot">{{ $category->name }}</span>
            @endforeach
        </div>
    @endif

    @if ($data->header_image)
        <img src="{{ asset('public/' . $data->header_image) }}" alt="" class="lr-cover">
    @endif

    <article class="lr-card lr-article">
        {!! $data->page_content !!}
    </article>

    @if (!empty($data->tags))
        <div class="lr-tags" style="margin-top:14px">
            @foreach ((array) $data->tags as $tag)
                <span class="lr-chip mut no-dot">#{{ is_array($tag) ? ($tag['value'] ?? '') : $tag }}</span>
            @endforeach
        </div>
    @endif
</div>
@endsection
