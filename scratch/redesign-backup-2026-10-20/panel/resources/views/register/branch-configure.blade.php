@extends('lb.layouts.onboard')

{{--
    Branch setup (onboarding). Same backend: POST route('branch.configure') via AJAX (multipart),
    same field names as before: name, founder_day, email, mobile, upi_id, seats, hour, locker_amount,
    extend_days, token_money, fixed_billing_date, plans[], monthdays, floors[i][name|from|to], library_logo.
    Success JSON {status:true, redirect}; 422 → field errors; 400 → message.
--}}

@section('title', 'Set up your branch')
@section('ob_step', 'branch')

@php
    $lib = getLibrary();
    $canAllDay = auth()->user() && auth()->user()->can('has-permission', 'All Day');
    $oldHour = old('hour', isset($hour) ? $hour->hour : '');
    $oldPlans = old('plans', ['1 MONTH']);
    $showPlans = ($branchCount ?? 0) == 0;
@endphp

@section('content')
<div class="ob-wrap">
    <header class="ob-head ob-head--left">
        <span class="ob-eyebrow"><x-lb-icon name="badge-check" />Payment done · Step 3 of 5</span>
        <h1>Set up your branch.</h1>
        <p>Tell us about your library. This takes about 2 minutes, and you can change most of it later.</p>
    </header>

    <form action="{{ route('branch.configure') }}" method="POST" enctype="multipart/form-data" id="branchUpdate" class="ob-form" novalidate>
        @csrf
        @if(isset($branch)) @method('PUT') @endif
        <div class="ob-alert" id="ob-form-alert" role="alert" hidden></div>

        <div class="ob-grid">
            <div class="ob-col">
                {{-- 1. Basics --}}
                <section class="ob-card" aria-labelledby="s1">
                    <div class="ob-card-head"><i>1</i><div><h2 id="s1">Library details</h2><p>How students and your staff will know this branch.</p></div></div>
                    <div class="ob-fields">
                        <div class="ob-field ob-field--full">
                            <label for="name">Branch name <em>*</em></label>
                            <input type="text" id="name" name="name" value="{{ old('name', $branch->name ?? optional($lib)->library_name ?? '') }}" placeholder="e.g. Shiv Study Point – Talwandi" maxlength="255" {{ isset($branch) ? 'readonly' : '' }} required>
                            <small>For your records. This can't be changed later.</small>
                        </div>
                        <div class="ob-field">
                            <label for="email">Email <em>*</em></label>
                            <input type="email" id="email" name="email" value="{{ old('email', $branch->email ?? optional($lib)->email ?? '') }}" placeholder="you@example.com" autocomplete="email" required>
                        </div>
                        <div class="ob-field">
                            <label for="mobile">WhatsApp number <em>*</em></label>
                            <div class="ob-affix"><span>+91</span><input type="tel" id="mobile" name="mobile" class="digit-only" inputmode="numeric" maxlength="10" value="{{ old('mobile', $branch->mobile ?? optional($lib)->library_mobile ?? '') }}" placeholder="10-digit number" required></div>
                        </div>
                        <div class="ob-field">
                            <label for="founder_day">Library started on <em>*</em></label>
                            <input type="date" id="founder_day" name="founder_day" value="{{ old('founder_day', $branch->founder_day ?? '') }}" max="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="ob-field">
                            <label for="upi_id">UPI ID <span>optional</span></label>
                            <input type="text" id="upi_id" name="upi_id" value="{{ old('upi_id', $branch->upi_id ?? '') }}" placeholder="yourname@upi" autocomplete="off">
                            <small>Lets students pay online when they book a seat.</small>
                        </div>
                    </div>
                </section>

                {{-- 2. Seats & timing --}}
                <section class="ob-card" aria-labelledby="s2">
                    <div class="ob-card-head"><i>2</i><div><h2 id="s2">Seats &amp; timing</h2><p>Your capacity and how long the library is open each day.</p></div></div>
                    <div class="ob-fields">
                        <div class="ob-field">
                            <label for="seats">Total seats <em>*</em></label>
                            <div class="ob-stepper" data-stepper>
                                <button type="button" data-step="-1" aria-label="Fewer seats"><x-lb-icon name="minus" /></button>
                                <input type="text" id="seats" name="seats" class="digit-only" inputmode="numeric" maxlength="4" value="{{ old('seats') }}" placeholder="e.g. 60" required>
                                <button type="button" data-step="1" aria-label="More seats"><x-lb-icon name="plus" /></button>
                            </div>
                        </div>
                        <div class="ob-field">
                            <label for="locker_amount">Locker fee (₹ / month) <em>*</em></label>
                            <div class="ob-affix"><span>₹</span><input type="text" id="locker_amount" name="locker_amount" class="digit-only" inputmode="numeric" maxlength="4" value="{{ old('locker_amount', '0') }}" placeholder="0" required></div>
                            <small>Keep 0 if you don't offer lockers.</small>
                        </div>
                        <div class="ob-field ob-field--full">
                            <span class="ob-label" id="hour-label">Open for how many hours a day? <em>*</em></span>
                            <div class="ob-chips" role="radiogroup" aria-labelledby="hour-label" data-radio="hour">
                                @foreach(range(10, $canAllDay ? 24 : 23) as $h)
                                    <button type="button" role="radio" data-v="{{ $h }}" aria-checked="{{ (string) $oldHour === (string) $h ? 'true' : 'false' }}">{{ $h === 24 ? '24 hrs' : $h . ' hrs' }}</button>
                                @endforeach
                            </div>
                            <input type="hidden" name="hour" id="hour" value="{{ $oldHour }}">
                        </div>
                    </div>
                </section>

                {{-- 3. Billing rules --}}
                <section class="ob-card" aria-labelledby="s3">
                    <div class="ob-card-head"><i>3</i><div><h2 id="s3">Billing rules</h2><p>How renewals and dues work in your library.</p></div></div>
                    <div class="ob-fields">
                        <div class="ob-field">
                            <label for="extend_days">Grace days after expiry <em>*</em></label>
                            <div class="ob-affix ob-affix--end"><input type="text" id="extend_days" name="extend_days" class="digit-only no-validate" inputmode="numeric" maxlength="4" value="{{ old('extend_days', '0') }}" placeholder="0" required><span>days</span></div>
                            <small>Days a learner can keep the seat after the plan ends.</small>
                        </div>
                        <div class="ob-field">
                            <label for="token_money">Token money <span>optional</span></label>
                            <div class="ob-affix"><span>₹</span><input type="text" id="token_money" name="token_money" class="digit-only no-validate" inputmode="numeric" maxlength="4" value="{{ old('token_money') }}" placeholder="e.g. 200"></div>
                            <small>Advance a learner pays to hold a seat.</small>
                        </div>
                        <div class="ob-field">
                            <label for="fixed_billing_date">Fixed billing date <span>optional</span></label>
                            <div class="ob-affix ob-affix--end"><input type="text" id="fixed_billing_date" name="fixed_billing_date" class="digit-only no-validate" inputmode="numeric" maxlength="2" value="{{ old('fixed_billing_date') }}" placeholder="e.g. 1"><span>of month</span></div>
                            <small>Leave empty if each learner pays on their own join date.</small>
                        </div>
                    </div>
                </section>

                {{-- 4. Plans --}}
                @if($showPlans)
                <section class="ob-card" aria-labelledby="s4">
                    <div class="ob-card-head"><i>4</i><div><h2 id="s4">Plans you offer</h2><p>Pick every duration a learner can buy. <b>1 Month</b> is required.</p></div></div>
                    <div class="ob-plangroups" id="ob-plans">
                        @foreach(['Months' => ['MONTH', 12], 'Weeks' => ['WEEK', 4], 'Days' => ['DAY', 31]] as $g => [$unit, $max])
                            <div class="ob-plangroup">
                                <span class="ob-label">{{ $g }}</span>
                                <div class="ob-chips ob-chips--multi">
                                    @for($i = 1; $i <= $max; $i++)
                                        @php $v = $i . ' ' . $unit; $req = $v === '1 MONTH'; @endphp
                                        <button type="button" data-plan="{{ $v }}" aria-pressed="{{ in_array($v, $oldPlans, true) || $req ? 'true' : 'false' }}" @if($req) data-locked title="Required" @endif>{{ $i }} {{ strtolower($unit) }}{{ $i > 1 ? 's' : '' }}@if($req)<x-lb-icon name="lock" />@endif</button>
                                    @endfor
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="ob-plan-inputs" id="ob-plan-inputs"></div>
                    <div class="ob-field ob-field--inline">
                        <span class="ob-label" id="md-label">A "1 month" plan lasts</span>
                        <div class="ob-seg" role="radiogroup" aria-labelledby="md-label" data-radio="monthdays">
                            <button type="button" role="radio" data-v="" aria-checked="{{ old('monthdays', '') === '' ? 'true' : 'false' }}">Calendar month</button>
                            <button type="button" role="radio" data-v="30" aria-checked="{{ old('monthdays') == 30 ? 'true' : 'false' }}">30 days</button>
                            <button type="button" role="radio" data-v="28" aria-checked="{{ old('monthdays') == 28 ? 'true' : 'false' }}">28 days</button>
                        </div>
                        <input type="hidden" name="monthdays" id="monthdays" value="{{ old('monthdays') }}">
                    </div>
                </section>
                @endif

                {{-- 5. Floors --}}
                <section class="ob-card" aria-labelledby="s5">
                    <div class="ob-card-head"><i>{{ $showPlans ? 5 : 4 }}</i><div><h2 id="s5">Floors or halls <span class="ob-opt">optional</span></h2><p>Split your seats by floor or hall so students see where they sit.</p></div></div>
                    <div id="floorWrapper" class="ob-floors">
                        <div class="floor-block ob-floor">
                            <div class="ob-field"><label>Floor / hall name</label><input type="text" name="floors[0][name]" placeholder="e.g. Ground floor"></div>
                            <div class="ob-field"><label>Seat from</label><input type="number" min="1" name="floors[0][from]" placeholder="1"></div>
                            <div class="ob-field"><label>Seat to</label><input type="number" min="1" name="floors[0][to]" placeholder="50"></div>
                            <button type="button" class="ob-iconbtn remove-floor" aria-label="Remove this floor" hidden><x-lb-icon name="x" /></button>
                        </div>
                    </div>
                    <button type="button" class="ob-addbtn" id="addFloor"><x-lb-icon name="plus" />Add another floor</button>
                </section>
            </div>

            {{-- Sidebar: logo + summary + submit --}}
            <aside class="ob-side">
                <div class="ob-card ob-sticky">
                    <h2 class="ob-side-title">Library logo <span class="ob-opt">optional</span></h2>
                    <label class="ob-logo" for="fileInput">
                        <span class="ob-logo-img" id="preview">
                            @if(isset($branch) && $branch->library_logo)
                                <img src="{{ asset('public/' . $branch->library_logo) }}" alt="">
                            @else
                                <x-lb-icon name="images" />
                            @endif
                        </span>
                        <span class="ob-logo-txt"><b>Upload logo</b><small>JPG, PNG or WEBP · up to 2 MB</small></span>
                        <input type="file" name="library_logo" id="fileInput" class="no-validate" accept="image/jpeg, image/png, image/webp">
                    </label>
                    <p class="ob-err" id="logoUploadError" hidden></p>

                    <div class="ob-summary" aria-live="polite">
                        <div><span>Seats</span><b data-sum="seats">—</b></div>
                        <div><span>Open</span><b data-sum="hour">—</b></div>
                        @if($showPlans)<div><span>Plans</span><b data-sum="plans">1 month</b></div>@endif
                        <div><span>Floors</span><b data-sum="floors">—</b></div>
                    </div>

                    <button type="submit" class="lb-btn lb-btn--lg lb-btn--block ob-submit"><span class="lb-btn-label">{{ isset($branch) ? 'Update branch' : 'Save & continue' }}</span><x-lb-icon name="arrow-right" size="18" /></button>
                    <p class="ob-side-note"><x-lb-icon name="info" />Next: add your shifts and fees.</p>
                </div>
            </aside>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';
    var form = document.getElementById('branchUpdate');
    var alertBox = document.getElementById('ob-form-alert');
    var $ = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

    /* digits only */
    $$('.digit-only', form).forEach(function (i) { i.addEventListener('input', function () { var v = i.value.replace(/\D/g, ''); if (i.maxLength > 0) v = v.slice(0, i.maxLength); if (v !== i.value) i.value = v; summary(); }); });

    /* seat stepper */
    $$('[data-stepper]').forEach(function (w) {
        var inp = $('input', w);
        $$('button', w).forEach(function (b) { b.addEventListener('click', function () { var v = (parseInt(inp.value, 10) || 0) + (+b.getAttribute('data-step')) * (inp.value ? 1 : 0); if (!inp.value) v = 50; inp.value = Math.max(1, Math.min(9999, v)); summary(); }); });
    });

    /* radio chips → hidden input */
    $$('[data-radio]').forEach(function (g) {
        var hidden = document.getElementById(g.getAttribute('data-radio'));
        g.addEventListener('click', function (e) {
            var b = e.target.closest('[data-v]'); if (!b) return;
            $$('[data-v]', g).forEach(function (x) { x.setAttribute('aria-checked', x === b ? 'true' : 'false'); });
            hidden.value = b.getAttribute('data-v'); clearErr(hidden.name); summary();
        });
    });

    /* plan chips → plans[] */
    var planBox = document.getElementById('ob-plans'), planInputs = document.getElementById('ob-plan-inputs');
    function syncPlans() {
        if (!planBox) return;
        var on = $$('[data-plan][aria-pressed="true"]', planBox).map(function (b) { return b.getAttribute('data-plan'); });
        planInputs.innerHTML = on.map(function (v) { return '<input type="hidden" name="plans[]" value="' + v + '">'; }).join('');
        var s = $('[data-sum="plans"]'); if (s) s.textContent = on.length + (on.length === 1 ? ' plan' : ' plans');
    }
    if (planBox) planBox.addEventListener('click', function (e) {
        var b = e.target.closest('[data-plan]'); if (!b) return;
        if (b.hasAttribute('data-locked')) { window.obToast('The 1 month plan is required.'); return; }
        b.setAttribute('aria-pressed', b.getAttribute('aria-pressed') === 'true' ? 'false' : 'true'); syncPlans(); clearErr('plans');
    });
    syncPlans();

    /* floors */
    var wrap = document.getElementById('floorWrapper');
    function reindex() {
        var blocks = $$('.floor-block', wrap);
        blocks.forEach(function (bl, i) { $$('input', bl).forEach(function (inp) { inp.name = inp.name.replace(/floors\[\d+\]/, 'floors[' + i + ']'); }); $('.remove-floor', bl).hidden = blocks.length === 1; });
        summary();
    }
    document.getElementById('addFloor').addEventListener('click', function () {
        var first = $('.floor-block', wrap), clone = first.cloneNode(true);
        $$('input', clone).forEach(function (i) { i.value = ''; }); $$('.ob-err', clone).forEach(function (x) { x.remove(); }); $$('.is-invalid', clone).forEach(function (x) { x.classList.remove('is-invalid'); });
        wrap.appendChild(clone); reindex(); $('input', clone).focus();
    });
    wrap.addEventListener('click', function (e) { var b = e.target.closest('.remove-floor'); if (b) { b.closest('.floor-block').remove(); reindex(); } });
    wrap.addEventListener('input', summary);
    reindex();

    /* logo */
    var fileInput = document.getElementById('fileInput'), preview = document.getElementById('preview'), logoErr = document.getElementById('logoUploadError');
    fileInput.addEventListener('change', function () {
        var f = fileInput.files[0]; logoErr.hidden = true;
        if (!f) return;
        if (['image/jpeg', 'image/png', 'image/webp'].indexOf(f.type) < 0) { logoErr.textContent = 'Please choose a JPG, PNG or WEBP image.'; logoErr.hidden = false; fileInput.value = ''; return; }
        if (f.size > 2 * 1024 * 1024) { logoErr.textContent = 'The logo should be 2 MB or smaller.'; logoErr.hidden = false; fileInput.value = ''; return; }
        var r = new FileReader(); r.onload = function (e) { preview.innerHTML = '<img src="' + e.target.result + '" alt="">'; }; r.readAsDataURL(f);
    });

    /* summary */
    function summary() {
        var s = $('#seats').value, h = $('#hour').value, fl = $$('.floor-block', wrap).filter(function (b) { return $('input', b).value.trim(); }).length;
        $('[data-sum="seats"]').textContent = s ? s + ' seats' : '—';
        $('[data-sum="hour"]').textContent = h ? h + ' hrs / day' : '—';
        $('[data-sum="floors"]').textContent = fl ? fl + (fl > 1 ? ' floors' : ' floor') : 'Not split';
    }
    summary();

    /* errors */
    function fieldFor(key) {
        if (key === 'plans' || key.indexOf('plans.') === 0) return planBox;
        var parts = key.split('.'), name = parts.shift(); parts.forEach(function (p) { name += '[' + p + ']'; });
        var el = form.querySelector('[name="' + name + '"]');
        if (el && el.type === 'hidden' && el.name === 'hour') return $('[data-radio="hour"]');
        if (el && el.type === 'hidden' && el.name === 'monthdays') return $('[data-radio="monthdays"]');
        return el;
    }
    function showErr(key, msg) {
        var el = fieldFor(key); if (!el) return null;
        var host = el.closest('.ob-field') || el.closest('.ob-card') || el.parentElement;
        host.classList.add('is-invalid');
        var p = document.createElement('p'); p.className = 'ob-err'; p.setAttribute('data-err-for', key); p.textContent = msg;
        (el === planBox ? planBox : host).appendChild(p);
        return host;
    }
    function clearErr(name) { $$('[data-err-for="' + name + '"]', form).forEach(function (p) { var h = p.closest('.is-invalid'); if (h) h.classList.remove('is-invalid'); p.remove(); }); }
    form.addEventListener('input', function (e) { if (e.target.name) clearErr(e.target.name.replace(/\[(\w+)\]/g, '.$1')); });

    /* submit (AJAX, same endpoint) */
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = $('.ob-submit', form); if (btn.disabled) return;
        $$('.ob-err[data-err-for]', form).forEach(function (p) { p.remove(); });
        $$('.is-invalid', form).forEach(function (x) { x.classList.remove('is-invalid'); });
        alertBox.hidden = true;
        // Light checks first
        var first = null;
        if (!$('#name').value.trim()) first = first || showErr('name', 'Please enter the branch name.');
        if (!/^\d{10}$/.test($('#mobile').value)) first = first || showErr('mobile', 'Enter a valid 10-digit number.');
        if (!$('#seats').value) first = first || showErr('seats', 'How many seats does the library have?');
        if (!$('#hour').value) first = first || showErr('hour', 'Choose how many hours the library is open.');
        if (first) { first.scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }

        var label = $('.lb-btn-label', btn), orig = label.textContent;
        btn.disabled = true; btn.classList.add('is-loading'); label.textContent = 'Saving…';
        var fd = new FormData(form);
        fetch(form.action, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return { s: r.status, j: j }; }); })
            .then(function (res) {
                if (res.j && res.j.status === true && res.j.redirect) { label.textContent = 'Saved'; window.location.href = res.j.redirect; return; }
                btn.disabled = false; btn.classList.remove('is-loading'); label.textContent = orig;
                var firstHost = null;
                if (res.s === 422 && res.j.errors) {
                    Object.keys(res.j.errors).forEach(function (k) { var h = showErr(k, res.j.errors[k][0]); firstHost = firstHost || h; });
                }
                if (res.s !== 422 || !firstHost) { alertBox.textContent = (res.j && (res.j.message || res.j.error)) || 'Could not save. Please check the details and try again.'; alertBox.hidden = false; firstHost = firstHost || alertBox; }
                firstHost.scrollIntoView({ behavior: 'smooth', block: 'center' });
            })
            .catch(function () { btn.disabled = false; btn.classList.remove('is-loading'); label.textContent = orig; alertBox.textContent = 'No internet connection. Please try again.'; alertBox.hidden = false; alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' }); });
    });
})();
</script>
@endpush
