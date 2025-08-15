@extends('layouts.library')
@section('content')

<!-- Content Header (Page header) -->

<div class="row">
    <div class="col-lg-12 text-end">
        
        @can('has-permission', 'Export Library Seats')
        @if(!in_array('22', toggleHideField()))
        <a href="{{ route('learners.export-csv') }}" class="btn btn-primary export"><i class="fa-solid fa-file-export"></i> Export All Data in CSV</a>
        @endif
        @endcan
        @can('has-permission', 'Import Library Seats')
        @if(!in_array('11', toggleHideField()))
        <a href="{{ route('library.upload.form') }}" class="btn btn-primary export bg-4"><i class="fa-solid fa-file-import"></i> Import Learners Data to Portal</a>
        @endif
        @endcan
    </div>
</div>
@can('has-permission', 'Filter')
<div class="row mb-3">
    <div class="col-lg-12">
        <div class="filter p-3 bg-white">
            <h4><i class="fa fa-filter"></i> Filter Learners</h4>
            <form action="{{ route('learners') }}" method="GET">
                <div class="row g-4">

                    <!-- Filter By Plan -->
                    <div class="col-lg-2">
                        <label for="plan_id">Choose Plan</label>
                        <select name="plan_id" id="plan_id2" class="form-select">
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
                        <label for="is_paid">Payment Status</label>
                        <select name="is_paid" id="is_paid" class="form-select">
                            <option value="">Choose Status</option>
                            <option value="1" {{ request()->get('is_paid') == '1' ? 'selected' : '' }}>Paid</option>
                            <option value="0" {{ request()->get('is_paid') == '0' ? 'selected' : '' }}>Unpaid</option>
                        </select>
                    </div>
                    <!-- Filter By Active/Expired Status -->
                    <div class="col-lg-2">
                        <label for="status">Active / Expired</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Choose Status</option>
                            <option value="active" {{ request()->get('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="expired" {{ request()->get('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                        </select>
                    </div>

                    <!-- Filter By Seat No -->
                    <div class="col-lg-2">
                        <label for="seat_no">Seat No.</label>
                        <select name="seat_no" id="seat_no" class="form-select">
                            <option value="">Seat No</option>
                            @for($seatNo = 1; $seatNo <= $totalSeats; $seatNo++) <option value="{{ $seatNo }}" {{ request()->get('seat_no') == $seatNo ? 'selected' : '' }}>
                                {{ $seatNo }}
                                </option>
                                @endfor
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label for="search">Search By</label>
                        <input type="text" class="form-control" name="search" placeholder="Enter Name, Mobile or Email" value="{{ request()->get('search') }}">
                    </div>

                    <!-- Search Button -->
                    <div class="col-lg-3 align-self-end">
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
@if(!in_array('24', toggleHideField()))
<div class="col-lg-12 mb-4">
    <div class="records">

        <p class="mb-2 text-dark"><b>Total Seats : {{$total_seats}} | Available Seats : {{$availble_seats}} | Booked Seats: {{$booked_seats}} | General Seats: {{$genral_seat ?? 0}}</b></p>

        <span class="text-success">Total Available Slots ({{$availble_seats}})</span> <span class="text-success">Total Booked Slots ({{$active_seat_count}})</span> <span class="text-danger">Total Expired Slots({{$expired_seat}})</span> <span class="text-danger">Extended Slots({{$extended_seats}})</span>
        @foreach($planTypeCounts as $plan)
        <span class="text-danger">{{ $plan['abbr'] }}: {{ $plan['name'] }} ({{ $plan['count'] }})</span>
        @endforeach

    </div>
</div>
@endif


<p><b>{{ $learners->total() }} Records for {{ $learners->perPage() }} per page</b></p>
@foreach($learners as $key => $value)

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
                    <!-- Edit Seat Info -->
                    @if($planStatus['diff_extend_day']>0)

                    {{-- <li><a href="{{route('learner.expire',$value->id)}}" title="Custom Seat Expire"><i class="fas fa-calendar"></i></a></li> --}}

                    <!-- Make payment -->
                    @if(paylater($value->learner_detail_id) || pending_amt($value->learner_detail_id))
                    @can('has-permission','Renew Seat')
                    <li><a href="{{route('learner.payment',$value->learner_detail_id)}}" title="Payment Lerners" class="payment-learner w-auto px-2">Pay Pending Amt.</a></li>

                    @endcan
                    @endif
                    @if($planStatus['diff_in_days'] <= 5 && $planStatus['diff_extend_day']> 0 )
                    @can('has-permission','Renew Seat')
                    <li><a href="{{route('learner.renew.plan',$value->id)}}" title="Renew Plan" class="w-auto px-2">Renew</a></li>

                    @endcan
                    @endif
                  
                    @if($planStatus['diff_in_days'] <= 5 && $planStatus['diff_extend_day']> 0 )
                        <!-- Sent Mail -->

                        @can('has-permission', 'WhatsApp Notification')
                        <li><a href="https://web.whatsapp.com/send?phone=91{{$value->mobile}}&text=Hey!%20🌟%0A%0AJust%20a%20friendly%20reminder:%20Your%20library%20seat%20plan%20will%20expire%20in%205%20days!%20📚✨%0A%0ADon%E2%80%99t%20miss%20out%20on%20the%20chance%20to%20keep%20enjoying%20your%20favorite%20books%20and%20resources.%20Plus,%20renewing%20now%20means%20you%20can%20unlock%20exciting%20rewards!%20🎁" target="_blank" data-id="11" onclick="incrementMessageCount({{ $value->id }}, 'whatsapp')" class="whatsapp w-auto px-2" data-bs-toggle="tooltip" data-bs-placement="bottom" title="" data-original-title="Send WhatsApp Reminder"><i class="fa-brands fa-whatsapp pe-1"></i> Send Reminder</a></li>

                        @endcan
                        <!-- Sent Mail -->
                        {{-- @can('has-permission', 'Email Notification')
                        <li><a href="mailto:{{$value->email }}?subject=Library Seat Renewal Reminder&body=Hey!%20🌟%0D%0A%0D%0AJust%20a%20friendly%20reminder:%20Your%20library%20seat%20plan%20will%20expire%20in%205%20days!%20📚✨%0D%0A%0D%0ADon%E2%80%99t%20miss%20out%20on%20the%20chance%20to%20keep%20enjoying%20your%20favorite%20books%20and%20resources.%20Plus,%20renewing%20now%20means%20you%20can%20unlock%20exciting%20rewards!%20🎁" target="_blank" data-id="11" onclick="incrementMessageCount({{ $value->id }}, 'email')" class="message" data-bs-toggle="tooltip" data-bs-placement="bottom" title="" data-original-title="Send Email Reminders"><i class="fas fa-envelope"></i></a></li>
                        @endcan --}}
                    @endif
                        <!-- Swap Seat-->

                    @can('has-permission', 'Swap Seat')

                    <li><a href="{{route('learners.swap',$value->id)}}" title="Swap Seat "><i class="fa-solid fa-arrow-right-arrow-left"></i></a></li>

                    @endcan


                    @can('has-permission', 'Change Plan')
                        @if(!in_array('14', toggleHideField()))
                        <li><a href="{{route('learner.change.plan',$value->id)}}" title="Change Plan"><i class="fa fa-arrow-up-short-wide"></i></a></li>
                        @endif
                    @endcan
                        <!---ID Card generate-->
                        {{-- @if(!in_array('15', toggleHideField()))
                         <li>
                        <form action="{{ route('generateIdCard') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="custId" name="detail_id" value="{{ $value->learner_detail_id }}">
                        <input type="hidden" name="learner_id" value="{{ $value->id }}">
                        <button type="submit"><i class="fa-solid fa-id-card-clip"></i></button>
                        </form>
                        </li>
                        @endif --}}
                    @if(!in_array('15', toggleHideField()))
                    <li><a target="_blank" href="{{ route('idCard',  $value->learner_detail_id) }}" class=""><i class="fa-solid fa-id-card-clip"></i> </a></li>
                    @endif
                    <!-- upgrade Seat-->
                    @if($planStatus['diff_in_days'] <= 5 && $planStatus['diff_extend_day']> 0 && $planStatus['diff_extend_day'] <= 5)
                        @can('has-permission', 'Upgrade Seat Plan' )
                        @if(!in_array('13', toggleHideField()))
                        <li><a href="{{route('learners.upgrade.renew',$value->id)}}" title="Upgrade Plan"><i class="fa-solid fa-circle-up"></i></a></li>
                        @endif
                        @endcan

                    @endif
                    <!-- Close Seat -->

                    @can('has-permission', 'Close Seat')
                    @if(!in_array('16', toggleHideField()))
                    <li><a href="javascript:void(0);" class="link-close-plan" data-id="{{ $value->id }}" data-learner_detail_id="{{$value->learner_detail_id}}" title="Close" data-plan_end_date="{{$value->plan_end_date}}"><i class="fas fa-times"></i></a></li>
                    @endif
                    @endcan
                    @endif

                    @can('has-permission', 'Reactive Seat')
                    @if($value->status==0)
                    <li><a href="{{route('learners.reactive',$value->id)}}" title="Reactivate Learner"><i class="fa-solid fa-arrows-rotate"></i></a></li>
                    @endif
                    @endcan

                    <li><a href="{{route('learner.other.payment',$value->learner_detail_id)}}" title="Other Payment " class="payment-learner"><i class="fa-solid fa-money-bill"></i></a></li>
                    <!-- View Seat Info -->
                    @can('has-permission', 'View Seat')
                    <li><a href="{{route('learners.show',$value->id)}}" title="View Seat Booking Full Details"><i class="fas fa-eye"></i></a></li>
                    @endcan

                    <!-- Deletr Seat -->

                    @can('has-permission', 'Edit Seat')
                    @if(!in_array('17', toggleHideField()))
                    <li><a href="{{route('learners.edit',$value->id)}}" title="Edit Seat Booking Details"><i class="fas fa-edit"></i></a></li>
                    @endif
                    @endcan

                    @can('has-permission', 'Delete Seat')
                    <li><a href="#" data-id="{{$value->id}}" data-learnerDetail="{{ $value->learner_detail_id }}" title="Delete Lerners" class="delete-customer"><i class="fas fa-trash"></i></a></li>
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
                         @if($transaction && $transaction->locker_amount)
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
 @if ($learners->lastPage() > 1)
<ul class="paginations">
    {{-- Prev Button --}}
    <li>
        <a href="{{ $learners->onFirstPage() ? '#' : $learners->appends(request()->all())->previousPageUrl() }}" class="w-auto px-3 text-muted">
            Prev
        </a>
    </li>

    {{-- Page Numbers --}}
    @for ($i = 1; $i <= $learners->lastPage(); $i++)
        <li>
            <a href="{{ $learners->appends(request()->all())->url($i) }}" class="{{ $learners->currentPage() == $i ? 'active' : '' }}">
                {{ $i }}
            </a>
        </li>
        @endfor

        {{-- Next Button --}}
        <li>
            <a href="{{ $learners->hasMorePages() ? $learners->appends(request()->all())->nextPageUrl() : '#' }}" class="w-auto px-3 text-muted">
                Next
            </a>
        </li>
</ul>
@endif
<!-- Modal Popup end for Configration -->

<!-- /.content -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>
    $(document).ready(function() {
        let table = new DataTable('#datatable', {
            searching: false, // This option hides the search bar
            ordering: false
        });
        var url = window.location.href;

        // Check if there are any URL parameters
        if (url.includes('?')) {
            // Redirect to the URL without parameters
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });

</script>

@endsection
