{{--
    Shared login screen — same layout as the Register page (form left, photo slider right).
    Used by: auth/login_admin (library owner & staff), auth/login (super admin), auth/login_learner.
    Variables: $userType, $role, $roleIcon, $cardTitle, $cardSub, $brandTitle, $brandLead, $points[], $badges[],
               $idField ('email'|'learner_no'), $showForgot, $showRegister, $helpText, $slides (optional)
--}}
@php
    $lbSlides = $slides ?? [
        ['armchair', 'Seat booking', 'Every seat, live on one map.', 'Allot, extend and free seats in a tap. No more registers.', 'slide-library'],
        ['indian-rupee', 'Fee collection', 'Get paid on time, every month.', 'Dues reminders go out on WhatsApp by themselves.', 'slide-study'],
        ['qr-code', 'Attendance', 'Check-in with one QR scan.', 'Learners scan at the door. You see who is in, right away.', 'slide-learner'],
        ['chart-line', 'Reports', 'Know your numbers in seconds.', 'Money in, seats filled and dues pending, ready to share.', 'hero-library'],
    ];
@endphp
<div class="lb-split">
    <div class="lb-split-form">
        <div class="lb-auth-top">
            @include('lb.partials.logo', ['height' => 28])
            <div class="lb-auth-top-right">
                @if(!empty($showRegister))
                    <a href="{{ route('register') }}" class="lb-back">Register</a>
                @endif
                @include('lb.partials.theme-toggle')
            </div>
        </div>

        <main class="lb-split-center" id="lb-main">
            <div class="lb-split-inner">
                <span class="lb-login-role"><x-lb-icon :name="$roleIcon" />{{ $role }}</span>
                <h1 class="lb-auth-title">{{ $cardTitle }}</h1>
                <p class="lb-auth-sub">{{ $cardSub }}</p>

                <form method="POST" action="{{ route('login.store') }}" class="lb-form lb-auth-form validateForm" autocomplete="on">
                    @csrf
                    <input type="hidden" name="user_type" value="{{ $userType }}">

                    @include('lb.partials.session-alerts')

                    @if($idField === 'learner_no')
                        <div class="lb-field">
                            <label class="lb-label" for="learner_no">Learner No.</label>
                            <div class="lb-input @error('learner_no') is-invalid @enderror">
                                <x-lb-icon name="id-card" />
                                <input id="learner_no" type="text" name="learner_no" value="{{ old('learner_no') }}" placeholder="Your learner number" autocomplete="username" autocapitalize="characters" required autofocus>
                            </div>
                            @include('lb.partials.field-error', ['name' => 'learner_no'])
                        </div>
                    @else
                        <div class="lb-field">
                            <label class="lb-label" for="email">Email address</label>
                            <div class="lb-input @error('email') is-invalid @enderror">
                                <x-lb-icon name="mail" />
                                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" inputmode="email" required autofocus>
                            </div>
                            @include('lb.partials.field-error', ['name' => 'email'])
                        </div>
                    @endif

                    <div class="lb-field">
                        <label class="lb-label" for="password">
                            <span>Password</span>
                            @if(!empty($showForgot))
                                <a href="{{ route('password.request.library') }}">Forgot password?</a>
                            @endif
                        </label>
                        <div class="lb-input @error('password') is-invalid @enderror">
                            <x-lb-icon name="lock" />
                            <input id="password" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                            <button type="button" class="lb-eye" aria-label="Show password"><x-lb-icon name="eye" class="lb-i-eye" /><x-lb-icon name="eye-off" class="lb-i-eye-off" /></button>
                        </div>
                        @include('lb.partials.field-error', ['name' => 'password'])
                    </div>

                    <div class="lb-row-between">
                        <label class="lb-check" for="remember">
                            <input type="checkbox" name="remember" id="remember" class="no-validate" {{ old('remember') ? 'checked' : '' }}>
                            <span>Keep me signed in</span>
                        </label>
                        <span class="lb-muted-note"><x-lb-icon name="shield-check" />Secure login</span>
                    </div>

                    <button type="submit" class="lb-btn lb-btn--lg lb-btn--block" data-loading-text="Logging in…">
                        <span class="lb-btn-label">Log in</span>
                        <x-lb-icon name="arrow-right" size="18" />
                    </button>
                </form>

                @if(!empty($showRegister))
                    <p class="lb-auth-alt">New to Libraro? <a href="{{ route('register') }}">Register your library</a></p>
                @endif
                <p class="lb-login-help">{!! $helpText !!}</p>
            </div>
        </main>

        <div class="lb-auth-foot">
            <span><x-lb-icon name="shield-check" />Your data is private and encrypted</span>
            <a href="tel:+918114479678">Need help? +91 81144 79678</a>
        </div>
    </div>

    <div class="lb-split-panel" aria-hidden="true">
        <div class="lb-slider" data-lb-slider data-seconds="5">
            @foreach($lbSlides as $k => $s)
                <div class="lb-slide {{ $k === 0 ? 'is-active' : '' }}">
                    <img src="{{ asset('public/img/site/' . $s[4] . '-1600.webp') }}" alt="" @if($k) loading="lazy" @endif decoding="async">
                </div>
            @endforeach
            <div class="lb-slider-shade"></div>
            <div class="lb-slider-top">
                <div class="lb-slider-chip"><i><x-lb-icon name="star" /></i>{{ $chip ?? 'Built for study libraries in India' }}</div>
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
