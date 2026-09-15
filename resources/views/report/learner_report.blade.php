@extends('layouts.library')

@section('title', 'Learner Master Report')

@section('content')

@php
    use Carbon\Carbon;
    $currentYear = date('Y');
    $currentMonth = date('m');
    $hasCustomFilter = !empty($filters['year']) || !empty($filters['month']) || isset($filters['is_paid']) || isset($filters['status']) || !empty($filters['search']);
@endphp

{{-- Dedicated Scoped Stylesheet for Learner Report --}}
<link rel="stylesheet" href="{{ asset('public/css/learner-report.css') }}?v={{ time() }}" />

<div class="learner-report-module">

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

    @can('has-permission', 'Learner Report')

    {{-- 1. Top Action Buttons Bar (Heading removed as per GEMINI.md standard) --}}
    <div class="heading-list py-1 d-flex justify-content-end align-items-center gap-2 mb-3">
        <div class="header-actions">
            {{-- Filter Toggle Button --}}
            <button type="button" class="btn btn-filter-toggle {{ $hasCustomFilter ? 'active' : '' }}" id="toggleFilterBtn" title="Show/Hide Filter Drawer">
                <i class="fa-solid fa-filter"></i>
                <span>Filters</span>
                @if($hasCustomFilter)
                    <span class="filter-badge-dot" title="Active Filter Applied"></span>
                @endif
            </button>

            <button type="button" class="btn btn-export-csv" id="btnExportReportCsv" title="Download report in CSV">
                <i class="fa-solid fa-file-csv"></i> Export CSV
            </button>

            <button type="button" class="btn btn-report-print" onclick="window.print()" title="Print this report">
                <i class="fa-solid fa-print"></i> Print
            </button>
        </div>
    </div>

    {{-- 2. 4 FINANCIAL & OPERATIONAL SUMMARY KPI CARDS --}}
    <div class="report-kpi-grid">
        {{-- Card 1: Today's Daily Collection --}}
        <div class="report-kpi-card kpi-today">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-calendar-day"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Today's Collection</div>
                <div class="kpi-value text-success" id="kpiTodayCollection">
                    ₹ {{ number_format($metrics['today_collection'] ?? 0, 2) }}
                </div>
                <div class="kpi-sub" id="kpiTodaySub">
                    <i class="fa-solid fa-bolt me-1"></i>{{ $metrics['today_count'] ?? 0 }} payments collected today
                </div>
            </div>
        </div>

        {{-- Card 2: Monthly Collection --}}
        <div class="report-kpi-card kpi-month">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label" id="kpiMonthLabel">{{ $metrics['filter_month_name'] ?? 'Monthly' }} Collection</div>
                <div class="kpi-value" style="color: #18225f;" id="kpiMonthlyCollection">
                    ₹ {{ number_format($metrics['monthly_collection'] ?? 0, 2) }}
                </div>
                <div class="kpi-sub" id="kpiMonthlySub">
                    <span>Online: <strong>₹{{ number_format($metrics['monthly_online'] ?? 0, 0) }}</strong></span> &bull;
                    <span>Offline: <strong>₹{{ number_format($metrics['monthly_offline'] ?? 0, 0) }}</strong></span>
                </div>
            </div>
        </div>

        {{-- Card 3: Total Pending Dues --}}
        <div class="report-kpi-card kpi-dues">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Pending Dues</div>
                <div class="kpi-value text-danger" id="kpiPendingDue">
                    ₹ {{ number_format($metrics['total_pending_due'] ?? 0, 2) }}
                </div>
                <div class="kpi-sub" id="kpiPendingSub">
                    <i class="fa-solid fa-users-viewfinder me-1"></i>{{ $metrics['pending_dues_count'] ?? 0 }} learners with dues
                </div>
            </div>
        </div>

        {{-- Card 4: Active Occupancy --}}
        <div class="report-kpi-card kpi-active">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Active Learners</div>
                <div class="kpi-value" style="color: #34939F;" id="kpiActiveCount">
                    {{ number_format($metrics['active_count'] ?? 0) }} Active
                </div>
                <div class="kpi-sub" id="kpiActiveSub">
                    <span>Total: <strong id="kpiTotalLearners">{{ number_format($metrics['total_learners'] ?? count($learners)) }}</strong> registered</span>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. QUICK FILTER TABS (INSTANT COLLECTION & STATUS VIEWS) --}}
    <div class="report-quick-tabs">
        <button type="button" class="quick-tab-btn active" data-tab="all" id="tabAll">
            All Learners <span class="tab-count" id="tabCountAll">{{ count($learners) }}</span>
        </button>
        <button type="button" class="quick-tab-btn tab-today" data-tab="today" id="tabToday">
            <i class="fa-solid fa-bolt"></i> Collected Today
            <span class="tab-count">₹ {{ number_format($metrics['today_collection'] ?? 0, 0) }}</span>
        </button>
        <button type="button" class="quick-tab-btn tab-month" data-tab="month" id="tabMonth">
            <i class="fa-solid fa-calendar-days"></i> {{ $metrics['filter_month_name'] ?? 'This Month' }}
            <span class="tab-count">₹ {{ number_format($metrics['monthly_collection'] ?? 0, 0) }}</span>
        </button>
        <button type="button" class="quick-tab-btn tab-dues" data-tab="dues" id="tabDues">
            <i class="fa-solid fa-triangle-exclamation"></i> Pending Dues
            <span class="tab-count">₹ {{ number_format($metrics['total_pending_due'] ?? 0, 0) }}</span>
        </button>
        <button type="button" class="quick-tab-btn tab-active" data-tab="active" id="tabActive">
            <i class="fa-solid fa-circle-check"></i> Active
            <span class="tab-count" id="tabCountActive">{{ $metrics['active_count'] ?? 0 }}</span>
        </button>
        <button type="button" class="quick-tab-btn tab-expired" data-tab="expired" id="tabExpired">
            <i class="fa-solid fa-clock"></i> Expired
            <span class="tab-count" id="tabCountExpired">{{ $metrics['expired_count'] ?? 0 }}</span>
        </button>
    </div>

    {{-- 4. Single-Line Collapsible Filter Drawer --}}
    <div class="report-filter-wrapper" id="reportFilterContainer" style="{{ $hasCustomFilter ? '' : 'display: none;' }}">
        <form action="{{ route('learner.report') }}" method="GET" id="reportFilterForm" class="single-line-filter-form">
            {{-- Year --}}
            <div class="filter-col">
                <label for="year" class="filter-inline-label"><i class="fa-regular fa-calendar"></i> Year</label>
                <select id="year" class="form-select filter-control" name="year">
                    <option value="">All Years</option>
                    @foreach($months as $yr => $monthData)
                        <option value="{{ $yr }}" {{ ((request('year') ?? ($metrics['filter_year'] ?? '')) == $yr) ? 'selected' : '' }}>
                            {{ $yr }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Month --}}
            <div class="filter-col">
                <label for="month" class="filter-inline-label"><i class="fa-regular fa-calendar-days"></i> Month</label>
                <select id="month" class="form-select filter-control" name="month">
                    <option value="">All Months</option>
                    @php $selectedYear = request('year') ?? ($metrics['filter_year'] ?? $currentYear); @endphp
                    @if(isset($months[$selectedYear]))
                        @foreach($months[$selectedYear] as $monthNumber => $monthName)
                            <option value="{{ $monthNumber }}" {{ ((request('month') ?? ($metrics['filter_month'] ?? '')) == $monthNumber) ? 'selected' : '' }}>
                                {{ $monthName }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            {{-- Payment Status --}}
            <div class="filter-col">
                <label for="is_paid" class="filter-inline-label"><i class="fa-solid fa-wallet"></i> Payment</label>
                <select name="is_paid" id="is_paid" class="form-select filter-control">
                    <option value="">All Payments</option>
                    <option value="1" {{ request()->get('is_paid') === '1' ? 'selected' : '' }}>Paid Only</option>
                    <option value="0" {{ request()->get('is_paid') === '0' ? 'selected' : '' }}>Unpaid / Dues</option>
                </select>
            </div>

            {{-- Active / Expired Status --}}
            <div class="filter-col">
                <label for="status" class="filter-inline-label"><i class="fa-solid fa-toggle-on"></i> Plan Status</label>
                <select name="status" id="status" class="form-select filter-control">
                    <option value="">All Statuses</option>
                    <option value="1" {{ request()->get('status') === '1' ? 'selected' : '' }}>Active Members</option>
                    <option value="0" {{ request()->get('status') === '0' ? 'selected' : '' }}>Expired Members</option>
                </select>
            </div>

            {{-- Search --}}
            <div class="filter-col">
                <label for="filterSearch" class="filter-inline-label"><i class="fa-solid fa-magnifying-glass"></i> Search</label>
                <input type="text" class="form-control filter-control" id="filterSearch" name="search" placeholder="Learner name, seat, mobile..." value="{{ request()->get('search') }}">
            </div>

            {{-- Actions --}}
            <div class="filter-col filter-col-actions">
                <span class="filter-inline-label" aria-hidden="true">&nbsp;</span>
                <div class="filter-actions-inline">
                    <button type="submit" class="btn btn-filter-apply" id="btnApplyFilter">
                        <i class="fa-solid fa-magnifying-glass"></i> Filter
                    </button>
                    <a href="{{ route('learner.report') }}" class="btn btn-filter-reset" id="btnResetFilter">
                        <i class="fa-solid fa-arrow-rotate-left"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- 5. Records Presentation (Matching Screenshot UI) --}}
    <div class="records-wrapper">
        <div class="records-controls-bar">
            <div class="records-count-info">
                <span>Learners:</span>
                <span class="records-count-badge" id="visibleCountBadge">{{ count($learners) }}</span>
                <span class="text-muted small">records</span>
            </div>
            <div class="records-search-box">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="cardSearchInput" placeholder="Search learner, seat, mobile, plan..." autocomplete="off" />
                <button type="button" class="btn-clear-search d-none" id="clearSearchBtn">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        {{-- Desktop Column Header (Visible on Desktop >= 992px) --}}
        <div class="records-header-row">
            <div>Learner &amp; Seat</div>
            <div>Plan &amp; Duration</div>
            <div class="text-center">Financials (Bill / Paid / Due)</div>
            <div class="text-center">Collection Date &amp; Mode</div>
            <div class="text-center">Status</div>
            <div class="text-center">Action</div>
        </div>

        {{-- Records Container --}}
        <div class="collection-records-grid" id="learnerReportTableContainer">
            @include('report.partials.learner_report_table', ['learners' => $learners])
        </div>

        {{-- Empty Search State --}}
        <div class="report-empty-state d-none" id="searchEmptyState">
            <i class="fa-solid fa-magnifying-glass empty-state-icon"></i>
            <h6 class="empty-state-title">No Matching Records Found</h6>
            <p class="small text-muted mb-3">No learner records matched your selected tab or search query.</p>
        </div>

        {{-- 6. Pagination Bar --}}
        <div class="records-pagination-wrapper" id="paginationWrapper">
            <div class="pagination-info" id="paginationInfoText">
                Showing 1 to 10 of {{ count($learners) }} records
            </div>
            <nav class="pagination-nav">
                <ul class="pagination mb-0" id="paginationList">
                    {{-- Generated by JS --}}
                </ul>
            </nav>
        </div>
    </div>

    @else
    <div class="card text-center py-5">
        <span class="text-danger fw-semibold">You don't have Permission to view Learner Report.</span>
    </div>
    @endcan

</div>

<script>
$(document).ready(function () {
    // 1. Filter Drawer Toggle
    $('#toggleFilterBtn').on('click', function () {
        var $container = $('#reportFilterContainer');
        $container.slideToggle(200);
        $(this).toggleClass('active');
    });

    // Dynamic Year -> Month cascade
    var monthsData = @json($months);
    $('#year').on('change', function () {
        var selectedYear = $(this).val();
        var $monthSelect = $('#month');
        $monthSelect.empty().append('<option value="">All Months</option>');

        if (selectedYear && monthsData[selectedYear]) {
            $.each(monthsData[selectedYear], function (mNum, mName) {
                $monthSelect.append($('<option>', {
                    value: mNum,
                    text: mName
                }));
            });
        }
    });

    // 2. Active Tab State & Instant Tab Switching
    var activeTab = 'all';
    var PAGE_SIZE = 10;
    var currentPage = 1;
    var $allCards = $('.collection-record-card');

    $('.quick-tab-btn').on('click', function() {
        $('.quick-tab-btn').removeClass('active');
        $(this).addClass('active');
        activeTab = $(this).attr('data-tab');
        currentPage = 1;
        renderPagination();
    });

    // 3. Filter Cards based on Tab & Live Instant Search
    function getFilteredCards() {
        var query = $('#cardSearchInput').val().toLowerCase().trim();
        
        return $allCards.filter(function() {
            var $c = $(this);

            // Tab Filter Check
            if (activeTab === 'today' && $c.attr('data-is-today') !== '1') {
                return false;
            }
            if (activeTab === 'month' && $c.attr('data-is-month') !== '1') {
                return false;
            }
            if (activeTab === 'dues' && $c.attr('data-has-due') !== '1') {
                return false;
            }
            if (activeTab === 'active' && $c.attr('data-status') !== 'active') {
                return false;
            }
            if (activeTab === 'expired' && $c.attr('data-status') !== 'expired') {
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

    function renderPagination() {
        var $matching = getFilteredCards();
        var totalMatching = $matching.length;
        var totalPages = Math.ceil(totalMatching / PAGE_SIZE) || 1;

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        $('#visibleCountBadge').text(totalMatching);

        // Hide all cards first using d-none
        $allCards.addClass('d-none');

        if (totalMatching === 0) {
            $('#searchEmptyState').removeClass('d-none');
            $('#paginationWrapper').hide();
            return;
        } else {
            $('#searchEmptyState').addClass('d-none');
            $('#paginationWrapper').show();
        }

        var startIndex = (currentPage - 1) * PAGE_SIZE;
        var endIndex = startIndex + PAGE_SIZE;

        // Show matching cards on active page
        $matching.slice(startIndex, endIndex).removeClass('d-none');

        var endDisplay = Math.min(endIndex, totalMatching);
        $('#paginationInfoText').text('Showing ' + (startIndex + 1) + ' to ' + endDisplay + ' of ' + totalMatching + ' records');

        var $paginationList = $('#paginationList');
        $paginationList.empty();

        if (totalPages <= 1) {
            return;
        }

        // Previous
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

        // Next
        var nextDisabled = (currentPage === totalPages) ? 'disabled' : '';
        $paginationList.append('<li class="page-item ' + nextDisabled + '"><a class="page-link" href="javascript:void(0);" data-page="' + (currentPage + 1) + '"><i class="fa-solid fa-chevron-right"></i></a></li>');
    }

    renderPagination();

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

    // 4. Export CSV
    $('#btnExportReportCsv').on('click', function() {
        var rows = [];
        var headers = ['Seat No', 'Learner Name', 'Mobile', 'Email', 'Plan Name', 'Slot', 'Start Date', 'End Date', 'Total Bill', 'Paid Amount', 'Pending Due', 'Collection Date', 'Status'];
        rows.push(headers.map(function(h) { return '"' + h.replace(/"/g, '""') + '"'; }).join(','));

        var $exportCards = getFilteredCards();
        if ($exportCards.length === 0) $exportCards = $allCards;

        $exportCards.each(function() {
            var $c = $(this);
            var row = [
                $c.attr('data-seat') || '',
                $c.attr('data-name') || '',
                $c.attr('data-mobile') || '',
                $c.attr('data-email') || '',
                $c.attr('data-plan') || '',
                $c.attr('data-slot') || '',
                $c.attr('data-start') || '',
                $c.attr('data-end') || '',
                $c.attr('data-total') || '0',
                $c.attr('data-paid') || '0',
                $c.attr('data-pending') || '0',
                $c.attr('data-date') || '',
                $c.attr('data-status') || ''
            ];
            rows.push(row.map(function(val) { return '"' + String(val).replace(/"/g, '""') + '"'; }).join(','));
        });

        var csvContent = "\uFEFF" + rows.join("\r\n");
        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", "Learner_Master_Report_" + new Date().toISOString().slice(0, 10) + ".csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // 5. AJAX Filter Submission
    $('#reportFilterForm').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        var url = $form.attr('action');
        var formData = $form.serialize();

        var $btn = $('#btnApplyFilter');
        var originalBtnHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Filtering...');
        $('#learnerReportTableContainer').css('opacity', '0.5');

        $.ajax({
            url: url,
            type: 'GET',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (res && res.html) {
                    $('#learnerReportTableContainer').html(res.html);

                    if (res.metrics) {
                        $('#kpiTodayCollection').text('₹ ' + parseFloat(res.metrics.today_collection || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                        $('#kpiTodaySub').html('<i class="fa-solid fa-bolt me-1"></i>' + (res.metrics.today_count || 0) + ' payments collected today');

                        $('#kpiMonthLabel').text((res.metrics.filter_month_name || 'Monthly') + ' Collection');
                        $('#kpiMonthlyCollection').text('₹ ' + parseFloat(res.metrics.monthly_collection || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                        $('#kpiMonthlySub').html('<span>Online: <strong>₹' + parseFloat(res.metrics.monthly_online || 0).toLocaleString('en-IN') + '</strong></span> &bull; <span>Offline: <strong>₹' + parseFloat(res.metrics.monthly_offline || 0).toLocaleString('en-IN') + '</strong></span>');

                        $('#kpiPendingDue').text('₹ ' + parseFloat(res.metrics.total_pending_due || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                        $('#kpiPendingSub').html('<i class="fa-solid fa-users-viewfinder me-1"></i>' + (res.metrics.pending_dues_count || 0) + ' learners with dues');

                        $('#kpiActiveCount').text((res.metrics.active_count || 0) + ' Active');
                        $('#kpiTotalLearners').text((res.metrics.total_learners || 0));

                        $('#tabCountAll').text(res.total_count || 0);
                        $('#tabCountActive').text(res.metrics.active_count || 0);
                        $('#tabCountExpired').text(res.metrics.expired_count || 0);
                    }

                    $allCards = $('.collection-record-card');
                    currentPage = 1;
                    renderPagination();

                    window.history.replaceState({}, '', url + '?' + formData);
                }
            },
            error: function () {
                window.location.href = url + '?' + formData;
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalBtnHtml);
                $('#learnerReportTableContainer').css('opacity', '1');
            }
        });
    });
});
</script>

@endsection