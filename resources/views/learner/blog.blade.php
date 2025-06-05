@extends('layouts.learner')
@section('content')


<!-- Learners Blog -->
<div class="row g-4 mb-4">
    @foreach($data as $key => $value)
        <div class="col-lg-4">
            <div class="blog-box">
                @if($value->header_image !="")
               
                <img src="{{ asset('public/'.   $value->header_image ) }}" alt="blog-title" class="blog-thumb">

                @else
                <img src="{{ url('public/img/blog.png') }}" alt="blog-title" class="blog-thumb">
                @endif
                <h4>{{ $value->page_title }}</h4>
                <a href="{{ route('blog.detail.show', ['slug' => $value->page_slug]) }}">Read Full Article >></a>

            </div>
        </div>
    @endforeach
</div>

@endsection