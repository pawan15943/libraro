@php
    use Carbon\Carbon;
@endphp

@forelse($learners as $index => $value)
@php
    $learnerObj = $value->learner;
    $learnerName = $learnerObj->name ?? 'Learner #' . $value->learner_id;
    $learnerMobile = $learnerObj->mobile ?? '';
    $learnerSeat = $learnerObj->seat_no ?? null;
    $seatDisplay = $learnerSeat ? getSeatDisplayByMainNo($learnerSeat) : null;
    
    $planPrice = (float) myPlanPrice($value->learner_detail_id);
    $lockerAmt = (float) ($value->locker_amount ?? 0);
    $discountAmt = (float) ($value->discount_amount ?? 0);
    $totalAmt = (float) ($value->total_amount ?? 0);
    $paidAmt = (float) ($value->paid_amount ?? 0);
    $pendingAmt = (float) ($value->pending_amount ?? 0);

    // Normalized Payment Mode: Online / Offline / Pay Later
    $rawMode = strtolower(trim((string)($value->payment_mode ?? '')));
    if (in_array($rawMode, ['1', 'online'])) {
        $modeLabel = 'Online';
        $modeClass = 'mode-online';
        $modeIcon = 'fa-globe';
    } elseif (in_array($rawMode, ['2', 'offline'])) {
        $modeLabel = 'Offline';
        $modeClass = 'mode-offline';
        $modeIcon = 'fa-money-bill-wave';
    } elseif (in_array($rawMode, ['3', 'pay later', 'paylater']) || str_contains($rawMode, 'pay')) {
        $modeLabel = 'Pay Later';
        $modeClass = 'mode-paylater';
        $modeIcon = 'fa-clock';
    } else {
        $modeLabel = !empty($rawMode) ? ucfirst($rawMode) : 'Offline';
        $modeClass = 'mode-offline';
        $modeIcon = 'fa-money-bill-wave';
    }

    $formattedDate = $value->paid_date ? Carbon::parse($value->paid_date)->format('d M Y') : '-';
    $searchKeywords = strtolower($learnerName . ' ' . $learnerMobile . ' ' . ($seatDisplay ? 'seat ' . $seatDisplay : 'general') . ' ' . $modeLabel . ' ' . $formattedDate);
@endphp

<div class="collection-record-card" 
     data-search="{{ $searchKeywords }}"
     data-seat="{{ $seatDisplay ?? 'General' }}"
     data-name="{{ $learnerName }}"
     data-mobile="{{ $learnerMobile }}"
     data-total="{{ $totalAmt }}"
     data-paid="{{ $paidAmt }}"
     data-pending="{{ $pendingAmt }}"
     data-date="{{ $formattedDate }}"
     data-mode="{{ $modeLabel }}">

    {{-- Mobile-Only Top Row (< 992px) --}}
    <div class="d-flex d-lg-none align-items-center justify-content-between flex-wrap gap-2 pb-2 border-bottom">
        <span class="badge-payment-mode {{ $modeClass }}" title="Mode: {{ $modeLabel }}">
            <i class="fa-solid {{ $modeIcon }}"></i> {{ $modeLabel }}
        </span>
        <div class="record-date-badge">
            <i class="fa-regular fa-calendar-days me-1"></i>{{ $formattedDate }}
        </div>
    </div>

    {{-- Col 1: Learner Details with Seat No. above student name --}}
    <div class="record-col-learner">
        <div class="record-learner-avatar">
            {{ strtoupper(substr($learnerName, 0, 1)) }}
        </div>
        <div class="record-learner-text">
            {{-- Small block-letter Seat Tag directly above student name --}}
            <div class="record-seat-tag {{ (!empty($seatDisplay) && $seatDisplay !== 'General') ? '' : 'seat-general' }}">
                @if(!empty($seatDisplay) && $seatDisplay !== 'General')
                    <i class="fa-solid fa-chair me-1"></i>SEAT {{ strtoupper($seatDisplay) }}
                @else
                    <i class="fa-solid fa-chair me-1"></i>GENERAL
                @endif
            </div>

            @if($value->learner_id)
                <a href="{{ route('learners.show', $value->learner_id) }}" class="record-learner-name" title="View Profile">
                    {{ $learnerName }}
                </a>
            @else
                <span class="record-learner-name">{{ $learnerName }}</span>
            @endif

            @if(!empty($learnerMobile))
                <div class="record-learner-contacts">
                    <a href="tel:{{ $learnerMobile }}" class="contact-item" title="Call {{ $learnerMobile }}">
                        <i class="fa-solid fa-phone"></i> {{ $learnerMobile }}
                    </a>
                    @if(strlen($learnerMobile) == 10)
                        <a href="https://wa.me/91{{ $learnerMobile }}" target="_blank" class="contact-item contact-whatsapp" title="WhatsApp">
                            <i class="fa-brands fa-whatsapp"></i> WhatsApp
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Col 2: Fee Breakdown Chips --}}
    <div class="record-col-breakdown">
        <span class="breakdown-pill">Plan: ₹{{ number_format($planPrice, 0) }}</span>
        @if($lockerAmt > 0)
            <span class="breakdown-pill">Locker: +₹{{ number_format($lockerAmt, 0) }}</span>
        @endif
        @if($discountAmt > 0)
            <span class="breakdown-pill text-danger">Disc: -₹{{ number_format($discountAmt, 0) }}</span>
        @endif
    </div>

    {{-- Mobile-Only Financials Strip (< 992px) --}}
    <div class="record-card-financials d-grid d-lg-none">
        <div class="fin-item">
            <span class="fin-label">Total Bill</span>
            <span class="fin-value">₹ {{ number_format($totalAmt, 0) }}</span>
        </div>
        <div class="fin-item fin-paid">
            <span class="fin-label">Paid</span>
            <span class="fin-value text-success">₹ {{ number_format($paidAmt, 0) }}</span>
        </div>
        <div class="fin-item">
            <span class="fin-label">Status</span>
            @if($pendingAmt > 0)
                <span class="record-due-tag" title="Pending: ₹{{ number_format($pendingAmt, 2) }}">
                    Due ₹ {{ number_format($pendingAmt, 0) }}
                </span>
            @else
                <span class="record-cleared-tag" title="Cleared">
                    <i class="fa-solid fa-check me-1"></i>Cleared
                </span>
            @endif
        </div>
    </div>

    {{-- Desktop Col 3: Total Bill (>= 992px) --}}
    <div class="d-none d-lg-block text-center">
        <span class="fin-value-desktop">₹ {{ number_format($totalAmt, 0) }}</span>
    </div>

    {{-- Desktop Col 4: Paid Amount (>= 992px) --}}
    <div class="d-none d-lg-block text-center">
        <span class="fin-value-desktop text-success">₹ {{ number_format($paidAmt, 0) }}</span>
    </div>

    {{-- Desktop Col 5: Due Status (>= 992px) --}}
    <div class="d-none d-lg-block text-center">
        @if($pendingAmt > 0)
            <span class="record-due-tag" title="Pending: ₹{{ number_format($pendingAmt, 2) }}">
                Due ₹ {{ number_format($pendingAmt, 0) }}
            </span>
        @else
            <span class="record-cleared-tag" title="Cleared">
                <i class="fa-solid fa-check me-1"></i>Cleared
            </span>
        @endif
    </div>

    {{-- Desktop Col 6: Collection Date (>= 992px) --}}
    <div class="d-none d-lg-block text-center text-muted font-monospace" style="font-size: 0.8rem;">
        {{ $formattedDate }}
    </div>

    {{-- Desktop Col 7: Payment Mode (>= 992px) --}}
    <div class="d-none d-lg-block text-center">
        <span class="badge-payment-mode {{ $modeClass }}" title="Mode: {{ $modeLabel }}">
            <i class="fa-solid {{ $modeIcon }}"></i> {{ $modeLabel }}
        </span>
    </div>

    {{-- Col 8: Actions (Receipt + Profile) --}}
    <div class="record-col-actions">
        @can('has-permission', 'Receipt Generation')
            @if($value->paid_amount > 0 || $value->is_paid == 1)
                <form action="{{ route('fee.generateReceipt') }}" method="POST" target="_blank" class="m-0 p-0 receipt-form">
                    @csrf
                    <input type="hidden" name="id" value="{{ $value->id }}">
                    <input type="hidden" name="type" value="learner">
                    <button type="submit" class="btn-card-receipt noLoader" title="Print Fee Receipt">
                        <i class="fa-solid fa-print"></i> <span class="d-inline d-lg-none ms-1">Receipt</span>
                    </button>
                </form>
            @endif
        @endcan

        @if($value->learner_id)
            <a href="{{ route('learners.show', $value->learner_id) }}" class="btn-card-profile" title="View Profile">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
            </a>
        @endif
    </div>
</div>
@empty
<div class="report-empty-state">
    <i class="fa-solid fa-folder-open empty-state-icon"></i>
    <h6 class="empty-state-title">No Collections Found for Selected Criteria</h6>
    <p class="small text-muted mb-3">
        Try expanding the date range or choosing a different filter preset.
    </p>
    <button type="button" class="btn btn-filter-apply" id="btnEmptyStateAll">
        <i class="fa-solid fa-list-check me-1"></i> View All Collections
    </button>
</div>
@endforelse
