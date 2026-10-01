@extends('layouts.library')

@section('title', 'Partial Payment Collection Report')

@section('content')

@php
    use Carbon\Carbon;
    $currentYear = date('Y');
    $currentMonth = date('m');
    $hasCustomFilter = (!empty($year) && $year !== 'all') || (!empty($month) && $month !== 'all') || (!empty($due_status) && $due_status !== 'all');
@endphp

{{-- Dedicated Scoped Stylesheet for Partial Payment Collection Report --}}
<link rel="stylesheet" href="{{ asset('public/css/payment-collection-report.css') }}?v={{ time() }}" />
<link rel="stylesheet" href="{{ asset('public/css/partial-payment-report.css') }}?v={{ time() }}" />

<div class="partial-payment-report-module">

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

    @can('has-permission', 'Partial Payment Report')

    {{-- 1. Top Action Buttons Bar --}}
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

            <button type="button" class="btn btn-export-csv" id="btnExportReportCsv" title="Download partial payment data in CSV">
                <i class="fa-solid fa-file-csv"></i> Export CSV
            </button>
        </div>
    </div>

    {{-- 2. Simple KPI Summary Cards --}}
    <div class="report-kpi-grid">
        {{-- Card 1: Total Pending Due --}}
        <div class="report-kpi-card kpi-pending">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-hand-holding-dollar"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Total Pending Due</div>
                <div class="kpi-value text-danger" id="kpiTotalPending">₹ {{ number_format($metrics['total_pending'] ?? 0, 2) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i>Unpaid Learner Dues
                </div>
            </div>
        </div>

        {{-- Card 2: Overdue Amount --}}
        <div class="report-kpi-card kpi-overdue-amt">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-calendar-xmark"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Overdue Amount</div>
                <div class="kpi-value text-warning" id="kpiOverdueAmt">₹ {{ number_format($metrics['overdue_amount'] ?? 0, 2) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i>Past Scheduled Due Date
                </div>
            </div>
        </div>

        {{-- Card 3: Overdue Accounts --}}
        <div class="report-kpi-card kpi-overdue-count">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-user-clock"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Overdue Learners</div>
                <div class="kpi-value" style="color: #18225f;" id="kpiOverdueCount">{{ number_format($metrics['overdue_count'] ?? 0) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-bell me-1"></i>Immediate Follow-up
                </div>
            </div>
        </div>

        {{-- Card 4: Total Due Records --}}
        <div class="report-kpi-card kpi-total">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Total Records</div>
                <div class="kpi-value" style="color: #34939F;" id="kpiTotalRecords">{{ number_format($metrics['total_count'] ?? 0) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-users me-1"></i>Active Fee Dues
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Single-Line Filter Bar --}}
    <div class="report-filter-wrapper" id="reportFilterContainer" style="{{ $hasCustomFilter ? '' : 'display: none;' }}">
        <form action="{{ route('partial.payment.collection.report') }}" method="GET" id="reportFilterForm" class="single-line-filter-form">
            {{-- 1. Year Filter --}}
            <div class="filter-col">
                <label for="filterYear" class="filter-inline-label"><i class="fa-regular fa-calendar"></i> Year</label>
                <select name="year" id="filterYear" class="form-select filter-control">
                    <option value="all">All Years</option>
                    @foreach($dynamicyears as $y)
                        <option value="{{ $y }}" {{ ((string)($year ?? '') === (string)$y) ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- 2. Month Filter --}}
            <div class="filter-col">
                <label for="filterMonth" class="filter-inline-label"><i class="fa-regular fa-calendar-days"></i> Month</label>
                <select name="month" id="filterMonth" class="form-select filter-control">
                    <option value="all">All Months</option>
                    @foreach($dynamicmonths as $m)
                        @php
                            $mPadded = str_pad($m, 2, '0', STR_PAD_LEFT);
                            $mName = DateTime::createFromFormat('!m', $m)->format('F');
                        @endphp
                        <option value="{{ $mPadded }}" {{ ((string)($month ?? '') === (string)$mPadded) ? 'selected' : '' }}>
                            {{ $mName }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- 3. Due Status Filter --}}
            <div class="filter-col">
                <label for="filterDueStatus" class="filter-inline-label"><i class="fa-solid fa-list-check"></i> Due Status</label>
                <select name="due_status" id="filterDueStatus" class="form-select filter-control">
                    <option value="all" {{ ($due_status ?? 'all') === 'all' ? 'selected' : '' }}>All Records</option>
                    <option value="overdue" {{ ($due_status ?? '') === 'overdue' ? 'selected' : '' }}>Overdue Only (Past Due Date)</option>
                    <option value="today" {{ ($due_status ?? '') === 'today' ? 'selected' : '' }}>Due Today</option>
                    <option value="upcoming" {{ ($due_status ?? '') === 'upcoming' ? 'selected' : '' }}>Upcoming Dues</option>
                    <option value="pending" {{ ($due_status ?? '') === 'pending' ? 'selected' : '' }}>Any Pending Balance (&gt; 0)</option>
                </select>
            </div>

            {{-- 4. Action Buttons --}}
            <div class="filter-col filter-col-actions">
                <span class="filter-inline-label filter-label-spacer" aria-hidden="true">&nbsp;</span>
                <div class="filter-actions-inline">
                    <button type="submit" class="btn btn-filter-apply" id="btnApplyFilter" title="Apply filter">
                        <i class="fa-solid fa-magnifying-glass"></i> Filter
                    </button>
                    <button type="button" class="btn btn-filter-reset" id="btnResetFilter" title="Reset all filters">
                        <i class="fa-solid fa-arrow-rotate-left"></i> Reset
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- 4. Card Row Data Presentation (Matching Screenshot UI) --}}
    <div class="records-wrapper">
        {{-- Search & Summary Bar --}}
        <div class="records-controls-bar">
            <div class="records-count-info">
                <span>Due Records:</span>
                <span class="records-count-badge" id="visibleCountBadge">{{ count($learners) }}</span>
                <span class="text-muted small">learners</span>
            </div>
            <div class="records-search-box">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="cardSearchInput" placeholder="Search learner, seat, mobile, status..." autocomplete="off" />
                <button type="button" class="btn-clear-search d-none" id="clearSearchBtn" title="Clear search">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        {{-- Desktop Column Header (Visible on Desktop >= 992px) --}}
        <div class="records-header-row">
            <div>Learner</div>
            <div>Fee Breakdown</div>
            <div class="text-center">Total Bill</div>
            <div class="text-center">Paid</div>
            <div class="text-center">Pending Due</div>
            <div class="text-center">Due Date</div>
            <div class="text-center">Mode</div>
            <div class="text-center">Action</div>
        </div>

        {{-- Records Container --}}
        <div class="collection-records-grid" id="recordsContainer">
            @include('report.partials.partial_collection_cards', ['learners' => $learners])
        </div>

        {{-- Live Search No-Results Placeholder --}}
        <div class="report-empty-state d-none" id="searchEmptyState">
            <i class="fa-solid fa-magnifying-glass empty-state-icon"></i>
            <h6 class="empty-state-title">No Matching Records Found</h6>
            <p class="small text-muted mb-3">
                No partial payment records matched your search query.
            </p>
        </div>

        {{-- 5. Pagination Bar --}}
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
    <div class="card text-center p-5 shadow-sm border-0 rounded-4">
        <div class="mb-3">
            <i class="fa-solid fa-lock text-danger fs-1"></i>
        </div>
        <h5 class="fw-bold text-navy" style="color: #18225f;">Access Restricted</h5>
        <p class="text-muted">You don't have permission to view the Partial Payment Report.</p>
    </div>
    @endcan

</div>

<script>
$(document).ready(function() {
    // 1. Toggle Filter Container
    $('#toggleFilterBtn').on('click', function(e) {
        e.preventDefault();
        var $filter = $('#reportFilterContainer');
        $filter.slideToggle(200, function() {
            if ($filter.is(':visible')) {
                $('#toggleFilterBtn').addClass('active');
            } else {
                var hasCustom = $('#toggleFilterBtn .filter-badge-dot').length > 0;
                if (!hasCustom) {
                    $('#toggleFilterBtn').removeClass('active');
                }
            }
        });
    });

    // 2. Pagination & Real-time Live Instant Search
    var PAGE_SIZE = 10;
    var currentPage = 1;
    var $allCards = $('.collection-record-card');

    function getFilteredCards() {
        var query = $('#cardSearchInput').val().toLowerCase().trim();
        if (!query) {
            return $allCards;
        }
        return $allCards.filter(function() {
            var searchData = $(this).attr('data-search') || '';
            return searchData.indexOf(query) !== -1;
        });
    }

    function renderPagination() {
        var $matching = getFilteredCards();
        var totalMatching = $matching.length;
        var totalPages = Math.ceil(totalMatching / PAGE_SIZE) || 1;

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        $allCards.addClass('d-none').attr('style', 'display: none !important;');

        if (totalMatching > 0) {
            var startIndex = (currentPage - 1) * PAGE_SIZE;
            var endIndex = startIndex + PAGE_SIZE;
            $matching.slice(startIndex, endIndex).removeClass('d-none').removeAttr('style');
            $('#searchEmptyState').addClass('d-none').attr('style', 'display: none !important;');
            $('#paginationWrapper').removeClass('d-none').attr('style', 'display: flex !important;');
        } else {
            $('#searchEmptyState').removeClass('d-none').attr('style', 'display: block !important;');
            $('#paginationWrapper').addClass('d-none').attr('style', 'display: none !important;');
        }

        $('#visibleCountBadge').text(totalMatching);
        var startRecord = totalMatching > 0 ? ((currentPage - 1) * PAGE_SIZE + 1) : 0;
        var endRecord = Math.min(currentPage * PAGE_SIZE, totalMatching);
        $('#paginationInfoText').text('Showing ' + startRecord + ' to ' + endRecord + ' of ' + totalMatching + ' records');

        var $list = $('#paginationList');
        $list.empty();

        if (totalPages <= 1) return;

        var prevDisabled = (currentPage === 1) ? ' disabled' : '';
        $list.append('<li class="page-item' + prevDisabled + '"><a class="page-link" href="#" data-page="' + (currentPage - 1) + '"><i class="fa-solid fa-chevron-left"></i></a></li>');

        var maxVisiblePages = 5;
        var startPage = Math.max(1, currentPage - 2);
        var endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        if (endPage - startPage < maxVisiblePages - 1) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        if (startPage > 1) {
            $list.append('<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>');
            if (startPage > 2) {
                $list.append('<li class="page-item disabled"><span class="page-link">&hellip;</span></li>');
            }
        }

        for (var p = startPage; p <= endPage; p++) {
            var activeClass = (p === currentPage) ? ' active' : '';
            $list.append('<li class="page-item ' + activeClass + '"><a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>');
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                $list.append('<li class="page-item disabled"><span class="page-link">&hellip;</span></li>');
            }
            $list.append('<li class="page-item"><a class="page-link" href="#" data-page="' + totalPages + '">' + totalPages + '</a></li>');
        }

        var nextDisabled = (currentPage === totalPages) ? ' disabled' : '';
        $list.append('<li class="page-item' + nextDisabled + '"><a class="page-link" href="#" data-page="' + (currentPage + 1) + '"><i class="fa-solid fa-chevron-right"></i></a></li>');
    }

    renderPagination();

    $(document).on('click', '#paginationList .page-link', function(e) {
        e.preventDefault();
        var page = parseInt($(this).attr('data-page'));
        if (page && page !== currentPage) {
            currentPage = page;
            renderPagination();
            var offset = $('#recordsContainer').offset();
            if (offset) {
                $('html, body').animate({ scrollTop: offset.top - 120 }, 150);
            }
        }
    });

    $('#cardSearchInput').on('input keyup', function() {
        var q = $(this).val().trim();
        if (q.length > 0) {
            $('#clearSearchBtn').removeClass('d-none');
        } else {
            $('#clearSearchBtn').addClass('d-none');
        }
        currentPage = 1;
        renderPagination();
    });

    $('#clearSearchBtn').on('click', function() {
        $('#cardSearchInput').val('').trigger('input');
    });

    // 3. Export CSV
    $('#btnExportReportCsv').on('click', function() {
        var rows = [];
        var headers = ['Seat No', 'Learner Name', 'Mobile', 'Total Bill', 'Paid Amount', 'Pending Due', 'Due Date', 'Payment Mode'];
        rows.push(headers.map(function(h) { return '"' + h.replace(/"/g, '""') + '"'; }).join(','));

        var $exportCards = getFilteredCards();
        if ($exportCards.length === 0) $exportCards = $allCards;

        $exportCards.each(function() {
            var $c = $(this);
            var row = [
                $c.attr('data-seat') || '',
                $c.attr('data-name') || '',
                $c.attr('data-mobile') || '',
                $c.attr('data-total') || '0',
                $c.attr('data-paid') || '0',
                $c.attr('data-pending') || '0',
                $c.attr('data-date') || '',
                $c.attr('data-mode') || ''
            ];
            rows.push(row.map(function(val) { return '"' + String(val).replace(/"/g, '""') + '"'; }).join(','));
        });

        var csvContent = "\uFEFF" + rows.join("\r\n");
        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", "Partial_Payment_Collection_Report_" + new Date().toISOString().slice(0, 10) + ".csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // 4. AJAX Filter Execution
    function executeAjaxFilter(formData) {
        var $container = $('#recordsContainer');
        var $applyBtn = $('#btnApplyFilter');
        var reportUrl = "{{ route('partial.payment.collection.report') }}?" + formData;

        $applyBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Filtering...');
        $container.css('opacity', '0.5');

        $.ajax({
            url: "{{ route('partial.payment.collection.report') }}",
            type: 'GET',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(response) {
                if (response && response.html) {
                    $container.html(response.html);

                    if (response.metrics) {
                        $('#kpiTotalPending').text('₹ ' + (response.metrics.total_pending || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#kpiOverdueAmt').text('₹ ' + (response.metrics.overdue_amount || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#kpiOverdueCount').text((response.metrics.overdue_count || 0).toLocaleString());
                        $('#kpiTotalRecords').text((response.metrics.total_count || 0).toLocaleString());
                    }

                    $allCards = $('.collection-record-card');
                    currentPage = 1;
                    renderPagination();

                    if (window.history && window.history.pushState) {
                        window.history.pushState(null, '', reportUrl);
                    }
                }
            },
            error: function() {
                window.location.href = reportUrl;
            },
            complete: function() {
                $container.css('opacity', '1');
                $applyBtn.prop('disabled', false).html('<i class="fa-solid fa-magnifying-glass"></i> Filter');
            }
        });
    }

    $('#reportFilterForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        executeAjaxFilter(formData);
    });

    $('#btnResetFilter').on('click', function(e) {
        e.preventDefault();
        $('#filterYear').val('all');
        $('#filterMonth').val('all');
        $('#filterDueStatus').val('all');
        var formData = $('#reportFilterForm').serialize();
        executeAjaxFilter(formData);
    });
});
</script>

@endsection