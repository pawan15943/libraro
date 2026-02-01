@extends('layouts.library')
@section('content')
<style>
   

    .seat-no {
        width: calc(45% - .5rem) !important;
        
    }
    .seat-actions {
        width: calc(55% - .5rem) !important;
        
    }

    @media (max-width: 843px) {
        .seat-no, .seat-actions {
            width: 100% !important;
            margin-bottom: .5rem;
        }
    }
</style>
<!-- Content Header (Page header) -->
@php

$current_route = Route::currentRouteName();
@endphp
@if ($learnerHistory->total()==0 && (!request()->has('search') || !request()->has('plan_id') || !request()->has('is_paid') || !request()->has('status')))
 <div class="no-data-found text-center py-5">
        <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>
        <dotlottie-wc
            src="https://lottie.host/2bd4f1dd-bce9-44cb-b8a4-f5acd681c123/sHuYyTQ6uD.lottie"
            style="width: 200px;height: 200px"
            autoplay
            loop
        ></dotlottie-wc>

       
                <h4>No Learners in This Category</h4>
                <span>You don’t have any learners that are expired, deleted, or closed yet.</span>            <div class="heading-list justify-content-end mb-1">
                @if(getCurrentBranch() !=0)
                    <a href="javascript:;" class="btn btn-primary export noseat_popup">
                        <i class="fa-solid fa-plus"></i> Book Seat
                    </a>
                @else
                    <h4>To add Plan Prices, first select your Branch.</h4>
                    <span>Plan names remain the same across all branches, but prices can be different. That’s why you need to choose the branch before adding plan prices.</span>
                @endif
            </div>
       
    </div>
@else
<div class="row">
    <div class="col-lg-12 text-end">
        <a href="{{ route('learners.export-csv') }}" class="btn btn-primary export" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Filter" id="filter"><i class="fa-solid fa-filter"></i></a>
    </div>
</div>

@can('has-permission', 'Filter')
<div class="row mb-3" id="filterContainer">
    <div class="col-lg-12">
        <div class="filter p-3 bg-white">
            <form action="{{ route('learnerHistory') }}" method="GET">
                <div class="row g-4">
                    <!-- Search By Name, Mobile & Email -->
                    <div class="col-lg-4">
                        <input type="text" class="form-control" name="search" placeholder="Enter Name, Mobile or Email"
                            value="{{ request()->get('search') }}">
                    </div>


                    <!-- Filter By Plan -->
                    <div class="col-lg-2">
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
                    <div class="col-lg-2">
                        <select name="is_paid" id="is_paid" class="form-select">
                            <option value="">Choose Payment Status</option>
                            <option value="1" {{ request()->get('is_paid') == '1' ? 'selected' : '' }}>Paid</option>
                            <option value="0" {{ request()->get('is_paid') == '0' ? 'selected' : '' }}>Unpaid</option>
                        </select>
                    </div>

                    <!-- Filter By Active/Expired Status -->
                    <div class="col-lg-2">
                        <select name="status" id="status" class="form-select">
                            <option value="">Choose Status</option>
                            <option value="active" {{ request()->get('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="expired" {{ request()->get('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                        </select>
                    </div>


                    <div class="col-lg-1">
                        <button class="btn btn-primary button">
                            <i class="fa fa-search"></i>
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
@endcan


<p><b>{{ $learnerHistory->total() }} Records for {{ $learnerHistory->perPage() }} per page</b></p>
@if($learnerHistory->total()==0)
    <div class="no-record-found text-center">
        <h4>No Record Found</h4>
    </div>
@endif
@foreach($learnerHistory as $key => $value)

@php

$learner_detail_id=$value->learner_detail_id;
$planStatus = getPlanStatusDetails($value->plan_end_date);
$transaction = learnerTransaction($value->id, $value->learner_detail_id);
if ($transaction && isset($transaction->pending_amount) && $transaction->due_date) {
    
$due_date = $transaction->due_date;
} else {

$due_date = null;
}

$learner_id=$value->id;
$operation = optional(getLearnerOperation($learner_detail_id))->operation;
$operationDate=optional(getLearnerOperation($learner_detail_id))->created_at;
\Log::info('Learner operation debug', [
        'learner_detail_id' => $learner_detail_id,
        'operation' => $operation,
        'operation_date' => $operationDate,
    ]);
 @endphp


<div class="row">
    <div class="col-lg-12">
        <div class="seat-info bg-white">    
            <div class="seat-no">
              
                <span style="display: inline-block !important;">
                    
                    Seat No.: {{ $value->seat_no ? getSeatDisplayByMainNo($value->seat_no): 'GEN' }} &nbsp;
                </span>
                

                @if($operation == 'closeSeat')
                <span class="extended" style="display: inline-block !important;"> Closed Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}</span>
                @elseif($operation == 'deleteSeat' && $value->deleted_at !=null)
                <span class="extended" style="display: inline-block !important;"> Deleted Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}</span>
                @else
                {!! getUserStatusWithSpan($value->plan_end_date,$learner_id) !!}
                @endif
                @if(round(learnerTransaction($value->id, $value->learner_detail_id)?->refund) > 0)
                <span style="display: inline-block !important; ">
                @if(round(learnerTransaction($value->id, $value->learner_detail_id)?->refund ?? 0) != 0)
                    Refund due : {{ round(learnerTransaction($value->id, $value->learner_detail_id)?->refund ?? 0) }}
                @endif                
                </span>
                @endif
            </div>
            <div class="seat-actions">
                <ul>
                    {{-- Reactivate Seat --}}
                    @can('has-permission', 'Reactive Seat')
                        <li><a href="{{route('learners.reactive',$value->id)}}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Reactivate Learner" class="px-2 w-auto"><i class="fa-solid fa-arrows-rotate pe-2"></i> Reactivate Seat</a></li>
                    @endcan

                    {{-- Refund Amount --}}
                    @if(refund($value->id)!=0 && learnerTransaction($value->id, $value->learner_detail_id)?->refund >0)
                        <li><a href="{{route('learner.other.payment',$value->learner_detail_id)}}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Learner Refund" class="payment-learner px-2 w-auto"><i class="fa-solid fa-money-bill pe-2"> </i> Refund</a></li>
                    @endif
                    
                    <!-- View Seat Info -->
                    @can('has-permission', 'View Seat')
                        <li><a href="{{route('learners.show',$value->id)}}" title="View Seat Booking Full Details" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="View Seat Booking Full Details"><i class="fas fa-eye"></i></a></li>
                    @endcan

                    {{-- Permanently Delete Seat --}}
                    @can('has-permission', 'Delete Seat')
                        @if($value->status == 0 && (empty($transaction?->refund) || $transaction->refund == 0))                    
                            <li><a href="#" data-id="{{$learner_id}}" data-learnerDetail="{{ $value->learner_detail_id }}" data-seat="{{$value->seat_no}}" data-permanent="1" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Permanent Delete Lerners" class="delete-permanent-customer"><i class="fas fa-trash text-danger"></i></a></li>
                        @endif
                    @endcan
                    @if($operation == 'deleteSeat' && $value->deleted_at !=null)
                        <li>
                            <a href="#" 
                            data-id="{{ $learner_id }}" 
                            data-learnerDetail="{{ $learner_detail_id }}"  
                            data-seat="{{ $value->seat_no }}"  
                            data-bs-toggle="tooltip" 
                            title="Restore Learner" 
                            class="restore-customer">
                            <i class="fas fa-trash-restore"></i>
                            </a>
                        </li>
                    @endif

                </ul>
            </div>

            <div class="seat-informarion">
                <img src="{{ $value->profile_picture ? asset($value->profile_picture) : asset('public/img/student_profile.jpeg') }}" alt="profile">
                <div class="information">
                    <h4>{{$value->name}}
                        @if($operation == 'closeSeat')
                        <span class="extended">Closed</span>
                        @elseif($operation == 'deleteSeat' && $value->deleted_at !=null)
                        <span class="extended">Deleted</span>
                         @else
                        
                        <span class=" {{ $planStatus['class'] == 'expired' ? 'expired' : ($planStatus['class'] == 'extedned' ? 'extedned' : 'actives') }} ps-1">{{$planStatus['status']}}</span>
                        @endif
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
                    <li style="width: 20%;">
                        <span>Payment Status</span>
                        <div class="d-flex g-1">
                            <p class="text-danger">
                                Refunded : {{ round(refund($value->id)) }}
                            </p>
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
<ul class="paginations mt-4">
    {{-- Prev Button --}}
    <li>
        <a href="{{ $learnerHistory->onFirstPage() ? '#' : $learnerHistory->appends(request()->all())->previousPageUrl() }}" class="w-auto px-3 text-muted">
            Prev
        </a>
    </li>

    {{-- Page Numbers --}}
     @for ($i = max(1, $learnerHistory->currentPage() - 2); $i <= min($learnerHistory->lastPage(), $learnerHistory->currentPage() + 2); $i++)
    {{-- @for ($i = 1; $i <= $learnerHistory->lastPage(); $i++) --}}
        <li>
            <a href="{{ $learnerHistory->url($i) }}" class="{{ $learnerHistory->currentPage() == $i ? 'active' : '' }}">
                {{ $i }}
            </a>
        </li>
    @endfor
     @if ($learnerHistory->currentPage() < $learnerHistory->lastPage() - 2)
        <li><span>...</span></li>
        <li><a href="{{ $learnerHistory->url($learnerHistory->lastPage()) }}">{{ $learnerHistory->lastPage() }}</a></li>
    @endif


        {{-- Next Button --}}
        <li>
             <a href="{{ $learnerHistory->hasMorePages() ? $learnerHistory->nextPageUrl() : '#' }}" class="w-auto px-3 text-muted">Next</a>
                Next
            </a>
        </li>
</ul>
@endif
    
@endif

<!-- /.content -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).on('click', '.delete-customer', function() {
        var id = $(this).data('id');
        var url = '{{ route('learners.destroy', ':id') }}';
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
        var url = '{{ route( 'learners.close' ) }}'; // Adjust the route as necessary

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