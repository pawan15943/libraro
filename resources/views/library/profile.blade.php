@extends('layouts.library')

@section('title', 'Library Profile')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
@endpush

@section('content')

@php
    $libName = $library->library_name ?? 'Your library';
    $libInitials = collect(explode(' ', trim((string) $libName)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: 'L';
    $onboarding = $iscomp == false && !$is_expire;
@endphp

<div class="lpg lpg--narrow lpf">
    @if($onboarding)
        <ol class="lpg-steps" aria-label="Setup steps">
            <li class="is-done"><a href="{{ $checkSub ? '#' : route('subscriptions.choosePlan') }}"><i>1</i>Plan</a></li>
            <li class="is-on"><a href="{{ $ispaid ? route('branch.create') : '#' }}"><i>2</i>Profile &amp; branch</a></li>
            <li><a href="{{ ($checkSub && $ispaid && $isProfile) ? route('library.master') : '#' }}"><i>3</i>Configure library</a></li>
        </ol>
    @endif

    <header class="lpg-head">
        <div class="lpg-head-t">
            @unless($onboarding)
                <div class="lpg-crumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><span>Profile</span></div>
            @endunless
            <h1>{{ $onboarding ? 'A few details to make it yours' : 'Library profile' }}</h1>
            <p>Your registered library details and the owner name that appears on receipts and invoices.</p>
        </div>
    </header>

    {{-- identity --}}
    <section class="lpg-card lpf-id">
        <div class="lpf-id-top">
            <span class="lpf-logo">{{ $libInitials }}<i><x-lb-icon name="badge-check" /></i></span>
            <div class="lpf-id-t">
                <h2>{{ $libName }}</h2>
                <div class="lpf-id-tags">
                    @if(!empty($library->library_no))
                        <span class="lpg-tag is-acc"><x-lb-icon name="hash" />{{ $library->library_no }}</span>
                    @endif
                    <span class="lpg-tag is-ok"><x-lb-icon name="circle-check" />Verified</span>
                </div>
            </div>
        </div>

        <dl class="lpf-facts">
            <div>
                <dt><x-lb-icon name="mail" />Email</dt>
                <dd>{{ $library->email ?: '—' }}</dd>
            </div>
            <div>
                <dt><x-lb-icon name="message-circle" />WhatsApp</dt>
                <dd>{{ $library->library_mobile ? '+91 ' . $library->library_mobile : '—' }}</dd>
            </div>
            <div>
                <dt><x-lb-icon name="user-round" />Owner</dt>
                <dd>{{ $library->library_owner ?: 'Not set yet' }}</dd>
            </div>
        </dl>

        <p class="lpf-lock"><x-lb-icon name="lock" />Name, email and WhatsApp number are verified and locked. To change them, contact Libraro support.</p>
    </section>

    {{-- editable --}}
    <form action="{{ route('library.profile.update') }}" class="validateForm profile" method="POST" novalidate>
        @csrf
        <input type="hidden" name="library_name" value="{{ $library->library_name }}">
        <input type="hidden" name="email" value="{{ $library->email }}">
        <input type="hidden" name="library_mobile" value="{{ $library->library_mobile }}">

        <section class="lef-sec lpf-form">
            <div class="lef-sec-h">
                <span class="lef-sec-ic is-violet"><x-lb-icon name="user-cog" /></span>
                <div>
                    <h4>Owner details</h4>
                    <p>The primary contact person for this library.</p>
                </div>
            </div>

            <div class="lef-f @error('library_owner') is-bad @enderror">
                <label for="library_owner">Owner full name <i>*</i></label>
                <div class="lef-in">
                    <x-lb-icon name="user-round" />
                    <input type="text" class="char-only @error('library_owner') is-invalid @enderror" name="library_owner" id="library_owner"
                        value="{{ old('library_owner', $library->library_owner ?? '') }}" placeholder="e.g. Rahul Sharma" autocomplete="name" required maxlength="255">
                </div>
                @error('library_owner')
                    <p class="lef-err">{{ $message }}</p>
                @else
                    <p class="lef-hint">Printed on receipts, invoices and messages sent to learners.</p>
                @enderror
            </div>
        </section>

        <div class="lpg-save">
            <p>{{ $onboarding ? 'Next: configure seats, shifts and fees.' : 'Only the owner name can be changed here.' }}</p>
            @unless($onboarding)
                <a href="{{ route('library.master') }}" class="lp-btn lp-btn--ghost">Cancel</a>
            @endunless
            <button type="submit" class="lp-btn"><x-lb-icon name="circle-check" />{{ $onboarding ? 'Save & continue' : 'Save' }}</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var f = document.querySelector('.lpf form.profile');
        if (!f) return;
        f.addEventListener('submit', function (e) {
            var inp = document.getElementById('library_owner');
            if (!inp.value.trim()) {
                e.preventDefault();
                inp.closest('.lef-f').classList.add('is-bad');
                inp.focus();
                return;
            }
            f.querySelector('button[type="submit"]').classList.add('is-busy');
        });
        document.getElementById('library_owner').addEventListener('input', function () {
            this.closest('.lef-f').classList.remove('is-bad');
        });
    });
</script>

@include('library.script')
@endsection
