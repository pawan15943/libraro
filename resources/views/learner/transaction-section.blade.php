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
    $formatDateDDMMYYYY = function ($date) {
        if (! $date) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($date)->format('d-m-Y');
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
    $overviewReceivedAmt = $apiOverview['total_amount_received'] ?? ($summary['received_amount'] ?? 0);
    $overviewTotalAmt = $apiOverview['total_amount'] ?? ($summary['total_amount'] ?? 0);
    $overviewPendingAmt = $apiOverview['pending_amount'] ?? ($summary['pending_amount'] ?? 0);
    $overviewExtraAmt = $apiOverview['extra_amount'] ?? ($summary['extra_amount'] ?? 0);
    $overviewRefundAmt = $apiOverview['refund_amount'] ?? ($summary['refund_amount'] ?? 0);
@endphp

<link rel="stylesheet" href="{{ asset('public/css/learner-transaction.css') }}?v={{ time() }}" />

<div class="transaction-page">
    <div class="transaction-header mb-3">
        <img src="{{ $learner->profile_picture ? asset($learner->profile_picture) : asset('public/img/student_profile.jpeg') }}" alt="profile">
        <div>
            <h4>{{ $learner->name }}</h4>
            <p>{{ $learner->mobile }} &bull; {{ $learner->email ?: 'No email' }} &bull; Seat: {{ (!empty($learner->seat_no) && $learner->seat_no != 0) ? getSeatDisplayShortFloorName($learner->seat_no) : 'General' }}</p>
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
            {{-- Unified Overview Hero Card (Matching Mobile and Desktop exact same) --}}
            <div class="overview-hero-card">
                <div class="overview-hero-header">
                    <h5 class="overview-hero-title">Total Amount Received</h5>
                    <div class="overview-hero-amount">₹{{ $fmt($overviewReceivedAmt) }}</div>
                    <p class="overview-hero-subtitle">
                        This is the final amount received from this learner, including all pending and refunded amounts.
                    </p>
                </div>
                <div class="overview-breakdown-row">
                    <div class="breakdown-col">
                        <span class="breakdown-label label-total">Total Amt.</span>
                        <span class="breakdown-value">₹{{ $fmt($overviewTotalAmt) }}</span>
                    </div>
                    <div class="breakdown-col">
                        <span class="breakdown-label label-pending">Pending Amt.</span>
                        <span class="breakdown-value">₹{{ $fmt($overviewPendingAmt) }}</span>
                    </div>
                    <div class="breakdown-col">
                        <span class="breakdown-label label-extra">Extra Amt.</span>
                        <span class="breakdown-value">₹{{ $fmt($overviewExtraAmt) }}</span>
                    </div>
                    <div class="breakdown-col">
                        <span class="breakdown-label label-refund">Refund Amt.</span>
                        <span class="breakdown-value">₹{{ $fmt($overviewRefundAmt) }}</span>
                    </div>
                </div>
            </div>

            @php
                $rawNextDueDate = $apiOverview['next_due_date'] ?? ($summary['next_due_date'] ?? null);
            @endphp
            @if($rawNextDueDate)
                <div class="section-title">Next Payment Due</div>
                <div class="payment-card">
                    <div class="payment-icon"><i class="fa-solid fa-calendar-days"></i></div>
                    <div>
                        <h6>{{ $formatDateDDMMYYYY($rawNextDueDate) }}</h6>
                        <small>(Subscription + Carryforward) - Extra Amt.</small>
                    </div>
                    <div class="amount">{{ $fmt($apiOverview['next_due_amount'] ?? $summary['pending_amount']) }}</div>
                    @php
                        $isRenewableForNextMonth = ($apiOverview['is_renew'] ?? true) && !($apiOverview['next_plan'] ?? 0);
                        $seatNoForRenew = $currentDetail?->seat_no ?? $learner->seat_no;
                        $endDateForRenew = $currentDetail?->plan_end_date ?? $rawNextDueDate;
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

        </div>

        <div class="tab-pane fade" id="subscription">
            @php $subscription = $apiSubscription->first() ?? []; @endphp
            @if($subscription)
                {{-- 3 Metric Summary Cards matching mobile --}}
                <div class="subscription-metric-grid">
                    <div class="subscription-metric-card">
                        <span class="subscription-metric-label">Total Payment</span>
                        <span class="subscription-metric-value">₹{{ $fmt($subscription['total_amount'] ?? ($subscription['final_payable_amount'] ?? 0)) }}</span>
                    </div>
                    <div class="subscription-metric-card">
                        <span class="subscription-metric-label">Received Amt.</span>
                        <span class="subscription-metric-value received">₹{{ $fmt($subscription['total_paid_amount'] ?? 0) }}</span>
                    </div>
                    <div class="subscription-metric-card">
                        <span class="subscription-metric-label">Pending Amt.</span>
                        <span class="subscription-metric-value pending">₹{{ $fmt($subscription['pending_amount'] ?? 0) }}</span>
                    </div>
                </div>

                {{-- Subscription Summary Card matching mobile --}}
                <div class="subscription-summary-panel">
                    <div class="subscription-summary-header">
                        <h5 class="subscription-summary-title">Subscription Summery</h5>
                        @php
                            $isFrozen = !empty($subscription['frozen_status']) || strtolower((string)($subscription['status_badge'] ?? '')) === 'frozen';
                        @endphp
                        @if($isFrozen && !empty($subscription['freeze_date']))
                            <span class="subscription-status-badge frozen">
                                Frozen on: {{ \Carbon\Carbon::parse($subscription['freeze_date'])->format('d-M-Y') }}
                            </span>
                        @else
                            <span class="subscription-status-badge {{ strtolower((string) ($subscription['status_badge'] ?? 'active')) }}">
                                {{ $subscription['status_badge'] ?? 'Active' }}
                            </span>
                        @endif
                    </div>

                    @php
                        $startDate = $dateFmt($subscription['plan_start_date'] ?? null);
                        $endDate = $isFrozen ? 'Frozen' : $dateFmt($subscription['plan_end_date'] ?? null);
                        $shiftName = $subscription['plan_type_name'] ?? '';

                        $hasLocker = ($subscription['locker'] ?? '') === 'Yes' || ((float) ($subscription['locker_amount'] ?? 0)) > 0 || !empty($subscription['locker_no']);
                        $lockerNo = $subscription['locker_no'] ?? null;
                        $lockerPrice = $fmt($subscription['locker_amount'] ?? 0);
                    @endphp

                    <div class="subscription-row">
                        <span class="sub-label">Plan</span>
                        <span class="sub-value">{{ $subscription['plan_name'] ?? 'Plan' }}</span>
                    </div>

                    <div class="subscription-row">
                        <span class="sub-label">Duration</span>
                        <span class="sub-value">{{ $startDate }} – {{ $endDate }}{{ $shiftName ? ' (' . $shiftName . ')' : '' }}</span>
                    </div>

                    <div class="subscription-row">
                        <span class="sub-label">Locker</span>
                        <span class="sub-value">
                            @if($hasLocker)
                                Yes : Locker No. {{ $lockerNo ?: '-' }} | Price : ₹{{ $lockerPrice }}
                            @else
                                No | Price : ₹{{ $lockerPrice }}
                            @endif
                        </span>
                    </div>

                    <div class="subscription-row">
                        <span class="sub-label">Plan Price</span>
                        <span class="sub-value">₹{{ $fmt($subscription['plan_price'] ?? 0) }}</span>
                    </div>

                    <div class="subscription-row">
                        <span class="sub-label">Discount (in Amount)</span>
                        <span class="sub-value">₹{{ $fmt($subscription['discount_amount'] ?? 0) }}</span>
                    </div>

                    <div class="subscription-row total-row">
                        <span class="sub-label">Total Payable</span>
                        <span class="sub-value">₹{{ $fmt($subscription['total_amount'] ?? ($subscription['final_payable_amount'] ?? 0)) }}</span>
                    </div>

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
            <div class="subscription-metric-grid">
                <div class="subscription-metric-card">
                    <span class="subscription-metric-label">Total Payment</span>
                    <span class="subscription-metric-value">₹{{ $fmt($otherPaymentSummary['total_payment'] ?? 0) }}</span>
                </div>
                <div class="subscription-metric-card">
                    <span class="subscription-metric-label">Received Amt.</span>
                    <span class="subscription-metric-value received">₹{{ $fmt($otherPaymentSummary['received_amount'] ?? 0) }}</span>
                </div>
                <div class="subscription-metric-card">
                    <span class="subscription-metric-label">Pending Amt.</span>
                    <span class="subscription-metric-value pending">₹{{ $fmt($otherPaymentSummary['pending_amount'] ?? 0) }}</span>
                </div>
            </div>

            <h5 class="other-payment-heading">Payment Summary</h5>

            @forelse($apiOtherPayments as $activity)
                @include('learner.partials.transaction-card', ['activity' => $activity, 'fmt' => $fmt, 'dateFmt' => $dateFmt])
            @empty
                <div class="transaction-empty">No other payment recorded.</div>
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
