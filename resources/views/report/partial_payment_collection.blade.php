@extends('report.layout')

@section('title', 'Dues')
@section('rpt-name', 'Dues to collect')
@section('rpt-desc', 'Balances members still owe — oldest first, so you know whom to call today.')

@section('rpt-actions')
    <button type="button" class="lp-btn lp-btn--ghost" data-rpt-csv="dues"><x-lb-icon name="download" />Export</button>
@endsection

@section('rpt-filters')
    <form class="rpt-bar" data-rpt action="{{ route('partial.payment.collection.report') }}">
        <input type="hidden" name="due_status" value="{{ $due_status }}">
        <div class="lpg-seg" role="group" aria-label="Show">
            <button type="button" class="lpg-seg-b" data-set="due_status=pending">All dues<span class="lpg-n">{{ $metrics['counts']['pending'] }}</span></button>
            <button type="button" class="lpg-seg-b" data-set="due_status=overdue">Overdue<span class="lpg-n">{{ $metrics['counts']['overdue'] }}</span></button>
            <button type="button" class="lpg-seg-b" data-set="due_status=week">Due in 7 days<span class="lpg-n">{{ $metrics['counts']['week'] }}</span></button>
            <button type="button" class="lpg-seg-b" data-set="due_status=cleared">Cleared (30 days)</button>
        </div>
        <label class="lpg-search">
            <x-lb-icon name="search" />
            <input type="search" data-rpt-find placeholder="Find name, seat or mobile" autocomplete="off">
        </label>
    </form>
@endsection

@section('rpt-body')
    @include('report._dues')
@endsection
