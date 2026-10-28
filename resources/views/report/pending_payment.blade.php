@extends('report.layout')

@section('title', 'Renewals · grace period')
@section('rpt-name', 'Renewals')
@section('rpt-desc', 'Plans that have ended but are still inside the grace days — renew them before the seat is released.')

@section('rpt-actions')
    <button type="button" class="lp-btn lp-btn--ghost" data-rpt-csv="grace-period"><x-lb-icon name="download" />Export</button>
@endsection

@section('rpt-tabs')
    @include('report._renew-tabs', ['on' => 'grace'])
@endsection

@section('rpt-filters')
    <form class="rpt-bar" data-rpt action="{{ route('pending.payment.report') }}">
        <label class="lpg-field is-select">
            <select name="plan_id" aria-label="Plan">
                <option value="">All plans</option>
                @foreach($plans as $p)
                    <option value="{{ $p->id }}" @selected(($filters['plan_id'] ?? '') == $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="lpg-field is-select">
            <select name="plan_type" aria-label="Shift">
                <option value="">All shifts</option>
                @foreach($planTypes as $t)
                    <option value="{{ $t->id }}" @selected(($filters['plan_type'] ?? '') == $t->id)>{{ $t->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="lpg-search">
            <x-lb-icon name="search" />
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name, mobile or seat" autocomplete="off">
        </label>
    </form>
@endsection

@section('rpt-body')
    @include('report._grace')
@endsection
