@extends('report.layout')

@section('title', 'Expired members')
@section('rpt-name', 'Renewals')
@section('rpt-desc', 'Members who have left — the easiest new admissions are the people who already liked your library.')

@section('rpt-actions')
    <button type="button" class="lp-btn lp-btn--ghost" data-rpt-csv="expired-members"><x-lb-icon name="download" />Export</button>
@endsection

@section('rpt-tabs')
    @include('report._renew-tabs', ['on' => 'lost'])
@endsection

@section('rpt-filters')
    <form class="rpt-bar" data-rpt action="{{ route('expired.learner.report') }}">
        <input type="hidden" name="window" value="{{ $window }}">
        <div class="lpg-seg" role="group" aria-label="Left within">
            @foreach(['30' => 'Last 30 days', '90' => 'Last 90 days', '365' => 'Last year', 'all' => 'All time'] as $k => $label)
                <button type="button" class="lpg-seg-b" data-set="window={{ $k }}">{{ $label }}</button>
            @endforeach
        </div>
        <label class="lpg-search">
            <x-lb-icon name="search" />
            <input type="search" name="search" value="{{ $search }}" placeholder="Search name, mobile or seat" autocomplete="off">
        </label>
    </form>
@endsection

@section('rpt-body')
    @include('report._expired')
@endsection
