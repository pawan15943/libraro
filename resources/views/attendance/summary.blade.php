@extends('layouts.library')
@section('content')

<style>
    /* ==========================================================================
       1. SHIMMER SKELETON ANIMATIONS & BASE
       ========================================================================== */
    @keyframes attShimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }

    .att-shimmer-box {
        background: linear-gradient(90deg, #edf2f7 0%, #e2e8f0 25%, #ffffff 50%, #e2e8f0 75%, #edf2f7 100%);
        background-size: 200% 100%;
        animation: attShimmer 1.4s infinite linear;
        border-radius: 6px;
        display: inline-block;
        vertical-align: middle;
    }

    .att-skeleton-container {
        display: block;
        transition: opacity 0.25s ease, visibility 0.25s ease;
    }

    .att-content-container {
        opacity: 0;
        transition: opacity 0.35s ease;
    }

    .att-content-container.is-ready {
        opacity: 1 !important;
    }

    .att-skeleton-container.is-hidden {
        opacity: 0 !important;
        visibility: hidden !important;
        display: none !important;
    }

    /* ==========================================================================
       2. TOP LEARNER PROFILE HEADER CARD
       ========================================================================== */
    .att-learner-header {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px 22px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        box-shadow: 0 2px 10px rgba(7, 21, 111, 0.04);
        flex-wrap: wrap;
    }

    .att-learner-profile {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .att-avatar {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: linear-gradient(135deg, #07156f 0%, #1e3a8a 100%);
        color: #ffffff;
        font-weight: 600;
        font-size: 1.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(7, 21, 111, 0.22);
        flex-shrink: 0;
    }

    .att-profile-info h4 {
        margin: 0 0 4px 0;
        color: #07156f;
        font-weight: 600;
        font-size: 1.25rem;
        letter-spacing: -0.2px;
    }

    .att-profile-badges {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .att-header-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.76rem;
        font-weight: 500;
        color: #475569;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        padding: 3px 9px;
        border-radius: 6px;
    }

    .att-header-pill.highlight {
        color: #07156f;
        background: #eef2ff;
        border-color: #c7d2fe;
    }

    .att-header-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .att-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #07156f;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .att-back-btn:hover {
        background: #07156f;
        color: #ffffff;
        border-color: #07156f;
        box-shadow: 0 3px 8px rgba(7, 21, 111, 0.15);
    }

    /* ==========================================================================
       3. STICKY CALENDAR CARD
       ========================================================================== */
    @media (min-width: 992px) {
        .att-sticky-sidebar {
            position: -webkit-sticky;
            position: sticky;
            top: 80px;
            align-self: flex-start;
            z-index: 20;
        }
    }

    .att-calendar-card {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #ffffff;
        padding: 20px;
        box-shadow: 0 4px 16px rgba(7, 21, 111, 0.05);
        transition: box-shadow 0.2s ease;
    }

    .att-calendar-card:hover {
        box-shadow: 0 6px 20px rgba(7, 21, 111, 0.08);
    }

    .att-calendar-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }

    .att-calendar-nav .btn-nav {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #f0f3ff;
        color: #07156f;
        display: grid;
        place-items: center;
        border: 1px solid #d4dcff;
        text-decoration: none;
        flex: 0 0 auto;
        transition: all 0.2s ease;
    }

    .att-calendar-nav .btn-nav:hover {
        background: #07156f;
        color: #ffffff;
        border-color: #07156f;
        transform: scale(1.05);
    }

    .att-calendar-nav .btn-nav.disabled {
        background: #f1f5f9;
        color: #94a3b8;
        border-color: #e2e8f0;
        pointer-events: none;
        opacity: 0.7;
    }

    .att-calendar-nav h5 {
        margin: 0;
        color: #07156f;
        font-weight: 600;
        font-size: 1.05rem;
        text-align: center;
        letter-spacing: -0.1px;
    }

    .att-weekdays {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 5px;
        text-align: center;
        color: #64748b;
        font-weight: 500;
        font-size: 0.75rem;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .att-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 5px;
    }

    .att-cell {
        border-radius: 8px;
        padding: 7px 2px;
        text-align: center;
        min-height: 52px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        transition: all 0.15s ease;
        border: 1px solid transparent;
    }

    .att-cell .day-num {
        font-weight: 600;
        font-size: 0.88rem;
        line-height: 1;
    }

    .att-cell .day-hours {
        display: block;
        font-size: 0.65rem;
        font-weight: 500;
        margin-top: 3px;
        opacity: 0.9;
    }

    .att-cell.out-of-month, .att-cell.out-of-range {
        color: #cbd5e1;
        background: #f8fafc;
    }

    .att-cell.status-full {
        background: #e6f9ed;
        color: #0d8a43;
        border-color: #bbf0cc;
    }

    .att-cell.status-half {
        background: #fffbeb;
        color: #b45309;
        border-color: #fed7aa;
    }

    .att-cell.status-absent {
        background: #fef2f2;
        color: #dc2626;
        border-color: #fecaca;
    }

    .att-cell.selectable {
        cursor: pointer;
    }

    .att-cell.selectable:hover {
        transform: translateY(-2px);
        box-shadow: 0 3px 8px rgba(0,0,0,0.06);
    }

    .att-cell.selected {
        outline: 2.5px solid #07156f !important;
        outline-offset: -1px;
        font-weight: 800;
    }

    .att-legend {
        display: flex;
        gap: 14px;
        margin-top: 16px;
        padding-top: 12px;
        border-top: 1px solid #f1f5f9;
        font-size: 0.78rem;
        color: #64748b;
        flex-wrap: wrap;
        justify-content: space-between;
    }

    .att-legend span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 500;
    }

    .att-legend .dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        flex: 0 0 auto;
    }

    .dot-full { background: #10b981; }
    .dot-half { background: #f59e0b; }
    .dot-absent { background: #ef4444; }

    /* ==========================================================================
       4. ENGAGING KPI STATS METRIC CARDS (CLEAN WITHOUT ICONS)
       ========================================================================== */
    .att-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 22px;
    }

    .att-kpi-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 18px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        transition: all 0.22s ease;
        box-shadow: 0 2px 8px rgba(7, 21, 111, 0.03);
        position: relative;
        overflow: hidden;
    }

    .att-kpi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
    }

    .att-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(7, 21, 111, 0.08);
    }

    /* Card 1: Present */
    .att-kpi-card.kpi-present::before { background: linear-gradient(90deg, #10b981, #34d399); }
    .att-kpi-card.kpi-present:hover { border-color: #a7f3d0; }

    /* Card 2: Half Day */
    .att-kpi-card.kpi-half::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
    .att-kpi-card.kpi-half:hover { border-color: #fde68a; }

    /* Card 3: Absent */
    .att-kpi-card.kpi-absent::before { background: linear-gradient(90deg, #ef4444, #f87171); }
    .att-kpi-card.kpi-absent:hover { border-color: #fecaca; }

    /* Card 4: Total Study */
    .att-kpi-card.kpi-total::before { background: linear-gradient(90deg, #2563eb, #3b82f6); }
    .att-kpi-card.kpi-total:hover { border-color: #bfdbfe; }

    .att-kpi-body {
        width: 100%;
    }

    .att-kpi-label {
        font-size: 0.78rem;
        font-weight: 500;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        display: block;
        margin-bottom: 4px;
    }

    .att-kpi-val-row {
        display: flex;
        align-items: baseline;
        gap: 5px;
    }

    .att-kpi-num {
        font-size: 1.55rem;
        font-weight: 600;
        color: #0f172a;
        line-height: 1.1;
        letter-spacing: -0.3px;
    }

    .att-kpi-unit {
        font-size: 0.8rem;
        font-weight: 500;
        color: #94a3b8;
    }

    .att-kpi-pill {
        display: inline-block;
        font-size: 0.68rem;
        font-weight: 500;
        padding: 2px 8px;
        border-radius: 4px;
        margin-top: 6px;
        width: fit-content;
    }

    .pill-present { background: #dcfce7; color: #15803d; }
    .pill-half { background: #fef3c7; color: #b45309; }
    .pill-absent { background: #fee2e2; color: #b91c1c; }
    .pill-total { background: #dbeafe; color: #1d4ed8; }

    /* ==========================================================================
       5. ATTENDANCE LOGS LIST & ROWS
       ========================================================================== */
    .att-logs-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e2e8f0;
    }

    .att-logs-header-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .att-logs-header h5 {
        margin: 0;
        color: #07156f;
        font-weight: 800;
        font-size: 1.15rem;
        letter-spacing: -0.3px;
    }

    .att-logs-count-chip {
        background: #eef2ff;
        color: #07156f;
        border: 1px solid #c7d2fe;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 20px;
    }

    #attShowAll {
        display: none;
        font-size: 0.82rem;
        font-weight: 600;
        color: #2563eb;
        text-decoration: none;
        margin-left: 8px;
    }

    #attShowAll:hover {
        text-decoration: underline;
    }

    .att-log-row {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #ffffff;
        padding: 13px 18px;
        display: grid;
        grid-template-columns: 52px minmax(0, 1fr) auto auto;
        gap: 16px;
        align-items: center;
        margin-bottom: 12px;
        text-decoration: none;
        color: inherit;
        transition: all 0.2s ease;
        box-shadow: 0 1px 4px rgba(7, 21, 111, 0.03);
    }

    .att-log-row:hover {
        border-color: #07156f;
        transform: translateY(-1.5px);
        box-shadow: 0 6px 18px rgba(7, 21, 111, 0.09);
        color: inherit;
    }

    .att-log-row.has-overtime-row {
        border-left: 4px solid #dc2626;
    }

    .att-log-date-badge {
        border-radius: 10px;
        text-align: center;
        padding: 6px 4px;
        font-weight: 700;
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #e2e8f0;
    }

    .att-log-date-badge.status-full {
        background: linear-gradient(135deg, #07156f 0%, #1e3a8a 100%);
        color: #ffffff;
        border-color: #07156f;
    }

    .att-log-date-badge.status-absent {
        background: #fee2e2;
        color: #dc2626;
        border-color: #fca5a5;
    }

    .att-log-date-badge small {
        display: block;
        font-size: 0.66rem;
        font-weight: 700;
        letter-spacing: 0.4px;
    }

    .att-log-date-badge .num {
        font-size: 1.15rem;
        line-height: 1.1;
    }

    .att-date-title {
        font-weight: 700;
        color: #1e293b;
        font-size: 0.96rem;
    }

    .att-shift-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.7rem;
        font-weight: 600;
        color: #475569;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        padding: 2px 7px;
        border-radius: 4px;
    }

    .att-log-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 4px;
    }

    .att-punch-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.8rem;
        color: #475569;
    }

    .att-punch-pill i {
        font-size: 0.75rem;
    }

    .att-log-hours {
        text-align: right;
        color: #07156f;
        font-weight: 700;
        white-space: nowrap;
    }

    .att-hours-val {
        font-size: 0.96rem;
        font-weight: 700;
    }

    .att-overtime-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.72rem;
        font-weight: 700;
        color: #b91c1c;
        background: #fee2e2;
        border: 1px solid #fca5a5;
        padding: 2px 8px;
        border-radius: 6px;
        margin-top: 3px;
        box-shadow: 0 1px 3px rgba(220, 38, 38, 0.08);
    }

    .att-view-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        color: #07156f;
        background: #f0f3ff;
        border: 1px solid #d4dcff;
        border-radius: 8px;
        padding: 7px 14px;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .att-log-row:hover .att-view-btn {
        background: #07156f;
        color: #ffffff;
        border-color: #07156f;
    }

    .att-mobile-chevron {
        display: none;
    }

    /* ==========================================================================
       6. RESPONSIVE BREAKPOINTS
       ========================================================================== */
    @media (max-width: 991px) {
        .att-kpi-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .att-sticky-sidebar {
            position: static !important;
            margin-bottom: 20px;
        }
    }

    @media (max-width: 767px) {
        .att-learner-header {
            padding: 12px 16px;
        }
        .att-kpi-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        .att-kpi-card {
            padding: 12px 14px;
        }
        .att-kpi-num {
            font-size: 1.35rem;
        }
        .att-calendar-card {
            padding: 14px;
        }
        .att-log-row {
            grid-template-columns: 44px minmax(0, 1fr) auto;
            padding: 11px 12px;
            gap: 10px;
        }
        .att-log-hours {
            text-align: right;
            font-size: 0.85rem;
        }
        .att-log-meta {
            gap: 8px;
        }
        .att-punch-pill {
            font-size: 0.75rem;
        }
        .att-view-btn {
            display: none !important;
        }
        .att-mobile-chevron {
            display: block !important;
            color: #94a3b8;
        }
    }

    @media (max-width: 480px) {
        .att-cell {
            min-height: 42px;
            padding: 4px 1px;
        }
        .att-cell .day-num {
            font-size: 0.8rem;
        }
        .att-cell .day-hours {
            font-size: 0.6rem;
        }
        .att-calendar-nav .btn-nav {
            width: 32px;
            height: 32px;
        }
    }
</style>

<div class="attendance-summary-wrapper mt-2 mb-4">

    <!-- ====================================================================
         SHIMMER SKELETON (Visible instantly on page load / month navigation)
         ==================================================================== -->
    <div class="att-skeleton-container" id="attSkeletonContainer" aria-hidden="true">
        <!-- Top Profile Banner Skeleton -->
        <div class="att-learner-header" style="background:#fff; border-color:#e2e8f0;">
            <div class="att-learner-profile">
                <div class="att-shimmer-box" style="width: 52px; height: 52px; border-radius: 14px;"></div>
                <div>
                    <div class="att-shimmer-box" style="width: 160px; height: 22px; margin-bottom: 6px;"></div>
                    <div style="display: flex; gap: 8px;">
                        <div class="att-shimmer-box" style="width: 90px; height: 16px;"></div>
                        <div class="att-shimmer-box" style="width: 100px; height: 16px;"></div>
                    </div>
                </div>
            </div>
            <div>
                <div class="att-shimmer-box" style="width: 120px; height: 34px; border-radius: 8px;"></div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column: Calendar Skeleton -->
            <div class="col-lg-4">
                <div class="att-calendar-card" style="background:#fff;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                        <div class="att-shimmer-box" style="width:36px; height:36px; border-radius:10px;"></div>
                        <div class="att-shimmer-box" style="width:130px; height:20px;"></div>
                        <div class="att-shimmer-box" style="width:36px; height:36px; border-radius:10px;"></div>
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(7, 1fr); gap:5px; margin-bottom:10px;">
                        @for($w = 0; $w < 7; $w++)
                            <div class="att-shimmer-box" style="width:100%; height:14px;"></div>
                        @endfor
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(7, 1fr); gap:5px; margin-bottom:16px;">
                        @for($c = 0; $c < 35; $c++)
                            <div class="att-shimmer-box" style="width:100%; height:50px; border-radius:8px;"></div>
                        @endfor
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <div class="att-shimmer-box" style="width:80px; height:14px;"></div>
                        <div class="att-shimmer-box" style="width:80px; height:14px;"></div>
                        <div class="att-shimmer-box" style="width:70px; height:14px;"></div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Stats & Logs Skeleton -->
            <div class="col-lg-8">
                <!-- 4 KPI Cards Skeleton (No Icons) -->
                <div class="att-kpi-grid">
                    @for($k = 0; $k < 4; $k++)
                    <div class="att-kpi-card" style="background:#fff;">
                        <div class="att-shimmer-box" style="width:70px; height:12px; margin-bottom:6px;"></div>
                        <div class="att-shimmer-box" style="width:90px; height:26px; margin-bottom:6px;"></div>
                        <div class="att-shimmer-box" style="width:75px; height:12px;"></div>
                    </div>
                    @endfor
                </div>

                <!-- Logs Header Skeleton -->
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid #e2e8f0;">
                    <div class="att-shimmer-box" style="width:180px; height:22px;"></div>
                    <div class="att-shimmer-box" style="width:80px; height:18px; border-radius:20px;"></div>
                </div>

                <!-- 4 Log Row Skeletons -->
                @for($r = 0; $r < 4; $r++)
                <div class="att-log-row" style="background:#fff; pointer-events:none;">
                    <div class="att-shimmer-box" style="width:52px; height:46px; border-radius:10px;"></div>
                    <div>
                        <div class="att-shimmer-box" style="width:140px; height:18px; margin-bottom:6px;"></div>
                        <div style="display:flex; gap:10px;">
                            <div class="att-shimmer-box" style="width:110px; height:14px;"></div>
                            <div class="att-shimmer-box" style="width:110px; height:14px;"></div>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div class="att-shimmer-box" style="width:75px; height:18px;"></div>
                    </div>
                    <div>
                        <div class="att-shimmer-box" style="width:90px; height:32px; border-radius:8px;"></div>
                    </div>
                </div>
                @endfor
            </div>
        </div>
    </div>

    <!-- ====================================================================
         REAL PAGE CONTENT
         ==================================================================== -->
    <div class="att-content-container" id="attContentContainer">

        <!-- 1. Top Learner Profile Banner -->
        <div class="att-learner-header">
            <div class="att-learner-profile">
                <div class="att-avatar">
                    {{ $learnerInitials ?? 'LR' }}
                </div>
                <div class="att-profile-info">
                    <h4>{{ $learnerName }}</h4>
                    <div class="att-profile-badges">
                        <span class="att-header-pill highlight" title="Learner ID">
                            <i class="fa-solid fa-id-card"></i> {{ $learnerNo ?: 'LRN-' . $learnerId }}
                        </span>
                        @if(!empty($branchName))
                            <span class="att-header-pill" title="Library Branch">
                                <i class="fa-solid fa-building-columns"></i> {{ $branchName }}
                            </span>
                        @endif
                        @if(!empty($membershipTag))
                            <span class="att-header-pill" title="Seat & Plan">
                                <i class="fa-solid fa-chair"></i> {{ $membershipTag }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="att-header-actions">
                <a href="{{ route('learners') }}" class="att-back-btn" title="Back to Learners List">
                    <i class="fa-solid fa-arrow-left"></i> All Learners
                </a>
            </div>
        </div>

        <!-- 2. Main Grid: Left (Sticky Calendar) & Right (KPIs + Logs) -->
        <div class="row">
            <!-- Left Column: Sticky Calendar Card -->
            <div class="col-lg-4 att-sticky-sidebar">
                <div class="att-calendar-card">
                    <div class="att-calendar-nav">
                        @if($canGoPrev)
                            <a class="btn-nav" href="{{ route('attendance.summary', $learnerId) }}?year={{ $prevMonth->year }}&month={{ $prevMonth->month }}" title="Previous Month">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        @else
                            <span class="btn-nav disabled"><i class="fa-solid fa-chevron-left"></i></span>
                        @endif

                        <h5>{{ $displayMonth->format('F Y') }}</h5>

                        @if($canGoNext)
                            <a class="btn-nav" href="{{ route('attendance.summary', $learnerId) }}?year={{ $nextMonth->year }}&month={{ $nextMonth->month }}" title="Next Month">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        @else
                            <span class="btn-nav disabled"><i class="fa-solid fa-chevron-right"></i></span>
                        @endif
                    </div>

                    <div class="att-weekdays">
                        <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                    </div>

                    <div class="att-grid" id="attCalendarGrid">
                        @foreach($calendarCells as $cell)
                            @if(!$cell['in_month'])
                                <div class="att-cell out-of-month"><span class="day-num">{{ $cell['day'] }}</span></div>
                            @elseif(!$cell['in_range'])
                                <div class="att-cell out-of-range"><span class="day-num">{{ $cell['day'] }}</span></div>
                            @else
                                <div class="att-cell status-{{ $cell['status'] }} selectable {{ $cell['date'] === $selectedDate ? 'selected' : '' }}"
                                     data-att-date="{{ $cell['date'] }}"
                                     title="{{ \Carbon\Carbon::parse($cell['date'])->format('d M Y') }} · Status: {{ ucfirst($cell['status']) }}">
                                    <span class="day-num">{{ $cell['day'] }}</span>
                                    <small class="day-hours">{{ $cell['status'] === 'absent' ? '0h' : ($cell['hours'] !== null ? rtrim(rtrim(number_format($cell['hours'], 1), '0'), '.').'h' : '') }}</small>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <div class="att-legend">
                        <span><i class="dot dot-full"></i> Present</span>
                        <span><i class="dot dot-half"></i> Half Day</span>
                        <span><i class="dot dot-absent"></i> Absent</span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Engaging KPI Cards & Attendance Logs -->
            <div class="col-lg-8">
                <!-- 4 Engaging KPI Metric Cards (Without Icons) -->
                <div class="att-kpi-grid">
                    <!-- KPI 1: Present -->
                    <div class="att-kpi-card kpi-present">
                        <div class="att-kpi-body">
                            <span class="att-kpi-label">Present</span>
                            <div class="att-kpi-val-row">
                                <span class="att-kpi-num">{{ $presentCount }}</span>
                                <span class="att-kpi-unit">days</span>
                            </div>
                            <span class="att-kpi-pill pill-present">Full Sessions</span>
                        </div>
                    </div>

                    <!-- KPI 2: Half Day -->
                    <div class="att-kpi-card kpi-half">
                        <div class="att-kpi-body">
                            <span class="att-kpi-label">Half Day</span>
                            <div class="att-kpi-val-row">
                                <span class="att-kpi-num">{{ $halfCount }}</span>
                                <span class="att-kpi-unit">days</span>
                            </div>
                            <span class="att-kpi-pill pill-half">Partial Punch</span>
                        </div>
                    </div>

                    <!-- KPI 3: Absent -->
                    <div class="att-kpi-card kpi-absent">
                        <div class="att-kpi-body">
                            <span class="att-kpi-label">Absent</span>
                            <div class="att-kpi-val-row">
                                <span class="att-kpi-num">{{ $absentCount }}</span>
                                <span class="att-kpi-unit">days</span>
                            </div>
                            <span class="att-kpi-pill pill-absent">Not Attended</span>
                        </div>
                    </div>

                    <!-- KPI 4: Total Study Hours -->
                    <div class="att-kpi-card kpi-total">
                        <div class="att-kpi-body">
                            <span class="att-kpi-label">Total Study</span>
                            <div class="att-kpi-val-row">
                                <span class="att-kpi-num">{{ $totalHoursLabel }}</span>
                                <span class="att-kpi-unit">hrs</span>
                            </div>
                            <span class="att-kpi-pill pill-total">Total Duration</span>
                        </div>
                    </div>
                </div>

                <!-- Logs Header Bar -->
                <div class="att-logs-header">
                    <div class="att-logs-header-left">
                        <h5>Attendance History & Logs</h5>
                        <span class="att-logs-count-chip">
                            <span id="attLogsCount">{{ count($logs) }}</span> Entries
                        </span>
                        <a href="javascript:void(0)" id="attShowAll"><i class="fa-solid fa-rotate-left me-1"></i>(Show All Days)</a>
                    </div>
                </div>

                <!-- Attendance Logs List (Descending Order) -->
                <div id="attLogsList">
                    @forelse($logs as $log)
                        <a href="{{ route('attendance.logs.page', [$learnerId, $log['date']]) }}" 
                           class="att-log-row {{ !empty($log['has_overtime']) ? 'has-overtime-row' : '' }}" 
                           data-att-log-date="{{ $log['date'] }}"
                           title="View Details for {{ \Carbon\Carbon::parse($log['date'])->format('d M Y') }}">
                            <div class="att-log-date-badge status-{{ $log['status'] }}">
                                <small>{{ strtoupper($log['day_name']) }}</small>
                                <span class="num">{{ $log['day_num'] }}</span>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="att-date-title">{{ \Carbon\Carbon::parse($log['date'])->format('d M Y') }}</span>
                                    <i class="fa-solid fa-circle" style="font-size: 0.45rem; color: {{ $log['status'] === 'full' ? '#10b981' : ($log['status'] === 'half' ? '#f59e0b' : '#ef4444') }};"></i>
                                    @if(!empty($log['shift_text']))
                                        <span class="att-shift-pill" title="Assigned Shift">
                                            <i class="fa-regular fa-id-badge"></i> {{ $log['shift_text'] }}
                                        </span>
                                    @endif
                                </div>
                                <div class="att-log-meta">
                                    <span class="att-punch-pill">
                                        <i class="fa-solid fa-arrow-right text-success"></i> 
                                        <b>In:</b> {{ $log['check_in'] ?? 'No Check-in' }}
                                    </span>
                                    <span class="att-punch-pill">
                                        <i class="fa-solid fa-arrow-left text-danger"></i> 
                                        <b>Out:</b> {{ $log['check_out'] ?? ($log['is_inside'] ? 'In Library' : 'No Check-out') }}{{ $log['implied_out'] ? ' (shift end)' : '' }}
                                    </span>
                                </div>
                            </div>
                            <div class="att-log-hours">
                                <div class="att-hours-val">
                                    <i class="fa-regular fa-clock me-1"></i>{{ $log['hours'] !== null ? number_format($log['hours'], 2) : '0.00' }} Hrs
                                </div>
                                @if(!empty($log['has_overtime']))
                                    <div class="att-overtime-pill" title="Overstay: +{{ $log['overtime_text'] }} beyond assigned shift">
                                        <i class="fa-solid fa-clock-rotate-left"></i> +{{ $log['overtime_text'] }} Extra
                                    </div>
                                @endif
                            </div>
                            <div class="att-action-wrap">
                                <span class="att-view-btn">View Details <i class="fa-solid fa-arrow-right"></i></span>
                                <i class="fa-solid fa-chevron-right att-mobile-chevron"></i>
                            </div>
                        </a>
                    @empty
                        <div class="text-muted text-center py-5 bg-white rounded-4 border">
                            <i class="fa-regular fa-calendar-xmark fa-2x text-muted mb-2 d-block"></i>
                            No attendance records found for this period.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Silky smooth shimmer transition
        var skel = document.getElementById('attSkeletonContainer');
        var content = document.getElementById('attContentContainer');

        function revealContent() {
            if (skel) {
                skel.style.opacity = '0';
                setTimeout(function () {
                    skel.classList.add('is-hidden');
                    if (content) content.classList.add('is-ready');
                }, 180);
            } else if (content) {
                content.classList.add('is-ready');
            }
        }

        // Quick reveal after initial DOM paint (220ms gives smooth polished feel)
        setTimeout(revealContent, 220);

        // Fallback safety timeout
        setTimeout(function() {
            if (content && !content.classList.contains('is-ready')) {
                revealContent();
            }
        }, 600);

        // Interactive Filtering Logic
        const grid = document.getElementById('attCalendarGrid');
        const logRows = document.querySelectorAll('#attLogsList [data-att-log-date]');
        const countEl = document.getElementById('attLogsCount');
        const showAllLink = document.getElementById('attShowAll');
        const totalCount = logRows.length;

        function filterLogs(date) {
            let visible = 0;
            logRows.forEach(function (row) {
                const match = row.dataset.attLogDate === date;
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            if (countEl) countEl.textContent = visible;
            if (showAllLink) showAllLink.style.display = 'inline-block';
        }

        function showAll() {
            logRows.forEach(function (row) { row.style.display = ''; });
            if (countEl) countEl.textContent = totalCount;
            if (showAllLink) showAllLink.style.display = 'none';
        }

        if (grid) {
            grid.addEventListener('click', function (event) {
                const cell = event.target.closest('.att-cell.selectable');
                if (!cell) return;

                grid.querySelectorAll('.att-cell.selected').forEach(function (el) {
                    el.classList.remove('selected');
                });
                cell.classList.add('selected');
                filterLogs(cell.dataset.attDate);
            });
        }

        if (showAllLink) {
            showAllLink.addEventListener('click', function (event) {
                event.preventDefault();
                grid.querySelectorAll('.att-cell.selected').forEach(function (el) {
                    el.classList.remove('selected');
                });
                showAll();
            });
        }
    });
</script>

@endsection
