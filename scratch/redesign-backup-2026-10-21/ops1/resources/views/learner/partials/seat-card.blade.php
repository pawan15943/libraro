{{--
    One seat on the seat map. Included from learner/seat.blade.php for every seat.
    Uses the parent's prefetched collections ($activeLearnersBySeat, $hoursSumBySeat, $futureBookingsBySeat,
    $pendingAmountCache, $dueAmountValueCache, $allBranchPlanTypes, $branchTotalHours, $total_hour, $lsmPT).
    Classes/data-attributes used by the seat map JS and learner/script.blade.php are kept as before.
--}}
@php
    $usersForSeat = $activeLearnersBySeat->get((string)$seatNo, collect());
    $sumofhourseat = $hoursSumBySeat->get((string)$seatNo, 0);
    $remainingHours = $total_hour - $sumofhourseat;

    $dayTypesMap = [
        1 => 'Full Day', 2 => '1st Half', 3 => '2nd Half', 4 => 'Hourly 1', 5 => 'Hourly 2', 6 => 'Hourly 3',
        7 => 'Hourly 4', 8 => 'All Day', 9 => 'Full Night', 10 => 'RESERVED', 11 => 'VIP'
    ];

    $seatShifts = [];
    $bookedDayTypeIds = [];

    if ($usersForSeat->count() > 0) {
        foreach ($usersForSeat as $u) {
            $isNonExpiry = (int) ($u->no_expiry ?? 0) === 1;
            if ($isNonExpiry) {
                $planDetails = ['status' => 'Non-Expired', 'class' => 'non_expiry_class'];
                $isExtended = false;
                $isExpiring = false;
            } else {
                $planDetails = getPlanStatusDetails($u->plan_end_date);
                $isExtended = ($planDetails['status'] === 'In Extension' || $planDetails['status'] === 'Extension ends today' || (isset($planDetails['class']) && in_array($planDetails['class'], ['extedned', 'extended'])));
                $isExpiring = (isset($planDetails['class']) && $planDetails['class'] === 'aboutToExpire');
            }
            $isFuture = (!empty($u->plan_start_date) && \Carbon\Carbon::parse($u->plan_start_date)->isFuture());

            // Skip expired bookings so only active seats/bookings are shown on seatmap (Non-expiry seats auto-renew and never expire)
            if (!$isNonExpiry && $planDetails['status'] === 'Expired') {
                continue;
            }

            $hasDue = $pendingAmountCache[$u->learner_detail_id] ?? (pending_amt($u->learner_detail_id) || paylater($u->learner_detail_id) || (int)($u->is_paid ?? 1) === 0 || (int)($u->payment_mode ?? 0) === 3);
            $dueVal = $dueAmountValueCache[$u->learner_detail_id] ?? 0;
            if ($hasDue && $dueVal <= 0) {
                $dueVal = (float)($u->plan_price_id ?? 0);
            }

            $bookedDayTypeIds[] = $u->day_type_id;
            $learnerName = !empty($u->name) ? $u->name : ('Student #' . $u->id);

            $isFrozenLearner = (int)($u->frozen_status ?? 0) === 1 || !empty($u->freeze_start_date);
            $formattedFreezeDate = !empty($u->freeze_start_date) ? \Carbon\Carbon::parse($u->freeze_start_date)->format('d/m/Y') : date('d/m/Y');
            $dayTypeLabelText = $isFrozenLearner ? ('Freezed on ' . $formattedFreezeDate) : ($dayTypesMap[$u->day_type_id] ?? ($u->plan_type_name ?? 'Booked'));

            $seatShifts[] = [
                'type' => $isFuture ? 'future' : 'booked',
                'user_id' => $u->id,
                'learner_detail_id' => $u->learner_detail_id,
                'name' => $learnerName,
                'mobile' => $u->mobile ?? '',
                'profile_picture' => $u->profile_picture,
                'plan_name' => $u->plan_type_name ?? 'Plan',
                'plan_type_id' => $u->plan_type_id,
                'day_type_id' => $u->day_type_id,
                'day_type_label' => $dayTypeLabelText,
                'frozen_status' => $u->frozen_status ?? 0,
                'freeze_start_date' => $u->freeze_start_date,
                'plan_start_date' => $u->plan_start_date,
                'plan_end_date' => $u->plan_end_date,
                'has_due' => $hasDue,
                'due_amount' => $dueVal,
                'is_non_expiry' => $isNonExpiry,
                'is_extended' => $isExtended,
                'is_future' => $isFuture,
                'is_expiring' => $isExpiring,
                'class' => $hasDue ? 'due_pending_class' : ($isNonExpiry ? 'non_expiry_class' : $planDetails['class']),
            ];
        }
    }

    $futureUser = $futureBookingsBySeat->get((string)$seatNo, collect())->first();
    if ($futureUser) {
        $seatShifts[] = [
            'type' => 'future',
            'user_id' => $futureUser->learner_id,
            'name' => !empty($futureUser->learner_name) ? $futureUser->learner_name : 'Future Booking',
            'mobile' => $futureUser->learner_mobile ?? '',
            'plan_type_id' => $futureUser->plan_type_id ?? null,
            'day_type_label' => 'From ' . \Carbon\Carbon::parse($futureUser->plan_start_date)->format('d/m/Y'),
            'has_due' => false,
            'is_non_expiry' => false,
            'is_extended' => false,
            'is_future' => true,
            'is_expiring' => false,
        ];
    }

    $bookedDayTypeIds = collect($usersForSeat)->pluck('day_type_id')->map(fn($v) => (int)$v)->toArray();
    $bookedPlanTypeIds = collect($usersForSeat)->pluck('plan_type_id')->map(fn($v) => (int)$v)->toArray();

    $hasAnyDaytimeBooked = false;
    foreach ($bookedDayTypeIds as $dt) {
        if (in_array((int)$dt, [1, 2, 3, 4, 5, 6, 7])) { $hasAnyDaytimeBooked = true; break; }
    }

    $seatUsedHours = (float) ($hoursSumBySeat->get((string)$seatNo, 0) ?: $hoursSumBySeat->get((int)$seatNo, 0));

    $is24HoursBooked = (
        in_array(8, $bookedDayTypeIds) ||
        in_array(10, $bookedDayTypeIds) ||
        in_array(11, $bookedDayTypeIds) ||
        (in_array(1, $bookedDayTypeIds) && in_array(9, $bookedDayTypeIds)) ||
        (in_array(2, $bookedDayTypeIds) && in_array(3, $bookedDayTypeIds) && in_array(9, $bookedDayTypeIds)) ||
        ($branchTotalHours < 24 && in_array(1, $bookedDayTypeIds)) ||
        ($branchTotalHours > 0 && $seatUsedHours >= $branchTotalHours)
    );

    if (!$is24HoursBooked) {
        if ($allBranchPlanTypes->count() > 0) {
            foreach ($allBranchPlanTypes as $pt) {
                // 1. Cannot book the same exact plan type already booked on this seat
                if (in_array($pt->id, $bookedPlanTypeIds)) continue;
                // 2. Cannot book the same day_type already booked (unless custom 0)
                if ($pt->day_type_id != 0 && in_array($pt->day_type_id, $bookedDayTypeIds)) continue;
                // 3. 24-hr shifts require seat to be completely unbooked
                if (count($bookedDayTypeIds) > 0 && in_array($pt->day_type_id, [8, 10, 11])) continue;
                // 4. If branch open hours < 24, All Day (8) and Full Night (9) are not allowed
                if ($branchTotalHours < 24 && in_array($pt->day_type_id, [8, 9])) continue;
                // 5. If any daytime shift is booked, Full Day (1) cannot be booked
                if ($pt->day_type_id == 1 && $hasAnyDaytimeBooked) continue;
                // 6. If Full Day (1) is booked, daytime half/hourly shifts cannot be booked
                if (in_array(1, $bookedDayTypeIds) && in_array($pt->day_type_id, [2, 3, 4, 5, 6, 7])) continue;

                $seatShifts[] = ['type' => 'available', 'label' => $pt->name, 'day_type_id' => $pt->day_type_id, 'plan_type_id' => $pt->id];
            }
        } else {
            if (!in_array(1, $bookedDayTypeIds) && !in_array(2, $bookedDayTypeIds)) {
                $seatShifts[] = ['type' => 'available', 'label' => '1st Half (Morning)', 'day_type_id' => 2];
            }
            if (!in_array(1, $bookedDayTypeIds) && !in_array(3, $bookedDayTypeIds)) {
                $seatShifts[] = ['type' => 'available', 'label' => '2nd Half (Evening)', 'day_type_id' => 3];
            }
            if (!in_array(9, $bookedDayTypeIds) && !in_array(8, $bookedDayTypeIds)) {
                $seatShifts[] = ['type' => 'available', 'label' => 'Full Night Shift', 'day_type_id' => 9];
            }
            if (count($bookedDayTypeIds) === 0) {
                array_unshift($seatShifts, ['type' => 'available', 'label' => 'Full Day (24 Hrs)', 'day_type_id' => 1]);
            }
        }
    }

    $studentNames = [];
    $shiftLabels = [];
    $hasAvailableShift = $hasBookedShift = $hasDueShift = $hasExpiringShift = $hasExtendedShift = $hasFutureShift = $hasNonExpiredShift = false;

    foreach ($seatShifts as $sh) {
        if ($sh['type'] === 'available') {
            $hasAvailableShift = true;
            if (!empty($sh['label'])) $shiftLabels[] = strtolower($sh['label']);
        } elseif ($sh['type'] === 'future' || !empty($sh['is_future'])) {
            $hasFutureShift = true;
            if (!empty($sh['name'])) $studentNames[] = strtolower($sh['name']);
            if (!empty($sh['day_type_label'])) $shiftLabels[] = strtolower($sh['day_type_label']);
        } elseif ($sh['type'] === 'booked') {
            $hasBookedShift = true;
            if (!empty($sh['name'])) $studentNames[] = strtolower($sh['name']);
            if (!empty($sh['day_type_label'])) $shiftLabels[] = strtolower($sh['day_type_label']);
        }
        if (!empty($sh['plan_name'])) $shiftLabels[] = strtolower($sh['plan_name']);
        if (!empty($sh['has_due'])) $hasDueShift = true;
        if (!empty($sh['is_expiring'])) $hasExpiringShift = true;
        if (!empty($sh['is_extended'])) $hasExtendedShift = true;
        if (!empty($sh['is_non_expiry'])) $hasNonExpiredShift = true;
    }

    // Overall seat state (top-right dot + card accent)
    $seatTone = 'free';
    if ($hasDueShift) $seatTone = 'due';
    elseif ($hasNonExpiredShift) $seatTone = 'nonexp';
    elseif ($hasExtendedShift) $seatTone = 'ext';
    elseif ($hasExpiringShift) $seatTone = 'exp';
    elseif ($hasFutureShift) $seatTone = 'future';
    elseif ($hasBookedShift) $seatTone = $hasAvailableShift ? 'part' : 'booked';

    $isWholeDayBooked = in_array(1, $bookedDayTypeIds) || in_array(8, $bookedDayTypeIds) || in_array(10, $bookedDayTypeIds) || in_array(11, $bookedDayTypeIds) || ($is24HoursBooked && count($usersForSeat) === 1);
    $shiftCount = count($seatShifts);
    $seatLabel = $seatLabelText ?? ('Seat ' . $seatNo);
@endphp

<div class="seat-card-item lsm-seat is-{{ $seatTone }} {{ ($hasDueShift || $hasExtendedShift) ? 'seat-card-alert' : '' }}"
     id="seatCard_{{ $seatNo }}"
     data-seat-no="{{ $seatNo }}"
     data-student-names="{{ implode(' ', $studentNames) }}"
     data-shifts="{{ implode(' ', array_unique($shiftLabels)) }}"
     data-has-available="{{ $hasAvailableShift ? '1' : '0' }}"
     data-has-booked="{{ $hasBookedShift ? '1' : '0' }}"
     data-has-due="{{ $hasDueShift ? '1' : '0' }}"
     data-has-expiring="{{ $hasExpiringShift ? '1' : '0' }}"
     data-has-extended="{{ $hasExtendedShift ? '1' : '0' }}"
     data-has-future="{{ $hasFutureShift ? '1' : '0' }}"
     data-has-non-expired="{{ $hasNonExpiredShift ? '1' : '0' }}"
     tabindex="0" role="button" aria-label="{{ $seatLabel }}">

    <div class="lsm-seat-top">
        <span class="lsm-seat-no">{{ $seatLabel }}</span>
        @if(!$isWholeDayBooked && $shiftCount > 1)
        <span class="lsm-seat-count" title="{{ $shiftCount }} shifts"><span class="current-shift-num">1</span>/{{ $shiftCount }}</span>
        @endif
        <i class="lsm-seat-dot" aria-hidden="true"></i>
    </div>

    <div class="shift-slides-wrapper">
        @foreach($seatShifts as $sIdx => $shift)
        @php
            $isNonExpiry = !empty($shift['is_non_expiry']) || (isset($shift['class']) && $shift['class'] === 'non_expiry_class');
            if ($isNonExpiry) { $isExt = false; $isExpiring = false; }
            else {
                $isExt = (isset($shift['class']) && ($shift['class'] === 'extedned' || $shift['class'] === 'extended')) || !empty($shift['is_extended']);
                $isExpiring = !empty($shift['is_expiring']) || (isset($shift['class']) && $shift['class'] === 'aboutToExpire');
            }
            $hasDue = !empty($shift['has_due']);
            $isFuture = ($shift['type'] === 'future' || !empty($shift['is_future']));
            $isAvail = $shift['type'] === 'available';

            $tone = $isAvail ? 'free' : ($hasDue ? 'due' : ($isNonExpiry ? 'nonexp' : ($isExt ? 'ext' : ($isExpiring ? 'exp' : ($isFuture ? 'future' : 'booked')))));
            $shiftStatusColor = ['free' => '#00793d', 'due' => '#de3b3d', 'nonexp' => '#bf2a82', 'ext' => '#9a3412', 'exp' => '#eb881f', 'future' => '#b08900', 'booked' => '#1d3c86'][$tone];
            $shiftStatusText = $isAvail ? 'Available' : ($hasDue ? 'Fee Overdue' : ($isNonExpiry ? 'Non-Expired' : ($isExt ? 'In Extension' : ($isExpiring ? 'Expiring Soon' : ($isFuture ? 'Future Booked' : 'Active Booked')))));

            $pt = !empty($shift['plan_type_id']) ? ($lsmPT[$shift['plan_type_id']] ?? null) : null;
            $shiftName = $isAvail ? ($shift['label'] ?? 'Available') : ($shift['plan_name'] ?? ($shift['day_type_label'] ?? 'Booked'));
            $shiftTime = $pt['time'] ?? '';
            $shiftColor = $pt['color'] ?? '#7b7c83';
            $initials = collect(explode(' ', trim((string) ($shift['name'] ?? ''))))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: strtoupper(substr($shift['name'] ?? '', 0, 2));
            $shortName = $isAvail ? '' : (function ($n) { $p = preg_split('/\s+/', trim((string) $n)); return count($p) > 1 ? $p[0] . ' ' . mb_substr(end($p), 0, 1) . '.' : $n; })($shift['name'] ?? '');
            $hasPhoto = !$isAvail && !$isFuture && !empty($shift['profile_picture']);
            $bkIsFrozen = (int)($shift['frozen_status'] ?? 0) === 1 || !empty($shift['freeze_start_date']);
        @endphp
        <div class="shift-slide-item lsm-slide is-{{ $tone }} {{ $sIdx === 0 ? 'active-slide' : 'd-none' }}"
             data-shift-idx="{{ $sIdx }}"
             data-type="{{ $shift['type'] }}"
             data-user-id="{{ $shift['user_id'] ?? '' }}"
             data-name="{{ $shift['name'] ?? 'Available' }}"
             data-mobile="{{ $shift['mobile'] ?? '' }}"
             data-shift-name="{{ $isAvail ? ($shift['label'] ?? 'Available') : ($shift['day_type_label'] ?? ($shift['plan_name'] ?? 'Booked')) }}"
             data-plan-end="{{ !empty($shift['plan_end_date']) ? \Carbon\Carbon::parse($shift['plan_end_date'])->format('d M Y') : '' }}"
             data-due-amount="{{ !empty($shift['due_amount']) ? number_format($shift['due_amount']) : '0' }}"
             data-has-due="{{ $hasDue ? '1' : '0' }}"
             data-status-label="{{ $shiftStatusText }}"
             data-status-color="{{ $shiftStatusColor }}">

            <div class="lsm-av-wrap">
                @if($isAvail)
                    <span class="lsm-av lsm-av--free avatar-circle-available" title="Available for booking"><x-lb-icon name="armchair" /></span>
                @elseif($shift['type'] === 'future')
                    <span class="lsm-av lsm-av--future avatar-circle-future" title="Future booking: {{ $shift['name'] }}"><x-lb-icon name="calendar-clock" /></span>
                @else
                    @php
                        $avatarDotClass = $hasDue ? 'seat-avatar-dot due-dot seatBlink' : ($isNonExpiry ? 'seat-avatar-dot non-expiry-dot' : ($isExt ? 'seat-avatar-dot extension-dot seatBlink' : ($isExpiring ? 'seat-avatar-dot expiring-dot seatBlink' : 'seat-avatar-dot active-dot')));
                    @endphp
                    @if($hasPhoto)
                        <a href="{{ asset($shift['profile_picture']) }}" class="lsm-av lsm-av--photo view-image" title="View {{ $shift['name'] }} photo"><img src="{{ asset($shift['profile_picture']) }}" alt="{{ $shift['name'] }}" loading="lazy" decoding="async"></a>
                    @else
                        <span class="lsm-av lsm-av--init">{{ $initials }}</span>
                    @endif
                    <span class="{{ $avatarDotClass }} lsm-av-dot" title="{{ $shiftStatusText }}"></span>
                @endif
            </div>

            <div class="shift-info">
                @if($isAvail)
                    <b class="seat-student-name lsm-name lsm-name--free">Free</b>
                @else
                    <b class="seat-student-name lsm-name" title="{{ $shift['name'] }}">{{ $shortName }}</b>
                @endif
                @if($hasDue && !empty($shift['due_amount']) && $shift['due_amount'] > 0)
                    <span class="seat-due-amount-pill lsm-tag lsm-tag--due" title="Due: ₹{{ number_format($shift['due_amount']) }}">₹{{ number_format($shift['due_amount']) }} due</span>
                @elseif(!$isAvail && $tone !== 'booked')
                    <span class="lsm-tag lsm-tag--{{ $tone }} {{ $bkIsFrozen ? 'is-frozen' : '' }}">{{ $bkIsFrozen ? $shift['day_type_label'] : $shiftStatusText }}</span>
                @elseif(!$isAvail && $bkIsFrozen)
                    <span class="lsm-tag lsm-tag--due is-frozen">{{ $shift['day_type_label'] }}</span>
                @endif

                <div class="lsm-shift">
                    @if($shiftCount > 1)
                    <button type="button" class="shift-prev-btn lsm-arrow" data-seat="{{ $seatNo }}" title="Previous shift" aria-label="Previous shift"><x-lb-icon name="chevron-left" /></button>
                    @endif
                    <span class="lsm-shift-t seat-shift-label" title="{{ $shiftName }}{{ $shiftTime ? ' · ' . $shiftTime : '' }}">
                        <span class="lsm-shift-n"><i style="background: {{ $shiftColor }}"></i><span>{{ $shiftName }}</span></span>
                        @if($shiftTime)<small>{{ $shiftTime }}</small>@endif
                    </span>
                    @if($shiftCount > 1)
                    <button type="button" class="shift-next-btn lsm-arrow" data-seat="{{ $seatNo }}" title="Next shift" aria-label="Next shift"><x-lb-icon name="chevron-right" /></button>
                    @endif
                </div>
            </div>

            <div class="card-action-bar lsm-act">
                @if($isAvail)
                <button type="button" class="lsm-btn lsm-btn--book first_popup"
                        data-bs-toggle="modal" data-bs-target="#seatAllotmentModal"
                        data-id="{{ $seatNo }}"
                        data-seat_no="{{ $seatNo }}"
                        data-plan_type_id="{{ $shift['plan_type_id'] ?? '' }}"
                        data-day_type_id="{{ $shift['day_type_id'] ?? '' }}"><x-lb-icon name="plus" />Book</button>
                @else
                <button type="button" class="lsm-btn lsm-btn--view second_popup"
                        data-bs-toggle="modal" data-bs-target="#seatAllotmentModal2" data-seat_no="{{ $seatNo }}" data-userid="{{ $shift['user_id'] }}">View</button>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    @if($shiftCount > 1)
    <div class="shift-dots-row lsm-dots">
        @foreach($seatShifts as $sIdx => $shift)
        @php
            $dotTone = $shift['type'] === 'available' ? 'free' : (!empty($shift['has_due']) ? 'due' : ((!empty($shift['is_non_expiry']) || (isset($shift['class']) && $shift['class'] === 'non_expiry_class')) ? 'nonexp' : ((!empty($shift['is_extended']) || (isset($shift['class']) && in_array($shift['class'], ['extedned', 'extended']))) ? 'ext' : ((!empty($shift['is_expiring']) || (isset($shift['class']) && $shift['class'] === 'aboutToExpire')) ? 'exp' : (($shift['type'] === 'future' || !empty($shift['is_future'])) ? 'future' : 'booked')))));
        @endphp
        <span class="shift-dot lsm-d is-{{ $dotTone }} {{ $sIdx === 0 ? 'active-dot' : '' }}" data-seat="{{ $seatNo }}" data-shift-idx="{{ $sIdx }}" role="button" aria-label="Show shift {{ $sIdx + 1 }}"></span>
        @endforeach
    </div>
    @endif
</div>
