@extends('layouts.library')

@section('title', 'Admin Dashboard (V2)')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

@php
use App\Helpers\HelperService;
$totalSeatsCount = function_exists('totalSeat') ? totalSeat() : 0;
$currentYear = date('Y');
$currentMonth = date('m');
$completion = getProfileCompletionPercentage();
$alertClass = $completion < 50 ? 'alert-danger' : 'alert-warning';
$urgentActionsTotal = count($renewSeats) + ($pendingDueCount ?? 0) + (isset($qrbookings) ? count($qrbookings) : 0);
@endphp

<!-- Dedicated Modern Stylesheet -->
<link rel="stylesheet" href="{{ asset('public/css/dashboard-v2.css') }}?v={{ time() }}">

<!-- Branch Profile Alert if incomplete -->
@if ($completion < 70)
<div class="alert {{ $alertClass }} alert-dismissible fade show d-flex align-items-center p-3 rounded-3 shadow-sm mb-3" role="alert">
    <i class="fa-solid fa-triangle-exclamation me-3 fs-5 {{ $alertClass == 'alert-danger' ? 'text-danger' : 'text-warning' }}"></i>
    <div class="flex-grow-1">
        <strong>Branch Profile is only {{ $completion }}% complete.</strong> Kindly complete your details to unlock all features.
        @if(getCurrentBranch())
            <a href="{{ route('branch.edit', getCurrentBranch()) }}" class="ms-2 text-decoration-underline fw-bold">
                <i class="fas fa-edit me-1"></i>Update Profile
            </a>
        @endif
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="libraro-dashboard-v2">

    <!-- ====================================================================
         Zone 1: Executive Top Header & Mode Switcher
         ==================================================================== -->
    <div class="v2-header-bar">
        <div>
            <div class="v2-greeting-title">
                @if($festival)
                    <i class="fas fa-gift text-success"></i>
                    <span>Happy {{ $festival->festival_name }}!</span>
                @else
                    <i class="fas fa-sun text-warning" id="greeting-icon"></i>
                    <span id="greeting-message">Good Morning! Library Owner</span>
                @endif
                <span class="v2-badge-pill v2-badge-v2 ms-2">V2 DASHBOARD</span>
            </div>
            <p class="v2-greeting-subtitle">
                <i class="fa-solid fa-code-branch me-1 text-teal"></i>
                <strong>{{ getCurrentBranchName() ?? 'Main Branch' }}</strong> &bull; {{ date('l, d M Y') }}
            </p>
        </div>

        <div class="d-flex align-items-center flex-wrap gap-2">
            <a href="{{ route('library.home') }}" class="v2-mode-switcher-btn" title="Switch to the Classic Dashboard layout">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Switch to Classic View</span>
            </a>
            <a href="{{ route('library.how-to-use') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <i class="fa-solid fa-circle-question me-1"></i> Guide
            </a>
        </div>
    </div>

    <!-- ====================================================================
         Zone 2: Operational Quick Launchpad (Most frequent owner tasks)
         ==================================================================== -->
    <div class="v2-launchpad-card">
        <div class="v2-launchpad-row">
            @can('has-permission', 'Book Seat')
            <a href="javascript:;" class="v2-launch-btn btn-primary-action noseat_popup">
                <i class="fa-solid fa-chair"></i>
                <span>Allot / Book Seat</span>
            </a>
            @endcan

            @can('has-permission', 'Search Learner')
            <a href="{{ route('learner.search') }}" class="v2-launch-btn">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span>Search Seat / Learner</span>
            </a>
            @endcan

            @can('has-permission', 'Add Daily Expense')
            <a href="{{ route('add.expense.list') }}" class="v2-launch-btn">
                <i class="fa-solid fa-plus-circle text-danger"></i>
                <span>Add Daily Expense</span>
            </a>
            @endcan

            @can('has-permission', 'Genrate ID Card')
            <a href="{{ route('learner.checklist') }}" class="v2-launch-btn">
                <i class="fa-solid fa-id-card text-teal"></i>
                <span>Print Bulk ID Cards</span>
            </a>
            @endcan

            @can('has-permission', 'QR Seat Booking')
            @if($branch?->uuid && $branch?->upi_id)
            <a href="javascript:;" data-bs-toggle="modal" data-bs-target="#branchQR" class="v2-launch-btn">
                <i class="fa-solid fa-qrcode text-indigo"></i>
                <span>Desk QR Standee</span>
            </a>
            @endif
            @endcan

            <a href="{{ route('activities.all') }}" class="v2-launch-btn">
                <i class="fa-solid fa-timeline text-muted"></i>
                <span>Audit Trail</span>
            </a>
        </div>
    </div>

    <!-- ====================================================================
         Zone 3: Top KPI Command Ribbon (4 Key Pillars)
         ==================================================================== -->
    <div class="row g-3 mb-4">
        <!-- Pillar 1: Live Seat Occupancy -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="v2-kpi-card kpi-navy">
                <div>
                    <div class="v2-kpi-header">
                        <span class="v2-kpi-label">Seat Occupancy</span>
                        <div class="v2-kpi-icon"><i class="fa-solid fa-chair"></i></div>
                    </div>
                    <div class="v2-kpi-val">
                        <span id="booked_seat">0</span> <span style="font-size: 1.1rem; color: #94a3b8; font-weight: 500;">/ <span id="total_seat">{{ $totalSeatsCount }}</span></span>
                    </div>
                    <div class="v2-mini-progress">
                        <div class="v2-mini-progress-bar" id="occupancyProgressBar" style="width: 0%;"></div>
                    </div>
                </div>
                <div class="v2-kpi-subtext d-flex justify-content-between align-items-center">
                    <span><strong id="available_seat" class="text-success">0</strong> Free Vacancies</span>
                    <a href="{{ route('seats') }}" class="text-primary text-decoration-none fw-bold" style="font-size: 11.5px;">View Matrix &rarr;</a>
                </div>
            </div>
        </div>

        <!-- Pillar 2: Flexible General Bookings -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="v2-kpi-card kpi-teal">
                <div>
                    <div class="v2-kpi-header">
                        <span class="v2-kpi-label">General Seats (Flexible)</span>
                        <div class="v2-kpi-icon"><i class="fa-solid fa-users"></i></div>
                    </div>
                    <div class="v2-kpi-val" id="gen-totalBookings">0</div>
                    <div class="d-flex align-items-center gap-2 my-1" style="font-size: 11.5px;">
                        <span class="badge bg-success-subtle text-success">Active: <strong id="gen-active-seat">0</strong></span>
                        <span class="badge bg-warning-subtle text-warning">Expiring: <strong id="gen-aboutToExpire">0</strong></span>
                        <span class="badge bg-danger-subtle text-danger">Expired: <strong id="gen-expired">0</strong></span>
                    </div>
                </div>
                <p class="v2-kpi-subtext">
                    <i class="fa-solid fa-arrow-trend-up text-success"></i> Extra revenue booster
                </p>
            </div>
        </div>

        <!-- Pillar 3: Today's Net Cashflow -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="v2-kpi-card kpi-green">
                <div>
                    <div class="v2-kpi-header">
                        <span class="v2-kpi-label">Today's Net Balance</span>
                        <div class="v2-kpi-icon"><i class="fa-solid fa-wallet"></i></div>
                    </div>
                    <div class="v2-kpi-val text-success">
                        ₹<span id="todayNetVal">{{ (int)$todayBalance == $todayBalance ? (int)$todayBalance : $todayBalance }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-1" style="font-size: 11.5px;">
                        <span class="text-success fw-bold">+₹{{ (int)$todayCollection }} in</span>
                        <span class="text-muted">&bull;</span>
                        <span class="text-danger fw-bold">-₹{{ (int)$todayExpense }} out</span>
                    </div>
                </div>
                <div class="v2-kpi-subtext d-flex justify-content-between align-items-center">
                    <span>Refunds: ₹{{ (int)$today_refund }}</span>
                    <a href="{{ route('library.transaction') }}" class="text-success text-decoration-none fw-bold" style="font-size: 11.5px;">Transactions &rarr;</a>
                </div>
            </div>
        </div>

        <!-- Pillar 4: Urgent Action Items -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="v2-kpi-card {{ $urgentActionsTotal > 0 ? 'kpi-red' : 'kpi-navy' }}">
                <div>
                    <div class="v2-kpi-header">
                        <span class="v2-kpi-label">Urgent Action Items</span>
                        <div class="v2-kpi-icon"><i class="fa-solid fa-bell"></i></div>
                    </div>
                    <div class="v2-kpi-val {{ $urgentActionsTotal > 0 ? 'text-danger' : 'text-primary' }}">
                        {{ $urgentActionsTotal }}
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-1 mb-1" style="font-size: 11px;">
                        <span class="badge bg-warning-subtle text-warning">{{ count($renewSeats) }} Expiring</span>
                        <span class="badge bg-danger-subtle text-danger">{{ $pendingDueCount ?? 0 }} Dues</span>
                        @if(isset($qrbookings) && count($qrbookings) > 0)
                        <span class="badge bg-info-subtle text-info">{{ count($qrbookings) }} QR Requests</span>
                        @endif
                    </div>
                </div>
                <p class="v2-kpi-subtext">
                    <i class="fa-solid fa-circle-exclamation text-danger"></i> Needs attention today
                </p>
            </div>
        </div>
    </div>

    <!-- ====================================================================
         Zone 4: Dynamic Reporting Filter Bar (Year & Month Selector)
         ==================================================================== -->
    <div class="v2-dynamic-filter-bar">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-chart-pie text-teal"></i>
            <span class="fw-bold" style="font-size: 13.5px; color: #18225f;">Filter Reporting Periods:</span>
        </div>

        <div class="d-flex align-items-center gap-2">
            <select id="datayaer" class="v2-filter-select" title="Filter by Year">
                <option value="">Select Year</option>
                @foreach($months as $year => $monthData)
                <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>
                    {{ $year }}
                </option>
                @endforeach
            </select>

            <select id="dataFilter" class="v2-filter-select" title="Filter by Month">
                <option value="">Select Month</option>
                @if(isset($months[$currentYear]))
                @foreach($months[$currentYear] as $monthNumber => $monthName)
                <option value="{{ $monthNumber }}" {{ $monthNumber == $currentMonth ? 'selected' : '' }}>
                    {{ $monthName }}
                </option>
                @endforeach
                @endif
            </select>
        </div>
    </div>

    <!-- ====================================================================
         Zone 5: Urgent Action Hub (Split Grid: Renewals Due & Payment Dues)
         ==================================================================== -->
    <div class="row g-4 mb-4">
        <!-- Column 1: Renewals Due in Next 5 Days -->
        <div class="col-lg-6 col-12">
            <div class="v2-section-card">
                <div class="v2-section-header">
                    <h5 class="v2-section-title">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <span>Seats Expiring in Next 5 Days</span>
                    </h5>
                    <span class="badge bg-warning text-dark fw-bold rounded-pill px-2 py-1" style="font-size: 11px;">
                        {{ count($renewSeats) }} Due
                    </span>
                </div>

                @if(!$renewSeats->isEmpty())
                <ul class="v2-action-list">
                    @foreach($renewSeats as $value)
                    @php
                        $valPlanEndDate = data_get($value, 'plan_end_date');
                        $valSeatNo = data_get($value, 'seat_no');
                        $valSeatId = data_get($value, 'seat_id');
                        $valUserId = data_get($value, 'learner_id');
                        $valDetailId = data_get($value, 'id');
                        $valName = data_get($value, 'name', 'Learner');
                        $valMobile = data_get($value, 'mobile');
                        $planTypeName = is_object($value) ? optional($value->planType)->name : data_get($value, 'plan_type', 'Slot');
                        $daysLeft = $valPlanEndDate ? \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($valPlanEndDate), false) : 0;
                        $shortSeat = $valSeatNo ? getSeatDisplayShortFloor($valSeatNo) : 'GEN';
                        $cleanRenewMobile = preg_replace('/[^0-9]/', '', (string)$valMobile);
                    @endphp
                    <li class="v2-action-item">
                        <div class="v2-action-meta">
                            <div class="v2-action-name">{{ $valName }}</div>
                            <div class="v2-action-tags">
                                <span class="v2-pill-tag seat">Seat {{ $shortSeat }} ({{ $planTypeName }})</span>
                                <span class="v2-pill-tag expiry">
                                    @if($daysLeft <= 0)
                                        Expires Today
                                    @else
                                        {{ $daysLeft }} day{{ $daysLeft > 1 ? 's' : '' }} left
                                    @endif
                                </span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <!-- Direct WhatsApp Reminder -->
                            @if(!empty($cleanRenewMobile))
                            <a target="_blank"
                               class="v2-btn-whatsapp"
                               href="https://wa.me/{{ str_starts_with($cleanRenewMobile, '91') ? $cleanRenewMobile : '91' . $cleanRenewMobile }}?text={{ rawurlencode("Dear {$valName} (Seat No: {$valSeatNo}),\n\nYour library subscription at " . getCurrentBranchName() . " is ending on " . ($valPlanEndDate ? changeFormate($valPlanEndDate) : '') . ".\n\nPlease renew promptly to preserve your seat.\n\nThank you,\n" . getCurrentBranchName()) }}"
                               title="Send WhatsApp Reminder">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            @endif

                            @can('has-permission', 'Renew Seat')
                            <a href="javascript:;" 
                               class="v2-btn-action-small primary renew_extend" 
                               data-seat_no="{{ $valSeatNo }}" 
                               data-seat_id="{{ $valSeatId }}" 
                               data-user="{{ $valUserId }}" 
                               data-end_date="{{ $valPlanEndDate }}" 
                               data-learner_detail="{{ $valDetailId }}"
                               title="Renew Learner Seat">
                                Renew
                            </a>
                            @endcan
                        </div>
                    </li>
                    @endforeach
                </ul>
                @else
                <div class="v2-empty-state-card">
                    <div class="v2-empty-state-icon"><i class="fa-solid fa-circle-check"></i></div>
                    <h6 class="fw-bold mb-1" style="font-size: 13.5px; color: #18225f;">No Upcoming Renewals</h6>
                    <p class="mb-0" style="font-size: 12px;">All active member plans have comfortable validity remaining.</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Column 2: Payment Due Members & QR Requests -->
        <div class="col-lg-6 col-12">
            <div class="v2-section-card">
                <div class="v2-section-header">
                    <h5 class="v2-section-title">
                        <i class="fa-solid fa-receipt text-danger"></i>
                        <span>Payment Due Members</span>
                    </h5>
                    <span class="badge bg-danger text-white fw-bold rounded-pill px-2 py-1" style="font-size: 11px;">
                        {{ $pendingDueCount ?? 0 }} Overdue
                    </span>
                </div>

                @if(!empty($pendingDueMembers) && count($pendingDueMembers) > 0)
                <ul class="v2-action-list">
                    @foreach($pendingDueMembers->take(6) as $due)
                    @php
                        $dueId = data_get($due, 'id');
                        $dueName = data_get($due, 'name', 'Learner');
                        $dueMobile = data_get($due, 'mobile', '');
                        $dueSeatNo = data_get($due, 'seat_no', 'GEN');
                        $pendingAmt = (float) (data_get($due, 'payment.pending_amount') ?? data_get($due, 'pending_amount', 0));
                        $dueDate = data_get($due, 'payment.due_date') ?? data_get($due, 'due_date');
                        $cleanMobile = preg_replace('/[^0-9]/', '', (string)$dueMobile);
                    @endphp
                    <li class="v2-action-item">
                        <div class="v2-action-meta">
                            <div class="v2-action-name">{{ $dueName }}</div>
                            <div class="v2-action-tags">
                                <span class="v2-pill-tag seat">Seat {{ $dueSeatNo }}</span>
                                <span class="v2-pill-tag due">Due: ₹{{ number_format($pendingAmt, 0) }}</span>
                                @if(!empty($dueMobile))
                                <span>{{ $dueMobile }}</span>
                                @endif
                                @if(!empty($dueDate))
                                <span>&bull; Due: {{ date('d M', strtotime($dueDate)) }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <!-- WhatsApp Payment Ping -->
                            @if(!empty($cleanMobile))
                            <a target="_blank"
                               class="v2-btn-whatsapp"
                               href="https://wa.me/{{ str_starts_with($cleanMobile, '91') ? $cleanMobile : '91' . $cleanMobile }}?text={{ rawurlencode("Hello {$dueName},\n\nThis is a friendly reminder that you have a pending payment of ₹" . number_format($pendingAmt, 0) . " at " . getCurrentBranchName() . ".\n\nPlease clear the dues at your earliest convenience.\n\nThank you!") }}"
                               title="Send WhatsApp Payment Reminder">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            @endif

                            @if(!empty($dueId))
                            <a href="{{ route('learner.pending.payment', ['id' => $dueId]) }}" class="v2-btn-action-small primary" title="Collect pending due payment">
                                Pay Due
                            </a>
                            @endif
                        </div>
                    </li>
                    @endforeach
                </ul>
                <div class="text-center mt-3 pt-2 border-top">
                    <a href="{{ route('learner.pending.payment.list', ['filter' => 'pending']) }}" class="text-primary fw-bold text-decoration-none" style="font-size: 12px;">
                        View All {{ $pendingDueCount ?? 0 }} Pending Dues &rarr;
                    </a>
                </div>
                @else
                <div class="v2-empty-state-card">
                    <div class="v2-empty-state-icon"><i class="fa-solid fa-thumbs-up"></i></div>
                    <h6 class="fw-bold mb-1" style="font-size: 13.5px; color: #18225f;">No Outstanding Dues</h6>
                    <p class="mb-0" style="font-size: 12px;">All learner payments are up-to-date.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- ====================================================================
         Zone 6: Live Shift & Slot Vacancy Matrix
         ==================================================================== -->
    <div class="v2-section-card mb-4">
        <div class="v2-section-header">
            <div>
                <h5 class="v2-section-title">
                    <i class="fa-solid fa-layer-group text-teal"></i>
                    <span>Shift & Slot Availability Matrix</span>
                </h5>
                <small class="text-muted" style="font-size: 12px;">Real-time vacancy count per slot for instant walk-in seat allotment</small>
            </div>
            <a href="{{ route('seats') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                <i class="fa-solid fa-table-cells me-1"></i> Full Seat Grid
            </a>
        </div>

        <div class="v2-shift-grid">
            @php
            // Extract shift vacancies dynamically
            $shiftSummary = [
                ['name' => 'Full Day (FD)', 'code' => 'FD', 'badge' => '24h / Full'],
                ['name' => 'First Half (FH)', 'code' => 'FH', 'badge' => 'Morning Slot'],
                ['name' => 'Second Half (SH)', 'code' => 'SH', 'badge' => 'Evening Slot'],
                ['name' => 'Full Night (FN)', 'code' => 'FN', 'badge' => 'Night Shift'],
            ];
            @endphp

            @foreach($shiftSummary as $shift)
            <div class="v2-shift-card">
                <div>
                    <div class="v2-shift-header">
                        <h6 class="v2-shift-name">{{ $shift['name'] }}</h6>
                        <span class="v2-shift-badge">{{ $shift['badge'] }}</span>
                    </div>
                    <div class="v2-shift-vacant-num">
                        <span class="shift-vacant-dynamic">Available</span>
                    </div>
                    <p class="v2-shift-vacant-label">Numbered & Shared Slots</p>
                </div>
                <div class="pt-2 border-top mt-2">
                    <a href="{{ route('seats') }}" class="text-primary fw-bold text-decoration-none" style="font-size: 12px;">
                        Check Seats &rarr;
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- ====================================================================
         Zone 7: Financial Intelligence (Today's Cashflow vs Monthly Run-Rate)
         ==================================================================== -->
    <div class="row g-4 mb-4">
        <!-- Today's Snapshot -->
        <div class="col-lg-6 col-12">
            <div class="v2-section-card">
                <div class="v2-section-header">
                    <h5 class="v2-section-title">
                        <i class="fa-solid fa-money-bill-transfer text-success"></i>
                        <span>Today's Cash Flow Breakdown</span>
                    </h5>
                    <span class="v2-badge-pill" style="background: #ecfdf5; color: #059669;">Real-time</span>
                </div>

                <div class="v2-finance-grid">
                    <div class="v2-finance-tile">
                        <div class="v2-finance-tile-title">Collection</div>
                        <div class="v2-finance-tile-val green">₹{{ (int)$todayCollection }}</div>
                    </div>

                    <div class="v2-finance-tile">
                        <div class="v2-finance-tile-title">Other Income</div>
                        <div class="v2-finance-tile-val green">₹{{ (int)$today_other_amt }}</div>
                    </div>

                    <div class="v2-finance-tile">
                        <div class="v2-finance-tile-title">Expense</div>
                        <div class="v2-finance-tile-val red">₹{{ (int)$todayExpense }}</div>
                    </div>

                    <div class="v2-finance-tile">
                        <div class="v2-finance-tile-title">Refund</div>
                        <div class="v2-finance-tile-val red">₹{{ (int)$today_refund }}</div>
                    </div>

                    <div class="v2-finance-tile">
                        <div class="v2-finance-tile-title">Pending Payment</div>
                        <div class="v2-finance-tile-val amber">₹{{ (int)$today_pending }}</div>
                    </div>

                    <div class="v2-finance-tile">
                        <div class="v2-finance-tile-title">Net Balance</div>
                        <div class="v2-finance-tile-val" style="color: #18225f;">₹{{ (int)$todayBalance }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Financial Overview (Dynamic) -->
        <div class="col-lg-6 col-12">
            <div class="v2-section-card">
                <div class="v2-section-header">
                    <h5 class="v2-section-title">
                        <i class="fa-solid fa-calendar-check text-primary"></i>
                        <span>Monthly Financial Overview</span>
                    </h5>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1" style="font-size: 11px;">Filtered Period</span>
                </div>

                <div class="v2-finance-grid">
                    <div class="v2-finance-tile">
                        <div class="v2-finance-tile-title">Total Collection</div>
                        <div class="v2-finance-tile-val green">₹<span id="total_income">0</span></div>
                    </div>

                    <div class="v2-finance-tile">
                        <div class="v2-finance-tile-title">Other Collection</div>
                        <div class="v2-finance-tile-val green">₹<span id="other_total_income">0</span></div>
                    </div>

                    <div class="v2-finance-tile">
                        <div class="v2-finance-tile-title">Total Expense</div>
                        <div class="v2-finance-tile-val red">₹<span id="total_expense">0</span></div>
                    </div>

                    <div class="v2-finance-tile">
                        <div class="v2-finance-tile-title">Total Refund</div>
                        <div class="v2-finance-tile-val red">₹<span id="total_refund">0</span></div>
                    </div>

                    <div class="v2-finance-tile">
                        <div class="v2-finance-tile-title">Total Pending</div>
                        <div class="v2-finance-tile-val amber">₹<span id="total_pending">0</span></div>
                    </div>

                    <div class="v2-finance-tile">
                        <div class="v2-finance-tile-title">Final Balance</div>
                        <div class="v2-finance-tile-val" style="color: #18225f;">₹<span id="total_balance">0</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ====================================================================
         Zone 8: Operational Activity Summary Counters
         ==================================================================== -->
    <div class="v2-section-card mb-4">
        <div class="v2-section-header">
            <h5 class="v2-section-title">
                <i class="fa-solid fa-chart-column text-teal"></i>
                <span>Library Operational Activity Counters</span>
            </h5>
            <small class="text-muted" style="font-size: 12px;">Aggregated counts for the selected reporting month</small>
        </div>

        <div class="row g-3">
            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="p-2 border rounded-3 bg-light text-center">
                    <small class="text-muted d-block fw-bold" style="font-size: 11px;">ONLINE PAID</small>
                    <h5 class="fw-bold mb-0 text-primary" id="onlinePaid">0</h5>
                </div>
            </div>

            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="p-2 border rounded-3 bg-light text-center">
                    <small class="text-muted d-block fw-bold" style="font-size: 11px;">OFFLINE PAID</small>
                    <h5 class="fw-bold mb-0 text-primary" id="offlinePaid">0</h5>
                </div>
            </div>

            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="p-2 border rounded-3 bg-light text-center">
                    <small class="text-muted d-block fw-bold" style="font-size: 11px;">OTHER PAID</small>
                    <h5 class="fw-bold mb-0 text-primary" id="otherPaid">0</h5>
                </div>
            </div>

            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="p-2 border rounded-3 bg-light text-center">
                    <small class="text-muted d-block fw-bold" style="font-size: 11px;">SEAT SWAPPED</small>
                    <h5 class="fw-bold mb-0 text-primary" id="swap_seat">0</h5>
                </div>
            </div>

            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="p-2 border rounded-3 bg-light text-center">
                    <small class="text-muted d-block fw-bold" style="font-size: 11px;">PLAN UPGRADED</small>
                    <h5 class="fw-bold mb-0 text-primary" id="learnerUpgrade">0</h5>
                </div>
            </div>

            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="p-2 border rounded-3 bg-light text-center">
                    <small class="text-muted d-block fw-bold" style="font-size: 11px;">PLAN CHANGED</small>
                    <h5 class="fw-bold mb-0 text-primary" id="change_plan_seat">0</h5>
                </div>
            </div>

            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="p-2 border rounded-3 bg-light text-center">
                    <small class="text-muted d-block fw-bold" style="font-size: 11px;">REACTIVE SEAT</small>
                    <h5 class="fw-bold mb-0 text-primary" id="reactive">0</h5>
                </div>
            </div>

            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="p-2 border rounded-3 bg-light text-center">
                    <small class="text-muted d-block fw-bold" style="font-size: 11px;">RENEWED SEATS</small>
                    <h5 class="fw-bold mb-0 text-primary" id="renew_seat">0</h5>
                </div>
            </div>

            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="p-2 border rounded-3 bg-light text-center">
                    <small class="text-muted d-block fw-bold" style="font-size: 11px;">CLOSED SEATS</small>
                    <h5 class="fw-bold mb-0 text-primary" id="close_seat">0</h5>
                </div>
            </div>

            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="p-2 border rounded-3 bg-light text-center">
                    <small class="text-muted d-block fw-bold" style="font-size: 11px;">DELETED SEATS</small>
                    <h5 class="fw-bold mb-0 text-primary" id="delete_seat">0</h5>
                </div>
            </div>

            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="p-2 border rounded-3 bg-light text-center">
                    <small class="text-muted d-block fw-bold" style="font-size: 11px;">EXTENDED SEATS</small>
                    <h5 class="fw-bold mb-0 text-primary" id="extended_seats">0</h5>
                </div>
            </div>

            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="p-2 border rounded-3 bg-light text-center">
                    <small class="text-muted d-block fw-bold" style="font-size: 11px;">5-DAY EXPIRING</small>
                    <h5 class="fw-bold mb-0 text-primary" id="expiredInFive">0</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- ====================================================================
         Zone 9: Live Activity Stream & Audit Trail
         ==================================================================== -->
    <div class="v2-section-card">
        <div class="v2-section-header">
            <div>
                <h5 class="v2-section-title">
                    <i class="fa-solid fa-clock-rotate-left text-teal"></i>
                    <span>Live Activity & Audit Stream</span>
                </h5>
                <small class="text-muted" style="font-size: 12px;">Real-time timeline of what just happened in your library</small>
            </div>
            <a href="{{ route('activities.all') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> View Full Audit Log
            </a>
        </div>

        @if(!empty($recent_activitys) && count($recent_activitys) > 0)
        <div class="d-flex flex-direction-column flex-column gap-2">
            @foreach(collect($recent_activitys)->take(8) as $act)
            @php
                $meta = HelperService::activityMeta($act->operation ?? '');
                $color = $meta['color_code'] ?? '#18225f';
            @endphp
            <div class="v2-feed-item">
                <div class="v2-feed-dot" style="background-color: {{ $color }};"></div>
                <div class="v2-feed-content">
                    <span class="badge me-1" style="background-color: {{ $color }}18; color: {{ $color }}; font-size: 10px;">
                        {{ $meta['label'] ?? 'Activity' }}
                    </span>
                    <span>{!! $act->message ?? '' !!}</span>
                </div>
                <div class="v2-feed-time">
                    {{ \Carbon\Carbon::parse($act->created_at)->diffForHumans() }}
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="v2-empty-state-card">
            <div class="v2-empty-state-icon"><i class="fa-solid fa-clock"></i></div>
            <h6 class="fw-bold mb-1" style="font-size: 13.5px; color: #18225f;">No Recent Activities Recorded</h6>
            <p class="mb-0" style="font-size: 12px;">Activities like renewals, seat swaps, and bookings will appear here in real-time.</p>
        </div>
        @endif
    </div>

</div>

<!-- Hidden Elements for dynamic AJAX compatibility -->
<div style="display:none;">
    <h2 id="expired_seat">0</h2>
    <h4 id="totalBookings">0</h4>
    <h4 id="active_booking">0</h4>
    <h4 id="expiredSeats">0</h4>
    <h4 id="thismonth_total_book">0</h4>
    <h4 id="month_total_active_book">0</h4>
    <h4 id="month_all_expired">0</h4>
    <div id="no-data"></div>
    <div id="no-data2"></div>
    <div id="no-data3"></div>
    <canvas id="revenueChart"></canvas>
    <canvas id="bookingCountChart"></canvas>
    <div class="row g-4 planwisecount"></div>
</div>

<!-- Scripts for AJAX data loading and filters -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const yearDropdown = document.getElementById('datayaer');
        const monthDropdown = document.getElementById('dataFilter');
        const monthsData = @json($months);

        yearDropdown.addEventListener('change', function() {
            const selectedYear = this.value;
            monthDropdown.innerHTML = '<option value="">Select Month</option>';

            if (selectedYear && monthsData[selectedYear]) {
                Object.entries(monthsData[selectedYear]).forEach(([monthNumber, monthName]) => {
                    const option = document.createElement('option');
                    option.value = monthNumber;
                    option.textContent = monthName;
                    monthDropdown.appendChild(option);
                });

                if (selectedYear == @json($currentYear)) {
                    monthDropdown.value = @json($currentMonth);
                }
                monthDropdown.disabled = false;
            } else {
                monthDropdown.disabled = true;
            }

            fetchDashboardV2Data(selectedYear, monthDropdown.value);
        });

        monthDropdown.addEventListener('change', function() {
            fetchDashboardV2Data(yearDropdown.value, this.value);
        });

        // Initial Data Fetch
        fetchDashboardV2Data(yearDropdown.value, monthDropdown.value);

        function fetchDashboardV2Data(year, month) {
            $.ajax({
                url: '{{ route("dashboard.data.get") }}',
                method: 'POST',
                data: {
                    year: year,
                    month: month,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.highlights) {
                        const h = response.highlights;

                        // Numbers
                        $('#total_seat').text(h.total_seat || '0');
                        $('#booked_seat').text(h.booked_seat || '0');
                        $('#available_seat').text(h.available_seat || '0');
                        $('#expired_seat').text(h.expired_seat || '0');

                        // Calculate Occupancy Progress Bar
                        const total = parseInt(h.total_seat) || 0;
                        const booked = parseInt(h.booked_seat) || 0;
                        const pct = total > 0 ? Math.min(100, Math.round((booked / total) * 100)) : 0;
                        $('#occupancyProgressBar').css('width', pct + '%');

                        // General Seats
                        $('#gen-totalBookings').text(h['gen-totalBookings'] || '0');
                        $('#gen-active-seat').text(h['gen-active-seat'] || '0');
                        $('#gen-aboutToExpire').text(h['gen-aboutToExpire'] || '0');
                        $('#gen-expired').text(h['gen-expired'] || '0');

                        // Financial Monthly Highlights
                        $('#total_income').text(h.total_income || '0');
                        $('#other_total_income').text(h.other_total_income || '0');
                        $('#total_expense').text(h.total_expense || '0');
                        $('#total_refund').text(h.total_refund || '0');
                        $('#total_pending').text(h.total_pending || '0');
                        $('#total_balance').text(h.total_balance || '0');

                        // Operations Summary
                        $('#onlinePaid').text(h.onlinePaid || '0');
                        $('#offlinePaid').text(h.offlinePaid || '0');
                        $('#otherPaid').text(h.otherPaid || '0');
                        $('#swap_seat').text(h.swap_seat || '0');
                        $('#learnerUpgrade').text(h.learnerUpgrade || '0');
                        $('#reactive').text(h.reactive || '0');
                        $('#renew_seat').text(h.renew_seat || '0');
                        $('#close_seat').text(h.close_seat || '0');
                        $('#delete_seat').text(h.delete_seat || '0');
                        $('#change_plan_seat').text(h.change_plan_seat || '0');
                        $('#extended_seats').text(h.extended_seats || '0');
                        $('#expiredInFive').text(h.expiredInFive || '0');
                    }
                },
                error: function(xhr) {
                    console.error("Dashboard V2 data fetch error:", xhr);
                }
            });
        }

        // QR Code Downloader
        window.downloadBranchQR = function() {
            let svgElement = document.querySelector("#branchQR svg");
            if (!svgElement) return;
            let svgData = new XMLSerializer().serializeToString(svgElement);

            let canvas = document.createElement("canvas");
            let ctx = canvas.getContext("2d");

            let img = new Image();
            img.onload = function() {
                canvas.width = 650;
                canvas.height = 650;
                ctx.drawImage(img, 0, 0, 650, 650);

                let pngFile = canvas.toDataURL("image/png");
                let downloadLink = document.createElement("a");
                downloadLink.download = "branch-qr.png";
                downloadLink.href = pngFile;
                downloadLink.click();
            };
            img.src = "data:image/svg+xml;base64," + btoa(unescape(encodeURIComponent(svgData)));
        };

        // Booking deletion with SweetAlert confirmation
        $(document).on('click', '.delete-booking', function(e) {
            e.preventDefault();
            let bookingId = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "This booking will be permanently deleted!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('booking') }}/" + bookingId,
                        type: "DELETE",
                        data: {
                            _token: "{{ csrf_token() }}",
                        },
                        success: function(response) {
                            Swal.fire('Deleted!', 'Booking has been deleted.', 'success').then(() => {
                                location.reload();
                            });
                        },
                        error: function() {
                            Swal.fire('Error!', 'Something went wrong. Please try again.', 'error');
                        }
                    });
                }
            });
        });

        // Booking approval confirmation
        $(document).on('submit', '.approve-form', function(e) {
            e.preventDefault();
            let form = this;
            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to approve this booking.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Approve it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endsection
