@extends('layouts.library')

@section('title', 'Billing & invoices')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
@endpush

@section('content')

@php
    use Carbon\Carbon;

    $latest = $transaction->first();
    $planLabel = $plan ? $plan->name : ($latest?->plan ?? 'Active plan');
    $cycleOf = fn ($m) => !$m ? null : ($m == 12 ? 'Yearly' : ($m == 1 ? 'Monthly' : $m . ' months'));
    $lastPaid = $latest && $latest->transaction_date ? Carbon::parse($latest->transaction_date) : null;
@endphp

<div class="lpg lbl">
    @php $vtActive = 0; @endphp
<header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><span>Billing</span></div>
            <h1>Billing &amp; invoices</h1>
            <p>Every payment you’ve made for Libraro, with receipts ready to download.</p>
        </div>
        @if(Route::has('library.myplan'))
            <div class="lpg-head-r">
                @include('partials.view-toggles', ['vt' => ['counts'], 'vtActive' => $vtActive])
                <a href="{{ route('library.myplan') }}" class="lp-btn lp-btn--ghost"><x-lb-icon name="crown" />My plan</a>
            </div>
        @endif
    </header>

    <div class="lpg-stats" data-vt="counts">
        <div class="lpg-stat is-violet">
            <span class="lpg-stat-ic"><x-lb-icon name="crown" /></span>
            <span>Current plan</span>
            <b title="{{ $planLabel }}">{{ $planLabel }}</b>
        </div>
        <div class="lpg-stat is-ok">
            <span class="lpg-stat-ic"><x-lb-icon name="wallet" /></span>
            <span>Total paid</span>
            <b>₹{{ number_format($transaction->sum('paid_amount')) }}</b>
        </div>
        <div class="lpg-stat">
            <span class="lpg-stat-ic"><x-lb-icon name="receipt-text" /></span>
            <span>Invoices</span>
            <b>{{ $transaction->count() }}</b>
        </div>
        <div class="lpg-stat is-warn">
            <span class="lpg-stat-ic"><x-lb-icon name="calendar-check" /></span>
            <span>Last payment</span>
            <b>{{ $lastPaid ? $lastPaid->format('j M Y') : '—' }}</b>
        </div>
    </div>

    @if($transaction->isNotEmpty())
        <div class="lpg-sec">Payment history <span class="lpg-n">{{ $transaction->count() }}</span></div>

        <div class="lpg-list lbl-list">
            @foreach($transaction as $value)
                @php
                    $name = $plan ? $plan->name : ($value->plan ?? 'Standard Plan');
                    $slug = strtolower(str_replace(' ', '', $name));
                    $tier = str_contains($slug, 'premium') ? 'premium' : (str_contains($slug, 'basic') ? 'basic' : 'standard');
                    $date = $value->transaction_date ? Carbon::parse($value->transaction_date) : null;
                    $cycle = $cycleOf($value->month);
                    $gst = (float) $value->paid_amount - (float) $value->amount;
                @endphp
                <article class="lpg-row lbl-row">
                    <span class="lbl-ic t-{{ $tier }}"><x-lb-icon name="{{ ['premium' => 'crown', 'standard' => 'star', 'basic' => 'zap'][$tier] }}" /></span>

                    <div class="lpg-row-t">
                        <div class="lbl-top">
                            <b>{{ $name }}</b>
                            @if($cycle)<span class="lpg-tag">{{ $cycle }}</span>@endif
                            @if($value->is_paid == 1)
                                <span class="lpg-tag is-ok"><x-lb-icon name="check" />Paid</span>
                            @else
                                <span class="lpg-tag is-bad">Failed</span>
                            @endif
                        </div>
                        <small>
                            <span>{{ $date ? $date->format('j M Y') : '—' }}</span>
                            <span>{{ $value->payment_mode == 1 ? 'Online' : ($value->payment_mode == 2 ? 'Offline' : 'Not paid') }}</span>
                        </small>
                        @if($value->transaction_id)
                            <button type="button" class="lbl-txn" data-copy="{{ $value->transaction_id }}" title="Copy transaction ID">
                                <code>{{ $value->transaction_id }}</code><x-lb-icon name="copy" />
                            </button>
                        @endif
                    </div>

                    <div class="lpg-row-r lbl-amt">
                        <b>₹{{ number_format($value->paid_amount, 2) }}</b>
                        <small>Plan ₹{{ number_format($value->amount) }}@if($gst > 0.009) + GST ₹{{ number_format($gst, 2) }}@endif</small>
                    </div>

                    @can('has-permission', 'Download Payment Receipt')
                        <form action="{{ route('fee.generateReceipt') }}" method="POST" class="lbl-rc">
                            @csrf
                            <input type="hidden" name="id" value="{{ $value->id }}">
                            <input type="hidden" name="type" value="library">
                            <button type="submit" class="lpg-round noLoader" title="Download receipt" aria-label="Download receipt"><x-lb-icon name="download" /></button>
                        </form>
                    @endcan
                </article>
            @endforeach
        </div>
    @else
        <div class="lpg-empty lbl-gap">
            <span><x-lb-icon name="receipt" /></span>
            <b>No payments yet</b>
            <p>When you pay for a Libraro plan, the invoice shows up here.</p>
            @if(Route::has('library.myplan'))
                <a href="{{ route('library.myplan') }}" class="lp-btn"><x-lb-icon name="crown" />See my plan</a>
            @endif
        </div>
    @endif
</div>

<script>
    document.addEventListener('click', function (e) {
        var b = e.target.closest('.lbl-txn');
        if (!b) return;
        var t = b.dataset.copy, done = function () {
            b.classList.add('is-done');
            setTimeout(function () { b.classList.remove('is-done'); }, 1400);
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(t).then(done);
        } else {
            var a = document.createElement('textarea');
            a.value = t; a.style.cssText = 'position:fixed;opacity:0';
            document.body.appendChild(a); a.select();
            try { document.execCommand('copy'); done(); } catch (err) {}
            a.remove();
        }
    });
</script>

@endsection
