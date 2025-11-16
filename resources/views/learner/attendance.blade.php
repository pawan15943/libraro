@extends('layouts.library')
@section('content')

<style>
    div#datatable_wrapper input,
    div#datatable_wrapper select {
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

<!-- Note -->
<div class="row pb-4">
    <div class="col-lg-12">
        <div class="text-danger pb-3">
            <b>Note :</b> If you don't provide an out time, then learner's closing shift time will be used as the out time.
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-12">
        <h4 class="mb-4">Take Learner Attendance</h4>

        <!-- Learners List (kept all data from your table, preserved this card/list structure) -->
        <div class="row g-2 mb-4">
            @foreach($learners as $key => $value)
            <div class="col-lg-12">
                <div class="revenue-info">
                    <ul>
                        <!-- Profile -->
                        <li style="width: 8%;">
                            <img src="{{ $value->profile_picture ? asset($value->profile_picture) : asset('public/img/student_profile.jpeg') }}" alt="profile" class="profile-learner">
                        </li>

                        <!-- Seat No. + Plan Type -->
                        <li>
                            <span>Seat No.</span>
                            <p>{{ $value->seat_no ?? 'G' }} :
                                {{ $value->planType ?$value->planType->name : 'NA' }}
                            </p>
                        </li>

                        <!-- Name + DOB -->
                        <li>
                            <span>Learner Info</span>
                            <p class="uppercase truncate name">
                                {{ $value->name }}
                            </p>
                        </li>


                        <!-- Active Plan (Start Date + Plan Name) -->
                        <li>
                            <span>Active Plan</span>
                            <p>
                                {{ $value->plan_start_date }}
                                <small class="d-block">{{ $value->plan_name }}</small>
                            </p>
                        </li>

                        <!-- Expired On (End Date + Status Active/Extended per your rule) -->
                        <li class="col-12 col-md-3">
                            <span>Expired On</span>
                            <p>
                                @php
                                $today = \Carbon\Carbon::today();
                                @endphp

                                @if(\Carbon\Carbon::parse($value->plan_end_date)->gte($today))
                                <span class="text-success">
                                    {{ $value->plan_end_date }} : Active
                                </span>
                                @else
                                <span class="text-danger">
                                    {{ $value->plan_end_date }} : Extended
                                </span>
                                @endif
                            </p>
                        </li>

                        <!-- Toggle: Mark Present (same as original "In time" toggle/attendance) -->
                        <li>
                            <span>In Punch</span>
                            <div class=" form-switch justify-content-center">
                                <input
                                    class="form-check-input toggle"
                                    type="checkbox"
                                    id="myToggle{{ $value->learner_id }}"
                                    data-learner="{{ $value->learner_id }}"
                                    {{ $value->in_time ? 'checked' : '' }}>
                            </div>
                        </li>

                        <!-- Toggle: Mark Out (same as original outToggle) -->
                        <li>
                            <span>Out Punch</span>
                            <div class=" form-switch justify-content-center">
                                <input
                                    class="form-check-input outToggle"
                                    type="checkbox"
                                    id="outToggle{{ $value->learner_id }}"
                                    data-learner="{{ $value->learner_id }}" {{ $value->out_time ? 'checked' : '' }}>
                            </div>
                        </li>


                    </ul>
                </div>
            </div>
            @endforeach
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