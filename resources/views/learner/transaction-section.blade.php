@extends('layouts.library')
@section('content')

@php
    $fmt = fn ($amount) => rtrim(rtrim(number_format((float) ($amount ?? 0), 2, '.', ''), '0'), '.');
    $dateFmt = fn ($date) => $date ? \Carbon\Carbon::parse($date)->format('j M Y') : 'NA';
    $modeLabel = function ($mode) {
        if ($mode === null || $mode === '') {
            return 'NA';
        }
        if (is_numeric($mode)) {
            return match ((int) $mode) {
                1 => 'Online Payment',
                2 => 'Offline Payment',
                3 => 'Pay Later',
                default => (string) $mode,
            };
        }
        return ucwords(strtolower((string) $mode));
    };
    $activeStatus = (int) ($learner->status ?? 0) === 1 ? 'Active' : 'Inactive';
@endphp

<style>
    .transaction-page { max-width: 1180px; margin: 0 auto; }
    .transaction-header { border: 1px solid #dfe3ea; border-radius: 8px; background: #fff; padding: 18px; display: flex; align-items: center; gap: 16px; }
    .transaction-header img { width: 64px; height: 64px; border-radius: 50%; object-fit: cover; background: #f2f2f2; }
    .transaction-header h4 { margin: 0; color: #07156f; font-size: 1.15rem; font-weight: 700; }
    .transaction-header p { margin: 2px 0 0; color: #666; font-size: .84rem; }
    .transaction-status { margin-left: auto; background: #d9ffc9; color: #188000; border-radius: 5px; padding: 7px 14px; font-size: .82rem; font-weight: 700; }
    .transaction-tabs { border-bottom: 1px solid #e5e7eb; gap: 18px; flex-wrap: nowrap; overflow-x: auto; }
    .transaction-tabs .nav-link { border: 0; color: #777; font-weight: 600; padding: 16px 2px 11px; white-space: nowrap; }
    .transaction-tabs .nav-link.active { color: #07156f; border-bottom: 4px solid #07156f; background: transparent; }
    .amount-panel { border: 1px solid #dfe3ea; border-radius: 8px; background: #f7f8ff; padding: 20px; text-align: center; }
    .amount-panel h5 { color: #07156f; font-weight: 700; font-size: 1rem; }
    .amount-panel .main-amount { color: #24177a; font-size: 2.8rem; line-height: 1; font-weight: 800; }
    .metric-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 18px; }
    .metric, .payment-box { border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px; background: #fff; text-align: center; min-height: 82px; }
    .metric span, .payment-box span { color: #747474; font-size: .8rem; }
    .metric strong, .payment-box strong { display: block; font-size: 1.25rem; margin-top: 4px; color: #111; }
    .metric .pending, .payment-box .pending { color: #d60000; }
    .metric .received, .payment-box .received { color: #4dae3c; }
    .section-title { color: #686868; font-weight: 700; margin: 22px 0 10px; }
    .payment-card { border: 1px solid #e3e6eb; border-radius: 8px; background: #fff; padding: 14px 16px; display: flex; align-items: center; gap: 14px; margin-bottom: 12px; }
    .payment-icon { width: 44px; height: 44px; border-radius: 50%; background: #dbffd1; display: grid; place-items: center; color: #55bd39; flex: 0 0 auto; }
    .payment-icon.debit { background: #ffe7e7; color: #e42424; }
    .payment-card h6 { margin: 0; color: #07156f; font-weight: 800; }
    .payment-card small { color: #777; }
    .payment-card .amount { margin-left: auto; text-align: right; font-weight: 800; color: #4dae3c; }
    .payment-card .amount.debit { color: #d60000; }
    .detail-panel { border: 1px solid #dfe3ea; border-radius: 8px; background: #fff; padding: 20px; }
    .detail-row { display: flex; justify-content: space-between; gap: 16px; padding: 7px 0; font-size: .92rem; }
    .detail-row span { color: #666; }
    .detail-row strong { text-align: right; }
    @media (max-width: 768px) {
        .transaction-header { align-items: flex-start; }
        .transaction-status { margin-left: 0; }
        .metric-grid { grid-template-columns: repeat(2, 1fr); }
        .transaction-header { flex-wrap: wrap; }
        .payment-card { align-items: flex-start; }
    }
</style>

<div class="transaction-page">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <a href="{{ route('learners') }}" class="btn btn-light border"><i class="fa-solid fa-arrow-left"></i></a>
        <h4 class="mb-0 text-primary fw-bold">All Transaction</h4>
        <span></span>
    </div>

    <div class="transaction-header mb-3">
        <img src="{{ $learner->profile_picture ? asset($learner->profile_picture) : asset('public/img/student_profile.jpeg') }}" alt="profile">
        <div>
            <h4>{{ $learner->name }}</h4>
            <p>{{ $learner->learner_no ?? 'NA' }} &bull; Seat {{ $currentDetail?->seat_no ? getSeatDisplayByMainNo($currentDetail->seat_no) : 'GEN' }}</p>
        </div>
        <div class="transaction-status">{{ $activeStatus }}</div>
    </div>

    <ul class="nav nav-tabs transaction-tabs" id="transactionTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#overview" type="button">Overview</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#subscription" type="button">Subscription</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#otherPayment" type="button">Other Payment</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#allTransactions" type="button">All Transactions</button></li>
    </ul>

    <div class="tab-content pt-3">
        <div class="tab-pane fade show active" id="overview">
            <div class="amount-panel">
                <h5>Total Amount Received</h5>
                <div class="main-amount">{{ $fmt($summary['received_amount']) }}</div>
                <p class="text-muted small mb-0">Final amount received from this learner, including subscription and other payments.</p>
                <div class="metric-grid">
                    <div class="metric"><span>Total Amt.</span><strong>{{ $fmt($summary['total_amount']) }}</strong></div>
                    <div class="metric"><span>Pending Amt.</span><strong class="pending">{{ $fmt($summary['pending_amount']) }}</strong></div>
                    <div class="metric"><span>Extra Amt.</span><strong class="received">{{ $fmt($summary['extra_amount']) }}</strong></div>
                    <div class="metric"><span>Refund Amt.</span><strong class="pending">{{ $fmt($summary['refund_amount']) }}</strong></div>
                </div>
            </div>

            @if($summary['next_due_date'])
                <div class="section-title">Next Payment Due</div>
                <div class="payment-card">
                    <div class="payment-icon"><i class="fa-solid fa-calendar-days"></i></div>
                    <div>
                        <h6>{{ $dateFmt($summary['next_due_date']) }}</h6>
                        <small>{{ $modeLabel($currentDetail?->payment_mode) }}</small>
                    </div>
                    <div class="amount pending">{{ $fmt($summary['pending_amount']) }}</div>
                </div>
            @endif

            <div class="section-title">Last 3 Transactions</div>
            @forelse($activities->take(3) as $activity)
                @include('learner.partials.transaction-card', ['activity' => $activity, 'fmt' => $fmt, 'dateFmt' => $dateFmt, 'modeLabel' => $modeLabel])
            @empty
                <div class="payment-card text-muted">No transaction recorded.</div>
            @endforelse
        </div>

        <div class="tab-pane fade" id="subscription">
            <div class="detail-panel mb-3">
                <h5 class="text-primary fw-bold mb-3">Active Subscription details</h5>
                <div class="detail-row"><span>Plan</span><strong>{{ $currentDetail?->plan?->name ?? 'NA' }}</strong></div>
                <div class="detail-row"><span>Duration</span><strong>{{ $dateFmt($currentDetail?->plan_start_date) }} - {{ $dateFmt($currentDetail?->plan_end_date) }}</strong></div>
                <div class="detail-row"><span>Locker</span><strong>{{ ($latestTransaction?->locker_amount ?? 0) > 0 ? 'Yes : Price '.$fmt($latestTransaction->locker_amount) : 'No' }}</strong></div>
                <div class="detail-row"><span>Plan Price</span><strong>{{ $fmt(($latestTransaction?->total_amount ?? 0) - ($latestTransaction?->locker_amount ?? 0) + ($latestTransaction?->discount_amount ?? 0)) }}</strong></div>
                <div class="detail-row"><span>Discount</span><strong>{{ $fmt($latestTransaction?->discount_amount ?? 0) }}</strong></div>
                <div class="detail-row"><span>Total Payable</span><strong>{{ $fmt($latestTransaction?->total_amount ?? 0) }}</strong></div>
            </div>

            <div class="metric-grid mb-3">
                <div class="payment-box"><span>Total Payment</span><strong>{{ $fmt($summary['total_amount']) }}</strong></div>
                <div class="payment-box"><span>Received Amt.</span><strong class="received">{{ $fmt($summary['received_amount']) }}</strong></div>
                <div class="payment-box"><span>Pending Amt.</span><strong class="pending">{{ $fmt($summary['pending_amount']) }}</strong></div>
                <div class="payment-box"><span>Plans</span><strong>{{ $transactions->count() }}</strong></div>
            </div>

            <div class="section-title">Subscription Summary</div>
            @forelse($subscriptionActivities as $activity)
                @include('learner.partials.transaction-card', ['activity' => $activity, 'fmt' => $fmt, 'dateFmt' => $dateFmt, 'modeLabel' => $modeLabel])
            @empty
                <div class="payment-card text-muted">No subscription activity recorded.</div>
            @endforelse
        </div>

        <div class="tab-pane fade" id="otherPayment">
            <div class="metric-grid mb-3">
                <div class="payment-box"><span>Total Payment</span><strong>{{ $fmt(($latestTransaction?->token_money ?? 0) + ($latestTransaction?->miscellaneous ?? 0) + $summary['refund_amount']) }}</strong></div>
                <div class="payment-box"><span>Received Amt.</span><strong class="received">{{ $fmt(($latestTransaction?->token_money ?? 0) + ($latestTransaction?->miscellaneous ?? 0)) }}</strong></div>
                <div class="payment-box"><span>Pending Amt.</span><strong class="pending">{{ $fmt($summary['extra_amount']) }}</strong></div>
                <div class="payment-box"><span>Entries</span><strong>{{ $otherActivities->count() }}</strong></div>
            </div>
            <div class="section-title">Payment Summary</div>
            @forelse($otherActivities as $activity)
                @include('learner.partials.transaction-card', ['activity' => $activity, 'fmt' => $fmt, 'dateFmt' => $dateFmt, 'modeLabel' => $modeLabel])
            @empty
                <div class="payment-card text-muted">No other payment recorded.</div>
            @endforelse
        </div>

        <div class="tab-pane fade" id="allTransactions">
            @forelse($activities as $activity)
                @include('learner.partials.transaction-card', ['activity' => $activity, 'fmt' => $fmt, 'dateFmt' => $dateFmt, 'modeLabel' => $modeLabel])
            @empty
                <div class="payment-card text-muted">No transaction recorded.</div>
            @endforelse
        </div>
    </div>
</div>

@endsection
