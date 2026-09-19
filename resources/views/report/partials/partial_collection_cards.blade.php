@php
    use Carbon\Carbon;
    $today = Carbon::today();
    $branchName = getCurrentBranchName();
@endphp

@forelse($learners as $index => $value)
@php
    $learnerName = $value->name ?? ('Learner #' . ($value->learner_id ?? ''));
    $learnerMobile = '';
    if (!empty($value->mobile)) {
        try {
            $learnerMobile = decryptData($value->mobile);
        } catch (\Exception $e) {
            $learnerMobile = $value->mobile;
        }
    }

    $learnerSeat = $value->seat_no ?? null;
    $seatDisplay = $learnerSeat ? getSeatDisplayByMainNo($learnerSeat) : null;

    $totalAmt = (float) ($value->total_amount ?? 0);
    $paidAmt = (float) ($value->paid_amount ?? 0);
    $pendingAmt = (float) ($value->pending_amount ?? 0);

    $dueDate = !empty($value->due_date) ? Carbon::parse($value->due_date) : null;
    $formattedDueDate = $dueDate ? $dueDate->format('d M Y') : '-';

    $isPaid = ($value->is_paid == 1 || $pendingAmt <= 0);
    $isOverdue = (!$isPaid && $dueDate && $dueDate->lt($today));
    $isDueToday = (!$isPaid && $dueDate && $dueDate->isToday());
    $isUpcoming = (!$isPaid && $dueDate && $dueDate->gt($today));
    $overdueDays = ($isOverdue && $dueDate) ? $today->diffInDays($dueDate) : 0;
    $daysLeft = ($isUpcoming && $dueDate) ? $dueDate->diffInDays($today) : 0;

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

    $waDueDateFormatted = $dueDate ? $dueDate->format('d-m-Y') : 'scheduled date';
    $waMessage = "Dear {$learnerName}\n\n" .
                 "This is a gentle reminder that your library seat payment (Seat " . ($seatDisplay ?: 'GEN') . ") is pending.\n\n" .
                 "Pending Balance: ₹" . number_format($pendingAmt, 2) . "\n" .
                 "Due Date: {$waDueDateFormatted}.\n\n" .
                 "To avoid seat cancellation, please complete the payment soon.\n\n" .
                 "– Team " . $branchName;
    $waUrl = "https://wa.me/" . (str_starts_with($learnerMobile, '91') ? $learnerMobile : '91' . $learnerMobile) . "?text=" . rawurlencode($waMessage);

    $searchKeywords = strtolower($learnerName . ' ' . $learnerMobile . ' ' . ($seatDisplay ? 'seat ' . $seatDisplay : 'general') . ' ' . $modeLabel . ' ' . $formattedDueDate);
@endphp

<div class="collection-record-card" 
     data-search="{{ $searchKeywords }}"
     data-seat="{{ $seatDisplay ?? 'General' }}"
     data-name="{{ $learnerName }}"
     data-mobile="{{ $learnerMobile }}"
     data-total="{{ $totalAmt }}"
     data-paid="{{ $paidAmt }}"
     data-pending="{{ $pendingAmt }}"
     data-date="{{ $formattedDueDate }}"
     data-mode="{{ $modeLabel }}">

    {{-- Mobile-Only Top Row (< 992px) --}}
    <div class="d-flex d-lg-none align-items-center justify-content-between flex-wrap gap-2 pb-2 border-bottom">
        <span class="badge-payment-mode {{ $modeClass }}">
            <i class="fa-solid {{ $modeIcon }}"></i> {{ $modeLabel }}
        </span>
        <div class="record-date-badge">
            <i class="fa-regular fa-calendar-days me-1"></i>{{ $formattedDueDate }}
        </div>
    </div>

    {{-- Col 1: Learner Details with Seat No. above student name --}}
    <div class="record-col-learner">
        <div class="record-learner-avatar">
            {{ strtoupper(substr($learnerName, 0, 1)) }}
        </div>
        <div class="record-learner-text">
            <div class="record-seat-tag {{ (!empty($seatDisplay) && $seatDisplay !== 'General' && $seatDisplay !== 'GEN') ? '' : 'seat-general' }}">
                @if(!empty($seatDisplay) && $seatDisplay !== 'General' && $seatDisplay !== 'GEN')
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
        <span class="breakdown-pill">Total: ₹{{ number_format($totalAmt, 0) }}</span>
        @if($paidAmt > 0)
            <span class="breakdown-pill">Paid: ₹{{ number_format($paidAmt, 0) }}</span>
        @endif
    </div>

    {{-- Mobile Financials Strip (< 992px) --}}
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
            <span class="fin-label">Pending</span>
            <span class="fin-value text-danger">₹ {{ number_format($pendingAmt, 0) }}</span>
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

    {{-- Desktop Col 5: Pending Due (>= 992px) --}}
    <div class="d-none d-lg-block text-center">
        <span class="fin-value-desktop text-danger fw-bold">₹ {{ number_format($pendingAmt, 0) }}</span>
    </div>

    {{-- Desktop Col 6: Due Date (>= 992px) --}}
    <div class="d-none d-lg-block text-center text-muted font-monospace" style="font-size: 0.8rem;">
        {{ $formattedDueDate }}
        @if($isOverdue)
            <div><span class="badge bg-danger" style="font-size: 0.65rem;">Overdue</span></div>
        @elseif($isDueToday)
            <div><span class="badge bg-warning text-dark" style="font-size: 0.65rem;">Due Today</span></div>
        @endif
    </div>

    {{-- Desktop Col 7: Payment Mode (>= 992px) --}}
    <div class="d-none d-lg-block text-center">
        <span class="badge-payment-mode {{ $modeClass }}" title="Mode: {{ $modeLabel }}">
            <i class="fa-solid {{ $modeIcon }}"></i> {{ $modeLabel }}
        </span>
    </div>

    {{-- Col 8: Actions (Settle + Profile / WhatsApp) --}}
    <div class="record-col-actions">
        <a href="javascript:void(0)" 
           data-id="{{ $value->learner_id }}" 
           data-learnerDetail="{{ $value->learner_detail_id ?? $value->id }}" 
           class="btn-card-receipt settlement-learner" 
           title="Settle Payment Balance">
            <i class="fas fa-credit-card"></i>
        </a>

        @if(!empty($learnerMobile))
            <a href="{{ $waUrl }}" target="_blank" class="btn-card-profile" title="WhatsApp Reminder" style="color: #16a34a;">
                <i class="fab fa-whatsapp"></i>
            </a>
        @endif
    </div>
</div>
@empty
<div class="report-empty-state">
    <i class="fa-solid fa-circle-check empty-state-icon text-success"></i>
    <h6 class="empty-state-title">No Pending Partial Payments</h6>
    <p class="small text-muted mb-0">All partial payment balances are fully cleared.</p>
</div>
@endforelse
