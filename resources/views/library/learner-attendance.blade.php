@extends('layouts.library')
@section('content')

{{--
    Attendance summary of one day (route('get.learner.attendance')): the same cards as the
    dashboard's Live Attendance (DashboardInsightsService::liveAttendance for the picked day).
    Inside green · left orange · absent red; photo dot: fee overdue red, plan in extension amber.
    Filters: date (d/m/Y), search (name / seat, in the browser), status tabs (all / in / out / late).
--}}
<link rel="stylesheet" href="{{ asset('public/css/lb-dash.css') }}?v={{ @filemtime(public_path('css/lb-dash.css')) }}" />
<link rel="stylesheet" href="{{ asset('public/css/lb-attmark.css') }}?v={{ @filemtime(public_path('css/lb-attmark.css')) }}" />

@php
    $selectedDate = $selectedDate ?? (request('date') ?: date('Y-m-d'));
    $selectedDateFormatted = $selectedDateFormatted ?? \Carbon\Carbon::parse($selectedDate)->format('d/m/Y');
    $asDay = \Carbon\Carbon::parse($selectedDate);
    $asToday = $asDay->isToday();
    $asNow = (int) ($att['now'] ?? now()->timestamp);
    $asPeople = collect($att['people'] ?? []);
    $asShifts = collect($att['shifts'] ?? [])->keyBy('key');
    $asCounts = $att['counts'] ?? ['all' => 0, 'in' => 0, 'out' => 0, 'late' => 0, 'punches' => 0];
    // old links used status=present / absent
    $asFilter = ['present' => 'all', 'absent' => 'late'][request('status')] ?? (in_array(request('status'), ['all', 'in', 'out', 'late'], true) ? request('status') : 'all');
    $asTm = fn ($ts) => \Carbon\Carbon::createFromTimestamp($ts)->format('g:i a');
    $asDur = function ($sec, $live = false) { $sec = max(0, (int) $sec); $h = intdiv($sec, 3600); $m = intdiv($sec % 3600, 60); return ($h ? $h . 'h ' : '') . ($h ? str_pad($m, 2, '0', STR_PAD_LEFT) : $m) . 'm' . ($live ? ' ' . str_pad($sec % 60, 2, '0', STR_PAD_LEFT) . 's' : ''); };
    $asInside = function ($p) use ($asNow) { $t = 0; $a = $p['punches']; for ($i = 0; $i < count($a); $i += 2) { $t += ($a[$i + 1] ?? $asNow) - $a[$i]; } return $t; };
    $asPrev = $asDay->copy()->subDay()->format('d/m/Y');
    $asNext = $asDay->copy()->addDay()->format('d/m/Y');
    $asStates = [
        'in' => ['tag' => $asToday ? 'INSIDE' : 'PRESENT', 'icon' => 'log-in', 'cls' => 'in'],
        'out' => ['tag' => 'LEFT', 'icon' => 'log-out', 'cls' => 'out'],
        'late' => ['tag' => 'ABSENT', 'icon' => 'clock-alert', 'cls' => 'late'],
    ];
    $asTone = fn ($n) => ['is-blue', 'is-green', 'is-amber', 'is-violet', 'is-rose'][abs(crc32((string) $n)) % 5];
@endphp

<div class="lam las" id="lasRoot" data-today="{{ $asToday ? '1' : '0' }}" data-now="{{ $asNow }}">

    {{-- day + search --}}
    <form action="{{ route('get.learner.attendance') }}" method="GET" id="attendanceFilterForm" class="lam-bar" role="search">
        <div class="lam-day">
            <a href="{{ route('get.learner.attendance', array_filter(['date' => $asPrev, 'search' => request('search')])) }}" class="lam-daybtn" aria-label="Previous day"><x-lb-icon name="chevron-left" /></a>
            <label class="lam-date">
                <x-lb-icon name="calendar-days" />
                <span class="lam-date-t"><small>{{ $asToday ? 'Today' : $asDay->format('l') }}</small><b>{{ $asDay->format('j M Y') }}</b></span>
                <input type="text" name="date" id="attendance_date" value="{{ $selectedDateFormatted }}" readonly aria-label="Choose date">
            </label>
            <a href="{{ route('get.learner.attendance', array_filter(['date' => $asNext, 'search' => request('search')])) }}" class="lam-daybtn" aria-label="Next day"><x-lb-icon name="chevron-right" /></a>
            @unless($asToday)<a href="{{ route('get.learner.attendance', array_filter(['search' => request('search')])) }}" class="lam-todaybtn">Today</a>@endunless
        </div>
        <label class="lam-search">
            <x-lb-icon name="search" />
            <input type="search" name="search" id="attendance_search" placeholder="Search name or seat" value="{{ request('search') }}" autocomplete="off">
        </label>
    </form>

    @if($asPeople->isNotEmpty())
    {{-- counts: tap to filter --}}
    <div class="lpd-pv-tabs" role="tablist" aria-label="Filter by status">
        <button type="button" class="lpd-pv-tab {{ $asFilter === 'all' ? 'is-on' : '' }}" data-f="all" role="tab"><b>{{ $asCounts['all'] }}</b><span><i></i>All</span></button>
        <button type="button" class="lpd-pv-tab lpd-pv-tab--in {{ $asFilter === 'in' ? 'is-on' : '' }}" data-f="in" role="tab"><b>{{ $asCounts['in'] }}</b><span><i></i>{{ $asToday ? 'Inside' : 'No out' }}</span></button>
        <button type="button" class="lpd-pv-tab lpd-pv-tab--out {{ $asFilter === 'out' ? 'is-on' : '' }}" data-f="out" role="tab"><b>{{ $asCounts['out'] }}</b><span><i></i>Left</span></button>
        <button type="button" class="lpd-pv-tab lpd-pv-tab--late {{ $asFilter === 'late' ? 'is-on' : '' }}" data-f="late" role="tab"><b>{{ $asCounts['late'] }}</b><span><i></i>Absent</span></button>
    </div>

    <div class="lpd-att-list" id="lasList">
        @foreach($asPeople->groupBy('shift') as $shiftKey => $group)
        @php
            $sh = $asShifts->get($shiftKey, ['name' => 'General', 'time' => '']);
            $gc = ['in' => $group->where('state', 'in')->count(), 'out' => $group->where('state', 'out')->count(), 'late' => $group->where('state', 'late')->count()];
        @endphp
        <div class="lpd-pv-g" data-group>
            <div class="lpd-pv-gh"><x-lb-icon name="clock-3" /><b>{{ $sh['name'] }}</b><small>{{ $sh['time'] }}</small>
                <span><em class="lpd-att-in">{{ $gc['in'] }} in</em><em class="lpd-att-out">{{ $gc['out'] }} left</em><em class="lpd-att-late">{{ $gc['late'] }} absent</em></span></div>
            @foreach($group as $p)
            @php
                $st = $asStates[$p['state']];
                $n = count($p['punches']);
                $isLive = $p['state'] === 'in' && $asToday;
                $time = $p['state'] === 'late' ? 'Absent' : $asDur($asInside($p), $isLive);
                $sub = $p['state'] === 'in' ? ($asToday ? 'inside now · live' : 'no out punch') : ($p['state'] === 'out' ? 'total time inside' : 'no punch');
                $q = strtolower($p['name'] . ' ' . $p['seat']);
            @endphp
            <article class="lpd-pc lpd-pc--{{ $st['cls'] }}" data-st="{{ $p['state'] }}" data-q="{{ $q }}" @if($isLive) data-live="{{ $p['punches'][$n - 1] }}" data-base="{{ $asInside($p) - ($asNow - $p['punches'][$n - 1]) }}" @endif>
                <div class="lpd-pc-blk"><x-lb-icon :name="$st['icon']" /><span>{{ $st['tag'] }}</span></div>
                <div class="lpd-pc-b">
                    <div class="lpd-pc-top">
                        <span class="lpd-pc-avw">
                            <span class="lp-face {{ $asTone($p['name']) }} lpd-pc-av">@if($p['img'])<img src="{{ $p['img'] }}" alt="" loading="lazy">@else{{ $p['initials'] }}@endif</span>
                            @if(!empty($p['alert']))<i class="lpd-pc-blink is-{{ $p['alert'] }}" title="{{ $p['alert'] === 'due' ? 'Fee overdue' : 'Plan expired · in extension' }}"></i>@endif
                        </span>
                        <a href="{{ $p['url'] }}" class="lpd-pc-n"><b>{{ $p['name'] }}@if(!empty($p['plan'])) <em class="lpd-pc-plan is-{{ $p['plan']['tone'] }}">{{ $p['plan']['label'] }}</em>@endif</b><small><strong>{{ $p['seat'] }}</strong> · {{ $sh['name'] }} <strong>{{ $sh['time'] }}</strong></small></a>
                        <span class="lpd-pc-d"><b data-time>{{ $time }}</b><small>{{ $sub }}</small></span>
                    </div>
                    <div class="lpd-pc-f">
                        <div class="lpd-pc-chips">
                            @if(!$n)
                                <span class="lpd-pn lpd-pn--late"><x-lb-icon name="clock" />Booked · {{ $sh['time'] ?: 'no punch yet' }}</span>
                            @else
                                <span class="lpd-pn lpd-pn--in {{ $n === 1 ? 'is-last' : '' }}"><x-lb-icon name="log-in" /><small>FIRST</small>IN <b>{{ $asTm($p['punches'][0]) }}</b></span>
                                @if($n > 1)
                                    @php $lastIn = ($n - 1) % 2 === 0; @endphp
                                    <span class="lpd-pn-sep">@if($n > 2)<em>+{{ $n - 2 }}</em>@endif</span>
                                    <span class="lpd-pn {{ $lastIn ? 'lpd-pn--in' : 'lpd-pn--out' }} is-last"><x-lb-icon :name="$lastIn ? 'log-in' : 'log-out'" /><small>LAST</small>{{ $lastIn ? 'IN' : 'OUT' }} <b>{{ $asTm($p['punches'][$n - 1]) }}</b></span>
                                @endif
                            @endif
                        </div>
                        <a href="{{ $p['url'] }}" class="lpd-pc-all" title="All punches" aria-label="All punches of {{ $p['name'] }}"><x-lb-icon name="list-tree" />@if($n)<i>{{ $n }}</i>@endif</a>
                    </div>
                </div>
            </article>
            @endforeach
        </div>
        @endforeach
    </div>
    <div class="lam-none" id="lasNone" hidden><x-lb-icon name="search" /><b>No learner matches</b><span>Try another name, seat or status.</span></div>
    @else
    <div class="lam-none">
        <x-lb-icon name="users-round" />
        <b>No learners on this day</b>
        <span>No member held a plan on {{ $asDay->format('j M Y') }}.</span>
        <a href="{{ route('get.learner.attendance') }}" class="lp-btn lp-btn--ghost"><x-lb-icon name="rotate-ccw" />Back to today</a>
    </div>
    @endif
</div>

<script>
$(function () {
    var filter = @json($asFilter);
    if (typeof flatpickr !== 'undefined') {
        flatpickr('#attendance_date', {
            dateFormat: 'd/m/Y', allowInput: false, disableMobile: 'true', defaultDate: @json($selectedDateFormatted),
            positionElement: document.querySelector('.lam-date'),
            onChange: function (d, str) { if (str) $('#attendanceFilterForm').submit(); }
        });
    }
    $('.lam-date').on('click', function () { var fp = document.getElementById('attendance_date')._flatpickr; if (fp) fp.open(); });

    function apply() {
        var q = ($('#attendance_search').val() || '').trim().toLowerCase(), shown = 0;
        $('.las .lpd-pc').each(function () {
            var ok = (filter === 'all' || $(this).attr('data-st') === filter) && (!q || $(this).attr('data-q').indexOf(q) > -1);
            $(this).prop('hidden', !ok); if (ok) shown++;
        });
        $('.las [data-group]').each(function () { $(this).prop('hidden', !$(this).find('.lpd-pc:not([hidden])').length); });
        $('#lasNone').prop('hidden', shown > 0 || !$('.las .lpd-pc').length);
    }
    $('.las .lpd-pv-tab').on('click', function () {
        filter = $(this).attr('data-f');
        $('.las .lpd-pv-tab').removeClass('is-on'); $(this).addClass('is-on');
        apply();
    });
    $('#attendance_search').on('input', apply);
    apply();

    // live time of members inside now
    var skew = Date.now() / 1000 - (+$('#lasRoot').attr('data-now') || Date.now() / 1000);
    function dur(sec) { sec = Math.max(0, Math.floor(sec)); var h = Math.floor(sec / 3600), m = Math.floor(sec % 3600 / 60), s = sec % 60; return (h ? h + 'h ' + String(m).padStart(2, '0') : m) + 'm ' + String(s).padStart(2, '0') + 's'; }
    setInterval(function () {
        var now = Date.now() / 1000 - skew;
        $('.las .lpd-pc[data-live]').each(function () { $(this).find('[data-time]').text(dur(+$(this).attr('data-base') + now - +$(this).attr('data-live'))); });
    }, 1000);
});
</script>
@endsection
