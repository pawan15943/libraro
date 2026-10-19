{{--
    Shared login screen (brand panel + card). Used by:
    auth/login_admin (library owner & staff), auth/login (super admin), auth/login_learner.
    Variables: $userType, $role, $roleIcon, $cardTitle, $cardSub, $brandTitle, $brandLead, $points[], $badges[],
               $idField ('email'|'learner_no'), $showForgot, $showRegister, $helpText
--}}
<div class="lb-login">
    <aside class="lb-login-brand" aria-label="About Libraro">
        @include('lb.partials.logo', ['height' => 30])
        <div>
            <h1>{{ $brandTitle }}</h1>
            <p class="lb-login-lead">{{ $brandLead }}</p>
            <ul class="lb-login-points">
                @foreach($points as [$icon, $text])
                    <li><span class="lb-dot"><x-lb-icon :name="$icon" /></span>{{ $text }}</li>
                @endforeach
            </ul>
        </div>
        <div class="lb-login-badges">
            @foreach($badges as [$big, $small])
                <div><strong>{{ $big }}</strong><span>{{ $small }}</span></div>
            @endforeach
        </div>
    </aside>

    <main class="lb-login-main" id="lb-main">
        @include('lb.partials.logo', ['class' => 'lb-mobile-logo', 'height' => 26])
        @include('lb.partials.theme-toggle')

        <div class="lb-login-card-wrap">
            <div class="lb-login-card">
                <span class="lb-login-role"><x-lb-icon :name="$roleIcon" />{{ $role }}</span>
                <h2>{{ $cardTitle }}</h2>
                <p class="lb-card-sub">{{ $cardSub }}</p>

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
            </div>
            <p class="lb-login-help">{!! $helpText !!}</p>
        </div>
    </main>
</div>
