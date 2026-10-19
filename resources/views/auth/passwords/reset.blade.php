@extends('lb.layouts.auth')

@section('seo')
    <title>Reset your Password</title>
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
<div class="lb-center">
    @include('lb.partials.center-top', ['backUrl' => route('login.library'), 'backLabel' => 'Back to log in'])

    <main class="lb-center-main" id="lb-main">
        <div class="lb-center-inner">
            <div class="lb-steps" aria-hidden="true"><span class="is-on"></span><span class="is-on"></span><span class="is-on"></span></div>
            <div class="lb-auth-icon"><x-lb-icon name="lock-keyhole" /></div>
            <h1 class="lb-auth-title">Set a new password.</h1>
            <p class="lb-auth-sub">Pick something you will remember. At least 8 characters.</p>

            <form action="{{ route('password.update.library') }}" method="POST" class="lb-form lb-auth-form">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                @include('lb.partials.session-alerts')
                @error('email')
                    <div class="lb-alert lb-alert--danger" role="alert"><x-lb-icon name="circle-alert" /><span>{{ $message }}</span></div>
                @enderror
                @error('token')
                    <div class="lb-alert lb-alert--danger" role="alert"><x-lb-icon name="circle-alert" /><span>{{ $message }}</span></div>
                @enderror

                <div class="lb-field">
                    <label class="lb-label" for="password">New password</label>
                    <div class="lb-input @error('password') is-invalid @enderror">
                        <x-lb-icon name="lock" />
                        <input id="password" type="password" name="password" minlength="8" placeholder="At least 8 characters" autocomplete="new-password" required autofocus>
                        <button type="button" class="lb-eye" aria-label="Show password"><x-lb-icon name="eye" class="lb-i-eye" /><x-lb-icon name="eye-off" class="lb-i-eye-off" /></button>
                    </div>
                    <div class="lb-meter" data-lb-strength="password" aria-hidden="true">
                        <div class="lb-meter-bars"><span></span><span></span><span></span><span></span></div>
                        <span class="lb-meter-label"></span>
                    </div>
                    @include('lb.partials.field-error', ['name' => 'password'])
                </div>

                <div class="lb-field">
                    <label class="lb-label" for="password-confirm">Confirm new password</label>
                    <div class="lb-input">
                        <x-lb-icon name="lock" />
                        <input id="password-confirm" type="password" name="password_confirmation" placeholder="Type it again" autocomplete="new-password" data-lb-match="password" required>
                        <button type="button" class="lb-eye" aria-label="Show password"><x-lb-icon name="eye" class="lb-i-eye" /><x-lb-icon name="eye-off" class="lb-i-eye-off" /></button>
                    </div>
                </div>

                <button type="submit" class="lb-btn lb-btn--lg lb-btn--block" data-loading-text="Saving…">
                    <span class="lb-btn-label">Save new password</span>
                    <x-lb-icon name="check" size="18" />
                </button>
            </form>
        </div>
    </main>

    @include('lb.partials.center-foot')
</div>
@endsection
