@extends('layouts.app') {{-- Use your layout if available --}}

@section('title', '403 Forbidden')

@section('content')
<div class="container text-center mt-5">
    <h1 class="display-4 text-danger">403</h1>
    <p class="lead">Forbidden: You don’t have permission to access this page.</p>
    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Go Back</a>
</div>
@endsection
