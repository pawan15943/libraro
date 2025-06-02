@extends('layouts.library')
@section('content')

<!-- Content Header (Page header) -->
@php

$current_route = Route::currentRouteName();
@endphp
<style>
    /* Pagination container styling */
    .pagination-container nav {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 20px;
    }

    /* Styling for the pagination links */
    .pagination-container a,
    .pagination-container span {
        text-decoration: none;
        padding: 8px 12px;
        margin: 0 4px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background-color: #f9f9f9;
        color: #151f38;
        transition: background-color 0.3s, color 0.3s;
        height: 41px ! IMPORTANT;
        display: inline-flex;
        border-radius: 2rem;
        justify-content: center;
        align-items: center;
    }

    /* Hover effect for pagination links */
    .pagination-container a:hover {
        background-color: #007bff;
        /* Blue background on hover */
        color: white;
        /* White text on hover */
    }

    /* Disabled state styling */
    .pagination-container span.cursor-not-allowed {
        background-color: #ffffff;
        color: #000000;
        cursor: not-allowed;
        height: 41px !important;
        display: inline-block;
        font-size: 1rem;
        border-radius: 3rem;
    }

    /* Active page styling */
    .pagination-container .bg-blue-500 {
        background-color: #151f38;
        color: white;
        font-weight: bold;
        border-color: #151f38;
    }

    .pagination-container a:hover {
        background-color: #e1e9ff ! important;
        color: #000000;
    }

    /* Adjusting spacing for the Previous and Next links */
    .pagination-container .flex {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    /* Styling for the Previous and Next buttons */
    .pagination-container .pagination-container a:hover,
    .pagination-container .pagination-container .bg-blue-500 {
        text-decoration: none;
        color: white;
        /* Make sure the text color stays white on hover */
    }
</style>

@if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif
@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif
@can('has-permission', 'Filter')
<div class="row d-none">
    <div class="col-lg-12">
        <div class="filter-box">
            <h4 class="mb-3">Filter Box</h4>

            <form action="{{ route('learnerHistory') }}" method="GET">
                <div class="row">
                    <!-- Filter By Plan -->
                    <div class="col-lg-3">
                        <label for="plan_id">Filter By Plan</label>
                        <select name="plan_id" id="plan_id" class="form-select">
                            <option value="">Choose Plan</option>
                            @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" {{ request()->get('plan_id') == $plan->id ? 'selected' : '' }}>
                                {{ $plan->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter By Payment Status -->
                    <div class="col-lg-3">
                        <label for="is_paid">Filter By Payment Status</label>
                        <select name="is_paid" id="is_paid" class="form-select">
                            <option value="">Choose Payment Status</option>
                            <option value="1" {{ request()->get('is_paid') == '1' ? 'selected' : '' }}>Paid</option>
                            <option value="0" {{ request()->get('is_paid') == '0' ? 'selected' : '' }}>Unpaid</option>
                        </select>
                    </div>

                    <!-- Filter By Active/Expired Status -->
                    <div class="col-lg-3">
                        <label for="status">Filter By Active / Expired</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Choose Status</option>
                            <option value="active" {{ request()->get('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="expired" {{ request()->get('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                        </select>
                    </div>

                    <!-- Search By Name, Mobile & Email -->
                    <div class="col-lg-3">
                        <label for="search">Search By Name, Mobile & Email</label>
                        <input type="text" class="form-control" name="search" placeholder="Enter Name, Mobile or Email"
                            value="{{ request()->get('search') }}">
                    </div>
                </div>

                <div class="row mt-3">
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
@endcan
<div class="row">
    <div class="col-lg-12">
    <p class="info-message">
    <span class="close-btn" onclick="this.parentElement.style.display='none';">&times;</span>    
    <b>Important: </b>This section displays a list of all closed plans and expired seats. If a seat owner does not renew their plan within a month or specified extension period, the seat will automatically expire and become available for others to book.However, if a user wishes to rebook their seat at a later time, we offer an option to reactivate the seat under a different seat number using the reactivation feature. In this case, we will not collect personal information again; instead, we will use the existing information on file.</p>
        <div class="table-responsive">
            <table class="table text-center datatable border-bottom" id="datatable">
                <thead>
                    <tr>
                        <th>Seat No.</th>
                        <th>Learner Info</th>
                        <th>Contact Info</th>
                        <th>Active Plan</th>
                        <th>Expired On</th>
                        <th>Action</th>
                    </tr>
                </thead>

               
                <tbody>
                    @foreach($learnerHistory as $key => $value)
                  
                    <tr>
                        <td>{{$value->seat_no}}<br>
                            <small>{{$value->plan_type_name}}</small>
                        </td>
                        <td><span class="uppercase truncate" data-bs-toggle="tooltip"
                                data-bs-title="{{$value->name}}" data-bs-placement="bottom">{{$value->name}}</span>
                            <br> <small>{{$value->dob}}</small>
                        </td>
                        <td><span class="truncate" data-bs-toggle="tooltip"
                                data-bs-title="{{$value->email }}" data-bs-placement="bottom"><i
                                    class="fa-solid fa-times text-danger"></i></i>
                                {{$value->email }}</span> <br>
                            <small> +91-{{$value->mobile}}</small>
                        </td>
                        <td>{{$value->plan_start_date}}<br>
                            <small>{{$value->plan_name}}</small>
                        </td>
                        <td>{{$value->plan_end_date}}<br>
                        
                        {!! getUserStatusDetails($value->plan_end_date) !!}
                        </td>
                        <td>
                            <ul class="actionalbls">
                            <li><a href="{{route('learners.show',$value->id)}}" title="View Seat Booking Full Details"><i class="fas fa-eye"></i></a></li>
                            <li><a href="{{route('learners.reactive',$value->id)}}" title="Reactivate Learner"><i class="fa-solid fa-arrows-rotate"></i></a></li>
                            </ul>
                        </td>

                    </tr>

                    @endforeach
                </tbody>
              
            </table>
         
        </div>
    </div>
</div>


<!-- /.content -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).on('click', '.delete-customer', function() {
        var id = $(this).data('id');
        var url = '{{ route('learners.destroy', ': id ') }}';
        url = url.replace(':id', id);

        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire(
                            'Deleted!',
                            'User has been deleted.',
                            'success'
                        ).then(() => {
                            location.reload(); // Optionally, you can refresh the page
                        });
                    },
                    error: function(xhr, status, error) {
                        Swal.fire(
                            'Error!',
                            'An error occurred while deleting the student.',
                            'error'
                        );
                    }
                });
            }
        });
    });
</script>
<script>
    function confirmSwap(customerId) {
        const form = document.getElementById(`swap-seat-form-${customerId}`);
        const oldSeat = document.getElementById(`old-seat-${customerId}`).value;
        const newSeatSelect = document.getElementById(`new-seat-${customerId}`);
        const newSeat = newSeatSelect.options[newSeatSelect.selectedIndex].text;

        // Confirm message with old seat and new seat details
        const confirmation = confirm(`Are you sure you want to swap from seat ${oldSeat} to seat ${newSeat}?`);

        if (confirmation) {
            form.submit();
        } else {
            // Reset the dropdown to prevent accidental changes
            newSeatSelect.value = '';
        }
    }
</script>
<script>
    $(document).on('click', '.link-close-plan', function() {
        const learner_id = this.getAttribute('data-id');
        var url = '{{ route('learners.close') }}'; // Adjust the route as necessary

        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, close it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'POST', // Use POST or PATCH for this type of operation
                    data: {
                        _token: '{{ csrf_token() }}',
                        learner_id: learner_id
                    },
                    success: function(response) {
                        Swal.fire(
                            'Closed!',
                            'The user plan has been closed.',
                            'success'
                        ).then(() => {
                            location.reload(); // Optionally reload the page after closing the plan
                        });
                    },
                    error: function(xhr, status, error) {
                        Swal.fire(
                            'Error!',
                            'An error occurred while closing the plan.',
                            'error'
                        );
                    }
                });
            }
        });
    });
</script>

<script>
     
    $(document).ready(function() {
        let table = new DataTable('#datatable');


        var url = window.location.href;

        // Check if there are any URL parameters
        if (url.includes('?')) {
            // Redirect to the URL without parameters
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });
</script>
@endsection