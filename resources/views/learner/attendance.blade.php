@extends('layouts.library')
@section('content')

{{--
    Manual attendance (route('attendance')): pick a day, then punch each learner In / Out.
    Saves through route('update.attendance') exactly as before: learner_id, attendance (1 / 0),
    date (d/m/Y), time (in / out), custom_time (HH:MM). Out needs an In first.
    State colours as on the dashboard's Live Attendance: inside green, left orange, absent red.
    The dot on the photo: fee overdue red, plan in its grace (extension) days amber.
--}}
<link rel="stylesheet" href="{{ asset('public/css/lb-attmark.css') }}?v={{ @filemtime(public_path('css/lb-attmark.css')) }}" />

@php
    $selectedDate = $selectedDate ?? (request('date') ?: date('Y-m-d'));
    $selectedDateFormatted = $selectedDateFormatted ?? \Carbon\Carbon::parse($selectedDate)->format('d/m/Y');
    $amDay = \Carbon\Carbon::parse($selectedDate);
    $amIsToday = $amDay->isToday();
    $amPrev = $amDay->copy()->subDay()->format('d/m/Y');
    $amNext = $amDay->copy()->addDay()->format('d/m/Y');
    $alerts = $alerts ?? [];
    $amFmt = function ($t) { try { $c = \Carbon\Carbon::parse($t); return $c->format('i') === '00' ? $c->format('g A') : $c->format('g:i A'); } catch (\Throwable $e) { return ''; } };
    $amInitials = fn ($n) => collect(explode(' ', trim((string) $n)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: 'L';
@endphp

<div class="lam" id="lamRoot" data-today="{{ $amIsToday ? '1' : '0' }}">

    {{-- day + search --}}
    <form action="{{ route('attendance') }}" method="GET" id="attendanceFilterForm" class="lam-bar" role="search">
        <div class="lam-day">
            <a href="{{ route('attendance', array_filter(['date' => $amPrev, 'search' => request('search')])) }}" class="lam-daybtn" aria-label="Previous day"><x-lb-icon name="chevron-left" /></a>
            <label class="lam-date">
                <x-lb-icon name="calendar-days" />
                <span class="lam-date-t"><small>{{ $amIsToday ? 'Today' : $amDay->format('l') }}</small><b>{{ $amDay->format('j M Y') }}</b></span>
                <input type="text" name="date" id="attendance_date" value="{{ $selectedDateFormatted }}" readonly aria-label="Choose date">
            </label>
            <a href="{{ route('attendance', array_filter(['date' => $amNext, 'search' => request('search')])) }}" class="lam-daybtn" aria-label="Next day"><x-lb-icon name="chevron-right" /></a>
            @unless($amIsToday)<a href="{{ route('attendance', array_filter(['search' => request('search')])) }}" class="lam-todaybtn">Today</a>@endunless
        </div>
        <label class="lam-search">
            <x-lb-icon name="search" />
            <input type="search" name="search" id="attendance_search" placeholder="Search name or seat" value="{{ request('search') }}" autocomplete="off">
        </label>
    </form>

    @if($learners->count() > 0)
    {{-- counts: tap to filter --}}
    <div class="lam-tabs" role="tablist" aria-label="Filter by status">
        <button type="button" class="lam-tab is-on" data-f="all" role="tab" aria-selected="true"><b data-n="all">0</b><span><i></i>All</span></button>
        <button type="button" class="lam-tab lam-tab--in" data-f="in" role="tab" aria-selected="false"><b data-n="in">0</b><span><i></i>{{ $amIsToday ? 'Inside' : 'Present' }}</span></button>
        <button type="button" class="lam-tab lam-tab--out" data-f="out" role="tab" aria-selected="false"><b data-n="out">0</b><span><i></i>Left</span></button>
        <button type="button" class="lam-tab lam-tab--late" data-f="late" role="tab" aria-selected="false"><b data-n="late">0</b><span><i></i>Absent</span></button>
    </div>
    {{-- kept for older scripts that read the totals --}}
    <span id="statTotal" hidden></span><span id="statPresent" hidden></span><span id="statAbsent" hidden></span>

    <div class="lam-list" id="attendanceListContainer">
        @foreach($learners as $value)
        @php
            $lid = $value->learner_id;
            $hasIn = !empty($value->in_time);
            $hasOut = !empty($value->out_time);
            $alert = $alerts[$lid] ?? null;
            $pt = $value->planType;
            $shiftTime = $pt ? $pt->timeLabel() : '';
            $seat = $value->seat_no ? getSeatDisplayByMainNo($value->seat_no) : 'General';
            $photo = $value->profile_picture && file_exists(public_path($value->profile_picture)) ? asset($value->profile_picture) : null;
        @endphp
        <article class="lam-card" data-st="{{ !$hasIn ? 'late' : ($hasOut ? 'out' : 'in') }}" id="attendance_row_{{ $lid }}" data-learner="{{ $lid }}" data-q="{{ strtolower($value->name . ' ' . $seat . ' ' . $value->seat_no) }}">
            <div class="lam-card-top">
                <span class="lam-ava">
                    <span class="lam-ava-in">@if($photo)<img src="{{ $photo }}" alt="" loading="lazy">@else{{ $amInitials($value->name) }}@endif</span>
                    @if($alert)<i class="lam-alert is-{{ $alert }}" title="{{ $alert === 'due' ? 'Fee overdue' : 'Plan expired · in extension' }}"></i>@endif
                </span>
                <div class="lam-who">
                    <b>{{ $value->name }}</b>
                    <small><strong>{{ $seat === 'General' ? 'General seat' : 'Seat ' . $seat }}</strong> · {{ $pt->name ?? ($value->plan_type_name ?? 'Shift') }}@if($shiftTime) <strong>{{ $shiftTime }}</strong>@endif</small>
                </div>
                <span class="lam-pill" data-state-t>Absent</span>
            </div>

            {{-- In → Out times (tap a time to change it) --}}
            <div class="lam-flow">
                <span class="lam-t lam-t--in" id="punch_item_in_{{ $lid }}">
                    <x-lb-icon name="log-in" /><small>In</small>
                    <input type="text" data-lb-time data-lb-time-icon="0" data-lb-time-placeholder="--:--" class="punch-time-input in-time-input" id="in_time_input_{{ $lid }}" data-learner="{{ $lid }}" data-time="in" value="{{ $hasIn ? \Carbon\Carbon::parse($value->in_time)->format('H:i') : '' }}">
                </span>
                <span class="lam-line" aria-hidden="true"></span>
                <span class="lam-t lam-t--out" id="punch_item_out_{{ $lid }}">
                    <x-lb-icon name="log-out" /><small>Out</small>
                    <input type="text" data-lb-time data-lb-time-icon="0" data-lb-time-placeholder="--:--" class="punch-time-input out-time-input" id="out_time_input_{{ $lid }}" data-learner="{{ $lid }}" data-time="out" value="{{ $hasOut ? \Carbon\Carbon::parse($value->out_time)->format('H:i') : '' }}">
                </span>
                <span class="lam-dur" data-state-s id="time_spent_{{ $lid }}"></span>
            </div>

            {{-- one clear next step: Mark In → Mark Out → done; Undo removes the last punch --}}
            <div class="lam-act">
                <button type="button" class="lam-main" data-main><x-lb-icon name="log-in" class="is-in" /><x-lb-icon name="log-out" class="is-out" /><x-lb-icon name="circle-check" class="is-done" /><span data-main-t>Mark In</span></button>
                <button type="button" class="lam-undo" data-undo title="Undo the last punch" aria-label="Undo the last punch" hidden><x-lb-icon name="rotate-ccw" /><span>Undo</span></button>
            </div>
            {{-- the punch switches the save script reads --}}
            <input type="checkbox" hidden id="in_toggle_{{ $lid }}" class="punch-toggle in-toggle" data-learner="{{ $lid }}" data-time="in" data-in-time="{{ $value->in_time ?: '' }}" @checked($hasIn)>
            <input type="checkbox" hidden id="out_toggle_{{ $lid }}" class="punch-toggle out-toggle" data-learner="{{ $lid }}" data-time="out" data-out-time="{{ $value->out_time ?: '' }}" @checked($hasOut)>
        </article>
        @endforeach
    </div>
    <div class="lam-none" id="lamNone" hidden><x-lb-icon name="search" /><b>No learner matches</b><span>Try another name, seat or status.</span></div>
    @else
    <div class="lam-none">
        <x-lb-icon name="users-round" />
        <b>No learners found</b>
        <span>No active learners for this day{{ request('search') ? ' or search' : '' }}.</span>
        <a href="{{ route('attendance') }}" class="lp-btn lp-btn--ghost"><x-lb-icon name="rotate-ccw" />Back to today</a>
    </div>
    @endif
</div>

<script>
$(function () {
    var isToday = $('#lamRoot').attr('data-today') === '1', filter = 'all';

    if (typeof flatpickr !== 'undefined') {
        flatpickr('#attendance_date', {
            dateFormat: 'd/m/Y', allowInput: false, disableMobile: 'true', defaultDate: @json($selectedDateFormatted),
            positionElement: document.querySelector('.lam-date'),
            onChange: function (d, str) { if (str) $('#attendanceFilterForm').submit(); }
        });
    }
    $('.lam-date').on('click', function () { var fp = document.getElementById('attendance_date')._flatpickr; if (fp) fp.open(); });

    function now24() { var d = new Date(); return ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2); }
    function toMin(t) { var m = /^(\d{1,2}):(\d{2})/.exec(t || ''); return m ? +m[1] * 60 + +m[2] : null; }
    function dur(mins) { mins = Math.max(0, mins); var h = Math.floor(mins / 60), m = mins % 60; return (h ? h + ' hr ' : '') + m + ' min'; }
    function fmt(t) { var m = toMin(t); if (m === null) return ''; var h = Math.floor(m / 60), mm = m % 60; return (h % 12 || 12) + (mm ? ':' + ('0' + mm).slice(-2) : '') + (h < 12 ? ' AM' : ' PM'); }

    // one card: state colour, status text and the punch buttons, from the checkboxes and times
    function paint(id) {
        var $c = $('#attendance_row_' + id); if (!$c.length) return;
        var hasIn = $('#in_toggle_' + id).prop('checked'), hasOut = $('#out_toggle_' + id).prop('checked');
        var tin = $('#in_time_input_' + id).val(), tout = $('#out_time_input_' + id).val();
        var st = !hasIn ? 'late' : (hasOut ? 'out' : 'in');
        var pill = st === 'late' ? 'Absent' : (st === 'out' ? 'Left' : (isToday ? 'Inside' : 'Present'));
        var durTxt = '';
        if (st === 'out') { var a = toMin(tin), b = toMin(tout); if (a !== null && b !== null) durTxt = dur(b >= a ? b - a : b + 1440 - a); }
        else if (st === 'in' && isToday) { var n = new Date(), x = toMin(tin); if (x !== null) durTxt = dur(n.getHours() * 60 + n.getMinutes() - x) + ' so far'; }
        $c.attr('data-st', st);
        $c.find('[data-state-t]').text(pill);
        $c.find('[data-state-s]').text(st === 'in' ? durTxt : '');   // a finished visit shows its time on the Done button
        $c.find('[data-main-t]').text(st === 'late' ? 'Mark In' : (st === 'in' ? 'Mark Out' : 'Done' + (durTxt ? ' · ' + durTxt : '')));
        $c.find('[data-main]').prop('disabled', st === 'out');
        $c.find('[data-undo]').prop('hidden', st === 'late');
        $('#punch_item_in_' + id).toggleClass('is-on', hasIn);
        $('#punch_item_out_' + id).toggleClass('is-on', hasOut).toggleClass('is-off', !hasIn);
    }
    function counts() {
        var n = { all: 0, in: 0, out: 0, late: 0 };
        $('.lam-card').each(function () { n.all++; n[$(this).attr('data-st')]++; });
        Object.keys(n).forEach(function (k) { $('[data-n="' + k + '"]').text(n[k]); });
        $('#statTotal').text(n.all); $('#statPresent').text(n.in + n.out); $('#statAbsent').text(n.late);
    }
    function applyFilter() {
        var q = ($('#attendance_search').val() || '').trim().toLowerCase(), shown = 0;
        $('.lam-card').each(function () {
            var ok = (filter === 'all' || $(this).attr('data-st') === filter) && (!q || $(this).attr('data-q').indexOf(q) > -1);
            $(this).prop('hidden', !ok); if (ok) shown++;
        });
        $('#lamNone').prop('hidden', shown > 0 || !$('.lam-card').length);
    }
    function refresh(id) { if (id) paint(id); counts(); applyFilter(); }

    $('.lam-card').each(function () { paint($(this).attr('data-learner')); });
    counts();
    if (isToday) setInterval(function () { $('.lam-card[data-st="in"]').each(function () { paint($(this).attr('data-learner')); }); }, 60000);

    $('.lam-tab').on('click', function () {
        filter = $(this).attr('data-f');
        $('.lam-tab').removeClass('is-on').attr('aria-selected', 'false');
        $(this).addClass('is-on').attr('aria-selected', 'true');
        applyFilter();
    });
    $('#attendance_search').on('input', applyFilter);

    function save(id, type, checked, time) {
        var $in = $('#in_toggle_' + id), $out = $('#out_toggle_' + id);
        $('#attendance_row_' + id).addClass('is-busy');
        $.ajax({
            url: @json(route('update.attendance')), method: 'POST',
            data: { learner_id: id, attendance: checked ? 1 : 0, date: $('#attendance_date').val() || @json($selectedDateFormatted), time: type, custom_time: time || null, _token: @json(csrf_token()) }
        }).done(function (r) {
            if (r.present) {
                if (r.in_time_raw) { $in.prop('checked', true).attr('data-in-time', r.in_time_raw); $('#in_time_input_' + id).val(r.in_time_24); }
                if (r.out_time_raw) { $out.prop('checked', true).attr('data-out-time', r.out_time_raw); $('#out_time_input_' + id).val(r.out_time_24); }
                else if (type === 'out' && !checked) { $out.prop('checked', false).attr('data-out-time', ''); $('#out_time_input_' + id).val(''); }
            } else {
                $in.prop('checked', false).attr('data-in-time', ''); $('#in_time_input_' + id).val('');
                $out.prop('checked', false).attr('data-out-time', ''); $('#out_time_input_' + id).val('');
            }
            refresh(id);
            if (window.toastr) toastr.success(r.message || 'Attendance saved');
        }).fail(function (x) {
            (type === 'in' ? $in : $out).prop('checked', !checked);
            refresh(id);
            var msg = (x.responseJSON && x.responseJSON.message) || 'Could not save attendance. Please try again.';
            if (window.toastr) toastr.error(msg); else alert(msg);
        }).always(function () { $('#attendance_row_' + id).removeClass('is-busy'); });
    }
    function needIn(id) {
        if ($('#in_toggle_' + id).prop('checked')) return false;
        if (window.toastr) toastr.warning('Mark the In punch first.'); else alert('Mark the In punch first.');
        return true;
    }

    // the one button: Mark In, then Mark Out (at now, or the time already typed)
    $(document).on('click', '.lam [data-main]', function () {
        var id = $(this).closest('.lam-card').attr('data-learner');
        var $t = !$('#in_toggle_' + id).prop('checked') ? $('#in_toggle_' + id) : (!$('#out_toggle_' + id).prop('checked') ? $('#out_toggle_' + id) : null);
        if ($t) $t.prop('checked', true).trigger('change');
    });
    // Undo: the Out punch first, then the In punch (marks the learner absent)
    $(document).on('click', '.lam [data-undo]', function () {
        var id = $(this).closest('.lam-card').attr('data-learner');
        var $t = $('#out_toggle_' + id).prop('checked') ? $('#out_toggle_' + id) : $('#in_toggle_' + id);
        if ($t.prop('checked')) $t.prop('checked', false).trigger('change');
    });
    // tap In / Out: mark it now (or at the time already set); tap again to undo
    $(document).on('change', '.lam .punch-toggle', function () {
        var $t = $(this), id = $t.data('learner'), type = $t.data('time'), on = $t.prop('checked');
        if (type === 'out' && on && needIn(id)) { $t.prop('checked', false); return; }
        var $time = $('#' + type + '_time_input_' + id);
        save(id, type, on, on ? ($time.val() || now24()) : null);
    });
    // set or change a punch time
    $(document).on('change', '.lam .punch-time-input', function () {
        var $i = $(this), id = $i.data('learner'), type = $i.data('time'), v = $i.val();
        if (!v) { save(id, type, false, null); return; }
        if (type === 'out' && needIn(id)) { $i.val(''); return; }
        save(id, type, true, v);
    });
});
</script>
@endsection
