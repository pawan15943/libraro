{{-- One member card (members list + learner search). Needs $value, $rowContext and the $lml* helpers from the page;
     falls back to its own helpers so it can be included anywhere. --}}
@php
$lmlInitials ??= fn ($name) => collect(explode(' ', trim((string) $name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: '?';
$lmlMoney ??= fn ($v) => '₹' . rtrim(rtrim(number_format((float) $v, 2, '.', ','), '0'), '.');
$lmlSeatLabel ??= fn ($no) => $no ? (getSeatDisplayShortFloorName($no) ?: $no) : 'GEN';
@endphp
@php
$learner_detail_id=$value->learner_detail_id;
$planStatus = getPlanStatusDetails($value->plan_end_date);
// Batched in LearnerController::learnerList() / LearnerService::buildLearnerListRowContext()
// to avoid the N+1 queries each of these used to run per row.
$rowContextData = $rowContext[$learner_detail_id] ?? [];
$transaction = $rowContextData['transaction'] ?? null;
$totalPendingAmt = $rowContextData['total_pending'] ?? 0;
$totalExtraAmt = $rowContextData['total_extra'] ?? 0;
$paylaterFlag = $rowContextData['paylater'] ?? false;
$hasPendingAmtFlag = $rowContextData['has_pending_amt'] ?? false;
$paybleRefundAmt = $rowContextData['payble_refund'] ?? 0;
$overdueFlag = $rowContextData['overdue'] ?? false;
$isRenewUpdateFlag = $rowContextData['is_renew_update'] ?? false;
$canRenewFlag = $rowContextData['can_renew'] ?? true;
$statusPrecomputed = $rowContextData['status_precomputed'] ?? null;

$oneWeekLater = \Carbon\Carbon::parse($value->plan_start_date)->addWeek();

$due_date = $rowContextData['due_date'] ?? ($transaction->due_date ?? null);

$today = \Carbon\Carbon::now();
$threeDaysAfterStart = \Carbon\Carbon::parse($value->plan_start_date)->addDays(3);
$operationRow = $rowContextData['operation'] ?? null;
$operation = optional($operationRow)->operation;
$operationDate = optional($operationRow)->created_at;
$learner_id=$value->id;
// a booking that hasn't started yet (future filter, or one listed under "All members") gets the future-booking actions
$lmlRowFuture = ($lmlMode ?? 'current') === 'future'
    || ((int) ($value->learner_detail_status ?? 1) === 0 && !empty($value->plan_start_date) && \Carbon\Carbon::parse($value->plan_start_date)->isAfter(today()));

// Determine status dot color & title based on plan status
$dotColorClass = 'dot-active';
$dotTitle = 'Active';

if ($operation == 'closeSeat') {
    $dotColorClass = 'dot-closed';
    $dotTitle = 'Closed';
} elseif ($operation == 'deleteSeat' && $value->deleted_at != null) {
    $dotColorClass = 'dot-extension';
    $dotTitle = 'Deleted';
} elseif ($value->frozen_status == 1) {
    $dotColorClass = 'dot-frozen';
    $dotTitle = 'Frozen';
} elseif ((int)($value->no_expiry ?? 0) === 1 || ($statusPrecomputed['no_expiry_active'] ?? false)) {
    $dotColorClass = 'dot-non-expiry';
    $dotTitle = 'Non-Expired';
} elseif ((!empty($value->plan_start_date) && \Carbon\Carbon::parse($value->plan_start_date)->isFuture() && (int)($value->status ?? 0) === 0) || (($statusPrecomputed['has_future_start'] ?? false) && !($statusPrecomputed['has_past_plan'] ?? false))) {
    $dotColorClass = 'dot-upcoming';
    $dotTitle = 'Upcoming';
} elseif ($planStatus['status'] == 'About to Expire' || str_contains(strtolower($planStatus['status'] ?? ''), 'about to expire') || str_contains(strtolower($planStatus['status'] ?? ''), 'today')) {
    $dotColorClass = 'dot-about-to-expire';
    $dotTitle = 'About to Expire';
} elseif ($planStatus['class'] == 'extedned' || str_contains(strtolower($planStatus['status'] ?? ''), 'extension') || str_contains(strtolower($planStatus['status'] ?? ''), 'expired')) {
    $dotColorClass = 'dot-extension';
    $dotTitle = 'In Extension';
} else {
    $dotColorClass = 'dot-active';
    $dotTitle = 'Active';
}

$isExtensionPlan = ($dotColorClass == 'dot-extension' || $planStatus['class'] == 'extedned' || str_contains(strtolower($planStatus['status'] ?? ''), 'extension'));
$isOverdueAndExtension = ($overdueFlag && $isExtensionPlan);
$dotBlinkClass = $isOverdueAndExtension ? ' dot-blink' : '';
if ($isOverdueAndExtension) {
    $dotTitle .= ' & Payment Overdue';
}

// Determine expiry status string & banner styling
if ($operation == 'closeSeat') {
    $expiryHtml = '<span class="text-secondary"><i class="fa-regular fa-clock me-1"></i> Closed Seat on ' . ($operationDate ? date('j M Y', strtotime($operationDate)) : '') . '</span>';
    $bannerClass = 'banner-danger';
    $expiryTextOnly = 'Closed Seat on ' . ($operationDate ? date('j M Y', strtotime($operationDate)) : '');
} elseif ($operation == 'deleteSeat' && $value->deleted_at != null) {
    $expiryHtml = '<span class="text-danger"><i class="fa-regular fa-clock me-1"></i> Deleted Seat on ' . ($operationDate ? date('j M Y', strtotime($operationDate)) : '') . '</span>';
    $bannerClass = 'banner-danger';
    $expiryTextOnly = 'Deleted Seat on ' . ($operationDate ? date('j M Y', strtotime($operationDate)) : '');
} else {
    $rawExpiry = getUserStatusWithSpan($value->plan_end_date, $learner_id, $statusPrecomputed);
    $expiryTextOnly = trim(strip_tags($rawExpiry));
    $lowerExpiry = strtolower($expiryTextOnly);
    if (str_contains($lowerExpiry, 'non-expired') || str_contains($lowerExpiry, 'non expired') || (int)($value->no_expiry ?? 0) === 1 || ($statusPrecomputed['no_expiry_active'] ?? false)) {
        $bannerClass = 'banner-pink';
        $expiryHtml = '<span style="color: #c8009d !important; font-weight: 600;"><i class="fa-regular fa-clock me-1"></i> Non-Expired</span>';
        $expiryTextOnly = 'Non-Expired';
    } elseif (str_contains($lowerExpiry, 'extension') || str_contains($lowerExpiry, 'expired')) {
        $bannerClass = 'banner-danger';
        $expiryHtml = '<span class="text-danger"><i class="fa-regular fa-clock me-1"></i> ' . $expiryTextOnly . '</span>';
    } elseif (str_contains($lowerExpiry, 'about to expire') || str_contains($lowerExpiry, 'today')) {
        $bannerClass = 'banner-warning';
        $expiryHtml = '<span style="color: #d97706 !important;"><i class="fa-regular fa-clock me-1"></i> ' . $expiryTextOnly . '</span>';
    } else {
        $bannerClass = '';
        $expiryHtml = '<span class="text-success"><i class="fa-regular fa-clock me-1"></i> ' . $expiryTextOnly . '</span>';
    }
}

$formattedDueDate = !empty($due_date) ? (is_object($due_date) ? (!empty($due_date->due_date) ? date('j M', strtotime($due_date->due_date)) : '') : date('j M', strtotime($due_date))) : '';
$hasPendingBalance = ($paylaterFlag && $transaction?->pending_amount != 0) || $hasPendingAmtFlag;
// a due fee is overdue once its due date has passed (or none was set): only then the seat turns red
$lmlDueRaw = is_object($due_date) ? ($due_date->due_date ?? null) : $due_date;
$lmlOverdue = $hasPendingBalance && (empty($lmlDueRaw) || \Carbon\Carbon::parse($lmlDueRaw)->startOfDay()->lt(today()));
@endphp
@php
    $lmlTone = match($dotColorClass) {
        'dot-closed' => 'mut', 'dot-frozen' => 'blue', 'dot-non-expiry' => 'pink', 'dot-upcoming' => 'future',
        'dot-about-to-expire' => 'exp', 'dot-extension' => 'ext', default => 'ok',
    };
    $lmlTagTone = $lmlTone;
    if ($hasPendingBalance) $lmlTone = 'bad';
    $lmlPaid = (float) ($transaction->paid_amount ?? 0);
@endphp
@php
    $lmlTone = match($dotColorClass) {
        'dot-closed' => 'mut', 'dot-frozen' => 'blue', 'dot-non-expiry' => 'pink', 'dot-upcoming' => 'future',
        'dot-about-to-expire' => 'exp', 'dot-extension' => 'ext', default => 'ok',
    };
    $lmlTagTone = $lmlTone;
    if ($hasPendingBalance) $lmlTone = 'bad';
    $lmlPaid = (float) ($transaction->paid_amount ?? 0);
    // advance: paid more than the bills (used on the next renewal / refunded on leaving; docs/LEARNER_ADVANCE.md)
    $lmlAdvance = max(0, (float) $totalExtraAmt - (float) $totalPendingAmt);
    $lmlFee = (float) ($transaction->total_amount ?? ($value->plan_price_id ?? 0));
    $lmlPalette = ['#3a81d7', '#eb881f', '#8f68cb', '#2fa37a', '#5b6b8c', '#34939f', '#bf2a82', '#1d3c86'];
    $lmlShiftCol = $lmlPalette[((int) ($value->plan_type_id ?? 0)) % 8];
    $lmlSeatTxt = $lmlSeatLabel($value->seat_no);
    // Multi-shift booking: the extra shifts after the main one (docs/ADVANCED_SHIFTS.md)
    $lmlExtras = collect($rowContextData['extra_shifts'] ?? []);
    $lmlSeatsTxt = $lmlExtras->isNotEmpty()
        ? collect([$lmlSeatTxt])->merge($lmlExtras->map(fn ($s) => $lmlSeatLabel($s->seat_no)))->unique()->implode(', ')
        : $lmlSeatTxt;
    $lmlShiftNames = collect([$value->plan_type_name])->merge($lmlExtras->map(fn ($s) => optional($s->planType)->name))->filter()->values();

    // One status per member: colour only shows the status, the dot blinks only when it needs
    // action now (fee due, plan expired incl. grace); expiring soon is red without a blink.
    $lmlPs = strtolower($planStatus['status'] ?? '');
    $lmlLeft = (int) ($planStatus['diff_in_days'] ?? 0);
    // $lmlState: the plan's status shown after the name (fee due is not part of it)
    $lmlState = match (true) {
        $dotColorClass === 'dot-closed' => ['tone' => 'mut', 'label' => 'Closed', 'blink' => false],
        str_starts_with($dotTitle, 'Deleted') => ['tone' => 'mut', 'label' => 'Deleted', 'blink' => false],
        $dotColorClass === 'dot-frozen' => ['tone' => 'blue', 'label' => 'Frozen', 'blink' => false],
        // colours follow the seat map legend (learner/seat.blade.php): payment due red, extension amber,
        // expiring soon orange, future booked gold, non-expired pink, frozen blue
        $dotColorClass === 'dot-non-expiry' => ['tone' => 'pink', 'label' => 'No expiry', 'blink' => false],
        $dotColorClass === 'dot-upcoming' => ['tone' => 'future', 'label' => 'Upcoming', 'blink' => false],
        ($planStatus['class'] ?? '') === 'extedned' || ($planStatus['class'] ?? '') === 'expired' || str_contains($lmlPs, 'extension') || $lmlPs === 'expired'
            => ['tone' => ($planStatus['class'] ?? '') === 'expired' || $lmlPs === 'expired' ? 'bad' : 'ext', 'label' => str_contains($lmlPs, 'today') ? 'Expired · grace ends today' : (($planStatus['class'] ?? '') === 'extedned' ? 'Expired · in grace period' : 'Expired'), 'blink' => true],
        ($planStatus['class'] ?? '') === 'aboutToExpire' => ['tone' => 'exp', 'label' => $lmlLeft <= 0 ? 'Ends today' : 'Ends in ' . $lmlLeft . ($lmlLeft === 1 ? ' day' : ' days'), 'blink' => false],
        default => ['tone' => 'ok', 'label' => 'Active', 'blink' => false],
    };
    // $lmlBadge: the seat status after the name — a plan in grace period or ending soon is still an active seat
    // (the head message already says how many days are left)
    $lmlBadge = in_array($lmlState['tone'], ['ext', 'exp'], true)
        ? ['tone' => 'ok', 'label' => 'Active']
        : $lmlState;
    // $lmlHead: the circle in the head and the row tint — an overdue fee wins (red, blinking);
    // a fee not due yet keeps the plan's colour
    $lmlHead = $lmlOverdue && !in_array($lmlState['tone'], ['mut'], true)
        ? ['tone' => 'bad', 'blink' => true]
        : ['tone' => $lmlState['tone'], 'blink' => $lmlState['blink']];
@endphp
@php
    $lmlStart = !empty($value->plan_start_date) ? date('j M Y', strtotime($value->plan_start_date)) : null;
    $lmlEnd = $value->frozen_status == 1 ? 'Frozen' : (!empty($value->plan_end_date) ? date('j M Y', strtotime($value->plan_end_date)) : 'No expiry');
    $lmlDuration = $lmlStart ? $lmlStart . ' – ' . $lmlEnd : $lmlEnd;
@endphp
        <article class="learner-card lml-card lml-card--inline lml-card--member is-{{ $lmlTone }} lml-tint-{{ $lmlHead['tone'] }}" data-learner="{{ $learner_id }}">
            <div class="lml-card-top">
                <span class="lml-dot avatar-status-dot {{ $dotColorClass }}{{ $dotBlinkClass }}" title="{{ $dotTitle }}" hidden></span>
                {{-- head: status circle (blinks for a due fee or an expired plan), seat, then the plan message in that colour --}}
                <i class="lml-sdot lml-headdot is-{{ $lmlHead['tone'] }}{{ $lmlHead['blink'] ? ' is-blink' : '' }}" aria-hidden="true"></i>
                <span class="lml-headseat">{{ $lmlSeatsTxt === 'GEN' ? 'General seat' : 'Seat' }}@if($lmlSeatsTxt !== 'GEN') <b>{{ $lmlSeatsTxt }}</b>@endif</span>
                @if($expiryTextOnly)
                <span class="lml-exp lml-stxt is-{{ $lmlHead['tone'] }}" title="{{ $expiryTextOnly }}">{{ $expiryTextOnly }}</span>
                @endif
                @if($value->mobile)
                <a href="https://wa.me/+91{{ $value->mobile }}?text={{ urlencode('Hello! Please click the link below to access the Attendance App and mark your attendance securely. ' . route('qr.attendance.link')) }}" target="_blank" rel="noopener" class="lml-share noLoader" title="Share Attendance App link" aria-label="Share Attendance App link with {{ $value->name }}"><x-lb-icon name="share-2" /></a>
                @endif
            </div>

            <div class="lml-card-body">
                <div class="lml-who">
                    <a href="{{ asset($value->profile_picture ? $value->profile_picture : 'public/img/student_profile.jpeg') }}" class="lml-av view-image learner-list-profile-photo {{ $lmlOverdue ? 'is-due' : (in_array($lmlTone, ['exp', 'ext'], true) ? 'is-warn' : '') }}" title="View profile photo">
                        @if($value->profile_picture)
                        <img src="{{ asset($value->profile_picture) }}" alt="" loading="lazy" decoding="async">
                        @else
                        <span>{{ $lmlInitials($value->name) }}</span>
                        @endif
                    </a>
                    <div class="lml-who-t">
                        <small class="lml-lbl lml-lbl--member">Member</small>
                        {{-- plan status right after the name (fee due shows in the head circle and Payment) --}}
                        <div class="lml-name-row">
                            <h3 title="{{ $value->name }}"><a href="{{ route('learners.show', $value->id) }}" data-lsd-open="{{ $value->id }}" data-lsd-variant="member">{{ $value->name }}</a></h3>
                            <span class="lml-state is-{{ $lmlBadge['tone'] }}" title="{{ $lmlBadge['label'] }}">{{ $lmlBadge['label'] }}</span>
                        </div>
                        {{-- UID is the learner's login, so it stands out --}}
                        <div class="lml-uid">UID <b>{{ $value->learner_no }}</b>
                            <button type="button" class="copy-action-btn lml-copy" data-copy="{{ $value->learner_no }}" title="Copy UID" aria-label="Copy UID"><x-lb-icon name="copy" /></button>
                        </div>
                    </div>
                    <span class="lml-st lml-st--{{ $lmlTagTone }}">{{ $dotTitle }}</span>
                </div>

                <div class="lml-chips">
                    @if(!empty($value->plan_type_name))<span class="lml-chip"><i class="lml-chip-dot" style="background: {{ $lmlShiftCol }}"></i>{{ $value->plan_type_name }}</span>@endif
                    @foreach($lmlExtras as $lmlSlot)
                    <span class="lml-chip"><i class="lml-chip-dot" style="background: {{ $lmlPalette[((int) $lmlSlot->plan_type_id) % 8] }}"></i>{{ optional($lmlSlot->planType)->name }}@if((string) $lmlSlot->seat_no !== (string) $value->seat_no) · Seat {{ $lmlSeatLabel($lmlSlot->seat_no) }}@endif</span>
                    @endforeach
                    @if(!empty($value->plan_name))<span class="lml-chip"><x-lb-icon name="calendar-range" />{{ $value->plan_name }}</span>@endif
                    @if($transaction && $transaction->locker_amount)<span class="lml-chip"><x-lb-icon name="lock" />Locker ₹{{ $transaction->locker_amount }}</span>@endif
                </div>
                <div class="lml-dur"><x-lb-icon name="calendar" /><span>{{ $lmlDuration }}</span></div>

                {{-- labelled fields: columns in list view, one row each in card view --}}
                <span class="lml-col lml-col-shift"><small class="lml-lbl">Shift</small><span class="lml-val"@if($lmlShiftNames->count() > 1) title="{{ $lmlShiftNames->implode(' + ') }}"@endif>{{ $lmlShiftNames->first() ?: '—' }}@if($lmlShiftNames->count() > 1) <em class="lml-more">+{{ $lmlShiftNames->count() - 1 }}</em>@endif</span></span>
                <span class="lml-col lml-col-plan"><small class="lml-lbl">Plan</small><span class="lml-val">{{ $value->plan_name ?: '—' }}@if($transaction && $transaction->locker_amount)<small>Locker ₹{{ $transaction->locker_amount }}</small>@endif</span></span>
                <span class="lml-col lml-col-till"><small class="lml-lbl">Duration</small><span class="lml-val">{{ $lmlDuration }}</span></span>
                <span class="lml-col lml-col-fee"><small class="lml-lbl">Payment</small><span class="lml-val">{{ $transaction ? $lmlMoney($lmlPaid) : '—' }}@if($hasPendingBalance)<em class="{{ $lmlOverdue ? 'is-due' : 'is-due-soon' }}"> · {{ $lmlMoney($totalPendingAmt) }} due</em>@if(!empty($formattedDueDate))<small class="{{ $lmlOverdue ? 'is-due' : 'is-due-soon' }}">{{ $lmlOverdue ? 'Overdue since' : 'Due on' }} {{ $formattedDueDate }}</small>@endif @elseif($transaction && $lmlAdvance > 0)<em class="is-clear"> · {{ $lmlMoney($lmlAdvance) }} advance</em>@elseif($transaction)<em class="is-clear"> · All paid</em>@endif</span></span>

                <div class="lml-money">
                    <div class="lml-paid">
                        <small>Paid</small>
                        <b>{{ $transaction ? $lmlMoney($lmlPaid) : '—' }}</b>
                    </div>
                    @if($hasPendingBalance)
                    <div class="lml-due {{ $lmlOverdue ? 'is-due' : 'is-soon' }}">
                        <small>{{ $lmlOverdue ? 'Overdue' : 'Due' }} @if(!empty($formattedDueDate))· {{ $formattedDueDate }}@endif</small>
                        <b>{{ $lmlMoney($totalPendingAmt) }}</b>
                        @can('has-permission', 'Learner Transactions')
                        <a href="javascript:;" class="open-transaction-modal lml-txn" data-learner_id="{{ $learner_id }}" data-bs-toggle="modal" data-bs-target="#cf-modal" title="View transactions" aria-label="View transactions"><x-lb-icon name="external-link" /></a>
                        @endcan
                    </div>
                    @elseif($transaction && $lmlAdvance > 0)
                    <div class="lml-due is-clear is-adv" title="Paid more than the bills: used on the next renewal, or refunded when the learner leaves">
                        <small>Advance</small>
                        <b>{{ $lmlMoney($lmlAdvance) }}</b>
                    </div>
                    @else
                    <div class="lml-due is-clear">
                        <small>Balance</small>
                        <b>{{ empty($transaction?->pending_amount) && !$transaction ? '—' : 'All paid' }}</b>
                    </div>
                    @endif
                </div>
            </div>

            <div class="lml-card-act">
                {{-- one main button: Collect when a fee is due; Renew from 5 days before the plan ends until its grace (extension) period is over; otherwise View --}}
                @php
                    $lmlCanRenewBtn = $canRenewFlag && (int) $value->frozen_status !== 1
                        && (int) ($planStatus['diff_in_days'] ?? 999) <= 5 && (int) ($planStatus['diff_extend_day'] ?? -1) >= 0
                        && auth()->user() && auth()->user()->can('has-permission', 'Renew Seat');
                @endphp
                @php
                    // past bookings (members list history filters): a deleted member is restored, an expired or closed one reactivated
                    $lmlDeleted = !empty($value->deleted_at);
                    $lmlCanReactivate = (int) $value->status === 0 && (int) $value->frozen_status !== 1
                        && auth()->user() && auth()->user()->can('has-permission', 'Reactive Seat');
                @endphp
                @if($lmlDeleted)
                <a href="#" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" data-seat="{{ $value->seat_no }}" class="lp-btn lp-btn--ok restore-customer"><x-lb-icon name="rotate-ccw" /><span>Restore</span></a>
                @elseif($hasPendingBalance)
                <a href="javascript:void(0)" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" class="lp-btn settlement-learner lml-collect"><x-lb-icon name="indian-rupee" /><span><em>Collect </em>{{ $lmlMoney($totalPendingAmt) }}</span></a>
                @elseif($lmlCanRenewBtn)
                <a href="javascript:void(0)" class="lp-btn lml-renewbtn renew_extend" data-seat_no="{{ $value->seat_no }}" data-user="{{ $learner_id }}" data-end_date="{{ $value->plan_end_date }}" data-learner_detail="{{ $learner_detail_id }}"><x-lb-icon name="refresh-cw" /><span>Renew</span></a>
                @elseif($lmlCanReactivate)
                <a href="{{ route('learners.reactive', $value->id) }}" class="lp-btn lml-renewbtn"><x-lb-icon name="refresh-cw" /><span>Reactivate seat</span></a>
                @else
                <a href="{{ route('learners.show', $value->id) }}" class="lp-btn lp-btn--ghost lml-viewbtn" data-lsd-open="{{ $value->id }}" data-lsd-variant="member"><x-lb-icon name="eye" /><span>View details</span></a>
                @endif
                @if ($transaction?->id)
                <form action="{{ route('learner.receipt.download') }}" method="POST" enctype="multipart/form-data" target="_blank" class="lml-rcpt2">
                    @csrf
                    <input type="hidden" name="learner_id" value="{{$learner_id}}">
                    <input type="hidden" name="id" value="{{($transaction->id ?? 0)}}">
                    <input type="hidden" name="learner_detail_id" value="{{$learner_detail_id}}">
                    <input type="hidden" name="type" value="learner">
                    <button type="submit" class="lp-btn lp-btn--ghost noLoader" title="Print receipt" aria-label="Print receipt"><x-lb-icon name="printer" /><span>Receipt</span></button>
                </form>
                @endif
                @if($value->mobile)
                <a href="tel:+91{{ $value->mobile }}" class="lp-btn lp-btn--ghost lml-callbtn" title="Call +91 {{ $value->mobile }}" aria-label="Call {{ $value->name }}"><x-lb-icon name="phone" /></a>
                @endif
            </div>

            {{-- every action, always visible: upper bar in list view, two rows under the card in card view --}}
            <div class="lml-ai mobile-actions-scroll" aria-label="Actions for {{ $value->name }}">
                @if($lmlRowFuture)
                    @include('learner.partials.future-booking-actions', ['isMobile' => true])
                @else
                    @include('learner.partials.learner-actions', ['isMobile' => true])
                @endif
                @if($lmlDeleted)
                <div class="mobile-action-item">
                    <a href="#" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" data-seat="{{ $value->seat_no }}" class="action-btn restore-customer" title="Restore learner"><x-lb-icon name="rotate-ccw" /></a>
                    <span class="mobile-action-label">Restore</span>
                </div>
                @endif
                @if(($lmlMode ?? 'current') === 'history' && (int) $value->status === 0 && empty($transaction?->refund))
                @can('has-permission', 'Delete Seat')
                <div class="mobile-action-item is-danger">
                    <a href="#" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" data-seat="{{ $value->seat_no }}" data-permanent="1" class="action-btn delete-permanent-customer" title="Permanently delete learner"><i class="fas fa-trash"></i></a>
                    <span class="mobile-action-label">Delete</span>
                </div>
                @endcan
                @endif
            </div>
        </article>
