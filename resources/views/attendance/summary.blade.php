@extends('layouts.library')
@section('content')

<style>
    .att-calendar-card { border: 1px solid #dfe3ea; border-radius: 10px; background: #fff; padding: 18px; margin-bottom: 18px; }
    .att-calendar-nav { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .att-calendar-nav .btn-nav { width: 38px; height: 38px; border-radius: 50%; background: #07156f; color: #fff; display: grid; place-items: center; border: 0; text-decoration: none; flex: 0 0 auto; }
    .att-calendar-nav .btn-nav.disabled { background: #ccd1e0; pointer-events: none; }
    .att-calendar-nav h5 { margin: 0; color: #07156f; font-weight: 800; font-size: 1rem; text-align: center; }
    .att-weekdays { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; text-align: center; color: #777; font-weight: 700; font-size: .78rem; margin-bottom: 6px; }
    .att-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
    .att-cell { border-radius: 8px; padding: 8px 2px; text-align: center; min-height: 52px; }
    .att-cell .day-num { font-weight: 700; font-size: .9rem; }
    .att-cell .day-hours { display: block; font-size: .68rem; margin-top: 2px; }
    .att-cell.out-of-month, .att-cell.out-of-range { color: #ccc; }
    .att-cell.status-full { background: #e6f7e2; color: #188000; }
    .att-cell.status-half { background: #fff6df; color: #b07a00; }
    .att-cell.status-absent { background: #fde9e9; color: #d60000; }
    .att-cell.selectable { cursor: pointer; }
    .att-cell.selected { outline: 2px solid #07156f; outline-offset: -2px; }
    .att-legend { display: flex; gap: 14px; margin-top: 14px; padding-top: 12px; border-top: 1px solid #edf0f4; font-size: .8rem; color: #555; flex-wrap: wrap; }
    .att-legend span { display: inline-flex; align-items: center; gap: 6px; }
    .att-legend .dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; flex: 0 0 auto; }
    .dot-full { background: #2e9c2e; }
    .dot-half { background: #e8a300; }
    .dot-absent { background: #d60000; }
    .att-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 18px; }
    .att-stat { border-radius: 10px; padding: 14px 8px; text-align: center; }
    .att-stat strong { display: block; font-size: 1.3rem; }
    .att-stat span { font-size: .78rem; color: #555; }
    .att-stat.present { background: #e6f7e2; }
    .att-stat.half { background: #fff6df; }
    .att-stat.absent { background: #fde9e9; }
    .att-stat.total { background: #eaf0ff; }
    .att-logs-header { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 10px; }
    .att-logs-header h5 { margin: 0; color: #07156f; font-weight: 800; }
    .att-logs-count { color: #777; font-size: .85rem; }
    #attShowAll { display: none; font-size: .82rem; }
    .att-log-row { border: 1px solid #e4e7ed; border-radius: 8px; background: #fff; padding: 12px 14px; display: grid; grid-template-columns: 52px minmax(0, 1fr) auto 18px; gap: 12px; align-items: center; margin-bottom: 10px; text-decoration: none; color: inherit; }
    .att-log-row:hover { border-color: #07156f; }
    .att-log-date-badge { border-radius: 8px; text-align: center; padding: 6px 4px; font-weight: 700; background: #eef1ff; color: #07156f; }
    .att-log-date-badge.status-full { background: #07156f; color: #fff; }
    .att-log-date-badge.status-absent { background: #fde9e9; color: #d60000; }
    .att-log-date-badge small { display: block; font-size: .68rem; font-weight: 600; }
    .att-log-date-badge .num { font-size: 1.05rem; }
    .att-log-meta small { display: inline-flex; align-items: center; gap: 5px; color: #555; margin-right: 14px; }
    .att-log-hours { text-align: right; color: #07156f; font-weight: 700; white-space: nowrap; }

    @media (max-width: 767px) {
        .att-stats { grid-template-columns: repeat(2, 1fr); gap: 8px; }
        .att-calendar-card { padding: 12px; }
        .att-log-row { grid-template-columns: 44px minmax(0, 1fr) 16px; padding: 10px; gap: 8px; }
        .att-log-hours { grid-column: 2 / 3; text-align: left; font-size: .85rem; }
        .att-log-meta small { margin-right: 10px; font-size: .8rem; }
    }

    @media (max-width: 480px) {
        .att-cell { min-height: 40px; padding: 5px 1px; }
        .att-cell .day-num { font-size: .8rem; }
        .att-cell .day-hours { font-size: .6rem; }
        .att-calendar-nav .btn-nav { width: 32px; height: 32px; }
        .att-stat strong { font-size: 1.1rem; }
    }
</style>

<div class="row mt-2 mb-4">
    <div class="col-lg-4">
        <div class="att-calendar-card">
            <div class="att-calendar-nav">
                @if($canGoPrev)
                    <a class="btn-nav" href="{{ route('attendance.summary', $learnerId) }}?year={{ $prevMonth->year }}&month={{ $prevMonth->month }}"><i class="fa-solid fa-chevron-left"></i></a>
                @else
                    <span class="btn-nav disabled"><i class="fa-solid fa-chevron-left"></i></span>
                @endif

                <h5>{{ $displayMonth->format('F Y') }}</h5>

                @if($canGoNext)
                    <a class="btn-nav" href="{{ route('attendance.summary', $learnerId) }}?year={{ $nextMonth->year }}&month={{ $nextMonth->month }}"><i class="fa-solid fa-chevron-right"></i></a>
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
                             data-att-date="{{ $cell['date'] }}">
                            <span class="day-num">{{ $cell['day'] }}</span>
                            <small class="day-hours">{{ $cell['status'] === 'absent' ? '0h' : ($cell['hours'] !== null ? rtrim(rtrim(number_format($cell['hours'], 1), '0'), '.').'h' : '') }}</small>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="att-legend">
                <span><i class="dot dot-full"></i> Fully Present</span>
                <span><i class="dot dot-half"></i> Half Present</span>
                <span><i class="dot dot-absent"></i> Absent</span>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="att-stats">
            <div class="att-stat present"><strong>{{ $presentCount }}</strong><span>Present</span></div>
            <div class="att-stat half"><strong>{{ $halfCount }}</strong><span>Half Day</span></div>
            <div class="att-stat absent"><strong>{{ $absentCount }}</strong><span>Absent</span></div>
            <div class="att-stat total"><strong>{{ $totalHoursLabel }}</strong><span>Total Hours</span></div>
        </div>

        <div class="att-logs-header">
            <h5>Attendance Logs</h5>
            <span class="att-logs-count"><span id="attLogsCount">{{ count($logs) }}</span> entries <a href="#" id="attShowAll">(show all)</a></span>
        </div>

        <div id="attLogsList">
            @forelse($logs as $log)
                <a href="{{ route('attendance.logs.page', [$learnerId, $log['date']]) }}" class="att-log-row" data-att-log-date="{{ $log['date'] }}">
                    <div class="att-log-date-badge status-{{ $log['status'] }}">
                        <small>{{ strtoupper($log['day_name']) }}</small>
                        <span class="num">{{ $log['day_num'] }}</span>
                    </div>
                    <div>
                        <div>{{ \Carbon\Carbon::parse($log['date'])->format('Y-m-d') }}
                            <i class="fa-solid fa-circle" style="font-size:.5rem; color: {{ $log['status'] === 'full' ? '#2e9c2e' : ($log['status'] === 'half' ? '#e8a300' : '#d60000') }};"></i>
                        </div>
                        <div class="att-log-meta">
                            <small><i class="fa-solid fa-arrow-right text-success"></i> {{ $log['check_in'] ?? 'No Check-in' }}</small>
                            <small><i class="fa-solid fa-arrow-left text-danger"></i> {{ $log['check_out'] ?? 'No Check-out' }}{{ $log['implied_out'] ? ' (shift end)' : '' }}</small>
                        </div>
                    </div>
                    <div class="att-log-hours">
                        <i class="fa-regular fa-clock"></i> {{ $log['hours'] !== null ? number_format($log['hours'], 2) : '0.00' }} Hrs
                    </div>
                    <i class="fa-solid fa-chevron-right text-muted"></i>
                </a>
            @empty
                <div class="text-muted text-center py-4">No attendance records in the allowed date range.</div>
            @endforelse
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
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
            countEl.textContent = visible;
            showAllLink.style.display = 'inline';
        }

        function showAll() {
            logRows.forEach(function (row) { row.style.display = ''; });
            countEl.textContent = totalCount;
            showAllLink.style.display = 'none';
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
