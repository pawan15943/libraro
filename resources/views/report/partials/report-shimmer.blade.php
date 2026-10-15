{{-- Reusable Report Pages Shimmer Skeleton Loader --}}
@php
    $shimmerType = $type ?? 'default';
@endphp

{{-- Instantly suppress full-page blocking overlay loader (#loaderone) so shimmer skeleton is front and center --}}
<style>
    #loaderone, #loader {
        display: none !important;
        opacity: 0 !important;
        visibility: hidden !important;
        pointer-events: none !important;
        z-index: -9999 !important;
    }
</style>
<script>
    try {
        var el1 = document.getElementById('loaderone');
        if (el1) { el1.style.display = 'none'; el1.remove(); }
        var el0 = document.getElementById('loader');
        if (el0) { el0.style.display = 'none'; el0.remove(); }
    } catch(e) {}
</script>

<div id="reportShimmer" class="report-shimmer-container type-{{ $shimmerType }}" aria-hidden="true">

    {{-- 1. Top Action Buttons Bar --}}
    <div class="report-shimmer-top-actions">
        <span class="report-skeleton-box report-shimmer-btn-filter"></span>
        <span class="report-skeleton-box report-shimmer-btn-export"></span>
    </div>

    {{-- 2. 4 Financial & Operational Summary KPI Cards --}}
    <div class="report-shimmer-kpi-grid">
        <div class="report-shimmer-kpi-card">
            <span class="report-skeleton-box report-shimmer-kpi-icon"></span>
            <div class="report-shimmer-kpi-content">
                <span class="report-skeleton-box report-shimmer-kpi-label"></span>
                <span class="report-skeleton-box report-shimmer-kpi-value"></span>
            </div>
        </div>
        <div class="report-shimmer-kpi-card">
            <span class="report-skeleton-box report-shimmer-kpi-icon"></span>
            <div class="report-shimmer-kpi-content">
                <span class="report-skeleton-box report-shimmer-kpi-label" style="width: 110px;"></span>
                <span class="report-skeleton-box report-shimmer-kpi-value" style="width: 140px;"></span>
            </div>
        </div>
        <div class="report-shimmer-kpi-card">
            <span class="report-skeleton-box report-shimmer-kpi-icon"></span>
            <div class="report-shimmer-kpi-content">
                <span class="report-skeleton-box report-shimmer-kpi-label" style="width: 85px;"></span>
                <span class="report-skeleton-box report-shimmer-kpi-value" style="width: 115px;"></span>
            </div>
        </div>
        <div class="report-shimmer-kpi-card">
            <span class="report-skeleton-box report-shimmer-kpi-icon"></span>
            <div class="report-shimmer-kpi-content">
                <span class="report-skeleton-box report-shimmer-kpi-label" style="width: 100px;"></span>
                <span class="report-skeleton-box report-shimmer-kpi-value" style="width: 95px;"></span>
            </div>
        </div>
    </div>

    {{-- 3. Quick Filter Tabs --}}
    <div class="report-shimmer-quick-tabs">
        <span class="report-skeleton-box report-shimmer-tab-pill" style="width: 110px;"></span>
        <span class="report-skeleton-box report-shimmer-tab-pill" style="width: 135px;"></span>
        <span class="report-skeleton-box report-shimmer-tab-pill" style="width: 145px;"></span>
        <span class="report-skeleton-box report-shimmer-tab-pill" style="width: 125px;"></span>
        <span class="report-skeleton-box report-shimmer-tab-pill" style="width: 95px;"></span>
        <span class="report-skeleton-box report-shimmer-tab-pill" style="width: 100px;"></span>
    </div>

    {{-- 4. Records Wrapper --}}
    <div class="report-shimmer-records-wrapper">
        {{-- Controls Bar --}}
        <div class="report-shimmer-controls-bar">
            <span class="report-skeleton-box report-shimmer-count-pill"></span>
            <span class="report-skeleton-box report-shimmer-search-box"></span>
        </div>

        @if($shimmerType === 'default')
            {{-- Desktop Header Row (6 Columns) --}}
            <div class="report-shimmer-header-row">
                <span class="report-skeleton-box report-shimmer-header-col" style="width: 100px;"></span>
                <span class="report-skeleton-box report-shimmer-header-col" style="width: 90px;"></span>
                <span class="report-skeleton-box report-shimmer-header-col" style="width: 110px; margin: 0 auto;"></span>
                <span class="report-skeleton-box report-shimmer-header-col" style="width: 100px; margin: 0 auto;"></span>
                <span class="report-skeleton-box report-shimmer-header-col" style="width: 60px; margin: 0 auto;"></span>
                <span class="report-skeleton-box report-shimmer-header-col" style="width: 50px; margin: 0 auto;"></span>
            </div>

            {{-- 5 Record Rows --}}
            @for($i = 0; $i < 5; $i++)
            <div class="report-shimmer-record-row">
                {{-- Col 1: Identity --}}
                <div class="report-shimmer-identity-cluster">
                    <span class="report-skeleton-box report-shimmer-avatar"></span>
                    <div class="report-shimmer-identity-details">
                        <span class="report-skeleton-box report-shimmer-seat-tag"></span>
                        <span class="report-skeleton-box report-shimmer-name"></span>
                        <span class="report-skeleton-box report-shimmer-submeta"></span>
                    </div>
                </div>

                {{-- Col 2: Plan & Duration --}}
                <div class="report-shimmer-plan-col">
                    <span class="report-skeleton-box report-shimmer-plan-name"></span>
                    <span class="report-skeleton-box report-shimmer-plan-sub"></span>
                </div>

                {{-- Col 3: Financials --}}
                <div class="report-shimmer-financials-col">
                    <span class="report-skeleton-box report-shimmer-fin-pill"></span>
                    <span class="report-skeleton-box report-shimmer-fin-pill"></span>
                    <span class="report-skeleton-box report-shimmer-fin-pill"></span>
                </div>

                {{-- Col 4: Date & Mode --}}
                <div class="report-shimmer-date-col">
                    <span class="report-skeleton-box report-shimmer-date-text"></span>
                    <span class="report-skeleton-box report-shimmer-mode-pill"></span>
                </div>

                {{-- Col 5: Status --}}
                <div class="report-shimmer-status-col">
                    <span class="report-skeleton-box report-shimmer-status-pill"></span>
                </div>

                {{-- Col 6: Action --}}
                <div class="report-shimmer-action-col">
                    <span class="report-skeleton-box report-shimmer-action-btn"></span>
                    <span class="report-skeleton-box report-shimmer-action-btn"></span>
                </div>
            </div>
            @endfor

        @elseif($shimmerType === 'activity')
            {{-- Activity Log Cards --}}
            @for($i = 0; $i < 5; $i++)
            <div class="report-shimmer-activity-card">
                <span class="report-skeleton-box report-shimmer-activity-icon"></span>
                <div class="report-shimmer-activity-body">
                    <div class="report-shimmer-activity-top">
                        <div class="d-flex align-items-center gap-2">
                            <span class="report-skeleton-box" style="width: 140px; height: 18px; border-radius: 4px;"></span>
                            <span class="report-skeleton-box" style="width: 80px; height: 22px; border-radius: 20px;"></span>
                        </div>
                        <span class="report-skeleton-box" style="width: 110px; height: 14px; border-radius: 4px;"></span>
                    </div>
                    <div class="report-shimmer-activity-diff">
                        <span class="report-skeleton-box" style="width: 120px; height: 22px; border-radius: 6px;"></span>
                        <span class="report-skeleton-box" style="width: 15px; height: 12px; border-radius: 2px;"></span>
                        <span class="report-skeleton-box" style="width: 130px; height: 22px; border-radius: 6px;"></span>
                    </div>
                </div>
            </div>
            @endfor

        @elseif($shimmerType === 'revenue')
            {{-- Desktop Header Row for Revenue Table --}}
            <div class="report-shimmer-header-row" style="grid-template-columns: 1.5fr 1fr 1fr 1fr 1fr 80px;">
                <span class="report-skeleton-box report-shimmer-header-col" style="width: 100px;"></span>
                <span class="report-skeleton-box report-shimmer-header-col" style="width: 90px;"></span>
                <span class="report-skeleton-box report-shimmer-header-col" style="width: 90px;"></span>
                <span class="report-skeleton-box report-shimmer-header-col" style="width: 90px;"></span>
                <span class="report-skeleton-box report-shimmer-header-col" style="width: 70px;"></span>
                <span class="report-skeleton-box report-shimmer-header-col" style="width: 40px; margin: 0 auto;"></span>
            </div>

            @for($i = 0; $i < 5; $i++)
            <div class="report-shimmer-record-row" style="grid-template-columns: 1.5fr 1fr 1fr 1fr 1fr 80px;">
                <span class="report-skeleton-box" style="width: 130px; height: 20px; border-radius: 4px;"></span>
                <span class="report-skeleton-box" style="width: 90px; height: 18px; border-radius: 4px;"></span>
                <span class="report-skeleton-box" style="width: 85px; height: 18px; border-radius: 4px;"></span>
                <span class="report-skeleton-box" style="width: 95px; height: 22px; border-radius: 20px;"></span>
                <span class="report-skeleton-box" style="width: 60px; height: 20px; border-radius: 6px;"></span>
                <span class="report-skeleton-box report-shimmer-action-btn" style="margin: 0 auto;"></span>
            </div>
            @endfor

        @elseif($shimmerType === 'attendance')
            {{-- Monthly Attendance Matrix Skeleton --}}
            <div class="report-shimmer-attendance-matrix">
                @for($r = 0; $r < 6; $r++)
                <div class="report-shimmer-matrix-row">
                    <span class="report-skeleton-box report-shimmer-matrix-learner"></span>
                    @for($c = 0; $c < 15; $c++)
                    <span class="report-skeleton-box report-shimmer-matrix-cell"></span>
                    @endfor
                </div>
                @endfor
            </div>
        @endif
    </div>

</div>

<script>
(function() {
    function killBlockingLoaders() {
        try {
            var el1 = document.getElementById('loaderone');
            if (el1) { el1.style.display = 'none'; el1.remove(); }
            var el0 = document.getElementById('loader');
            if (el0) { el0.style.display = 'none'; el0.remove(); }
            if (window.jQuery) {
                window.jQuery('#loaderone, #loader').remove();
            }
        } catch(e) {}
    }

    // Call immediately
    killBlockingLoaders();

    var hasRevealed = false;
    function revealReportContent() {
        if (hasRevealed) return;
        hasRevealed = true;

        killBlockingLoaders();

        var shimmer = document.getElementById('reportShimmer');
        var content = document.getElementById('reportContent');

        if (shimmer) {
            shimmer.classList.add('fade-out');
            setTimeout(function() {
                shimmer.style.display = 'none';
            }, 250);
        }

        if (content) {
            content.classList.add('is-loaded');
        }
    }

    // Display shimmer for 400ms to guarantee smooth experience before cross-fading
    if (document.readyState === 'complete') {
        setTimeout(revealReportContent, 400);
    } else {
        window.addEventListener('load', function() {
            setTimeout(revealReportContent, 400);
        });
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(revealReportContent, 400);
        });
    }

    // Safety fallback: guaranteed reveal within 750ms
    setTimeout(revealReportContent, 750);
})();
</script>

