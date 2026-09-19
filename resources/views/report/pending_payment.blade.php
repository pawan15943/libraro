@extends('layouts.library')

@section('title', 'Pending Payment Report')

@section('content')

@php
    use Carbon\Carbon;
    $currentYear = date('Y');
    $currentMonth = date('m');
    $hasCustomFilter = !empty($filters['year']) || !empty($filters['month']) || !empty($filters['plan_id']) || !empty($filters['plan_type']) || !empty($filters['search']);
@endphp

{{-- Dedicated Scoped Stylesheet for Pending Payment Report --}}
<link rel="stylesheet" href="{{ asset('public/css/pending-payment-report.css') }}?v={{ time() }}" />

<div class="pending-payment-report-module">

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

    @can('has-permission', 'Pending Payment Report')

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
        </div>
    </div>

    {{-- 2. 4 KPI Summary Metric Cards --}}
    <div class="report-kpi-grid">
        {{-- Card 1: Total Pending Renewals --}}
        <div class="report-kpi-card kpi-due">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Pending Renewals</div>
                <div class="kpi-value text-danger" id="kpiTotalPending">{{ number_format($metrics['total_pending'] ?? count($learners)) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-users me-1"></i>Learners Overdue / Due
                </div>
            </div>
        </div>

        {{-- Card 2: In Extension Grace Period --}}
        <div class="report-kpi-card kpi-extension">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-user-clock"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">In Grace Period</div>
                <div class="kpi-value" style="color: #d97706;" id="kpiInExtension">{{ number_format($metrics['in_extension'] ?? 0) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-shield me-1"></i>Extension Days Active
                </div>
            </div>
        </div>

        {{-- Card 3: Pending Settlements --}}
        <div class="report-kpi-card kpi-settlement">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Due Settlements</div>
                <div class="kpi-value" style="color: #34939F;" id="kpiSettlementCount">{{ number_format($metrics['settlement_count'] ?? 0) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-credit-card me-1"></i>Awaiting Settle Payment
                </div>
            </div>
        </div>

        {{-- Card 4: Total Pending Amount --}}
        <div class="report-kpi-card kpi-amount">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-circle-dollar-to-slot"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Settlement Balance</div>
                <div class="kpi-value" style="color: #18225f;" id="kpiTotalDue">₹ {{ number_format($metrics['total_settlement_due'] ?? 0, 2) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-coins me-1"></i>Outstanding Balance
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Single-Line Collapsible Filter Bar --}}
    <div class="report-filter-wrapper" id="reportFilterContainer" style="{{ $hasCustomFilter ? '' : 'display: none;' }}">
        <form action="{{ route('pending.payment.report') }}" method="GET" id="reportFilterForm" class="single-line-filter-form">
            {{-- Year --}}
            <div class="filter-col">
                <label for="filterYear" class="filter-inline-label"><i class="fa-regular fa-calendar"></i> Year</label>
                <select id="filterYear" class="form-select filter-control" name="year">
                    <option value="">All Years</option>
                    @foreach($dynamicyears as $year)
                        <option value="{{ $year }}" {{ (request('year') == $year) ? 'selected' : '' }}>
                            {{ $year }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Month --}}
            <div class="filter-col">
                <label for="filterMonth" class="filter-inline-label"><i class="fa-regular fa-calendar-days"></i> Month</label>
                <select id="filterMonth" class="form-select filter-control" name="month">
                    <option value="">All Months</option>
                    @foreach($dynamicmonths as $month)
                        <option value="{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}" {{ request('month') == str_pad($month, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                            {{ DateTime::createFromFormat('!m', $month)->format('M') }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Plan --}}
            <div class="filter-col">
                <label for="filterPlan" class="filter-inline-label"><i class="fa-solid fa-tags"></i> Plan</label>
                <select name="plan_id" id="filterPlan" class="form-select filter-control">
                    <option value="">All Plans</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ request()->get('plan_id') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Plan Type --}}
            <div class="filter-col">
                <label for="filterPlanType" class="filter-inline-label"><i class="fa-solid fa-cubes"></i> Slot Type</label>
                <select name="plan_type" id="filterPlanType" class="form-select filter-control">
                    <option value="">All Slot Types</option>
                    @foreach($planTypes as $key => $value)
                        <option value="{{ $value->id }}" {{ request()->get('plan_type') == $value->id ? 'selected' : '' }}>
                            {{ $value->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Search --}}
            <div class="filter-col">
                <label for="filterSearch" class="filter-inline-label"><i class="fa-solid fa-magnifying-glass"></i> Search</label>
                <input type="text" class="form-control filter-control" id="filterSearch" name="search" placeholder="Name, Mobile, Email" value="{{ request()->get('search') }}">
            </div>

            {{-- Actions --}}
            <div class="filter-col filter-col-actions">
                <span class="filter-inline-label" aria-hidden="true">&nbsp;</span>
                <div class="filter-actions-inline">
                    <button type="submit" class="btn btn-filter-apply" id="btnApplyFilter">
                        <i class="fa-solid fa-magnifying-glass"></i> Filter
                    </button>
                    <a href="{{ route('pending.payment.report') }}" class="btn btn-filter-reset" id="btnResetFilter">
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
                <span>Pending Renewals:</span>
                <span class="records-count-badge" id="visibleCountBadge">{{ count($learners) }}</span>
                <span class="text-muted small">learners</span>
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
            <div>Learner</div>
            <div>Active Plan &amp; Slot</div>
            <div class="text-center">Due Date</div>
            <div class="text-center">Grace / Overdue</div>
            <div class="text-center">Settlement</div>
            <div class="text-center">Action</div>
        </div>

        {{-- Records Container --}}
        <div class="collection-records-grid" id="pendingPaymentTableContainer">
            @include('report.partials.pending_payment_table', ['learners' => $learners, 'extendDay' => $extendDay])
        </div>

        {{-- Empty Search State --}}
        <div class="report-empty-state d-none" id="searchEmptyState">
            <i class="fa-solid fa-magnifying-glass empty-state-icon"></i>
            <h6 class="empty-state-title">No Matching Records Found</h6>
            <p class="small text-muted mb-3">No pending payment records matched your search query.</p>
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
        <span class="text-danger fw-semibold">You don't have Permission to view Pending Payment Report.</span>
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
            var offset = $('#pendingPaymentTableContainer').offset();
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
        var headers = ['Seat No', 'Learner Name', 'Mobile', 'Plan Name', 'Due Date', 'Status'];
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
                $c.attr('data-status') || ''
            ];
            rows.push(row.map(function(val) { return '"' + String(val).replace(/"/g, '""') + '"'; }).join(','));
        });

        var csvContent = "\uFEFF" + rows.join("\r\n");
        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", "Pending_Payment_Report_" + new Date().toISOString().slice(0, 10) + ".csv");
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
        $('#pendingPaymentTableContainer').css('opacity', '0.5');

        $.ajax({
            url: url,
            type: 'GET',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (res && res.html) {
                    $('#pendingPaymentTableContainer').html(res.html);

                    if (res.metrics) {
                        $('#kpiTotalPending').text(res.metrics.total_pending.toLocaleString());
                        $('#kpiInExtension').text(res.metrics.in_extension.toLocaleString());
                        $('#kpiSettlementCount').text(res.metrics.settlement_count.toLocaleString());
                        $('#kpiTotalDue').text('₹ ' + (res.metrics.total_settlement_due || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
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
                $('#pendingPaymentTableContainer').css('opacity', '1');
            }
        });
    });
});
</script>

@endsection