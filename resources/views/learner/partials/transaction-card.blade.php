{{--
    Other Payments tab row.
    Expects the API-formatted array shape from
    LearnerLifecycleService::formatOtherPayments().
--}}
@php
    $opType = strtoupper((string) ($activity['payment_type'] ?? ''));
    $opOut = $opType === 'REFUND';
@endphp
<div class="ltx-item">
    <span class="ltx-item-ic {{ $opOut ? 'is-bad' : 'is-ok' }}"><x-lb-icon name="arrow-up-right" /></span>
    <div class="ltx-item-t">
        <b>{{ ucwords(strtolower($activity['payment_type'] ?? 'Transaction')) }}</b>
        <small>
            <span>{{ $dateFmt($activity['paid_date'] ?? '') }}</span>
            <span>{{ $activity['added_by_name'] ?? 'Admin' }}</span>
        </small>
    </div>
    <div class="ltx-item-r">
        <strong class="{{ $opOut ? 'is-bad' : '' }}">₹{{ $fmt($activity['amount'] ?? 0) }}</strong>
        @if(!empty($activity['payment_mode']))
            <span class="ltx-mode is-{{ $modeTone($activity['payment_mode']) }}">{{ $activity['payment_mode'] }}</span>
        @endif
    </div>
    @if(!empty($activity['download_receipt_url']))
        <a href="{{ $activity['download_receipt_url'] }}" target="_blank" class="ltx-icbtn" title="Download receipt" aria-label="Download receipt"><x-lb-icon name="download" /></a>
    @else
        <span class="ltx-icbtn is-off" aria-hidden="true"><x-lb-icon name="download" /></span>
    @endif
</div>
