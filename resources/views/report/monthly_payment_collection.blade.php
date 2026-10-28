@extends('report.layout')

@section('title', 'Cash book')
@section('rpt-name', 'Cash book')
@section('rpt-desc', 'Day-by-day money in and money out — fees, expenses and refunds in one ledger.')

@section('rpt-actions')
    <button type="button" class="lp-btn lp-btn--ghost" data-rpt-csv="cash-book"><x-lb-icon name="download" />Export</button>
@endsection

@section('rpt-filters')
    <form class="rpt-bar" data-rpt action="{{ route('monthly.payment.collection.report') }}" id="cbForm">
        <input type="hidden" name="year" value="{{ request('start_date') ? '' : $fromDate->year }}">
        <input type="hidden" name="month" value="{{ request('start_date') ? '' : $fromDate->month }}">
        <input type="hidden" name="flow" value="{{ request('flow', 'all') }}">
        <input type="hidden" name="start_date" value="{{ request('start_date') }}">
        <input type="hidden" name="end_date" value="{{ request('end_date') }}">
        <div class="lpg-seg" role="group" aria-label="Show">
            <button type="button" class="lpg-seg-b" data-set="flow=all">Everything</button>
            <button type="button" class="lpg-seg-b" data-set="flow=credit">Money in</button>
            <button type="button" class="lpg-seg-b" data-set="flow=expense">Expenses</button>
            <button type="button" class="lpg-seg-b" data-set="flow=refund">Refunds</button>
        </div>
        <label class="lpg-field is-select">
            <select name="payment_mode" aria-label="Payment mode">
                <option value="">All modes</option>
                @foreach(['ONLINE' => 'Online', 'OFFLINE' => 'Offline / cash', 'PAYLATER' => 'Pay later'] as $k => $label)
                    <option value="{{ $k }}" @selected(request('payment_mode') === $k)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="lpg-search">
            <x-lb-icon name="search" />
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name, seat or note" autocomplete="off">
        </label>
    </form>
@endsection

@section('rpt-body')
    @include('report._cashbook')
@endsection

@push('rpt-scripts')
<script>
    // month stepper lives in the body (so its label refreshes); it drives the form
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-month]');
        if (!b) return;
        var f = document.getElementById('cbForm'), ym = b.dataset.month.split('-');
        f.elements.year.value = ym[0];
        f.elements.month.value = ym[1];
        f.elements.start_date.value = '';
        f.elements.end_date.value = '';
        f.requestSubmit();
    });
</script>
@endpush
