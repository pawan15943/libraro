@extends('layouts.library')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
@endpush

@section('content')

@php
    use Carbon\Carbon;
    use App\Helpers\HelperService;

    $views = [
        'Seats' => [
            'total_booking' => 'All bookings',
            'active_booking' => 'Active seats',
            'expired_seats' => 'Expired seats',
            'expired_in_five' => 'Expiring in 5 days',
            'extended_seat' => 'In extension',
        ],
        'This month' => [
            'thisbooking_slot' => 'All this month',
            'booing_slot' => 'Booked this month',
            'expire_booking_slot' => 'Expired this month',
            'till_previous_book' => 'Carried from last month',
        ],
        'Payments' => [
            'online_paid' => 'Paid online',
            'offline_paid' => 'Paid in cash',
            'other_paid' => 'Pay later',
        ],
        'Activity' => [
            'renew_seat' => 'Renewals',
            'learnerUpgrade' => 'Upgrades',
            'change_plan_seat' => 'Plan changes',
            'swap_seat' => 'Seat swaps',
            'reactive_seat' => 'Reactivations',
            'close_seat' => 'Closed seats',
            'delete_seat' => 'Deleted seats',
        ],
    ];
    $flat = collect($views)->collapse();
    $typeKey = request('type', $type ?? '');
    $title = $flat[$typeKey] ?? ucwords(str_replace('_', ' ', $typeKey ?: 'Seats'));
    $isActivity = array_key_exists($typeKey, $views['Activity']);
    $usesMonth = !in_array($typeKey, ['expired_in_five', 'extended_seat'], true);
    $y = (int) request('year', date('Y'));
    $m = (int) request('month', date('m'));
    $periodText = $usesMonth ? date('F Y', mktime(0, 0, 0, $m, 1, $y)) : 'As of today · ' . now()->format('d M Y');
    $fmtDate = function ($d) { try { return $d && $d !== 'N/A' ? Carbon::parse($d)->format('d M Y') : '—'; } catch (\Throwable $e) { return (string) $d; } };
    $today = Carbon::today();

    // Normalise the four row shapes the controller returns — once — for the tiles and the table.
    $rows = [];
    foreach ($result as $data) {
        $opMsg = ''; $opType = ''; $opDate = null;
        if (!empty($data->operation_date)) {
            $learner = App\Models\Learner::withTrashed()->find($data->learner_id);
            $ld = App\Models\LearnerDetail::withTrashed()->with(['plan', 'planType'])->find($data->learner_detail_id);
            $op = DB::table('learner_operations_log')->where('learner_id', $data->learner_id)->where('learner_detail_id', $data->learner_detail_id)
                ->where('operation', $data->operation)->whereDate('created_at', $data->operation_date)->first();
            if ($op) { $op->learner_name = $learner->name ?? 'Learner'; $op->learner_seat_no = $learner->seat_no ?? null; }
            $details = $op ? HelperService::getOperationDetails($op) : [];
            $r = [
                'id' => $learner->id ?? $data->learner_id, 'name' => $learner->name ?? 'Learner', 'uid' => $learner->learner_no ?? null,
                'seat' => $learner->seat_no ?? null, 'mobile' => $learner->mobile ?? '', 'email' => $learner->email ?? '',
                'plan' => $ld->plan->name ?? null, 'shift' => $ld->planType->name ?? null,
                'start' => $ld->plan_start_date ?? null, 'end' => $ld->plan_end_date ?? null,
                'active' => $ld && $ld->status == 1, 'paid' => $ld && $ld->is_paid == 1,
            ];
            $opMsg = $details['message'] ?? ''; $opType = $details['operation_type'] ?? ''; $opDate = $data->operation_date;
        } elseif (!empty($data->max_plan_start_date)) {
            $ld = App\Models\LearnerDetail::with(['plan', 'planType'])->where('learner_id', $data->learner_id)->where('plan_start_date', $data->max_plan_start_date)->first();
            $r = [
                'id' => $data->learner_id ?? $data->id, 'name' => $data->name ?? 'Learner', 'uid' => $data->learner_no ?? null,
                'seat' => $data->seat_no ?? null, 'mobile' => $data->mobile ?? '', 'email' => $data->email ?? '',
                'plan' => $ld->plan->name ?? null, 'shift' => $ld->planType->name ?? null,
                'start' => $data->max_plan_start_date, 'end' => $data->max_plan_end_date,
                'active' => $ld && $ld->status == 1, 'paid' => $ld && $ld->is_paid == 1,
            ];
        } else {
            $r = [
                'id' => $data->learner_id ?? $data->id ?? null, 'name' => $data->name ?? 'Learner', 'uid' => $data->learner_no ?? null,
                'seat' => $data->seat_no ?? null, 'mobile' => $data->mobile ?? '', 'email' => $data->email ?? '',
                'plan' => optional($data->plan)->name, 'shift' => optional($data->planType)->name,
                'start' => $data->plan_start_date ?? null, 'end' => $data->plan_end_date ?? null,
                'active' => ($data->status ?? 0) == 1, 'paid' => ($data->is_paid ?? 0) == 1,
            ];
        }
        $phone = preg_replace('/[^0-9]/', '', (string) $r['mobile']);
        if (strlen($phone) > 10 && str_starts_with($phone, '91')) $phone = substr($phone, 2);
        $days = null;
        try { $days = $r['end'] ? $today->diffInDays(Carbon::parse($r['end'])->startOfDay(), false) : null; } catch (\Throwable $e) {}
        $rows[] = $r + ['phone' => $phone, 'days' => $days, 'opMsg' => $opMsg, 'opType' => $opType, 'opDate' => $opDate];
    }
    $total = count($rows);
    $cnt = fn ($k, $v) => count(array_filter($rows, fn ($r) => $r[$k] === $v));
    $tiles = [
        ['all', 'All members', $total, 'users', 'all'],
        ['active', 'Active', $cnt('active', true), 'user-check', 'active'],
        ['inactive', 'Inactive', $cnt('active', false), 'user-x', 'inactive'],
        ['paid', 'Paid', $cnt('paid', true), 'badge-check', 'paid'],
        ['unpaid', 'Unpaid', $cnt('paid', false), 'indian-rupee', 'unpaid'],
    ];
    if ($typeKey === 'extended_seat') {
        $tiles[] = ['', 'Grace period', '+' . ($extendDay ?? 0) . 'd', 'hourglass', 'grace'];
    }
    $baseParams = fn (array $p) => array_filter(array_merge(['type' => $typeKey, 'year' => $y, 'month' => $m], $p), fn ($v) => $v !== null && $v !== '');
@endphp

<div class="lpg ltv lsv" id="svRoot" data-base="{{ route('learners.list.view') }}">

    <header class="lpg-head ltv-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><span>Seats</span></div>
            <h1 id="svTitle">{{ $title }}</h1>
            <p id="svPeriod">{{ $periodText }}</p>
        </div>
        <div class="lpg-head-r">
            <label class="lpg-field is-select lsv-view" title="Choose a list">
                <x-lb-icon name="layers" />
                <select id="svType" aria-label="List">
                    @foreach($views as $group => $items)
                        <optgroup label="{{ $group }}">
                            @foreach($items as $k => $label)
                                <option value="{{ $k }}" @selected($typeKey === $k)>{{ $label }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </label>
            <label class="lpg-field ltv-month" title="Choose month" @unless($usesMonth) hidden @endunless>
                <x-lb-icon name="calendar-days" />
                <input type="month" id="svMonth" value="{{ sprintf('%04d-%02d', $y, $m) }}" aria-label="Month">
            </label>
        </div>
    </header>

    <nav class="ltv-kpis lsv-kpis" id="svKpis" aria-label="Filter by status">
        @foreach($tiles as [$key, $label, $value, $icon, $tone])
            @if($key !== '')
                @php $pct = $total ? (int) round($value / $total * 100) : 0; @endphp
                <button type="button" class="lsv-tile t-{{ $tone }} {{ $key === 'all' ? 'is-on' : '' }}" data-filter="{{ $key }}" aria-pressed="{{ $key === 'all' ? 'true' : 'false' }}">
                    <span class="lsv-tile-ic"><x-lb-icon :name="$icon" /></span>
                    <span class="lsv-tile-t"><b>{{ $value }}</b><small>{{ $label }}</small></span>
                    @if($key !== 'all')<em>{{ $pct }}%</em>@endif
                    <i class="lsv-tile-bar" style="width: {{ $key === 'all' ? 100 : $pct }}%"></i>
                </button>
            @else
                <div class="lsv-tile t-{{ $tone }} is-static">
                    <span class="lsv-tile-ic"><x-lb-icon :name="$icon" /></span>
                    <span class="lsv-tile-t"><b>{{ $value }}</b><small>{{ $label }}</small></span>
                </div>
            @endif
        @endforeach
    </nav>

    <div class="lpg-tools ltv-tools">
        <label class="lpg-search">
            <x-lb-icon name="search" />
            <input type="search" id="svSearch" placeholder="Search name, mobile, seat or UID" autocomplete="off" enterkeyhint="search">
            <span class="ltv-spin" aria-hidden="true"></span>
        </label>
        <div class="ltv-filters">
            <label class="lpg-field is-select" title="Sort">
                <x-lb-icon name="arrow-up-down" />
                <select id="svSort" aria-label="Sort by">
                    <option value="">{{ $isActivity ? 'Newest activity' : 'Default order' }}</option>
                    <option value="end">Ends soonest</option>
                    <option value="end-desc">Ends latest</option>
                    <option value="seat">Seat number</option>
                    <option value="name">Name A–Z</option>
                </select>
            </label>
        </div>
    </div>

    <section id="svList" aria-live="polite">
        <div class="ltv-bar">
            <p><b id="svShown">{{ $total }}</b> of {{ $total }} {{ \Illuminate\Support\Str::plural('member', $total) }}</p>
            <div class="ltv-bar-r">
                <label class="ltv-pp">Show
                    <select id="svPerPage" aria-label="Rows per page">
                        @foreach([25, 50, 100] as $pp)<option value="{{ $pp }}">{{ $pp }}</option>@endforeach
                    </select>
                </label>
                @if($total)
                    <button type="button" class="lpg-ibtn ltv-csv" id="svCsv" title="Download as CSV" aria-label="Download CSV"><x-lb-icon name="download" /></button>
                @endif
            </div>
        </div>

        @if($total)
            <div class="ltv-table lsv-table" role="table" aria-label="{{ $title }}">
                <div class="ltv-tr lsv-tr ltv-th" role="row">
                    <span role="columnheader">#</span>
                    <span role="columnheader">Member</span>
                    <span role="columnheader">Plan · shift</span>
                    <span role="columnheader">Duration</span>
                    <span role="columnheader">Status</span>
                    <span role="columnheader" class="ltv-r">Actions</span>
                </div>
                @foreach($rows as $i => $r)
                    @php
                        $seatTxt = $r['seat'] ? getSeatDisplayShortFloorName($r['seat']) : 'GEN';
                        $d = $r['days'];
                        $dueTone = $d === null ? '' : ($d < 0 ? 'is-bad' : ($d <= 5 ? 'is-warn' : ''));
                        $dueTxt = $d === null ? '' : ($d < 0 ? 'Ended ' . abs($d) . 'd ago' : ($d === 0 ? 'Ends today' : 'Ends in ' . $d . 'd'));
                    @endphp
                    <div class="ltv-tr lsv-tr" role="row"
                         data-i="{{ $i }}"
                         data-q="{{ strtolower($r['name'] . ' ' . $r['mobile'] . ' ' . $seatTxt . ' ' . $r['uid'] . ' ' . $r['plan'] . ' ' . $r['shift']) }}"
                         data-active="{{ $r['active'] ? 1 : 0 }}" data-paid="{{ $r['paid'] ? 1 : 0 }}"
                         data-end="{{ $r['end'] ? \Illuminate\Support\Str::of($r['end'])->substr(0, 10) : '' }}" data-seat="{{ (int) $r['seat'] }}" data-name="{{ strtolower($r['name']) }}"
                         data-csv="{{ json_encode([$r['name'], $r['uid'], $seatTxt, $r['mobile'], $r['plan'], $r['shift'], $fmtDate($r['start']), $fmtDate($r['end']), $r['active'] ? 'Active' : 'Inactive', $r['paid'] ? 'Paid' : 'Unpaid', $r['opType']]) }}">
                        <span class="lsv-no" role="cell">{{ $i + 1 }}</span>
                        <span class="lsv-who" role="cell">
                            <span class="lsv-who-t">
                                <span class="lsv-name">
                                    @if($r['id'])
                                        <a href="{{ route('learners.show', $r['id']) }}"><b>{{ $r['name'] }}</b></a>
                                    @else
                                        <b>{{ $r['name'] }}</b>
                                    @endif
                                    <span class="lsv-chip c-seat"><x-lb-icon name="armchair" />{{ $seatTxt === 'GEN' ? 'General' : 'Seat ' . $seatTxt }}</span>
                                </span>
                                <small>
                                    @if($r['phone'])<a href="tel:+91{{ $r['phone'] }}" class="lsv-tel">+91 {{ display_learner_mobile($r['phone']) }}</a>@endif
                                    @if($r['uid'])<span>{{ $r['uid'] }}</span>@endif
                                </small>
                                @if($r['opMsg'])
                                    <span class="lsv-op">@if($r['opType'])<i>{{ $r['opType'] }}</i>@endif{!! $r['opMsg'] !!}@if($r['opDate'])<em>{{ $fmtDate($r['opDate']) }}</em>@endif</span>
                                @endif
                            </span>
                        </span>
                        <span class="lsv-plan" role="cell"><b>{{ $r['plan'] ?: '—' }}</b><small>{{ $r['shift'] ?: '' }}</small></span>
                        <span class="lsv-dur" role="cell">
                            <span>{{ $fmtDate($r['start']) }} – {{ $fmtDate($r['end']) }}</span>
                            @if($dueTxt)<small class="{{ $dueTone }}">{{ $dueTxt }}</small>@endif
                        </span>
                        <span class="lsv-st" role="cell">
                            <span class="lsv-chip {{ $r['active'] ? 'c-active' : 'c-inactive' }}"><i></i>{{ $r['active'] ? 'Active' : 'Inactive' }}</span>
                            <span class="lsv-chip {{ $r['paid'] ? 'c-paid' : 'c-unpaid' }}"><i></i>{{ $r['paid'] ? 'Paid' : 'Unpaid' }}</span>
                        </span>
                        <span class="lsv-act" role="cell">
                            @if($r['phone'])
                                <a href="https://wa.me/91{{ $r['phone'] }}" target="_blank" rel="noopener" class="lpg-round lsv-wa noLoader" title="WhatsApp" aria-label="WhatsApp {{ $r['name'] }}"><x-lb-icon name="message-circle" /></a>
                            @endif
                            @if($r['id'])
                                <a href="{{ route('learners.show', $r['id']) }}" class="lpg-round" title="View profile" aria-label="View {{ $r['name'] }}"><x-lb-icon name="eye" /></a>
                                <a href="{{ route('learners.edit.plan', $r['id']) }}" class="lpg-round" title="Edit plan" aria-label="Edit plan for {{ $r['name'] }}"><x-lb-icon name="square-pen" /></a>
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
            <div class="lpg-empty" id="svNone" hidden style="margin-top:8px">
                <span><x-lb-icon name="search" /></span>
                <b>No members match</b>
                <p>Try a different search or tap “All”.</p>
            </div>
            <ul class="lpg-pages" id="svPages"></ul>
        @else
            <div class="lpg-empty">
                <span><x-lb-icon name="inbox" /></span>
                <b>Nothing in “{{ $title }}”</b>
                <p>{{ $usesMonth ? 'No members for ' . $periodText . '. Try another month or list.' : 'No members are in this list right now.' }}</p>
            </div>
        @endif
    </section>
</div>

<script>
(function () {
    var root = document.getElementById('svRoot');
    if (!root) return;
    var base = root.getAttribute('data-base');
    var state = { filter: 'all', q: '', sort: '', per: 25, page: 1 };
    var busy = null;

    function rows() { return Array.prototype.slice.call(root.querySelectorAll('#svList .lsv-tr[data-i]')); }

    // ---------- instant, in-page: search / status tiles / sort / pages ----------
    // rows matching the current tile + search, in the chosen order
    function matching() {
        var list = rows().filter(function (r) {
            var f = state.filter;
            if (f === 'active' && r.dataset.active !== '1') return false;
            if (f === 'inactive' && r.dataset.active !== '0') return false;
            if (f === 'paid' && r.dataset.paid !== '1') return false;
            if (f === 'unpaid' && r.dataset.paid !== '0') return false;
            return !state.q || r.dataset.q.indexOf(state.q) !== -1;
        });
        var by = state.sort;
        list.sort(function (a, b) {
            if (by === 'end' || by === 'end-desc') {
                var x = a.dataset.end || '9999', y = b.dataset.end || '9999';
                return by === 'end' ? x.localeCompare(y) : y.localeCompare(x);
            }
            if (by === 'seat') return (+a.dataset.seat || 1e9) - (+b.dataset.seat || 1e9);
            if (by === 'name') return a.dataset.name.localeCompare(b.dataset.name);
            return +a.dataset.i - +b.dataset.i;
        });
        return list;
    }

    function render() {
        var all = rows();
        if (!all.length) return;
        var list = matching();
        var pages = Math.max(1, Math.ceil(list.length / state.per));
        if (state.page > pages) state.page = pages;
        var from = (state.page - 1) * state.per, to = from + state.per;
        var table = root.querySelector('.lsv-table');
        all.forEach(function (r) { r.hidden = true; });
        list.forEach(function (r, i) {
            table.appendChild(r);
            r.hidden = i < from || i >= to;
            r.querySelector('.lsv-no').textContent = i + 1;
        });
        root.querySelector('#svShown').textContent = list.length;
        root.querySelector('#svNone').hidden = list.length > 0;
        var ul = root.querySelector('#svPages'), html = '';
        if (pages > 1) {
            html += '<li><a href="#" data-pg="' + (state.page - 1) + '" class="' + (state.page === 1 ? 'is-off' : '') + '" aria-label="Previous page">‹</a></li>';
            for (var p = Math.max(1, state.page - 2); p <= Math.min(pages, state.page + 2); p++) {
                html += '<li><a href="#" data-pg="' + p + '" class="' + (p === state.page ? 'is-on' : '') + '">' + p + '</a></li>';
            }
            html += '<li><a href="#" data-pg="' + (state.page + 1) + '" class="' + (state.page === pages ? 'is-off' : '') + '" aria-label="Next page">›</a></li>';
        }
        ul.innerHTML = html;
    }

    root.addEventListener('click', function (e) {
        var tile = e.target.closest('#svKpis [data-filter]');
        if (tile) {
            state.filter = tile.dataset.filter; state.page = 1;
            root.querySelectorAll('#svKpis [data-filter]').forEach(function (t) { var on = t === tile; t.classList.toggle('is-on', on); t.setAttribute('aria-pressed', String(on)); });
            render();
            return;
        }
        var pg = e.target.closest('#svPages a[data-pg]');
        if (pg) {
            e.preventDefault();
            if (pg.classList.contains('is-off')) return;
            state.page = +pg.dataset.pg; render();
            root.querySelector('.ltv-bar').scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
        if (e.target.closest('#svCsv')) {
            var out = [['Name', 'UID', 'Seat', 'Mobile', 'Plan', 'Shift', 'Start', 'End', 'Status', 'Payment', 'Activity']];
            matching().forEach(function (r) { out.push(JSON.parse(r.dataset.csv)); });
            var csv = '﻿' + out.map(function (r) { return r.map(function (v) { return '"' + String(v == null ? '' : v).replace(/"/g, '""') + '"'; }).join(','); }).join('\r\n');
            var a = document.createElement('a');
            a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
            a.download = 'seats-' + (new URL(location.href).searchParams.get('type') || 'list') + '.csv';
            document.body.appendChild(a); a.click(); a.remove();
        }
    });
    root.addEventListener('input', function (e) {
        if (e.target.id === 'svSearch') { state.q = e.target.value.trim().toLowerCase(); state.page = 1; render(); }
    });

    // ---------- other list / month: AJAX, no reload ----------
    function load(url, push) {
        if (busy) busy.abort();
        busy = new AbortController();
        root.classList.add('is-loading');
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: busy.signal, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                if (!doc.getElementById('svRoot')) { location.href = url; return; }
                ['#svTitle', '#svPeriod', '#svKpis', '#svList'].forEach(function (sel) {
                    var f = doc.querySelector(sel), c = root.querySelector(sel);
                    if (f && c) c.innerHTML = f.innerHTML;
                });
                root.querySelector('.ltv-month').hidden = doc.querySelector('.ltv-month').hidden;
                root.querySelector('#svSort').options[0].text = doc.querySelector('#svSort').options[0].text;
                document.title = doc.title || document.title;
                state.filter = 'all'; state.page = 1;
                root.querySelector('#svPerPage').value = String(state.per);
                render();
                if (push) history.pushState({ sv: 1 }, '', url);
            })
            .catch(function (e) { if (e.name !== 'AbortError') location.href = url; })
            .finally(function () { root.classList.remove('is-loading'); });
    }
    function urlWith(changes) {
        var p = new URL(location.href).searchParams;
        Object.keys(changes).forEach(function (k) { p.set(k, changes[k]); });
        return base + '?' + p.toString();
    }

    root.addEventListener('change', function (e) {
        var el = e.target;
        if (el.id === 'svSort') { state.sort = el.value; state.page = 1; render(); }
        else if (el.id === 'svPerPage') { state.per = +el.value; state.page = 1; render(); }
        else if (el.id === 'svType') { load(urlWith({ type: el.value }), true); }
        else if (el.id === 'svMonth' && el.value) {
            var ym = el.value.split('-');
            load(urlWith({ year: ym[0], month: String(+ym[1]) }), true);
        }
    });

    window.addEventListener('popstate', function () {
        var t = new URL(location.href).searchParams.get('type');
        if (t) root.querySelector('#svType').value = t;
        load(location.href, false);
    });
    try { history.replaceState({ sv: 1 }, '', location.href); } catch (e) {}
    render();
})();
</script>

@endsection
