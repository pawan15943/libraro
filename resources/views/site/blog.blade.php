@extends('sitelayouts.layout')
@section('content')
<section class="inner-header">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h1>Blog</h1>
            </div>
        </div>
    </div>
</section>

<section class="breadcrumb">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Blog</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</section>

<section class="page-content">
    <div class="container">
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
                        <a href="{{ route('blog-detail', ['slug' => $value->page_slug]) }}">Read Full Article >></a>

                    </div>
                </div>
            @endforeach
        </div>
    </div>
  
</section>
@endsection