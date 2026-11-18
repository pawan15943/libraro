@extends('lb.layouts.auth')

@section('seo')
    <title>Learner Login | Libraro</title>
    <meta name="description" content="Learners can log in to Libraro to see their seat, plan, attendance, fee receipts and library ID card.">
    <meta name="robots" content="noindex, follow">
@endsection

@section('body_class', 'll-page')

@push('head')
    <link rel="preload" as="image" imagesrcset="{{ asset('public/img/site/study-girl-800.webp') }} 800w, {{ asset('public/img/site/study-girl-1600.webp') }} 1600w" imagesizes="(max-width: 960px) 100vw, 40vw">
    <link rel="stylesheet" href="{{ asset('public/css/lb-learner-login.css') }}?v={{ @filemtime(public_path('css/lb-learner-login.css')) }}">
@endpush

@section('content')
<div class="ll">
    {{-- ================= Form ================= --}}
    <section class="ll-form-side">
        <header class="ll-top">
            @include('lb.partials.logo', ['height' => 30])
            @include('lb.partials.theme-toggle')
        </header>

        <main class="ll-main" id="lb-main">
            <div class="ll-box">
                <span class="ll-tag">Student login</span>
                <h1 class="ll-title">Welcome back<span>.</span></h1>
                <p class="ll-sub">Log in to see your seat, plan, attendance and receipts.</p>

                <form method="POST" action="{{ route('login.store') }}" class="lb-form ll-form validateForm" autocomplete="on">
                    @csrf
                    <input type="hidden" name="user_type" value="learner">

                    @include('lb.partials.session-alerts')

                    <div class="lb-field">
                        <label class="lb-label" for="learner_no">Learner number</label>
                        <div class="lb-input @error('learner_no') is-invalid @enderror">
                            <input id="learner_no" type="text" name="learner_no" value="{{ old('learner_no') }}" placeholder="Enter your learner number" autocomplete="username" autocapitalize="characters" spellcheck="false" required autofocus>
                        </div>
                        @include('lb.partials.field-error', ['name' => 'learner_no'])
                    </div>

                    <div class="lb-field">
                        <label class="lb-label" for="password">Password</label>
                        <div class="lb-input @error('password') is-invalid @enderror">
                            <input id="password" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                            <button type="button" class="lb-eye" aria-label="Show password"><x-lb-icon name="eye" class="lb-i-eye" /><x-lb-icon name="eye-off" class="lb-i-eye-off" /></button>
                        </div>
                        <span class="ll-caps" data-ll-caps hidden><x-lb-icon name="info" />Caps Lock is on</span>
                        @include('lb.partials.field-error', ['name' => 'password'])
                    </div>

                    <label class="lb-check" for="remember">
                        <input type="checkbox" name="remember" id="remember" class="no-validate" {{ old('remember') ? 'checked' : '' }}>
                        <span>Keep me signed in</span>
                    </label>

                    <button type="submit" class="lb-btn lb-btn--lg lb-btn--block ll-btn" data-loading-text="Logging in…">
                        <span class="lb-btn-label">Log in</span>
                        <x-lb-icon name="arrow-right" size="18" />
                    </button>
                </form>

                <p class="ll-help">Forgot your learner number or password?<br><strong>Ask your library desk.</strong></p>
            </div>
        </main>

        <footer class="ll-foot">
            <span>&copy; {{ date('Y') }} Libraro</span>
            <a href="tel:+918114479678">Need help? +91 81144 79678</a>
        </footer>
    </section>

    {{-- ================= Visual ================= --}}
    @php
        $llSlides = [
            ['study-girl', '58% 40%', 'Your seat is waiting', 'One login for your seat, attendance and receipts.'],
            ['reader', '64% 35%', 'Every study day counts', 'Scan the QR at the door. Your attendance updates by itself.'],
            ['slide-learner', '46% 40%', 'Receipts and ID card, always with you', 'Download fee receipts and show your digital ID anytime.'],
        ];
    @endphp
    <aside class="ll-visual" aria-hidden="true" data-ll-slider>
        @foreach($llSlides as $k => $s)
            <img class="ll-slide {{ $k === 0 ? 'is-active' : '' }}" style="object-position: {{ $s[1] }}"
                 src="{{ asset('public/img/site/' . $s[0] . '-1600.webp') }}"
                 srcset="{{ asset('public/img/site/' . $s[0] . '-800.webp') }} 800w, {{ asset('public/img/site/' . $s[0] . '-1600.webp') }} 1600w"
                 sizes="(max-width: 960px) 100vw, 40vw" alt="" decoding="async" @if($k) loading="lazy" @endif>
        @endforeach

        <div class="ll-pass">
            <div class="ll-pass-row">
                <span class="ll-pass-brand">LIBRARO <em>PASS</em></span>
                <span class="ll-pass-live"><i></i>Active</span>
            </div>
            <div class="ll-pass-row ll-pass-main">
                <div>
                    <small>Seat</small>
                    <b>A-12</b>
                </div>
                <div>
                    <small>Plan left</small>
                    <b>18 days</b>
                </div>
            </div>
            <span class="ll-pass-bar"><i></i></span>
        </div>

        <div class="ll-caption">
            <div class="ll-caption-stack">
                @foreach($llSlides as $k => $s)
                    <div class="ll-cap {{ $k === 0 ? 'is-active' : '' }}">
                        <h2>{{ $s[2] }}<span>.</span></h2>
                        <p>{{ $s[3] }}</p>
                    </div>
                @endforeach
            </div>
            <div class="ll-dots">
                @foreach($llSlides as $k => $s)
                    <button type="button" tabindex="-1" class="{{ $k === 0 ? 'is-active' : '' }}"><i></i></button>
                @endforeach
            </div>
        </div>
    </aside>
</div>
@endsection

@push('scripts')
<script>
(function () {
    /* Student photo slider */
    var sl = document.querySelector('[data-ll-slider]');
    if (sl) {
        var imgs = sl.querySelectorAll('.ll-slide'), caps = sl.querySelectorAll('.ll-cap'), dots = sl.querySelectorAll('.ll-dots button');
        var n = imgs.length, i = 0, t = null, SECS = 5;
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        sl.style.setProperty('--ll-dur', SECS + 's');
        var show = function (k) {
            i = (k + n) % n;
            [imgs, caps].forEach(function (list) { list.forEach(function (el, j) { el.classList.toggle('is-active', j === i); }); });
            dots.forEach(function (d, j) {
                d.classList.toggle('is-done', j < i);
                d.classList.remove('is-active');
                if (j === i) { void d.offsetWidth; d.classList.add('is-active'); }
            });
            clearTimeout(t);
            if (!reduce) t = setTimeout(function () { show(i + 1); }, SECS * 1000);
        };
        dots.forEach(function (d, j) { d.addEventListener('click', function () { show(j); }); });
        if (n > 1) show(0);
    }

    var pw = document.getElementById('password'), caps = document.querySelector('[data-ll-caps]');
    if (!pw || !caps) return;
    var chk = function (e) { if (e.getModifierState) caps.hidden = !e.getModifierState('CapsLock'); };
    pw.addEventListener('keydown', chk); pw.addEventListener('keyup', chk);
    pw.addEventListener('blur', function () { caps.hidden = true; });
})();
</script>
@endpush
