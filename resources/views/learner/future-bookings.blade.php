@extends('layouts.library')
@section('content')

<link rel="stylesheet" href="{{ asset('public/css/learner-list.css') }}?v={{ time() }}" />

<!-- Profile Image Preview Modal -->
<div id="imageViewModal" class="image-modal" style="display:none;opacity:0;" aria-hidden="true">
    <div class="image-modal-content">
        <span class="close-modal" role="button" tabindex="0" aria-label="Close">&times;</span>
        <img src="" id="modalImage" alt="Profile photo preview">
    </div>
</div>

@php
$hasActiveFilters = request()->filled('search') || request()->filled('plan_id') || request()->filled('status')
    || request()->filled('seat_no') || request()->filled('is_paid');
$hiddenFields = toggleHideField();
$currentBranchName = getCurrentBranchName();
$isNotificationActive = notificationActive();
$isWabaNotificationActive = $isNotificationActive && wabaNotificationActive();
$isTextNotificationActive = $isNotificationActive && textNotificationActive();
@endphp

@if ($learners->total() == 0)
<div class="no-data-found">
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>
    <dotlottie-wc src="https://lottie.host/2bd4f1dd-bce9-44cb-b8a4-f5acd681c123/sHuYyTQ6uD.lottie"
        style="width: 200px;height: 200px" autoplay loop></dotlottie-wc>
    @if($hasActiveFilters)
    <h4>No Bookings Found</h4>
    <span>No future bookings match the selected filters. Try adjusting or clearing the filters above.</span>
    <div class="heading-list justify-content-center mt-3">
        <a href="{{ route('future.bookings') }}" class="btn btn-primary export">
            <i class="fa-solid fa-rotate-right me-1"></i> Clear Filters
        </a>
    </div>
    @else
    <h4>No Future Bookings</h4>
    <span>You haven’t added any learners for upcoming dates. Add learners by clicking the button below.</span>
    <div class="heading-list justify-content-end mb-1">
        @if(getCurrentBranch() != 0)
        <a href="javascript:;" class="btn btn-primary export noseat_popup">
            <i class="fa-solid fa-plus"></i> Book Seat
        </a>
        @else
        <h4>To add Plan Prices, first select your Branch.</h4>
        <span>Plan names remain the same across all branches, but prices can be different. That’s why you need to choose the branch before adding plan prices.</span>
        @endif
    </div>
    @endif
</div>
@else

<div class="row mb-2">
    <div class="col-lg-12 text-end">
        <a href="javascript:void(0)" class="btn btn-primary export" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Filter" id="learnerFilterToggleBtn"><i class="fa-solid fa-filter"></i></a>
        <a href="{{ route('learners.export-csv') }}" class="btn btn-primary export" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Counts" id="counts"><i class="fa-solid fa-star"></i></a>

        @can('has-permission', 'Export Library Seats')
        @if(!in_array('22', $hiddenFields))
        <a href="{{ route('learners.export-csv') }}" class="btn btn-primary export" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Export Learners Data to CSV"><i class="fa-solid fa-file-export"></i></a>
        @endif
        @endcan
        @can('has-permission', 'Import Library Seats')
        @if(!in_array('11', $hiddenFields))
        <a href="{{ route('library.upload.form') }}" class="btn btn-primary export bg-4" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Import Learners Data to Portal"><i class="fa-solid fa-file-import"></i></a>
        @endif
        @endcan
    </div>
</div>

{{-- Modern Tile Bar Filter (options as is, text as is, utility as is) --}}
@can('has-permission', 'Filter')
<div class="row mb-3 learner-filter-module" id="learnerFilterContainer" style="{{ $hasActiveFilters ? '' : 'display: none;' }}">
    <div class="col-lg-12">
        <div class="learner-filter-card">
            <form action="{{ route('future.bookings') }}" method="GET" class="learner-filter-form" id="learnerFilterForm">
                <div class="learner-filter-grid">
                    <!-- Search Learner -->
                    <div class="filter-field-box filter-search-box" id="filterSearchBox">
                        <div class="filter-field-icon icon-slate">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>
                        <div class="filter-field-content">
                            <span class="filter-field-label">Search Learner</span>
                            <input type="text" 
                                   class="filter-input" 
                                   name="search" 
                                   id="search_learner_input"
                                   placeholder="Enter Name, Mobile or Email" 
                                   value="{{ request()->get('search') }}"
                                   autocomplete="off">
                        </div>
                    </div>

                    <!-- Plan -->
                    @php
                        $selectedPlanName = 'Choose Plan';
                        if (request()->filled('plan_id')) {
                            $matchedPlan = $plans->firstWhere('id', request()->get('plan_id'));
                            if ($matchedPlan) {
                                $selectedPlanName = $matchedPlan->name;
                            }
                        }
                    @endphp
                    <div class="filter-field-box custom-dropdown" id="dropdownPlan" data-dropdown="plan">
                        <div class="filter-field-icon icon-purple">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>
                        <div class="filter-field-content">
                            <span class="filter-field-label">Plan</span>
                            <span class="filter-field-value" id="plan_display">{{ $selectedPlanName }}</span>
                            <input type="hidden" name="plan_id" id="plan_id" value="{{ request()->get('plan_id') }}">
                        </div>
                        <div class="filter-field-chevron">
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>

                        <!-- Dropdown Menu -->
                        <div class="custom-dropdown-menu scrollable-dropdown" id="plan_dropdown_menu">
                            <div class="dropdown-item-option {{ !request()->filled('plan_id') ? 'active' : '' }}" data-value="">
                                <i class="fa-solid fa-file-lines item-icon text-purple"></i>
                                <span>Choose Plan</span>
                            </div>
                            @foreach($plans as $plan)
                            <div class="dropdown-item-option {{ request()->get('plan_id') == $plan->id ? 'active' : '' }}" data-value="{{ $plan->id }}">
                                <i class="fa-solid fa-book-bookmark item-icon text-purple"></i>
                                <span>{{ $plan->name }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Payment Status (Paid / Unpaid as is) -->
                    @php
                        $selectedPaymentName = 'Choose Payment';
                        if (request()->get('is_paid') === '1') {
                            $selectedPaymentName = 'Paid';
                        } elseif (request()->get('is_paid') === '0') {
                            $selectedPaymentName = 'Unpaid';
                        }
                    @endphp
                    <div class="filter-field-box custom-dropdown" id="dropdownPayment" data-dropdown="payment">
                        <div class="filter-field-icon icon-green">
                            <i class="fa-solid fa-credit-card"></i>
                        </div>
                        <div class="filter-field-content">
                            <span class="filter-field-label">Payment</span>
                            <span class="filter-field-value" id="payment_display">{{ $selectedPaymentName }}</span>
                            <input type="hidden" name="is_paid" id="is_paid" value="{{ request()->get('is_paid') }}">
                        </div>
                        <div class="filter-field-chevron">
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>

                        <div class="custom-dropdown-menu" id="payment_dropdown_menu">
                            <div class="dropdown-item-option {{ request()->get('is_paid') === null || request()->get('is_paid') === '' ? 'active' : '' }}" data-value="">
                                <i class="fa-solid fa-credit-card item-icon text-primary"></i>
                                <span>Choose Payment</span>
                            </div>
                            <div class="dropdown-item-option {{ request()->get('is_paid') === '1' ? 'active' : '' }}" data-value="1">
                                <i class="fa-solid fa-circle-check item-icon text-success"></i>
                                <span>Paid</span>
                            </div>
                            <div class="dropdown-item-option {{ request()->get('is_paid') === '0' ? 'active' : '' }}" data-value="0">
                                <i class="fa-solid fa-clock item-icon text-warning"></i>
                                <span>Unpaid</span>
                            </div>
                        </div>
                    </div>

                    <!-- Active / Expired Status -->
                    @php
                        $selectedStatusName = 'Choose Status';
                        if (request()->get('status') == 'active') {
                            $selectedStatusName = 'Active';
                        } elseif (request()->get('status') == 'expired') {
                            $selectedStatusName = 'Expired';
                        }
                    @endphp
                    <div class="filter-field-box custom-dropdown" id="dropdownStatus" data-dropdown="status">
                        <div class="filter-field-icon icon-blue">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div class="filter-field-content">
                            <span class="filter-field-label">Status</span>
                            <span class="filter-field-value" id="status_display">{{ $selectedStatusName }}</span>
                            <input type="hidden" name="status" id="status" value="{{ request()->get('status') }}">
                        </div>
                        <div class="filter-field-chevron">
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>

                        <div class="custom-dropdown-menu" id="status_dropdown_menu">
                            <div class="dropdown-item-option {{ !request()->filled('status') ? 'active' : '' }}" data-value="">
                                <i class="fa-solid fa-users item-icon text-primary"></i>
                                <span>Choose Status</span>
                            </div>
                            <div class="dropdown-item-option {{ request()->get('status') == 'active' ? 'active' : '' }}" data-value="active">
                                <i class="fa-solid fa-circle-check item-icon text-success"></i>
                                <span>Active</span>
                            </div>
                            <div class="dropdown-item-option {{ request()->get('status') == 'expired' ? 'active' : '' }}" data-value="expired">
                                <i class="fa-solid fa-circle-xmark item-icon text-danger"></i>
                                <span>Expired</span>
                            </div>
                        </div>
                    </div>

                    <!-- Seat No -->
                    @php
                        $selectedSeatName = 'Seat No';
                        if (request()->filled('seat_no')) {
                            $selectedSeatName = getSeatDisplayShortFloorName(request()->get('seat_no'));
                        }
                    @endphp
                    <div class="filter-field-box custom-dropdown" id="dropdownSeat" data-dropdown="seat">
                        <div class="filter-field-icon icon-gray">
                            <i class="fa-solid fa-hashtag"></i>
                        </div>
                        <div class="filter-field-content">
                            <span class="filter-field-label">Seat No</span>
                            <span class="filter-field-value" id="seat_display">{{ $selectedSeatName }}</span>
                            <input type="hidden" name="seat_no" id="seat_no" value="{{ request()->get('seat_no') }}">
                        </div>
                        <div class="filter-field-chevron">
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>

                        <div class="custom-dropdown-menu scrollable-dropdown" id="seat_dropdown_menu">
                            <div class="dropdown-item-option {{ !request()->filled('seat_no') ? 'active' : '' }}" data-value="">
                                <i class="fa-solid fa-hashtag item-icon text-muted"></i>
                                <span>Seat No (All)</span>
                            </div>
                            @for($seatNo = 1; $seatNo <= $totalSeats; $seatNo++)
                            <div class="dropdown-item-option {{ request()->get('seat_no') == $seatNo ? 'active' : '' }}" data-value="{{ $seatNo }}">
                                <i class="fa-solid fa-chair item-icon text-muted"></i>
                                <span>{{ getSeatDisplayShortFloorName($seatNo) }}</span>
                            </div>
                            @endfor
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="filter-actions-group">
                        <button type="submit" class="filter-btn-search" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <span>Search</span>
                        </button>
                        <button type="button" id="clearFilter" class="filter-btn-clear" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Clear Filter">
                            <i class="fa-solid fa-rotate-right"></i>
                            <span>Clear</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@if(!in_array('24', $hiddenFields))
<div class="col-lg-12 mb-3" id="countsContainer">
    <div class="records p-3 bg-white rounded-3 border shadow-sm">
        <p class="mb-2 text-dark font-outfit">
            <i class="fa-solid fa-chart-pie me-1" style="color: #18225f;"></i>
            <b>Total Seats: {{ $total_seats ?? 0 }} | Available Seats: {{ $availble_seats ?? 0 }} | Booked Seats: {{ $booked_seats ?? 0 }} | General Seats: {{ $genral_seat ?? 0 }}</b>
        </p>
        
        <div class="seat-legend-scroll-wrapper position-relative d-flex align-items-center mt-2">
            <button type="button" class="btn btn-sm btn-light border me-2 scroll-arrow-btn shadow-none" id="scrollLeftBtn" title="Scroll Left" style="z-index: 2; min-width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #18225f;">
                <i class="fa-solid fa-chevron-left"></i>
            </button>

            <div class="seat-legend-items d-flex align-items-center gap-2 overflow-hidden flex-nowrap w-100 py-1" id="seatLegendContainer" style="scroll-behavior: smooth; white-space: nowrap;">
                <div class="legend-chip d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill bg-success-subtle border border-success-subtle">
                    <i class="fa-solid fa-check-circle text-success"></i>
                    <span class="fw-bold font-outfit small text-success">AV: Available ({{ $availble_seats ?? 0 }})</span>
                </div>
                <div class="legend-chip d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill border" style="background-color: #eef2ff; border-color: #c7d2fe !important;">
                    <i class="fa-solid fa-check-circle" style="color: #18225f;"></i>
                    <span class="fw-bold font-outfit small" style="color: #18225f;">ACT: Booked ({{ $active_seat_count ?? 0 }})</span>
                </div>
                <div class="legend-chip d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill border" style="background-color: #f0f9ff; border-color: #bae6fd !important;">
                    <i class="fa-solid fa-check-circle" style="color: #0284c7;"></i>
                    <span class="fw-bold font-outfit small" style="color: #0284c7;">GEN: General ({{ $genral_seat ?? 0 }})</span>
                </div>
                <div class="legend-chip d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill border" style="background-color: #fdf2f2; border-color: #fecdd3 !important;">
                    <i class="fa-solid fa-check-circle seatBlink" style="color: #800000;"></i>
                    <span class="fw-bold font-outfit small" style="color: #800000;">EXT: Extension ({{ $extended_seats ?? 0 }})</span>
                </div>
                <div class="legend-chip d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill border" style="background-color: #eff6ff; border-color: #bfdbfe !important;">
                    <i class="fa-solid fa-check-circle seatBlink" style="color: #2E3ECD;"></i>
                    <span class="fw-bold font-outfit small" style="color: #2E3ECD;">DUE: Fee Overdue</span>
                </div>
                <div class="legend-chip d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill border" style="background-color: #fffbe6; border-color: #ffe58f !important;">
                    <i class="fa-solid fa-check-circle" style="color: #d97706;"></i>
                    <span class="fw-semibold font-outfit small" style="color: #d97706; font-weight: 600 !important;">ATE: About to Expire</span>
                </div>
                <div class="legend-chip d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill bg-danger-subtle border border-danger-subtle">
                    <i class="fa-solid fa-check-circle text-danger"></i>
                    <span class="fw-bold font-outfit small text-danger">EXP: Expired ({{ $expired_seat ?? 0 }})</span>
                </div>
                <div class="legend-chip d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill border" style="background-color: #fdf4ff; border-color: #f5d0fe !important;">
                    <i class="fa-solid fa-check-circle" style="color: #c8009d;"></i>
                    <span class="fw-bold font-outfit small" style="color: #c8009d;">NE: Non-Expiry</span>
                </div>
                <div class="legend-chip d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill border" style="background-color: #fffbeb; border-color: #fde68a !important;">
                    <i class="fa-solid fa-check-circle" style="color: #C09600;"></i>
                    <span class="fw-bold font-outfit small" style="color: #C09600;">FUT: Future Booking</span>
                </div>
                @foreach($planTypeCounts as $plan)
                <div class="legend-chip d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill bg-light border">
                    <i class="fa-solid fa-clock text-secondary"></i>
                    <span class="fw-bold font-outfit small text-dark">{{ $plan['abbr'] }}: {{ $plan['name'] }} ({{ $plan['count'] }})</span>
                </div>
                @endforeach
            </div>

            <button type="button" class="btn btn-sm btn-light border ms-2 scroll-arrow-btn shadow-none" id="scrollRightBtn" title="Scroll Right" style="z-index: 2; min-width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #18225f;">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>
@endif

<div class="learner-list-module">
<div class="mb-3 set-table">
    <p class="m-0"><b>{{ $learners->total() }} Records for {{ $learners->perPage() }} per page</b></p>
    <a class="sort" href="{{ request()->fullUrlWithQuery([
        'sort_by' => 'seat_no',
        'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'
    ]) }}">
        Sort by Seat No. 
        @if(request('sort_by') == 'seat_no')
            ({{ request('sort_order') == 'asc' ? '↑' : '↓' }})
        @endif
    </a>
</div>

@foreach($learners as $key => $value)
@php
    $learner_detail_id = $value->learner_detail_id;
    $learner_id = $value->id;
    $planStatus = getPlanStatusDetails($value->plan_end_date);
    $transaction = learnerTransaction($value->id, $learner_detail_id);
    $totalPendingAmt = optional($transaction)->pending_amount ?? 0;
    $due_date = optional($transaction)->due_date;
    $operation = optional(getLearnerOperation($learner_detail_id))->operation;
    $operationDate = optional(getLearnerOperation($learner_detail_id))->created_at;

    $formattedDueDate = !empty($due_date) ? (is_object($due_date) ? (!empty($due_date->due_date) ? date('j M', strtotime($due_date->due_date)) : '') : date('j M', strtotime($due_date))) : '';
    $hasPendingBalance = (paylater($learner_detail_id) && $totalPendingAmt != 0) || pending_amt($learner_detail_id);
    $canRenewFlag = true;
    $overdueFlag = false;
    $today = \Carbon\Carbon::now();
    $oneWeekLater = !empty($value->plan_start_date) ? \Carbon\Carbon::parse($value->plan_start_date)->addWeek() : \Carbon\Carbon::now()->addWeek();
    $threeDaysAfterStart = !empty($value->plan_start_date) ? \Carbon\Carbon::parse($value->plan_start_date)->addDays(3) : \Carbon\Carbon::now()->addDays(3);
    $paybleRefundAmt = function_exists('paybleRefund') ? paybleRefund($learner_detail_id) : 0;
    $totalExtraAmt = 0;
@endphp

<div class="row">
    <div class="col-lg-12">
        <div class="learner-card">
            
            {{-- DESKTOP LAYOUT --}}
            <div class="desktop-only-section">
                <div class="learner-top-row">
                    <div class="learner-top-left">
                        <div class="seat-badge-box">
                            <span class="seat-label">Seat No. :</span>
                            <span class="seat-val">{{ $value->seat_no ? getSeatDisplayShortFloorName($value->seat_no) : 'GEN' }}</span>
                        </div>
                        <div class="learner-meta-block">
                            <span class="learner-expiry-line">
                                @if($operation == 'closeSeat')
                                    <span class="text-secondary"><i class="fa-regular fa-clock me-1"></i> Closed Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}</span>
                                @elseif($operation == 'deleteSeat' && $value->deleted_at != null)
                                    <span class="text-danger"><i class="fa-regular fa-clock me-1"></i> Deleted Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}</span>
                                @elseif(!empty($value->plan_start_date))
                                    <span style="color: #d97706 !important;"><i class="fa-solid fa-calendar-check me-1"></i> Starts on {{ date('j M Y', strtotime($value->plan_start_date)) }}</span>
                                @else
                                    <span class="text-muted"><i class="fa-regular fa-clock me-1"></i> Future Booking</span>
                                @endif
                            </span>
                        </div>
                    </div>

                    <ul class="learner-actions-strip">
                        @include('learner.partials.learner-actions', ['isMobile' => false])
                    </ul>
                </div>

                {{-- Bottom Grid: 5 Columns --}}
                <div class="learner-bottom-grid">
                    {{-- Column 1: Learner Identity & Contact Info --}}
                    <div class="learner-profile-col">
                        <div class="avatar-wrap">
                            <a href="{{ asset($value->profile_picture ? $value->profile_picture : 'public/img/student_profile.jpeg') }}" class="view-image learner-list-profile-photo" title="View profile photo">
                                <img src="{{ asset($value->profile_picture ? $value->profile_picture : 'public/img/student_profile.jpeg') }}" alt="{{ $value->name }}" class="avatar-img">
                            </a>
                            <span class="avatar-status-dot dot-upcoming" title="Upcoming" data-bs-toggle="tooltip" data-bs-title="Upcoming"></span>
                        </div>
                        <div class="learner-details-text">
                            <h5 class="learner-name-title">{{ $value->name }}</h5>
                            <div class="detail-row">
                                <span class="detail-label">UID :</span>
                                <a href="{{ route('learners.show', $value->id) }}" class="detail-value">{{ $value->learner_no }}</a>
                                <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $value->learner_no }}" title="Copy UID">
                                    <i class="fa-regular fa-clone"></i>
                                </button>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">M :</span>
                                <a href="tel:+91-{{ $value->mobile }}" class="detail-value">+91-{{ display_learner_mobile($value->mobile) }}</a>
                                <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $value->mobile }}" title="Copy Mobile">
                                    <i class="fa-regular fa-clone"></i>
                                </button>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">E :</span>
                                @if($value->email)
                                    <a href="mailto:{{ $value->email }}" class="detail-value detail-email">{{ display_learner_email($value->email) }}</a>
                                @else
                                    <span class="text-danger detail-email" style="font-size: 11.5px;"><i class="fa-solid fa-xmark"></i> Email ID Not Available</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Column 2: Subscription Info --}}
                    <div class="info-stat-block">
                        <div class="info-icon-box info-icon-purple">
                            <i class="fa-regular fa-file-lines"></i>
                        </div>
                        <div class="info-text">
                            <span class="info-label">Subscription Info</span>
                            <span class="info-value">
                                {{ $value->plan_type_name ?? '—' }}@if(!empty($value->plan_name)) ({{ $value->plan_name }})@endif
                            </span>
                        </div>
                    </div>

                    {{-- Column 3: Plan Duration --}}
                    <div class="info-stat-block">
                        <div class="info-icon-box info-icon-blue">
                            <i class="fa-regular fa-calendar-days"></i>
                        </div>
                        <div class="info-text">
                            <span class="info-label">Plan Duration</span>
                            <span class="info-value">
                                @if(!empty($value->plan_start_date) && !empty($value->plan_end_date))
                                    {{ date('j M Y', strtotime($value->plan_start_date)) }} to {{ date('j M Y', strtotime($value->plan_end_date)) }}
                                @elseif(!empty($value->plan_start_date))
                                    From {{ date('j M Y', strtotime($value->plan_start_date)) }}
                                @else
                                    —
                                @endif
                            </span>
                        </div>
                    </div>

                    {{-- Column 4: Payment Status --}}
                    <div class="info-stat-block">
                        <div class="info-icon-box info-icon-green">
                            <i class="fa-regular fa-credit-card"></i>
                        </div>
                        <div class="info-text">
                            <span class="info-label">Payment Status</span>
                            <div class="d-flex align-items-center">
                                @if($hasPendingBalance)
                                    <a href="javascript:void(0)" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" class="text-danger fw-bold settlement-learner text-decoration-none">
                                        Due ₹{{ rtrim(rtrim(number_format(($totalPendingAmt), 2, '.', ''), '0'), '.') }}@if(!empty($formattedDueDate)) ({{ $formattedDueDate }})@endif
                                    </a>
                                @elseif(!empty($totalPendingAmt) && $totalPendingAmt == 0)
                                    <span class="text-success fw-bold payment-status-value">Fully Paid</span>
                                    @if(optional($transaction)->id)
                                    <form action="{{ route('learner.receipt.download') }}" method="POST" enctype="multipart/form-data" target="_blank" class="d-inline ms-1">
                                        @csrf
                                        <input type="hidden" name="learner_id" value="{{$learner_id}}">
                                        <input type="hidden" name="id" value="{{($transaction->id ?? 0)}}">
                                        <input type="hidden" name="type" value="learner">
                                        <button type="submit" class="receipt-btn noLoader" title="Download Receipt">
                                            <i class="fa-solid fa-download"></i>
                                        </button>
                                    </form>
                                    @endif
                                @else
                                    <span class="text-muted payment-status-value">—</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Column 5: Locker --}}
                    <div class="info-stat-block">
                        <div class="info-icon-box info-icon-amber">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <div class="info-text">
                            <span class="info-label">Locker</span>
                            <span class="info-value">
                                @if($transaction && $transaction->locker_amount)
                                    Yes – ₹{{ $transaction->locker_amount }} Paid
                                @else
                                    No
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================= MOBILE LAYOUT ================= --}}
            <div class="mobile-only-section">
                {{-- Top Profile Header --}}
                <div class="mobile-profile-header">
                    <div class="mobile-profile-left">
                        <div class="avatar-wrap">
                            <a href="{{ asset($value->profile_picture ? $value->profile_picture : 'public/img/student_profile.jpeg') }}" class="view-image learner-list-profile-photo" title="View profile photo">
                                <img src="{{ asset($value->profile_picture ? $value->profile_picture : 'public/img/student_profile.jpeg') }}" alt="{{ $value->name }}" class="avatar-img">
                            </a>
                            <span class="avatar-status-dot dot-upcoming" title="Upcoming"></span>
                        </div>
                        <div class="mobile-details-text">
                            <div class="mobile-name-row">
                                <h5 class="mobile-name">{{ $value->name }}</h5>
                                <span class="mobile-seat-badge">Seat {{ $value->seat_no ? getSeatDisplayShortFloorName($value->seat_no) : 'GEN' }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">UID :</span>
                                <a href="{{ route('learners.show', $value->id) }}" class="detail-value">{{ $value->learner_no }}</a>
                                <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $value->learner_no }}" title="Copy UID">
                                    <i class="fa-regular fa-clone"></i>
                                </button>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">M :</span>
                                <a href="tel:+91-{{ $value->mobile }}" class="detail-value">+91-{{ display_learner_mobile($value->mobile) }}</a>
                                <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $value->mobile }}" title="Copy Mobile">
                                    <i class="fa-regular fa-clone"></i>
                                </button>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">E :</span>
                                @if($value->email)
                                    <a href="mailto:{{ $value->email }}" class="detail-value detail-email">{{ display_learner_email($value->email) }}</a>
                                @else
                                    <span class="text-danger detail-email" style="font-size: 11.5px;"><i class="fa-solid fa-xmark"></i> Email ID Not Available</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('learners.show', $value->id) }}" class="mobile-chevron-link" title="View Profile">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>

                {{-- Collapsible Banner --}}
                <div class="mobile-expiry-banner banner-warning js-mobile-collapsible-toggle" role="button" tabindex="0">
                    <div class="mobile-expiry-text">
                        <i class="fa-regular fa-clock"></i>
                        <span>
                            @if(!empty($value->plan_start_date))
                                Starts on {{ date('j M Y', strtotime($value->plan_start_date)) }}
                            @else
                                Future Booking
                            @endif
                        </span>
                    </div>
                    <i class="fa-solid fa-chevron-right mobile-expand-icon"></i>
                </div>

                {{-- Collapsible Subscription Info Body --}}
                <div class="mobile-collapsible-content">
                    {{-- 1. Subscription Info --}}
                    <div class="mobile-info-item">
                        <div class="info-icon-box info-icon-purple">
                            <i class="fa-regular fa-file-lines"></i>
                        </div>
                        <div class="info-text">
                            <span class="info-label">Subscription Info</span>
                            <span class="info-value">
                                {{ $value->plan_type_name ?? '—' }}@if(!empty($value->plan_name)) ({{ $value->plan_name }})@endif
                            </span>
                        </div>
                    </div>

                    {{-- 2. Plan Duration --}}
                    <div class="mobile-info-item">
                        <div class="info-icon-box info-icon-blue">
                            <i class="fa-regular fa-calendar-days"></i>
                        </div>
                        <div class="info-text">
                            <span class="info-label">Plan Duration</span>
                            <span class="info-value">
                                @if(!empty($value->plan_start_date) && !empty($value->plan_end_date))
                                    {{ date('j M Y', strtotime($value->plan_start_date)) }} to {{ date('j M Y', strtotime($value->plan_end_date)) }}
                                @elseif(!empty($value->plan_start_date))
                                    From {{ date('j M Y', strtotime($value->plan_start_date)) }}
                                @else
                                    —
                                @endif
                            </span>
                        </div>
                    </div>

                    {{-- 3. Payment Status --}}
                    <div class="mobile-info-item">
                        <div class="info-icon-box info-icon-green">
                            <i class="fa-regular fa-credit-card"></i>
                        </div>
                        <div class="info-text">
                            <span class="info-label">Payment Status</span>
                            <div class="d-flex align-items-center">
                                @if($hasPendingBalance)
                                    <a href="javascript:void(0)" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" class="text-danger fw-bold settlement-learner text-decoration-none">
                                        Due ₹{{ rtrim(rtrim(number_format(($totalPendingAmt), 2, '.', ''), '0'), '.') }}@if(!empty($formattedDueDate)) ({{ $formattedDueDate }})@endif
                                    </a>
                                @elseif(!empty($totalPendingAmt) && $totalPendingAmt == 0)
                                    <span class="text-success fw-bold payment-status-value">Fully Paid</span>
                                    @if(optional($transaction)->id)
                                    <form action="{{ route('learner.receipt.download') }}" method="POST" enctype="multipart/form-data" target="_blank" class="d-inline ms-1">
                                        @csrf
                                        <input type="hidden" name="learner_id" value="{{$learner_id}}">
                                        <input type="hidden" name="id" value="{{($transaction->id ?? 0)}}">
                                        <input type="hidden" name="type" value="learner">
                                        <button type="submit" class="receipt-btn noLoader" title="Download Receipt">
                                            <i class="fa-solid fa-download"></i>
                                        </button>
                                    </form>
                                    @endif
                                @else
                                    <span class="text-muted payment-status-value">—</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- 4. Locker --}}
                    <div class="mobile-info-item">
                        <div class="info-icon-box info-icon-amber">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <div class="info-text">
                            <span class="info-label">Locker</span>
                            <span class="info-value">
                                @if($transaction && $transaction->locker_amount)
                                    Yes – ₹{{ $transaction->locker_amount }} Paid
                                @else
                                    No
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Actions Section with Horizontally Scrollable Row --}}
                <div class="mobile-actions-container">
                    <div class="mobile-actions-header">
                        <h6 class="mobile-actions-title">Actions</h6>
                        <span class="mobile-actions-seeall">See All <i class="fa-solid fa-chevron-right" style="font-size: 11px;"></i></span>
                    </div>
                    <div class="mobile-actions-scroll">
                        @include('learner.partials.learner-actions', ['isMobile' => true])
                    </div>
                    <div class="mobile-scroll-indicator"></div>

                    {{-- Full-width "View Details →" button --}}
                    <a href="{{ route('learners.show', $value->id) }}" class="mobile-view-details-btn">
                        View Details <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
@endforeach
</div>

@if ($learners->lastPage() > 1)
<ul class="paginations mt-4">
    <li>
        <a href="{{ $learners->onFirstPage() ? '#' : $learners->previousPageUrl() }}" class="w-auto px-3 text-muted">Prev</a>
    </li>
    @if ($learners->currentPage() > 3)
        <li><a href="{{ $learners->url(1) }}">1</a></li>
        <li><span>...</span></li>
    @endif
    @for ($i = max(1, $learners->currentPage() - 2); $i <= min($learners->lastPage(), $learners->currentPage() + 2); $i++)
        <li>
            <a href="{{ $learners->url($i) }}" class="{{ $learners->currentPage() == $i ? 'active' : '' }}">
                {{ $i }}
            </a>
        </li>
    @endfor
    @if ($learners->currentPage() < $learners->lastPage() - 2)
        <li><span>...</span></li>
        <li><a href="{{ $learners->url($learners->lastPage()) }}">{{ $learners->lastPage() }}</a></li>
    @endif
    <li>
        <a href="{{ $learners->hasMorePages() ? $learners->nextPageUrl() : '#' }}" class="w-auto px-3 text-muted">Next</a>
    </li>
</ul>
@endif

@endif

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function closeProfileImageModal() {
        var $m = $('#imageViewModal');
        $m.stop(true, true).animate({ opacity: 0 }, 150, function () {
            $m.css({ display: 'none' });
            $m.attr('aria-hidden', 'true');
            $('#modalImage').attr('src', '');
        });
    }

    function openProfileImageModal(imageUrl) {
        var $m = $('#imageViewModal');
        $('#modalImage').attr('src', imageUrl);
        $m.attr('aria-hidden', 'false');
        $m.css({ display: 'flex', opacity: 0, zIndex: 99999 }).stop(true, true).animate({ opacity: 1 }, 200);
    }

    $(document).on('click', 'a.view-image, .view-image, .learner-card .avatar-wrap img', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var imageUrl = $(this).attr('href') || $(this).attr('src') || $(this).find('img').attr('src');
        if (imageUrl && imageUrl !== '#' && imageUrl !== 'javascript:;') {
            openProfileImageModal(imageUrl);
        }
    });

    $('#imageViewModal .close-modal').on('click', function (e) {
        e.stopPropagation();
        closeProfileImageModal();
    });

    $('#imageViewModal').on('click', function (e) {
        if ($(e.target).is(this)) {
            closeProfileImageModal();
        }
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $('#imageViewModal').css('display') === 'flex') {
            closeProfileImageModal();
        }
    });

    $(document).ready(function() {
        // Filter visibility: show if any filter is active, hide only when fully empty
        @if($hasActiveFilters)
            $('#learnerFilterContainer').show();
        @else
            $('#learnerFilterContainer').hide();
        @endif

        // Filter toggle button (#learnerFilterToggleBtn in header)
        $(document).on('click', '#learnerFilterToggleBtn, #filter', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            $('#learnerFilterContainer').stop(true, true).slideToggle(200);
        });

        // Toggle custom dropdowns
        $(document).on('click', '.learner-filter-module .custom-dropdown', function(e) {
            if ($(e.target).closest('.dropdown-item-option').length) {
                return;
            }
            e.stopPropagation();
            var $this = $(this);
            var wasOpen = $this.hasClass('open');
            
            $('.learner-filter-module .custom-dropdown').removeClass('open');
            if (!wasOpen) {
                $this.addClass('open');
            }
        });

        // Option click in custom dropdown
        $(document).on('click', '.learner-filter-module .dropdown-item-option', function(e) {
            e.stopPropagation();
            var $item = $(this);
            var $dropdown = $item.closest('.custom-dropdown');
            var val = $item.data('value');
            var text = $item.find('span').text();

            $dropdown.find('input[type="hidden"]').val(val);
            $dropdown.find('.filter-field-value').text(text);
            $dropdown.find('.dropdown-item-option').removeClass('active');
            $item.addClass('active');
            $dropdown.removeClass('open');
        });

        // Click outside closes custom dropdowns
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.learner-filter-module .custom-dropdown').length) {
                $('.learner-filter-module .custom-dropdown').removeClass('open');
            }
        });

        // Escape closes custom dropdowns
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.learner-filter-module .custom-dropdown').removeClass('open');
            }
        });

        // Search Learner box click to focus input
        $(document).on('click', '.learner-filter-module .filter-search-box', function(e) {
            if (!$(e.target).is('input')) {
                $(this).find('.filter-input').focus();
            }
        });

        // Clear filter button
        $(document).on('click', '.learner-filter-module #clearFilter', function(e) {
            e.preventDefault();
            window.location.href = "{{ route('future.bookings') }}";
        });

        // Scroll legend chips
        var $legendContainer = $('#seatLegendContainer');
        $('#scrollLeftBtn').on('click', function() {
            $legendContainer.animate({ scrollLeft: '-=240px' }, 300);
        });
        $('#scrollRightBtn').on('click', function() {
            $legendContainer.animate({ scrollLeft: '+=240px' }, 300);
        });
        $legendContainer.on('wheel', function(e) {
            if (e.originalEvent.deltaY !== 0) {
                e.preventDefault();
                this.scrollLeft += e.originalEvent.deltaY;
            }
        });

        // Copy button micro-interaction
        $(document).on('click', '.copy-action-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var val = $(this).data('copy');
            if (val && navigator.clipboard) {
                navigator.clipboard.writeText(val).then(function() {
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Copied: ' + val);
                    }
                }).catch(function() {
                    if (typeof toastr !== 'undefined') {
                        toastr.info('Copied: ' + val);
                    }
                });
            }
        });

        // Mobile Actions "See All >" smooth scroll
        $(document).on('click', '.mobile-actions-seeall', function(e) {
            e.preventDefault();
            var $scroll = $(this).closest('.mobile-actions-container').find('.mobile-actions-scroll');
            if ($scroll.length) {
                var maxScroll = $scroll[0].scrollWidth - $scroll.innerWidth();
                if ($scroll.scrollLeft() >= maxScroll - 15) {
                    $scroll.animate({ scrollLeft: 0 }, 300);
                } else {
                    $scroll.animate({ scrollLeft: maxScroll }, 400);
                }
            }
        });

        // Mobile Collapsible Toggle
        $(document).on('click', '.js-mobile-collapsible-toggle', function() {
            var $banner = $(this);
            var $content = $banner.next('.mobile-collapsible-content');
            $content.slideToggle(200);
            $banner.toggleClass('is-open');
        });
    });
</script>

@endsection
