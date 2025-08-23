@extends('layouts.library')
@section('content')
@php
use Carbon\Carbon;
$current_route = Route::currentRouteName();
@endphp

<!-- Bootstrap Toggle CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">



<div class="row mb-4 ">
    <div class="col-lg-12">
        <div class="filter-box">
            <form action="{{ route('get.learner.attendance') }}" method="GET">

                <!-- Filter By Plan -->
                <div class="row g-4">
                    <div class="col-lg-4">
                        <input type="date" class="form-control" name="date" value="{{ request('date') ?: date('Y-m-d') }}" id="date">
                    </div>


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

<div class="row mb-4">
    <div class="col-lg-12">
        <h4 class="mb-4">Daily Attendence Summery</h4>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="attendence-box">
                    <h4>150</h4>
                    <span>Total Student</span>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="attendence-box">
                    <h4>25 </h4>
                    <span>Present Student</span>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="attendence-box">
                    <h4>25 </h4>
                    <span>Absent Student</span>
                </div>
            </div>
        </div>
        <div class="row py-4">
            <div class="col-lg-12">
                <div class="text-danger pb-3"><b>Note :</b> If you don't provide an out time, then learner's closing shift time will be used as the out time.</div>
            </div>
        </div>
        <div class="row g-4 mb-4">
            @foreach($learners as $key => $value)
            <div class="col-lg-12">
                <div class="revenue-info">
                    <ul>
                        <li style="width: 8%;">
                            <div class="icon">
                                <img src="" alt="">
                            </div>
                        </li>
                        <li>
                            <span>Seat No</span>
                            <p>{{$value->seat_no ?? 'G'}}</p>
                        </li>
                        <li>
                            <span>Name</span>
                            <p>{{$value->name}}</p>
                        </li>
                        <li>
                            <span>Plan End Date</span>
                            <p>25 Aug, 2025</p>
                        </li>
                        <li>
                            <span>Punch In</span>
                            <p>{{ $value->in_time ? \Carbon\Carbon::parse($value->in_time)->format('h:i A') : '-' }}</p>
                        </li>
                        <li>
                            <span>Punch Out</span>
                            <p>{{ $value->out_time ? \Carbon\Carbon::parse($value->out_time)->format('h:i A') : '-' }}</p>
                        </li>
                        <li>
                            <span>Punch Out</span>
                            <p>@if($value->attendance==1)
                                Present
                                @elseif($value->attendance==0)
                                Absent
                                @else
                                No Attendance
                                @endif</p>
                        </li>

                    </ul>
                </div>
            </div>
            @endforeach
        </div>
        
        
    </div>
</div>

@endsection