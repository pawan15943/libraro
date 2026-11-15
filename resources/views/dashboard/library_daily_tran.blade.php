@extends('layouts.library')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
@endpush

@section('content')

@php
    $type = request('type', $type ?? 'today_collection');
    $isDaily = str_starts_with($type, 'today_');
    $isBalance = $type === 'monthly_balance';
    $year = (int) request('year', $year ?? date('Y'));
    $month = (int) request('month', $month ?? date('m'));
    $periodLabel = $isDaily ? 'Today · ' . now()->format('d M Y') : date('F Y', mktime(0, 0, 0, $month, 1, $year));
    $keep = ['year' => $year, 'month' => $month, 'per_page' => $perPage ?? 25];
    $txUrl = fn (array $p = []) => route('library.transaction.view', array_merge($keep, $p));
    $inr = fn ($v) => '₹' . number_format((float) $v, 0, '.', ',');
    $hasFilter = request()->filled('payment_type') || request()->filled('expense') || request()->filled('from') || request()->filled('to') || request()->filled('q');

    // [type, label, letter, value, icon, tone]
    $tiles = $isDaily ? [
        ['today_collection', 'Booking income', 'A', $today_booking_amt, 'trending-up', 'ok'],
        ['today_other_collection', 'Other income', 'B', $today_other_amt, 'hand-coins', 'violet'],
        ['today_expense', 'Expenses', 'C', $today_expense, 'trending-down', 'bad'],
        ['today_refund', 'Refunds', 'D', $today_refund, 'rotate-ccw', 'warn'],
        ['today_pending', 'Due', 'E', $today_pending, 'clock', 'blue'],
        ['today_advance', 'Advance received', 'part of A', $today_advance ?? 0, 'wallet', 'teal'],
        ['today_balance', 'Net revenue', 'A+B+E−C−D', $total_revenue, 'wallet', 'acc'],
    ] : [
        ['monthly_collection', 'Booking income', 'A', $monthly_income, 'trending-up', 'ok'],
        ['monthly_other_collection', 'Other income', 'B', $other_total_income, 'hand-coins', 'violet'],
        ['monthly_expense', 'Expenses', 'C', $monthly_expense, 'trending-down', 'bad'],
        ['monthly_refund', 'Refunds', 'D', $monthly_refund, 'rotate-ccw', 'warn'],
        ['monthly_pending', 'Due', 'E', $monthly_pending, 'clock', 'blue'],
        ['monthly_advance', 'Advance received', 'part of A', $monthly_advance ?? 0, 'wallet', 'teal'],
        ['monthly_balance', 'Net balance', 'day by day', $monthlyBalance, 'wallet', 'acc'],
    ];

    $typeOptions = match (true) {
        in_array($type, ['today_other_collection', 'monthly_other_collection']) => ['TOKEN MONEY', 'MISCELLANEOUS'],
        in_array($type, ['today_pending', 'monthly_pending']) => ['PENDING'],
        // same groups as the dashboard numbers (App\Support\PaymentTypes)
        in_array($type, ['today_refund', 'monthly_refund']) => \App\Support\PaymentTypes::refundTypeNames(),
        in_array($type, ['today_collection', 'monthly_collection']) => \App\Support\PaymentTypes::collectionTypeNames(),
        default => array_values(array_unique(array_merge(\App\Support\PaymentTypes::collectionTypeNames(), \App\Support\PaymentTypes::OTHER, ['EXPENSE', 'REFUND', 'PENDING']))),
    };
    $isExpenseType = in_array($type, ['today_expense', 'monthly_expense']);
    $typeTone = fn ($t) => match (strtoupper((string) $t)) {
        'SEAT ASSIGNMENT', 'RENEW', 'REACTIVE', 'UPGRADE', 'CHANGE PLAN', 'NON-EXPIRED', 'VIP', 'RESERVED', 'EDIT', 'RESTORE' => 'is-acc',
        'TOKEN MONEY', 'MISCELLANEOUS' => 'is-violet',
        'EXPENSE' => 'is-bad',
        'REFUND' => 'is-warn',
        'PENDING' => 'is-warn',
        default => '',
    };
    $niceType = fn ($t) => strtoupper((string) $t) === 'MISCELLANEOUS' ? 'Other fee' : ucwords(strtolower((string) $t));
@endphp

<div class="lpg ltv" id="txRoot" data-base="{{ route('library.transaction.view') }}">

    @php $vtActive = collect(request()->except(['page', 'search', 'view']))->filter(fn ($v) => $v !== null && $v !== '')->count(); @endphp
<header class="lpg-head ltv-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><span>Transactions</span></div>
            <h1>Transactions</h1>
            <p id="txPeriod">{{ $periodLabel }}</p>
        </div>
        <div class="lpg-head-r ltv-period">
                @include('partials.view-toggles', ['vt' => ['counts', 'filters'], 'vtActive' => $vtActive])
            <div class="lpg-seg" role="group" aria-label="Period">
                <a href="{{ $txUrl(['type' => 'today_collection']) }}" class="lpg-seg-b {{ $isDaily ? 'is-on' : '' }}" data-tx data-period="today"><x-lb-icon name="calendar-check" />Today</a>
                <a href="{{ $txUrl(['type' => 'monthly_collection']) }}" class="lpg-seg-b {{ $isDaily ? '' : 'is-on' }}" data-tx data-period="month"><x-lb-icon name="calendar-range" />Month</a>
            </div>
            <label class="lpg-field ltv-month" title="Choose month" @if($isDaily) hidden @endif>
                <x-lb-icon name="calendar-days" />
                <input type="month" id="txMonth" value="{{ sprintf('%04d-%02d', $year, $month) }}" max="{{ now()->format('Y-m') }}" aria-label="Month">
            </label>
        </div>
    </header>

    {{-- KPI tiles: each one switches the list below --}}
    <nav class="ltv-kpis" data-vt="counts" id="txKpis" aria-label="Transaction types">
        @foreach($tiles as [$tType, $tLabel, $tLetter, $tValue, $tIcon, $tTone])
            <a href="{{ $txUrl(['type' => $tType]) }}" class="ltv-kpi is-{{ $tTone }} {{ $type === $tType ? 'is-on' : '' }}" data-tx @if($type === $tType) aria-current="true" @endif>
                <span class="ltv-kpi-top"><x-lb-icon :name="$tIcon" /><em>{{ $tLetter }}</em></span>
                <b>{{ $inr($tValue) }}</b>
                <small>{{ $tLabel }}</small>
            </a>
        @endforeach
    </nav>

    @unless($isBalance)
        <div class="lpg-tools ltv-tools">
            <label class="lpg-search">
                <x-lb-icon name="search" />
                <input type="search" id="txSearch" placeholder="Search name, seat, Txn ID…" value="{{ request('q') }}" autocomplete="off" enterkeyhint="search">
                <span class="ltv-spin" aria-hidden="true"></span>
            </label>
            <div class="ltv-filters {{ $vtActive ? 'vt-on' : '' }}" data-vt="filters" id="txFilters">
                @if($isExpenseType)
                    <label class="lpg-field is-select" title="Category">
                        <x-lb-icon name="tag" />
                        <select name="expense" aria-label="Expense category">
                            <option value="">All categories</option>
                            @foreach($expensedata as $expType)
                                <option value="{{ $expType->name }}" @selected(request('expense') == $expType->name)>{{ $expType->name }}</option>
                            @endforeach
                        </select>
                    </label>
                @elseif(count($typeOptions) > 1)
                    <label class="lpg-field is-select" title="Type">
                        <x-lb-icon name="funnel" />
                        <select name="payment_type" aria-label="Payment type">
                            <option value="">All types</option>
                            @foreach($typeOptions as $opt)
                                <option value="{{ $opt }}" @selected(request('payment_type') == $opt)>{{ $niceType($opt) }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                @unless($isDaily)
                    <label class="lpg-field ltv-date" title="From"><span>From</span><input type="date" name="from" value="{{ request('from') }}" aria-label="From date"></label>
                    <label class="lpg-field ltv-date" title="To"><span>To</span><input type="date" name="to" value="{{ request('to') }}" aria-label="To date"></label>
                @endunless
                @if($hasFilter)
                    <a href="{{ $txUrl(['type' => $type]) }}" class="lpg-ibtn" data-tx data-clear title="Clear filters" aria-label="Clear filters"><x-lb-icon name="x" /></a>
                @endif
            </div>
        </div>
    @endunless

    <section id="txList" aria-live="polite">
    @if(!$isBalance)
        <div class="ltv-bar">
            <p>
                <b>{{ number_format($collection->total()) }}</b> {{ \Illuminate\Support\Str::plural('entry', $collection->total()) }} · {{ $label ?? 'Transactions' }}
                @if($filteredCr > 0)<span class="ltv-sum is-ok">+{{ $inr($filteredCr) }}</span>@endif
                @if($filteredDr > 0)<span class="ltv-sum is-bad">−{{ $inr($filteredDr) }}</span>@endif
            </p>
            <div class="ltv-bar-r">
                <label class="ltv-pp">Show
                    <select id="txPerPage" aria-label="Rows per page">
                        @foreach([25, 50, 100] as $pp)<option value="{{ $pp }}" @selected($perPage == $pp)>{{ $pp }}</option>@endforeach
                    </select>
                </label>
                @if($collection->count())
                    <button type="button" class="lpg-ibtn ltv-csv" id="txCsv" title="Download this page as CSV" aria-label="Download CSV"><x-lb-icon name="download" /></button>
                @endif
            </div>
        </div>

        @if($collection->count())
            <div class="ltv-table" role="table" aria-label="{{ $label ?? 'Transactions' }}">
                <div class="ltv-tr ltv-th" role="row">
                    <span role="columnheader">Date</span>
                    <span role="columnheader">Member / item</span>
                    <span role="columnheader">Type</span>
                    <span role="columnheader">Txn ID</span>
                    <span role="columnheader">Note</span>
                    <span role="columnheader" class="ltv-r">Amount</span>
                </div>
                @foreach($collection as $row)
                    @php
                        $isCr = $row->dr_cr === 'Cr';
                        $isExp = $row->payment_type === 'EXPENSE';
                        $lr = $row->learner;
                        $seat = $lr && $lr->seat_no ? getSeatDisplayShortFloorName($lr->seat_no) : null;
                        $d = !empty($row->date) ? \Carbon\Carbon::parse($row->date) : null;
                    @endphp
                    <div class="ltv-tr" role="row"
                         data-csv="{{ json_encode([$d ? $d->format('d-m-Y') : '', $isExp ? 'Expense' : ($lr->name ?? ''), $seat ?? '', $row->payment_type, $row->transaction_id, $row->particular, ($isCr ? '' : '-') . $row->amount]) }}">
                        <span class="ltv-date-c" role="cell">
                            <i class="ltv-dir {{ $isCr ? 'is-cr' : 'is-dr' }}" title="{{ $isCr ? 'Money in' : 'Money out' }}"><x-lb-icon name="arrow-up-right" /></i>
                            <span><b>{{ $d ? $d->format('d M') : '—' }}</b><small>{{ $d ? $d->format('D') : '' }}</small></span>
                        </span>
                        <span class="ltv-who" role="cell">
                            @if($isExp)
                                <b>Library expense</b>
                            @elseif($lr)
                                @can('has-permission', 'Learner Transactions')<a href="{{ route('learners.transactions', $lr->id) }}" title="Open {{ $lr->name }}’s transactions"><b>{{ $lr->name }}</b></a>@else<b>{{ $lr->name }}</b>@endcan
                                @if($seat)<small>Seat {{ $seat }}</small>@endif
                            @else
                                <b>—</b>
                            @endif
                        </span>
                        <span role="cell"><span class="lpg-tag {{ $typeTone($row->payment_type) }}">{{ $niceType($row->payment_type) }}</span></span>
                        <span class="ltv-id" role="cell">{{ $row->transaction_id ?: '—' }}</span>
                        @php
                            // advance list: how much of this payment was over the bill; other fees: what they were for
                            $ltvAdv = isset($advanceByTx) ? ($advanceByTx[$row->learner_transaction_id] ?? null) : null;
                            $ltvNote = $ltvAdv ? '₹' . number_format((float) $ltvAdv, 0, '.', ',') . ' advance · next renewal'
                                : (strtoupper((string) $row->payment_type) === 'MISCELLANEOUS' ? otherPaymentTitle($row->payment_type, $row->particular) : $row->particular);
                        @endphp
                        <span class="ltv-note{{ $ltvAdv ? ' is-adv' : '' }}" role="cell" title="{{ $ltvNote }}">{{ $ltvNote ?: '—' }}</span>
                        <span class="ltv-amt {{ $isCr ? 'is-cr' : 'is-dr' }}" role="cell">{{ $isCr ? '+' : '−' }}₹{{ number_format($row->amount, 0, '.', ',') }}</span>
                    </div>
                @endforeach
                <div class="ltv-tr ltv-tf" role="row">
                    <span role="cell">This page</span><span></span><span></span><span></span><span></span>
                    <span class="ltv-r" role="cell">{{ $inr($collection->getCollection()->sum(fn ($r) => $r->dr_cr === 'Cr' ? $r->amount : -$r->amount)) }}</span>
                </div>
            </div>

            @if($collection->lastPage() > 1)
                <ul class="lpg-pages">
                    <li><a href="{{ $collection->previousPageUrl() ?? '#' }}" class="{{ $collection->onFirstPage() ? 'is-off' : '' }}" data-tx aria-label="Previous page"><x-lb-icon name="chevron-left" /></a></li>
                    @for($i = max(1, $collection->currentPage() - 2); $i <= min($collection->lastPage(), $collection->currentPage() + 2); $i++)
                        <li><a href="{{ $collection->url($i) }}" class="{{ $collection->currentPage() == $i ? 'is-on' : '' }}" data-tx @if($collection->currentPage() == $i) aria-current="page" @endif>{{ $i }}</a></li>
                    @endfor
                    <li><a href="{{ $collection->nextPageUrl() ?? '#' }}" class="{{ $collection->hasMorePages() ? '' : 'is-off' }}" data-tx aria-label="Next page"><x-lb-icon name="chevron-right" /></a></li>
                </ul>
            @endif
        @else
            <div class="lpg-empty">
                <span><x-lb-icon name="inbox" /></span>
                <b>No entries {{ $hasFilter ? 'match these filters' : 'for ' . ($isDaily ? 'today' : $periodLabel) }}</b>
                <p>{{ $hasFilter ? 'Try a different search, type or date range.' : 'Payments, expenses and refunds will show up here as they happen.' }}</p>
                @if($hasFilter)<a href="{{ $txUrl(['type' => $type]) }}" class="lp-btn lp-btn--ghost" data-tx><x-lb-icon name="x" />Clear filters</a>@endif
            </div>
        @endif
    @else
        @php
            $mb = collect($monthly_balance);
            $mbSum = fn ($k) => $mb->sum($k);
        @endphp
        <div class="ltv-bar"><p><b>{{ $mb->count() }}</b> {{ \Illuminate\Support\Str::plural('day', $mb->count()) }} with entries · {{ $periodLabel }}</p></div>
        @if($mb->count())
            <div class="ltv-scroll">
                <table class="ltv-mb">
                    <thead>
                        <tr><th>Date</th><th class="is-ok">Booking +</th><th class="is-violet">Other +</th><th class="is-bad">Expense −</th><th class="is-warn">Refund −</th><th class="is-blue">Due</th><th>Day net</th><th>Running</th></tr>
                    </thead>
                    <tbody>
                        @foreach($mb as $r)
                            <tr>
                                <td><b>{{ \Carbon\Carbon::parse($r['date'])->format('d M') }}</b> <small>{{ \Carbon\Carbon::parse($r['date'])->format('D') }}</small></td>
                                <td>{{ $r['collection'] ? $inr($r['collection']) : '—' }}</td>
                                <td>{{ $r['other_collection'] ? $inr($r['other_collection']) : '—' }}</td>
                                <td>{{ $r['expense'] ? $inr($r['expense']) : '—' }}</td>
                                <td>{{ $r['refund'] ? $inr($r['refund']) : '—' }}</td>
                                <td>{{ $r['pending'] ? $inr($r['pending']) : '—' }}</td>
                                <td class="{{ $r['net'] >= 0 ? 'is-cr' : 'is-dr' }}">{{ $r['net'] < 0 ? '−' : '' }}{{ $inr(abs($r['net'])) }}</td>
                                <td class="{{ $r['final_balance'] < 0 ? 'is-dr' : '' }}"><b>{{ $r['final_balance'] < 0 ? '−' : '' }}{{ $inr(abs($r['final_balance'])) }}</b></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Total</td>
                            <td>{{ $inr($mbSum('collection')) }}</td><td>{{ $inr($mbSum('other_collection')) }}</td><td>{{ $inr($mbSum('expense')) }}</td>
                            <td>{{ $inr($mbSum('refund')) }}</td><td>{{ $inr($mbSum('pending')) }}</td>
                            <td class="{{ $mbSum('net') >= 0 ? 'is-cr' : 'is-dr' }}">{{ $mbSum('net') < 0 ? '−' : '' }}{{ $inr(abs($mbSum('net'))) }}</td>
                            @php $mbEnd = $mb->last()['final_balance'] ?? 0; @endphp
                            <td class="{{ $mbEnd < 0 ? 'is-dr' : '' }}"><b>{{ $mbEnd < 0 ? '−' : '' }}{{ $inr(abs($mbEnd)) }}</b></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="lpg-empty">
                <span><x-lb-icon name="calendar-x" /></span>
                <b>No entries in {{ $periodLabel }}</b>
                <p>Pick another month above.</p>
            </div>
        @endif
    @endif
    </section>
</div>

<script>
(function () {
    var root = document.getElementById('txRoot');
    if (!root) return;
    var base = root.getAttribute('data-base');
    var busy = null, timer = null;

    function params() { return new URL(location.href).searchParams; }
    function urlWith(changes, resetPage) {
        var p = params();
        Object.keys(changes).forEach(function (k) {
            var v = changes[k];
            if (v === '' || v == null) p.delete(k); else p.set(k, v);
        });
        if (resetPage) p.delete('page');
        var qs = p.toString();
        return base + (qs ? '?' + qs : '');
    }

    // swap only the parts that change, so the search box keeps focus while typing
    function swap(doc) {
        ['#txKpis', '#txFilters', '#txList', '#txPeriod'].forEach(function (sel) {
            var fresh = doc.querySelector(sel), cur = root.querySelector(sel);
            if (cur && fresh) cur.innerHTML = fresh.innerHTML;
        });
        // the toolbar exists for lists only (not for the day-by-day balance)
        var freshTools = doc.querySelector('.ltv-tools'), curTools = root.querySelector('.ltv-tools');
        if (!!freshTools !== !!curTools) {
            if (curTools) curTools.remove();
            else root.querySelector('#txKpis').insertAdjacentElement('afterend', freshTools);
        }
        var fp = doc.querySelectorAll('.ltv-period [data-period]');
        root.querySelectorAll('.ltv-period [data-period]').forEach(function (a, i) { a.className = fp[i].className; a.href = fp[i].href; });
        root.querySelector('.ltv-month').hidden = doc.querySelector('.ltv-month').hidden;
        document.title = doc.title || document.title;
    }

    function load(url, push) {
        if (busy) busy.abort();
        busy = new AbortController();
        root.classList.add('is-loading');
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Cache-Control': 'no-cache' }, signal: busy.signal, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                if (!doc.getElementById('txRoot')) { location.href = url; return; }
                swap(doc);
                if (push) history.pushState({ tx: 1 }, '', url);
            })
            .catch(function (e) { if (e.name !== 'AbortError') location.href = url; })
            .finally(function () { root.classList.remove('is-loading'); });
    }

    // links: tiles, Today/Month, pagination, clear
    root.addEventListener('click', function (e) {
        var a = e.target.closest('a[data-tx]');
        if (!a) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;
        e.preventDefault();
        if (a.classList.contains('is-off') || a.getAttribute('href') === '#') return;
        if (a.hasAttribute('data-clear') || a.closest('#txKpis') || a.hasAttribute('data-period')) {
            var s = document.getElementById('txSearch'); if (s) s.value = '';
        }
        load(a.href, true);
        if (a.closest('.lpg-pages')) root.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    // filters, month, rows per page
    root.addEventListener('change', function (e) {
        var el = e.target;
        if (el.id === 'txMonth') {
            if (!el.value) return;
            var ym = el.value.split('-');
            load(urlWith({ year: ym[0], month: String(+ym[1]) }, true), true);
        } else if (el.id === 'txPerPage') {
            load(urlWith({ per_page: el.value }, true), true);
        } else if (el.closest('#txFilters') && el.name) {
            var c = {}; c[el.name] = el.value;
            load(urlWith(c, true), true);
        }
    });

    // search across all pages (debounced)
    root.addEventListener('input', function (e) {
        if (e.target.id !== 'txSearch') return;
        clearTimeout(timer);
        var v = e.target.value.trim();
        timer = setTimeout(function () { load(urlWith({ q: v }, true), true); }, 350);
    });
    root.addEventListener('keydown', function (e) {
        if (e.target.id === 'txSearch' && e.key === 'Enter') { e.preventDefault(); clearTimeout(timer); load(urlWith({ q: e.target.value.trim() }, true), true); }
    });

    // CSV of the rows on screen
    root.addEventListener('click', function (e) {
        if (!e.target.closest('#txCsv')) return;
        var rows = [['Date', 'Member / item', 'Seat', 'Type', 'Txn ID', 'Note', 'Amount']];
        root.querySelectorAll('.ltv-tr[data-csv]').forEach(function (r) { rows.push(JSON.parse(r.getAttribute('data-csv'))); });
        var csv = '﻿' + rows.map(function (r) { return r.map(function (v) { return '"' + String(v == null ? '' : v).replace(/"/g, '""') + '"'; }).join(','); }).join('\r\n');
        var a = document.createElement('a');
        a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
        a.download = 'transactions-' + (params().get('type') || 'list') + '.csv';
        document.body.appendChild(a); a.click(); a.remove();
    });

    window.addEventListener('popstate', function () { load(location.href, false); });
    try { history.replaceState({ tx: 1 }, '', location.href); } catch (e) {}
})();
</script>

@endsection
