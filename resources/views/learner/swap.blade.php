@extends('layouts.library')
@section('content')
@php
    $planDetails = getPlanStatusDetails($customer->plan_end_date);
    $class=$planDetails['class'];
@endphp

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
               
<input id="plan_type_id" type="hidden" name="plan_type_id" value="{{$customer->plan_type_id }}">

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
                        <input type="text" class="form-control @error('name') is-invalid @enderror char-only" placeholder="Full Name" name="name" id="name" value="{{ old('name', $customer->name) }}" readonly>
                        @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-lg-6 col-6">
                        <label for="">DOB <span>*</span></label>
                        <input type="date" class="form-control @error('dob') is-invalid @enderror" placeholder="DOB" name="dob" id="dob" value="{{ old('dob', $customer->dob) }}" readonly>
                        @error('dob')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-lg-6 col-6">
                        <label for="">Mobile Number <span>*</span></label>
                        <input type="text" class="form-control @error('mobile') is-invalid @enderror digit-only" maxlength="10" minlength="10" placeholder="Mobile Number" name="mobile" id="mobile" value="{{ old('mobile', $customer->mobile) }}" readonly>
                        @error('mobile')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-lg-6 col-6">
                        <label for="">Email Id <span>*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" placeholder="Email Id" name="email" id="email" value="{{ old('email', $customer->email) }}" readonly>
                        @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>
            </div>
            <form action="{{ route('learners.swap-seat', $customer->id) }}" method="POST" enctype="multipart/form-data" id="swapseat">
                @csrf
                @method('PUT')
                <div class="action-box">
                    <h4 class="mb-4">Actionables
                        <div class="info-container">
                            <i class="fa-solid fa-circle-info info-icon"></i>
                            <div class="info-card">
                                <h3 class="info-title">Swap Seat</h3>
                                <p class="info-details">Learners can request to change their current
                                    seat to another available seat. If the requested seat is
                                    available, the learner’s current seat will be swapped with the
                                    new one.</p>
                            </div>
                        </div>
                    </h4>
                    <p class="text-danger font-weight-bold">Note : You can swap your seat with any other seat that has the same plan available for booking.</p>
                    <input id="user_id" type="hidden" name="learner_id" value="{{ $customer->id}}">
                    <div class="row">
                        <div class="col-lg-6 col-6">
                            <label for="">Current Seat No. <span>*</span></label>
                            <input  class="form-control"   value="{{ $customer->seat_no }} - {{ $customer->plan_type_name }}" readonly>
                        
                        </div>
                        <div class="col-lg-6 col-6">
                            <label for="">Select Seat<span>*</span></label>
                            <select name="seat_id" id="new_seat_id" class="form-control form-select @error('seat_id') is-invalid @enderror">
                                <option>Select Seat</option>
                                @foreach($availableseats as  $seat_no)
                                <option value="{{ $seat_no }}"> {{ $seat_no }}</option>
                                @endforeach
                              
                            </select>
                            @error('seat_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <input type="hidden" value="{{ $customer->seat_no }}" id="swap_old_value">

                    </div>
                    <div class="row mt-3">
                        <div class="col-lg-6 col-6">
                        <span>Current Seat Status</span>
                        <h4 id="swap_status"></h4>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-lg-3">
                            <input type="submit" class="btn btn-primary btn-block button" id="swapsubmit" value="Swap Seat">
                        </div>
                    </div>
                    
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-3 order-1 order-md-2">
        <div class="seat--info">
              
            @if($customer->seat_no)
            <span class="d-block ">Seat No : {{ $customer->seat_no}}</span>
            @endif
            <img src="{{ asset($customer->image) }}" alt="Seat" class="seat py-3 {{$class}}">
            <p>{{ $customer->plan_name}}</p>
            <button class="mb-3"> Booked for <b>{{ $customer->plan_type_name}}</b></button>
            {!! getUserStatusWithSpan($customer->plan_end_date) !!}
            
        </div>
    </div>
</div>

  
<script>
    document.getElementById("swapsubmit").disabled = true;
     // Call the handleFormChanges function for the specific form when the DOM is fully loaded
  document.addEventListener('DOMContentLoaded', function() {
        handleFormChanges('swapseat', {{$customer->id}});
    });
   
</script>
@include('learner.script')

@endsection