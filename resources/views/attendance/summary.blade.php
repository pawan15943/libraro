@extends('layouts.library')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-attn.css') }}?v={{ @filemtime(public_path('css/lb-attn.css')) }}" />
@endpush

@section('content')

@php
    $atPhoto = ($learnerModel->profile_picture ?? null) && file_exists(public_path($learnerModel->profile_picture)) ? asset($learnerModel->profile_picture) : null;
    $atMarked = $presentCount + $halfCount + $absentCount;
    $atRate = $atMarked > 0 ? (int) round(($presentCount + $halfCount * 0.5) / $atMarked * 100) : 0;
    $atPct = fn ($n) => $atMarked > 0 ? round($n / $atMarked * 100, 2) : 0;
    $atHours = fn ($h) => $h !== null ? rtrim(rtrim(number_format($h, 1), '0'), '.') . 'h' : '';
    $atMonthUrl = fn ($m) => route('attendance.summary', $learnerId) . '?year=' . $m->year . '&month=' . $m->month;
    $atStatusLabel = ['full' => 'Present', 'half' => 'Half day', 'absent' => 'Absent'];
@endphp

<div class="lat" id="latRoot">

    {{-- Member --}}
    <header class="lat-head">
        <div class="lat-head-top">
            <a href="{{ route('learners') }}" class="lat-back" title="All learners" aria-label="All learners"><x-lb-icon name="arrow-left" /></a>
            <div class="lat-crumb">Members<x-lb-icon name="chevron-right" /><span>Attendance</span></div>
        </div>
        <div class="lat-member">
            <span class="lat-ava">@if($atPhoto)<img src="{{ $atPhoto }}" alt="{{ $learnerName }}">@else{{ $learnerInitials ?? 'LR' }}@endif</span>
            <div class="lat-member-id">
                <h1>{{ $learnerName }}</h1>
                <div class="lat-tags">
                    <span class="lat-uid">{{ $learnerNo ?: 'LRN-' . $learnerId }}</span>
                    @if(!empty($membershipTag))
                        <span class="lat-tag"><x-lb-icon name="armchair" />{{ $membershipTag }}</span>
                    @endif
                    @if(!empty($branchName))
                        <span class="lat-tag"><x-lb-icon name="building" />{{ $branchName }}</span>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <div class="lat-layout">

        {{-- Month: rate + calendar --}}
        <aside class="lat-side">
            <section class="lat-card lat-cal">
                <div class="lat-cal-nav">
                    @if($canGoPrev)
                        <a class="lat-nav" href="{{ $atMonthUrl($prevMonth) }}" title="Previous month" aria-label="Previous month"><x-lb-icon name="chevron-left" /></a>
                    @else
                        <span class="lat-nav is-off" aria-hidden="true"><x-lb-icon name="chevron-left" /></span>
                    @endif
                    <h2>{{ $displayMonth->format('F Y') }}</h2>
                    @if($canGoNext)
                        <a class="lat-nav" href="{{ $atMonthUrl($nextMonth) }}" title="Next month" aria-label="Next month"><x-lb-icon name="chevron-right" /></a>
                    @else
                        <span class="lat-nav is-off" aria-hidden="true"><x-lb-icon name="chevron-right" /></span>
                    @endif
                </div>

                <div class="lat-rate">
                    <div class="lat-rate-t">
                        <span>Attendance rate</span>
                        <b>{{ $atRate }}<small>%</small></b>
                    </div>
                    <div class="lat-rate-hrs">
                        <span>Study time</span>
                        <b>{{ $totalHoursLabel }}<small> hrs</small></b>
                    </div>
                </div>
                <div class="lat-bar" role="img" aria-label="{{ $presentCount }} present, {{ $halfCount }} half days, {{ $absentCount }} absent">
                    @if($atMarked > 0)
                        <i class="is-full" style="width: {{ $atPct($presentCount) }}%"></i>
                        <i class="is-half" style="width: {{ $atPct($halfCount) }}%"></i>
                        <i class="is-absent" style="width: {{ $atPct($absentCount) }}%"></i>
                    @endif
                </div>
                <div class="lat-counts">
                    <span><i class="lat-dot is-full"></i>Present<b>{{ $presentCount }}</b></span>
                    <span><i class="lat-dot is-half"></i>Half day<b>{{ $halfCount }}</b></span>
                    <span><i class="lat-dot is-absent"></i>Absent<b>{{ $absentCount }}</b></span>
                </div>

                <div class="lat-week" aria-hidden="true">
                    <span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span>
                </div>
                <div class="lat-grid" id="latGrid">
                    @foreach($calendarCells as $cell)
                        @if(!$cell['in_month'])
                            <span class="lat-day is-out"><b>{{ $cell['day'] }}</b></span>
                        @elseif(!$cell['in_range'])
                            <span class="lat-day is-off"><b>{{ $cell['day'] }}</b></span>
                        @else
                            <button type="button" class="lat-day is-{{ $cell['status'] }} {{ $cell['date'] === $today ? 'is-today' : '' }}"
                                    data-date="{{ $cell['date'] }}"
                                    aria-pressed="false"
                                    aria-label="{{ \Carbon\Carbon::parse($cell['date'])->format('d M Y') }}, {{ $atStatusLabel[$cell['status']] ?? 'No record' }}">
                                <b>{{ $cell['day'] }}</b>
                                <small>{{ $cell['status'] === 'absent' ? '' : $atHours($cell['hours']) }}</small>
                            </button>
                        @endif
                    @endforeach
                </div>
                <p class="lat-cal-tip"><x-lb-icon name="info" />Tap a day to see only that day below.</p>
            </section>
        </aside>

        {{-- Day list --}}
        <section class="lat-main">
            <div class="lat-list-h">
                <h2>Daily log<span id="latCount">{{ count($logs) }}</span></h2>
                <button type="button" class="lat-clear" id="latShowAll" hidden><x-lb-icon name="x" /><span id="latFilterLabel">Show all days</span></button>
            </div>

            @if(count($logs))
                <div class="lat-list" id="latList">
                    @foreach($logs as $log)
                        @php
                            $lgDate = \Carbon\Carbon::parse($log['date']);
                            $lgOut = $log['check_out'] ?? ($log['is_inside'] ? 'Inside now' : null);
                        @endphp
                        <a href="{{ route('attendance.logs.page', [$learnerId, $log['date']]) }}" class="lat-row" data-date="{{ $log['date'] }}">
                            <span class="lat-badge is-{{ $log['status'] }}">
                                <small>{{ $log['day_name'] }}</small>
                                <b>{{ $log['day_num'] }}</b>
                            </span>
                            <span class="lat-row-t">
                                <span class="lat-row-top">
                                    <b>{{ $lgDate->format('d M') }}</b>
                                    <span class="lat-st is-{{ $log['status'] }}">{{ $atStatusLabel[$log['status']] ?? 'No record' }}</span>
                                    @if(!empty($log['has_overtime']))
                                        <span class="lat-ot"><x-lb-icon name="timer" />+{{ $log['overtime_text'] }}</span>
                                    @endif
                                </span>
                                @if($log['check_in'] || $lgOut)
                                    <span class="lat-times">
                                        <span><x-lb-icon name="log-in" />{{ $log['check_in'] ?? '—' }}</span>
                                        <span class="{{ $log['is_inside'] && !$log['check_out'] ? 'is-live' : '' }}"><x-lb-icon name="log-out" />{{ $lgOut ?? '—' }}{{ $log['implied_out'] ? ' · shift end' : '' }}</span>
                                    </span>
                                @else
                                    <span class="lat-times is-none">No punch recorded</span>
                                @endif
                            </span>
                            <span class="lat-row-r">
                                <b>{{ $log['hours'] !== null ? number_format($log['hours'], 1) : '0' }}<small>h</small></b>
                                <x-lb-icon name="chevron-right" />
                            </span>
                        </a>
                    @endforeach
                </div>
                <div class="lat-empty" id="latNone" hidden><x-lb-icon name="calendar-x" />No log for this day.</div>
            @else
                <div class="lat-empty"><x-lb-icon name="calendar-x" />No attendance records for {{ $displayMonth->format('F Y') }}.</div>
            @endif
        </section>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var grid = document.getElementById('latGrid');
        var rows = document.querySelectorAll('#latList .lat-row');
        var count = document.getElementById('latCount');
        var clear = document.getElementById('latShowAll');
        var label = document.getElementById('latFilterLabel');
        var none = document.getElementById('latNone');
        if (!grid) return;

        function select(btn) {
            grid.querySelectorAll('.lat-day[aria-pressed="true"]').forEach(function (el) { el.setAttribute('aria-pressed', 'false'); });
            var date = btn ? btn.dataset.date : null;
            var shown = 0;
            rows.forEach(function (row) {
                var on = !date || row.dataset.date === date;
                row.hidden = !on;
                if (on) shown++;
            });
            if (btn) btn.setAttribute('aria-pressed', 'true');
            if (count) count.textContent = shown;
            if (none) none.hidden = shown > 0;
            if (clear) clear.hidden = !date;
            if (label && btn) label.textContent = btn.getAttribute('aria-label').split(',')[0];
            if (btn && window.matchMedia('(max-width: 991.98px)').matches) {
                document.querySelector('.lat-list-h').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        grid.addEventListener('click', function (e) {
            var btn = e.target.closest('.lat-day[data-date]');
            if (!btn) return;
            select(btn.getAttribute('aria-pressed') === 'true' ? null : btn);
        });
        if (clear) clear.addEventListener('click', function () { select(null); });
    });
</script>

@endsection
