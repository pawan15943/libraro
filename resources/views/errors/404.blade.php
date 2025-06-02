@extends('layouts.app')

@section('content')

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<link rel="stylesheet" href="{{ asset('public/css/style.css') }}">
<style>
    main.py-4 {
        padding: 0;
        background: linear-gradient(180deg, #ffffff, #ced5ff);
        height: 100vh;
    }

    h1 {
        font-size: 200px;
        color: navy !important;
        font-weight: 800;
    }

    .logo {
        width: 180px;
    }
</style>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
            <a href="{{ url('/') }}">
                <img src="{{ asset('public/img/libraro-logo.svg') }}" alt="Libraro Logo" class="logo"></a>

            <div class="error-box">
                <h1>404</h1>
                <h2>Oops! 🚧 You’ve wandered off track...</h2>
                <p>Looks like the page you’re looking for has taken a detour or doesn’t exist anymore. But don’t worry! Let’s get you back on track.Try searching again</p>
                <a class="text-center mt-4"><a href="{{route('/')}}" class="btn btn-primary button w-25 m-auto">Go to Homepage</a></a>
            </div>
        </div>
    </div>
</div>
@endsection