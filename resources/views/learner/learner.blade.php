@extends('layouts.library')
@section('content')

<link rel="stylesheet" href="{{ asset('public/css/learner-list.css') }}?v={{ time() }}" />

<!-- Content Header (Page header) -->
<div class="modal fade" id="cf-modal" tabindex="-1" aria-labelledby="cf-modal-label" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="cf-modal-label">Transactions</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="transactionPendingBadge" class="small text-muted mb-2 d-none"></p>
        <div class="table-responsive mb-4">
          <table class="table table-bordered table-sm align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Plan Price</th>
                <th>Other Addon Cost <span class="fw-normal text-muted small">(Locker | miscellaneous | token)</span></th>
                <th>Discount on Plan Price</th>
                <th>Paid Amt</th>
                <th>Pending Amt</th>
                <th>Payment Date</th>
                <th>Payment Mode</th>
                <th>Download Receipt</th>
              </tr>
            </thead>
            <tbody id="transactionTableBody">
              <tr>
                <td colspan="9" class="text-center text-muted">Open from a learner row to load data.</td>
              </tr>
            </tbody>
          </table>
        </div>
        <h6 class="mb-2">All activity for this learner</h6>
        <div class="table-responsive">
          <table class="table table-bordered table-sm align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Particular</th>
                <th>Payment Type</th>
                <th>Payment Mode</th>
                <th>Amt.</th>
                <th>Payment Date</th>
                <th>Dr / Cr</th>
              </tr>
            </thead>
            <tbody id="activityTableBody">
              <tr>
                <td colspan="6" class="text-center text-muted">—</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
{{-- Large profile preview — keep display:flex for centering (jQuery fadeIn sets display:block and breaks layout) --}}
<div id="imageViewModal" class="image-modal" style="display:none;opacity:0;" aria-hidden="true">
    <div class="image-modal-content">
        <span class="close-modal" role="button" tabindex="0" aria-label="Close">&times;</span>
        <img src="" id="modalImage" alt="Profile photo preview">
    </div>
</div>

@php
$hasActiveFilters = request()->filled('search') || request()->filled('plan_id') || request()->filled('status')
    || request()->filled('seat_no') || request()->filled('payment_filter') || request()->filled('is_paid');
// Computed once per page load — these are branch/library-level settings,
// not per-learner, so they were previously being re-queried on every use
// (including once per row inside the loop below).
$hiddenFields = toggleHideField();
$currentBranchName = getCurrentBranchName();
$isNotificationActive = notificationActive();
$isWabaNotificationActive = $isNotificationActive && wabaNotificationActive();
$isTextNotificationActive = $isNotificationActive && textNotificationActive();
@endphp

<div class="row">
    <div class="col-lg-12 text-end">
        @if(!empty($hasPendingSyncLearners))
        <a href="javascript:void(0)" class="btn btn-primary export" id="btnSyncLearnerStatus" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Recalculate & Sync Expired Learner Statuses">
            <i class="fa-solid fa-arrows-rotate" id="syncStatusSpinIcon"></i> Sync Status
        </a>
        @endif
        <a href="javascript:void(0)" class="btn btn-primary export" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Filter" id="learnerFilterToggleBtn"><i class="fa-solid fa-filter"></i></a>

        <a href="{{ route('learners.export-csv') }}" class="btn btn-primary export" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Counts" id="counts"><i class="fa-solid fa-star"></i></a>
        <a href="{{ route('learners.export-csv') }}" class="btn btn-primary export"><i class="fa-solid fa-file-export"></i> Export All Data in CSV</a>

        <a href="{{ route('learners.list.pdf', request()->query()) }}" class="btn btn-primary export"
            target="_blank" data-bs-toggle="tooltip" data-bs-placement="bottom"
            data-bs-title="Download the currently filtered learner list as a PDF"><i
                class="fa-solid fa-file-pdf"></i> Download Learner List (PDF)</a>

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

@can('has-permission', 'Filter')
<div class="row mb-3 learner-filter-module" id="learnerFilterContainer" style="{{ $hasActiveFilters ? '' : 'display: none;' }}">
    <div class="col-lg-12">
        <div class="learner-filter-card">
            <form action="{{ route('learners') }}" method="GET" class="learner-filter-form" id="learnerFilterForm">
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

                    <!-- Status -->
                    @php
                        $selectedStatusName = 'Choose Status';
                        if (request()->get('status') == 'active') {
                            $selectedStatusName = 'Active';
                        } elseif (request()->get('status') == 'expired') {
                            $selectedStatusName = 'Expired';
                        } elseif (request()->get('status') == 'about_to_expire') {
                            $selectedStatusName = 'About to Expire';
                        } elseif (request()->get('status') == 'extended') {
                            $selectedStatusName = 'Extended';
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

                        <!-- Dropdown Menu -->
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
                            <div class="dropdown-item-option {{ request()->get('status') == 'about_to_expire' ? 'active' : '' }}" data-value="about_to_expire">
                                <i class="fa-solid fa-clock item-icon text-warning"></i>
                                <span>About to Expire</span>
                            </div>
                            <div class="dropdown-item-option {{ request()->get('status') == 'extended' ? 'active' : '' }}" data-value="extended">
                                <i class="fa-solid fa-calendar-plus item-icon text-info"></i>
                                <span>Extended</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment -->
                    @php
                        $selectedPaymentName = 'Choose Payment';
                        if (request()->get('payment_filter') == 'paid') {
                            $selectedPaymentName = 'Paid';
                        } elseif (request()->get('payment_filter') == 'pending_payment') {
                            $selectedPaymentName = 'Pending Payment';
                        } elseif (request()->get('payment_filter') == 'failed_payment') {
                            $selectedPaymentName = 'Failed Payment';
                        }
                    @endphp
                    <div class="filter-field-box custom-dropdown" id="dropdownPayment" data-dropdown="payment">
                        <div class="filter-field-icon icon-green">
                            <i class="fa-solid fa-credit-card"></i>
                        </div>
                        <div class="filter-field-content">
                            <span class="filter-field-label">Payment</span>
                            <span class="filter-field-value" id="payment_display">{{ $selectedPaymentName }}</span>
                            <input type="hidden" name="payment_filter" id="payment_filter" value="{{ request()->get('payment_filter') }}">
                        </div>
                        <div class="filter-field-chevron">
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>

                        <!-- Dropdown Menu matching exact UI screenshot -->
                        <div class="custom-dropdown-menu" id="payment_dropdown_menu">
                            <div class="dropdown-item-option {{ !request()->filled('payment_filter') ? 'active' : '' }}" data-value="">
                                <i class="fa-solid fa-credit-card item-icon text-primary"></i>
                                <span>Choose Payment</span>
                            </div>
                            <div class="dropdown-item-option {{ request()->get('payment_filter') == 'paid' ? 'active' : '' }}" data-value="paid">
                                <i class="fa-solid fa-circle-check item-icon text-success"></i>
                                <span>Paid</span>
                            </div>
                            <div class="dropdown-item-option {{ request()->get('payment_filter') == 'pending_payment' ? 'active' : '' }}" data-value="pending_payment">
                                <i class="fa-solid fa-clock item-icon text-warning"></i>
                                <span>Pending Payment</span>
                            </div>
                            <div class="dropdown-item-option {{ request()->get('payment_filter') == 'failed_payment' ? 'active' : '' }}" data-value="failed_payment">
                                <i class="fa-solid fa-circle-xmark item-icon text-danger"></i>
                                <span>Failed Payment</span>
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

                        <!-- Dropdown Menu -->
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

@if ( $learners->total()==0)
<div class="no-data-found">
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>

    <dotlottie-wc src="https://lottie.host/2bd4f1dd-bce9-44cb-b8a4-f5acd681c123/sHuYyTQ6uD.lottie"
        style="width: 200px;height: 200px" autoplay loop></dotlottie-wc>
    @if($hasActiveFilters)
    <h4>No Learners Found</h4>
    <span>No learners match the selected filters. Try adjusting or clearing the filters above.</span>
    @else
    <h4>No Learner Added Yet</h4>
    <span> You haven’t added any learners to your library yet. Start adding learners by clicking the button
        below.</span>
    <!-- Masters -->
    <div class="heading-list justify-content-end mb-1">
        @if(getCurrentBranch() !=0)
        <a href="javascript:;" class="btn btn-primary export noseat_popup">
            <i class="fa-solid fa-plus "></i> Book Seat
        </a>
        @else
        <h4>To add Plan Prices, first select your Branch.</h4>
        <span> Plan names remain the same across all branches, but prices can be different. That’s why you need to
            choose the branch before adding plan prices.</span>
        @endif
    </div>
    @endif
</div>

@else
@if(!in_array('24', $hiddenFields))
@php
    $currentLibId = getLibraryId();
    $currentBranchId = getCurrentBranch();
    $todayDate = date('Y-m-d');
    $fiveDaysLater = date('Y-m-d', strtotime('+5 days'));

    $due_count = \App\Models\LearnerDetail::whereNull('deleted_at')
        ->where('library_id', $currentLibId)
        ->when($currentBranchId, fn($q) => $q->where('branch_id', $currentBranchId))
        ->where('is_paid', 0)
        ->where('status', 1)
        ->count();

    $about_to_expire_count = \App\Models\LearnerDetail::whereNull('deleted_at')
        ->where('library_id', $currentLibId)
        ->when($currentBranchId, fn($q) => $q->where('branch_id', $currentBranchId))
        ->where('status', 1)
        ->whereBetween('plan_end_date', [$todayDate, $fiveDaysLater])
        ->count();

    $non_expiry_count = \App\Models\LearnerDetail::whereNull('deleted_at')
        ->where('library_id', $currentLibId)
        ->when($currentBranchId, fn($q) => $q->where('branch_id', $currentBranchId))
        ->where('status', 1)
        ->whereNull('plan_end_date')
        ->count();

    $future_booking_count = \App\Models\LearnerDetail::whereNull('deleted_at')
        ->where('library_id', $currentLibId)
        ->when($currentBranchId, fn($q) => $q->where('branch_id', $currentBranchId))
        ->where('status', 0)
        ->where('plan_start_date', '>', $todayDate)
        ->count();
@endphp
<div class="col-lg-12 mb-3 seat-overview-module" id="countsContainer">
    <div class="seat-overview-card">
        <!-- Header -->
        <div class="seat-overview-header">
            <div class="seat-overview-title-group">
                <div class="seat-overview-icon-box">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <div class="seat-overview-text">
                    <h5 class="seat-overview-title">Seat Overview</h5>
                    <p class="seat-overview-subtitle">Live seat count and status distribution</p>
                </div>
            </div>
            <div class="seat-overview-total-badge">
                <i class="fa-solid fa-users"></i>
                <span>Total Seats: <b class="total-seats-num">{{ $total_seats ?? 0 }}</b></span>
            </div>
        </div>

        <!-- Carousel / Scroll Row -->
        <div class="seat-overview-carousel-wrapper">
            <button type="button" class="seat-carousel-arrow-btn" id="scrollLeftBtn" title="Scroll Left" aria-label="Scroll Left">
                <i class="fa-solid fa-chevron-left"></i>
            </button>

            <div class="seat-overview-items" id="seatLegendContainer">
                <!-- Available -->
                <div class="seat-stat-tile stat-available">
                    <div class="stat-icon-circle">
                        <i class="fa-solid fa-chair"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Available</span>
                        <span class="stat-count">{{ $availble_seats ?? 0 }}</span>
                    </div>
                </div>

                <!-- Booked -->
                <div class="seat-stat-tile stat-booked">
                    <div class="stat-icon-circle">
                        <i class="fa-solid fa-chair"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Booked</span>
                        <span class="stat-count">{{ $booked_seats ?? ($active_seat_count ?? 0) }}</span>
                    </div>
                </div>

                <!-- General -->
                <div class="seat-stat-tile stat-general">
                    <div class="stat-icon-circle">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">General</span>
                        <span class="stat-count">{{ $genral_seat ?? 0 }}</span>
                    </div>
                </div>

                <!-- Extension -->
                <div class="seat-stat-tile stat-extension">
                    <div class="stat-icon-circle">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Extension</span>
                        <span class="stat-count">{{ $extended_seats ?? 0 }}</span>
                    </div>
                </div>

                <!-- Fee Overdue -->
                <div class="seat-stat-tile stat-overdue">
                    <div class="stat-icon-circle">
                        <i class="fa-solid fa-indian-rupee-sign"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Fee Overdue</span>
                        <span class="stat-count">{{ $due_count ?? 0 }}</span>
                    </div>
                </div>

                <!-- About to Expire -->
                <div class="seat-stat-tile stat-expiring">
                    <div class="stat-icon-circle">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">About to Expire</span>
                        <span class="stat-count">{{ $about_to_expire_count ?? 0 }}</span>
                    </div>
                </div>

                <!-- Expired -->
                <div class="seat-stat-tile stat-expired">
                    <div class="stat-icon-circle">
                        <i class="fa-solid fa-ban"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Expired</span>
                        <span class="stat-count">{{ $expired_seat ?? 0 }}</span>
                    </div>
                </div>

                <!-- Non-Expiry -->
                <div class="seat-stat-tile stat-non-expiry">
                    <div class="stat-icon-circle">
                        <i class="fa-solid fa-infinity"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Non-Expiry</span>
                        <span class="stat-count">{{ $non_expiry_count ?? 0 }}</span>
                    </div>
                </div>

                <!-- Future Booking -->
                <div class="seat-stat-tile stat-future">
                    <div class="stat-icon-circle">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Future Booking</span>
                        <span class="stat-count">{{ $future_booking_count ?? 0 }}</span>
                    </div>
                </div>

                <!-- Plan Types -->
                @foreach($planTypeCounts as $plan)
                <div class="seat-stat-tile stat-plan-type">
                    <div class="stat-icon-circle">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label" title="{{ $plan['name'] }}">{{ $plan['abbr'] ?? $plan['name'] }}</span>
                        <span class="stat-count">{{ $plan['count'] }}</span>
                    </div>
                </div>
                @endforeach
            </div>

            <button type="button" class="seat-carousel-arrow-btn" id="scrollRightBtn" title="Scroll Right" aria-label="Scroll Right">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>

        <!-- Carousel Dots Indicator -->
        <div class="seat-overview-dots" id="seatOverviewDots">
            <span class="overview-dot active" data-index="0"></span>
            <span class="overview-dot" data-index="1"></span>
            <span class="overview-dot" data-index="2"></span>
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
    if (str_contains($lowerExpiry, 'extension') || str_contains($lowerExpiry, 'expired')) {
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

<div class="row">
    <div class="col-lg-12">
        <div class="learner-card">
            
            {{-- ================= DESKTOP LAYOUT ================= --}}
            <div class="desktop-only-section">
                {{-- Top Row: Seat Box + Expiry on left, Action buttons on right --}}
                <div class="learner-top-row">
                    <div class="learner-top-left">
                        {{-- Seat Badge Box (Single Line) --}}
                        <div class="seat-badge-box">
                            <span class="seat-label">Seat No. :</span>
                            <span class="seat-val">{{ $value->seat_no ? getSeatDisplayShortFloorName($value->seat_no) : 'GEN' }}</span>
                        </div>

                        {{-- Status & Expiry Meta Block (No chips) --}}
                        <div class="learner-meta-block">
                            <div class="learner-expiry-line">
                                {!! $expiryHtml !!}
                            </div>
                        </div>
                    </div>

                    {{-- Action buttons strip --}}
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
                            <span class="avatar-status-dot {{ $dotColorClass }}" title="{{ $dotTitle }}" data-bs-toggle="tooltip" data-bs-title="{{ $dotTitle }}"></span>
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
                                @if(!empty($value->plan_start_date))
                                    {{ date('j M Y', strtotime($value->plan_start_date)) }} to 
                                    @if($value->frozen_status == 1)
                                        Frozen
                                    @elseif(!empty($value->plan_end_date))
                                        {{ date('j M Y', strtotime($value->plan_end_date)) }}
                                    @else
                                        —
                                    @endif
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
                                    <a href="javascript:;" class="open-transaction-modal ms-1 text-muted" data-learner_id="{{ $learner_id }}" data-bs-toggle="modal" data-bs-target="#cf-modal" style="font-size: .8rem;">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                @elseif(!empty($transaction?->pending_amount) && $transaction?->pending_amount == 0)
                                    <span class="text-success fw-bold payment-status-value">Fully Paid</span>
                                @elseif(empty($transaction?->pending_amount))
                                    <span class="text-muted payment-status-value">—</span>
                                @else
                                    <span class="text-success fw-bold payment-status-value">Fully Paid</span>
                                @endif

                                @if ($transaction?->id)
                                    <form action="{{ route('learner.receipt.download') }}" method="POST" enctype="multipart/form-data" target="_blank" class="d-inline ms-1">
                                        @csrf
                                        <input type="hidden" name="learner_id" value="{{$learner_id}}">
                                        <input type="hidden" name="id" value="{{($transaction->id ?? 0)}}">
                                        <input type="hidden" name="learner_detail_id" value="{{$learner_detail_id}}">
                                        <input type="hidden" name="type" value="learner">
                                        <button type="submit" class="receipt-btn noLoader" title="Download Receipt">
                                            <i class="fa-solid fa-download"></i>
                                        </button>
                                    </form>
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
                            <span class="avatar-status-dot {{ $dotColorClass }}" title="{{ $dotTitle }}"></span>
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
                <div class="mobile-expiry-banner {{ $bannerClass }} js-mobile-collapsible-toggle" role="button" tabindex="0">
                    <div class="mobile-expiry-text">
                        <i class="fa-regular fa-clock"></i>
                        <span>{{ $expiryTextOnly }}</span>
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
                                @if(!empty($value->plan_start_date))
                                    {{ date('j M Y', strtotime($value->plan_start_date)) }} to 
                                    @if($value->frozen_status == 1)
                                        Frozen
                                    @elseif(!empty($value->plan_end_date))
                                        {{ date('j M Y', strtotime($value->plan_end_date)) }}
                                    @else
                                        —
                                    @endif
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
                                    <a href="javascript:;" class="open-transaction-modal ms-1 text-muted" data-learner_id="{{ $learner_id }}" data-bs-toggle="modal" data-bs-target="#cf-modal" style="font-size: .8rem;">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                @elseif(!empty($transaction?->pending_amount) && $transaction?->pending_amount == 0)
                                    <span class="text-success fw-bold payment-status-value">Fully Paid</span>
                                @elseif(empty($transaction?->pending_amount))
                                    <span class="text-muted payment-status-value">—</span>
                                @else
                                    <span class="text-success fw-bold payment-status-value">Fully Paid</span>
                                @endif

                                @if ($transaction?->id)
                                    <form action="{{ route('learner.receipt.download') }}" method="POST" enctype="multipart/form-data" target="_blank" class="d-inline ms-1">
                                        @csrf
                                        <input type="hidden" name="learner_id" value="{{$learner_id}}">
                                        <input type="hidden" name="id" value="{{($transaction->id ?? 0)}}">
                                        <input type="hidden" name="learner_detail_id" value="{{$learner_detail_id}}">
                                        <input type="hidden" name="type" value="learner">
                                        <button type="submit" class="receipt-btn noLoader" title="Download Receipt">
                                            <i class="fa-solid fa-download"></i>
                                        </button>
                                    </form>
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

{{-- Pagination --}}


@if ($learners->lastPage() > 1)
<ul class="paginations mt-4">
    {{-- Prev --}}
    <li>
        <a href="{{ $learners->onFirstPage() ? '#' : $learners->previousPageUrl() }}" class="w-auto px-3 text-muted">Prev</a>
    </li>

    {{-- Page Numbers (shortened: 1 ... current ... last) --}}
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

            {{-- Next --}}
            <li>
                <a href="{{ $learners->hasMorePages() ? $learners->nextPageUrl() : '#' }}" class="w-auto px-3 text-muted">Next</a>
            </li>
</ul>
@endif

@endif
<!-- Modal Popup end for Configration -->

<!-- /.content -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        let table = new DataTable('#datatable', {
            searching: false, // This option hides the search bar
            ordering: false
        });
    });

    var learnerTransactionsDataBase = @json(url('library/learners/transactions-data'));

    $(document).on('click', '.open-transaction-modal', function () {
        var learnerId = $(this).data('learner_id');
        var requestUrl = learnerTransactionsDataBase.replace(/\/+$/, '') + '/' + encodeURIComponent(learnerId);

        $('#transactionPendingBadge').addClass('d-none').text('');
        $('#transactionTableBody').html(
            '<tr><td colspan="9" class="text-center">Loading...</td></tr>'
        );
        $('#activityTableBody').html(
            '<tr><td colspan="6" class="text-center">Loading...</td></tr>'
        );

        $.ajax({
            url: requestUrl,
            type: 'GET',
            success: function (res) {
                if (!res.status) {
                    $('#transactionTableBody').html(
                        '<tr><td colspan="9" class="text-center text-danger">Unable to load transactions.</td></tr>'
                    );
                    $('#activityTableBody').html(
                        '<tr><td colspan="6" class="text-center text-danger">—</td></tr>'
                    );
                    return;
                }

                var badgeText = '';
                if (!res.transactions || res.transactions.length === 0) {
                    badgeText = 'No transaction records for this learner.';
                } else if (res.has_pending) {
                    badgeText = 'Showing rows with pending balance.';
                } else {
                    badgeText = 'No pending balance — showing latest transaction summary.';
                }
                $('#transactionPendingBadge').removeClass('d-none').text(badgeText);

                if (res.transactions && res.transactions.length > 0) {
                    var rows = '';
                    res.transactions.forEach(function (item, index) {
                        var receiptCell = item.receipt_url
                            ? '<a href="' + item.receipt_url + '" target="_blank" rel="noopener">Download</a>'
                            : '<span class="text-muted">—</span>';
                        rows += '<tr>' +
                            '<td>' + (index + 1) + '</td>' +
                            '<td>' + (item.plan_price ?? '—') + '</td>' +
                            '<td>' + (item.other_addon_label ?? '—') + '</td>' +
                            '<td>' + (item.discount_amount ?? '—') + '</td>' +
                            '<td>' + (item.paid_amount ?? '—') + '</td>' +
                            '<td>' + (item.pending_amount ?? '—') + '</td>' +
                            '<td>' + (item.paid_date ?? '—') + '</td>' +
                            '<td>' + (item.payment_mode ?? '—') + '</td>' +
                            '<td>' + receiptCell + '</td>' +
                            '</tr>';
                    });
                    $('#transactionTableBody').html(rows);
                } else {
                    $('#transactionTableBody').html(
                        '<tr><td colspan="9" class="text-center">No transactions found.</td></tr>'
                    );
                }

                if (res.activities && res.activities.length > 0) {
                    var aRows = '';
                    res.activities.forEach(function (a) {
                        aRows += '<tr>' +
                            '<td>' + (a.particular ?? '—') + '</td>' +
                            '<td>' + (a.payment_type ?? '—') + '</td>' +
                            '<td>' + (a.payment_mode ?? '—') + '</td>' +
                            '<td>' + (a.amount_display ?? '—') + '</td>' +
                            '<td>' + (a.payment_date ?? '—') + '</td>' +
                            '<td>' + (a.dr_cr ?? '—') + '</td>' +
                            '</tr>';
                    });
                    $('#activityTableBody').html(aRows);
                } else {
                    $('#activityTableBody').html(
                        '<tr><td colspan="6" class="text-center">No activity recorded.</td></tr>'
                    );
                }
            },
            error: function () {
                $('#transactionTableBody').html(
                    '<tr><td colspan="9" class="text-center text-danger">Failed to load. Please try again.</td></tr>'
                );
                $('#activityTableBody').html(
                    '<tr><td colspan="6" class="text-center text-danger">—</td></tr>'
                );
            }
        });
    });

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

    // Profile photo: open large preview on click of avatar image or link
    $(document).on('click', 'a.view-image, .view-image, .learner-list-profile-photo, .learner-card .avatar-wrap img', function (e) {
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
        var $legendContainer = $('#seatLegendContainer');
        var $dots = $('#seatOverviewDots .overview-dot');

        function updateOverviewDots() {
            if (!$legendContainer.length || !$dots.length) return;
            var scrollLeft = $legendContainer.scrollLeft();
            var maxScroll = $legendContainer[0].scrollWidth - $legendContainer[0].clientWidth;
            if (maxScroll <= 5) return;
            var ratio = scrollLeft / maxScroll;
            var activeIdx = Math.min(Math.round(ratio * ($dots.length - 1)), $dots.length - 1);
            $dots.removeClass('active');
            $dots.eq(activeIdx).addClass('active');
        }

        $('#scrollLeftBtn').on('click', function() {
            $legendContainer.animate({ scrollLeft: '-=260px' }, 250, updateOverviewDots);
        });
        $('#scrollRightBtn').on('click', function() {
            $legendContainer.animate({ scrollLeft: '+=260px' }, 250, updateOverviewDots);
        });
        $legendContainer.on('scroll', function() {
            updateOverviewDots();
        });
        $legendContainer.on('wheel', function(e) {
            if (e.originalEvent.deltaY !== 0) {
                e.preventDefault();
                this.scrollLeft += e.originalEvent.deltaY;
                updateOverviewDots();
            }
        });

        $dots.on('click', function() {
            var idx = $(this).data('index');
            if (typeof idx !== 'undefined' && $legendContainer.length) {
                var maxScroll = $legendContainer[0].scrollWidth - $legendContainer[0].clientWidth;
                var targetScroll = (idx / ($dots.length - 1)) * maxScroll;
                $legendContainer.animate({ scrollLeft: targetScroll }, 250, updateOverviewDots);
            }
        });

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
            var form = document.getElementById('learnerFilterForm');
            if (form) {
                form.reset();
                window.location.href = form.action;
            } else {
                window.location.href = "{{ route('learners') }}";
            }
        });

        $(document).on('click', '#btnSyncLearnerStatus', function() {
            let $btn = $(this);
            let $icon = $('#syncStatusSpinIcon');
            $btn.prop('disabled', true);
            $icon.addClass('fa-spin');

            $.ajax({
                url: "{{ route('learners.sync.status') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    $icon.removeClass('fa-spin');
                    $btn.prop('disabled', false);
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Status Synchronized',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Something went wrong', 'error');
                    }
                },
                error: function(xhr) {
                    $icon.removeClass('fa-spin');
                    $btn.prop('disabled', false);
                    Swal.fire('Error', 'Failed to update learner statuses.', 'error');
                }
            });
        });

        // Mobile collapsible subscription toggle
        $(document).on('click', '.js-mobile-collapsible-toggle', function() {
            var $banner = $(this);
            var $content = $banner.next('.mobile-collapsible-content');
            $content.slideToggle(200);
            $banner.toggleClass('is-open');
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
    });
</script>

@endsection
