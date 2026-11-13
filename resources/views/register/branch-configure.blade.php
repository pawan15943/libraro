{{-- always inside the panel; the setup steps show on top during the first-time setup --}}
@extends('lb.layouts.onboard-panel')

{{--
    Branch setup (onboarding). Same backend: POST route('branch.configure') via AJAX (multipart),
    same field names as before: name, founder_day, email, mobile, upi_id, library_address, state_id, city_id,
    library_zip, seats, open_time, close_time, all_day (hour is worked out from them), locker_amount, extend_days, token_money, fixed_billing_date, plans[], monthdays,
    floors[i][name|from|to], library_logo.
    Used for the registration branch step and for Add branch in the panel: both show the same form, with
    the setup steps on top (registration: the full stepper from the layout; panel: Branch → Shifts).
    Success JSON {status:true, redirect}; 422 → field errors; 400 → message.
--}}

@section('title', 'Set up your branch')
@section('ob_step', 'branch')
@section('ob_contact', '0')

@php
    $lib = getLibrary();
    $canAllDay = auth()->user() && auth()->user()->can('has-permission', 'All Day');
    $oldPlans = old('plans', ['1 MONTH']);
    $showPlans = ($branchCount ?? 0) == 0;
    // first-time setup (steps on top, no crumb) or Add branch from inside the panel
    $inSetup = librarySetup()['in_setup'];
    // only the first branch gets the library name; a second one with it would be refused as a duplicate
    $nameDefault = ($libraryBranchCount ?? 0) === 0 ? optional($lib)->library_name : '';
    $n = 0;
@endphp

@push('head')
<link rel="stylesheet" href="{{ asset('public/css/lb-branch.css') }}?v={{ @filemtime(public_path('css/lb-branch.css')) }}">
@endpush

@section('ob_content')
<div class="ob-wrap">
    <header class="ob-head ob-head--left">
        @if($inSetup)
            <h1>Set up your branch</h1>
        @else
            <div class="ob-crumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><a href="{{ route('branch.list') }}">Branches</a><x-lb-icon name="chevron-right" /><span>Add</span></div>
            <h1>Add branch</h1>
        @endif
    </header>

    <form action="{{ route('branch.configure') }}" method="POST" enctype="multipart/form-data" id="branchUpdate" class="ob-form" novalidate>
        @csrf
        @if(isset($branch)) @method('PUT') @endif
        <div class="ob-alert" id="ob-form-alert" role="alert" hidden></div>

        <div class="ob-grid">
            <div class="ob-col">
                {{-- 1. Basics --}}
                <section class="ob-card" aria-labelledby="s1">
                    <div class="ob-card-head"><i>{{ ++$n }}</i><div><h2 id="s1">Branch details</h2></div></div>
                    <div class="ob-fields">
                        <div class="ob-field ob-field--full">
                            <label for="ob_name">Branch name <em>*</em><span class="ob-label-r">Can't be changed later</span></label>
                            <div class="ob-affix ob-affix--ic"><span class="ob-ic"><x-lb-icon name="building" /></span><input type="text" id="ob_name" name="name" value="{{ old('name', $branch->name ?? $nameDefault) }}" placeholder="e.g. Shiv Study Point – Talwandi" maxlength="255" {{ isset($branch) ? 'readonly' : '' }} required></div>
                        </div>
                        <div class="ob-field">
                            <label for="email">Email <em>*</em></label>
                            <div class="ob-affix ob-affix--ic"><span class="ob-ic"><x-lb-icon name="mail" /></span><input type="email" id="email" name="email" value="{{ old('email', $branch->email ?? optional($lib)->email ?? '') }}" placeholder="branch@example.com" autocomplete="email" required></div>
                        </div>
                        <div class="ob-field">
                            <label for="ob_mobile">WhatsApp number <em>*</em></label>
                            <div class="ob-affix"><span>+91</span><input type="tel" id="ob_mobile" name="mobile" class="digit-only" inputmode="numeric" maxlength="10" value="{{ old('mobile', $branch->mobile ?? optional($lib)->library_mobile ?? '') }}" placeholder="10 digit number" required></div>
                        </div>
                        <div class="ob-field">
                            <label for="founder_day">Founded on <em>*</em></label>
                            <div class="ob-affix ob-affix--ic"><span class="ob-ic"><x-lb-icon name="cake" /></span><input type="date" id="founder_day" name="founder_day" value="{{ old('founder_day', $branch->founder_day ?? '') }}" max="{{ now()->toDateString() }}" required></div>
                        </div>
                        <div class="ob-field">
                            <label for="upi_id">UPI ID <span>optional</span></label>
                            <div class="ob-affix ob-affix--ic"><span class="ob-ic"><x-lb-icon name="wallet" /></span><input type="text" id="upi_id" name="upi_id" value="{{ old('upi_id', $branch->upi_id ?? '') }}" placeholder="name@bank" autocomplete="off" autocapitalize="off" spellcheck="false"></div>
                            <small>Learners pay you online at this UPI ID when they book or renew a seat by QR or your listing.</small>
                        </div>
                    </div>
                </section>

                {{-- Address (shown on the branch card, receipts and the listing) --}}
                <section class="ob-card" aria-labelledby="s-addr">
                    <div class="ob-card-head"><i>{{ ++$n }}</i><div><h2 id="s-addr">Address</h2></div></div>
                    <div class="ob-fields ob-fields--addr">
                        <div class="ob-field ob-field--full">
                            <label for="library_address">Full address with landmark <em>*</em></label>
                            <textarea id="library_address" name="library_address" rows="3" maxlength="500" placeholder="Building, street, area and a landmark near you">{{ old('library_address', $branch->library_address ?? '') }}</textarea>
                        </div>
                        <div class="ob-field lbr-choice">
                            <label for="ob_state">State <em>*</em></label>
                            <select id="ob_state" name="state_id" class="ob-select">
                                <option value="">Select state</option>
                                @foreach($states as $st)
                                    <option value="{{ $st->id }}" @selected(old('state_id', $branch->state_id ?? '') == $st->id)>{{ $st->state_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ob-field lbr-choice">
                            <label for="ob_city">City <em>*</em></label>
                            <select id="ob_city" name="city_id" class="ob-select" data-selected="{{ old('city_id', $branch->city_id ?? '') }}" @disabled(!old('state_id', $branch->state_id ?? ''))>
                                <option value="">{{ old('state_id', $branch->state_id ?? '') ? 'Select city' : 'Select state first' }}</option>
                            </select>
                        </div>
                        <div class="ob-field">
                            <label for="library_zip">PIN code <em>*</em></label>
                            <div class="ob-affix ob-affix--ic"><span class="ob-ic"><x-lb-icon name="hash" /></span><input type="text" id="library_zip" name="library_zip" class="digit-only" inputmode="numeric" maxlength="6" value="{{ old('library_zip', $branch->library_zip ?? '') }}" placeholder="6 digits"></div>
                        </div>
                    </div>
                </section>

                {{-- 2. Seats & timing --}}
                <section class="ob-card" aria-labelledby="s2">
                    <div class="ob-card-head"><i>{{ ++$n }}</i><div><h2 id="s2">Seats &amp; timing</h2></div></div>
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
                            <small>0 = no lockers</small>
                        </div>
                        <div class="ob-field ob-field--full">
                            <span class="ob-label">Library timing <em>*</em></span>
                            @include('lb.partials.open-hours', ['skin' => 'ob', 'open' => '', 'close' => '', 'allDay' => false, 'canAllDay' => $canAllDay])
                        </div>
                    </div>
                </section>

                {{-- 3. Billing rules --}}
                <section class="ob-card" aria-labelledby="s3">
                    <div class="ob-card-head"><i>{{ ++$n }}</i><div><h2 id="s3">Billing rules</h2></div></div>
                    <div class="ob-fields">
                        <div class="ob-field">
                            <label for="extend_days">Grace days (Extend period) <em>*</em></label>
                            <div class="ob-affix ob-affix--end"><input type="text" id="extend_days" name="extend_days" class="digit-only no-validate" inputmode="numeric" maxlength="4" value="{{ old('extend_days', '0') }}" placeholder="0" required><span>days</span></div>
                            <small>Seat kept after the plan ends</small>
                        </div>
                        <div class="ob-field">
                            <label for="token_money">Token money <span>optional</span></label>
                            <div class="ob-affix"><span>₹</span><input type="text" id="token_money" name="token_money" class="digit-only no-validate" inputmode="numeric" maxlength="4" value="{{ old('token_money') }}" placeholder="e.g. 200"></div>
                            <small>Advance to hold a seat</small>
                        </div>
                        <div class="ob-field">
                            <label for="fixed_billing_date">Fixed billing date <span>optional</span></label>
                            <div class="ob-affix ob-affix--end"><input type="text" id="fixed_billing_date" name="fixed_billing_date" class="digit-only no-validate" inputmode="numeric" maxlength="2" value="{{ old('fixed_billing_date') }}" placeholder="e.g. 1"><span>of month</span></div>
                            <small>Fill it to take fees on this date every month</small>
                        </div>
                    </div>
                </section>

                {{-- 4. Plans --}}
                @if($showPlans)
                <section class="ob-card" aria-labelledby="s4">
                    <div class="ob-card-head"><i>{{ ++$n }}</i><div><h2 id="s4">Plans you offer</h2><p><b>1 month</b> is required.</p></div></div>
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
                </section>

                {{-- Month length: its own section, it decides every learner's expiry date --}}
                <section class="ob-card" aria-labelledby="s-md">
                    <div class="ob-card-head"><i>{{ ++$n }}</i><div><h2 id="s-md">Month length</h2></div></div>
                    <div class="ob-fields">
                        <div class="ob-field">
                            <label for="monthdays">A "1 month" plan lasts <em>*</em></label>
                            <select id="monthdays" name="monthdays" class="ob-select">
                                <option value="" @selected(old('monthdays', '') === '')>Calendar month (28 to 31 days)</option>
                                <option value="30" @selected(old('monthdays') == 30)>30 days</option>
                                <option value="28" @selected(old('monthdays') == 28)>28 days</option>
                            </select>
                        </div>
                    </div>
                    <div class="ob-md-note" id="mdNote" aria-live="polite">
                        <span class="ob-md-k">Decides every learner's expiry date</span>
                        <p data-md=""><span class="ob-md-b">Calendar month:</span> the plan ends one day before the same date next month, whatever the month's length. Joined 5 Jan → valid till 4 Feb. Joined 5 Feb → valid till 4 Mar.</p>
                        <p data-md="30" hidden><span class="ob-md-b">30 days:</span> every month is exactly 30 days. Joined 5 Jan → valid till 3 Feb. A 3 month plan is 90 days.</p>
                        <p data-md="28" hidden><span class="ob-md-b">28 days:</span> every month is exactly 28 days (4 weeks). Joined 5 Jan → valid till 1 Feb. A 3 month plan is 84 days.</p>
                    </div>
                </section>
                @endif

                {{-- 5. Floors --}}
                <section class="ob-card" aria-labelledby="s5">
                    <div class="ob-card-head"><i>{{ ++$n }}</i><div><h2 id="s5">Floors or halls <span class="ob-opt">optional</span></h2></div></div>
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
                {{-- same card as Edit branch: logo, name, one action, save below a divider --}}
                <div class="ob-card ob-sticky ob-idcard">
                    <div class="ob-idcard-top">
                        <label class="ob-idlogo" for="fileInput" aria-label="Upload library logo">
                            <span class="ob-idlogo-in" id="preview"><b id="obInitials">{{ $nameDefault ? mb_strtoupper(mb_substr($nameDefault, 0, 2)) : 'B' }}</b></span>
                            <i><x-lb-icon name="camera" /></i>
                            <input type="file" name="library_logo" id="fileInput" class="no-validate" accept="image/jpeg, image/png, image/webp">
                        </label>
                        <h3 id="obIdTitle">{{ $nameDefault ?: 'New branch' }}</h3>
                        <label class="ob-logo-btn" for="fileInput"><x-lb-icon name="upload" />Upload logo</label>
                        <p class="ob-logo-note">250 × 250 px · JPG, PNG or WEBP</p>
                        <p class="ob-err" id="logoUploadError" hidden></p>
                    </div>
                    {{-- the summary is still filled by the page script; this card doesn't show it --}}
                    <div class="ob-summary" aria-live="polite" hidden>
                        <div><span>Seats</span><b data-sum="seats">—</b></div>
                        <div><span>Open</span><b data-sum="hour">—</b></div>
                        @if($showPlans)<div><span>Plans</span><b data-sum="plans">1 month</b></div>@endif
                        <div><span>Floors</span><b data-sum="floors">—</b></div>
                    </div>
                    <div class="ob-idcard-save">
                        <button type="submit" class="lb-btn lb-btn--lg lb-btn--block ob-submit"><span class="lb-btn-label">Save &amp; continue</span><x-lb-icon name="arrow-right" size="18" /></button>
                        @unless($inSetup)<a href="{{ route('branch.list') }}" class="ob-cancel">Cancel</a>@endunless
                    </div>
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
    // looked up inside this form: the layout's booking popup has fields with the same ids (name, mobile, email)
    var $ = function (s, r) { return (r || form).querySelector(s); };
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
        // cut to 250 x 250 in the same crop popup as learner photos (when the page has it)
        if (window.lpCrop && typeof DataTransfer !== 'undefined') {
            window.lpCrop(f, { width: 250, height: 250, title: 'Crop logo' }).then(function (cut) {
                if (!cut) { fileInput.value = ''; return; }
                var dt = new DataTransfer(); dt.items.add(cut); fileInput.files = dt.files;
                preview.innerHTML = '<img src="' + URL.createObjectURL(cut) + '" alt="">';
            });
            return;
        }
        var r = new FileReader(); r.onload = function (e) { preview.innerHTML = '<img src="' + e.target.result + '" alt="">'; }; r.readAsDataURL(f);
    });

    /* state → city (same endpoint as the edit branch page: {id: name}) */
    var stateSel = document.getElementById('ob_state'), citySel = document.getElementById('ob_city');
    // searchable dropdowns, the same as on Edit branch (plain selects if Choices isn't on the page)
    var choiceOpts = { searchEnabled: true, shouldSort: false, itemSelectText: '', searchPlaceholderValue: 'Search…' };
    // Choices loads further down the layout than this script, so it is set up once the page is parsed
    var stateCh = null, cityCh = null;
    function setCities(list, placeholder, disabled) {
        if (cityCh) {
            cityCh.clearStore();
            cityCh.setChoices([{ value: '', label: placeholder, placeholder: true, selected: !list.some(function (c) { return c.selected; }) }].concat(list), 'value', 'label', true);
            disabled ? cityCh.disable() : cityCh.enable();
            return;
        }
        citySel.innerHTML = '<option value="">' + placeholder + '</option>' + list.map(function (c) {
            var o = document.createElement('option'); o.value = c.value; o.textContent = c.label; o.selected = !!c.selected; return o.outerHTML;
        }).join('');
        citySel.disabled = disabled;
    }
    function loadCities(stateId, selected) {
        if (!stateId) { setCities([], 'Select state first', true); return; }
        setCities([], 'Loading…', true);
        fetch("{{ route('cityGetStateWise') }}?state_id=" + encodeURIComponent(stateId), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (cities) {
                setCities(Object.keys(cities || {}).map(function (id) { return { value: id, label: cities[id], selected: String(selected) === String(id) }; }), 'Select city', false);
            })
            .catch(function () { setCities([], 'Select city', false); });
    }
    stateSel.addEventListener('change', function () { clearErr('state_id'); clearErr('city_id'); loadCities(stateSel.value, ''); });
    citySel.addEventListener('change', function () { clearErr('city_id'); });
    function initPlaces() {
        if (window.Choices) { stateCh = new Choices(stateSel, choiceOpts); cityCh = new Choices(citySel, choiceOpts); }
        if (stateSel.value) loadCities(stateSel.value, citySel.getAttribute('data-selected'));
        else setCities([], 'Select state first', true);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initPlaces); else initPlaces();

    /* "1 month plan lasts": show what the picked choice means */
    var mdSel = document.getElementById('monthdays');
    function paintMd() {
        var v = mdSel ? mdSel.value : '';
        document.querySelectorAll('#mdNote [data-md]').forEach(function (p) { p.hidden = p.getAttribute('data-md') !== v; });
    }
    if (mdSel) { mdSel.addEventListener('change', paintMd); paintMd(); }

    /* card title and initials follow the branch name (panel card) */
    var idTitle = document.getElementById('obIdTitle'), idIni = document.getElementById('obInitials');
    if (idTitle) $('#ob_name').addEventListener('input', function () {
        var v = this.value.trim();
        idTitle.textContent = v || 'New branch';
        if (idIni) idIni.textContent = (v.split(/\s+/).filter(Boolean).slice(0, 2).map(function (w) { return w[0]; }).join('') || 'B').toUpperCase();
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
        if (el && el.type === 'hidden' && el.name === 'hour') return $('[data-lboh]');
        if (key === 'open_time' || key === 'close_time' || key === 'all_day') return $('[data-lboh]');
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
        if (!$('#ob_name').value.trim()) first = first || showErr('name', 'Please enter the branch name.');
        if (!/^\d{10}$/.test($('#ob_mobile').value)) first = first || showErr('mobile', 'Enter a valid 10-digit number.');
        if (!$('#library_address').value.trim()) first = first || showErr('library_address', 'Please enter the address.');
        if (!$('#ob_state').value) first = first || showErr('state_id', 'Choose the state.');
        if (!$('#ob_city').value) first = first || showErr('city_id', 'Choose the city.');
        if (!/^\d{6}$/.test($('#library_zip').value)) first = first || showErr('library_zip', 'Enter the 6 digit PIN code.');
        if (!$('#seats').value) first = first || showErr('seats', 'How many seats does the library have?');
        var hoursErr = window.lbOpenHours ? lbOpenHours($('[data-lboh]')).check() : '';
        if (hoursErr) first = first || showErr('open_time', hoursErr);
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
