{{--
    Rich collapsible transaction card matching mobile transaction screen UI.
    Shared by Overview tab's "Last Subscription Transaction" and All Transactions tab.
--}}
@php
    $relatedActivities = collect($transaction['activity'] ?? []);
    $isPending = (float) ($transaction['pending_amount'] ?? 0) > 0;
    $hasRefund = (float) ($transaction['extra_amount'] ?? 0) > 0 || $relatedActivities->contains(fn ($activity) => strtoupper((string) ($activity['payment_type'] ?? '')) === 'REFUND');
    $cardDate = $transaction['paid_date'] ?: ($transaction['transaction_date'] ?? '');
@endphp

<div class="transaction-history-card" {!! $cardAttrs ?? '' !!}>
    <button class="transaction-history-main" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="false" aria-controls="{{ $collapseId }}">
        <div class="other-payment-icon-wrap {{ $hasRefund ? 'is-refund' : '' }}">
            @if($hasRefund)
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="7" y1="17" x2="17" y2="7"></line>
                    <polyline points="7 7 17 7 17 17"></polyline>
                </svg>
            @else
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="7" y1="7" x2="17" y2="17"></line>
                    <polyline points="17 7 17 17 7 17"></polyline>
                </svg>
            @endif
        </div>
        <div class="transaction-history-title">
            <h6>{{ strtoupper($transaction['transaction_type'] ?? 'RE-NEW SEAT') }}</h6>
            <small>{{ $dateFmt($cardDate) }}, Received By {{ $transaction['added_by_name'] ?? 'Admin' }}</small>
        </div>
        <div class="transaction-history-amount-wrap">
            <span class="transaction-history-amount">₹{{ $fmt($transaction['total_paid_amount'] ?? 0) }}</span>
            @if(!empty($transaction['payment_mode']))
                <span class="transaction-history-mode">{{ strtoupper($transaction['payment_mode']) }}</span>
            @endif
        </div>
        <span class="transaction-chevron"><i class="fa-solid fa-chevron-down"></i></span>
    </button>

    <div class="collapse" id="{{ $collapseId }}">
        <div class="transaction-history-body">
            {{-- 4 Column Breakdown Grid --}}
            <div class="trxn-breakdown-grid">
                <div class="trxn-breakdown-col">
                    <span class="trxn-breakdown-label">Plan Price</span>
                    <strong class="trxn-breakdown-value">₹{{ $fmt($transaction['plan_price'] ?? 0) }}</strong>
                </div>
                <div class="trxn-breakdown-col">
                    <span class="trxn-breakdown-label">Locker</span>
                    <strong class="trxn-breakdown-value">₹{{ $fmt($transaction['locker_amount'] ?? 0) }}</strong>
                </div>
                <div class="trxn-breakdown-col">
                    <span class="trxn-breakdown-label">Discount</span>
                    <strong class="trxn-breakdown-value">₹{{ $fmt($transaction['discount_amount'] ?? 0) }}</strong>
                </div>
                <div class="trxn-breakdown-col">
                    <span class="trxn-breakdown-label">Total Amt</span>
                    <strong class="trxn-breakdown-value">₹{{ $fmt($transaction['total_amount'] ?? 0) }}</strong>
                </div>
            </div>

            {{-- Activities / Transaction Row --}}
            @if($relatedActivities->isNotEmpty())
                @foreach($relatedActivities as $activity)
                    <div class="trxn-activity-item">
                        <div class="trxn-activity-left">
                            <span class="trxn-ref-num">Trxn : #{{ $activity['transaction_id'] ?: ($transaction['transaction_ref'] ?? $activity['id']) }}</span>
                            <p class="trxn-desc">{{ $activity['trxn_message'] ?: ($typeLabel($activity['payment_type'] ?? '') . ' (' . ($activity['payment_mode'] ?? '') . ')') }}</p>
                        </div>
                        <div class="trxn-activity-right">
                            <span class="trxn-activity-amount {{ strtolower((string)($activity['dr_cr'] ?? '')) === 'dr' ? 'debit' : '' }}">
                                ₹{{ $fmt($activity['paid_amount'] ?? 0) }}
                            </span>
                            @if(!empty($activity['updated_date']) || !empty($activity['transaction_date']))
                                <div class="trxn-modify-meta">
                                    Modify on : {{ $dateFmt($activity['updated_date'] ?: $activity['transaction_date']) }}<br>
                                    By {{ $activity['updated_by_name'] ?: ($activity['added_by_name'] ?? 'Admin') }}
                                </div>
                            @endif
                            <div class="trxn-activity-actions">
                                @if(!empty($activity['id']))
                                    <form method="POST" action="{{ route('learners.transactions.activity.destroy', $activity['id']) }}" onsubmit="return confirm('Delete this activity?');" class="d-inline m-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="trxn-mini-action-btn delete" data-bs-toggle="tooltip" data-bs-title="Delete Activity">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="trxn-activity-item">
                    <div class="trxn-activity-left">
                        <span class="trxn-ref-num">Trxn : #{{ $transaction['transaction_ref'] ?? $transaction['id'] }}</span>
                        <p class="trxn-desc">{{ $transaction['transaction_type'] ?? 'Transaction' }} ({{ $transaction['payment_mode'] ?? 'Online' }})</p>
                    </div>
                    <div class="trxn-activity-right">
                        <span class="trxn-activity-amount">
                            ₹{{ $fmt($transaction['total_paid_amount'] ?? 0) }}
                        </span>
                        @if(!empty($transaction['updated_date']) || !empty($transaction['transaction_date']))
                            <div class="trxn-modify-meta">
                                Modify on : {{ $dateFmt($transaction['updated_date'] ?: $transaction['transaction_date']) }}<br>
                                By {{ $transaction['updated_by_name'] ?: ($transaction['added_by_name'] ?? 'Admin') }}
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <hr class="trxn-divider">

            {{-- 3 Column Footer Breakdown --}}
            <div class="trxn-footer-breakdown-grid">
                <div class="trxn-footer-col">
                    <span class="trxn-footer-label">Final Payable Amt</span>
                    <strong class="trxn-footer-value">₹{{ $fmt($transaction['final_payable_amount'] ?? 0) }}</strong>
                </div>
                <div class="trxn-footer-col">
                    <span class="trxn-footer-label">Paid Amt.</span>
                    <strong class="trxn-footer-value paid">₹{{ $fmt($transaction['total_paid_amount'] ?? 0) }}</strong>
                </div>
                <div class="trxn-footer-col">
                    <span class="trxn-footer-label">Pending / Extra Amt.</span>
                    <strong class="trxn-footer-value {{ $isPending ? 'pending' : 'extra' }}">
                        ₹{{ $isPending ? $fmt($transaction['pending_amount'] ?? 0) : $fmt($transaction['extra_amount'] ?? 0) }}
                    </strong>
                </div>
            </div>

            <hr class="trxn-divider">

            {{-- Centered Bottom Action Buttons --}}
            <div class="trxn-card-bottom-actions">
                @if(!empty($transaction['subscription_download_receipt_link']))
                    <a href="{{ $transaction['subscription_download_receipt_link'] }}" target="_blank" class="trxn-bottom-action-btn" data-bs-toggle="tooltip" data-bs-title="Download Receipt">
                        <i class="fa-solid fa-download"></i>
                    </a>
                @else
                    <span class="trxn-bottom-action-btn disabled opacity-25" data-bs-toggle="tooltip" data-bs-title="Receipt unavailable">
                        <i class="fa-solid fa-download"></i>
                    </span>
                @endif
                @if(!empty($transaction['id']))
                    <a href="{{ route('learners.transactions.edit', $transaction['id']) }}" class="trxn-bottom-action-btn" data-bs-toggle="tooltip" data-bs-title="Edit Transaction">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                @endif
                @if(!empty($transaction['delete_url']))
                    <form method="POST" action="{{ $transaction['delete_url'] }}" onsubmit="return confirm('Delete this renew transaction?');" class="d-inline m-0">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="learner_id" value="{{ $learner->id }}">
                        <button type="submit" class="trxn-bottom-action-btn" data-bs-toggle="tooltip" data-bs-title="Delete Transaction">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
