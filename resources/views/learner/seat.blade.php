@extends('layouts.library')
@section('content')

@php
use App\Models\Learner;
use App\Models\LearnerDetail;
use Carbon\Carbon;
$fullDayCount = 0;
$halfDayFirstHalfCount = 0;
$halfDaySecondHalfCount = 0;
$hourlyCount = 0;
$today = Carbon::today();

@endphp

<style>
    .seat {
        opacity: 0;
        transform: translateY(50px);
        transition: transform 0.5s ease, opacity 0.5s ease;
    }
</style>
@if(getCurrentBranch() !=0 )

<div class="row mb-4">
    <div class="col-lg-12">
        <p class="info-message mt-4 mb-0">
            <span class="close-btn" onclick="this.parentElement.style.display='none';">&times;</span>
            <b>Monthly Seat Activity:</b> Explore an overview of your library seat bookings across the current and previous months. This dashboard tracks each seat's booking, expiration, and renewal status, updating monthly as seats are renewed on varying dates. Stay up-to-date with your seating activity in one convenient place.
        </p>
    </div>
    <div class="col-lg-12 text-end mt-4">
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


    @if(!in_array('24', toggleHideField()))
    <div class="col-lg-12" id="countsContainer">
        <div class="records">
            <p class="mb-2 text-dark"><b>Total Seats : {{$total_seats ?? 0}} | Available Seats : {{$availble_seats ?? 0}} | Booked Seats: {{$booked_seats ?? 0}} | General Seats: {{$genral_seat ?? 0}}</b></p>
            <span class="text-success">Total Available Slots ({{$availble_seats ?? 0}})</span> <span class="text-success">Total Booked Slots ({{$active_seat_count ?? 0}})</span><span class="text-success">Total General Booked Slots ({{$genral_seat}})</span> <span class="text-danger">Total Expired Slots({{$expired_seat ?? 0}})</span> <span class="text-danger">Extended Slots({{$extended_seats ?? 0}})</span>
            @foreach($planTypeCounts as $plan)
            <span class="text-danger">{{ $plan['abbr'] }}: {{ $plan['name'] }} ({{ $plan['count'] }})</span>
            @endforeach
        </div>
    </div>
    @endif
</div>

<div class="row mb-4">
    <div class="col-lg-12">
        <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pills-home-tab" data-bs-toggle="pill" data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home" aria-selected="true">Library Seats</button>
            </li>
            @can('has-permission', 'General Seat Booking')
            @if(!in_array('12', toggleHideField()))
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pills-profile-tab" data-bs-toggle="pill" data-bs-target="#pills-profile" type="button" role="tab" aria-controls="pills-profile" aria-selected="false">General Seats</button>
            </li>
            @endif
            @endcan
        </ul>
        <div class="tab-content" id="pills-tabContent">
            <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab" tabindex="0">

                <div class="col-lg-12 mt-0">
                    

                        @if(isset($total_seats) && $total_seats != 0)
                            @php
                                $seatNo = 1; 
                            @endphp

                            @foreach($floors as $floor)
                                @php
                                    $startSeat = $floor->from_seat ?? 1;
                                    $endSeat   = $floor->to_seat ?? 0;
                                    
                                @endphp

                                <h4 class="uppercase py-3">{{ $floor->name }}</h4>
                                <div class="seat-booking">

                                @for($seatNo2 = $startSeat; $seatNo2 <= $endSeat && $seatNo <= $total_seats; $seatNo2++)
                                    <div class="seat">
                                        @php
                                            $usersForSeat =Learner::leftJoin('learner_detail','learner_detail.learner_id','=','learners.id')->leftJoin('plan_types','learner_detail.plan_type_id','=','plan_types.id')->where('learners.branch_id',getCurrentBranch())->where('learners.seat_no', $seatNo)->select('learners.id','learners.seat_no','learner_detail.plan_type_id','plan_types.day_type_id','plan_types.image','learner_detail.plan_end_date','learner_detail.id as learner_detail_id','plan_types.name as plan_type_name')->where('learners.status',1)->where('learner_detail.status',1)->get();
                                            \App\Support\LearnerShiftSupport::applyBulkShiftLabelsToLearnerRows($usersForSeat);
                                            $sumofhourseat = LearnerDetail::where('seat_no', $seatNo)
                                            ->where('status',1)
                                            ->whereDate('plan_start_date', '<=', $today)
                                                // ->whereDate('plan_end_date', '>=', $today)
                                                ->where('branch_id',getCurrentBranch())
                                                ->sum('hour');
                                                $remainingHours = $total_hour - $sumofhourseat;

                                                $seatCount = 0;
                                                $halfday = 1;
                                                $hourly = 1;
                                                $x=1;
                                        @endphp

                                        @if($usersForSeat->count() > 0)
                                            @php
                                            $halfDayBookings = $usersForSeat->where('day_type_id', 2)->count() + $usersForSeat->where('day_type_id', 3)->count();
                                            $hourlyBookings = $usersForSeat->whereIn('day_type_id', [4, 5, 6, 7])->count();
                                            $halldaybooking=$usersForSeat->where('day_type_id', 8)->count();
                                            $nightbooking=$usersForSeat->where('day_type_id', 9)->count();
                                            $fulldaybooking=$usersForSeat->where('day_type_id', 1)->count();
                                            $reservedbooking=$usersForSeat->where('day_type_id', 10)->count();
                                            $vipbooking=$usersForSeat->where('day_type_id', 11)->count();
                                            $custombooking=$usersForSeat->where('day_type_id', 0)->count();
                                            if($remainingHours==0 || $remainingHours<0){
                                                $seatCount=0;
                                                }
                                                elseif ($halfDayBookings==1 && $hourlyBookings==1) {
                                                $seatCount=1;
                                                }elseif($remainingHours !=0 && $hourlyBookings>0){
                                                $seatCount = 4-$hourlyBookings;
                                                }elseif($remainingHours != 0 && $halfDayBookings>0){
                                                $seatCount = 1;
                                                }elseif($halldaybooking==1){
                                                $seatCount = 0;
                                                }elseif($nightbooking==1 && $remainingHours != 0){
                                                $seatCount = 1;
                                                }elseif($fulldaybooking==1 && $remainingHours != 0){
                                                $seatCount = 1;
                                                }elseif($reservedbooking==1 && $remainingHours != 0){
                                                $seatCount = 1;
                                                }elseif($vipbooking==1 && $remainingHours != 0){
                                                $seatCount = 1;
                                                }elseif( $custombooking >=1 && $remainingHours !=0){
                                                $seatCount = 1;
                                                }
                                                $extendDay = getExtendDays();

                                            @endphp
                                            <ul>
                                                @foreach($usersForSeat as $user)
                                                @php
                                                $planDetails = getPlanStatusDetails($user->plan_end_date);
                                                $pending_amt=pending_amt($user->learner_detail_id);


                                                if(overdue($user->id, $pending_amt)){
                                                    $class='orange_class';
                                                }elseif(paylater($user->learner_detail_id)){
                                                    $class='paylater_class';
                                                }else{
                                                    $class=$planDetails['class'];
                                                }

                                                @endphp

                                                @if($user->day_type_id == 1 || $user->day_type_id==10 || $user->day_type_id==11)
                                                <li><a href="javascript:;" data-bs-toggle="modal" class="second_popup " data-seat_no="{{ $seatNo }}" 
                                                        data-bs-target="#seatAllotmentModal2" data-userid="{{ $user->id }}"><i
                                                            class="fa-solid fa-check-circle booked {{$class}}"></i></a></li>

                                                @elseif($user->day_type_id == 2)

                                                <li><a href="javascript:;" data-bs-toggle="modal" class="second_popup " data-seat_no="{{ $seatNo }}"
                                                        data-bs-target="#seatAllotmentModal2" data-userid="{{ $user->id }}"><i
                                                            class="fa-solid fa-check-circle booked {{$class}}"></i></a></li>


                                                @elseif($user->day_type_id == 3)
                                                <li><a href="javascript:;" data-bs-toggle="modal" class="second_popup " data-seat_no="{{ $seatNo }}"
                                                        data-bs-target="#seatAllotmentModal2" data-userid="{{ $user->id }}"><i
                                                            class="fa-solid fa-check-circle booked {{$class}}"></i></a></li>

                                                @elseif(in_array($user->day_type_id, [4, 5, 6, 7]))
                                                <li><a href="javascript:;" data-bs-toggle="modal" class="second_popup " data-seat_no="{{ $seatNo }}"
                                                        data-bs-target="#seatAllotmentModal2" data-userid="{{ $user->id }}"><i
                                                            class="fa-solid fa-check-circle booked {{$class}}"></i></a></li>
                                                @elseif(in_array($user->day_type_id, [8, 9,0]))
                                                <li><a href="javascript:;" data-bs-toggle="modal" class="second_popup " data-seat_no="{{ $seatNo }}"
                                                        data-bs-target="#seatAllotmentModal2" data-userid="{{ $user->id }}"><i
                                                            class="fa-solid fa-check-circle booked {{$class}}"></i></a></li>
                                                @endif


                                                @endforeach

                                                @for ($i = 0; $i < $seatCount; $i++)

                                                    <li><a href="javascript:;" data-bs-toggle="modal" class="first_popup"
                                                        data-bs-target="#seatAllotmentModal" data-id="{{ $seatNo }}" data-seat_no="{{ $seatNo }}"><i
                                                            class="fa-solid fa-check-circle available"></i></a></li>

                                                @endfor
                                            </ul>

                                            @foreach($usersForSeat as $user)
                                            @php
                                            $planDetails = getPlanStatusDetails($user->plan_end_date);
                                            $class=$planDetails['class'];
                                            $pending_amt=pending_amt($user->learner_detail_id);


                                            if(overdue($user->id, $pending_amt)){
                                            $class='orange_class';
                                            }elseif(paylater($user->learner_detail_id)){
                                            $class='paylater_class';
                                            }else{
                                            $class=$planDetails['class'];
                                            }
                                            $dayTypes = [1 => 'FD', 2 => 'FH', 3 => 'SH', 4 => 'H1', 5 => 'H2', 6 => 'H3', 7 => 'H4', 8 => 'AD', 9 => 'FN',10=>'RESERVED',11=>'VIP'];
                                            @endphp

                                            <small class="text-dark d-inline {{ $class }}">
                                                @if($user->day_type_id == 0)
                                                {{ strtoupper(substr($user->plan_type_name, 0, 2)) }}
                                                @elseif(isset($dayTypes[$user->day_type_id]))
                                                {{ $dayTypes[$user->day_type_id] }}
                                                @endif
                                            </small>
                                            @endforeach

                                            <img src="{{ asset('public/img/booked.png') }}" class="booked {{$class}}" alt="book">
                                            <small class="text-dark">Seat No.{{ $startSeat }}</small>

                                            @else
                                            <ul>

                                                <li><a href="javascript:;" data-bs-toggle="modal" class="first_popup"
                                                        data-bs-target="#seatAllotmentModal" data-id="{{ $seatNo }}" data-seat_no="{{ $seatNo }}"><i
                                                            class="fa-solid fa-check-circle available "></i></a></li>
                                            </ul>
                                            <small class="text-dark">Available </small>
                                            <img src="{{ asset('public/img/available.png') }}" alt="book">
                                            <small class="text-dark">Seat No. {{ $startSeat }}</small>


                                        @endif
                                    </div>
                                    @php
                                        $seatNo++;
                                        $startSeat++;
                                    @endphp
                                @endfor

                                </div>
                            @endforeach

                            {{-- Show remaining seats if total_seats > last assigned seat --}}
                            @if($seatNo <= $total_seats)
                                @if(isset($floors) && count($floors) > 0)
                                <h4 class="uppercase py-3">Seats Without Floor</h4>
                                @endif
                                <div class="seat-booking">
                                @for($seatNo2 = $seatNo; $seatNo2 <= $total_seats; $seatNo2++)
                                    @php
                                        $seatNo = $seatNo2;
                                    @endphp
                                    <div class="seat">
                                        @php
                                        $usersForSeat =Learner::leftJoin('learner_detail','learner_detail.learner_id','=','learners.id')->leftJoin('plan_types','learner_detail.plan_type_id','=','plan_types.id')->where('learners.branch_id',getCurrentBranch())->where('learners.seat_no', $seatNo)->select('learners.id','learners.seat_no','learner_detail.plan_type_id','plan_types.day_type_id','plan_types.image','learner_detail.plan_end_date','learner_detail.id as learner_detail_id','plan_types.name as plan_type_name')->where('learners.status',1)->where('learner_detail.status',1)->get();
                                        \App\Support\LearnerShiftSupport::applyBulkShiftLabelsToLearnerRows($usersForSeat);
                                        $sumofhourseat = LearnerDetail::where('seat_no', $seatNo)
                                        ->where('status',1)
                                        ->whereDate('plan_start_date', '<=', $today)
                                            // ->whereDate('plan_end_date', '>=', $today)
                                            ->where('branch_id',getCurrentBranch())
                                            ->sum('hour');
                                            $remainingHours = $total_hour - $sumofhourseat;

                                            $seatCount = 0;
                                            $halfday = 1;
                                            $hourly = 1;
                                            $x=1;
                                        @endphp

                                        @if($usersForSeat->count() > 0)
                                            @php
                                            $halfDayBookings = $usersForSeat->where('day_type_id', 2)->count() + $usersForSeat->where('day_type_id', 3)->count();
                                            $hourlyBookings = $usersForSeat->whereIn('day_type_id', [4, 5, 6, 7])->count();
                                            $halldaybooking=$usersForSeat->where('day_type_id', 8)->count();
                                            $nightbooking=$usersForSeat->where('day_type_id', 9)->count();
                                            $fulldaybooking=$usersForSeat->where('day_type_id', 1)->count();
                                            $custombooking=$usersForSeat->where('day_type_id', 0)->count();
                                            $reservedbooking=$usersForSeat->where('day_type_id', 10)->count();
                                            $vipbooking=$usersForSeat->where('day_type_id', 11)->count();
                                            if($remainingHours==0 || $remainingHours<0){
                                                $seatCount=0;
                                                }
                                                elseif ($halfDayBookings==1 && $hourlyBookings==1) {
                                                $seatCount=1;
                                                }elseif($remainingHours !=0 && $hourlyBookings>0){
                                                $seatCount = 4-$hourlyBookings;
                                                }elseif($remainingHours != 0 && $halfDayBookings>0){
                                                $seatCount = 1;
                                                }elseif($halldaybooking==1){
                                                $seatCount = 0;
                                                }elseif($nightbooking==1 && $remainingHours != 0){
                                                $seatCount = 1;
                                                }elseif($fulldaybooking==1 && $remainingHours != 0){
                                                $seatCount = 1;
                                                }elseif($reservedbooking==1 && $remainingHours != 0){
                                                $seatCount = 1;
                                                }elseif($vipbooking==1 && $remainingHours != 0){
                                                $seatCount = 1;
                                                }elseif( $custombooking >=1 && $remainingHours !=0){
                                                $seatCount = 1;
                                                }
                                                $extendDay = getExtendDays();

                                            @endphp
                                            <ul>
                                                @foreach($usersForSeat as $user)
                                                @php
                                                $planDetails = getPlanStatusDetails($user->plan_end_date);
                                                $pending_amt=pending_amt($user->learner_detail_id);


                                                if(overdue($user->id, $pending_amt)){
                                                $class='orange_class';
                                                }elseif(paylater($user->learner_detail_id)){
                                                $class='paylater_class';
                                                }else{
                                                $class=$planDetails['class'];
                                                }

                                                @endphp

                                                @if($user->day_type_id == 1 || $user->day_type_id == 10 || $user->day_type_id == 11)
                                                <li><a href="javascript:;" data-bs-toggle="modal" class="second_popup " data-seat_no="{{ $seatNo }}"
                                                        data-bs-target="#seatAllotmentModal2" data-userid="{{ $user->id }}"><i
                                                            class="fa-solid fa-check-circle booked {{$class}}"></i></a></li>

                                                @elseif($user->day_type_id == 2)

                                                <li><a href="javascript:;" data-bs-toggle="modal" class="second_popup " data-seat_no="{{ $seatNo }}"
                                                        data-bs-target="#seatAllotmentModal2" data-userid="{{ $user->id }}"><i
                                                            class="fa-solid fa-check-circle booked {{$class}}"></i></a></li>


                                                @elseif($user->day_type_id == 3)
                                                <li><a href="javascript:;" data-bs-toggle="modal" class="second_popup " data-seat_no="{{ $seatNo }}"
                                                        data-bs-target="#seatAllotmentModal2" data-userid="{{ $user->id }}"><i
                                                            class="fa-solid fa-check-circle booked {{$class}}"></i></a></li>

                                                @elseif(in_array($user->day_type_id, [4, 5, 6, 7]))
                                                <li><a href="javascript:;" data-bs-toggle="modal" class="second_popup " data-seat_no="{{ $seatNo }}"
                                                        data-bs-target="#seatAllotmentModal2" data-userid="{{ $user->id }}"><i
                                                            class="fa-solid fa-check-circle booked {{$class}}"></i></a></li>
                                                @elseif(in_array($user->day_type_id, [8, 9,0]))
                                                <li><a href="javascript:;" data-bs-toggle="modal" class="second_popup " data-seat_no="{{ $seatNo }}"
                                                        data-bs-target="#seatAllotmentModal2" data-userid="{{ $user->id }}"><i
                                                            class="fa-solid fa-check-circle booked {{$class}}"></i></a></li>
                                                @endif


                                                @endforeach

                                                @for ($i = 0; $i < $seatCount; $i++)

                                                    <li><a href="javascript:;" data-bs-toggle="modal" class="first_popup"
                                                        data-bs-target="#seatAllotmentModal" data-id="{{ $seatNo }}" data-seat_no="{{ $seatNo }}"><i
                                                            class="fa-solid fa-check-circle available"></i></a></li>

                                                @endfor
                                            </ul>

                                            @foreach($usersForSeat as $user)
                                            @php
                                            $planDetails = getPlanStatusDetails($user->plan_end_date);
                                            $class=$planDetails['class'];
                                            $pending_amt=pending_amt($user->learner_detail_id);


                                            if(overdue($user->id, $pending_amt)){
                                            $class='orange_class';
                                            }elseif(paylater($user->learner_detail_id)){
                                            $class='paylater_class';
                                            }else{
                                            $class=$planDetails['class'];
                                            }
                                            $dayTypes = [1 => 'FD', 2 => 'FH', 3 => 'SH', 4 => 'H1', 5 => 'H2', 6 => 'H3', 7 => 'H4', 8 => 'AD', 9 => 'FN',10=>'RESERVED',11=>'VIP'];
                                            @endphp

                                            <small class="text-dark d-inline {{ $class }}">
                                                @if($user->day_type_id == 0)
                                                {{ strtoupper(substr($user->plan_type_name, 0, 2)) }}
                                                @elseif(isset($dayTypes[$user->day_type_id]))
                                                {{ $dayTypes[$user->day_type_id] }}
                                                @endif
                                            </small>
                                            @endforeach

                                            <img src="{{ asset('public/img/booked.png') }}" class="booked {{$class}}" alt="book">
                                            <small class="text-dark">Seat No.{{ $seatNo }}</small>

                                            @else
                                            <ul>

                                                <li><a href="javascript:;" data-bs-toggle="modal" class="first_popup"
                                                        data-bs-target="#seatAllotmentModal" data-id="{{ $seatNo }}" data-seat_no="{{ $seatNo }}"><i
                                                            class="fa-solid fa-check-circle available "></i></a></li>
                                            </ul>
                                            <small class="text-dark">Available </small>
                                            <img src="{{ asset('public/img/available.png') }}" alt="book">
                                            <small class="text-dark">Seat No. {{ $seatNo }}</small>


                                        @endif
                                    </div>
                                @endfor
                                </div>
                            @endif
                        @endif

                </div>
            </div>

            <div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab" tabindex="0">
                <div class="seat-booking">

                    @if(countWithoutSeatNo() >0)
                    @php
                    $usersForSeat =Learner::leftJoin('learner_detail','learner_detail.learner_id','=','learners.id')->leftJoin('plan_types','learner_detail.plan_type_id','=','plan_types.id')->where('learners.branch_id',getCurrentBranch())->whereNull('learners.seat_no')->whereNull('learner_detail.seat_no')->select('learners.id','learner_detail.plan_type_id','plan_types.day_type_id','plan_types.image','learner_detail.plan_end_date','learner_detail.id as learner_detail_id','plan_types.name as plan_type_name')->where('learners.status',1)->where('learner_detail.status',1)->get();
                    \App\Support\LearnerShiftSupport::applyBulkShiftLabelsToLearnerRows($usersForSeat);

                    @endphp
                    @foreach($usersForSeat as $user)

                    <div class="seat">

                        @php
                        $planDetails = getPlanStatusDetails($user->plan_end_date);
                        $class=$planDetails['class'];
                        $dayTypes = [1 => 'FD', 2 => 'FH', 3 => 'SH', 4 => 'H1', 5 => 'H2', 6 => 'H3', 7 => 'H4', 8 => 'AD', 9 => 'FN',10=>'RESERVED',11=>'VIP'];
                        @endphp

                        <ul>
                            <li>
                                <a href="javascript:;" data-bs-toggle="modal" class="second_popup_without_seat" data-bs-target="#seatAllotmentModal2" data-userid="{{ $user->id }}"><i class="fa-solid fa-check-circle booked {{$class}}"></i></a>
                            </li>
                        </ul>
                        <small class="text-dark d-inline {{ $class }}">
                            @if($user->day_type_id == 0)
                            {{ strtoupper(substr($user->plan_type_name, 0, 2)) }}
                            @elseif(isset($dayTypes[$user->day_type_id]))
                            {{ $dayTypes[$user->day_type_id] }}
                            @endif
                        </small>

                        <img src="{{ asset('public/img/booked.png') }}" class="booked {{$class}}" alt="book">

                        <small class="text-dark">General</small>
                    </div>
                    @endforeach
                    @else
                    <h4 class="mb-4">No any General Seat Booked</h4>

                    @endif


                </div>
            </div>
        </div>

    </div>
</div>
@else
<p class="info-message mt-4 mb-0">
    you dont select any branch
</p>
@endif
@can('has-permission', 'View Seat')
<div class="modal fade" id="seatAllotmentModal2" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="seat_details_info">Book Seat</h1>
                <span id="seat_name" style="display: none;"></span>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="actions">
                            <div class="upper-box">
                                <h4 class="mb-4">Leraners Info</h4>
                                <div class="row g-4">
                                    <div class="col-lg-6 col-6">
                                        <span>Seat Owner Name</span>
                                        <h5 id="owner" class="uppercase">NA</h5>
                                    </div>
                                    @if(!in_array('2', toggleHideField()))
                                    <div class="col-lg-6 col-6">
                                        <span>Date Of Birth </span>
                                        <h5 id="learner_dob">NA</h5>
                                    </div>
                                    @endif

                                    <div class="col-lg-6 col-6">
                                        <span>Mobile Number</span>
                                        <h5 id="learner_mobile">NA</h5>
                                    </div>
                                    @if(!in_array('1', toggleHideField()))
                                    <div class="col-lg-6 col-6">
                                        <span>Email Id</span>
                                        <h5 id="learner_email">NA</h5>
                                    </div>
                                    @endif

                                    
                                </div>
                            </div>
                            <div class="action-box">
                                <h4>Other Seat Info</h4>
                                <div class="row g-4">
                                    <div class="col-lg-4 col-6">
                                        <span>Plan</span>
                                        <h5 id="planName">NA</h5>
                                    </div>
                                    <div class="col-lg-4 col-6">
                                        <span>Plan Type</span>
                                        <h5 id="planTypeName">NA</h5>
                                    </div>
                                    <div class="col-lg-4 col-6">
                                        <span>Plan Price</span>
                                        <h5 id="price">NA</h5>
                                    </div>
                                    <div class="col-lg-4 col-6">
                                        <span>Seat Booked On</span>
                                        <h5 id="joinOn">NA</h5>
                                    </div>
                                    <div class="col-lg-4 col-6">
                                        <span>Plan Starts On</span>
                                        <h5 id="startOn">NA</h5>
                                    </div>
                                    <div class="col-lg-4 col-6">
                                        <span>Plan Ends On</span>
                                        <h5 id="endOn">NA</h5>
                                    </div>
                                    <div class="col-lg-4 col-6">
                                        <span>Payment Mode</span>
                                        <h5 id="paymentmode">NA</h5>
                                    </div>
                                    <div class="col-lg-4 col-6">
                                        <span>Id Proof</span>
                                        <h5 id="proof"><a class="">View Docuemnt</a></h5>
                                    </div>
                                    <div class="col-lg-4">
                                        <span>Seat Timings</span>
                                        <h5 id="planTiming">NA</h5>
                                    </div>
                                    <div>
                                        <h5 id="extendday" class="text-center"></h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @can('has-permission', 'Renew Seat')
                        <div class="row justify-content-center">
                            <div class="col-lg-6">
                                <input type="hidden" value="" id="user_id">
                                <input type="hidden" value="" id="learner_detail_id">

                                <a id="upgrade" class="btn btn-primary btn-block mt-2 button" style="height : auto;">Renew Library Membership</a>
                            </div>
                        </div>
                        @else
                        <span class="text-danger text-center d-block mt-2">You don't have Permission to Renew Student.</span>
                        @endcan
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endcan



<script>
    $(document).ready(function() {
        // Check if the animation has already been run in the current session
        if (!sessionStorage.getItem('seatsAnimated')) {
            // Animate each seat one by one
            $('.seat').each(function(index) {
                $(this).delay(index * 200).queue(function(next) {
                    $(this).css({
                        'opacity': '1',
                        'transform': 'translateY(0)'
                    });
                    next(); // Move to the next item in the queue
                });
            });

            // After all animations complete, set the sessionStorage flag
            setTimeout(function() {
                sessionStorage.setItem('seatsAnimated', 'true');
            }, $('.seat').length * 200 + 500); // Wait for all seats to animate
        } else {
            // If the animation has already run, make all seats visible immediately
            $('.seat').css({
                'opacity': '1',
                'transform': 'translateY(0)',
                'transition': 'none' // Disable the transition so they don't animate again
            });
        }
    });
</script>

{{-- <script>
    document.getElementById('plan_start_date').addEventListener('change', function() {
    
        const startDate = new Date(this.value);
        console.log("stat_date",startDate);
        if (startDate) {
            // Add 30 days to the start date
            const endDate = new Date(startDate);
            endDate.setDate(endDate.getDate() + 30);

            // Format the date to yyyy-mm-dd for the input field
            const formattedDate = endDate.toISOString().split('T')[0];

            // Set the calculated end date in the input field
            document.getElementById('plan_end_date').value = formattedDate;
        }
    });
</script> --}}


@endsection