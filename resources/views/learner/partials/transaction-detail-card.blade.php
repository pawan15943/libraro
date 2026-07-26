{{--
    Rich collapsible transaction card - shared by the Overview tab's "Last Subscription
    Transaction" and the All Transactions tab, since both render the exact same
    formatSubscriptionTransaction() shaped array (see LearnerLifecycleService::transactionTabs()).
    Expects: $transaction (array), $fmt, $dateFmt, $modeLabel, $typeLabel (closures),
    $learner (for the delete form's hidden learner_id), $collapseId (unique DOM id).
    Optional: $cardAttrs (raw HTML attribute string, e.g. data-transaction-card data-tags="...").
--}}
@php
    $relatedActivities = collect($transaction['activity'] ?? []);
    $isPending = (float) ($transaction['pending_amount'] ?? 0) > 0;
    $hasRefund = (float) ($transaction['extra_amount'] ?? 0) > 0 || $relatedActivities->contains(fn ($activity) => strtoupper((string) ($activity['payment_type'] ?? '')) === 'REFUND');
    $cardDate = $transaction['paid_date'] ?: ($transaction['transaction_date'] ?? '');
@endphp

<div class="transaction-history-card" {!! $cardAttrs ?? '' !!}>
    <button class="transaction-history-main" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="false" aria-controls="{{ $collapseId }}">
        <span class="payment-icon {{ $hasRefund ? 'debit' : '' }}">
            <i class="fa-solid fa-arrow-{{ $hasRefund ? 'up' : 'down' }}"></i>
        </span>
        <span class="transaction-history-title">
            <h6>{{ $transaction['transaction_type'] ?? 'NA' }}</h6>
            <small>{{ $dateFmt($cardDate) }}, Received By {{ $transaction['added_by_name'] ?? '' }}</small>
        </span>
        <span class="transaction-history-amount">
            {{ $fmt($transaction['total_paid_amount'] ?? 0) }}
            <small class="d-block text-muted">{{ $transaction['payment_mode'] ?? '' }}</small>
        </span>
        <span class="transaction-chevron"><i class="fa-solid fa-chevron-down"></i></span>
    </button>

    <div class="collapse" id="{{ $collapseId }}">
        <div class="transaction-history-body">
            @if(!empty($transaction['transaction_ref']))
                <div class="detail-row"><span>Txn No.</span><strong>{{ $transaction['transaction_ref'] }}</strong></div>
            @endif
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
                            <span>{{ $activity['trxn_message'] ?? $typeLabel($activity['payment_type'] ?? '') }}</span>
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
