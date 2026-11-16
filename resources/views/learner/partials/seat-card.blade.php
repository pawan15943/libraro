{{--
    One seat on the seat map. Included from learner/seat.blade.php for every seat.
    Uses the parent's prefetched collections ($activeLearnersBySeat, $hoursSumBySeat, $futureBookingsBySeat,
    $pendingAmountCache, $dueAmountValueCache, $allBranchPlanTypes, $branchTotalHours, $total_hour, $lsmPT).
    Classes/data-attributes used by the seat map JS and learner/script.blade.php are kept as before.
--}}
@php
    $lsmShowDob = $lsmShowDob ?? true;
    $usersForSeat = $activeLearnersBySeat->get((string)$seatNo, collect());
    $sumofhourseat = $hoursSumBySeat->get((string)$seatNo, 0);
    $remainingHours = $total_hour - $sumofhourseat;

    $dayTypesMap = [
        1 => 'Full Day', 2 => '1st Half', 3 => '2nd Half', 4 => 'Hourly 1', 5 => 'Hourly 2', 6 => 'Hourly 3',
        7 => 'Hourly 4', 8 => 'All Day', 9 => 'Full Night', 10 => 'RESERVED', 11 => 'VIP'
    ];

    $seatShifts = [];
    // Bookings that still hold the seat (not expired, already started). Expired plans
    // free the seat right away, so availability is worked out from these only.
    $activeBookingsForSeat = [];
    $seatUsedHours = 0.0;

    if ($usersForSeat->count() > 0) {
        foreach ($usersForSeat as $u) {
            $isNonExpiry = (int) ($u->no_expiry ?? 0) === 1;
            if ($isNonExpiry) {
                $planDetails = ['status' => 'Non-Expired', 'class' => 'non_expiry_class'];
                $isExtended = false;
                $isExpiring = false;
            } else {
                $planDetails = getPlanStatusDetails($u->plan_end_date, $branchExtendDays ?? null);
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

            if (!$isFuture) {
                $uHours = (float) ($u->hour ?? 0);
                if ($uHours <= 0) $uHours = (float) ($u->slot_hours ?? 0);
                if ($uHours <= 0 && !empty($u->start_time) && !empty($u->end_time)) $uHours = durationInHours($u->start_time, $u->end_time);
                if ($uHours <= 0) {
                    $uHours = match ((int) $u->day_type_id) {
                        1 => ($branchTotalHours < 24 ? $branchTotalHours : 16),
                        2, 3, 9 => 8,
                        8, 10, 11 => 24,
                        4, 5, 6, 7 => 4,
                        default => 0,
                    };
                }
                $seatUsedHours += $uHours;
                $activeBookingsForSeat[] = [
                    'plan_type_id' => (int) $u->plan_type_id,
                    'day_type_id' => (int) $u->day_type_id,
                    'start_time' => $u->start_time ?? null,
                    'end_time' => $u->end_time ?? null,
                ];
            }
            $learnerName = !empty($u->name) ? $u->name : ('Student #' . $u->id);

            $isFrozenLearner = (int)($u->frozen_status ?? 0) === 1 || !empty($u->freeze_start_date);
            $formattedFreezeDate = !empty($u->freeze_start_date) ? \Carbon\Carbon::parse($u->freeze_start_date)->format('d/m/Y') : date('d/m/Y');
            $dayTypeLabelText = $isFrozenLearner ? ('Freezed on ' . $formattedFreezeDate) : ($dayTypesMap[$u->day_type_id] ?? ($u->plan_type_name ?? 'Booked'));
            // Extra shift of a multi-shift booking (main booking opens on View)
            if (!empty($u->slot_id)) {
                $dayTypeLabelText .= ' · Extra shift';
            }

            $seatShifts[] = [
                'type' => $isFuture ? 'future' : 'booked',
                'user_id' => $u->id,
                'learner_detail_id' => $u->learner_detail_id,
                'name' => $learnerName,
                'mobile' => $u->mobile ?? '',
                'profile_picture' => $u->profile_picture,
                'is_bday' => $lsmShowDob && !empty($u->dob) && (function ($d) { try { return \Carbon\Carbon::parse($d)->format('m-d') === now()->format('m-d'); } catch (\Throwable $e) { return false; } })($u->dob),
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
                'advance_amount' => $hasDue ? 0 : (float) (($advanceCache ?? [])[(int) $u->id] ?? 0),
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

    $bookedDayTypeIds = array_column($activeBookingsForSeat, 'day_type_id');
    $bookedPlanTypeIds = array_column($activeBookingsForSeat, 'plan_type_id');

    $hasAnyDaytimeBooked = false;
    foreach ($activeBookingsForSeat as $ab) {
        if (in_array((int) $ab['day_type_id'], [1, 2, 3, 4, 5, 6, 7])) { $hasAnyDaytimeBooked = true; break; }
        if (!empty($ab['start_time']) && !empty($ab['end_time']) && planTypeTimesOverlap($ab['start_time'], $ab['end_time'], '06:00', '22:00')) { $hasAnyDaytimeBooked = true; break; }
    }

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
                if (in_array((int) $pt->id, $bookedPlanTypeIds)) continue;
                // 2. Cannot book the same day_type already booked (unless custom 0)
                if ((int) $pt->day_type_id !== 0 && in_array((int) $pt->day_type_id, $bookedDayTypeIds)) continue;
                // 3. 24-hr shifts require seat to be completely unbooked
                if (count($activeBookingsForSeat) > 0 && in_array((int) $pt->day_type_id, [8, 10, 11])) continue;
                // 4. If branch open hours < 24, All Day (8) and Full Night (9) are not allowed
                if ($branchTotalHours < 24 && in_array((int) $pt->day_type_id, [8, 9])) continue;
                // 5. If any daytime shift is booked, Full Day (1) cannot be booked
                if ((int) $pt->day_type_id === 1 && $hasAnyDaytimeBooked) continue;
                // 6. If Full Day (1) is booked, daytime half/hourly shifts cannot be booked
                if (in_array(1, $bookedDayTypeIds) && in_array((int) $pt->day_type_id, [2, 3, 4, 5, 6, 7])) continue;
                // 7. Time overlap with a booking that still holds the seat
                $hasOverlap = false;
                foreach ($activeBookingsForSeat as $ab) {
                    if (!empty($pt->start_time) && !empty($pt->end_time) && !empty($ab['start_time']) && !empty($ab['end_time'])
                        && planTypeTimesOverlap($ab['start_time'], $ab['end_time'], $pt->start_time, $pt->end_time)) { $hasOverlap = true; break; }
                }
                if ($hasOverlap) continue;
                // 8. Shift hours must fit in the branch's remaining hours
                $ptHours = (float) ($pt->slot_hours ?? 0);
                if ($ptHours <= 0 && !empty($pt->start_time) && !empty($pt->end_time)) $ptHours = durationInHours($pt->start_time, $pt->end_time);
                if ($ptHours <= 0) {
                    $ptHours = match ((int) $pt->day_type_id) {
                        1 => ($branchTotalHours < 24 ? $branchTotalHours : 16),
                        2, 3, 9 => 8,
                        8, 10, 11 => 24,
                        4, 5, 6, 7 => 4,
                        default => 0,
                    };
                }
                if ($branchTotalHours > 0 && ($seatUsedHours + $ptHours) > $branchTotalHours) continue;

                $seatShifts[] = ['type' => 'available', 'label' => $pt->name, 'day_type_id' => $pt->day_type_id, 'plan_type_id' => $pt->id];
            }
        } else {
            if (!in_array(1, $bookedDayTypeIds) && !in_array(2, $bookedDayTypeIds) && ($seatUsedHours + 8 <= $branchTotalHours)) {
                $seatShifts[] = ['type' => 'available', 'label' => '1st Half (Morning)', 'day_type_id' => 2];
            }
            if (!in_array(1, $bookedDayTypeIds) && !in_array(3, $bookedDayTypeIds) && ($seatUsedHours + 8 <= $branchTotalHours)) {
                $seatShifts[] = ['type' => 'available', 'label' => '2nd Half (Evening)', 'day_type_id' => 3];
            }
            if ($branchTotalHours >= 24 && !in_array(9, $bookedDayTypeIds) && !in_array(8, $bookedDayTypeIds) && ($seatUsedHours + 8 <= $branchTotalHours)) {
                $seatShifts[] = ['type' => 'available', 'label' => 'Full Night Shift', 'day_type_id' => 9];
            }
            if (count($activeBookingsForSeat) === 0) {
                array_unshift($seatShifts, ['type' => 'available', 'label' => ($branchTotalHours >= 24 ? 'Full Day (24 Hrs)' : 'Full Day (' . $branchTotalHours . ' Hrs)'), 'day_type_id' => 1]);
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

    $isWholeDayBooked = in_array(1, $bookedDayTypeIds) || in_array(8, $bookedDayTypeIds) || in_array(10, $bookedDayTypeIds) || in_array(11, $bookedDayTypeIds) || ($is24HoursBooked && count($activeBookingsForSeat) === 1);
    $shiftCount = count($seatShifts);
    $seatLabel = $seatLabelText ?? ('Seat ' . $seatNo);
    // how many shifts of this seat are taken and how many are still free (count badge tooltip)
    $lsmFreeCount = collect($seatShifts)->where('type', 'available')->count();
    $lsmTakenCount = $shiftCount - $lsmFreeCount;
@endphp

@php
    // Per-slide display data (shift label/time/colour + state) used by the card and the seat map JS
    $lsmSlides = [];
    foreach ($seatShifts as $sIdx => $shift) {
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
        $statusText = $isAvail ? 'Available' : ($hasDue ? 'Fee Overdue' : ($isNonExpiry ? 'Non-Expired' : ($isExt ? 'In Extension' : ($isExpiring ? 'Expiring Soon' : ($isFuture ? 'Future Booked' : 'Active Booked')))));
        $pt = !empty($shift['plan_type_id']) ? ($lsmPT[$shift['plan_type_id']] ?? null) : null;
        $lsmSlides[] = [
            'shift' => $shift, 'tone' => $tone, 'status' => $statusText, 'avail' => $isAvail, 'future' => $isFuture,
            'due' => $hasDue, 'ext' => $isExt, 'exp' => $isExpiring, 'nonexp' => $isNonExpiry,
            'bday' => !$isAvail && !empty($shift['is_bday']),
            'frozen' => !$isAvail && ((int)($shift['frozen_status'] ?? 0) === 1 || !empty($shift['freeze_start_date'])),
            'sname' => $isAvail ? ($shift['label'] ?? 'Available') : ($shift['plan_name'] ?? ($shift['day_type_label'] ?? 'Booked')),
            'stime' => $pt['time'] ?? '',
            'scolor' => $pt['color'] ?? '#7b7c83',
        ];
    }
    $lsm0 = $lsmSlides[0] ?? ['tone' => 'free', 'bday' => false, 'sname' => '', 'stime' => '', 'scolor' => '#7b7c83'];
    $lsmAnyBday = collect($lsmSlides)->contains('bday', true);
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
     data-has-bday="{{ $lsmAnyBday ? '1' : '0' }}"
     data-cur="{{ $lsm0['tone'] }}" data-cur-bday="{{ $lsm0['bday'] ? '1' : '0' }}"
     tabindex="0" role="button" aria-label="{{ $seatLabel }}">

    <i class="lsm-bday-bar" aria-hidden="true"></i>
    <div class="lsm-seat-top">
        <span class="lsm-seat-no">{{ $seatLabel }}</span>
        <span class="lsm-flags">
            <span class="lsm-flag-cake" title="Birthday today"><x-lb-icon name="cake" /></span>
            <i class="lsm-blink lsm-blink--due" title="Payment due"></i>
            <i class="lsm-blink lsm-blink--ext" title="Extension due"></i>
            @if($shiftCount > 0)
                {{-- shift shown / shifts of this seat; the tooltip says how many are booked and free --}}
                <span class="lsm-seat-count is-on" title="{{ $lsmTakenCount }} booked · {{ $lsmFreeCount }} available" aria-label="{{ $lsmTakenCount }} booked, {{ $lsmFreeCount }} available"><span class="current-shift-num">1</span>@if($shiftCount > 1)<small>/{{ $shiftCount }}</small>@endif</span>
            @endif
        </span>
    </div>

    <div class="lsm-view">
        <div class="shift-slides-wrapper lsm-track">
        @foreach($lsmSlides as $sIdx => $sl)
        @php
            $shift = $sl['shift']; $isAvail = $sl['avail']; $tone = $sl['tone'];
            $initials = collect(explode(' ', trim((string) ($shift['name'] ?? ''))))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: strtoupper(substr($shift['name'] ?? '', 0, 2));
            $shortName = $isAvail ? '' : (function ($n) { $p = preg_split('/\s+/', trim((string) $n)); return count($p) > 1 ? $p[0] . ' ' . mb_substr(end($p), 0, 1) . '.' : $n; })($shift['name'] ?? '');
            $hasPhoto = !$isAvail && !$sl['future'] && !empty($shift['profile_picture']);
            $avatarDotClass = $sl['due'] ? 'seat-avatar-dot due-dot seatBlink' : ($sl['nonexp'] ? 'seat-avatar-dot non-expiry-dot' : ($sl['ext'] ? 'seat-avatar-dot extension-dot seatBlink' : ($sl['exp'] ? 'seat-avatar-dot expiring-dot seatBlink' : 'seat-avatar-dot active-dot')));
            $statusColor = ['free' => '#00793d', 'due' => '#de3b3d', 'nonexp' => '#bf2a82', 'ext' => '#9a3412', 'exp' => '#eb881f', 'future' => '#b08900', 'booked' => '#1d3c86'][$tone];
        @endphp
        <div class="shift-slide-item lsm-slide is-{{ $tone }} {{ $sIdx === 0 ? 'active-slide' : '' }}"
             data-shift-idx="{{ $sIdx }}"
             data-type="{{ $shift['type'] }}"
             data-tone="{{ $tone }}" data-bday="{{ $sl['bday'] ? '1' : '0' }}"
             data-sname="{{ $sl['sname'] }}" data-stime="{{ $sl['stime'] }}" data-scolor="{{ $sl['scolor'] }}"
             data-user-id="{{ $shift['user_id'] ?? '' }}"
             data-name="{{ $shift['name'] ?? 'Available' }}"
             data-mobile="{{ $shift['mobile'] ?? '' }}"
             data-shift-name="{{ $isAvail ? ($shift['label'] ?? 'Available') : ($shift['day_type_label'] ?? ($shift['plan_name'] ?? 'Booked')) }}"
             data-plan-end="{{ !empty($shift['plan_end_date']) ? \Carbon\Carbon::parse($shift['plan_end_date'])->format('d M Y') : '' }}"
             data-due-amount="{{ !empty($shift['due_amount']) ? number_format($shift['due_amount']) : '0' }}"
             data-has-due="{{ $sl['due'] ? '1' : '0' }}"
             data-advance-amount="{{ !empty($shift['advance_amount']) ? number_format($shift['advance_amount']) : '0' }}"
             data-status-label="{{ $sl['status'] }}"
             data-status-color="{{ $statusColor }}" @if($sIdx !== 0) aria-hidden="true" @endif>
            @if($isAvail)
                <span class="lsm-free avatar-circle-available" title="Available for booking"><x-lb-icon name="armchair" /></span>
                <b class="seat-student-name lsm-name lsm-name--free">Available</b>
            @elseif($shift['type'] === 'future')
                <span class="lsm-free lsm-free--future avatar-circle-future" title="Future booking: {{ $shift['name'] }}"><x-lb-icon name="calendar-clock" /></span>
                <b class="seat-student-name lsm-name" title="{{ $shift['name'] }}">{{ $shortName }}</b>
                <span class="lsm-sub lsm-sub--future">{{ $shift['day_type_label'] ?? 'Future booking' }}</span>
            @else
                <span class="lsm-av-wrap is-{{ $tone }}">
                    @if($hasPhoto)
                        <a href="{{ asset($shift['profile_picture']) }}" class="lsm-av lsm-av--photo view-image" title="View {{ $shift['name'] }} photo"><img src="{{ asset($shift['profile_picture']) }}" alt="" loading="lazy" decoding="async"></a>
                    @else
                        <span class="lsm-av lsm-av--init">{{ $initials }}</span>
                    @endif
                    <span class="{{ $avatarDotClass }} lsm-av-dot" title="{{ $sl['status'] }}" hidden></span>
                    {{-- fee due: the amount sits on the avatar --}}
                    @if($sl['due'] && !empty($shift['due_amount']) && $shift['due_amount'] > 0)
                        <span class="seat-due-amount-pill lsm-av-due" title="Due: ₹{{ number_format($shift['due_amount']) }}">₹{{ number_format($shift['due_amount']) }}</span>
                    @elseif(!empty($shift['advance_amount']) && $shift['advance_amount'] > 0)
                        {{-- advance: paid over the bills, used on the next renewal --}}
                        <span class="lsm-av-due lsm-av-adv" title="Advance: ₹{{ number_format($shift['advance_amount']) }} (used on the next renewal)">+₹{{ number_format($shift['advance_amount']) }}</span>
                    @endif
                </span>
                <b class="seat-student-name lsm-name" title="{{ $shift['name'] }}">{{ $shortName }}</b>
                @if($sl['bday'])
                    <span class="lsm-sub lsm-sub--bday"><x-lb-icon name="party-popper" />Happy birthday!</span>
                @elseif($sl['due'] && !empty($shift['due_amount']) && $shift['due_amount'] > 0)
                @elseif($sl['frozen'])
                    <span class="lsm-sub lsm-sub--frozen">{{ $shift['day_type_label'] }}</span>
                @elseif($tone !== 'booked')
                    <span class="lsm-sub lsm-sub--{{ $tone }}">{{ $sl['status'] }}</span>
                @endif
            @endif
            {{-- what a tap on the card does for this shift (the card click presses the button below) --}}
            <span class="lsm-cta {{ $isAvail ? 'is-book' : 'is-view' }}" aria-hidden="true">{{ $isAvail ? 'Book' : 'View' }}</span>
            <span class="card-action-bar lsm-act">
                @if($isAvail)
                <button type="button" class="first_popup" tabindex="-1"
                        data-bs-toggle="modal" data-bs-target="#seatAllotmentModal"
                        data-id="{{ $seatNo }}"
                        data-seat_no="{{ $seatNo }}"
                        data-plan_type_id="{{ $shift['plan_type_id'] ?? '' }}"
                        data-day_type_id="{{ $shift['day_type_id'] ?? '' }}">Book</button>
                @else
                <button type="button" class="second_popup" tabindex="-1"
                        data-bs-toggle="modal" data-bs-target="#seatAllotmentModal2" data-seat_no="{{ $seatNo }}" data-userid="{{ $shift['user_id'] }}">View</button>
                @endif
            </span>
        </div>
        @endforeach
        @if(empty($lsmSlides))
        <div class="lsm-slide is-booked active-slide"><span class="lsm-free lsm-free--na"><x-lb-icon name="lock" /></span><b class="lsm-name">Not available</b></div>
        @endif
        </div>
    </div>

    <div class="lsm-foot">
        <button type="button" class="shift-prev-btn lsm-arrow" data-seat="{{ $seatNo }}" title="Previous shift" aria-label="Previous shift" @if($shiftCount < 2) disabled @endif><x-lb-icon name="chevron-left" /></button>
        <span class="lsm-shift" title="{{ $lsm0['sname'] }}{{ $lsm0['stime'] ? ' · ' . $lsm0['stime'] : '' }}">
            <span class="lsm-shift-n"><i style="background: {{ $lsm0['scolor'] }}"></i><span>{{ $lsm0['sname'] }}</span></span>
            <small>{{ $lsm0['stime'] }}</small>
        </span>
        <button type="button" class="shift-next-btn lsm-arrow" data-seat="{{ $seatNo }}" title="Next shift" aria-label="Next shift" @if($shiftCount < 2) disabled @endif><x-lb-icon name="chevron-right" /></button>
    </div>
    <div class="shift-dots-row lsm-dots">
        @foreach($lsmSlides as $sIdx => $sl)
        <span class="shift-dot lsm-d is-{{ $sl['tone'] }} {{ $sIdx === 0 ? 'active-dot' : '' }}" style="--c: {{ $sl['scolor'] }}" data-seat="{{ $seatNo }}" data-shift-idx="{{ $sIdx }}" role="button" aria-label="Show shift {{ $sIdx + 1 }}"></span>
        @endforeach
    </div>
</div>
