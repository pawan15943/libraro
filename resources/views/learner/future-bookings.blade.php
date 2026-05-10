@extends('layouts.library')
@section('content')

<!-- Content Header (Page header) -->
@if ( $learners->total()==0)
<div class="no-data-found">
    <script
        src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js"
        type="module"></script>

    <dotlottie-wc
    src="https://lottie.host/2bd4f1dd-bce9-44cb-b8a4-f5acd681c123/sHuYyTQ6uD.lottie"
    style="width: 200px;height: 200px"
    autoplay
    loop
    ></dotlottie-wc>
    <h4>No Future Bookings</h4>
    <span>You haven’t added any learners for upcoming dates. Add learners by clicking the button below.</span>
    <!-- Masters -->
    <div class="heading-list justify-content-end mb-1">
        @if(getCurrentBranch() !=0)
        <a href="javascript:;"  class="btn btn-primary export noseat_popup">
            <i class="fa-solid fa-plus "></i> Book Seat
        </a>
        @else
        <h4>To add Plan Prices, first select your Branch.</h4>
        <span> Plan names remain the same across all branches, but prices can be different. That’s why you need to choose the branch before adding plan prices.</span>
        @endif
    </div>
</div>
@else
<div class="row">
    <div class="col-lg-12 text-end">
        <a href="{{ route('learners.export-csv') }}" class="btn btn-primary export" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Filter" id="filter"><i class="fa-solid fa-filter"></i></a>
        <a href="{{ route('learners.export-csv') }}" class="btn btn-primary export" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Counts" id="counts"><i class="fa-solid fa-star"></i></a>

        @can('has-permission', 'Export Library Seats')
        @if(!in_array('22', toggleHideField()))
        <a href="{{ route('learners.export-csv') }}" class="btn btn-primary export" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Export Learners Data to CSV"><i class="fa-solid fa-file-export"></i></a>
        @endif
        @endcan
        @can('has-permission', 'Import Library Seats')
        @if(!in_array('11', toggleHideField()))
        <a href="{{ route('library.upload.form') }}" class="btn btn-primary export bg-4" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Import Learners Data to Portal"><i class="fa-solid fa-file-import"></i></a>
        @endif
        @endcan
    </div>
</div>
@can('has-permission', 'Filter')
<div class="row mb-3" id="filterContainer">
    <div class="col-lg-12">
        <div class="filter p-3 bg-white">
            <form action="{{ route('learners') }}" method="GET">
                <div class="row g-3">
                    <div class="col-lg-2">
                        <input type="text" class="form-control" name="search" placeholder="Enter Name, Mobile or Email" value="{{ request()->get('search') }}">
                    </div>

                    <!-- Filter By Plan -->
                    <div class="col-lg-2">
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
                        <select name="is_paid" id="is_paid" class="form-select">
                            <option value="">Choose Status</option>
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

                    <!-- Filter By Seat No -->
                    <div class="col-lg-2">
                        <select name="seat_no" id="seat_no" class="form-select">
                            <option value="">Seat No</option>
                            @for($seatNo = 1; $seatNo <= $totalSeats; $seatNo++) <option value="{{ $seatNo }}" {{ request()->get('seat_no') == $seatNo ? 'selected' : '' }}>
                                {{ $seatNo }}
                                </option>
                                @endfor
                        </select>
                    </div>

                    <!-- Search Button -->
                    <div class="col-lg-1 align-self-end">
                        <button class="btn btn-primary button" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Search">
                            Search
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
@if(!in_array('24', toggleHideField()))
<div class="col-lg-12 mb-4" id="countsContainer">
    <div class="records">

        <p class="mb-2 text-dark"><b>Total Seats : {{$total_seats}} | Available Seats : {{$availble_seats}} | Booked Seats: {{$booked_seats}} | General Seats: {{$genral_seat ?? 0}}</b></p>

        <span class="text-success">Total Available Slots ({{$availble_seats}})</span> <span class="text-success">Total Booked Slots ({{$active_seat_count}})</span> <span class="text-danger">Total Expired Slots({{$expired_seat}})</span> <span class="text-danger">Extended Slots({{$extended_seats}})</span>
        @foreach($planTypeCounts as $plan)
        <span class="text-danger">{{ $plan['abbr'] }}: {{ $plan['name'] }} ({{ $plan['count'] }})</span>
        @endforeach

    </div>
</div>
@endif



<div class="mb-3 set-table">
    <p class="m-0"><b>{{ $learners->total() }} Records for {{ $learners->perPage() }} per page</b></p>
    <a class="sort" href="{{ request()->fullUrlWithQuery([
        'sort_by' => 'seat_no',
        'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'
    ]) }}">
        Sort by Seat No. 
        @if(request('sort_by') == 'seat_no')
            ({{ request('sort_order') == 'asc' ? '↑' : '↓' }})
        @endif
    </a>
</div>

@foreach($learners as $key => $value)

    @php
    $learner_detail_id=$value->learner_detail_id;
    $planStatus = getPlanStatusDetails($value->plan_end_date);
    $transaction = learnerTransaction($value->id, $learner_detail_id);
    $oneWeekLater = \Carbon\Carbon::parse($value->plan_start_date)->addWeek();
    

    if ($transaction && isset($transaction->pending_amount) && $transaction->due_date) {
    
    $due_date = $transaction->due_date;
    } else {
    
    $due_date = null;
    }

    $today = \Carbon\Carbon::now();
    $threeDaysAfterStart  = \Carbon\Carbon::parse($value->plan_start_date)->addDays(3);
   $operation = optional(getLearnerOperation($learner_detail_id))->operation;
   $operationDate=optional(getLearnerOperation($learner_detail_id))->created_at;
   $learner_id=$value->id;
    @endphp

<!-- Modal -->


<div class="row">
    <div class="col-lg-12">
        <div class="seat-info bg-white">
            <div class="seat-no">

                @if($value->seat_no )
                <span> Seat No. : {{$value->seat_no ? getSeatDisplayByMainNo($value->seat_no) : 'GEN'}} </span>
                @else
                <span> Seat No. : {{$value->seat_no ? getSeatDisplayByMainNo($value->seat_no) : 'GEN'}} </span>
                @endif
                @if($operation == 'closeSeat')
                <span class="extended"> Closed Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}</span>
                @elseif($operation == 'deleteSeat' && $value->deleted_at !=null)
                <span class="extended"> Deleted Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}</span>
                @else
                {!! getUserStatusWithSpan($value->plan_end_date,$learner_id) !!}
                @endif



            </div>
            <div class="seat-actions">
                <ul>
                    <!-- Edit Seat Info -->
                    @if($planStatus['diff_extend_day']>0)

                        {{-- <li><a href="{{route('learner.expire',$value->id)}}" title="Custom Seat Expire"><i class="fas fa-calendar"></i></a></li> --}}

                        <!-- To Handle Paylater & Pending Amount Icon -->
                        {{-- @if(paylater($learner_detail_id) || pending_amt($learner_detail_id))
                            @can('has-permission','Renew Seat')
                            <li><a href="{{ route('learner.pending.payment', ['id' => $transaction->id]) }}"  data-bs-toggle="tooltip" data-bs-placement="bottom" title="" data-original-title="Send Email Reminders" class="payment-learner w-auto px-2" >Pay Due Amount</a></li>
                            @endcan

                        @endif
                        @if($planStatus['diff_in_days'] <= 5 && $planStatus['diff_extend_day']> 0 )
                            @can('has-permission','Renew Seat')
                            <li><a href="{{route('learner.renew.plan',$value->id)}}" title="Renew Plan" class="w-auto px-2">Renew</a></li>

                            @endcan
                        @endif

                        @if($planStatus['diff_in_days'] <= 5 && $planStatus['diff_extend_day']> 0 )
                           

                            @can('has-permission', 'WhatsApp Notification')
                            <li><a href="https://web.whatsapp.com/send?phone=91{{$value->mobile}}&text=Hey!%20🌟%0A%0AJust%20a%20friendly%20reminder:%20Your%20library%20seat%20plan%20will%20expire%20in%205%20days!%20📚✨%0A%0ADon%E2%80%99t%20miss%20out%20on%20the%20chance%20to%20keep%20enjoying%20your%20favorite%20books%20and%20resources.%20Plus,%20renewing%20now%20means%20you%20can%20unlock%20exciting%20rewards!%20🎁" target="_blank" data-id="11" onclick="incrementMessageCount({{ $value->id }}, 'whatsapp')" class="whatsapp w-auto px-2" data-bs-toggle="tooltip" data-bs-placement="bottom" data-original-title="Send WhatsApp Reminder"><i class="fa-brands fa-whatsapp pe-1"></i> Send Reminder</a></li>

                            @endcan
                           
                        @endif --}}
                         <!-- Sent Mail -->
                            {{-- @can('has-permission', 'Email Notification')
                            <li><a href="mailto:{{$value->email }}?subject=Library Seat Renewal Reminder&body=Hey!%20🌟%0D%0A%0D%0AJust%20a%20friendly%20reminder:%20Your%20library%20seat%20plan%20will%20expire%20in%205%20days!%20📚✨%0D%0A%0D%0ADon%E2%80%99t%20miss%20out%20on%20the%20chance%20to%20keep%20enjoying%20your%20favorite%20books%20and%20resources.%20Plus,%20renewing%20now%20means%20you%20can%20unlock%20exciting%20rewards!%20🎁" target="_blank" data-id="11" onclick="incrementMessageCount({{ $value->id }}, 'email')" class="message" data-bs-toggle="tooltip" data-bs-placement="bottom" title="" data-original-title="Send Email Reminders"><i class="fas fa-envelope"></i></a></li>
                            @endcan --}}
                            <!-- Swap Seat-->

                        @can('has-permission', 'Swap Seat')

                        <li><a href="{{route('learners.swap',$value->id)}}" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Swap Seat"><i class="fa-solid fa-arrow-right-arrow-left"></i></a></li>

                        @endcan


                        {{-- @can('has-permission', 'Change Plan')

                            @if(!in_array('14', toggleHideField()) && !$today->greaterThanOrEqualTo($oneWeekLater))
                            <li><a href="{{route('learner.change.plan',$value->id)}}" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Change Plan"><i class="fa fa-arrow-up-short-wide"></i></a></li>
                            @endif
                        @endcan --}}
                                <!---ID Card generate-->
                                {{-- @if(!in_array('15', toggleHideField()))
                                <li data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Genrate ID Card">
                                <form action="{{ route('generateIdCard') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                <input type="hidden" id="custId" name="detail_id" value="{{ $learner_detail_id }}">
                                <input type="hidden" name="learner_id" value="{{ $value->id }}">
                                <button type="submit"><i class="fa-solid fa-id-card-clip"></i></button>
                                </form>
                                </li>
                                @endif --}}
                        @if(!in_array('15', toggleHideField()))
                        <li><a target="_blank" href="{{ route('idCard',  $learner_detail_id) }}" class="" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Genrate ID Card"><i class="fa-solid fa-id-card-clip"></i> </a></li>
                        @endif
                            <!-- upgrade Seat-->
                             <!-- (&& $planStatus['diff_extend_day'] <= 5) we remove this block -->
                        {{-- @if($planStatus['diff_in_days'] <= 5 && $planStatus['diff_extend_day']> 0 )
                                @can('has-permission', 'Upgrade Seat Plan' )
                                    @if(!in_array('13', toggleHideField()))
                                    <li><a href="{{route('learners.upgrade',$value->id)}}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Upgrade Plan"><i class="fa-solid fa-circle-up"></i></a></li>
                                    @endif
                                @endcan

                        @endif --}}
                                    <!-- Close Seat -->

                        @can('has-permission', 'Close Seat')
                            @if(!in_array('16', toggleHideField()))
                            <li><a href="javascript:void(0);" class="link-close-plan" data-id="{{$value->id}}" data-learnerDetail="{{ $learner_detail_id }}" data-learner_detail_id="{{$learner_detail_id}}" data-payblerefund="{{ paybleRefund($learner_detail_id) }}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Close Plan" data-plan_end_date="{{$value->plan_end_date}}"><i class="fas fa-times"></i></a></li>

                            @endif
                        @endcan
                    @endif

                    {{-- @can('has-permission', 'Reactive Seat')
                        @if($value->status==0)
                        <li><a href="{{route('learners.reactive',$value->id)}}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Reactivate Learner"><i class="fa-solid fa-arrows-rotate"></i></a></li>
                        @endif
                    @endcan --}}

                    <li><a href="{{route('learner.other.payment',$learner_detail_id)}}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Other Payment" class="payment-learner"><i class="fa-solid fa-money-bill"></i></a></li>
                    <!-- View Seat Info -->
                    @can('has-permission', 'View Seat')
                    <li><a href="{{route('learners.show',$value->id)}}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="View Seat Booking Full Details"><i class="fas fa-eye"></i></a></li>
                    @endcan

                    <!-- Deletr Seat -->

                    @can('has-permission', 'Edit Seat')
                    @if(!in_array('17', toggleHideField()))
                    <li><a href="{{route('learners.edit',$value->id)}}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Edit Seat Booking Details"><i class="fas fa-edit"></i></a></li>
                    <li><a href="{{route('learners.edit.plan',$value->id)}}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Edit Plan Details"><i class="fa-solid fa-calendar-days"></i></a></li>
                    @endif
                    @endcan

                    @can('has-permission', 'Delete Seat')
                    <li><a href="#" data-id="{{$value->id}}" data-learnerDetail="{{ $learner_detail_id }}" data-payblerefund="{{ paybleRefund($learner_detail_id) }}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Delete Lerners" class="delete-customer"><i class="fas fa-trash"></i></a></li>
                    @endcan
                        {{-- @can('has-permission', 'Delete Seat')
                        @if($today->lessThanOrEqualTo($threeDaysAfterStart))
                    <li><a href="#" data-id="{{$value->id}}" data-learnerDetail="{{ $learner_detail_id }}" data-seat="{{$value->seat_no}}" data-permanent="1" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Permanent Delete Lerners" class="delete-permanent-customer"><i class="fas fa-trash text-danger"></i></a></li>
                    @endif
                    @endcan --}}
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
                        <span  style="color: purple;">Upcoming</span>
                        @endif
                    </h4>
                    <span>UID : <a href="{{route('learners.show',$value->id)}}">{{$value->learner_no}}</a> &nbsp; | &nbsp; M : <a href="tel:+91-{{$value->mobile}}">+91-{{ display_learner_mobile($value->mobile) }}</a> </span>
                    <span class="d-block">E: <a href="mailto:{{$value->email}}"> @if($value->email) {{ display_learner_email($value->email) }} @else <i class="fa-solid fa-times text-danger"></i> Email ID Not Available @endif </a></span>
                </div>
            </div>
            <div class="plan-info">
                <ul>
                    <!-- Plan Details -->
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
                            @if(paylater($learner_detail_id) && learnerTransaction($learner_id,$learner_detail_id)->pending_amount!=0)
                             <a href="{{ route('learner.pending.payment', ['id' => $transaction->id]) }}" class="text-danger d-block">
                                <span class="extended" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?">
                                    PayLater {{ rtrim(rtrim(number_format(   (learnerTransaction($learner_id, $learner_detail_id))->pending_amount, 2, '.', ''), '0'), '.') }}

                                </span>
                             </a>
                            @elseif(!empty(learnerTransaction($learner_id,$learner_detail_id)->pending_amount) && learnerTransaction($learner_id,$learner_detail_id)->pending_amount==0)
                            <span class="payment" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?">Fully Paid</span>

                            <form action="{{ route('learner.receipt.download') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="learner_id" value="{{$learner_id}}">
                                <input type="hidden" name="id" value="{{ learnerTransaction($learner_id,$learner_detail_id)->id ?? 'NA'}}">
                                <input type="hidden" name="type" value="learner">
                                <button type="submit" class="noLoader">
                                    <i class="fa fa-download receipt"></i>
                                </button>
                            </form>

                            @elseif(empty(learnerTransaction($learner_id,$learner_detail_id)->pending_amount))
                            <span></span>

                            @elseif( pending_amt($learner_detail_id))
                            <a href="{{ route('learner.pending.payment', ['id' => $transaction->id]) }}" class="text-danger d-block">
                                @if(is_object($due_date) && !empty($due_date->due_date) && overdue($learner_id, learnerTransaction($learner_id, $learner_detail_id)->pending_amount))
                                <span class="extended" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?">Overdue {{ rtrim(rtrim(number_format(optional(learnerTransaction($learner_id, $learner_detail_id))->pending_amount, 2, '.', ''), '0'), '.') }}({{date('j M Y', strtotime($due_date->due_date))}})</span>
                                @else
                                <span class="extended" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?">
                                    Pending {{ rtrim(rtrim(number_format(   (learnerTransaction($learner_id, $learner_detail_id))->pending_amount, 2, '.', ''), '0'), '.') }}

                                </span>

                                @endif
                            </a>
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
<ul class="paginations mt-4">
    {{-- Prev --}}
    <li>
        <a href="{{ $learners->onFirstPage() ? '#' : $learners->previousPageUrl() }}" class="w-auto px-3 text-muted">Prev</a>
    </li>

    {{-- Page Numbers (shortened: 1 ... current ... last) --}}
    @if ($learners->currentPage() > 3)
        <li><a href="{{ $learners->url(1) }}">1</a></li>
        <li><span>...</span></li>
    @endif

    @for ($i = max(1, $learners->currentPage() - 2); $i <= min($learners->lastPage(), $learners->currentPage() + 2); $i++)
        <li>
            <a href="{{ $learners->url($i) }}" class="{{ $learners->currentPage() == $i ? 'active' : '' }}">
                {{ $i }}
            </a>
        </li>
    @endfor

    @if ($learners->currentPage() < $learners->lastPage() - 2)
        <li><span>...</span></li>
        <li><a href="{{ $learners->url($learners->lastPage()) }}">{{ $learners->lastPage() }}</a></li>
    @endif

    {{-- Next --}}
    <li>
        <a href="{{ $learners->hasMorePages() ? $learners->nextPageUrl() : '#' }}" class="w-auto px-3 text-muted">Next</a>
    </li>
</ul>
@endif

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
