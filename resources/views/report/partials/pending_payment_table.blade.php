@php
    use Carbon\Carbon;
    $today = Carbon::today();
    $branchName = getCurrentBranchName();
@endphp

@forelse($learners as $index => $value)
@php
    $endDate = Carbon::parse($value->plan_end_date);
    $diffInDays = $today->diffInDays($endDate, false);
    $inextendDate = $endDate->copy()->addDays($extendDay ?? 0);
    $diffExtendDay = $today->diffInDays($inextendDate, false);
    $transaction = learnerTransaction($value->learner_id, $value->id);
    
    $seat = getSeatDisplayByMainNo(optional($value->learner)->seat_no) ?? 'GEN';
    $learnerName = optional($value->learner)->name ?? 'Learner #' . $value->learner_id;
    $learnerMobile = optional($value->learner)->mobile ?? '';
    $learnerEmail = optional($value->learner)->email ?? '';
    $isExtended = ($diffInDays <= 0 && $diffExtendDay > 0);
    $formattedDueDate = $endDate->format('d M Y');

    $planName = optional($value->plan)->name ?? 'Standard Plan';
    $planTypeName = optional($value->planType)->name ?? 'Slot';
    $planPrice = (float) myPlanPrice($value->id);

    $waMessage = "Dear {$learnerName},\n\n" .
                 "Your library plan expired on {$value->plan_end_date}.\n\n" .
                 "Please renew it soon to continue uninterrupted access to your seat (Seat {$seat}).\n\n" .
                 "You are currently in the extension grace period — after this, your seat may be allotted to another learner.\n\n" .
                 "– Team " . $branchName;
    $waUrl = "https://wa.me/91" . preg_replace('/[^0-9]/', '', $learnerMobile) . "?text=" . rawurlencode($waMessage);

    $searchKeywords = strtolower($learnerName . ' ' . $learnerMobile . ' ' . ($seat ? 'seat ' . $seat : 'general') . ' ' . $planName . ' ' . $formattedDueDate);
@endphp

<div class="collection-record-card" 
     data-search="{{ $searchKeywords }}"
     data-seat="{{ $seat }}"
     data-name="{{ $learnerName }}"
     data-mobile="{{ $learnerMobile }}"
     data-plan="{{ $planName }}"
     data-due="{{ $formattedDueDate }}"
     data-status="{{ $isExtended ? 'In Grace' : 'Overdue' }}">

    {{-- Mobile Top Strip (< 992px) --}}
    <div class="d-flex d-lg-none align-items-center justify-content-between flex-wrap gap-2 pb-2 border-bottom">
        @if($isExtended)
            <span class="status-pill pill-extension"><i class="fa-solid fa-clock me-1"></i>Grace: {{ $diffExtendDay }}d left</span>
        @else
            <span class="status-pill pill-overdue"><i class="fa-solid fa-triangle-exclamation me-1"></i>Overdue</span>
        @endif
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
            <div class="record-seat-tag {{ ($seat !== 'General' && $seat !== 'GEN') ? '' : 'seat-general' }}">
                <i class="fa-solid fa-chair me-1"></i>SEAT {{ strtoupper($seat) }}
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
                    @if(strlen(preg_replace('/[^0-9]/', '', $learnerMobile)) >= 10)
                        <a href="https://wa.me/91{{ preg_replace('/[^0-9]/', '', $learnerMobile) }}" target="_blank" class="contact-item contact-whatsapp" title="WhatsApp">
                            <i class="fa-brands fa-whatsapp"></i> WhatsApp
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Col 2: Plan Breakdown Chips --}}
    <div class="record-col-breakdown">
        <span class="breakdown-pill">Plan: {{ $planName }}</span>
        @if($planPrice > 0)
            <span class="breakdown-pill">₹{{ number_format($planPrice, 0) }}</span>
        @endif
        <span class="breakdown-pill">{{ $planTypeName }}</span>
    </div>

    {{-- Mobile Settlement / Pending Amount Banner (< 992px) --}}
    @if($transaction && $transaction->pending_amount > 0)
        <div class="d-flex d-lg-none align-items-center justify-content-between p-2 rounded-2 border border-danger-subtle bg-danger-subtle mt-1 mb-1">
            <span class="text-danger small fw-semibold"><i class="fa-solid fa-triangle-exclamation me-1"></i>Pending Balance:</span>
            <span class="text-danger fw-bold">₹ {{ number_format($transaction->pending_amount, 0) }}</span>
        </div>
    @endif

    {{-- Desktop Col 3: Due Date --}}
    <div class="d-none d-lg-block text-center text-muted font-monospace" style="font-size: 0.82rem;">
        {{ $formattedDueDate }}
    </div>

    {{-- Desktop Col 4: Extension / Overdue Status --}}
    <div class="d-none d-lg-block text-center">
        @if ($isExtended)
            <span class="status-pill pill-extension">
                <i class="fa-solid fa-clock"></i> {{ $diffExtendDay }}d left
            </span>
        @else
            <span class="status-pill pill-overdue">
                <i class="fa-solid fa-triangle-exclamation"></i> Overdue
            </span>
        @endif
    </div>

    {{-- Desktop Col 5: Settlement / Dues --}}
    <div class="d-none d-lg-block text-center">
        @if($transaction && $transaction->pending_amount > 0)
            <span class="status-pill pill-pending font-monospace fw-bold" title="Pending Settlement">
                Due ₹{{ number_format($transaction->pending_amount, 0) }}
            </span>
        @else
            <span class="status-pill pill-cleared">
                <i class="fa-solid fa-check"></i> Plan Paid
            </span>
        @endif
    </div>

    {{-- Col 6: Actions --}}
    <div class="record-col-actions">
        @if($transaction && $transaction->pending_amount > 0)
            <a href="javascript:void(0)" data-id="{{ $value->learner_id }}" data-learnerDetail="{{ $value->id }}" class="btn-card-receipt settlement-learner" title="Settle Pending Dues">
                <i class="fas fa-credit-card"></i>
            </a>
        @else
            <a href="{{ route('learner.other.payment', $value->id) }}" class="btn-card-receipt" title="Renew / Other Payment">
                <i class="fa-solid fa-money-bill"></i>
            </a>
        @endif

        @if(!empty($learnerMobile))
            <a href="{{ $waUrl }}" target="_blank" class="btn-card-profile" title="Send WhatsApp Reminder" style="color: #16a34a;">
                <i class="fab fa-whatsapp"></i>
            </a>
        @endif
    </div>
</div>
@empty
<div class="report-empty-state">
    <i class="fa-solid fa-circle-check empty-state-icon text-success"></i>
    <h6 class="empty-state-title">No Pending Renewals Found</h6>
    <p class="small text-muted mb-0">All active learners are up-to-date with their payments and subscriptions.</p>
</div>
@endforelse
