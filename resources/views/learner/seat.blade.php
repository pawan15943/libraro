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

<link rel="stylesheet" href="{{ asset('public/css/seat-module.css') }}">

<div class="library-seat-module">
@if(getCurrentBranch() !=0 )

<div class="row mb-4">
<!-- Professional Seat & Shift Search Filter Control Panel -->
<div class="col-lg-12 mt-3 mb-4">
    <div class="seat-search-filter-panel">
        
        <!-- Top Row: Search Input (Left) & Shift Select Filter (Right) -->
        <div class="row g-3 align-items-center mb-3 pb-3 border-bottom" style="border-bottom-color: #f1f5f9 !important;">
            <div class="col-md-7 col-lg-8">
                <div class="seat-search-input-group d-flex align-items-center">
                    <i class="fa-solid fa-magnifying-glass me-2" style="color: #18225f; font-size: 0.9rem;"></i>
                    <input type="text" id="seatSearchInput" class="form-control font-outfit text-dark p-1" 
                           placeholder="Search by Seat No (e.g. 05, 25) or Student Name...">
                </div>
            </div>

            <div class="col-md-5 col-lg-4">
                <div class="d-flex align-items-center justify-content-md-end gap-2">
                    <label for="seatShiftFilterSelect" class="form-label mb-0 fw-bold font-outfit text-nowrap small" style="color: #18225f; font-size: 0.84rem;">
                        <i class="fa-solid fa-clock me-1" style="color: #18225f;"></i> Shift:
                    </label>
                    <select id="seatShiftFilterSelect" class="form-select font-outfit seat-shift-select" style="max-width: 220px;">
                        <option value="">All Shifts</option>
                        @foreach($allBranchPlanTypes as $pt)
                        <option value="{{ strtolower($pt->name) }}">{{ $pt->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Bottom Row: Status Filter Pills (Left) & Match Counter + Reset (Right) -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            
            <!-- Status Filter Pills -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="small fw-bold font-outfit me-1" style="color: #18225f; font-size: 0.8rem;">Status:</span>
                
                <button type="button" class="btn seat-status-filter-btn active" data-filter="all">
                    All
                </button>
                <button type="button" class="btn seat-status-filter-btn" data-filter="booked">
                    <span class="filter-dot d-inline-block rounded-circle me-1.5" style="width: 8px; height: 8px; background-color: #34939F;"></span> Booked
                </button>
                <button type="button" class="btn seat-status-filter-btn" data-filter="available">
                    <span class="filter-dot d-inline-block rounded-circle me-1.5" style="width: 8px; height: 8px; background-color: #22c55e;"></span> Available
                </button>
                <button type="button" class="btn seat-status-filter-btn" data-filter="expiring">
                    <span class="filter-dot d-inline-block rounded-circle me-1.5" style="width: 8px; height: 8px; background-color: #d97706;"></span> About to expire
                </button>
                <button type="button" class="btn seat-status-filter-btn" data-filter="extended">
                    <span class="filter-dot d-inline-block rounded-circle me-1.5" style="width: 8px; height: 8px; background-color: #800000;"></span> Extended
                </button>
                <button type="button" class="btn seat-status-filter-btn" data-filter="future">
                    <span class="filter-dot d-inline-block rounded-circle me-1.5" style="width: 8px; height: 8px; background-color: #c09600;"></span> Future booked
                </button>
                <button type="button" class="btn seat-status-filter-btn" data-filter="non_expired">
                    <span class="filter-dot d-inline-block rounded-circle me-1.5" style="width: 8px; height: 8px; background-color: #c8009d;"></span> Non Expired
                </button>
                <button type="button" class="btn seat-status-filter-btn" data-filter="due">
                    <span class="filter-dot d-inline-block rounded-circle me-1.5" style="width: 8px; height: 8px; background-color: #ef4444;"></span> Pending Fee
                </button>
            </div>

            <!-- Right: Reset Button -->
            <div class="d-flex align-items-center gap-2 ms-auto">
                <button type="button" id="resetSeatFilterBtn" class="btn btn-sm btn-light border rounded-pill px-3 py-1.5 font-outfit fw-bold shadow-none" title="Reset Filters" style="font-size: 0.78rem; background: #f8fafc; color: #64748b; border-color: #cbd5e1;">
                    <i class="fa-solid fa-rotate-right me-1"></i> Reset
                </button>
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
    <div class="col-lg-12">
        <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pills-home-tab" data-bs-toggle="pill" data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home" aria-selected="true">Library Seats</button>
            </li>
            @can('has-permission', 'General Seat Booking')
            @if(!in_array('12', toggleHideField()))
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pills-profile-tab" data-bs-toggle="pill" data-bs-target="#pills-profile" type="button" role="tab" aria-controls="pills-profile" aria-selected="false">General Seats</button>
            </li>
            @endif
            @endcan
        </ul>
        <div class="tab-content" id="pills-tabContent">
@php
    $currentBranchId = getCurrentBranch();

    // 1. Batch pre-fetch all active learners for current branch grouped by seat_no
    $activeLearnersBySeat = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
        ->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
        ->where('learners.branch_id', $currentBranchId)
        ->where('learners.status', 1)
        ->where('learner_detail.status', 1)
        ->select(
            'learners.id',
            'learners.name',
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
        ->select('learner_detail.*', 'learners.name as learner_name')
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

                                <div class="floor-collapsible-card mb-4 overflow-hidden" style="border: 1px solid #e2e8f0 !important; border-radius: 1rem !important; background: transparent !important;">
                                    <div class="floor-header-bar p-3 d-flex align-items-center justify-content-between cursor-pointer" 
                                         data-bs-toggle="collapse" 
                                         data-bs-target="#floorCollapse_{{ $floorId }}" 
                                         aria-expanded="true" 
                                         aria-controls="floorCollapse_{{ $floorId }}"
                                         style="background: transparent; color: #18225f; cursor: pointer; user-select: none; border-bottom: 1px solid #e2e8f0;">
                                        
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-layer-group fs-5" style="color: #18225f;"></i>
                                            <h5 class="mb-0 font-outfit fw-bold text-uppercase tracking-wide" style="font-size: 1.05rem; color: #18225f;">{{ $floor->name }}</h5>
                                            <span class="badge rounded-pill ms-2 font-outfit small fw-bold" style="background-color: #f1f5f9; color: #18225f; border: 1px solid #cbd5e1;">
                                                Seats {{ $startSeat }} - {{ $endSeat }}
                                            </span>
                                        </div>

                                        <div class="d-flex align-items-center gap-2">
                                            <span class="small font-outfit text-muted d-none d-md-inline" style="font-size: 0.8rem;">Click to Collapse / Expand</span>
                                            <i class="fa-solid fa-chevron-down collapse-icon ms-1" style="font-size: 0.95rem; color: #18225f; transition: transform 0.3s ease;"></i>
                                        </div>
                                    </div>

                                    <div class="collapse show p-3" id="floorCollapse_{{ $floorId }}">
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
                                                'day_type_label' => 'From ' . \Carbon\Carbon::parse($futureUser->plan_start_date)->format('d/m/Y'),
                                                'has_due' => false,
                                                'is_non_expiry' => false,
                                                'is_extended' => false,
                                                'is_future' => true,
                                                'is_expiring' => false,
                                            ];
                                        }

                                        $is24HoursBooked = (
                                            in_array(1, $bookedDayTypeIds) ||
                                            in_array(8, $bookedDayTypeIds) ||
                                            in_array(10, $bookedDayTypeIds) ||
                                            in_array(11, $bookedDayTypeIds)
                                        );

                                        if (!$is24HoursBooked) {
                                            if ($allBranchPlanTypes->count() > 0) {
                                                $hasAnyDaytimeBooked = (
                                                    in_array(1, $bookedDayTypeIds) ||
                                                    in_array(2, $bookedDayTypeIds) ||
                                                    in_array(3, $bookedDayTypeIds) ||
                                                    in_array(4, $bookedDayTypeIds) ||
                                                    in_array(5, $bookedDayTypeIds) ||
                                                    in_array(6, $bookedDayTypeIds) ||
                                                    in_array(7, $bookedDayTypeIds) ||
                                                    in_array(10, $bookedDayTypeIds) ||
                                                    in_array(11, $bookedDayTypeIds)
                                                );

                                                $hasFullDayOrVipBooked = (
                                                    in_array(1, $bookedDayTypeIds) ||
                                                    in_array(10, $bookedDayTypeIds) ||
                                                    in_array(11, $bookedDayTypeIds)
                                                );

                                                foreach ($allBranchPlanTypes as $pt) {
                                                    // 1. If this exact day_type_id is already booked, skip it
                                                    if ($pt->day_type_id != 0 && in_array($pt->day_type_id, $bookedDayTypeIds)) {
                                                        continue;
                                                    }
                                                    // 2. If Full Day (1), Reserved (10), or VIP (11) is booked, skip all remaining shifts
                                                    if ($hasFullDayOrVipBooked) {
                                                        continue;
                                                    }
                                                    // 3. If Full Night (9) is booked, skip 1, 8, 9, 10, 11
                                                    if (in_array(9, $bookedDayTypeIds) && in_array($pt->day_type_id, [1, 8, 9, 10, 11])) {
                                                        continue;
                                                    }
                                                    // 4. If any daytime shift is booked, skip Full Day (1), All Day (8), Reserved (10), and VIP (11)
                                                    if (in_array($pt->day_type_id, [1, 8, 10, 11]) && $hasAnyDaytimeBooked) {
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
                                                if (!in_array(9, $bookedDayTypeIds) && !$hasFullDayOrVipBooked) {
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
                                    @endphp

                                    <div class="seat-card-item p-3 bg-white border position-relative d-flex flex-column align-items-center justify-content-between text-center shadow-sm" 
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
                                         style="border-radius: 18px !important; border: 1px solid #e2e8f0; background: #ffffff;">
                                        
                                        <!-- Top Bar: Seat Badge -->
                                        <div class="d-flex align-items-center justify-content-center w-100 mb-2">
                                            <span class="badge rounded-pill px-2.5 py-1 font-outfit fw-bold d-inline-flex align-items-center" 
                                                  style="background-color: #eff6ff; color: #18225f; border: 1px solid #dbeafe; font-size: 0.78rem;">
                                                Seat {{ sprintf('%02d', $seatNo) }}
                                            </span>
                                        </div>

                                        <!-- Shift Slides Container -->
                                        <div class="shift-slides-wrapper w-100 position-relative my-2">
                                            @foreach($seatShifts as $sIdx => $shift)
                                            @php
                                                $isExt = (isset($shift['class']) && ($shift['class'] === 'extedned' || $shift['class'] === 'extended'));
                                                $hasDue = !empty($shift['has_due']);
                                            @endphp
                                            <div class="shift-slide-item {{ $sIdx === 0 ? 'active-slide' : 'd-none' }}" data-shift-idx="{{ $sIdx }}">
                                                

                                                <!-- Avatar Area -->
                                                <div class="avatar-container position-relative d-inline-block mx-auto mb-2">
                                                    @if($shift['type'] === 'available')
                                                    <div class="avatar-circle-available d-flex align-items-center justify-content-center mx-auto position-relative" 
                                                         style="width: 60px; height: 60px; border-radius: 50%; border: 2.5px solid #22c55e; background-color: #f0fdf4; color: #22c55e;">
                                                        <i class="fa-solid fa-chair fs-4" style="color: #22c55e;"></i>
                                                        <span class="position-absolute bottom-0 end-0 bg-success border border-white rounded-circle" style="width: 12px; height: 12px; margin-bottom: 2px; margin-right: 2px;"></span>
                                                    </div>
                                                    @elseif($shift['type'] === 'future')
                                                    <div class="avatar-circle-future d-flex align-items-center justify-content-center mx-auto text-white font-outfit fw-bold fs-5 shadow-sm position-relative" 
                                                         style="width: 60px; height: 60px; border-radius: 50%; background-color: #c09600;">
                                                        FUT
                                                        <span class="position-absolute bottom-0 end-0 bg-warning border border-white rounded-circle" style="width: 12px; height: 12px; margin-bottom: 2px; margin-right: 2px;"></span>
                                                    </div>
                                                    @else
                                                    @php
                                                        $initials = strtoupper(substr($shift['name'], 0, 2));
                                                        $avatarBg = $hasDue ? '#ef4444' : ($isExt ? '#800000' : ($shift['is_non_expiry'] ? '#c8009d' : '#18225f'));
                                                        $avatarDotClass = $hasDue ? 'seat-avatar-dot due-dot seatBlink' : ($isExt ? 'seat-avatar-dot extension-dot seatBlink' : 'seat-avatar-dot active-dot');
                                                        $avatarTooltip = $hasDue ? 'Fee Overdue' : ($isExt ? 'Extension Active' : 'Active Booking');
                                                        $hasPhoto = !empty($shift['profile_picture']);
                                                    @endphp
                                                    @if($hasPhoto)
                                                    <div class="avatar-circle-booked d-flex align-items-center justify-content-center mx-auto text-white font-outfit fw-bold fs-5 shadow-sm position-relative overflow-hidden" 
                                                         style="width: 60px; height: 60px; border-radius: 50%; background-color: {{ $avatarBg }};">
                                                        <a href="{{ asset($shift['profile_picture']) }}" class="view-image w-100 h-100 d-block" title="View {{ $shift['name'] }} photo">
                                                            <img src="{{ asset($shift['profile_picture']) }}" alt="{{ $shift['name'] }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                                        </a>
                                                    </div>
                                                    @else
                                                    <div class="avatar-circle-booked d-flex align-items-center justify-content-center mx-auto text-white font-outfit fw-bold fs-5 shadow-sm position-relative" 
                                                         style="width: 60px; height: 60px; border-radius: 50%; background-color: {{ $avatarBg }};">
                                                        {{ $initials }}
                                                    </div>
                                                    @endif
                                                     <span class="{{ $avatarDotClass }}" data-bs-toggle="tooltip" title="{{ $avatarTooltip }}"></span>
                                                     @if($hasDue && !empty($shift['due_amount']) && $shift['due_amount'] > 0)
                                                     <span class="seat-due-amount-pill shadow-sm" data-bs-toggle="tooltip" title="Due Amount: ₹{{ number_format($shift['due_amount']) }}">₹{{ number_format($shift['due_amount']) }}</span>
                                                     @endif
                                                    @endif
                                                </div>

                                                <!-- Middle Navigation Row: Fixed Left Arrow | Shift Info | Fixed Right Arrow -->
                                                <div class="position-relative w-100 my-1 d-flex align-items-center justify-content-center" style="min-height: 42px;">
                                                    @if(count($seatShifts) > 1)
                                                    <button type="button" class="btn btn-sm btn-light border rounded-circle p-0 shift-prev-btn shadow-none position-absolute start-0 top-50 translate-middle-y" 
                                                            data-seat="{{ $seatNo }}" 
                                                            style="width: 26px; height: 26px; min-width: 26px; z-index: 5; display: flex; align-items: center; justify-content: center; background: #ffffff; color: #64748b; border-color: #e2e8f0;">
                                                        <i class="fa-solid fa-chevron-left small" style="font-size: 0.7rem;"></i>
                                                    </button>
                                                    @endif

                                                    <div class="shift-info text-center w-100 overflow-hidden" style="padding-left: 28px; padding-right: 28px;">
                                                        @if($shift['type'] === 'available')
                                                        <div class="fw-bold text-success font-outfit text-truncate mx-auto" style="font-size: 0.88rem;">
                                                            Available
                                                        </div>
                                                        @php
                                                            $availLabel = strtoupper($shift['label'] ?? '');
                                                        @endphp
                                                        @if(strlen($availLabel) > 14)
                                                        <marquee behavior="scroll" direction="left" scrollamount="3" class="small text-muted font-outfit text-uppercase fw-bold d-block w-100" style="font-size: 0.73rem;" title="{{ $shift['label'] }}">
                                                            {{ $availLabel }}
                                                        </marquee>
                                                        @else
                                                        <div class="small text-muted font-outfit text-truncate text-uppercase fw-bold" style="font-size: 0.73rem;" title="{{ $shift['label'] }}">
                                                            {{ $availLabel }}
                                                        </div>
                                                        @endif
                                                        @elseif($shift['type'] === 'future')
                                                        <div class="fw-bold text-warning font-outfit text-truncate mx-auto" style="font-size: 0.88rem;" title="{{ $shift['name'] }}">
                                                            {{ $shift['name'] }}
                                                        </div>
                                                        @php
                                                            $futLabel = strtoupper($shift['day_type_label'] ?? '');
                                                        @endphp
                                                        @if(strlen($futLabel) > 14)
                                                        <marquee behavior="scroll" direction="left" scrollamount="3" class="small text-muted font-outfit text-uppercase fw-bold d-block w-100" style="font-size: 0.73rem;" title="{{ $shift['day_type_label'] }}">
                                                            {{ $futLabel }}
                                                        </marquee>
                                                        @else
                                                        <div class="small text-muted font-outfit text-truncate text-uppercase fw-bold" style="font-size: 0.73rem;" title="{{ $shift['day_type_label'] }}">
                                                            {{ $futLabel }}
                                                        </div>
                                                        @endif
                                                        @else
                                                        <div class="fw-bold font-outfit text-truncate mx-auto" style="font-size: 0.88rem; color: #1e293b;" title="{{ $shift['name'] }}">
                                                            {{ $shift['name'] }}
                                                        </div>
                                                        @php
                                                            $bkLabel = strtoupper($shift['day_type_label'] ?? '');
                                                            $bkIsFrozen = (int)($shift['frozen_status'] ?? 0) === 1 || !empty($shift['freeze_start_date']);
                                                            $bkColor = $bkIsFrozen ? 'text-danger' : 'text-muted';
                                                        @endphp
                                                        @if(strlen($bkLabel) > 14)
                                                        <marquee behavior="scroll" direction="left" scrollamount="3" class="small {{ $bkColor }} font-outfit text-uppercase fw-bold d-block w-100" style="font-size: 0.73rem;" title="{{ $shift['day_type_label'] }}">
                                                            {{ $bkLabel }}
                                                        </marquee>
                                                        @else
                                                        <div class="small {{ $bkColor }} font-outfit text-truncate text-uppercase fw-bold" style="font-size: 0.73rem;" title="{{ $shift['day_type_label'] }}">
                                                            {{ $bkLabel }}
                                                        </div>
                                                        @endif
                                                        @endif
                                                    </div>

                                                    @if(count($seatShifts) > 1)
                                                    <button type="button" class="btn btn-sm btn-light border rounded-circle p-0 shift-next-btn shadow-none position-absolute end-0 top-50 translate-middle-y" 
                                                            data-seat="{{ $seatNo }}" 
                                                            style="width: 26px; height: 26px; min-width: 26px; z-index: 5; display: flex; align-items: center; justify-content: center; background: #ffffff; color: #64748b; border-color: #e2e8f0;">
                                                        <i class="fa-solid fa-chevron-right small" style="font-size: 0.7rem;"></i>
                                                    </button>
                                                    @endif
                                                </div>

                                                <!-- Action Button -->
                                                <div class="w-100 text-center mt-2">
                                                    @if($shift['type'] === 'available')
                                                    <button type="button" class="btn btn-success btn-sm font-outfit fw-bold rounded-pill px-3 py-1 first_popup shadow-none" 
                                                            data-bs-toggle="modal" data-bs-target="#seatAllotmentModal" 
                                                            data-id="{{ $seatNo }}" 
                                                            data-seat_no="{{ $seatNo }}" 
                                                            data-plan_type_id="{{ $shift['plan_type_id'] ?? '' }}" 
                                                            data-day_type_id="{{ $shift['day_type_id'] ?? '' }}" 
                                                            style="background-color: #10b981; border: none; font-size: 0.78rem; height: auto !important;">
                                                        Book
                                                    </button>
                                                    @else
                                                    <button type="button" class="btn btn-primary btn-sm font-outfit fw-bold rounded-pill px-3 py-1 second_popup shadow-none" 
                                                            data-bs-toggle="modal" data-bs-target="#seatAllotmentModal2" data-seat_no="{{ $seatNo }}" data-userid="{{ $shift['user_id'] }}" 
                                                            style="background-color: #18225f; border: none; font-size: 0.78rem; height: auto !important;">
                                                        View
                                                    </button>
                                                    @endif
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>

                                        <!-- Bottom Indicator Dots Row -->
                                        <div class="w-100 pt-2 mt-2 border-top border-dashed d-flex align-items-center justify-content-center gap-1.5 shift-dots-row" style="border-top: 1px dashed #e2e8f0;">
                                            @foreach($seatShifts as $sIdx => $shift)
                                            <span class="shift-dot rounded-circle cursor-pointer transition-all {{ $sIdx === 0 ? 'active-dot' : '' }}" 
                                                  data-seat="{{ $seatNo }}" data-shift-idx="{{ $sIdx }}" 
                                                  style="width: 8px; height: 8px; background-color: {{ $shift['type'] === 'available' ? '#22c55e' : ($shift['type'] === 'future' ? '#c09600' : '#34939F') }}; opacity: {{ $sIdx === 0 ? '1' : '0.35' }}; transform: {{ $sIdx === 0 ? 'scale(1.3)' : 'scale(1)' }}; display: inline-block;"></span>
                                            @endforeach
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
                                <div class="floor-collapsible-card mb-4 overflow-hidden" style="border: 1px solid #e2e8f0 !important; border-radius: 1rem !important; background: transparent !important;">
                                    <div class="floor-header-bar p-3 d-flex align-items-center justify-content-between cursor-pointer" 
                                         data-bs-toggle="collapse" 
                                         data-bs-target="#floorCollapse_unassigned" 
                                         aria-expanded="true" 
                                         aria-controls="floorCollapse_unassigned"
                                         style="background: transparent; color: #18225f; cursor: pointer; user-select: none; border-bottom: 1px solid #e2e8f0;">
                                        
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-layer-group fs-5" style="color: #18225f;"></i>
                                            <h5 class="mb-0 font-outfit fw-bold text-uppercase tracking-wide" style="font-size: 1.05rem; color: #18225f;">Seats Without Floor</h5>
                                            <span class="badge rounded-pill ms-2 font-outfit small fw-bold" style="background-color: #f1f5f9; color: #18225f; border: 1px solid #cbd5e1;">
                                                Seats {{ $seatNo }} - {{ $total_seats }}
                                            </span>
                                        </div>

                                        <div class="d-flex align-items-center gap-2">
                                            <span class="small font-outfit text-muted d-none d-md-inline" style="font-size: 0.8rem;">Click to Collapse / Expand</span>
                                            <i class="fa-solid fa-chevron-down collapse-icon ms-1" style="font-size: 0.95rem; color: #18225f; transition: transform 0.3s ease;"></i>
                                        </div>
                                    </div>

                                    <div class="collapse show p-3" id="floorCollapse_unassigned">
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
                                                'day_type_label' => 'From ' . \Carbon\Carbon::parse($futureUser->plan_start_date)->format('d/m/Y'),
                                                'has_due' => false,
                                                'is_non_expiry' => false,
                                                'is_extended' => false,
                                                'is_future' => true,
                                                'is_expiring' => false,
                                            ];
                                        }

                                        $is24HoursBooked = (
                                            in_array(1, $bookedDayTypeIds) ||
                                            in_array(8, $bookedDayTypeIds) ||
                                            in_array(10, $bookedDayTypeIds) ||
                                            in_array(11, $bookedDayTypeIds)
                                        );

                                        if (!$is24HoursBooked) {
                                            if ($allBranchPlanTypes->count() > 0) {
                                                $hasAnyDaytimeBooked = (
                                                    in_array(1, $bookedDayTypeIds) ||
                                                    in_array(2, $bookedDayTypeIds) ||
                                                    in_array(3, $bookedDayTypeIds) ||
                                                    in_array(4, $bookedDayTypeIds) ||
                                                    in_array(5, $bookedDayTypeIds) ||
                                                    in_array(6, $bookedDayTypeIds) ||
                                                    in_array(7, $bookedDayTypeIds) ||
                                                    in_array(10, $bookedDayTypeIds) ||
                                                    in_array(11, $bookedDayTypeIds)
                                                );

                                                $hasFullDayOrVipBooked = (
                                                    in_array(1, $bookedDayTypeIds) ||
                                                    in_array(10, $bookedDayTypeIds) ||
                                                    in_array(11, $bookedDayTypeIds)
                                                );

                                                foreach ($allBranchPlanTypes as $pt) {
                                                    // 1. If this exact day_type_id is already booked, skip it
                                                    if ($pt->day_type_id != 0 && in_array($pt->day_type_id, $bookedDayTypeIds)) {
                                                        continue;
                                                    }
                                                    // 2. If Full Day (1), Reserved (10), or VIP (11) is booked, skip all remaining shifts
                                                    if ($hasFullDayOrVipBooked) {
                                                        continue;
                                                    }
                                                    // 3. If Full Night (9) is booked, skip 1, 8, 9, 10, 11
                                                    if (in_array(9, $bookedDayTypeIds) && in_array($pt->day_type_id, [1, 8, 9, 10, 11])) {
                                                        continue;
                                                    }
                                                    // 4. If any daytime shift is booked, skip Full Day (1), All Day (8), Reserved (10), and VIP (11)
                                                    if (in_array($pt->day_type_id, [1, 8, 10, 11]) && $hasAnyDaytimeBooked) {
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
                                                if (!in_array(9, $bookedDayTypeIds) && !$hasFullDayOrVipBooked) {
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
                                    @endphp

                                    <div class="seat-card-item p-3 bg-white border position-relative d-flex flex-column align-items-center justify-content-between text-center shadow-sm" 
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
                                         style="border-radius: 18px !important; border: 1px solid #e2e8f0; background: #ffffff;">
                                        
                                        <!-- Top Bar: Seat Badge -->
                                        <div class="d-flex align-items-center justify-content-center w-100 mb-2">
                                            <span class="badge rounded-pill px-2.5 py-1 font-outfit fw-bold d-inline-flex align-items-center" 
                                                  style="background-color: #eff6ff; color: #18225f; border: 1px solid #dbeafe; font-size: 0.78rem;">
                                                Seat {{ sprintf('%02d', $seatNo) }}
                                            </span>
                                        </div>

                                        <!-- Shift Slides Container -->
                                        <div class="shift-slides-wrapper w-100 position-relative my-2">
                                            @foreach($seatShifts as $sIdx => $shift)
                                            @php
                                                $isExt = (isset($shift['class']) && ($shift['class'] === 'extedned' || $shift['class'] === 'extended'));
                                                $hasDue = !empty($shift['has_due']);
                                            @endphp
                                            <div class="shift-slide-item {{ $sIdx === 0 ? 'active-slide' : 'd-none' }}" data-shift-idx="{{ $sIdx }}">
                                                

                                                <!-- Avatar Area -->
                                                <div class="avatar-container position-relative d-inline-block mx-auto mb-2">
                                                    @if($shift['type'] === 'available')
                                                    <div class="avatar-circle-available d-flex align-items-center justify-content-center mx-auto position-relative" 
                                                         style="width: 60px; height: 60px; border-radius: 50%; border: 2.5px solid #22c55e; background-color: #f0fdf4; color: #22c55e;">
                                                        <i class="fa-solid fa-chair fs-4" style="color: #22c55e;"></i>
                                                        <span class="position-absolute bottom-0 end-0 bg-success border border-white rounded-circle" style="width: 12px; height: 12px; margin-bottom: 2px; margin-right: 2px;"></span>
                                                    </div>
                                                    @elseif($shift['type'] === 'future')
                                                    <div class="avatar-circle-future d-flex align-items-center justify-content-center mx-auto text-white font-outfit fw-bold fs-5 shadow-sm position-relative" 
                                                         style="width: 60px; height: 60px; border-radius: 50%; background-color: #c09600;">
                                                        FUT
                                                        <span class="position-absolute bottom-0 end-0 bg-warning border border-white rounded-circle" style="width: 12px; height: 12px; margin-bottom: 2px; margin-right: 2px;"></span>
                                                    </div>
                                                    @else
                                                    @php
                                                        $initials = strtoupper(substr($shift['name'], 0, 2));
                                                        $avatarBg = $hasDue ? '#ef4444' : ($isExt ? '#800000' : ($shift['is_non_expiry'] ? '#c8009d' : '#18225f'));
                                                        $avatarDotClass = $hasDue ? 'seat-avatar-dot due-dot seatBlink' : ($isExt ? 'seat-avatar-dot extension-dot seatBlink' : 'seat-avatar-dot active-dot');
                                                        $avatarTooltip = $hasDue ? 'Fee Overdue' : ($isExt ? 'Extension Active' : 'Active Booking');
                                                        $hasPhoto = !empty($shift['profile_picture']);
                                                    @endphp
                                                    @if($hasPhoto)
                                                    <div class="avatar-circle-booked d-flex align-items-center justify-content-center mx-auto text-white font-outfit fw-bold fs-5 shadow-sm position-relative overflow-hidden" 
                                                         style="width: 60px; height: 60px; border-radius: 50%; background-color: {{ $avatarBg }};">
                                                        <a href="{{ asset($shift['profile_picture']) }}" class="view-image w-100 h-100 d-block" title="View {{ $shift['name'] }} photo">
                                                            <img src="{{ asset($shift['profile_picture']) }}" alt="{{ $shift['name'] }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                                        </a>
                                                    </div>
                                                    @else
                                                    <div class="avatar-circle-booked d-flex align-items-center justify-content-center mx-auto text-white font-outfit fw-bold fs-5 shadow-sm position-relative" 
                                                         style="width: 60px; height: 60px; border-radius: 50%; background-color: {{ $avatarBg }};">
                                                        {{ $initials }}
                                                    </div>
                                                    @endif
                                                     <span class="{{ $avatarDotClass }}" data-bs-toggle="tooltip" title="{{ $avatarTooltip }}"></span>
                                                     @if($hasDue && !empty($shift['due_amount']) && $shift['due_amount'] > 0)
                                                     <span class="seat-due-amount-pill shadow-sm" data-bs-toggle="tooltip" title="Due Amount: ₹{{ number_format($shift['due_amount']) }}">₹{{ number_format($shift['due_amount']) }}</span>
                                                     @endif
                                                    @endif
                                                </div>

                                                <!-- Middle Navigation Row: Fixed Left Arrow | Shift Info | Fixed Right Arrow -->
                                                <div class="position-relative w-100 my-1 d-flex align-items-center justify-content-center" style="min-height: 42px;">
                                                    @if(count($seatShifts) > 1)
                                                    <button type="button" class="btn btn-sm btn-light border rounded-circle p-0 shift-prev-btn shadow-none position-absolute start-0 top-50 translate-middle-y" 
                                                            data-seat="{{ $seatNo }}" 
                                                            style="width: 26px; height: 26px; min-width: 26px; z-index: 5; display: flex; align-items: center; justify-content: center; background: #ffffff; color: #64748b; border-color: #e2e8f0;">
                                                        <i class="fa-solid fa-chevron-left small" style="font-size: 0.7rem;"></i>
                                                    </button>
                                                    @endif

                                                    <div class="shift-info text-center w-100 overflow-hidden" style="padding-left: 28px; padding-right: 28px;">
                                                        @if($shift['type'] === 'available')
                                                        <div class="fw-bold text-success font-outfit text-truncate mx-auto" style="font-size: 0.88rem;">
                                                            Available
                                                        </div>
                                                        <div class="small text-muted font-outfit text-truncate text-uppercase fw-bold" style="font-size: 0.73rem;" title="{{ $shift['label'] }}">
                                                            {{ strtoupper($shift['label']) }}
                                                        </div>
                                                        @elseif($shift['type'] === 'future')
                                                        <div class="fw-bold text-warning font-outfit text-truncate mx-auto" style="font-size: 0.88rem;" title="{{ $shift['name'] }}">
                                                            {{ $shift['name'] }}
                                                        </div>
                                                        <div class="small text-muted font-outfit text-truncate text-uppercase fw-bold" style="font-size: 0.73rem;" title="{{ $shift['day_type_label'] }}">
                                                            {{ strtoupper($shift['day_type_label']) }}
                                                        </div>
                                                        @else
                                                        <div class="fw-bold font-outfit text-truncate mx-auto" style="font-size: 0.88rem; color: #1e293b;" title="{{ $shift['name'] }}">
                                                            {{ $shift['name'] }}
                                                        </div>
                                                        <div class="small text-muted font-outfit text-truncate text-uppercase fw-bold" style="font-size: 0.73rem;" title="{{ $shift['day_type_label'] }}">
                                                            {{ strtoupper($shift['day_type_label']) }}
                                                        </div>
                                                        @endif
                                                    </div>

                                                    @if(count($seatShifts) > 1)
                                                    <button type="button" class="btn btn-sm btn-light border rounded-circle p-0 shift-next-btn shadow-none position-absolute end-0 top-50 translate-middle-y" 
                                                            data-seat="{{ $seatNo }}" 
                                                            style="width: 26px; height: 26px; min-width: 26px; z-index: 5; display: flex; align-items: center; justify-content: center; background: #ffffff; color: #64748b; border-color: #e2e8f0;">
                                                        <i class="fa-solid fa-chevron-right small" style="font-size: 0.7rem;"></i>
                                                    </button>
                                                    @endif
                                                </div>

                                                <!-- Action Button -->
                                                <div class="w-100 text-center mt-2">
                                                    @if($shift['type'] === 'available')
                                                    <button type="button" class="btn btn-success btn-sm font-outfit fw-bold rounded-pill px-3 py-1 first_popup shadow-none" 
                                                            data-bs-toggle="modal" data-bs-target="#seatAllotmentModal" 
                                                            data-id="{{ $seatNo }}" 
                                                            data-seat_no="{{ $seatNo }}" 
                                                            data-plan_type_id="{{ $shift['plan_type_id'] ?? '' }}" 
                                                            data-day_type_id="{{ $shift['day_type_id'] ?? '' }}" 
                                                            style="background-color: #10b981; border: none; font-size: 0.78rem; height: auto !important;">
                                                        Book
                                                    </button>
                                                    @else
                                                    <button type="button" class="btn btn-primary btn-sm font-outfit fw-bold rounded-pill px-3 py-1 second_popup shadow-none" 
                                                            data-bs-toggle="modal" data-bs-target="#seatAllotmentModal2" data-seat_no="{{ $seatNo }}" data-userid="{{ $shift['user_id'] }}" 
                                                            style="background-color: #18225f; border: none; font-size: 0.78rem; height: auto !important;">
                                                        View
                                                    </button>
                                                    @endif
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>

                                        <!-- Bottom Indicator Dots Row -->
                                        <div class="w-100 pt-2 mt-2 border-top border-dashed d-flex align-items-center justify-content-center gap-1.5 shift-dots-row" style="border-top: 1px dashed #e2e8f0;">
                                            @foreach($seatShifts as $sIdx => $shift)
                                            <span class="shift-dot rounded-circle cursor-pointer transition-all {{ $sIdx === 0 ? 'active-dot' : '' }}" 
                                                  data-seat="{{ $seatNo }}" data-shift-idx="{{ $sIdx }}" 
                                                  style="width: 8px; height: 8px; background-color: {{ $shift['type'] === 'available' ? '#22c55e' : ($shift['type'] === 'future' ? '#c09600' : '#34939F') }}; opacity: {{ $sIdx === 0 ? '1' : '0.35' }}; transform: {{ $sIdx === 0 ? 'scale(1.3)' : 'scale(1)' }}; display: inline-block;"></span>
                                            @endforeach
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
                        $avatarBg = $hasDue ? '#ef4444' : ($isExt ? '#800000' : ($isNonExpiry ? '#c8009d' : '#18225f'));
                        $initials = strtoupper(substr($user->name, 0, 2));
                        $avatarDotClass = $hasDue ? 'seat-avatar-dot due-dot seatBlink' : ($isExt ? 'seat-avatar-dot extension-dot seatBlink' : 'seat-avatar-dot active-dot');
                        $avatarTooltip = $hasDue ? 'Fee Overdue' : ($isExt ? 'Extension Active' : 'Active Booking');
                        $hasPhoto = !empty($user->profile_picture);
                        @endphp

                        <div class="seat-card-item p-3 bg-white border position-relative d-flex flex-column align-items-center justify-content-between text-center shadow-sm" 
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
                             style="border-radius: 18px !important; border: 1px solid #e2e8f0; background: #ffffff;">
                            
                            

                            <!-- Top Bar: Seat Badge -->
                            <div class="d-flex align-items-center justify-content-center w-100 mb-2">
                                <span class="badge rounded-pill px-2.5 py-1 font-outfit fw-bold d-inline-flex align-items-center" 
                                      style="background-color: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; font-size: 0.78rem;">
                                    GEN #{{ sprintf('%02d', $genIdx + 1) }}
                                </span>
                            </div>

                            <!-- Avatar & Learner Info Area -->
                            <div class="avatar-container position-relative d-inline-block mx-auto mb-2">
                                @if($hasPhoto)
                                <div class="avatar-circle-booked d-flex align-items-center justify-content-center mx-auto text-white font-outfit fw-bold fs-5 shadow-sm position-relative overflow-hidden" 
                                     style="width: 60px; height: 60px; border-radius: 50%; background-color: {{ $avatarBg }};">
                                    <a href="{{ asset($user->profile_picture) }}" class="view-image w-100 h-100 d-block" title="View {{ $user->name }} photo">
                                        <img src="{{ asset($user->profile_picture) }}" alt="{{ $user->name }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                    </a>
                                </div>
                                @else
                                <div class="avatar-circle-booked d-flex align-items-center justify-content-center mx-auto text-white font-outfit fw-bold fs-5 shadow-sm position-relative" 
                                     style="width: 60px; height: 60px; border-radius: 50%; background-color: {{ $avatarBg }};">
                                    {{ $initials }}
                                </div>
                                @endif
                                 <span class="{{ $avatarDotClass }}" data-bs-toggle="tooltip" title="{{ $avatarTooltip }}"></span>
                            </div>

                            <!-- Learner Name & Plan Type Info -->
                            <div class="shift-info text-center w-100 overflow-hidden my-1">
                                <div class="fw-bold font-outfit text-truncate mx-auto" style="font-size: 0.88rem; color: #1e293b;" title="{{ $user->name }}">
                                    {{ $user->name }}
                                </div>
                                @php
                                                                    $genIsFrozen = (int)($user->frozen_status ?? 0) === 1 || !empty($user->freeze_start_date);
                                                                    $genLabelStr = $genIsFrozen 
                                                                        ? ('FREEZED ON ' . (\Carbon\Carbon::parse($user->freeze_start_date)->format('d/m/Y')))
                                                                        : strtoupper($user->plan_type_name ?? '');
                                                                    $genIsLong = strlen($genLabelStr) > 14;
                                                                    $genColorClass = $genIsFrozen ? 'text-danger' : 'text-muted';
                                                                @endphp
                                                                @if($genIsLong)
                                                                <marquee behavior="scroll" direction="left" scrollamount="3" class="small {{ $genColorClass }} font-outfit text-uppercase fw-bold d-block w-100" style="font-size: 0.73rem;" title="{{ $genLabelStr }}">
                                                                    {{ $genLabelStr }}
                                                                </marquee>
                                                                @else
                                                                <div class="small {{ $genColorClass }} font-outfit text-truncate text-uppercase fw-bold" style="font-size: 0.73rem;" title="{{ $genLabelStr }}">
                                                                    {{ $genLabelStr }}
                                                                </div>
                                                                @endif
                            </div>

                            <!-- Action Button -->
                            <div class="w-100 text-center mt-2">
                                <button type="button" class="btn btn-primary btn-sm font-outfit fw-bold rounded-pill px-3 py-1 second_popup_without_seat shadow-none" 
                                        data-bs-toggle="modal" data-bs-target="#seatAllotmentModal2" data-userid="{{ $user->id }}" 
                                        style="background-color: #18225f; border: none; font-size: 0.78rem; height: auto !important;">
                                    View Details
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
@else
<p class="info-message mt-4 mb-0">
    you dont select any branch
</p>
@endif
@can('has-permission', 'View Seat')
<div class="modal fade library-seat-module" id="seatAllotmentModal2" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="seat_details_info">Book Seat</h1>
                <span id="seat_name" style="display: none;"></span>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="actions">
                            <div class="upper-box">
                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <h4 class="mb-0">Learners Info</h4>
                                    @if(Auth::user()->can('has-permission', 'Edit Seat') || Auth::user()->can('has-permission', 'Learner Edit'))
                                     <a href="javascript:void(0)" class="btn btn-sm rounded-pill px-3 py-1 font-outfit fw-bold shadow-none header-edit-profile-btn" id="headerEditProfileBtn" style="font-size: 0.78rem; background-color: #1e293b; color: #ffffff; border: 1px solid #1e293b;">
                                         <i class="fa-solid fa-user-pen me-1"></i> Edit Profile
                                     </a>
                                    @endif
                                </div>
                                <div class="row g-4">
                                    <div class="col-lg-6 col-6">
                                        <span>Seat Owner Name</span>
                                        <h5 id="owner" class="uppercase">NA</h5>
                                    </div>
                                    @if(!in_array('2', toggleHideField()))
                                    <div class="col-lg-6 col-6">
                                        <span>Date Of Birth </span>
                                        <h5 id="learner_dob">NA</h5>
                                    </div>
                                    @endif

                                    <div class="col-lg-6 col-6">
                                        <span>Mobile Number</span>
                                        <h5 id="learner_mobile">NA</h5>
                                    </div>
                                    @if(!in_array('1', toggleHideField()))
                                    <div class="col-lg-6 col-6">
                                        <span>Email Id</span>
                                        <h5 id="learner_email">NA</h5>
                                    </div>
                                    @endif

                                    
                                </div>
                            </div>
                            <div class="action-box">
                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <h4 class="mb-0">Other Seat Info</h4>
                                     <a href="javascript:void(0)" class="btn btn-sm rounded-pill px-3 py-1 font-outfit fw-bold shadow-none header-edit-plan-btn" id="headerEditPlanBtn" style="font-size: 0.78rem; background-color: #18225f; color: #ffffff; border: 1px solid #18225f; display:none;">
                                         <i class="fa-solid fa-pen-to-square me-1"></i> Edit Plan
                                     </a>
                                </div>
                                <div class="row g-4">
                                    <div class="col-lg-4 col-6">
                                        <span>Plan</span>
                                        <h5 id="planName">NA</h5>
                                    </div>
                                    <div class="col-lg-4 col-6">
                                        <span>Plan Type</span>
                                        <h5 id="planTypeName">NA</h5>
                                    </div>
                                    <div class="col-lg-4 col-6">
                                        <span>Plan Price</span>
                                        <h5 id="price">NA</h5>
                                    </div>
                                    <div class="col-lg-4 col-6">
                                        <span>Seat Booked On</span>
                                        <h5 id="joinOn">NA</h5>
                                    </div>
                                    <div class="col-lg-4 col-6">
                                        <span>Plan Starts On</span>
                                        <h5 id="startOn">NA</h5>
                                    </div>
                                    <div class="col-lg-4 col-6">
                                        <span>Plan Ends On</span>
                                        <h5 id="endOn">NA</h5>
                                    </div>
                                    <div class="col-lg-4 col-6">
                                        <span>Payment Mode</span>
                                        <h5 id="paymentmode">NA</h5>
                                    </div>
                                    <div class="col-lg-4 col-6">
                                        <span>Id Proof</span>
                                        <h5 id="proof"><a class="">View Docuemnt</a></h5>
                                    </div>
                                    <div class="col-lg-4">
                                        <span>Seat Timings</span>
                                        <h5 id="planTiming">NA</h5>
                                    </div>
                                    <div>
                                        <h5 id="extendday" class="text-center"></h5>
                                    </div>
                                </div>

                                <!-- Single Row Circular Dark Navy Blue Operations Icons with Left/Right Scroll Arrows -->
                                <div class="modal-op-scroll-wrapper position-relative w-100 mt-3 pt-2 px-4">
                                    <button type="button" class="btn btn-sm btn-light border shadow-sm op-scroll-arrow-btn position-absolute start-0 top-50 translate-middle-y" 
                                            id="opScrollLeftBtn" title="Scroll Left" style="z-index: 10;">
                                        <i class="fa-solid fa-chevron-left"></i>
                                    </button>

                                    <div class="modal-op-items d-flex align-items-center gap-2.5 overflow-hidden flex-nowrap w-100 py-2" id="modalOpContainer" style="scroll-behavior: smooth; white-space: nowrap;">
                                        <!-- 1. Edit Profile -->
                                        <a href="javascript:void(0)" class="modal-op-item" id="modalBtnEditProfile" data-bs-toggle="tooltip" title="Edit Learner Profile">
                                            <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-user-pen"></i></div>
                                            <span class="op-icon-label">Edit Profile</span>
                                        </a>

                                        <!-- 2. Edit Plan -->
                                        <a href="javascript:void(0)" class="modal-op-item" id="modalBtnEditPlan" style="display:none;" data-bs-toggle="tooltip" title="Edit Plan Details">
                                            <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-pen-to-square"></i></div>
                                            <span class="op-icon-label">Edit Plan</span>
                                        </a>

                                        <!-- 3. Renew Plan -->
                                        <a href="javascript:void(0)" class="modal-op-item" id="modalBtnRenew" style="display:none;" data-bs-toggle="tooltip" title="Renew Plan">
                                            <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-rotate-right"></i></div>
                                            <span class="op-icon-label">Renew</span>
                                        </a>

                                        <!-- 4. WhatsApp Reminder -->
                                        <a href="javascript:void(0)" target="_blank" class="modal-op-item" id="modalBtnWhatsapp" data-bs-toggle="tooltip" title="Send WhatsApp Reminder">
                                            <div class="op-icon-circle shadow-sm"><i class="fa-brands fa-whatsapp"></i></div>
                                            <span class="op-icon-label">WhatsApp</span>
                                        </a>

                                        <!-- 5. Swap Seat -->
                                        <a href="javascript:void(0)" class="modal-op-item" id="modalBtnSwap" data-bs-toggle="tooltip" title="Swap Seat">
                                            <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-arrow-right-arrow-left"></i></div>
                                            <span class="op-icon-label">Swap</span>
                                        </a>

                                        <!-- 6. Change Plan -->
                                        <a href="javascript:void(0)" class="modal-op-item" id="modalBtnChangePlan" style="display:none;" data-bs-toggle="tooltip" title="Change Plan">
                                            <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-arrows-rotate"></i></div>
                                            <span class="op-icon-label">Change</span>
                                        </a>

                                        <!-- 7. Upgrade Plan -->
                                        <a href="javascript:void(0)" class="modal-op-item" id="modalBtnUpgradePlan" style="display:none;" data-bs-toggle="tooltip" title="Upgrade Plan">
                                            <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-circle-up"></i></div>
                                            <span class="op-icon-label">Upgrade</span>
                                        </a>

                                        <!-- 8. Gift Days -->
                                        <a href="javascript:void(0)" class="modal-op-item giftDaysBtn" id="modalBtnGift" data-bs-toggle="tooltip" title="Gift Days">
                                            <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-gift"></i></div>
                                            <span class="op-icon-label">Gift</span>
                                        </a>

                                        <!-- 9. Freeze Days -->
                                        <a href="javascript:void(0)" class="modal-op-item freezDaysBtn" id="modalBtnFreeze" data-bs-toggle="tooltip" title="Freeze Plan">
                                            <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-snowflake"></i></div>
                                            <span class="op-icon-label">Freeze</span>
                                        </a>

                                        <!-- 10. Miscellaneous Payment -->
                                        <a href="javascript:void(0)" class="modal-op-item payment-learner" id="modalBtnMiscPayment" data-bs-toggle="tooltip" title="Other / Miscellaneous Payment">
                                            <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-money-bill"></i></div>
                                            <span class="op-icon-label">Other Pay</span>
                                        </a>

                                        <!-- 11. Transactions -->
                                        <a href="javascript:void(0)" class="modal-op-item" id="modalBtnTransactions" data-bs-toggle="tooltip" title="Transactions">
                                            <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-wallet"></i></div>
                                            <span class="op-icon-label">Transactions</span>
                                        </a>

                                        <!-- 12. Settlement -->
                                        <a href="javascript:void(0)" class="modal-op-item settlement-learner" id="modalBtnSettlement" style="display:none;" data-bs-toggle="tooltip" title="Settlement">
                                            <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-scale-balanced"></i></div>
                                            <span class="op-icon-label">Settlement</span>
                                        </a>

                                        <!-- 13. ID Card -->
                                        <a href="javascript:void(0)" target="_blank" class="modal-op-item" id="modalBtnIdCard" data-bs-toggle="tooltip" title="Generate ID Card">
                                            <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-id-card-clip"></i></div>
                                            <span class="op-icon-label">ID Card</span>
                                        </a>

                                        <!-- 14. Profile -->
                                        <a href="javascript:void(0)" class="modal-op-item" id="modalBtnProfile" data-bs-toggle="tooltip" title="View Full Profile">
                                            <div class="op-icon-circle shadow-sm"><i class="fas fa-eye"></i></div>
                                            <span class="op-icon-label">Profile</span>
                                        </a>

                                        <!-- 15. Custom Expire (Commented out) -->
                                        {{-- 
                                        <a href="javascript:void(0)" class="modal-op-item" id="modalBtnExpire" data-bs-toggle="tooltip" title="Custom Seat Expire">
                                            <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-calendar-days"></i></div>
                                            <span class="op-icon-label">Expire</span>
                                        </a>
                                        --}}

                                        <!-- 16. Activity -->
                                        <a href="javascript:void(0)" class="modal-op-item" id="modalBtnActivity" data-bs-toggle="tooltip" title="Learner Activity Log">
                                            <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-clock-rotate-left"></i></div>
                                            <span class="op-icon-label">Activity</span>
                                        </a>

                                        <!-- 17. History -->
                                        <a href="javascript:void(0)" class="modal-op-item" id="modalBtnHistory" data-bs-toggle="tooltip" title="Seat & Learner History">
                                            <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-history"></i></div>
                                            <span class="op-icon-label">History</span>
                                        </a>

                                        <!-- 18. Receipt -->
                                        <a href="javascript:void(0)" class="modal-op-item" id="modalBtnReceipt" data-bs-toggle="tooltip" title="Receipt & Payment Details">
                                            <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-file-invoice"></i></div>
                                            <span class="op-icon-label">Receipt</span>
                                        </a>

                                        <!-- 19. Close Seat -->
                                        <a href="javascript:void(0)" class="modal-op-item link-close-plan close-seat" id="modalBtnCloseSeat" data-bs-toggle="tooltip" title="Close Seat">
                                            <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-circle-xmark"></i></div>
                                            <span class="op-icon-label">Close</span>
                                        </a>

                                        <!-- 20. Reactivate -->
                                        <a href="javascript:void(0)" class="modal-op-item" id="modalBtnReactive" style="display:none;" data-bs-toggle="tooltip" title="Reactivate Learner">
                                            <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-arrows-rotate"></i></div>
                                            <span class="op-icon-label">Reactivate</span>
                                        </a>

                                        <!-- 21. Delete -->
                                        <a href="javascript:void(0)" class="modal-op-item delete-customer" id="modalBtnDelete" data-bs-toggle="tooltip" title="Delete Learner">
                                            <div class="op-icon-circle shadow-sm"><i class="fas fa-trash"></i></div>
                                            <span class="op-icon-label">Delete</span>
                                        </a>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light border shadow-sm op-scroll-arrow-btn position-absolute end-0 top-50 translate-middle-y" 
                                            id="opScrollRightBtn" title="Scroll Right" style="z-index: 10;">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </button>
                                </div>
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

        // Modal Operations Horizontal Scroll Navigation (One-by-One Icon Step)
        var $opContainer = $('#modalOpContainer');
        var itemStep = 74; // 64px item width + 10px gap
        $('#opScrollLeftBtn').on('click', function() {
            $opContainer.animate({ scrollLeft: '-=' + itemStep + 'px' }, 200);
        });
        $('#opScrollRightBtn').on('click', function() {
            $opContainer.animate({ scrollLeft: '+=' + itemStep + 'px' }, 200);
        });
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
    });
</script>

<div id="imageViewModal" class="image-modal" style="display:none;opacity:0;" aria-hidden="true">
    <div class="image-modal-content">
        <span class="close-modal" title="Close">&times;</span>
        <img id="modalImage" src="" alt="Full view">
    </div>
</div>

</div> <!-- End .library-seat-module -->

@endsection