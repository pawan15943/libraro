@extends('layouts.library')

@section('title', 'Website Enquiries')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
@endpush

@section('content')

@php
    use Carbon\Carbon;

    $total = $datas->count();
    $today = $datas->filter(fn ($e) => $e->created_at && Carbon::parse($e->created_at)->isToday())->count();
    $month = $datas->filter(fn ($e) => $e->created_at && Carbon::parse($e->created_at)->isCurrentMonth() && Carbon::parse($e->created_at)->isCurrentYear())->count();
    $topShift = $datas->pluck('planType.name')->filter()->countBy()->sortDesc()->keys()->first();
    $library = optional(getLibrary())->library_name ?: 'our library';
    $faces = ['c1', 'c2', 'c3', 'c4', 'c5'];
@endphp

<div class="lpg lenq">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><span>Enquiries</span></div>
            <h1>Website enquiries</h1>
            <p>People who asked about a seat from your public library page. Reply quickly — the first library to call usually wins.</p>
        </div>
    </header>

    <div class="lpg-stats">
        <div class="lpg-stat">
            <span class="lpg-stat-ic"><x-lb-icon name="inbox" /></span>
            <span>All enquiries</span>
            <b>{{ $total }}</b>
        </div>
        <div class="lpg-stat is-ok">
            <span class="lpg-stat-ic"><x-lb-icon name="bell-ring" /></span>
            <span>Today</span>
            <b>{{ $today }}</b>
        </div>
        <div class="lpg-stat is-violet">
            <span class="lpg-stat-ic"><x-lb-icon name="calendar-range" /></span>
            <span>This month</span>
            <b>{{ $month }}</b>
        </div>
        <div class="lpg-stat is-warn">
            <span class="lpg-stat-ic"><x-lb-icon name="clock" /></span>
            <span>Most asked shift</span>
            <b title="{{ $topShift }}">{{ $topShift ?: '—' }}</b>
        </div>
    </div>

    @if($total)
        <div class="lpg-tools lenq-tools">
            <div class="lpg-seg" role="group" aria-label="When">
                <button type="button" class="lpg-seg-b is-on" data-when="all">All<span class="lpg-n">{{ $total }}</span></button>
                <button type="button" class="lpg-seg-b" data-when="today">Today<span class="lpg-n">{{ $today }}</span></button>
                <button type="button" class="lpg-seg-b" data-when="month">This month<span class="lpg-n">{{ $month }}</span></button>
            </div>
            <label class="lpg-search">
                <x-lb-icon name="search" />
                <input type="search" id="enqSearch" placeholder="Search name, mobile or message" autocomplete="off">
            </label>
        </div>

        <div class="lpg-list" id="enqList">
            @foreach($datas as $i => $e)
                @php
                    $at = $e->created_at ? Carbon::parse($e->created_at) : null;
                    $phone = preg_replace('/[^0-9]/', '', (string) $e->mobile);
                    if (strlen($phone) > 10 && str_starts_with($phone, '91')) $phone = substr($phone, 2);
                    $shift = $e->planType;
                    $timing = '';
                    try { if ($shift && $shift->start_time && $shift->end_time) $timing = Carbon::parse($shift->start_time)->format('g:i A') . ' – ' . Carbon::parse($shift->end_time)->format('g:i A'); } catch (\Throwable $ex) {}
                    $initials = collect(explode(' ', trim((string) $e->name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: '?';
                    $wa = urlencode('Hello ' . ucwords(strtolower($e->name)) . ', thank you for your enquiry at ' . $library . ($shift ? ' for the ' . $shift->name . ' shift' : '') . '. When would you like to visit?');
                    $when = $at && $at->isToday() ? 'today month' : ($at && $at->isCurrentMonth() && $at->isCurrentYear() ? 'month' : '');
                @endphp
                <article class="lpg-row lenq-row" data-when="{{ $when }}" data-q="{{ strtolower($e->name . ' ' . $e->mobile . ' ' . $e->enquiry . ' ' . optional($shift)->name) }}">
                    <span class="lp-face {{ $faces[$i % 5] }}">{{ $initials }}</span>
                    <div class="lpg-row-t">
                        <div class="lenq-top">
                            <b>{{ ucwords(strtolower($e->name)) }}</b>
                            @if($at && $at->isToday())<span class="lpg-tag is-ok">New</span>@endif
                        </div>
                        <small>
                            @if($e->mobile)<span><a href="tel:+91{{ $phone }}" class="lenq-tel">+91 {{ $e->mobile }}</a></span>@endif
                            @if($shift)<span>{{ $shift->name }}@if($timing) · {{ $timing }}@endif</span>@endif
                            @if($at)<span title="{{ $at->format('d M Y, g:i A') }}">{{ $at->diffForHumans() }}</span>@endif
                        </small>
                        @if($e->enquiry)
                            <p class="lenq-msg">{{ $e->enquiry }}</p>
                        @endif
                    </div>
                    <div class="lenq-act">
                        @if($phone)
                            <a href="https://wa.me/91{{ $phone }}?text={{ $wa }}" target="_blank" rel="noopener" class="lpg-round lenq-wa noLoader" title="Reply on WhatsApp" aria-label="Reply to {{ $e->name }} on WhatsApp"><x-lb-icon name="message-circle" /></a>
                            <a href="tel:+91{{ $phone }}" class="lpg-round" title="Call" aria-label="Call {{ $e->name }}"><x-lb-icon name="phone" /></a>
                        @endif
                        <form action="{{ route('library.enquiry.destroy', $e->id) }}" method="POST" class="lenq-del" data-name="{{ ucwords(strtolower($e->name)) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="lpg-round is-bad" title="Delete" aria-label="Delete enquiry from {{ $e->name }}"><x-lb-icon name="trash" /></button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="lpg-empty" id="enqNone" hidden style="margin-top:12px">
            <span><x-lb-icon name="search" /></span>
            <b>No enquiries match</b>
            <p>Try another name or switch to “All”.</p>
        </div>
    @else
        <div class="lpg-empty" style="margin-top:14px">
            <span><x-lb-icon name="inbox" /></span>
            <b>No enquiries yet</b>
            <p>When someone asks about a seat from your public library page, it shows up here.</p>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var list = document.getElementById('enqList');
        if (!list) return;
        var rows = list.querySelectorAll('.lenq-row');
        var when = 'all', q = '';

        function apply() {
            var n = 0;
            rows.forEach(function (r) {
                var hit = (when === 'all' || (' ' + r.dataset.when + ' ').indexOf(' ' + when + ' ') > -1) && (!q || r.dataset.q.indexOf(q) > -1);
                r.hidden = !hit;
                if (hit) n++;
            });
            document.getElementById('enqNone').hidden = n > 0;
        }
        document.querySelectorAll('.lenq-tools [data-when]').forEach(function (b) {
            b.addEventListener('click', function () {
                when = b.dataset.when;
                document.querySelectorAll('.lenq-tools [data-when]').forEach(function (x) { x.classList.toggle('is-on', x === b); });
                apply();
            });
        });
        document.getElementById('enqSearch').addEventListener('input', function () { q = this.value.trim().toLowerCase(); apply(); });

        // confirm before deleting
        list.addEventListener('submit', function (e) {
            var f = e.target.closest('.lenq-del');
            if (!f || f.dataset.ok) return;
            e.preventDefault();
            var go = function () { f.dataset.ok = '1'; f.submit(); };
            if (window.Swal) {
                Swal.fire({ title: 'Delete this enquiry?', text: 'From ' + f.dataset.name + '. This can’t be undone.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#de3b3d' })
                    .then(function (r) { if (r.isConfirmed) go(); });
            } else if (confirm('Delete the enquiry from ' + f.dataset.name + '?')) {
                go();
            }
        });
    });
</script>

@endsection
