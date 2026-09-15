@extends('layouts.library')

@section('title', 'Monthly Attendance Report')

@section('content')

@php
    use Carbon\Carbon;
    $currentYear = (int) date('Y');
    $currentMonth = (int) date('m');
@endphp

{{-- Dedicated Scoped Stylesheet for Attendance Report --}}
<link rel="stylesheet" href="{{ asset('public/css/attendance-report.css') }}?v={{ time() }}" />

<div class="attendance-report-module">

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

    @can('has-permission','Attendence Report')

    {{-- 1. TOP ACTION BAR (RIGHT-ALIGNED, NO REDUNDANT HEADING BLOCK) --}}
    <div class="header-actions">
        <button type="button" class="btn-filter-toggle {{ !empty($hasCustomFilter) ? 'active' : '' }}" id="toggleFilterBtn" title="Toggle Filters Bar">
            <i class="fa-solid fa-filter"></i> Filter
            @if(!empty($hasCustomFilter))
                <span class="filter-badge-dot" title="Active Filter Applied"></span>
            @endif
        </button>
        <button type="button" class="btn-export-csv" id="btnExportReportCsv" title="Export Attendance Matrix to CSV">
            <i class="fa-solid fa-file-csv"></i> Export CSV
        </button>
        <button type="button" class="btn-report-print" id="btnPrintReport" title="Print Attendance Report">
            <i class="fa-solid fa-print"></i> Print
        </button>
    </div>

    {{-- 2. KPI SUMMARY CARDS BAR --}}
    <div class="report-kpis-grid">
        {{-- KPI 1: Total Active Learners --}}
        <div class="report-kpi-card kpi-total">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Total Learners</div>
                <div class="kpi-value" id="kpiTotalLearners">{{ $metrics['total_learners'] ?? 0 }}</div>
                <div class="kpi-sub" id="kpiMonthLabel">
                    {{ DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}
                </div>
            </div>
        </div>

        {{-- KPI 2: Total Present Marks --}}
        <div class="report-kpi-card kpi-present">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Total Present</div>
                <div class="kpi-value" id="kpiTotalPresent">{{ $metrics['total_present'] ?? 0 }}</div>
                <div class="kpi-sub">Across {{ $daymonth }} month days</div>
            </div>
        </div>

        {{-- KPI 3: Total Absent Marks --}}
        <div class="report-kpi-card kpi-absent">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Total Absent</div>
                <div class="kpi-value" id="kpiTotalAbsent">{{ $metrics['total_absent'] ?? 0 }}</div>
                <div class="kpi-sub">Unattended slots</div>
            </div>
        </div>

        {{-- KPI 4: Average Attendance Rate --}}
        <div class="report-kpi-card kpi-rate">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-chart-pie"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Attendance Rate</div>
                <div class="kpi-value" id="kpiAvgRate">{{ $metrics['avg_rate'] ?? 0 }}%</div>
                <div class="kpi-sub" id="kpiHighCount">{{ $metrics['high_attendance_count'] ?? 0 }} students &gt; 75%</div>
            </div>
        </div>
    </div>

    {{-- 3. SINGLE-LINE FILTER BAR (COLLAPSIBLE, HIDDEN BY DEFAULT) --}}
    <div class="report-filter-wrapper {{ !empty($hasCustomFilter) ? '' : 'd-none' }}" id="reportFilterContainer">
        <form id="reportFilterForm" action="{{ route('attendance.report') }}" method="GET" class="single-line-filter-form">
            {{-- Year --}}
            <div>
                <label for="filterYear" class="filter-group-label">Select Year</label>
                <select id="filterYear" class="filter-select" name="year">
                    @foreach($dynamicyears as $y)
                        <option value="{{ $y }}" {{ (int)$year === (int)$y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Month --}}
            <div>
                <label for="filterMonth" class="filter-group-label">Select Month</label>
                <select id="filterMonth" class="filter-select" name="month">
                    @foreach($dynamicmonths as $m)
                        @php $mInt = (int)$m; @endphp
                        <option value="{{ str_pad($mInt, 2, '0', STR_PAD_LEFT) }}" {{ (int)$month === $mInt ? 'selected' : '' }}>
                            {{ DateTime::createFromFormat('!m', $mInt)->format('F') }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Quick Search by Student or Seat --}}
            <div class="filter-col-search">
                <label for="filterSearch" class="filter-group-label">Search Learner / Seat</label>
                <input type="text" id="filterSearch" name="search" class="filter-input" 
                       placeholder="Student name, mobile or seat..." 
                       value="{{ request('search') ?? '' }}" />
            </div>

            {{-- Action Buttons --}}
            <div class="filter-col-actions d-flex gap-2">
                <button type="submit" class="btn-filter-apply" id="btnApplyFilter">
                    <i class="fa-solid fa-magnifying-glass"></i> Filter
                </button>
                <button type="button" class="btn-filter-reset" id="btnResetFilter" title="Reset Filters">
                    <i class="fa-solid fa-rotate-right"></i> Reset
                </button>
            </div>
        </form>
    </div>

    {{-- 4. CONTROLS BAR (REALTIME SEARCH & LEGEND) --}}
    <div class="matrix-controls-bar">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="matrix-search-box">
                <i class="fa-solid fa-magnifying-glass matrix-search-icon"></i>
                <input type="text" id="matrixSearchInput" class="matrix-search-input" placeholder="Search learner on this sheet..." />
                <button type="button" id="clearMatrixSearchBtn" class="matrix-search-clear d-none" title="Clear search">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <span class="badge rounded-pill" style="background-color: #18225f; color: #ffffff; font-size: 0.78rem; font-weight: 600; padding: 6px 12px;" id="matrixLearnerCountBadge">
                {{ count($learnerAttendance) }} Learners
            </span>
        </div>

        <div class="matrix-legend">
            <span class="legend-item">
                <span class="legend-badge legend-p">P</span> Present
            </span>
            <span class="legend-item">
                <span class="legend-badge legend-a">A</span> Absent
            </span>
            <span class="legend-item">
                <span class="legend-badge legend-dash">-</span> No Record
            </span>
            <span class="legend-item">
                <span class="legend-badge legend-sun"><i class="fa-solid fa-sun" style="font-size: 0.65rem;"></i></span> Sunday
            </span>
        </div>
    </div>

    {{-- 5. ATTENDANCE MATRIX CONTAINER --}}
    <div id="attendanceMatrixContainer">
        @include('report.partials.attendance_matrix')
    </div>

    {{-- 6. ATTENDANCE BREAKDOWN MODAL --}}
    <div class="modal fade" id="attendanceDetailModal" tabindex="-1" aria-labelledby="attendanceDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-calendar-check text-white me-1"></i>
                        <h5 class="modal-title mb-0" id="attendanceDetailModalLabel" style="color: #ffffff !important; font-weight: 600;">Learner Attendance Breakdown</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <!-- Learner Info Strip -->
                    <div class="modal-learner-strip d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="modal-learner-avatar-circle" id="modalAttAvatar">L</div>
                            <div>
                                <div id="modalAttSeatTag"></div>
                                <h6 id="modalAttLearnerName" class="modal-learner-name"></h6>
                                <div id="modalAttMobile" class="modal-learner-mobile"></div>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge" id="modalAttRateBadge" style="font-size: 0.82rem; font-weight: 600; padding: 4px 10px;"></span>
                        </div>
                    </div>

                    <div class="p-3 p-md-4">
                        <!-- Stats Grid -->
                        <div class="modal-stats-grid">
                            <div class="modal-stat-card">
                                <div class="modal-stat-val text-success" id="modalAttPresentCount">0</div>
                                <div class="modal-stat-lbl">Days Present</div>
                            </div>
                            <div class="modal-stat-card">
                                <div class="modal-stat-val text-danger" id="modalAttAbsentCount">0</div>
                                <div class="modal-stat-lbl">Days Absent</div>
                            </div>
                            <div class="modal-stat-card">
                                <div class="modal-stat-val text-secondary" id="modalAttUnrecordedCount">0</div>
                                <div class="modal-stat-lbl">Unrecorded / Off</div>
                            </div>
                        </div>

                        <!-- Days Breakdown Header -->
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="small fw-semibold text-secondary text-uppercase" style="letter-spacing: 0.5px;">Day-by-Day Attendance</span>
                            <small class="text-muted" id="modalAttMonthSub"></small>
                        </div>

                        <!-- Days Calendar Grid -->
                        <div class="modal-days-grid" id="modalAttDaysContainer">
                            <!-- Populated dynamically by JS -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 px-4 bg-light border-top d-flex justify-content-between align-items-center">
                    <a href="#" id="modalAttProfileBtn" class="btn btn-outline-primary btn-sm px-3" style="border-radius: 6px; font-weight: 600; color: #18225f; border-color: #18225f;" target="_blank">
                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> View Full Profile
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal" style="border-radius: 6px; font-weight: 600;">Close</button>
                </div>
            </div>
        </div>
    </div>

    @else
    <div class="card text-center py-5 border-0 shadow-sm" style="border-radius: 12px;">
        <i class="fa-solid fa-lock text-danger fs-1 mb-3"></i>
        <h5 class="fw-bold" style="color: #18225f;">Permission Restricted</h5>
        <p class="text-muted mb-0">You don't have permission to view the Monthly Attendance Report. Please contact your administrator.</p>
    </div>
    @endcan

</div>

{{-- JAVASCRIPT LOGIC --}}
<script>
$(document).ready(function() {

    // 1. Toggle Filter Bar with animation
    $('#toggleFilterBtn').on('click', function(e) {
        e.preventDefault();
        var $filter = $('#reportFilterContainer');
        if ($filter.is(':visible')) {
            $filter.slideUp(200, function() {
                $filter.addClass('d-none');
            });
            $(this).removeClass('active');
        } else {
            $filter.removeClass('d-none').hide().slideDown(200);
            $(this).addClass('active');
            $('#filterSearch').focus();
        }
    });

    // 2. AJAX Dynamic Filter Execution
    function executeAttendanceFilter(formData) {
        var $container = $('#attendanceMatrixContainer');
        var $applyBtn = $('#btnApplyFilter');

        $applyBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Filtering...');
        $container.css('opacity', '0.5');

        var reportUrl = "{{ route('attendance.report') }}";

        $.ajax({
            url: reportUrl,
            type: 'GET',
            data: formData,
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    // Update Matrix HTML
                    $container.html(response.html);

                    // Update KPI summary cards
                    if (response.metrics) {
                        $('#kpiTotalLearners').text(response.metrics.total_learners);
                        $('#kpiTotalPresent').text(response.metrics.total_present);
                        $('#kpiTotalAbsent').text(response.metrics.total_absent);
                        $('#kpiAvgRate').text(response.metrics.avg_rate + '%');
                        $('#kpiHighCount').text(response.metrics.high_attendance_count + ' students > 75%');
                    }

                    // Update matrix learner count badge
                    var rowCount = $('#attendanceMatrixTbody tr.att-learner-row').length;
                    $('#matrixLearnerCountBadge').text(rowCount + ' Learners');

                    // Clear client-side filter
                    $('#matrixSearchInput').val('');
                    $('#clearMatrixSearchBtn').addClass('d-none');

                    // Update filter badge dot
                    var currentY = "{{ (int)date('Y') }}";
                    var currentM = "{{ (int)date('m') }}";
                    var f = response.filters || {};
                    var hasCustom = (parseInt(f.year) !== parseInt(currentY)) ||
                                    (parseInt(f.month) !== parseInt(currentM)) ||
                                    (f.search && f.search.trim() !== '');

                    if (hasCustom) {
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

                    // Re-initialize tooltips if available
                    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                        tooltipTriggerList.map(function(el) { return new bootstrap.Tooltip(el); });
                    }
                }
            },
            error: function() {
                // Fallback to regular GET submit
                $('#reportFilterForm')[0].submit();
            },
            complete: function() {
                $container.css('opacity', '1');
                $applyBtn.prop('disabled', false).html('<i class="fa-solid fa-magnifying-glass"></i> Filter');
            }
        });
    }

    // Submit Filter Form via AJAX
    $('#reportFilterForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        executeAttendanceFilter(formData);
    });

    // Reset Filters via AJAX
    $('#btnResetFilter').on('click', function(e) {
        e.preventDefault();
        $('#filterYear').val("{{ (int)date('Y') }}");
        $('#filterMonth').val("{{ str_pad((int)date('m'), 2, '0', STR_PAD_LEFT) }}");
        $('#filterSearch').val('');
        var formData = $('#reportFilterForm').serialize();
        executeAttendanceFilter(formData);
    });

    // 3. Instant Real-Time Client Search across Loaded Table
    $('#matrixSearchInput').on('keyup input', function() {
        var term = $(this).val().toLowerCase().trim();
        if (term.length > 0) {
            $('#clearMatrixSearchBtn').removeClass('d-none');
        } else {
            $('#clearMatrixSearchBtn').addClass('d-none');
        }

        var visibleCount = 0;
        $('#attendanceMatrixTbody tr.att-learner-row').each(function() {
            var searchData = $(this).attr('data-search') || '';
            if (term === '' || searchData.indexOf(term) > -1) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });

        $('#matrixLearnerCountBadge').text(visibleCount + ' Learners');
    });

    $('#clearMatrixSearchBtn').on('click', function() {
        $('#matrixSearchInput').val('').trigger('input');
    });

    // 4. Client-side Instant CSV Export
    $('#btnExportReportCsv').on('click', function() {
        var rows = [];
        var headers = ['Seat No', 'Learner Name', 'Mobile'];

        // Get day column numbers from table headers
        var dayCount = $('#attendanceMatrixTable thead th.day-col-header').length;
        for (var d = 1; d <= dayCount; d++) {
            headers.push('Day ' + d);
        }
        headers.push('Total Present', 'Total Absent', 'Rate (%)');
        rows.push(headers.map(function(h) { return '"' + h.replace(/"/g, '""') + '"'; }).join(','));

        $('#attendanceMatrixTbody tr.att-learner-row:visible').each(function() {
            var $tr = $(this);
            var $btn = $tr.find('.btn-show-att-modal');
            var seat = $btn.attr('data-seat') || 'General';
            var name = $btn.attr('data-name') || '';
            var mobile = $btn.attr('data-mobile') || '';
            var tp = $btn.attr('data-present') || '0';
            var ta = $btn.attr('data-absent') || '0';
            var rate = $btn.attr('data-rate') || '0';

            var b64Daily = $btn.attr('data-daily-b64');
            var dailyMap = {};
            if (b64Daily) {
                try {
                    dailyMap = JSON.parse(decodeURIComponent(escape(atob(b64Daily))));
                } catch(e) {
                    try { dailyMap = JSON.parse(atob(b64Daily)); } catch(err) {}
                }
            }

            var rowData = [seat, name, mobile];
            for (var d = 1; d <= dayCount; d++) {
                rowData.push(dailyMap[d] || '-');
            }
            rowData.push(tp, ta, rate + '%');

            rows.push(rowData.map(function(val) {
                return '"' + String(val).replace(/"/g, '""') + '"';
            }).join(','));
        });

        var csvContent = "\uFEFF" + rows.join("\r\n");
        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement("a");
        link.setAttribute("href", url);
        var selectedY = $('#filterYear').val() || "{{ $year }}";
        var selectedM = $('#filterMonth').val() || "{{ $month }}";
        link.setAttribute("download", "Monthly_Attendance_Report_" + selectedY + "_" + selectedM + ".csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // 5. Print Trigger
    $('#btnPrintReport').on('click', function() {
        window.print();
    });

    // 6. Attendance Detail Modal Handler
    $(document).on('click', '.btn-show-att-modal', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var name = $btn.attr('data-name') || 'Learner';
        var seat = $btn.attr('data-seat') || 'General';
        var mobile = $btn.attr('data-mobile') || '';
        var present = parseInt($btn.attr('data-present'), 10) || 0;
        var absent = parseInt($btn.attr('data-absent'), 10) || 0;
        var rate = parseFloat($btn.attr('data-rate')) || 0;
        var learnerId = $btn.attr('data-id') || '';

        var b64Daily = $btn.attr('data-daily-b64');
        var dailyMap = {};
        if (b64Daily) {
            try {
                dailyMap = JSON.parse(decodeURIComponent(escape(atob(b64Daily))));
            } catch(e) {
                try { dailyMap = JSON.parse(atob(b64Daily)); } catch(err) {}
            }
        }

        // Set Learner Strip
        $('#modalAttAvatar').text(name.charAt(0).toUpperCase());
        $('#modalAttLearnerName').text(name);
        $('#modalAttMobile').html(mobile && mobile !== '-' ? '<i class="fa-solid fa-phone me-1"></i> +91 ' + mobile : '');

        var seatTagHtml = (seat !== 'General') 
            ? '<span class="modal-seat-pill"><i class="fa-solid fa-chair me-1"></i>SEAT ' + seat.toUpperCase() + '</span>' 
            : '<span class="modal-seat-pill text-muted bg-light border-0"><i class="fa-solid fa-chair me-1"></i>GENERAL</span>';
        $('#modalAttSeatTag').html(seatTagHtml);

        // Rate Badge
        var rateBadgeClass = 'bg-secondary-subtle text-secondary';
        if (present + absent > 0) {
            if (rate >= 75) rateBadgeClass = 'bg-success-subtle text-success';
            else if (rate >= 50) rateBadgeClass = 'bg-warning-subtle text-warning';
            else rateBadgeClass = 'bg-danger-subtle text-danger';
        }
        $('#modalAttRateBadge').attr('class', 'badge ' + rateBadgeClass).text(rate + '% Attendance');

        // Stats Counters
        $('#modalAttPresentCount').text(present);
        $('#modalAttAbsentCount').text(absent);

        var totalDays = Object.keys(dailyMap).length || 31;
        var unrecorded = Math.max(0, totalDays - (present + absent));
        $('#modalAttUnrecordedCount').text(unrecorded);

        // Populate Days Grid
        var $grid = $('#modalAttDaysContainer');
        $grid.empty();

        for (var day = 1; day <= totalDays; day++) {
            var st = dailyMap[day] || '-';
            var stClass = 'status-dash';
            var stBadge = '<span class="text-muted fw-bold">-</span>';

            if (st === 'P') {
                stClass = 'status-p';
                stBadge = '<span class="text-success fw-bold"><i class="fa-solid fa-check me-1"></i>P</span>';
            } else if (st === 'A') {
                stClass = 'status-a';
                stBadge = '<span class="text-danger fw-bold"><i class="fa-solid fa-xmark me-1"></i>A</span>';
            }

            var dayEl = $('<div class="modal-day-badge ' + stClass + '">' +
                          '<div class="small fw-semibold text-secondary" style="font-size: 0.68rem;">Day ' + day + '</div>' +
                          '<div class="mt-1" style="font-size: 0.8rem;">' + stBadge + '</div>' +
                          '</div>');
            $grid.append(dayEl);
        }

        // Profile button link
        if (learnerId) {
            var profileUrl = "{{ url('learners') }}/" + learnerId;
            $('#modalAttProfileBtn').attr('href', profileUrl).removeClass('d-none');
        } else {
            $('#modalAttProfileBtn').addClass('d-none');
        }

        // Show Modal
        var modalEl = document.getElementById('attendanceDetailModal');
        if (modalEl) {
            var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            bsModal.show();
        }
    });

});
</script>

@endsection