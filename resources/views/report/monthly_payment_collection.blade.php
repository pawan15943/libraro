@extends('layouts.library')

@section('title', 'Monthly Payment Collection Report')

@section('content')

@php
    use Carbon\Carbon;
    $currentYear = (int) date('Y');
    $currentMonth = (int) date('m');
    $selectedYear = request('year', $currentYear);
    $selectedMonth = request('month', $currentMonth);
    $selectedFlow = request('flow', 'all');
    $selectedMode = request('payment_mode', 'all');
    $selectedStartDate = request('start_date');
    $selectedEndDate = request('end_date');
@endphp

{{-- Dedicated Scoped Stylesheet for Monthly Payment Collection Report --}}
<link rel="stylesheet" href="{{ asset('public/css/monthly-payment-report.css') }}?v={{ time() }}" />
<link rel="stylesheet" href="{{ asset('public/css/report-shimmer.css') }}?v={{ time() }}" />

@include('report.partials.report-shimmer', ['type' => 'default'])

<div class="monthly-payment-report-module report-live-content" id="reportContent">

    {{-- System Flash Alerts --}}
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

    @can('has-permission', 'Monthly Revenue Report')

    {{-- 1. Top Action & Month Navigator Bar (Zero Cognitive Load for Library Owner) --}}
    <div class="monthly-header-bar d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        {{-- Month Navigator Box --}}
        <div class="month-navigator-box">
            <a href="{{ route('monthly.payment.collection.report', ['year' => $prevMonthDate->year, 'month' => $prevMonthDate->month]) }}" 
               class="btn-month-nav" 
               title="Previous Month ({{ $prevMonthDate->format('M Y') }})">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            
            <div class="month-display-title">
                <i class="fa-regular fa-calendar-check me-2 text-teal"></i>
                <span class="active-period-text" id="activePeriodLabel">
                    @if(request()->filled('start_date') && request()->filled('end_date'))
                        {{ Carbon::parse(request('start_date'))->format('d M') }} – {{ Carbon::parse(request('end_date'))->format('d M Y') }}
                    @else
                        {{ $fromDate->format('F Y') }}
                    @endif
                </span>
                @if($isCurrentMonth && !request()->filled('start_date'))
                    <span class="badge-current-month" id="badgeCurrentMonth">This Month</span>
                @endif
            </div>

            @if(!$isFutureMonth && !$isCurrentMonth)
                <a href="{{ route('monthly.payment.collection.report', ['year' => $nextMonthDate->year, 'month' => $nextMonthDate->month]) }}" 
                   class="btn-month-nav" 
                   title="Next Month ({{ $nextMonthDate->format('M Y') }})">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            @else
                <span class="btn-month-nav disabled" title="Current Month">
                    <i class="fa-solid fa-chevron-right"></i>
                </span>
            @endif

            @if(!$isCurrentMonth || request()->filled('start_date'))
                <a href="{{ route('monthly.payment.collection.report') }}" class="btn-jump-current" title="Reset to Current Month">
                    <i class="fa-solid fa-rotate me-1"></i> Today
                </a>
            @endif
        </div>

        {{-- Right Action Buttons --}}
        <div class="header-actions d-flex align-items-center gap-2">
            {{-- Filter Toggle Button --}}
            <button type="button" class="btn btn-filter-toggle {{ !empty($hasCustomFilter) ? 'active' : '' }}" id="toggleFilterBtn" title="Show/Hide Advanced Filters">
                <i class="fa-solid fa-filter"></i>
                <span>Filters</span>
                @if(!empty($hasCustomFilter))
                    <span class="filter-badge-dot" title="Active Filter Applied"></span>
                @endif
            </button>

            {{-- Export CSV Button --}}
            <a href="{{ route('monthly.payment.export', request()->all()) }}" class="btn btn-export-csv" id="btnExportReportCsv" title="Download monthly collection report in CSV">
                <i class="fa-solid fa-file-csv"></i> Export CSV
            </a>
        </div>
    </div>

    {{-- 2. Monthly Collection & Cash Reconciliation Summary (Executive Summary Card) --}}
    <div class="monthly-summary-card">
        <div class="summary-card-header">
            <div class="summary-title-wrap">
                <div class="summary-badge-icon">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <div>
                    <h5 class="summary-title">Monthly Collection Summary</h5>
                    <p class="summary-subtitle">
                        <span>{{ $fromDate->format('F Y') }}</span> • Cash vs Digital Breakdown
                    </p>
                </div>
            </div>
            <div class="summary-header-metrics">
                <div class="summary-total-chip">
                    <span class="chip-label">Total Inflow</span>
                    <span class="chip-value text-success" id="summaryGrossCollection">+₹ {{ number_format($totalCollection ?? 0, 0) }}</span>
                </div>
            </div>
        </div>

        <div class="summary-metrics-grid">
            {{-- Stat 1: Cash In Drawer (Galla) --}}
            <div class="summary-metric-box item-cash">
                <div class="metric-icon-wrap">
                    <i class="fa-solid fa-money-bill-wave"></i>
                </div>
                <div class="metric-info">
                    <span class="metric-label">Cash In Drawer (Galla)</span>
                    <h4 class="metric-value" id="reconcileCashVal">₹ {{ number_format($cashCollection ?? 0, 0) }}</h4>
                    <span class="metric-sub" id="reconcileCashCount">{{ $cashCount ?? 0 }} cash transactions</span>
                </div>
            </div>

            {{-- Stat 2: Online & UPI (Bank) --}}
            <div class="summary-metric-box item-online">
                <div class="metric-icon-wrap">
                    <i class="fa-solid fa-qrcode"></i>
                </div>
                <div class="metric-info">
                    <span class="metric-label">Online &amp; UPI (Bank)</span>
                    <h4 class="metric-value" id="reconcileOnlineVal">₹ {{ number_format($onlineCollection ?? 0, 0) }}</h4>
                    <span class="metric-sub" id="reconcileOnlineCount">{{ $onlineCount ?? 0 }} digital payments</span>
                </div>
            </div>

            {{-- Stat 3: Active Days & Run-Rate --}}
            <div class="summary-metric-box item-avg">
                <div class="metric-icon-wrap">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
                <div class="metric-info">
                    <span class="metric-label">Active Days &amp; Run-Rate</span>
                    <h4 class="metric-value" id="reconcileDailyAvg">₹ {{ number_format(($metrics['activeDays'] ?? 0) > 0 ? ($totalCollection / $metrics['activeDays']) : 0, 0) }} / day</h4>
                    <span class="metric-sub" id="reconcileActiveDays">{{ $metrics['activeDays'] ?? 0 }} active collection days</span>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. Quick Filter Tabs with Live Count Badges --}}
    <div class="report-quick-tabs" id="quickTabs">
        <button type="button" class="quick-tab-btn active" data-tab="all">
            <i class="fa-solid fa-layer-group"></i> 
            <span>All Transactions</span>
            <span class="tab-badge" id="tabBadgeAll">{{ $metrics['count'] ?? 0 }}</span>
        </button>
        <button type="button" class="quick-tab-btn" data-tab="credit">
            <i class="fa-solid fa-arrow-down-left text-success"></i> 
            <span>Collections</span>
            <span class="tab-badge badge-success" id="tabBadgeInflow">{{ $inflowCount ?? 0 }}</span>
        </button>
        <button type="button" class="quick-tab-btn" data-tab="expense">
            <i class="fa-solid fa-receipt text-danger"></i> 
            <span>Expenses</span>
            <span class="tab-badge badge-danger" id="tabBadgeExpense">{{ $expenseCount ?? 0 }}</span>
        </button>
        <button type="button" class="quick-tab-btn" data-tab="refund">
            <i class="fa-solid fa-rotate-left text-warning"></i> 
            <span>Refunds</span>
            <span class="tab-badge badge-warning" id="tabBadgeRefund">{{ $refundCount ?? 0 }}</span>
        </button>
        <button type="button" class="quick-tab-btn" data-tab="cash">
            <i class="fa-solid fa-money-bill-wave text-amber"></i> 
            <span>Cash</span>
            <span class="tab-badge badge-amber" id="tabBadgeCash">{{ $cashCount ?? 0 }}</span>
        </button>
        <button type="button" class="quick-tab-btn" data-tab="online">
            <i class="fa-solid fa-globe text-primary"></i> 
            <span>Online / UPI</span>
            <span class="tab-badge badge-primary" id="tabBadgeOnline">{{ $onlineCount ?? 0 }}</span>
        </button>
    </div>

    {{-- 5. Single-Line Collapsible Filter Drawer with Quick Presets --}}
    <div class="report-filter-wrapper" id="reportFilterContainer" style="{{ !empty($hasCustomFilter) ? '' : 'display: none;' }}">
        {{-- Quick Presets Row --}}
        <div class="filter-presets-bar d-flex align-items-center gap-2 mb-2 pb-2 border-bottom flex-wrap">
            <span class="text-muted small fw-semibold"><i class="fa-solid fa-bolt text-teal me-1"></i> Quick Presets:</span>
            <button type="button" class="btn btn-preset" data-preset="this_month">This Month</button>
            <button type="button" class="btn btn-preset" data-preset="last_month">Last Month</button>
            <button type="button" class="btn btn-preset" data-preset="today">Today</button>
        </div>

        <form action="{{ route('monthly.payment.collection.report') }}" method="GET" id="reportFilterForm" class="single-line-filter-form">
            {{-- Start Date --}}
            <div class="filter-col">
                <label for="start_date" class="filter-inline-label"><i class="fa-regular fa-calendar"></i> Start Date</label>
                <input type="date" class="form-control filter-control" id="start_date" name="start_date" value="{{ $selectedStartDate }}">
            </div>

            {{-- End Date --}}
            <div class="filter-col">
                <label for="end_date" class="filter-inline-label"><i class="fa-regular fa-calendar"></i> End Date</label>
                <input type="date" class="form-control filter-control" id="end_date" name="end_date" value="{{ $selectedEndDate }}">
            </div>

            {{-- Year --}}
            <div class="filter-col">
                <label for="year" class="filter-inline-label"><i class="fa-regular fa-calendar-days"></i> Year</label>
                <select name="year" id="year" class="form-select filter-control">
                    <option value="">All Years</option>
                    @foreach($dynamicyears ?? range($currentYear, $currentYear - 3) as $yr)
                        <option value="{{ $yr }}" {{ ((string)$selectedYear === (string)$yr) ? 'selected' : '' }}>
                            {{ $yr }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Month --}}
            <div class="filter-col">
                <label for="month" class="filter-inline-label"><i class="fa-regular fa-calendar-check"></i> Month</label>
                <select name="month" id="month" class="form-select filter-control">
                    <option value="">All Months</option>
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ ((string)$selectedMonth === (string)$m) ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Flow / Type --}}
            <div class="filter-col">
                <label for="flow" class="filter-inline-label"><i class="fa-solid fa-money-bill-transfer"></i> Flow</label>
                <select name="flow" id="flow" class="form-select filter-control">
                    <option value="all" {{ $selectedFlow === 'all' ? 'selected' : '' }}>All Flows</option>
                    <option value="credit" {{ $selectedFlow === 'credit' ? 'selected' : '' }}>Credits (Collections)</option>
                    <option value="debit" {{ $selectedFlow === 'debit' ? 'selected' : '' }}>Debits (Expenses &amp; Refunds)</option>
                    <option value="expense" {{ $selectedFlow === 'expense' ? 'selected' : '' }}>Expenses Only</option>
                    <option value="refund" {{ $selectedFlow === 'refund' ? 'selected' : '' }}>Refunds Only</option>
                </select>
            </div>

            {{-- Payment Mode --}}
            <div class="filter-col">
                <label for="payment_mode" class="filter-inline-label"><i class="fa-solid fa-credit-card"></i> Mode</label>
                <select name="payment_mode" id="payment_mode" class="form-select filter-control">
                    <option value="all" {{ $selectedMode === 'all' ? 'selected' : '' }}>All Modes</option>
                    <option value="Cash" {{ $selectedMode === 'Cash' ? 'selected' : '' }}>Cash</option>
                    <option value="Online" {{ $selectedMode === 'Online' ? 'selected' : '' }}>Online</option>
                    <option value="UPI" {{ $selectedMode === 'UPI' ? 'selected' : '' }}>UPI</option>
                </select>
            </div>

            {{-- Actions --}}
            <div class="filter-col filter-col-actions">
                <span class="filter-inline-label filter-label-spacer" aria-hidden="true">&nbsp;</span>
                <div class="filter-actions-inline">
                    <button type="submit" class="btn btn-filter-apply" id="btnApplyFilter">
                        <i class="fa-solid fa-magnifying-glass"></i> Filter
                    </button>
                    <a href="{{ route('monthly.payment.collection.report') }}" class="btn btn-filter-reset" id="btnResetFilter">
                        <i class="fa-solid fa-arrow-rotate-left"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- 6. Records Presentation --}}
    <div class="records-wrapper">
        <div class="records-controls-bar">
            <div class="records-count-info">
                <span>Transactions:</span>
                <span class="records-count-badge" id="visibleCountBadge">
                    {{ isset($groupedTransactions) ? $groupedTransactions->flatten()->count() : 0 }}
                </span>
                <span class="text-muted small">records</span>
            </div>
            <div class="records-search-box">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="cardSearchInput" placeholder="Quick search transactions, seats, notes..." autocomplete="off" />
                <button type="button" class="btn-clear-search d-none" id="clearSearchBtn" title="Clear Search">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        {{-- Records Container --}}
        <div class="monthly-records-container" id="monthlyReportContainer">
            @include('report.partials.monthly_payment_table', [
                'groupedTransactions' => $groupedTransactions,
                'totalCr'             => $totalCr,
                'totalDr'             => $totalDr,
                'totalCollection'     => $totalCollection,
                'totalExpense'        => $totalExpense,
                'totalRevenue'        => $totalRevenue,
                'grandTotal'          => $grandTotal,
                'cashCollection'      => $cashCollection,
                'onlineCollection'    => $onlineCollection,
                'inflowCount'         => $inflowCount,
                'expenseCount'        => $expenseCount,
                'refundCount'         => $refundCount,
                'cashCount'           => $cashCount,
                'onlineCount'         => $onlineCount
            ])
        </div>

        {{-- Client-side Search Empty State --}}
        <div class="report-empty-state d-none" id="searchEmptyState">
            <div class="empty-state-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
            <h6 class="empty-state-title">No Matching Records Found</h6>
            <p class="empty-state-desc">Try clearing the search box or selecting a different filter tab above.</p>
        </div>
    </div>

    @else
    <div class="card text-center p-4">
        <span class="text-danger fw-semibold">You do not have permission to view the Monthly Revenue Report.</span>
    </div>
    @endcan

</div>

{{-- Dynamic Interaction Script --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleFilterBtn = document.getElementById('toggleFilterBtn');
    const filterContainer = document.getElementById('reportFilterContainer');
    const searchInput = document.getElementById('cardSearchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const quickTabs = document.querySelectorAll('.quick-tab-btn');
    const visibleCountBadge = document.getElementById('visibleCountBadge');
    const searchEmptyState = document.getElementById('searchEmptyState');
    const filterForm = document.getElementById('reportFilterForm');
    const exportCsvBtn = document.getElementById('btnExportReportCsv');
    const presetButtons = document.querySelectorAll('.btn-preset');

    let activeTab = 'all';

    // 1. Toggle Filter Drawer
    if (toggleFilterBtn && filterContainer) {
        toggleFilterBtn.addEventListener('click', function () {
            if (filterContainer.style.display === 'none' || filterContainer.style.display === '') {
                filterContainer.style.display = 'block';
                toggleFilterBtn.classList.add('active');
            } else {
                filterContainer.style.display = 'none';
                toggleFilterBtn.classList.remove('active');
            }
        });
    }

    // 2. Quick Presets (This Month, Last Month, Today)
    presetButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            presetButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const preset = this.getAttribute('data-preset');
            const now = new Date();
            const currYear = now.getFullYear();
            const currMonth = now.getMonth() + 1;

            const startInput = document.getElementById('start_date');
            const endInput = document.getElementById('end_date');
            const yearSelect = document.getElementById('year');
            const monthSelect = document.getElementById('month');

            if (preset === 'this_month') {
                if (startInput) startInput.value = '';
                if (endInput) endInput.value = '';
                if (yearSelect) yearSelect.value = currYear;
                if (monthSelect) monthSelect.value = currMonth;
            } else if (preset === 'last_month') {
                if (startInput) startInput.value = '';
                if (endInput) endInput.value = '';
                let lastMonth = currMonth - 1;
                let lastYear = currYear;
                if (lastMonth < 1) {
                    lastMonth = 12;
                    lastYear--;
                }
                if (yearSelect) yearSelect.value = lastYear;
                if (monthSelect) monthSelect.value = lastMonth;
            } else if (preset === 'today') {
                const todayStr = now.toISOString().split('T')[0];
                if (startInput) startInput.value = todayStr;
                if (endInput) endInput.value = todayStr;
                if (yearSelect) yearSelect.value = '';
                if (monthSelect) monthSelect.value = '';
            }

            if (filterForm) {
                filterForm.dispatchEvent(new Event('submit', { cancelable: true }));
            }
        });
    });

    // 3. Client-side Quick Tabs Switching
    quickTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            quickTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            activeTab = this.getAttribute('data-tab');
            applyClientFilters();
        });
    });

    // 4. Client-side Search Input
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            if (this.value.trim().length > 0) {
                clearSearchBtn.classList.remove('d-none');
            } else {
                clearSearchBtn.classList.add('d-none');
            }
            applyClientFilters();
        });
    }

    // 5. Clear Search Button
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function () {
            if (searchInput) {
                searchInput.value = '';
                this.classList.add('d-none');
                applyClientFilters();
                searchInput.focus();
            }
        });
    }

    // Function to apply Tab + Search filter across Desktop Ledger Table AND Mobile Cards
    function applyClientFilters() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        let totalVisibleTxns = 0;
        const visibleDates = new Set();

        function checkMatches(tokens, flow, tabCat, modeCat) {
            let matchesTab = false;
            if (activeTab === 'all') {
                matchesTab = true;
            } else if (activeTab === 'credit' && flow === 'credit') {
                matchesTab = true;
            } else if (activeTab === 'expense' && tabCat === 'expense') {
                matchesTab = true;
            } else if (activeTab === 'refund' && tabCat === 'refund') {
                matchesTab = true;
            } else if (activeTab === 'cash' && modeCat === 'cash') {
                matchesTab = true;
            } else if (activeTab === 'online' && modeCat === 'online') {
                matchesTab = true;
            }
            const matchesSearch = query === '' || tokens.includes(query);
            return matchesTab && matchesSearch;
        }

        let totalDesktopVisible = 0;
        let totalMobileVisible = 0;

        // 1. Filter Desktop Ledger Table
        const tbodies = document.querySelectorAll('.date-group-tbody');
        tbodies.forEach(function (tbody) {
            const rows = tbody.querySelectorAll('.ledger-table-row');
            const dateCell = tbody.querySelector('.td-date-span');
            const balanceCell = tbody.querySelector('.td-balance-span');
            const visibleRows = [];

            rows.forEach(function (row) {
                const searchTokens = row.getAttribute('data-search') || '';
                const rowFlow = row.getAttribute('data-flow') || '';
                const rowTabCat = row.getAttribute('data-tab-cat') || '';
                const rowModeCat = row.getAttribute('data-mode-cat') || '';
                const dayRef = row.getAttribute('data-day-ref') || '';

                if (checkMatches(searchTokens, rowFlow, rowTabCat, rowModeCat)) {
                    row.style.removeProperty('display');
                    visibleRows.push(row);
                    totalDesktopVisible++;
                    if (dayRef) visibleDates.add(dayRef);
                } else {
                    row.style.setProperty('display', 'none', 'important');
                }
            });

            if (visibleRows.length === 0) {
                tbody.style.setProperty('display', 'none', 'important');
            } else {
                tbody.style.removeProperty('display');
                // Dynamically re-parent dateCell and balanceCell to first visible row and update rowSpan
                const firstRow = visibleRows[0];
                if (dateCell) {
                    dateCell.rowSpan = visibleRows.length;
                    if (dateCell.parentElement !== firstRow) {
                        firstRow.prepend(dateCell);
                    }
                }
                if (balanceCell) {
                    balanceCell.rowSpan = visibleRows.length;
                    if (balanceCell.parentElement !== firstRow) {
                        firstRow.appendChild(balanceCell);
                    }
                }
            }
        });

        // 2. Filter Mobile Cards (if present)
        const mobileCards = document.querySelectorAll('.monthly-mobile-cards-wrap .collection-record-card');
        mobileCards.forEach(function (card) {
            const searchTokens = card.getAttribute('data-search') || '';
            const rowFlow = card.getAttribute('data-flow') || '';
            const rowTabCat = card.getAttribute('data-tab-cat') || '';
            const rowModeCat = card.getAttribute('data-mode-cat') || '';
            const dayRef = card.getAttribute('data-day-ref') || '';

            if (checkMatches(searchTokens, rowFlow, rowTabCat, rowModeCat)) {
                card.style.removeProperty('display');
                totalMobileVisible++;
                if (dayRef) visibleDates.add(dayRef);
            } else {
                card.style.setProperty('display', 'none', 'important');
            }
        });

        // 3. Show/hide Mobile Date Dividers
        const dayDividers = document.querySelectorAll('.monthly-mobile-cards-wrap .date-group-divider');
        dayDividers.forEach(function (divider) {
            const day = divider.getAttribute('data-day-group');
            if (visibleDates.has(day)) {
                divider.style.removeProperty('display');
            } else {
                divider.style.setProperty('display', 'none', 'important');
            }
        });

        const activeCount = (window.innerWidth >= 992 || mobileCards.length === 0) ? totalDesktopVisible : totalMobileVisible;
        if (visibleCountBadge) {
            visibleCountBadge.textContent = activeCount;
        }

        if (searchEmptyState) {
            if (activeCount === 0 && (mobileCards.length > 0 || tbodies.length > 0)) {
                searchEmptyState.classList.remove('d-none');
            } else {
                searchEmptyState.classList.add('d-none');
            }
        }
    }

    // 6. AJAX Form Submission for Filter Drawer
    if (filterForm) {
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const url = new URL(filterForm.action);
            const formData = new FormData(filterForm);
            formData.forEach((val, key) => {
                if (val !== '' && val !== 'all') {
                    url.searchParams.set(key, val);
                } else {
                    url.searchParams.delete(key);
                }
            });

            // Update CSV export link with current filters
            if (exportCsvBtn) {
                const exportUrl = new URL("{{ route('monthly.payment.export') }}", window.location.origin);
                formData.forEach((val, key) => {
                    if (val !== '' && val !== 'all') {
                        exportUrl.searchParams.set(key, val);
                    }
                });
                exportCsvBtn.href = exportUrl.toString();
            }

            const applyBtn = document.getElementById('btnApplyFilter');
            const originalBtnHtml = applyBtn.innerHTML;
            applyBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Filtering...';
            applyBtn.disabled = true;

            fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('monthlyReportContainer');
                if (container && data.html) {
                    container.innerHTML = data.html;
                }

                // Update Summary Card & Reconcile Strip
                if (data.metrics) {
                    const m = data.metrics;
                    const summaryGross = document.getElementById('summaryGrossCollection');
                    if (summaryGross) summaryGross.textContent = '+₹ ' + Number(m.totalCollection || 0).toLocaleString();

                    const kpiExp = document.getElementById('kpiTotalExpenses');
                    const kpiRef = document.getElementById('kpiTotalRefunds');
                    const kpiNet = document.getElementById('kpiNetRevenue');

                    if (kpiCol) kpiCol.textContent = '+₹ ' + Number(m.totalCollection || 0).toLocaleString();
                    if (kpiExp) kpiExp.textContent = '-₹ ' + Number(m.totalExpense || 0).toLocaleString();
                    if (kpiRef) kpiRef.textContent = '-₹ ' + Number(m.totalRevenue || 0).toLocaleString();
                    if (kpiNet) {
                        const net = Number(m.grandTotal || 0);
                        kpiNet.textContent = (net >= 0 ? '+' : '-') + '₹ ' + Math.abs(net).toLocaleString();
                        kpiNet.className = 'kpi-value ' + (net >= 0 ? 'text-navy' : 'text-danger');
                    }

                    // Update Reconcile Strip
                    const recCash = document.getElementById('reconcileCashVal');
                    const recCashCount = document.getElementById('reconcileCashCount');
                    const recOnline = document.getElementById('reconcileOnlineVal');
                    const recOnlineCount = document.getElementById('reconcileOnlineCount');
                    const recDailyAvg = document.getElementById('reconcileDailyAvg');
                    const recActiveDays = document.getElementById('reconcileActiveDays');

                    if (recCash) recCash.textContent = '₹ ' + Number(m.cashCollection || 0).toLocaleString();
                    if (recCashCount) recCashCount.textContent = (m.cashCount || 0) + ' cash transactions';
                    if (recOnline) recOnline.textContent = '₹ ' + Number(m.onlineCollection || 0).toLocaleString();
                    if (recOnlineCount) recOnlineCount.textContent = (m.onlineCount || 0) + ' digital payments';
                    if (recDailyAvg) {
                        const avg = (m.activeDays > 0) ? Math.round(m.totalCollection / m.activeDays) : 0;
                        recDailyAvg.textContent = '₹ ' + avg.toLocaleString() + ' / day';
                    }
                    if (recActiveDays) recActiveDays.textContent = (m.activeDays || 0) + ' active collection days';

                    // Update Tab Badges
                    const tbAll = document.getElementById('tabBadgeAll');
                    const tbInflow = document.getElementById('tabBadgeInflow');
                    const tbExp = document.getElementById('tabBadgeExpense');
                    const tbRef = document.getElementById('tabBadgeRefund');
                    const tbCash = document.getElementById('tabBadgeCash');
                    const tbOnline = document.getElementById('tabBadgeOnline');

                    if (tbAll) tbAll.textContent = m.count || 0;
                    if (tbInflow) tbInflow.textContent = m.inflowCount || 0;
                    if (tbExp) tbExp.textContent = m.expenseCount || 0;
                    if (tbRef) tbRef.textContent = m.refundCount || 0;
                    if (tbCash) tbCash.textContent = m.cashCount || 0;
                    if (tbOnline) tbOnline.textContent = m.onlineCount || 0;

                    if (m.periodLabel) {
                        const pLabel = document.getElementById('activePeriodLabel');
                        if (pLabel) pLabel.textContent = m.periodLabel;
                    }
                }

                // Reset search box & tabs
                if (searchInput) searchInput.value = '';
                if (clearSearchBtn) clearSearchBtn.classList.add('d-none');
                quickTabs.forEach(t => t.classList.remove('active'));
                const allTab = document.querySelector('.quick-tab-btn[data-tab="all"]');
                if (allTab) allTab.classList.add('active');
                activeTab = 'all';

                applyClientFilters();
                window.history.pushState({}, '', url.toString());
            })
            .catch(err => {
                console.error('Filter AJAX error:', err);
                filterForm.submit();
            })
            .finally(() => {
                applyBtn.innerHTML = originalBtnHtml;
                applyBtn.disabled = false;
            });
        });
    }
});
</script>

@endsection

