@extends('layouts.library')
@section('content')

<!-- Content Header (Page header) -->
@php

$current_route = Route::currentRouteName();
@endphp


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


<p><b>{{ $learnerHistory->total() }} Records for {{ $learnerHistory->perPage() }} per page</b></p>
@foreach($learnerHistory as $key => $value)

@php
$planStatus = getPlanStatusDetails($value->plan_end_date);
$transaction = learnerTransaction($value->id, $value->learner_detail_id);

if ($transaction && isset($transaction->pending_amount)) {
    $due_date = DB::table('learner_pending_transaction')
        ->where('learner_id', $value->id)
        ->where('status', 0)
        ->where('pending_amount', $transaction->pending_amount)
        ->select('due_date')
        ->first();
} else {
    $due_date = null;
}
@endphp
<div class="row">
    <div class="col-lg-12">
        <div class="seat-info bg-white">
            <div class="seat-no">

                @if($value->seat_no )
                <span> Seat No. {{$value->seat_no ? $value->seat_no : 'GEN'}} </span>
                @else
                <span> {{$value->seat_no ? $value->seat_no : 'GEN'}} </span>
                @endif

                {!! getUserStatusDetails($value->plan_end_date) !!}

            </div>
            <div class="seat-actions">
                <ul>
                    <!-- View Seat Info -->
                    @can('has-permission', 'View Seat')
                    <li><a href="{{route('learners.show',$value->id)}}" title="View Seat Booking Full Details"><i class="fas fa-eye"></i></a></li>
                    @endcan
                    @can('has-permission', 'Reactive Seat')
                    <li><a href="{{route('learners.reactive',$value->id)}}" title="Reactivate Learner"><i class="fa-solid fa-arrows-rotate"></i></a></li>          
                    @endcan              
                </ul>
            </div>

            <div class="seat-informarion">
                <img src="{{ $value->profile_picture ? asset($value->profile_picture) : asset('public/img/student_profile.jpeg') }}" alt="profile">
                <div class="information">
                    <h4>{{$value->name}}
                        <span class="{{$planStatus['class']}} ps-1">{{$planStatus['status']}}</span>

                    </h4>
                    <span>UID : <a href="{{route('learners.show',$value->id)}}">{{$value->learner_no}}</a> &nbsp; | &nbsp; M : <a href="tel:+91-{{$value->mobile}}">+91-{{$value->mobile}}</a> </span>
                    <span class="d-block">E: <a href="mailto:{{$value->email}}"> {!! $value->email ? $value->email : '<i class="fa-solid fa-times text-danger"></i> Email ID Not Available' !!} </a></span>
                </div>
            </div>
            <div class="plan-info">
                <ul>
                    <li>
                        <span>Plan</span>
                        <p>{{$value->plan_name??''}}</p>
                    </li>
                    <li>
                        <span>Plan Type</span>
                        <p>{{$value->plan_type_name ?? ''}}</p>
                    </li>
                    <li>
                        <span>Plan Start Date</span>
                        <p>{{ $value->plan_start_date ? date('j M Y', strtotime($value->plan_start_date)) : '' }}</p>

                    </li>
                    <li>
                        <span>Plan End Date</span>
                        <p>{{ $value->plan_end_date ? date('j M Y', strtotime($value->plan_end_date)) : '' }}</p>
                    </li>
                    <li>
                        <span>Payment Status</span>
                        <div class="d-flex g-1">
                            @if(!empty(learnerTransaction($value->id,$value->learner_detail_id)->pending_amount) && learnerTransaction($value->id,$value->learner_detail_id)->pending_amount==0)
                            <span class="payment" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?">Fully Paid</span>

                            <form action="{{ route('fee.generateReceipt') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="id" value="{{ $value->id ?? 'NA'}}">
                                <input type="hidden" name="type" value="learner">
                                <button type="submit">
                                    <i class="fa fa-download receipt"></i>
                                </button>
                            </form>

                            @elseif(empty(learnerTransaction($value->id,$value->learner_detail_id)->pending_amount))
                            <span></span>
                            @elseif( pending_amt($value->learner_detail_id))
                            <a href="{{ route('learner.pending.payment', ['id' => $value->id]) }}" class="text-danger d-block">
                                @if(overdue($value->id,learnerTransaction($value->id, $value->learner_detail_id)->pending_amount))
                                <span class="extended" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?">Overdue {{ rtrim(rtrim(number_format(optional(learnerTransaction($value->id, $value->learner_detail_id))->pending_amount, 2, '.', ''), '0'), '.') }}({{date('j M Y', strtotime($due_date->due_date))}})</span>
                                @else
                                <span class="extended" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?"> {{ rtrim(rtrim(number_format(optional(learnerTransaction($value->id, $value->learner_detail_id))->pending_amount, 2, '.', ''), '0'), '.') }}({{$due_date->due_date}})</span>
                                @endif
                            </a>

                            @elseif(paylater($value->learner_detail_id))
                            <span class="extended" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?">Pay Later</span>
                            @endif
                        </div>
                    </li>
                    <li>
                        <span>Locker</span>
                        @if(optional($transaction)->locker_amount)
                            <p>Yes – #{{ $transaction->locker_amount }} Paid</p>
                        @else
                            <p>No</p>
                        @endif


                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endforeach

{{-- Pagination --}}
@if ($learnerHistory->lastPage() > 1)
<ul class="paginations">
    {{-- Prev Button --}}
    <li>
        <a href="{{ $learnerHistory->onFirstPage() ? '#' : $learnerHistory->previousPageUrl() }}" class="w-auto px-3 text-muted {{ $learnerHistory->onFirstPage() ? 'disabled' : '' }}">
            Prev
        </a>
    </li>

    {{-- Page Numbers --}}
    @for ($i = 1; $i <= $learnerHistory->lastPage(); $i++)
        <li>
            <a href="{{ $learnerHistory->url($i) }}" class="{{ $learnerHistory->currentPage() == $i ? 'active' : '' }}">
                {{ $i }}
            </a>
        </li>
        @endfor

        {{-- Next Button --}}
        <li>
            <a href="{{ $learnerHistory->hasMorePages() ? $learnerHistory->nextPageUrl() : '#' }}" class="w-auto px-3 text-muted {{ $learnerHistory->hasMorePages() ? '' : 'disabled' }}">
                Next
            </a>
        </li>
</ul>
@endif
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