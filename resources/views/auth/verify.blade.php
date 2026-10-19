@extends('lb.layouts.auth')

@section('seo')
    <title>Libraro : Library Management Software</title>
    <meta name="robots" content="noindex, nofollow">
@endsection

@push('scripts')
<script>
    /* The email is only flashed for one request; keep it for retries after a wrong OTP. */
    (function () {
        var f = document.getElementById('otp_email');
        if (!f) return;
        try {
            if (f.value) sessionStorage.setItem('lb-otp-email', f.value);
            else if (sessionStorage.getItem('lb-otp-email')) {
                f.value = sessionStorage.getItem('lb-otp-email');
                var l = document.getElementById('otp_email_label');
                if (l) l.textContent = f.value;
            }
        } catch (e) {}
    })();
</script>
@endpush

@php($lbOtpEmail = session('library_email') ?? session('email'))

@section('content')
<div class="lb-center">
    @include('lb.partials.center-top', ['backUrl' => route('login.library'), 'backLabel' => 'Back'])

    <main class="lb-center-main" id="lb-main">
        <div class="lb-center-inner">
            <div class="lb-auth-icon"><x-lb-icon name="mail-check" /></div>
            <h1 class="lb-auth-title">Check your email.</h1>
            <p class="lb-auth-sub">
                We sent a 6-digit code to
                <strong id="otp_email_label">{{ $lbOtpEmail ?: 'your email' }}</strong>.
                Enter it below to verify your account.
            </p>

            <form method="POST" action="{{ route('verify.otp') }}" class="lb-form lb-auth-form validateForm" data-autosubmit="1">
                @csrf
                <input type="hidden" name="email" id="otp_email" value="{{ $lbOtpEmail }}">
                <input type="hidden" name="user_type" value="admin">
                <input type="hidden" name="email_otp" id="email_otp" value="{{ old('email_otp') }}">

                @include('lb.partials.session-alerts')
                @error('email')
                    <div class="lb-alert lb-alert--danger" role="alert"><x-lb-icon name="circle-alert" /><span>{{ $message === 'The email field is required.' ? 'Your session expired. Please log in again to get a new code.' : $message }}</span></div>
                @enderror

                <div class="lb-field">
                    <span class="lb-label" id="otp-label">Enter code</span>
                    <div class="lb-otp @error('email_otp') is-invalid @enderror" data-lb-otp="email_otp" role="group" aria-labelledby="otp-label">
                        @for($i = 0; $i < 6; $i++)
                            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}" aria-label="Digit {{ $i + 1 }}" class="no-validate">
                        @endfor
                    </div>
                    @include('lb.partials.field-error', ['name' => 'email_otp'])
                </div>

                <button type="submit" class="lb-btn lb-btn--lg lb-btn--block" data-loading-text="Verifying…">
                    <span class="lb-btn-label">Verify</span>
                    <x-lb-icon name="check" size="18" />
                </button>
            </form>

            <div class="lb-help-box">
                <x-lb-icon name="info" />
                <div>Didn't get the email? Check your spam folder, or call us at <a href="tel:+918114479678">+91 81144 79678</a>, <a href="tel:+917737918848">+91 77379 18848</a> or <a href="tel:+918386007688">+91 83860 07688</a> and we'll help you finish registration.</div>
            </div>
        </div>
    </main>

    @include('lb.partials.center-foot', ['note' => 'Never share this code with anyone'])
</div>
@endsection
