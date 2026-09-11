@extends('layouts.library')
@section('content')

<link rel="stylesheet" href="{{ asset('public/css/attendance.css') }}?v={{ time() }}" />

@php
    $selectedDate = $selectedDate ?? (request('date') ?: date('Y-m-d'));
    $today = \Carbon\Carbon::today();

    $totalStudents = $learners->count();
    $presentStudents = $learners->filter(function($l) {
        return !empty($l->in_time) || (int)$l->attendance === 1;
    })->count();
    $absentStudents = max(0, $totalStudents - $presentStudents);
@endphp

<div class="attendance-module">
    {{-- Header & Filter Bar --}}
    <div class="attendance-header-card">
        <form action="{{ route('attendance') }}" method="GET" id="attendanceFilterForm">
            <div class="attendance-controls-row">
                <div class="attendance-filter-group">
                    {{-- Date Picker --}}
                    <div class="filter-input-wrap">
                        <i class="fa-regular fa-calendar-days"></i>
                        <input type="date" 
                               name="date" 
                               id="attendance_date" 
                               class="control-date-input" 
                               value="{{ $selectedDate }}" 
                               onchange="$('#attendanceFilterForm').submit();">
                    </div>

                    {{-- Search Input --}}
                    <div class="filter-input-wrap">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" 
                               name="search" 
                               id="attendance_search" 
                               class="control-search-input" 
                               placeholder="Search learner name..." 
                               value="{{ request('search') }}" 
                               autocomplete="off">
                    </div>

                    {{-- Filter Buttons --}}
                    <div class="filter-buttons-row">
                        <button type="submit" class="btn-search-action">
                            <i class="fa-solid fa-magnifying-glass"></i> Search
                        </button>

                        @if(request()->filled('search') || (request()->filled('date') && request('date') != date('Y-m-d')))
                        <a href="{{ route('attendance') }}" class="btn-reset-action">
                            <i class="fa-solid fa-rotate-right"></i> Reset
                        </a>
                        @endif
                    </div>
                </div>

                {{-- Live Summary Pills --}}
                <div class="attendance-stats-wrap">
                    <span class="stat-pill stat-pill-total">
                        Total: <b id="statTotal">{{ $totalStudents }}</b>
                    </span>
                    <span class="stat-pill stat-pill-present">
                        Present: <b id="statPresent">{{ $presentStudents }}</b>
                    </span>
                    <span class="stat-pill stat-pill-absent">
                        Absent: <b id="statAbsent">{{ $absentStudents }}</b>
                    </span>
                </div>
            </div>
        </form>
    </div>

    {{-- Clean Attendance List --}}
    @if($learners->count() > 0)
    <div class="attendance-list-container">
        @foreach($learners as $value)
        @php
            $hasInTime = !empty($value->in_time);
            $hasOutTime = !empty($value->out_time);
            $isPresent = $hasInTime || ((int)$value->attendance === 1);
            $planEndDate = !empty($value->plan_end_date) ? \Carbon\Carbon::parse($value->plan_end_date) : null;
            $isPlanActive = $planEndDate && $planEndDate->gte($today);
            $durationName = $value->planType->name ?? ($value->plan_type_name ?? 'Standard');

            // Calculate library time
            $libraryTimeText = '—';
            $isInside = false;
            if ($hasInTime) {
                $inCarbon = \Carbon\Carbon::parse($value->in_time);
                if ($hasOutTime) {
                    $outCarbon = \Carbon\Carbon::parse($value->out_time);
                    if ($outCarbon->lt($inCarbon)) {
                        $outCarbon = $outCarbon->copy()->addDay();
                    }
                    $diffMinutes = $inCarbon->diffInMinutes($outCarbon);
                    $hrs = floor($diffMinutes / 60);
                    $mins = $diffMinutes % 60;
                    $libraryTimeText = ($hrs > 0 ? "{$hrs} hr " : "") . "{$mins} min";
                } elseif ($selectedDate === date('Y-m-d')) {
                    $diffMinutes = $inCarbon->diffInMinutes(now());
                    $hrs = floor($diffMinutes / 60);
                    $mins = $diffMinutes % 60;
                    $libraryTimeText = ($hrs > 0 ? "{$hrs} hr " : "") . "{$mins} min";
                    $isInside = true;
                } else {
                    $libraryTimeText = 'In: ' . $inCarbon->format('h:i A');
                }
            }
        @endphp
        <div class="clean-attendance-row" id="attendance_row_{{ $value->learner_id }}">
            {{-- Top Identity & Status Section --}}
            <div class="card-identity-header">
                {{-- 1. Photo & Name --}}
                <div class="learner-identity">
                    <div class="avatar-box">
                        <img src="{{ $value->profile_picture ? asset($value->profile_picture) : asset('public/img/student_profile.jpeg') }}" alt="{{ $value->name }}" class="avatar-img">
                        <span class="status-dot {{ $isPresent ? 'dot-present' : 'dot-absent' }}" id="dot_{{ $value->learner_id }}"></span>
                    </div>
                    <div class="learner-info">
                        <div class="learner-name-line">
                            <span class="learner-name">{{ $value->name }}</span>
                        </div>
                        <div class="learner-meta-tags">
                            <span class="seat-tag">Seat {{ $value->seat_no ? getSeatDisplayByMainNo($value->seat_no) : 'GEN' }}</span>
                            <span class="submeta-uid">UID: #{{ $value->learner_no ?? $value->learner_id }}</span>
                            @if(!empty($value->mobile))
                             <a href="tel:+91{{ $value->mobile }}" class="learner-phone-link" title="Call Learner"><i class="fa-solid fa-phone" style="font-size: 10px;"></i>+91-{{ display_learner_mobile($value->mobile) }}</a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- 4. Attendance Status (Present / Absent Shown Separately) --}}
                <div class="clean-info-col attendance-status-col">
                    <span class="col-label desktop-only">Status</span>
                    <div>
                        <span class="status-badge {{ $isPresent ? 'badge-present' : 'badge-absent' }}" id="status_badge_{{ $value->learner_id }}">
                            <i class="fa-solid {{ $isPresent ? 'fa-circle-check' : 'fa-circle-xmark' }} me-1"></i>
                            {{ $isPresent ? 'Present' : 'Absent' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Details & Punches Section --}}
            <div class="card-details-panel">
                <div class="panel-meta-row">
                    {{-- 2. Plan End Date --}}
                    <div class="clean-info-col plan-date-col">
                        <span class="col-label">Plan End Date</span>
                        <span class="col-value">
                            <i class="fa-regular fa-calendar text-muted" style="font-size: 12.5px;"></i>
                            @if($planEndDate)
                                {{ $planEndDate->format('d M Y') }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </span>
                    </div>

                    {{-- 5. Library Shift Time (Shifted at the very last) --}}
                    <div class="clean-info-col library-time-col">
                        <span class="col-label">Library Shift & Time</span>
                        <span class="col-value library-time-value" id="lib_time_{{ $value->learner_id }}">
                            <i class="fa-regular fa-clock text-muted" style="font-size: 13px;"></i>
                            <span class="time-spent-text" id="time_spent_{{ $value->learner_id }}">{{ $libraryTimeText }}</span>
                            <span class="inside-indicator ms-1 {{ $isInside ? '' : 'd-none' }}" id="inside_badge_{{ $value->learner_id }}">Inside</span>
                        </span>
                        <small class="text-muted" style="font-size: 11.5px;">{{ $durationName }}</small>
                    </div>
                </div>

                <div class="panel-divider mobile-only"></div>

                {{-- 3. In & Out Punch Controls with Time Picker --}}
                <div class="punch-controls-group">
                    {{-- In Punch --}}
                    <div class="punch-item punch-in-item {{ $hasInTime ? 'is-punched' : '' }}" id="punch_item_in_{{ $value->learner_id }}">
                        <span class="punch-label">
                            <span class="punch-icon-badge in-badge"><i class="fa-solid fa-arrow-right-to-bracket"></i></span>
                            <span class="punch-title-text">In Punch</span>
                        </span>
                        <div class="punch-action-wrap">
                            <label class="clean-switch punch-in-switch" for="in_toggle_{{ $value->learner_id }}">
                                <input type="checkbox" 
                                       id="in_toggle_{{ $value->learner_id }}" 
                                       class="punch-toggle in-toggle" 
                                       data-learner="{{ $value->learner_id }}" 
                                       data-time="in"
                                       data-in-time="{{ $value->in_time ? $value->in_time : '' }}"
                                       {{ $hasInTime ? 'checked' : '' }}>
                                <span class="clean-slider"></span>
                            </label>
                            <div class="time-picker-box {{ $hasInTime ? 'has-value' : '' }}" id="in_picker_box_{{ $value->learner_id }}" title="Choose or change In punch time">
                                <i class="fa-regular fa-clock picker-clock-icon"></i>
                                <input type="time" 
                                       class="punch-time-input in-time-input" 
                                       id="in_time_input_{{ $value->learner_id }}" 
                                       data-learner="{{ $value->learner_id }}" 
                                       data-time="in"
                                       value="{{ $hasInTime ? \Carbon\Carbon::parse($value->in_time)->format('H:i') : '' }}">
                            </div>
                        </div>
                    </div>

                    {{-- Out Punch --}}
                    <div class="punch-item punch-out-item {{ $hasOutTime ? 'is-punched' : '' }}" id="punch_item_out_{{ $value->learner_id }}">
                        <span class="punch-label">
                            <span class="punch-icon-badge out-badge"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                            <span class="punch-title-text">Out Punch</span>
                        </span>
                        <div class="punch-action-wrap">
                            <label class="clean-switch punch-out-switch" for="out_toggle_{{ $value->learner_id }}">
                                <input type="checkbox" 
                                       id="out_toggle_{{ $value->learner_id }}" 
                                       class="punch-toggle out-toggle" 
                                       data-learner="{{ $value->learner_id }}" 
                                       data-time="out"
                                       data-out-time="{{ $value->out_time ? $value->out_time : '' }}"
                                       {{ $hasOutTime ? 'checked' : '' }}>
                                <span class="clean-slider"></span>
                            </label>
                            <div class="time-picker-box {{ $hasOutTime ? 'has-value' : '' }}" id="out_picker_box_{{ $value->learner_id }}" title="Choose or change Out punch time">
                                <i class="fa-regular fa-clock picker-clock-icon"></i>
                                <input type="time" 
                                       class="punch-time-input out-time-input" 
                                       id="out_time_input_{{ $value->learner_id }}" 
                                       data-learner="{{ $value->learner_id }}" 
                                       data-time="out"
                                       value="{{ $hasOutTime ? \Carbon\Carbon::parse($value->out_time)->format('H:i') : '' }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="attendance-header-card text-center py-5">
        <h5 class="fw-bold mb-2" style="color: #18225f;">No Learners Found</h5>
        <p class="text-muted mb-3">No learners found for the selected date or search filter.</p>
        <a href="{{ route('attendance') }}" class="btn-search-action" style="text-decoration: none; display: inline-flex;">
            <i class="fa-solid fa-rotate-right"></i> Reset to Today
        </a>
    </div>
    @endif
</div>

<script>
$(document).ready(function() {
    function getCurrentTime24() {
        var d = new Date();
        var hours = d.getHours();
        var minutes = d.getMinutes();
        return (hours < 10 ? '0' + hours : hours) + ':' + (minutes < 10 ? '0' + minutes : minutes);
    }

    function recalculateSummaryCounters() {
        var presentCount = $('.in-toggle:checked').length;
        var totalCount = $('.clean-attendance-row').length;
        var absentCount = Math.max(0, totalCount - presentCount);

        $('#statPresent').text(presentCount);
        $('#statAbsent').text(absentCount);
        $('#statTotal').text(totalCount);
    }

    function calculateTimeDiff(inTimeStr, outTimeStr) {
        if (!inTimeStr) return '—';
        var inDate = new Date(inTimeStr);
        var outDate = outTimeStr ? new Date(outTimeStr) : new Date();
        var diffMs = Math.max(0, outDate - inDate);
        var diffMins = Math.floor(diffMs / 60000);
        var hrs = Math.floor(diffMins / 60);
        var mins = diffMins % 60;
        return (hrs > 0 ? (hrs + ' hr ') : '') + mins + ' min';
    }

    function executePunchUpdate(learnerId, timeType, isChecked, customTime) {
        var date = $('#attendance_date').val() || "{{ $selectedDate }}";
        var attendanceVal = isChecked ? 1 : 0;
        var $inToggle = $('#in_toggle_' + learnerId);
        var $outToggle = $('#out_toggle_' + learnerId);
        var $inInput = $('#in_time_input_' + learnerId);
        var $outInput = $('#out_time_input_' + learnerId);

        $.ajax({
            url: '{{ route("update.attendance") }}',
            method: 'POST',
            data: {
                learner_id: learnerId,
                attendance: attendanceVal,
                date: date,
                time: timeType,
                custom_time: customTime || null,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.present) {
                    $('#status_badge_' + learnerId)
                        .removeClass('badge-absent')
                        .addClass('badge-present')
                        .html('<i class="fa-solid fa-circle-check me-1"></i> Present');
                    $('#dot_' + learnerId).removeClass('dot-absent').addClass('dot-present');

                    if (response.in_time_raw) {
                        $inToggle.prop('checked', true).attr('data-in-time', response.in_time_raw);
                        $inInput.val(response.in_time_24);
                        $('#punch_item_in_' + learnerId).addClass('is-punched');
                        $('#in_picker_box_' + learnerId).addClass('has-value');
                    } else if (timeType === 'in' && !isChecked) {
                        $('#punch_item_in_' + learnerId).removeClass('is-punched');
                        $('#in_picker_box_' + learnerId).removeClass('has-value');
                    }

                    if (response.out_time_raw) {
                        $outToggle.prop('checked', true).attr('data-out-time', response.out_time_raw);
                        $outInput.val(response.out_time_24);
                        $('#punch_item_out_' + learnerId).addClass('is-punched');
                        $('#out_picker_box_' + learnerId).addClass('has-value');
                    } else if (timeType === 'out' && !isChecked) {
                        $outToggle.prop('checked', false).attr('data-out-time', '');
                        $outInput.val('');
                        $('#punch_item_out_' + learnerId).removeClass('is-punched');
                        $('#out_picker_box_' + learnerId).removeClass('has-value');
                    }

                    if (response.duration) {
                        $('#time_spent_' + learnerId).text(response.duration);
                    }

                    if (response.is_inside) {
                        $('#inside_badge_' + learnerId).removeClass('d-none');
                    } else {
                        $('#inside_badge_' + learnerId).addClass('d-none');
                    }
                } else {
                    // Marked absent
                    $inToggle.prop('checked', false).attr('data-in-time', '');
                    $inInput.val('');
                    $outToggle.prop('checked', false).attr('data-out-time', '');
                    $outInput.val('');
                    $('#punch_item_in_' + learnerId).removeClass('is-punched');
                    $('#in_picker_box_' + learnerId).removeClass('has-value');
                    $('#punch_item_out_' + learnerId).removeClass('is-punched');
                    $('#out_picker_box_' + learnerId).removeClass('has-value');

                    $('#status_badge_' + learnerId)
                        .removeClass('badge-present')
                        .addClass('badge-absent')
                        .html('<i class="fa-solid fa-circle-xmark me-1"></i> Absent');
                    $('#dot_' + learnerId).removeClass('dot-present').addClass('dot-absent');

                    $('#time_spent_' + learnerId).text('—');
                    $('#inside_badge_' + learnerId).addClass('d-none');
                }

                recalculateSummaryCounters();

                if (window.toastr) {
                    toastr.options = {
                        positionClass: "toast-bottom-right",
                        timeOut: 2500,
                        closeButton: true,
                        progressBar: true
                    };
                    toastr.success(response.message || 'Attendance updated successfully');
                }
            },
            error: function(xhr) {
                var errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to update attendance.';
                if (timeType === 'in') {
                    $inToggle.prop('checked', !isChecked);
                } else {
                    $outToggle.prop('checked', !isChecked);
                }
                if (window.toastr) {
                    toastr.error(errorMsg);
                } else {
                    alert(errorMsg);
                }
            }
        });
    }

    // 1. Toggle switch change
    $(document).on('change', '.punch-toggle', function() {
        var $toggle = $(this);
        var learnerId = $toggle.data('learner');
        var timeType = $toggle.data('time');
        var isChecked = $toggle.prop('checked');
        var $inToggle = $('#in_toggle_' + learnerId);
        var $timeInput = (timeType === 'in') ? $('#in_time_input_' + learnerId) : $('#out_time_input_' + learnerId);

        if (timeType === 'out' && isChecked && !$inToggle.prop('checked')) {
            $toggle.prop('checked', false);
            if (window.toastr) {
                toastr.warning('Please mark IN punch before marking OUT punch!');
            } else {
                alert('Please mark IN punch before marking OUT punch!');
            }
            return;
        }

        var customTime = isChecked ? ($timeInput.val() || getCurrentTime24()) : null;
        executePunchUpdate(learnerId, timeType, isChecked, customTime);
    });

    // 2. Direct time picker input change
    $(document).on('change', '.punch-time-input', function() {
        var $input = $(this);
        var learnerId = $input.data('learner');
        var timeType = $input.data('time');
        var customTime = $input.val();
        var $inToggle = $('#in_toggle_' + learnerId);

        if (!customTime) {
            // Cleared time -> uncheck corresponding toggle
            executePunchUpdate(learnerId, timeType, false, null);
            return;
        }

        if (timeType === 'out' && !$inToggle.prop('checked')) {
            $input.val('');
            if (window.toastr) {
                toastr.warning('Please mark IN punch before setting OUT time!');
            } else {
                alert('Please mark IN punch before setting OUT time!');
            }
            return;
        }

        executePunchUpdate(learnerId, timeType, true, customTime);
    });
});
</script>

@endsection
