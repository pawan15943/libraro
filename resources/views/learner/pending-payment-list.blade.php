@extends('report.layout')

@php
    $activeFilter = request()->get('filter', 'all');
    $tabs = [
        'all' => ['All', 'list'],
        'pending' => ['Pending', 'hourglass'],
        'overdue' => ['Overdue', 'clock-alert'],
        'expired' => ['Expired members', 'user-x'],
        'adjusted' => ['Adjusted', 'scale'],
        'received' => ['Received', 'circle-check'],
    ];
    $sel = (array) request()->get('plan_type_id', []);
    $sortKey = request()->get('sort_by', 'seat_no') . ':' . request()->get('sort_order', 'asc');
    $s = $summary;
    $bd = $s['total_pending_breakdown'] ?? [];
@endphp

@section('title', 'Pending payments')
@section('rpt-crumb')<span>Pending payments</span>@endsection
@section('rpt-name', 'Pending payments')
@section('rpt-desc', 'Every member with money still to pay — call, remind on WhatsApp or collect in one tap.')

@section('rpt-actions')
    <a href="{{ route('partial.payment.collection.report') }}" class="lp-btn lp-btn--ghost"><x-lb-icon name="chart-column" />Dues report</a>
    <button type="button" class="lp-btn lp-btn--ghost" data-rpt-csv="pending-payments"><x-lb-icon name="download" />Export</button>
@endsection

@section('rpt-tabs')
    <div class="rpt-kpis pp-kpis">
        <div class="rpt-kpi is-bad">
            <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="hand-coins" /></span></div>
            <span>Final due</span>
            <b id="summaryFinalDue">{{ rptInr($s['final_due']) }}</b>
            <small>total left to collect</small>
        </div>
        <div class="rpt-kpi is-warn">
            <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="hourglass" /></span></div>
            <span>Active members owe</span>
            <b id="summaryTotalPending">{{ rptInr($s['total_pending_amount']) }}</b>
            <small>Overdue {{ rptInr($bd['non_expired_due'] ?? 0) }} · Pay later {{ rptInr($bd['pay_later'] ?? 0) }}</small>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="user-x" /></span></div>
            <span>Expired members owe</span>
            <b id="summaryExpiredDue">{{ rptInr($s['expired_learner_due_amount']) }}</b>
            <small>left without clearing dues</small>
        </div>
        <div class="rpt-kpi is-ok">
            <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="circle-check" /></span></div>
            <span>Received on dues</span>
            <b>{{ rptInr($s['received_pending_amount'] ?? 0) }}</b>
            <small id="summaryAdjusted">Adjusted {{ rptInr($s['adjusted_pending_amount']) }}</small>
        </div>
    </div>
@endsection

@section('rpt-filters')
    <form class="rpt-bar pp-bar" data-rpt action="{{ route('learner.pending.payment.list') }}" id="ppFilterForm">
        <input type="hidden" name="filter" value="{{ $activeFilter }}">
        <input type="hidden" name="page" value="{{ request()->get('page', 1) }}">
        <input type="hidden" name="sort_by" value="{{ request()->get('sort_by', 'seat_no') }}">
        <input type="hidden" name="sort_order" value="{{ request()->get('sort_order', 'asc') }}">

        <div class="lpg-seg" role="group" aria-label="Status">
            @foreach($tabs as $k => [$label, $icon])
                <button type="button" class="lpg-seg-b" data-set="filter={{ $k }}"><x-lb-icon name="{{ $icon }}" />{{ $label }}</button>
            @endforeach
        </div>

        <label class="lpg-search">
            <x-lb-icon name="search" />
            <input type="search" name="search" value="{{ request()->get('search') }}" placeholder="Search name, mobile or seat" autocomplete="off">
        </label>
        <label class="lpg-field is-select">
            <select name="plan_type_id[]" aria-label="Shift">
                <option value="">All shifts</option>
                @foreach($planTypes as $planType)
                    <option value="{{ $planType->id }}" @selected(in_array($planType->id, $sel))>{{ $planType->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="lpg-field is-select">
            <select id="ppSort" aria-label="Sort by">
                @foreach(['seat_no:asc' => 'Seat no. ↑', 'seat_no:desc' => 'Seat no. ↓', 'name:asc' => 'Name A–Z', 'name:desc' => 'Name Z–A', 'expire_date:asc' => 'Plan ends — soonest', 'expire_date:desc' => 'Plan ends — latest', 'gen:asc' => 'General seats first'] as $k => $label)
                    <option value="{{ $k }}" @selected($sortKey === $k)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="lpg-field is-date" title="Due from">
            <small>Due from</small>
            <input type="date" name="from_date" value="{{ request()->get('from_date') }}">
        </label>
    </form>
@endsection

@section('rpt-body')
    @include('learner.partials.pending-payment-table')
@endsection

@push('rpt-scripts')
<script>
    // one sort dropdown drives the two hidden sort fields
    document.getElementById('ppSort').addEventListener('change', function () {
        var f = this.form, kv = this.value.split(':');
        f.elements.sort_by.value = kv[0];
        f.elements.sort_order.value = kv[1];
    }, true);
</script>
@endpush
