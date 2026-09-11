@extends('layouts.library')

@section('content')

{{-- Dedicated Scoped Stylesheet for Library Transactions Module --}}
<link rel="stylesheet" href="{{ asset('public/css/library-transactions.css') }}?v={{ time() }}" />

<div class="library-transaction-module">

    {{-- Top KPI Summary Statistics Grid --}}
    <div class="kpi-stats-grid">
        {{-- KPI 1: Active Subscription --}}
        <div class="kpi-card">
            <div class="kpi-icon-badge badge-purple">
                <i class="fa-solid fa-crown"></i>
            </div>
            <div class="kpi-info">
                <p class="kpi-label">Active Subscription</p>
                <h3 class="kpi-value">{{ $plan ? $plan->name : ($transaction->first()?->plan ?? 'Active Plan') }}</h3>
                <p class="kpi-subtext">
                    @if($transaction->isNotEmpty() && $transaction->first()->month)
                        {{ $transaction->first()->month == 12 ? 'Yearly Billing Cycle' : ($transaction->first()->month . ' Month(s) Billing') }}
                    @else
                        Subscription Plan
                    @endif
                </p>
            </div>
        </div>

        {{-- KPI 2: Total Amount Paid --}}
        <div class="kpi-card">
            <div class="kpi-icon-badge badge-teal">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div class="kpi-info">
                <p class="kpi-label">Total Amount Paid</p>
                <h3 class="kpi-value">₹{{ number_format($transaction->sum('paid_amount'), 2) }}</h3>
                <p class="kpi-subtext">All-time billing payments</p>
            </div>
        </div>

        {{-- KPI 3: Total Invoices --}}
        <div class="kpi-card">
            <div class="kpi-icon-badge badge-blue">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
            <div class="kpi-info">
                <p class="kpi-label">Total Invoices</p>
                <h3 class="kpi-value">{{ $transaction->count() }}</h3>
                <p class="kpi-subtext">Successful transactions</p>
            </div>
        </div>

        {{-- KPI 4: Latest Payment Date --}}
        <div class="kpi-card">
            <div class="kpi-icon-badge badge-green">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div class="kpi-info">
                <p class="kpi-label">Last Payment Date</p>
                <h3 class="kpi-value">
                    @if($transaction->isNotEmpty() && $transaction->first()->transaction_date)
                        {{ \Carbon\Carbon::parse($transaction->first()->transaction_date)->format('d M, Y') }}
                    @else
                        N/A
                    @endif
                </h3>
                <p class="kpi-subtext">Most recent transaction</p>
            </div>
        </div>
    </div>

    {{-- Transaction Table Card --}}
    <div class="transaction-table-card">
        <div class="table-card-header">
            <div class="d-flex align-items-center gap-3">
                <div class="table-badge-icon">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <h5 class="table-header-title">Billing & Invoice History</h5>
                    <p class="table-header-sub">All subscription transactions and official payment receipts</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="count-badge">
                    <i class="fa-solid fa-circle text-success" style="font-size: 8px;"></i>
                    Total: {{ $transaction->count() }} {{ Str::plural('Record', $transaction->count()) }}
                </span>
                @if(Route::has('library.myplan'))
                <a href="{{ route('library.myplan') }}" class="btn-plan-details">
                    <i class="fa-solid fa-crown text-warning"></i> My Plan Details
                </a>
                @endif
            </div>
        </div>

        @if($transaction->isNotEmpty())
        <div class="table-responsive">
            <table class="library-trans-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">S.No</th>
                        <th style="width: 14%;">Plan Name</th>
                        <th style="width: 11%;">Plan Price</th>
                        <th style="width: 14%;">Paid Amt (After GST)</th>
                        <th style="width: 16%;">Trxn Id</th>
                        <th style="width: 12%;">Trxn Date</th>
                        <th style="width: 12%;">Payment Method</th>
                        <th style="width: 9%;">Trxn Status</th>
                        <th style="width: 7%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transaction as $key => $value)
                    @php
                        $planName = $plan ? $plan->name : ($value->plan ?? 'Standard Plan');
                        $planSlug = strtolower(str_replace(' ', '', $planName));
                    @endphp
                    <tr>
                        {{-- S.No --}}
                        <td>
                            <span class="sno-badge">{{ $key + 1 }}</span>
                        </td>

                        {{-- Plan Name --}}
                        <td>
                            @if(str_contains($planSlug, 'basic'))
                                <span class="plan-pill-badge plan-badge-basic">
                                    <i class="fa-solid fa-bolt"></i> {{ $planName }}
                                </span>
                            @elseif(str_contains($planSlug, 'standard'))
                                <span class="plan-pill-badge plan-badge-standard">
                                    <i class="fa-solid fa-star"></i> {{ $planName }}
                                </span>
                            @elseif(str_contains($planSlug, 'premium'))
                                <span class="plan-pill-badge plan-badge-premium">
                                    <i class="fa-solid fa-crown"></i> {{ $planName }}
                                </span>
                            @else
                                <span class="plan-pill-badge plan-badge-default">
                                    <i class="fa-solid fa-tag"></i> {{ $planName }}
                                </span>
                            @endif
                        </td>

                        {{-- Plan Price --}}
                        <td>
                            <span class="price-value">₹{{ number_format($value->amount, 2) }}</span>
                        </td>

                        {{-- Paid Amt (After GST) --}}
                        <td>
                            <span class="paid-value">₹{{ number_format($value->paid_amount, 2) }}</span>
                        </td>

                        {{-- Trxn Id --}}
                        <td>
                            @if($value->transaction_id)
                            <div class="trxn-id-wrap">
                                <code class="trxn-code" title="{{ $value->transaction_id }}">{{ $value->transaction_id }}</code>
                                <button type="button" class="copy-trxn-btn" data-copy="{{ $value->transaction_id }}" data-bs-toggle="tooltip" title="Copy Trxn ID">
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                            </div>
                            @else
                            <span class="text-muted small">N/A</span>
                            @endif
                        </td>

                        {{-- Trxn Date --}}
                        <td>
                            <span class="date-text">
                                {{ $value->transaction_date ? \Carbon\Carbon::parse($value->transaction_date)->format('d M, Y') : 'N/A' }}
                            </span>
                        </td>

                        {{-- Payment Method --}}
                        <td>
                            @if($value->payment_mode == 1)
                                <span class="method-badge online">
                                    <i class="fa-solid fa-globe"></i> Online Paid
                                </span>
                            @elseif($value->payment_mode == 2)
                                <span class="method-badge offline">
                                    <i class="fa-solid fa-money-bill-wave"></i> Offline Paid
                                </span>
                            @else
                                <span class="method-badge not-paid">
                                    <i class="fa-solid fa-circle-xmark"></i> Not Paid
                                </span>
                            @endif
                        </td>

                        {{-- Trxn Status --}}
                        <td>
                            @if($value->is_paid == 1)
                                <span class="status-badge success">
                                    <i class="fa-solid fa-circle-check"></i> Success
                                </span>
                            @else
                                <span class="status-badge failed">
                                    <i class="fa-solid fa-circle-xmark"></i> Failed
                                </span>
                            @endif
                        </td>

                        {{-- Action (Download Receipt) --}}
                        <td>
                            @can('has-permission', 'Download Payment Receipt')
                            <div class="action-btn-wrapper">
                                <form action="{{ route('fee.generateReceipt') }}" method="POST" enctype="multipart/form-data" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $value->id }}">
                                    <input type="hidden" name="type" value="library">
                                    <button type="submit" class="receipt-print-btn noLoader" data-bs-toggle="tooltip" title="Download / Print Receipt">
                                        <i class="fa-solid fa-print"></i>
                                    </button>
                                </form>
                            </div>
                            @else
                            <span class="text-muted small">—</span>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        {{-- Empty State --}}
        <div class="empty-state-wrap">
            <div class="empty-icon-box">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <h5 class="fw-bold mb-1" style="color: #18225f;">No Transactions Found</h5>
            <p class="text-muted small mb-3">You do not have any recorded payment transactions yet.</p>
            @if(Route::has('library.myplan'))
            <a href="{{ route('library.myplan') }}" class="btn btn-primary button" style="background: #18225f !important;">
                <i class="fa-solid fa-crown me-1"></i> Explore Subscription Plans
            </a>
            @endif
        </div>
        @endif
    </div>

</div>

{{-- Interactive Scripts: Tooltips & Copy-to-Clipboard --}}
<script>
$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Copy Transaction ID to clipboard
    $(document).on('click', '.copy-trxn-btn', function(e) {
        e.preventDefault();
        var copyText = $(this).attr('data-copy');
        var btn = $(this);

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(copyText).then(function() {
                showCopySuccess(btn);
            });
        } else {
            // Fallback for older browsers / http
            var textArea = document.createElement("textarea");
            textArea.value = copyText;
            textArea.style.position = "fixed";
            textArea.style.opacity = "0";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                showCopySuccess(btn);
            } catch (err) {
                console.error('Copy fallback failed', err);
            }
            document.body.removeChild(textArea);
        }
    });

    function showCopySuccess(btn) {
        var originalHtml = btn.html();
        btn.html('<i class="fa-solid fa-check text-success"></i>');
        setTimeout(function() {
            btn.html(originalHtml);
        }, 1500);
    }
});
</script>

@endsection