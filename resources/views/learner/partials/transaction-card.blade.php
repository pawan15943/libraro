{{--
    Other Payment tab row matching mobile UI.
    Expects the API-formatted array shape from
    LearnerLifecycleService::formatOtherPayments().
--}}
<div class="other-payment-item-card">
    <div class="other-payment-icon-wrap">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="7" y1="7" x2="17" y2="17"></line>
            <polyline points="17 7 17 17 7 17"></polyline>
        </svg>
    </div>
    <div class="other-payment-info">
        <h6 class="other-payment-title">{{ strtoupper($activity['payment_type'] ?? 'Transaction') }}</h6>
        <p class="other-payment-meta">{{ $dateFmt($activity['paid_date'] ?? '') }}, Received by {{ $activity['added_by_name'] ?? 'Admin' }}</p>
    </div>
    <div class="other-payment-amount-wrap">
        <span class="other-payment-amount">₹{{ $fmt($activity['amount'] ?? 0) }}</span>
        @if(!empty($activity['payment_mode']))
            <span class="other-payment-mode">{{ strtoupper($activity['payment_mode']) }}</span>
        @endif
    </div>
    @if(!empty($activity['download_receipt_url']))
        <a href="{{ $activity['download_receipt_url'] }}" target="_blank" class="other-payment-download-btn" data-bs-toggle="tooltip" data-bs-title="Download Receipt">
            <i class="fa-solid fa-download"></i>
        </a>
    @else
        <span class="other-payment-download-btn disabled opacity-25">
            <i class="fa-solid fa-download"></i>
        </span>
    @endif
</div>
