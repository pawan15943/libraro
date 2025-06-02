@extends('layouts.admin')
@section('content')
<div class="row">

    

    <div class="col-lg-9">
        <div class="actions">
            <div class="upper-box">
                <div class="d-flex">
                    <h4 class="mb-3">Leraners Info</h4>
                    <a href="javascript:void(0);" class="go-back"
                        onclick="window.history.back();">Go
                        Back <i class="fa-solid fa-backward pl-2"></i></a>
                </div>
                <div class="row g-4">
                    <div class="col-lg-6">
                        <span>Name</span>
                        <h5 class="uppercase">{{ Auth::user()->name }}</h5>
                    </div>
                    <div class="col-lg-6">
                        <span>Date Of Birth </span>
                        <h5>{{ Auth::user()->dob }}</h5>
                    </div>
                    <div class="col-lg-6">
                        <span>Mobile Number</span>
                        <h5>+91-{{ (Auth::user()->mobile) }}</h5>
                    </div>
                    <div class="col-lg-6">
                        <span>Email Id</span>
                        <h5>{{ (Auth::user()->email) }}</h5>
                    </div>

                </div>
            </div>
            <div class="action-box">
                <h4>Active Plan Info</h4>
                <div class="row g-4">
                    <div class="col-lg-4">
                        <span>Plan</span>
                        <h5>{{ $learner ? $learner->plan_name : ''}}</h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Plan Type</span>
                        <h5>{{ $learner ? $learner->plan_type_name : '' }}</h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Plan Price</span>
                        <h5>{{ $learner ? $learner->plan_price_id : ''}}</h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Plan Start Date</span>
                        <h5>{{ $learner ? $learner->plan_start_date : ''}}</h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Plan End Date</span>
                        <h5>{{ $learner ?  $learner->plan_end_date : ''}}</h5>
                    </div>

                </div>

            </div>
        </div>
    </div>
    <div class="col-lg-3 order-1 order-md-2">
        <div class="seat--info">


            <span class="d-block ">Seat No : {{ Auth::user()->seat_no}}</span>
            <img src="{{ asset('public/img/available.png') }}" alt="Seat" class="seat py-3 ">
            <p>{{ $learner ? $learner->plan_name :''}}</p>
            <button class="mb-3"> Booked for <b>{{$learner ? $learner->plan_type_name : ''}}</b></button>

        </div>

    </div>
</div>

@include('learner.script')
@endsection