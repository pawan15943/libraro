@extends('layouts.library')
@section('content')
<!-- Content Header (Page header) -->
@can('has-permission', 'Search Learner')
    <div class="row justify-content-center mt-5 mb-4 find-a-learner">
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
                <p class="text-center mt-2 text-secondary">Here you see all the learners who are active or expired.</p>
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
    <div class="row ">
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
                        @if(paylater($value->learner_detail_id) || pending_amt($value->learner_detail_id))
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
                                <!-- Sent Mail -->

                                @can('has-permission', 'WhatsApp Notification')
                                <li><a href="https://web.whatsapp.com/send?phone=91{{$value->mobile}}&text=Hey!%20🌟%0A%0AJust%20a%20friendly%20reminder:%20Your%20library%20seat%20plan%20will%20expire%20in%205%20days!%20📚✨%0A%0ADon%E2%80%99t%20miss%20out%20on%20the%20chance%20to%20keep%20enjoying%20your%20favorite%20books%20and%20resources.%20Plus,%20renewing%20now%20means%20you%20can%20unlock%20exciting%20rewards!%20🎁" target="_blank" data-id="11" onclick="incrementMessageCount({{ $value->id }}, 'whatsapp')" class="whatsapp w-auto px-2" data-bs-toggle="tooltip" data-bs-placement="bottom" data-original-title="Send WhatsApp Reminder"><i class="fa-brands fa-whatsapp pe-1"></i> Send Reminder</a></li>

                                @endcan
                                <!-- Sent Mail -->
                                {{-- @can('has-permission', 'Email Notification')
                                <li><a href="mailto:{{$value->email }}?subject=Library Seat Renewal Reminder&body=Hey!%20🌟%0D%0A%0D%0AJust%20a%20friendly%20reminder:%20Your%20library%20seat%20plan%20will%20expire%20in%205%20days!%20📚✨%0D%0A%0D%0ADon%E2%80%99t%20miss%20out%20on%20the%20chance%20to%20keep%20enjoying%20your%20favorite%20books%20and%20resources.%20Plus,%20renewing%20now%20means%20you%20can%20unlock%20exciting%20rewards!%20🎁" target="_blank" data-id="11" onclick="incrementMessageCount({{ $value->id }}, 'email')" class="message" data-bs-toggle="tooltip" data-bs-placement="bottom" title="" data-original-title="Send Email Reminders"><i class="fas fa-envelope"></i></a></li>
                                @endcan --}}
                                @endif
                                <!-- Swap Seat-->

                                @can('has-permission', 'Swap Seat')

                                <li><a href="{{route('learners.swap',$value->id)}}" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Swap Seat"><i class="fa-solid fa-arrow-right-arrow-left"></i></a></li>

                                @endcan


                                @can('has-permission', 'Change Plan')

                                @if(!in_array('14', toggleHideField()) && !$today->greaterThanOrEqualTo($oneWeekLater))
                                <li><a href="{{route('learner.change.plan',$value->id)}}" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Change Plan"><i class="fa fa-arrow-up-short-wide"></i></a></li>
                                @endif
                                @endcan
                                <!---ID Card generate-->
                                {{-- @if(!in_array('15', toggleHideField()))
                        <li data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Genrate ID Card">
                        <form action="{{ route('generateIdCard') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" id="custId" name="detail_id" value="{{ $value->learner_detail_id }}">
                                <input type="hidden" name="learner_id" value="{{ $value->id }}">
                                <button type="submit"><i class="fa-solid fa-id-card-clip"></i></button>
                                </form>
                                </li>
                                @endif --}}
                                @if(!in_array('15', toggleHideField()))
                                <li><a target="_blank" href="{{ route('idCard',  $value->learner_detail_id) }}" class="" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Genrate ID Card"><i class="fa-solid fa-id-card-clip"></i> </a></li>
                                @endif
                                <!-- upgrade Seat-->
                                @if($planStatus['diff_in_days'] <= 5 && $planStatus['diff_extend_day']> 0 && $planStatus['diff_extend_day'] <= 5)
                                        @can('has-permission', 'Upgrade Seat Plan' )
                                        @if(!in_array('13', toggleHideField()))
                                        <li><a href="{{route('learners.upgrade',$value->id)}}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Upgrade Plan"><i class="fa-solid fa-circle-up"></i></a></li>
                                        @endif
                                        @endcan

                                        @endif
                                        <!-- Close Seat -->

                                        @can('has-permission', 'Close Seat')
                                        @if(!in_array('16', toggleHideField()))
                                        <li><a href="javascript:void(0);" class="link-close-plan" data-id="{{$value->id}}" data-learnerDetail="{{ $value->learner_detail_id }}" data-learner_detail_id="{{$value->learner_detail_id}}" data-payblerefund="{{ paybleRefund($value->learner_detail_id) }}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Close Plan" data-plan_end_date="{{$value->plan_end_date}}"><i class="fas fa-times"></i></a></li>

                                        @endif
                                        @endcan
                                        @endif

                                        @can('has-permission', 'Reactive Seat')
                                        @if($value->status==0)
                                        <li><a href="{{route('learners.reactive',$value->id)}}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Reactivate Learner"><i class="fa-solid fa-arrows-rotate"></i></a></li>
                                        @endif
                                        @endcan

                                        <li><a href="{{route('learner.other.payment',$value->learner_detail_id)}}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Other Payment" class="payment-learner"><i class="fa-solid fa-money-bill"></i></a></li>
                                        <!-- View Seat Info -->
                                        @can('has-permission', 'View Seat')
                                        <li><a href="{{route('learners.show',$value->id)}}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="View Seat Booking Full Details"><i class="fas fa-eye"></i></a></li>
                                        @endcan

                                        <!-- Deletr Seat -->

                                        @can('has-permission', 'Edit Seat')
                                        @if(!in_array('17', toggleHideField()))
                                        <li><a href="{{route('learners.edit',$value->id)}}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Edit Seat Booking Details"><i class="fas fa-edit"></i></a></li>
                                        @endif
                                        @endcan

                                        @can('has-permission', 'Delete Seat')
                                        <li><a href="#" data-id="{{$value->id}}" data-learnerDetail="{{ $value->learner_detail_id }}" data-payblerefund="{{ paybleRefund($value->learner_detail_id) }}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Delete Lerners" class="delete-customer"><i class="fas fa-trash"></i></a></li>
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
                            <span class="{{ $planStatus['class'] == 'expired' ? 'expired' : ($planStatus['class'] == 'extended' ? 'extedned' : 'actives') }} ps-1">{{$planStatus['status']}}</span>
                            @endif
                        </h4>
                        <span>UID : <a href="{{route('learners.show',$value->id)}}">{{$value->learner_no}}</a> &nbsp; | &nbsp; M : <a href="tel:+91-{{$value->mobile}}">+91-{{$value->mobile}}</a> </span>
                        <span class="d-block">E: <a href="mailto:{{$value->email}}"> {!! $value->email ? $value->email : '<i class="fa-solid fa-times text-danger"></i> Email ID Not Available' !!} </a></span>
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

                                <form action="{{ route('fee.generateReceipt') }}" method="POST" enctype="multipart/form-data">
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