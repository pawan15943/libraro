@extends('layouts.library')
@section('content')
<!-- Content Header (Page header) -->
<!-- Main row -->

@if ($finalGeneralLearners->count() == 0 &&
    collect($seats)->sum(fn($seat) => $seat->learners->count()) == 0)
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
    <h4>No Learner Added Yet</h4>
    <span> You haven’t added any learners to your library yet. Start adding learners by clicking the button below.</span>
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
<p class="info-message">
    <span class="close-btn" onclick="this.parentElement.style.display='none';">×</span>
    <b>Important :</b> The Seat History page displays a comprehensive list of all library seats, along with seat-specific booking details in a single view. If you need information about library seats, this section provides helpful details to guide you.
</p>

@foreach($seats as $seat)
    @if($seat->learners->count() > 0)
        @foreach($seat->learners as $user)
            @php
                $learner = optional($user); // Already joined, can access fields directly
                $planStatus = getPlanStatusDetails($user->plan_end_date);
                $operation = optional(getLearnerOperation($user->learner_detail_id))->operation;
                $learner_id=$learner->id;
                $learner_detail_id=$user->learner_detail_id;
                $transaction = learnerTransaction($learner_id, $learner_detail_id);      

                if ($transaction && isset($transaction->pending_amount) && $transaction->due_date) {
            
                $due_date = $transaction->due_date;
                } else {
                
                $due_date = null;
                }
                $operationDate=optional(getLearnerOperation($learner_detail_id))->created_at;
            @endphp

            <div class="row">
                <div class="col-lg-12">
                    <div class="seat-info bg-white">
                        <div class="seat-no">
                            
                            <span>Seat No.: {{ getSeatDisplayByMainNo($seat->seat_no) }}</span>
                        
                            @if($operation == 'closeSeat')
                            <span class="extended"> Closed Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}</span>
                            @elseif($operation == 'deleteSeat' && $user->deleted_at !=null)
                            <span class="extended"> Deleted Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}</span>
                            @else
                            {!! getUserStatusWithSpan($user->plan_end_date,$learner_id) !!}
                            @endif
                        </div>

                        <div class="seat-actions">
                            <ul>
                                <li><a href="{{ url('seats/history', $seat->seat_no) }}" class="w-auto px-2"><i class="fa-solid fa-clock-rotate-left me-1"></i> View Seat Previous History</a></li>
                            </ul>
                        </div>

                        <div class="seat-informarion">
                           <img src="{{ $learner?->profile_picture  ? asset($learner->profile_picture) : asset('public/img/student_profile.jpeg') }}" alt="profile">

                            <div class="information">
                                <h4>{{ $learner->name ?? '' }} <span class="
                                {{ $planStatus['class'] == 'expired' ? 'expired' : ($planStatus['class'] == 'extended' ? 'extedned' : 'actives') }}">{{ $planStatus['status'] ?? '' }}</span></h4>
                                <span>UID: <a href="{{ route('learners.show', $user->learner_id) }}">{{ $learner->learner_no ?? '' }}</a> | M: <a href="tel:+91-{{ $learner->mobile ?? '' }}">+91-{{ $learner->mobile ?? '' }}</a></span>
                                 <span class="d-block">E: <a href="mailto:{{$learner->email}}"> {!! $learner->email ? $learner->email : '<i class="fa-solid fa-times text-danger"></i> Email ID Not Available' !!} </a></span>
           
                            </div>
                        </div>

                        <div class="plan-info">
                            <ul>
                                <li><span>Plan</span><p>{{ optional(myPlan($user->plan_id))->name }}</p></li>
                                <li><span>Plan Type</span><p>{{ optional(myPlanType($user->plan_type_id))->name }}</p></li>
                                <li><span>Join On</span><p>{{ $user->join_date ?? '' }}</p></li>
                                <li><span>Start On</span><p>{{ $user->plan_start_date ?? '' }}</p></li>
                                <li><span>Ends On</span><p>{{ $user->plan_end_date ?? '' }}</p></li>
                                <li><span class=" d-block">Payment</span>

                                
                                @if(paylater($learner_detail_id) && learnerTransaction($learner_id,$learner_detail_id)->pending_amount!=0)
                                <a href="{{ route('learner.pending.payment', ['id' => $transaction->id]) }}" class="text-danger d-block">
                                    <span class="extended" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?">
                                        PayLater {{ rtrim(rtrim(number_format(   (learnerTransaction($learner_id, $learner_detail_id))->pending_amount, 2, '.', ''), '0'), '.') }}

                                    </span>
                                </a>
                                @elseif(!empty(learnerTransaction($learner_id,$learner_detail_id)->pending_amount) && learnerTransaction($learner_id,$learner_detail_id)->pending_amount==0)
                                <span class="payment" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?">Fully Paid</span>

                                <form action="{{ route('fee.generateReceipt') }}" method="POST" class="d-inline" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="learner_id" value="{{$learner_id}}">
                                    <input type="hidden" name="id" value="{{ learnerTransaction($learner_id,$learner_detail_id)->id ?? 'NA'}}">
                                    <input type="hidden" name="type" value="learner">
                                    <button type="submit">
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
                                </li>
                        
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        @endforeach
    @endif
@endforeach

@if($finalGeneralLearners->count())
@foreach($finalGeneralLearners as $user)
@php
$learner = myLearner($user->learner_id);
$planStatus = getPlanStatusDetails($user->plan_end_date);
 $operation = optional(getLearnerOperation($user->learner_detail_id))->operation;
$learner_id = optional($learner)->id;
 $learner_detail_id=$user->learner_detail_id;
$transaction = learnerTransaction($learner_id, $learner_detail_id);      

if ($transaction && isset($transaction->pending_amount) && $transaction->due_date) {

$due_date = $transaction->due_date;
} else {

$due_date = null;
}

@endphp

<div class="row">
    <div class="col-lg-12">
        <div class="seat-info bg-white">
            <div class="seat-no">
                <span>Seat No.: GEN</span>
                @if($operation == 'closeSeat')
                    <span class="extended"> Closed Seat on {{ $user->plan_end_date ? date('j M Y', strtotime($user->plan_end_date)) : '' }}</span>
                @elseif($operation == 'deleteSeat' && $user->deleted_at !=null)
                    <span class="extended"> Delete Seat on {{ $user->plan_end_date ? date('j M Y', strtotime($user->plan_end_date)) : '' }}</span>
                @else
                    {!! getUserStatusWithSpan($user->plan_end_date,$learner_id) !!}
                @endif
            </div>

            <div class="seat-actions">
                <ul>
                    <li><a href="{{ route('general.seat.history') }}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="View General Seat History" class="w-auto px-2"><i class="fa-solid fa-clock-rotate-left me-1"></i> View Seat Previous History</a></li>
                </ul>
            </div>

            <div class="seat-informarion">
                <img src="{{ $learner?->profile_picture  ? asset($learner->profile_picture) : asset('public/img/student_profile.jpeg') }}" alt="profile">
                <div class="information">
                    <h4>{{ $learner->name ?? ''}}
                         @if($operation == 'closeSeat')
                        <span class="extended">Closed</span>
                        @elseif($operation == 'deleteSeat' && $user->deleted_at !=null)
                        <span class="extended">Deleted</span>
                         @else
                        <span class="{{$planStatus['class']}} ps-1">{{$planStatus['status']}}</span>
                        @endif
                    </h4>
                    <span>UID: <a href="{{route('learners.show',$learner_id)}}">{{$user->learner_no ?? ''}}</a> | M: <a href="tel:+91-{{ $user->mobile }}">+91-{{ $user->mobile }}</a></span>
                   <span class="d-block">E: <a href="mailto:{{$learner->email}}"> {!! $learner->email ? $learner->email : '<i class="fa-solid fa-times text-danger"></i> Email ID Not Available' !!} </a></span>
   
                </div>
            </div>

            <div class="plan-info">
                <ul>
                    <li><span>Plan</span>
                        <p>{{ optional(myPlan($user->plan_id))->name }}</p>
                    </li>
                    <li><span>Plan Type</span>
                        <p>{{ optional(myPlanType($user->plan_type_id))->name }}</p>
                    </li>
                    <li><span>Join On</span>
                        <p>{{ $user->join_date ?? '' }}</p>
                    </li>
                    <li><span>Start On</span>
                        <p>{{ $user->plan_start_date ?? '' }}</p>
                    </li>
                    <li><span>Ends On</span>
                        <p>{{ $user->plan_end_date ?? '' }}</p>
                    </li>
                   <li><span class=" d-block">Payment</span>    
                    @if(paylater($learner_detail_id) && learnerTransaction($learner_id,$learner_detail_id)->pending_amount!=0)
                    <a href="{{ route('learner.pending.payment', ['id' => $transaction->id]) }}" class="text-danger d-block">
                        <span class="extended" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?">
                            PayLater {{ rtrim(rtrim(number_format(   (learnerTransaction($learner_id, $learner_detail_id))->pending_amount, 2, '.', ''), '0'), '.') }}

                        </span>
                    </a>
                    @elseif(!empty(learnerTransaction($learner_id,$learner_detail_id)->pending_amount) && learnerTransaction($learner_id,$learner_detail_id)->pending_amount==0)
                    <span class="payment" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?">Fully Paid</span>

                    <form action="{{ route('fee.generateReceipt') }}" method="POST" class="d-inline" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="learner_id" value="{{$learner_id}}">
                        <input type="hidden" name="id" value="{{ learnerTransaction($learner_id,$learner_detail_id)->id ?? 'NA'}}">
                        <input type="hidden" name="type" value="learner">
                        <button type="submit">
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
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endforeach
@endif
@endif

<!-- /.row (main row) -->
@endsection