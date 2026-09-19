@php
    use Carbon\Carbon;
@endphp

@forelse($learners as $index => $value)
@php
    $learnerObj = $value->learner;
    $learnerName = $learnerObj->name ?? ('Learner #' . ($value->learner_id ?? ''));
    
    // Safely decrypt contact info
    $learnerMobile = '';
    if (!empty($learnerObj->mobile)) {
        try {
            $learnerMobile = decryptData($learnerObj->mobile);
        } catch (\Exception $e) {
            $learnerMobile = $learnerObj->mobile;
        }
    }

    $learnerSeat = $learnerObj->seat_no ?? null;
    $seatDisplay = $learnerSeat ? getSeatDisplayByMainNo($learnerSeat) : null;
    if (empty($seatDisplay)) {
        $seatDisplay = 'General';
    }

    // Operation Configuration & Badges
    $op = strtolower(trim((string)($value->operation ?? '')));
    $opLabel = 'Activity';
    $opClass = 'op-default';
    $opIcon = 'fa-clock-rotate-left';

    switch ($op) {
        case 'swapseat':
            $opLabel = 'Seat Swap';
            $opClass = 'op-swap';
            $opIcon = 'fa-chair';
            break;
        case 'changeplan':
            $opLabel = 'Change Plan';
            $opClass = 'op-plan';
            $opIcon = 'fa-arrows-rotate';
            break;
        case 'learnerupgrade':
            $opLabel = 'Plan Upgrade';
            $opClass = 'op-upgrade';
            $opIcon = 'fa-arrow-up-right-dots';
            break;
        case 'renewseat':
            $opLabel = 'Renew Seat';
            $opClass = 'op-renew';
            $opIcon = 'fa-calendar-check';
            break;
        case 'closeseat':
            $opLabel = 'Close Seat';
            $opClass = 'op-close';
            $opIcon = 'fa-door-closed';
            break;
        case 'reactive':
            $opLabel = 'Reactivate';
            $opClass = 'op-renew';
            $opIcon = 'fa-power-off';
            break;
        case 'deleteseat':
            $opLabel = 'Delete Seat';
            $opClass = 'op-close';
            $opIcon = 'fa-trash';
            break;
        case 'restoreseat':
            $opLabel = 'Restore Seat';
            $opClass = 'op-swap';
            $opIcon = 'fa-trash-arrow-up';
            break;
        case 'freezeplan':
            $opLabel = 'Freeze Plan';
            $opClass = 'op-freeze';
            $opIcon = 'fa-snowflake';
            break;
        case 'unfreezeplan':
            $opLabel = 'Unfreeze Plan';
            $opClass = 'op-freeze';
            $opIcon = 'fa-sun';
            break;
        case 'giftdays':
            $opLabel = 'Gift Days';
            $opClass = 'op-upgrade';
            $opIcon = 'fa-gift';
            break;
        case 'edit':
            $opLabel = 'Edit Details';
            $opClass = 'op-default';
            $opIcon = 'fa-pen-to-square';
            break;
        default:
            $opLabel = !empty($value->operation) ? ucwords(str_replace(['_', '-'], ' ', $value->operation)) : 'Logged Action';
            $opClass = 'op-default';
            $opIcon = 'fa-clock-rotate-left';
            break;
    }

    $createdAt = $value->created_at ? Carbon::parse($value->created_at) : null;
    $dateFormatted = $createdAt ? $createdAt->format('d M Y') : '-';
    $timeFormatted = $createdAt ? $createdAt->format('h:i A') : '';
    $rawDateStr = $createdAt ? $createdAt->format('Y-m-d H:i:s') : '';

    $oldVal = $value->old_value ?? '';
    $newVal = $value->new_value ?? '';

    // Summary text
    $summaryText = $value->summary;
    if (empty($summaryText)) {
        if ($op === 'swapseat' && !empty($oldVal) && !empty($newVal)) {
            $summaryText = "Seat #{$oldVal} → Seat #{$newVal}";
        } elseif (!empty($value->field_updated)) {
            $summaryText = 'Updated: ' . ucwords(str_replace('_', ' ', $value->field_updated));
        } else {
            $summaryText = $opLabel . ' completed';
        }
    }

    // Base64 encode for secure HTML data transfer
    $b64Old = base64_encode($oldVal);
    $b64New = base64_encode($newVal);

    $searchKeywords = strtolower(
        $learnerName . ' ' .
        $learnerMobile . ' ' .
        ($seatDisplay ? 'seat ' . $seatDisplay : 'general') . ' ' .
        $opLabel . ' ' .
        $summaryText . ' ' .
        $dateFormatted
    );
@endphp

<div class="activity-record-card"
     data-search="{{ $searchKeywords }}"
     data-seat="{{ $seatDisplay }}"
     data-name="{{ $learnerName }}"
     data-mobile="{{ $learnerMobile }}"
     data-operation="{{ $opLabel }}"
     data-op-class="{{ $opClass }}"
     data-op-icon="{{ $opIcon }}"
     data-summary="{{ $summaryText }}"
     data-field="{{ $value->field_updated ?? 'Field' }}"
     data-old-b64="{{ $b64Old }}"
     data-new-b64="{{ $b64New }}"
     data-date="{{ $dateFormatted }} {{ $timeFormatted }}">

    {{-- Mobile-Only Top Row (< 992px) --}}
    <div class="record-card-mobile-top d-flex d-lg-none">
        <span class="badge-activity-op {{ $opClass }}">
            <i class="fa-solid {{ $opIcon }}"></i> {{ $opLabel }}
        </span>
        <div class="text-muted small">
            <i class="fa-regular fa-calendar me-1"></i>{{ $dateFormatted }} {{ $timeFormatted }}
        </div>
    </div>

    {{-- Col 1: Learner Details with Seat No. above student name --}}
    <div class="record-col-learner">
        <div class="record-learner-avatar">
            {{ strtoupper(substr($learnerName, 0, 1)) }}
        </div>
        <div class="record-learner-text">
            {{-- Small block-letter Seat Tag directly above student name --}}
            <div class="record-seat-tag {{ ($seatDisplay !== 'General') ? '' : 'seat-general' }}">
                @if($seatDisplay !== 'General')
                    <i class="fa-solid fa-chair me-1"></i>SEAT {{ strtoupper($seatDisplay) }}
                @else
                    <i class="fa-solid fa-chair me-1"></i>GENERAL
                @endif
            </div>

            @if(!empty($value->learner_id))
                <a href="{{ route('learners.show', $value->learner_id) }}" class="record-learner-name" title="View Learner Profile">
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
                </div>
            @endif
        </div>
    </div>

    {{-- Desktop Col 2: Activity Name & Badge (>= 992px) --}}
    <div class="d-none d-lg-block text-center">
        <span class="badge-activity-op {{ $opClass }}">
            <i class="fa-solid {{ $opIcon }}"></i> {{ $opLabel }}
        </span>
    </div>

    {{-- Desktop Col 3: Summary / Key Note (>= 992px) --}}
    <div class="d-none d-lg-block text-center text-truncate" title="{{ $summaryText }}">
        <span class="record-summary-text">{{ $summaryText }}</span>
    </div>

    {{-- Desktop Col 4: Activity Log Date & Time (>= 992px) --}}
    <div class="d-none d-lg-block text-center">
        <span class="record-activity-date">{{ $dateFormatted }}</span>
        <span class="record-activity-time">{{ $timeFormatted }}</span>
    </div>

    {{-- Desktop Col 5: Modal Trigger Button (>= 992px) --}}
    <div class="d-none d-lg-block text-center">
        <button type="button" class="btn-open-diff btn-view-changes" title="View Value Changes">
            <i class="fa-solid fa-code-compare me-1"></i> View Changes
        </button>
    </div>

    {{-- Col 6: Profile Action (>= 992px) --}}
    <div class="record-col-actions d-none d-lg-flex">
        @if(!empty($value->learner_id))
            <a href="{{ route('learners.show', $value->learner_id) }}" class="btn-action-profile" data-bs-toggle="tooltip" title="View Learner Profile">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
            </a>
        @endif
    </div>

    {{-- Mobile-Only Summary (< 992px) --}}
    <div class="d-block d-lg-none mt-2 pt-2 border-top">
        <div class="small text-muted mb-2">
            <span class="fw-semibold text-dark">Summary:</span> {{ $summaryText }}
        </div>
        <div class="d-flex align-items-center justify-content-between gap-2">
            <button type="button" class="btn-open-diff btn-view-changes w-100 py-1" title="View Value Changes">
                <i class="fa-solid fa-code-compare me-1"></i> View Value Changes
            </button>
            @if(!empty($value->learner_id))
                <a href="{{ route('learners.show', $value->learner_id) }}" class="btn-action-profile" title="View Profile">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </a>
            @endif
        </div>
    </div>

</div>
@empty
<div class="report-empty-state">
    <i class="fa-solid fa-folder-open empty-state-icon"></i>
    <h6 class="empty-state-title">No Activity Logs Found for Selected Criteria</h6>
    <p class="small text-muted mb-3">
        No administrative or learner actions matched the selected filters.
    </p>
    <button type="button" class="btn btn-filter-apply" id="btnEmptyStateAll">
        <i class="fa-solid fa-list-check me-1"></i> View All Activities
    </button>
</div>
@endforelse
