@extends('layouts.library')
@section('content')

<style>
    .att-logs-page { max-width: 620px; margin: 0 auto; }
    .att-logs-header-row { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
    .att-logs-header-row h4 { margin: 0; color: #07156f; font-weight: 800; }
    .att-logs-summary { border: 1px solid #dfe3ea; border-radius: 10px; background: #fff; padding: 16px 18px; margin-bottom: 18px; }
    .att-logs-summary .row-line { display: flex; justify-content: space-between; padding: 6px 0; font-size: .92rem; }
    .att-logs-summary .row-line span { color: #666; }
    .att-punch-row { border: 1px solid #e4e7ed; border-radius: 8px; background: #fff; padding: 12px 16px; display: flex; align-items: center; gap: 14px; margin-bottom: 10px; }
    .att-punch-icon { width: 40px; height: 40px; border-radius: 50%; display: grid; place-items: center; background: #e6f7e2; color: #2e9c2e; }
    .att-punch-icon.out { background: #fde9e9; color: #d60000; }
    .att-punch-row h6 { margin: 0; font-weight: 700; }
    .att-punch-row small { color: #777; }
</style>

<div class="att-logs-page">
    <div class="att-logs-header-row">
        <a href="{{ route('attendance.summary', $learnerId) }}?date={{ $date }}" class="btn btn-light border"><i class="fa-solid fa-arrow-left"></i></a>
        <h4>Attendance Logs</h4>
    </div>

    <div class="att-logs-summary">
        <div class="row-line"><span>Learner</span><strong>{{ $learnerName }}</strong></div>
        <div class="row-line"><span>Date</span><strong>{{ $dateLabel }}</strong></div>
        <div class="row-line"><span>Check-in</span><strong>{{ $attendanceRow?->in_time ? \Carbon\Carbon::parse($attendanceRow->in_time)->format('h:i A') : 'No Check-in' }}</strong></div>
        <div class="row-line"><span>Check-out</span><strong>{{ $attendanceRow?->out_time ? \Carbon\Carbon::parse($attendanceRow->out_time)->format('h:i A') : 'No Check-out' }}</strong></div>
    </div>

    <h6 class="text-muted mb-3">Punch Events ({{ count($punches) }})</h6>

    @forelse($punches as $punch)
        <div class="att-punch-row">
            <div class="att-punch-icon {{ strtolower($punch['punch']) }}">
                <i class="fa-solid fa-arrow-{{ $punch['punch'] === 'IN' ? 'right' : 'left' }}"></i>
            </div>
            <div>
                <h6>{{ $punch['punch'] }}</h6>
                <small>Source: {{ $punch['source'] }}</small>
            </div>
            <div class="ms-auto fw-bold">{{ $punch['time'] }}</div>
        </div>
    @empty
        <div class="text-muted text-center py-4">No punch events recorded for this date.</div>
    @endforelse
</div>

@endsection
