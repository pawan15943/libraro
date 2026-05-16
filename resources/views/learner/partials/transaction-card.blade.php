@php
    $isDebit = strtolower((string) ($activity->dr_cr ?? '')) === 'dr';
@endphp

<div class="payment-card">
    <div class="payment-icon {{ $isDebit ? 'debit' : '' }}">
        <i class="fa-solid fa-arrow-{{ $isDebit ? 'up' : 'down' }}"></i>
    </div>
    <div>
        <h6>{{ $activity->payment_type ?: ($activity->particular ?: 'Transaction') }}</h6>
        <small>{{ $modeLabel($activity->payment_mode) }}</small>
    </div>
    <div class="amount {{ $isDebit ? 'debit' : '' }}">
        {{ $fmt($activity->amount) }}
        <div><small class="text-muted">{{ $dateFmt($activity->date) }}</small></div>
    </div>
</div>
