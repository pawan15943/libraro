@extends('layouts.library')
@section('content')
@php
use Carbon\Carbon;
$current_route = Route::currentRouteName();
@endphp
<style>
    .revenue-info a {
        display: flex;
        background: navy;
        color: #fff !important;
        justify-content: center;
        align-items: center;
        border-radius: 2rem;
        width: 78px;
        font-size: .8rem;
        height: 30px !important;
    }

    .revenue-info ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

</style>


<!-- Attendance Logs Modal -->
<div class="modal fade" id="attendanceLogModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Attendance Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th class="text-center">Source</th>
                            <th class="text-center">Type</th>
                            <th class="text-center">Time</th>
                        </tr>
                    </thead>
                    <tbody id="logBody">
                        <tr>
                            <td colspan="3" class="text-center">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>



<div class="row mb-4 ">
    <div class="col-lg-12">
        <div class="filter-box p-3">
            <form action="" method="GET">

                <!-- Filter By Plan -->
                <div class="row g-2">
                    <div class="col-lg-4">
                        <input type="date" class="form-control" name="from_date" value="{{ request('from_date') ?: '' }}">
                    </div>

                    <div class="col-lg-4">
                        <input type="date" class="form-control" name="to_date" value="{{ request('to_date') ?: '' }}">
                    </div>

                    <div class="col-lg-2">
                        <button class="btn btn-primary button">
                            <i class="fa fa-search"></i> Search Records
                        </button>
                    </div>

                       <div class="col-lg-1 align-self-end">
                            <button type="button" 
                                    id="clearFilter" 
                                    class="btn btn-secondary button"
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="bottom" 
                                    data-bs-title="Clear Filter">
                                Clear
                            </button>
                        </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-12">
        <h4 class="mb-4">Attendace Summery</h4>
        <div class="row g-2 mb-4">

            @forelse($attendance as $index => $value)

            <div class="col-lg-12">
                <div class="revenue-info">
                    <ul>
                        <li>
                            <span>Attendance Date</span>
                            <p>{{ $value->date }}</p>
                        </li>

                        <li>
                            <span>Punch In</span>
                            <p>{{ $value->in_time ? Carbon::parse($value->in_time)->format('h:i A') : '-' }}</p>
                        </li>

                        <li>
                            <span>Punch Out</span>
                            <p>{{ $value->out_time ? Carbon::parse($value->out_time)->format('h:i A') : '-' }}</p>
                        </li>

                        <li>
                            <span>Status</span>
                            <p>
                                @if($value->attendance == 1)
                                <span class="text-success">Present</span>
                                @elseif($value->attendance == 0)
                                <span class="text-danger">Absent</span>
                                @else
                                <span class="text-warning">No Attendance</span>
                                @endif
                            </p>
                        </li>

                        <li style="width:8% !important;">
                            <a href="#" class="btn btn-sm btn-outline-primary view-log" data-bs-toggle="modal" data-bs-target="#attendanceLogModal" data-learner="{{ $value->learner_id }}" data-date="{{ $value->date }}">
                                View Log
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            @empty

            <div class="col-lg-12 text-center">
                <p>No records found.</p>
            </div>

            @endforelse
        </div>


    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        document.querySelectorAll('.view-log').forEach(btn => {
            btn.addEventListener('click', function() {

                let learnerId = this.getAttribute('data-learner');
                let date = this.getAttribute('data-date');

                let logBody = document.getElementById('logBody');
                logBody.innerHTML = `<tr><td colspan="3" class="text-center">Loading...</td></tr>`;

                fetch(`{{ route('attendance.logs') }}?learner_id=${learnerId}&date=${date}`)
                    .then(res => res.json())
                    .then(data => {

                        if (!Array.isArray(data) || data.length === 0) {
                            logBody.innerHTML =
                                `<tr><td colspan="3" class="text-center text-muted">No logs found</td></tr>`;
                            return;
                        }

                        let html = '';
                        data.forEach((row, index) => {
                            let type = index % 2 === 0 ? 'IN' : 'OUT';
                            html += `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${type}</td>
                                <td>${row.punch_time}</td>
                                <td>${row.source}</td>
                            </tr>
                        `;
                        });

                        logBody.innerHTML = html;
                    })
                    .catch(() => {
                        logBody.innerHTML =
                            `<tr><td colspan="3" class="text-danger text-center">Error loading logs</td></tr>`;
                    });
            });
        });

    });

</script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const clearBtn = document.getElementById('clearFilter');

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {

            // Clear inputs
            const fromDate = document.querySelector('input[name="from_date"]');
            const toDate   = document.querySelector('input[name="to_date"]');

            if (fromDate) fromDate.value = '';
            if (toDate) toDate.value = '';

            // Reload page WITHOUT query params
            window.location.href = "{{ url()->current() }}";
        });
    }

});
</script>





@endsection
