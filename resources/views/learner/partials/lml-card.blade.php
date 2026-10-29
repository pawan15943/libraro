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
@endphp
@php
    $lmlTone = match($dotColorClass) {
        'dot-closed' => 'mut', 'dot-frozen' => 'blue', 'dot-non-expiry' => 'pink', 'dot-upcoming' => 'blue',
        'dot-about-to-expire' => 'warn', 'dot-extension' => 'bad', default => 'ok',
    };
    $lmlTagTone = $lmlTone;
    if ($hasPendingBalance) $lmlTone = 'bad';
    $lmlPaid = (float) ($transaction->paid_amount ?? 0);
@endphp
@php
    $lmlTone = match($dotColorClass) {
        'dot-closed' => 'mut', 'dot-frozen' => 'blue', 'dot-non-expiry' => 'pink', 'dot-upcoming' => 'blue',
        'dot-about-to-expire' => 'warn', 'dot-extension' => 'bad', default => 'ok',
    };
    $lmlTagTone = $lmlTone;
    if ($hasPendingBalance) $lmlTone = 'bad';
    $lmlPaid = (float) ($transaction->paid_amount ?? 0);
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
@endphp
@php
    $lmlStart = !empty($value->plan_start_date) ? date('j M Y', strtotime($value->plan_start_date)) : null;
    $lmlEnd = $value->frozen_status == 1 ? 'Frozen' : (!empty($value->plan_end_date) ? date('j M Y', strtotime($value->plan_end_date)) : 'No expiry');
    $lmlDuration = $lmlStart ? $lmlStart . ' – ' . $lmlEnd : $lmlEnd;
@endphp
        <article class="learner-card lml-card lml-card--inline is-{{ $lmlTone }}" data-learner="{{ $learner_id }}">
            <div class="lml-card-top">
                <i class="lml-blink {{ ($hasPendingBalance || $lmlTone === 'warn' || $lmlTone === 'bad') ? 'is-on' : '' }}" aria-hidden="true"></i>
                <span class="lml-dot avatar-status-dot {{ $dotColorClass }}{{ $dotBlinkClass }}" title="{{ $dotTitle }}" hidden></span>
                <span class="lml-seatno">Seat <b>{{ $lmlSeatsTxt }}</b></span>
                <x-lb-icon :name="in_array($lmlTone, ['warn', 'bad']) ? 'alarm-clock' : 'shield-check'" class="lml-top-ic" />
                <span class="lml-exp" title="{{ $expiryTextOnly }}">{{ $expiryTextOnly }}</span>
                @if($hasPendingBalance)
                <span class="lml-duepill"><x-lb-icon name="indian-rupee" />{{ $lmlMoney($totalPendingAmt) }} payment due</span>
                @endif
                @if($value->mobile)
                <a href="https://wa.me/+91{{ $value->mobile }}?text={{ urlencode('Hello! Please click the link below to access the Attendance App and mark your attendance securely. ' . route('qr.attendance.link')) }}" target="_blank" rel="noopener" class="lml-share noLoader" title="Share Attendance App link" aria-label="Share Attendance App link with {{ $value->name }}"><x-lb-icon name="share-2" /></a>
                @endif
            </div>

            <div class="lml-card-body">
                <div class="lml-who">
                    <a href="{{ asset($value->profile_picture ? $value->profile_picture : 'public/img/student_profile.jpeg') }}" class="lml-av view-image learner-list-profile-photo {{ $hasPendingBalance ? 'is-due' : ($lmlTone === 'warn' ? 'is-warn' : '') }}" title="View profile photo">
                        @if($value->profile_picture)
                        <img src="{{ asset($value->profile_picture) }}" alt="" loading="lazy" decoding="async">
                        @else
                        <span>{{ $lmlInitials($value->name) }}</span>
                        @endif
                    </a>
                    <div class="lml-who-t">
                        <h3 title="{{ $value->name }}"><a href="{{ route('learners.show', $value->id) }}" data-lsd-open="{{ $value->id }}" data-lsd-variant="member">{{ $value->name }}</a></h3>
                        <div class="lml-uid">UID {{ $value->learner_no }}
                            <button type="button" class="copy-action-btn lml-copy" data-copy="{{ $value->learner_no }}" title="Copy UID" aria-label="Copy UID"><x-lb-icon name="copy" /></button>
                            @if($value->mobile)
                            <i class="lml-uid-sep" aria-hidden="true"></i>
                            <a href="tel:+91{{ $value->mobile }}" class="lml-tel" title="Call +91 {{ $value->mobile }}" aria-label="Call +91 {{ $value->mobile }}"><x-lb-icon name="phone" /></a>
                            <button type="button" class="copy-action-btn lml-copy lml-copy--on" data-copy="{{ $value->mobile }}" title="Copy mobile number" aria-label="Copy mobile number"><x-lb-icon name="copy" /></button>
                            @endif
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

                {{-- list layout columns --}}
                <span class="lml-col lml-col-seat">{{ $lmlShiftNames->implode(' + ') ?: '—' }}@if(!empty($value->plan_name) || $lmlShiftNames->count() > 1)<small>{{ collect([$value->plan_name, $lmlShiftNames->count() > 1 ? $lmlShiftNames->count() . ' shifts' : null])->filter()->implode(' · ') }}</small>@endif</span>
                <span class="lml-col lml-col-till">{{ $lmlDuration }}</span>
                <span class="lml-col lml-col-fee">{{ $transaction ? $lmlMoney($lmlPaid) : '—' }}@if($hasPendingBalance)<small class="is-due">{{ $lmlMoney($totalPendingAmt) }} due{{ !empty($formattedDueDate) ? ' · ' . $formattedDueDate : '' }}</small>@elseif($transaction)<small class="is-clear">All paid</small>@endif</span>
                <span class="lml-col lml-st-col"><span class="lml-st lml-st--{{ $lmlTagTone }}">{{ $dotTitle }}</span></span>

                <div class="lml-money">
                    <div class="lml-paid">
                        <small>Paid</small>
                        <b>{{ $transaction ? $lmlMoney($lmlPaid) : '—' }}</b>
                    </div>
                    @if($hasPendingBalance)
                    <div class="lml-due is-due">
                        <small>Due @if(!empty($formattedDueDate))· {{ $formattedDueDate }}@endif</small>
                        <b>{{ $lmlMoney($totalPendingAmt) }}</b>
                        <a href="javascript:;" class="open-transaction-modal lml-txn" data-learner_id="{{ $learner_id }}" data-bs-toggle="modal" data-bs-target="#cf-modal" title="View transactions" aria-label="View transactions"><x-lb-icon name="external-link" /></a>
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
                @if($hasPendingBalance)
                <a href="javascript:void(0)" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" class="lp-btn settlement-learner lml-collect"><x-lb-icon name="indian-rupee" /><span><em>Collect </em>{{ $lmlMoney($totalPendingAmt) }}</span></a>
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
            </div>

            {{-- every action, always visible: upper bar in list view, two rows under the card in card view --}}
            <div class="lml-ai mobile-actions-scroll" aria-label="Actions for {{ $value->name }}">
                @include('learner.partials.learner-actions', ['isMobile' => true])
            </div>
        </article>
