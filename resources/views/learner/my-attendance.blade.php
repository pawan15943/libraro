@extends('layouts.learner')

@section('title', 'Attendance')

@push('lr-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-learner-attn.css') }}?v={{ @filemtime(public_path('css/lb-learner-attn.css')) }}">
@endpush

@section('content')
@php
    $m = $a['member'];
    $marked = $a['present'] + $a['half'] + $a['absent'];
    $rate = $marked > 0 ? (int) round(($a['present'] + $a['half'] * 0.5) / $marked * 100) : 0;
    $pct = fn ($n) => $marked > 0 ? round($n / $marked * 100, 2) : 0;
    $hrs = fn ($h) => $h !== null ? rtrim(rtrim(number_format($h, 1), '0'), '.') . 'h' : '';
    $monthUrl = fn ($d) => route('my-attendance', ['request_name' => $d->format('Y-m')]);
    $label = ['full' => 'Present', 'half' => 'Half day', 'absent' => 'Absent'];
    $today = $lr['today'] ?? ['state' => 'none'];
@endphp

<div class="lat">
    <header class="lat-head">
        <div class="lat-head-top">
            <a href="{{ route('learner.home') }}" class="lat-back" title="Dashboard" aria-label="Back to dashboard"><x-lb-icon name="arrow-left" /></a>
            <div class="lat-crumb">My library<x-lb-icon name="chevron-right" /><span>Attendance</span></div>
        </div>
        <div class="lat-member">
            <span class="lat-ava">@if($m['photo'])<img src="{{ $m['photo'] }}" alt="{{ $m['name'] }}">@else{{ $m['initials'] }}@endif</span>
            <div class="lat-member-id">
                <h1>{{ $m['name'] }}</h1>
                <div class="lat-tags">
                    <span class="lat-uid">{{ $m['no'] }}</span>
                    <span class="lat-tag"><x-lb-icon name="armchair" />{{ $m['tag'] }}</span>
                    <span class="lat-tag"><x-lb-icon name="building" />{{ $m['branch'] }}</span>
                </div>
            </div>
        </div>
        @if ($a['month']->isSameMonth(now()))
            <div class="lat-today-card {{ $today['state'] === 'in' ? 'is-in' : '' }}">
                <x-lb-icon name="{{ $today['state'] === 'none' ? 'fingerprint-pattern' : ($today['state'] === 'in' ? 'log-in' : 'circle-check') }}" />
                <div>
                    <b>@if ($today['state'] === 'in') You are in the library @elseif ($today['state'] === 'done') Done for today @else Not checked in today @endif</b>
                    <span>@if ($today['state'] === 'in') Since {{ $today['in']->format('g:i A') }} @elseif ($today['state'] === 'done') {{ $today['in']->format('g:i A') }} – {{ $today['out']->format('g:i A') }} @else Scan the library QR when you arrive @endif</span>
                </div>
                @unless (\App\Support\FeatureToggles::routeHidden('learner.attendance.scan'))
                    <a href="{{ route('learner.attendance.scan') }}" class="lat-scan"><x-lb-icon name="scan-line" /> Scan QR</a>
                @endunless
            </div>
        @endif
    </header>

    <div class="lat-layout">
        <aside class="lat-side">
            <section class="lat-card lat-cal">
                <div class="lat-cal-nav">
                    @if($a['can_prev'])<a class="lat-nav" href="{{ $monthUrl($a['month']->copy()->subMonth()) }}" aria-label="Previous month"><x-lb-icon name="chevron-left" /></a>
                    @else<span class="lat-nav is-off" aria-hidden="true"><x-lb-icon name="chevron-left" /></span>@endif
                    <h2>{{ $a['month']->format('F Y') }}</h2>
                    @if($a['can_next'])<a class="lat-nav" href="{{ $monthUrl($a['month']->copy()->addMonth()) }}" aria-label="Next month"><x-lb-icon name="chevron-right" /></a>
                    @else<span class="lat-nav is-off" aria-hidden="true"><x-lb-icon name="chevron-right" /></span>@endif
                </div>

                <div class="lat-rate">
                    <div class="lat-rate-t"><span>Attendance rate</span><b>{{ $rate }}<small>%</small></b></div>
                    <div class="lat-rate-hrs"><span>Study time</span><b>{{ $a['hours_label'] }}<small> hrs</small></b></div>
                </div>
                <div class="lat-bar" role="img" aria-label="{{ $a['present'] }} present, {{ $a['half'] }} half days, {{ $a['absent'] }} absent">
                    @if($marked > 0)
                        <i class="is-full" style="width: {{ $pct($a['present']) }}%"></i>
                        <i class="is-half" style="width: {{ $pct($a['half']) }}%"></i>
                        <i class="is-absent" style="width: {{ $pct($a['absent']) }}%"></i>
                    @endif
                </div>
                <div class="lat-counts">
                    <span><i class="lat-dot is-full"></i>Present<b>{{ $a['present'] }}</b></span>
                    <span><i class="lat-dot is-half"></i>Half day<b>{{ $a['half'] }}</b></span>
                    <span><i class="lat-dot is-absent"></i>Absent<b>{{ $a['absent'] }}</b></span>
                </div>

                <div class="lat-week" aria-hidden="true"><span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span></div>
                <div class="lat-grid" id="latGrid">
                    @foreach($a['calendar'] as $c)
                        @if(!$c['in_month'])
                            <span class="lat-day is-out"><b>{{ $c['day'] }}</b></span>
                        @elseif(!$c['in_range'])
                            <span class="lat-day is-off"><b>{{ $c['day'] }}</b></span>
                        @else
                            <button type="button" class="lat-day is-{{ $c['status'] }} {{ $c['date'] === $a['today'] ? 'is-today' : '' }}" data-date="{{ $c['date'] }}" aria-pressed="false"
                                    aria-label="{{ \Carbon\Carbon::parse($c['date'])->format('d M Y') }}, {{ $label[$c['status']] ?? 'No record' }}">
                                <b>{{ $c['day'] }}</b><small>{{ $c['status'] === 'absent' ? '' : $hrs($c['hours']) }}</small>
                            </button>
                        @endif
                    @endforeach
                </div>
                <p class="lat-cal-tip"><x-lb-icon name="info" />Tap a day to see only that day.</p>
            </section>
            <div class="lat-note"><x-lb-icon name="qr-code" /><p>Scan the library QR when you arrive and again when you leave. Missed a scan? Tell the library desk.</p></div>
        </aside>

        <section class="lat-main">
            <div class="lat-list-h">
                <h2>Daily log<span id="latCount">{{ count($a['logs']) }}</span></h2>
                <button type="button" class="lat-clear" id="latShowAll" hidden><x-lb-icon name="x" /><span id="latFilterLabel">Show all days</span></button>
            </div>

            @if(count($a['logs']))
                <div class="lat-list" id="latList">
                    @foreach($a['logs'] as $l)
                        @php $out = $l['check_out'] ?? ($l['is_inside'] ? 'Inside now' : null); @endphp
                        <a href="{{ route('learner.attendance.day', $l['date']) }}" class="lat-row" data-date="{{ $l['date'] }}">
                            <span class="lat-badge is-{{ $l['status'] }}"><small>{{ $l['day_name'] }}</small><b>{{ $l['day_num'] }}</b></span>
                            <span class="lat-row-t">
                                <span class="lat-row-top">
                                    <b>{{ \Carbon\Carbon::parse($l['date'])->format('d M') }}</b>
                                    <span class="lat-st is-{{ $l['status'] }}">{{ $label[$l['status']] ?? 'No record' }}</span>
                                    @if($l['has_overtime'])<span class="lat-ot"><x-lb-icon name="timer" />+{{ $l['overtime_text'] }}</span>@endif
                                </span>
                                @if($l['check_in'] || $out)
                                    <span class="lat-times">
                                        <span><x-lb-icon name="log-in" />{{ $l['check_in'] ?? '—' }}</span>
                                        <span class="{{ $l['is_inside'] && !$l['check_out'] ? 'is-live' : '' }}"><x-lb-icon name="log-out" />{{ $out ?? '—' }}{{ $l['implied_out'] ? ' · shift end' : '' }}</span>
                                    </span>
                                @else
                                    <span class="lat-times is-none">No punch recorded</span>
                                @endif
                            </span>
                            <span class="lat-row-r"><b>{{ $l['hours'] !== null ? number_format($l['hours'], 1) : '0' }}<small>h</small></b><x-lb-icon name="chevron-right" /></span>
                        </a>
                    @endforeach
                </div>
                <div class="lat-empty" id="latNone" hidden><x-lb-icon name="calendar-x" />No log for this day.</div>
            @else
                <div class="lat-empty"><x-lb-icon name="calendar-x" />No attendance records for {{ $a['month']->format('F Y') }}.</div>
            @endif
        </section>
    </div>
</div>
@endsection

@push('lr-scripts')
<script>
(function () {
    var grid = document.getElementById('latGrid'), rows = document.querySelectorAll('#latList .lat-row'),
        count = document.getElementById('latCount'), clear = document.getElementById('latShowAll'),
        label = document.getElementById('latFilterLabel'), none = document.getElementById('latNone');
    if (!grid) return;
    function select(btn) {
        grid.querySelectorAll('.lat-day[aria-pressed="true"]').forEach(function (el) { el.setAttribute('aria-pressed', 'false'); });
        var date = btn ? btn.dataset.date : null, shown = 0;
        rows.forEach(function (r) { var on = !date || r.dataset.date === date; r.hidden = !on; if (on) shown++; });
        if (btn) btn.setAttribute('aria-pressed', 'true');
        if (count) count.textContent = shown;
        if (none) none.hidden = shown > 0;
        if (clear) clear.hidden = !date;
        if (label && btn) label.textContent = btn.getAttribute('aria-label').split(',')[0];
    }
    grid.addEventListener('click', function (e) {
        var btn = e.target.closest('.lat-day[data-date]');
        if (btn) select(btn.getAttribute('aria-pressed') === 'true' ? null : btn);
    });
    if (clear) clear.addEventListener('click', function () { select(null); });
})();
</script>
@endpush
