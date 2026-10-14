@extends('layouts.library')
@section('content')

<link rel="stylesheet" href="{{ asset('public/css/attendance-logs.css') }}?v={{ time() }}" />

<div class="attendance-logs-module">

    <!-- ====================================================================
         0. SHIMMER SKELETON (Instant display on load & transitions)
         ==================================================================== -->
    <div class="att-skeleton-container" id="attLogsSkeletonContainer" aria-hidden="true">
        <!-- Top Nav Skeleton -->
        <div class="att-top-nav-bar" style="margin-bottom:14px;">
            <div class="att-shimmer-box" style="width: 180px; height: 36px; border-radius: 10px;"></div>
            <div class="att-shimmer-box" style="width: 140px; height: 28px; border-radius: 20px;"></div>
        </div>

        <!-- Learner Hero Banner Skeleton -->
        <div class="learner-header-card" style="margin-bottom:22px;">
            <div class="learner-info-group">
                <div class="att-shimmer-box" style="width: 56px; height: 56px; border-radius: 14px;"></div>
                <div>
                    <div class="att-shimmer-box" style="width: 170px; height: 22px; margin-bottom: 8px;"></div>
                    <div style="display:flex; gap:8px;">
                        <div class="att-shimmer-box" style="width: 90px; height: 20px; border-radius:6px;"></div>
                        <div class="att-shimmer-box" style="width: 110px; height: 20px; border-radius:6px;"></div>
                    </div>
                </div>
            </div>
            <div style="display:flex; gap:8px;">
                <div class="att-shimmer-box" style="width: 38px; height: 42px; border-radius: 10px;"></div>
                <div class="att-shimmer-box" style="width: 170px; height: 42px; border-radius: 12px;"></div>
                <div class="att-shimmer-box" style="width: 38px; height: 42px; border-radius: 10px;"></div>
            </div>
        </div>

        <!-- 4 KPI Cards Skeleton -->
        <div class="kpi-grid">
            @for($k = 0; $k < 4; $k++)
            <div class="kpi-card" style="background:#fff;">
                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                    <div class="att-shimmer-box" style="width: 75px; height: 12px;"></div>
                    <div class="att-shimmer-box" style="width: 50px; height: 12px; border-radius:4px;"></div>
                </div>
                <div class="att-shimmer-box" style="width: 110px; height: 28px; margin-bottom: 8px;"></div>
                <div class="att-shimmer-box" style="width: 90px; height: 14px; border-radius:4px;"></div>
            </div>
            @endfor
        </div>

        <!-- Actions Panel Skeleton -->
        <div class="actions-panel-card" style="margin-bottom:22px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:14px;">
                <div class="att-shimmer-box" style="width: 190px; height: 18px;"></div>
                <div class="att-shimmer-box" style="width: 120px; height: 18px; border-radius:12px;"></div>
            </div>
            <div class="action-btns-grid">
                <div class="att-shimmer-box" style="width: 100%; height: 68px; border-radius: 12px;"></div>
                <div class="att-shimmer-box" style="width: 100%; height: 68px; border-radius: 12px;"></div>
            </div>
        </div>

        <!-- Timeline Card Skeleton -->
        <div class="punch-logs-card">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid #f1f5f9;">
                <div style="display:flex; gap:12px;">
                    <div class="att-shimmer-box" style="width: 44px; height: 44px; border-radius: 12px;"></div>
                    <div>
                        <div class="att-shimmer-box" style="width: 160px; height: 20px; margin-bottom: 6px;"></div>
                        <div class="att-shimmer-box" style="width: 220px; height: 14px;"></div>
                    </div>
                </div>
                <div class="att-shimmer-box" style="width: 80px; height: 28px; border-radius: 20px;"></div>
            </div>
            <div style="display:flex; flex-direction:column; gap:16px;">
                <div class="att-shimmer-box" style="width: 100%; height: 60px; border-radius: 12px;"></div>
                <div class="att-shimmer-box" style="width: 100%; height: 60px; border-radius: 12px;"></div>
            </div>
        </div>
    </div>

    <!-- ====================================================================
         LIVE CONTENT CONTAINER (Smooth fade-in)
         ==================================================================== -->
    <div class="att-content-container" id="attLogsContentContainer">

        <!-- 1. Top Breadcrumb & Navigation Bar -->
        <div class="att-top-nav-bar">
            <a href="{{ route('attendance.summary', $learnerId) }}?year={{ \Carbon\Carbon::parse($date)->year }}&month={{ \Carbon\Carbon::parse($date)->month }}" 
               class="att-back-btn" 
               title="Return to monthly attendance summary">
                <i class="fa-solid fa-arrow-left-long"></i>
                <span>Monthly Calendar</span>
            </a>

            <div>
                @if(!empty($isToday))
                    <span class="badge-day-mode live">
                        <span class="pulse-dot"></span> Today's Live Attendance
                    </span>
                @else
                    <span class="badge-day-mode history">
                        <i class="fa-regular fa-calendar-check me-1"></i> {{ $dayName }}, {{ $dateLabel }}
                    </span>
                @endif
            </div>
        </div>

        <!-- 2. Learner Profile Header & Date Switcher -->
        <div class="learner-header-card">
            <div class="learner-info-group">
                <div class="learner-avatar" title="{{ $learnerName }}">
                    {{ $learnerInitials }}
                </div>
                <div class="learner-meta">
                    <div class="learner-name-wrap">
                        <h4 class="learner-name">{{ $learnerName }}</h4>
                        <i class="fa-solid fa-circle-check learner-verified-badge" title="Active Library Member"></i>
                        <span class="learner-uid-badge">{{ $learnerNo ?: 'LRN-' . $learnerId }}</span>
                    </div>
                    <div class="learner-tags">
                        <span class="learner-tag-chip" title="Branch">
                            <i class="fa-solid fa-building-columns"></i> {{ $branchName }}
                        </span>
                        <span class="learner-tag-chip" title="Seat & Plan">
                            <i class="fa-solid fa-shield-halved"></i> {{ $membershipTag }}
                        </span>
                        <span class="learner-tag-chip" title="Shift Allowed Hours">
                            <i class="fa-regular fa-clock"></i> Shift: {{ $shiftDurationText }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Date Switcher Controls -->
            <div class="date-switcher-wrap">
                <a href="{{ $hasPrev ? route('attendance.logs.page', ['learner' => $learnerId, 'date' => $prevDate]) : 'javascript:void(0)' }}" 
                   class="date-nav-btn {{ !$hasPrev ? 'disabled' : '' }}" 
                   title="Previous Day ({{ \Carbon\Carbon::parse($prevDate)->format('d M') }})">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>

                <div class="date-picker-pill" id="dateNavCenter" title="Click to choose a specific date">
                    <i class="fa-regular fa-calendar-days cal-icon"></i>
                    <div class="date-info">
                        <span class="date-main">{{ $dateLabel }}</span>
                        <span class="date-day">{{ $dayName }}</span>
                    </div>
                    <input type="date" id="dateJumpInput" value="{{ $date }}" min="{{ $minDate }}" max="{{ $maxDate }}">
                </div>

                <a href="{{ $hasNext ? route('attendance.logs.page', ['learner' => $learnerId, 'date' => $nextDate]) : 'javascript:void(0)' }}" 
                   class="date-nav-btn {{ !$hasNext ? 'disabled' : '' }}" 
                   title="Next Day ({{ \Carbon\Carbon::parse($nextDate)->format('d M') }})">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>

                @if(empty($isToday))
                    <a href="{{ route('attendance.logs.page', ['learner' => $learnerId, 'date' => $todayDate ?? date('Y-m-d')]) }}" 
                       class="btn-jump-today" 
                       title="Jump directly to Today's date">
                        <i class="fa-solid fa-bolt"></i>
                        <span>Today</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- 3. 4 Engaging KPI Metric Cards (Clean, Icon-less Style) -->
        <div class="kpi-grid">
            <!-- Card 1: First Punch In -->
            <div class="kpi-card kpi-in">
                <div class="kpi-card-top">
                    <span class="kpi-label">First Punch In</span>
                    @if($hasPunchIn)
                        <span class="kpi-mini-pill" style="color: #15803d;">Entry Logged</span>
                    @else
                        <span class="kpi-mini-pill" style="color: #94a3b8;">No Entry</span>
                    @endif
                </div>
                <div class="kpi-value">{{ $inTimeText }}</div>
                <div class="kpi-card-bottom">
                    @if($hasPunchIn)
                        <span class="kpi-pill pill-present" title="Entry location">
                            <i class="fa-solid fa-location-dot"></i> {{ $inLocation }}
                        </span>
                    @else
                        <span class="kpi-pill pill-absent">
                            <i class="fa-solid fa-minus"></i> Not Recorded
                        </span>
                    @endif
                </div>
            </div>

            <!-- Card 2: Last Punch Out -->
            <div class="kpi-card kpi-out">
                <div class="kpi-card-top">
                    <span class="kpi-label">Last Punch Out</span>
                    @if(!empty($isInside))
                        <span class="kpi-mini-pill" style="color: #15803d;">Active Inside</span>
                    @elseif($hasPunchOut)
                        <span class="kpi-mini-pill" style="color: #15803d;">Exit Logged</span>
                    @elseif(!empty($missingPunchOut))
                        <span class="kpi-mini-pill" style="color: #b45309;">Punch Missing</span>
                    @else
                        <span class="kpi-mini-pill" style="color: #94a3b8;">No Exit</span>
                    @endif
                </div>
                <div class="kpi-value">{{ $outTimeText }}</div>
                <div class="kpi-card-bottom">
                    @if(!empty($isInside))
                        <span class="kpi-pill pill-live" title="Learner is actively inside library right now">
                            <span class="kpi-live-dot"></span> In Library Now
                        </span>
                    @elseif($hasPunchOut)
                        <span class="kpi-pill pill-present" title="Exit location">
                            <i class="fa-solid fa-location-dot"></i> {{ $outLocation }}
                        </span>
                    @elseif(!empty($missingPunchOut))
                        <span class="kpi-pill pill-warn" title="No exit punch was logged on this past date">
                            <i class="fa-solid fa-triangle-exclamation"></i> Missing Checkout
                        </span>
                    @else
                        <span class="kpi-pill pill-absent">
                            <i class="fa-solid fa-minus"></i> Not Recorded
                        </span>
                    @endif
                </div>
            </div>

            <!-- Card 3: Study Duration -->
            <div class="kpi-card kpi-duration {{ !empty($hasOvertime) ? 'has-overtime' : '' }}">
                <div class="kpi-card-top">
                    <span class="kpi-label">Study Duration</span>
                    @if(!empty($hasOvertime))
                        <span class="kpi-mini-pill" style="color: #dc2626;">Overstay Logged</span>
                    @else
                        <span class="kpi-mini-pill" style="color: #2563eb;">Session Time</span>
                    @endif
                </div>
                <div class="kpi-value">{{ $durationText }}</div>
                <div class="kpi-card-bottom">
                    @if(!empty($hasOvertime))
                        <span class="kpi-pill pill-overtime" title="Stay exceeded assigned shift allowance ({{ $shiftDurationText }}) by {{ $overtimeText }}">
                            <i class="fa-solid fa-clock-rotate-left"></i> +{{ $overtimeText }} Overstay
                        </span>
                    @else
                        <span class="kpi-pill pill-total">
                            {{ $durationSubtext }}
                        </span>
                    @endif
                </div>
            </div>

            <!-- Card 4: Daily Status -->
            <div class="kpi-card kpi-status {{ $isAbsent ? 'status-absent' : 'status-present' }}">
                <div class="kpi-card-top">
                    <span class="kpi-label">Daily Status</span>
                    <span class="kpi-mini-pill" style="color: {{ $isAbsent ? '#dc2626' : '#15803d' }};">Verification</span>
                </div>
                <div class="kpi-value">{{ $statusText }}</div>
                <div class="kpi-card-bottom">
                    <span class="kpi-pill {{ $isAbsent ? 'pill-absent' : 'pill-present' }}">
                        ● {{ $statusSubtext }}
                    </span>
                </div>
            </div>
        </div>

        @if(!empty($hasOvertime))
        <!-- 4. Shift Overstay Warning Banner -->
        <div class="overtime-alert-card">
            <div class="overtime-alert-icon">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div class="overtime-alert-body">
                <div class="overtime-alert-title">
                    Shift Overstay Recorded: <span class="overtime-alert-highlight">+{{ $overtimeText }} Extra Time</span>
                </div>
                <p class="overtime-alert-desc">
                    Learner stayed <strong>{{ $durationText }}</strong> in the library, exceeding their assigned shift of <strong>{{ $shiftDurationText }} ({{ $shiftName }})</strong> by <strong>{{ $overtimeText }}</strong>.
                </p>
            </div>
            <div class="overtime-alert-chip">
                <i class="fa-solid fa-triangle-exclamation"></i> +{{ $overtimeText }} Overstay
            </div>
        </div>
        @endif

        <!-- 5. Quick Attendance Actions Panel -->
        <div class="actions-panel-card">
            <div class="actions-panel-header">
                <div class="actions-header-left">
                    <i class="fa-solid fa-sliders actions-header-icon"></i>
                    <h5 class="actions-title">Attendance Quick Controls</h5>
                </div>
                <div>
                    @if(!empty($isToday))
                        <span class="actions-badge badge-today">
                            <i class="fa-solid fa-calendar-check me-1"></i> Today's Live Actions
                        </span>
                    @else
                        <span class="actions-badge badge-past">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i> Manual Adjustment for {{ $dateLabel }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="action-btns-grid">
                <button type="button" 
                        class="btn-action-punch btn-punch-in" 
                        onclick="triggerAttendancePunch('in')"
                        title="Record a Punch In event for {{ $learnerName }} on {{ $dateLabel }}">
                    <div class="punch-action-icon">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    </div>
                    <div class="punch-action-text">
                        <strong>Punch In</strong>
                        <span>Record entry timestamp for this day</span>
                    </div>
                </button>

                <button type="button" 
                        class="btn-action-punch btn-punch-out" 
                        onclick="triggerAttendancePunch('out')"
                        title="Record a Punch Out event for {{ $learnerName }} on {{ $dateLabel }}">
                    <div class="punch-action-icon">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </div>
                    <div class="punch-action-text">
                        <strong>Punch Out</strong>
                        <span>Record exit timestamp for this day</span>
                    </div>
                </button>
            </div>
        </div>

        <!-- 6. Punch Activity Timeline Card -->
        <div class="punch-logs-card">
            <div class="logs-header">
                <div class="logs-title-wrap">
                    <div class="logs-header-icon">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <div>
                        <h5 class="logs-title">
                            @if(!empty($isToday))
                                Today's Punch Activity
                            @else
                                Punch Timeline · {{ $dateLabel }}
                            @endif
                        </h5>
                        <p class="logs-subtitle">Entry and exit events recorded for this calendar date</p>
                    </div>
                </div>
                <div class="logs-header-right">
                    <span class="badge-events-count">{{ count($punches) }} {{ count($punches) === 1 ? 'Event' : 'Events' }}</span>
                    <span class="shift-info-pill" title="Assigned shift duration">
                        <i class="fa-regular fa-clock"></i> {{ $shiftDurationText }} Shift
                    </span>
                </div>
            </div>

            @if(count($punches) > 0)
                <div class="timeline-container">
                    @foreach($punches as $index => $punch)
                        @php
                            $isPunchIn = ($punch['type'] === 'IN');
                        @endphp

                        {{-- If an OUT punch follows an IN punch with duration, show connector badge --}}
                        @if(!empty($punch['session_duration']))
                            <div class="timeline-session-row">
                                <div class="timeline-session-indent"></div>
                                <div class="timeline-session-connector">
                                    <div class="timeline-session-line"></div>
                                </div>
                                <div>
                                    <span class="timeline-session-badge">
                                        <i class="fa-solid fa-stopwatch"></i> Session Stay: {{ $punch['session_duration'] }}
                                    </span>
                                </div>
                            </div>
                        @endif

                        <div class="timeline-row">
                            <!-- Time Column -->
                            <div class="timeline-time-col">
                                <div class="timeline-time">{{ $punch['time'] }}</div>
                                <div class="timeline-date">{{ $punch['date'] }}</div>
                            </div>

                            <!-- Timeline Node & Connector Line -->
                            <div class="timeline-node-col">
                                <div class="timeline-node-dot {{ $isPunchIn ? 'in' : 'out' }}"></div>
                                @if(!$loop->last || !empty($isInside))
                                    <div class="timeline-line"></div>
                                @endif
                            </div>

                            <!-- Timeline Event Card -->
                            <div class="timeline-event-card">
                                <div class="event-card-left">
                                    <div class="event-card-icon {{ $isPunchIn ? 'in' : 'out' }}">
                                        <i class="fa-solid {{ $isPunchIn ? 'fa-arrow-right-to-bracket' : 'fa-arrow-right-from-bracket' }}"></i>
                                    </div>
                                    <div>
                                        <h6 class="event-card-title">{{ $punch['title'] }}</h6>
                                        <div class="event-card-location">
                                            <i class="fa-solid fa-door-open"></i> {{ $punch['source'] }}
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <span class="event-badge {{ $punch['badge_class'] }}">{{ $punch['badge'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    {{-- If currently inside today without a punch out, show active live session card --}}
                    @if(!empty($isInside))
                        <div class="timeline-active-session">
                            <div class="active-session-left">
                                <span class="pulse-dot"></span>
                                <div class="active-session-text">
                                    <strong>Active Study Session in Progress</strong>
                                    <span>Checked in at {{ $inTimeText }} · Current active duration: {{ $durationText }}</span>
                                </div>
                            </div>
                            <button type="button" class="btn-quick-punchout" onclick="triggerAttendancePunch('out')">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i> Punch Out Now
                            </button>
                        </div>
                    @endif
                </div>
            @else
                <!-- Premium Empty State for Absent / No Punch Days -->
                <div class="empty-logs-card">
                    <div class="empty-icon-wrap">
                        <i class="fa-regular fa-calendar-xmark"></i>
                    </div>
                    <h5 class="empty-logs-title">No Punch Activity on {{ $dateLabel }}</h5>
                    <p class="empty-logs-desc">
                        No entry or exit punch events were logged for <strong>{{ $learnerName }}</strong> on {{ $dayName }}, {{ $dateLabel }}. Status is marked as <strong>Absent</strong>.
                    </p>
                    <button type="button" class="btn-empty-action" onclick="triggerAttendancePunch('in')">
                        <i class="fa-solid fa-plus"></i> Record Manual Punch In for this Day
                    </button>
                </div>
            @endif
        </div>

    </div>

</div>

<script>
(function() {
    // 1. Shimmer Skeleton Smooth Transition with multiple safeguards
    function revealLogsContent() {
        var skel = document.getElementById('attLogsSkeletonContainer');
        var content = document.getElementById('attLogsContentContainer');
        if (skel) {
            skel.style.opacity = '0';
            setTimeout(function() {
                skel.classList.add('is-hidden');
                if (content) {
                    content.classList.add('is-ready');
                }
            }, 100);
        } else if (content) {
            content.classList.add('is-ready');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(revealLogsContent, 100);
        });
    } else {
        setTimeout(revealLogsContent, 80);
    }

    // Safety fallback - guarantees content is revealed
    setTimeout(revealLogsContent, 350);

    // 2. Date Switcher Trigger & Jump
    $(document).ready(function() {
        $('#dateNavCenter').on('click', function(e) {
            if (e.target.id !== 'dateJumpInput') {
                var input = document.getElementById('dateJumpInput');
                if (input) {
                    if (typeof input.showPicker === 'function') {
                        input.showPicker();
                    } else {
                        input.focus();
                    }
                }
            }
        });

        $('#dateJumpInput').on('change', function() {
            var selectedDate = $(this).val();
            if (selectedDate) {
                var skel = document.getElementById('attLogsSkeletonContainer');
                var content = document.getElementById('attLogsContentContainer');
                if (content) content.classList.remove('is-ready');
                if (skel) {
                    skel.classList.remove('is-hidden');
                    skel.style.opacity = '1';
                }
                var targetUrl = "{{ route('attendance.logs.page', ['learner' => $learnerId, 'date' => ':date']) }}".replace(':date', selectedDate);
                window.location.href = targetUrl;
            }
        });
    });
})();

/**
 * Trigger Punch In / Out via AJAX with SweetAlert2 confirmation
 */
function triggerAttendancePunch(punchType) {
    var actionName = (punchType === 'in') ? 'Punch In' : 'Punch Out';
    var actionVerb = (punchType === 'in') ? 'record entry' : 'record exit';
    var dateLabel = "{{ $dateLabel }}";
    var learnerName = "{{ $learnerName }}";

    function proceedWithPunch() {
        var $buttons = $('.btn-action-punch, .btn-quick-punchout, .btn-empty-action');
        $buttons.prop('disabled', true).css('opacity', '0.6');

        $.ajax({
            url: "{{ route('update.attendance') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                learner_id: {{ $learnerId }},
                attendance: 1,
                date: "{{ $date }}",
                time: punchType
            },
            dataType: "json",
            success: function(response) {
                if (response.success || response.status || response.present || response.absent) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(actionName + ' successfully recorded!');
                    }
                    setTimeout(function() {
                        window.location.reload();
                    }, 400);
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || ('Could not record ' + actionName),
                            confirmButtonColor: '#07156f'
                        });
                    } else {
                        alert(response.message || ('Could not record ' + actionName));
                    }
                    $buttons.prop('disabled', false).css('opacity', '1');
                }
            },
            error: function(xhr) {
                $buttons.prop('disabled', false).css('opacity', '1');
                var errorMsg = 'Failed to record attendance.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    var firstKey = Object.keys(xhr.responseJSON.errors)[0];
                    errorMsg = xhr.responseJSON.errors[firstKey][0];
                }

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Request Failed',
                        text: errorMsg,
                        confirmButtonColor: '#07156f'
                    });
                } else if (typeof toastr !== 'undefined') {
                    toastr.error(errorMsg);
                } else {
                    alert(errorMsg);
                }
            }
        });
    }

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: actionName + ' Confirmation',
            html: 'Are you sure you want to <strong>' + actionVerb + '</strong> for <strong>' + learnerName + '</strong> on <strong>' + dateLabel + '</strong>?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: (punchType === 'in' ? '#10b981' : '#ef4444'),
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fa-solid fa-check me-1"></i> Yes, ' + actionName,
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                proceedWithPunch();
            }
        });
    } else {
        if (confirm('Are you sure you want to record ' + actionName + ' for ' + learnerName + ' on ' + dateLabel + '?')) {
            proceedWithPunch();
        }
    }
}
</script>

@endsection
