@extends('layouts.library')

@section('title', 'Learner Activity Report')

@section('content')

@php
    use Carbon\Carbon;
    $currentYear = date('Y');
    $currentMonth = date('m');
    $hasCustomFilter = (!empty($filters['year']) && $filters['year'] !== 'all') ||
                       (!empty($filters['month']) && $filters['month'] !== 'all') ||
                       (!empty($filters['operation']) && $filters['operation'] !== 'all') ||
                       (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== 'all') ||
                       !empty($filters['search']);
@endphp

{{-- Dedicated Scoped Stylesheet for Activity Report --}}
<link rel="stylesheet" href="{{ asset('public/css/activity-report.css') }}?v={{ time() }}" />

<div class="activity-report-module">

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

    @can('has-permission', 'Activity Report')

    {{-- 1. Top Action Buttons Bar (Heading removed as per GEMINI.md standard) --}}
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

            <button type="button" class="btn btn-export-csv" id="btnExportReportCsv" title="Download activity logs in CSV">
                <i class="fa-solid fa-file-csv"></i> Export CSV
            </button>

            <button type="button" class="btn btn-report-print" onclick="window.print()" title="Print this report">
                <i class="fa-solid fa-print"></i> Print
            </button>
        </div>
    </div>

    {{-- Single Learner Filter Alert Banner (if opened from learner profile) --}}
    @if(!empty($filterLearnerId))
        <div class="learner-filter-banner mb-3">
            <div>
                <i class="fa-solid fa-user-tag me-1"></i> Showing activities for <strong>{{ $filterLearnerName ?? ('Learner #' . $filterLearnerId) }}</strong> only.
            </div>
            <a href="{{ route('activity.report', request()->except(['learner_id', 'page'])) }}" title="Show All Learners">
                <i class="fa-solid fa-xmark me-1"></i> Clear Filter
            </a>
        </div>
    @endif

    {{-- 2. Simple KPI Summary Cards (No left border, clean & modern) --}}
    <div class="report-kpi-grid">
        {{-- Card 1: Total Activities Logged --}}
        <div class="report-kpi-card kpi-total">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Total Activities</div>
                <div class="kpi-value" style="color: #18225f;" id="kpiTotalActivities">{{ number_format($metrics['total_activities'] ?? 0) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-list-check me-1"></i>Total Desk Operations Logged
                </div>
            </div>
        </div>

        {{-- Card 2: Seat Swaps --}}
        <div class="report-kpi-card kpi-swaps">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-chair"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Seat Swaps</div>
                <div class="kpi-value" style="color: #34939F;" id="kpiSwapCount">{{ number_format($metrics['swap_count'] ?? 0) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-repeat me-1"></i>Seat Reallocations
                </div>
            </div>
        </div>

        {{-- Card 3: Plan Changes & Upgrades --}}
        <div class="report-kpi-card kpi-plans">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-arrows-rotate"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Plan Changes &amp; Upgrades</div>
                <div class="kpi-value" style="color: #7e22ce;" id="kpiPlanChanges">{{ number_format($metrics['plan_change_count'] ?? 0) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-arrow-up-right-dots me-1"></i>Subscriptions Modified
                </div>
            </div>
        </div>

        {{-- Card 4: Renewals & Reactivations --}}
        <div class="report-kpi-card kpi-renews">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Renewals &amp; Extensions</div>
                <div class="kpi-value text-success" id="kpiRenewCount">{{ number_format($metrics['renew_count'] ?? 0) }}</div>
                <div class="kpi-sub">
                    <i class="fa-solid fa-shield-heart me-1"></i>Memberships Extended
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Single-Line Filter Bar (Hidden by default, toggled on Filter Button click) --}}
    <div class="report-filter-wrapper" id="reportFilterContainer" style="{{ $hasCustomFilter ? '' : 'display: none;' }}">
        <form action="{{ route('activity.report') }}" method="GET" id="reportFilterForm" class="single-line-filter-form">
            <input type="hidden" name="learner_id" value="{{ $filterLearnerId }}" />

            {{-- 1. Year Filter --}}
            <div class="filter-col">
                <label for="filterYear" class="filter-inline-label"><i class="fa-regular fa-calendar"></i> Year</label>
                <select name="year" id="filterYear" class="form-select filter-control">
                    <option value="all">All Years</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ ((string)($filters['year'] ?? '') === (string)$y) ? 'selected' : '' }}>
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
                    @foreach($months as $m)
                        @php
                            $mPadded = str_pad($m, 2, '0', STR_PAD_LEFT);
                            $mName = DateTime::createFromFormat('!m', $m)->format('F');
                        @endphp
                        <option value="{{ $mPadded }}" {{ ((string)($filters['month'] ?? '') === (string)$mPadded) ? 'selected' : '' }}>
                            {{ $mName }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- 3. Activity / Operation Filter --}}
            <div class="filter-col">
                <label for="filterOperation" class="filter-inline-label"><i class="fa-solid fa-clock-rotate-left"></i> Activity</label>
                <select name="operation" id="filterOperation" class="form-select filter-control">
                    <option value="all">All Activities</option>
                    <option value="swapseat" {{ ($filters['operation'] ?? '') === 'swapseat' ? 'selected' : '' }}>Seat Swap</option>
                    <option value="changePlan" {{ ($filters['operation'] ?? '') === 'changePlan' ? 'selected' : '' }}>Change Plan</option>
                    <option value="learnerUpgrade" {{ ($filters['operation'] ?? '') === 'learnerUpgrade' ? 'selected' : '' }}>Plan Upgrade</option>
                    <option value="renewSeat" {{ ($filters['operation'] ?? '') === 'renewSeat' ? 'selected' : '' }}>Renew Seat</option>
                    <option value="closeSeat" {{ ($filters['operation'] ?? '') === 'closeSeat' ? 'selected' : '' }}>Close Seat</option>
                    <option value="reactive" {{ ($filters['operation'] ?? '') === 'reactive' ? 'selected' : '' }}>Reactivate</option>
                    <option value="deleteSeat" {{ ($filters['operation'] ?? '') === 'deleteSeat' ? 'selected' : '' }}>Delete Seat</option>
                    <option value="restoreSeat" {{ ($filters['operation'] ?? '') === 'restoreSeat' ? 'selected' : '' }}>Restore Seat</option>
                    <option value="freezePlan" {{ ($filters['operation'] ?? '') === 'freezePlan' ? 'selected' : '' }}>Freeze Plan</option>
                    <option value="unfreezePlan" {{ ($filters['operation'] ?? '') === 'unfreezePlan' ? 'selected' : '' }}>Unfreeze Plan</option>
                    <option value="giftDays" {{ ($filters['operation'] ?? '') === 'giftDays' ? 'selected' : '' }}>Gift Days</option>
                    <option value="edit" {{ ($filters['operation'] ?? '') === 'edit' ? 'selected' : '' }}>Edit Details</option>
                </select>
            </div>

            {{-- 4. Learner Status Filter --}}
            <div class="filter-col">
                <label for="filterStatus" class="filter-inline-label"><i class="fa-solid fa-user-check"></i> Learner Status</label>
                <select name="status" id="filterStatus" class="form-select filter-control">
                    <option value="all">All Statuses</option>
                    <option value="1" {{ (string)($filters['status'] ?? '') === '1' ? 'selected' : '' }}>Active Learners</option>
                    <option value="0" {{ (string)($filters['status'] ?? '') === '0' ? 'selected' : '' }}>Expired Learners</option>
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
                <span>Activities:</span>
                <span class="records-count-badge" id="visibleCountBadge">{{ count($learners) }}</span>
                <span class="text-muted small">logs</span>
            </div>
            <div class="records-search-box">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="cardSearchInput" placeholder="Search learner, seat, activity, value..." autocomplete="off" />
                <button type="button" class="btn-clear-search d-none" id="clearSearchBtn" title="Clear search">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        {{-- Desktop Column Header (Visible on Desktop >= 992px) --}}
        <div class="records-header-row">
            <div>Learner</div>
            <div class="text-center">Activity</div>
            <div class="text-center">Summary / Key Note</div>
            <div class="text-center">Date &amp; Time</div>
            <div class="text-center">Changes</div>
            <div class="text-center">Profile</div>
        </div>

        {{-- Records Container (Desktop Rows / Mobile Cards) --}}
        <div class="collection-records-grid" id="recordsContainer">
            @include('report.partials.activity_cards', ['learners' => $learners])
        </div>

        {{-- Live Search No-Results Placeholder --}}
        <div class="report-empty-state d-none" id="searchEmptyState">
            <i class="fa-solid fa-magnifying-glass empty-state-icon"></i>
            <h6 class="empty-state-title">No Matching Activity Logs Found</h6>
            <p class="small text-muted mb-3">
                No activity records matched your search query. Try typing another name, seat number, or operation.
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
        <p class="text-muted">You don't have permission to view the Learners Activity Report. Please contact your library administrator.</p>
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
    var $allCards = $('.activity-record-card');

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
        $allCards.addClass('d-none');

        // Show cards for active page
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

        // Update counts
        $('#visibleCountBadge').text(totalMatching);
        var startRecord = totalMatching > 0 ? ((currentPage - 1) * PAGE_SIZE + 1) : 0;
        var endRecord = Math.min(currentPage * PAGE_SIZE, totalMatching);
        $('#paginationInfoText').text('Showing ' + startRecord + ' to ' + endRecord + ' of ' + totalMatching + ' records');

        // Build Pagination List
        var $list = $('#paginationList');
        $list.empty();

        if (totalPages <= 1) {
            return;
        }

        // Previous button
        var prevDisabled = (currentPage === 1) ? ' disabled' : '';
        $list.append('<li class="page-item' + prevDisabled + '"><a class="page-link" href="#" data-page="' + (currentPage - 1) + '"><i class="fa-solid fa-chevron-left"></i></a></li>');

        // Page numbers with smart ellipsis
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
            $list.append('<li class="page-item' + activeClass + '"><a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>');
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                $list.append('<li class="page-item disabled"><span class="page-link">&hellip;</span></li>');
            }
            $list.append('<li class="page-item"><a class="page-link" href="#" data-page="' + totalPages + '">' + totalPages + '</a></li>');
        }

        // Next button
        var nextDisabled = (currentPage === totalPages) ? ' disabled' : '';
        $list.append('<li class="page-item' + nextDisabled + '"><a class="page-link" href="#" data-page="' + (currentPage + 1) + '"><i class="fa-solid fa-chevron-right"></i></a></li>');
    }

    // Pagination Click Handler
    $(document).on('click', '.activity-report-module .pagination-nav .page-link', function(e) {
        e.preventDefault();
        var targetPage = parseInt($(this).attr('data-page'));
        if (!isNaN(targetPage) && targetPage >= 1) {
            currentPage = targetPage;
            renderPagination();
            var offset = $('#recordsContainer').offset();
            if (offset) {
                $('html, body').animate({ scrollTop: offset.top - 120 }, 150);
            }
        }
    });

    // Instant Search Input
    $('#cardSearchInput').on('input', function() {
        var val = $(this).val();
        if (val.length > 0) {
            $('#clearSearchBtn').removeClass('d-none');
        } else {
            $('#clearSearchBtn').addClass('d-none');
        }
        currentPage = 1;
        renderPagination();
    });

    // Clear Search Buttons
    $('#clearSearchBtn, #btnResetSearch').on('click', function(e) {
        e.preventDefault();
        $('#cardSearchInput').val('').trigger('input');
    });

    // Initialize pagination on first load
    renderPagination();

    // 3. Seamless AJAX Filtering
    function executeAjaxFilter(urlParams) {
        var $container = $('#recordsContainer');
        var $applyBtn = $('#btnApplyFilter');

        $applyBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Filtering...');
        $container.addClass('ajax-loading-spinner');

        var reportUrl = "{{ route('activity.report') }}";
        if (urlParams) {
            reportUrl += '?' + urlParams;
        }

        $.ajax({
            url: reportUrl,
            type: 'GET',
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.status) {
                    // Update KPI Summary Badges
                    if (response.metrics) {
                        $('#kpiTotalActivities').text(response.metrics.total_activities);
                        $('#kpiSwapCount').text(response.metrics.swap_count);
                        $('#kpiPlanChanges').text(response.metrics.plan_change_count);
                        $('#kpiRenewCount').text(response.metrics.renew_count);
                    }

                    // Update Cards Grid
                    $container.html(response.html);

                    // Re-cache cards for search and pagination
                    $allCards = $('.activity-record-card');
                    $('#cardSearchInput').val('');
                    $('#clearSearchBtn').addClass('d-none');
                    currentPage = 1;
                    renderPagination();

                    // Update Filter Toggle Badge
                    var f = response.filters || {};
                    var isCustom = (f.year && f.year !== 'all') ||
                                   (f.month && f.month !== 'all') ||
                                   (f.operation && f.operation !== 'all') ||
                                   (f.status !== undefined && f.status !== '' && f.status !== 'all') ||
                                   (f.search && f.search !== '');

                    if (isCustom) {
                        if ($('#toggleFilterBtn .filter-badge-dot').length === 0) {
                            $('#toggleFilterBtn').append('<span class="filter-badge-dot" title="Active Filter Applied"></span>');
                        }
                        $('#toggleFilterBtn').addClass('active');
                    } else {
                        $('#toggleFilterBtn .filter-badge-dot').remove();
                        if (!$('#reportFilterContainer').is(':visible')) {
                            $('#toggleFilterBtn').removeClass('active');
                        }
                    }

                    // Update browser history URL
                    if (window.history && window.history.pushState) {
                        window.history.pushState(null, '', reportUrl);
                    }

                    // Reinitialize bootstrap tooltips if present
                    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                        tooltipTriggerList.map(function (tooltipTriggerEl) {
                            return new bootstrap.Tooltip(tooltipTriggerEl);
                        });
                    }
                }
            },
            error: function() {
                // Fallback to regular submission if AJAX fails
                $('#reportFilterForm')[0].submit();
            },
            complete: function() {
                $container.removeClass('ajax-loading-spinner');
                $applyBtn.prop('disabled', false).html('<i class="fa-solid fa-magnifying-glass"></i> Filter');
            }
        });
    }

    // Submit Filter Form via AJAX
    $('#reportFilterForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        executeAjaxFilter(formData);
    });

    // Reset Filter Button via AJAX
    $('#btnResetFilter').on('click', function(e) {
        e.preventDefault();
        $('#filterYear').val('all');
        $('#filterMonth').val('all');
        $('#filterOperation').val('all');
        $('#filterStatus').val('all');
        var formData = $('#reportFilterForm').serialize();
        executeAjaxFilter(formData);
    });

    // Empty state "View All Activities" button
    $(document).on('click', '#btnEmptyStateAll', function(e) {
        e.preventDefault();
        $('#btnResetFilter').click();
    });

    // 4. Client-side Instant CSV Export
    $('#btnExportReportCsv').on('click', function() {
        var rows = [];
        var headers = [
            'Seat No',
            'Learner Name',
            'Mobile',
            'Activity',
            'Summary',
            'Date & Time'
        ];
        rows.push(headers.map(function(h) { return '"' + h.replace(/"/g, '""') + '"'; }).join(','));

        var $exportCards = getFilteredCards();
        if ($exportCards.length === 0) {
            $exportCards = $allCards;
        }

        $exportCards.each(function() {
            var $c = $(this);
            var row = [
                $c.attr('data-seat') || '',
                $c.attr('data-name') || '',
                $c.attr('data-mobile') || '',
                $c.attr('data-operation') || '',
                $c.attr('data-summary') || '',
                $c.attr('data-date') || ''
            ];
            rows.push(row.map(function(val) {
                return '"' + String(val).replace(/"/g, '""') + '"';
            }).join(','));
        });

        var csvContent = "\uFEFF" + rows.join("\r\n");
        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement("a");
        link.setAttribute("href", url);
        var nowStr = new Date().toISOString().slice(0, 10);
        link.setAttribute("download", "Learner_Activity_Report_" + nowStr + ".csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // 5. Activity Value Changes Modal Comparison Engine
    function safeAtob(b64) {
        if (!b64) return '';
        try {
            return decodeURIComponent(escape(atob(b64)));
        } catch (e) {
            try {
                return atob(b64);
            } catch (err) {
                return '';
            }
        }
    }

    function tryParseJson(str) {
        if (!str || typeof str !== 'string') return null;
        var trimmed = str.trim();
        if ((trimmed.startsWith('{') && trimmed.endsWith('}')) || (trimmed.startsWith('[') && trimmed.endsWith(']'))) {
            try {
                return JSON.parse(trimmed);
            } catch (e) {
                return null;
            }
        }
        return null;
    }

    function flattenObject(ob, prefix) {
        prefix = prefix || '';
        var toReturn = {};
        for (var i in ob) {
            if (!ob.hasOwnProperty(i)) continue;
            if (typeof ob[i] === 'object' && ob[i] !== null && !Array.isArray(ob[i])) {
                var flatObject = flattenObject(ob[i], prefix + i + '.');
                for (var x in flatObject) {
                    if (!flatObject.hasOwnProperty(x)) continue;
                    toReturn[x] = flatObject[x];
                }
            } else {
                toReturn[prefix + i] = ob[i];
            }
        }
        return toReturn;
    }

    // Technical keys that should be filtered out from human comparison
    var technicalKeys = [
        'id', 'learner_id', 'learner_detail_id', 'library_id', 'branch_id',
        'updated_by', 'created_at', 'updated_at', 'deleted_at', 'remember_token'
    ];

    function isValueEmpty(val) {
        if (val === null || val === undefined) return true;
        if (typeof val === 'string') {
            var trimmed = val.trim();
            return trimmed === '' || trimmed === 'null' || trimmed === 'None' || trimmed === 'none';
        }
        if (typeof val === 'boolean') return false;
        if (typeof val === 'number') return false;
        if (typeof val === 'object') return Object.keys(val).length === 0;
        return false;
    }

    function formatIfDate(val) {
        if (!val || typeof val !== 'string') return null;
        var trimmed = val.trim();
        // ISO timestamp: 2026-08-30T12:39:09...
        if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/.test(trimmed)) {
            var d = new Date(trimmed);
            if (!isNaN(d.getTime())) {
                return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ', ' +
                       d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
            }
        }
        // YYYY-MM-DD
        if (/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) {
            var parts = trimmed.split('-');
            var year = parseInt(parts[0], 10);
            var month = parseInt(parts[1], 10) - 1;
            var day = parseInt(parts[2], 10);
            var d2 = new Date(year, month, day);
            if (!isNaN(d2.getTime())) {
                return d2.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            }
        }
        return null;
    }

    function isImageFile(val, key) {
        if (!val || typeof val !== 'string') return false;
        var lowerVal = val.toLowerCase();
        var lowerKey = (key || '').toLowerCase();
        return lowerVal.match(/\.(jpg|jpeg|png|webp|gif)(\?.*)?$/i) !== null || 
               lowerKey.includes('picture') || 
               lowerKey.includes('photo') || 
               (lowerVal.includes('upload/') && lowerVal.match(/\.(jpg|jpeg|png|webp|gif)/i));
    }

    function getFieldMeta(key) {
        if (!key) return { label: 'Attribute', icon: 'fa-tag' };
        var cleanKey = key.replace(/^(learner|detail|billing)\./, '').toLowerCase();
        
        var iconMap = {
            'profile_picture': 'fa-image',
            'picture': 'fa-image',
            'image': 'fa-image',
            'avatar': 'fa-image',
            'name': 'fa-user',
            'fullname': 'fa-user',
            'first_name': 'fa-user',
            'last_name': 'fa-user',
            'father_name': 'fa-user-tie',
            'mobile': 'fa-phone',
            'phone': 'fa-phone',
            'alternate_mobile': 'fa-square-phone',
            'alt_mobile': 'fa-square-phone',
            'email': 'fa-envelope',
            'seat_no': 'fa-chair',
            'seat_id': 'fa-chair',
            'seat': 'fa-chair',
            'plan_id': 'fa-calendar-days',
            'plan': 'fa-calendar-days',
            'plan_type': 'fa-calendar-days',
            'slot': 'fa-clock',
            'slot_id': 'fa-clock',
            'amount': 'fa-indian-rupee-sign',
            'fee': 'fa-indian-rupee-sign',
            'price': 'fa-indian-rupee-sign',
            'paid_amount': 'fa-indian-rupee-sign',
            'due_amount': 'fa-indian-rupee-sign',
            'start_date': 'fa-calendar-plus',
            'join_date': 'fa-calendar-plus',
            'end_date': 'fa-calendar-check',
            'expiry_date': 'fa-calendar-check',
            'status': 'fa-toggle-on',
            'gender': 'fa-venus-mars',
            'dob': 'fa-cake-candles',
            'address': 'fa-location-dot',
            'city': 'fa-city',
            'pincode': 'fa-map-pin',
            'locker_no': 'fa-vault',
            'id_proof_name': 'fa-id-card',
            'id_proof_file': 'fa-file-shield',
            'id_proof_number': 'fa-passport',
            'exam_id': 'fa-graduation-cap',
            'no_expiry': 'fa-infinity',
            'remark': 'fa-comment-dots',
            'payment_mode': 'fa-credit-card'
        };

        var clean = key.replace(/^(learner|detail|billing)\./, '');
        clean = clean.replace(/_/g, ' ');
        var label = clean.replace(/\b\w/g, function(l) { return l.toUpperCase(); });

        // Specific overrides for professional labels
        if (cleanKey === 'seat_no' || cleanKey === 'seat') label = 'Seat Assignment';
        if (cleanKey === 'dob') label = 'Date of Birth';
        if (cleanKey === 'no_expiry') label = 'Plan Expiry Setting';
        if (cleanKey === 'exam_id') label = 'Target Exam';
        if (cleanKey === 'id_proof_name') label = 'ID Proof Type';
        if (cleanKey === 'id_proof_number') label = 'ID Proof Number';
        if (cleanKey === 'alternate_mobile') label = 'Alternate Mobile';

        return { label: label, icon: iconMap[cleanKey] || 'fa-sliders' };
    }

    function formatDisplayValue(val, key) {
        if (isValueEmpty(val)) {
            if (key && (key.includes('picture') || key.includes('photo') || key.includes('image'))) {
                return '<span class="val-box-none"><i class="fa-solid fa-image-slash me-1"></i>No Photo Set</span>';
            }
            return '<span class="val-box-none"><i class="fa-solid fa-minus me-1"></i>Not Set</span>';
        }

        var lowerKey = (key || '').toLowerCase();
        var strVal = String(val).trim();

        // Check Profile Picture / Image
        if (isImageFile(strVal, key)) {
            var assetBase = "{{ asset('') }}";
            var cleanPath = strVal.replace(/^\/+/, '');
            var fullImgUrl = assetBase + cleanPath;
            var fileName = strVal.split('/').pop() || 'Photo';

            return '<div class="modal-diff-media-card text-start">' +
                   '  <div class="diff-media-thumb-box">' +
                   '    <img src="' + fullImgUrl + '" alt="Photo" class="diff-media-thumb" onerror="this.onerror=null; this.parentElement.innerHTML=\'<div class=\\\'diff-media-fallback\\\'><i class=\\\'fa-solid fa-image\\\'></i></div>\';" />' +
                   '  </div>' +
                   '  <div class="diff-media-meta text-start">' +
                   '    <span class="diff-media-name" title="' + $('<div>').text(strVal).html() + '">' + $('<div>').text(fileName).html() + '</span>' +
                   '    <a href="' + fullImgUrl + '" target="_blank" class="diff-media-link" title="Open full photo in new tab">' +
                   '      <i class="fa-solid fa-arrow-up-right-from-square"></i> View Full Photo' +
                   '    </a>' +
                   '  </div>' +
                   '</div>';
        }

        // Date check
        var formattedDate = formatIfDate(strVal);
        if (formattedDate) {
            return '<span class="fw-semibold" style="color: #1e293b;"><i class="fa-regular fa-calendar me-1 text-primary"></i>' + formattedDate + '</span>';
        }

        // Seat formatting
        if (lowerKey === 'seat_no' || lowerKey === 'seat' || lowerKey.endsWith('.seat_no')) {
            var seatNum = parseInt(strVal, 10);
            if (!isNaN(seatNum) && seatNum > 0) {
                return '<span class="modal-seat-pill"><i class="fa-solid fa-chair me-1"></i>SEAT ' + seatNum + '</span>';
            }
            if (strVal.toLowerCase() === 'general' || strVal === '0') {
                return '<span class="modal-seat-pill text-muted bg-light border-0"><i class="fa-solid fa-chair me-1"></i>GENERAL</span>';
            }
        }

        // Mobile formatting
        if ((lowerKey.includes('mobile') || lowerKey.includes('phone')) && strVal.replace(/\D/g, '').length === 10) {
            var cleanDigits = strVal.replace(/\D/g, '');
            return '<span class="fw-semibold" style="color: #1e293b;"><i class="fa-solid fa-phone me-1 text-muted"></i>+91 ' + cleanDigits + '</span>';
        }

        // Email formatting
        if (lowerKey === 'email' || lowerKey.endsWith('.email')) {
            if (strVal === 'false') return '<span class="val-box-none"><i class="fa-solid fa-minus me-1"></i>Not Provided</span>';
            return '<span class="fw-semibold" style="color: #1e293b;"><i class="fa-regular fa-envelope me-1 text-muted"></i>' + $('<div>').text(strVal).html() + '</span>';
        }

        // Boolean: no_expiry
        if (lowerKey.includes('no_expiry')) {
            return (strVal === '1' || strVal === 'true') 
                ? '<span class="badge bg-success-subtle text-success fw-semibold">Lifetime (No Expiry)</span>'
                : '<span class="badge bg-secondary-subtle text-secondary fw-semibold">Standard Expiry</span>';
        }

        // Status
        if (lowerKey === 'status' || lowerKey.endsWith('.status')) {
            return (strVal === '1' || strVal.toLowerCase() === 'active')
                ? '<span class="badge bg-success-subtle text-success fw-semibold">Active</span>'
                : '<span class="badge bg-danger-subtle text-danger fw-semibold">Inactive / Closed</span>';
        }

        // Currency (fee, amount, price, paid_amount)
        if (lowerKey.includes('amount') || lowerKey.includes('fee') || lowerKey.includes('price')) {
            var num = parseFloat(strVal);
            if (!isNaN(num)) {
                return '<span class="fw-semibold" style="color: #15803d;">₹' + num.toLocaleString('en-IN') + '</span>';
            }
        }

        return $('<div>').text(strVal).html();
    }

    $(document).on('click', '.btn-view-changes', function(e) {
        e.preventDefault();
        var $card = $(this).closest('.activity-record-card');
        var learnerName = $card.attr('data-name') || 'Learner';
        var seatDisplay = $card.attr('data-seat') || 'General';
        var mobile = $card.attr('data-mobile') || '';
        var opLabel = $card.attr('data-operation') || 'Activity';
        var opClass = $card.attr('data-op-class') || 'op-default';
        var opIcon = $card.attr('data-op-icon') || 'fa-clock-rotate-left';
        var dateStr = $card.attr('data-date') || '';
        var summaryText = $card.attr('data-summary') || '';
        var fieldName = $card.attr('data-field') || 'Updated Field';

        var rawOld = safeAtob($card.attr('data-old-b64'));
        var rawNew = safeAtob($card.attr('data-new-b64'));

        // Update Modal Header & Learner Strip
        $('#modalOpBadge').attr('class', 'modal-header-badge');
        $('#modalOpIcon').attr('class', 'fa-solid ' + opIcon);
        $('#modalOpTitle').text(opLabel);
        $('#modalLearnerName').text(learnerName);
        $('#modalLearnerAvatar').text((learnerName || 'L').charAt(0).toUpperCase());
        $('#modalLearnerMobile').html(mobile ? '<i class="fa-solid fa-phone me-1"></i> +91 ' + mobile : '');
        $('#modalDate').text(dateStr);

        var seatTagHtml = (seatDisplay !== 'General') 
            ? '<span class="modal-seat-pill"><i class="fa-solid fa-chair me-1"></i>SEAT ' + seatDisplay.toUpperCase() + '</span>' 
            : '<span class="modal-seat-pill text-muted bg-light border-0"><i class="fa-solid fa-chair me-1"></i>GENERAL</span>';
        $('#modalSeatTag').html(seatTagHtml);

        var jsonOld = tryParseJson(rawOld);
        var jsonNew = tryParseJson(rawNew);

        var rows = [];
        var changedFields = 0;

        if (jsonOld || jsonNew) {
            var flatOld = flattenObject(jsonOld || {});
            var flatNew = flattenObject(jsonNew || {});

            var allKeys = Array.from(new Set([...Object.keys(flatOld), ...Object.keys(flatNew)]));

            allKeys.forEach(function(k) {
                var baseKey = k.replace(/^(learner|detail|billing)\./, '').toLowerCase();
                if (technicalKeys.includes(baseKey)) return;

                var oVal = flatOld[k];
                var nVal = flatNew[k];

                var oEmpty = isValueEmpty(oVal);
                var nEmpty = isValueEmpty(nVal);

                // LOGICAL BRAIN: If BOTH old and new are empty/null/false, skip this field completely!
                if (oEmpty && nEmpty) {
                    return;
                }

                var strO = !oEmpty ? String(oVal).trim() : '';
                var strN = !nEmpty ? String(nVal).trim() : '';

                var isChanged = (strO !== strN);
                if (isChanged) changedFields++;

                var meta = getFieldMeta(k);
                rows.push({
                    key: meta.label,
                    rawKey: k,
                    oldVal: oVal,
                    newVal: nVal,
                    isChanged: isChanged
                });
            });
        } else {
            // Scalar comparison (e.g. swapseat)
            var oEmpty = isValueEmpty(rawOld);
            var nEmpty = isValueEmpty(rawNew);
            var strO = !oEmpty ? rawOld.trim() : '';
            var strN = !nEmpty ? rawNew.trim() : '';
            var isChanged = (strO !== strN);
            if (isChanged) changedFields++;

            var meta = getFieldMeta(fieldName);
            rows.push({
                key: meta.label,
                rawKey: fieldName,
                oldVal: rawOld,
                newVal: rawNew,
                isChanged: isChanged
            });
        }

        // Build Human-Friendly Logical Summary Banner
        var opCode = ($card.attr('data-operation') || '').toLowerCase();
        var bannerClass = 'banner-success';
        var summaryHtml = '';

        if (opCode.includes('swap')) {
            var oldS = rawOld ? (parseInt(rawOld, 10) > 0 ? 'Seat ' + rawOld : rawOld) : 'General';
            var newS = rawNew ? (parseInt(rawNew, 10) > 0 ? 'Seat ' + rawNew : rawNew) : seatDisplay;
            summaryHtml = '<i class="fa-solid fa-chair text-teal me-2 fs-6"></i>' +
                          '<span>Seat transferred from <strong>' + oldS + '</strong> ➔ <strong>' + newS + '</strong>.</span>';
            bannerClass = 'banner-info';
        } else if (changedFields === 1) {
            var changedItem = rows.find(r => r.isChanged);
            var fieldLabel = changedItem ? changedItem.key : 'Field';
            summaryHtml = '<i class="fa-solid fa-circle-check text-success me-2 fs-6"></i>' +
                          '<span>1 profile attribute updated: <strong>' + fieldLabel + '</strong> was modified.</span>';
            bannerClass = 'banner-success';
        } else if (changedFields > 1) {
            var changedNames = rows.filter(r => r.isChanged).map(r => r.key);
            var previewText = changedNames.slice(0, 3).join(', ') + (changedNames.length > 3 ? ' +' + (changedNames.length - 3) + ' more' : '');
            summaryHtml = '<i class="fa-solid fa-circle-check text-success me-2 fs-6"></i>' +
                          '<span><strong>' + changedFields + ' fields updated</strong> (' + previewText + ').</span>';
            bannerClass = 'banner-success';
        } else {
            summaryHtml = '<i class="fa-solid fa-circle-info text-primary me-2 fs-6"></i>' +
                          '<span><strong>' + opLabel + ' completed.</strong> No profile attributes were altered in this operation log.</span>';
            bannerClass = 'banner-info';
        }

        $('#modalSummaryText').html(summaryHtml);
        $('#modalSummaryWrapper').removeClass('d-none banner-success banner-info banner-warning').addClass(bannerClass);

        // Populate Table Rows
        var $tbody = $('#modalDiffTableBody');
        $tbody.empty();

        if (rows.length === 0) {
            $('#modalNoChangesAlert').removeClass('d-none');
            $('.diff-table-wrapper').addClass('d-none');
            $('#modalChangedBadgeCount').text('0 Changes').attr('style', 'background-color: #64748b !important; color: #fff;');
            $('#modalChangesSummary').text('No active fields recorded');
        } else {
            $('#modalNoChangesAlert').addClass('d-none');
            $('.diff-table-wrapper').removeClass('d-none');
            
            if (changedFields > 0) {
                $('#modalChangedBadgeCount').text(changedFields + ' Change' + (changedFields > 1 ? 's' : ''))
                                           .attr('style', 'background-color: #15803d !important; color: #fff; font-weight: 600;');
                $('#modalChangesSummary').text('out of ' + rows.length + ' active attribute' + (rows.length > 1 ? 's' : ''));
            } else {
                $('#modalChangedBadgeCount').text('0 Changes')
                                           .attr('style', 'background-color: #64748b !important; color: #fff; font-weight: 600;');
                $('#modalChangesSummary').text(rows.length + ' active attributes (identical)');
            }

            // Changed rows first, then alphabetically
            rows.sort(function(a, b) {
                if (a.isChanged && !b.isChanged) return -1;
                if (!a.isChanged && b.isChanged) return 1;
                return a.key.localeCompare(b.key);
            });

            rows.forEach(function(item) {
                var rowClass = item.isChanged ? 'diff-row-changed' : 'diff-row-unchanged';
                var tr = $('<tr class="' + rowClass + '"></tr>');

                var fieldMeta = getFieldMeta(item.rawKey || item.key);

                // Column 1: Field Name (Left-aligned, 28% width)
                var tdKey = $('<td style="text-align: left !important; vertical-align: middle !important; width: 28%;">' +
                              '<div class="diff-field-cell text-start">' +
                              '  <span class="diff-field-icon"><i class="fa-solid ' + fieldMeta.icon + '"></i></span>' +
                              '  <span class="diff-field-label" title="' + $('<div>').text(item.key).html() + '">' + $('<div>').text(fieldMeta.label).html() + '</span>' +
                              '</div>' +
                              '</td>');

                // Column 2: Old Value (Left-aligned, 32% width)
                var oldContent = formatDisplayValue(item.oldVal, item.rawKey);
                var oldHtml = '';
                if (item.isChanged) {
                    if (isImageFile(String(item.oldVal || ''), item.rawKey) || oldContent.includes('modal-diff-media-card') || oldContent.includes('modal-seat-pill')) {
                        oldHtml = oldContent;
                    } else if (isValueEmpty(item.oldVal)) {
                        oldHtml = oldContent;
                    } else {
                        oldHtml = '<span class="val-box-old"><del>' + oldContent + '</del></span>';
                    }
                } else {
                    oldHtml = '<span class="val-box-normal">' + oldContent + '</span>';
                }

                var tdOld = $('<td style="text-align: left !important; vertical-align: middle !important; width: 32%;">' +
                              '<div class="text-start">' + oldHtml + '</div>' +
                              '</td>');

                // Column 3: New Value (Left-aligned with right-docked status badge, 40% width)
                var newContent = formatDisplayValue(item.newVal, item.rawKey);
                var newHtml = '';
                var statusBadge = '';

                if (item.isChanged) {
                    if (isImageFile(String(item.newVal || ''), item.rawKey) || newContent.includes('modal-diff-media-card')) {
                        newHtml = newContent;
                    } else {
                        newHtml = '<span class="val-box-new"><i class="fa-solid fa-arrow-right me-1 text-success"></i>' + newContent + '</span>';
                    }
                    statusBadge = '<span class="badge-changed-pill ms-auto flex-shrink-0"><i class="fa-solid fa-check me-1"></i>Changed</span>';
                } else {
                    newHtml = '<span class="val-box-normal">' + newContent + '</span>';
                    statusBadge = '<span class="badge-unchanged-pill ms-auto flex-shrink-0">Unchanged</span>';
                }

                var tdNew = $('<td style="text-align: left !important; vertical-align: middle !important; width: 40%;">' +
                              '<div class="d-flex align-items-center justify-content-between gap-2 text-start w-100">' +
                              '  <div class="text-start flex-grow-1 min-w-0">' + newHtml + '</div>' +
                              '  ' + statusBadge +
                              '</div>' +
                              '</td>');

                tr.append(tdKey, tdOld, tdNew);
                $tbody.append(tr);
            });
        }

        // Apply toggle filter (Show Changed Only)
        // If there are changed fields, default to show changed only
        if (changedFields > 0) {
            $('#toggleOnlyChangedSwitch').prop('checked', true);
            $('#toggleOnlyChangedWrapper').removeClass('d-none');
        } else {
            $('#toggleOnlyChangedSwitch').prop('checked', false);
            $('#toggleOnlyChangedWrapper').addClass('d-none');
        }
        applyModalFilter();

        // Show Modal
        var modalEl = document.getElementById('activityChangeModal');
        if (modalEl) {
            var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            bsModal.show();
        }
    });

    function applyModalFilter() {
        var onlyChanged = $('#toggleOnlyChangedSwitch').is(':checked');
        if (onlyChanged) {
            $('#modalDiffTableBody tr.diff-row-unchanged').hide();
        } else {
            $('#modalDiffTableBody tr.diff-row-unchanged').show();
        }
    }

    $('#toggleOnlyChangedSwitch').on('change', function() {
        applyModalFilter();
    });

    // Reinitialize tooltips on page load
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});
</script>

{{-- 6. VALUE CHANGES MODAL (Beautiful & Strictly Aligned) --}}
<div class="modal fade" id="activityChangeModal" tabindex="-1" aria-labelledby="activityChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span id="modalOpBadge" class="modal-header-badge">
                        <i id="modalOpIcon" class="fa-solid fa-clock-rotate-left"></i> <span id="modalOpTitle">Activity</span>
                    </span>
                    <h5 class="modal-title mb-0" id="activityChangeModalLabel" style="color: #ffffff !important; font-weight: 600; font-size: 1.08rem; font-family: 'Outfit', sans-serif;">Value Change Details</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Learner Meta Strip -->
                <div class="modal-learner-strip d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="modal-learner-avatar-circle" id="modalLearnerAvatar">L</div>
                        <div>
                            <div id="modalSeatTag"></div>
                            <h6 id="modalLearnerName" class="modal-learner-name"></h6>
                            <div id="modalLearnerMobile" class="modal-learner-mobile"></div>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="modal-log-label">Logged Date &amp; Time</div>
                        <div id="modalDate" class="modal-log-date"></div>
                    </div>
                </div>

                <div class="p-3 p-md-4">
                    <!-- Summary Bar (if available) -->
                    <div id="modalSummaryWrapper" class="modal-summary-banner mb-3 d-none">
                        <div id="modalSummaryText" class="fw-semibold d-flex align-items-center flex-wrap gap-1"></div>
                    </div>

                    <!-- Changes Count & Filter Toggle -->
                    <div class="modal-controls-bar mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge rounded-pill" style="background-color: #18225f; color: #ffffff; font-size: 0.76rem; font-weight: 600; padding: 4px 10px;" id="modalChangedBadgeCount">0</span>
                            <span class="text-secondary small fw-medium" id="modalChangesSummary">active attributes</span>
                        </div>
                        <div class="form-check form-switch m-0 d-flex align-items-center gap-2" id="toggleOnlyChangedWrapper">
                            <input class="form-check-input mt-0" type="checkbox" role="switch" id="toggleOnlyChangedSwitch" checked>
                            <label class="form-check-label small fw-semibold text-secondary" for="toggleOnlyChangedSwitch" style="cursor: pointer;">Show Changed Only</label>
                        </div>
                    </div>

                    <!-- Comparison Table Wrapper -->
                    <div class="diff-table-wrapper">
                        <table class="table diff-table align-middle mb-0" style="width: 100%; table-layout: fixed; margin: 0;">
                            <thead>
                                <tr>
                                    <th style="width: 28%; text-align: left !important; padding: 10px 16px;">FIELD / ATTRIBUTE</th>
                                    <th style="width: 32%; text-align: left !important; padding: 10px 16px;">PREVIOUS VALUE</th>
                                    <th style="width: 40%; text-align: left !important; padding: 10px 16px;">NEW VALUE</th>
                                </tr>
                            </thead>
                            <tbody id="modalDiffTableBody">
                                <!-- Populated dynamically by JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty Changes State (if identical) -->
                    <div id="modalNoChangesAlert" class="text-center py-5 px-3 d-none">
                        <i class="fa-solid fa-circle-check text-success fs-1 mb-2"></i>
                        <h6 class="fw-bold mb-1" style="color: #18225f;">No Values Changed</h6>
                        <p class="text-muted small mb-0">The previous and new values recorded for this activity are identical.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2 px-4 bg-light border-top">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal" style="background-color: #64748b; border: none; border-radius: 6px; font-weight: 600;">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection