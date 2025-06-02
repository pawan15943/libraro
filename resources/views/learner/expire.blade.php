@extends('layouts.library')
@section('content')
@php
$current_route = Route::currentRouteName();
use Carbon\Carbon;
$today = Carbon::today();
$endDate = Carbon::parse($customer->plan_end_date);
$diffInDays = $today->diffInDays($endDate, false);

@endphp
<form  action="{{ route('learner.expire.update', $customer->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input id="edit_seat" type="hidden" name="seat_no" value="{{ old('seat_no', $customer->seat_no) }}">
    <input name="user_id" type="hidden" value="{{$customer->id}}">
    <input name="plan_id" type="hidden" value="{{$customer->plan_id}}">
    <input name="plan_type_id" type="hidden" value="{{$customer->plan_type_id}}">
    <input name="plan_price_id" type="hidden" value="{{$customer->plan_price_id}}">
    <input name="plan_start_date" type="hidden" value="{{$customer->plan_start_date}}">
    <div class="row g-4">
        <div class="col-lg-9 order-2 order-md-1">
            <div class="actions">
                <div class="upper-box">
                    <div class="d-flex">
                    <h4 class="mb-3">Leraners Info</h4>
                    <a href="javascript:void(0);" class="go-back"
                    onclick="window.history.back();">Go
                    Back <i class="fa-solid fa-backward pl-2"></i></a>
                    </div>
                    <div class="row g-4">
                        <div class="col-lg-6 col-6">
                            <label for="">Seat Owner Name <span>*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror char-only" placeholder="Full Name" name="name" id="name" value="{{ old('name', $customer->name) }}" >
                            @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-6 col-6">
                            <label for="">DOB <span>*</span></label>
                            <input type="date" class="form-control @error('dob') is-invalid @enderror" placeholder="DOB" name="dob" id="dob" value="{{ old('dob', $customer->dob) }}" >
                            @error('dob')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-6 col-6">
                            <label for="">Mobile Number <span>*</span></label>
                            <input type="text" class="form-control @error('mobile') is-invalid @enderror digit-only" maxlength="10" minlength="10" placeholder="Mobile Number" name="mobile" id="mobile" value="{{ old('mobile', $customer->mobile) }}" >
                            @error('mobile')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-6 col-6">
                            <label for="">Email Id <span>*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" placeholder="Email Id" name="email" id="email" value="{{ old('email', $customer->email) }}" >
                            @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="action-box">
                    <h4 class="mb-4">Actionables</h4>
                    <div class="row g-4">
                            <div class="col-lg-6 col-6">
                                <label for="">Start Date <span>*</span></label>
                                <input type="date" class="form-control " name="plan_start_date" value="{{ old('plan_start_date', $customer->plan_start_date) }}" >
                                @error('plan_start_date')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="col-lg-6 col-6">
                                <label for="">End Date <span>*</span></label>
                                <input type="date" class="form-control " name="plan_end_date" value="{{ old('plan_end_date', $customer->plan_end_date) }}" >
                                @error('plan_end_date')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-lg-3">
                            <input type="submit" class="btn btn-primary btn-block button" id="submit" value="Update Seat Info" autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 order-1 order-md-2">
            <div class="seat--info">
                <span class="d-block">Seat No : {{ $customer->seat_no}}</span>
                <img src="{{ asset($customer->image) }}" alt="Seat" class="seat py-3">
                <p>{{ $customer->plan_name}}</p>
                <button>Booked for <b>{{ $customer->plan_type_name}}</b></button>
                <span class="text-success">Plan Expires in {{$diffInDays}} Days</span>
            </div>
        </div>
    </div>

</form>
@endsection