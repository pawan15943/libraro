@extends('report.layout')

@section('title', 'Renewals')
@section('rpt-name', 'Renewals')
@section('rpt-desc', 'Members whose plan ends soon — remind them before the seat goes empty.')

@section('rpt-actions')
    <button type="button" class="lp-btn lp-btn--ghost" data-rpt-csv="expiring-soon"><x-lb-icon name="download" />Export</button>
@endsection

@section('rpt-tabs')
    @include('report._renew-tabs', ['on' => 'soon'])
@endsection

@section('rpt-filters')
    <form class="rpt-bar" data-rpt action="{{ route('upcoming.payment.report') }}">
        <input type="hidden" name="days" value="{{ $filters['days'] }}">
        <div class="lpg-seg" role="group" aria-label="Ending within">
            @foreach([3 => '3 days', 7 => '7 days', 15 => '15 days', 30 => '30 days'] as $d => $label)
                <button type="button" class="lpg-seg-b" data-set="days={{ $d }}">{{ $label }}</button>
            @endforeach
        </div>
        <label class="lpg-field is-select">
            <select name="plan_id" aria-label="Plan">
                <option value="">All plans</option>
                @foreach($plans as $p)
                    <option value="{{ $p->id }}" @selected($filters['plan_id'] == $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="lpg-search">
            <x-lb-icon name="search" />
            <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search name, mobile or seat" autocomplete="off">
        </label>
    </form>
@endsection

@section('rpt-body')
    @include('report._expiring')
@endsection
