@extends('layouts.library')

@section('title', 'Change password')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-staff.css') }}?v={{ @filemtime(public_path('css/lb-staff.css')) }}">
@endpush

@section('content')
@php
    $cpUser = getAuthenticatedUser();
    $cpName = $cpUser->library_name ?? ($cpUser->name ?? 'Your account');
    $cpEmail = $cpUser->email ?? null;
@endphp

<div class="lpg lpg--narrow lcp">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><span>Change password</span></div>
            <h1>Change password</h1>
            <p>Pick a new password you haven't used before. You stay signed in on this device.</p>
        </div>
    </header>

    <form action="{{ route('change-password') }}" method="POST" id="cpForm" novalidate>
        @csrf
        <section class="lef-sec">
            <div class="lef-sec-h">
                <span class="lef-sec-ic is-blue"><x-lb-icon name="lock-keyhole" /></span>
                <div><h4>{{ $cpName }}</h4>@if($cpEmail)<p>Signed in as {{ $cpEmail }}</p>@endif</div>
            </div>

            <div class="lef-grid">
                <div class="lef-f is-full @error('current_password') is-bad @enderror">
                    <label for="cp_cur">Current password <i>*</i></label>
                    <div class="lef-in"><x-lb-icon name="key-round" />
                        <input type="password" name="current_password" id="cp_cur" autocomplete="current-password" placeholder="The password you use now" required>
                        <button type="button" class="lst-eye" data-eye="cp_cur" aria-label="Show password"><x-lb-icon name="eye" /></button>
                    </div>
                    @error('current_password')<p class="lef-err">{{ $message }}</p>@enderror
                </div>

                <div class="lef-f is-full @error('new_password') is-bad @enderror">
                    <label for="cp_new">New password <i>*</i></label>
                    <div class="lef-in"><x-lb-icon name="lock" />
                        <input type="password" name="new_password" id="cp_new" autocomplete="new-password" placeholder="At least 8 characters" minlength="8" required>
                        <button type="button" class="lst-eye" data-eye="cp_new" aria-label="Show password"><x-lb-icon name="eye" /></button>
                    </div>
                    <div class="lcp-meter" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
                    <p class="lcp-hint" id="cpStrength" aria-live="polite">Use 8 or more characters. Mixing letters, numbers and a symbol makes it stronger.</p>
                    @error('new_password')<p class="lef-err">{{ $message }}</p>@enderror
                </div>

                <div class="lef-f is-full" id="cpConfirmField">
                    <label for="cp_new2">Type the new password again <i>*</i></label>
                    <div class="lef-in"><x-lb-icon name="lock" />
                        <input type="password" name="new_password_confirmation" id="cp_new2" autocomplete="new-password" placeholder="Same as above" required>
                        <button type="button" class="lst-eye" data-eye="cp_new2" aria-label="Show password"><x-lb-icon name="eye" /></button>
                    </div>
                    <p class="lcp-hint" id="cpMatch" aria-live="polite" hidden></p>
                </div>
            </div>
        </section>

        <div class="lpg-save">
            <p>Next time you sign in, use the new password.</p>
            <a href="{{ route('library.home') }}" class="lp-btn lp-btn--ghost">Cancel</a>
            <button type="submit" class="lp-btn" id="cpSave"><x-lb-icon name="circle-check" />Update password</button>
        </div>
    </form>
</div>

<style>
    body.lp .lcp-meter { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; margin-top: 8px; }
    body.lp .lcp-meter i { height: 4px; border-radius: 4px; background: var(--lp-soft2); transition: background .2s; }
    body.lp .lcp[data-s="1"] .lcp-meter i:nth-child(-n+1) { background: #de3b3d; }
    body.lp .lcp[data-s="2"] .lcp-meter i:nth-child(-n+2) { background: #eb881f; }
    body.lp .lcp[data-s="3"] .lcp-meter i:nth-child(-n+3) { background: #2fa37a; }
    body.lp .lcp[data-s="4"] .lcp-meter i { background: #0f8a4f; }
    body.lp .lcp-hint { margin: 6px 2px 0; font-size: 12.5px; color: var(--lp-mut2) !important; }
    body.lp .lcp-hint.is-ok { color: var(--lp-ok-ink) !important; font-weight: 600; }
    body.lp .lcp-hint.is-bad { color: var(--lp-bad-ink) !important; font-weight: 600; }
    body.lp .lcp .lef-err { margin: 6px 2px 0; }
</style>

<script>
(function () {
    var root = document.querySelector('.lcp'), form = document.getElementById('cpForm');
    var cur = document.getElementById('cp_cur'), pw = document.getElementById('cp_new'), pw2 = document.getElementById('cp_new2');
    var strength = document.getElementById('cpStrength'), match = document.getElementById('cpMatch');
    var words = ['', 'Weak', 'Okay', 'Good', 'Strong'];

    document.querySelectorAll('.lcp .lst-eye').forEach(function (b) {
        b.addEventListener('click', function () {
            var inp = document.getElementById(b.dataset.eye), show = inp.type === 'password';
            inp.type = show ? 'text' : 'password';
            b.classList.toggle('is-on', show);
            b.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    });

    function score(v) {
        if (!v) return 0;
        var s = v.length >= 8 ? 1 : 0;
        if (v.length >= 12) s++;
        if (/[a-z]/i.test(v) && /\d/.test(v)) s++;
        if (/[^a-z0-9]/i.test(v) || (/[a-z]/.test(v) && /[A-Z]/.test(v))) s++;
        return v.length < 8 ? 1 : Math.max(1, Math.min(4, s));
    }
    function checkMatch() {
        if (!pw2.value) { match.hidden = true; return true; }
        var ok = pw.value === pw2.value;
        match.hidden = false;
        match.textContent = ok ? 'Passwords match' : "Doesn't match the new password";
        match.className = 'lcp-hint ' + (ok ? 'is-ok' : 'is-bad');
        return ok;
    }
    pw.addEventListener('input', function () {
        var s = score(pw.value);
        root.dataset.s = s;
        strength.textContent = !pw.value ? 'Use 8 or more characters. Mixing letters, numbers and a symbol makes it stronger.'
            : (pw.value.length < 8 ? (8 - pw.value.length) + ' more ' + (8 - pw.value.length === 1 ? 'character' : 'characters') + ' needed' : words[s] + ' password');
        strength.className = 'lcp-hint' + (pw.value && pw.value.length < 8 ? ' is-bad' : (s >= 3 ? ' is-ok' : ''));
        pw.closest('.lef-f').classList.remove('is-bad');
        checkMatch();
    });
    pw2.addEventListener('input', function () { pw2.closest('.lef-f').classList.remove('is-bad'); checkMatch(); });
    cur.addEventListener('input', function () { cur.closest('.lef-f').classList.remove('is-bad'); });

    form.addEventListener('submit', function (e) {
        var bad = null;
        if (!cur.value) { cur.closest('.lef-f').classList.add('is-bad'); bad = bad || cur; }
        if (pw.value.length < 8) { pw.closest('.lef-f').classList.add('is-bad'); pw.dispatchEvent(new Event('input')); bad = bad || pw; }
        if (!pw2.value || !checkMatch()) { pw2.closest('.lef-f').classList.add('is-bad'); if (!pw2.value) { match.hidden = false; match.textContent = 'Type the new password again'; match.className = 'lcp-hint is-bad'; } bad = bad || pw2; }
        if (bad) { e.preventDefault(); bad.focus(); return; }
        document.getElementById('cpSave').disabled = true;
    });
})();
</script>
@endsection
