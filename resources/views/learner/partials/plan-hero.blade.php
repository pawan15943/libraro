{{-- Plan card: seat, plan, shift time, days left. Needs $lr (LearnerPortalService). --}}
@php
    $p = $lr['plan'] ?? null;
    [$stateText, $stateTone] = \App\Services\LearnerPortalService::stateLabel($p);
@endphp
<section class="lr-hero" data-state="{{ $p['state'] ?? 'none' }}">
    @if ($p)
        <div class="lr-hero-top">
            <div>
                <div class="lr-hero-k">{{ $p['seat'] ? 'Your seat' : 'Seat' }}</div>
                <div class="lr-hero-seat">{{ $p['seat'] ?: 'General' }} @unless($p['seat'])<small>any free seat</small>@endunless</div>
            </div>
            <span class="lr-pill">{{ $stateText }}</span>
        </div>
        <div class="lr-hero-plan">{{ $p['name'] }}@if($p['type']) · {{ $p['type'] }}@endif</div>
        @if ($p['time'])
            <div class="lr-hero-time"><x-lb-icon name="clock" /> {{ $p['time'] }}@if($p['hours']) <span>({{ $p['hours'] }} hrs)</span>@endif</div>
        @endif
        <div class="lr-bar" aria-hidden="true"><span style="width: {{ $p['state'] === 'expired' ? 100 : $p['progress'] }}%"></span></div>
        <div class="lr-hero-dates">
            <span>Started {{ $p['start']->format('j M Y') }}</span>
            <span>{{ $p['state'] === 'expired' ? 'Ended' : 'Ends' }} {{ $p['end']->format('j M Y') }}</span>
        </div>
    @else
        <div class="lr-hero-k">Plan</div>
        <div class="lr-hero-seat" style="font-size:26px">No active plan</div>
        <p style="opacity:.85;margin-top:6px;font-size:14px">Visit the library desk to book a seat.</p>
    @endif
</section>
