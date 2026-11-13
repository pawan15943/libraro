{{-- always inside the panel; the setup steps show on top during the first-time setup --}}
@extends('lb.layouts.onboard-panel')

{{--
    Shifts & fees (onboarding). Same backend: POST route('master.configuration.store') (AJAX, form-encoded)
    with plan_types[i][plan_type_id|plan_price_id|day_type_id|custom_plan_type|booking_mode|start_time|end_time|slot_hours|price].
    booking_mode (custom shifts only, docs/ADVANCED_SHIFTS.md): fixed | duration | free | flexible.
    Success JSON {status:true, message, redirect?}; 422 → errors by plan_types.i.field.
    Each shift card: one Shift dropdown (Basic and Advanced shifts grouped), Shift name (editable for
    custom and advanced shifts), Start, End, Duration, Monthly fee. Every field is always on the card,
    so all cards are the same size; a field that doesn't apply is read-only.
--}}

@section('title', 'Add your shifts')
@section('ob_step', 'shifts')
@section('ob_contact', '0')

@php
    $openHours = $total_hour ?? optional($operatingHour)->hour;
    $user = auth()->user();
    $can = fn ($p) => $user && $user->can('has-permission', $p);
    // Basic shift types
    $types = [];
    if ($can('Full Day'))   $types[] = [1, 'Full Day'];
    if ($can('First Half')) $types[] = [2, 'First Half'];
    if ($can('Second Half'))$types[] = [3, 'Second Half'];
    if ((string) $openHours === '24') {
        if ($can('All Day'))    $types[] = [8, 'All Day'];
        if ($can('Full Night')) $types[] = [9, 'Full Night'];
    }
    if ($can('Custom Plan')) $types[] = [0, 'Custom (your own time)'];
    $types[] = [10, 'Reserved'];
    $types[] = [11, 'VIP'];

    $rows = $shiftRows->isNotEmpty() ? $shiftRows : collect([(object) ['id' => null, 'day_type_id' => null, 'name' => null, 'booking_mode' => null, 'start_time' => null, 'end_time' => null, 'slot_hours' => null, 'price' => null]]);
    $hm = fn ($t) => $t ? substr($t, 0, 5) : '';

    // after the first-time setup: crumb + operating hours edit link (during setup the steps show on top)
    $inPanel = !librarySetup()['in_setup'];
    $inUseShiftIds = $inUseShiftIds ?? [];

    // Advanced shifts: how the shift is booked (docs/ADVANCED_SHIFTS.md)
    // (what each one does is in "How shifts work")
    $advModes = \App\Models\PlanType::MODE_LABELS;

    // library open – close time (BranchHoursService): advanced shifts are booked inside it
    $libTimes = $libraryTimes ?? null;
    $libAllDay = (int) $openHours >= 24;
    $libLabel = $libTimes ? \App\Services\BranchHoursService::timeLabel($libTimes[0]) . ' – ' . \App\Services\BranchHoursService::timeLabel($libTimes[1]) : null;
    $libNotSet = $openHours && !$libAllDay && !$libTimes;
    $libJs = ['open' => $libTimes[0] ?? null, 'close' => $libTimes[1] ?? null, 'hours' => (int) $openHours, 'allDay' => $libAllDay, 'label' => $libLabel];

@endphp

{{-- in the panel: library hours on the right of the page title (partials/breadcrumbs), no second heading --}}
@if($inPanel)
@section('body_class', 'ob-has-title')
@endif
@if($inPanel && $openHours)
@push('page_head_right')
    @php
        // saved open – close time, else the one worked out from the shifts
        $ohTimes = $libTimes ?: ($libraryTimesGuess ?? null);
        $ohLabel = fn ($t) => \App\Services\BranchHoursService::timeLabel($t);
    @endphp
    <div class="lp-headcard lp-ophours">
        <i><x-lb-icon name="clock-3" /></i>
        <div>
            <small>Operating hours · {{ $libAllDay ? '24' : $openHours }} hrs</small>
            @if($libAllDay)
                <b>Open 24 hours</b>
            @elseif($ohTimes)
                <span class="lp-oh-times">
                    <span><em>Open</em><b>{{ $ohLabel($ohTimes[0]) }}</b></span>
                    <span class="lp-oh-sep" aria-hidden="true">–</span>
                    <span><em>Close</em><b>{{ $ohLabel($ohTimes[1]) }}</b></span>
                </span>
                @unless($libTimes)<span class="lp-oh-note">From your shifts · tap ✎ to save</span>@endunless
            @else
                <b>Set open &amp; close time</b>
            @endif
        </div>
        @if(getCurrentBranch())
            <a href="{{ route('hour.create', getCurrentBranch()) }}" class="lp-headcard-edit" title="Change operating hours" aria-label="Change operating hours"><x-lb-icon name="pencil" /></a>
        @endif
    </div>
@endpush
@endif

@section('ob_content')
<div class="ob-wrap ob-wrap--shifts">
    @unless($inPanel)
    {{-- first-time setup: title on the left, operating hours on the right --}}
    <header class="ob-head ob-head--left ob-head--shifts">
        <div class="ob-head-t">
            <h1>Add your shifts &amp; fees</h1>
        </div>
        @if($openHours)
            <div class="ob-hourcard {{ $libNotSet ? 'is-warn' : '' }}">
                <i><x-lb-icon name="clock-3" /></i>
                @if($libTimes)
                    <div><small>Library hours</small><b>{{ $libLabel }} <em>· {{ $openHours }} hrs</em></b></div>
                @elseif($libAllDay)
                    <div><small>Library hours</small><b>Open 24 hours</b></div>
                @else
                    <div><small>Library hours · {{ $openHours }} hrs</small><b class="ob-hourcard-set">Set open &amp; close time</b></div>
                @endif
            </div>
        @endif
    </header>
    @endunless

    <form method="POST" action="{{ route('master.configuration.store') }}" id="configure" autocomplete="off" novalidate>
        @csrf
        <div class="ob-alert" id="ob-form-alert" role="alert" hidden>{{ session('error') }}</div>

        @if($libNotSet)
            <div class="ob-libwarn" role="note">
                <x-lb-icon name="clock-alert" />
                <p><b>Set when the library opens and closes.</b> Shifts must fit inside these hours, and advanced shifts can only be added once they are set.
                    @if($inPanel && getCurrentBranch())<a href="{{ route('hour.create', getCurrentBranch()) }}">Set library hours</a>@endif</p>
            </div>
        @endif

        <div class="ob-barwrap">
        <div class="ob-shifts-bar">
            <div class="ob-shifts-bar-t">
                <b>Your shifts</b>
                <button type="button" class="ob-help" id="obHelpBtn" aria-expanded="false" aria-controls="obHelp"><x-lb-icon name="info" /><span>How shifts work</span></button>
            </div>
            <button type="button" class="ob-addbtn add-plan-row"><x-lb-icon name="plus" />Add shift</button>
        </div>

        {{-- How each kind of shift works (Basic / Advanced) --}}
        <div class="ob-helppop" id="obHelp" role="dialog" aria-modal="false" aria-labelledby="obHelpTitle" hidden>
            <div class="ob-helppop-head">
                <b id="obHelpTitle">How shifts work</b>
                <button type="button" class="ob-helppop-x" data-help-close aria-label="Close"><x-lb-icon name="x" /></button>
            </div>
            <div class="ob-helptabs" role="tablist" aria-label="Kinds of shifts">
                <button type="button" role="tab" id="obHelpTabB" aria-controls="obHelpB" aria-selected="true">Basic shifts</button>
                <button type="button" role="tab" id="obHelpTabA" aria-controls="obHelpA" aria-selected="false" tabindex="-1">Advanced shifts</button>
            </div>
            <div class="ob-helppane" role="tabpanel" id="obHelpB" aria-labelledby="obHelpTabB">
                <div class="ob-helpitem">
                    <i><x-lb-icon name="sun" /></i>
                    <div>
                        <b>Same time for everyone</b>
                        <p>Full Day, First Half, Second Half or your own Custom time. You set the start and end time here once, and every learner on it comes at that time.</p>
                        <p class="ob-helpeg">e.g. First Half · 8 AM – 2 PM · ₹600 a month</p>
                    </div>
                </div>
                <p class="ob-helpnote"><x-lb-icon name="clock-3" />Each shift has to be inside library hours{{ $libLabel ? ' (' . $libLabel . ')' : ($libAllDay ? ' (open 24 hours)' : '') }}.</p>
            </div>
            <div class="ob-helppane" role="tabpanel" id="obHelpA" aria-labelledby="obHelpTabA" hidden>
                <div class="ob-helpitem">
                    <i><x-lb-icon name="timer" /></i>
                    <div>
                        <b>Fixed hours</b>
                        <p>The learner gets a set number of hours a day. You pick the start time on the booking form, and the end time is worked out.</p>
                        <p class="ob-helpeg">e.g. 4 hrs · booked 7 AM – 11 AM, or 2 PM – 6 PM</p>
                    </div>
                </div>
                <div class="ob-helpitem">
                    <i><x-lb-icon name="hourglass" /></i>
                    <div>
                        <b>Flexible hours</b>
                        <p>The learner comes any time the library is open and stays the set hours. No time is picked at booking. It is booked on a general seat (no seat number).</p>
                        <p class="ob-helpeg">e.g. 6 hrs · comes at 9 AM one day, 3 PM the next</p>
                    </div>
                </div>
                <div class="ob-helpitem">
                    <i><x-lb-icon name="zap" /></i>
                    <div>
                        <b>Direct booking</b>
                        <p>Everything is put in on the booking form: start time, end time and the fee. Good for one-off timings that don't match any shift.</p>
                        <p class="ob-helpeg">e.g. 5:30 PM – 9 PM · ₹450 for the month</p>
                    </div>
                </div>
                <p class="ob-helpnote"><x-lb-icon name="lock" />Advanced shifts use library hours{{ $libLabel ? ' (' . $libLabel . ')' : ($libAllDay ? ' (open 24 hours)' : '') }} as their time, so these fields are locked here. The booking form only allows a time inside them.</p>
            </div>
        </div>
        </div>

        <div id="planRowContainer" class="ob-shifts ob-shifts--grid">
            @foreach($rows as $index => $row)
                @php
                    $dt = $row->day_type_id === null ? '' : (string) $row->day_type_id;
                    $mode = $dt === '0' && in_array($row->booking_mode ?? null, ['duration', 'free', 'flexible'], true) ? $row->booking_mode : 'fixed';
                    // the Shift dropdown value: b:<day type> for a Basic shift, a:<booking mode> for an Advanced one
                    $pick = $mode !== 'fixed' ? 'a:' . $mode : ($dt !== '' ? 'b:' . $dt : '');
                    $isCustom = $dt === '0';
                    $inUse = $row->id && isset($inUseShiftIds[$row->id]);
                    // an advanced shift takes the library hours (00:00 – 00:00); an older one saved with its
                    // own window keeps it while learners are on it
                    $anyTime = $row instanceof \App\Models\PlanType && $row->isAnyTime();
                    $ownWindow = $mode !== 'fixed' && !$anyTime && $row->start_time;
                @endphp
                <section class="ob-card ob-shift plan-row-wrapper" data-row @if($inUse) data-inuse @endif @if($ownWindow) data-ownwin @endif>
                    <div class="ob-shift-head">
                        <span class="ob-shift-no"><i>{{ $index + 1 }}</i></span>
                        <span class="ob-shift-name"><b class="shift-title">Shift {{ $index + 1 }}</b><span class="ob-shift-sum" data-summary></span></span>
                        {{-- learners are on this shift: it can't be removed (the save refuses it too) --}}
                        <span class="ob-inuse" title="Learners are on this shift, so it can't be removed" @unless($inUse) hidden @endunless>In use</span>
                        <button type="button" class="ob-iconbtn ob-iconbtn--add add-after-row" aria-label="Add a shift below this one" title="Add a shift below" @if(!$loop->last) hidden @endif><x-lb-icon name="plus" /></button>
                        <button type="button" class="ob-iconbtn ob-iconbtn--del remove-plan-row" aria-label="Remove this shift" title="Remove this shift" data-id="{{ $row->id ?? '' }}" @if($inUse || ($index === 0 && $rows->count() === 1)) hidden @endif><x-lb-icon name="trash" /></button>
                    </div>

                    <input type="hidden" name="plan_types[{{ $index }}][plan_type_id]" value="{{ $row->id ?? '' }}">
                    <input type="hidden" name="plan_types[{{ $index }}][plan_price_id]" value="{{ $row->price->id ?? '' }}">
                    <input type="hidden" name="plan_types[{{ $index }}][day_type_id]" value="{{ $dt }}" class="plan-type">
                    <input type="hidden" name="plan_types[{{ $index }}][booking_mode]" value="{{ $mode }}" class="booking-mode">

                    <div class="ob-form2">
                        <div class="ob-field">
                            <label>Shift <em>*</em></label>
                            <select class="shift-select ob-select">
                                <option value="">Select shift</option>
                                <optgroup label="Basic · fixed time">
                                    @foreach($types as [$id, $label])
                                        <option value="b:{{ $id }}" @selected($pick === 'b:' . $id)>{{ $label }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Advanced">
                                    @foreach($advModes as $mv => $mlabel)
                                        <option value="a:{{ $mv }}" @selected($pick === 'a:' . $mv)>{{ $mlabel }}</option>
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>
                        {{-- read-only (not disabled) while off, so it is still posted, empty --}}
                        <div class="ob-field ob-custom {{ $isCustom ? '' : 'is-off' }}">
                            <label>Shift name <em>*</em></label>
                            <input type="text" name="plan_types[{{ $index }}][custom_plan_type]" value="{{ $isCustom ? $row->name : '' }}" class="custom-plan" maxlength="100" placeholder="e.g. Morning Batch" @if(!$isCustom) readonly tabindex="-1" aria-disabled="true" @endif>
                        </div>
                        {{-- an advanced shift shows the library hours here, locked: the time is picked on the booking form --}}
                        <div class="ob-field ob-tfield">
                            <label><span class="lbl-start">Start time</span> <em class="time-req">*</em></label>
                            <input type="text" name="plan_types[{{ $index }}][start_time]" value="{{ $anyTime ? '' : $hm($row->start_time) }}" class="start_time" data-lb-time data-lb-time-class="ob-select" placeholder="Select time">
                            <div class="ob-libtime" data-libtime="start" title="Library hours: booked inside them on the booking form"><x-lb-icon name="lock" /><span></span></div>
                        </div>
                        <div class="ob-field ob-tfield">
                            <label><span class="lbl-end">End time</span> <em class="time-req">*</em></label>
                            <input type="text" name="plan_types[{{ $index }}][end_time]" value="{{ $anyTime ? '' : $hm($row->end_time) }}" class="end_time" data-lb-time data-lb-time-class="ob-select" placeholder="Select time">
                            <div class="ob-libtime" data-libtime="end" title="Library hours: booked inside them on the booking form"><x-lb-icon name="lock" /><span></span></div>
                        </div>
                        <div class="ob-field">
                            <label><span class="lbl-hours">Duration</span><em class="hours-req" hidden> *</em></label>
                            <div class="ob-affix ob-affix--end ob-readonly"><input type="text" inputmode="decimal" maxlength="4" name="plan_types[{{ $index }}][slot_hours]" value="{{ $row->slot_hours ?? '' }}" class="slot_hours" readonly tabindex="-1"><span>hours</span></div>
                        </div>
                        <div class="ob-field">
                            <label><span class="lbl-price">Monthly fee</span> <em>*</em></label>
                            <div class="ob-affix"><span>₹</span><input type="text" inputmode="numeric" maxlength="5" name="plan_types[{{ $index }}][price]" value="{{ $row->price->price ?? '' }}" class="price-input digit-only" placeholder="e.g. 800"></div>
                        </div>
                    </div>
                </section>
            @endforeach
        </div>

        <div class="ob-finish ob-finish--sticky">
            <div><b>All set?</b></div>
            <button class="lb-btn lb-btn--lg ob-submit" type="submit"><span class="lb-btn-label">Save &amp; go to dashboard</span><x-lb-icon name="arrow-right" size="18" /></button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
@include('lb.partials.confirm-modal')
<script>
(function () {
    'use strict';
    var form = document.getElementById('configure'), box = document.getElementById('planRowContainer'), alertBox = document.getElementById('ob-form-alert');
    var $ = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
    if (alertBox.textContent.trim()) alertBox.hidden = false;
    try {
        var savedMsg = sessionStorage.getItem('ob_shifts_saved');
        if (savedMsg) { sessionStorage.removeItem('ob_shifts_saved'); setTimeout(function () { window.obToast(savedMsg, 'ok'); }, 300); }
    } catch (x) {}
    // library open – close time: shifts fit inside it, advanced shifts are booked inside it
    var LIB = @json($libJs);
    var LABEL = { day_type_id: 'Shift type', custom_plan_type: 'Shift name', booking_mode: 'Shift system', start_time: 'Start time', end_time: 'End time', slot_hours: 'Hours', price: 'Monthly fee' };
    // Field labels per booking mode (docs/ADVANCED_SHIFTS.md)
    var MODE = {
        fixed:    { start: 'Start time', end: 'End time', hours: 'Duration', price: 'Monthly fee' },
        duration: { start: 'Available from', end: 'Available till', hours: 'Hours per day', price: 'Monthly fee' },
        free:     { start: 'Available from', end: 'Available till', hours: 'Range', price: 'Default monthly fee' },
        flexible: { start: 'Available from', end: 'Available till', hours: 'Hours per day', price: 'Monthly fee' }
    };

    function minutes(row) {
        var s = $('.start_time', row).value, e = $('.end_time', row).value;
        if (!s || !e) return 0;
        var a = s.split(':'), b = e.split(':'), d = (+b[0] * 60 + +b[1]) - (+a[0] * 60 + +a[1]);
        return d <= 0 ? d + 1440 : d;
    }
    function mode(row) { return $('.plan-type', row).value === '0' ? ($('.booking-mode', row).value || 'fixed') : 'fixed'; }
    function hoursTyped(row) { var m = mode(row); return m === 'duration' || m === 'flexible'; }
    // advanced shifts (fixed hours / flexible hours / direct booking) take the library hours as their time
    function libTime(row) { return mode(row) !== 'fixed'; }
    // an older advanced shift saved with its own window keeps it while learners are on it
    function keepsOwn(row) { return row.hasAttribute('data-ownwin') && row.hasAttribute('data-inuse'); }
    function toMin(t) { var m = String(t || '').match(/^(\d{1,2}):(\d{2})/); return m ? (+m[1] % 24) * 60 + +m[2] : null; }
    function libHours() { return LIB.allDay ? 24 : LIB.hours; }
    // whether start–end lies inside the library hours (always, when they aren't set or it's open 24 hours)
    function fitsLib(s, e) {
        if (!LIB.open || LIB.allDay) return true;
        var o = toMin(LIB.open), c = o + LIB.hours * 60, a = toMin(s), b = toMin(e), len = b > a ? b - a : b + 1440 - a;
        return [0, 1440].some(function (k) { return a + k >= o && a + k + len <= c; });
    }
    function slot(row) {
        if (hoursTyped(row)) return;
        var d = minutes(row), out = $('.slot_hours', row);
        // direct booking on library hours: open as long as the library is
        out.value = d ? Math.round(d / 60 * 100) / 100 : (libTime(row) ? libHours() : '');
    }
    function fmt(t) { if (!t) return ''; var p = t.split(':'), h = +p[0], m = p[1]; return (h % 12 || 12) + (m !== '00' ? ':' + m : '') + (h < 12 ? ' AM' : ' PM'); }
    function libText(which) { return LIB.open ? fmt(which === 'start' ? LIB.open : LIB.close) : (LIB.allDay ? 'Open 24 hrs' : 'Library hours'); }
    function shiftName(row) {
        if ($('.plan-type', row).value === '0') return $('.custom-plan', row).value.trim();
        var o = $('.shift-select', row).selectedOptions[0];
        return o && o.value ? o.textContent : '';
    }
    // Card head: "Shift 1" and one line with the name, time and fee
    function sum(row) {
        var s = $('.start_time', row).value, e = $('.end_time', row).value, p = $('.price-input', row).value, m = mode(row), h = $('.slot_hours', row).value;
        var parts = [shiftName(row)];
        if (m === 'duration' || m === 'flexible') { if (h) parts.push(h + ' hr ' + (m === 'flexible' ? 'flexible' : 'fixed')); }
        else if (m === 'free') parts.push('Direct booking');
        if (s && e) parts.push(fmt(s) + ' to ' + fmt(e));
        else if (libTime(row)) parts.push(LIB.label ? 'within ' + LIB.label : (LIB.allDay ? 'any time' : 'library hours'));
        if (p !== '') parts.push('₹' + Number(p).toLocaleString('en-IN') + ' a month');
        $('[data-summary]', row).textContent = parts.filter(Boolean).join(' · ');
        $('.shift-title', row).textContent = 'Shift ' + ($$('[data-row]', box).indexOf(row) + 1);
    }
    function applyMode(row) {
        var cfg = MODE[mode(row)], hrs = $('.slot_hours', row), typed = hoursTyped(row);
        $('.lbl-start', row).textContent = cfg.start;
        $('.lbl-end', row).textContent = cfg.end;
        // advanced: library hours, locked (an in-use older shift shows its own saved window)
        var lock = libTime(row), own = lock && keepsOwn(row);
        row.classList.toggle('is-libtime', lock);
        $$('.time-req', row).forEach(function (x) { x.hidden = lock; });
        if (lock && !own) { $('.start_time', row).value = ''; $('.end_time', row).value = ''; clearErr(row, 'start_time'); clearErr(row, 'end_time'); }
        $$('[data-libtime]', row).forEach(function (x) {
            var w = x.getAttribute('data-libtime');
            $('span', x).textContent = own ? fmt($('.' + w + '_time', row).value) : libText(w);
        });
        $('.lbl-hours', row).textContent = cfg.hours;
        $('.lbl-price', row).textContent = cfg.price;
        $('.hours-req', row).hidden = !typed;
        hrs.readOnly = !typed;
        hrs.tabIndex = typed ? 0 : -1;
        hrs.placeholder = typed ? 'e.g. 4' : '';
        hrs.closest('.ob-affix').classList.toggle('ob-readonly', !typed);
        slot(row);
        sum(row);
    }
    // Shift name: typed for a custom or advanced shift, otherwise read-only (the shift type is the name)
    function applyType(row) {
        var v = $('.plan-type', row).value, price = $('.price-input', row), name = $('.custom-plan', row), off = v !== '0';
        name.readOnly = off;
        name.tabIndex = off ? -1 : 0;
        name.toggleAttribute('aria-disabled', off);
        name.closest('.ob-field').classList.toggle('is-off', off);
        if (off) { name.value = ''; $('.booking-mode', row).value = 'fixed'; }
        var o = $('.shift-select', row).selectedOptions[0];
        name.placeholder = off ? (o && o.value ? o.textContent : 'Select a shift first') : 'e.g. Morning Batch';
        var vip = v === '11';
        if (vip) price.value = 0;
        price.readOnly = vip;
        price.closest('.ob-affix').classList.toggle('ob-readonly', vip);
        applyMode(row);
    }
    // Shift dropdown → day_type_id + booking_mode: "b:1" Full Day, "b:0" own fixed time, "a:duration" advanced
    function pick(row, value) {
        var was = hoursTyped(row), dt = '', m = 'fixed';
        if (value.indexOf('a:') === 0) { dt = '0'; m = value.slice(2); }
        else if (value.indexOf('b:') === 0) { dt = value.slice(2); }
        $('.plan-type', row).value = dt;
        $('.booking-mode', row).value = m;
        if (hoursTyped(row) !== was) $('.slot_hours', row).value = '';
        clearErr(row, 'day_type_id'); clearErr(row, 'slot_hours');
        applyType(row);
        if (dt === '0' && !$('.custom-plan', row).value) $('.custom-plan', row).focus();
        else if (hoursTyped(row)) $('.slot_hours', row).focus();
    }
    function reindex() {
        var rows = $$('[data-row]', box);
        rows.forEach(function (row, i) {
            $('.ob-shift-no i', row).textContent = i + 1;
            $$('input, select', row).forEach(function (inp) { if (inp.name) inp.name = inp.name.replace(/plan_types\[\d+]/, 'plan_types[' + i + ']'); });
            // delete: not on the only shift or a shift learners are on; + only on the last shift
            $('.remove-plan-row', row).hidden = rows.length === 1 || row.hasAttribute('data-inuse');
            $('.add-after-row', row).hidden = i !== rows.length - 1;
            sum(row);
        });
    }
    $$('[data-row]', box).forEach(applyType);

    box.addEventListener('click', function (e) {
        var add = e.target.closest('.add-after-row');
        if (add) { addShift(add.closest('[data-row]')); return; }
        var rm = e.target.closest('.remove-plan-row');
        if (rm) {
            var r = rm.closest('[data-row]');
            var drop = function () { if (!r.animate) { r.remove(); reindex(); return; } r.animate([{ opacity: 1, transform: 'none' }, { opacity: 0, transform: 'scale(.97)' }], { duration: 220 }).onfinish = function () { r.remove(); reindex(); }; };
            // a saved shift, or one with something filled in, is removed only after a confirm
            var saved = !!rm.getAttribute('data-id');
            var filled = saved || $('.plan-type', r).value !== '' || $('.price-input', r).value !== '' || $('.start_time', r).value !== '';
            if (!filled || !window.lbConfirm) { drop(); return; }
            var name = shiftName(r) || $('.shift-title', r).textContent;
            lbConfirm({
                title: 'Remove ' + name + '?',
                text: saved ? 'This shift and its fee will be deleted when you save this page.' : 'What you filled in for this shift will be cleared.',
                confirm: 'Remove shift'
            }).then(function (ok) { if (ok) drop(); });
        }
    });
    box.addEventListener('input', function (e) {
        var row = e.target.closest('[data-row]'); if (!row) return;
        if (e.target.classList.contains('price-input')) { var v = e.target.value.replace(/\D/g, '').slice(0, 5); if (v !== e.target.value) e.target.value = v; }
        if (e.target.classList.contains('slot_hours') && !e.target.readOnly) { var hv = e.target.value.replace(/[^0-9.]/g, '').slice(0, 4); if (hv !== e.target.value) e.target.value = hv; }
        var m = e.target.name && e.target.name.match(/\[(\w+)\]$/); if (m) clearErr(row, m[1]);
        sum(row);
    });
    box.addEventListener('change', function (e) {
        var row = e.target.closest('[data-row]'); if (!row) return;
        var t = e.target;
        if (t.classList.contains('shift-select')) { pick(row, t.value); return; }
        if (t.classList.contains('start_time')) clearErr(row, 'start_time');
        if (t.classList.contains('end_time')) clearErr(row, 'end_time');
        slot(row); sum(row);
    });

    // A new empty shift always goes at the end (Add shift and the + on the last card), so the
    // shifts already filled in keep their place and number
    $('.add-plan-row').addEventListener('click', function () { var rows = $$('[data-row]', box); addShift(rows[rows.length - 1] || null); });
    function addShift(after) {
        var first = $('[data-row]', box), clone = first.cloneNode(true);
        // the copied time-picker buttons still show the first card's times: the picker builds fresh ones
        $$('.lbt-field', clone).forEach(function (f) { f.remove(); });
        $$('input', clone).forEach(function (i) { i.value = ''; i.readOnly = i.classList.contains('slot_hours'); });
        $$('select', clone).forEach(function (s) { s.selectedIndex = 0; });
        $('.booking-mode', clone).value = 'fixed';
        $$('.ob-err', clone).forEach(function (x) { x.remove(); }); $$('.is-invalid', clone).forEach(function (x) { x.classList.remove('is-invalid'); });
        $('.remove-plan-row', clone).removeAttribute('data-id');
        clone.removeAttribute('data-inuse'); clone.removeAttribute('data-ownwin');
        $('.ob-inuse', clone).hidden = true;
        box.insertBefore(clone, after ? after.nextSibling : null); reindex(); applyType(clone);
        if (clone.animate) clone.animate([{ opacity: 0, transform: 'translateY(12px)' }, { opacity: 1, transform: 'none' }], { duration: 320, easing: 'cubic-bezier(.22,.61,.36,1)' });
        clone.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function fieldHost(row, field) {
        if (field === 'day_type_id' || field === 'booking_mode') return $('.shift-select', row).closest('.ob-field');
        var inp = row.querySelector('[name$="[' + field + ']"]'); return inp ? inp.closest('.ob-field') : row;
    }
    function showErr(row, field, msg) { var h = fieldHost(row, field); h.classList.add('is-invalid'); var p = document.createElement('p'); p.className = 'ob-err'; p.setAttribute('data-f', field); p.textContent = msg; h.appendChild(p); return h; }
    function clearErr(row, field) { $$('.ob-err[data-f="' + field + '"]', row).forEach(function (p) { p.parentElement.classList.remove('is-invalid'); p.remove(); }); }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = $('.ob-submit', form); if (btn.disabled) return;
        $$('.ob-err', form).forEach(function (p) { p.remove(); }); $$('.is-invalid', form).forEach(function (x) { x.classList.remove('is-invalid'); }); alertBox.hidden = true;
        reindex();
        var first = null;
        $$('[data-row]', box).forEach(function (row) {
            var dt = $('.plan-type', row).value, s = $('.start_time', row).value, en = $('.end_time', row).value, p = $('.price-input', row).value;
            if (dt === '') first = first || showErr(row, 'day_type_id', 'Choose a shift.');
            if (dt === '0' && !$('.custom-plan', row).value.trim()) first = first || showErr(row, 'custom_plan_type', 'Give this shift a name.');
            if (libTime(row)) {
                // advanced shifts are booked inside library hours: they need to be set
                if (!LIB.open && !LIB.allDay && LIB.hours) first = first || showErr(row, 'day_type_id', 'Set the library open & close time first: advanced shifts are booked inside it.');
            } else {
                if (!s) first = first || showErr(row, 'start_time', 'Choose a start time.');
                if (!en) first = first || showErr(row, 'end_time', 'Choose an end time.');
                if (s && en && !fitsLib(s, en)) first = first || showErr(row, 'start_time', 'Outside library hours (' + LIB.label + '). Pick a time inside them.');
                else if (s && en && !LIB.allDay && LIB.hours && minutes(row) > LIB.hours * 60) first = first || showErr(row, 'end_time', 'Longer than the ' + LIB.hours + ' hrs the library is open.');
            }
            if (hoursTyped(row)) {
                var hrs = parseFloat($('.slot_hours', row).value), range = minutes(row) / 60, cap = libHours() || 24;
                if (!(hrs > 0)) first = first || showErr(row, 'slot_hours', 'Add the hours per day.');
                else if (hrs > cap) first = first || showErr(row, 'slot_hours', cap >= 24 ? 'A day has 24 hours at most.' : 'The library is open only ' + cap + ' hrs a day.');
                else if (range && hrs > range) first = first || showErr(row, 'slot_hours', 'Must fit in the ' + range + ' hr range.');
            }
            if (p === '') first = first || showErr(row, 'price', 'Add the monthly fee.');
        });
        if (first) { first.scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }

        var label = $('.lb-btn-label', btn), orig = label.textContent;
        btn.disabled = true; btn.classList.add('is-loading'); label.textContent = 'Saving…';
        var body = new URLSearchParams(new FormData(form));
        fetch(form.action, { method: 'POST', body: body, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return { s: r.status, j: j }; }); })
            .then(function (res) {
                if (res.j && res.j.status === true) {
                    window.obToast(res.j.message || 'Shifts saved', 'ok');
                    label.textContent = 'Saved';
                    // a later save stays on this page: the message is shown again after the reload
                    if (!res.j.redirect) { try { sessionStorage.setItem('ob_shifts_saved', res.j.message || 'Shifts saved'); } catch (x) {} }
                    setTimeout(function () { if (res.j.redirect) window.location.href = res.j.redirect; else window.location.reload(); }, 500);
                    return;
                }
                btn.disabled = false; btn.classList.remove('is-loading'); label.textContent = orig;
                var firstHost = null, rows = $$('[data-row]', box);
                if (res.s === 422 && res.j.errors) {
                    Object.keys(res.j.errors).forEach(function (k) {
                        var m = k.match(/plan_types\.(\d+)\.(.+)/); if (!m || !rows[+m[1]]) return;
                        var h = showErr(rows[+m[1]], m[2], 'Shift ' + (+m[1] + 1) + ': ' + (res.j.errors[k][0] || ((LABEL[m[2]] || m[2]) + ' is required')));
                        firstHost = firstHost || h;
                    });
                }
                if (!firstHost) { alertBox.textContent = (res.j && (res.j.message || res.j.error)) || 'Unable to save shifts. Please try again.'; alertBox.hidden = false; firstHost = alertBox; }
                firstHost.scrollIntoView({ behavior: 'smooth', block: 'center' });
            })
            .catch(function () { btn.disabled = false; btn.classList.remove('is-loading'); label.textContent = orig; alertBox.textContent = 'No internet connection. Please try again.'; alertBox.hidden = false; });
    });

    /* "How shifts work": Basic / Advanced tabs; Esc or a click outside closes it */
    var helpBtn = document.getElementById('obHelpBtn'), help = document.getElementById('obHelp');
    var tabs = $$('[role="tab"]', help);
    function helpOpen(on) {
        help.hidden = !on; helpBtn.setAttribute('aria-expanded', on ? 'true' : 'false');
        if (on) { var t = tabs.filter(function (x) { return x.getAttribute('aria-selected') === 'true'; })[0]; if (t) t.focus(); }
    }
    function pickTab(t) {
        tabs.forEach(function (x) {
            var on = x === t;
            x.setAttribute('aria-selected', on ? 'true' : 'false'); x.tabIndex = on ? 0 : -1;
            document.getElementById(x.getAttribute('aria-controls')).hidden = !on;
        });
        t.focus();
    }
    helpBtn.addEventListener('click', function (e) { e.stopPropagation(); helpOpen(help.hidden); });
    $('[data-help-close]', help).addEventListener('click', function () { helpOpen(false); helpBtn.focus(); });
    tabs.forEach(function (t, i) {
        t.addEventListener('click', function () { pickTab(t); });
        t.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') { e.preventDefault(); pickTab(tabs[(i + (e.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length]); }
        });
    });
    document.addEventListener('click', function (e) { if (!help.hidden && !help.contains(e.target) && e.target !== helpBtn) helpOpen(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !help.hidden) { helpOpen(false); helpBtn.focus(); } });
})();
</script>
@endpush
