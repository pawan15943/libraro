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
            <div class="col-lg-8">
                <img src="{{ $data->header_image }} " alt="{{ $data->page_title }}">
                <div class="">

                {!! $data->page_content !!}
                </div>
            </div>
        </div>
    </div>
</section>

@endsection