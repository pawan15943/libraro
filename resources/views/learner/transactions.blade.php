@extends('layouts.learner')

@section('title', 'Payments')

@push('lr-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-learner-sub.css') }}?v={{ @filemtime(public_path('css/lb-learner-sub.css')) }}">
@endpush

@section('content')
@php
    $rows = collect($transaction ?? []);
    $paid = $rows->where('is_paid', 1);
    $totalPaid = $paid->sum(fn ($t) => (float) $t->paid_amount);
    $dues = $lr['dues'] ?? ['amount' => 0, 'overdue' => false, 'due_date' => null];
    $money = fn ($v) => '₹' . number_format((float) $v);
    $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('j M Y') : '—';
    $months = $rows->groupBy(fn ($t) => \Carbon\Carbon::parse($t->paid_date ?: $t->created_at)->format('Y-m'));
    $mode = fn ($m) => [1 => 'Online', 2 => 'Cash / offline', 3 => 'Pay later'][(int) $m] ?? 'Offline';
    $receipts = $rows->filter(fn ($t) => $t->receipt_url)->count();
    $lastPaid = $paid->first();
@endphp

<x-lr-hero icon="wallet" kicker="Payments" title="Your payments & receipts" sub="Every payment for your seat. Tap a receipt to view or download it."
           :chips="[['receipt-text', $receipts . ' ' . \Illuminate\Support\Str::plural('receipt', $receipts)], ['calendar-days', $months->count() . ' ' . \Illuminate\Support\Str::plural('month', $months->count())]]">
    <div class="lx-hero-amt"><small>Total paid</small><b>{{ $money($totalPaid) }}</b></div>
</x-lr-hero>

<div class="lx-stats">
    <div class="lx-stat" data-tone="green"><span><x-lb-icon name="badge-indian-rupee" /></span><b>{{ $money($totalPaid) }}</b><small>Total paid</small></div>
    <div class="lx-stat" data-tone="{{ $dues['amount'] > 0 ? ($dues['overdue'] ? 'rose' : 'orange') : 'teal' }}"><span><x-lb-icon name="indian-rupee" /></span><b>{{ $dues['amount'] > 0 ? $money($dues['amount']) : '₹0' }}</b><small>{{ $dues['overdue'] ? 'Overdue' : 'Due now' }}</small></div>
    <div class="lx-stat" data-tone="indigo"><span><x-lb-icon name="receipt-text" /></span><b>{{ $rows->count() }}</b><small>Payments</small></div>
    <div class="lx-stat" data-tone="violet"><span><x-lb-icon name="calendar-check" /></span><b>{{ $lastPaid ? \Carbon\Carbon::parse($lastPaid->paid_date)->format('j M') : '—' }}</b><small>Last payment</small></div>
</div>

@if ($dues['amount'] > 0)
    <div class="lr-alert {{ $dues['overdue'] ? 'bad' : 'warn' }} lx-alerts">
        <span class="lr-alert-ic"><x-lb-icon name="indian-rupee" /></span>
        <div>
            <b>{{ $money($dues['amount']) }} {{ $dues['overdue'] ? 'is overdue' : 'is due' }}</b>
            <p>@if ($dues['due_date']){{ $dues['overdue'] ? 'It was due on' : 'Please pay by' }} {{ $dues['due_date']->format('j M Y') }}. @endif Pay at the library desk.</p>
        </div>
    </div>
@endif

@if ($rows->isEmpty())
    <div class="lx-card lx-empty is-big"><x-lb-icon name="wallet" /><b>No payments yet</b><p>When you pay for a plan it will show here.</p></div>
@else
    <div class="lx-sec">History</div>
    @foreach ($months as $ym => $list)
        @php
            $monthDate = \Carbon\Carbon::createFromFormat('Y-m-d', $ym . '-01');
            $monthPaid = $list->where('is_paid', 1)->sum(fn ($t) => (float) $t->paid_amount);
        @endphp
        <details class="lx-card lrs-month lx-month" @if ($loop->first) open @endif>
            <summary>
                <span class="lx-month-date"><b>{{ $monthDate->format('M') }}</b><small>{{ $monthDate->format('Y') }}</small></span>
                <span class="lrs-month-t"><b>{{ $monthDate->format('F Y') }}</b><small>{{ $list->count() }} {{ $list->count() == 1 ? 'payment' : 'payments' }}</small></span>
                <span class="lrs-month-sum">{{ $money($monthPaid) }}</span>
                <x-lb-icon name="chevron-down" class="lrs-month-chev" />
            </summary>
            <div class="lrs-month-body">
                @foreach ($list as $t)
                    @php $ok = (int) $t->is_paid === 1; @endphp
                    <div class="lx-tx">
                        <span class="lx-row-ic" data-tone="{{ $ok ? 'green' : 'rose' }}"><x-lb-icon name="{{ $ok ? 'circle-check' : 'circle-x' }}" /></span>
                        <div class="lx-row-main">
                            <b>{{ $t->plan_name ?: 'Plan' }}@if($t->plan_type_name) · {{ $t->plan_type_name }}@endif</b>
                            <small>{{ $fmt($t->paid_date) }} · {{ $mode($t->payment_mode) }}@if ($t->plan_start_date) · For {{ $fmt($t->plan_start_date) }} – {{ $fmt($t->plan_end_date) }}@endif</small>
                            @if ((float) $t->pending_amount > 0)<small class="lx-tx-due">{{ $money($t->pending_amount) }} still to pay</small>@endif
                        </div>
                        <div class="lx-tx-end">
                            <b>{{ $money($t->paid_amount) }}</b>
                            @if ((float) $t->total_amount && (float) $t->total_amount != (float) $t->paid_amount)<small>of {{ $money($t->total_amount) }}</small>@endif
                            @unless ($ok)<span class="lx-pill is-bad">Failed</span>@endunless
                            @if ($t->receipt_url)
                                <button type="button" class="lx-rc" data-receipt="{{ $t->receipt_url }}" data-title="{{ $monthDate->format('M Y') }} · {{ $money($t->paid_amount) }}">
                                    <x-lb-icon name="receipt-text" /> Receipt
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </details>
    @endforeach
@endif

{{-- Receipt preview --}}
<div class="lrs-modal-bg" id="rcModal" role="dialog" aria-modal="true" aria-labelledby="rcTitle">
    <div class="lrs-modal">
        <div class="lrs-modal-head">
            <b id="rcTitle">Receipt</b>
            <a href="#" id="rcOpen" target="_blank" rel="noopener" class="lr-btn is-sm is-ghost"><x-lb-icon name="download" /> Open / download</a>
            <button type="button" class="lr-ibtn" id="rcClose" aria-label="Close"><x-lb-icon name="x" /></button>
        </div>
        <iframe id="rcFrame" title="Receipt" src="about:blank"></iframe>
        <div class="lrs-modal-foot">Preview not showing? Use "Open / download".</div>
    </div>
</div>
@endsection

@push('lr-scripts')
<script>
(function () {
    var bg = document.getElementById('rcModal'), fr = document.getElementById('rcFrame'), last = null;
    function close() { bg.classList.remove('is-open'); fr.src = 'about:blank'; document.body.style.overflow = ''; if (last) last.focus(); }
    document.querySelectorAll('[data-receipt]').forEach(function (b) {
        b.addEventListener('click', function () {
            last = b; fr.src = b.dataset.receipt;
            document.getElementById('rcOpen').href = b.dataset.receipt;
            document.getElementById('rcTitle').textContent = 'Receipt · ' + b.dataset.title;
            bg.classList.add('is-open'); document.body.style.overflow = 'hidden';
            document.getElementById('rcClose').focus();
        });
    });
    document.getElementById('rcClose').addEventListener('click', close);
    bg.addEventListener('click', function (e) { if (e.target === bg) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && bg.classList.contains('is-open')) close(); });
})();
</script>
@endpush
