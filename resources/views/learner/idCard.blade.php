@extends('layouts.learner')

@section('title', 'ID card')

@push('lr-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-learner-scan.css') }}?v={{ @filemtime(public_path('css/lb-learner-scan.css')) }}">
@endpush

@section('content')
@php
    $p = $lr['plan'] ?? null;
    [$stateText, $stateTone] = \App\Services\LearnerPortalService::stateLabel($p);
@endphp

<div class="lr-head">
    <div>
        <h1>My ID card</h1>
        <p>Show this at the library desk when asked.</p>
    </div>
</div>

<div class="lr-idcard">
    <div class="lr-idcard-top">
        <span class="lr-logo">{{ mb_strtoupper(mb_substr($lr['library']['name'] ?? 'L', 0, 1)) }}</span>
        <div style="min-width:0">
            <b>{{ $lr['library']['name'] ?? 'Library' }}</b>
            <small>Student ID card</small>
        </div>
    </div>
    <div class="lr-idcard-body">
        <div class="lr-idcard-photo">{{ $lr['initials'] ?? 'L' }}</div>
        <div class="lr-idcard-name">{{ $lr['name'] ?? '' }}</div>
        <span class="lr-idcard-no">ID {{ $lr['learner_no'] ?? '—' }}</span>

        <div class="lr-idcard-grid">
            <div><small>Seat</small><b>{{ $p ? ($p['seat'] ?: 'General') : '—' }}</b></div>
            <div><small>Valid till</small><b>{{ $p ? $p['end']->format('j M Y') : '—' }}</b></div>
            <div><small>Plan</small><b>{{ $p['name'] ?? '—' }}</b></div>
            <div><small>Shift</small><b>{{ $p['type'] ?? '—' }}</b></div>
            @if ($p && $p['time'])
                <div style="grid-column:1 / -1"><small>Time</small><b>{{ $p['time'] }}</b></div>
            @endif
        </div>

        @if ($p && $p['state'] !== 'expired' && !empty($qrPayload))
            <div class="lr-idcard-qr">
                {!! QrCode::size(224)->margin(0)->errorCorrection('M')->generate($qrPayload) !!}
                <div>
                    <b>Attendance QR</b>
                    <small>Show this at the library desk scanner to mark IN / OUT.</small>
                </div>
            </div>
        @endif
    </div>
</div>

<div style="max-width:380px;margin:16px auto 0">
    @unless (\App\Support\FeatureToggles::routeHidden('learner.attendance.scan'))
        <a href="{{ route('learner.attendance.scan') }}" class="lr-btn is-ghost is-block" style="margin-bottom:10px"><x-lb-icon name="scan-line" /> Scan library QR instead</a>
    @endunless
    @if ($p && $p['state'] === 'expired')
        <div class="lr-alert bad" style="margin-bottom:12px">
            <span class="lr-alert-ic"><x-lb-icon name="circle-alert" /></span>
            <div><b>This card is not valid now</b><p>Your plan has ended. Renew it to use the card again.</p></div>
        </div>
    @endif

    @if ($lr['id_card_url'] ?? null)
        <a href="{{ $lr['id_card_url'] }}" target="_blank" rel="noopener" class="lr-btn is-block"><x-lb-icon name="download" /> Download PDF</a>
    @else
        <p class="lr-hint" style="text-align:center">The PDF card is ready once you have an active plan.</p>
    @endif
</div>
@endsection
