@extends('report.layout')

@section('title', 'Collections')
@section('rpt-name', 'Collections')
@section('rpt-desc', 'Every fee received — who paid, how much and how.')

@section('rpt-actions')
    <button type="button" class="lp-btn lp-btn--ghost" data-rpt-csv="collections"><x-lb-icon name="download" />Export</button>
@endsection

@section('rpt-filters')
    <form class="rpt-bar" data-rpt action="{{ route('payment.collection.report') }}">
        <input type="hidden" name="preset" value="{{ $preset }}">
        <div class="lpg-seg" role="group" aria-label="Period">
            @foreach(['today' => 'Today', 'this_week' => 'This week', 'this_month' => 'This month', 'last_month' => 'Last month', 'last_90' => '90 days', 'all' => 'All time', 'custom' => 'Custom'] as $k => $label)
                <button type="button" class="lpg-seg-b" data-set="preset={{ $k }}">{{ $label }}</button>
            @endforeach
        </div>
        <label class="lpg-field is-date" data-show-custom @if($preset !== 'custom') hidden @endif>
            <small>From</small><input type="date" name="start_date" value="{{ optional($start)->toDateString() }}">
        </label>
        <label class="lpg-field is-date" data-show-custom @if($preset !== 'custom') hidden @endif>
            <small>To</small><input type="date" name="end_date" value="{{ optional($end)->toDateString() }}">
        </label>
        <label class="lpg-field is-select">
            <select name="payment_mode" aria-label="Payment mode">
                <option value="">All modes</option>
                <option value="online" @selected($payment_mode === 'online')>Online</option>
                <option value="offline" @selected($payment_mode === 'offline')>Offline / cash</option>
                <option value="paylater" @selected($payment_mode === 'paylater')>Pay later</option>
            </select>
        </label>
        <label class="lpg-search">
            <x-lb-icon name="search" />
            <input type="search" data-rpt-find placeholder="Find name, seat or plan" autocomplete="off">
        </label>
    </form>
@endsection

@section('rpt-body')
    @include('report._collections')
@endsection

@push('rpt-scripts')
<script>
    // custom dates only make sense for the "Custom" preset
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-set^="preset="]');
        if (!b) return;
        var custom = b.dataset.set === 'preset=custom';
        document.querySelectorAll('[data-show-custom]').forEach(function (el) { el.hidden = !custom; });
    }, true);
</script>
@endpush
