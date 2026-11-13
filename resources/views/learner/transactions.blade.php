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
@endphp

<div class="lr-head">
    <div>
        <h1>Payments</h1>
        <p>Every payment for your seat, with receipts.</p>
    </div>
</div>

<div class="lr-grid-2" style="margin-bottom:12px">
    <div class="lr-stat"><small>Total paid</small><b class="ok">{{ $money($totalPaid) }}</b></div>
    <div class="lr-stat">
        <small>Due now</small>
        <b class="{{ $dues['amount'] > 0 ? ($dues['overdue'] ? 'bad' : 'warn') : '' }}">{{ $dues['amount'] > 0 ? $money($dues['amount']) : '₹0' }}</b>
    </div>
</div>

@if ($dues['amount'] > 0)
    <div class="lr-alert {{ $dues['overdue'] ? 'bad' : 'warn' }}" style="margin-bottom:12px">
        <span class="lr-alert-ic"><x-lb-icon name="indian-rupee" /></span>
        <div>
            <b>{{ $money($dues['amount']) }} {{ $dues['overdue'] ? 'is overdue' : 'is due' }}</b>
            <p>@if ($dues['due_date']){{ $dues['overdue'] ? 'It was due on' : 'Please pay by' }} {{ $dues['due_date']->format('j M Y') }}. @endif Pay at the library desk.</p>
        </div>
    </div>
@endif

@if ($rows->isEmpty())
    <div class="lr-card lr-empty">
        <div class="lr-empty-ic"><x-lb-icon name="wallet" /></div>
        <b>No payments yet</b>
        <p>When you pay for a plan it will show here.</p>
    </div>
@else
    @foreach ($months as $ym => $list)
        @php
            $monthDate = \Carbon\Carbon::createFromFormat('Y-m-d', $ym . '-01');
            $monthPaid = $list->where('is_paid', 1)->sum(fn ($t) => (float) $t->paid_amount);
        @endphp
        <details class="lr-card lrs-month" @if ($loop->first) open @endif>
            <summary>
                <span class="lrs-month-ic"><x-lb-icon name="calendar-days" /></span>
                <span class="lrs-month-t"><b>{{ $monthDate->format('F Y') }}</b><small>{{ $list->count() }} {{ $list->count() == 1 ? 'payment' : 'payments' }}</small></span>
                <span class="lrs-month-sum">{{ $money($monthPaid) }}</span>
                <x-lb-icon name="chevron-down" class="lrs-month-chev" />
            </summary>
            <div class="lrs-month-body lr-rows">
                @foreach ($list as $t)
                    @php $ok = (int) $t->is_paid === 1; @endphp
                    <div class="lr-row" style="align-items:flex-start">
                        <span class="lr-row-ic {{ $ok ? 'ok' : 'bad' }}"><x-lb-icon name="{{ $ok ? 'circle-check' : 'circle-x' }}" /></span>
                        <div class="lr-row-main">
                            <b>{{ $t->plan_name ?: 'Plan' }}@if($t->plan_type_name) · {{ $t->plan_type_name }}@endif</b>
                            <p>{{ $fmt($t->paid_date) }} · {{ $mode($t->payment_mode) }}</p>
                            @if ($t->plan_start_date)<p style="font-size:12.5px">For {{ $fmt($t->plan_start_date) }} – {{ $fmt($t->plan_end_date) }}</p>@endif
                            @if ((float) $t->pending_amount > 0)<p style="font-size:12.5px;color:var(--lp-warn-ink);font-weight:600">{{ $money($t->pending_amount) }} still to pay</p>@endif
                            @if ($t->receipt_url)
                                <button type="button" class="lr-btn is-ghost is-sm" style="margin-top:8px" data-receipt="{{ $t->receipt_url }}" data-title="{{ $monthDate->format('M Y') }} · {{ $money($t->paid_amount) }}">
                                    <x-lb-icon name="receipt-text" /> View receipt
                                </button>
                            @endif
                        </div>
                        <div class="lr-row-end">
                            <b>{{ $money($t->paid_amount) }}</b>
                            @if ((float) $t->total_amount && (float) $t->total_amount != (float) $t->paid_amount)<small>of {{ $money($t->total_amount) }}</small>@endif
                            @unless ($ok)<span class="lr-chip bad" style="margin-top:4px">Failed</span>@endunless
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
