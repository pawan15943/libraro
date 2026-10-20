@extends('layouts.library')

@section('content')

{{-- Suppress legacy global overlay immediately to prioritize content-aware skeleton shimmer --}}
<style>
    #loaderone, #loader { display: none !important; opacity: 0 !important; visibility: hidden !important; pointer-events: none !important; }
</style>
<script>
    try {
        var l1 = document.getElementById('loaderone'); if (l1) l1.style.display = 'none';
        var l2 = document.getElementById('loader'); if (l2) l2.style.display = 'none';
    } catch(e) {}
</script>

@php
    use Carbon\Carbon;

    $type = request('type', $type ?? 'today_collection');
    $isDaily = str_starts_with($type, 'today_');
    $isMonthly = str_starts_with($type, 'monthly_');
    $hasCustomFilter = request()->filled('payment_type') || request()->filled('expense') || request()->filled('from') || request()->filled('to');
    $year = request('year', $year ?? date('Y'));
    $month = request('month', $month ?? date('m'));
    $monthNumber = (int)$month;
    $monthName = date('F', mktime(0, 0, 0, $monthNumber, 1));
@endphp

{{-- Scoped Stylesheet for Library Transaction View --}}
<link rel="stylesheet" href="{{ asset('public/css/library-transaction-view.css') }}?v={{ time() }}">

<div class="library-transaction-view-module">

    {{-- ==========================================================================
       CONTENT-AWARE SKELETON SHIMMER LOADER (Rendered instantly on initial load)
       ========================================================================== --}}
    <div id="txSkeletonContainer" class="tx-skeleton-container" aria-hidden="true">
        {{-- 1. Header Card Skeleton --}}
        <div class="page-header-card skeleton-card">
            <div class="header-title-wrap">
                <div class="skeleton-box" style="width: 44px; height: 44px; border-radius: 12px;"></div>
                <div>
                    <div class="skeleton-box mb-1" style="width: 220px; height: 22px; border-radius: 6px;"></div>
                    <div><div class="skeleton-box" style="width: 170px; height: 14px; border-radius: 4px;"></div></div>
                </div>
            </div>
            <div class="header-actions-group">
                <div class="skeleton-box" style="width: 155px; height: 36px; border-radius: 50rem;"></div>
                <div class="skeleton-box" style="width: 85px; height: 38px; border-radius: 10px;"></div>
            </div>
        </div>

        {{-- 2. 6 Financial KPI Metric Summary Cards Skeleton --}}
        <div class="tx-kpi-grid">
            @for($i = 0; $i < 6; $i++)
            <div class="skeleton-card" style="padding: 0.95rem 1rem; min-height: 110px; display: flex; flex-direction: column; justify-content: space-between; border-radius: 14px;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="skeleton-box" style="width: 36px; height: 36px; border-radius: 10px;"></div>
                </div>
                <div>
                    <div class="skeleton-box mb-2" style="width: 65%; height: 12px; border-radius: 4px;"></div>
                    <div class="skeleton-box mb-1" style="width: 80%; height: 22px; border-radius: 6px;"></div>
                    <div class="skeleton-box" style="width: 50%; height: 10px; border-radius: 4px;"></div>
                </div>
            </div>
            @endfor
        </div>

        {{-- 3. Records Controls Bar Skeleton --}}
        <div class="records-controls-bar skeleton-card">
            <div class="records-title-wrap">
                <div class="skeleton-box" style="width: 180px; height: 20px; border-radius: 6px;"></div>
                <div class="skeleton-box" style="width: 75px; height: 22px; border-radius: 50rem;"></div>
            </div>
            <div class="skeleton-box" style="width: 240px; height: 38px; border-radius: 50rem;"></div>
        </div>

        @if($type !== 'monthly_balance')
            {{-- Desktop Header Skeleton --}}
            <div class="tx-desktop-header" style="border: 1px solid #e2e8f0; background: #f8fafc;">
                <div><div class="skeleton-box" style="width: 60px; height: 12px;"></div></div>
                <div><div class="skeleton-box" style="width: 90px; height: 12px;"></div></div>
                <div><div class="skeleton-box" style="width: 85px; height: 12px;"></div></div>
                <div><div class="skeleton-box" style="width: 55px; height: 12px;"></div></div>
                <div><div class="skeleton-box" style="width: 45px; height: 12px;"></div></div>
                <div><div class="skeleton-box" style="width: 95px; height: 12px;"></div></div>
            </div>

            {{-- Record Cards Skeleton List --}}
            <div class="tx-records-list">
                @for($i = 0; $i < 5; $i++)
                <div class="tx-record-card skeleton-card">
                    <div class="col-tx-id">
                        <div class="skeleton-box" style="width: 32px; height: 32px; border-radius: 8px;"></div>
                        <div class="skeleton-box" style="width: 90px; height: 16px; border-radius: 4px;"></div>
                    </div>
                    <div class="col-learner-info">
                        <div class="skeleton-box" style="width: 55px; height: 20px; border-radius: 6px;"></div>
                        <div class="skeleton-box" style="width: 120px; height: 16px; border-radius: 4px;"></div>
                    </div>
                    <div class="col-payment-type">
                        <div class="skeleton-box" style="width: 90px; height: 22px; border-radius: 50rem;"></div>
                    </div>
                    <div class="col-amount">
                        <div class="skeleton-box" style="width: 75px; height: 18px; border-radius: 4px;"></div>
                    </div>
                    <div class="col-date">
                        <div class="skeleton-box" style="width: 85px; height: 14px; border-radius: 4px;"></div>
                    </div>
                    <div class="col-particular">
                        <div class="skeleton-box" style="width: 110px; height: 14px; border-radius: 4px;"></div>
                    </div>
                </div>
                @endfor
            </div>
        @else
            {{-- Monthly Balance Table Skeleton --}}
            <div class="monthly-balance-card table-responsive skeleton-card">
                <table class="monthly-balance-table">
                    <thead>
                        <tr>
                            @for($col = 0; $col < 8; $col++)
                                <th><div class="skeleton-box" style="width: 85px; height: 14px;"></div></th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @for($r = 0; $r < 5; $r++)
                        <tr>
                            @for($c = 0; $c < 8; $c++)
                                <td><div class="skeleton-box" style="width: {{ $c == 0 ? '110px' : '75px' }}; height: 16px;"></div></td>
                            @endfor
                        </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <noscript>
        <style>
            #txSkeletonContainer { display: none !important; }
            #txRealContentContainer { display: block !important; opacity: 1 !important; }
        </style>
    </noscript>

    {{-- ==========================================================================
       REAL CONTENT CONTAINER (Fades in smoothly once loaded)
       ========================================================================== --}}
    <div id="txRealContentContainer" class="tx-real-content-container" style="display: none;">

        {{-- 1. Top Header Card & Action Toolbar --}}
        <div class="page-header-card">
            <div class="header-title-wrap">
                <div class="header-icon-badge">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <div>
                    <h4 class="header-title">
                        {{ $isDaily ? "Today's Financial Snapshot" : "Monthly Financial Snapshot" }}
                    </h4>
                    <p class="header-subtitle">
                        @if($isDaily)
                            <span>Live Transaction &amp; Collection Breakdown • <strong>{{ date('d M, Y') }}</strong></span>
                        @else
                            <span><strong>{{ $monthName }} {{ $year }}</strong> Revenue &amp; Expense Summary</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="header-actions-group">
                {{-- Daily / Monthly View Switcher Pill --}}
                <div class="view-mode-pill">
                    <a href="{{ route('library.transaction.view', ['type' => 'today_collection', 'year' => $year, 'month' => $month]) }}" class="mode-btn {{ $isDaily ? 'active' : '' }}">
                        <i class="fa-solid fa-calendar-day"></i> Daily
                    </a>
                    <a href="{{ route('library.transaction.view', ['type' => 'monthly_collection', 'year' => $year, 'month' => $month]) }}" class="mode-btn {{ $isMonthly ? 'active' : '' }}">
                        <i class="fa-solid fa-calendar-days"></i> Monthly
                    </a>
                </div>

                {{-- Filter Drawer Toggle Button --}}
                @if($type !== 'monthly_balance')
                <button type="button" class="btn btn-filter-toggle {{ $hasCustomFilter ? 'active' : '' }}" id="toggleFilterBtn" title="Show/Hide Filters">
                    <i class="fa-solid fa-filter"></i>
                    <span>Filters</span>
                    @if($hasCustomFilter)
                        <span class="filter-badge-dot" title="Filter applied"></span>
                    @endif
                </button>
                @endif
            </div>
        </div>

        {{-- 2. 6 Financial KPI Metric Summary Cards --}}
        <div class="tx-kpi-grid">
            @if($isDaily)
                {{-- 1. Daily Booking Income (A) --}}
                <a href="{{ route('library.transaction.view', ['type' => 'today_collection', 'year' => $year, 'month' => $month]) }}" 
                   class="tx-kpi-card kpi-card-booking {{ $type === 'today_collection' ? 'active' : '' }}" title="View Today's Booking Income">
                    <div class="card-top-row">
                        <div class="kpi-icon-box">
                            <i class="fa-solid fa-arrow-trend-up"></i>
                        </div>
                        <span class="kpi-active-pill">Active</span>
                    </div>
                    <div>
                        <div class="kpi-label">Booking Income (A)</div>
                        <div class="kpi-value">₹{{ number_format((float)$today_booking_amt) }}</div>
                        <div class="kpi-sub">Seats, Renew &amp; Reactive</div>
                    </div>
                </a>

                {{-- 2. Daily Other Income (B) --}}
                <a href="{{ route('library.transaction.view', ['type' => 'today_other_collection', 'year' => $year, 'month' => $month]) }}" 
                   class="tx-kpi-card kpi-card-other {{ $type === 'today_other_collection' ? 'active' : '' }}" title="View Today's Other Income">
                    <div class="card-top-row">
                        <div class="kpi-icon-box">
                            <i class="fa-solid fa-coins"></i>
                        </div>
                        <span class="kpi-active-pill">Active</span>
                    </div>
                    <div>
                        <div class="kpi-label">Other Income (B)</div>
                        <div class="kpi-value">₹{{ number_format((float)$today_other_amt) }}</div>
                        <div class="kpi-sub">Token &amp; Miscellaneous</div>
                    </div>
                </a>

                {{-- 3. Daily Expenses (C) --}}
                <a href="{{ route('library.transaction.view', ['type' => 'today_expense', 'year' => $year, 'month' => $month]) }}" 
                   class="tx-kpi-card kpi-card-expense {{ $type === 'today_expense' ? 'active' : '' }}" title="View Today's Expenses">
                    <div class="card-top-row">
                        <div class="kpi-icon-box">
                            <i class="fa-solid fa-arrow-trend-down"></i>
                        </div>
                        <span class="kpi-active-pill">Active</span>
                    </div>
                    <div>
                        <div class="kpi-label">Expenses (C)</div>
                        <div class="kpi-value">₹{{ number_format((float)$today_expense) }}</div>
                        <div class="kpi-sub">Library Daily Expenses</div>
                    </div>
                </a>

                {{-- 4. Daily Refunds (D) --}}
                <a href="{{ route('library.transaction.view', ['type' => 'today_refund', 'year' => $year, 'month' => $month]) }}" 
                   class="tx-kpi-card kpi-card-refund {{ $type === 'today_refund' ? 'active' : '' }}" title="View Today's Refunds">
                    <div class="card-top-row">
                        <div class="kpi-icon-box">
                            <i class="fa-solid fa-rotate-left"></i>
                        </div>
                        <span class="kpi-active-pill">Active</span>
                    </div>
                    <div>
                        <div class="kpi-label">Refunds (D)</div>
                        <div class="kpi-value">₹{{ number_format((float)$today_refund) }}</div>
                        <div class="kpi-sub">Returned Fees</div>
                    </div>
                </a>

                {{-- 5. Daily Due / Pending (E) --}}
                <a href="{{ route('library.transaction.view', ['type' => 'today_pending', 'year' => $year, 'month' => $month]) }}" 
                   class="tx-kpi-card kpi-card-pending {{ $type === 'today_pending' ? 'active' : '' }}" title="View Today's Pending / Due">
                    <div class="card-top-row">
                        <div class="kpi-icon-box">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                        <span class="kpi-active-pill">Active</span>
                    </div>
                    <div>
                        <div class="kpi-label">Due Amt (E)</div>
                        <div class="kpi-value">₹{{ number_format((float)$today_pending) }}</div>
                        <div class="kpi-sub">Pending Receipts</div>
                    </div>
                </a>

                {{-- 6. Daily Total Revenue --}}
                <a href="{{ route('library.transaction.view', ['type' => 'today_balance', 'year' => $year, 'month' => $month]) }}" 
                   class="tx-kpi-card kpi-card-balance {{ $type === 'today_balance' ? 'active' : '' }}" title="View Today's Net Revenue">
                    <div class="card-top-row">
                        <div class="kpi-icon-box">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                        <span class="kpi-active-pill">Active</span>
                    </div>
                    <div>
                        <div class="kpi-label">Total Revenue</div>
                        <div class="kpi-value">₹{{ number_format((float)$total_revenue) }}</div>
                        <div class="kpi-sub">A + B + E - (C + D)</div>
                    </div>
                </a>
            @else
                {{-- 1. Monthly Booking Income (A) --}}
                <a href="{{ route('library.transaction.view', ['type' => 'monthly_collection', 'year' => $year, 'month' => $month]) }}" 
                   class="tx-kpi-card kpi-card-booking {{ $type === 'monthly_collection' ? 'active' : '' }}" title="View Monthly Booking Income">
                    <div class="card-top-row">
                        <div class="kpi-icon-box">
                            <i class="fa-solid fa-arrow-trend-up"></i>
                        </div>
                        <span class="kpi-active-pill">Active</span>
                    </div>
                    <div>
                        <div class="kpi-label">Monthly Booking (A)</div>
                        <div class="kpi-value">₹{{ number_format((float)$monthly_income) }}</div>
                        <div class="kpi-sub">Seats, Renew &amp; Reactive</div>
                    </div>
                </a>

                {{-- 2. Monthly Other Income (B) --}}
                <a href="{{ route('library.transaction.view', ['type' => 'monthly_other_collection', 'year' => $year, 'month' => $month]) }}" 
                   class="tx-kpi-card kpi-card-other {{ $type === 'monthly_other_collection' ? 'active' : '' }}" title="View Monthly Other Income">
                    <div class="card-top-row">
                        <div class="kpi-icon-box">
                            <i class="fa-solid fa-coins"></i>
                        </div>
                        <span class="kpi-active-pill">Active</span>
                    </div>
                    <div>
                        <div class="kpi-label">Other Income (B)</div>
                        <div class="kpi-value">₹{{ number_format((float)$other_total_income) }}</div>
                        <div class="kpi-sub">Token &amp; Miscellaneous</div>
                    </div>
                </a>

                {{-- 3. Monthly Expenses (C) --}}
                <a href="{{ route('library.transaction.view', ['type' => 'monthly_expense', 'year' => $year, 'month' => $month]) }}" 
                   class="tx-kpi-card kpi-card-expense {{ $type === 'monthly_expense' ? 'active' : '' }}" title="View Monthly Expenses">
                    <div class="card-top-row">
                        <div class="kpi-icon-box">
                            <i class="fa-solid fa-arrow-trend-down"></i>
                        </div>
                        <span class="kpi-active-pill">Active</span>
                    </div>
                    <div>
                        <div class="kpi-label">Expenses (C)</div>
                        <div class="kpi-value">₹{{ number_format((float)$monthly_expense) }}</div>
                        <div class="kpi-sub">Monthly Operational Outflows</div>
                    </div>
                </a>

                {{-- 4. Monthly Refunds (D) --}}
                <a href="{{ route('library.transaction.view', ['type' => 'monthly_refund', 'year' => $year, 'month' => $month]) }}" 
                   class="tx-kpi-card kpi-card-refund {{ $type === 'monthly_refund' ? 'active' : '' }}" title="View Monthly Refunds">
                    <div class="card-top-row">
                        <div class="kpi-icon-box">
                            <i class="fa-solid fa-rotate-left"></i>
                        </div>
                        <span class="kpi-active-pill">Active</span>
                    </div>
                    <div>
                        <div class="kpi-label">Refunds (D)</div>
                        <div class="kpi-value">₹{{ number_format((float)$monthly_refund) }}</div>
                        <div class="kpi-sub">Returned Fees</div>
                    </div>
                </a>

                {{-- 5. Monthly Due / Pending (E) --}}
                <a href="{{ route('library.transaction.view', ['type' => 'monthly_pending', 'year' => $year, 'month' => $month]) }}" 
                   class="tx-kpi-card kpi-card-pending {{ $type === 'monthly_pending' ? 'active' : '' }}" title="View Monthly Pending / Due">
                    <div class="card-top-row">
                        <div class="kpi-icon-box">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                        <span class="kpi-active-pill">Active</span>
                    </div>
                    <div>
                        <div class="kpi-label">Due Amt (E)</div>
                        <div class="kpi-value">₹{{ number_format((float)$monthly_pending) }}</div>
                        <div class="kpi-sub">Pending Receipts</div>
                    </div>
                </a>

                {{-- 6. Monthly Total Revenue --}}
                <a href="{{ route('library.transaction.view', ['type' => 'monthly_balance', 'year' => $year, 'month' => $month]) }}" 
                   class="tx-kpi-card kpi-card-balance {{ $type === 'monthly_balance' ? 'active' : '' }}" title="View Monthly Total Revenue Breakdown">
                    <div class="card-top-row">
                        <div class="kpi-icon-box">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                        <span class="kpi-active-pill">Active</span>
                    </div>
                    <div>
                        <div class="kpi-label">Total Revenue</div>
                        <div class="kpi-value">₹{{ number_format((float)$monthlyBalance) }}</div>
                        <div class="kpi-sub">Day-by-Day Balance</div>
                    </div>
                </a>
            @endif
        </div>

        {{-- 3. Collapsible Filter Drawer --}}
        @if($type !== 'monthly_balance')
        <div class="report-filter-wrapper" id="filterContainer" style="display: {{ $hasCustomFilter ? 'block' : 'none' }};">
            <form method="GET" action="{{ route('library.transaction.view') }}" class="single-line-filter-form" id="txFilterForm">
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">

                @if($type === 'today_expense' || $type === 'monthly_expense')
                <div class="filter-col">
                    <label class="filter-inline-label"><i class="fa-solid fa-tags"></i> Expense Category</label>
                    <select name="expense" class="form-select filter-control">
                        <option value="">All Categories</option>
                        @foreach($expensedata as $expType)
                        <option value="{{ $expType->name }}" {{ request('expense') == $expType->name ? 'selected' : '' }}>
                            {{ $expType->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @else
                <div class="filter-col">
                    <label class="filter-inline-label"><i class="fa-solid fa-credit-card"></i> Payment Type</label>
                    <select name="payment_type" class="form-select filter-control">
                        <option value="">All Types</option>
                        @if($type === 'today_other_collection' || $type === 'monthly_other_collection')
                            <option value="TOKEN MONEY" {{ request('payment_type') == 'TOKEN MONEY' ? 'selected' : '' }}>TOKEN MONEY</option>
                            <option value="MISCELLANEOUS" {{ request('payment_type') == 'MISCELLANEOUS' ? 'selected' : '' }}>MISCELLANEOUS</option>
                        @elseif($type === 'today_pending' || $type === 'monthly_pending')
                            <option value="PENDING" {{ request('payment_type') == 'PENDING' ? 'selected' : '' }}>PENDING</option>
                        @elseif($type === 'today_refund' || $type === 'monthly_refund')
                            <option value="REFUND" {{ request('payment_type') == 'REFUND' ? 'selected' : '' }}>REFUND</option>
                        @elseif($type === 'today_collection' || $type === 'monthly_collection')
                            <option value="SEAT ASSIGNMENT" {{ request('payment_type') == 'SEAT ASSIGNMENT' ? 'selected' : '' }}>SEAT ASSIGNMENT</option>
                            <option value="RENEW" {{ request('payment_type') == 'RENEW' ? 'selected' : '' }}>RENEW</option>
                            <option value="REACTIVE" {{ request('payment_type') == 'REACTIVE' ? 'selected' : '' }}>REACTIVE</option>
                            <option value="CHANGE PLAN" {{ request('payment_type') == 'CHANGE PLAN' ? 'selected' : '' }}>CHANGE PLAN</option>
                        @else
                            <option value="SEAT ASSIGNMENT" {{ request('payment_type') == 'SEAT ASSIGNMENT' ? 'selected' : '' }}>SEAT ASSIGNMENT</option>
                            <option value="RENEW" {{ request('payment_type') == 'RENEW' ? 'selected' : '' }}>RENEW</option>
                            <option value="REACTIVE" {{ request('payment_type') == 'REACTIVE' ? 'selected' : '' }}>REACTIVE</option>
                            <option value="CHANGE PLAN" {{ request('payment_type') == 'CHANGE PLAN' ? 'selected' : '' }}>CHANGE PLAN</option>
                            <option value="TOKEN MONEY" {{ request('payment_type') == 'TOKEN MONEY' ? 'selected' : '' }}>TOKEN MONEY</option>
                            <option value="MISCELLANEOUS" {{ request('payment_type') == 'MISCELLANEOUS' ? 'selected' : '' }}>MISCELLANEOUS</option>
                            <option value="EXPENSE" {{ request('payment_type') == 'EXPENSE' ? 'selected' : '' }}>EXPENSE</option>
                            <option value="REFUND" {{ request('payment_type') == 'REFUND' ? 'selected' : '' }}>REFUND</option>
                            <option value="PENDING" {{ request('payment_type') == 'PENDING' ? 'selected' : '' }}>PENDING</option>
                        @endif
                    </select>
                </div>
                @endif

                <div class="filter-col">
                    <label class="filter-inline-label"><i class="fa-regular fa-calendar-days"></i> From Date</label>
                    <input type="date" name="from" class="form-control filter-control" value="{{ request('from') }}">
                </div>

                <div class="filter-col">
                    <label class="filter-inline-label"><i class="fa-regular fa-calendar-days"></i> To Date</label>
                    <input type="date" name="to" class="form-control filter-control" value="{{ request('to') }}">
                </div>

                <div class="filter-col-actions">
                    <div class="filter-actions-inline">
                        <button type="submit" class="btn-filter-apply">
                            <i class="fa-solid fa-check"></i> Filter
                        </button>
                        <a href="{{ route('library.transaction.view', ['type' => $type, 'year' => $year, 'month' => $month]) }}" class="btn-filter-reset">
                            <i class="fa-solid fa-rotate-right"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
        @endif

        {{-- 4. Records Section Controls Bar --}}
        @if($type !== 'monthly_balance')
            <div class="records-controls-bar">
                <div class="records-title-wrap">
                    <h5 class="records-title">
                        <i class="fa-solid fa-receipt text-primary"></i> {{ $label ?? 'Transaction' }} Summary
                    </h5>
                    <span class="records-count-badge">{{ $collection->total() }} Records</span>
                </div>

                <div class="records-search-box">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="recordSearchInput" placeholder="Quick search on page..." autocomplete="off">
                    <button type="button" class="btn-clear-search" id="btnClearSearch" title="Clear Search">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>

            {{-- Desktop Table Header --}}
            <div class="tx-desktop-header">
                <div>Trxn. ID</div>
                <div>Learner / Seat</div>
                <div>Payment Type</div>
                <div>Amount</div>
                <div>Date</div>
                <div>Particular / Note</div>
            </div>

            {{-- Transaction Cards Listing --}}
            <div class="tx-records-list" id="txRecordsContainer">
                @forelse ($collection as $data)
                @php
                    $seatDisplay = !empty($data->learner->seat_no) ? (getSeatDisplayShortFloorName($data->learner->seat_no) ?? $data->learner->seat_no) : 'GENERAL';
                @endphp
                <div class="tx-record-card" data-search="{{ strtolower(($data->transaction_id ?? '') . ' ' . ($data->learner->name ?? '') . ' ' . ($data->learner->seat_no ?? '') . ' ' . $seatDisplay . ' ' . ($data->payment_type ?? '') . ' ' . ($data->particular ?? '')) }}">
                    
                    {{-- Column 1: Transaction ID & Dr/Cr Direction --}}
                    <div class="col-tx-id">
                        <div class="tx-flow-icon {{ $data->dr_cr === 'Cr' ? 'cr' : 'dr' }}" title="{{ $data->dr_cr === 'Cr' ? 'Credit (+)' : 'Debit (-)' }}">
                            @if($data->dr_cr === 'Cr')
                                <i class="fa fa-long-arrow-right fa-solid fa-arrow-right-long text-success"></i>
                            @else
                                <i class="fa fa-long-arrow-left fa-solid fa-arrow-left-long text-danger"></i>
                            @endif
                        </div>
                        <span class="tx-id-text">{{ $data->transaction_id ?? 'N/A' }}</span>
                    </div>

                    {{-- Column 2: Learner & Seat / Expense Info --}}
                    <div class="col-learner-info">
                        @if($data->payment_type === 'EXPENSE')
                            <span class="expense-category-badge">
                                <i class="fa-solid fa-receipt"></i> Library Expense
                            </span>
                        @else
                            <span class="learner-seat-badge" title="Seat Number">
                                <i class="fa-solid fa-chair text-muted me-1"></i>{{ $seatDisplay }}
                            </span>
                            <span class="learner-name-text" title="{{ $data->learner->name ?? 'N/A' }}">
                                {{ $data->learner->name ?? 'N/A' }}
                            </span>
                        @endif
                    </div>

                    {{-- Column 3: Payment Type --}}
                    <div class="col-payment-type">
                        <span class="tx-type-pill">{{ $data->payment_type ?? 'N/A' }}</span>
                    </div>

                    {{-- Column 4: Amount with Dr/Cr coloring --}}
                    <div class="col-amount">
                        <span class="tx-amount-text {{ $data->dr_cr === 'Cr' ? 'cr' : 'dr' }}">
                            {{ $data->dr_cr === 'Cr' ? '+₹' : '-₹' }}{{ number_format($data->amount, 2) }}
                        </span>
                    </div>

                    {{-- Column 5: Date --}}
                    <div class="col-date">
                        <i class="fa-regular fa-calendar"></i>
                        <span>{{ !empty($data->date) ? \Carbon\Carbon::parse($data->date)->format('d M, Y') : 'N/A' }}</span>
                    </div>

                    {{-- Column 6: Particular --}}
                    <div class="col-particular" title="{{ $data->particular ?? '' }}">
                        {{ $data->particular ?? '—' }}
                    </div>
                </div>
                @empty
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="fa-regular fa-folder-open"></i>
                    </div>
                    <h6 class="empty-state-title">No Records Found</h6>
                    <p class="empty-state-desc">There are no transaction records matching your current selection.</p>
                    @if($hasCustomFilter)
                        <a href="{{ route('library.transaction.view', ['type' => $type, 'year' => $year, 'month' => $month]) }}" class="btn btn-sm btn-filter-reset">
                            <i class="fa-solid fa-rotate-right me-1"></i> Reset Filters
                        </a>
                    @endif
                </div>
                @endforelse
            </div>

            {{-- Themed Pagination --}}
            @if ($collection->lastPage() > 1)
            <ul class="theme-pagination">
                {{-- Prev --}}
                @if(!$collection->onFirstPage())
                    <li>
                        <a href="{{ $collection->previousPageUrl() }}">
                            <i class="fa-solid fa-chevron-left me-1"></i> Prev
                        </a>
                    </li>
                @endif

                {{-- First Page --}}
                @if ($collection->currentPage() > 3)
                    <li><a href="{{ $collection->url(1) }}">1</a></li>
                    @if($collection->currentPage() > 4)
                        <li><span>...</span></li>
                    @endif
                @endif

                {{-- Page Range --}}
                @for ($i = max(1, $collection->currentPage() - 2); $i <= min($collection->lastPage(), $collection->currentPage() + 2); $i++)
                    <li>
                        <a href="{{ $collection->url($i) }}" class="{{ $collection->currentPage() == $i ? 'active' : '' }}">
                            {{ $i }}
                        </a>
                    </li>
                @endfor

                {{-- Last Page --}}
                @if ($collection->currentPage() < $collection->lastPage() - 2)
                    @if($collection->currentPage() < $collection->lastPage() - 3)
                        <li><span>...</span></li>
                    @endif
                    <li><a href="{{ $collection->url($collection->lastPage()) }}">{{ $collection->lastPage() }}</a></li>
                @endif

                {{-- Next --}}
                @if($collection->hasMorePages())
                    <li>
                        <a href="{{ $collection->nextPageUrl() }}">
                            Next <i class="fa-solid fa-chevron-right ms-1"></i>
                        </a>
                    </li>
                @endif
            </ul>
            @endif

        @else
            {{-- Monthly Balance Day-by-Day Breakdown --}}
            <div class="records-controls-bar">
                <div class="records-title-wrap">
                    <h5 class="records-title">
                        <i class="fa-solid fa-calendar-check text-primary"></i> Day-by-Day Balance Breakdown
                    </h5>
                    <span class="records-count-badge">{{ count($monthly_balance) }} Recorded Days</span>
                </div>
            </div>

            <div class="monthly-balance-card table-responsive">
                <table class="monthly-balance-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th style="color: #059669;">Booking Coll. (+)</th>
                            <th style="color: #0d9488;">Other Coll. (+)</th>
                            <th style="color: #dc2626;">Expenses (-)</th>
                            <th style="color: #d97706;">Refunds (-)</th>
                            <th style="color: #4f46e5;">Pending Receipts</th>
                            <th>Day Net</th>
                            <th style="color: #18225f;">Running Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($monthly_balance as $row)
                        <tr>
                            <td>
                                <strong>{{ \Carbon\Carbon::parse($row['date'])->format('d M, Y (D)') }}</strong>
                            </td>
                            <td style="color: #059669; font-weight: 600;">
                                +₹{{ number_format($row['collection']) }}
                            </td>
                            <td style="color: #0d9488; font-weight: 600;">
                                +₹{{ number_format($row['other_collection']) }}
                            </td>
                            <td style="color: #dc2626; font-weight: 600;">
                                -₹{{ number_format($row['expense']) }}
                            </td>
                            <td style="color: #d97706; font-weight: 600;">
                                -₹{{ number_format($row['refund']) }}
                            </td>
                            <td style="color: #4f46e5; font-weight: 600;">
                                ₹{{ number_format($row['pending']) }}
                            </td>
                            <td>
                                <span class="badge {{ $row['net'] >= 0 ? 'bg-success' : 'bg-danger' }}" style="font-size: 0.78rem; font-weight: 600;">
                                    ₹{{ number_format($row['net']) }}
                                </span>
                            </td>
                            <td>
                                <strong style="color: #18225f; font-size: 0.92rem;">
                                    ₹{{ number_format($row['final_balance']) }}
                                </strong>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                No monthly balance records found for this period.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

    </div>

</div>

<script>
    $(document).ready(function() {
        // Smooth transition from skeleton shimmer to real content
        var skeleton = document.getElementById('txSkeletonContainer');
        var realContent = document.getElementById('txRealContentContainer');

        setTimeout(function() {
            if (skeleton) {
                skeleton.classList.add('fade-out');
                setTimeout(function() {
                    skeleton.style.display = 'none';
                }, 180);
            }
            if (realContent) {
                realContent.style.display = 'block';
                void realContent.offsetHeight; // Force reflow
                realContent.classList.add('is-active');
            }
        }, 260);

        // Safety fallback: ensure real content is never stuck hidden
        setTimeout(function() {
            if (skeleton && skeleton.style.display !== 'none') {
                skeleton.style.display = 'none';
            }
            if (realContent && !realContent.classList.contains('is-active')) {
                realContent.style.display = 'block';
                realContent.classList.add('is-active');
            }
        }, 700);

        // When user submits filter, show shimmer instantly
        $('#txFilterForm').on('submit', function() {
            if (skeleton && realContent) {
                realContent.style.display = 'none';
                skeleton.classList.remove('fade-out');
                skeleton.style.display = 'block';
            }
        });

        // Preserve Filter Drawer state if filter is active
        @if($hasCustomFilter)
            setTimeout(function() {
                $('#filterContainer').show();
                $('#toggleFilterBtn').addClass('active');
            }, 60);
        @endif

        // Toggle Filter Drawer
        $('#toggleFilterBtn').on('click', function(e) {
            e.preventDefault();
            $('#filterContainer').slideToggle(200);
            $(this).toggleClass('active');
        });

        // Instant Client-side Search on active page cards
        $('#recordSearchInput').on('input', function() {
            var query = $(this).val().toLowerCase().trim();
            if (query.length > 0) {
                $('#btnClearSearch').show();
            } else {
                $('#btnClearSearch').hide();
            }

            $('#txRecordsContainer .tx-record-card').each(function() {
                var searchStr = $(this).attr('data-search') || '';
                if (!query || searchStr.indexOf(query) !== -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // Clear Search Button
        $('#btnClearSearch').on('click', function() {
            $('#recordSearchInput').val('').trigger('input').focus();
        });
    });
</script>

@endsection
