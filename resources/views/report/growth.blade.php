@extends('report.layout')

@section('title', 'Growth & retention')
@section('rpt-name', 'Growth & retention')
@section('rpt-desc', 'Are you growing? New members, renewals and leavers every month — and how many stay.')

@section('rpt-actions')
    <button type="button" class="lp-btn lp-btn--ghost" data-rpt-csv="growth"><x-lb-icon name="download" />Export</button>
@endsection

@section('rpt-filters')
    <form class="rpt-bar" data-rpt action="{{ route('reports.growth') }}">
        <input type="hidden" name="span" value="{{ $span }}">
        <div class="lpg-seg" role="group" aria-label="Period">
            <button type="button" class="lpg-seg-b" data-set="span=6">6 months</button>
            <button type="button" class="lpg-seg-b" data-set="span=12">12 months</button>
            <button type="button" class="lpg-seg-b" data-set="span=24">24 months</button>
        </div>
    </form>
@endsection

@section('rpt-body')
    @include('report._growth')
@endsection
