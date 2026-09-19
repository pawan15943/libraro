@php
    use Carbon\Carbon;
    $today = Carbon::today();
    $branchName = getCurrentBranchName();
@endphp

@php $hasRecords = false; @endphp
@foreach($learners as $value)
    @foreach ($value->learnerDetails as $detail)
        @php
            $hasRecords = true;
            $endDate = Carbon::parse($detail->plan_end_date);
            $daysDiff = $today->diffInDays($endDate, false);
            $seat = getSeatDisplayByMainNo($value->seat_no) ?? 'GEN';
            $learnerName = $value->name ?? 'Learner #' . $value->id;
            $learnerMobile = $value->mobile ?? '';
            $learnerEmail = $value->email ?? '';
            $formattedDueDate = $endDate->format('d M Y');

            $planName = optional($detail->plan)->name ?? 'Plan Subscription';
            $planTypeName = optional($detail->planType)->name ?? 'Slot';
            $planPrice = (float) myPlanPrice($detail->id);

            $waMessage = "Dear {$learnerName},\n\n" .
                         "Your library subscription for Seat {$seat} is expiring on {$detail->plan_end_date}.\n\n" .
                         "Please renew your plan in advance to keep your reserved seat intact.\n\n" .
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
             data-diff="{{ $daysDiff }}">

            {{-- Mobile Top Strip (< 992px) --}}
            <div class="d-flex d-lg-none align-items-center justify-content-between flex-wrap gap-2 pb-2 border-bottom">
                @if($daysDiff === 0)
                    <span class="status-pill pill-today"><i class="fa-solid fa-circle-exclamation me-1"></i>Expires Today</span>
                @elseif($daysDiff === 1)
                    <span class="status-pill pill-tomorrow"><i class="fa-solid fa-hourglass-half me-1"></i>Tomorrow</span>
                @else
                    <span class="status-pill pill-soon"><i class="fa-solid fa-clock me-1"></i>In {{ $daysDiff }} Days</span>
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

                    @if($value->id)
                        <a href="{{ route('learners.show', $value->id) }}" class="record-learner-name" title="View Profile">
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

            {{-- Desktop Col 3: Due Date --}}
            <div class="d-none d-lg-block text-center text-muted font-monospace" style="font-size: 0.82rem;">
                {{ $formattedDueDate }}
            </div>

            {{-- Desktop Col 4: Days Remaining --}}
            <div class="d-none d-lg-block text-center">
                @if($daysDiff === 0)
                    <span class="status-pill pill-today">
                        <i class="fa-solid fa-circle-exclamation"></i> Today
                    </span>
                @elseif($daysDiff === 1)
                    <span class="status-pill pill-tomorrow">
                        <i class="fa-solid fa-hourglass-half"></i> Tomorrow
                    </span>
                @elseif($daysDiff > 1)
                    <span class="status-pill pill-soon">
                        <i class="fa-solid fa-clock"></i> In {{ $daysDiff }}d
                    </span>
                @else
                    <span class="status-pill pill-today">
                        <i class="fa-solid fa-triangle-exclamation"></i> Expired
                    </span>
                @endif
            </div>

            {{-- Col 5: Actions --}}
            <div class="record-col-actions">
                <a href="{{ route('learner.payment', $detail->id) }}" class="btn-card-receipt" title="Collect Renewal Payment">
                    <i class="fas fa-credit-card"></i>
                </a>

                @if(!empty($learnerMobile))
                    <a href="{{ $waUrl }}" target="_blank" class="btn-card-profile" title="Send WhatsApp Reminder" style="color: #16a34a;">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                @endif
            </div>
        </div>
    @endforeach
@endforeach

@if(!$hasRecords)
<div class="report-empty-state">
    <i class="fa-solid fa-calendar-check empty-state-icon text-success"></i>
    <h6 class="empty-state-title">No Upcoming Expirations Found</h6>
    <p class="small text-muted mb-0">No learners have memberships expiring within the selected window.</p>
</div>
@endif
