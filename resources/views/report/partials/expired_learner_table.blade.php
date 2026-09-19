@php
    use Carbon\Carbon;
    $today = Carbon::today();
@endphp

@forelse($learners as $value)
    @php
        $endDate = !empty($value->plan_end_date) ? Carbon::parse($value->plan_end_date) : null;
        $daysSinceExpired = $endDate ? $endDate->diffInDays($today, false) : 0;
        $seat = getSeatDisplayByMainNo(optional($value->learner)->seat_no) ?? 'GEN';
        $learnerName = optional($value->learner)->name ?? 'Learner #' . $value->learner_id;
        $firstLetter = strtoupper(substr($learnerName, 0, 1));
        $mobile = optional($value->learner)->mobile ?? '';
        $email = optional($value->learner)->email ?? '';
        $planName = optional($value->plan)->name ?? 'Plan';
        $slotName = optional($value->planType)->name ?? 'General';
        
        $searchData = strtolower($learnerName . ' ' . $mobile . ' ' . $email . ' ' . $seat . ' ' . $planName . ' ' . $slotName . ' ' . $value->plan_end_date);
    @endphp

    <div class="collection-record-card" 
         data-search="{{ $searchData }}"
         data-name="{{ $learnerName }}"
         data-seat="{{ $seat }}"
         data-mobile="{{ $mobile }}"
         data-email="{{ $email }}"
         data-plan="{{ $planName }}"
         data-slot="{{ $slotName }}"
         data-start="{{ $value->plan_start_date }}"
         data-end="{{ $value->plan_end_date }}"
         data-status="{{ $daysSinceExpired <= 30 ? 'Recent' : 'Inactive' }}">

        {{-- Mobile-Only Top Bar (< 992px) --}}
        <div class="d-flex d-lg-none align-items-center justify-content-between flex-wrap gap-2 pb-2 border-bottom">
            <span class="status-pill {{ $daysSinceExpired <= 30 ? 'pill-recent' : 'pill-expired' }}">
                <i class="fa-solid {{ $daysSinceExpired <= 30 ? 'fa-clock' : 'fa-user-xmark' }}"></i>
                {{ $daysSinceExpired <= 30 ? 'Expired ' . $daysSinceExpired . 'd ago' : 'Inactive (' . $daysSinceExpired . 'd)' }}
            </span>
            <div class="text-danger fw-semibold small">
                Expired: {{ $value->plan_end_date }}
            </div>
        </div>

        {{-- Col 1: Learner Profile & Seat Info --}}
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
                        <a target="_blank" href="https://wa.me/91{{ preg_replace('/[^0-9]/', '', $mobile) }}?text={{ rawurlencode("Hi {$learnerName},\n\nWe noticed you haven't visited Libraro library recently! Your previous membership ended on {$value->plan_end_date}.\n\nWe'd love to welcome you back. Check out our latest study slots and rejoin today!\n\nBest regards,\nTeam Libraro") }}" class="contact-item contact-whatsapp" title="Chat on WhatsApp">
                            <i class="fa-brands fa-whatsapp"></i> WhatsApp
                        </a>
                    @else
                        <span class="contact-item text-muted">No Contact</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Col 2: Breakdown Pills (Plan & Slot) --}}
        <div class="record-col-breakdown">
            <span class="breakdown-pill" title="Previous Plan">
                Plan: {{ $planName }}
            </span>
            <span class="breakdown-pill" title="Slot / Plan Type">
                Slot: {{ $slotName }}
            </span>
        </div>

        {{-- Col 3: Plan Duration --}}
        <div class="record-col-duration">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-1">
                <span class="text-muted small" style="font-size: 0.76rem;"><i class="fa-regular fa-calendar me-1"></i>Duration:</span>
                <span class="fw-semibold text-dark" style="font-size: 0.82rem;">{{ $value->plan_start_date }} &rarr; {{ $value->plan_end_date }}</span>
            </div>
        </div>

        {{-- Col 4: Expired On & Status Pill - Desktop Only --}}
        <div class="text-lg-center d-none d-lg-block">
            <div class="fw-semibold text-danger" style="font-size: 0.84rem;">{{ $value->plan_end_date }}</div>
            <div class="mt-1">
                @if($daysSinceExpired <= 30)
                    <span class="status-pill pill-recent">
                        <i class="fa-solid fa-clock"></i> Expired {{ $daysSinceExpired }}d ago
                    </span>
                @else
                    <span class="status-pill pill-expired">
                        <i class="fa-solid fa-user-xmark"></i> Inactive ({{ $daysSinceExpired }}d)
                    </span>
                @endif
            </div>
        </div>

        {{-- Col 5: Actions --}}
        <div class="record-col-actions">
            {{-- Edit / Re-enroll --}}
            <a href="{{ route('learners.edit', $value->learner_id) }}" class="btn-card-receipt" data-bs-toggle="tooltip" title="Renew / Edit Learner">
                <i class="fas fa-edit"></i>
            </a>

            {{-- WhatsApp Re-engagement --}}
            @if(!empty($mobile))
                <a target="_blank" href="https://wa.me/91{{ preg_replace('/[^0-9]/', '', $mobile) }}?text={{ rawurlencode("Hi {$learnerName},\n\nWe noticed you haven't visited Libraro library recently! Your previous membership ended on {$value->plan_end_date}.\n\nWe'd love to welcome you back. Check out our latest study slots and rejoin today!\n\nBest regards,\nTeam Libraro") }}" class="btn-card-profile" style="color: #16a34a;" data-bs-toggle="tooltip" title="Send Re-engagement WhatsApp">
                    <i class="fab fa-whatsapp"></i>
                </a>
            @endif
        </div>

    </div>
@empty
    <div class="report-empty-state">
        <i class="fa-solid fa-users-slash empty-state-icon"></i>
        <h6 class="empty-state-title">No Expired Learners Found</h6>
        <p class="small text-muted mb-0">No learners match the selected expired year and month criteria.</p>
    </div>
@endforelse
