@extends((($iscomp ?? false) && ($isBranch ?? false)) ? 'lb.layouts.onboard-panel' : 'lb.layouts.onboard')

{{--
    Shifts & fees (onboarding). Same backend: POST route('master.configuration.store') (AJAX, form-encoded)
    with plan_types[i][plan_type_id|plan_price_id|day_type_id|custom_plan_type|booking_mode|start_time|end_time|slot_hours|price].
    booking_mode (custom shifts only, docs/ADVANCED_SHIFTS.md): fixed | duration | free | flexible.
    Success JSON {status:true, message, redirect?}; 422 → errors by plan_types.i.field.
    Each shift card is a plain two-column form: Category (Basic / Advanced), Shift type or Shift
    system, Shift name (custom and advanced), Start, End, Duration, Monthly fee.
--}}

@section('title', 'Add your shifts')
@section('ob_step', 'shifts')

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

    $rows = $planTypes->isNotEmpty() ? $planTypes : collect([(object) ['id' => null, 'day_type_id' => null, 'name' => null, 'booking_mode' => null, 'start_time' => null, 'end_time' => null, 'slot_hours' => null, 'price' => null]]);
    $hm = fn ($t) => $t ? substr($t, 0, 5) : '';

    // Advanced shifts: how the shift is booked (docs/ADVANCED_SHIFTS.md)
    $advModes = ['duration' => 'Fixed hours', 'free' => 'Free timing', 'flexible' => 'Flexible'];

    // Time picker: a plain list of times every 15 minutes, written the way people say them
    $timeOptions = function ($selected) {
        $html = '<option value="">Select time</option>';
        $found = false;
        for ($m = 0; $m < 1440; $m += 15) {
            $value = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
            $h = intdiv($m, 60);
            $label = (($h % 12) ?: 12) . ':' . sprintf('%02d', $m % 60) . ($h < 12 ? ' AM' : ' PM');
            $isSel = $value === $selected;
            $found = $found || $isSel;
            $html .= '<option value="' . $value . '"' . ($isSel ? ' selected' : '') . '>' . $label . '</option>';
        }
        if ($selected && !$found) {
            $html .= '<option value="' . e($selected) . '" selected>' . e($selected) . '</option>';
        }
        return $html;
    };
@endphp

@section('ob_content')
<div class="ob-wrap ob-wrap--shifts">
    <header class="ob-head ob-head--left">
        <span class="ob-eyebrow ob-eyebrow--step"><x-lb-icon name="badge-check" />Branch saved · Step 4 of 5</span>
        <h1>Add your shifts &amp; fees.</h1>
        <p>Shifts are the time slots learners can book, each with its own monthly fee. Add at least one.</p>
    </header>

    @if($openHours)
        <div class="ob-hours">
            <i><x-lb-icon name="clock-3" /></i>
            <div><b>Operating hours: {{ $openHours }} hours a day</b><span>Shift timings must fit inside these hours.</span></div>
        </div>
    @endif

    <form method="POST" action="{{ route('master.configuration.store') }}" id="configure" autocomplete="off" novalidate>
        @csrf
        <div class="ob-alert" id="ob-form-alert" role="alert" hidden>{{ session('error') }}</div>

        <div class="ob-shifts-bar">
            <div><b>Your shifts</b><span>Basic: normal shift with a fixed time. Advanced: fixed hours, free timing or flexible.</span></div>
            <button type="button" class="ob-addbtn add-plan-row"><x-lb-icon name="plus" />Add shift</button>
        </div>

        <div id="planRowContainer" class="ob-shifts ob-shifts--grid">
            @foreach($rows as $index => $row)
                @php
                    $dt = $row->day_type_id === null ? '' : (string) $row->day_type_id;
                    $mode = $dt === '0' && in_array($row->booking_mode ?? null, ['duration', 'free', 'flexible'], true) ? $row->booking_mode : 'fixed';
                    $cat = $mode === 'fixed' ? 'basic' : 'advanced';
                @endphp
                <section class="ob-card ob-shift plan-row-wrapper" data-row data-cat="{{ $cat }}">
                    <div class="ob-shift-head">
                        <span class="ob-shift-no"><i>{{ $index + 1 }}</i></span>
                        <span class="ob-shift-name"><b class="shift-title">Shift {{ $index + 1 }}</b><span class="ob-shift-sum" data-summary></span></span>
                        <button type="button" class="ob-iconbtn ob-iconbtn--add add-after-row" aria-label="Add a shift below this one" title="Add a shift below"><x-lb-icon name="plus" /></button>
                        <button type="button" class="ob-iconbtn ob-iconbtn--del remove-plan-row" aria-label="Remove this shift" title="Remove this shift" data-id="{{ $row->id ?? '' }}" @if($index === 0 && $rows->count() === 1) hidden @endif><x-lb-icon name="trash" /></button>
                    </div>

                    <input type="hidden" name="plan_types[{{ $index }}][plan_type_id]" value="{{ $row->id ?? '' }}">
                    <input type="hidden" name="plan_types[{{ $index }}][plan_price_id]" value="{{ $row->price->id ?? '' }}">
                    <input type="hidden" name="plan_types[{{ $index }}][day_type_id]" value="{{ $dt }}" class="plan-type">
                    <input type="hidden" name="plan_types[{{ $index }}][booking_mode]" value="{{ $mode }}" class="booking-mode">

                    <div class="ob-form2">
                        <div class="ob-field">
                            <label>Category <em>*</em></label>
                            <select class="cat-select ob-select">
                                <option value="basic" {{ $cat === 'basic' ? 'selected' : '' }}>Basic</option>
                                <option value="advanced" {{ $cat === 'advanced' ? 'selected' : '' }}>Advanced</option>
                            </select>
                        </div>
                        <div class="ob-field ob-basic" @if($cat !== 'basic') hidden @endif>
                            <label>Shift type <em>*</em></label>
                            <select class="type-select ob-select">
                                <option value="">Select shift</option>
                                @foreach($types as [$id, $label])
                                    <option value="{{ $id }}" {{ $cat === 'basic' && $dt !== '' && (string) $id === $dt ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ob-field ob-adv" @if($cat !== 'advanced') hidden @endif>
                            <label>Shift system <em>*</em></label>
                            <select class="mode-select ob-select">
                                @foreach($advModes as $mv => $mlabel)
                                    <option value="{{ $mv }}" {{ $mode === $mv ? 'selected' : '' }}>{{ $mlabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ob-field ob-field--full ob-custom" @if($dt !== '0') hidden @endif>
                            <label>Shift name <em>*</em></label>
                            <input type="text" name="plan_types[{{ $index }}][custom_plan_type]" value="{{ $dt === '0' ? $row->name : '' }}" class="custom-plan" maxlength="100" placeholder="e.g. Morning Batch">
                        </div>
                        <div class="ob-field">
                            <label><span class="lbl-start">Start time</span> <em>*</em></label>
                            <select name="plan_types[{{ $index }}][start_time]" class="start_time ob-select ob-time">{!! $timeOptions($hm($row->start_time)) !!}</select>
                        </div>
                        <div class="ob-field">
                            <label><span class="lbl-end">End time</span> <em>*</em></label>
                            <select name="plan_types[{{ $index }}][end_time]" class="end_time ob-select ob-time">{!! $timeOptions($hm($row->end_time)) !!}</select>
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
                    <p class="ob-mode-hint" hidden></p>
                    <small class="ob-vip-note" hidden>VIP seats are free, so the fee is ₹0.</small>
                </section>
            @endforeach
        </div>

        <div class="ob-finish">
            <div><b>All set?</b><span>You can edit shifts and fees any time from Settings.</span></div>
            <button class="lb-btn lb-btn--lg ob-submit" type="submit"><span class="lb-btn-label">Save &amp; go to dashboard</span><x-lb-icon name="arrow-right" size="18" /></button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';
    var form = document.getElementById('configure'), box = document.getElementById('planRowContainer'), alertBox = document.getElementById('ob-form-alert');
    var $ = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
    if (alertBox.textContent.trim()) alertBox.hidden = false;
    var LABEL = { day_type_id: 'Shift type', custom_plan_type: 'Shift name', booking_mode: 'Shift system', start_time: 'Start time', end_time: 'End time', slot_hours: 'Hours', price: 'Monthly fee' };
    // Per booking mode: field labels and a one-line hint (docs/ADVANCED_SHIFTS.md)
    var MODE = {
        fixed:    { start: 'Start time', end: 'End time', hours: 'Duration', price: 'Monthly fee', hint: '' },
        duration: { start: 'Usable from', end: 'Usable till', hours: 'Hours per day', price: 'Monthly fee', hint: 'Numbered seat only. When booking, you choose the start time and the shift ends after these hours.' },
        free:     { start: 'Usable from', end: 'Usable till', hours: 'Range', price: 'Default monthly fee', hint: 'Numbered seat only. When booking, you type the exact time and fee. This fee is only the default.' },
        flexible: { start: 'Usable from', end: 'Usable till', hours: 'Hours per day', price: 'Monthly fee', hint: 'General seat only. The learner can come any time between these times and stay these hours.' }
    };

    function cat(row) { return row.getAttribute('data-cat') === 'advanced' ? 'advanced' : 'basic'; }
    function minutes(row) {
        var s = $('.start_time', row).value, e = $('.end_time', row).value;
        if (!s || !e) return 0;
        var a = s.split(':'), b = e.split(':'), d = (+b[0] * 60 + +b[1]) - (+a[0] * 60 + +a[1]);
        return d <= 0 ? d + 1440 : d;
    }
    function mode(row) { return $('.plan-type', row).value === '0' ? ($('.booking-mode', row).value || 'fixed') : 'fixed'; }
    function hoursTyped(row) { var m = mode(row); return m === 'duration' || m === 'flexible'; }
    function slot(row) {
        if (hoursTyped(row)) return;
        var d = minutes(row), out = $('.slot_hours', row);
        out.value = d ? Math.round(d / 60 * 100) / 100 : '';
    }
    function fmt(t) { if (!t) return ''; var p = t.split(':'), h = +p[0], m = p[1]; return (h % 12 || 12) + (m !== '00' ? ':' + m : '') + (h < 12 ? ' AM' : ' PM'); }
    function shiftName(row) {
        if ($('.plan-type', row).value === '0') return $('.custom-plan', row).value.trim();
        var o = $('.type-select', row).selectedOptions[0];
        return o && o.value ? o.textContent : '';
    }
    // Card head: "Shift 1" and one line with the name, time and fee
    function sum(row) {
        var s = $('.start_time', row).value, e = $('.end_time', row).value, p = $('.price-input', row).value, m = mode(row), h = $('.slot_hours', row).value;
        var parts = [shiftName(row)];
        if (m === 'duration' || m === 'flexible') { if (h) parts.push(h + ' hr ' + (m === 'flexible' ? 'flexible' : 'fixed')); }
        else if (m === 'free') parts.push('Free timing');
        if (s && e) parts.push((m === 'fixed' ? '' : 'within ') + fmt(s) + ' to ' + fmt(e));
        if (p !== '') parts.push('₹' + Number(p).toLocaleString('en-IN') + ' a month');
        $('[data-summary]', row).textContent = parts.filter(Boolean).join(' · ');
        $('.shift-title', row).textContent = 'Shift ' + ($$('[data-row]', box).indexOf(row) + 1);
    }
    function applyMode(row) {
        var m = mode(row), cfg = MODE[m], hrs = $('.slot_hours', row), typed = hoursTyped(row);
        if (m !== 'fixed') $('.mode-select', row).value = m;
        $('.lbl-start', row).textContent = cfg.start;
        $('.lbl-end', row).textContent = cfg.end;
        $('.lbl-hours', row).textContent = cfg.hours;
        $('.lbl-price', row).textContent = cfg.price;
        $('.hours-req', row).hidden = !typed;
        hrs.readOnly = !typed;
        hrs.tabIndex = typed ? 0 : -1;
        hrs.placeholder = typed ? 'e.g. 4' : '';
        hrs.closest('.ob-affix').classList.toggle('ob-readonly', !typed);
        var hint = $('.ob-mode-hint', row);
        hint.textContent = cfg.hint;
        hint.hidden = !cfg.hint;
        slot(row);
        sum(row);
    }
    function applyType(row) {
        var v = $('.plan-type', row).value, price = $('.price-input', row), note = $('.ob-vip-note', row);
        $('.ob-basic', row).hidden = cat(row) !== 'basic';
        $('.ob-adv', row).hidden = cat(row) !== 'advanced';
        $('.ob-custom', row).hidden = v !== '0';
        if (v !== '0') { $('.custom-plan', row).value = ''; $('.booking-mode', row).value = 'fixed'; }
        else if (cat(row) === 'basic') { $('.booking-mode', row).value = 'fixed'; }
        if (v === '11') { price.value = 0; price.readOnly = true; note.hidden = false; } else { price.readOnly = false; note.hidden = true; }
        applyMode(row);
    }
    // Basic: a normal shift from Shift type (Custom = own fixed time). Advanced: own name + Shift system.
    function setCat(row, c) {
        row.setAttribute('data-cat', c);
        $('.cat-select', row).value = c;
        if (c === 'advanced') {
            // remember the Basic choice so switching back restores it
            row.setAttribute('data-basic-type', $('.plan-type', row).value);
            row.setAttribute('data-basic-name', $('.custom-plan', row).value);
            $('.plan-type', row).value = '0';
            $('.booking-mode', row).value = $('.mode-select', row).value || 'duration';
        } else {
            var back = row.getAttribute('data-basic-type');
            if (back !== null) {
                $('.plan-type', row).value = back;
                $('.type-select', row).value = back;
                $('.custom-plan', row).value = back === '0' ? (row.getAttribute('data-basic-name') || '') : '';
            }
            $('.type-select', row).value = $('.plan-type', row).value;
            $('.booking-mode', row).value = 'fixed';
        }
        $('.slot_hours', row).value = '';
        clearErr(row, 'day_type_id');
        applyType(row);
    }
    function reindex() {
        var rows = $$('[data-row]', box);
        rows.forEach(function (row, i) {
            $('.ob-shift-no i', row).textContent = i + 1;
            $$('input, select', row).forEach(function (inp) { if (inp.name) inp.name = inp.name.replace(/plan_types\[\d+]/, 'plan_types[' + i + ']'); });
            $('.remove-plan-row', row).hidden = rows.length === 1;
            sum(row);
        });
    }
    $$('[data-row]', box).forEach(applyType);

    box.addEventListener('click', function (e) {
        var add = e.target.closest('.add-after-row');
        if (add) { addShift(add.closest('[data-row]')); return; }
        var rm = e.target.closest('.remove-plan-row');
        if (rm) { var r = rm.closest('[data-row]'); if (!r.animate) { r.remove(); reindex(); return; } r.animate([{ opacity: 1, transform: 'none' }, { opacity: 0, transform: 'scale(.97)' }], { duration: 220 }).onfinish = function () { r.remove(); reindex(); }; }
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
        if (t.classList.contains('cat-select')) { setCat(row, t.value); if (t.value === 'advanced') $('.custom-plan', row).focus(); return; }
        if (t.classList.contains('type-select')) { $('.plan-type', row).value = t.value; clearErr(row, 'day_type_id'); applyType(row); if (t.value === '0') $('.custom-plan', row).focus(); return; }
        if (t.classList.contains('mode-select')) {
            var was = hoursTyped(row);
            $('.booking-mode', row).value = t.value;
            if (hoursTyped(row) !== was) $('.slot_hours', row).value = '';
            clearErr(row, 'slot_hours');
            applyMode(row);
            if (hoursTyped(row)) $('.slot_hours', row).focus();
            return;
        }
        if (t.classList.contains('start_time')) clearErr(row, 'start_time');
        if (t.classList.contains('end_time')) clearErr(row, 'end_time');
        slot(row); sum(row);
    });

    // A new empty shift: at the top (Add shift button) or right below a card (its + icon)
    $('.add-plan-row').addEventListener('click', function () { addShift(null); });
    function addShift(after) {
        var first = $('[data-row]', box), clone = first.cloneNode(true);
        $$('input', clone).forEach(function (i) { i.value = ''; i.readOnly = i.classList.contains('slot_hours'); });
        $$('select', clone).forEach(function (s) { s.selectedIndex = 0; });
        $('.mode-select', clone).value = 'duration';
        $('.booking-mode', clone).value = 'fixed';
        clone.setAttribute('data-cat', 'basic');
        clone.removeAttribute('data-basic-type');
        clone.removeAttribute('data-basic-name');
        $$('.ob-err', clone).forEach(function (x) { x.remove(); }); $$('.is-invalid', clone).forEach(function (x) { x.classList.remove('is-invalid'); });
        $('.remove-plan-row', clone).removeAttribute('data-id');
        box.insertBefore(clone, after ? after.nextSibling : box.firstChild); reindex(); applyType(clone);
        if (clone.animate) clone.animate([{ opacity: 0, transform: 'translateY(12px)' }, { opacity: 1, transform: 'none' }], { duration: 320, easing: 'cubic-bezier(.22,.61,.36,1)' });
        clone.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function fieldHost(row, field) {
        if (field === 'day_type_id') return $('.type-select', row).closest('.ob-field');
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
            if (dt === '') first = first || showErr(row, 'day_type_id', 'Choose a shift type.');
            if (dt === '0' && !$('.custom-plan', row).value.trim()) first = first || showErr(row, 'custom_plan_type', 'Give this shift a name.');
            if (!s) first = first || showErr(row, 'start_time', 'Choose a start time.');
            if (!en) first = first || showErr(row, 'end_time', 'Choose an end time.');
            if (hoursTyped(row)) {
                var hrs = parseFloat($('.slot_hours', row).value), range = minutes(row) / 60;
                if (!(hrs > 0)) first = first || showErr(row, 'slot_hours', 'Add the hours per day.');
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
})();
</script>
@endpush
