@extends('layouts.library')
@section('content')

@php
    $fmt = function ($amount) {
        return number_format((float) ($amount ?? 0), 0, '.', ',');
    };
    $dateFmt = function ($date) {
        if (! $date) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($date)->format('d M Y');
        } catch (\Throwable $e) {
            return (string) $date;
        }
    };
    $dateTimeFmt = function ($date) {
        if (! $date) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($date)->format('d M Y, h:i A');
        } catch (\Throwable $e) {
            return (string) $date;
        }
    };
    $modeLabel = function ($mode) {
        return match ((string) $mode) {
            '1' => 'Online',
            '2' => 'Offline',
            '3' => 'Pay Later',
            default => $mode ?: '-',
        };
    };
    $typeLabel = function ($type) {
        return match (strtoupper((string) $type)) {
            'SUBSCRIPTION' => 'Seat Booking',
            'RENEW' => 'Renew Seat',
            'UPGRADE' => 'Plan Upgrade',
            'CHANGE PLAN', 'CHANGEPLAN' => 'Change Plan',
            'TOKEN MONEY' => 'Token Money',
            'MISCELLANEOUS' => 'Miscellaneous',
            'REFUND' => 'Refund',
            'SETTLED' => 'Settled',
            'RESTORE' => 'Restored',
            default => $type ?: 'Transaction',
        };
    };
    $activeStatus = (int) ($learner->status ?? 0) === 1 ? 'Active' : 'Inactive';
    $apiOverview = $tabData['overview'] ?? [];
    $apiSubscription = collect($tabData['subscription'] ?? [])->filter();
    $apiOtherPayments = collect($tabData['other_payment']['payments'] ?? []);
    $apiAllTransactions = collect($tabData['all_transaction'] ?? []);
    $apiActivities = collect($tabData['transaction_activity'] ?? [])->sortByDesc('id')->values();
@endphp

<style>
    .transaction-page { max-width: 1180px; margin: 0 auto 3rem; }
    .transaction-header { border: 1px solid #e7e9f0; border-radius: 12px; background: #fff; padding: 20px 22px; display: flex; align-items: center; gap: 18px; box-shadow: 0 2px 10px rgba(17, 24, 63, .04); }
    .transaction-header img { width: 64px; height: 64px; border-radius: 50%; object-fit: cover; background: #f2f2f2; border: 3px solid #eef1ff; }
    .transaction-header h4 { margin: 0; color: #07156f; font-size: 1.2rem; font-weight: 700; }
    .transaction-header p { margin: 3px 0 0; color: #6b7280; font-size: .85rem; }
    .transaction-status { margin-left: auto; background: #d9ffc9; color: #188000; border-radius: 20px; padding: 7px 16px; font-size: .82rem; font-weight: 700; }
    .transaction-status.inactive { background: #ffe7e7; color: #d60000; }
    .transaction-tabs { border-bottom: 1px solid #e7e9f0; margin: 20px 0 16px; display: flex; gap: 8px; }
    .transaction-tabs .nav-link { color: #6b7280; font-weight: 600; font-size: .92rem; padding: 10px 18px; border: 0; border-bottom: 2px solid transparent; background: transparent; transition: all .15s ease; }
    .transaction-tabs .nav-link:hover { color: #07156f; }
    .transaction-tabs .nav-link.active { color: #07156f; border-bottom-color: #07156f; background: transparent; }
    .metric-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px; }
    .metric, .payment-box { border: 1px solid #e7e9f0; border-radius: 12px; background: #fff; padding: 16px 18px; box-shadow: 0 2px 8px rgba(17, 24, 63, .03); }
    .metric span, .payment-box span { display: block; color: #6b7280; font-size: .8rem; margin-bottom: 6px; font-weight: 600; }
    .metric strong, .payment-box strong { font-size: 1.25rem; font-weight: 800; color: #1e2333; }
    .metric .received, .payment-box .received { color: #4dae3c; }
    .metric .pending, .payment-box .pending { color: #d60000; }
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
    .activity-mini-list { margin-top: 12px; }
    .activity-mini-list h6 { color: #838a99; font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; margin-bottom: 8px; }
    .activity-mini-item { display: flex; justify-content: space-between; gap: 10px; border-top: 1px dashed #e4e7ed; padding: 9px 0; font-size: .84rem; }
    .transaction-empty { border: 1px dashed #dfe3ea; border-radius: 12px; background: #fbfbfd; padding: 28px; text-align: center; color: #838a99; }

    /* Enhanced Activity Tab Styles */
    .activity-card-enhanced { border: 1px solid #e7e9f0; border-radius: 12px; background: #fff; padding: 16px 18px; display: flex; align-items: center; gap: 16px; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(17, 24, 63, .03); transition: all .2s ease; border-left: 4px solid #4dae3c; }
    .activity-card-enhanced.is-debit { border-left-color: #d60000; }
    .activity-card-enhanced:hover { box-shadow: 0 6px 18px rgba(17, 24, 63, .07); transform: translateY(-1px); }
    .activity-icon-badge { width: 44px; height: 44px; border-radius: 10px; display: grid; place-items: center; font-size: 1.1rem; flex: 0 0 auto; }
    .activity-icon-badge.credit { background: #eafbe7; color: #2e991c; }
    .activity-icon-badge.debit { background: #ffebeb; color: #d60000; }
    .activity-content { flex: 1; min-width: 0; }
    .activity-main-line { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 6px; }
    .activity-title { margin: 0; color: #171923; font-weight: 700; font-size: .96rem; }
    .activity-amount { font-size: 1.15rem; font-weight: 800; white-space: nowrap; }
    .activity-amount.credit { color: #2e991c; }
    .activity-amount.debit { color: #d60000; }
    .activity-meta-line { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; font-size: .82rem; color: #6b7280; }
    .activity-meta-item { display: inline-flex; align-items: center; }
    .activity-badge { padding: 3px 10px; border-radius: 12px; font-size: .75rem; font-weight: 600; display: inline-flex; align-items: center; }
    .activity-badge.mode-online { background: #eef2ff; color: #3b5bdb; }
    .activity-badge.mode-offline { background: #f3f4f6; color: #4b5563; }
    .activity-badge.mode-paylater { background: #fff7ed; color: #c2410c; }
    .activity-badge.mode-default { background: #f1f3f5; color: #495057; }
    .activity-receipt-link { display: inline-flex; align-items: center; color: #07156f; font-weight: 600; text-decoration: none; padding: 2px 8px; border-radius: 6px; background: #f0f2ff; transition: background .15s ease; }
    .activity-receipt-link:hover { background: #e0e4ff; color: #07156f; }

    @media (max-width: 768px) {
        .transaction-header { align-items: flex-start; flex-wrap: wrap; }
        .transaction-status { margin-left: 0; }
        .metric-grid { grid-template-columns: repeat(2, 1fr); }
        .payment-card { align-items: flex-start; }
        .transaction-history-main { grid-template-columns: 44px minmax(0, 1fr) auto 20px; padding: 12px; }
        .transaction-breakdown { grid-template-columns: repeat(2, 1fr); }
        .transaction-breakdown div:nth-child(2) { border-right: 0; }
        .transaction-breakdown div:nth-child(-n+2) { border-bottom: 1px solid #edf0f4; }
        .transaction-breakdown div:nth-child(odd) { padding-left: 0; }
        .transaction-breakdown div:nth-child(even) { padding-right: 0; }
        .transaction-body-row { grid-template-columns: 1fr; }
        .activity-card-enhanced { align-items: flex-start; }
        .activity-main-line { flex-direction: column; align-items: flex-start; gap: 4px; }
        .activity-amount { font-size: 1rem; }
    }
</style>

<div class="transaction-page">
    <div class="transaction-header mb-3">
        <img src="{{ $learner->profile_picture ? asset($learner->profile_picture) : asset('public/img/student_profile.jpeg') }}" alt="profile">
        <div>
            <h4>{{ $learner->name }}</h4>
            <p>{{ $learner->mobile }} &bull; {{ $learner->email ?: 'No email' }} &bull; Seat: {{ (!empty($learner->seat_no) && $learner->seat_no != 0) ? getSeatDisplayByMainNo($learner->seat_no) : 'General' }}</p>
        </div>
        <div class="transaction-status {{ $activeStatus === 'Active' ? '' : 'inactive' }}">
            {{ $activeStatus }}
        </div>
    </div>

    <ul class="nav nav-tabs transaction-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">Overview</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#subscription" type="button" role="tab">Subscription</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#otherPayment" type="button" role="tab">Other Payment</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#allTransactions" type="button" role="tab">All Transactions</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab">Activity</button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="overview">
            <div class="metric-grid">
                <div class="metric">
                    <span>Total Payment</span>
                    <strong>{{ $fmt($apiOverview['summary']['total_amount'] ?? $summary['total_amount']) }}</strong>
                </div>
                <div class="metric">
                    <span>Received Amt.</span>
                    <strong class="received">{{ $fmt($apiOverview['summary']['received_amount'] ?? $summary['received_amount']) }}</strong>
                </div>
                <div class="metric">
                    <span>Pending Amt.</span>
                    <strong class="pending">{{ $fmt($apiOverview['summary']['pending_amount'] ?? $summary['pending_amount']) }}</strong>
                </div>
                <div class="metric">
                    <span>Refund / Extra</span>
                    <strong class="refund">{{ $fmt($apiOverview['summary']['extra_amount'] ?? $summary['extra_amount']) }}</strong>
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
                    @php
                        $isRenewableForNextMonth = ($apiOverview['is_renew'] ?? true) && !($apiOverview['next_plan'] ?? 0);
                        $seatNoForRenew = $currentDetail?->seat_no ?? $learner->seat_no;
                        $endDateForRenew = $currentDetail?->plan_end_date ?? ($apiOverview['next_due_date'] ?? $summary['next_due_date']);
                        $detailIdForRenew = $currentDetail?->id;
                    @endphp
                    @if($isRenewableForNextMonth)
                        @can('has-permission', 'Renew Seat')
                            <a href="javascript:void(0);"
                               class="renew_extend payment-card-action ms-2"
                               data-seat_no="{{ $seatNoForRenew }}"
                               data-user="{{ $learner->id }}"
                               data-end_date="{{ $endDateForRenew }}"
                               data-learner_detail="{{ $detailIdForRenew }}"
                               data-bs-toggle="tooltip"
                               data-bs-placement="bottom"
                               data-bs-title="Renew Plan for Next Month">
                                <i class="fa-solid fa-credit-card text-primary"></i>
                            </a>
                        @endcan
                    @else
                        <span class="payment-card-action ms-2 opacity-50 cursor-not-allowed"
                              data-bs-toggle="tooltip"
                              data-bs-placement="bottom"
                              data-bs-title="Already Renewed for Next Month">
                            <i class="fa-solid fa-credit-card text-muted"></i>
                        </span>
                    @endif
                </div>
            @endif

            <div class="section-title">Last Subscription Transaction</div>
            @if($apiOverview['last_transactions'] ?? null)
                @include('learner.partials.transaction-detail-card', ['transaction' => $apiOverview['last_transactions'], 'fmt' => $fmt, 'dateFmt' => $dateFmt, 'modeLabel' => $modeLabel, 'typeLabel' => $typeLabel, 'learner' => $learner, 'collapseId' => 'overview-last-transaction'])
            @else
                <div class="payment-card text-muted">No transaction recorded.</div>
            @endif

            <div class="section-title">Recent Activity</div>
            @forelse(collect($apiOverview['recent_activities'] ?? [])->take(5) as $activity)
                @include('learner.partials.transaction-card', ['activity' => $activity, 'fmt' => $fmt, 'dateFmt' => $dateFmt])
            @empty
                <div class="payment-card text-muted">No recent activity recorded.</div>
            @endforelse
        </div>

        <div class="tab-pane fade" id="subscription">
            @php $subscription = $apiSubscription->first() ?? []; @endphp
            @if($subscription)
                <div class="detail-panel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="m-0 text-dark fw-bold">{{ $subscription['plan_name'] ?? 'Plan' }} ({{ $subscription['plan_type_name'] ?? 'Shift' }})</h5>
                        <span class="subscription-status-badge {{ strtolower((string) ($subscription['status_badge'] ?? 'active')) }}">
                            {{ $subscription['status_badge'] ?? 'Active' }}
                        </span>
                    </div>

                    <div class="detail-row"><span>Start Date</span><strong>{{ $dateFmt($subscription['plan_start_date'] ?? null) }}</strong></div>
                    <div class="detail-row"><span>End Date</span><strong>{{ $dateFmt($subscription['plan_end_date'] ?? null) }}</strong></div>
                    <div class="detail-row"><span>Seat Number</span><strong>{{ (!empty($subscription['seat_no'])) ? getSeatDisplayByMainNo($subscription['seat_no']) : 'General' }}</strong></div>
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

        {{-- All Transactions Tab: Full width clean view without filter bar or summary panel --}}
        <div class="tab-pane fade" id="allTransactions">
            <div class="section-title">All Transactions ({{ $apiAllTransactions->count() }})</div>
            @forelse($apiAllTransactions as $transaction)
                @include('learner.partials.transaction-detail-card', [
                    'transaction' => $transaction,
                    'fmt' => $fmt,
                    'dateFmt' => $dateFmt,
                    'modeLabel' => $modeLabel,
                    'typeLabel' => $typeLabel,
                    'learner' => $learner,
                    'collapseId' => 'transaction-detail-'.$transaction['id'],
                ])
            @empty
                <div class="transaction-empty">No transaction recorded.</div>
            @endforelse
        </div>

        {{-- Activity Tab: Enhanced modern UI with latest activity on top --}}
        <div class="tab-pane fade" id="activity">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="section-title my-0">Transaction Activity ({{ $apiActivities->count() }})</div>
                <small class="text-muted"><i class="fa-solid fa-clock-rotate-left me-1"></i>Latest First</small>
            </div>

            @forelse($apiActivities as $activity)
                @php
                    $isDebit = strtolower((string) ($activity['dr_cr'] ?? '')) === 'dr';
                    $clearMessage = $activity['trxn_message'] ?: $typeLabel($activity['payment_type'] ?? ($activity['particular'] ?? ''));
                    $mode = $activity['payment_mode'] ?? '';
                    $modeClass = match(strtolower($mode)) {
                        'online' => 'mode-online',
                        'offline' => 'mode-offline',
                        'pay later' => 'mode-paylater',
                        default => 'mode-default',
                    };
                    $modeIcon = match(strtolower($mode)) {
                        'online' => 'fa-globe',
                        'offline' => 'fa-money-bill-wave',
                        'pay later' => 'fa-business-time',
                        default => 'fa-credit-card',
                    };
                @endphp
                <div class="activity-card-enhanced {{ $isDebit ? 'is-debit' : 'is-credit' }}">
                    <div class="activity-icon-badge {{ $isDebit ? 'debit' : 'credit' }}">
                        <i class="fa-solid {{ $isDebit ? 'fa-arrow-up-right-from-square' : 'fa-arrow-down-left' }}"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-main-line">
                            <h6 class="activity-title">{{ $clearMessage }}</h6>
                            <div class="activity-amount {{ $isDebit ? 'debit' : 'credit' }}">
                                <span>{{ $isDebit ? '-' : '+' }} ₹{{ $fmt($activity['paid_amount'] ?? 0) }}</span>
                            </div>
                        </div>
                        <div class="activity-meta-line">
                            <span class="activity-meta-item">
                                <i class="fa-regular fa-calendar me-1"></i>{{ $dateFmt($activity['transaction_date'] ?? '') }}
                            </span>
                            <span class="activity-meta-item">
                                <i class="fa-regular fa-user me-1"></i>{{ $activity['added_by_name'] ?? $activity['added_by'] ?? 'Admin' }}
                            </span>
                            @if(!empty($mode))
                                <span class="activity-badge {{ $modeClass }}">
                                    <i class="fa-solid {{ $modeIcon }} me-1"></i>{{ $mode }}
                                </span>
                            @endif
                            @if(!empty($activity['transaction_id']) && $activity['transaction_id'] !== 'NA')
                                <span class="activity-meta-item text-muted">
                                    <i class="fa-solid fa-hashtag me-1"></i>{{ $activity['transaction_id'] }}
                                </span>
                            @endif
                            @if(!empty($activity['download_receipt_url']))
                                <a href="{{ $activity['download_receipt_url'] }}" target="_blank" class="activity-receipt-link" data-bs-toggle="tooltip" data-bs-title="Download Receipt">
                                    <i class="fa-solid fa-receipt me-1"></i>Receipt
                                </a>
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

@endsection
