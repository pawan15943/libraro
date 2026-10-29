{{--
    Onboarding layout (after OTP): Choose plan → Payment → Branch → Shifts → Dashboard.
    Full-screen, no sidebar. Pages set:
      @section('ob_step', 'plan'|'branch'|'shifts')   current step
      @section('ob_stepper', '0')                       hide the stepper (e.g. upgrade/renew from My Plan)
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    @include('lb.partials.head-common')
    <title>@yield('title', 'Set up your library') | Libraro</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="_token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('public/css/lb-onboard.css') }}?v={{ @filemtime(public_path('css/lb-onboard.css')) }}">
    @stack('head')
</head>
<body class="lb ob @yield('body_class')">
@php
    $obStep = trim($__env->yieldContent('ob_step')) ?: 'plan';
    $obShowSteps = trim($__env->yieldContent('ob_stepper')) !== '0';
    $obSteps = [
        'account' => ['Account', 'circle-check'],
        'plan'    => ['Choose plan', 'badge-check'],
        'branch'  => ['Branch setup', 'building'],
        'shifts'  => ['Shifts & fees', 'clock-3'],
        'ready'   => ['Go live', 'sparkles'],
    ];
    $obKeys = array_keys($obSteps);
    $obIdx = array_search($obStep, $obKeys, true);
    $obLib = function_exists('getLibrary') ? getLibrary() : null;
@endphp
<a href="#ob-main" class="lb-skip">Skip to content</a>

<header class="ob-top">
    <div class="ob-top-in">
        @include('lb.partials.logo', ['height' => 32])
        <div class="ob-top-right">
            <a href="tel:+918114479678" class="ob-help"><x-lb-icon name="headset" /><span>Need help? <b>+91 81144 79678</b></span></a>
            @include('lb.partials.theme-toggle')
            <form method="POST" action="{{ route('logout') }}" class="ob-logout">
                @csrf
                <button type="submit" aria-label="Log out" title="Log out"><x-lb-icon name="log-out" /></button>
            </form>
        </div>
    </div>
    @if($obShowSteps)
        <nav class="ob-steps" aria-label="Setup progress">
            <ol>
                @foreach($obSteps as $key => [$label, $icon])
                    @php $i = array_search($key, $obKeys, true); @endphp
                    <li class="{{ $i < $obIdx ? 'is-done' : ($i === $obIdx ? 'is-on' : '') }}" @if($i === $obIdx) aria-current="step" @endif>
                        <i>@if($i < $obIdx)<x-lb-icon name="check" />@else{{ $i + 1 }}@endif</i>
                        <span>{{ $label }}</span>
                    </li>
                @endforeach
            </ol>
            <div class="ob-steps-bar" aria-hidden="true"><span style="width: {{ round(($obIdx / (count($obSteps) - 1)) * 100) }}%"></span></div>
        </nav>
    @endif
</header>

<main class="ob-main" id="ob-main">
    @if(session('success') || session('error') || session('message'))
        <div class="ob-flash-wrap">
            @if(session('success'))<div class="ob-flash ob-flash--ok"><x-lb-icon name="circle-check" />{{ session('success') }}</div>@endif
            @if(session('error'))<div class="ob-flash ob-flash--err"><x-lb-icon name="circle-alert" />{{ session('error') }}</div>@endif
            @if(session('message'))<div class="ob-flash"><x-lb-icon name="info" />{{ session('message') }}</div>@endif
        </div>
    @endif
    @yield('ob_content')
    @yield('content')
    @include('lb.partials.ob-contact')
</main>

<div class="ob-toasts" id="ob-toasts" aria-live="polite"></div>
<script src="{{ asset('public/js/lb-core.js') }}?v={{ @filemtime(public_path('js/lb-core.js')) }}" defer></script>
<script>
    /* Small toast helper (keeps toastr.* calls working on these pages) */
    window.obToast = function (msg, type) {
        var box = document.getElementById('ob-toasts'); if (!box || !msg) return;
        var t = document.createElement('div'); t.className = 'ob-toast ob-toast--' + (type || 'info'); t.textContent = msg;
        box.appendChild(t); setTimeout(function () { t.classList.add('is-out'); setTimeout(function () { t.remove(); }, 400); }, 3600);
    };
    window.toastr = window.toastr || { success: function (m) { obToast(m, 'ok'); }, error: function (m) { obToast(m, 'err'); }, info: function (m) { obToast(m); }, warning: function (m) { obToast(m, 'err'); } };
</script>
@stack('scripts')
</body>
</html>
