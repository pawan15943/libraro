@extends('report.layout')

@section('title', 'Attendance')
@section('rpt-name', 'Attendance')
@section('rpt-desc', 'Who is actually coming — and who has stopped. Regular members renew; absent ones leave.')

@section('rpt-actions')
    <button type="button" class="lp-btn lp-btn--ghost" data-rpt-csv="attendance"><x-lb-icon name="download" />Export</button>
@endsection

@section('rpt-filters')
    <form class="rpt-bar" data-rpt action="{{ route('attendance.report') }}" id="attForm">
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">
        <label class="lpg-search">
            <x-lb-icon name="search" />
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name, mobile or seat" autocomplete="off">
        </label>
    </form>
@endsection

@section('rpt-body')
    @include('report._attendance')
@endsection

@push('rpt-scripts')
<script>
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-month]');
        if (!b) return;
        var f = document.getElementById('attForm'), ym = b.dataset.month.split('-');
        f.elements.year.value = ym[0];
        f.elements.month.value = ym[1];
        f.requestSubmit();
    });
</script>
@endpush
