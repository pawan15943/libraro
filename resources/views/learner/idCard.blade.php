@extends('layouts.learner')
@section('content')




<div class="row">
    <div class="col-lg-4">
        <div class="myId">
            <h5>{{$library_name->library_name}} - ID</h5>
            <hr>
            <!-- Photo -->
            <img src="{{url('public/img/user.png')}}" alt="User" class="id-profile">
            <!-- Personal Info -->
            <div class="learner-info mt-4">
                <h4>{{ Auth::user()->name }}</h4>
                <h6>Library Id {{$library_name->library_no}}</h6>
            </div>
            <hr>
            <!-- Plan Info -->
            <div class="learner-plan-info mt-4">
                <p class="m-0">{{$data->plan_type_name}} ({{$data->plan_name}})</p>
                <p class="m-0">Shift [{{ \Carbon\Carbon::parse($data->start_time)->format('h:i A') }} to {{ \Carbon\Carbon::parse($data->end_time)->format('h:i A') }}]</p>
                <p class="m-0">End Date : {{ \Carbon\Carbon::parse($data->plan_end_date)->format('M j, Y') }}</p>
            </div>
            <hr>
            <h4 class="mt-4">Seat No : {{ Auth::user()->seat_no }}</h4>
        </div>
    </div>
</div>


@endsection