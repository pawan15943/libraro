@extends('lb.layouts.auth')

@section('seo')
    <title>Register Library on Libraro | Grow with Library Booking Software</title>
    <link rel="canonical" href="https://www.libraro.in/library/register" />
    <meta name="description" content="Register your library today with Libraro and start managing seat bookings, payments, expenses, and reports from one intuitive dashboard.">
    <meta name="keywords" content="Library Software Registration, Library Sign Up, Seat Booking Software, Library Management Software Join">
    <meta name="author" content="TECHITO India Pvt. Ltd.">
    <meta property="og:title" content="Library Sign Up | Get Started with Libraro Today" />
@endsection

@section('content')
<div class="lb-split">
    <div class="lb-split-form">
        <div class="lb-auth-top">
            @include('lb.partials.logo', ['height' => 34])
            <div class="lb-auth-top-right">
                <a href="{{ route('login.library') }}" class="lb-back">Log in</a>
                @include('lb.partials.theme-toggle')
            </div>
        </div>

        <main class="lb-split-center" id="lb-main">
            <div class="lb-split-inner">
                <h1 class="lb-auth-title">Start for free.</h1>
                <p class="lb-auth-sub">Set up your library in 2 minutes. 7-day refund on any plan.</p>

                <form action="{{ route('library.store') }}" method="POST" class="lb-form lb-auth-form validateForm" enctype="multipart/form-data" autocomplete="on">
                    @csrf
                    <input type="hidden" name="referral_code" value="{{ old('referral_code', request('ref')) }}">
                    <input type="hidden" name="referral_type" value="link">

                    @include('lb.partials.session-alerts')

                    <div class="lb-field">
                        <label class="lb-label" for="library_name">Library name</label>
                        <div class="lb-input @error('library_name') is-invalid @enderror">
                            <x-lb-icon name="library-big" />
                            <input id="library_name" type="text" class="char-only" name="library_name" value="{{ old('library_name') }}" placeholder="e.g. Bright Study Point" autocomplete="organization" minlength="2" required>
                        </div>
                        @include('lb.partials.field-error', ['name' => 'library_name'])
                    </div>

                    <div class="lb-grid-2">
                        <div class="lb-field">
                            <label class="lb-label" for="library_mobile">WhatsApp number</label>
                            <div class="lb-input @error('library_mobile') is-invalid @enderror">
                                <x-lb-icon name="smartphone" />
                                <input id="library_mobile" type="tel" class="digit-only" name="library_mobile" data-maxlength="10" minlength="10" pattern="[0-9]{10}" title="Enter a valid 10-digit mobile number." value="{{ old('library_mobile') }}" placeholder="98765 43210" inputmode="numeric" autocomplete="tel-national" required>
                            </div>
                            @include('lb.partials.field-error', ['name' => 'library_mobile'])
                        </div>
                        <div class="lb-field">
                            <label class="lb-label" for="email">Email</label>
                            <div class="lb-input @error('email') is-invalid @enderror">
                                <x-lb-icon name="mail" />
                                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" inputmode="email" autocomplete="email" required>
                            </div>
                            @include('lb.partials.field-error', ['name' => 'email'])
                        </div>
                    </div>

                    <div class="lb-field">
                        <label class="lb-label" for="password">Create password</label>
                        <div class="lb-input @error('password') is-invalid @enderror">
                            <x-lb-icon name="lock" />
                            <input id="password" type="password" name="password" minlength="8" placeholder="At least 8 characters" autocomplete="new-password" required>
                            <button type="button" class="lb-eye" aria-label="Show password"><x-lb-icon name="eye" class="lb-i-eye" /><x-lb-icon name="eye-off" class="lb-i-eye-off" /></button>
                        </div>
                        <div class="lb-meter" data-lb-strength="password" aria-hidden="true">
                            <div class="lb-meter-bars"><span></span><span></span><span></span><span></span></div>
                            <span class="lb-meter-label"></span>
                        </div>
                        <span class="lb-hint">Use upper &amp; lower case, a number and a symbol.</span>
                        @include('lb.partials.field-error', ['name' => 'password'])
                    </div>

                    <div class="lb-field">
                        <label class="lb-check" for="terms">
                            <input type="checkbox" name="terms" id="terms" class="@error('terms') is-invalid @enderror" required>
                            <span>I agree to the <a href="{{ route('term-and-condition') }}" target="_blank" rel="noopener">Terms</a> and <a href="{{ route('privacy-policy') }}" target="_blank" rel="noopener">Privacy policy</a>.</span>
                        </label>
                        @include('lb.partials.field-error', ['name' => 'terms'])
                    </div>

                    <button type="submit" class="lb-btn lb-btn--lg lb-btn--block" data-loading-text="Creating account…">
                        <span class="lb-btn-label">Create account</span>
                        <x-lb-icon name="arrow-right" size="18" />
                    </button>
                </form>

                <p class="lb-auth-alt">Already have an account? <a href="{{ route('login.library') }}">Log in</a></p>
            </div>
        </main>

        <div class="lb-auth-foot">
            <span><x-lb-icon name="shield-check" />Your data is private and encrypted</span>
            <a href="tel:+918114479678">Need help? +91 81144 79678</a>
        </div>
    </div>

    <div class="lb-split-panel" aria-hidden="true">
        @php
            $lbSlides = [
                ['armchair', 'Seat booking', 'Every seat, live on one map.', 'Allot, extend and free seats in a tap. No more registers.', 'slide-library'],
                ['indian-rupee', 'Fee collection', 'Get paid on time, every month.', 'Dues reminders go out on WhatsApp by themselves.', 'slide-study'],
                ['qr-code', 'Attendance', 'Check-in with one QR scan.', 'Learners scan at the door. You see who is in, right away.', 'slide-learner'],
                ['chart-line', 'Reports', 'Know your numbers in seconds.', 'Money in, seats filled and dues pending, ready to share.', 'hero-library'],
            ];
        @endphp
        <div class="lb-slider" data-lb-slider data-seconds="5">
            @foreach($lbSlides as $k => $s)
                <div class="lb-slide {{ $k === 0 ? 'is-active' : '' }}">
                    <img src="{{ asset('public/img/site/' . $s[4] . '-1600.webp') }}" alt="" @if($k) loading="lazy" @endif decoding="async">
                </div>
            @endforeach
            <div class="lb-slider-shade"></div>
            <div class="lb-slider-top">
                <div class="lb-slider-chip"><i><x-lb-icon name="star" /></i>Built for study libraries in India</div>
                <div class="lb-slider-count">01 / {{ sprintf('%02d', count($lbSlides)) }}</div>
            </div>
            <div class="lb-slider-bottom">
                @foreach($lbSlides as $k => $s)
                    <div class="lb-slide-text {{ $k === 0 ? 'is-active' : '' }}">
                        <div class="lb-slide-eyebrow"><x-lb-icon :name="$s[0]" />{{ $s[1] }}</div>
                        <h2>{{ $s[2] }}</h2>
                        <p>{{ $s[3] }}</p>
                    </div>
                @endforeach
                <div class="lb-slider-ctrl">
                    <div class="lb-slider-bars">
                        @foreach($lbSlides as $k => $s)
                            <button type="button" tabindex="-1" aria-label="{{ $s[1] }}"><span><i></i></span></button>
                        @endforeach
                    </div>
                    <div class="lb-slider-arrows">
                        <button type="button" data-prev tabindex="-1" aria-label="Previous"><x-lb-icon name="chevron-left" /></button>
                        <button type="button" data-next tabindex="-1" aria-label="Next"><x-lb-icon name="chevron-right" /></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
