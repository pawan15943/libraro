@extends('lb.layouts.auth')

@section('seo')
    <title>Forgot Password</title>
    <meta name="robots" content="noindex, follow">
@endsection

@php($lbSent = session('status'))

@section('content')
<div class="lb-center">
    @include('lb.partials.center-top', ['backUrl' => route('login.library'), 'backLabel' => 'Back to log in'])

    <main class="lb-center-main" id="lb-main">
        <div class="lb-center-inner">
            <div class="lb-steps" aria-hidden="true">
                <span class="is-on"></span><span class="{{ $lbSent ? 'is-on' : '' }}"></span><span></span>
            </div>

            @if($lbSent)
                <div class="lb-auth-icon"><x-lb-icon name="mail-check" /></div>
                <h1 class="lb-auth-title">Check your inbox.</h1>
                <p class="lb-auth-sub">{{ $lbSent }}</p>
                <p class="lb-auth-sub">Open the email from Libraro and tap the reset link. It may take a minute, so check your spam folder too.</p>
                <div class="lb-auth-form">
                    <a href="{{ route('login.library') }}" class="lb-btn lb-btn--lg lb-btn--block">Back to log in <x-lb-icon name="arrow-right" size="18" /></a>
                </div>
                <p class="lb-auth-alt">Didn't get it? <a href="{{ route('password.request.library') }}">Send again</a></p>
            @else
                <div class="lb-auth-icon"><x-lb-icon name="key-round" /></div>
                <h1 class="lb-auth-title">Forgot your password?</h1>
                <p class="lb-auth-sub">No worries. Enter your registered email and we'll send you a link to set a new one.</p>

                <form method="POST" action="{{ route('password.email') }}" class="lb-form lb-auth-form validateForm">
                    @csrf
                    @include('lb.partials.session-alerts')
                    <div class="lb-field">
                        <label class="lb-label" for="email">Email address</label>
                        <div class="lb-input @error('email') is-invalid @enderror">
                            <x-lb-icon name="mail" />
                            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" inputmode="email" required autofocus>
                        </div>
                        @include('lb.partials.field-error', ['name' => 'email'])
                    </div>
                    <button type="submit" class="lb-btn lb-btn--lg lb-btn--block" data-loading-text="Sending link…">
                        <span class="lb-btn-label">Send reset link</span>
                        <x-lb-icon name="send" size="18" />
                    </button>
                </form>
                <p class="lb-auth-alt">Remember it now? <a href="{{ route('login.library') }}">Log in</a></p>
            @endif
        </div>
    </main>

    @include('lb.partials.center-foot')
</div>
@endsection
