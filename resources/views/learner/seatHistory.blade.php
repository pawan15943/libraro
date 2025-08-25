@extends('layouts.library')
@section('content')
<!-- Content Header (Page header) -->
<!-- Main row -->


@foreach($seats as $seat)
    @php
        $usersForSeat = App\Models\LearnerDetail::where('seat_no',$seat->seat_no)->where('status',1)->get();
        if ($usersForSeat->isEmpty()) {
            $usersForSeat = App\Models\LearnerDetail::where('seat_no',$seat->seat_no)->where('status',0)->get();
        }
    @endphp

    @if($usersForSeat->count() > 0)
        @foreach($usersForSeat as $user)
            @php
                $learner = myLearner($user->learner_id);
                $planStatus = getPlanStatusDetails($user->plan_end_date);
            @endphp

            <div class="row">
                <div class="col-lg-12">
                    <div class="seat-info bg-white">
                        <div class="seat-no">
                            <span>Seat No. {{ $seat->seat_no }}</span>
                            {!! getUserStatusDetails($user->plan_end_date) !!}
                        </div>

                        <div class="seat-actions">
                            <ul>
                                <li><a href="{{ url('seats/history', $seat->seat_no) }}" title="View Seat History" class="w-auto px-2"><i class="fa-solid fa-clock-rotate-left me-1"></i> View Seat Previous History</a></li>
                            </ul>
                        </div>

                        <div class="seat-informarion">
                            <img src="{{ optional($learner)->profile_picture ? asset($learner->profile_picture) : asset('public/img/student_profile.jpeg') }}" alt="profile">

                            <div class="information">
                                <h4>{{ $learner->name ?? ''}}
                                    <span class="{{ $planStatus['class'] }}">{{ $planStatus['status'] }}</span>
                                </h4>
                                <span>UID: <a href="{{route('learners.show',$learner->id)}}">{{$learner->learner_no}}</a> | M: <a href="tel:+91-{{ $learner->mobile }}">+91-{{ $learner->mobile }}</a></span>
                                <span class="d-block">E: <a href="mailto:{{$learner->email}}"> {!! $learner->email ? $learner->email : '<i class="fa-solid fa-times text-danger"></i> Email ID Not Available' !!} </a></span>
                            </div>
                        </div>

                        <div class="plan-info">
                            <ul>
                                <li><span>Plan</span><p>{{ optional(myPlan($user->plan_id))->name }}</p></li>
                                <li><span>Plan Type</span><p>{{ optional(myPlanType($user->plan_type_id))->name }}</p></li>
                                <li><span>Join On</span><p>{{ $user->join_date }}</p></li>
                                <li><span>Start On</span><p>{{ $user->plan_start_date }}</p></li>
                                <li><span>Ends On</span><p>{{ $user->plan_end_date }}</p></li>
                                <li><span>Payment</span>
                                    @if(isset($user->is_paid) && $user->is_paid == 1)
                                        <p>Paid</p>
                                    @else
                                        <p>Unpaid</p>
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
        @endphp

        <div class="row">
            <div class="col-lg-12">
                <div class="seat-info bg-white">
                    <div class="seat-no">
                        <span>Seat No. GEN</span>
                        {!! getUserStatusDetails($user->plan_end_date) !!}
                    </div>

                    <div class="seat-actions">
                        <ul>
                             <li><a href="{{ route('general.seat.history') }}" title="View General Seat History" class="w-auto px-2"><i class="fa-solid fa-clock-rotate-left me-1"></i> View Seat Previous History</a></li>
                          
                        </ul>
                    </div>

                    <div class="seat-informarion">
                        <img src="{{ $learner->profile_picture ? asset($learner->profile_picture) : asset('public/img/student_profile.jpeg') }}" alt="profile">
                        <div class="information">
                            <h4>{{ $learner->name ?? ''}}{{$learner->email ?? ''}}
                                <span class="{{ $planStatus['class'] }}">{{ $planStatus['status'] }}</span>
                            </h4>
                            <span>UID: <a href="{{route('learners.show',$learner->id)}}">{{$learner->learner_no}}</a> | M: <a href="tel:+91-{{ $learner->mobile }}">+91-{{ $learner->mobile }}</a></span>
                            <span class="d-block">E: <a href="mailto:{{$learner->email}}"> {!! $learner->email ? $learner->email : '<i class="fa-solid fa-times text-danger"></i> Email ID Not Available' !!} </a></span>
                        </div>
                    </div>

                    <div class="plan-info">
                        <ul>
                            <li><span>Plan</span><p>{{ optional(myPlan($user->plan_id))->name }}</p></li>
                            <li><span>Plan Type</span><p>{{ optional(myPlanType($user->plan_type_id))->name }}</p></li>
                            <li><span>Join On</span><p>{{ $user->join_date }}</p></li>
                            <li><span>Start On</span><p>{{ $user->plan_start_date }}</p></li>
                            <li><span>Ends On</span><p>{{ $user->plan_end_date }}</p></li>
                            <li><span>Payment</span>
                                @if(isset($user->is_paid) && $user->is_paid == 1)
                                    <p>Paid</p>
                                @else
                                    <p>Unpaid</p>
                                @endif
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif

<!-- /.row (main row) -->
@endsection