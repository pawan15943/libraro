@extends('layouts.library')

@section('title', 'Monthly Revenue Report')

@section('content')

{{-- Dedicated Scoped Stylesheet for Monthly Revenue Report --}}
<link rel="stylesheet" href="{{ asset('public/css/monthly-revenue-report.css') }}?v={{ time() }}" />

<div class="monthly-revenue-module">

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

    @can('has-permission','Monthly Revenue Report')

    @php
        $currentYear = date('Y');
        $sumTotalRevenue = 0;
        $sumTotalExpenses = 0;
        $sumMonthlyRevenue = 0;
        $totalRecords = 0;
        $profitableCount = 0;
        $deficitCount = 0;
        $currentYearCount = 0;

        if (isset($reportData) && is_iterable($reportData)) {
            foreach ($reportData as $row) {
                if (!empty($row['total_revenue']) || !empty($row['total_expenses']) || !empty($row['monthly_revenue'])) {
                    $totalRecords++;
                    $rev = floatval($row['total_revenue'] ?? 0);
                    $exp = floatval($row['total_expenses'] ?? 0);
                    $sumTotalRevenue += $rev;
                    $sumTotalExpenses += $exp;
                    $sumMonthlyRevenue += floatval($row['monthly_revenue'] ?? 0);
                    
                    if (($rev - $exp) >= 0) {
                        $profitableCount++;
                    } else {
                        $deficitCount++;
                    }

                    if (($row['year'] ?? '') == $currentYear) {
                        $currentYearCount++;
                    }
                }
            }
        }
        $netProfit = $sumTotalRevenue - $sumTotalExpenses;
    @endphp

    {{-- 1. TOP ACTION BAR (RIGHT-ALIGNED, NO REDUNDANT HEADING) --}}
    <div class="heading-list py-1 d-flex justify-content-end align-items-center gap-2 mb-3">
        <div class="header-actions">
            <a href="{{ route('add.expense.list') }}" class="btn btn-manage-expenses-top" title="Manage Monthly Expenses">
                <i class="fa-solid fa-receipt"></i> Manage Expenses
            </a>
            <button type="button" class="btn btn-export-csv" id="btnExportReportCsv" title="Download report in CSV">
                <i class="fa-solid fa-file-csv"></i> Export CSV
            </button>
        </div>
    </div>

    {{-- 2. KPI SUMMARY CARDS (ADAPTIVE 2x2 ON MOBILE < 768px) --}}
    <div class="report-kpi-grid">
        {{-- KPI 1: Total Revenue (A) --}}
        <div class="report-kpi-card kpi-rev">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Total Revenue</div>
                <div class="kpi-value" style="color: #18225f;">₹{{ number_format($sumTotalRevenue, 2) }}</div>
                <div class="kpi-sub">Total Collections (A)</div>
            </div>
        </div>

        {{-- KPI 2: Total Expenses (B) --}}
        <div class="report-kpi-card kpi-exp">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-arrow-trend-down"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Total Expenses</div>
                <div class="kpi-value text-danger">₹{{ number_format($sumTotalExpenses, 2) }}</div>
                <div class="kpi-sub">Recorded Outflows (B)</div>
            </div>
        </div>

        {{-- KPI 3: Net Profit (A - B) --}}
        <div class="report-kpi-card kpi-profit">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Net Profit</div>
                <div class="kpi-value {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                    ₹{{ number_format($netProfit, 2) }}
                </div>
                <div class="kpi-sub">Collections minus Expenses</div>
            </div>
        </div>

        {{-- KPI 4: Monthly Revenue (C) --}}
        <div class="report-kpi-card kpi-monthly">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Monthly Revenue</div>
                <div class="kpi-value" style="color: #34939F;">₹{{ number_format($sumMonthlyRevenue, 2) }}</div>
                <div class="kpi-sub">Amortized Plan Value (C)</div>
            </div>
        </div>
    </div>

    {{-- 3. QUICK FILTER TABS --}}
    <div class="report-quick-tabs">
        <button type="button" class="quick-tab-btn active" data-tab="all" id="tabAll">
            All Months <span class="tab-count" id="tabCountAll">{{ $totalRecords }}</span>
        </button>
        <button type="button" class="quick-tab-btn tab-profit" data-tab="profit" id="tabProfit">
            <i class="fa-solid fa-arrow-trend-up text-success"></i> Profitable
            <span class="tab-count" id="tabCountProfit">{{ $profitableCount }}</span>
        </button>
        <button type="button" class="quick-tab-btn tab-loss" data-tab="loss" id="tabLoss">
            <i class="fa-solid fa-arrow-trend-down text-danger"></i> Deficit / Loss
            <span class="tab-count" id="tabCountLoss">{{ $deficitCount }}</span>
        </button>
        <button type="button" class="quick-tab-btn tab-curr-year" data-tab="current_year" id="tabCurrYear">
            <i class="fa-regular fa-calendar"></i> Year {{ $currentYear }}
            <span class="tab-count" id="tabCountCurrYear">{{ $currentYearCount }}</span>
        </button>
    </div>

    {{-- 4. RECORDS WRAPPER & MODERN CARD / GRID PRESENTATION --}}
    <div class="records-wrapper">
        <div class="records-controls-bar">
            <div class="records-count-info">
                <span>Monthly Records:</span>
                <span class="records-count-badge" id="visibleCountBadge">{{ $totalRecords }}</span>
                <span class="text-muted small">months</span>
            </div>
            <div class="records-search-box">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="cardSearchInput" placeholder="Search month, year..." autocomplete="off" />
                <button type="button" class="btn-clear-search d-none" id="clearSearchBtn">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        {{-- Desktop Column Header (Visible on Desktop >= 992px) --}}
        <div class="records-header-row">
            <div>Month &amp; Year</div>
            <div class="text-center">Total Revenue (A)</div>
            <div class="text-center">Total Expenses (B)</div>
            <div class="text-center">Net Profit (A - B)</div>
            <div class="text-center">Monthly Revenue (C)</div>
            <div class="text-center">Action</div>
        </div>

        {{-- Records Container --}}
        <div class="collection-records-grid" id="monthlyRevenueGridContainer">
            @if(isset($reportData) && !empty($reportData))
                @php $mIdx = 1; @endphp
                @foreach ($reportData as $key => $value)
                    @php
                        $dt = null;
                        if (!empty($value['month'])) {
                            $dt = DateTime::createFromFormat('!m', $value['month']);
                        }
                        $monthName = $dt ? $dt->format('F') : 'Month ' . ($value['month'] ?? '');
                        $totRev = floatval($value['total_revenue'] ?? 0);
                        $totExp = floatval($value['total_expenses'] ?? 0);
                        $netA = $totRev - $totExp;
                        $monRev = floatval($value['monthly_revenue'] ?? 0);
                        $netC = $monRev - $totExp;
                        $yr = $value['year'] ?? date('Y');
                        $isProfit = ($netA >= 0);
                        $isCurrYear = ($yr == $currentYear);
                        $searchData = strtolower($monthName . ' ' . $yr . ' ' . ($isProfit ? 'profit profitable' : 'loss deficit'));
                    @endphp

                    <div class="collection-record-card"
                         data-search="{{ $searchData }}"
                         data-month="{{ $monthName }}"
                         data-year="{{ $yr }}"
                         data-is-profit="{{ $isProfit ? '1' : '0' }}"
                         data-is-loss="{{ !$isProfit ? '1' : '0' }}"
                         data-is-curryear="{{ $isCurrYear ? '1' : '0' }}"
                         data-rev="{{ $totRev }}"
                         data-exp="{{ $totExp }}"
                         data-net="{{ $netA }}"
                         data-monthly="{{ $monRev }}">

                        {{-- Mobile-Only Top Bar (< 992px) --}}
                        <div class="d-flex d-lg-none align-items-center justify-content-between flex-wrap gap-2 pb-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-regular fa-calendar" style="color: #18225f;"></i>
                                <span class="fw-bold" style="color: #18225f; font-size: 0.94rem;">{{ $monthName }} {{ $yr }}</span>
                            </div>
                            <span class="status-pill {{ $isProfit ? 'pill-profit' : 'pill-loss' }}">
                                <i class="fa-solid {{ $isProfit ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                                {{ $isProfit ? 'Profit' : 'Deficit' }}
                            </span>
                        </div>

                        {{-- Col 1: Month & Year Info --}}
                        <div class="record-col-month">
                            <div class="record-month-avatar">
                                <i class="fa-regular fa-calendar-days"></i>
                            </div>
                            <div class="record-month-text">
                                <span class="record-month-name">{{ $monthName }} {{ $yr }}</span>
                                <div class="record-month-tag">
                                    <span>#{{ $mIdx++ }} &bull; Fiscal Period</span>
                                </div>
                            </div>
                        </div>

                        {{-- Col 2: Total Revenue (A) --}}
                        <div class="record-col-rev d-none d-lg-block">
                            <div class="val-main text-revenue">₹ {{ number_format($totRev, 2) }}</div>
                            <div class="val-sub">Collections (A)</div>
                        </div>

                        {{-- Col 3: Total Expenses (B) --}}
                        <div class="record-col-exp d-none d-lg-block">
                            <div class="val-main text-expense">₹ {{ number_format($totExp, 2) }}</div>
                            <div class="val-sub">Expenses (B)</div>
                        </div>

                        {{-- Col 4: Net Profit (A - B) --}}
                        <div class="record-col-profit d-none d-lg-block">
                            <span class="status-pill {{ $isProfit ? 'pill-profit' : 'pill-loss' }}">
                                <i class="fa-solid {{ $isProfit ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                                {{ $isProfit ? '+' : '' }}₹ {{ number_format($netA, 2) }}
                            </span>
                            <div class="val-sub">Net Profit (A - B)</div>
                        </div>

                        {{-- Col 5: Monthly Revenue (C) --}}
                        <div class="record-col-monthly d-none d-lg-block">
                            <div class="val-main" style="color: #34939F;">₹ {{ number_format($monRev, 2) }}</div>
                            <div class="val-sub">Net: ₹ {{ number_format($netC, 2) }}</div>
                        </div>

                        {{-- Mobile Financials Strip (< 992px) --}}
                        <div class="record-card-financials d-grid d-lg-none">
                            <div class="fin-item">
                                <span class="fin-label">Revenue (A)</span>
                                <span class="fin-value text-revenue">₹{{ number_format($totRev, 0) }}</span>
                            </div>
                            <div class="fin-item">
                                <span class="fin-label">Expenses (B)</span>
                                <span class="fin-value text-expense">₹{{ number_format($totExp, 0) }}</span>
                            </div>
                            <div class="fin-item">
                                <span class="fin-label">Net Profit</span>
                                <span class="fin-value {{ $isProfit ? 'text-success' : 'text-danger' }}">
                                    ₹{{ number_format($netA, 0) }}
                                </span>
                            </div>
                        </div>

                        {{-- Col 6: Actions --}}
                        <div class="record-col-actions">
                            <a href="{{ route('add.expense.list') }}" class="btn-card-receipt" data-bs-toggle="tooltip" title="Manage Monthly Expenses">
                                <i class="fa-solid fa-receipt"></i>
                            </a>
                            @if(!empty($value['year']) && !empty($value['month']))
                                <a href="{{ route('report.expense', ['year' => $value['year'], 'month' => $value['month']]) }}" class="btn-card-profile" data-bs-toggle="tooltip" title="View Expense Breakdown">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            @endif
                        </div>

                    </div>
                @endforeach
            @endif
        </div>

        {{-- Empty Search State --}}
        <div class="report-empty-state d-none" id="searchEmptyState">
            <i class="fa-solid fa-folder-open empty-state-icon"></i>
            <h6 class="empty-state-title">No Matching Records Found</h6>
            <p class="small text-muted mb-0">No monthly revenue records matched your search query or selected tab.</p>
        </div>

        {{-- 5. Pagination Bar --}}
        <div class="records-pagination-wrapper" id="paginationWrapper">
            <div class="pagination-info" id="paginationInfoText">
                Showing 1 to 10 of {{ $totalRecords }} records
            </div>
            <nav class="pagination-nav">
                <ul class="pagination mb-0" id="paginationList">
                    {{-- Generated by JS --}}
                </ul>
            </nav>
        </div>
    </div>

    @else
    <div class="card text-center py-5 border-0 shadow-sm" style="border-radius: 12px;">
        <i class="fa-solid fa-lock text-danger fs-1 mb-3"></i>
        <h5 class="fw-bold" style="color: #18225f;">Permission Restricted</h5>
        <p class="text-muted mb-0">You don't have permission to view the Monthly Revenue Report. Please contact your administrator.</p>
    </div>
    @endcan

</div>

{{-- CLIENT-SIDE PAGINATION, SEARCH, AND CSV EXPORT --}}
<script>
$(document).ready(function () {
    var activeTab = 'all';
    var PAGE_SIZE = 10;
    var currentPage = 1;
    var $allCards = $('.collection-record-card');

    // 1. Quick Tabs Switching
    $('.quick-tab-btn').on('click', function () {
        $('.quick-tab-btn').removeClass('active');
        $(this).addClass('active');
        activeTab = $(this).attr('data-tab');
        currentPage = 1;
        renderPagination();
    });

    // 2. Filter Cards based on Tab & Live Instant Search
    function getFilteredCards() {
        var query = $('#cardSearchInput').val().toLowerCase().trim();

        return $allCards.filter(function () {
            var $c = $(this);

            // Tab Filter Check
            if (activeTab === 'profit' && $c.attr('data-is-profit') !== '1') {
                return false;
            }
            if (activeTab === 'loss' && $c.attr('data-is-loss') !== '1') {
                return false;
            }
            if (activeTab === 'current_year' && $c.attr('data-is-curryear') !== '1') {
                return false;
            }

            // Search Query Check
            if (query) {
                var searchData = $c.attr('data-search') || '';
                return searchData.indexOf(query) !== -1;
            }

            return true;
        });
    }

    // 3. Render Pagination and Cards Visibility
    function renderPagination() {
        var $matching = getFilteredCards();
        var totalMatching = $matching.length;
        var totalPages = Math.ceil(totalMatching / PAGE_SIZE) || 1;

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        $('#visibleCountBadge').text(totalMatching);

        // Hide all cards using both class and inline style
        $allCards.addClass('d-none').attr('style', 'display: none !important;');

        if (totalMatching === 0) {
            $('#searchEmptyState').removeClass('d-none').attr('style', 'display: block !important;');
            $('#paginationWrapper').attr('style', 'display: none !important;');
            return;
        } else {
            $('#searchEmptyState').addClass('d-none').attr('style', 'display: none !important;');
            $('#paginationWrapper').attr('style', 'display: flex !important;');
        }

        var startIndex = (currentPage - 1) * PAGE_SIZE;
        var endIndex = startIndex + PAGE_SIZE;

        // Show matching cards on active page
        $matching.slice(startIndex, endIndex).removeClass('d-none').removeAttr('style');

        var endDisplay = Math.min(endIndex, totalMatching);
        $('#paginationInfoText').text('Showing ' + (startIndex + 1) + ' to ' + endDisplay + ' of ' + totalMatching + ' records');

        var $paginationList = $('#paginationList');
        $paginationList.empty();

        if (totalPages <= 1) {
            return;
        }

        // Previous button
        var prevDisabled = (currentPage === 1) ? 'disabled' : '';
        $paginationList.append('<li class="page-item ' + prevDisabled + '"><a class="page-link" href="javascript:void(0);" data-page="' + (currentPage - 1) + '"><i class="fa-solid fa-chevron-left"></i></a></li>');

        var maxPagesToShow = 5;
        var startPage = Math.max(1, currentPage - 2);
        var endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
        if (endPage - startPage < maxPagesToShow - 1) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }

        if (startPage > 1) {
            $paginationList.append('<li class="page-item"><a class="page-link" href="javascript:void(0);" data-page="1">1</a></li>');
            if (startPage > 2) {
                $paginationList.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
            }
        }

        for (var p = startPage; p <= endPage; p++) {
            var activeClass = (p === currentPage) ? 'active' : '';
            $paginationList.append('<li class="page-item ' + activeClass + '"><a class="page-link" href="javascript:void(0);" data-page="' + p + '">' + p + '</a></li>');
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                $paginationList.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
            }
            $paginationList.append('<li class="page-item"><a class="page-link" href="javascript:void(0);" data-page="' + totalPages + '">' + totalPages + '</a></li>');
        }

        // Next button
        var nextDisabled = (currentPage === totalPages) ? 'disabled' : '';
        $paginationList.append('<li class="page-item ' + nextDisabled + '"><a class="page-link" href="javascript:void(0);" data-page="' + (currentPage + 1) + '"><i class="fa-solid fa-chevron-right"></i></a></li>');
    }

    renderPagination();

    // Page link click handler
    $(document).on('click', '#paginationList .page-link', function (e) {
        e.preventDefault();
        var targetPage = parseInt($(this).attr('data-page'));
        if (targetPage && targetPage !== currentPage) {
            var totalPages = Math.ceil(getFilteredCards().length / PAGE_SIZE) || 1;
            if (targetPage >= 1 && targetPage <= totalPages) {
                currentPage = targetPage;
                renderPagination();
                $('html, body').animate({
                    scrollTop: $('.records-wrapper').offset().top - 80
                }, 150);
            }
        }
    });

    // 4. Live Search Input
    $('#cardSearchInput').on('input keyup', function () {
        var val = $(this).val().toLowerCase().trim();
        if (val.length > 0) {
            $('#clearSearchBtn').removeClass('d-none');
        } else {
            $('#clearSearchBtn').addClass('d-none');
        }
        currentPage = 1;
        renderPagination();
    });

    $('#clearSearchBtn').on('click', function () {
        $('#cardSearchInput').val('').trigger('input');
    });

    // 5. Export CSV
    $('#btnExportReportCsv').on('click', function () {
        var rows = [];
        var headers = ['Month', 'Year', 'Total Revenue (A)', 'Total Expenses (B)', 'Net Profit (A - B)', 'Monthly Revenue (C)'];
        rows.push(headers.map(function (h) { return '"' + h.replace(/"/g, '""') + '"'; }).join(','));

        var $exportCards = getFilteredCards();
        if ($exportCards.length === 0) $exportCards = $allCards;

        $exportCards.each(function () {
            var $c = $(this);
            var row = [
                $c.attr('data-month') || '',
                $c.attr('data-year') || '',
                parseFloat($c.attr('data-rev') || 0).toFixed(2),
                parseFloat($c.attr('data-exp') || 0).toFixed(2),
                parseFloat($c.attr('data-net') || 0).toFixed(2),
                parseFloat($c.attr('data-monthly') || 0).toFixed(2)
            ];
            rows.push(row.map(function (val) { return '"' + String(val).replace(/"/g, '""') + '"'; }).join(','));
        });

        var csvContent = "\uFEFF" + rows.join("\r\n");
        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", "Monthly_Revenue_Report_" + new Date().toISOString().slice(0, 10) + ".csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>

@endsection