@extends('layouts.library')

@section('title', 'Upcoming Payment Report')

@section('content')

@php
    use Carbon\Carbon;
    $hasCustomFilter = (!empty($filters['days']) && $filters['days'] != 5) || !empty($filters['plan_id']) || !empty($filters['search']);
@endphp

{{-- Dedicated Scoped Stylesheet for Upcoming Payment Report --}}
<link rel="stylesheet" href="{{ asset('public/css/upcoming-payment-report.css') }}?v={{ time() }}" />

<div class="upcoming-payment-report-module">

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

    @can('has-permission', 'Upcoming Payment Report')

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
        {{-- Card 1: Total Expiring Soon --}}
        <div class="report-kpi-card kpi-total">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-bell"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Expiring Soon</div>
                <div class="kpi-value" style="color: #18225f;" id="kpiTotalUpcoming">{{ number_format($metrics['total_upcoming'] ?? count($learners)) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-users me-1"></i>Upcoming Plan Expirations
                </div>
            </div>
        </div>

        {{-- Card 2: Expiring Today --}}
        <div class="report-kpi-card kpi-today">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-calendar-day"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Expiring Today</div>
                <div class="kpi-value text-danger" id="kpiExpiringToday">{{ number_format($metrics['expiring_today'] ?? 0) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-circle-exclamation me-1"></i>Action Needed Today
                </div>
            </div>
        </div>

        {{-- Card 3: Expiring Tomorrow --}}
        <div class="report-kpi-card kpi-tomorrow">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Expiring Tomorrow</div>
                <div class="kpi-value" style="color: #d97706;" id="kpiExpiringTomorrow">{{ number_format($metrics['expiring_tomorrow'] ?? 0) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-clock me-1"></i>1 Day Remaining
                </div>
            </div>
        </div>

        {{-- Card 4: Expiring in 2-5 Days --}}
        <div class="report-kpi-card kpi-week">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-calendar-week"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">2 to 5 Days Left</div>
                <div class="kpi-value" style="color: #34939F;" id="kpiExpiringLater">{{ number_format($metrics['expiring_later'] ?? 0) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-shield-halved me-1"></i>Advance Renewal Window
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Single-Line Collapsible Filter Bar --}}
    <div class="report-filter-wrapper" id="reportFilterContainer" style="{{ $hasCustomFilter ? '' : 'display: none;' }}">
        <form action="{{ route('upcoming.payment.report') }}" method="GET" id="reportFilterForm" class="single-line-filter-form">
            {{-- Window Days --}}
            <div class="filter-col">
                <label for="filterDays" class="filter-inline-label"><i class="fa-regular fa-clock"></i> Expiry Window</label>
                <select id="filterDays" class="form-select filter-control" name="days">
                    <option value="1" {{ ($filters['days'] ?? 5) == 1 ? 'selected' : '' }}>Expiring in 24 Hours</option>
                    <option value="3" {{ ($filters['days'] ?? 5) == 3 ? 'selected' : '' }}>Next 3 Days</option>
                    <option value="5" {{ ($filters['days'] ?? 5) == 5 ? 'selected' : '' }}>Next 5 Days (Default)</option>
                    <option value="7" {{ ($filters['days'] ?? 5) == 7 ? 'selected' : '' }}>Next 7 Days</option>
                    <option value="15" {{ ($filters['days'] ?? 5) == 15 ? 'selected' : '' }}>Next 15 Days</option>
                </select>
            </div>

            {{-- Plan --}}
            <div class="filter-col">
                <label for="filterPlan" class="filter-inline-label"><i class="fa-solid fa-tags"></i> Plan</label>
                <select name="plan_id" id="filterPlan" class="form-select filter-control">
                    <option value="">All Plans</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ ($filters['plan_id'] ?? '') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Search --}}
            <div class="filter-col">
                <label for="filterSearch" class="filter-inline-label"><i class="fa-solid fa-magnifying-glass"></i> Search</label>
                <input type="text" class="form-control filter-control" id="filterSearch" name="search" placeholder="Search learner, seat, mobile..." value="{{ $filters['search'] ?? '' }}">
            </div>

            {{-- Actions --}}
            <div class="filter-col filter-col-actions">
                <span class="filter-inline-label" aria-hidden="true">&nbsp;</span>
                <div class="filter-actions-inline">
                    <button type="submit" class="btn btn-filter-apply" id="btnApplyFilter">
                        <i class="fa-solid fa-magnifying-glass"></i> Filter
                    </button>
                    <a href="{{ route('upcoming.payment.report') }}" class="btn btn-filter-reset" id="btnResetFilter">
                        <i class="fa-solid fa-arrow-rotate-left"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- 4. Card Row Data Presentation (Matching Screenshot UI) --}}
    <div class="records-wrapper">
        <div class="records-controls-bar">
            <div class="records-count-info">
                <span>Upcoming Expirations:</span>
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
            <div>Active Plan &amp; Slot</div>
            <div class="text-center">Due Date</div>
            <div class="text-center">Days Remaining</div>
            <div class="text-center">Action</div>
        </div>

        {{-- Records Container --}}
        <div class="collection-records-grid" id="upcomingPaymentTableContainer">
            @include('report.partials.upcoming_payment_table', ['learners' => $learners])
        </div>

        {{-- Empty Search State --}}
        <div class="report-empty-state d-none" id="searchEmptyState">
            <i class="fa-solid fa-magnifying-glass empty-state-icon"></i>
            <h6 class="empty-state-title">No Matching Records Found</h6>
            <p class="small text-muted mb-3">No upcoming expiration records matched your search query.</p>
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
        <span class="text-danger fw-semibold">You don't have Permission to view Upcoming Payment Report.</span>
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

        $allCards.addClass('d-none');

        if (totalMatching > 0) {
            var startIndex = (currentPage - 1) * PAGE_SIZE;
            var endIndex = startIndex + PAGE_SIZE;
            $matching.slice(startIndex, endIndex).removeClass('d-none');
            $('#searchEmptyState').addClass('d-none');
            $('#paginationWrapper').removeClass('d-none');
        } else {
            $('#searchEmptyState').removeClass('d-none');
            $('#paginationWrapper').addClass('d-none');
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
            var offset = $('#upcomingPaymentTableContainer').offset();
            if (offset) {
                $('html, body').animate({ scrollTop: offset.top - 120 }, 150);
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
        var headers = ['Seat No', 'Learner Name', 'Mobile', 'Plan Name', 'Due Date', 'Days Left'];
        rows.push(headers.map(function(h) { return '"' + h.replace(/"/g, '""') + '"'; }).join(','));

        var $exportCards = getFilteredCards();
        if ($exportCards.length === 0) $exportCards = $allCards;

        $exportCards.each(function() {
            var $c = $(this);
            var row = [
                $c.attr('data-seat') || '',
                $c.attr('data-name') || '',
                $c.attr('data-mobile') || '',
                $c.attr('data-plan') || '',
                $c.attr('data-due') || '',
                $c.attr('data-diff') || ''
            ];
            rows.push(row.map(function(val) { return '"' + String(val).replace(/"/g, '""') + '"'; }).join(','));
        });

        var csvContent = "\uFEFF" + rows.join("\r\n");
        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", "Upcoming_Payment_Report_" + new Date().toISOString().slice(0, 10) + ".csv");
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
        $('#upcomingPaymentTableContainer').css('opacity', '0.5');

        $.ajax({
            url: url,
            type: 'GET',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (res && res.html) {
                    $('#upcomingPaymentTableContainer').html(res.html);

                    if (res.metrics) {
                        $('#kpiTotalUpcoming').text(res.metrics.total_upcoming.toLocaleString());
                        $('#kpiExpiringToday').text(res.metrics.expiring_today.toLocaleString());
                        $('#kpiExpiringTomorrow').text(res.metrics.expiring_tomorrow.toLocaleString());
                        $('#kpiExpiringLater').text(res.metrics.expiring_later.toLocaleString());
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
                $('#upcomingPaymentTableContainer').css('opacity', '1');
            }
        });
    });
});
</script>

@endsection