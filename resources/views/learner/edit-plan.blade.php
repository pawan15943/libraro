@extends('layouts.library')
@section('content')

@php
$planEndDate = $customer->plan_end_date;
$today = \Carbon\Carbon::today();

if ($planEndDate) {
    $endDate = \Carbon\Carbon::parse($planEndDate);
    $diffInDays = $today->diffInDays($endDate, false); // negative if in past
    $extendDays = function_exists('getExtendDays') ? getExtendDays() : 0;
    $inextendDate = $endDate->copy()->addDays($extendDays);
    $diffExtendDay = $today->diffInDays($inextendDate, false);

    if ($diffInDays < 0 && $diffExtendDay < 0) {
        $statusText = 'Expired ' . abs($diffInDays) . ' days ago';
        $statusClass = 'status-expired';
        $statusIcon = 'fa-solid fa-circle-xmark';
    } elseif ($diffInDays < 0 && $diffExtendDay > 0) {
        $statusText = 'Extension: ' . abs($diffExtendDay) . ' days left';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-clock-rotate-left';
    } elseif ($diffInDays < 0 && $diffExtendDay == 0) {
        $statusText = 'Extension ends today';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-triangle-exclamation';
    } elseif ($diffInDays == 0) {
        $statusText = 'Expires today';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-triangle-exclamation';
    } elseif ($diffInDays == 1) {
        $statusText = 'Expires in 1 day';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-clock';
    } elseif ($diffInDays <= 5) {
        $statusText = 'Expires in ' . $diffInDays . ' days';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-clock';
    } else {
        $statusText = 'Active (Expires in ' . $diffInDays . ' days)';
        $statusClass = 'status-active';
        $statusIcon = 'fa-solid fa-circle-check';
    }
} else {
    $statusText = 'Active';
    $statusClass = 'status-active';
    $statusIcon = 'fa-solid fa-circle-check';
}

$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class = $planDetails['class'];

if ($customer->locker_no) {
    $locker_read = '';
} else {
    $locker_read = 'readonly';
}

$paymentType = 'EDIT';
$route = route('learners.update', $customer->id);

$oldDiff = old('diffrence_amount');
$diffSign = ($oldDiff !== null && $oldDiff !== '' && (float) $oldDiff < 0) ? -1 : 1;
$diffAbs = ($oldDiff !== null && $oldDiff !== '') ? (int) round(abs((float) $oldDiff)) : '';
$diffLabel = $diffSign < 0 ? 'Amount to Refund' : 'Amount to pay';

$oldPending = old('pending_amount');
$pendingSign = ($oldPending !== null && $oldPending !== '' && (float) $oldPending < 0) ? -1 : 1;
$pendingAbs = ($oldPending !== null && $oldPending !== '') ? (int) round(abs((float) $oldPending)) : '';
$pendingLabel = $pendingSign < 0 ? 'Pending Refund Amount' : 'Pending Amount';
$whenLabel = $pendingSign < 0 ? 'When do you want to refund this amount' : 'When do you want to pay this amount';
@endphp

{{-- Scoped CSS for Learner Edit Plan Module --}}
<link rel="stylesheet" href="{{ asset('public/css/learner-edit-plan.css') }}?v={{ time() }}">

<div class="learner-edit-plan-module">
    <div class="edit-plan-wrapper">

        {{-- 1. TOP SEAT HEADER HERO CARD (MATCHING SWAP SEAT & CHANGE PLAN) --}}
        <div class="learner-seat-header-card">
            <div class="seat-header-main">
                <div class="seat-header-identity">
                    <div class="seat-header-avatar-box">
                        @php
                            $learnerProfilePic = $customer->learner->profile_picture ?? ($customer->profile_picture ?? null);
                        @endphp
                        @if($learnerProfilePic && file_exists(public_path($learnerProfilePic)))
                            <img id="topSeatAvatarImg" src="{{ asset($learnerProfilePic) }}" alt="{{ $customer->name }}" class="avatar-user-photo">
                        @elseif(isset($customer->image) && $customer->image)
                            <img id="topSeatAvatarImg" src="{{ asset($customer->image) }}" alt="Seat" class="avatar-seat-chair {{ $class }}">
                        @else
                            <img id="topSeatAvatarImg" src="{{ asset('public/img/booked.png') }}" alt="Seat" class="avatar-seat-chair {{ $class }}">
                        @endif
                    </div>
                    <div class="seat-header-info">
                        <div class="seat-badge-row">
                            <span class="seat-status-badge {{ $statusClass }}">
                                <i class="{{ $statusIcon }} me-1"></i>{{ $statusText }}
                            </span>
                        </div>
                        <h3 class="seat-title text-uppercase">
                            {{ strtoupper($customer->name ?? ($customer->learner->name ?? 'Learner')) }}
                        </h3>
                        <p class="seat-subtitle">
                            <span>Learner UID: <strong class="seat-uid-tag">{{ $customer->learner_no ?? ($customer->learner->learner_no ?? ('#' . $customer->id)) }}</strong></span>
                        </p>
                    </div>
                </div>
                <div class="seat-header-actions">
                    <a href="{{ route('learners') }}" class="btn-seat-back btn-back-desktop" title="Go Back">
                        <i class="fa-solid fa-arrow-left"></i> <span class="btn-back-text">Go Back</span>
                    </a>
                    {{-- Mobile Collapse/Expand Toggle Arrow (Closed by default on mobile) --}}
                    <button type="button" class="btn-seat-collapse is-collapsed" id="btnToggleDetails" title="Show / Hide Details" aria-expanded="false">
                        <i class="fa-solid fa-chevron-down toggle-icon"></i>
                    </button>
                </div>
            </div>

            {{-- 4 GLASSMORPHIC DETAIL TILES (Closed by default on mobile) --}}
            <div class="glass-info-grid is-collapsed" id="glassInfoGrid">
                {{-- Tile 1: Plan Name --}}
                <div class="glass-tile tile-plan">
                    <div class="glass-tile-icon">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Plan</div>
                        <div class="glass-tile-value" title="{{ $customer->plan_name ?? 'N/A' }}">
                            {{ $customer->plan_name ?? 'N/A' }}
                        </div>
                    </div>
                </div>

                {{-- Tile 2: Shift Hours / Timing --}}
                <div class="glass-tile tile-shift">
                    <div class="glass-tile-icon">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Shift Timing</div>
                        <div class="glass-tile-value" title="{{ $customer->plan_type_name ?? 'N/A' }}">
                            @if(!empty($customer->start_time) && !empty($customer->end_time))
                                {{ \Carbon\Carbon::parse($customer->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($customer->end_time)->format('h:i A') }}
                            @elseif(!empty($customer->slot_hours))
                                {{ $customer->slot_hours }} Hours ({{ $customer->plan_type_name }})
                            @else
                                {{ $customer->plan_type_name ?? 'Regular Shift' }}
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Tile 3: Seat Number --}}
                <div class="glass-tile tile-seat">
                    <div class="glass-tile-icon">
                        <i class="fa-solid fa-chair"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Seat No.</div>
                        <div class="glass-tile-value">
                            @if($customer->seat_no)
                                <span class="badge-seat-tag">{{ getSeatDisplayShortFloorName($customer->seat_no) }}</span>
                            @else
                                <span class="badge-seat-tag badge-general">General</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Tile 4: Contact / Mobile --}}
                <div class="glass-tile tile-contact">
                    <div class="glass-tile-icon">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Mobile</div>
                        <div class="glass-tile-value">
                            @if($customer->mobile)
                                <a href="tel:{{ $customer->mobile }}">{{ $customer->mobile }}</a>
                            @else
                                <span>Not Provided</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- FORM WRAPPER --}}
        <form id="editPlanForm" action="{{ route('learners.update', $customer->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <input id="edit_seat" type="hidden" name="seat_no" value="{{ old('seat_no', $customer->seat_no) }}">
            <input name="user_id" type="hidden" value="{{$customer->id}}">
            <input name="learner_id" type="hidden" value="{{$customer->id}}">
            <input name="plan_id" type="hidden" value="{{$customer->plan_id}}" id="plan_id10">
            <input name="plan_type_id" type="hidden" value="{{$customer->plan_type_id}}" id="plan_type_id10">
            <input type="hidden" name="payment_type" value="EDIT" id="payment_type_operation">

            <input type="hidden" name="name" value="{{ $customer->name }}">
            <input type="hidden" name="dob" value="{{ $customer->dob }}">
            <input type="hidden" name="mobile" value="{{ $customer->mobile }}">
            <input type="hidden" name="email" value="{{ $customer->email }}">

            {{-- SINGLE COLUMN FLOW (EXACT SAME WIDTH AS SWAP SEAT) --}}
            <div class="change-plan-cards-column">

                {{-- 2. CARD: CURRENT PLAN INFO --}}
                <div class="plan-card">
                    <div class="plan-card-header header-green">
                        <div class="plan-header-left">
                            <div class="plan-header-icon">
                                <i class="fa-solid fa-calendar-days"></i>
                            </div>
                            <div>
                                <h4 class="plan-header-title">Current Plan Info</h4>
                                <p class="plan-header-subtitle">Update plan dates and view current plan pricing.</p>
                            </div>
                        </div>
                    </div>
                    <div class="plan-card-body">
                        <div class="plan-tip-box">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <div>
                                <strong>Warning:</strong> Changing the start date will also affect the plan end date. Please ensure you update it carefully and with full understanding of its impact.
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-4 form-group">
                                <label class="form-label">Plan <span class="required-star">*</span></label>
                                <input type="text" class="form-control" value="{{ $customer->plan_name }}" readonly>
                                @error('plan_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="form-label">Plan Type / Shift <span class="required-star">*</span></label>
                                <input type="text" class="form-control" value="{{ $customer->plan_type_name }}" readonly>
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="form-label" for="plan_price10">Plan Price <span class="required-star">*</span></label>
                                <input id="plan_price10" class="form-control price-highlight-input @error('plan_price_id') is-invalid @enderror" value="{{ old('plan_price_id') !== null ? (int) round((float) old('plan_price_id')) : ((float)$customer->plan_price_id ? (int) round((float)$customer->plan_price_id) : '') }}" name="plan_price_id" readonly>
                                @error('plan_price_id')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="col-md-6 form-group">
                                <label class="form-label" for="start_date10">Plan Start Date <span class="required-star">*</span></label>
                                <input type="date" class="form-control datepicker @error('plan_start_date') is-invalid @enderror"
                                    name="plan_start_date" value="{{ old('plan_start_date', $customer->plan_start_date) }}" id="start_date10">
                                @error('plan_start_date')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6 form-group">
                                <label class="form-label" for="plan_end_date_edit">Plan End Date <span class="required-star">*</span></label>
                                <input type="date" class="form-control datepicker @error('plan_end_date') is-invalid @enderror"
                                    name="plan_end_date" value="{{ old('plan_end_date', $customer->plan_end_date) }}" id="plan_end_date_edit" readonly>
                                @error('plan_end_date')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. CARD: PLAN ADD-ON'S (COLLAPSED) --}}
                <div class="plan-card">
                    <div class="plan-card-header header-amber collapsible-header" data-bs-toggle="collapse" data-bs-target="#planAddonsCollapse" aria-expanded="false" aria-controls="planAddonsCollapse">
                        <div class="plan-header-left">
                            <div class="plan-header-icon">
                                <i class="fa-solid fa-cube"></i>
                            </div>
                            <div>
                                <h4 class="plan-header-title">Plan Add-on's</h4>
                                <p class="plan-header-subtitle">Configure locker allotment and discount preferences.</p>
                            </div>
                        </div>
                        <div class="plan-header-toggle">
                            <i class="fa-solid fa-chevron-down toggle-icon"></i>
                        </div>
                    </div>
                    <div class="collapse @if($errors->has('locker_amount') || $errors->has('locker_no') || $errors->has('discount_amount')) show @endif" id="planAddonsCollapse">
                        <div class="plan-card-body">
                            <div class="row g-2">
                                @if(!in_array('3', toggleHideField()) || (in_array('3', toggleHideField()) && ($hasLocker == 'yes')))
                                <div class="col-md-4 form-group {{ !is_locker() ? 'd-none' : '' }}">
                                    <label class="form-label" for="toggleFieldCheckbox10">Locker?</label>
                                    <select name="locker" id="toggleFieldCheckbox10" class="form-select">
                                        <option value="no" {{ old('locker', $hasLocker) === 'no' ? 'selected' : '' }}>No</option>
                                        <option value="yes" {{ old('locker', $hasLocker) === 'yes' ? 'selected' : '' }}>Yes, I Need a Locker</option>
                                    </select>
                                </div>

                                <div class="col-md-4 form-group {{ !is_locker() ? 'd-none' : '' }}">
                                    <label class="form-label" for="locker_amount10">Locker Amount <span class="required-star">*</span></label>
                                    <input type="text" id="locker_amount10" class="form-control @error('locker_amount') is-invalid @enderror" name="locker_amount" placeholder="0" value="{{ old('locker_amount') !== null ? (int) round((float) old('locker_amount')) : ((float)$locker_amt ? (int) round((float)$locker_amt) : '0') }}" readonly>
                                    @error('locker_amount')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>

                                <div class="col-md-4 form-group {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer2">
                                    <label class="form-label" for="locker_no10">Locker No.</label>
                                    <input type="text" class="form-control digit-only @error('locker_no') is-invalid @enderror" name="locker_no" id="locker_no10" placeholder="Enter Locker No." value="{{ old('locker_no', $customer->locker_no) }}" {{$locker_read}}>
                                    @error('locker_no')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                                @endif

                                @if(!in_array('6', toggleHideField()) || (in_array('6', toggleHideField()) && $discountAmount))
                                <div class="col-md-6 form-group">
                                    <label class="form-label" for="discountType10">Discount Type</label>
                                    <select id="discountType10" class="form-select" name="discountType">
                                        <option value="">Select Discount Type</option>
                                        <option value="amount" {{ old('discountType', $selectedDiscountType) == 'amount' ? 'selected' : '' }}>Amount</option>
                                        <option value="percentage" {{ old('discountType', $selectedDiscountType) == 'percentage' ? 'selected' : '' }}>Percentage</option>
                                    </select>
                                </div>

                                <div class="col-md-6 form-group">
                                    <label class="form-label" for="discount_amount10">Discount Amount (<span id="typeVal10">INR / %</span>)</label>
                                    <input type="text" id="discount_amount10" class="form-control @error('discount_amount') is-invalid @enderror" placeholder="0" name="discount_amount"
                                    value="{{ old('discount_amount') !== null ? (int) round((float) old('discount_amount')) : ((float)(currentTransaction($customer->learner_detail_id)->discount_amount ?? 0) ? (int) round((float)(currentTransaction($customer->learner_detail_id)->discount_amount ?? 0)) : '0') }}" readonly>
                                    @error('discount_amount')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. CARD: PAYMENT & SETTLEMENT --}}
                <div class="plan-card">
                    <div class="plan-card-header header-navy">
                        <div class="plan-header-left">
                            <div class="plan-header-icon">
                                <i class="fa-solid fa-calculator"></i>
                            </div>
                            <div>
                                <h4 class="plan-header-title">Payment &amp; Settlement</h4>
                                <p class="plan-header-subtitle">Review previous calculations &amp; total amount.</p>
                            </div>
                        </div>
                    </div>
                    <div class="plan-card-body">
                        {{-- LIVE PRICE CHANGE SUMMARY BANNER --}}
                        <div class="live-price-summary-banner" id="livePriceSummaryBanner">
                            <div class="summary-box">
                                <span class="summary-sublabel">Previous Paid</span>
                                <span class="summary-amount" id="summaryPrevPaid">₹{{ (int) round((float) currentTransaction($customer->learner_detail_id)->paid_amount) }}</span>
                            </div>
                            <div class="summary-arrow-circle">
                                <i class="fa-solid fa-arrow-right"></i>
                            </div>
                            <div class="summary-box">
                                <span class="summary-sublabel">New Total</span>
                                <span class="summary-amount new-total" id="summaryNewTotal">₹{{ (int) round((float) optional(currentTransaction($customer->learner_detail_id))->total_amount) }}</span>
                            </div>
                            <div class="summary-badge-box">
                                <span class="summary-sublabel" id="summaryDiffLabel">{{ $diffLabel }}</span>
                                <span class="summary-diff-pill {{ $diffSign < 0 ? 'pill-refund' : 'pill-pay' }}" id="summaryDiffBadge">₹{{ $diffAbs ? (int) round((float)$diffAbs) : 0 }}</span>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-6 form-group">
                                <label class="form-label" for="previous_amount10">Previous Paid Amount <span class="required-star">*</span></label>
                                <input type="text" class="form-control @error('previous_amount') is-invalid @enderror"
                                    name="previous_amount" id="previous_amount10"
                                    value="{{ (int) round((float) currentTransaction($customer->learner_detail_id)->paid_amount) }}" readonly>
                                @error('previous_amount')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="col-md-6 form-group">
                                <label class="form-label" for="total_amount10">Total Amount <span class="required-star">*</span></label>
                                <input type="text" id="total_amount10" class="form-control price-highlight-input @error('paid_amount') is-invalid @enderror" name="paid_amount" value="{{ old('paid_amount') !== null ? (int) round((float) old('paid_amount')) : (optional(currentTransaction($customer->learner_detail_id))->total_amount ? (int) round((float) optional(currentTransaction($customer->learner_detail_id))->total_amount) : 0) }}" readonly>
                                @error('paid_amount')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                                <span id="chargeable_days10" class="text-info mt-1 d-block small"></span>
                            </div>
                            <input type="hidden" name="previous_pending" id="previous_pending10" value="{{ old('previous_pending', (int) round((float) totalPending($customer->id))) }}">
                        </div>
                    </div>
                </div>

                {{-- 5. CARD: ACTION --}}
                <div class="plan-card">
                    <div class="plan-card-header header-blue">
                        <div class="plan-header-left">
                            <div class="plan-header-icon">
                                <i class="fa-solid fa-bolt"></i>
                            </div>
                            <div>
                                <h4 class="plan-header-title">Action</h4>
                                <p class="plan-header-subtitle">Schedule payment timing and complete the transaction.</p>
                            </div>
                        </div>
                    </div>
                    <div class="plan-card-body">
                        <div class="action-card-rows">
                            {{-- ROW 1: TIMING & DIFFERENCE AMOUNT / PAY LATER NOTICE TILE --}}
                            <div class="row g-2 action-timing-row align-items-end">
                                <div class="col-md-6 form-group timing-col" id="timingCol10">
                                    <label class="form-label" id="refund_pay_timing_label10" for="refund_pay_timing10">{{ $whenLabel }} <span class="required-star">*</span></label>
                                    <select id="refund_pay_timing10" name="refund_pay_timing" class="form-select @error('refund_pay_timing') is-invalid @enderror">
                                        <option value="">Select</option>
                                        <option value="now" {{ old('refund_pay_timing') == 'now' ? 'selected' : '' }}>Now</option>
                                        <option value="later" {{ old('refund_pay_timing') == 'later' ? 'selected' : '' }}>Later</option>
                                    </select>
                                    @error('refund_pay_timing')
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>

                                {{-- When Timing is Now: Difference Amount to pay / refund input --}}
                                <div class="col-md-6 form-group diff-amount-col" id="diffAmountCol10" style="{{ (old('refund_pay_timing') == 'later') ? 'display: none;' : '' }}">
                                    <label class="form-label" for="diffrence_amount10" id="diffrence_amount_label10">{{ $diffLabel }} <span class="required-star">*</span></label>
                                    <input type="text" class="form-control diff-amount-input @error('diffrence_amount') is-invalid @enderror"
                                        name="diffrence_amount" id="diffrence_amount10" data-sign="{{ $diffSign }}" value="{{ $diffAbs }}" placeholder="0">
                                    @error('diffrence_amount')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>

                                {{-- When Timing is Later: Symmetrical Pay Later Notice Tile (Prevents Grid Shift) --}}
                                <div class="col-md-6 form-group pay-later-info-col" id="payLaterInfoCol10" style="{{ (old('refund_pay_timing') == 'later') ? '' : 'display: none;' }}">
                                    <label class="form-label text-muted">Settlement Notice</label>
                                    <div class="pay-later-status-pill">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                        <div class="pay-later-status-text">
                                            <strong>Pay Later Selected</strong> — Full amount added to Pending Due
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ROW 2: SETTLEMENT BREAKDOWN (PENDING, DUE DATE, PAYMENT MODE) --}}
                            <div class="row g-2 action-settlement-row">
                                <div class="col-md-4 form-group">
                                    <label class="form-label" for="pending_amt10" id="pending_amt_label10">{{ $pendingLabel }} <span class="required-star">*</span></label>
                                    <input type="text" id="pending_amt10" class="form-control highlight-pending-input" name="pending_amount" placeholder="0" value="{{ $pendingAbs }}" readonly>
                                    <span id="pending_amt_error" class="text-danger small mt-1 d-block"></span>
                                </div>

                                <div class="col-md-4 form-group">
                                    <label class="form-label" for="due_date10">Choose Due Date <span class="required-star" id="due_date_star" style="{{ (old('refund_pay_timing') == 'later' || old('payment_mode') == '3' || (float)$pendingAbs > 0) ? '' : 'display: none;' }}">*</span></label>
                                    <div class="date-picker-input-wrap position-relative">
                                        <input type="text" id="due_date10" class="form-control duedate @error('due_date') is-invalid @enderror" placeholder="YYYY-MM-DD" name="due_date" value="{{ old('due_date', $customer->due_date ?? '') }}" readonly autocomplete="off">
                                        <i class="fa-regular fa-calendar-days date-input-calendar-icon"></i>
                                    </div>
                                    <span class="invalid-feedback d-block" id="due_date_client_error" style="{{ $errors->has('due_date') ? '' : 'display: none !important;' }}">
                                        <strong>{{ $errors->first('due_date') }}</strong>
                                    </span>
                                </div>

                                <div class="col-md-4 form-group">
                                    <label class="form-label" for="payment_mode10">Payment Mode <span class="required-star">*</span></label>
                                    <select name="payment_mode" id="payment_mode10" class="form-select @error('payment_mode') is-invalid @enderror">
                                        <option value="">Select Payment Mode</option>
                                        <option value="1" {{ $customer->payment_mode == 1 ? 'selected' : '' }}>Online</option>
                                        <option value="2" {{ $customer->payment_mode == 2 ? 'selected' : '' }}>Offline</option>
                                        <option value="3" {{ $customer->payment_mode == 3 ? 'selected' : '' }}>Pay Later</option>
                                    </select>
                                    @error('payment_mode')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 6. ACTION BUTTON BAR (CANCEL BUTTON REMOVED) --}}
                <div class="form-action-bar">
                    <button type="submit" class="btn-submit-operation" id="editPlanSubmit">
                        <i class="fa-solid fa-arrows-rotate"></i> Update Plan
                    </button>
                </div>

            </div>

        </form>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof flatpickr !== 'undefined') {
            flatpickr("#due_date10", {
                dateFormat: "Y-m-d",
                minDate: "today",
                disableMobile: "true",
                allowInput: true,
                onChange: function(selectedDates, dateStr) {
                    if (dateStr) {
                        $('#due_date10').removeClass('is-invalid');
                        $('#due_date_client_error').hide().find('strong').text('');
                    }
                }
            });
        }

        function cleanAmountVal(val) {
            if (val === null || val === undefined || val === '') return '';
            const num = parseFloat(val);
            if (isNaN(num)) return val;
            return Math.round(num).toString();
        }

        function cleanAllInputs() {
            const fields = [
                '#total_amount10',
                '#diffrence_amount10',
                '#pending_amt10',
                '#plan_price10',
                '#previous_amount10',
                '#locker_amount10',
                '#discount_amount10'
            ];
            fields.forEach(function(sel) {
                const el = $(sel);
                if (el.length && el.val() !== '') {
                    const raw = el.val().toString();
                    if (raw.indexOf('.') !== -1) {
                        el.val(cleanAmountVal(raw));
                    }
                }
            });
        }

        // Clean on initial load
        cleanAllInputs();

        // Amount to Refund/pay and Pending are derived from the plan/price already on this page,
        // so compute them immediately on load too
        if (!$('#diffrence_amount10').val()) {
            calculatePaidAmount();
            const $diffField = $('#diffrence_amount10');
            const sign = parseFloat($diffField.attr('data-sign')) || 1;
            const absVal = Math.round(Math.abs(parseFloat($diffField.val()) || 0));
            calculatePending(sign * absVal);
            cleanAllInputs();
        }

        // Form submit validation & pre-submission processing
        const formElement = document.getElementById('editPlanForm');
        if (formElement) {
            formElement.addEventListener('submit', function(e) {
                const timing = $('#refund_pay_timing10').val();
                const paymentMode = $('#payment_mode10').val();
                const pendingAmt = parseFloat($('#pending_amt10').val()) || 0;
                const dueDate = ($('#due_date10').val() || '').trim();

                // Due Date is required when payment mode is Pay Later ('3'), timing is 'later', or there is a pending amount
                const isDueDateRequired = (timing === 'later' || paymentMode === '3' || pendingAmt > 0);
                if (isDueDateRequired && !dueDate) {
                    e.preventDefault();
                    $('#due_date10').addClass('is-invalid');
                    $('#due_date_client_error').show().find('strong').text('Due date is required when payment mode is Pay Later or pending amount exists.');
                    const dueDateEl = document.getElementById('due_date10');
                    if (dueDateEl) {
                        dueDateEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        if (dueDateEl._flatpickr) {
                            dueDateEl._flatpickr.open();
                        }
                    }
                    return false;
                } else {
                    $('#due_date10').removeClass('is-invalid');
                    $('#due_date_client_error').hide().find('strong').text('');
                }

                const diffField = document.getElementById('diffrence_amount10');
                if (diffField) {
                    const sign = parseFloat(diffField.getAttribute('data-sign')) || 1;
                    const absVal = Math.round(Math.abs(parseFloat(diffField.value) || 0));
                    diffField.value = (sign * absVal).toString();
                }
                cleanAllInputs();
            });
        }

        function syncTimingAndDiffLayout() {
            const timing = $('#refund_pay_timing10').val();
            const $diffCol = $('#diffAmountCol10');
            const $payLaterCol = $('#payLaterInfoCol10');
            const sign = parseFloat($('#diffrence_amount10').attr('data-sign')) || 1;
            const isRefund = sign < 0;
            const paymentMode = $('#payment_mode10').val();
            const pendingAmt = parseFloat($('#pending_amt10').val()) || 0;

            // Sync required star on Due Date
            if (timing === 'later' || paymentMode === '3' || pendingAmt > 0) {
                $('#due_date_star').show();
            } else {
                $('#due_date_star').hide();
            }

            if (timing === 'later') {
                $diffCol.hide();
                $payLaterCol.show();
                if (isRefund) {
                    $payLaterCol.find('.pay-later-status-text').html('<strong>Refund Later Selected</strong> — Scheduled as Pending Refund');
                } else {
                    $payLaterCol.find('.pay-later-status-text').html('<strong>Pay Later Selected</strong> — Full amount added to Pending Due');
                }
            } else {
                $payLaterCol.hide();
                $diffCol.show();
            }
        }

        function updateLivePriceSummary() {
            syncTimingAndDiffLayout();
            cleanAllInputs();
            const prevAmount = Math.round(parseFloat($('#previous_amount10').val()) || 0);
            const totalAmount = Math.round(parseFloat($('#total_amount10').val()) || 0);
            const diffField = $('#diffrence_amount10');
            const diffVal = Math.round(parseFloat(diffField.val()) || 0);
            const sign = parseFloat(diffField.attr('data-sign')) || 1;
            const isRefund = sign < 0;
            const timing = $('#refund_pay_timing10').val();
            const isLater = timing === 'later';
            const pendingVal = Math.round(parseFloat($('#pending_amt10').val()) || diffVal);

            $('#summaryPrevPaid').text('₹' + prevAmount);
            $('#summaryNewTotal').text('₹' + totalAmount);
            if (isLater) {
                $('#summaryDiffLabel').text(isRefund ? 'Pending Refund (Later)' : 'Pending Due (Pay Later)');
                $('#summaryDiffBadge').text('₹' + pendingVal);
            } else {
                $('#summaryDiffLabel').text(isRefund ? 'Amount to Refund' : 'Amount to pay');
                $('#summaryDiffBadge').text('₹' + diffVal);
            }

            if (isRefund) {
                $('#summaryDiffBadge').removeClass('pill-pay').addClass('pill-refund');
            } else {
                $('#summaryDiffBadge').removeClass('pill-refund').addClass('pill-pay');
            }

            // Subtle pulse on banner
            const $banner = $('#livePriceSummaryBanner');
            $banner.addClass('price-pulse');
            setTimeout(() => $banner.removeClass('price-pulse'), 500);
        }

        // Initialize layout and live price summary
        syncTimingAndDiffLayout();
        updateLivePriceSummary();

        // Direct listener on timing change for immediate zero-latency feedback
        $('#refund_pay_timing10').on('change', function() {
            syncTimingAndDiffLayout();
            setTimeout(function() {
                cleanAllInputs();
                updateLivePriceSummary();
            }, 30);
        });

        // Listen for price and field updates
        $(document).on('input change', '#start_date10, #plan_price10, #total_amount10, #diffrence_amount10, #discountType10, #discount_amount10, #toggleFieldCheckbox10, #locker_amount10, #refund_pay_timing10, #payment_mode10', function() {
            syncTimingAndDiffLayout();
            setTimeout(function() {
                cleanAllInputs();
                updateLivePriceSummary();
            }, 60);
        });

        // Heartbeat monitor to catch any programmatic updates from external scripts
        let lastDiffVal = $('#diffrence_amount10').val();
        let lastTotalVal = $('#total_amount10').val();
        let lastPendingVal = $('#pending_amt10').val();
        setInterval(function() {
            const curDiff = $('#diffrence_amount10').val();
            const curTotal = $('#total_amount10').val();
            const curPending = $('#pending_amt10').val();
            if (curDiff !== lastDiffVal || curTotal !== lastTotalVal || curPending !== lastPendingVal) {
                lastDiffVal = curDiff;
                lastTotalVal = curTotal;
                lastPendingVal = curPending;
                cleanAllInputs();
                updateLivePriceSummary();
            }
        }, 150);

        // Mobile info details collapse toggle
        const btnToggleDetails = document.getElementById('btnToggleDetails');
        const infoGrid = document.getElementById('glassInfoGrid');

        if (btnToggleDetails && infoGrid) {
            btnToggleDetails.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const isCurrentlyCollapsed = infoGrid.classList.contains('is-collapsed');

                if (isCurrentlyCollapsed) {
                    infoGrid.classList.remove('is-collapsed');
                    btnToggleDetails.classList.remove('is-collapsed');
                    btnToggleDetails.setAttribute('aria-expanded', 'true');
                } else {
                    infoGrid.classList.add('is-collapsed');
                    btnToggleDetails.classList.add('is-collapsed');
                    btnToggleDetails.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });
</script>

@endsection
