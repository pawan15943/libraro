@extends('layouts.learner')

@section('title', 'Attendance')

@push('lr-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-learner-attn.css') }}?v={{ @filemtime(public_path('css/lb-learner-attn.css')) }}">
@endpush

@section('content')
@php
    $m = $d['member'];
    $date = $d['date_obj'];
    $back = route('my-attendance', ['request_name' => $date->format('Y-m')]);
    $dayUrl = fn ($x) => route('learner.attendance.day', $x);
    $min = fn ($raw) => ($t = \Carbon\Carbon::parse($raw))->hour * 60 + $t->minute;
    $sessions = [];
    $open = null;
    foreach ($d['punches'] as $p) {
        $v = $min($p['raw_datetime']);
        if ($p['type'] === 'IN') { $open = $v; }
        elseif ($open !== null) { $sessions[] = ['from' => $open, 'to' => max($v, $open + 1), 'live' => false]; $open = null; }
    }
    if ($open !== null) {
        $sessions[] = $d['inside'] ? ['from' => $open, 'to' => max(now()->hour * 60 + now()->minute, $open + 1), 'live' => true] : ['from' => $open, 'to' => $open + 1, 'live' => false, 'open' => true];
    }
    $tone = $d['absent'] ? 'absent' : ($d['inside'] ? 'live' : 'full');
@endphp

<div class="lat lal">
    <header class="lat-head">
        <div class="lat-head-top">
            <a href="{{ $back }}" class="lat-back" title="Monthly calendar" aria-label="Monthly calendar"><x-lb-icon name="arrow-left" /></a>
            <div class="lat-crumb"><a href="{{ $back }}">Attendance</a><x-lb-icon name="chevron-right" /><span>{{ $date->format('d M') }}</span></div>
            @if($d['is_today'])<span class="lal-live"><i></i>Live today</span>@endif
        </div>
        <div class="lat-member">
            <span class="lat-ava">@if($m['photo'])<img src="{{ $m['photo'] }}" alt="{{ $m['name'] }}">@else{{ $m['initials'] }}@endif</span>
            <div class="lat-member-id">
                <h1>{{ $m['name'] }}</h1>
                <div class="lat-tags">
                    <span class="lat-uid">{{ $m['no'] }}</span>
                    <span class="lat-tag"><x-lb-icon name="armchair" />{{ $m['tag'] }}</span>
                    <span class="lat-tag"><x-lb-icon name="clock" />{{ $d['shift_text'] }} shift</span>
                </div>
            </div>
        </div>
        <div class="lal-switch">
            <a href="{{ $d['has_prev'] ? $dayUrl($d['prev']) : '#' }}" class="lat-nav {{ $d['has_prev'] ? '' : 'is-off' }}" @if(!$d['has_prev']) aria-disabled="true" tabindex="-1" @endif aria-label="Previous day"><x-lb-icon name="chevron-left" /></a>
            <label class="lal-date" title="Pick a date">
                <x-lb-icon name="calendar-days" />
                <span><b>{{ $date->format('D, d M Y') }}</b></span>
                <input type="date" id="lalDate" value="{{ $d['date'] }}" min="{{ $d['min'] }}" max="{{ $d['max'] }}" aria-label="Pick a date">
            </label>
            <a href="{{ $d['has_next'] ? $dayUrl($d['next']) : '#' }}" class="lat-nav {{ $d['has_next'] ? '' : 'is-off' }}" @if(!$d['has_next']) aria-disabled="true" tabindex="-1" @endif aria-label="Next day"><x-lb-icon name="chevron-right" /></a>
            @unless($d['is_today'])<a href="{{ $dayUrl($d['max']) }}" class="lal-today">Today</a>@endunless
        </div>
    </header>

    <div class="lal-cols">
        <div>
            <section class="lal-hero is-{{ $tone }}">
                <div class="lal-hero-top">
                    <span class="lal-hero-ic"><x-lb-icon name="{{ $d['absent'] ? 'calendar-x' : ($d['inside'] ? 'user-check' : 'circle-check') }}" /></span>
                    <div class="lal-hero-t"><span>{{ $d['status_sub'] }}</span><b>{{ $d['status'] }}</b></div>
                    <div class="lal-hero-d"><span>{{ $d['duration_sub'] }}</span><b>{{ $d['duration'] }}</b></div>
                </div>
                <div class="lal-track" aria-hidden="true">
                    <div class="lal-track-bar">
                        @foreach($sessions as $s)
                            <i class="{{ $s['live'] ? 'is-live' : '' }} {{ !empty($s['open']) ? 'is-open' : '' }}" style="left: {{ round($s['from'] / 1440 * 100, 2) }}%; width: {{ max(0.6, round(($s['to'] - $s['from']) / 1440 * 100, 2)) }}%"></i>
                        @endforeach
                    </div>
                    <div class="lal-track-ticks"><span>12a</span><span>6a</span><span>12p</span><span>6p</span><span>12a</span></div>
                </div>
            </section>

            <div class="lal-kpis">
                <div class="lal-kpi"><span><x-lb-icon name="log-in" />First in</span><b>{{ $d['in_text'] }}</b><small>{{ $d['has_in'] ? $d['in_loc'] : 'Not recorded' }}</small></div>
                <div class="lal-kpi {{ $d['missing_out'] ? 'is-warn' : '' }}"><span><x-lb-icon name="log-out" />Last out</span><b>{{ $d['out_text'] }}</b>
                    <small>@if($d['inside']) In library now @elseif($d['has_out']) {{ $d['out_loc'] }} @elseif($d['missing_out']) Checkout missing @else Not recorded @endif</small></div>
                <div class="lal-kpi"><span><x-lb-icon name="clock" />Shift</span><b>{{ $d['shift_text'] }}</b><small>{{ $d['shift_name'] }}</small></div>
                <div class="lal-kpi {{ $d['has_overtime'] ? 'is-bad' : '' }}"><span><x-lb-icon name="timer" />Overstay</span><b>{{ $d['has_overtime'] ? '+' . $d['overtime_text'] : 'None' }}</b><small>{{ $d['has_overtime'] ? 'Beyond shift' : 'Within shift' }}</small></div>
            </div>

            @if($d['has_overtime'])
                <div class="lal-alert"><x-lb-icon name="clock-alert" /><p>You stayed <b>{{ $d['duration'] }}</b> against a <b>{{ $d['shift_text'] }}</b> shift — <b>+{{ $d['overtime_text'] }}</b> extra.</p></div>
            @endif
        </div>

        <div>
            <section class="lat-card lal-tl">
                <div class="lal-act-h"><h2>Punch timeline</h2><span>{{ count($d['punches']) }} {{ count($d['punches']) === 1 ? 'event' : 'events' }}</span></div>
                @if(count($d['punches']))
                    <ol class="lal-events">
                        @foreach($d['punches'] as $p)
                            @php $isIn = $p['type'] === 'IN'; @endphp
                            @if(!empty($p['session_duration']))<li class="lal-gap"><x-lb-icon name="timer" />Stayed {{ $p['session_duration'] }}</li>@endif
                            <li class="lal-ev {{ $isIn ? 'is-in' : 'is-out' }}">
                                <span class="lal-ev-dot"><x-lb-icon name="{{ $isIn ? 'log-in' : 'log-out' }}" /></span>
                                <div class="lal-ev-t"><b>{{ $p['title'] }}</b><small><x-lb-icon name="map-pin" />{{ $p['source'] }}</small></div>
                                <time>{{ $p['time'] }}</time>
                            </li>
                        @endforeach
                        @if($d['inside'])
                            <li class="lal-ev is-now">
                                <span class="lal-ev-dot"><i></i></span>
                                <div class="lal-ev-t"><b>Studying now</b><small>In since {{ $d['in_text'] }} · {{ $d['duration'] }} so far</small></div>
                            </li>
                        @endif
                    </ol>
                @else
                    <div class="lal-none">
                        <span class="lal-none-ic"><x-lb-icon name="calendar-x" /></span>
                        <b>No punches on {{ $date->format('j M Y') }}</b>
                        <p>There is no entry or exit on this day, so it counts as absent. Think it is wrong? Tell the library desk.</p>
                    </div>
                @endif
            </section>
            <div class="lat-note"><x-lb-icon name="qr-code" /><p>Your punches come from scanning the library QR at the gate.</p></div>
        </div>
    </div>
</div>
@endsection

@push('lr-scripts')
<script>
(function () {
    var input = document.getElementById('lalDate');
    if (!input) return;
    input.addEventListener('change', function () {
        if (this.value) window.location.href = "{{ route('learner.attendance.day', ':date') }}".replace(':date', this.value);
    });
    input.closest('.lal-date').addEventListener('click', function (e) {
        if (e.target !== input && typeof input.showPicker === 'function') { try { input.showPicker(); } catch (err) {} }
    });
})();
</script>
@endpush
