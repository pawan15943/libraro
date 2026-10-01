@extends('layouts.library')
@section('content')

<link rel="stylesheet" href="{{ asset('public/css/attendance-logs.css') }}?v={{ time() }}" />

<div class="attendance-logs-module">

    <!-- 1. Learner Header Card & Date Navigator -->
    <div class="learner-header-card">
        <div class="learner-info-group">
            <div class="learner-avatar">
                {{ $learnerInitials }}
            </div>
            <div class="learner-meta">
                <h4 class="learner-name">{{ $learnerName }}</h4>
                <div class="learner-uid">Learner ID: {{ $learnerNo ?: 'LRN-' . $learnerId }}</div>
                <div class="learner-tags">
                    <span class="learner-tag-item">
                        <i class="fa-solid fa-book-open"></i> {{ $branchName }}
                    </span>
                    <span class="learner-tag-item">
                        <i class="fa-regular fa-user"></i> {{ $membershipTag }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Date Navigator -->
        <div class="date-navigator-wrap">
            <a href="{{ $hasPrev ? route('attendance.logs.page', ['learner' => $learnerId, 'date' => $prevDate]) : 'javascript:void(0)' }}" 
               class="date-nav-btn {{ !$hasPrev ? 'disabled' : '' }}" 
               title="Previous Day">
                <i class="fa-solid fa-chevron-left"></i>
            </a>

            <div class="date-nav-center" id="dateNavCenter" title="Click to choose date">
                <i class="fa-regular fa-calendar date-nav-icon"></i>
                <div class="date-nav-text">
                    <strong>{{ $dateLabel }}</strong>
                    <span>{{ $dayName }}</span>
                </div>
                <input type="date" id="dateJumpInput" value="{{ $date }}" min="{{ $minDate }}" max="{{ $maxDate }}">
            </div>

            <a href="{{ $hasNext ? route('attendance.logs.page', ['learner' => $learnerId, 'date' => $nextDate]) : 'javascript:void(0)' }}" 
               class="date-nav-btn {{ !$hasNext ? 'disabled' : '' }}" 
               title="Next Day">
                <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>
    </div>

    <!-- 2. 4 KPI Metric Cards Grid -->
    <div class="kpi-grid">
        <!-- KPI 1: Punch In -->
        <div class="kpi-card kpi-in">
            <div class="kpi-icon-in">
                <i class="fa-solid fa-arrow-right"></i>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Punch In</span>
                <span class="kpi-value">{{ $inTimeText }}</span>
                <span class="kpi-sub">
                    <i class="fa-solid fa-location-dot"></i> {{ $inLocation }}
                </span>
            </div>
        </div>

        <!-- KPI 2: Punch Out -->
        <div class="kpi-card kpi-out">
            <div class="kpi-icon-out">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Punch Out</span>
                <span class="kpi-value">{{ $outTimeText }}</span>
                @if($hasPunchOut)
                    <span class="kpi-sub">
                        <i class="fa-solid fa-location-dot"></i> {{ $outLocation }}
                    </span>
                @else
                    <span class="badge-not-yet">Not yet</span>
                @endif
            </div>
        </div>

        <!-- KPI 3: Study Duration -->
        <div class="kpi-card kpi-duration">
            <div class="kpi-icon-duration">
                <i class="fa-regular fa-clock"></i>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Study Duration</span>
                <span class="kpi-value">{{ $durationText }}</span>
                <span class="kpi-sub">{{ $durationSubtext }}</span>
            </div>
        </div>

        <!-- KPI 4: Status -->
        <div class="kpi-card kpi-status">
            <span class="status-pill-badge {{ $isAbsent ? 'absent' : '' }}">
                ● Status
            </span>
            <span class="status-val {{ $isAbsent ? 'absent' : '' }}">{{ $statusText }}</span>
            <span class="kpi-sub">{{ $statusSubtext }}</span>
        </div>
    </div>

    <!-- 3. Action Buttons Grid (Punch In & Punch Out) -->
    <div class="action-btns-grid">
        <button type="button" class="btn-action-punch btn-punch-in" onclick="triggerAttendancePunch('in')">
            <div class="punch-action-icon">
                <i class="fa-solid fa-arrow-right-to-bracket"></i>
            </div>
            <div class="punch-action-text">
                <strong>Punch In</strong>
                <span>Start your study session</span>
            </div>
        </button>

        <button type="button" class="btn-action-punch btn-punch-out" onclick="triggerAttendancePunch('out')">
            <div class="punch-action-icon">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </div>
            <div class="punch-action-text">
                <strong>Punch Out</strong>
                <span>End your study session</span>
            </div>
        </button>
    </div>

    <!-- 4. Today's Punch Logs Card -->
    <div class="punch-logs-card">
        <div class="logs-header">
            <div class="logs-title-wrap">
                <div class="logs-header-icon">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <div>
                    <h5 class="logs-title">Today's Punch Logs</h5>
                    <p class="logs-subtitle">Punch in/out events for {{ $dateLabel }}</p>
                </div>
            </div>
            <span class="badge-events-count">{{ count($punches) }} {{ count($punches) === 1 ? 'Event' : 'Events' }}</span>
        </div>

        @if(count($punches) > 0)
            <div class="timeline-container">
                @foreach($punches as $index => $punch)
                    @php
                        $isPunchIn = ($punch['type'] === 'IN');
                    @endphp
                    <div class="timeline-row">
                        <!-- Time Column on Left -->
                        <div class="timeline-time-col">
                            <div class="timeline-time">{{ $punch['time'] }}</div>
                            <div class="timeline-date">{{ $punch['date'] }}</div>
                        </div>

                        <!-- Timeline Node & Connecting Line -->
                        <div class="timeline-node-col">
                            <div class="timeline-node-dot {{ $isPunchIn ? 'in' : 'out' }}"></div>
                            @if(!$loop->last)
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
                                    <h6 class="event-card-title {{ $isPunchIn ? 'in' : 'out' }}">{{ $punch['title'] }}</h6>
                                    <div class="event-card-location">
                                        <i class="fa-solid fa-location-dot"></i> {{ $punch['source'] }}
                                    </div>
                                </div>
                            </div>
                            <div>
                                <span class="event-badge {{ $punch['badge_class'] }}">{{ $punch['badge'] }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-logs-box">
                <div class="empty-logs-icon">
                    <i class="fa-regular fa-calendar-xmark"></i>
                </div>
                <div class="empty-logs-title">No Punch Events Recorded</div>
                <p class="mb-0">There are no punch in or punch out records for {{ $dateLabel }}.</p>
            </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
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
            var targetUrl = "{{ route('attendance.logs.page', ['learner' => $learnerId, 'date' => ':date']) }}".replace(':date', selectedDate);
            window.location.href = targetUrl;
        }
    });
});

function triggerAttendancePunch(punchType) {
    var actionName = (punchType === 'in') ? 'Punch In' : 'Punch Out';
    
    if (!confirm('Are you sure you want to record ' + actionName + ' for {{ $learnerName }} on {{ $dateLabel }}?')) {
        return;
    }

    var $buttons = $('.btn-action-punch');
    $buttons.prop('disabled', true).css('opacity', '0.65');

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
            if (response.success || response.status) {
                if (typeof toastr !== 'undefined') {
                    toastr.success(actionName + ' successfully recorded!');
                }
                setTimeout(function() {
                    window.location.reload();
                }, 500);
            } else {
                alert(response.message || ('Could not record ' + actionName));
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
            if (typeof toastr !== 'undefined') {
                toastr.error(errorMsg);
            } else {
                alert(errorMsg);
            }
        }
    });
}
</script>
@endpush

@endsection
