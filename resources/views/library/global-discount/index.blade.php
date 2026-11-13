@extends('layouts.library')

@section('title', 'Global Discount')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-gdisc.css') }}?v={{ @filemtime(public_path('css/lb-gdisc.css')) }}">
@endpush

@section('content')
@php
    $inr = fn ($n) => '₹' . number_format((float) $n, (float) $n == floor((float) $n) ? 0 : 2);
    $stateTag = [
        'running' => ['is-ok', 'Running now'],
        'scheduled' => ['is-acc', 'Starts later'],
        'ended' => ['', 'Ended'],
        'off' => ['is-bad', 'Turned off'],
    ];
    $reopen = $errors->any() ? (string) old('form_id', 'new') : null;
    $today = today()->toDateString();
@endphp

{{-- Add / edit discount --}}
<div class="modal fade lpg-modal" id="gdModal" tabindex="-1" aria-labelledby="gdModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <form id="gdForm" method="POST" action="{{ route('global-discount.store') }}" novalidate>
                @csrf
                <input type="hidden" name="_method" value="POST" id="gdMethod">
                <input type="hidden" name="form_id" value="new" id="gdFormId">
                <div class="modal-header">
                    <span class="lpg-ic is-ok"><x-lb-icon name="tag" /></span>
                    <div>
                        <h2 id="gdModalTitle">New discount</h2>
                        <small>Applies by itself to every booking form of your library</small>
                    </div>
                    <button type="button" class="lpg-close" data-bs-dismiss="modal" aria-label="Close"><x-lb-icon name="x" /></button>
                </div>
                <div class="modal-body">
                    @if ($errors->any())
                        <div class="lef-alert gd-alert" role="alert">{{ $errors->first() }}</div>
                    @endif
                    <div class="lef-f @error('name') is-bad @enderror">
                        <label for="gdName">Discount name <i>*</i></label>
                        <div class="lef-in"><x-lb-icon name="tag" /><input type="text" id="gdName" name="name" maxlength="100" placeholder="e.g. Diwali Offer" value="{{ old('name') }}" required></div>
                        @error('name')<p class="lef-err">{{ $message }}</p>@enderror
                    </div>
                    <div class="lef-f" style="margin-top:14px">
                        <label>Discount type <i>*</i></label>
                        <div class="lpg-radio is-seg" role="radiogroup" aria-label="Discount type">
                            <label><input type="radio" name="discount_type" value="percentage" @checked(old('discount_type', 'percentage') === 'percentage')><span>Percent (%)</span></label>
                            <label><input type="radio" name="discount_type" value="amount" @checked(old('discount_type') === 'amount')><span>Fixed amount (₹)</span></label>
                        </div>
                    </div>
                    <div class="lef-f @error('discount_value') is-bad @enderror" style="margin-top:14px">
                        <label for="gdValue">Discount value <i>*</i></label>
                        <div class="lef-in"><span class="lef-pre" id="gdPre">%</span><input type="number" id="gdValue" name="discount_value" min="0.01" step="any" inputmode="decimal" placeholder="10" value="{{ old('discount_value') }}" required></div>
                        @error('discount_value')<p class="lef-err">{{ $message }}</p>@enderror
                        <p class="lef-hint" id="gdHint">Taken off the plan fee. The locker charge is not discounted.</p>
                    </div>
                    <div class="lef-grid" style="margin-top:14px">
                        <div class="lef-f @error('start_date') is-bad @enderror">
                            <label for="gdStart">Start date <i>*</i></label>
                            <div class="lef-in"><x-lb-icon name="calendar-days" /><input type="date" id="gdStart" name="start_date" value="{{ old('start_date', $today) }}" required></div>
                        </div>
                        <div class="lef-f @error('end_date') is-bad @enderror">
                            <label for="gdEnd">End date <i>*</i></label>
                            <div class="lef-in"><x-lb-icon name="calendar-check" /><input type="date" id="gdEnd" name="end_date" value="{{ old('end_date') }}" required></div>
                        </div>
                    </div>
                    @if ($errors->has('start_date') || $errors->has('end_date'))
                        <p class="lef-err">{{ $errors->first('start_date') ?: $errors->first('end_date') }}</p>
                    @endif
                    <label class="gd-switch">
                        <input type="hidden" name="is_enabled" value="0">
                        <input type="checkbox" class="form-check-input" role="switch" name="is_enabled" value="1" id="gdOn" @checked(old('is_enabled', '1') == '1')>
                        <span><b>Turned on</b><small>It starts and stops by itself on these dates. Turn it off to pause it.</small></span>
                    </label>
                    <div class="gd-preview" id="gdPreview" aria-live="polite"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lp-btn lp-btn--ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="lp-btn"><x-lb-icon name="check" />Save discount</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="lpg gd">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><span>Setup</span><x-lb-icon name="chevron-right" /><span>Global Discount</span></div>
            <h1>Global Discount</h1>
            <p>One offer for all your students. While it runs, every booking form takes it off by itself (new booking, renew, upgrade, change plan and reactivate) and students see it on their dashboard. No coupon code needed.</p>
        </div>
        @if ($discounts->isNotEmpty())
            <div class="lpg-head-r">
                <button type="button" class="lp-btn" data-gd-new><x-lb-icon name="plus" />New discount</button>
            </div>
        @endif
    </header>

    @if ($discounts->isEmpty())
        <section class="lpg-empty">
            <span><x-lb-icon name="party-popper" /></span>
            <b>No discount yet</b>
            <p>Run a festival or season offer, e.g. 10% off from 1 to 15 November. It switches on and off by itself on those dates.</p>
            <button type="button" class="lp-btn" data-gd-new><x-lb-icon name="plus" />Create a discount</button>
        </section>
    @else
        <div class="lpg-stats gd-stats">
            <div class="lpg-stat {{ $running ? 'is-ok' : '' }}">
                <span class="lpg-stat-ic"><x-lb-icon name="party-popper" /></span>
                <span>Running now</span>
                <b>{{ $running ? $running->label() : 'None' }}</b>
                <small>{{ $running ? $running->name . ' · till ' . $running->end_date->format('j M') : ($scheduledCount ? $scheduledCount . ' starting later' : 'Booking forms charge the plan fee') }}</small>
            </div>
            <div class="lpg-stat is-violet">
                <span class="lpg-stat-ic"><x-lb-icon name="ticket" /></span>
                <span>Bookings with a discount</span>
                <b>{{ number_format($totalBookings) }}</b>
                <small>Students saved {{ $inr($totalSaved) }} in all</small>
            </div>
        </div>

        <div class="lpg-sec">All discounts <span class="lpg-n">{{ $discounts->count() }}</span><em>Only one can run on a day</em></div>
        <div class="gd-list">
            @foreach ($discounts as $d)
                @php
                    $st = $d->state();
                    $u = $usage->get($d->id);
                    $days = $st === 'running' ? today()->diffInDays($d->end_date) : ($st === 'scheduled' ? today()->diffInDays($d->start_date) : null);
                    $editData = [
                        'id' => $d->id, 'name' => $d->name, 'discount_type' => $d->discount_type, 'discount_value' => $d->discount_value,
                        'start_date' => $d->start_date->toDateString(), 'end_date' => $d->end_date->toDateString(),
                        'is_enabled' => $d->is_enabled, 'url' => route('global-discount.update', $d->id),
                    ];
                @endphp
                <article class="gd-card is-{{ $st }}">
                    <div class="gd-top">
                        <span class="gd-badge">{{ $d->discount_type === 'percentage' ? rtrim(rtrim(number_format($d->discount_value, 2, '.', ''), '0'), '.') . '%' : $inr($d->discount_value) }}<small>off</small></span>
                        <div class="gd-id">
                            <h3 title="{{ $d->name }}">{{ $d->name }}</h3>
                            <div class="gd-tags">
                                <span class="lpg-tag {{ $stateTag[$st][0] }}">{{ $stateTag[$st][1] }}</span>
                                <span class="lpg-tag">{{ $d->discount_type === 'percentage' ? 'Percent' : 'Fixed amount' }}</span>
                            </div>
                        </div>
                    </div>
                    <dl class="gd-facts">
                        <div><dt><x-lb-icon name="calendar-range" /></dt><dd>{{ $d->start_date->format('j M Y') }} – {{ $d->end_date->format('j M Y') }}
                            @if ($st === 'running') <em>{{ $days === 0 ? 'last day today' : $days . ' ' . \Illuminate\Support\Str::plural('day', $days) . ' left' }}</em>
                            @elseif ($st === 'scheduled') <em>starts in {{ $days }} {{ \Illuminate\Support\Str::plural('day', $days) }}</em>@endif
                        </dd></div>
                        <div><dt><x-lb-icon name="ticket" /></dt><dd>{{ $u ? number_format($u->bookings) . ' ' . \Illuminate\Support\Str::plural('booking', $u->bookings) . ' · ' . $inr($u->saved) . ' saved' : 'Not used yet' }}</dd></div>
                    </dl>
                    <div class="gd-acts">
                        <button type="button" class="gd-btn" data-gd-edit="{{ json_encode($editData) }}"><x-lb-icon name="pencil" />Edit</button>
                        <form method="POST" action="{{ route('global-discount.toggle', $d->id) }}">@csrf
                            <button class="gd-btn"><x-lb-icon name="power" />{{ $d->is_enabled ? 'Turn off' : 'Turn on' }}</button>
                        </form>
                        <form method="POST" action="{{ route('global-discount.destroy', $d->id) }}" data-gd-delete="{{ $d->name }}">@csrf @method('DELETE')
                            <button class="gd-ibtn" aria-label="Delete {{ $d->name }}" title="Delete"><x-lb-icon name="trash" /></button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>

<script>
(function () {
    var modalEl = document.getElementById('gdModal');
    var form = document.getElementById('gdForm');
    var storeUrl = @json(route('global-discount.store'));
    var today = @json($today);
    var f = function (id) { return document.getElementById(id); };

    function typeNow() { var r = form.querySelector('input[name="discount_type"]:checked'); return r ? r.value : 'percentage'; }
    function paint() {
        var t = typeNow(), v = parseFloat(f('gdValue').value) || 0;
        f('gdPre').textContent = t === 'percentage' ? '%' : '₹';
        f('gdValue').placeholder = t === 'percentage' ? '10' : '200';
        var ex = 1000, off = t === 'percentage' ? Math.min(100, v) * ex / 100 : Math.min(v, ex);
        var s = f('gdStart').value, e = f('gdEnd').value;
        var fmt = function (d) { var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(d || ''); return m ? new Date(+m[1], +m[2] - 1, +m[3]).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) : ''; };
        var when = s && e ? (s > today ? 'Starts by itself on ' + fmt(s) + ', ends ' + fmt(e) + '.' : (e < today ? 'These dates are over, so it will not apply.' : 'Runs from today till ' + fmt(e) + '.')) : 'Pick the start and end date.';
        f('gdPreview').innerHTML = v > 0
            ? '<b>Example: a ₹1,000 plan</b><span>Original ₹1,000 · Discount − ₹' + Math.round(off).toLocaleString('en-IN') + ' · Payable ₹' + Math.round(ex - off).toLocaleString('en-IN') + '</span><small>' + when + '</small>'
            : '<small>' + when + '</small>';
    }

    function fill(d) {
        form.action = d ? d.url : storeUrl;
        f('gdMethod').value = d ? 'PUT' : 'POST';
        f('gdFormId').value = d ? d.id : 'new';
        f('gdModalTitle').textContent = d ? 'Edit discount' : 'New discount';
        f('gdName').value = d ? d.name : '';
        form.querySelectorAll('input[name="discount_type"]').forEach(function (r) { r.checked = r.value === (d ? d.discount_type : 'percentage'); });
        f('gdValue').value = d ? d.discount_value : '';
        f('gdStart').value = d ? d.start_date : today;
        f('gdEnd').value = d ? d.end_date : '';
        f('gdOn').checked = d ? !!d.is_enabled : true;
        form.querySelectorAll('.lef-f.is-bad').forEach(function (x) { x.classList.remove('is-bad'); });
        form.querySelectorAll('.lef-err, .gd-alert').forEach(function (x) { x.remove(); });
        paint();
    }
    function open() { if (window.bootstrap && bootstrap.Modal) bootstrap.Modal.getOrCreateInstance(modalEl).show(); else if (window.jQuery) jQuery(modalEl).modal('show'); }

    document.querySelectorAll('[data-gd-new]').forEach(function (b) { b.addEventListener('click', function () { fill(null); open(); }); });
    document.querySelectorAll('[data-gd-edit]').forEach(function (b) { b.addEventListener('click', function () { fill(JSON.parse(b.getAttribute('data-gd-edit'))); open(); }); });
    form.addEventListener('input', paint);
    form.addEventListener('change', paint);
    modalEl.addEventListener('shown.bs.modal', function () { f('gdName').focus(); });

    document.querySelectorAll('[data-gd-delete]').forEach(function (fm) {
        fm.addEventListener('submit', function (e) {
            e.preventDefault();
            var go = function () { fm.submit(); };
            if (window.Swal) {
                Swal.fire({ title: 'Delete "' + fm.getAttribute('data-gd-delete') + '"?', text: 'Booking forms stop applying it. Bookings that already got it keep their price.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#de3b3d', confirmButtonText: 'Delete', cancelButtonText: 'Cancel' })
                    .then(function (r) { if (r.isConfirmed) go(); });
            } else if (confirm('Delete this discount?')) { go(); }
        });
    });

    // reopen the form with what was typed when saving failed
    @if ($reopen !== null)
        @php
            $editing = $reopen !== 'new' ? $discounts->firstWhere('id', (int) $reopen) : null;
        @endphp
        document.addEventListener('DOMContentLoaded', function () {
            @if ($editing)
                form.action = @json(route('global-discount.update', $editing->id)); f('gdMethod').value = 'PUT'; f('gdFormId').value = @json($editing->id); f('gdModalTitle').textContent = 'Edit discount';
            @endif
            paint(); open();
        });
    @else
        paint();
    @endif
})();
</script>
@endsection
