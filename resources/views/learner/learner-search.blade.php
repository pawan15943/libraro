@extends('layouts.library')
@section('content')

<style>
    .actionIcon {
        display: none;
    }

    .btn.btn-primary.button {
        background: var(--c1) !important;
        color: #fff !important;
        margin: 0 auto;
        height: auto;
        gap: .8rem;
        width: auto;
        padding: .5rem 1.5rem;
    }

    ul.learner-info {
        list-style: none;
        padding: 0;
        display: flex;
        gap: 1.5rem;
        justify-content: space-between;
        padding: 1.2rem;
        border: 1px solid #dcdcdc;
        border-radius: .8rem;
        background: #fff;
        margin: 0;
        z-index: 2;
        position: relative;
        align-items: flex-start;
    }

    ul.learner-info li {
        width: calc(100% / 7);
    }

    ul.learner-info .d-flex {
        flex-direction: column;

    }
    ul.learner-info small{
        margin: 0 !important;
    }


    ul.learner-info span {
        font-size: .8rem;
        text-transform: uppercase;
        font-weight: 500;
        color: #ababab;
    }
    .text-warning {
        --bs-text-opacity: 1;
        color: rgb(213 160 0) !important;
    }
    ul.learner-info h5 {
        font-weight: 700;
        margin: 0;
        font-size: .8rem;
        text-transform: uppercase;
    }

    .action {
        background: #f7f7f7;
        padding: 1rem;
        border-radius: 0 0 1rem 1rem;
        display: flex;
        padding-top: 1.5rem;
        gap: .5rem;
        margin-top: -.5rem;
        z-index: 0;
        position: relative;
        flex-wrap: wrap;
    }

    .action a {
        text-decoration: none;
    }

    .record {
        background: #f5f5f5;
        border-radius: .8rem;
        position: relative;
        z-index: 0;
    }

    .record i {
        background: #000;
        width: 36px;
        height: 35px;
        display: flex ! IMPORTANT;
        justify-content: center;
        align-items: center;
        border-radius: 35px;
        color: #fff;
        font-size: .8rem;
        box-shadow: 1px 0 15px #00000045;
    }

    i:hover {
        background: #ababab;
    }

    @media screen and (max-width:768px) {
        ul.learner-info {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
    }

    h2.font-weight-700 {
        font-size: 1.5rem;
        margin-bottom: 1.25rem;
    }

    input.form-control.form-control-lg.text-center {
        height: 50px !important;
        font-size: 1rem 16px !important;
    }

.content {
    background: linear-gradient(180deg, #eafdff, transparent),
                url("http://localhost/genrate/public/img/search-bg.webp") no-repeat !important;
    background-position: bottom !important;
    background-size: 100% !important;
}
</style>

<!-- Content Header (Page header) -->

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
                <div class="row justify-content-center mt-5 mb-4">
                    <div class="col-lg-6 text-center">
                        <h2 class="font-weight-700">Search Here</h2>
                        <form action="{{ route('learner.search') }}" method="GET">
                            <div class="row g-4">
                                <div class="col-lg-12">
                                    <input type="text" name="search" class="form-control @error('search') is-invalid @enderror form-control-lg text-center" value="{{ request()->get('search') }}" placeholder="Search Here by Name | Mobile | Seat No" id="search-input">
                                    @error('search')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                    
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-lg-12 text-center">
                                    <button class="btn btn-primary button">
                                        Search
                                    </button>
                                </div>
                            </div>
                        </form>
                        @if(isset($learners) && $learners->isEmpty())
                        <div class="row mt-3">
                            <div class="col-lg-12">
                                <h4>No results found.</h4>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @if(isset($learners) && $learners->total() > 0)
                <p><b>{{ $learners->total() }} Records for {{ $learners->perPage() }} per page</b></p>
                @endif
                @foreach($learners ?? [] as $key => $value)

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
                <div class="row ">
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
                                        <!-- Make payment -->
                                        @if(paylater($value->learner_detail_id) || pending_amt($value->learner_detail_id))
                                        @can('has-permission','Renew Seat')
                                        <li><a href="{{route('learner.payment',$value->learner_detail_id)}}" title="Payment Lerners" class="payment-learner w-auto px-2">Pay Pending Amt.</a></li>

                                        @endcan
                                    @endif
                                    @if($planStatus['diff_in_days'] <= 5 && $planStatus['diff_extend_day']> 0 )
                                    @can('has-permission','Renew Seat')
                                    <li><a href="{{route('learner.renew.plan',$value->id)}}" title="Renew Plan" class="w-auto px-2" 000\ nh8uy>Renew</a></li>

                                    @endcan
                                    @endif
                                    @if($planStatus['diff_extend_day'] > 0)
                                            <!-- Sent Mail -->

                                            @can('has-permission', 'WhatsApp Notification')
                                            <li><a href="https://web.whatsapp.com/send?phone=91{{$value->mobile}}&text=Hey!%20🌟%0A%0AJust%20a%20friendly%20reminder:%20Your%20library%20seat%20plan%20will%20expire%20in%205%20days!%20📚✨%0A%0ADon%E2%80%99t%20miss%20out%20on%20the%20chance%20to%20keep%20enjoying%20your%20favorite%20books%20and%20resources.%20Plus,%20renewing%20now%20means%20you%20can%20unlock%20exciting%20rewards!%20🎁" target="_blank" data-id="11" onclick="incrementMessageCount({{ $value->id }}, 'whatsapp')" class="whatsapp w-auto px-2" data-bs-toggle="tooltip" data-bs-placement="bottom" title="" data-original-title="Send WhatsApp Reminder"><i class="fa-brands fa-whatsapp pe-1"></i> Send Reminder</a></li>

                                            @endcan
                                            <!-- Sent Mail -->
                                            @can('has-permission', 'Email Notification')
                                            <li><a href="mailto:{{$value->email }}?subject=Library Seat Renewal Reminder&body=Hey!%20🌟%0D%0A%0D%0AJust%20a%20friendly%20reminder:%20Your%20library%20seat%20plan%20will%20expire%20in%205%20days!%20📚✨%0D%0A%0D%0ADon%E2%80%99t%20miss%20out%20on%20the%20chance%20to%20keep%20enjoying%20your%20favorite%20books%20and%20resources.%20Plus,%20renewing%20now%20means%20you%20can%20unlock%20exciting%20rewards!%20🎁" target="_blank" data-id="11" onclick="incrementMessageCount({{ $value->id }}, 'email')" class="message" data-bs-toggle="tooltip" data-bs-placement="bottom" title="" data-original-title="Send Email Reminders"><i class="fas fa-envelope"></i></a></li>
                                            @endcan
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
                                    <li><a href="#" data-id="{{$value->id}}" data-learnerDetail="{{$value->learner_detail_id}}" title="Delete Lerners" class="delete-customer"><i class="fas fa-trash"></i></a></li>
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
                @if(isset($learners))
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
                
                @endif
    @endcan





<!-- Modal Popup end for Configration -->

<!-- /.content -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>
    $('.actionIcons').hide();
    $('.action-items').on('click', function() {
        $(this).closest('.record').find('.actionIcons').toggle();
    });

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

<script>
const input = document.getElementById("search-input");
const texts = ["Search by Learner Name", "Search by Learner Mobile Number", "Search by Learner Seat No"];
let currentText = 0;
let charIndex = 0;
let typing = true;

function typeEffect() {
  let current = texts[currentText];
  
  if (typing) {
    input.setAttribute("placeholder", current.substring(0, charIndex++));
    if (charIndex > current.length) {
      typing = false;
      setTimeout(typeEffect, 300);
    } else {
      setTimeout(typeEffect, 100);
    }
  } else {
    charIndex--;
    input.setAttribute("placeholder", current.substring(0, charIndex));
    if (charIndex === 0) {
      typing = true;
      currentText = (currentText + 1) % texts.length;
      setTimeout(typeEffect, 300);
    } else {
      setTimeout(typeEffect, 50);
    }
  }
}

typeEffect();
</script>


@endsection