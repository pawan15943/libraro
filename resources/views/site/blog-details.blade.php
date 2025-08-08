@extends('sitelayouts.layout')
@section('content')

<section class="blog-header">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="text-center">{{ $data->page_title }}</h1>
            </div>
        </div>
    </div>
</section>

<section class="blog-content py-5">
    <div class="container">
       {{-- Categories --}}
        @if($categories->isNotEmpty())
            
            <ul>
                @foreach($categories as $category)
                    <li>{{ $category->name }}</li>
                @endforeach
            </ul>
        @endif
        <h2>{{ $data->page_title }}</h2>
        <ul>
            <li>Author</li>
            <li>Posted on 08-08-2025</li>
        </ul>
        <div class="row justify-content-center">
            <div class="col-lg-8">
            <img src="{{ asset('public/' . $data->header_image) }}" alt="{{ $data->page_title }}">

                <div class="mt-4">
                {!! $data->page_content !!}
                </div>
            </div>

            <div class="col-lg-8">
                <div class="ul">
                    @if(!empty($data->tags))
                    @foreach($data->tags as $tag)
                        <li>{{ $tag }}</li>
                    @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

@endsection