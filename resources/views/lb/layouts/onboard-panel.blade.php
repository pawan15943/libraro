{{--
    The plan / branch / shifts pages inside the owner panel (sidebar + top bar): during the
    first-time setup (with the 3 setup steps on top) and later (renew / upgrade from My Plan,
    Add branch, Shifts). Pages put their body in @section('ob_content') and their scripts in
    @push('scripts').
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
    {{-- first-time setup only: Choose plan → Branch → Shift (a page can hide it: @section('ob_stepper', '0')) --}}
    @if(trim($__env->yieldContent('ob_stepper')) !== '0')
        <div class="ob-wrap ob-wrap--steps">@include('lb.partials.setup-steps', ['step' => ['plan' => 'plan', 'branch' => 'branch', 'shifts' => 'shift'][trim($__env->yieldContent('ob_step'))] ?? null])</div>
    @endif
    @yield('ob_content')
    {{-- pages can leave the help strip out: @section('ob_contact', '0') --}}
    @if(trim($__env->yieldContent('ob_contact')) !== '0')
        @include('lb.partials.ob-contact')
    @endif
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
