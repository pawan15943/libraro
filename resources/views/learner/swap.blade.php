@extends('layouts.library')

@section('content')
@php
$planEndDate = $customer->plan_end_date;
$today = \Carbon\Carbon::today();

if ($planEndDate) {
    $endDate = \Carbon\Carbon::parse($planEndDate);
    $diffInDays = $today->diffInDays($endDate, false); // negative if in past
    $extendDays = function_exists('getExtendDays') ? getExtendDays() : 0;
    $inextendDate = $endDate->copy()->addDays($extendDays);
    $diffExtendDay = $today->diffInDays($inextendDate, false);

    if ($diffInDays < 0 && $diffExtendDay < 0) {
        $statusText = 'Expired ' . abs($diffInDays) . ' days ago';
        $statusClass = 'status-expired';
        $statusIcon = 'fa-solid fa-circle-xmark';
    } elseif ($diffInDays < 0 && $diffExtendDay > 0) {
        $statusText = 'Extension: ' . abs($diffExtendDay) . ' days left';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-clock-rotate-left';
    } elseif ($diffInDays < 0 && $diffExtendDay == 0) {
        $statusText = 'Extension ends today';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-triangle-exclamation';
    } elseif ($diffInDays == 0) {
        $statusText = 'Expires today';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-triangle-exclamation';
    } elseif ($diffInDays == 1) {
        $statusText = 'Expires in 1 day';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-clock';
    } elseif ($diffInDays <= 5) {
        $statusText = 'Expires in ' . $diffInDays . ' days';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-clock';
    } else {
        $statusText = 'Active (Expires in ' . $diffInDays . ' days)';
        $statusClass = 'status-active';
        $statusIcon = 'fa-solid fa-circle-check';
    }
} else {
    $statusText = 'Active';
    $statusClass = 'status-active';
    $statusIcon = 'fa-solid fa-circle-check';
}

$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class = $planDetails['class'];
@endphp

@php
    // ---- view-only helpers (no data changes) ----
    $lswName = trim((string) ($customer->name ?? ($customer->learner->name ?? 'Learner')));
    $lswFirst = \Illuminate\Support\Str::of($lswName)->explode(' ')->first() ?: $lswName;
    $lswInitials = collect(explode(' ', $lswName))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: 'L';
    $lswPhoto = !empty($customer->profile_picture) ? asset($customer->profile_picture) : null;
    $lswTone = $statusClass === 'status-expired' ? 'bad' : ($statusClass === 'status-warning' ? 'warn' : 'ok');
    $lswSeatMap = collect(generateSeatNumbers());
    $lswCurSeat = $customer->seat_no ?? null;
    $lswCurLabel = $lswCurSeat ? (getSeatDisplayShortFloorName($lswCurSeat) ?? $lswCurSeat) : 'General';
    $lswShift = $customer->plan_type_name ?? 'N/A';
    $lswSeats = collect($newAvailableSeats)->map(function ($v) {
        $v = (array) $v;
        $floorName = $v['floor_name'] ?? null;
        $short = $floorName ? shortFloorName($floorName) : '';
        return [
            'main' => $v['main'],
            'num' => $v['floor'] ?? $v['main'],
            'short' => $short,
            'floor' => $floorName ?: 'Other seats',
            'display' => $v['display'] ?? ('Seat No - ' . $v['main']),
        ];
    })->values();
    $lswFloors = $lswSeats->pluck('floor')->unique()->values();
@endphp

<link rel="stylesheet" href="{{ asset('public/css/lb-swap.css') }}?v={{ @filemtime(public_path('css/lb-swap.css')) }}" />

<input id="swap_plan_type_id" type="hidden" name="plan_type_id" value="{{ $customer->plan_type_id }}">

<div class="lsw" id="operationContent">
    <form action="{{ route('learners.swap-seat', $customer->id) }}" method="POST" enctype="multipart/form-data" id="swapseat" class="lsw-sheet" novalidate>
        @csrf
        @method('PUT')
        <input id="user_id" type="hidden" name="learner_id" value="{{ $customer->id }}">
        <input type="hidden" value="{{ $customer->seat_no }}" id="swap_old_value">
        <input type="hidden" id="swap_plan_type_id" value="{{ $customer->plan_type_id }}">
        {{-- native select: the value that is submitted + what the availability check listens to --}}
        <select name="seat_id" id="new_seat_id" class="d-none @error('seat_id') is-invalid @enderror" tabindex="-1" aria-hidden="true">
            <option value="">Select seat</option>
            @foreach($lswSeats as $s)
            <option value="{{ $s['main'] }}">{{ $s['display'] }}</option>
            @endforeach
        </select>

        {{-- header --}}
        <header class="lsw-head">
            <span class="lsw-head-ic"><x-lb-icon name="arrow-left-right" /></span>
            <div class="lsw-head-t">
                <span class="lsw-kicker">Swap seat</span>
                <h2>Move {{ $lswFirst }} to another seat</h2>
            </div>
            <a href="{{ route('learners') }}" class="lsw-close" title="Back to learners" aria-label="Back to learners"><x-lb-icon name="x" /></a>
        </header>

        <div class="lsw-body">
            {{-- member card --}}
            <section class="lsw-member">
                <div class="lsw-member-top">
                    <span class="lsw-ava">
                        @if($lswPhoto)<img src="{{ $lswPhoto }}" alt="{{ $lswName }}" loading="lazy">@else{{ $lswInitials }}@endif
                    </span>
                    <div class="lsw-member-id">
                        <h3><span class="lsw-nm">{{ $lswName }}</span> <span class="lsw-name-seat"><x-lb-icon name="armchair" />{{ $lswCurSeat ? 'Seat ' . $lswCurLabel : 'General seat' }}</span></h3>
                        <div class="lsw-member-tags">
                            <span class="lsw-uid">UID: {{ $customer->learner_no ?? ('#' . $customer->id) }}</span>
                            <span class="lsw-pill is-{{ $lswTone }}"><x-lb-icon name="{{ $lswTone === 'ok' ? 'circle-check' : ($lswTone === 'bad' ? 'circle-x' : 'clock') }}" />{{ $statusText }}</span>
                        </div>
                    </div>
                </div>
                <div class="lsw-chips">
                    <span class="lsw-chip"><x-lb-icon name="calendar-days" />{{ $customer->plan_name ?? 'Plan' }}</span>
                    <span class="lsw-chip"><x-lb-icon name="clock" />{{ $lswShift }}</span>
                    @if($customer->plan_end_date)
                    <span class="lsw-chip"><x-lb-icon name="calendar-check" />Till {{ \Carbon\Carbon::parse($customer->plan_end_date)->format('d M Y') }}</span>
                    @endif
                </div>
            </section>

            {{-- from → to --}}
            <section class="lsw-route" aria-label="Seat change">
                <div class="lsw-from">
                    <span class="lsw-route-k">Current seat <x-lb-icon name="lock" /></span>
                    <strong>{{ $lswCurSeat ? $lswCurLabel : 'General' }}</strong>
                    <small>{{ $lswShift }}</small>
                </div>
                <span class="lsw-route-arrow" aria-hidden="true"><x-lb-icon name="arrow-right" /></span>
                <div class="lsw-to" id="lswTo" data-state="empty">
                    <span class="lsw-route-k">New seat</span>
                    <strong id="lswToLabel">Pick below</strong>
                    <small id="lswToSub">{{ $lswShift }}</small>
                </div>
            </section>

            {{-- seat picker --}}
            <section class="lsw-pick">
                <div class="lsw-pick-head">
                    <h4>Choose new seat</h4>
                    <span class="lsw-count" id="lswCount">{{ $lswSeats->count() }} free</span>
                    @if($lswSeats->count())
                    <span class="lsw-rail-nav">
                        <button type="button" class="lsw-rail-btn" id="lswPrev" aria-label="Previous seats"><x-lb-icon name="chevron-left" /></button>
                        <button type="button" class="lsw-rail-btn" id="lswNext" aria-label="Next seats"><x-lb-icon name="chevron-right" /></button>
                    </span>
                    @endif
                </div>

                @if($lswSeats->count())
                <div class="lsw-tools">
                    <label class="lsw-search">
                        <x-lb-icon name="search" />
                        <input type="search" id="seat_search_input" placeholder="Search seat number, e.g. 12" autocomplete="off" spellcheck="false" inputmode="search" aria-controls="lswGrid">
                        <button type="button" class="lsw-search-x" id="lswSearchX" aria-label="Clear search" hidden><x-lb-icon name="x" /></button>
                    </label>
                    @if($lswFloors->count() > 1)
                    <div class="lsw-floors" role="tablist" aria-label="Floors">
                        <button type="button" class="lsw-floor is-on" data-floor="" role="tab" aria-selected="true"><x-lb-icon name="layers" />All</button>
                        @foreach($lswFloors as $f)
                        <button type="button" class="lsw-floor" data-floor="{{ $f }}" role="tab" aria-selected="false">{{ $f }}</button>
                        @endforeach
                    </div>
                    @endif
                </div>

                <div class="lsw-grid" id="lswGrid" role="listbox" aria-label="Available seats" aria-orientation="horizontal">
                    @foreach($lswSeats as $s)
                    <button type="button" class="lsw-seat" role="option" aria-selected="false"
                        data-value="{{ $s['main'] }}" data-floor="{{ $s['floor'] }}"
                        data-label="{{ $s['num'] }}{{ $s['short'] ? ' (' . $s['short'] . ')' : '' }}"
                        data-q="{{ strtolower($s['num'] . ' ' . $s['main'] . ' ' . $s['short'] . $s['num'] . ' ' . $s['num'] . $s['short'] . ' ' . $s['floor']) }}">
                        <x-lb-icon name="armchair" />
                        <b>{{ $s['num'] }}</b>
                        @if($s['short'])<small>{{ $s['short'] }}</small>@endif
                    </button>
                    @endforeach
                </div>
                <p class="lsw-empty" id="lswEmpty" hidden><x-lb-icon name="search" /> No free seat matches “<span id="lswEmptyQ"></span>”</p>
                @else
                <div class="lsw-none">
                    <span class="lsw-none-ic"><x-lb-icon name="armchair" /></span>
                    <strong>No vacant seat in {{ $lswShift }}</strong>
                    <p>Every seat is taken in this shift right now. Free a seat or change the plan’s shift, then try again.</p>
                </div>
                @endif

                @error('seat_id')
                <p class="lsw-err"><x-lb-icon name="circle-x" />{{ $message }}</p>
                @enderror

                {{-- live availability (filled by the existing seat check) --}}
                <div class="lsw-status" id="lswStatus" aria-live="polite"><div id="swap_status"></div></div>
            </section>
        </div>

        {{-- footer --}}
        <footer class="lsw-foot">
            <div class="lsw-fee">
                <span>Fee change</span>
                <strong>No extra fee</strong>
            </div>
            <a href="{{ route('learners') }}" class="lsw-btn-ghost">Cancel</a>
            <button type="submit" class="lsw-btn-primary btn-submit-swap no-auto-enable" id="swapsubmit" disabled>
                <x-lb-icon name="arrow-left-right" /><span>Swap seat</span>
            </button>
        </footer>
    </form>
</div>

<script>
    function setSubmitButtonState(enabled) {
        const btn = document.getElementById('swapsubmit');
        if (!btn) return;
        btn.disabled = !enabled;
        if (enabled) btn.removeAttribute('disabled'); else btn.setAttribute('disabled', 'disabled');
        btn.style.pointerEvents = ''; btn.style.opacity = ''; btn.style.cursor = '';
        if (window.jQuery) $(btn).prop('disabled', !enabled).css({ 'pointer-events': '', 'opacity': '', 'cursor': '' });
    }
    window.setSubmitButtonState = setSubmitButtonState;
    setSubmitButtonState(false);

    document.addEventListener('DOMContentLoaded', function () {
        setSubmitButtonState(false);
        if (typeof handleFormChanges === 'function') handleFormChanges('swapseat', "{{ $customer->id }}");

        const form = document.getElementById('swapseat');
        const select = document.getElementById('new_seat_id');
        const grid = document.getElementById('lswGrid');
        const search = document.getElementById('seat_search_input');
        const searchX = document.getElementById('lswSearchX');
        const empty = document.getElementById('lswEmpty');
        const emptyQ = document.getElementById('lswEmptyQ');
        const count = document.getElementById('lswCount');
        const to = document.getElementById('lswTo');
        const toLabel = document.getElementById('lswToLabel');
        const status = document.getElementById('swap_status');
        const statusBox = document.getElementById('lswStatus');
        const submit = document.getElementById('swapsubmit');
        const seats = grid ? Array.from(grid.querySelectorAll('.lsw-seat')) : [];
        const total = seats.length;
        let floor = '';
        let chosen = null;

        function seatState(state) {
            // state: empty | checking | ok | bad
            to.setAttribute('data-state', state);
            if (chosen) chosen.setAttribute('data-state', state === 'empty' ? '' : state);
        }

        function choose(btn) {
            if (chosen === btn) return;
            seats.forEach(s => { s.classList.remove('is-on'); s.setAttribute('aria-selected', 'false'); s.removeAttribute('data-state'); });
            chosen = btn || null;
            if (chosen) {
                chosen.classList.add('is-on');
                chosen.setAttribute('aria-selected', 'true');
                chosen.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
                toLabel.textContent = chosen.getAttribute('data-label');
                seatState('checking');
            } else {
                toLabel.textContent = 'Pick below';
                seatState('empty');
            }
            select.value = chosen ? chosen.getAttribute('data-value') : '';
            setSubmitButtonState(false);
            // the shared learner script checks availability on this change event and enables the button
            if (window.jQuery) { $(select).trigger('change'); } else { select.dispatchEvent(new Event('change', { bubbles: true })); }
        }

        seats.forEach(btn => btn.addEventListener('click', () => choose(chosen === btn ? null : btn)));

        // keyboard: arrows move between visible seats, Enter/Space picks
        if (grid) grid.addEventListener('keydown', function (e) {
            if (!['ArrowRight', 'ArrowLeft', 'ArrowDown', 'ArrowUp', 'Home', 'End'].includes(e.key)) return;
            const vis = seats.filter(s => !s.hidden);
            let i = vis.indexOf(document.activeElement);
            if (i < 0) return;
            if (e.key === 'ArrowUp') { e.preventDefault(); if (search) search.focus(); return; }
            if (e.key === 'ArrowRight' || e.key === 'ArrowDown') i++; else if (e.key === 'ArrowLeft') i--; else if (e.key === 'Home') i = 0; else i = vis.length - 1;
            i = Math.max(0, Math.min(vis.length - 1, i));
            e.preventDefault(); vis[i].focus({ preventScroll: true }); vis[i].scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
        });

        // Rail arrows: page by the visible width, disabled at the ends
        const prev = document.getElementById('lswPrev'), next = document.getElementById('lswNext');
        function railEdges() {
            if (!grid || !prev) return;
            const max = grid.scrollWidth - grid.clientWidth - 2;
            prev.disabled = grid.scrollLeft <= 2;
            next.disabled = grid.scrollLeft >= max;
        }
        function railBy(dir) { grid.scrollBy({ left: dir * Math.max(160, grid.clientWidth - 90), behavior: 'smooth' }); }
        if (prev) { prev.addEventListener('click', () => railBy(-1)); next.addEventListener('click', () => railBy(1)); }
        if (grid) {
            grid.addEventListener('scroll', railEdges, { passive: true });
            window.addEventListener('resize', railEdges);
            // mouse wheel scrolls the rail sideways (trackpads already do)
            grid.addEventListener('wheel', function (e) {
                if (Math.abs(e.deltaY) <= Math.abs(e.deltaX) || grid.scrollWidth <= grid.clientWidth) return;
                const atStart = grid.scrollLeft <= 0 && e.deltaY < 0, atEnd = grid.scrollLeft >= grid.scrollWidth - grid.clientWidth - 1 && e.deltaY > 0;
                if (atStart || atEnd) return;
                e.preventDefault(); grid.scrollLeft += e.deltaY;
            }, { passive: false });
            railEdges();
        }

        function applyFilter() {
            const q = (search ? search.value : '').trim().toLowerCase().replace(/^seat\s*(no\.?)?\s*-?\s*/, '');
            let n = 0;
            seats.forEach(s => {
                const okFloor = !floor || s.getAttribute('data-floor') === floor;
                const hay = s.getAttribute('data-q');
                const okQ = !q || hay.split(' ').some(t => t === q || t.startsWith(q)) || hay.includes(q);
                s.hidden = !(okFloor && okQ);
                if (!s.hidden) n++;
            });
            if (count) count.textContent = (q || floor) ? n + ' of ' + total + ' free' : total + ' free';
            if (empty) { empty.hidden = n > 0; if (emptyQ) emptyQ.textContent = search.value.trim() || floor; }
            if (searchX) searchX.hidden = !search.value;
            if (grid) { grid.scrollLeft = 0; railEdges(); }
            return seats.filter(s => !s.hidden);
        }
        if (search) {
            search.addEventListener('input', applyFilter);
            search.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();               // never submit from the search box
                    const vis = applyFilter();
                    const q = search.value.trim().toLowerCase();
                    const exacts = vis.filter(s => s.getAttribute('data-q').split(' ').slice(0, 3).includes(q));
                    const pick = exacts.length === 1 ? exacts[0] : (vis.length === 1 ? vis[0] : null);
                    if (pick) { choose(pick); pick.focus(); } else if (vis.length) { vis[0].focus(); }
                } else if (e.key === 'ArrowDown') {
                    const v = seats.find(s => !s.hidden); if (v) { e.preventDefault(); v.focus(); }
                } else if (e.key === 'Escape') { search.value = ''; applyFilter(); }
            });
        }
        if (searchX) searchX.addEventListener('click', () => { search.value = ''; applyFilter(); search.focus(); });
        document.querySelectorAll('.lsw-floor').forEach(b => b.addEventListener('click', function () {
            floor = this.getAttribute('data-floor');
            document.querySelectorAll('.lsw-floor').forEach(x => { const on = x === this; x.classList.toggle('is-on', on); x.setAttribute('aria-selected', String(on)); });
            applyFilter();
        }));

        // Mirror the availability message into the seat + target box states
        if (status) new MutationObserver(function () {
            const txt = status.textContent.trim().toLowerCase();
            const html = status.innerHTML.toLowerCase();
            let st = '';
            if (!txt) st = '';
            else if (html.includes('fa-spin') || txt.startsWith('checking')) st = 'checking';
            else if (txt.includes('available to swap') && !txt.includes('not')) st = 'ok';
            else st = 'bad';
            statusBox.setAttribute('data-state', st);
            if (chosen) seatState(st || 'checking');
            if (st === 'ok' || st === 'bad') { try { statusBox.scrollIntoView({ block: 'nearest', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' }); } catch (e) {} }
        }).observe(status, { childList: true, subtree: true, characterData: true });

        // Submit guard (same rule as before: only a seat from the available list) + one-tap protection
        form.addEventListener('submit', function (e) {
            const val = select.value;
            const inList = seats.some(s => s.getAttribute('data-value') === val && val !== '');
            if (!inList || submit.disabled) {
                e.preventDefault(); e.stopPropagation();
                setSubmitButtonState(false);
                statusBox.setAttribute('data-state', 'bad');
                status.innerHTML = '<span>Please pick an available seat before swapping.</span>';
                (search || grid || submit).focus && (search || submit).focus();
                return false;
            }
            setTimeout(function () { submit.disabled = true; submit.classList.add('is-busy'); submit.querySelector('span').textContent = 'Swapping…'; }, 0);
        });

        // Back/forward cache: reset the busy button
        window.addEventListener('pageshow', function (e) { if (e.persisted) { submit.classList.remove('is-busy'); submit.querySelector('span').textContent = 'Swap seat'; choose(null); } });
    });
</script>

@endsection
