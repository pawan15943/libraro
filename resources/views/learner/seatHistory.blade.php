@extends('layouts.library')
@section('content')
<!-- Content Header (Page header) -->
<!-- Main row -->
<div class="row mb-4">
    <!-- Main Info -->
    <div class="col-lg-12">
        <p class="info-message">
            <span class="close-btn" onclick="this.parentElement.style.display='none';">&times;</span>
            <b>Important :</b> The Seat History page displays a comprehensive list of all library seats, along with seat-specific booking details in a single view. If you need information about library seats, this section provides helpful details to guide you.
        </p>
        <div class="table-responsive">
            <table class="table text-center datatable">
                <thead>
                    <tr>
                        <th style="width: 10%">Seat No.</th>
                        <th style="width: 20%">Seat Owner Name</th>
                        <th style="width: 20%">Contact Info</th>
                        <th style="width: 10%">Plan Info</th>
                        <th style="width: 10%">Join On</th>
                        <th style="width: 10%">Start On</th>
                        <th style="width: 10%">Ends On</th>
                        <th style="width: 15%">Action</th>
                    </tr>
                </thead>

               

                @if(count($seats) == 0 && $finalGeneralLearners->count()==0)
                      <tbody>
                    <tr>
                        <td colspan="8" class="text-center">No data available</td>
                    </tr>
                </tbody>
                @else
                
                <tbody>

                    @foreach($seats as $seat)
                        @php
                        // First, check if there are any customers with status 1 for the given seat
                        $usersForSeat = App\Models\LearnerDetail::where('seat_no',$seat->seat_no)->where('status',1)->get();
                        // If no learners with status 1 are found, check for learners with status 0
                        if ($usersForSeat->isEmpty()) {
                            $usersForSeat = App\Models\LearnerDetail::where('seat_no',$seat->seat_no)->where('status',0)->limit(1)->get();
                
                        }
                        
                        @endphp
    
                        @if($usersForSeat->count() > 0)

                        <tr>
                            <td rowspan="{{ $usersForSeat->count() }}">{{ $seat->seat_no }}</td>
                            @foreach($usersForSeat as $user)
                            @php
                            $learner=myLearner($user->learner_id);
                            @endphp
                        @if (!$loop->first)
                        <tr>
                        @endif
                            <td><span class="uppercase truncate name mt-0 mb-0" data-bs-toggle="tooltip"
                                    data-bs-title="{{$learner->name}}" data-bs-placement="bottom">{{$learner->name}}</span></td>
                            <td><span class="truncate" >
                                {!! $learner->email ? $learner->email : '<i class="fa-solid fa-times text-danger"></i> Email ID Not Available' !!} 
                                </span> <br>
                                <small> +91-{{($learner->mobile)}}</small>
                            </td>
                            <td>
                                {{ optional(myPlanType($user->plan_type_id))->name }}<br>
                                <small>{{ optional(myPlan($user->plan_id))->name }}</small>
                            </td>

                            <td>{{ $user->join_date }}
                                @if(isset($user->is_paid) && $user->is_paid==1)
                                <small class="fs-10 d-block ">Paid</small>
                                @else
                                <small class="fs-10 d-block ">Unpaid</small>
                                @endif
                            </td>
                            <td>{{ $user->plan_start_date }}</td>
                            <td>{{ $user->plan_end_date }}<br>
                                {!! getUserStatusDetails($user->plan_end_date) !!}

                            </td>
                            @if ($loop->first)
                            <td rowspan="{{ $usersForSeat->count()  }}">

                                <ul class="actionalbls">
                                    <li>
                                        <a href="{{ url('seats/history', $seat->seat_no) }}" title="View Seat Previous Booking " class="disabled"><i class="fa-solid fa-clock-rotate-left"></i></a>
                                    </li>
                                </ul>

                            </td>
                            @endif
                            @if (!$loop->first)
                        </tr>
                        @endif
                        @endforeach
                        </tr>

                        @endif

                    @endforeach
                    @if($finalGeneralLearners->count())
                    <tr>
                        <td rowspan="{{ $finalGeneralLearners->count() }}">General</td>
                        @foreach($finalGeneralLearners as $user)
                            @php $learner = myLearner($user->learner_id); @endphp
                            @if(!$loop->first)<tr>@endif
                            <td>{{ $learner->name }}</td>
                            <td><span class="truncate" >
                                {!! $learner->email ? $learner->email : '<i class="fa-solid fa-times text-danger"></i> Email ID Not Available' !!} 
                                </span> <br>
                                <small> +91-{{($learner->mobile)}}</small>
                            </td>
                              <td>
                                {{ optional(myPlanType($user->plan_type_id))->name }}<br>
                                <small>{{ optional(myPlan($user->plan_id))->name }}</small>
                            </td>
                            <td>{{ $user->join_date }}
                                 @if(isset($user->is_paid) && $user->is_paid==1)
                                <small class="fs-10 d-block ">Paid</small>
                                @else
                                <small class="fs-10 d-block ">Unpaid</small>
                                @endif
                            </td>
                            <td>{{ $user->plan_start_date }}</td>
                            <td>{{ $user->plan_end_date }}<br>
                                 {!! getUserStatusDetails($user->plan_end_date) !!}
                            </td>
                            @if($loop->first)
                                <td rowspan="{{ $finalGeneralLearners->count() }}">
                                      <ul class="actionalbls">
                                    <li>
                                        <a href="{{route('general.seat.history')}}" title="View General Seat History" class="disabled"><i class="fa-solid fa-clock-rotate-left"></i></a>
                                    </li>
                                </ul>
                                </td>
                            @endif
                            @if(!$loop->first)</tr>@endif
                        @endforeach
                    </tr>
                    @endif


                </tbody>
                @endif
            </table>

        </div>
    </div>
</div>

@include('learner.script')
<!-- /.row (main row) -->
@endsection