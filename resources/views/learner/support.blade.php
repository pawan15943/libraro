@extends('layouts.learner')

@section('title', 'Contact library')

@section('content')
@php
    $lib = $lr['library'] ?? [];
    $phones = array_values(array_unique(array_filter([$lib['phone'] ?? null, $lib['phone2'] ?? null])));
    $digits = fn ($n) => preg_replace('/\D+/', '', (string) $n);
    $wa = $phones ? (strlen($digits($phones[0])) === 10 ? '91' . $digits($phones[0]) : $digits($phones[0])) : null;
    $mapUrl = !empty($lib['map']) && str_starts_with($lib['map'], 'http')
        ? $lib['map']
        : (!empty($lib['address']) ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($lib['address']) : null);
@endphp

<div class="lr-head">
    <div>
        <h1>Contact library</h1>
        <p>{{ $lib['name'] ?? 'Your library' }}</p>
    </div>
</div>

<div class="lr-grid is-main">
    <div class="lr-stack">
        @foreach ($phones as $ph)
            <a href="tel:{{ $digits($ph) }}" class="lr-contact">
                <span class="lr-row-ic ok"><x-lb-icon name="phone" /></span>
                <div class="lr-row-main"><b>{{ $ph }}</b><p>Tap to call</p></div>
                <x-lb-icon name="chevron-right" style="color:var(--lp-mut3)" />
            </a>
        @endforeach

        @if ($wa)
            <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="lr-contact">
                <span class="lr-row-ic ok"><x-lb-icon name="message-circle" /></span>
                <div class="lr-row-main"><b>WhatsApp</b><p>Send a message</p></div>
                <x-lb-icon name="chevron-right" style="color:var(--lp-mut3)" />
            </a>
        @endif

        @if (!empty($lib['email']))
            <a href="mailto:{{ $lib['email'] }}" class="lr-contact">
                <span class="lr-row-ic acc"><x-lb-icon name="mail" /></span>
                <div class="lr-row-main"><b>{{ $lib['email'] }}</b><p>Write an email</p></div>
                <x-lb-icon name="chevron-right" style="color:var(--lp-mut3)" />
            </a>
        @endif

        @if (!empty($lib['address']))
            <a href="{{ $mapUrl }}" target="_blank" rel="noopener" class="lr-contact">
                <span class="lr-row-ic warn"><x-lb-icon name="map-pin" /></span>
                <div class="lr-row-main"><b>Address</b><p>{{ $lib['address'] }}</p></div>
                <x-lb-icon name="external-link" style="color:var(--lp-mut3)" />
            </a>
        @endif

        @if (!$phones && empty($lib['email']) && empty($lib['address']))
            <div class="lr-card">
                <div class="lr-empty">
                    <div class="lr-empty-ic"><x-lb-icon name="headset" /></div>
                    <b>No contact details yet</b>
                    <p>Please ask at the library desk.</p>
                </div>
            </div>
        @endif
    </div>

    <div class="lr-stack">
        <div class="lr-card">
            <div class="lr-card-title"><h2><x-lb-icon name="message-square-text" /> Other ways</h2></div>
            <div class="lr-rows">
                <a href="{{ route('learner.request') }}" class="lr-row">
                    <span class="lr-row-ic"><x-lb-icon name="send" /></span>
                    <div class="lr-row-main"><b>Send a request</b><p>Renew, change plan or seat</p></div>
                    <x-lb-icon name="chevron-right" style="color:var(--lp-mut3)" />
                </a>
                <a href="{{ route('complaints') }}" class="lr-row">
                    <span class="lr-row-ic"><x-lb-icon name="circle-alert" /></span>
                    <div class="lr-row-main"><b>Report a problem</b><p>Track the library's reply</p></div>
                    <x-lb-icon name="chevron-right" style="color:var(--lp-mut3)" />
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
