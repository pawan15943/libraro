@extends('layouts.library')
@section('content')

@php
use App\Models\Learner;
use App\Models\LearnerDetail;
use Carbon\Carbon;
$fullDayCount = 0;
$halfDayFirstHalfCount = 0;
$halfDaySecondHalfCount = 0;
$hourlyCount = 0;
$today = Carbon::today();

$allBranchPlanTypes = \App\Models\PlanType::where('branch_id', getCurrentBranch())
    ->whereNull('deleted_at')
    ->get();

@endphp

<link rel="stylesheet" href="{{ asset('public/css/seat-module.css') }}?v={{ time() }}">

<style>
/* Suppress legacy global overlay immediately */
#loaderone, #loader { display: none !important; opacity: 0 !important; visibility: hidden !important; pointer-events: none !important; }
</style>
<script>
try {
    var l1 = document.getElementById('loaderone'); if (l1) l1.style.display = 'none';
    var l2 = document.getElementById('loader'); if (l2) l2.style.display = 'none';
} catch(e) {}
</script>

<div class="library-seat-module">
@if(getCurrentBranch() !=0 )

<div class="row mb-2">
<!-- Professional Seat & Shift Search Filter Control Panel (Mobile-First) -->
<div class="col-lg-12">
    <div class="seat-search-filter-panel shadow-sm">
        
        <!-- Primary Action Bar (Equal height 44px, identical radius and styling) -->
        <div class="d-flex align-items-center gap-2.5">
            <!-- Search Input (Height 44px, pill rounded, clean border) -->
            <div class="seat-search-input-group flex-grow-1 d-flex align-items-center">
                <i class="fa-solid fa-magnifying-glass me-2" style="color: #18225f; font-size: 0.9rem;"></i>
                <input type="text" id="seatSearchInput" class="form-control font-outfit" 
                       placeholder="Search Seat No or Student Name...">
            </div>

            <!-- Filter Toggle Button (Height 44px, matching pill, uniform border & gap) -->
            <button type="button" class="btn btn-filter-toggle flex-shrink-0" id="toggleFilterPanelBtn" title="Toggle Filters">
                <i class="fa-solid fa-sliders"></i>
                <span class="filter-btn-text ms-1 font-outfit fw-bold">Filters</span>
                <span class="filter-active-dot d-none" id="activeFilterBadge"></span>
            </button>
        </div>

        <!-- Collapsible Filter Options (Uniform Layout for Desktop & Mobile) -->
        <div class="seat-filter-collapse-content mt-3 pt-3 border-top" id="seatFilterCollapse" style="display: none; border-top-color: #f1f5f9 !important;">
            <div class="seat-filter-wrapper">
                <!-- Top Row: Shift Selector & Reset Button -->
                <div class="seat-filter-top-row d-flex align-items-center justify-content-between gap-2.5 mb-2.5">
                    <div class="seat-shift-select-box position-relative flex-grow-1">
                        <i class="fa-solid fa-clock shift-clock-icon"></i>
                        <select id="seatShiftFilterSelect" class="form-select font-outfit seat-shift-select">
                            <option value="">All Shifts</option>
                            @foreach($allBranchPlanTypes as $pt)
                            <option value="{{ strtolower($pt->name) }}">{{ $pt->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Reset Button -->
                    <button type="button" id="resetSeatFilterBtn" class="btn btn-seat-reset font-outfit flex-shrink-0" title="Reset Filters">
                        <i class="fa-solid fa-rotate-right me-1"></i> Reset
                    </button>
                </div>

                <!-- Status Filter Pills Container -->
                <div class="seat-status-filter-scroll">
                    <button type="button" class="btn seat-status-filter-btn active" data-filter="all">All</button>
                    <button type="button" class="btn seat-status-filter-btn" data-filter="booked">
                        <span class="filter-dot" style="background-color: #34939F;"></span> Booked
                    </button>
                    <button type="button" class="btn seat-status-filter-btn" data-filter="available">
                        <span class="filter-dot" style="background-color: #22c55e;"></span> Available
                    </button>
                    <button type="button" class="btn seat-status-filter-btn" data-filter="due">
                        <span class="filter-dot" style="background-color: #ef4444;"></span> Pending Fee
                    </button>
                    <button type="button" class="btn seat-status-filter-btn" data-filter="expiring">
                        <span class="filter-dot" style="background-color: #d97706;"></span> Expiring Soon
                    </button>
                    <button type="button" class="btn seat-status-filter-btn" data-filter="extended">
                        <span class="filter-dot" style="background-color: #800000;"></span> Extended
                    </button>
                    <button type="button" class="btn seat-status-filter-btn" data-filter="future">
                        <span class="filter-dot" style="background-color: #c09600;"></span> Future Booked
                    </button>
                    <button type="button" class="btn seat-status-filter-btn" data-filter="non_expired">
                        <span class="filter-dot" style="background-color: #c8009d;"></span> Non Expired
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>
</div>


    @if(!in_array('24', toggleHideField()))
    <div class="col-lg-12 mb-3" id="countsContainer">
        <div class="records p-3.5 bg-white border rounded-4 shadow-sm" style="border-color: #e2e8f0 !important; border-radius: 1rem !important; background: #ffffff;">
            <!-- Metrics Header Pills -->
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3 pb-2 border-bottom" style="border-bottom-color: #f1f5f9 !important;">
                <span class="fw-bold font-outfit text-uppercase tracking-wide small me-2" style="color: #18225f; font-size: 0.82rem;">
                    <i class="fa-solid fa-chart-pie me-1.5" style="color: #18225f;"></i> Overview Stats:
                </span>
                
                <span class="badge rounded-pill px-3 py-1.5 font-outfit fw-bold" style="background-color: #f1f5f9; color: #18225f; border: 1px solid #cbd5e1; font-size: 0.8rem;">
                    Total Seats: <strong class="ms-1" style="color: #18225f;">{{ $total_seats ?? 0 }}</strong>
                </span>
                <span class="badge rounded-pill px-3 py-1.5 font-outfit fw-bold" style="background-color: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; font-size: 0.8rem;">
                    Available: <strong class="ms-1" style="color: #15803d;">{{ $availble_seats ?? 0 }}</strong>
                </span>
                <span class="badge rounded-pill px-3 py-1.5 font-outfit fw-bold" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 0.8rem;">
                    Booked: <strong class="ms-1" style="color: #1d4ed8;">{{ $booked_seats ?? 0 }}</strong>
                </span>
                <span class="badge rounded-pill px-3 py-1.5 font-outfit fw-bold" style="background-color: #f0f9ff; color: #0369a1; border: 1px solid #bae6fd; font-size: 0.8rem;">
                    General: <strong class="ms-1" style="color: #0369a1;">{{ $genral_seat ?? 0 }}</strong>
                </span>
            </div>
            
            <div class="seat-legend-scroll-wrapper position-relative d-flex align-items-center mt-2">
                <!-- Left Navigation Arrow -->
                <button type="button" class="btn btn-sm btn-light border me-2 scroll-arrow-btn shadow-none" id="scrollLeftBtn" title="Scroll Left" style="z-index: 2; min-width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #18225f;">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>

                <!-- Scrollable Legend Chips Container -->
                <div class="seat-legend-items d-flex align-items-center gap-2 overflow-hidden flex-nowrap w-100 py-1" id="seatLegendContainer" style="scroll-behavior: smooth; white-space: nowrap;">
                    
                    <!-- Available (AV) -->
                    <div class="legend-chip d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill bg-success-subtle border border-success-subtle">
                        <i class="fa-solid fa-check-circle text-success"></i>
                        <span class="fw-bold font-outfit small text-success">AV: Available ({{ $availble_seats ?? 0 }})</span>
                    </div>

                    <!-- Active (ACT) -->
                    <div class="legend-chip d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill border" style="background-color: #eef2ff; border-color: #c7d2fe !important;">
                        <i class="fa-solid fa-check-circle" style="color: #18225f;"></i>
                        <span class="fw-bold font-outfit small" style="color: #18225f;">ACT: Booked ({{ $active_seat_count ?? 0 }})</span>
                    </div>

                    <!-- General (GEN) -->
                    <div class="legend-chip d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill border" style="background-color: #f0f9ff; border-color: #bae6fd !important;">
                        <i class="fa-solid fa-check-circle" style="color: #0284c7;"></i>
                        <span class="fw-bold font-outfit small" style="color: #0284c7;">GEN: General ({{ $genral_seat ?? 0 }})</span>
                    </div>

                    <!-- Extension (EXT) -->
                    <div class="legend-chip d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill border" style="background-color: #fdf2f2; border-color: #fecdd3 !important;">
                        <i class="fa-solid fa-check-circle seatBlink" style="color: #800000;"></i>
                        <span class="fw-bold font-outfit small" style="color: #800000;">EXT: Extension ({{ $extended_seats ?? 0 }})</span>
                    </div>

                    <!-- Fee Overdue (DUE) -->
                    <div class="legend-chip d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill border" style="background-color: #eff6ff; border-color: #bfdbfe !important;">
                        <i class="fa-solid fa-check-circle seatBlink" style="color: #2E3ECD;"></i>
                        <span class="fw-bold font-outfit small" style="color: #2E3ECD;">DUE: Fee Overdue</span>
                    </div>

                    <!-- About to Expire (ATE) -->
                    <div class="legend-chip d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill border" style="background-color: #fffbe6; border-color: #ffe58f !important;">
                        <i class="fa-solid fa-check-circle" style="color: #d97706;"></i>
                        <span class="fw-semibold font-outfit small" style="color: #d97706; font-weight: 600 !important;">ATE: About to Expire</span>
                    </div>

                    <!-- Expired (EXP) -->
                    <div class="legend-chip d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill bg-danger-subtle border border-danger-subtle">
                        <i class="fa-solid fa-check-circle text-danger"></i>
                        <span class="fw-bold font-outfit small text-danger">EXP: Expired ({{ $expired_seat ?? 0 }})</span>
                    </div>

                    <!-- Non-Expiry (NE) -->
                    <div class="legend-chip d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill border" style="background-color: #fdf4ff; border-color: #f5d0fe !important;">
                        <i class="fa-solid fa-check-circle" style="color: #c8009d;"></i>
                        <span class="fw-bold font-outfit small" style="color: #c8009d;">NE: Non-Expiry</span>
                    </div>

                    <!-- Future Booking (FUT) -->
                    <div class="legend-chip d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill border" style="background-color: #fffbeb; border-color: #fde68a !important;">
                        <i class="fa-solid fa-check-circle" style="color: #C09600;"></i>
                        <span class="fw-bold font-outfit small" style="color: #C09600;">FUT: Future Booking</span>
                    </div>

                    <!-- Plan Types Loop -->
                    @foreach($planTypeCounts as $plan)
                    <div class="legend-chip d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill bg-light border">
                        <i class="fa-solid fa-clock text-secondary"></i>
                        <span class="fw-bold font-outfit small text-dark">{{ $plan['abbr'] }}: {{ $plan['name'] }} ({{ $plan['count'] }})</span>
                    </div>
                    @endforeach

                </div>

                <!-- Right Navigation Arrow -->
                <button type="button" class="btn btn-sm btn-light border ms-2 scroll-arrow-btn shadow-none" id="scrollRightBtn" title="Scroll Right" style="z-index: 2; min-width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #18225f;">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
    @endif

<div class="row mb-4">
    <div class="col-12">
        <div class="seat-portal-tabs-card mb-2">
            <ul class="nav nav-pills seat-portal-tabs" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="pills-home-tab" data-bs-toggle="pill" data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home" aria-selected="true">
                        <i class="fa-solid fa-chair me-1.5"></i> Library Seats
                        <span class="badge tab-badge-stat ms-1.5">{{ $total_seats ?? 0 }}</span>
                    </button>
                </li>
                @can('has-permission', 'General Seat Booking')
                @if(!in_array('12', toggleHideField()))
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pills-profile-tab" data-bs-toggle="pill" data-bs-target="#pills-profile" type="button" role="tab" aria-controls="pills-profile" aria-selected="false">
                        <i class="fa-solid fa-users me-1.5"></i> General Seats
                        @if(countWithoutSeatNo() > 0)
                        <span class="badge tab-badge-stat ms-1.5">{{ countWithoutSeatNo() }}</span>
                        @endif
                    </button>
                </li>
                @endif
                @endcan
            </ul>
        </div>
    </div>
    <div class="col-12">
        <div class="tab-content" id="pills-tabContent">
@php
    $currentBranchId = getCurrentBranch();
    $branchHourRecord = \App\Models\Hour::where('branch_id', $currentBranchId)->first();
    $branchTotalHours = $branchHourRecord ? (int)$branchHourRecord->hour : 24;

    // 1. Batch pre-fetch all active learners for current branch grouped by seat_no
    $activeLearnersBySeat = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
        ->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
        ->where('learners.branch_id', $currentBranchId)
        ->where('learners.status', 1)
        ->where('learner_detail.status', 1)
        ->select(
            'learners.id',
            'learners.name',
            'learners.mobile',
            'learners.profile_picture',
            'learners.seat_no',
            'learners.no_expiry',
            'learners.frozen_status',
            'learner_detail.freeze_start_date',
            'learner_detail.plan_type_id',
            'plan_types.day_type_id',
            'plan_types.image',
            'learner_detail.plan_start_date',
            'learner_detail.plan_end_date',
            'learner_detail.id as learner_detail_id',
            'plan_types.name as plan_type_name'
        )
        ->get()
        ->groupBy(function($item) {
            return (string)$item->seat_no;
        });

    // 2. Batch pre-fetch total hours sum per seat for current branch
    $hoursSumBySeat = LearnerDetail::where('branch_id', $currentBranchId)
        ->where('status', 1)
        ->whereDate('plan_start_date', '<=', $today)
        ->groupBy('seat_no')
        ->selectRaw('seat_no, SUM(hour) as total_hours')
        ->pluck('total_hours', 'seat_no');

    // 3. Batch pre-fetch future bookings for current branch grouped by seat_no
    $futureBookingsBySeat = LearnerDetail::leftJoin('learners', 'learner_detail.learner_id', '=', 'learners.id')
        ->where('learner_detail.branch_id', $currentBranchId)
        ->where('learner_detail.plan_start_date', '>', date('Y-m-d'))
        ->whereNull('learner_detail.deleted_at')
        ->select('learner_detail.*', 'learners.name as learner_name', 'learners.mobile as learner_mobile')
        ->orderBy('learner_detail.plan_start_date', 'asc')
        ->get()
        ->groupBy(function($item) {
            return (string)$item->seat_no;
        });

    // 4. Batch pre-fetch pending amounts & exact due values for all active learners in current branch
    $allLearnerDetailIds = [];
    foreach ($activeLearnersBySeat as $seatGroup) {
        foreach ($seatGroup as $lItem) {
            if ($lItem->learner_detail_id) {
                $allLearnerDetailIds[] = $lItem->learner_detail_id;
            }
        }
    }

    $pendingAmountCache = [];
    $dueAmountValueCache = [];
    if (!empty($allLearnerDetailIds)) {
        $txList = \App\Models\LearnerTransaction::whereIn('learner_detail_id', $allLearnerDetailIds)
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('learner_detail_id');

        foreach ($allLearnerDetailIds as $ldId) {
            $txsForDetail = $txList->get($ldId);
            $dueVal = 0;
            $hasDueFlag = false;

            if ($txsForDetail && $txsForDetail->count() > 0) {
                $latestTx = $txsForDetail->first();
                if ((float)($latestTx->pending_amount ?? 0) > 0) {
                    $dueVal = (float)$latestTx->pending_amount;
                    $hasDueFlag = true;
                } elseif ((int)($latestTx->is_paid ?? 1) === 0) {
                    $dueVal = (float)($latestTx->total_amount ?? 0);
                    $hasDueFlag = $dueVal > 0;
                }
            }

            if (!$hasDueFlag && (pending_amt($ldId) || paylater($ldId))) {
                $hasDueFlag = true;
            }

            $pendingAmountCache[$ldId] = $hasDueFlag;
            $dueAmountValueCache[$ldId] = $dueVal;
        }
    }
@endphp

            <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab" tabindex="0">

                <div class="col-lg-12 mt-0">
                    

                        @if(isset($total_seats) && $total_seats != 0)
                            @php
                                $seatNo = 1; 
                            @endphp

                            @foreach($floors as $index => $floor)
                                @php
                                    $startSeat = $floor->from_seat ?? 1;
                                    $endSeat   = $floor->to_seat ?? 0;
                                    $floorId   = $floor->id ?? $index;
                                @endphp

                                <div class="floor-collapsible-card mb-4 overflow-hidden bg-white shadow-sm" style="border: 1px solid #e2e8f0 !important; border-radius: 1rem !important; background: #ffffff !important;">
                                    <div class="floor-header-bar p-3 d-flex align-items-center justify-content-between cursor-pointer bg-white" 
                                         data-bs-toggle="collapse" 
                                         data-bs-target="#floorCollapse_{{ $floorId }}" 
                                         aria-expanded="true" 
                                         aria-controls="floorCollapse_{{ $floorId }}"
                                         style="background: #ffffff; color: #18225f; cursor: pointer; user-select: none; border-bottom: 1px solid #f1f5f9;">
                                        
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-layer-group" style="color: #18225f; font-size: 0.95rem;"></i>
                                            <h5 class="mb-0 font-outfit fw-bold text-uppercase tracking-wide floor-title-text" style="color: #18225f;">{{ $floor->name }}</h5>
                                            <span class="badge rounded-pill ms-2 font-outfit small fw-bold floor-seats-badge" style="background-color: #f1f5f9; color: #18225f; border: 1px solid #cbd5e1;">
                                                Seats {{ $startSeat }} - {{ $endSeat }}
                                            </span>
                                        </div>

                                        <div class="d-flex align-items-center gap-2">
                                            <span class="small font-outfit text-muted d-none d-md-inline" style="font-size: 0.8rem;">Click to Collapse / Expand</span>
                                            <i class="fa-solid fa-chevron-down collapse-icon ms-1" style="font-size: 0.95rem; color: #18225f; transition: transform 0.3s ease;"></i>
                                        </div>
                                    </div>

                                    <div class="collapse show p-3 floor-seat-collapse" id="floorCollapse_{{ $floorId }}">
                                        <div class="seat-booking">

                                @for($seatNo2 = $startSeat; $seatNo2 <= $endSeat && $seatNo <= $total_seats; $seatNo2++)
                                    @php
                                        $usersForSeat = $activeLearnersBySeat->get((string)$seatNo, collect());
                                        $sumofhourseat = $hoursSumBySeat->get((string)$seatNo, 0);
                                        $remainingHours = $total_hour - $sumofhourseat;

                                        $dayTypesMap = [
                                            1 => 'Full Day', 
                                            2 => '1st Half', 
                                            3 => '2nd Half', 
                                            4 => 'Hourly 1', 
                                            5 => 'Hourly 2', 
                                            6 => 'Hourly 3', 
                                            7 => 'Hourly 4', 
                                            8 => 'All Day', 
                                            9 => 'Full Night',
                                            10 => 'RESERVED',
                                            11 => 'VIP'
                                        ];

                                        $seatShifts = [];
                                        $bookedDayTypeIds = [];

                                        if ($usersForSeat->count() > 0) {
                                            foreach ($usersForSeat as $u) {
                                                $planDetails = getPlanStatusDetails($u->plan_end_date);
                                                $isNonExpiry = (int) ($u->no_expiry ?? 0) === 1;
                                                $isExtended = ($planDetails['status'] === 'In Extension' || $planDetails['status'] === 'Extension ends today' || (isset($planDetails['class']) && in_array($planDetails['class'], ['extedned', 'extended'])));
                                                $isFuture = (!empty($u->plan_start_date) && \Carbon\Carbon::parse($u->plan_start_date)->isFuture());
                                                $isExpiring = (isset($planDetails['class']) && $planDetails['class'] === 'aboutToExpire');

                                                // Skip expired bookings so only active seats/bookings are shown on seatmap
                                                if ($planDetails['status'] === 'Expired' && !$isNonExpiry) {
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
                                            if (in_array((int)$dt, [1, 2, 3, 4, 5, 6, 7])) {
                                                $hasAnyDaytimeBooked = true;
                                                break;
                                            }
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
                                                    if (in_array($pt->id, $bookedPlanTypeIds)) {
                                                        continue;
                                                    }
                                                    // 2. Cannot book the same day_type already booked (unless custom 0)
                                                    if ($pt->day_type_id != 0 && in_array($pt->day_type_id, $bookedDayTypeIds)) {
                                                        continue;
                                                    }
                                                    // 3. 24-hr shifts require seat to be completely unbooked
                                                    if (count($bookedDayTypeIds) > 0 && in_array($pt->day_type_id, [8, 10, 11])) {
                                                        continue;
                                                    }
                                                    // 4. If branch open hours < 24, All Day (8) and Full Night (9) are not allowed
                                                    if ($branchTotalHours < 24 && in_array($pt->day_type_id, [8, 9])) {
                                                        continue;
                                                    }
                                                    // 5. If any daytime shift is booked, Full Day (1) cannot be booked
                                                    if ($pt->day_type_id == 1 && $hasAnyDaytimeBooked) {
                                                        continue;
                                                    }
                                                    // 6. If Full Day (1) is booked, daytime half/hourly shifts cannot be booked
                                                    if (in_array(1, $bookedDayTypeIds) && in_array($pt->day_type_id, [2, 3, 4, 5, 6, 7])) {
                                                        continue;
                                                    }

                                                    $seatShifts[] = [
                                                        'type' => 'available',
                                                        'label' => $pt->name,
                                                        'day_type_id' => $pt->day_type_id,
                                                        'plan_type_id' => $pt->id
                                                    ];
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

                                        $bookedCount = count($usersForSeat);
                                        if ($bookedCount === 0) {
                                            $overallStatus = 'Available';
                                            $overallStatusClass = 'text-success';
                                        } elseif ($remainingHours > 0) {
                                            $overallStatus = 'Partly booked';
                                            $overallStatusClass = 'text-primary';
                                        } else {
                                            $overallStatus = 'Needs action';
                                            $overallStatusClass = 'text-danger';
                                        }

                                        $studentNames = [];
                                        $shiftLabels = [];
                                        $hasAvailableShift = false;
                                        $hasBookedShift = false;
                                        $hasDueShift = false;
                                        $hasExpiringShift = false;
                                        $hasExtendedShift = false;
                                        $hasFutureShift = false;
                                        $hasNonExpiredShift = false;

                                        foreach ($seatShifts as $sh) {
                                            if ($sh['type'] === 'available') {
                                                $hasAvailableShift = true;
                                                if (!empty($sh['label'])) {
                                                    $shiftLabels[] = strtolower($sh['label']);
                                                }
                                            } elseif ($sh['type'] === 'future' || !empty($sh['is_future'])) {
                                                $hasFutureShift = true;
                                                if (!empty($sh['name'])) {
                                                    $studentNames[] = strtolower($sh['name']);
                                                }
                                                if (!empty($sh['day_type_label'])) {
                                                    $shiftLabels[] = strtolower($sh['day_type_label']);
                                                }
                                            } elseif ($sh['type'] === 'booked') {
                                                $hasBookedShift = true;
                                                if (!empty($sh['name'])) {
                                                    $studentNames[] = strtolower($sh['name']);
                                                }
                                                if (!empty($sh['day_type_label'])) {
                                                    $shiftLabels[] = strtolower($sh['day_type_label']);
                                                }
                                            }

                                            if (!empty($sh['has_due'])) {
                                                $hasDueShift = true;
                                            }
                                            if (!empty($sh['is_expiring'])) {
                                                $hasExpiringShift = true;
                                            }
                                            if (!empty($sh['is_extended'])) {
                                                $hasExtendedShift = true;
                                            }
                                            if (!empty($sh['is_non_expiry'])) {
                                                $hasNonExpiredShift = true;
                                            }
                                        }

                                        // Status color for dense view and card top accent border
                                        $primaryStatusColor = '#22c55e';
                                        if ($hasDueShift) {
                                            $primaryStatusColor = '#ef4444';
                                        } elseif ($hasExtendedShift) {
                                            $primaryStatusColor = '#800000';
                                        } elseif ($hasExpiringShift) {
                                            $primaryStatusColor = '#d97706';
                                        } elseif ($hasNonExpiredShift) {
                                            $primaryStatusColor = '#c8009d';
                                        } elseif ($hasFutureShift) {
                                            $primaryStatusColor = '#c09600';
                                        } elseif ($hasBookedShift) {
                                            $primaryStatusColor = '#18225f';
                                        }

                                        $isWholeDayBooked = in_array(1, $bookedDayTypeIds) ||
                                                            in_array(8, $bookedDayTypeIds) ||
                                                            in_array(10, $bookedDayTypeIds) ||
                                                            in_array(11, $bookedDayTypeIds) ||
                                                            ($is24HoursBooked && count($usersForSeat) === 1);
                                    @endphp

                                    <div class="seat-card-item bg-white border position-relative d-flex flex-column align-items-center justify-content-between text-center" 
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
                                         data-has-non-expired="{{ $hasNonExpiredShift ? '1' : '0' }}">
                                        
                                        <!-- Top Bar: Compact Seat Badge & Shift Count Chip -->
                                        <div class="card-top-bar d-flex align-items-center justify-content-between w-100">
                                            <span class="seat-badge-pill font-outfit fw-bold">
                                                Seat {{ sprintf('%02d', $seatNo) }}
                                            </span>
                                            @if($isWholeDayBooked)
                                            <span class="seat-shift-count-chip font-outfit fw-bold" title="Whole Day Booked">1</span>
                                            @elseif(count($seatShifts) > 1)
                                            <span class="seat-shift-count-chip font-outfit fw-bold" title="{{ count($seatShifts) }} Shifts Available/Booked">
                                                <span class="current-shift-num">1</span>/{{ count($seatShifts) }}
                                            </span>
                                            @endif
                                        </div>

                                        <!-- Shift Slides Container -->
                                        <div class="shift-slides-wrapper w-100 position-relative">
                                            @foreach($seatShifts as $sIdx => $shift)
                                            @php
                                                $isExt = (isset($shift['class']) && ($shift['class'] === 'extedned' || $shift['class'] === 'extended')) || !empty($shift['is_extended']);
                                                $hasDue = !empty($shift['has_due']);
                                                $isExpiring = !empty($shift['is_expiring']) || (isset($shift['class']) && $shift['class'] === 'aboutToExpire');
                                                $isNonExpiry = !empty($shift['is_non_expiry']) || (isset($shift['class']) && $shift['class'] === 'non_expiry_class');
                                                $isFuture = ($shift['type'] === 'future' || !empty($shift['is_future']));

                                                if ($hasDue) {
                                                    $shiftStatusColor = '#ef4444'; // Pending Fee / Due
                                                } elseif ($isExt) {
                                                    $shiftStatusColor = '#800000'; // Extended
                                                } elseif ($isExpiring) {
                                                    $shiftStatusColor = '#d97706'; // About to Expire
                                                } elseif ($isNonExpiry) {
                                                    $shiftStatusColor = '#c8009d'; // Non-Expiry
                                                } elseif ($isFuture) {
                                                    $shiftStatusColor = '#c09600'; // Future Booking
                                                } else {
                                                    $shiftStatusColor = '#18225f'; // Booked
                                                }

                                                $shiftStatusText = $shift['type'] === 'available' ? 'Available' : ($hasDue ? 'Fee Overdue' : ($isExt ? 'In Extension' : ($isExpiring ? 'Expiring Soon' : ($isNonExpiry ? 'Non-Expiry' : ($isFuture ? 'Future Booked' : 'Active Booked')))));
                                            @endphp
                                            <div class="shift-slide-item {{ $sIdx === 0 ? 'active-slide' : 'd-none' }}" 
                                                 data-shift-idx="{{ $sIdx }}"
                                                 data-type="{{ $shift['type'] }}"
                                                 data-user-id="{{ $shift['user_id'] ?? '' }}"
                                                 data-name="{{ $shift['name'] ?? 'Available' }}"
                                                 data-mobile="{{ $shift['mobile'] ?? '' }}"
                                                 data-shift-name="{{ $shift['type'] === 'available' ? ($shift['label'] ?? 'Available') : ($shift['day_type_label'] ?? ($shift['plan_name'] ?? 'Booked')) }}"
                                                 data-plan-end="{{ !empty($shift['plan_end_date']) ? \Carbon\Carbon::parse($shift['plan_end_date'])->format('d M Y') : '' }}"
                                                 data-due-amount="{{ !empty($shift['due_amount']) ? number_format($shift['due_amount']) : '0' }}"
                                                 data-has-due="{{ $hasDue ? '1' : '0' }}"
                                                 data-status-label="{{ $shiftStatusText }}"
                                                 data-status-color="{{ $shiftStatusColor }}">
                                                
                                                <!-- Avatar Area -->
                                                <div class="avatar-container position-relative d-inline-block mx-auto">
                                                    @if($shift['type'] === 'available')
                                                    <div class="avatar-circle-available d-flex align-items-center justify-content-center mx-auto position-relative" title="Available for Booking">
                                                        <i class="fa-solid fa-chair"></i>
                                                    </div>
                                                    @elseif($shift['type'] === 'future')
                                                    <div class="avatar-circle-future d-flex align-items-center justify-content-center mx-auto text-white font-outfit fw-bold shadow-sm position-relative" title="Future Booking: {{ $shift['name'] }}">
                                                        FUT
                                                        <span class="seat-avatar-dot active-dot" style="background-color: #c09600 !important;"></span>
                                                    </div>
                                                    @else
                                                    @php
                                                        $initials = strtoupper(substr($shift['name'], 0, 2));
                                                        $avatarBg = $hasDue ? '#ef4444' : ($isExt ? '#800000' : ($isExpiring ? '#d97706' : ($shift['is_non_expiry'] ? '#c8009d' : '#18225f')));
                                                        $avatarDotClass = $hasDue ? 'seat-avatar-dot due-dot seatBlink' : ($isExt ? 'seat-avatar-dot extension-dot seatBlink' : ($isExpiring ? 'seat-avatar-dot expiring-dot seatBlink' : 'seat-avatar-dot active-dot'));
                                                        $avatarTooltip = $hasDue ? 'Fee Overdue' : ($isExt ? 'Extension Active' : ($isExpiring ? 'About to Expire' : 'Active Booking'));
                                                        $hasPhoto = !empty($shift['profile_picture']);
                                                    @endphp
                                                    @if($hasPhoto)
                                                    <div class="avatar-circle-booked d-flex align-items-center justify-content-center mx-auto shadow-sm position-relative overflow-hidden" 
                                                         style="background-color: {{ $avatarBg }};">
                                                        <a href="{{ asset($shift['profile_picture']) }}" class="view-image w-100 h-100 d-block" title="View {{ $shift['name'] }} photo">
                                                            <img src="{{ asset($shift['profile_picture']) }}" alt="{{ $shift['name'] }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                                        </a>
                                                    </div>
                                                    @else
                                                    <div class="avatar-circle-booked d-flex align-items-center justify-content-center mx-auto text-white font-outfit fw-bold shadow-sm position-relative" 
                                                         style="background-color: {{ $avatarBg }};">
                                                        {{ $initials }}
                                                    </div>
                                                    @endif
                                                    <span class="{{ $avatarDotClass }}" data-bs-toggle="tooltip" title="{{ $avatarTooltip }}"></span>
                                                    @if($hasDue && !empty($shift['due_amount']) && $shift['due_amount'] > 0)
                                                    <span class="seat-due-amount-pill shadow-sm" data-bs-toggle="tooltip" title="Due: ₹{{ number_format($shift['due_amount']) }}">₹{{ number_format($shift['due_amount']) }}</span>
                                                    @endif
                                                    @endif
                                                </div>

                                                <!-- Student Name & Shift Info Row with integrated Left/Right arrows if multi-shift -->
                                                <div class="shift-info-row position-relative w-100">
                                                    @if(count($seatShifts) > 1)
                                                    <button type="button" class="btn btn-sm shift-prev-btn shadow-none" data-seat="{{ $seatNo }}" title="Previous Shift">
                                                        <i class="fa-solid fa-chevron-left"></i>
                                                    </button>
                                                    @endif

                                                    <div class="shift-info text-center w-100">
                                                        @if($shift['type'] === 'available')
                                                        <div class="seat-student-name fw-bold text-success font-outfit" title="Available">
                                                            Available
                                                        </div>
                                                        <div class="seat-shift-label text-muted font-outfit" title="{{ $shift['label'] ?? '' }}">
                                                            {{ strtoupper($shift['label'] ?? '') }}
                                                        </div>
                                                        @elseif($shift['type'] === 'future')
                                                        <div class="seat-student-name fw-bold text-warning font-outfit" title="{{ $shift['name'] }}">
                                                            {{ $shift['name'] }}
                                                        </div>
                                                        <div class="seat-shift-label text-muted font-outfit" title="{{ $shift['day_type_label'] ?? '' }}">
                                                            {{ strtoupper($shift['day_type_label'] ?? '') }}
                                                        </div>
                                                        @else
                                                        @php
                                                            $bkIsFrozen = (int)($shift['frozen_status'] ?? 0) === 1 || !empty($shift['freeze_start_date']);
                                                            $bkColor = $bkIsFrozen ? 'text-danger' : 'text-muted';
                                                        @endphp
                                                        <div class="seat-student-name fw-bold font-outfit" title="{{ $shift['name'] }}">
                                                            {{ $shift['name'] }}
                                                        </div>
                                                        <div class="seat-shift-label {{ $bkColor }} font-outfit" title="{{ $shift['day_type_label'] ?? '' }}">
                                                            {{ strtoupper($shift['day_type_label'] ?? '') }}
                                                        </div>
                                                        @endif
                                                    </div>

                                                    @if(count($seatShifts) > 1)
                                                    <button type="button" class="btn btn-sm shift-next-btn shadow-none" data-seat="{{ $seatNo }}" title="Next Shift">
                                                        <i class="fa-solid fa-chevron-right"></i>
                                                    </button>
                                                    @endif
                                                </div>

                                                <!-- Action Button -->
                                                <div class="card-action-bar w-100 text-center">
                                                    @if($shift['type'] === 'available')
                                                    <button type="button" class="btn btn-success btn-sm font-outfit fw-bold rounded-pill first_popup shadow-none seat-action-btn" 
                                                            data-bs-toggle="modal" data-bs-target="#seatAllotmentModal" 
                                                            data-id="{{ $seatNo }}" 
                                                            data-seat_no="{{ $seatNo }}" 
                                                            data-plan_type_id="{{ $shift['plan_type_id'] ?? '' }}" 
                                                            data-day_type_id="{{ $shift['day_type_id'] ?? '' }}">
                                                        <i class="fa-solid fa-plus me-0.5" style="font-size: 0.62rem;"></i> Book
                                                    </button>
                                                    @else
                                                    <button type="button" class="btn btn-sm font-outfit fw-bold rounded-pill second_popup shadow-none seat-action-btn" 
                                                            data-bs-toggle="modal" data-bs-target="#seatAllotmentModal2" data-seat_no="{{ $seatNo }}" data-userid="{{ $shift['user_id'] }}" 
                                                            style="background-color: {{ $shiftStatusColor }}; color: #ffffff !important;">
                                                        View
                                                    </button>
                                                    @endif
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>

                                        <!-- Bottom Indicator Dots Row (Only if multi-shift) -->
                                        @if(count($seatShifts) > 1)
                                        <div class="shift-dots-row">
                                            @foreach($seatShifts as $sIdx => $shift)
                                            @php
                                                $dotColor = $shift['type'] === 'available' ? '#22c55e' : (!empty($shift['has_due']) ? '#ef4444' : ((!empty($shift['is_extended']) || (isset($shift['class']) && in_array($shift['class'], ['extedned', 'extended']))) ? '#800000' : ((!empty($shift['is_expiring']) || (isset($shift['class']) && $shift['class'] === 'aboutToExpire')) ? '#d97706' : ((!empty($shift['is_non_expiry']) || (isset($shift['class']) && $shift['class'] === 'non_expiry_class')) ? '#c8009d' : (($shift['type'] === 'future' || !empty($shift['is_future'])) ? '#c09600' : '#18225f')))));
                                            @endphp
                                            <span class="shift-dot cursor-pointer {{ $sIdx === 0 ? 'active-dot' : '' }}" 
                                                  data-seat="{{ $seatNo }}" data-shift-idx="{{ $sIdx }}" 
                                                  style="background-color: {{ $dotColor }}; opacity: {{ $sIdx === 0 ? '1' : '0.35' }}; transform: {{ $sIdx === 0 ? 'scale(1.3)' : 'scale(1)' }};"></span>
                                            @endforeach
                                        </div>
                                        @endif

                                        <!-- Dense View Tile (Displayed in Dense Mode) -->
                                        <div class="seat-dense-tile">
                                            <div class="dense-seat-no">{{ sprintf('%02d', $seatNo) }}</div>
                                            <div class="dense-status-badge" style="background-color: {{ $primaryStatusColor }};"></div>
                                            <div class="dense-name">
                                                @if($hasBookedShift)
                                                    {{ $studentNames[0] ?? 'Booked' }}
                                                @elseif($hasFutureShift)
                                                    Future
                                                @else
                                                    Free
                                                @endif
                                            </div>
                                        </div>

                                    </div>
                                    @php
                                        $seatNo++;
                                        $startSeat++;
                                    @endphp
                                @endfor

                                </div>
                                    </div>
                                </div>
                            @endforeach

                            {{-- Show remaining seats if total_seats > last assigned seat --}}
                            @if($seatNo <= $total_seats)
                                <div class="floor-collapsible-card mb-4 overflow-hidden bg-white shadow-sm" style="border: 1px solid #e2e8f0 !important; border-radius: 1rem !important; background: #ffffff !important;">
                                    <div class="floor-header-bar p-3 d-flex align-items-center justify-content-between cursor-pointer bg-white" 
                                         data-bs-toggle="collapse" 
                                         data-bs-target="#floorCollapse_unassigned" 
                                         aria-expanded="true" 
                                         aria-controls="floorCollapse_unassigned"
                                         style="background: #ffffff; color: #18225f; cursor: pointer; user-select: none; border-bottom: 1px solid #f1f5f9;">
                                        
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-layer-group" style="color: #18225f; font-size: 0.95rem;"></i>
                                            <h5 class="mb-0 font-outfit fw-bold text-uppercase tracking-wide floor-title-text" style="color: #18225f;">Seats Without Floor</h5>
                                            <span class="badge rounded-pill ms-2 font-outfit small fw-bold floor-seats-badge" style="background-color: #f1f5f9; color: #18225f; border: 1px solid #cbd5e1;">
                                                Seats {{ $seatNo }} - {{ $total_seats }}
                                            </span>
                                        </div>

                                        <div class="d-flex align-items-center gap-2">
                                            <span class="small font-outfit text-muted d-none d-md-inline" style="font-size: 0.8rem;">Click to Collapse / Expand</span>
                                            <i class="fa-solid fa-chevron-down collapse-icon ms-1" style="font-size: 0.95rem; color: #18225f; transition: transform 0.3s ease;"></i>
                                        </div>
                                    </div>

                                    <div class="collapse show p-3 floor-seat-collapse" id="floorCollapse_unassigned">
                                        <div class="seat-booking">
                                @for($seatNo2 = $seatNo; $seatNo2 <= $total_seats; $seatNo2++)
                                    @php
                                        $seatNo = $seatNo2;
                                        $usersForSeat = $activeLearnersBySeat->get((string)$seatNo, collect());
                                        $sumofhourseat = $hoursSumBySeat->get((string)$seatNo, 0);
                                        $remainingHours = $total_hour - $sumofhourseat;

                                        $dayTypesMap = [
                                            1 => 'Full Day', 
                                            2 => '1st Half', 
                                            3 => '2nd Half', 
                                            4 => 'Hourly 1', 
                                            5 => 'Hourly 2', 
                                            6 => 'Hourly 3', 
                                            7 => 'Hourly 4', 
                                            8 => 'All Day', 
                                            9 => 'Full Night',
                                            10 => 'RESERVED',
                                            11 => 'VIP'
                                        ];

                                        $seatShifts = [];
                                        $bookedDayTypeIds = [];

                                        if ($usersForSeat->count() > 0) {
                                            foreach ($usersForSeat as $u) {
                                                $planDetails = getPlanStatusDetails($u->plan_end_date);
                                                $isNonExpiry = (int) ($u->no_expiry ?? 0) === 1;
                                                $isExtended = ($planDetails['status'] === 'In Extension' || $planDetails['status'] === 'Extension ends today' || (isset($planDetails['class']) && in_array($planDetails['class'], ['extedned', 'extended'])));
                                                $isFuture = (!empty($u->plan_start_date) && \Carbon\Carbon::parse($u->plan_start_date)->isFuture());
                                                $isExpiring = (isset($planDetails['class']) && $planDetails['class'] === 'aboutToExpire');

                                                // Skip expired bookings so only active seats/bookings are shown on seatmap
                                                if ($planDetails['status'] === 'Expired' && !$isNonExpiry) {
                                                    continue;
                                                }

                                                $hasDue = $pendingAmountCache[$u->learner_detail_id] ?? (pending_amt($u->learner_detail_id) || paylater($u->learner_detail_id) || (int)($u->is_paid ?? 1) === 0 || (int)($u->payment_mode ?? 0) === 3);
                                                $dueVal = $dueAmountValueCache[$u->learner_detail_id] ?? 0;
                                                if ($hasDue && $dueVal <= 0) {
                                                    $dueVal = (float)($u->plan_price_id ?? 0);
                                                }

                                                $bookedDayTypeIds[] = $u->day_type_id;

                                                $learnerName = !empty($u->name) ? $u->name : ('Student #' . $u->id);

                                                $seatShifts[] = [
                                                    'type' => $isFuture ? 'future' : 'booked',
                                                    'user_id' => $u->id,
                                                    'learner_detail_id' => $u->learner_detail_id,
                                                    'name' => $learnerName,
                                                    'mobile' => $u->mobile ?? '',
                                                    'profile_picture' => $u->profile_picture,
                                                    'plan_name' => $u->plan_type_name ?? 'Plan',
                                                    'day_type_id' => $u->day_type_id,
                                                    'day_type_label' => $dayTypesMap[$u->day_type_id] ?? ($u->plan_type_name ?? 'Booked'),
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
                                            if (in_array((int)$dt, [1, 2, 3, 4, 5, 6, 7])) {
                                                $hasAnyDaytimeBooked = true;
                                                break;
                                            }
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
                                                    if (in_array($pt->id, $bookedPlanTypeIds)) {
                                                        continue;
                                                    }
                                                    // 2. Cannot book the same day_type already booked (unless custom 0)
                                                    if ($pt->day_type_id != 0 && in_array($pt->day_type_id, $bookedDayTypeIds)) {
                                                        continue;
                                                    }
                                                    // 3. 24-hr shifts require seat to be completely unbooked
                                                    if (count($bookedDayTypeIds) > 0 && in_array($pt->day_type_id, [8, 10, 11])) {
                                                        continue;
                                                    }
                                                    // 4. If branch open hours < 24, All Day (8) and Full Night (9) are not allowed
                                                    if ($branchTotalHours < 24 && in_array($pt->day_type_id, [8, 9])) {
                                                        continue;
                                                    }
                                                    // 5. If any daytime shift is booked, Full Day (1) cannot be booked
                                                    if ($pt->day_type_id == 1 && $hasAnyDaytimeBooked) {
                                                        continue;
                                                    }
                                                    // 6. If Full Day (1) is booked, daytime half/hourly shifts cannot be booked
                                                    if (in_array(1, $bookedDayTypeIds) && in_array($pt->day_type_id, [2, 3, 4, 5, 6, 7])) {
                                                        continue;
                                                    }

                                                    $seatShifts[] = [
                                                        'type' => 'available',
                                                        'label' => $pt->name,
                                                        'day_type_id' => $pt->day_type_id,
                                                        'plan_type_id' => $pt->id
                                                    ];
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

                                        $bookedCount = count($usersForSeat);
                                        if ($bookedCount === 0) {
                                            $overallStatus = 'Available';
                                            $overallStatusClass = 'text-success';
                                        } elseif ($remainingHours > 0) {
                                            $overallStatus = 'Partly booked';
                                            $overallStatusClass = 'text-primary';
                                        } else {
                                            $overallStatus = 'Needs action';
                                            $overallStatusClass = 'text-danger';
                                        }

                                        $studentNames = [];
                                        $shiftLabels = [];
                                        $hasAvailableShift = false;
                                        $hasBookedShift = false;
                                        $hasDueShift = false;
                                        $hasExpiringShift = false;
                                        $hasExtendedShift = false;
                                        $hasFutureShift = false;
                                        $hasNonExpiredShift = false;

                                        foreach ($seatShifts as $sh) {
                                            if ($sh['type'] === 'available') {
                                                $hasAvailableShift = true;
                                                if (!empty($sh['label'])) {
                                                    $shiftLabels[] = strtolower($sh['label']);
                                                }
                                            } elseif ($sh['type'] === 'future' || !empty($sh['is_future'])) {
                                                $hasFutureShift = true;
                                                if (!empty($sh['name'])) {
                                                    $studentNames[] = strtolower($sh['name']);
                                                }
                                                if (!empty($sh['day_type_label'])) {
                                                    $shiftLabels[] = strtolower($sh['day_type_label']);
                                                }
                                            } elseif ($sh['type'] === 'booked') {
                                                $hasBookedShift = true;
                                                if (!empty($sh['name'])) {
                                                    $studentNames[] = strtolower($sh['name']);
                                                }
                                                if (!empty($sh['day_type_label'])) {
                                                    $shiftLabels[] = strtolower($sh['day_type_label']);
                                                }
                                            }

                                            if (!empty($sh['has_due'])) {
                                                $hasDueShift = true;
                                            }
                                            if (!empty($sh['is_expiring'])) {
                                                $hasExpiringShift = true;
                                            }
                                            if (!empty($sh['is_extended'])) {
                                                $hasExtendedShift = true;
                                            }
                                            if (!empty($sh['is_non_expiry'])) {
                                                $hasNonExpiredShift = true;
                                            }
                                        }

                                        // Status color for dense view and card top accent border
                                        $primaryStatusColor = '#22c55e';
                                        if ($hasDueShift) {
                                            $primaryStatusColor = '#ef4444';
                                        } elseif ($hasExtendedShift) {
                                            $primaryStatusColor = '#800000';
                                        } elseif ($hasExpiringShift) {
                                            $primaryStatusColor = '#d97706';
                                        } elseif ($hasNonExpiredShift) {
                                            $primaryStatusColor = '#c8009d';
                                        } elseif ($hasFutureShift) {
                                            $primaryStatusColor = '#c09600';
                                        } elseif ($hasBookedShift) {
                                            $primaryStatusColor = '#18225f';
                                        }

                                        $isWholeDayBooked = in_array(1, $bookedDayTypeIds) ||
                                                            in_array(8, $bookedDayTypeIds) ||
                                                            in_array(10, $bookedDayTypeIds) ||
                                                            in_array(11, $bookedDayTypeIds) ||
                                                            ($is24HoursBooked && count($usersForSeat) === 1);
                                    @endphp

                                    <div class="seat-card-item bg-white border position-relative d-flex flex-column align-items-center justify-content-between text-center" 
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
                                         data-has-non-expired="{{ $hasNonExpiredShift ? '1' : '0' }}">
                                        
                                        <!-- Top Bar: Compact Seat Badge & Shift Count Chip -->
                                        <div class="card-top-bar d-flex align-items-center justify-content-between w-100">
                                            <span class="seat-badge-pill font-outfit fw-bold">
                                                Seat {{ sprintf('%02d', $seatNo) }}
                                            </span>
                                            @if($isWholeDayBooked)
                                            <span class="seat-shift-count-chip font-outfit fw-bold" title="Whole Day Booked">1</span>
                                            @elseif(count($seatShifts) > 1)
                                            <span class="seat-shift-count-chip font-outfit fw-bold" title="{{ count($seatShifts) }} Shifts Available/Booked">
                                                <span class="current-shift-num">1</span>/{{ count($seatShifts) }}
                                            </span>
                                            @endif
                                        </div>

                                        <!-- Shift Slides Container -->
                                        <div class="shift-slides-wrapper w-100 position-relative">
                                            @foreach($seatShifts as $sIdx => $shift)
                                            @php
                                                $isExt = (isset($shift['class']) && ($shift['class'] === 'extedned' || $shift['class'] === 'extended')) || !empty($shift['is_extended']);
                                                $hasDue = !empty($shift['has_due']);
                                                $isExpiring = !empty($shift['is_expiring']) || (isset($shift['class']) && $shift['class'] === 'aboutToExpire');
                                                $isNonExpiry = !empty($shift['is_non_expiry']) || (isset($shift['class']) && $shift['class'] === 'non_expiry_class');
                                                $isFuture = ($shift['type'] === 'future' || !empty($shift['is_future']));

                                                if ($hasDue) {
                                                    $shiftStatusColor = '#ef4444'; // Pending Fee / Due
                                                } elseif ($isExt) {
                                                    $shiftStatusColor = '#800000'; // Extended
                                                } elseif ($isExpiring) {
                                                    $shiftStatusColor = '#d97706'; // About to Expire
                                                } elseif ($isNonExpiry) {
                                                    $shiftStatusColor = '#c8009d'; // Non-Expiry
                                                } elseif ($isFuture) {
                                                    $shiftStatusColor = '#c09600'; // Future Booking
                                                } else {
                                                    $shiftStatusColor = '#18225f'; // Booked
                                                }

                                                $shiftStatusText = $shift['type'] === 'available' ? 'Available' : ($hasDue ? 'Fee Overdue' : ($isExt ? 'In Extension' : ($isExpiring ? 'Expiring Soon' : ($isNonExpiry ? 'Non-Expiry' : ($isFuture ? 'Future Booked' : 'Active Booked')))));
                                            @endphp
                                            <div class="shift-slide-item {{ $sIdx === 0 ? 'active-slide' : 'd-none' }}" 
                                                 data-shift-idx="{{ $sIdx }}"
                                                 data-type="{{ $shift['type'] }}"
                                                 data-user-id="{{ $shift['user_id'] ?? '' }}"
                                                 data-name="{{ $shift['name'] ?? 'Available' }}"
                                                 data-mobile="{{ $shift['mobile'] ?? '' }}"
                                                 data-shift-name="{{ $shift['type'] === 'available' ? ($shift['label'] ?? 'Available') : ($shift['day_type_label'] ?? ($shift['plan_name'] ?? 'Booked')) }}"
                                                 data-plan-end="{{ !empty($shift['plan_end_date']) ? \Carbon\Carbon::parse($shift['plan_end_date'])->format('d M Y') : '' }}"
                                                 data-due-amount="{{ !empty($shift['due_amount']) ? number_format($shift['due_amount']) : '0' }}"
                                                 data-has-due="{{ $hasDue ? '1' : '0' }}"
                                                 data-status-label="{{ $shiftStatusText }}"
                                                 data-status-color="{{ $shiftStatusColor }}">
                                                
                                                <!-- Avatar Area -->
                                                <div class="avatar-container position-relative d-inline-block mx-auto">
                                                    @if($shift['type'] === 'available')
                                                    <div class="avatar-circle-available d-flex align-items-center justify-content-center mx-auto position-relative" title="Available for Booking">
                                                        <i class="fa-solid fa-chair"></i>
                                                    </div>
                                                    @elseif($shift['type'] === 'future')
                                                    <div class="avatar-circle-future d-flex align-items-center justify-content-center mx-auto text-white font-outfit fw-bold shadow-sm position-relative" title="Future Booking: {{ $shift['name'] }}">
                                                        FUT
                                                        <span class="seat-avatar-dot active-dot" style="background-color: #c09600 !important;"></span>
                                                    </div>
                                                    @else
                                                    @php
                                                        $initials = strtoupper(substr($shift['name'], 0, 2));
                                                        $avatarBg = $hasDue ? '#ef4444' : ($isExt ? '#800000' : ($isExpiring ? '#d97706' : ($shift['is_non_expiry'] ? '#c8009d' : '#18225f')));
                                                        $avatarDotClass = $hasDue ? 'seat-avatar-dot due-dot seatBlink' : ($isExt ? 'seat-avatar-dot extension-dot seatBlink' : ($isExpiring ? 'seat-avatar-dot expiring-dot seatBlink' : 'seat-avatar-dot active-dot'));
                                                        $avatarTooltip = $hasDue ? 'Fee Overdue' : ($isExt ? 'Extension Active' : ($isExpiring ? 'About to Expire' : 'Active Booking'));
                                                        $hasPhoto = !empty($shift['profile_picture']);
                                                    @endphp
                                                    @if($hasPhoto)
                                                    <div class="avatar-circle-booked d-flex align-items-center justify-content-center mx-auto shadow-sm position-relative overflow-hidden" 
                                                         style="background-color: {{ $avatarBg }};">
                                                        <a href="{{ asset($shift['profile_picture']) }}" class="view-image w-100 h-100 d-block" title="View {{ $shift['name'] }} photo">
                                                            <img src="{{ asset($shift['profile_picture']) }}" alt="{{ $shift['name'] }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                                        </a>
                                                    </div>
                                                    @else
                                                    <div class="avatar-circle-booked d-flex align-items-center justify-content-center mx-auto text-white font-outfit fw-bold shadow-sm position-relative" 
                                                         style="background-color: {{ $avatarBg }};">
                                                        {{ $initials }}
                                                    </div>
                                                    @endif
                                                    <span class="{{ $avatarDotClass }}" data-bs-toggle="tooltip" title="{{ $avatarTooltip }}"></span>
                                                    @if($hasDue && !empty($shift['due_amount']) && $shift['due_amount'] > 0)
                                                    <span class="seat-due-amount-pill shadow-sm" data-bs-toggle="tooltip" title="Due: ₹{{ number_format($shift['due_amount']) }}">₹{{ number_format($shift['due_amount']) }}</span>
                                                    @endif
                                                    @endif
                                                </div>

                                                <!-- Student Name & Shift Info Row with integrated Left/Right arrows if multi-shift -->
                                                <div class="shift-info-row position-relative w-100">
                                                    @if(count($seatShifts) > 1)
                                                    <button type="button" class="btn btn-sm shift-prev-btn shadow-none" data-seat="{{ $seatNo }}" title="Previous Shift">
                                                        <i class="fa-solid fa-chevron-left"></i>
                                                    </button>
                                                    @endif

                                                    <div class="shift-info text-center w-100">
                                                        @if($shift['type'] === 'available')
                                                        <div class="seat-student-name fw-bold text-success font-outfit" title="Available">
                                                            Available
                                                        </div>
                                                        <div class="seat-shift-label text-muted font-outfit" title="{{ $shift['label'] ?? '' }}">
                                                            {{ strtoupper($shift['label'] ?? '') }}
                                                        </div>
                                                        @elseif($shift['type'] === 'future')
                                                        <div class="seat-student-name fw-bold text-warning font-outfit" title="{{ $shift['name'] }}">
                                                            {{ $shift['name'] }}
                                                        </div>
                                                        <div class="seat-shift-label text-muted font-outfit" title="{{ $shift['day_type_label'] ?? '' }}">
                                                            {{ strtoupper($shift['day_type_label'] ?? '') }}
                                                        </div>
                                                        @else
                                                        @php
                                                            $bkIsFrozen = (int)($shift['frozen_status'] ?? 0) === 1 || !empty($shift['freeze_start_date']);
                                                            $bkColor = $bkIsFrozen ? 'text-danger' : 'text-muted';
                                                        @endphp
                                                        <div class="seat-student-name fw-bold font-outfit" title="{{ $shift['name'] }}">
                                                            {{ $shift['name'] }}
                                                        </div>
                                                        <div class="seat-shift-label {{ $bkColor }} font-outfit" title="{{ $shift['day_type_label'] ?? '' }}">
                                                            {{ strtoupper($shift['day_type_label'] ?? '') }}
                                                        </div>
                                                        @endif
                                                    </div>

                                                    @if(count($seatShifts) > 1)
                                                    <button type="button" class="btn btn-sm shift-next-btn shadow-none" data-seat="{{ $seatNo }}" title="Next Shift">
                                                        <i class="fa-solid fa-chevron-right"></i>
                                                    </button>
                                                    @endif
                                                </div>

                                                <!-- Action Button -->
                                                <div class="card-action-bar w-100 text-center">
                                                    @if($shift['type'] === 'available')
                                                    <button type="button" class="btn btn-success btn-sm font-outfit fw-bold rounded-pill first_popup shadow-none seat-action-btn" 
                                                            data-bs-toggle="modal" data-bs-target="#seatAllotmentModal" 
                                                            data-id="{{ $seatNo }}" 
                                                            data-seat_no="{{ $seatNo }}" 
                                                            data-plan_type_id="{{ $shift['plan_type_id'] ?? '' }}" 
                                                            data-day_type_id="{{ $shift['day_type_id'] ?? '' }}">
                                                        <i class="fa-solid fa-plus me-0.5" style="font-size: 0.62rem;"></i> Book
                                                    </button>
                                                    @else
                                                    <button type="button" class="btn btn-sm font-outfit fw-bold rounded-pill second_popup shadow-none seat-action-btn" 
                                                            data-bs-toggle="modal" data-bs-target="#seatAllotmentModal2" data-seat_no="{{ $seatNo }}" data-userid="{{ $shift['user_id'] }}" 
                                                            style="background-color: {{ $shiftStatusColor }}; color: #ffffff !important;">
                                                        View
                                                    </button>
                                                    @endif
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>

                                        <!-- Bottom Indicator Dots Row (Only if multi-shift) -->
                                        @if(count($seatShifts) > 1)
                                        <div class="shift-dots-row">
                                            @foreach($seatShifts as $sIdx => $shift)
                                            @php
                                                $dotColor = $shift['type'] === 'available' ? '#22c55e' : (!empty($shift['has_due']) ? '#ef4444' : ((!empty($shift['is_extended']) || (isset($shift['class']) && in_array($shift['class'], ['extedned', 'extended']))) ? '#800000' : ((!empty($shift['is_expiring']) || (isset($shift['class']) && $shift['class'] === 'aboutToExpire')) ? '#d97706' : ((!empty($shift['is_non_expiry']) || (isset($shift['class']) && $shift['class'] === 'non_expiry_class')) ? '#c8009d' : (($shift['type'] === 'future' || !empty($shift['is_future'])) ? '#c09600' : '#18225f')))));
                                            @endphp
                                            <span class="shift-dot cursor-pointer {{ $sIdx === 0 ? 'active-dot' : '' }}" 
                                                  data-seat="{{ $seatNo }}" data-shift-idx="{{ $sIdx }}" 
                                                  style="background-color: {{ $dotColor }}; opacity: {{ $sIdx === 0 ? '1' : '0.35' }}; transform: {{ $sIdx === 0 ? 'scale(1.3)' : 'scale(1)' }};"></span>
                                            @endforeach
                                        </div>
                                        @endif

                                        <!-- Dense View Tile (Displayed in Dense Mode) -->
                                        <div class="seat-dense-tile">
                                            <div class="dense-seat-no">{{ sprintf('%02d', $seatNo) }}</div>
                                            <div class="dense-status-badge" style="background-color: {{ $primaryStatusColor }};"></div>
                                            <div class="dense-name">
                                                @if($hasBookedShift)
                                                    {{ $studentNames[0] ?? 'Booked' }}
                                                @elseif($hasFutureShift)
                                                    Future
                                                @else
                                                    Free
                                                @endif
                                            </div>
                                        </div>

                                    </div>
                                @endfor
                                </div>
                                    </div>
                                </div>
                            @endif
                        @endif

                </div>
            </div>

            <div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab" tabindex="0">
                <div class="floor-collapsible-card mb-4 overflow-hidden bg-white shadow-sm" style="border: 1px solid #e2e8f0 !important; border-radius: 1rem !important; background: #ffffff !important;">
                    <div class="floor-header-bar p-3 d-flex align-items-center justify-content-between bg-white" style="background: #ffffff; color: #18225f; border-bottom: 1px solid #f1f5f9;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-users" style="color: #18225f; font-size: 0.95rem;"></i>
                            <h5 class="mb-0 font-outfit fw-bold text-uppercase tracking-wide floor-title-text" style="color: #18225f;">General Shift Seats</h5>
                            <span class="badge rounded-pill ms-2 font-outfit small fw-bold floor-seats-badge" style="background-color: #f1f5f9; color: #18225f; border: 1px solid #cbd5e1;">
                                {{ countWithoutSeatNo() }} Booked
                            </span>
                        </div>
                    </div>
                    <div class="p-3 floor-seat-collapse">
                        <div class="seat-booking">

                    @if(countWithoutSeatNo() > 0)
                    @php
                    $usersForSeat = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
                        ->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
                        ->where('learners.branch_id', getCurrentBranch())
                        ->where(function ($q) {
                            $q->whereNull('learners.seat_no')
                              ->orWhere('learners.seat_no', '')
                              ->orWhere('learners.seat_no', '0')
                              ->orWhere('learners.seat_no', 0)
                              ->orWhere('learners.seat_no', 'GEN');
                        })
                        ->select(
                            'learners.id',
                            'learners.name',
                            'learners.mobile',
                            'learners.profile_picture',
                            'learners.no_expiry',
                            'learner_detail.plan_type_id',
                            'plan_types.day_type_id',
                            'plan_types.image',
                            'learner_detail.plan_end_date',
                            'learner_detail.id as learner_detail_id',
                            'plan_types.name as plan_type_name'
                        )
                        ->where('learners.status', 1)
                        ->where('learner_detail.status', 1)
                        ->get();
                    @endphp
                    @foreach($usersForSeat as $genIdx => $user)

                        @php
                        $planDetails = getPlanStatusDetails($user->plan_end_date);
                        $hasDue = $pendingAmountCache[$user->learner_detail_id] ?? pending_amt($user->learner_detail_id);
                        $isNonExpiry = (int) ($user->no_expiry ?? 0) === 1;
                        $isExt = ($planDetails['status'] === 'In Extension' || $planDetails['status'] === 'Extension ends today' || (isset($planDetails['class']) && in_array($planDetails['class'], ['extedned', 'extended'])));
                        $isFuture = (!empty($user->plan_start_date) && \Carbon\Carbon::parse($user->plan_start_date)->isFuture());
                        $isExpiring = (isset($planDetails['class']) && $planDetails['class'] === 'aboutToExpire');
                        
                        if ($hasDue) {
                            $genStatusColor = '#ef4444'; // Red (Due)
                        } elseif ($isExt) {
                            $genStatusColor = '#800000'; // Maroon (Extended)
                        } elseif ($isExpiring) {
                            $genStatusColor = '#d97706'; // Amber/Orange (Expiring)
                        } elseif ($isNonExpiry) {
                            $genStatusColor = '#c8009d'; // Pink/Magenta (Non-Expiry)
                        } elseif ($isFuture) {
                            $genStatusColor = '#c09600'; // Yellow/Gold (Future)
                        } else {
                            $genStatusColor = '#0284c7'; // Sky Blue (General Booked)
                        }

                        $avatarBg = $hasDue ? '#ef4444' : ($isExt ? '#800000' : ($isExpiring ? '#d97706' : ($isNonExpiry ? '#c8009d' : ($isFuture ? '#c09600' : '#0284c7'))));
                        $initials = strtoupper(substr($user->name, 0, 2));
                        $avatarDotClass = $hasDue ? 'seat-avatar-dot due-dot seatBlink' : ($isExt ? 'seat-avatar-dot extension-dot seatBlink' : ($isExpiring ? 'seat-avatar-dot expiring-dot seatBlink' : 'seat-avatar-dot active-dot'));
                        $avatarTooltip = $hasDue ? 'Fee Overdue' : ($isExt ? 'Extension Active' : ($isExpiring ? 'About to Expire' : ($isFuture ? 'Future Booking' : 'Active Booking')));
                        $hasPhoto = !empty($user->profile_picture);
                        $genIsFrozen = (int)($user->frozen_status ?? 0) === 1 || !empty($user->freeze_start_date);
                        $genLabelStr = $genIsFrozen 
                            ? ('FREEZED ON ' . (\Carbon\Carbon::parse($user->freeze_start_date)->format('d/m/Y')))
                            : strtoupper($user->plan_type_name ?? 'GENERAL');
                        @endphp

                        <div class="seat-card-item bg-white border position-relative d-flex flex-column align-items-center justify-content-between text-center" 
                             data-seat-no="GEN-{{ $genIdx + 1 }}"
                             data-student-names="{{ strtolower($user->name) }}"
                             data-shifts="{{ strtolower($user->plan_type_name) }}"
                             data-has-available="0"
                             data-has-booked="1"
                             data-has-due="{{ $hasDue ? '1' : '0' }}"
                             data-has-expiring="{{ $isExpiring ? '1' : '0' }}"
                             data-has-extended="{{ $isExt ? '1' : '0' }}"
                             data-has-future="{{ $isFuture ? '1' : '0' }}"
                             data-has-non-expired="{{ $isNonExpiry ? '1' : '0' }}"
                             data-preview-name="{{ $user->name }}"
                             data-preview-mobile="{{ $user->mobile ?? '' }}"
                             data-preview-seat="GEN #{{ sprintf('%02d', $genIdx + 1) }}"
                             data-preview-shift="{{ $user->plan_type_name }}"
                             data-preview-status="{{ $avatarTooltip }}"
                             data-preview-color="{{ $genStatusColor }}"
                             data-preview-due="{{ $hasDue ? '₹' . $hasDue : '' }}"
                             data-preview-validity="{{ !empty($user->plan_end_date) ? \Carbon\Carbon::parse($user->plan_end_date)->format('d M Y') : 'N/A' }}"
                             data-preview-avatar="{{ $hasPhoto ? asset($user->profile_picture) : '' }}"
                             data-preview-initials="{{ $initials }}">

                            <!-- Top Bar: Seat Badge & Chip -->
                            <div class="card-top-bar d-flex align-items-center justify-content-between w-100">
                                <span class="seat-badge-pill font-outfit fw-bold">GEN {{ sprintf('%02d', $genIdx + 1) }}</span>
                                <span class="seat-shift-count-chip font-outfit fw-bold" title="General Shift">1</span>
                            </div>

                            <!-- Avatar & Learner Info Area -->
                            <div class="avatar-container position-relative d-inline-block mx-auto">
                                @if($hasPhoto)
                                <div class="avatar-circle-booked d-flex align-items-center justify-content-center mx-auto text-white font-outfit fw-bold position-relative overflow-hidden" 
                                     style="background-color: {{ $avatarBg }};">
                                    <a href="{{ asset($user->profile_picture) }}" class="view-image w-100 h-100 d-block" title="View {{ $user->name }} photo">
                                        <img src="{{ asset($user->profile_picture) }}" alt="{{ $user->name }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                    </a>
                                </div>
                                @else
                                <div class="avatar-circle-booked d-flex align-items-center justify-content-center mx-auto text-white font-outfit fw-bold position-relative" 
                                     style="background-color: {{ $avatarBg }};">
                                    {{ $initials }}
                                </div>
                                @endif
                                <span class="{{ $avatarDotClass }}" data-bs-toggle="tooltip" title="{{ $avatarTooltip }}"></span>
                                @if($hasDue)
                                <span class="seat-due-amount-pill shadow-sm" data-bs-toggle="tooltip" title="Due: ₹{{ $hasDue }}">₹{{ $hasDue }}</span>
                                @endif
                            </div>

                            <!-- Student Name & Shift Info Row -->
                            <div class="shift-info-row position-relative w-100">
                                <div class="shift-info text-center w-100">
                                    <div class="seat-student-name fw-bold font-outfit" title="{{ $user->name }}">
                                        {{ $user->name }}
                                    </div>
                                    <div class="seat-shift-label text-muted font-outfit" title="{{ $genLabelStr }}">
                                        {{ $genLabelStr }}
                                    </div>
                                </div>
                            </div>

                            <!-- Action Button -->
                            <div class="card-action-bar w-100 text-center">
                                <button type="button" class="btn btn-sm font-outfit fw-bold rounded-pill second_popup_without_seat shadow-none seat-action-btn" 
                                        data-bs-toggle="modal" data-bs-target="#seatAllotmentModal2" data-userid="{{ $user->id }}" 
                                        style="background-color: {{ $genStatusColor }}; color: #ffffff !important;">
                                    View
                                </button>
                            </div>

                        </div>
                    @endforeach
                    @else
                    <div class="col-12 text-center py-4">
                        <div class="p-4 bg-white border rounded-4 shadow-sm d-inline-block" style="max-width: 400px;">
                            <i class="fa-solid fa-chair text-muted fs-1 mb-3" style="color: #cbd5e1 !important;"></i>
                            <h5 class="fw-bold font-outfit text-dark mb-1">No General Seats Booked</h5>
                            <p class="small text-muted font-outfit mb-0">Currently there are no general seat bookings for this branch.</p>
                        </div>
                    </div>
                    @endif

                </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
</div>

@else
<p class="info-message mt-4 mb-0">
    you dont select any branch
</p>
@endif
@can('has-permission', 'View Seat')
<div class="modal fade library-seat-module" id="seatAllotmentModal2" tabindex="-1" aria-labelledby="seat_details_info" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header border-bottom py-3 px-3 px-md-4 bg-white">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="modal-seat-icon-badge d-inline-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-couch"></i>
                    </span>
                    <h1 class="modal-title fs-5 mb-0 fw-semibold font-outfit" id="seat_details_info">Book Seat</h1>
                    <span id="seat_name" style="display: none;"></span>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="actions border-0 shadow-none p-0 bg-transparent mb-0">
                            <!-- Top Card: Learners Info (Sleek Executive Card) -->
                            <div class="upper-box">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="upper-box-icon-badge d-inline-flex align-items-center justify-content-center">
                                            <i class="fa-solid fa-graduation-cap"></i>
                                        </span>
                                        <h4 class="mb-0 fw-semibold font-outfit text-white" style="font-size: 0.95rem;">Learners Info</h4>
                                    </div>
                                    @if(Auth::user()->can('has-permission', 'Edit Seat') || Auth::user()->can('has-permission', 'Learner Edit'))
                                     <a href="javascript:void(0)" class="btn btn-sm rounded-pill px-3 py-1 font-outfit fw-semibold shadow-none header-edit-profile-btn" id="headerEditProfileBtn">
                                         <i class="fa-solid fa-user-pen me-1"></i> Edit Profile
                                     </a>
                                    @endif
                                </div>
                                <div class="row g-2">
                                    <div class="col-sm-6 col-6">
                                        <div class="modal-info-item">
                                            <span class="modal-info-label">Seat Owner Name</span>
                                            <h5 id="owner" class="uppercase modal-info-val">NA</h5>
                                        </div>
                                    </div>
                                    @if(!in_array('2', toggleHideField()))
                                    <div class="col-sm-6 col-6">
                                        <div class="modal-info-item">
                                            <span class="modal-info-label">Date Of Birth</span>
                                            <h5 id="learner_dob" class="modal-info-val">NA</h5>
                                        </div>
                                    </div>
                                    @endif

                                    <div class="col-sm-6 col-6">
                                        <div class="modal-info-item">
                                            <span class="modal-info-label">Mobile Number</span>
                                            <h5 id="learner_mobile" class="modal-info-val">NA</h5>
                                        </div>
                                    </div>
                                    @if(!in_array('1', toggleHideField()))
                                    <div class="col-sm-6 col-6">
                                        <div class="modal-info-item">
                                            <span class="modal-info-label">Email Id</span>
                                            <h5 id="learner_email" class="modal-info-val">NA</h5>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Middle Card: Other Seat Info (Clean Card on Slate Background) -->
                            <div class="action-box mt-3">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="action-box-icon-badge d-inline-flex align-items-center justify-content-center">
                                            <i class="fa-solid fa-circle-info"></i>
                                        </span>
                                        <h4 class="mb-0 fw-semibold font-outfit" style="color: #18225f; font-size: 0.95rem;">Other Seat Info</h4>
                                    </div>
                                     <a href="javascript:void(0)" class="btn btn-sm rounded-pill px-3 py-1 font-outfit fw-semibold shadow-none header-edit-plan-btn" id="headerEditPlanBtn" style="display:none;">
                                         <i class="fa-solid fa-pen-to-square me-1"></i> Edit Plan
                                     </a>
                                </div>
                                <div class="row g-2">
                                    {{-- Combined 1: Subscription (Plan Type + Plan Name) --}}
                                    <div class="col-sm-6 col-12">
                                        <div class="modal-info-item">
                                            <span class="modal-info-label">Subscription</span>
                                            <h5 class="modal-info-val" id="subscriptionDisplay">—</h5>
                                            <span id="planName" style="display:none;"></span>
                                            <span id="planTypeName" style="display:none;"></span>
                                        </div>
                                    </div>

                                    {{-- Combined 2: Plan Duration (Start Date to End Date) --}}
                                    <div class="col-sm-6 col-12">
                                        <div class="modal-info-item">
                                            <span class="modal-info-label">Plan Duration</span>
                                            <h5 class="modal-info-val" id="planDurationDisplay">—</h5>
                                            <span id="startOn" style="display:none;"></span>
                                            <span id="endOn" style="display:none;"></span>
                                        </div>
                                    </div>

                                    {{-- Combined 3: Plan Price & Mode --}}
                                    <div class="col-sm-6 col-6">
                                        <div class="modal-info-item">
                                            <span class="modal-info-label">Plan Price & Mode</span>
                                            <h5 class="modal-info-val" id="planPriceModeDisplay">—</h5>
                                            <span id="price" style="display:none;"></span>
                                            <span id="paymentmode" style="display:none;"></span>
                                        </div>
                                    </div>

                                    {{-- 4: Seat Timings --}}
                                    <div class="col-sm-6 col-6">
                                        <div class="modal-info-item">
                                            <span class="modal-info-label">Seat Timings</span>
                                            <h5 id="planTiming" class="modal-info-val">—</h5>
                                        </div>
                                    </div>

                                    {{-- Hidden compatibility elements --}}
                                    <span id="joinOn" style="display:none;"></span>
                                    <span id="proof" style="display:none;"></span>
                                </div>
                                
                                <!-- Status Badge Container -->
                                <div class="seat-modal-status-wrapper w-100 text-center mt-3 pt-3">
                                    <div id="extendday" class="d-inline-flex align-items-center justify-content-center text-center"></div>
                                </div>
                            </div>

                            <!-- Operations Quick Action Ribbon with Left/Right Scroll Navigation -->
                            <div class="modal-op-scroll-wrapper position-relative w-100 mt-3">
                                <button type="button" class="btn btn-sm op-scroll-arrow-btn position-absolute start-0 top-50 translate-middle-y" 
                                        id="opScrollLeftBtn" title="Scroll Left" aria-label="Previous actions">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </button>

                                <div class="modal-op-items d-flex align-items-start gap-2 flex-nowrap w-100" id="modalOpContainer" style="scroll-behavior: smooth; white-space: nowrap;">
                                    <div class="py-3 text-center text-muted small w-100" id="modalOpLoadingPlaceholder">
                                        <i class="fa-solid fa-spinner fa-spin me-1"></i> Loading actions...
                                    </div>
                                </div>

                                <button type="button" class="btn btn-sm op-scroll-arrow-btn position-absolute end-0 top-50 translate-middle-y" 
                                        id="opScrollRightBtn" title="Scroll Right" aria-label="Next actions">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>

                        @can('has-permission', 'Renew Seat')
                        <div class="row justify-content-center mt-3">
                            <div class="col-lg-6">
                                <input type="hidden" value="" id="user_id">
                                <input type="hidden" value="" id="learner_detail_id">

                                <a id="upgrade" class="btn btn-primary btn-block button w-100 py-2" style="height : auto;">Renew Library Membership</a>
                            </div>
                        </div>
                        @else
                        <span class="text-danger text-center d-block mt-2">You don't have Permission to Renew Student.</span>
                        @endcan
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endcan



<script>
    $(document).ready(function() {
        // Seat Legend Horizontal Scroll Navigation
        var $legendContainer = $('#seatLegendContainer');
        $('#scrollLeftBtn').on('click', function() {
            $legendContainer.animate({ scrollLeft: '-=150px' }, 200);
        });
        $('#scrollRightBtn').on('click', function() {
            $legendContainer.animate({ scrollLeft: '+=150px' }, 200);
        });
        $legendContainer.on('wheel', function(e) {
            if (e.originalEvent.deltaY !== 0) {
                e.preventDefault();
                this.scrollLeft += e.originalEvent.deltaY;
            }
        });

        // Modal Operations Mouse Wheel Scroll
        var $opContainer = $('#modalOpContainer');
        $opContainer.on('wheel', function(e) {
            if (e.originalEvent.deltaY !== 0) {
                e.preventDefault();
                this.scrollLeft += e.originalEvent.deltaY;
            }
        });

        // Seat Card Shift Navigation (< > buttons and shift dots)
        $(document).on('click', '.shift-next-btn, .shift-prev-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $card = $(this).closest('.seat-card-item');
            var $slides = $card.find('.shift-slide-item');
            var totalSlides = $slides.length;
            var currentIdx = parseInt($slides.filter('.active-slide').attr('data-shift-idx')) || 0;
            
            var nextIdx;
            if ($(this).hasClass('shift-next-btn')) {
                nextIdx = (currentIdx + 1) % totalSlides;
            } else {
                nextIdx = (currentIdx - 1 + totalSlides) % totalSlides;
            }
            
            $slides.addClass('d-none').removeClass('active-slide');
            $slides.filter('[data-shift-idx="' + nextIdx + '"]').removeClass('d-none').addClass('active-slide');
            
            $card.find('.current-shift-num').text(nextIdx + 1);

            var $dots = $card.find('.shift-dot');
            $dots.css({'opacity': '0.35', 'transform': 'scale(1)'}).removeClass('active-dot');
            $dots.filter('[data-shift-idx="' + nextIdx + '"]').css({'opacity': '1', 'transform': 'scale(1.3)'}).addClass('active-dot');
        });

        $(document).on('click', '.shift-dot', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var targetIdx = parseInt($(this).attr('data-shift-idx'));
            var $card = $(this).closest('.seat-card-item');
            var $slides = $card.find('.shift-slide-item');
            
            $slides.addClass('d-none').removeClass('active-slide');
            $slides.filter('[data-shift-idx="' + targetIdx + '"]').removeClass('d-none').addClass('active-slide');
            
            $card.find('.current-shift-num').text(targetIdx + 1);

            var $dots = $card.find('.shift-dot');
            $dots.css({'opacity': '0.35', 'transform': 'scale(1)'}).removeClass('active-dot');
            $(this).css({'opacity': '1', 'transform': 'scale(1.3)'}).addClass('active-dot');
        });

        // Check if the animation has already been run in the current session
        if (!sessionStorage.getItem('seatsAnimated')) {
            // Animate each seat one by one
            $('.seat').each(function(index) {
                $(this).delay(index * 200).queue(function(next) {
                    $(this).css({
                        'opacity': '1',
                        'transform': 'translateY(0)'
                    });
                    next(); // Move to the next item in the queue
                });
            });

            // After all animations complete, set the sessionStorage flag
            setTimeout(function() {
                sessionStorage.setItem('seatsAnimated', 'true');
            }, $('.seat').length * 200 + 500); // Wait for all seats to animate
        } else {
            // If the animation has already run, make all seats visible immediately
            $('.seat').css({
                'opacity': '1',
                'transform': 'translateY(0)',
                'transition': 'none' // Disable the transition so they don't animate again
            });
        }
    });
</script>

{{-- <script>
    document.getElementById('plan_start_date').addEventListener('change', function() {
    
        const startDate = new Date(this.value);
        console.log("stat_date",startDate);
        if (startDate) {
            // Add 30 days to the start date
            const endDate = new Date(startDate);
            endDate.setDate(endDate.getDate() + 30);

            // Format the date to yyyy-mm-dd for the input field
            const formattedDate = endDate.toISOString().split('T')[0];

            // Set the calculated end date in the input field
            document.getElementById('plan_end_date').value = formattedDate;
        }
    });
</script> --}}
<script>
    $(document).ready(function() {
        let currentStatusFilter = 'all';

        function filterSeatCards() {
            let query = ($('#seatSearchInput').val() || '').trim().toLowerCase();
            let shiftQuery = ($('#seatShiftFilterSelect').val() || '').toLowerCase();
            let visibleCount = 0;
            let totalSeats = $('.seat-card-item').length;

            $('.seat-card-item').each(function() {
                let $card = $(this);
                let seatNo = String($card.attr('data-seat-no') || '').toLowerCase();
                let studentNames = String($card.attr('data-student-names') || '').toLowerCase();
                let shifts = String($card.attr('data-shifts') || '').toLowerCase();

                let hasAvailable = $card.attr('data-has-available') === '1';
                let hasBooked = $card.attr('data-has-booked') === '1';
                let hasDue = $card.attr('data-has-due') === '1';
                let hasExpiring = $card.attr('data-has-expiring') === '1';
                let hasExtended = $card.attr('data-has-extended') === '1';
                let hasFuture = $card.attr('data-has-future') === '1';
                let hasNonExpired = $card.attr('data-has-non-expired') === '1';

                // Check text search
                let matchText = true;
                if (query) {
                    let formattedSeat = 'seat ' + seatNo;
                    let formattedPaddedSeat = 'seat ' + (seatNo.length === 1 ? '0' + seatNo : seatNo);
                    matchText = seatNo.includes(query) || 
                                formattedSeat.includes(query) || 
                                formattedPaddedSeat.includes(query) || 
                                studentNames.includes(query);
                }

                // Check shift filter
                let matchShift = true;
                if (shiftQuery) {
                    matchShift = shifts.includes(shiftQuery);
                }

                // Check status filter
                let matchStatus = true;
                if (currentStatusFilter === 'available') {
                    matchStatus = hasAvailable;
                } else if (currentStatusFilter === 'booked') {
                    matchStatus = hasBooked;
                } else if (currentStatusFilter === 'due') {
                    matchStatus = hasDue;
                } else if (currentStatusFilter === 'expiring') {
                    matchStatus = hasExpiring;
                } else if (currentStatusFilter === 'extended') {
                    matchStatus = hasExtended;
                } else if (currentStatusFilter === 'future') {
                    matchStatus = hasFuture;
                } else if (currentStatusFilter === 'non_expired') {
                    matchStatus = hasNonExpired;
                }

                if (matchText && matchShift && matchStatus) {
                    $card.removeClass('d-none');
                    visibleCount++;

                    // If specific shift filter is selected, auto switch slide on card to matching shift
                    if (shiftQuery) {
                        $card.find('.shift-slide-item').each(function() {
                            let label = $(this).find('.shift-info').text().toLowerCase();
                            if (label.includes(shiftQuery)) {
                                let idx = $(this).data('shift-idx');
                                $card.find('.shift-slide-item').addClass('d-none').removeClass('active-slide');
                                $(this).removeClass('d-none').addClass('active-slide');
                                $card.find('.shift-dot').css({'opacity': '0.35', 'transform': 'scale(1)'}).removeClass('active-dot');
                                $card.find('.shift-dot[data-shift-idx="' + idx + '"]').css({'opacity': '1', 'transform': 'scale(1.3)'}).addClass('active-dot');
                            }
                        });
                    } else if (currentStatusFilter !== 'all') {
                        // If specific status filter is selected, auto switch slide on card to matching shift
                        $card.find('.shift-slide-item').each(function() {
                            let isMatch = false;
                            if (currentStatusFilter === 'available' && $(this).find('.avatar-circle-available').length > 0) isMatch = true;
                            if (currentStatusFilter === 'future' && $(this).find('.avatar-circle-future').length > 0) isMatch = true;
                            if (currentStatusFilter === 'due' && $(this).find('.due-dot, .seat-due-amount-pill').length > 0) isMatch = true;
                            if (currentStatusFilter === 'extended' && $(this).find('.extension-dot').length > 0) isMatch = true;
                            if (currentStatusFilter === 'expiring' && $(this).find('.seat-avatar-dot.seatBlink:not(.due-dot):not(.extension-dot)').length > 0) isMatch = true;

                            if (isMatch) {
                                let idx = $(this).data('shift-idx');
                                $card.find('.shift-slide-item').addClass('d-none').removeClass('active-slide');
                                $(this).removeClass('d-none').addClass('active-slide');
                                $card.find('.shift-dot').css({'opacity': '0.35', 'transform': 'scale(1)'}).removeClass('active-dot');
                                $card.find('.shift-dot[data-shift-idx="' + idx + '"]').css({'opacity': '1', 'transform': 'scale(1.3)'}).addClass('active-dot');
                            }
                        });
                    }
                } else {
                    $card.addClass('d-none');
                }
            });

            // Hide empty floor collapsible cards
            $('.floor-collapsible-card').each(function() {
                let visibleSeatsInFloor = $(this).find('.seat-card-item:not(.d-none)').length;
                if (visibleSeatsInFloor === 0) {
                    $(this).addClass('d-none');
                } else {
                    $(this).removeClass('d-none');
                }
            });

            updateFilterIndicator();
        }

        // Toggle Filter Panel on Icon Click (Mobile-First)
        $('#toggleFilterPanelBtn').on('click', function(e) {
            e.preventDefault();
            $('#seatFilterCollapse').slideToggle(200);
            $(this).toggleClass('active');
        });

        // Function to update active filter indicator dot
        function updateFilterIndicator() {
            var shiftVal = $('#seatShiftFilterSelect').val() || '';
            var hasFilter = (shiftVal !== '') || (currentStatusFilter !== 'all');
            if (hasFilter) {
                $('#activeFilterBadge').removeClass('d-none');
                $('#toggleFilterPanelBtn').addClass('active');
            } else {
                $('#activeFilterBadge').addClass('d-none');
                if (!$('#seatFilterCollapse').is(':visible')) {
                    $('#toggleFilterPanelBtn').removeClass('active');
                }
            }
        }

        // Live Search Input & Shift Select Events
        $('#seatSearchInput').on('keyup input', filterSeatCards);
        $('#seatShiftFilterSelect').on('change', filterSeatCards);

        // Status Filter Pill Buttons Click
        $(document).on('click', '.seat-status-filter-btn', function() {
            $('.seat-status-filter-btn').removeClass('active');
            $(this).addClass('active');

            currentStatusFilter = $(this).data('filter');
            filterSeatCards();
        });

        // Reset Filters Button
        $('#resetSeatFilterBtn').on('click', function() {
            $('#seatSearchInput').val('');
            $('#seatShiftFilterSelect').val('');
            currentStatusFilter = 'all';
            $('.seat-status-filter-btn').removeClass('active');
            $('.seat-status-filter-btn[data-filter="all"]').addClass('active');
            filterSeatCards();
        });

        // Profile photo lightbox preview modal
        $(document).on('click', 'a.view-image', function (e) {
            var imageUrl = $(this).attr('href');
            if (!imageUrl || !imageUrl.match(/\.(jpg|jpeg|png|webp)(\?|#|$)/i)) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            openProfileImageModal(imageUrl);
        });

        function openProfileImageModal(imageUrl) {
            var $m = $('#imageViewModal');
            if (!$m.length) return;
            $('#modalImage').attr('src', imageUrl);
            $m.attr('aria-hidden', 'false');
            $m.css({ display: 'flex', opacity: 0 }).animate({ opacity: 1 }, 200);
        }

        function closeProfileImageModal() {
            var $m = $('#imageViewModal');
            if (!$m.length) return;
            $m.animate({ opacity: 0 }, 150, function () {
                $m.css({ display: 'none', opacity: 1 });
                $m.attr('aria-hidden', 'true');
                $('#modalImage').attr('src', '');
            });
        }

        $(document).on('click', '#imageViewModal .close-modal', function (e) {
            e.stopPropagation();
            closeProfileImageModal();
        });

        $(document).on('click', '#imageViewModal', function (e) {
            if ($(e.target).is('#imageViewModal') || $(e.target).hasClass('image-modal-content')) {
                closeProfileImageModal();
            }
        });

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && $('#imageViewModal').css('display') === 'flex') {
                closeProfileImageModal();
            }
        });

        // Density View Mode Switching (Card Grid vs Dense Matrix View)
        // Card-Level Tap / Click Delegation (Mobile & Desktop UX friendly: tap anywhere on card)
        $(document).on('click', '.seat-card-item', function(e) {
            if ($(e.target).closest('button, a, .shift-prev-btn, .shift-next-btn, .shift-dot, .view-image').length) {
                return;
            }
            var $activeSlide = $(this).find('.shift-slide-item.active-slide');
            if (!$activeSlide.length) {
                $activeSlide = $(this).find('.shift-slide-item:first');
            }
            if ($activeSlide.length) {
                var $btn = $activeSlide.find('.second_popup, .first_popup, .second_popup_without_seat');
                if ($btn.length) {
                    $btn[0].click();
                }
            } else {
                var $btn = $(this).find('.second_popup_without_seat, .second_popup, .first_popup');
                if ($btn.length) {
                    $btn[0].click();
                }
            }
        });
    });
</script>

<div id="imageViewModal" class="image-modal" style="display:none;opacity:0;" aria-hidden="true">
    <div class="image-modal-content">
        <span class="close-modal" title="Close">&times;</span>
        <img id="modalImage" src="" alt="Full view">
    </div>
</div>

 <!-- End .library-seat-module -->

@endsection