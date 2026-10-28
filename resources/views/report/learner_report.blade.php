@extends('report.layout')

@section('title', 'Members')
@section('rpt-name', 'Members')
@section('rpt-desc', 'Your booking register — who sits where, on which plan, and whether they have paid.')

@section('rpt-actions')
    <button type="button" class="lp-btn lp-btn--ghost" data-rpt-csv="members"><x-lb-icon name="download" />Export</button>
@endsection

@section('rpt-filters')
    <form class="rpt-bar" data-rpt action="{{ route('learner.report') }}">
        <input type="hidden" name="status" value="{{ $status }}">
        <div class="lpg-seg" role="group" aria-label="Status">
            <button type="button" class="lpg-seg-b" data-set="status=1">Active</button>
            <button type="button" class="lpg-seg-b" data-set="status=0">Expired</button>
            <button type="button" class="lpg-seg-b" data-set="status=all">All</button>
        </div>
        <label class="lpg-field is-select">
            <select name="ym" aria-label="Month">
                <option value="">Any month</option>
                @foreach($months as $k => $label)
                    <option value="{{ $k }}" @selected($ym === $k)>{{ $label }}</option>
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
        <label class="lpg-field is-select">
            <select name="is_paid" aria-label="Payment">
                <option value="">Paid &amp; unpaid</option>
                <option value="1" @selected(($filters['is_paid'] ?? '') === '1')>Paid</option>
                <option value="0" @selected(($filters['is_paid'] ?? '') === '0')>Not paid</option>
            </select>
        </label>
        <label class="lpg-search">
            <x-lb-icon name="search" />
            <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search name, mobile or seat" autocomplete="off">
        </label>
    </form>
@endsection

@section('rpt-body')
    @include('report._members')
@endsection
