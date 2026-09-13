@extends('layouts.library')
@section('content')

<link rel="stylesheet" href="{{ asset('public/css/attendance.css') }}?v={{ time() }}" />

@php
    $selectedDate = $selectedDate ?? (request('date') ?: date('Y-m-d'));
    $selectedDateFormatted = $selectedDateFormatted ?? \Carbon\Carbon::parse($selectedDate)->format('d/m/Y');
    $today = \Carbon\Carbon::today();
    $activeFilter = $selectedStatus ?? (request('status') ?: 'present');
@endphp

<div class="attendance-module">
    {{-- Header & Filter Bar --}}
    <div class="attendance-header-card">
        <form action="{{ route('get.learner.attendance') }}" method="GET" id="attendanceFilterForm">
            <input type="hidden" name="status" id="filter_status_input" value="{{ $activeFilter }}">
            <div class="attendance-controls-row">
                <div class="attendance-filter-group">
                    {{-- Date Picker (DD/MM/YYYY) --}}
                    <div class="filter-input-wrap">
                        <i class="fa-regular fa-calendar-days"></i>
                        <input type="text" 
                               name="date" 
                               id="attendance_date" 
                               class="control-date-input" 
                               value="{{ $selectedDateFormatted }}" 
                               placeholder="DD/MM/YYYY" 
                               readonly>
                    </div>

                    {{-- Search Input --}}
                    <div class="filter-input-wrap">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" 
                               name="search" 
                               id="attendance_search" 
                               class="control-search-input" 
                               placeholder="Search learner name, mobile..." 
                               value="{{ request('search') }}" 
                               autocomplete="off">
                    </div>

                    {{-- Filter Buttons --}}
                    <div class="filter-buttons-row">
                        <button type="submit" class="btn-search-action">
                            <i class="fa-solid fa-magnifying-glass"></i> Search
                        </button>

                        @php
                            $isFiltered = request()->filled('search') 
                                || (request()->filled('date') && $selectedDate !== date('Y-m-d')) 
                                || (request()->filled('status') && request('status') !== 'present');
                        @endphp
                        @if($isFiltered)
                        <a href="{{ route('get.learner.attendance') }}" class="btn-reset-action">
                            <i class="fa-solid fa-rotate-right"></i> Reset
                        </a>
                        @endif
                    </div>
                </div>

                {{-- 3 Filter Options: Total, Present, Absent (Clean 1-line labels) --}}
                <div class="attendance-stats-wrap">
                    <span class="stat-pill stat-pill-total filter-btn {{ $activeFilter === 'all' ? 'active' : '' }}" 
                          data-filter="all" 
                          title="Click to view all students">
                        Total: <b id="statTotal">{{ $totalStudents }}</b>
                    </span>
                    <span class="stat-pill stat-pill-present filter-btn {{ $activeFilter === 'present' ? 'active' : '' }}" 
                          data-filter="present" 
                          title="Click to filter present students">
                        Present: <b id="statPresent">{{ $presentStudents }}</b>
                    </span>
                    <span class="stat-pill stat-pill-absent filter-btn {{ $activeFilter === 'absent' ? 'active' : '' }}" 
                          data-filter="absent" 
                          title="Click to filter absent students">
                        Absent: <b id="statAbsent">{{ $absentStudents }}</b>
                    </span>
                </div>
            </div>
        </form>
    </div>

    {{-- Attendance List Container --}}
    @if($learners->count() > 0)
    <div class="attendance-list-container" id="attendanceListContainer">
        @foreach($learners as $value)
        @php
            $hasInTime = !empty($value->in_time);
            $hasOutTime = !empty($value->out_time);
            $isPresent = $hasInTime || ((int)$value->attendance === 1);
            $planEndDate = !empty($value->plan_end_date) ? \Carbon\Carbon::parse($value->plan_end_date) : null;
            $durationName = $value->plan_type_name ?? 'Standard';

            // 1. Calculate Shift Duration (in minutes)
            $shiftMinutes = 0;
            if (!empty($value->shift_slot_hours) && (float)$value->shift_slot_hours > 0) {
                $shiftMinutes = (float)$value->shift_slot_hours * 60;
            } elseif (!empty($value->shift_start_time) && !empty($value->shift_end_time)) {
                $sStart = \Carbon\Carbon::parse($value->shift_start_time);
                $sEnd = \Carbon\Carbon::parse($value->shift_end_time);
                if ($sEnd->lt($sStart)) {
                    $sEnd = $sEnd->copy()->addDay();
                }
                $shiftMinutes = $sStart->diffInMinutes($sEnd);
            }

            // 2. Calculate Actual Spent Duration (in minutes)
            $spentMinutes = 0;
            $isInside = false;
            $libraryDurationText = '—';

            if ($hasInTime) {
                $inCarbon = \Carbon\Carbon::parse($value->in_time);
                if ($hasOutTime) {
                    $outCarbon = \Carbon\Carbon::parse($value->out_time);
                    if ($outCarbon->lt($inCarbon)) {
                        $outCarbon = $outCarbon->copy()->addDay();
                    }
                    $spentMinutes = $inCarbon->diffInMinutes($outCarbon);
                } elseif ($selectedDate === date('Y-m-d')) {
                    $spentMinutes = $inCarbon->diffInMinutes(now());
                    $isInside = true;
                } else {
                    if (!empty($value->shift_end_time)) {
                        $outCarbon = \Carbon\Carbon::parse($selectedDate . ' ' . $value->shift_end_time);
                        if ($outCarbon->lt($inCarbon)) {
                            $outCarbon = $outCarbon->copy()->addDay();
                        }
                        $spentMinutes = $inCarbon->diffInMinutes($outCarbon);
                    } else {
                        $spentMinutes = $shiftMinutes > 0 ? $shiftMinutes : 0;
                    }
                }

                $hrs = floor($spentMinutes / 60);
                $mins = $spentMinutes % 60;
                $libraryDurationText = ($hrs > 0 ? "{$hrs} hr " : "") . "{$mins} min";
            }

            // 3. Determine Duration Color State
            $durationClass = 'duration-neutral';
            $durationLabel = '';

            if ($hasInTime) {
                if ($shiftMinutes > 0) {
                    $tolerance = 15; // 15 min grace window
                    if ($spentMinutes > ($shiftMinutes + $tolerance)) {
                        $durationClass = 'duration-red';
                        $durationLabel = 'Overstay';
                    } elseif ($spentMinutes < ($shiftMinutes - $tolerance)) {
                        $durationClass = 'duration-orange';
                        $durationLabel = 'Under Shift';
                    } else {
                        $durationClass = 'duration-green';
                        $durationLabel = 'On Shift';
                    }
                } else {
                    $durationClass = 'duration-green';
                    $durationLabel = 'Completed';
                }
            }
        @endphp
        <div class="clean-attendance-row" 
             id="attendance_row_{{ $value->learner_id }}" 
             data-status="{{ $isPresent ? 'present' : 'absent' }}"
             @if($activeFilter === 'present' && !$isPresent) style="display: none;" @elseif($activeFilter === 'absent' && $isPresent) style="display: none;" @endif>
            
            {{-- Top Identity & Status Section --}}
            <div class="card-identity-header">
                {{-- 1. Photo & Identity --}}
                <div class="learner-identity">
                    <div class="avatar-box">
                        <img src="{{ $value->profile_picture ? asset($value->profile_picture) : asset('public/img/student_profile.jpeg') }}" 
                             alt="{{ $value->name }}" 
                             class="avatar-img">
                        <span class="status-dot {{ $isPresent ? 'dot-present' : 'dot-absent' }}"></span>
                    </div>
                    <div class="learner-info">
                        <div class="learner-name-line">
                            <span class="learner-name" title="{{ $value->name }}">{{ $value->name }}</span>
                        </div>
                        <div class="learner-meta-tags">
                            <span class="seat-tag">Seat {{ $value->seat_no ? getSeatDisplayByMainNo($value->seat_no) : 'GEN' }}</span>
                            <span class="submeta-uid">#{{ $value->learner_no ?? $value->learner_id }}</span>
                            @if(!empty($value->mobile))
                             <a href="tel:+91{{ $value->mobile }}" class="learner-phone-link" title="Call Learner">
                                 <i class="fa-solid fa-phone"></i> +91-{{ display_learner_mobile($value->mobile) }}
                             </a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Status Badge (Top-Right on Mobile, Column 4 on Desktop) --}}
                <div class="clean-info-col attendance-status-col">
                    <span class="col-label desktop-only">Status</span>
                    <span class="status-badge {{ $isPresent ? 'badge-present' : 'badge-absent' }}">
                        <i class="fa-solid {{ $isPresent ? 'fa-circle-check' : 'fa-circle-xmark' }} me-1"></i>
                        {{ $isPresent ? 'Present' : 'Absent' }}
                    </span>
                </div>
            </div>

            {{-- Details & Punches Section (Unified Panel on Mobile, Columns 2, 3, 5 on Desktop) --}}
            <div class="card-details-panel">
                <div class="panel-meta-row">
                    {{-- Plan End Date --}}
                    <div class="clean-info-col plan-date-col">
                        <span class="col-label">Plan End Date</span>
                        <span class="col-value">
                            <i class="fa-regular fa-calendar text-muted" style="font-size: 12px;"></i>
                            @if($planEndDate)
                                {{ $planEndDate->format('d M Y') }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </span>
                    </div>

                    {{-- Library Duration --}}
                    <div class="clean-info-col library-time-col">
                        <span class="col-label">Library Duration</span>
                        <div class="library-time-value">
                            <span class="duration-badge {{ $durationClass }}" title="{{ $durationLabel }}">
                                <i class="fa-regular fa-clock"></i>
                                <span>{{ $libraryDurationText }}</span>
                            </span>
                            @if($isInside)
                                <span class="inside-indicator ms-1">Inside</span>
                            @endif
                        </div>
                        <div class="duration-submeta">
                            <span class="shift-info-line">
                                {{ $durationName }}
                                @if(!empty($value->shift_start_time) && !empty($value->shift_end_time))
                                    ({{ \Carbon\Carbon::parse($value->shift_start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($value->shift_end_time)->format('h:i A') }})
                                @elseif(!empty($value->shift_slot_hours))
                                    ({{ $value->shift_slot_hours }} hrs)
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <div class="panel-divider mobile-only"></div>

                {{-- In & Out Punch Strip --}}
                <div class="clean-punch-display-group">
                    <div class="punch-display-item punch-in-item {{ $hasInTime ? 'is-punched' : '' }}">
                        <span class="punch-col-label">
                            <span class="punch-icon-badge in-badge"><i class="fa-solid fa-arrow-right-to-bracket"></i></span>
                            <span class="punch-title-text">In Punch</span>
                        </span>
                        <span class="punch-time-pill {{ $hasInTime ? 'time-filled' : 'time-empty' }}">
                            <i class="fa-regular fa-clock"></i>
                            <span>{{ $hasInTime ? \Carbon\Carbon::parse($value->in_time)->format('h:i A') : '—' }}</span>
                        </span>
                    </div>

                    <div class="punch-display-item punch-out-item {{ $hasOutTime ? 'is-punched' : '' }}">
                        <span class="punch-col-label">
                            <span class="punch-icon-badge out-badge"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                            <span class="punch-title-text">Out Punch</span>
                        </span>
                        <span class="punch-time-pill {{ $hasOutTime ? 'time-filled' : 'time-empty' }}">
                            <i class="fa-regular fa-clock"></i>
                            <span>{{ $hasOutTime ? \Carbon\Carbon::parse($value->out_time)->format('h:i A') : '—' }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Fallback Empty Message for JS Filtering --}}
    @php
        $showEmptyFilterState = false;
        if ($activeFilter === 'present' && $presentStudents === 0) {
            $showEmptyFilterState = true;
        } elseif ($activeFilter === 'absent' && $absentStudents === 0) {
            $showEmptyFilterState = true;
        } elseif ($activeFilter === 'all' && $totalStudents === 0) {
            $showEmptyFilterState = true;
        }
    @endphp
    <div class="attendance-header-card text-center py-5 {{ $showEmptyFilterState ? '' : 'd-none' }}" id="noFilterMatchState">
        <h5 class="fw-bold mb-2" style="color: #18225f;" id="noFilterMatchTitle">
            @if($activeFilter === 'present' && $presentStudents === 0)
                No Learners Marked Present
            @elseif($activeFilter === 'absent' && $absentStudents === 0)
                No Learners Marked Absent
            @else
                No Learners Match This Filter
            @endif
        </h5>
        <p class="text-muted mb-0" id="noFilterMatchDesc">
            @if($activeFilter === 'present' && $presentStudents === 0)
                No learners have punched in or been marked present for the selected date.
            @elseif($activeFilter === 'absent' && $absentStudents === 0)
                All learners are marked present for the selected date.
            @else
                No learners found under the selected attendance status.
            @endif
        </p>
    </div>

    @else
    <div class="attendance-header-card text-center py-5">
        <h5 class="fw-bold mb-2" style="color: #18225f;">No Learners Found</h5>
        <p class="text-muted mb-3">No learners found for the selected date or search filter.</p>
        <a href="{{ route('get.learner.attendance') }}" class="btn-search-action" style="text-decoration: none; display: inline-flex;">
            <i class="fa-solid fa-rotate-right"></i> Reset to Today
        </a>
    </div>
    @endif
</div>

{{-- Flatpickr & Instant Interactive 3-Filter JavaScript --}}
<script>
$(document).ready(function() {
    // 1. Initialize Flatpickr for Date Picker (DD/MM/YYYY)
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#attendance_date", {
            dateFormat: "d/m/Y",
            allowInput: false,
            disableMobile: "true",
            defaultDate: "{{ $selectedDateFormatted }}",
            onChange: function(selectedDates, dateStr, instance) {
                if (dateStr) {
                    $('#attendanceFilterForm').submit();
                }
            }
        });
    }

    // 2. Filter Pills click handler
    $('.filter-btn').on('click', function() {
        var filterType = $(this).data('filter');

        // Toggle active button state
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');

        // Update hidden status input
        $('#filter_status_input').val(filterType);

        // Filter cards in DOM
        var visibleCount = 0;
        if (filterType === 'all') {
            $('.clean-attendance-row').show();
            visibleCount = $('.clean-attendance-row').length;
        } else {
            $('.clean-attendance-row').each(function() {
                var rowStatus = $(this).data('status');
                if (rowStatus === filterType) {
                    $(this).show();
                    visibleCount++;
                } else {
                    $(this).hide();
                }
            });
        }

        // Show empty message if no rows match
        if (visibleCount === 0) {
            if (filterType === 'present') {
                $('#noFilterMatchTitle').text('No Learners Marked Present');
                $('#noFilterMatchDesc').text('No learners have punched in or been marked present for the selected date.');
            } else if (filterType === 'absent') {
                $('#noFilterMatchTitle').text('No Learners Marked Absent');
                $('#noFilterMatchDesc').text('All learners are marked present for the selected date.');
            } else {
                $('#noFilterMatchTitle').text('No Learners Match This Filter');
                $('#noFilterMatchDesc').text('No learners found under the selected attendance status.');
            }
            $('#noFilterMatchState').removeClass('d-none');
        } else {
            $('#noFilterMatchState').addClass('d-none');
        }

        // Update URL query state without full reload
        var currentUrl = new URL(window.location.href);
        if (filterType === 'present') {
            currentUrl.searchParams.delete('status');
        } else {
            currentUrl.searchParams.set('status', filterType);
        }
        window.history.replaceState({}, '', currentUrl.toString());
    });
});
</script>

@endsection