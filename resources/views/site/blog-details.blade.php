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
        <div class="row justify-content-center">
            <div class="col-lg-7">
                {{-- Categories --}}
                @if($categories->isNotEmpty())

                <ul class="categories-list mb-4">
                    @foreach($categories as $category)
                    <li>{{ $category->name }}</li>
                    @endforeach
                </ul>
                @endif
                <h2>{{ $data->page_title }}</h2>
                <ul class="categories-list mb-4">
                    <li>Author</li>
                    <li>Posted on 08-08-2025</li>
                </ul>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-7">
                <img src="{{ asset('public/' . $data->header_image) }}" alt="{{ $data->page_title }}" class="img-fluid mb-4 rounded-2">

                <div class="mt-4">
                    {!! $data->page_content !!}
                </div>
            </div>

            <div class="col-lg-7">
                <ul class="tags-list mt-4">
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