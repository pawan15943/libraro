@extends('layouts.library')
@section('content')
@php

$current_route = Route::currentRouteName();
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
    $statusText = 'Inactive / Expired';
    $statusClass = 'status-expired';
    $statusIcon = 'fa-solid fa-circle-xmark';
}

if($customer->locker_no){
    $locker_read='';
}else{
    $locker_read='readonly';
}
@endphp

{{-- Scoped CSS for Learner Reactive Plan Module --}}
<link rel="stylesheet" href="{{ asset('public/css/learner-reactive-plan.css') }}?v={{ time() }}">

<div class="learner-reactive-plan-module">
    <div class="reactive-plan-wrapper">

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

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <div class="d-flex align-items-start">
                    <i class="fa-solid fa-circle-exclamation me-2 mt-1"></i>
                    <div>
                        <strong>Please resolve the following error(s):</strong>
                        <ul class="mb-0 ps-3 mt-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
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
                {{-- Tile 1: Previous Plan --}}
                <div class="glass-tile tile-plan">
                    <div class="glass-tile-icon">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Previous Plan</div>
                        <div class="glass-tile-value">{{ $customer->plan_name ?? 'N/A' }}</div>
                    </div>
                </div>

                {{-- Tile 2: Previous Shift --}}
                <div class="glass-tile tile-shift">
                    <div class="glass-tile-icon">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Previous Shift</div>
                        <div class="glass-tile-value">{{ $customer->plan_type_name ?? 'N/A' }}</div>
                    </div>
                </div>

                {{-- Tile 3: Expired On --}}
                <div class="glass-tile tile-date">
                    <div class="glass-tile-icon">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Expired On</div>
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
                            @elseif($customer->learner && $customer->learner->mobile)
                                <a href="tel:{{ $customer->learner->mobile }}">{{ $customer->learner->mobile }}</a>
                            @else
                                <span>Not Provided</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. CARD: OLD PLAN REFERENCE (READ ONLY) --}}
        <div class="plan-card">
            <div class="plan-card-header header-navy">
                <div class="plan-header-left">
                    <div class="plan-header-icon">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div>
                        <h4 class="plan-header-title">Old Plan Summary</h4>
                        <p class="plan-header-subtitle">Historical reference of learner's previous subscription details.</p>
                    </div>
                </div>
            </div>
            <div class="plan-card-body">
                <div class="row g-3">
                    <div class="col-md-4 form-group">
                        <label class="form-label">Previous Plan</label>
                        <input class="form-control" value="{{ $customer->plan_name }}" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="form-label">Previous Shift</label>
                        <input class="form-control" value="{{ $customer->plan_type_name }}" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="form-label">Plan Price</label>
                        <input class="form-control" value="{{ (int) round($customer->plan_price_id ?? 0) }}" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="form-label">Plan Started On</label>
                        <input type="date" class="form-control" value="{{ $customer->plan_start_date }}" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="form-label">Plan Ended On</label>
                        <input type="date" class="form-control" value="{{ $customer->plan_end_date }}" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="form-label">Locker Allotted?</label>
                        <input class="form-control" value="{{ $hasLocker === 'yes' ? 'Yes' : 'No' }}" readonly>
                    </div>
                    @if($hasLocker === 'yes')
                    <div class="col-md-4 form-group">
                        <label class="form-label">Locker Amount</label>
                        <input type="text" class="form-control" value="{{ (int) round(currentTransaction($customer->learner_detail_id)->locker_amount ?? 0) }}" readonly>
                    </div>
                    @endif
                    @if(optional(currentTransaction($customer->learner_detail_id))->discount_amount)
                    <div class="col-md-4 form-group">
                        <label class="form-label">Discount Amount</label>
                        <input type="text" class="form-control" value="{{ (int) round(currentTransaction($customer->learner_detail_id)->discount_amount ?? 0) }}" readonly>
                    </div>
                    @endif
                    <div class="col-md-4 form-group">
                        <label class="form-label">Total Amount</label>
                        <input type="text" class="form-control" value="{{ (int) round(currentTransaction($customer->learner_detail_id)->total_amount ?? 0) }}" readonly>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. FORM WRAPPER FOR REACTIVE PLAN --}}
        <form action="{{ route('learner.reactive.store', $customer->id) }}" method="POST" enctype="multipart/form-data" id="reactive">
            @csrf
            @method('PUT')

            <input id="user_id" type="hidden" name="user_id" value="{{$customer->id }}">
            <input type="hidden" name="learner_id" value="{{$customer->id }}">
            <input id="learner_detail" type="hidden" name="learner_detail" value="{{$customer->learner_detail_id }}">
            <input type="hidden" name="payment_type" value="REACTIVE" id="payment_type_operation">

            {{-- 4. CARD: ACTIVATE NEW PLAN --}}
            <div class="plan-card">
                <div class="plan-card-header header-green">
                    <div class="plan-header-left">
                        <div class="plan-header-icon">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                        <div>
                            <h4 class="plan-header-title">Activate New Plan</h4>
                            <p class="plan-header-subtitle">Reactivate learner with a fresh plan, seat allotment, and fee collection.</p>
                        </div>
                    </div>
                </div>
                <div class="plan-card-body">
                    <div class="plan-tip-box">
                        <i class="fa-solid fa-gem"></i>
                        <div>
                            <strong>Note:</strong> Here you can activate an existing learner into an available seat with a new plan.
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4 form-group">
                            <label class="form-label" for="plan_id10">Plan <span class="required-star">*</span></label>
                            <select id="plan_id10" class="form-select @error('plan_id') is-invalid @enderror" name="plan_id">
                                <option value="">Select Plan</option>
                                @foreach($plans as $key => $value)
                                <option value="{{ $value->id }}"
                                    {{ old('plan_id', $customer->plan_id) == $value->id ? 'selected' : '' }}>
                                    {{ $value->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('plan_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="form-label" for="plan_type_id10">Plan Type (Shift) <span class="required-star">*</span></label>
                            <select id="plan_type_id10" class="form-select @error('plan_type_id') is-invalid @enderror" name="plan_type_id">
                                <option value="">Select Plan Type</option>
                                @foreach($planTypes as $planType)
                                <option value="{{ $planType->id }}"
                                    {{ old('plan_type_id', $customer->plan_type_id) == $planType->id ? 'selected' : '' }}>
                                    {{ $planType->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('plan_type_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="form-label" for="plan_price10">Plan Price <span class="required-star">*</span></label>
                            <input id="plan_price10" class="form-control price-highlight-input @error('plan_price_id') is-invalid @enderror" name="plan_price_id" value="{{ old('plan_price_id') }}" readonly placeholder="Plan Price">
                            @error('plan_price_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="form-label" for="seat_search_input">
                                <span>Select Seat <span class="required-star">*</span></span>
                                <span class="form-label-badge" id="seatAvailableBadge">{{ count($newAvailableSeats) }} Available</span>
                            </label>

                            {{-- Native select kept in sync for form submission and validation --}}
                            <select name="seat_no" id="new_seat_id2" class="d-none @error('seat_no') is-invalid @enderror">
                                <option value="">General</option>
                                @foreach($newAvailableSeats as $key => $value)
                                <option value="{{ $value['main'] }}" {{ $customer->seat_no == $value['main'] ? 'selected' : '' }}>{{ $value['display'] }}</option>
                                @endforeach
                            </select>

                            {{-- Searchable Combobox Field --}}
                            <div class="seat-search-combobox" id="seatSearchCombobox">
                                <div class="seat-search-input-wrapper @error('seat_no') is-invalid @enderror" id="seatSearchInputWrapper">
                                    <span class="seat-search-icon">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </span>
                                    <input 
                                        type="text" 
                                        id="seat_search_input" 
                                        class="seat-search-input" 
                                        placeholder="Search seat (e.g. 12, Floor 1)..." 
                                        autocomplete="off"
                                        spellcheck="false"
                                    >
                                    <button type="button" class="seat-search-clear-btn" id="seatSearchClearBtn" title="Clear selection" style="display: none;">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                    <button type="button" class="seat-search-dropdown-arrow" id="seatSearchDropdownArrow" title="Show all seats" tabindex="-1">
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </button>
                                </div>

                                {{-- Dropdown List --}}
                                <div class="seat-dropdown-menu" id="seatDropdownMenu" style="display: none;">
                                    <div class="seat-dropdown-meta">
                                        <span id="seatDropdownCount">Available Seats ({{ count($newAvailableSeats) }})</span>
                                        <span class="seat-dropdown-hint">Type to filter</span>
                                    </div>
                                    <div class="seat-dropdown-list" id="seatDropdownList" role="listbox">
                                        {{-- General Seat Option --}}
                                        <div class="seat-dropdown-item {{ empty($customer->seat_no) ? 'is-selected' : '' }}" data-value="" data-text="general unreserved open seat" data-display="General">
                                            <div class="seat-item-left">
                                                <i class="fa-solid fa-chair seat-item-icon general-icon"></i>
                                                <div class="seat-item-info">
                                                    <span class="seat-item-title">General Seat</span>
                                                    <span class="seat-item-subtitle">Unreserved / Open Seat</span>
                                                </div>
                                            </div>
                                            <span class="seat-item-tag general-tag">General</span>
                                        </div>

                                        {{-- Available Seats List --}}
                                        @foreach($newAvailableSeats as $key => $value)
                                        <div class="seat-dropdown-item {{ $customer->seat_no == $value['main'] ? 'is-selected' : '' }}" data-value="{{ $value['main'] }}" data-text="{{ strtolower($value['display'] . ' ' . $value['main'] . ' seat ' . $value['main']) }}" data-display="{{ $value['display'] }}">
                                            <div class="seat-item-left">
                                                <i class="fa-solid fa-chair seat-item-icon"></i>
                                                <div class="seat-item-info">
                                                    <span class="seat-item-title">{{ $value['display'] }}</span>
                                                    <span class="seat-item-subtitle">Seat #{{ $value['main'] }}</span>
                                                </div>
                                            </div>
                                            <span class="seat-item-tag">Available</span>
                                        </div>
                                        @endforeach

                                        {{-- No Results Match Found --}}
                                        <div class="seat-dropdown-empty" id="seatDropdownEmpty" style="display: none;">
                                            <i class="fa-solid fa-ban me-2"></i> No seats match "<span id="seatEmptyQuery"></span>"
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @error('seat_no')
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="form-label" for="start_date10">Plan Starts On <span class="required-star">*</span></label>
                            <input type="date" class="form-control @error('plan_start_date') is-invalid @enderror" placeholder="Plan Starts On" name="plan_start_date" id="start_date10" min="{{ \Carbon\Carbon::parse($customer->plan_end_date)->format('Y-m-d') }}" value="{{ old('plan_start_date', now()->lt(\Carbon\Carbon::parse($customer->plan_end_date)) ? \Carbon\Carbon::parse($customer->plan_end_date)->format('Y-m-d') : now()->format('Y-m-d')) }}">
                            @error('plan_start_date')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- 5. CARD: PLAN ADD-ON'S (COLLAPSIBLE) --}}
            <div class="plan-card">
                <div class="plan-card-header header-amber collapsible-header" data-bs-toggle="collapse" data-bs-target="#planAddonsCollapse" aria-expanded="false" aria-controls="planAddonsCollapse">
                    <div class="plan-header-left">
                        <div class="plan-header-icon">
                            <i class="fa-solid fa-cube"></i>
                        </div>
                        <div>
                            <h4 class="plan-header-title">Plan Add-on's</h4>
                            <p class="plan-header-subtitle">Configure optional locker allotment and discount preferences.</p>
                        </div>
                    </div>
                    <div class="plan-header-toggle">
                        <i class="fa-solid fa-chevron-down toggle-icon"></i>
                    </div>
                </div>
                <div class="collapse @if($errors->has('locker_amount') || $errors->has('locker_no') || $errors->has('discount_amount')) show @endif" id="planAddonsCollapse">
                    <div class="plan-card-body">
                        <div class="row g-3">
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
                                <input type="text" id="locker_amount10" class="form-control @error('locker_amount') is-invalid @enderror" name="locker_amount" placeholder="0" value="{{ old('locker_amount', (int) round($locker_amt)) }}" readonly>
                                @error('locker_amount')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="col-md-4 form-group {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer2">
                                <label class="form-label" for="locker_no10">Locker No.</label>
                                <input type="text" class="form-control digit-only @error('locker_no') is-invalid @enderror" name="locker_no" id="locker_no10" placeholder="Enter Locker No." value="{{ old('locker_no', $customer->locker_no) }}" {{$locker_read ?? ''}}>
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
                                    <option value="amount" {{ old('discountType', $selectedDiscountType) === 'amount' ? 'selected' : '' }}>Amount</option>
                                    <option value="percentage" {{ old('discountType', $selectedDiscountType) === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                </select>
                            </div>

                            <div class="col-md-6 form-group">
                                <label class="form-label" for="discount_amount10">Discount Amount (<span id="typeVal10">INR / %</span>)</label>
                                <input type="text" id="discount_amount10" class="form-control @error('discount_amount') is-invalid @enderror" placeholder="0" name="discount_amount" value="{{ old('discount_amount', (int) round(currentTransaction($customer->learner_detail_id)->discount_amount ?? 0)) }}" readonly>
                                @error('discount_amount')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- 6. CARD: PAYMENT & SETTLEMENT --}}
            <div class="plan-card">
                <div class="plan-card-header header-blue">
                    <div class="plan-header-left">
                        <div class="plan-header-icon">
                            <i class="fa-solid fa-calculator"></i>
                        </div>
                        <div>
                            <h4 class="plan-header-title">Payment &amp; Settlement</h4>
                            <p class="plan-header-subtitle">Review total calculation, due dates, and pending balance.</p>
                        </div>
                    </div>
                </div>
                <div class="plan-card-body">
                    <div class="row g-3">
                        {{-- ROW 1: Previous Pending Amount & Total Amount in Single Row --}}
                        <div class="col-md-6 form-group">
                            <label class="form-label" for="previous_pending10">Previous Pending Amount <span class="required-star">*</span></label>
                            <input type="text" class="form-control @error('previous_pending') is-invalid @enderror" name="previous_pending" id="previous_pending10" value="{{ old('previous_pending', (int) round(totalPending($customer->id))) }}" readonly>
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="form-label" for="total_amount10">Total Amount <span class="required-star">*</span></label>
                            <input type="text" id="total_amount10" class="form-control @error('paid_amount') is-invalid @enderror" name="paid_amount" placeholder="0" value="{{ old('paid_amount') }}" {{ (Route::currentRouteName() == 'learner.change.plan' ) ? 'readonly' : '' }}>
                            @error('paid_amount')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                            <span id="chargeable_days10" class="text-info mt-1 d-block small"></span>
                        </div>

                        {{-- ROW 2: Pending Amount, Due Date & Payment Mode in Next Row --}}
                        <div class="col-md-4 form-group">
                            <label class="form-label" for="pending_amt10">Pending Amount <span class="required-star">*</span></label>
                            <input type="text" id="pending_amt10" class="form-control" placeholder="0" value="{{ old('pending_amount') }}" readonly>
                            <span id="pending_amt_error" class="text-danger small mt-1 d-block"></span>
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="form-label" for="due_date10">Choose Due Date <span class="required-star" id="due_date_star_reactive" style="display: none;">*</span></label>
                            <div class="booking-date-group">
                                <input type="date" id="due_date10" class="form-control duedate @error('due_date') is-invalid @enderror" placeholder="Enter Due Date" name="due_date" readonly value="{{ old('due_date') }}">
                                <i class="fa-regular fa-calendar-days booking-date-icon"></i>
                            </div>
                            @error('due_date')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                            <span class="invalid-feedback d-block" id="due_date_reactive_error" style="display: none !important;"><strong></strong></span>
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="form-label" for="payment_mode">Payment Mode <span class="required-star">*</span></label>
                            <select name="payment_mode" id="payment_mode" class="form-select @error('payment_mode') is-invalid @enderror">
                                <option value="">Select Payment Mode</option>
                                <option value="1" {{ old('payment_mode') == 1 ? 'selected' : '' }}>Online</option>
                                <option value="2" {{ old('payment_mode') == 2 ? 'selected' : '' }}>Offline</option>
                                <option value="3" {{ old('payment_mode') == 3 ? 'selected' : '' }}>Pay Later</option>
                            </select>
                            @error('payment_mode')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- 7. ACTION BUTTON BAR (CANCEL REMOVED, PRIMARY PILL BUTTON) --}}
            <div class="form-action-bar">
                <button type="submit" class="btn-submit-operation" id="submit">
                    <i class="fa-solid fa-bolt"></i> Reactivate Plan
                </button>
            </div>

        </form>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Activity change handler
    if (typeof handleFormChanges === 'function') {
        handleFormChanges('reactive', {{ $customer->id }});
    }

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

    // Searchable Seat Combobox logic
    const searchInput = document.getElementById('seat_search_input');
    const clearBtn = document.getElementById('seatSearchClearBtn');
    const arrowBtn = document.getElementById('seatSearchDropdownArrow');
    const wrapper = document.getElementById('seatSearchInputWrapper');
    const combobox = document.getElementById('seatSearchCombobox');
    const dropdown = document.getElementById('seatDropdownMenu');
    const countMeta = document.getElementById('seatDropdownCount');
    const emptyState = document.getElementById('seatDropdownEmpty');
    const emptyQuery = document.getElementById('seatEmptyQuery');
    const nativeSelect = document.getElementById('new_seat_id2');
    const items = Array.from(document.querySelectorAll('#seatDropdownList .seat-dropdown-item'));

    if (searchInput && dropdown && nativeSelect) {
        let selectedValue = '';
        let selectedDisplay = '';
        let highlightedIndex = -1;

        // Initialize selection from existing state
        const initialSelected = items.find(el => el.classList.contains('is-selected'));
        if (initialSelected) {
            selectedValue = initialSelected.getAttribute('data-value') || '';
            selectedDisplay = initialSelected.getAttribute('data-display') || '';
            searchInput.value = selectedDisplay;
            if (selectedDisplay) {
                clearBtn.style.display = 'inline-flex';
            }
        } else if (nativeSelect.value) {
            const matchedItem = items.find(el => el.getAttribute('data-value') === nativeSelect.value);
            if (matchedItem) {
                selectedValue = matchedItem.getAttribute('data-value') || '';
                selectedDisplay = matchedItem.getAttribute('data-display') || '';
                searchInput.value = selectedDisplay;
                matchedItem.classList.add('is-selected');
                clearBtn.style.display = 'inline-flex';
            }
        }

        function updateDropdownPosition() {
            if (!dropdown || (dropdown.style.display !== 'flex' && !dropdown.classList.contains('is-open'))) return;
            const rect = wrapper.getBoundingClientRect();
            const dropdownHeight = dropdown.offsetHeight || 210;
            const spaceBelow = window.innerHeight - rect.bottom;
            const spaceAbove = rect.top;

            // Flip upwards if tight at the bottom
            if (spaceBelow < (dropdownHeight + 20) && spaceAbove > dropdownHeight) {
                dropdown.classList.add('drop-up');
            } else {
                dropdown.classList.remove('drop-up');
            }
        }

        const parentCard = combobox.closest('.plan-card');

        function openDropdown() {
            dropdown.style.display = 'flex';
            dropdown.classList.add('is-open');
            wrapper.classList.add('is-focused');
            if (parentCard) {
                parentCard.classList.add('has-open-combobox');
                parentCard.style.zIndex = '9999';
            }
            updateDropdownPosition();
        }

        function closeDropdown() {
            dropdown.style.display = 'none';
            dropdown.classList.remove('is-open');
            dropdown.classList.remove('drop-up');
            wrapper.classList.remove('is-focused');
            if (parentCard) {
                parentCard.classList.remove('has-open-combobox');
                parentCard.style.zIndex = '';
            }
            highlightedIndex = -1;
            items.forEach(el => el.classList.remove('is-highlighted'));
            // If user typed something but didn't select, restore selected label or clear
            if (selectedDisplay) {
                searchInput.value = selectedDisplay;
                clearBtn.style.display = 'inline-flex';
            } else {
                searchInput.value = '';
                clearBtn.style.display = 'none';
            }
            filterSeats('');
        }

        function filterSeats(query) {
            const cleanQuery = (query || '').toLowerCase().trim();
            let visibleCount = 0;

            items.forEach(item => {
                const text = (item.getAttribute('data-text') || '').toLowerCase();
                const display = (item.getAttribute('data-display') || '').toLowerCase();
                const val = (item.getAttribute('data-value') || '').toLowerCase();

                if (!cleanQuery || text.includes(cleanQuery) || display.includes(cleanQuery) || val === cleanQuery) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            if (visibleCount === 0) {
                if (emptyState) emptyState.style.display = 'flex';
                if (emptyQuery) emptyQuery.textContent = query;
                if (countMeta) countMeta.textContent = 'No seats found';
            } else {
                if (emptyState) emptyState.style.display = 'none';
                if (countMeta) countMeta.textContent = cleanQuery ? `Matching Seats (${visibleCount})` : `Available Seats (${Math.max(0, items.length - 1)})`;
            }

            highlightedIndex = -1;
            items.forEach(el => el.classList.remove('is-highlighted'));
            updateDropdownPosition();
        }

        function selectSeat(value, display) {
            selectedValue = value;
            selectedDisplay = display;

            searchInput.value = display;
            clearBtn.style.display = 'inline-flex';

            items.forEach(item => {
                if ((item.getAttribute('data-value') || '') === value) {
                    item.classList.add('is-selected');
                } else {
                    item.classList.remove('is-selected');
                }
            });

            // Update native select and fire events
            nativeSelect.value = value;
            if (window.jQuery) {
                $(nativeSelect).trigger('change');
            }
            nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));

            // Clear invalid styling if set
            wrapper.classList.remove('is-invalid');
            const seatError = document.querySelector('.invalid-feedback.d-block');
            if (seatError) seatError.style.display = 'none';

            dropdown.style.display = 'none';
            dropdown.classList.remove('is-open');
            wrapper.classList.remove('is-focused');
            if (parentCard) {
                parentCard.classList.remove('has-open-combobox');
                parentCard.style.zIndex = '';
            }
        }

        function clearSelection() {
            selectedValue = '';
            selectedDisplay = '';
            searchInput.value = '';
            clearBtn.style.display = 'none';

            items.forEach(item => item.classList.remove('is-selected'));
            nativeSelect.value = '';

            if (window.jQuery) {
                $(nativeSelect).trigger('change');
            }
            nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));

            filterSeats('');
            searchInput.focus();
        }

        // Click anywhere on wrapper to open & focus search
        wrapper.addEventListener('click', function(e) {
            if (e.target !== clearBtn && !clearBtn.contains(e.target) && e.target !== arrowBtn && !arrowBtn.contains(e.target)) {
                openDropdown();
                searchInput.focus();
                if (searchInput.value === selectedDisplay) {
                    searchInput.select();
                    filterSeats('');
                }
            }
        });

        // Event Listeners
        searchInput.addEventListener('focus', function() {
            openDropdown();
            filterSeats(this.value === selectedDisplay ? '' : this.value);
            if (this.value === selectedDisplay) {
                this.select();
            }
        });

        searchInput.addEventListener('input', function() {
            openDropdown();
            clearBtn.style.display = this.value ? 'inline-flex' : (selectedDisplay ? 'inline-flex' : 'none');
            filterSeats(this.value);
        });

        arrowBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (dropdown.style.display === 'flex' || dropdown.classList.contains('is-open')) {
                closeDropdown();
            } else {
                openDropdown();
                searchInput.focus();
                filterSeats('');
            }
        });

        clearBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            clearSelection();
        });

        items.forEach(item => {
            item.addEventListener('click', function(e) {
                e.stopPropagation();
                const val = this.getAttribute('data-value') || '';
                const disp = this.getAttribute('data-display') || 'General';
                selectSeat(val, disp);
            });
        });

        // Close on outside click
        document.addEventListener('click', function(e) {
            if (combobox && !combobox.contains(e.target)) {
                if (dropdown.style.display === 'flex' || dropdown.classList.contains('is-open')) {
                    closeDropdown();
                }
            }
        });

        // Keyboard navigation
        searchInput.addEventListener('keydown', function(e) {
            const visibleItems = items.filter(el => el.style.display !== 'none');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (dropdown.style.display !== 'flex' && !dropdown.classList.contains('is-open')) {
                    openDropdown();
                }
                if (visibleItems.length > 0) {
                    highlightedIndex = (highlightedIndex + 1) % visibleItems.length;
                    visibleItems.forEach((el, idx) => {
                        el.classList.toggle('is-highlighted', idx === highlightedIndex);
                    });
                    visibleItems[highlightedIndex].scrollIntoView({ block: 'nearest' });
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (visibleItems.length > 0) {
                    highlightedIndex = (highlightedIndex - 1 + visibleItems.length) % visibleItems.length;
                    visibleItems.forEach((el, idx) => {
                        el.classList.toggle('is-highlighted', idx === highlightedIndex);
                    });
                    visibleItems[highlightedIndex].scrollIntoView({ block: 'nearest' });
                }
            } else if (e.key === 'Enter') {
                if ((dropdown.style.display === 'flex' || dropdown.classList.contains('is-open')) && highlightedIndex >= 0 && visibleItems[highlightedIndex]) {
                    e.preventDefault();
                    const item = visibleItems[highlightedIndex];
                    selectSeat(item.getAttribute('data-value') || '', item.getAttribute('data-display') || '');
                }
            } else if (e.key === 'Escape') {
                closeDropdown();
            }
        });

        // Reposition dynamically on resize or scroll
        window.addEventListener('resize', updateDropdownPosition);
        window.addEventListener('scroll', updateDropdownPosition, true);
    }
});

$(document).ready(function () {
    $("#plan_start_date_edit").on("change", function () {
        let startDate = $(this).val();
        let totalDays = parseInt($("#total_days").val());
        if (!startDate || !totalDays || totalDays < 1) return;
        let start = new Date(startDate);
        start.setDate(start.getDate() + (totalDays - 1));
        let yyyy = start.getFullYear();
        let mm = ("0" + (start.getMonth() + 1)).slice(-2);
        let dd = ("0" + start.getDate()).slice(-2);
        let formatted = `${yyyy}-${mm}-${dd}`;
        $("#plan_end_date_edit").val(formatted).trigger("change");
    });

    // Auto-clean amounts to remove any decimals
    function cleanIntegerInputs() {
        ['#total_amount10', '#pending_amt10', '#locker_amount10', '#discount_amount10', '#plan_price10', '#previous_pending10'].forEach(function(sel) {
            const $el = $(sel);
            if ($el.length && $el.val() && $el.val().indexOf('.') !== -1) {
                const val = parseFloat($el.val());
                if (!isNaN(val)) {
                    $el.val(Math.round(val));
                }
            }
        });
    }

    // Keep inputs sanitized
    setInterval(cleanIntegerInputs, 200);

    // Dynamic due date validation & star toggle
    function syncReactiveDueDate() {
        const mode = $('#payment_mode').val();
        const pending = parseFloat($('#pending_amt10').val()) || 0;
        if (mode === '3' || pending > 0) {
            $('#due_date10').removeAttr('readonly');
            $('#due_date_star_reactive').show();
        } else {
            $('#due_date10').attr('readonly', true).removeClass('is-invalid');
            $('#due_date_star_reactive').hide();
            $('#due_date_reactive_error').hide().find('strong').text('');
        }
    }

    $(document).on('change', '#payment_mode', syncReactiveDueDate);
    $(document).on('input change', '#total_amount10', syncReactiveDueDate);

    $('#due_date10').on('input change', function() {
        if ($(this).val()) {
            $(this).removeClass('is-invalid');
            $('#due_date_reactive_error').hide().find('strong').text('');
        }
    });

    // Form client-side validation
    $('#reactive').on('submit', function(e) {
        let hasError = false;
        const mode = $('#payment_mode').val();
        const pending = parseFloat($('#pending_amt10').val()) || 0;
        const dueDate = $('#due_date10').val();

        if ((mode === '3' || pending > 0) && !dueDate) {
            $('#due_date10').addClass('is-invalid');
            const msg = mode === '3' ? 'Due Date is required when Payment Mode is Pay Later.' : 'Due Date is required when there is a pending amount.';
            $('#due_date_reactive_error').show().find('strong').text(msg);
            hasError = true;
        }

        if (hasError) {
            e.preventDefault();
            e.stopPropagation();
            const $firstError = $('.is-invalid').first();
            if ($firstError.length) {
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 140
                }, 200);
                $firstError.focus();
            }
            return false;
        }
    });
});
</script>

@endsection
