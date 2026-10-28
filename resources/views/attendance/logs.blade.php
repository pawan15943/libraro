@extends('layouts.library')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-attn.css') }}?v={{ @filemtime(public_path('css/lb-attn.css')) }}" />
@endpush

@section('content')

@php
    $dlPhoto = ($learnerModel->profile_picture ?? null) && file_exists(public_path($learnerModel->profile_picture)) ? asset($learnerModel->profile_picture) : null;
    $dlDate = \Carbon\Carbon::parse($date);
    $dlBack = route('attendance.summary', $learnerId) . '?year=' . $dlDate->year . '&month=' . $dlDate->month;
    $dlDayUrl = fn ($d) => route('attendance.logs.page', ['learner' => $learnerId, 'date' => $d]);

    // Sessions on a 24h track: pair each IN with the following OUT; an open IN runs till now (today) or stays a dot.
    $dlMin = function ($raw) {
        try {
            $t = \Carbon\Carbon::parse($raw);
            return $t->hour * 60 + $t->minute;
        } catch (\Throwable $e) {
            return null;
        }
    };
    $dlSessions = [];
    $dlOpen = null;
    foreach ($punches as $p) {
        $m = $dlMin($p['raw_datetime'] ?? null);
        if ($m === null) {
            continue;
        }
        if ($p['type'] === 'IN') {
            $dlOpen = $m;
        } elseif ($dlOpen !== null) {
            $dlSessions[] = ['from' => $dlOpen, 'to' => max($m, $dlOpen + 1), 'live' => false];
            $dlOpen = null;
        }
    }
    if ($dlOpen !== null) {
        $dlSessions[] = !empty($isInside)
            ? ['from' => $dlOpen, 'to' => max(now()->hour * 60 + now()->minute, $dlOpen + 1), 'live' => true]
            : ['from' => $dlOpen, 'to' => $dlOpen + 1, 'live' => false, 'open' => true];
    }
    $dlTone = $isAbsent ? 'absent' : (!empty($isInside) ? 'live' : 'full');
@endphp

<div class="lat lal">

    {{-- Member + date switcher --}}
    <header class="lat-head">
        <div class="lat-head-top">
            <a href="{{ $dlBack }}" class="lat-back" title="Monthly calendar" aria-label="Monthly calendar"><x-lb-icon name="arrow-left" /></a>
            <div class="lat-crumb"><a href="{{ $dlBack }}">Attendance</a><x-lb-icon name="chevron-right" /><span>{{ $dlDate->format('d M') }}</span></div>
            @if(!empty($isToday))
                <span class="lal-live"><i></i>Live today</span>
            @endif
        </div>
        <div class="lat-member">
            <span class="lat-ava">@if($dlPhoto)<img src="{{ $dlPhoto }}" alt="{{ $learnerName }}">@else{{ $learnerInitials }}@endif</span>
            <div class="lat-member-id">
                <h1>{{ $learnerName }}</h1>
                <div class="lat-tags">
                    <span class="lat-uid">{{ $learnerNo ?: 'LRN-' . $learnerId }}</span>
                    @if(!empty($membershipTag))
                        <span class="lat-tag"><x-lb-icon name="armchair" />{{ $membershipTag }}</span>
                    @endif
                    <span class="lat-tag"><x-lb-icon name="clock" />{{ $shiftDurationText }} shift</span>
                </div>
            </div>
        </div>

        <div class="lal-switch">
            <a href="{{ $hasPrev ? $dlDayUrl($prevDate) : '#' }}" class="lat-nav {{ $hasPrev ? '' : 'is-off' }}" @if(!$hasPrev) aria-disabled="true" tabindex="-1" @endif title="Previous day" aria-label="Previous day"><x-lb-icon name="chevron-left" /></a>
            <label class="lal-date" title="Pick a date">
                <x-lb-icon name="calendar-days" />
                <span><b>{{ $dlDate->format('D, d M Y') }}</b></span>
                <input type="date" id="lalDate" value="{{ $date }}" min="{{ $minDate }}" max="{{ $maxDate }}" aria-label="Pick a date">
            </label>
            <a href="{{ $hasNext ? $dlDayUrl($nextDate) : '#' }}" class="lat-nav {{ $hasNext ? '' : 'is-off' }}" @if(!$hasNext) aria-disabled="true" tabindex="-1" @endif title="Next day" aria-label="Next day"><x-lb-icon name="chevron-right" /></a>
            @if(empty($isToday))
                <a href="{{ $dlDayUrl($todayDate ?? date('Y-m-d')) }}" class="lal-today">Today</a>
            @endif
        </div>
    </header>

    {{-- Day status --}}
    <section class="lal-hero is-{{ $dlTone }}">
        <div class="lal-hero-top">
            <span class="lal-hero-ic"><x-lb-icon name="{{ $isAbsent ? 'calendar-x' : (!empty($isInside) ? 'user-check' : 'circle-check') }}" /></span>
            <div class="lal-hero-t">
                <span>{{ $statusSubtext }}</span>
                <b>{{ $statusText }}</b>
            </div>
            <div class="lal-hero-d">
                <span>{{ $durationSubtext }}</span>
                <b>{{ $durationText }}</b>
            </div>
        </div>

        <div class="lal-track" aria-hidden="true">
            <div class="lal-track-bar">
                @foreach($dlSessions as $s)
                    <i class="{{ $s['live'] ? 'is-live' : '' }} {{ !empty($s['open']) ? 'is-open' : '' }}" style="left: {{ round($s['from'] / 1440 * 100, 2) }}%; width: {{ max(0.6, round(($s['to'] - $s['from']) / 1440 * 100, 2)) }}%"></i>
                @endforeach
            </div>
            <div class="lal-track-ticks"><span>12a</span><span>6a</span><span>12p</span><span>6p</span><span>12a</span></div>
        </div>
    </section>

    {{-- Numbers --}}
    <div class="lal-kpis">
        <div class="lal-kpi">
            <span><x-lb-icon name="log-in" />First in</span>
            <b>{{ $inTimeText }}</b>
            <small>{{ $hasPunchIn ? $inLocation : 'Not recorded' }}</small>
        </div>
        <div class="lal-kpi {{ !empty($missingPunchOut) ? 'is-warn' : '' }}">
            <span><x-lb-icon name="log-out" />Last out</span>
            <b>{{ $outTimeText }}</b>
            <small>
                @if(!empty($isInside)) In library now
                @elseif($hasPunchOut) {{ $outLocation }}
                @elseif(!empty($missingPunchOut)) Checkout missing
                @else Not recorded
                @endif
            </small>
        </div>
        <div class="lal-kpi">
            <span><x-lb-icon name="clock" />Shift</span>
            <b>{{ $shiftDurationText }}</b>
            <small>{{ $shiftName ?: 'Allowed time' }}</small>
        </div>
        <div class="lal-kpi {{ !empty($hasOvertime) ? 'is-bad' : '' }}">
            <span><x-lb-icon name="timer" />Overstay</span>
            <b>{{ !empty($hasOvertime) ? '+' . $overtimeText : 'None' }}</b>
            <small>{{ !empty($hasOvertime) ? 'Beyond shift' : 'Within shift' }}</small>
        </div>
    </div>

    @if(!empty($hasOvertime))
        <div class="lal-alert">
            <x-lb-icon name="clock-alert" />
            <p>Stayed <b>{{ $durationText }}</b> against a <b>{{ $shiftDurationText }}</b> shift{{ $shiftName ? ' (' . $shiftName . ')' : '' }} — <b>+{{ $overtimeText }}</b> extra.</p>
        </div>
    @endif

    {{-- Manual punch --}}
    <section class="lat-card lal-act">
        <div class="lal-act-h">
            <h2>{{ !empty($isToday) ? 'Mark attendance' : 'Manual adjustment' }}</h2>
            <span>{{ !empty($isToday) ? 'Records the current time' : 'For ' . $dateLabel }}</span>
        </div>
        <div class="lal-act-btns">
            <button type="button" class="lal-punch is-in" data-punch="in"><x-lb-icon name="log-in" /><span><b>Punch in</b><small>Record entry</small></span></button>
            <button type="button" class="lal-punch is-out" data-punch="out"><x-lb-icon name="log-out" /><span><b>Punch out</b><small>Record exit</small></span></button>
        </div>
    </section>

    {{-- Timeline --}}
    <section class="lat-card lal-tl">
        <div class="lal-act-h">
            <h2>Punch timeline</h2>
            <span>{{ count($punches) }} {{ count($punches) === 1 ? 'event' : 'events' }}</span>
        </div>

        @if(count($punches) > 0)
            <ol class="lal-events">
                @foreach($punches as $punch)
                    @php $isIn = $punch['type'] === 'IN'; @endphp
                    @if(!empty($punch['session_duration']))
                        <li class="lal-gap"><x-lb-icon name="timer" />Stayed {{ $punch['session_duration'] }}</li>
                    @endif
                    <li class="lal-ev {{ $isIn ? 'is-in' : 'is-out' }}">
                        <span class="lal-ev-dot"><x-lb-icon name="{{ $isIn ? 'log-in' : 'log-out' }}" /></span>
                        <div class="lal-ev-t">
                            <b>{{ $punch['title'] }}</b>
                            <small><x-lb-icon name="map-pin" />{{ $punch['source'] }}</small>
                        </div>
                        <time>{{ $punch['time'] }}</time>
                    </li>
                @endforeach
                @if(!empty($isInside))
                    <li class="lal-ev is-now">
                        <span class="lal-ev-dot"><i></i></span>
                        <div class="lal-ev-t">
                            <b>Studying now</b>
                            <small>In since {{ $inTimeText }} · {{ $durationText }} so far</small>
                        </div>
                        <button type="button" class="lal-now-out" data-punch="out">Punch out</button>
                    </li>
                @endif
            </ol>
        @else
            <div class="lal-none">
                <span class="lal-none-ic"><x-lb-icon name="calendar-x" /></span>
                <b>No punches on {{ $dateLabel }}</b>
                <p>{{ $learnerName }} has no entry or exit on this day, so it counts as absent.</p>
                <button type="button" class="lal-now-out is-in" data-punch="in"><x-lb-icon name="plus" />Add a punch in</button>
            </div>
        @endif
    </section>
</div>

<script>
(function () {
    var input = document.getElementById('lalDate');
    if (input) {
        input.addEventListener('change', function () {
            if (!this.value) return;
            window.location.href = "{{ route('attendance.logs.page', ['learner' => $learnerId, 'date' => ':date']) }}".replace(':date', this.value);
        });
        input.closest('.lal-date').addEventListener('click', function (e) {
            if (e.target !== input && typeof input.showPicker === 'function') {
                try { input.showPicker(); } catch (err) {}
            }
        });
    }

    document.querySelectorAll('[data-punch]').forEach(function (btn) {
        btn.addEventListener('click', function () { triggerAttendancePunch(btn.dataset.punch); });
    });
})();

/**
 * Punch In / Out via AJAX with SweetAlert2 confirmation
 */
function triggerAttendancePunch(punchType) {
    var actionName = (punchType === 'in') ? 'Punch in' : 'Punch out';
    var actionVerb = (punchType === 'in') ? 'record entry' : 'record exit';
    var dateLabel = @json($dateLabel);
    var learnerName = @json($learnerName);
    var $buttons = $('[data-punch]');

    function fail(msg) {
        $buttons.prop('disabled', false).removeClass('is-busy');
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Could not save', text: msg, confirmButtonColor: '#1d3c86' });
        } else if (typeof toastr !== 'undefined') {
            toastr.error(msg);
        } else {
            alert(msg);
        }
    }

    function proceedWithPunch() {
        $buttons.prop('disabled', true);
        $('[data-punch="' + punchType + '"]').addClass('is-busy');

        $.ajax({
            url: "{{ route('update.attendance') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                learner_id: {{ $learnerId }},
                attendance: 1,
                date: "{{ $date }}",
                time: punchType
            },
            dataType: "json",
            success: function (response) {
                if (response.success || response.status || response.present || response.absent) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(actionName + ' recorded');
                    }
                    setTimeout(function () { window.location.reload(); }, 400);
                } else {
                    fail(response.message || ('Could not record ' + actionName.toLowerCase()));
                }
            },
            error: function (xhr) {
                var msg = 'Failed to record attendance.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    var firstKey = Object.keys(xhr.responseJSON.errors)[0];
                    msg = xhr.responseJSON.errors[firstKey][0];
                }
                fail(msg);
            }
        });
    }

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: actionName + '?',
            html: 'This will <strong>' + actionVerb + '</strong> for <strong>' + $('<div>').text(learnerName).html() + '</strong> on <strong>' + dateLabel + '</strong>.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: (punchType === 'in' ? '#00793d' : '#1d3c86'),
            cancelButtonColor: '#7b7c83',
            confirmButtonText: 'Yes, ' + actionName.toLowerCase(),
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (result.isConfirmed) {
                proceedWithPunch();
            }
        });
    } else if (confirm(actionName + ' for ' + learnerName + ' on ' + dateLabel + '?')) {
        proceedWithPunch();
    }
}
</script>

@endsection
