@extends('layouts.library')

@section('title', 'Expired Learner Report')

@section('content')

@php
    use Carbon\Carbon;
    $currentYear = date('Y');
    $currentMonth = date('m');
    $hasCustomFilter = !empty($filters['expiredyear']) || !empty($filters['expiredmonth']) || !empty($filters['search']);
@endphp

{{-- Dedicated Scoped Stylesheet for Expired Learner Report --}}
<link rel="stylesheet" href="{{ asset('public/css/expired-learner-report.css') }}?v={{ time() }}" />

<div class="expired-learner-report-module">

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

    @can('has-permission', 'Expired Learners Report')

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

    {{-- 2. 4 KPI Summary Metric Cards --}}
    <div class="report-kpi-grid">
        {{-- Card 1: Total Expired Learners --}}
        <div class="report-kpi-card kpi-total">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-user-xmark"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Total Expired</div>
                <div class="kpi-value text-danger" id="kpiTotalExpired">{{ number_format($metrics['total_expired'] ?? count($learners)) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-users me-1"></i>Inactive Accounts
                </div>
            </div>
        </div>

        {{-- Card 2: Expired This Month --}}
        <div class="report-kpi-card kpi-month">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-calendar-minus"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Expired This Month</div>
                <div class="kpi-value" style="color: #d97706;" id="kpiExpiredMonth">{{ number_format($metrics['expired_this_month'] ?? 0) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-clock me-1"></i>Current Month
                </div>
            </div>
        </div>

        {{-- Card 3: Expired This Year --}}
        <div class="report-kpi-card kpi-year">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-calendar-days"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Expired This Year</div>
                <div class="kpi-value" style="color: #18225f;" id="kpiExpiredYear">{{ number_format($metrics['expired_this_year'] ?? 0) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-chart-line me-1"></i>Annual Total
                </div>
            </div>
        </div>

        {{-- Card 4: Retention Opportunities (≤ 90 Days) --}}
        <div class="report-kpi-card kpi-retention">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Retention Leads</div>
                <div class="kpi-value" style="color: #34939F;" id="kpiRetentionLeads">{{ number_format($metrics['retention_leads'] ?? 0) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-rotate me-1"></i>Expired ≤ 90 Days
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Single-Line Collapsible Filter Bar --}}
    <div class="report-filter-wrapper" id="reportFilterContainer" style="{{ $hasCustomFilter ? '' : 'display: none;' }}">
        <form action="{{ route('expired.learner.report') }}" method="GET" id="reportFilterForm" class="single-line-filter-form">
            {{-- Year --}}
            <div class="filter-col">
                <label for="expiredyear" class="filter-inline-label"><i class="fa-regular fa-calendar"></i> Expiry Year</label>
                <select id="expiredyear" class="form-select filter-control" name="expiredyear">
                    <option value="">All Years</option>
                    @foreach($dynamicyears as $year)
                        <option value="{{ $year }}" {{ ((request('expiredyear') ?? ($filters['expiredyear'] ?? '')) == $year) ? 'selected' : '' }}>
                            {{ $year }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Month --}}
            <div class="filter-col">
                <label for="expiredmonth" class="filter-inline-label"><i class="fa-regular fa-calendar-days"></i> Expiry Month</label>
                <select id="expiredmonth" class="form-select filter-control" name="expiredmonth">
                    <option value="">All Months</option>
                    @foreach($dynamicmonths as $month)
                        @php $mPad = str_pad($month, 2, '0', STR_PAD_LEFT); @endphp
                        <option value="{{ $mPad }}" {{ ((request('expiredmonth') ?? ($filters['expiredmonth'] ?? '')) == $mPad) ? 'selected' : '' }}>
                            {{ DateTime::createFromFormat('!m', $month)->format('M') }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Search --}}
            <div class="filter-col">
                <label for="filterSearch" class="filter-inline-label"><i class="fa-solid fa-magnifying-glass"></i> Search</label>
                <input type="text" class="form-control filter-control" id="filterSearch" name="search" placeholder="Search learner, seat, mobile..." value="{{ $filters['search'] ?? request()->get('search') }}">
            </div>

            {{-- Actions --}}
            <div class="filter-col filter-col-actions">
                <span class="filter-inline-label" aria-hidden="true">&nbsp;</span>
                <div class="filter-actions-inline">
                    <button type="submit" class="btn btn-filter-apply" id="btnApplyFilter">
                        <i class="fa-solid fa-magnifying-glass"></i> Filter
                    </button>
                    <a href="{{ route('expired.learner.report') }}" class="btn btn-filter-reset" id="btnResetFilter">
                        <i class="fa-solid fa-arrow-rotate-left"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- 4. Records Presentation (Matching Screenshot UI) --}}
    <div class="records-wrapper">
        <div class="records-controls-bar">
            <div class="records-count-info">
                <span>Expired Learners:</span>
                <span class="records-count-badge" id="visibleCountBadge">{{ count($learners) }}</span>
                <span class="text-muted small">learners</span>
            </div>
            <div class="records-search-box">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="cardSearchInput" placeholder="Search learner, seat, mobile..." autocomplete="off" />
                <button type="button" class="btn-clear-search d-none" id="clearSearchBtn">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        {{-- Desktop Column Header (Visible on Desktop >= 992px) --}}
        <div class="records-header-row">
            <div>Learner</div>
            <div>Last Plan &amp; Slot</div>
            <div>Plan Duration</div>
            <div class="text-center">Expired On</div>
            <div class="text-center">Action</div>
        </div>

        {{-- Records Container --}}
        <div class="collection-records-grid" id="expiredLearnerTableContainer">
            @include('report.partials.expired_learner_table', ['learners' => $learners])
        </div>

        {{-- Empty Search State --}}
        <div class="report-empty-state d-none" id="searchEmptyState">
            <i class="fa-solid fa-magnifying-glass empty-state-icon"></i>
            <h6 class="empty-state-title">No Matching Records Found</h6>
            <p class="small text-muted mb-3">No expired learner records matched your search query.</p>
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
    <div class="card text-center py-5">
        <span class="text-danger fw-semibold">You don't have Permission to view Expired Learners Report.</span>
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

    // 2. Pagination & Real-time Live Instant Search
    var PAGE_SIZE = 10;
    var currentPage = 1;
    var $allCards = $('.collection-record-card');

    function getFilteredCards() {
        var query = $('#cardSearchInput').val().toLowerCase().trim();
        if (!query) return $allCards;
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

        $('#visibleCountBadge').text(totalMatching);

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

    // 3. Export CSV
    $('#btnExportReportCsv').on('click', function() {
        var rows = [];
        var headers = ['Seat No', 'Learner Name', 'Mobile', 'Email', 'Plan Name', 'Slot', 'Start Date', 'End Date', 'Status'];
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
                $c.attr('data-status') || ''
            ];
            rows.push(row.map(function(val) { return '"' + String(val).replace(/"/g, '""') + '"'; }).join(','));
        });

        var csvContent = "\uFEFF" + rows.join("\r\n");
        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", "Expired_Learners_Report_" + new Date().toISOString().slice(0, 10) + ".csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // 4. AJAX Filter Submission
    $('#reportFilterForm').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        var url = $form.attr('action');
        var formData = $form.serialize();

        var $btn = $('#btnApplyFilter');
        var originalBtnHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Filtering...');
        $('#expiredLearnerTableContainer').css('opacity', '0.5');

        $.ajax({
            url: url,
            type: 'GET',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (res && res.html) {
                    $('#expiredLearnerTableContainer').html(res.html);

                    if (res.metrics) {
                        $('#kpiTotalExpired').text(res.metrics.total_expired.toLocaleString());
                        $('#kpiExpiredMonth').text(res.metrics.expired_this_month.toLocaleString());
                        $('#kpiExpiredYear').text(res.metrics.expired_this_year.toLocaleString());
                        $('#kpiRetentionLeads').text(res.metrics.retention_leads.toLocaleString());
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
                $('#expiredLearnerTableContainer').css('opacity', '1');
            }
        });
    });
});
</script>

@endsection