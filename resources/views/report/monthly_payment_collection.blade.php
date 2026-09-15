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

<div class="monthly-payment-report-module">

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

    {{-- 1. Top Action Buttons Bar (No redundant heading as per GEMINI.md) --}}
    <div class="heading-list py-1 d-flex justify-content-end align-items-center gap-2 mb-3">
        <div class="header-actions">
            {{-- Filter Toggle Button --}}
            <button type="button" class="btn btn-filter-toggle {{ !empty($hasCustomFilter) ? 'active' : '' }}" id="toggleFilterBtn" title="Show/Hide Filter Drawer">
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

            {{-- Print Button --}}
            <button type="button" class="btn btn-report-print" onclick="window.print()" title="Print this report">
                <i class="fa-solid fa-print"></i> Print
            </button>
        </div>
    </div>

    {{-- 2. 4 Simple KPI Summary Cards (Clean, modern, no left border) --}}
    <div class="report-kpi-grid">
        {{-- Card 1: Total Collections (Credits) --}}
        <div class="report-kpi-card kpi-collections">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Total Collections</div>
                <div class="kpi-value text-success" id="kpiTotalCollections">
                    +₹ {{ number_format($totalCollection ?? 0, 0) }}
                </div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-circle-check me-1"></i>Learner Fees &amp; Inflow
                </div>
            </div>
        </div>

        {{-- Card 2: Total Expenses (Debits) --}}
        <div class="report-kpi-card kpi-expenses">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-arrow-trend-down"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Total Expenses</div>
                <div class="kpi-value text-danger" id="kpiTotalExpenses">
                    -₹ {{ number_format($totalExpense ?? 0, 0) }}
                </div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-arrow-right-from-bracket me-1"></i>Operational Outflow
                </div>
            </div>
        </div>

        {{-- Card 3: Total Refunds --}}
        <div class="report-kpi-card kpi-refunds">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-rotate-left"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Total Refunds</div>
                <div class="kpi-value text-warning" id="kpiTotalRefunds">
                    -₹ {{ number_format($totalRevenue ?? 0, 0) }}
                </div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i>Fee Reversals &amp; Security
                </div>
            </div>
        </div>

        {{-- Card 4: Net In-Hand Revenue --}}
        <div class="report-kpi-card kpi-net-revenue">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Net In-Hand Revenue</div>
                <div class="kpi-value {{ ($grandTotal ?? 0) >= 0 ? 'text-navy' : 'text-danger' }}" id="kpiNetRevenue">
                    {{ ($grandTotal ?? 0) >= 0 ? '+' : '-' }}₹ {{ number_format(abs($grandTotal ?? 0), 0) }}
                </div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-scale-balanced me-1"></i>Collections − Expenses − Refunds
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Quick Filter Tabs (Pill style) --}}
    <div class="report-quick-tabs" id="quickTabs">
        <button type="button" class="quick-tab-btn active" data-tab="all">
            <i class="fa-solid fa-layer-group"></i> All Transactions
        </button>
        <button type="button" class="quick-tab-btn" data-tab="credit">
            <i class="fa-solid fa-arrow-down-left text-success"></i> Inflow (Collections)
        </button>
        <button type="button" class="quick-tab-btn" data-tab="expense">
            <i class="fa-solid fa-receipt text-danger"></i> Expenses
        </button>
        <button type="button" class="quick-tab-btn" data-tab="refund">
            <i class="fa-solid fa-rotate-left text-warning"></i> Refunds
        </button>
        <button type="button" class="quick-tab-btn" data-tab="cash">
            <i class="fa-solid fa-money-bill-wave text-warning"></i> Cash Mode
        </button>
        <button type="button" class="quick-tab-btn" data-tab="online">
            <i class="fa-solid fa-globe text-primary"></i> Online / UPI
        </button>
    </div>

    {{-- 4. Single-Line Collapsible Filter Drawer --}}
    <div class="report-filter-wrapper" id="reportFilterContainer" style="{{ !empty($hasCustomFilter) ? '' : 'display: none;' }}">
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

            {{-- Search --}}
            <div class="filter-col filter-col-search">
                <label for="filterSearch" class="filter-inline-label"><i class="fa-solid fa-magnifying-glass"></i> Search</label>
                <input type="text" class="form-control filter-control" id="filterSearch" name="search" placeholder="Learner name, seat, particulars..." value="{{ request('search') }}">
            </div>

            {{-- Actions --}}
            <div class="filter-col filter-col-actions">
                <span class="filter-inline-label" aria-hidden="true">&nbsp;</span>
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

    {{-- 5. Records Presentation --}}
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

        {{-- Desktop Column Header (Matching exactly with .collection-record-card) --}}
        <div class="records-header-row">
            <div>Date</div>
            <div>Learner &amp; Seat</div>
            <div>Particulars</div>
            <div class="text-center">Flow (Cr/Dr)</div>
            <div class="text-end">Amount</div>
            <div class="text-center">Mode</div>
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
                'grandTotal'          => $grandTotal
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

    // 2. Client-side Quick Tabs Switching
    quickTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            quickTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            activeTab = this.getAttribute('data-tab');
            applyClientFilters();
        });
    });

    // 3. Client-side Search Input
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

    // 4. Clear Search Button
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

    // Function to apply Tab + Search filter across all transaction cards and update day dividers
    function applyClientFilters() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const cards = document.querySelectorAll('.collection-record-card');
        const dayDividers = document.querySelectorAll('.date-group-divider');
        let totalVisibleTxns = 0;
        const visibleDates = new Set();

        cards.forEach(function (card) {
            const searchTokens = card.getAttribute('data-search') || '';
            const rowFlow = card.getAttribute('data-flow') || '';
            const rowTabCat = card.getAttribute('data-tab-cat') || '';
            const rowModeCat = card.getAttribute('data-mode-cat') || '';
            const dayRef = card.getAttribute('data-day-ref') || '';

            // Check Tab matching
            let matchesTab = false;
            if (activeTab === 'all') {
                matchesTab = true;
            } else if (activeTab === 'credit' && rowFlow === 'credit') {
                matchesTab = true;
            } else if (activeTab === 'expense' && rowTabCat === 'expense') {
                matchesTab = true;
            } else if (activeTab === 'refund' && rowTabCat === 'refund') {
                matchesTab = true;
            } else if (activeTab === 'cash' && rowModeCat === 'cash') {
                matchesTab = true;
            } else if (activeTab === 'online' && rowModeCat === 'online') {
                matchesTab = true;
            }

            // Check Search matching
            const matchesSearch = query === '' || searchTokens.includes(query);

            if (matchesTab && matchesSearch) {
                card.style.display = '';
                totalVisibleTxns++;
                if (dayRef) visibleDates.add(dayRef);
            } else {
                card.style.display = 'none';
            }
        });

        // Show/hide day dividers based on whether that day has matching records
        dayDividers.forEach(function (divider) {
            const day = divider.getAttribute('data-day-group');
            if (visibleDates.has(day)) {
                divider.style.display = '';
            } else {
                divider.style.display = 'none';
            }
        });

        if (visibleCountBadge) {
            visibleCountBadge.textContent = totalVisibleTxns;
        }

        if (searchEmptyState) {
            if (totalVisibleTxns === 0 && cards.length > 0) {
                searchEmptyState.classList.remove('d-none');
            } else {
                searchEmptyState.classList.add('d-none');
            }
        }
    }

    // 5. AJAX Form Submission for Filter Drawer
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

                // Update KPI Cards
                if (data.metrics) {
                    const m = data.metrics;
                    const kpiCol = document.getElementById('kpiTotalCollections');
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
                // Fallback to normal submission
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
