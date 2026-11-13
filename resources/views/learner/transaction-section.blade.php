@extends('layouts.library')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-txn.css') }}?v={{ @filemtime(public_path('css/lb-txn.css')) }}" />
@endpush

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
    $modeTone = fn ($mode) => match (strtolower((string) $mode)) {
        'online' => 'online',
        'offline' => 'offline',
        'pay later' => 'later',
        default => 'none',
    };
    $isActive = (int) ($learner->status ?? 0) === 1;
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

    $txName = trim((string) ($learner->name ?? 'Learner'));
    $txInitials = collect(explode(' ', $txName))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: 'L';
    $txPhoto = ($learner->profile_picture && file_exists(public_path($learner->profile_picture))) ? asset($learner->profile_picture) : null;
    $txSeat = (!empty($learner->seat_no) && $learner->seat_no != 0) ? getSeatDisplayShortFloorName($learner->seat_no) : null;
    $txBack = url()->previous() !== url()->current() ? url()->previous() : route('learners');
    $txPlan = optional($currentDetail?->plan)->name;
    $txShift = optional($currentDetail?->planType)->name;
@endphp

<div class="ltx">

    {{-- Member header --}}
    <header class="ltx-head ltx-head--plain">
        <div class="ltx-head-top">
            <a href="{{ $txBack }}" class="ltx-back" title="Back" aria-label="Back"><x-lb-icon name="arrow-left" /></a>
            <div class="ltx-crumb">Members<x-lb-icon name="chevron-right" /><span>Transactions</span></div>
        </div>
        {{-- same member card as the operation pages --}}
        @include('learner.partials.member-card', [
            'mcLearner' => $learner,
            'mcDetail' => $currentDetail ?? $learner,
            'mcChips' => array_values(array_filter([
                $learner->mobile ? ['phone', $learner->mobile, 'tel:' . $learner->mobile] : null,
                $learner->email ? ['mail', $learner->email, 'mailto:' . $learner->email] : null,
            ])),
        ])
    </header>

    {{-- Tabs --}}
    <div class="ltx-tabs-wrap">
        <ul class="ltx-tabs" role="tablist">
            <li role="presentation"><button class="ltx-tab active" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab" aria-selected="true"><x-lb-icon name="chart-pie" />Overview</button></li>
            <li role="presentation"><button class="ltx-tab" data-bs-toggle="tab" data-bs-target="#subscription" type="button" role="tab" aria-selected="false"><x-lb-icon name="crown" />Subscription</button></li>
            <li role="presentation"><button class="ltx-tab" data-bs-toggle="tab" data-bs-target="#otherPayment" type="button" role="tab" aria-selected="false"><x-lb-icon name="receipt" />Other payments</button></li>
            <li role="presentation"><button class="ltx-tab" data-bs-toggle="tab" data-bs-target="#allTransactions" type="button" role="tab" aria-selected="false"><x-lb-icon name="list-checks" />All<span class="ltx-tab-n">{{ $apiAllTransactions->count() }}</span></button></li>
            <li role="presentation"><button class="ltx-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab" aria-selected="false"><x-lb-icon name="activity" />Activity<span class="ltx-tab-n">{{ $apiActivities->count() }}</span></button></li>
        </ul>
    </div>

    <div class="tab-content">

        {{-- Overview --}}
        <div class="tab-pane fade show active" id="overview" role="tabpanel">
            <section class="ltx-hero">
                <span class="ltx-hero-k">Total amount received</span>
                <strong class="ltx-hero-v">₹{{ $fmt($overviewReceivedAmt) }}</strong>
                <p class="ltx-hero-s">Final amount received from this learner, after pending and refunded amounts.</p>
                <div class="ltx-stats">
                    <div class="ltx-stat"><span><i class="ltx-dot is-acc"></i>Total</span><b>₹{{ $fmt($overviewTotalAmt) }}</b></div>
                    <div class="ltx-stat"><span><i class="ltx-dot is-bad"></i>Pending</span><b>₹{{ $fmt($overviewPendingAmt) }}</b></div>
                    <div class="ltx-stat"><span><i class="ltx-dot is-ok"></i>Extra</span><b>₹{{ $fmt($overviewExtraAmt) }}</b></div>
                    <div class="ltx-stat"><span><i class="ltx-dot is-warn"></i>Refund</span><b>₹{{ $fmt($overviewRefundAmt) }}</b></div>
                </div>
            </section>

            @php
                $rawNextDueDate = $apiOverview['next_due_date'] ?? ($summary['next_due_date'] ?? null);
            @endphp
            @if($rawNextDueDate)
                @php $isRenewableForNextMonth = ($apiOverview['is_renew'] ?? true) && !($apiOverview['next_plan'] ?? 0); @endphp
                <h2 class="ltx-h">Next payment due</h2>
                <section class="ltx-due">
                    <span class="ltx-due-ic"><x-lb-icon name="calendar-clock" /></span>
                    <div class="ltx-due-t">
                        <b>{{ $dateFmt($rawNextDueDate) }}</b>
                        <small>(Subscription + carry forward) − extra amount</small>
                    </div>
                    <strong class="ltx-due-v">₹{{ $fmt($apiOverview['next_due_amount'] ?? $summary['pending_amount']) }}</strong>
                    @if($isRenewableForNextMonth)
                        @can('has-permission', 'Renew Seat')
                            <a href="{{ route('learner.renew.plan', $learner->id) }}" class="ltx-btn ltx-btn--pri ltx-due-btn"><x-lb-icon name="refresh-cw" /><span>Renew</span></a>
                        @endcan
                    @else
                        <span class="ltx-due-done"><x-lb-icon name="circle-check" />Renewed</span>
                    @endif
                </section>
            @endif

            <h2 class="ltx-h">Last subscription transaction</h2>
            @if($apiOverview['last_transactions'] ?? null)
                @include('learner.partials.transaction-detail-card', ['transaction' => $apiOverview['last_transactions'], 'fmt' => $fmt, 'dateFmt' => $dateFmt, 'modeLabel' => $modeLabel, 'typeLabel' => $typeLabel, 'modeTone' => $modeTone, 'learner' => $learner, 'collapseId' => 'overview-last-transaction', 'isOpen' => true])
            @else
                <div class="ltx-empty"><x-lb-icon name="inbox" />No transaction recorded.</div>
            @endif
        </div>

        {{-- Subscription --}}
        <div class="tab-pane fade" id="subscription" role="tabpanel">
            @php $subscription = $apiSubscription->first() ?? []; @endphp
            @if($subscription)
                <div class="ltx-metrics">
                    <div class="ltx-metric"><span>Total payment</span><b>₹{{ $fmt($subscription['total_amount'] ?? ($subscription['final_payable_amount'] ?? 0)) }}</b></div>
                    <div class="ltx-metric is-ok"><span>Received</span><b>₹{{ $fmt($subscription['total_paid_amount'] ?? 0) }}</b></div>
                    <div class="ltx-metric is-bad"><span>Pending</span><b>₹{{ $fmt($subscription['pending_amount'] ?? 0) }}</b></div>
                </div>

                @php
                    $isFrozen = !empty($subscription['frozen_status']) || strtolower((string)($subscription['status_badge'] ?? '')) === 'frozen';
                    $startDate = $dateFmt($subscription['plan_start_date'] ?? null);
                    $endDate = $isFrozen ? 'Frozen' : $dateFmt($subscription['plan_end_date'] ?? null);
                    $shiftName = $subscription['plan_type_name'] ?? '';
                    $hasLocker = ($subscription['locker'] ?? '') === 'Yes' || ((float) ($subscription['locker_amount'] ?? 0)) > 0 || !empty($subscription['locker_no']);
                    $lockerNo = $subscription['locker_no'] ?? null;
                    $badge = strtolower((string) ($subscription['status_badge'] ?? 'active'));
                    $badgeTone = $isFrozen ? 'info' : (in_array($badge, ['active', 'paid'], true) ? 'ok' : (in_array($badge, ['expired', 'inactive', 'unpaid'], true) ? 'bad' : 'warn'));
                @endphp

                <section class="ltx-card">
                    <div class="ltx-card-h">
                        <h3>Subscription summary</h3>
                        <span class="ltx-pill is-{{ $badgeTone }}">
                            @if($isFrozen && !empty($subscription['freeze_date']))
                                <x-lb-icon name="snowflake" />Frozen on {{ \Carbon\Carbon::parse($subscription['freeze_date'])->format('d M Y') }}
                            @else
                                {{ $subscription['status_badge'] ?? 'Active' }}
                            @endif
                        </span>
                    </div>
                    <dl class="ltx-rows">
                        <div><dt>Plan</dt><dd>{{ $subscription['plan_name'] ?? 'Plan' }}</dd></div>
                        <div><dt>Duration</dt><dd>{{ $startDate }} – {{ $endDate }}@if($shiftName)<small>{{ $shiftName }}</small>@endif</dd></div>
                        <div><dt>Locker</dt><dd>@if($hasLocker)No. {{ $lockerNo ?: '-' }} · ₹{{ $fmt($subscription['locker_amount'] ?? 0) }}@else No @endif</dd></div>
                        <div><dt>Plan price</dt><dd>₹{{ $fmt($subscription['plan_price'] ?? 0) }}</dd></div>
                        <div><dt>Discount</dt><dd>− ₹{{ $fmt($subscription['discount_amount'] ?? 0) }}</dd></div>
                        <div class="is-total"><dt>Total payable</dt><dd>₹{{ $fmt($subscription['total_amount'] ?? ($subscription['final_payable_amount'] ?? 0)) }}</dd></div>
                    </dl>

                    @if(!empty($subscription['subscription_download_receipt_link']) || !empty($subscription['id']) || !empty($subscription['delete_url']))
                    <div class="ltx-actions">
                        @if(!empty($subscription['subscription_download_receipt_link']))
                            <a href="{{ $subscription['subscription_download_receipt_link'] }}" target="_blank" class="ltx-btn"><x-lb-icon name="download" /><span>Receipt</span></a>
                        @endif
                        @if(!empty($subscription['id']))
                            <a href="{{ route('learners.transactions.edit', $subscription['id']) }}" class="ltx-btn"><x-lb-icon name="square-pen" /><span>Edit</span></a>
                        @endif
                        @if(!empty($subscription['delete_url']))
                            <form method="POST" action="{{ $subscription['delete_url'] }}" onsubmit="return confirm('Delete this renew transaction?');">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="learner_id" value="{{ $learner->id }}">
                                <button type="submit" class="ltx-btn ltx-btn--bad"><x-lb-icon name="trash" /><span>Delete</span></button>
                            </form>
                        @endif
                    </div>
                    @endif
                </section>
            @else
                <div class="ltx-empty"><x-lb-icon name="inbox" />No subscription activity recorded.</div>
            @endif
        </div>

        {{-- Other payments --}}
        <div class="tab-pane fade" id="otherPayment" role="tabpanel">
            @php $otherPaymentSummary = $tabData['other_payment']['summary'] ?? []; @endphp
            <div class="ltx-metrics">
                <div class="ltx-metric"><span>Total payment</span><b>₹{{ $fmt($otherPaymentSummary['total_payment'] ?? 0) }}</b></div>
                <div class="ltx-metric is-ok"><span>Received</span><b>₹{{ $fmt($otherPaymentSummary['received_amount'] ?? 0) }}</b></div>
                <div class="ltx-metric is-bad"><span>Pending</span><b>₹{{ $fmt($otherPaymentSummary['pending_amount'] ?? 0) }}</b></div>
            </div>

            <h2 class="ltx-h">Payments<span>{{ $apiOtherPayments->count() }}</span></h2>
            @if($apiOtherPayments->isNotEmpty())
                <div class="ltx-list">
                    @foreach($apiOtherPayments as $activity)
                        @include('learner.partials.transaction-card', ['activity' => $activity, 'fmt' => $fmt, 'dateFmt' => $dateFmt, 'modeTone' => $modeTone])
                    @endforeach
                </div>
            @else
                <div class="ltx-empty"><x-lb-icon name="inbox" />No other payment recorded.</div>
            @endif
        </div>

        {{-- All transactions --}}
        <div class="tab-pane fade" id="allTransactions" role="tabpanel">
            <h2 class="ltx-h">All transactions<span>{{ $apiAllTransactions->count() }}</span></h2>
            @forelse($apiAllTransactions as $transaction)
                @include('learner.partials.transaction-detail-card', [
                    'transaction' => $transaction,
                    'fmt' => $fmt,
                    'dateFmt' => $dateFmt,
                    'modeLabel' => $modeLabel,
                    'typeLabel' => $typeLabel,
                    'modeTone' => $modeTone,
                    'learner' => $learner,
                    'collapseId' => 'transaction-detail-'.$transaction['id'],
                    'isOpen' => $loop->first,
                ])
            @empty
                <div class="ltx-empty"><x-lb-icon name="inbox" />No transaction recorded.</div>
            @endforelse
        </div>

        {{-- Activity --}}
        <div class="tab-pane fade" id="activity" role="tabpanel">
            <h2 class="ltx-h">Transaction activity<span>{{ $apiActivities->count() }}</span><em>Latest first</em></h2>

            @if($apiActivities->isNotEmpty())
                <div class="ltx-list">
                    @foreach($apiActivities as $activity)
                        @php
                            $isDebit = strtolower((string) ($activity['dr_cr'] ?? '')) === 'dr';
                            $clearMessage = $activity['trxn_message'] ?: $typeLabel($activity['payment_type'] ?? ($activity['particular'] ?? ''));
                            $mode = $activity['payment_mode'] ?? '';
                            $trxnRef = $activity['transaction_id'] ?: ($activity['id'] ?? '');
                        @endphp
                        <div class="ltx-item">
                            <span class="ltx-item-ic {{ $isDebit ? 'is-bad' : 'is-ok' }}"><x-lb-icon name="arrow-up-right" /></span>
                            <div class="ltx-item-t">
                                <b>{{ $clearMessage }}</b>
                                <small>
                                    <span>{{ $dateFmt($activity['transaction_date'] ?? '') }}</span>
                                    <span>{{ $activity['added_by_name'] ?? $activity['added_by'] ?? 'Admin' }}</span>
                                    @if(!empty($trxnRef) && $trxnRef !== 'NA')
                                        <span class="ltx-ref">#{{ ltrim($trxnRef, '#') }}</span>
                                    @endif
                                </small>
                            </div>
                            <div class="ltx-item-r">
                                <strong class="{{ $isDebit ? 'is-bad' : 'is-ok' }}">{{ $isDebit ? '−' : '+' }}₹{{ $fmt($activity['paid_amount'] ?? 0) }}</strong>
                                @if(!empty($mode))
                                    <span class="ltx-mode is-{{ $modeTone($mode) }}">{{ $mode }}</span>
                                @endif
                            </div>
                            @if(!empty($activity['download_receipt_url']))
                                <a href="{{ $activity['download_receipt_url'] }}" target="_blank" class="ltx-icbtn" title="Download receipt" aria-label="Download receipt"><x-lb-icon name="download" /></a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="ltx-empty"><x-lb-icon name="inbox" />No activity recorded.</div>
            @endif
        </div>
    </div>
</div>

@endsection
