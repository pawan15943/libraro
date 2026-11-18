@extends('layouts.library')

@section('title', 'Order Settings')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-ordering.css') }}?v={{ @filemtime(public_path('css/lb-ordering.css')) }}">
@endpush

@section('content')
@php
    $s = $settings;
    $fromMain = $errors->any() && old('form_kind') === 'settings'; // unticked switches are not posted
    $on = fn ($k) => $fromMain ? (bool) old($k) : (bool) $s->{$k};
    $val = fn ($k, $fmt = null) => old($k, $fmt ? $fmt($s->{$k}) : $s->{$k});
    $hm = fn ($t) => $t ? substr($t, 0, 5) : '';
    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
@endphp

<div class="lpg od od-set">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <p>How learners order in this branch: which modes and payment methods are on, ordering hours, how fast staff are reminded, and what happens when an item runs out.</p>
        </div>
        <div class="lpg-head-r">
            <a class="lp-btn lp-btn--ghost" href="{{ route('ordering.items') }}"><x-lb-icon name="list" />Menu & Items</a>
        </div>
    </header>

    @if ($errors->any() && ! $errors->has('labels'))
        <div class="lef-alert" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('ordering.settings.save') }}" id="odSetForm" novalidate>
        @csrf
        <input type="hidden" name="form_kind" value="settings">

        <label class="od-master {{ $on('is_enabled') ? 'is-on' : '' }}">
            <span class="od-master-ic"><x-lb-icon name="coffee" /></span>
            <span class="od-master-t"><b>Ordering & Services for learners</b><small>When on, learners see "Order" in their portal and can place orders.</small></span>
            <input type="checkbox" class="od-sw" role="switch" name="is_enabled" value="1" @checked($on('is_enabled')) data-od-master>
        </label>

        <div class="od-grid">
            {{-- Modes --}}
            <section class="od-card">
                <h3><x-lb-icon name="map-pin" />Order modes</h3>
                <label class="od-switch"><input type="checkbox" class="od-sw" role="switch" name="mode_seat" value="1" @checked($on('mode_seat'))>
                    <span><b>Seat delivery</b><small>Delivered to the learner's booked seat (filled in by itself).</small></span></label>
                <label class="od-switch"><input type="checkbox" class="od-sw" role="switch" name="mode_table" value="1" @checked($on('mode_table'))>
                    <span><b>Cafeteria table</b><small>Learner picks a table from the list below.</small></span></label>
                <label class="od-switch"><input type="checkbox" class="od-sw" role="switch" name="mode_counter" value="1" @checked($on('mode_counter'))>
                    <span><b>Counter pickup</b><small>Learner collects it when it is ready.</small></span></label>
                <label class="od-switch"><input type="checkbox" class="od-sw" role="switch" name="seat_change_allowed" value="1" @checked($on('seat_change_allowed'))>
                    <span><b>Let learners type another seat</b><small>Off: always their booked seat.</small></span></label>
            </section>

            {{-- Hours & limits --}}
            <section class="od-card">
                <h3><x-lb-icon name="clock" />Hours & limits</h3>
                <div class="lef-grid">
                    <div class="lef-f @error('opens_at') is-bad @enderror">
                        <label for="odOpen">Orders from</label>
                        <div class="lef-in"><x-lb-icon name="sunrise" /><input type="time" id="odOpen" name="opens_at" value="{{ $val('opens_at', $hm) }}"></div>
                    </div>
                    <div class="lef-f @error('closes_at') is-bad @enderror">
                        <label for="odClose">Orders till</label>
                        <div class="lef-in"><x-lb-icon name="sunset" /><input type="time" id="odClose" name="closes_at" value="{{ $val('closes_at', $hm) }}"></div>
                    </div>
                </div>
                <p class="lef-hint">Leave both empty to take orders all day.</p>
                <div class="lef-grid od-mt">
                    <div class="lef-f">
                        <label for="odPrep">Usual ready time</label>
                        <div class="lef-in"><x-lb-icon name="timer" /><input type="number" id="odPrep" name="prep_minutes" min="0" max="240" value="{{ $val('prep_minutes') }}"><span class="od-suf">min</span></div>
                    </div>
                    <div class="lef-f">
                        <label for="odMax">Open orders per learner</label>
                        <div class="lef-in"><x-lb-icon name="layers" /><input type="number" id="odMax" name="max_open_orders" min="0" max="20" value="{{ $val('max_open_orders') }}"></div>
                    </div>
                </div>
                <p class="lef-hint">0 = no limit.</p>
                <div class="lef-f od-mt">
                    <label for="odDel">Delivery charge per order</label>
                    <div class="lef-in"><span class="lef-pre">₹</span><input type="number" id="odDel" name="delivery_charge" min="0" step="any" value="{{ $val('delivery_charge', $num) }}"></div>
                    <p class="lef-hint">Added to seat and table orders only. 0 = free.</p>
                </div>
                <label class="od-switch"><input type="checkbox" class="od-sw" role="switch" name="expired_can_order" value="1" @checked($on('expired_can_order'))>
                    <span><b>Learners with an expired plan can order</b><small>Off: only active members.</small></span></label>
            </section>

            {{-- Payments --}}
            <section class="od-card">
                <h3><x-lb-icon name="wallet" />Payment methods</h3>
                <label class="od-switch"><input type="checkbox" class="od-sw" role="switch" name="pay_online" value="1" @checked($on('pay_online'))>
                    <span><b>Pay online (UPI QR)</b><small>Learner scans your branch UPI QR and uploads the screenshot; staff verify it.</small></span></label>
                @unless ($hasUpi)
                    <p class="od-warn"><x-lb-icon name="circle-alert" />This branch has no UPI ID yet, so online payment stays hidden for learners. Add it in Setup → Branch.</p>
                @endunless
                <label class="od-switch"><input type="checkbox" class="od-sw" role="switch" name="pay_offline" value="1" @checked($on('pay_offline'))>
                    <span><b>Pay at counter</b><small>Cash / UPI at the counter; staff mark it paid.</small></span></label>
                <label class="od-switch"><input type="checkbox" class="od-sw" role="switch" name="pay_later" value="1" @checked($on('pay_later')) data-od-paylater>
                    <span><b>Pay later</b><small>Order now, settle later (e.g. weekly).</small></span></label>
                <div class="od-sub" data-od-paylater-box @if (! $on('pay_later')) hidden @endif>
                    <div class="lpg-radio is-seg" role="radiogroup" aria-label="Who can pay later">
                        <label><input type="radio" name="pay_later_scope" value="all" @checked(old('pay_later_scope', $s->pay_later_scope) === 'all')><span>All learners</span></label>
                        <label><input type="radio" name="pay_later_scope" value="selected" @checked(old('pay_later_scope', $s->pay_later_scope) === 'selected')><span>Selected learners</span></label>
                    </div>
                    <div class="od-pick" data-od-pick @if (old('pay_later_scope', $s->pay_later_scope) !== 'selected') hidden @endif>
                        <label class="od-search"><x-lb-icon name="user-search" /><input type="search" placeholder="Search name, learner no. or seat" data-od-pick-q autocomplete="off" aria-label="Search learners"></label>
                        <ul class="od-pick-res" data-od-pick-res hidden></ul>
                        <div class="od-pick-sel" data-od-pick-sel>
                            @foreach ($payLaterLearners as $l)
                                <span class="od-pill">{{ $l->name }}@if ($l->seat_no) · S{{ $l->seat_no }}@endif<input type="hidden" name="pay_later_learner_ids[]" value="{{ $l->id }}"><button type="button" aria-label="Remove {{ $l->name }}">×</button></span>
                            @endforeach
                        </div>
                    </div>
                    <div class="lef-f od-mt">
                        <label for="odLimit">Unpaid limit per learner</label>
                        <div class="lef-in"><span class="lef-pre">₹</span><input type="number" id="odLimit" name="pay_later_limit" min="0" step="any" value="{{ $val('pay_later_limit', $num) }}"></div>
                        <p class="lef-hint">Pay later is hidden once a learner owes this much. 0 = no limit.</p>
                    </div>
                </div>
                <p class="lef-hint od-mt">Each category can also switch methods off (Menu & Items → edit category).</p>
            </section>

            {{-- Staff alerts --}}
            <section class="od-card">
                <h3><x-lb-icon name="bell-ring" />Staff alerts</h3>
                <div class="lef-grid">
                    <div class="lef-f @error('reminder_minutes') is-bad @enderror">
                        <label for="odRem">Remind staff after</label>
                        <div class="lef-in"><x-lb-icon name="alarm-clock" /><input type="number" id="odRem" name="reminder_minutes" min="1" max="120" value="{{ $val('reminder_minutes') }}"><span class="od-suf">min</span></div>
                    </div>
                    <div class="lef-f @error('escalation_minutes') is-bad @enderror">
                        <label for="odEsc">Alert owner after</label>
                        <div class="lef-in"><x-lb-icon name="megaphone" /><input type="number" id="odEsc" name="escalation_minutes" min="1" max="240" value="{{ $val('escalation_minutes') }}"><span class="od-suf">min</span></div>
                    </div>
                </div>
                <p class="lef-hint">A new order nobody opens gets a reminder, then moves to "Attention required". Orders are never rejected by themselves.</p>
                <label class="od-switch"><input type="checkbox" class="od-sw" role="switch" name="sound_enabled" value="1" @checked($on('sound_enabled'))>
                    <span><b>Sound on new orders</b><small>The order board plays a chime.</small></span></label>
            </section>

            {{-- Replacements --}}
            <section class="od-card">
                <h3><x-lb-icon name="arrow-left-right" />Item runs out</h3>
                <label class="od-switch"><input type="checkbox" class="od-sw" role="switch" name="replacement_enabled" value="1" @checked($on('replacement_enabled'))>
                    <span><b>Staff can suggest a replacement</b><small>The learner must accept it; a higher price is never charged silently.</small></span></label>
                <label class="od-switch"><input type="checkbox" class="od-sw" role="switch" name="replacement_multiple" value="1" @checked($on('replacement_multiple'))>
                    <span><b>Allow another suggestion</b><small>If the learner says no, staff may suggest again.</small></span></label>
                <label class="od-switch"><input type="checkbox" class="od-sw" role="switch" name="partial_fulfilment" value="1" @checked($on('partial_fulfilment'))>
                    <span><b>Prepare the rest meanwhile</b><small>Available items go ahead while the learner decides (if the learner allows it).</small></span></label>
                <div class="lef-grid od-mt">
                    <div class="lef-f">
                        <label for="odRto">Learner must answer in</label>
                        <div class="lef-in"><x-lb-icon name="hourglass" /><input type="number" id="odRto" name="replacement_timeout_minutes" min="1" max="240" value="{{ $val('replacement_timeout_minutes') }}"><span class="od-suf">min</span></div>
                    </div>
                    <div class="lef-f">
                        <label for="odRta">If no answer</label>
                        <div class="lef-in"><x-lb-icon name="circle-alert" />
                            <select id="odRta" name="replacement_timeout_action">
                                <option value="remove" @selected(old('replacement_timeout_action', $s->replacement_timeout_action) === 'remove')>Remove the item</option>
                                <option value="escalate" @selected(old('replacement_timeout_action', $s->replacement_timeout_action) === 'escalate')>Ask staff to decide</option>
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Cancellation & uploads --}}
            <section class="od-card">
                <h3><x-lb-icon name="circle-x" />Cancellation & printing</h3>
                <div class="lef-f">
                    <label for="odCancel">Learner can cancel by themselves until</label>
                    <div class="lef-in"><x-lb-icon name="ban" />
                        <select id="odCancel" name="learner_cancel_until">
                            <option value="new" @selected(old('learner_cancel_until', $s->learner_cancel_until) === 'new')>Staff open the order</option>
                            <option value="under_review" @selected(old('learner_cancel_until', $s->learner_cancel_until) === 'under_review')>Staff accept the order</option>
                            <option value="confirmed" @selected(old('learner_cancel_until', $s->learner_cancel_until) === 'confirmed')>Preparation starts</option>
                        </select>
                    </div>
                    <p class="lef-hint">After that, only staff can cancel (with a reason).</p>
                </div>
                <div class="lef-grid od-mt">
                    <div class="lef-f @error('upload_types') is-bad @enderror">
                        <label for="odTypes">Print file types</label>
                        <div class="lef-in"><x-lb-icon name="file-up" /><input type="text" id="odTypes" name="upload_types" maxlength="100" value="{{ $val('upload_types') }}"></div>
                    </div>
                    <div class="lef-f">
                        <label for="odMb">Max file size</label>
                        <div class="lef-in"><x-lb-icon name="upload" /><input type="number" id="odMb" name="upload_max_mb" min="1" max="25" value="{{ $val('upload_max_mb') }}"><span class="od-suf">MB</span></div>
                    </div>
                </div>
            </section>
        </div>

        <div class="od-save">
            <button type="submit" class="lp-btn"><x-lb-icon name="check" />Save settings</button>
        </div>
    </form>

    {{-- Cafeteria tables (own forms) --}}
    <section class="od-card od-tables" id="tables">
        <h3><x-lb-icon name="table" />Cafeteria tables <span class="lpg-n">{{ $tables->count() }}</span></h3>
        <form method="POST" action="{{ route('ordering.table.store') }}" class="od-tadd">
            @csrf
            <div class="lef-f @error('labels') is-bad @enderror">
                <div class="lef-in"><x-lb-icon name="plus" /><input type="text" name="labels" maxlength="500" placeholder="1-10  or  T1, T2, Window" value="{{ old('labels') }}" aria-label="Table numbers"></div>
                @error('labels')<p class="lef-err">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="lp-btn lp-btn--ghost">Add tables</button>
        </form>
        @if ($tables->isEmpty())
            <p class="od-none">No tables yet. Add them to use "Cafeteria table" orders.</p>
        @else
            <div class="od-tlist">
                @foreach ($tables as $t)
                    <span class="od-tbl {{ $t->is_active ? '' : 'is-off' }}">
                        <b>{{ $t->label }}</b>
                        <form method="POST" action="{{ route('ordering.table.toggle', $t->id) }}">@csrf
                            <button title="{{ $t->is_active ? 'Close table' : 'Open table' }}" aria-label="{{ $t->is_active ? 'Close' : 'Open' }} table {{ $t->label }}"><x-lb-icon :name="$t->is_active ? 'eye-off' : 'eye'" /></button>
                        </form>
                        <form method="POST" action="{{ route('ordering.table.destroy', $t->id) }}">@csrf @method('DELETE')
                            <button class="is-bad" title="Remove" aria-label="Remove table {{ $t->label }}"><x-lb-icon name="x" /></button>
                        </form>
                    </span>
                @endforeach
            </div>
        @endif
    </section>
</div>

<script>
(function () {
    var $ = function (s, r) { return (r || document).querySelector(s); };
    var form = $('#odSetForm');

    var master = $('[data-od-master]');
    master.addEventListener('change', function () { master.closest('.od-master').classList.toggle('is-on', master.checked); });

    var pl = $('[data-od-paylater]'), plBox = $('[data-od-paylater-box]'), pick = $('[data-od-pick]');
    pl.addEventListener('change', function () { plBox.hidden = !pl.checked; });
    form.querySelectorAll('input[name="pay_later_scope"]').forEach(function (r) {
        r.addEventListener('change', function () { pick.hidden = r.value !== 'selected' || !r.checked; });
    });

    // pay-later learner picker
    var q = $('[data-od-pick-q]'), res = $('[data-od-pick-res]'), sel = $('[data-od-pick-sel]'), timer = null;
    var searchUrl = @json(route('ordering.settings.learners'));
    function chosen() { return Array.prototype.map.call(sel.querySelectorAll('input'), function (i) { return i.value; }); }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
    function add(l) {
        if (chosen().indexOf(String(l.id)) !== -1) return;
        var p = document.createElement('span'); p.className = 'od-pill';
        p.innerHTML = esc(l.name) + (l.seat_no ? ' · S' + esc(l.seat_no) : '') + '<input type="hidden" name="pay_later_learner_ids[]" value="' + esc(l.id) + '"><button type="button" aria-label="Remove">×</button>';
        sel.appendChild(p);
    }
    sel.addEventListener('click', function (e) { if (e.target.tagName === 'BUTTON') e.target.parentNode.remove(); });
    q.addEventListener('input', function () {
        clearTimeout(timer);
        var term = q.value.trim();
        if (term.length < 2) { res.hidden = true; return; }
        timer = setTimeout(function () {
            fetch(searchUrl + '?q=' + encodeURIComponent(term), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (list) {
                    res.innerHTML = list.length ? '' : '<li class="is-empty">No learner found</li>';
                    list.forEach(function (l) {
                        var li = document.createElement('li');
                        li.innerHTML = '<b>' + esc(l.name) + '</b><small>' + esc(l.learner_no || '') + (l.seat_no ? ' · Seat ' + esc(l.seat_no) : '') + '</small>';
                        li.addEventListener('click', function () { add(l); q.value = ''; res.hidden = true; });
                        res.appendChild(li);
                    });
                    res.hidden = false;
                });
        }, 250);
    });
    q.addEventListener('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });
})();
</script>
@endsection
