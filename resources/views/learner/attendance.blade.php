@extends('layouts.library')
@section('content')

<style>
    div#datatable_wrapper input,
    div#datatable_wrapper select {
        height: auto !important;
        margin: .5rem;
        border-color: #e7e7e7;
    }
</style>



<div class="row mb-4 ">
    <div class="col-lg-12">
        <div class="filter-box">
            <form action="{{ route('attendance') }}" method="GET">
                <div class="row g-4">
                    <!-- Filter By Plan -->

                    <div class="col-lg-4">
                        <input type="date" class="form-control" name="date" value="{{ request('date') ?: date('Y-m-d') }}" id="date">
                    </div>
                    <div class="col-lg-2">
                        <button class="btn btn-primary button">
                            <i class="fa fa-search"></i> Search Records
                        </button>
                    </div>

                </div>


            </form>
        </div>
    </div>


</div>

<div class="row mb-4">
    <div class="col-lg-12">
        <div class="text-danger pb-3"><b>Note :</b> If you don't provide an out time, then learner's closing shift time will be used as the out time.</div>
        <div class="table-responsive mt-4">
            <table class="table text-center datatable border-bottom" id="datatable">
                <thead>
                    <tr>
                        <th>Seat No.</th>
                        <th>Learner Info</th>
                        <th>Contact Info</th>
                        <th>Active Plan</th>
                        <th>Expired On</th>
                        <th>In time</th>
                        <th>Out time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($learners as $key => $value)
                    <tr>
                        <td>{{ $value->seat_no ?? 'GENERAL' }}<br><small>{{ $value->plan_type_name }}</small></td>
                        <td>
                            <span class="uppercase truncate name">
                                {{ $value->name }}
                            </span><br>
                            <small>{{ $value->dob }}</small>
                        </td>
                        <td>
                            <span class="truncate">
                                {!! $value->email ? $value->email : '<i class="fa-solid fa-times text-danger"></i> Email ID Not Available' !!}
                            </span><br>
                            <small>+91-{{ $value->mobile }}</small>
                        </td>
                        <td>{{ $value->plan_start_date }}<br><small>{{ $value->plan_name }}</small></td>
                        <td>{{ $value->plan_end_date }}<br>{!! getUserStatusDetails($value->plan_end_date) !!}</td>
                        <td>
                            <div class="form-check form-switch justify-content-center">
                                <input class="form-check-input toggle" type="checkbox" id="myToggle{{ $value->learner_id }}"
                                    data-learner="{{ $value->learner_id }}" {{ $value->attendance == 1 ? 'checked' : '' }}>
                            </div>
                        </td>
                        <td>
                            <div class="form-check form-switch justify-content-center">
                                <input class="form-check-input outToggle" type="checkbox" id="outToggle{{ $value->learner_id }}"
                                    data-learner="{{ $value->learner_id }}">
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>



            </table>

        </div>
    </div>
</div>



<script>
    $(document).ready(function() {
        // Add event listener for the attendance toggle (In time)
        $('.toggle').on('change', function() {
            let learner_id = $(this).data("learner"); // Get the learner ID of the clicked toggle
            let attendance = $(this).prop("checked") ? 1 : 0; // Get the new attendance value (1 or 0)
            let currentToggle = $(this);

            // Get the selected date from the input
            var date = $('#date').val();

            // Validate date before making the AJAX request
            if (!date) {
                alert('Please select a date!');
                return;
            }

            // Ensure only the clicked learner's attendance is updated for "in" time
            var time = 'in';
            updateAttendance(learner_id, attendance, date, time); // Update attendance for the 'in' time
        });

        // Add event listener for the out-time toggle
        $('.outToggle').on('change', function() {
            let learner_id = $(this).data('learner'); // Get the learner ID of the clicked toggle
            var attendance = $(this).prop("checked") ? 1 : 0; // Attendance value (same for "out" toggle)

            var date = $('#date').val();

            // Validate date before making the AJAX request
            if (!date) {
                alert('Please select a date!');
                return;
            }

            // Ensure only the clicked learner's attendance is updated for "out" time
            var time = 'out'; // Set the time type to 'out'
            updateAttendance(learner_id, attendance, date, time); // Update attendance for the 'out' time
        });

        // Function to handle attendance update via AJAX
        function updateAttendance(learner_id, attendance, date, time) {
            // Send AJAX request to update attendance for the specific learner
            if (learner_id && attendance !== undefined && date) {
                $.ajax({
                    url: '{{ route("update.attendance") }}', // Ensure route is correct
                    method: 'POST',
                    data: {
                        learner_id: learner_id, // Pass the learner ID for the specific row
                        attendance: attendance, // Pass the attendance value (1 or 0)
                        date: date, // Pass the selected date
                        time: time, // Pass 'in' or 'out'
                        _token: '{{ csrf_token() }}' // CSRF token for security
                    },
                    success: function(response) {
                        if (response.present) {
                            toastr.options = {
                                positionClass: "toast-bottom-right", // Change position as needed
                                timeOut: 5000, // Auto close after 5 seconds
                                closeButton: true,
                                progressBar: true
                            };
                            toastr.success(response.message);
                            console.log(response.message); // Success message
                        } else if (response.absent) {
                            toastr.options = {
                                positionClass: "toast-bottom-right", // Change position as needed
                                timeOut: 5000, // Auto close after 5 seconds
                                closeButton: true,
                                progressBar: true
                            };
                            toastr.error(response.message);
                            console.log(response.message); // Success message
                        } else if (response.success) {
                            toastr.options = {
                                positionClass: "toast-bottom-right", // Change position as needed
                                timeOut: 5000, // Auto close after 5 seconds
                                closeButton: true,
                                progressBar: true
                            };
                            toastr.success(response.message);
                            console.log(response.message); // Success message
                        } else {
                            alert('Error updating attendance');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        alert('Something went wrong. Please try again.');
                    }
                });
            }
        }
    });
</script>

@endsection