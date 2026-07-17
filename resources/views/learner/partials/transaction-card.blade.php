{{--
    Other Payment tab row. Expects the API-formatted array shape from
    LearnerLifecycleService::formatOtherPayments() (payment_type, amount already
    money-formatted, payment_mode label, paid_date, added_by_name, download_receipt_url).
--}}
<div class="payment-card">
    <div class="payment-icon">
        <i class="fa-solid fa-arrow-down"></i>
    </div>
    <div>
        <h6>{{ $activity['payment_type'] ?? 'Transaction' }}</h6>
        <small>{{ $dateFmt($activity['paid_date'] ?? '') }}, Received by {{ $activity['added_by_name'] ?? '' }}</small>
    </div>
    <div class="amount">
        {{ $fmt($activity['amount'] ?? 0) }}
        <small class="d-block text-muted">{{ $activity['payment_mode'] ?? '' }}</small>
    </div>
    @if(!empty($activity['download_receipt_url']))
        <a href="{{ $activity['download_receipt_url'] }}" target="_blank" class="payment-card-action" data-bs-toggle="tooltip" data-bs-title="Download Receipt"><i class="fa-solid fa-download"></i></a>
    @endif
</div>
