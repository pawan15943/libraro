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
    $shiftTimeFmt = function ($planType) {
        if (!$planType || !$planType->start_time || !$planType->end_time) {
            return '';
        }
        try {
            $start = \Carbon\Carbon::parse($planType->start_time)->format('g a');
            $end = \Carbon\Carbon::parse($planType->end_time)->format('g a');
            return " ({$start} to {$end})";
        } catch (\Throwable $e) {
            return '';
        }
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
    .transaction-page { max-width: 1180px; margin: 0 auto 3rem; }
    .transaction-header { border: 1px solid #e7e9f0; border-radius: 12px; background: #fff; padding: 20px 22px; display: flex; align-items: center; gap: 18px; box-shadow: 0 2px 10px rgba(17, 24, 63, .04); }
    .transaction-header img { width: 64px; height: 64px; border-radius: 50%; object-fit: cover; background: #f2f2f2; border: 3px solid #eef1ff; }
    .transaction-header h4 { margin: 0; color: #07156f; font-size: 1.2rem; font-weight: 700; }
    .transaction-header p { margin: 3px 0 0; color: #6b7280; font-size: .85rem; }
    .transaction-status { margin-left: auto; background: #d9ffc9; color: #188000; border-radius: 20px; padding: 7px 16px; font-size: .82rem; font-weight: 700; }
    .transaction-tabs { border-bottom: 1px solid #e5e7eb; gap: 26px; flex-wrap: nowrap; overflow-x: auto; margin-top: 22px; }
    .transaction-tabs .nav-link { border: 0; color: #777; font-weight: 600; padding: 14px 0 12px; white-space: nowrap; font-size: .93rem; transition: color .15s ease; }
    .transaction-tabs .nav-link:hover { color: #07156f; }
    .transaction-tabs .nav-link.active { color: #07156f; border-bottom: 3px solid #07156f; background: transparent; }
    .amount-panel { border: 1px solid #dfe3ea; border-radius: 12px; background: linear-gradient(180deg, #f7f8ff 0%, #fbfbff 100%); padding: 26px 20px; text-align: center; }
    .amount-panel h5 { color: #5c6373; font-weight: 600; font-size: .92rem; text-transform: uppercase; letter-spacing: .4px; }
    .amount-panel .main-amount { color: #1b1464; font-size: 2.6rem; line-height: 1; font-weight: 800; margin-top: 6px; }
    .metric-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 20px; }
    .metric, .payment-box { border: 1px solid #e9ebf1; border-radius: 10px; padding: 14px; background: #fff; text-align: center; min-height: 82px; transition: box-shadow .15s ease, transform .15s ease; }
    .metric:hover, .payment-box:hover { box-shadow: 0 4px 14px rgba(17, 24, 63, .06); transform: translateY(-1px); }
    .metric span, .payment-box span { color: #838a99; font-size: .78rem; }
    .metric strong, .payment-box strong { display: block; font-size: 1.2rem; margin-top: 5px; color: #171923; }
    .metric .pending, .payment-box .pending { color: #d60000; }
    .metric .received, .payment-box .received { color: #4dae3c; }
    .metric .refund { color: #07156f; }
    .section-title { color: #4b5162; font-weight: 700; font-size: .95rem; margin: 26px 0 12px; display: flex; align-items: center; gap: 8px; }
    .section-title::before { content: ''; width: 4px; height: 16px; background: #07156f; border-radius: 2px; display: inline-block; }
    .payment-card { border: 1px solid #e9ebf1; border-radius: 10px; background: #fff; padding: 14px 16px; display: flex; align-items: center; gap: 14px; margin-bottom: 14px; transition: box-shadow .15s ease; }
    .payment-card:hover { box-shadow: 0 4px 14px rgba(17, 24, 63, .05); }
    .payment-icon { width: 42px; height: 42px; border-radius: 50%; background: #dbffd1; display: grid; place-items: center; color: #55bd39; flex: 0 0 auto; font-size: .95rem; }
    .payment-icon.debit { background: #ffe7e7; color: #e42424; }
    .payment-card h6 { margin: 0; color: #1e2333; font-weight: 700; font-size: .95rem; }
    .payment-card small { color: #838a99; }
    .payment-card .amount { margin-left: auto; text-align: right; font-weight: 800; color: #4dae3c; }
    .payment-card .amount.debit { color: #d60000; }
    .payment-card-action { width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; background: #f1f3f6; color: #111; text-decoration: none; flex: 0 0 auto; transition: background .15s ease; }
    .payment-card-action:hover { background: #e5e8f5; }
    .subscription-status-badge { background: #d9ffc9; color: #188000; border-radius: 20px; padding: 5px 14px; font-size: .78rem; font-weight: 700; }
    .subscription-status-badge.expired, .subscription-status-badge.closed, .subscription-status-badge.deleted { background: #ffe7e7; color: #d60000; }
    .subscription-status-badge.upcoming { background: #eef1ff; color: #07156f; }
    .detail-panel { border: 1px solid #e7e9f0; border-radius: 12px; background: #fff; padding: 22px; box-shadow: 0 2px 10px rgba(17, 24, 63, .03); }
    .detail-row { display: flex; justify-content: space-between; gap: 16px; padding: 9px 0; font-size: .92rem; border-bottom: 1px solid #f2f3f7; }
    .detail-row:last-of-type { border-bottom: 0; }
    .detail-row span { color: #6b7280; }
    .detail-row strong { text-align: right; color: #1e2333; }
    .all-transaction-layout { display: grid; grid-template-columns: minmax(0, 1fr) minmax(240px, 280px); gap: 18px; align-items: start; padding-right: 2px; }
    .transaction-filter-bar { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
    .transaction-filter-bar button { border: 1px solid #dce1ea; background: #fff; color: #555; border-radius: 20px; padding: 7px 15px; font-weight: 600; font-size: .82rem; transition: all .15s ease; }
    .transaction-filter-bar button.active { background: #07156f; border-color: #07156f; color: #fff; }
    .transaction-history-card { border: 1px solid #e7e9f0; border-radius: 12px; background: #fff; margin-bottom: 14px; overflow: hidden; box-shadow: 0 2px 8px rgba(17, 24, 63, .03); }
    .transaction-history-main { width: 100%; border: 0; background: #fff; padding: 16px; display: grid; grid-template-columns: 46px minmax(0, 1fr) auto 24px; gap: 12px; align-items: center; text-align: left; }
    .transaction-history-title h6 { color: #1e2333; font-weight: 700; margin: 0; font-size: .95rem; }
    .transaction-history-title small { color: #838a99; display: block; margin-top: 2px; }
    .transaction-history-amount { text-align: right; font-weight: 800; color: #4dae3c; white-space: nowrap; }
    .transaction-history-amount.debit, .transaction-history-amount.pending { color: #d60000; }
    .transaction-chevron { color: #9aa0ac; transition: transform .18s ease; }
    .transaction-history-main[aria-expanded="true"] .transaction-chevron { transform: rotate(180deg); }
    .transaction-history-body { border-top: 1px solid #edf0f4; padding: 16px; background: #fbfbfd; }
    .transaction-breakdown { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0; border-top: 1px solid #edf0f4; border-bottom: 1px solid #edf0f4; margin-top: 12px; }
    .transaction-breakdown div { padding: 11px 10px; border-right: 1px solid #edf0f4; }
    .transaction-breakdown div:first-child { padding-left: 0; }
    .transaction-breakdown div:last-child { padding-right: 0; border-right: 0; }
    .transaction-breakdown span { display: block; color: #838a99; font-size: .75rem; margin-bottom: 3px; }
    .transaction-breakdown strong { color: #1e2333; font-size: .9rem; }
    .transaction-body-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 12px; }
    .transaction-body-row div { background: #fff; border: 1px solid #edf0f4; border-radius: 8px; padding: 10px 12px; }
    .transaction-body-row span { display: block; color: #838a99; font-size: .75rem; }
    .transaction-body-row strong { font-size: .9rem; color: #1e2333; }
    .transaction-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px; }
    .transaction-actions a, .transaction-actions span, .transaction-actions button { width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; background: #f1f3f6; color: #111; text-decoration: none; border: 0; transition: background .15s ease; }
    .transaction-actions a:hover, .transaction-actions button:hover { background: #e5e8f5; }
    .transaction-actions form { margin: 0; }
    .all-summary-panel { border: 1px solid #e7e9f0; border-radius: 12px; background: #fff; padding: 18px; margin-right: 4px; position: sticky; top: 14px; box-shadow: 0 2px 10px rgba(17, 24, 63, .03); min-width: 0; }
    .all-summary-panel h6 { color: #07156f; font-weight: 800; margin-bottom: 6px; }
    .all-summary-panel .summary-line { display: flex; justify-content: space-between; gap: 10px; padding: 10px 0; border-bottom: 1px solid #edf0f4; font-size: .9rem; }
    .all-summary-panel .summary-line:last-child { border-bottom: 0; }
    .activity-mini-list { margin-top: 12px; }
    .activity-mini-list h6 { color: #838a99; font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; margin-bottom: 8px; }
    .activity-mini-item { display: flex; justify-content: space-between; gap: 10px; border-top: 1px dashed #e4e7ed; padding: 9px 0; font-size: .84rem; }
    .transaction-empty { border: 1px dashed #dfe3ea; border-radius: 12px; background: #fbfbfd; padding: 28px; text-align: center; color: #838a99; }
    .activity-list-card { border: 1px solid #e7e9f0; border-radius: 12px; background: #fff; padding: 16px; display: grid; grid-template-columns: 46px minmax(0, 1fr) auto; gap: 14px; align-items: center; margin-bottom: 14px; box-shadow: 0 2px 8px rgba(17, 24, 63, .03); transition: box-shadow .15s ease; }
    .activity-list-card:hover { box-shadow: 0 4px 14px rgba(17, 24, 63, .06); }
    .activity-list-card h6 { margin: 0; color: #1e2333; font-weight: 700; font-size: .93rem; line-height: 1.35; }
    .activity-list-card small { color: #838a99; display: block; margin-top: 3px; }
    .activity-list-card .amount { font-weight: 800; text-align: right; color: #4dae3c; }
    .activity-list-card .amount.debit { color: #d60000; }
    @media (max-width: 1100px) {
        .all-transaction-layout { grid-template-columns: 1fr; }
        .all-summary-panel { position: static; margin-right: 0; order: -1; }
    }
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
        .transaction-breakdown div:nth-child(odd) { padding-left: 0; }
        .transaction-breakdown div:nth-child(even) { padding-right: 0; }
        .transaction-body-row { grid-template-columns: 1fr; }
        .activity-list-card { grid-template-columns: 44px minmax(0, 1fr); }
        .activity-list-card .amount { grid-column: 2; text-align: left; }
    }
</style>

<div class="transaction-page">
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
                <p class="text-muted small mb-0">This is the final amount received from this learner, including all pending and refunded amounts.</p>
                <div class="metric-grid">
                    <div class="metric"><span>Total Amt.</span><strong>{{ $fmt($apiOverview['total_amount'] ?? $summary['total_amount']) }}</strong></div>
                    <div class="metric"><span>Pending Amt.</span><strong class="pending">{{ $fmt($apiOverview['pending_amount'] ?? $summary['pending_amount']) }}</strong></div>
                    <div class="metric"><span>Extra Amt.</span><strong class="received">{{ $fmt($apiOverview['extra_amount'] ?? $summary['extra_amount']) }}</strong></div>
                    <div class="metric"><span>Refund Amt.</span><strong class="refund">{{ $fmt($apiOverview['refund_amount'] ?? $summary['refund_amount']) }}</strong></div>
                </div>
            </div>

            @if($apiOverview['next_due_date'] ?? $summary['next_due_date'])
                <div class="section-title">Next Payment Due</div>
                <div class="payment-card">
                    <div class="payment-icon"><i class="fa-solid fa-calendar-days"></i></div>
                    <div>
                        <h6>{{ $apiOverview['next_due_date'] ?? $summary['next_due_date'] }}</h6>
                        <small>(Subscription + Carryforward) - Extra Amt.</small>
                    </div>
                    <div class="amount">{{ $fmt($apiOverview['next_due_amount'] ?? $summary['pending_amount']) }}</div>
                </div>
            @endif

            <div class="section-title">Last Subscription Transaction</div>
            @if($apiOverview['last_transactions'] ?? null)
                @include('learner.partials.transaction-detail-card', ['transaction' => $apiOverview['last_transactions'], 'fmt' => $fmt, 'dateFmt' => $dateFmt, 'modeLabel' => $modeLabel, 'typeLabel' => $typeLabel, 'learner' => $learner, 'collapseId' => 'overview-last-transaction'])
            @else
                <div class="payment-card text-muted">No transaction recorded.</div>
            @endif
        </div>

        <div class="tab-pane fade" id="subscription">
            <div class="metric-grid mb-3" style="grid-template-columns: repeat(3, 1fr);">
                <div class="payment-box"><span>Total Payment</span><strong>{{ $fmt($summary['total_amount']) }}</strong></div>
                <div class="payment-box"><span>Received Amt.</span><strong class="received">{{ $fmt($summary['received_amount']) }}</strong></div>
                <div class="payment-box"><span>Pending Amt.</span><strong class="pending">{{ $fmt($summary['pending_amount']) }}</strong></div>
            </div>

            @php $subscription = $apiSubscription->first(); @endphp
            @if($subscription)
                <div class="detail-panel mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="text-primary fw-bold mb-0">Subscription Summary</h5>
                        <span class="subscription-status-badge {{ strtolower($subscription['status'] ?? '') }}">{{ $subscription['status'] ?? 'NA' }}</span>
                    </div>
                    @if(!empty($subscription['transaction_ref']))
                        <div class="detail-row"><span>Txn No.</span><strong>{{ $subscription['transaction_ref'] }}</strong></div>
                    @endif
                    <div class="detail-row"><span>Plan</span><strong>{{ $subscription['plan'] ?? 'NA' }}</strong></div>
                    <div class="detail-row"><span>Duration</span><strong>{{ $dateFmt($subscription['plan_start_date'] ?? '') }} - {{ $dateFmt($subscription['plan_end_date'] ?? '') }}{{ $shiftTimeFmt($currentDetail?->planType) }}</strong></div>
                    <div class="detail-row"><span>Locker</span><strong>{{ $subscription['locker'] ?? 'No' }} | Price : {{ $fmt($subscription['locker_amount'] ?? 0) }}</strong></div>
                    <div class="detail-row"><span>Plan Price</span><strong>{{ $fmt($subscription['plan_price'] ?? 0) }}</strong></div>
                    <div class="detail-row"><span>Discount (in Amount)</span><strong>{{ $fmt($subscription['discount_amount'] ?? 0) }}</strong></div>
                    <div class="detail-row"><span class="fw-bold text-dark">Total Payable</span><strong>{{ $fmt($subscription['total_amount'] ?? 0) }}</strong></div>

                    <div class="transaction-actions">
                        @if(!empty($subscription['subscription_download_receipt_link']))
                            <a href="{{ $subscription['subscription_download_receipt_link'] }}" target="_blank" data-bs-toggle="tooltip" data-bs-title="Download Receipt"><i class="fa-solid fa-download"></i></a>
                        @endif
                        @if(!empty($subscription['id']))
                            <a href="{{ route('learners.transactions.edit', $subscription['id']) }}" data-bs-toggle="tooltip" data-bs-title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
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
            @else
                <div class="payment-card text-muted">No subscription activity recorded.</div>
            @endif
        </div>

        <div class="tab-pane fade" id="otherPayment">
            @php $otherPaymentSummary = $tabData['other_payment']['summary'] ?? []; @endphp
            <div class="metric-grid mb-3" style="grid-template-columns: repeat(3, 1fr);">
                <div class="payment-box"><span>Total Payment</span><strong>{{ $fmt($otherPaymentSummary['total_payment'] ?? 0) }}</strong></div>
                <div class="payment-box"><span>Received Amt.</span><strong class="received">{{ $fmt($otherPaymentSummary['received_amount'] ?? 0) }}</strong></div>
                <div class="payment-box"><span>Pending Amt.</span><strong class="pending">{{ $fmt($otherPaymentSummary['pending_amount'] ?? 0) }}</strong></div>
            </div>
            <div class="section-title">Payment Summary</div>
            @forelse($apiOtherPayments as $activity)
                @include('learner.partials.transaction-card', ['activity' => $activity, 'fmt' => $fmt, 'dateFmt' => $dateFmt])
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
                            $isPending = (float) ($transaction['pending_amount'] ?? 0) > 0;
                            $hasRefund = (float) ($transaction['extra_amount'] ?? 0) > 0 || $relatedActivities->contains(fn ($activity) => strtoupper((string) ($activity['payment_type'] ?? '')) === 'REFUND');
                            $filterTags = collect(['all'])
                                ->when(!$isPending, fn ($items) => $items->push('paid'))
                                ->when($isPending, fn ($items) => $items->push('pending'))
                                ->when($hasRefund, fn ($items) => $items->push('refund'))
                                ->implode(' ');
                        @endphp
                        @include('learner.partials.transaction-detail-card', [
                            'transaction' => $transaction,
                            'fmt' => $fmt,
                            'dateFmt' => $dateFmt,
                            'modeLabel' => $modeLabel,
                            'typeLabel' => $typeLabel,
                            'learner' => $learner,
                            'collapseId' => 'transaction-detail-'.$transaction['id'],
                            'cardAttrs' => 'data-transaction-card data-tags="'.$filterTags.'"',
                        ])
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
            <div class="section-title">Transaction Activity</div>
            @forelse($apiActivities as $activity)
                @php
                    $isDebit = strtolower((string) ($activity['dr_cr'] ?? '')) === 'dr';
                    $clearMessage = $activity['trxn_message'] ?: $typeLabel($activity['payment_type'] ?? ($activity['particular'] ?? ''));
                @endphp
                <div class="activity-list-card">
                    <span class="payment-icon {{ $isDebit ? 'debit' : '' }}">
                        <i class="fa-solid fa-arrow-{{ $isDebit ? 'up' : 'down' }}"></i>
                    </span>
                    <div>
                        <h6>{{ $clearMessage }}</h6>
                        <small>Received by {{ $activity['added_by_name'] ?? $activity['added_by'] ?? 'System' }} &bull; {{ $dateFmt($activity['transaction_date'] ?? '') }}</small>
                    </div>
                    <div class="amount {{ $isDebit ? 'debit' : '' }}">
                        {{ $fmt($activity['paid_amount'] ?? 0) }}
                        <small class="d-block text-muted fw-normal">{{ $isDebit ? 'Refunded' : 'Received' }}</small>
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
