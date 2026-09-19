@php
    use Carbon\Carbon;
    $today = Carbon::today();
    $currMonth = $today->month;
    $currYear = $today->year;
@endphp

@forelse($learners as $value)
    @php
        $seat = getSeatDisplayByMainNo(optional($value->learner)->seat_no) ?? 'GEN';
        $learnerName = optional($value->learner)->name ?? 'Learner #' . $value->learner_id;
        $firstLetter = strtoupper(substr($learnerName, 0, 1));
        $mobile = optional($value->learner)->mobile ?? '';
        $email = optional($value->learner)->email ?? '';
        $planName = optional($value->plan)->name ?? 'Standard Plan';
        $slotName = optional($value->planType)->name ?? 'General Slot';
        $isActive = ($value->status == 1);
        $dob = optional($value->learner)->dob ?? '';

        // Financial & Transaction Calculations
        $tx = $value->transaction;
        $totalAmt = (float) ($tx->total_amount ?? ($value->plan_price_id ?? 0));
        $paidAmt = (float) ($tx->paid_amount ?? ($value->is_paid == 1 ? $totalAmt : 0));
        $pendingAmt = (float) ($tx->pending_amount ?? max(0, $totalAmt - $paidAmt));
        $isPaid = ($value->is_paid == 1 || $pendingAmt <= 0);

        // Collection Date Intelligence
        $paidDateRaw = $tx->paid_date ?? $value->join_date ?? null;
        $paidDate = !empty($paidDateRaw) ? Carbon::parse($paidDateRaw) : null;
        $isPaidToday = ($paidDate && $paidDate->isToday());
        $isPaidThisMonth = ($paidDate && $paidDate->year == $currYear && $paidDate->month == $currMonth);
        $formattedPaidDate = $paidDate ? $paidDate->format('d M Y') : '-';

        // Validity & Days Remaining/Lapsed
        $endDate = !empty($value->plan_end_date) ? Carbon::parse($value->plan_end_date) : null;
        $daysDiff = $endDate ? $today->diffInDays($endDate, false) : 0;

        // Payment Mode
        $rawMode = strtolower(trim((string)($tx->payment_mode ?? $value->payment_mode ?? '')));
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

        $waMsg = "Hello {$learnerName},\n\nThis is regarding your membership at Libraro library (Seat " . ($seat ?: 'GEN') . ").\nPlan: {$planName}\nValid Till: {$value->plan_end_date}" . ($pendingAmt > 0 ? "\nPending Due: ₹" . number_format($pendingAmt, 2) : "") . "\n\nThank you,\nTeam Libraro";
        $waUrl = "https://wa.me/91" . preg_replace('/[^0-9]/', '', $mobile) . "?text=" . rawurlencode($waMsg);

        $searchData = strtolower($learnerName . ' ' . $mobile . ' ' . $email . ' ' . $seat . ' ' . $planName . ' ' . $slotName . ' ' . ($isPaid ? 'paid' : 'unpaid') . ' ' . ($isActive ? 'active' : 'expired') . ' ' . $modeLabel . ' ' . $formattedPaidDate);
    @endphp

    <div class="collection-record-card"
         data-search="{{ $searchData }}"
         data-name="{{ $learnerName }}"
         data-seat="{{ $seat }}"
         data-mobile="{{ $mobile }}"
         data-email="{{ $email }}"
         data-plan="{{ $planName }}"
         data-total="{{ $totalAmt }}"
         data-paid="{{ $paidAmt }}"
         data-pending="{{ $pendingAmt }}"
         data-date="{{ $formattedPaidDate }}"
         data-is-today="{{ $isPaidToday ? '1' : '0' }}"
         data-is-month="{{ $isPaidThisMonth ? '1' : '0' }}"
         data-has-due="{{ $pendingAmt > 0 ? '1' : '0' }}"
         data-status="{{ $isActive ? 'active' : 'expired' }}"
         data-is-paid="{{ $isPaid ? 'paid' : 'unpaid' }}">

        {{-- Mobile-Only Top Bar (< 992px) --}}
        <div class="d-flex d-lg-none align-items-center justify-content-between flex-wrap gap-2 pb-2 border-bottom">
            <span class="badge-payment-mode {{ $modeClass }}">
                <i class="fa-solid {{ $modeIcon }}"></i> {{ $modeLabel }}
            </span>
            <div class="d-flex align-items-center gap-2">
                @if($isPaidToday)
                    <span class="badge-today-collection">
                        <i class="fa-solid fa-bolt"></i> Today
                    </span>
                @endif
                <span class="status-pill {{ $isActive ? 'pill-active' : 'pill-expired' }}">
                    <i class="fa-solid {{ $isActive ? 'fa-circle-check' : 'fa-clock' }}"></i>
                    {{ $isActive ? 'Active' : 'Expired' }}
                </span>
            </div>
        </div>

        {{-- Col 1: Learner Info & Seat Tag --}}
        <div class="record-col-learner">
            <div class="record-learner-avatar">
                {{ $firstLetter }}
            </div>
            <div class="record-learner-text">
                <div class="record-seat-tag {{ ($seat !== 'GEN' && $seat !== 'General') ? '' : 'seat-general' }}">
                    <i class="fa-solid fa-chair"></i>
                    <span>SEAT {{ strtoupper($seat) }}</span>
                </div>
                <a href="{{ route('learners.show', $value->learner_id) }}" class="record-learner-name" title="View Learner Profile">
                    {{ $learnerName }}
                </a>
                <div class="record-learner-contacts">
                    @if(!empty($mobile))
                        <a href="tel:{{ $mobile }}" class="contact-item" title="Call Learner">
                            <i class="fa-solid fa-phone"></i> {{ $mobile }}
                        </a>
                        <a target="_blank" href="{{ $waUrl }}" class="contact-item contact-whatsapp" title="WhatsApp">
                            <i class="fa-brands fa-whatsapp"></i> WhatsApp
                        </a>
                    @else
                        <span class="contact-item text-muted">No Contact</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Col 2: Plan & Duration --}}
        <div class="record-col-plan">
            <div class="plan-chips-row">
                <span class="breakdown-pill" title="Enrolled Plan">
                    {{ $planName }}
                </span>
                <span class="breakdown-pill" title="Study Slot">
                    {{ $slotName }}
                </span>
            </div>
            <div class="plan-dates-text">
                {{ $value->plan_start_date }} to {{ $value->plan_end_date }}
            </div>
        </div>

        {{-- Col 3: Financials (Total / Paid / Due) - Desktop Only --}}
        <div class="record-col-financials d-none d-lg-block">
            <div class="fin-main-val">₹ {{ number_format($totalAmt, 0) }}</div>
            <div class="fin-sub-val text-success fw-semibold">
                Paid: ₹ {{ number_format($paidAmt, 0) }}
            </div>
            @if($pendingAmt > 0)
                <div class="fin-sub-val text-danger fw-semibold">
                    Due: ₹ {{ number_format($pendingAmt, 0) }}
                </div>
            @else
                <div class="fin-sub-val text-success" style="font-size: 0.72rem;">
                    <i class="fa-solid fa-check"></i> Cleared
                </div>
            @endif
        </div>

        {{-- Mobile Financials Strip (< 992px) --}}
        <div class="record-card-financials d-grid d-lg-none">
            <div class="fin-item">
                <span class="fin-label">Total Bill</span>
                <span class="fin-value">₹ {{ number_format($totalAmt, 0) }}</span>
            </div>
            <div class="fin-item">
                <span class="fin-label">Paid</span>
                <span class="fin-value text-success">₹ {{ number_format($paidAmt, 0) }}</span>
            </div>
            <div class="fin-item">
                <span class="fin-label">Pending</span>
                <span class="fin-value {{ $pendingAmt > 0 ? 'text-danger' : 'text-success' }}">
                    ₹ {{ number_format($pendingAmt, 0) }}
                </span>
            </div>
        </div>

        {{-- Col 4: Collection Details (Daily / Monthly Date & Mode) - Desktop Only --}}
        <div class="record-col-collection d-none d-lg-block">
            @if($isPaidToday)
                <div>
                    <span class="badge-today-collection">
                        <i class="fa-solid fa-bolt"></i> Paid Today
                    </span>
                </div>
            @endif
            <div class="fw-semibold text-dark" style="font-size: 0.82rem;">
                {{ $formattedPaidDate }}
            </div>
            <div class="mt-1">
                <span class="badge-payment-mode {{ $modeClass }}" title="Payment Mode">
                    <i class="fa-solid {{ $modeIcon }}"></i> {{ $modeLabel }}
                </span>
            </div>
        </div>

        {{-- Col 5: Membership Status - Desktop Only --}}
        <div class="record-col-status d-none d-lg-block">
            @if($isActive)
                <span class="status-pill pill-active">
                    <i class="fa-solid fa-circle-check"></i> Active
                </span>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                    {{ $daysDiff >= 0 ? $daysDiff . 'd left' : 'Ends today' }}
                </div>
            @else
                <span class="status-pill pill-expired">
                    <i class="fa-solid fa-clock"></i> Expired
                </span>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                    {{ abs($daysDiff) }}d ago
                </div>
            @endif
        </div>

        {{-- Col 6: Actions --}}
        <div class="record-col-actions">
            {{-- View Profile --}}
            <a href="{{ route('learners.show', $value->learner_id) }}" class="btn-card-receipt" data-bs-toggle="tooltip" title="View Learner Profile">
                <i class="fas fa-eye"></i>
            </a>

            {{-- Edit Learner --}}
            <a href="{{ route('learners.edit', $value->learner_id) }}" class="btn-card-profile" data-bs-toggle="tooltip" title="Edit / Renew Learner">
                <i class="fas fa-edit"></i>
            </a>

            {{-- WhatsApp Direct --}}
            @if(!empty($mobile))
                <a target="_blank" href="{{ $waUrl }}" class="btn-card-profile" style="color: #16a34a;" data-bs-toggle="tooltip" title="Message on WhatsApp">
                    <i class="fab fa-whatsapp"></i>
                </a>
            @endif
        </div>

    </div>
@empty
    <div class="report-empty-state">
        <i class="fa-solid fa-users-slash empty-state-icon"></i>
        <h6 class="empty-state-title">No Learners Found</h6>
        <p class="small text-muted mb-0">No learner records matched your filter criteria.</p>
    </div>
@endforelse
