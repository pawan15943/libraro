@extends('layouts.library')
@section('content')

@php
$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class = $planDetails['class'];

$planEndDate = $customer->plan_end_date;
$today = \Carbon\Carbon::today();

if ($planEndDate) {
    $endDate = \Carbon\Carbon::parse($planEndDate);
    $diffInDays = $today->diffInDays($endDate, false);
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

if (Route::currentRouteName() == 'learner.renew.plan') {
    $displayNone = 'style="display: none;"';
    $readonlyStyle = 'pointer-events: none; background-color: #e9ecef;';
} else {
    $displayNone = '';
    $readonlyStyle = '';
}
if ($customer->locker_no) {
    $locker_read = '';
} else {
    $locker_read = 'readonly';
}

$route = route('learner.upgrade.renew.store');
$ids = Route::currentRouteName() == 'learner.renew.plan' ? 'renewSeat' : 'learnerUpgrade';
$paymentType = Route::currentRouteName() == 'learner.renew.plan' ? 'RENEW' : 'UPGRADE';
$start_date = \Carbon\Carbon::parse($customer->plan_end_date)->addDay()->format('Y-m-d');
$pageTitle = Route::currentRouteName() == 'learner.renew.plan' ? 'Renew Plan' : 'Upgrade Plan';
@endphp

{{-- Scoped CSS for Learner Upgrade Plan Module --}}
<link rel="stylesheet" href="{{ asset('public/css/learner-upgrade-plan.css') }}?v={{ time() }}">

<div class="learner-upgrade-plan-module">
    <div class="upgrade-plan-wrapper">

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

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
                            <span>UID: <strong class="seat-uid-tag">{{ $customer->learner->learner_no ?? ($customer->learner_no ?? ('#' . $customer->id)) }}</strong></span>
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

            @php
                $currentSeatNo = $customer->seat_no ?? ($customer->learner->seat_no ?? null);
                $currentBranchId = $customer->branch_id ?? ($customer->learner->branch_id ?? getCurrentBranch());
                $floorDisplay = 'Ground Floor';
                if ($currentSeatNo && is_numeric($currentSeatNo)) {
                    $floorObj = \App\Models\Floor::withoutGlobalScopes()
                        ->where('branch_id', $currentBranchId)
                        ->where('from_seat', '<=', (int)$currentSeatNo)
                        ->where('to_seat', '>=', (int)$currentSeatNo)
                        ->whereNull('deleted_at')
                        ->first();
                    if ($floorObj && !empty($floorObj->name)) {
                        $floorDisplay = str_ends_with(strtolower($floorObj->name), 'floor') ? $floorObj->name : ($floorObj->name . ' Floor');
                    } else {
                        $firstFloor = \App\Models\Floor::withoutGlobalScopes()
                            ->where('branch_id', $currentBranchId)
                            ->whereNull('deleted_at')
                            ->first();
                        if ($firstFloor && !empty($firstFloor->name)) {
                            $floorDisplay = str_ends_with(strtolower($firstFloor->name), 'floor') ? $firstFloor->name : ($firstFloor->name . ' Floor');
                        }
                    }
                }
            @endphp

            {{-- Engaging Mobile-only Seat No & Floor Strip --}}
            <div class="seat-header-mobile-meta">
                <div class="mobile-meta-pill pill-seat">
                    <span class="meta-pill-icon"><i class="fa-solid fa-chair"></i></span>
                    <div class="meta-pill-text">
                        <span class="meta-pill-label">Seat No</span>
                        <strong class="meta-pill-val">{{ $currentSeatNo ? ('#' . $currentSeatNo) : 'Not Assigned' }}</strong>
                    </div>
                </div>
                <div class="mobile-meta-divider"></div>
                <div class="mobile-meta-pill pill-floor">
                    <span class="meta-pill-icon"><i class="fa-solid fa-layer-group"></i></span>
                    <div class="meta-pill-text">
                        <span class="meta-pill-label">Floor</span>
                        <strong class="meta-pill-val">{{ $floorDisplay }}</strong>
                    </div>
                </div>
            </div>

            {{-- 4 GLASSMORPHIC DETAIL TILES (Closed by default on mobile) --}}
            <div class="glass-info-grid is-collapsed" id="glassInfoGrid">
                {{-- Tile 1: Plan Type (e.g. Monthly, Quarterly, Yearly) --}}
                <div class="glass-tile tile-plan">
                    <div class="glass-tile-icon">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Current Plan</div>
                        <div class="glass-tile-value">{{ $customer->plan_name ?? 'Monthly' }}</div>
                    </div>
                </div>

                {{-- Tile 2: Shift / Plan --}}
                <div class="glass-tile tile-shift">
                    <div class="glass-tile-icon">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Shift / Plan</div>
                        <div class="glass-tile-value">{{ $customer->plan_type_name ?? 'N/A' }}</div>
                    </div>
                </div>

                {{-- Tile 3: Valid Till --}}
                <div class="glass-tile tile-date">
                    <div class="glass-tile-icon">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Valid Till</div>
                        <div class="glass-tile-value">
                            {{ $customer->plan_end_date ? \Carbon\Carbon::parse($customer->plan_end_date)->format('d M, Y') : 'N/A' }}
                        </div>
                    </div>
                </div>

                {{-- Tile 4: Contact Mobile --}}
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
        <form action="{{ $route }}" method="POST" enctype="multipart/form-data" id="{{ $ids }}">
            @csrf
            @method('POST')

            <input type="hidden" name="learner_detail" value="{{ $customer->learner_detail_id }}">
            <input type="hidden" name="learner_id" value="{{ $customer->id }}" >
            <input type="hidden" name="user_id" value="{{ $customer->id }}" id="user_id">
            <input type="hidden" name="library_id" value="{{ $customer->library_id }}">
            <input type="hidden" name="payment_type" value="{{ $paymentType }}" id="payment_type_operation">
            <input type="hidden" id="start_date10" value="{{ $start_date }}">

            <div class="upgrade-plan-cards-column">

                {{-- 2. CARD: PLAN SELECTION --}}
                <div class="plan-card">
                    <div class="plan-card-header header-green">
                        <div class="plan-header-left">
                            <div class="plan-header-icon">
                                <i class="fa-solid fa-circle-up"></i>
                            </div>
                            <div>
                                <h4 class="plan-header-title">{{ $pageTitle }}</h4>
                                <p class="plan-header-subtitle">Choose the upcoming plan and shift type for renewal/upgrade.</p>
                            </div>
                        </div>
                    </div>
                    <div class="plan-card-body">
                        <div class="plan-tip-box">
                            <i class="fa-solid fa-gem"></i>
                            <div>
                                @if(Route::currentRouteName() == 'learner.renew.plan')
                                <strong>Note:</strong> You can renew your plan 5 days before it expires or during the extended period. Plan and shift changes are not allowed.
                                @else
                                <strong>Note:</strong> You can upgrade your plan &amp; plan type (shift) 5 days before it expires or during the extended period.
                                @endif
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-4 form-group">
                                <label class="form-label" for="plan_id10">Plan <span class="required-star">*</span></label>
                                <select id="plan_id10" class="form-select @error('plan_id') is-invalid @enderror" name="plan_id" {{ (Route::currentRouteName() == 'learner.renew.plan') ? 'readonly' : '' }}>
                                    <option value="">Select Plan</option>
                                    @foreach($plans as $key => $value)
                                    <option value="{{ $value->id }}" {{ old('plan_id', $customer->plan_id) == $value->id ? 'selected' : '' }}>{{ $value->name }}</option>
                                    @endforeach
                                </select>
                                @error('plan_id')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="col-md-4 form-group">
                                <label class="form-label" for="plan_type_id10">Plan Type (Shift) <span class="required-star">*</span></label>
                                <select id="plan_type_id10" class="form-select @error('plan_type_id') is-invalid @enderror" name="plan_type_id" {{ Route::currentRouteName() == 'learner.renew.plan' ? 'readonly' : '' }}>
                                    @foreach($filteredPlanTypes as $planType)
                                    <option value="{{ $planType['id'] }}"
                                        {{ ($customer->plan_type_id == $planType['id']) ? 'selected' : (old('plan_type_id') == $planType['id'] ? 'selected' : '') }}>
                                        {{ $planType['name'] }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('plan_type_id')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="col-md-4 form-group">
                                <label class="form-label" for="plan_price10">Plan Price <span class="required-star">*</span></label>
                                <input id="plan_price10" class="form-control price-highlight-input @error('plan_price_id') is-invalid @enderror" value="{{ old('plan_price_id') !== null ? (int) round((float) old('plan_price_id')) : ((float)$customer->plan_price_id ? (int) round((float)$customer->plan_price_id) : '') }}" name="plan_price_id" readonly>
                                @error('plan_price_id')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. CARD: PLAN ADD-ON'S (COLLAPSIBLE) --}}
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
                                <p class="plan-header-subtitle">Review new total amount, pending status, and choose payment mode.</p>
                            </div>
                        </div>
                    </div>
                    <div class="plan-card-body">
                        <div class="row g-2 mb-2">
                            {{-- ROW 1: Previous Pending Amount & Total Amount --}}
                            <div class="col-md-6 form-group">
                                <label class="form-label" for="previous_pending10">Previous Pending Amount <span class="required-star">*</span></label>
                                <input type="text" class="form-control @error('previous_pending') is-invalid @enderror" name="previous_pending" id="previous_pending10" value="{{ old('previous_pending') !== null ? (int) round((float) old('previous_pending')) : (int) round((float) totalPending($customer->id)) }}" readonly>
                            </div>

                            <div class="col-md-6 form-group">
                                <label class="form-label" for="total_amount10">Total Amount <span class="required-star">*</span></label>
                                <input type="text" id="total_amount10" class="form-control price-highlight-input @error('paid_amount') is-invalid @enderror" name="paid_amount" value="{{ old('paid_amount') !== null ? (int) round((float) old('paid_amount')) : (optional(currentTransaction($customer->learner_detail_id))->total_amount ? (int) round((float) optional(currentTransaction($customer->learner_detail_id))->total_amount) : 0) }}">
                                @error('paid_amount')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                                <span id="chargeable_days10" class="text-info mt-1 d-block small"></span>
                            </div>
                        </div>

                        {{-- ROW 2: Symmetrical Shift-Proof Architecture (Pending, Due Date, Payment Mode & Notice) --}}
                        <div class="row g-2 action-settlement-row">
                            <div class="col-md-4 form-group" id="pendingAmtCol10">
                                <label class="form-label" for="pending_amt10">Pending Amount <span class="required-star">*</span></label>
                                <input type="text" id="pending_amt10" class="form-control highlight-pending-input" name="pending_amount" placeholder="0" value="{{ old('pending_amount') !== null ? (int) round((float) old('pending_amount')) : 0 }}" readonly>
                                <span id="pending_amt_error" class="text-danger small mt-1 d-block"></span>
                            </div>

                            <div class="col-md-4 form-group" id="dueDateCol10">
                                <label class="form-label" for="due_date10">Choose Due Date <span class="required-star" id="due_date_star" style="{{ (old('payment_mode') == '3' || (float)old('pending_amount', 0) > 0) ? '' : 'display: none;' }}">*</span></label>
                                <div class="date-picker-input-wrap position-relative">
                                    <input type="text" id="due_date10" class="form-control duedate @error('due_date') is-invalid @enderror" placeholder="YYYY-MM-DD" name="due_date" value="{{ old('due_date', $customer->due_date ?? '') }}" readonly autocomplete="off">
                                    <i class="fa-regular fa-calendar-days date-input-calendar-icon"></i>
                                </div>
                                <span class="invalid-feedback d-block" id="due_date_client_error" style="{{ $errors->has('due_date') ? '' : 'display: none !important;' }}">
                                    <strong>{{ $errors->first('due_date') }}</strong>
                                </span>
                            </div>

                            <div class="col-md-4 form-group" id="paymentModeCol10">
                                <label class="form-label" for="payment_mode10">Payment Mode <span class="required-star">*</span></label>
                                <select name="payment_mode" id="payment_mode10" class="form-select @error('payment_mode') is-invalid @enderror">
                                    <option value="">Select Payment Mode</option>
                                    <option value="1" {{ old('payment_mode', $customer->payment_mode) == 1 ? 'selected' : '' }}>Online</option>
                                    <option value="2" {{ old('payment_mode', $customer->payment_mode) == 2 ? 'selected' : '' }}>Offline</option>
                                    <option value="3" {{ old('payment_mode', $customer->payment_mode) == 3 ? 'selected' : '' }}>Pay Later</option>
                                </select>
                                @error('payment_mode')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        {{-- Symmetrical Pay Later Notice Tile (Appears below without shifting columns) --}}
                        <div class="row g-2 mt-1 pay-later-info-row" id="payLaterInfoCol10" style="{{ old('payment_mode') == '3' ? '' : 'display: none;' }}">
                            <div class="col-12">
                                <div class="pay-later-status-pill">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                    <div class="pay-later-status-text">
                                        <strong>Pay Later Selected</strong> — Full amount is added to Pending Due. Please specify an agreed Due Date above.
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- 5. ACTION BUTTON BAR (CANCEL REMOVED) --}}
                <div class="form-action-bar">
                    @if($planDetails['diff_in_days'] <= 5 && $planDetails['diff_extend_day']>= 0 && !$is_renew && !$isalreadyRenew)
                        <button type="submit" class="btn-submit-operation" id="upgradePlanSubmit">
                            <i class="fa-solid fa-circle-up"></i> {{ $pageTitle }}
                        </button>
                    @else
                        <div class="text-danger fw-semibold">
                            <i class="fa-solid fa-triangle-exclamation"></i> Button is available when you renew your Seat Booking
                        </div>
                    @endif
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

        // Calendar icon click opens picker
        $(document).on('click', '.date-input-calendar-icon', function() {
            const input = $(this).siblings('.duedate')[0];
            if (input && input._flatpickr) {
                input._flatpickr.open();
            } else if (input) {
                input.focus();
            }
        });

        handleFormChanges('{{ $ids }}', {{ $customer->id }});

        function cleanAmountVal(val) {
            if (val === null || val === undefined || val === '') return '';
            const num = parseFloat(val);
            if (isNaN(num)) return val;
            return Math.round(num).toString();
        }

        function cleanAllInputs() {
            const fields = [
                '#total_amount10',
                '#pending_amt10',
                '#plan_price10',
                '#previous_pending10',
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

        // Form submit validation & pre-submission processing
        const formElement = document.getElementById('{{ $ids }}');
        if (formElement) {
            formElement.addEventListener('submit', function(e) {
                const paymentMode = $('#payment_mode10').val();
                const pendingAmt = parseFloat($('#pending_amt10').val()) || 0;
                const dueDate = ($('#due_date10').val() || '').trim();

                // Due Date is required when payment mode is Pay Later ('3') or there is a pending amount
                const isDueDateRequired = (paymentMode === '3' || pendingAmt > 0);
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

                cleanAllInputs();
            });
        }

        function syncPaymentModeAndNotice() {
            const paymentMode = $('#payment_mode10').val();
            const pendingAmt = parseFloat($('#pending_amt10').val()) || 0;
            const $payLaterNotice = $('#payLaterInfoCol10');

            if (paymentMode === '3') {
                $payLaterNotice.show();
                $('#due_date_star').show();
            } else {
                $payLaterNotice.hide();
                if (pendingAmt > 0) {
                    $('#due_date_star').show();
                } else {
                    $('#due_date_star').hide();
                }
            }
        }

        function syncSettlementState() {
            syncPaymentModeAndNotice();
            cleanAllInputs();
            const prevPending = Math.round(parseFloat($('#previous_pending10').val()) || 0);
            const totalAmount = Math.round(parseFloat($('#total_amount10').val()) || 0);
            const paymentMode = $('#payment_mode10').val();

            if (paymentMode === '3') {
                const pendingVal = totalAmount + prevPending;
                $('#pending_amt10').val(pendingVal);
            }
        }

        // Initialize layout and state
        syncPaymentModeAndNotice();
        syncSettlementState();

        // Direct listener on payment_mode change
        $('#payment_mode10').on('change', function() {
            syncPaymentModeAndNotice();
            setTimeout(function() {
                cleanAllInputs();
                syncSettlementState();
            }, 30);
        });

        // Listen for price and field updates
        $(document).on('input change', '#plan_id10, #plan_type_id10, #plan_price10, #total_amount10, #discountType10, #discount_amount10, #toggleFieldCheckbox10, #locker_amount10, #payment_mode10', function() {
            syncPaymentModeAndNotice();
            setTimeout(function() {
                cleanAllInputs();
                syncSettlementState();
            }, 60);
        });

        // Heartbeat monitor to catch any programmatic updates from external scripts
        let lastTotalVal = $('#total_amount10').val();
        let lastPendingVal = $('#pending_amt10').val();
        let lastPlanPriceVal = $('#plan_price10').val();
        setInterval(function() {
            const curTotal = $('#total_amount10').val();
            const curPending = $('#pending_amt10').val();
            const curPlanPrice = $('#plan_price10').val();
            if (curTotal !== lastTotalVal || curPending !== lastPendingVal || curPlanPrice !== lastPlanPriceVal) {
                lastTotalVal = curTotal;
                lastPendingVal = curPending;
                lastPlanPriceVal = curPlanPrice;
                cleanAllInputs();
                syncSettlementState();
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
