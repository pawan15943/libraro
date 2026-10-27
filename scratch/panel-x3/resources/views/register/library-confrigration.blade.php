@extends((($iscomp ?? false) && ($isBranch ?? false)) ? 'lb.layouts.onboard-panel' : 'lb.layouts.onboard')

{{--
    Shifts & fees (onboarding). Same backend: POST route('master.configuration.store') (AJAX, form-encoded)
    with plan_types[i][plan_type_id|plan_price_id|day_type_id|custom_plan_type|start_time|end_time|slot_hours|price].
    Success JSON {status:true, message, redirect?}; 422 → errors by plan_types.i.field.
--}}

@section('title', 'Add your shifts')
@section('ob_step', 'shifts')

@php
    $openHours = $total_hour ?? optional($operatingHour)->hour;
    $user = auth()->user();
    $can = fn ($p) => $user && $user->can('has-permission', $p);
    $types = [];
    if ($can('Full Day'))   $types[] = [1, 'Full Day', 'sun', 'Whole day'];
    if ($can('First Half')) $types[] = [2, 'First Half', 'sunrise', 'Morning'];
    if ($can('Second Half'))$types[] = [3, 'Second Half', 'sunset', 'Evening'];
    if ((string) $openHours === '24') {
        if ($can('All Day'))    $types[] = [8, 'All Day', 'clock-3', '24 hours'];
        if ($can('Full Night')) $types[] = [9, 'Full Night', 'moon', 'Night'];
    }
    if ($can('Custom Plan')) $types[] = [0, 'Custom', 'pen-line', 'Your own'];
    $types[] = [10, 'Reserved', 'lock', 'Fixed seat'];
    $types[] = [11, 'VIP', 'star', 'Free / special'];

    $rows = $planTypes->isNotEmpty() ? $planTypes : collect([(object) ['id' => null, 'day_type_id' => null, 'name' => null, 'start_time' => null, 'end_time' => null, 'slot_hours' => null, 'price' => null]]);
    $hm = fn ($t) => $t ? substr($t, 0, 5) : '';
@endphp

@section('ob_content')
<div class="ob-wrap ob-wrap--narrow">
    <header class="ob-head ob-head--left">
        <span class="ob-eyebrow ob-eyebrow--step"><x-lb-icon name="badge-check" />Branch saved · Step 4 of 5</span>
        <h1>Add your shifts &amp; fees.</h1>
        <p>Shifts are the time slots learners can book, each with its own monthly fee. Add at least one.</p>
    </header>

    @if($openHours)
        <div class="ob-hours">
            <i><x-lb-icon name="clock-3" /></i>
            <div><b>Your library is open {{ $openHours }} hours a day</b><span>Each shift must fit inside these opening hours.</span></div>
        </div>
    @endif

    <form method="POST" action="{{ route('master.configuration.store') }}" id="configure" autocomplete="off" novalidate>
        @csrf
        <div class="ob-alert" id="ob-form-alert" role="alert" hidden>{{ session('error') }}</div>

        <div id="planRowContainer" class="ob-shifts">
            @foreach($rows as $index => $row)
                @php $dt = $row->day_type_id === null ? '' : (string) $row->day_type_id; @endphp
                <section class="ob-card ob-shift plan-row-wrapper" data-row>
                    <div class="ob-shift-head">
                        <span class="ob-shift-no"><i>{{ $index + 1 }}</i><b class="shift-title">Shift {{ $index + 1 }}</b></span>
                        <span class="ob-shift-sum" data-summary></span>
                        <button type="button" class="ob-iconbtn remove-plan-row" aria-label="Remove this shift" data-id="{{ $row->id ?? '' }}" @if($index === 0) hidden @endif><x-lb-icon name="trash" /></button>
                    </div>

                    <input type="hidden" name="plan_types[{{ $index }}][plan_type_id]" value="{{ $row->id ?? '' }}">
                    <input type="hidden" name="plan_types[{{ $index }}][plan_price_id]" value="{{ $row->price->id ?? '' }}">
                    <input type="hidden" name="plan_types[{{ $index }}][day_type_id]" value="{{ $dt }}" class="plan-type">

                    <div class="ob-field ob-field--full">
                        <span class="ob-label">Shift type <em>*</em></span>
                        <div class="ob-typegrid" role="radiogroup" aria-label="Shift type" data-types>
                            @foreach($types as [$id, $label, $icon, $hint])
                                <button type="button" role="radio" data-v="{{ $id }}" aria-checked="{{ $dt !== '' && (string) $id === $dt ? 'true' : 'false' }}"><i><x-lb-icon :name="$icon" /></i><b>{{ $label }}</b><small>{{ $hint }}</small></button>
                            @endforeach
                        </div>
                    </div>

                    <div class="ob-field ob-field--full ob-custom" @if($dt !== '0') hidden @endif>
                        <label>Custom shift name <em>*</em></label>
                        <input type="text" name="plan_types[{{ $index }}][custom_plan_type]" value="{{ $dt === '0' ? $row->name : '' }}" class="custom-plan" maxlength="100" placeholder="e.g. Early Morning">
                    </div>

                    <div class="ob-fields ob-fields--3">
                        <div class="ob-field">
                            <label>Starts at <em>*</em></label>
                            <input type="time" name="plan_types[{{ $index }}][start_time]" value="{{ $hm($row->start_time) }}" class="start_time" step="900">
                        </div>
                        <div class="ob-field">
                            <label>Ends at <em>*</em></label>
                            <input type="time" name="plan_types[{{ $index }}][end_time]" value="{{ $hm($row->end_time) }}" class="end_time" step="900">
                        </div>
                        <div class="ob-field">
                            <label>Duration</label>
                            <div class="ob-affix ob-affix--end ob-readonly"><input type="text" name="plan_types[{{ $index }}][slot_hours]" value="{{ $row->slot_hours ?? '' }}" class="slot_hours" readonly tabindex="-1" placeholder="—"><span>hours</span></div>
                        </div>
                    </div>

                    <div class="ob-field ob-field--price">
                        <label>Monthly fee <em>*</em></label>
                        <div class="ob-affix ob-affix--big"><span>₹</span><input type="text" inputmode="numeric" maxlength="5" name="plan_types[{{ $index }}][price]" value="{{ $row->price->price ?? '' }}" class="price-input digit-only" placeholder="e.g. 800"><span class="ob-affix-end">/ month</span></div>
                        <small class="ob-vip-note" hidden>VIP seats are free (₹0).</small>
                    </div>
                </section>
            @endforeach
        </div>

        <button type="button" class="ob-addbtn ob-addbtn--big add-plan-row"><x-lb-icon name="plus" />Add another shift</button>

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
    var LABEL = { day_type_id: 'Shift type', custom_plan_type: 'Custom shift name', start_time: 'Start time', end_time: 'End time', slot_hours: 'Duration', price: 'Monthly fee' };

    function slot(row) {
        var s = $('.start_time', row).value, e = $('.end_time', row).value, out = $('.slot_hours', row);
        if (!s || !e) { out.value = ''; return; }
        var a = s.split(':'), b = e.split(':'), d = (+b[0] * 60 + +b[1]) - (+a[0] * 60 + +a[1]);
        if (d <= 0) d += 1440;
        out.value = Math.floor(d / 60);
    }
    function fmt(t) { if (!t) return ''; var p = t.split(':'), h = +p[0], m = p[1]; return (h % 12 || 12) + (m !== '00' ? ':' + m : '') + (h < 12 ? ' AM' : ' PM'); }
    function sum(row) {
        var on = $('[data-types] [aria-checked="true"] b', row), s = $('.start_time', row).value, e = $('.end_time', row).value, p = $('.price-input', row).value;
        var parts = [];
        if (on) parts.push(on.textContent === 'Custom' ? ($('.custom-plan', row).value || 'Custom') : on.textContent);
        if (s && e) parts.push(fmt(s) + ' – ' + fmt(e));
        if (p !== '') parts.push('₹' + Number(p).toLocaleString('en-IN') + '/mo');
        $('[data-summary]', row).textContent = parts.join(' · ');
    }
    function applyType(row) {
        var v = $('.plan-type', row).value, custom = $('.ob-custom', row), price = $('.price-input', row), note = $('.ob-vip-note', row);
        custom.hidden = v !== '0';
        if (v !== '0') $('.custom-plan', row).value = '';
        if (v === '11') { price.value = 0; price.readOnly = true; note.hidden = false; } else { price.readOnly = false; note.hidden = true; }
        sum(row);
    }
    function reindex() {
        var rows = $$('[data-row]', box);
        rows.forEach(function (row, i) {
            $('.shift-title', row).textContent = 'Shift ' + (i + 1); $('.ob-shift-no i', row).textContent = i + 1;
            $$('input', row).forEach(function (inp) { if (inp.name) inp.name = inp.name.replace(/plan_types\[\d+]/, 'plan_types[' + i + ']'); });
            $('.remove-plan-row', row).hidden = rows.length === 1;
        });
    }
    function wire(row) { slot(row); applyType(row); }
    $$('[data-row]', box).forEach(wire);

    box.addEventListener('click', function (e) {
        var t = e.target.closest('[data-types] [data-v]');
        if (t) { var row = t.closest('[data-row]'); $$('[data-types] [data-v]', row).forEach(function (x) { x.setAttribute('aria-checked', x === t ? 'true' : 'false'); }); $('.plan-type', row).value = t.getAttribute('data-v'); clearErr(row, 'day_type_id'); applyType(row); if (t.getAttribute('data-v') === '0') $('.custom-plan', row).focus(); return; }
        var rm = e.target.closest('.remove-plan-row');
        if (rm) { var r = rm.closest('[data-row]'); if (!rm.closest('[data-row]').animate) { r.remove(); reindex(); return; } r.animate([{ opacity: 1, transform: 'none' }, { opacity: 0, transform: 'scale(.97)' }], { duration: 220 }).onfinish = function () { r.remove(); reindex(); }; }
    });
    box.addEventListener('input', function (e) {
        var row = e.target.closest('[data-row]'); if (!row) return;
        if (e.target.classList.contains('price-input')) { var v = e.target.value.replace(/\D/g, '').slice(0, 5); if (v !== e.target.value) e.target.value = v; }
        if (e.target.classList.contains('start_time') || e.target.classList.contains('end_time')) slot(row);
        var m = e.target.name && e.target.name.match(/\[(\w+)\]$/); if (m) clearErr(row, m[1]);
        sum(row);
    });
    box.addEventListener('change', function (e) { var row = e.target.closest('[data-row]'); if (row) { slot(row); sum(row); } });

    $('.add-plan-row').addEventListener('click', function () {
        var first = $('[data-row]', box), clone = first.cloneNode(true);
        $$('input', clone).forEach(function (i) { i.value = ''; i.readOnly = i.classList.contains('slot_hours'); });
        $$('[data-types] [data-v]', clone).forEach(function (x) { x.setAttribute('aria-checked', 'false'); });
        $$('.ob-err', clone).forEach(function (x) { x.remove(); }); $$('.is-invalid', clone).forEach(function (x) { x.classList.remove('is-invalid'); });
        $('.remove-plan-row', clone).removeAttribute('data-id');
        box.appendChild(clone); reindex(); wire(clone);
        if (clone.animate) clone.animate([{ opacity: 0, transform: 'translateY(12px)' }, { opacity: 1, transform: 'none' }], { duration: 320, easing: 'cubic-bezier(.22,.61,.36,1)' });
        clone.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    function fieldHost(row, field) {
        if (field === 'day_type_id') return $('[data-types]', row).closest('.ob-field');
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
            if (!s) first = first || showErr(row, 'start_time', 'Add a start time.');
            if (!en) first = first || showErr(row, 'end_time', 'Add an end time.');
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
