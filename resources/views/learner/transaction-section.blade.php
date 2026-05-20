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
    $typeLabel = function ($value) {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return 'Transaction';
        }
        return ucwords(strtolower(str_replace('_', ' ', $text)));
    };
    $transactionBreakdown = function ($transaction) {
        $total = (float) ($transaction->total_amount ?? 0);
        $locker = (float) ($transaction->locker_amount ?? 0);
        $token = (float) ($transaction->token_money ?? 0);
        $misc = (float) ($transaction->miscellaneous ?? 0);
        $discount = (float) ($transaction->discount_amount ?? 0);

        return [
            'plan_price' => max(0, $total - $locker - $token - $misc + $discount),
            'locker' => $locker,
            'discount' => $discount,
            'total_amount' => $total,
            'final_payable' => $total + $token + $misc,
            'paid_amount' => (float) ($transaction->paid_amount ?? 0) + $token + $misc,
            'pending_or_extra' => (float) ($transaction->pending_amount ?? 0) > 0
                ? (float) $transaction->pending_amount
                : (float) ($transaction->refund ?? 0),
            'pending_or_extra_label' => (float) ($transaction->pending_amount ?? 0) > 0 ? 'Pending Amt.' : 'Extra Amt.',
            'other_total' => $token + $misc,
        ];
    };
    $activeStatus = (int) ($learner->status ?? 0) === 1 ? 'Active' : 'Inactive';
    $apiOverview = $tabData['overview'] ?? [];
    $apiSubscription = collect($tabData['subscription'] ?? [])->filter();
    $apiOtherPayments = collect($tabData['other_payment']['payments'] ?? []);
    $apiAllTransactions = collect($tabData['all_transaction'] ?? []);
    $apiActivities = collect($tabData['transaction_activity'] ?? []);
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
    .all-transaction-layout { display: grid; grid-template-columns: minmax(0, 1fr) 280px; gap: 16px; align-items: start; }
    .transaction-filter-bar { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
    .transaction-filter-bar button { border: 1px solid #dce1ea; background: #fff; color: #555; border-radius: 8px; padding: 8px 12px; font-weight: 700; font-size: .82rem; }
    .transaction-filter-bar button.active { background: #07156f; border-color: #07156f; color: #fff; }
    .transaction-history-card { border: 1px solid #e4e7ed; border-radius: 8px; background: #fff; margin-bottom: 12px; overflow: hidden; }
    .transaction-history-main { width: 100%; border: 0; background: #fff; padding: 14px 16px; display: grid; grid-template-columns: 46px minmax(0, 1fr) auto 24px; gap: 12px; align-items: center; text-align: left; }
    .transaction-history-title h6 { color: #07156f; font-weight: 800; margin: 0; }
    .transaction-history-title small { color: #707070; display: block; margin-top: 2px; }
    .transaction-history-amount { text-align: right; font-weight: 800; color: #4dae3c; white-space: nowrap; }
    .transaction-history-amount.debit, .transaction-history-amount.pending { color: #d60000; }
    .transaction-chevron { color: #555; transition: transform .18s ease; }
    .transaction-history-main[aria-expanded="true"] .transaction-chevron { transform: rotate(180deg); }
    .transaction-history-body { border-top: 1px solid #edf0f4; padding: 14px 16px 16px; background: #fff; }
    .transaction-breakdown { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0; border-top: 1px solid #edf0f4; border-bottom: 1px solid #edf0f4; margin-top: 12px; }
    .transaction-breakdown div { padding: 11px 10px; border-right: 1px solid #edf0f4; }
    .transaction-breakdown div:last-child { border-right: 0; }
    .transaction-breakdown span { display: block; color: #777; font-size: .75rem; margin-bottom: 3px; }
    .transaction-breakdown strong { color: #111; font-size: .9rem; }
    .transaction-body-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 12px; }
    .transaction-body-row div { background: #f8fafc; border-radius: 8px; padding: 10px; }
    .transaction-body-row span { display: block; color: #777; font-size: .75rem; }
    .transaction-body-row strong { font-size: .9rem; }
    .transaction-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 12px; }
    .transaction-actions a, .transaction-actions span, .transaction-actions button { width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; background: #f1f3f6; color: #111; text-decoration: none; border: 0; }
    .transaction-actions form { margin: 0; }
    .all-summary-panel { border: 1px solid #e4e7ed; border-radius: 8px; background: #fff; padding: 16px; position: sticky; top: 14px; }
    .all-summary-panel h6 { color: #07156f; font-weight: 800; margin-bottom: 12px; }
    .all-summary-panel .summary-line { display: flex; justify-content: space-between; gap: 10px; padding: 9px 0; border-bottom: 1px solid #edf0f4; font-size: .9rem; }
    .all-summary-panel .summary-line:last-child { border-bottom: 0; }
    .activity-mini-list { margin-top: 12px; }
    .activity-mini-list h6 { color: #686868; font-size: .86rem; margin-bottom: 8px; }
    .activity-mini-item { display: flex; justify-content: space-between; gap: 10px; border-top: 1px dashed #e4e7ed; padding: 8px 0; font-size: .84rem; }
    .transaction-empty { border: 1px dashed #dfe3ea; border-radius: 8px; background: #fff; padding: 22px; text-align: center; color: #777; }
    .activity-list-card { border: 1px solid #e4e7ed; border-radius: 8px; background: #fff; padding: 14px 16px; display: grid; grid-template-columns: 46px minmax(0, 1fr) auto; gap: 12px; align-items: center; margin-bottom: 12px; }
    .activity-list-card h6 { margin: 0; color: #07156f; font-weight: 800; }
    .activity-list-card small { color: #707070; }
    .activity-list-card .amount { font-weight: 800; text-align: right; color: #4dae3c; }
    .activity-list-card .amount.debit { color: #d60000; }
    @media (max-width: 768px) {
        .transaction-header { align-items: flex-start; }
        .transaction-status { margin-left: 0; }
        .metric-grid { grid-template-columns: repeat(2, 1fr); }
        .transaction-header { flex-wrap: wrap; }
        .payment-card { align-items: flex-start; }
        .all-transaction-layout { grid-template-columns: 1fr; }
        .all-summary-panel { position: static; order: -1; }
        .transaction-history-main { grid-template-columns: 44px minmax(0, 1fr) auto 20px; padding: 12px; }
        .transaction-breakdown { grid-template-columns: repeat(2, 1fr); }
        .transaction-breakdown div:nth-child(2) { border-right: 0; }
        .transaction-breakdown div:nth-child(-n+2) { border-bottom: 1px solid #edf0f4; }
        .transaction-body-row { grid-template-columns: 1fr; }
        .activity-list-card { grid-template-columns: 44px minmax(0, 1fr); }
        .activity-list-card .amount { grid-column: 2; text-align: left; }
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
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#activity" type="button">Activity</button></li>
    </ul>

    <div class="tab-content pt-3">
        <div class="tab-pane fade show active" id="overview">
            <div class="amount-panel">
                <h5>Total Amount Received</h5>
                <div class="main-amount">{{ $fmt($apiOverview['total_amount_received'] ?? $summary['received_amount']) }}</div>
                <p class="text-muted small mb-0">Final amount received from this learner, including subscription and other payments.</p>
                <div class="metric-grid">
                    <div class="metric"><span>Total Amt.</span><strong>{{ $fmt($apiOverview['total_amount'] ?? $summary['total_amount']) }}</strong></div>
                    <div class="metric"><span>Pending Amt.</span><strong class="pending">{{ $fmt($apiOverview['pending_amount'] ?? $summary['pending_amount']) }}</strong></div>
                    <div class="metric"><span>Extra Amt.</span><strong class="received">{{ $fmt($apiOverview['extra_amount'] ?? $summary['extra_amount']) }}</strong></div>
                    <div class="metric"><span>Refund Amt.</span><strong class="pending">{{ $fmt($apiOverview['refund_amount'] ?? $summary['refund_amount']) }}</strong></div>
                </div>
            </div>

            @if($apiOverview['next_due_date'] ?? $summary['next_due_date'])
                <div class="section-title">Next Payment Due</div>
                <div class="payment-card">
                    <div class="payment-icon"><i class="fa-solid fa-calendar-days"></i></div>
                    <div>
                        <h6>{{ $dateFmt($apiOverview['next_due_date'] ?? $summary['next_due_date']) }}</h6>
                        <small>{{ $modeLabel($currentDetail?->payment_mode) }}</small>
                    </div>
                    <div class="amount pending">{{ $fmt($apiOverview['next_due_amount'] ?? $summary['pending_amount']) }}</div>
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
            @forelse($apiSubscription as $subscription)
                @php
                    $collapseId = 'subscription-detail-'.$subscription['id'];
                    $isPending = (float) ($subscription['pending_amount'] ?? 0) > 0;
                @endphp
                <div class="transaction-history-card">
                    <button class="transaction-history-main" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="false" aria-controls="{{ $collapseId }}">
                        <span class="payment-icon"><i class="fa-solid fa-arrow-down"></i></span>
                        <span class="transaction-history-title">
                            <h6>Subscription Payment</h6>
                            <small>{{ $subscription['payment_mode'] ?? 'NA' }} &bull; {{ $dateFmt($subscription['paid_date'] ?? '') }}</small>
                        </span>
                        <span class="transaction-history-amount {{ $isPending ? 'pending' : '' }}">
                            {{ $fmt($subscription['total_paid_amount'] ?? 0) }}
                            @if($isPending)<small class="d-block text-danger">Due {{ $fmt($subscription['pending_amount']) }}</small>@endif
                        </span>
                        <span class="transaction-chevron"><i class="fa-solid fa-chevron-down"></i></span>
                    </button>
                    <div class="collapse" id="{{ $collapseId }}">
                        <div class="transaction-history-body">
                            <div class="detail-row"><span>Plan</span><strong>{{ $subscription['plan'] ?? 'NA' }} / {{ $subscription['plan_type'] ?? 'NA' }}</strong></div>
                            <div class="detail-row"><span>Duration</span><strong>{{ $dateFmt($subscription['plan_start_date'] ?? '') }} - {{ $dateFmt($subscription['plan_end_date'] ?? '') }}</strong></div>
                            <div class="transaction-breakdown">
                                <div><span>Plan Price</span><strong>{{ $fmt($subscription['plan_price'] ?? 0) }}</strong></div>
                                <div><span>Locker</span><strong>{{ $fmt($subscription['locker_amount'] ?? 0) }}</strong></div>
                                <div><span>Discount</span><strong>{{ $fmt($subscription['discount_amount'] ?? 0) }}</strong></div>
                                <div><span>Total Amt.</span><strong>{{ $fmt($subscription['total_amount'] ?? 0) }}</strong></div>
                            </div>
                            <div class="transaction-actions">
                                @if(!empty($subscription['subscription_download_receipt_link']))
                                    <a href="{{ $subscription['subscription_download_receipt_link'] }}" target="_blank" data-bs-toggle="tooltip" data-bs-title="Download Receipt"><i class="fa-solid fa-download"></i></a>
                                @endif
                                @if(!empty($subscription['edit_url']))
                                    <a href="{{ $subscription['edit_url'] }}" data-bs-toggle="tooltip" data-bs-title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                @endif
                                @if(!empty($subscription['delete_url']))
                                    <form method="POST" action="{{ $subscription['delete_url'] }}" onsubmit="return confirm('Delete this renew transaction?');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="learner_id" value="{{ $learner->id }}">
                                        <button type="submit" data-bs-toggle="tooltip" data-bs-title="Delete"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
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
            <div class="all-transaction-layout">
                <div>
                    <div class="transaction-filter-bar" data-transaction-filters>
                        <button type="button" class="active" data-filter="all">All</button>
                        <button type="button" data-filter="paid">Paid</button>
                        <button type="button" data-filter="pending">Pending</button>
                        <button type="button" data-filter="other">Other</button>
                        <button type="button" data-filter="refund">Refund</button>
                    </div>

                    @forelse($apiAllTransactions as $transaction)
                        @php
                            $relatedActivities = collect($transaction['activity'] ?? []);
                            $primaryActivity = $relatedActivities->first();
                            $isPending = (float) ($transaction['pending_amount'] ?? 0) > 0;
                            $hasOther = false;
                            $hasRefund = (float) ($transaction['extra_amount'] ?? 0) > 0 || $relatedActivities->contains(fn ($activity) => strtoupper((string) ($activity['payment_type'] ?? '')) === 'REFUND');
                            $filterTags = collect(['all'])
                                ->when(!$isPending, fn ($items) => $items->push('paid'))
                                ->when($isPending, fn ($items) => $items->push('pending'))
                                ->when($hasOther, fn ($items) => $items->push('other'))
                                ->when($hasRefund, fn ($items) => $items->push('refund'))
                                ->implode(' ');
                            $collapseId = 'transaction-detail-'.$transaction['id'];
                            $title = $primaryActivity['transaction_type'] ?? 'Subscription Payment';
                            $amountClass = $isPending ? 'pending' : '';
                        @endphp

                        <div class="transaction-history-card" data-transaction-card data-tags="{{ $filterTags }}">
                            <button class="transaction-history-main" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="false" aria-controls="{{ $collapseId }}">
                                <span class="payment-icon {{ $hasRefund ? 'debit' : '' }}">
                                    <i class="fa-solid fa-arrow-{{ $hasRefund ? 'up' : 'down' }}"></i>
                                </span>
                                <span class="transaction-history-title">
                                    <h6>{{ $transaction['transaction_type'] ?? 'NA' }}</h6>
                                    <small>{{ $modeLabel($primaryActivity['payment_mode'] ?? $transaction['payment_mode'] ?? '') }} &bull; {{ $dateFmt($transaction['paid_date'] ?? '') }}</small>
                                </span>
                                <span class="transaction-history-amount {{ $amountClass }}">
                                    {{ $fmt($transaction['total_paid_amount'] ?? 0) }}
                                    @if($isPending)
                                        <small class="d-block text-danger">Due {{ $fmt($transaction['pending_amount'] ?? 0) }}</small>
                                    @endif
                                </span>
                                <span class="transaction-chevron"><i class="fa-solid fa-chevron-down"></i></span>
                            </button>

                            <div class="collapse" id="{{ $collapseId }}">
                                <div class="transaction-history-body">
                                    <div class="detail-row"><span>Plan</span><strong>{{ $transaction['plan'] ?? 'NA' }} / {{ $transaction['plan_type'] ?? 'NA' }}</strong></div>
                                    <div class="detail-row"><span>Duration</span><strong>{{ $dateFmt($transaction['plan_start_date'] ?? '') }} - {{ $dateFmt($transaction['plan_end_date'] ?? '') }}</strong></div>

                                    <div class="transaction-breakdown">
                                        <div><span>Plan Price</span><strong>{{ $fmt($transaction['plan_price'] ?? 0) }}</strong></div>
                                        <div><span>Locker</span><strong>{{ $fmt($transaction['locker_amount'] ?? 0) }}</strong></div>
                                        <div><span>Discount</span><strong>{{ $fmt($transaction['discount_amount'] ?? 0) }}</strong></div>
                                        <div><span>Total Amt.</span><strong>{{ $fmt($transaction['total_amount'] ?? 0) }}</strong></div>
                                    </div>

                                    <div class="transaction-body-row">
                                        <div><span>Final Payable Amt.</span><strong>{{ $fmt($transaction['final_payable_amount'] ?? 0) }}</strong></div>
                                        <div><span>Paid Amt.</span><strong class="text-success">{{ $fmt($transaction['total_paid_amount'] ?? 0) }}</strong></div>
                                        <div><span>Pending / Extra Amt.</span><strong class="{{ $isPending ? 'text-danger' : 'text-primary' }}">{{ $isPending ? $fmt($transaction['pending_amount'] ?? 0) : $fmt($transaction['extra_amount'] ?? 0) }}</strong></div>
                                    </div>

                                    @if($relatedActivities->isNotEmpty())
                                        <div class="activity-mini-list">
                                            <h6>Activity</h6>
                                            @foreach($relatedActivities as $activity)
                                                <div class="activity-mini-item">
                                                    <span>{{ $typeLabel($activity['payment_type'] ?? $activity['payment_type'] ?? '') }} &bull; {{ $modeLabel($activity['payment_mode'] ?? '') }}</span>
                                                    <strong class="{{ strtolower((string) ($activity['dr_cr'] ?? '')) === 'dr' ? 'text-danger' : 'text-success' }}">{{ $fmt($activity['paid_amount'] ?? 0) }}</strong>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="transaction-actions">
                                        @if(!empty($transaction['subscription_download_receipt_link']))
                                            <a href="{{ $transaction['subscription_download_receipt_link'] }}" target="_blank" data-bs-toggle="tooltip" data-bs-title="Download Receipt"><i class="fa-solid fa-download"></i></a>
                                        @else
                                            <span class="text-muted" data-bs-toggle="tooltip" data-bs-title="Receipt unavailable"><i class="fa-solid fa-download"></i></span>
                                        @endif
                                        @if(!empty($transaction['edit_url']))
                                            <a href="{{ $transaction['edit_url'] }}" data-bs-toggle="tooltip" data-bs-title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                        @endif
                                        @if(!empty($transaction['delete_url']))
                                            <form method="POST" action="{{ $transaction['delete_url'] }}" onsubmit="return confirm('Delete this renew transaction?');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="learner_id" value="{{ $learner->id }}">
                                                <button type="submit" data-bs-toggle="tooltip" data-bs-title="Delete"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="transaction-empty">No transaction recorded.</div>
                    @endforelse
                </div>

                <aside class="all-summary-panel">
                    <h6>All Transaction</h6>
                    <div class="summary-line"><span>Total Entries</span><strong>{{ $transactions->count() }}</strong></div>
                    <div class="summary-line"><span>Received</span><strong class="text-success">{{ $fmt($summary['received_amount']) }}</strong></div>
                    <div class="summary-line"><span>Pending</span><strong class="text-danger">{{ $fmt($summary['pending_amount']) }}</strong></div>
                    <div class="summary-line"><span>Other Payment</span><strong>{{ $fmt($transactions->sum('token_money') + $transactions->sum('miscellaneous')) }}</strong></div>
                    <div class="summary-line"><span>Refund / Extra</span><strong class="text-primary">{{ $fmt($summary['extra_amount']) }}</strong></div>
                </aside>
            </div>
        </div>

        <div class="tab-pane fade" id="activity">
            @forelse($apiActivities as $activity)
                @php $isDebit = strtolower((string) ($activity['dr_cr'] ?? '')) === 'dr'; @endphp
                <div class="activity-list-card">
                    <span class="payment-icon {{ $isDebit ? 'debit' : '' }}">
                        <i class="fa-solid fa-arrow-{{ $isDebit ? 'up' : 'down' }}"></i>
                    </span>
                    <div>
                        <h6>{{ $typeLabel($activity['particular'] ?? $activity['payment_type'] ?? '') }}</h6>
                        <small>{{ $modeLabel($activity['payment_mode'] ?? '') }} &bull; {{ $dateFmt($activity['transaction_date'] ?? '') }} &bull; Added by {{ $activity['added_by'] ?? 'System User' }}</small>
                    </div>
                    <div class="amount {{ $isDebit ? 'debit' : '' }}">
                        {{ $fmt($activity['paid_amount'] ?? 0) }}
                        <div class="transaction-actions mt-2">
                            @if(!empty($activity['download_receipt_url']))
                                <a href="{{ $activity['download_receipt_url'] }}" target="_blank" data-bs-toggle="tooltip" data-bs-title="Download"><i class="fa-solid fa-download"></i></a>
                            @else
                                <span class="text-muted" data-bs-toggle="tooltip" data-bs-title="Download unavailable"><i class="fa-solid fa-download"></i></span>
                            @endif
                            @if(!empty($activity['edit_url']))
                                <a href="{{ $activity['edit_url'] }}" data-bs-toggle="tooltip" data-bs-title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                            @endif
                            @if(!empty($activity['delete_url']))
                                <form method="POST" action="{{ $activity['delete_url'] }}" onsubmit="return confirm('Delete this activity?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" data-bs-toggle="tooltip" data-bs-title="Delete"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="transaction-empty">No activity recorded.</div>
            @endforelse
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filterWrap = document.querySelector('[data-transaction-filters]');
        if (!filterWrap) {
            return;
        }

        filterWrap.addEventListener('click', function (event) {
            const button = event.target.closest('button[data-filter]');
            if (!button) {
                return;
            }

            const filter = button.dataset.filter;
            filterWrap.querySelectorAll('button').forEach(item => item.classList.remove('active'));
            button.classList.add('active');

            document.querySelectorAll('[data-transaction-card]').forEach(card => {
                const tags = (card.dataset.tags || '').split(' ');
                card.style.display = tags.includes(filter) ? '' : 'none';
            });
        });
    });
</script>

@endsection
