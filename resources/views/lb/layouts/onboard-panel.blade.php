{{--
    Same onboarding pages (plan / branch / shifts) shown inside the owner panel
    (sidebar + top bar) once the library is already set up — e.g. renew/upgrade
    from My Plan, or Library Master Console → Shifts. Pages put their body in
    @section('ob_content') and their scripts in @push('scripts').
--}}
@extends('layouts.library')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-base.css') }}?v={{ @filemtime(public_path('css/lb-base.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-onboard.css') }}?v={{ @filemtime(public_path('css/lb-onboard.css')) }}">
<meta name="_token" content="{{ csrf_token() }}">
@stack('head')
@endpush

@section('content')
<div class="lb ob ob-in-panel @yield('body_class')">
    @yield('ob_content')
    @include('lb.partials.ob-contact')
</div>
<script>
    window.obToast = function (msg, type) {
        if (window.toastr && typeof toastr[type === 'err' ? 'error' : (type === 'ok' ? 'success' : 'info')] === 'function') {
            toastr[type === 'err' ? 'error' : (type === 'ok' ? 'success' : 'info')](msg);
        }
    };
</script>
@stack('scripts')
@endsection
