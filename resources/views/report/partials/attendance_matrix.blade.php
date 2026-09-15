@php
    use Carbon\Carbon;
@endphp

<div class="attendance-table-container">
    <table class="attendance-matrix-table" id="attendanceMatrixTable">
        <thead>
            <tr>
                {{-- Sticky Column: Learner Details & Seat No. --}}
                <th class="sticky-learner-col">Learner Info</th>

                {{-- Day Columns (1 to daymonth) --}}
                @for($d = 1; $d <= $daymonth; $d++)
                    @php
                        $dMeta = $daysMeta[$d] ?? null;
                        $isSun = $dMeta['is_sunday'] ?? false;
                        $isToday = $dMeta['is_today'] ?? false;
                        $wShort = $dMeta['short_weekday'] ?? '';
                    @endphp
                    <th class="day-col-header {{ $isSun ? 'is-sunday' : '' }} {{ $isToday ? 'is-today' : '' }}" 
                        title="{{ $dMeta['formatted'] ?? "Day $d" }}">
                        <span class="day-col-num">{{ $d }}</span>
                        <span class="day-col-weekday">{{ $wShort }}</span>
                    </th>
                @endfor

                {{-- Summary Columns --}}
                <th class="att-sum-col" title="Total Present Days">TP</th>
                <th class="att-sum-col" title="Total Absent Days">TA</th>
                <th class="att-sum-col" title="Attendance Percentage Rate">%</th>
                <th style="min-width: 44px; width: 44px; text-align: center;">Info</th>
            </tr>
        </thead>
        <tbody id="attendanceMatrixTbody">
            @forelse($learnerAttendance as $row)
                @php
                    $seat = $row['seat_display'] ?? 'General';
                    $learnerId = $row['learner_id'] ?? 0;
                    $learnerName = $row['name'] ?? 'Learner';
                    $learnerMobile = $row['mobile'] ?? '';
                    $firstLetter = strtoupper(substr($learnerName, 0, 1));
                    $presentCount = $row['present'] ?? 0;
                    $absentCount = $row['absent'] ?? 0;
                    $rate = $row['rate'] ?? 0;
                    $dailyMap = $row['daily'] ?? [];

                    $rateClass = 'att-rate-none';
                    if ($presentCount + $absentCount > 0) {
                        if ($rate >= 75) $rateClass = 'att-rate-high';
                        elseif ($rate >= 50) $rateClass = 'att-rate-mid';
                        else $rateClass = 'att-rate-low';
                    }

                    $searchKey = strtolower($learnerName . ' ' . $learnerMobile . ' ' . $seat);
                @endphp
                <tr class="att-learner-row" data-search="{{ $searchKey }}">
                    {{-- Sticky Learner Info Cell --}}
                    <td class="sticky-learner-col">
                        <div class="learner-cell-wrap">
                            <div class="learner-avatar-circle">
                                {{ $firstLetter }}
                            </div>
                            <div class="learner-meta-text">
                                {{-- Seat No tag directly above student name --}}
                                <div class="record-seat-tag {{ ($seat !== 'General') ? '' : 'seat-general' }}">
                                    @if($seat !== 'General')
                                        <i class="fa-solid fa-chair me-1"></i>SEAT {{ strtoupper($seat) }}
                                    @else
                                        <i class="fa-solid fa-chair me-1"></i>GENERAL
                                    @endif
                                </div>

                                @if(!empty($learnerId))
                                    <a href="{{ route('learners.show', $learnerId) }}" class="record-learner-name" title="View Profile">
                                        {{ $learnerName }}
                                    </a>
                                @else
                                    <span class="record-learner-name">{{ $learnerName }}</span>
                                @endif

                                @if(!empty($learnerMobile) && $learnerMobile !== '-')
                                    <div class="record-learner-mobile">
                                        <i class="fa-solid fa-phone"></i> {{ $learnerMobile }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- Day Attendance Cells --}}
                    @for($d = 1; $d <= $daymonth; $d++)
                        @php
                            $status = $dailyMap[$d] ?? '-';
                            $dMeta = $daysMeta[$d] ?? null;
                            $isSun = $dMeta['is_sunday'] ?? false;
                        @endphp
                        <td class="{{ $isSun ? 'td-sunday' : '' }}">
                            @if($status === 'P')
                                <span class="att-pill att-p" title="Day {{ $d }}: Present">P</span>
                            @elseif($status === 'A')
                                <span class="att-pill att-a" title="Day {{ $d }}: Absent">A</span>
                            @else
                                <span class="att-dash" title="Day {{ $d }}: No Record">-</span>
                            @endif
                        </td>
                    @endfor

                    {{-- Summary Cells --}}
                    <td class="att-sum-col">
                        <span class="att-sum-p" title="Total Present: {{ $presentCount }}">{{ $presentCount }}</span>
                    </td>
                    <td class="att-sum-col">
                        <span class="att-sum-a" title="Total Absent: {{ $absentCount }}">{{ $absentCount }}</span>
                    </td>
                    <td class="att-sum-col">
                        <span class="att-rate-pill {{ $rateClass }}" title="Attendance Rate: {{ $rate }}%">
                            {{ $rate }}%
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <button type="button" class="btn-att-details btn-show-att-modal" 
                                data-name="{{ $learnerName }}"
                                data-seat="{{ $seat }}"
                                data-mobile="{{ $learnerMobile }}"
                                data-present="{{ $presentCount }}"
                                data-absent="{{ $absentCount }}"
                                data-rate="{{ $rate }}"
                                data-id="{{ $learnerId }}"
                                data-daily-b64="{{ base64_encode(json_encode($dailyMap)) }}"
                                title="View Attendance Breakdown">
                            <i class="fa-solid fa-circle-info"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $daymonth + 5 }}">
                        <div class="report-empty-state">
                            <i class="fa-solid fa-clipboard-user empty-state-icon"></i>
                            <h5 class="empty-state-title">No Active Learners Found</h5>
                            <p class="text-muted small mb-0">No active student records matched your current month and year filters.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
