@extends('layouts.library')

@section('title', 'Payment Collection Report')

@section('content')

@php
    use Carbon\Carbon;
    $currDate = date('Y-m-d');
    $hasCustomFilter = !empty($payment_mode) || ($preset && !in_array($preset, ['this_month', '']));
@endphp

{{-- Dedicated Scoped Stylesheet for Payment Collection Report --}}
<link rel="stylesheet" href="{{ asset('public/css/payment-collection-report.css') }}?v={{ time() }}" />

<div class="payment-collection-report-module">

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

    @can('has-permission', 'Payment Collection Report')

    {{-- 1. Top Action Buttons Bar (Heading removed as requested) --}}
    <div class="heading-list py-1 d-flex justify-content-end align-items-center gap-2 mb-3">
        <div class="header-actions">
            {{-- Filter Toggle Button (Hidden by default) --}}
            <button type="button" class="btn btn-filter-toggle {{ $hasCustomFilter ? 'active' : '' }}" id="toggleFilterBtn" title="Show/Hide Filter Drawer">
                <i class="fa-solid fa-filter"></i>
                <span>Filters</span>
                @if($hasCustomFilter)
                    <span class="filter-badge-dot" title="Active Filter Applied"></span>
                @endif
            </button>

            <button type="button" class="btn btn-export-csv" id="btnExportReportCsv" title="Download collection data in CSV">
                <i class="fa-solid fa-file-csv"></i> Export CSV
            </button>
        </div>
    </div>

    {{-- 2. Simple KPI Summary Cards (No left border, clean & modern) --}}
    <div class="report-kpi-grid">
        {{-- Card 1: Total Collections --}}
        <div class="report-kpi-card kpi-collected">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-circle-dollar-to-slot"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Total Collections</div>
                <div class="kpi-value text-success">₹ {{ number_format($metrics['total_collected'] ?? 0, 2) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-receipt me-1"></i>{{ number_format($metrics['total_count'] ?? 0) }} Receipts Logged
                </div>
            </div>
        </div>

        {{-- Card 2: Total Invoiced / Billed --}}
        <div class="report-kpi-card kpi-invoiced">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-file-invoice"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Total Value Billed</div>
                <div class="kpi-value" style="color: #18225f;">₹ {{ number_format($metrics['total_invoiced'] ?? 0, 2) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-chair me-1"></i>Seat &amp; Locker Invoiced
                </div>
            </div>
        </div>

        {{-- Card 3: Pending Balance --}}
        <div class="report-kpi-card kpi-pending">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Unpaid / Due Amount</div>
                <div class="kpi-value text-danger">₹ {{ number_format($metrics['total_pending'] ?? 0, 2) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i>Pending Learner Dues
                </div>
            </div>
        </div>

        {{-- Card 4: Payment Mode Summary --}}
        <div class="report-kpi-card kpi-discount">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Payment Modes</div>
                <div class="kpi-value" style="color: #34939F;">₹ {{ number_format($metrics['total_collected'] ?? 0, 0) }}</div>
                <div class="kpi-sub">
                    <span>Online: <strong>₹{{ number_format($metrics['online_amount'] ?? 0, 0) }}</strong></span> &bull; 
                    <span>Offline: <strong>₹{{ number_format($metrics['offline_amount'] ?? 0, 0) }}</strong></span>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Single-Line Filter Bar (Hidden by default, toggled on Filter Button click) --}}
    <div class="report-filter-wrapper" id="reportFilterContainer" style="{{ $hasCustomFilter ? '' : 'display: none;' }}">
        <form action="{{ route('payment.collection.report') }}" method="GET" id="reportFilterForm" class="single-line-filter-form">
            {{-- 1. Date Range Preset --}}
            <div class="filter-col">
                <label for="filterPresetSelect" class="filter-inline-label"><i class="fa-solid fa-clock-rotate-left"></i> Preset</label>
                <select name="preset" id="filterPresetSelect" class="form-select filter-control">
                    <option value="this_month" {{ ($preset === 'this_month' || empty($preset)) ? 'selected' : '' }}>This Month</option>
                    <option value="today" {{ $preset === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ $preset === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="last_7_days" {{ $preset === 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="last_month" {{ $preset === 'last_month' ? 'selected' : '' }}>Last Month</option>
                    <option value="all" {{ $preset === 'all' ? 'selected' : '' }}>All Records</option>
                    <option value="custom" {{ $preset === 'custom' ? 'selected' : '' }}>Custom Date Range</option>
                </select>
            </div>

            {{-- 2. Start Date --}}
            <div class="filter-col">
                <label for="filterStartDate" class="filter-inline-label"><i class="fa-regular fa-calendar"></i> From</label>
                <input type="date" class="form-control filter-control" name="start_date" id="filterStartDate" value="{{ $start_date }}">
            </div>

            {{-- 3. End Date --}}
            <div class="filter-col">
                <label for="filterEndDate" class="filter-inline-label"><i class="fa-regular fa-calendar"></i> To</label>
                <input type="date" class="form-control filter-control" name="end_date" id="filterEndDate" value="{{ $end_date }}">
            </div>

            {{-- 4. Payment Mode --}}
            <div class="filter-col">
                <label for="filterPaymentMode" class="filter-inline-label"><i class="fa-solid fa-wallet"></i> Mode</label>
                <select name="payment_mode" id="filterPaymentMode" class="form-select filter-control">
                    <option value="">All Modes</option>
                    <option value="1" {{ (string)$payment_mode === '1' || strtolower((string)$payment_mode) === 'online' ? 'selected' : '' }}>Online</option>
                    <option value="2" {{ (string)$payment_mode === '2' || strtolower((string)$payment_mode) === 'offline' ? 'selected' : '' }}>Offline</option>
                    <option value="3" {{ (string)$payment_mode === '3' || str_contains(strtolower((string)$payment_mode), 'pay') ? 'selected' : '' }}>Pay Later</option>
                </select>
            </div>

            {{-- 5. Action Buttons --}}
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

    {{-- 4. Mobile-First Data Presentation (Desktop Rows + Mobile Cards - No Table) --}}
    <div class="records-wrapper">
        {{-- Search & Summary Bar --}}
        <div class="records-controls-bar">
            <div class="records-count-info">
                <span>Collections:</span>
                <span class="records-count-badge" id="visibleCountBadge">{{ count($learners) }}</span>
                <span class="text-muted small">records</span>
            </div>
            <div class="records-search-box">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="cardSearchInput" placeholder="Search learner, seat, mobile, mode..." autocomplete="off" />
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
            <div class="text-center">Status</div>
            <div class="text-center">Date</div>
            <div class="text-center">Mode</div>
            <div class="text-center">Action</div>
        </div>

        {{-- Records Container (Desktop Rows / Mobile-First Cards) --}}
        <div class="collection-records-grid" id="recordsContainer">
            @include('report.partials.collection_cards', ['learners' => $learners])
        </div>

        {{-- Live Search No-Results Placeholder --}}
        <div class="report-empty-state d-none" id="searchEmptyState">
            <i class="fa-solid fa-magnifying-glass empty-state-icon"></i>
            <h6 class="empty-state-title">No Matching Records Found</h6>
            <p class="small text-muted mb-3">
                No collection records matched your search query. Try typing another name, seat number, or payment mode.
            </p>
            <button type="button" class="btn btn-filter-reset" id="btnResetSearch">
                <i class="fa-solid fa-arrow-rotate-left me-1"></i> Clear Search
            </button>
        </div>

        {{-- 5. Pagination Bar (10 records per page, Mobile-Friendly) --}}
        <div class="records-pagination-wrapper" id="paginationWrapper">
            <div class="pagination-info" id="paginationInfoText">
                Showing 1 to 10 of {{ count($learners) }} records
            </div>
            <nav class="pagination-nav">
                <ul class="pagination mb-0" id="paginationList">
                    {{-- Dynamically generated by JS --}}
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
        <p class="text-muted">You don't have permission to view the Payment Collection Report. Please contact your library administrator.</p>
    </div>
    @endcan

</div>

<script>
$(document).ready(function() {
    // 1. Toggle Filter Container Show/Hide on click
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

    // 2. Pagination & Real-time Live Instant Search (10 Records per page)
    var PAGE_SIZE = 10;
    var currentPage = 1;
    var $allCards = $('.collection-record-card');
    var totalCards = $allCards.length;

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

        if (currentPage > totalPages) {
            currentPage = totalPages;
        }
        if (currentPage < 1) {
            currentPage = 1;
        }

        // Hide all cards first
        $allCards.addClass('d-none').attr('style', 'display: none !important;');

        // Show only the 10 cards on the active page
        var startIndex = (currentPage - 1) * PAGE_SIZE;
        var endIndex = startIndex + PAGE_SIZE;
        $matching.slice(startIndex, endIndex).removeClass('d-none').removeAttr('style');

        // Update count text
        var startDisplay = totalMatching > 0 ? (startIndex + 1) : 0;
        var endDisplay = Math.min(endIndex, totalMatching);
        $('#visibleCountBadge').text(totalMatching);
        $('#paginationInfoText').text('Showing ' + startDisplay + ' to ' + endDisplay + ' of ' + totalMatching + ' records');

        // Build pagination controls if records exist
        if (totalPages > 1) {
            $('#paginationWrapper').removeClass('d-none');
            var buttonsHtml = '';

            // Previous button
            buttonsHtml += '<li class="page-item ' + (currentPage === 1 ? 'disabled' : '') + '">' +
                '<a class="page-link" href="javascript:void(0)" data-page="' + (currentPage - 1) + '" aria-label="Previous">' +
                '<i class="fa-solid fa-chevron-left"></i>' +
                '</a></li>';

            // Numeric page buttons (with ellipsis for many pages)
            for (var p = 1; p <= totalPages; p++) {
                if (p === 1 || p === totalPages || (p >= currentPage - 2 && p <= currentPage + 2)) {
                    buttonsHtml += '<li class="page-item ' + (p === currentPage ? 'active' : '') + '">' +
                        '<a class="page-link" href="javascript:void(0)" data-page="' + p + '">' + p + '</a></li>';
                } else if (p === currentPage - 3 || p === currentPage + 3) {
                    buttonsHtml += '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
                }
            }

            // Next button
            buttonsHtml += '<li class="page-item ' + (currentPage === totalPages ? 'disabled' : '') + '">' +
                '<a class="page-link" href="javascript:void(0)" data-page="' + (currentPage + 1) + '" aria-label="Next">' +
                '<i class="fa-solid fa-chevron-right"></i>' +
                '</a></li>';

            $('#paginationList').html(buttonsHtml);
        } else {
            // Hide pagination links if <= 10 items, but keep the count info visible
            $('#paginationList').empty();
            if (totalMatching <= 0) {
                $('#paginationWrapper').addClass('d-none');
            } else {
                $('#paginationWrapper').removeClass('d-none');
            }
        }

        // Empty state check
        if (totalMatching === 0 && totalCards > 0) {
            $('#searchEmptyState').removeClass('d-none');
        } else {
            $('#searchEmptyState').addClass('d-none');
        }
    }

    // Initialize pagination on load
    renderPagination();

    // Page Click Handler
    $(document).on('click', '#paginationList .page-link', function(e) {
        e.preventDefault();
        var targetPage = parseInt($(this).attr('data-page'));
        if (!isNaN(targetPage) && targetPage !== currentPage && targetPage >= 1) {
            currentPage = targetPage;
            renderPagination();
            // Smooth scroll to records list top
            if ($('#recordsContainer').length) {
                $('html, body').animate({ scrollTop: $('#recordsContainer').offset().top - 100 }, 150);
            }
        }
    });

    // Instant Search Input
    $('#cardSearchInput').on('input keyup', function() {
        var query = $(this).val().toLowerCase().trim();
        if (query.length > 0) {
            $('#clearSearchBtn').removeClass('d-none');
        } else {
            $('#clearSearchBtn').addClass('d-none');
        }
        currentPage = 1;
        renderPagination();
    });

    // Clear Search Input
    $('#clearSearchBtn, #btnResetSearch').on('click', function() {
        $('#cardSearchInput').val('');
        $('#clearSearchBtn').addClass('d-none');
        currentPage = 1;
        renderPagination();
        $('#cardSearchInput').focus();
    });

    // 3. Fast Date Calculations for Filter Presets
    function getPresetDates(preset) {
        var now = new Date();
        var currY = now.getFullYear();
        var currM = now.getMonth();
        var pad = function(n) { return (n < 10 ? '0' : '') + n; };
        var toIso = function(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };

        if (preset === 'today') {
            var t = toIso(now);
            return { start: t, end: t };
        } else if (preset === 'yesterday') {
            var y = toIso(new Date(now.getTime() - 86400000));
            return { start: y, end: y };
        } else if (preset === 'last_7_days') {
            var past7 = toIso(new Date(now.getTime() - 7 * 86400000));
            return { start: past7, end: toIso(now) };
        } else if (preset === 'this_month') {
            var startM = currY + '-' + pad(currM + 1) + '-01';
            return { start: startM, end: toIso(now) };
        } else if (preset === 'last_month') {
            var firstPrev = new Date(currY, currM - 1, 1);
            var lastPrev = new Date(currY, currM, 0);
            return { start: toIso(firstPrev), end: toIso(lastPrev) };
        } else if (preset === 'all') {
            return { start: '', end: '' };
        }
        return null;
    }

    // 4. AJAX Smooth Filter Execution
    function runAjaxFilter() {
        var formData = $('#reportFilterForm').serialize();
        var url = $('#reportFilterForm').attr('action');

        // Loading states
        $('#recordsContainer, .report-kpi-grid').addClass('ajax-loading-spinner');
        $('#btnApplyFilter').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

        $.ajax({
            url: url,
            type: 'GET',
            data: formData,
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.status) {
                    // 1. Update KPI Cards Values
                    if (response.metrics) {
                        $('.kpi-collected .kpi-value').text(response.metrics.total_collected);
                        $('.kpi-collected .kpi-sub').html('<i class="fa-solid fa-receipt me-1"></i>' + response.metrics.total_count + ' Receipts Logged');
                        
                        $('.kpi-invoiced .kpi-value').text(response.metrics.total_invoiced);
                        
                        $('.kpi-pending .kpi-value').text(response.metrics.total_pending);
                        
                        $('.kpi-discount .kpi-value').text(response.metrics.total_collected);
                        $('.kpi-discount .kpi-sub').html(
                            '<span>Online: <strong>' + response.metrics.online_amount + '</strong></span> &bull; ' +
                            '<span>Offline: <strong>' + response.metrics.offline_amount + '</strong></span>'
                        );
                    }

                    // 2. Replace Records Container HTML
                    $('#recordsContainer').html(response.html);

                    // 3. Rebind cards reference & reset pagination
                    $allCards = $('.collection-record-card');
                    totalCards = $allCards.length;
                    currentPage = 1;
                    $('#cardSearchInput').val('');
                    $('#clearSearchBtn').addClass('d-none');
                    renderPagination();

                    // 4. Update Filter Active Dot
                    var isCustom = (response.payment_mode && response.payment_mode !== '') || 
                                   (response.preset && response.preset !== 'this_month' && response.preset !== '');
                    if (isCustom) {
                        if (!$('#toggleFilterBtn .filter-badge-dot').length) {
                            $('#toggleFilterBtn').append('<span class="filter-badge-dot" title="Active Filter Applied"></span>');
                        }
                    } else {
                        $('#toggleFilterBtn .filter-badge-dot').remove();
                    }

                    // 5. Update Browser URL seamlessly
                    if (window.history && window.history.pushState) {
                        var newUrl = url + '?' + formData;
                        window.history.pushState({ path: newUrl }, '', newUrl);
                    }
                }
            },
            error: function(xhr) {
                console.error('AJAX Filter failed', xhr);
                document.getElementById('reportFilterForm').submit();
            },
            complete: function() {
                $('#recordsContainer, .report-kpi-grid').removeClass('ajax-loading-spinner');
                $('#btnApplyFilter').prop('disabled', false).html('<i class="fa-solid fa-magnifying-glass"></i> Filter');
            }
        });
    }

    // Trigger on Preset Change
    $('#filterPresetSelect').on('change', function() {
        var preset = $(this).val();
        var dates = getPresetDates(preset);
        if (dates) {
            $('#filterStartDate').val(dates.start);
            $('#filterEndDate').val(dates.end);
        }
        runAjaxFilter();
    });

    // Trigger on Payment Mode Change
    $('#filterPaymentMode').on('change', function() {
        runAjaxFilter();
    });

    // Mark as custom if manual date changes
    $('#filterStartDate, #filterEndDate').on('change', function() {
        $('#filterPresetSelect').val('custom');
    });

    // Form Submit via AJAX
    $('#reportFilterForm').on('submit', function(e) {
        e.preventDefault();
        runAjaxFilter();
    });

    // Reset Button via AJAX
    $('#btnResetFilter').on('click', function() {
        $('#filterPresetSelect').val('this_month');
        var dates = getPresetDates('this_month');
        $('#filterStartDate').val(dates.start);
        $('#filterEndDate').val(dates.end);
        $('#filterPaymentMode').val('');
        runAjaxFilter();
    });

    // Empty state "View All" button
    $(document).on('click', '#btnEmptyStateAll', function() {
        $('#filterPresetSelect').val('all');
        $('#filterStartDate').val('');
        $('#filterEndDate').val('');
        $('#filterPaymentMode').val('');
        runAjaxFilter();
    });

    // 5. Fast CSV Export of Matching Records
    $('#btnExportReportCsv').on('click', function() {
        var $matching = getFilteredCards();
        var rows = [];

        // CSV Header
        rows.push(['S.No.', 'Seat No.', 'Learner Name', 'Mobile', 'Total Billed (INR)', 'Paid Amount (INR)', 'Due Amount (INR)', 'Collection Date', 'Payment Mode']);

        var counter = 1;
        $matching.each(function() {
            var seat = $(this).attr('data-seat') || '';
            var name = $(this).attr('data-name') || '';
            var mobile = $(this).attr('data-mobile') || '';
            var total = $(this).attr('data-total') || '0';
            var paid = $(this).attr('data-paid') || '0';
            var pending = $(this).attr('data-pending') || '0';
            var date = $(this).attr('data-date') || '';
            var mode = $(this).attr('data-mode') || '';

            rows.push([
                counter++,
                '"' + seat.replace(/"/g, '""') + '"',
                '"' + name.replace(/"/g, '""') + '"',
                '"' + mobile.replace(/"/g, '""') + '"',
                total,
                paid,
                pending,
                '"' + date.replace(/"/g, '""') + '"',
                '"' + mode.replace(/"/g, '""') + '"'
            ]);
        });

        if (rows.length <= 1) {
            alert('No records available to export.');
            return;
        }

        var csvContent = "\uFEFF"; // UTF-8 BOM
        rows.forEach(function(rowArray) {
            var row = rowArray.join(",");
            csvContent += row + "\r\n";
        });

        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", "Libraro_Payment_Collections_{{ date('Y-m-d') }}.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // 6. Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

@endsection
