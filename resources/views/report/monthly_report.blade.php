@extends('report.layout')

@section('title', 'Profit & loss')
@section('rpt-name', 'Profit & loss')
@section('rpt-desc', 'What came in, what went out and what you kept — month by month.')

@section('rpt-actions')
    <a href="{{ route('add.expense.list') }}" class="lp-btn lp-btn--ghost"><x-lb-icon name="plus" />Add expense</a>
    <button type="button" class="lp-btn lp-btn--ghost" data-rpt-csv="profit-and-loss"><x-lb-icon name="download" />Export</button>
@endsection

@section('rpt-filters')
    <form class="rpt-bar" data-rpt action="{{ route('report.monthly') }}">
        <input type="hidden" name="year" value="{{ $year }}">
        <div class="lpg-seg" role="group" aria-label="Period">
            <button type="button" class="lpg-seg-b" data-set="year=">Last 12 months</button>
            @foreach($years as $y)
                <button type="button" class="lpg-seg-b" data-set="year={{ $y }}">{{ $y }}</button>
            @endforeach
        </div>
    </form>
@endsection

@section('rpt-body')
    @include('report._pnl')
@endsection
