@extends('layouts.library')
@section('content')
@php

$current_route = Route::currentRouteName();
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

@if($current_route=='learners.edit')
<form action="{{ route('learners.update', $customer->id) }}" method="POST" enctype="multipart/form-data">
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
                            <input type="text" class="form-control @error('name') is-invalid @enderror char-only" placeholder="Full Name" name="name" id="name" value="{{ old('name', $customer->name) }}">
                            @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-6 col-6">
                            <label for="">DOB <span>*</span></label>
                            <input type="date" class="form-control @error('dob') is-invalid @enderror" placeholder="DOB" name="dob" id="dob" value="{{ old('dob', $customer->dob) }}">
                            @error('dob')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-6 col-6">
                            <label for="">Mobile Number <span>*</span></label>
                            <input type="text" class="form-control @error('mobile') is-invalid @enderror digit-only" maxlength="10" minlength="10" placeholder="Mobile Number" name="mobile" id="mobile" value="{{ old('mobile', $customer->mobile) }}">
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
                <div class="action-box">
                    <h4 class="mb-4">Actionables
                        <div class="info-container">
                            <i class="fa-solid fa-circle-info info-icon"></i>
                            <div class="info-card">
                                <h3 class="info-title">Edit Learner Info</h3>
                                <p class="info-details">You can change Leraners Personal info Here.</p>
                            </div>
                        </div>
                    </h4>
                    <p class="text-danger">Note : These details are optional. You may fill them in if you wish, or leave them blank.</p>
                    <div class="row g-4">
                        <div class="col-lg-6 col-6">
                            <label for="">Id Proof Received (Optional)</label>
                            <select id="id_proof_name" class="form-control @error('id_proof_name') is-invalid @enderror" name="id_proof_name">
                                <option value="">Select Id Proof</option>
                                <option value="1" {{ old('id_proof_name', $customer->id_proof_name) == 1 ? 'selected' : '' }}>Aadhar</option>
                                <option value="2" {{ old('id_proof_name', $customer->id_proof_name) == 2 ? 'selected' : '' }}>Driving License</option>
                                <option value="3" {{ old('id_proof_name', $customer->id_proof_name) == 3 ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('id_proof_name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-6 col-6">
                            <label for="">Upload Scan Copy of Proof (Optional)</label>
                            <input type="file" class="form-control @error('id_proof_file') is-invalid @enderror" name="id_proof_file" id="id_proof_file" autocomplete="off">
                            @error('id_proof_file')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                            @if($customer->id_proof_file)
                            <a href="{{ asset('storage/' . $customer->id_proof_file) }}" target="_blank">View</a>
                            @endif
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
              
                @if($customer->seat_no)
                <span class="d-block ">Seat No : {{ $customer->seat_no}}</span>
                @endif
                <img src="{{ asset($customer->image) }}" alt="Seat" class="seat py-3 {{$class}}">
                <p>{{ $customer->plan_name}}</p>
                <button class="mb-3"> Booked for <b>{{ $customer->plan_type_name}}</b></button>
                <!-- Expire days Info -->
                {!! getUserStatusWithSpan($customer->plan_end_date) !!}
                
            </div>
           
         </div>
    </div>

</form>

@elseif($current_route=='learners.reactive')

<div class="details">

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
                        <div class="col-lg-6 col-6">
                            <label for="">Seat Owner Name <span>*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror char-only" placeholder="Full Name" id="name" value="{{ old('name', $customer->name) }}" readonly>
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
                <div class="action-box">
                    <h4 class="mb-4">Actionables</h4>
                    <h4>Old Active Plan Info</h4>
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <label for=""> Plan <span>*</span></label>

                            <select class="form-select" name="plan_id" disabled>
                                <option value="{{ $customer->plan_id }}" selected>{{ $customer->plan_name }}</option>
                            </select>

                            @error('plan_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-4">
                            <label for="">Plan Type <span>*</span></label>
                            <select class="form-select" name="plan_id" disabled>
                                <option value="{{ $customer->plan_type_id }}" selected>{{ $customer->plan_type_name }}</option>
                            </select>

                        </div>
                        <div class="col-lg-4">
                            <label for="">Plan Price <span>*</span></label>
                            <input class="form-control" name="plan_price_id" value="{{ $customer->plan_price_id }}" readonly>

                        </div>
                        <div class="col-lg-4">
                            <label for="">Plan Starts On <span>*</span></label>
                            <input type="date" class="form-control" placeholder="Plan Starts On" name="plan_start_date"  value="{{ $customer->plan_start_date }}" readonly>

                        </div>

                        <div class="col-lg-4">
                            <label for="">Plan End On <span>*</span></label>
                            <input type="date" class="form-control" placeholder="Plan Starts On" name="plan_end_date"  value="{{$customer->plan_end_date}}" readonly>

                        </div>
                          @php
                            $hasLocker = currentTransaction($customer->learner_detail_id)->locker_amount > 0 ? 'yes' : 'no';
                            $discountAmount = currentTransaction($customer->learner_detail_id)->discount_amount ?? null;
                            $selectedDiscountType = $discountAmount ? 'amount' : '';
                        @endphp

                        <div class="col-lg-4">
                            <label for="locker">Locker?</label>
                            <select name="locker"  class="form-select" disabled>
                                <option value="no" {{ $hasLocker === 'no' ? 'selected' : '' }}>No</option>
                                <option value="yes" {{ $hasLocker === 'yes' ? 'selected' : '' }}>Yes, I Need a Locker</option>
                            </select>
                        </div>
                          <div class="col-lg-4">
                            <label for="">Locker Amount <span>*</span></label>
                            <input type="text" class="form-control @error('locker_amount') is-invalid @enderror"  name="locker_amount"  value="{{ currentTransaction($customer->learner_detail_id)->locker_amount }}" readonly>
                            @error('locker_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                     
                        <div class="col-lg-4">
                            <label for="discount_amount">Discount Type</label>
                            <select id="discountType" class="form-select" name="discountType" disabled>
                                <option value="">Select Discount Type</option>
                                <option value="amount" {{ $selectedDiscountType == 'amount' ? 'selected' : '' }}>Amount</option>
                                <option value="percentage" {{ $selectedDiscountType == 'percentage' ? 'selected' : '' }}>Percentage</option>
                            </select>
                        </div>
                          <div class="col-lg-4">
                            <label for="discount_amount">Discount Amount ( <span id="typeVal">INR / %</span> )</label>
                            <input type="text" class="form-control @error('discount_amount') is-invalid @enderror"  name="discount_amount"  value="{{ currentTransaction($customer->learner_detail_id)->discount_amount ?? 0 }}" readonly>
                            @error('discount_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                       

                        <div class="col-lg-4">
                            <label for="">Total Amount <span>*</span></label>
                            <input type="text" class="form-control @error('total_amount') is-invalid @enderror"  name="total_amount"  value="{{ currentTransaction($customer->learner_detail_id)->total_amount }}" readonly>
                            @error('total_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                    </div>
                 
                    <form action="{{ route('learner.reactive.store', $customer->id) }}" method="POST" enctype="multipart/form-data" id="reactive">
                        @csrf
                        @method('PUT')
                        <h4 class="mt-4">Activate New Plan</h4>
                        <p class="text-danger">Note : Here you can activate an existing seat learner into other seat.</p>

                        <div class="row g-4">

                            <input id="user_id" type="hidden" name="user_id" value="{{$customer->id }}">
                            <input id="learner_detail" type="hidden" name="learner_detail" value="{{$customer->learner_detail_id }}">

                            <div class="col-lg-4">
                                <label for=""> Plan <span>*</span></label>
                                <select id="plan_id2" class="form-select @error('plan_id') is-invalid @enderror" name="plan_id">
                                    <option value="">Select Plan</option>
                                    @foreach($plans as $key => $value)
                                        <option value="{{ $value->id }}"
                                            {{ old('plan_id', $customer->plan_id) == $value->id ? 'selected' : '' }}>
                                            {{ $value->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('plan_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror

                            </div>
                            <div class="col-lg-4">
                                <label for="">Plan Type <span>*</span></label>
                                <select id="plan_type_id" class="form-select @error('plan_type_id') is-invalid @enderror" name="plan_type_id">
                                    <option value="">Select Plan Type</option>
                                    @foreach($planTypes as $planType)
                                    <option value="{{ $planType->id }}"
                                        {{ old('plan_type_id',$customer->plan_type_id) == $planType->id ? 'selected' : '' }}>
                                        {{ $planType->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('plan_type_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="col-lg-4">
                                <label for="">Plan Price <span>*</span></label>
                                <input id="plan_price_id" class="form-control @error('plan_price_id') is-invalid @enderror" name="plan_price_id" value="{{ old('plan_price_id',$customer->plan_price_id) }}" readonly placeholder="Plan Price">
                                @error('plan_price_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                              @php
                            $hasLocker = currentTransaction($customer->learner_detail_id)->locker_amount > 0 ? 'yes' : 'no';
                            $discountAmount = currentTransaction($customer->learner_detail_id)->discount_amount ?? null;
                            $selectedDiscountType = $discountAmount ? 'amount' : '';
                        @endphp

                        <div class="col-lg-4">
                            <label for="locker">Locker?</label>
                            <select name="locker" id="toggleFieldCheckbox" class="form-select">
                                <option value="no" {{ $hasLocker === 'no' ? 'selected' : '' }}>No</option>
                                <option value="yes" {{ $hasLocker === 'yes' ? 'selected' : '' }}>Yes, I Need a Locker</option>
                            </select>
                        </div>
                          <div class="col-lg-4">
                            <label for="">Locker Amount <span>*</span></label>
                            <input type="text" class="form-control @error('locker_amount') is-invalid @enderror"  name="locker_amount" id="locker_amount" value="{{ currentTransaction($customer->learner_detail_id)->locker_amount }}" readonly>
                            @error('locker_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                     
                        <div class="col-lg-4">
                            <label for="discount_amount">Discount Type</label>
                            <select id="discountType" class="form-select" name="discountType">
                                <option value="">Select Discount Type</option>
                                <option value="amount" {{ $selectedDiscountType == 'amount' ? 'selected' : '' }}>Amount</option>
                                <option value="percentage" {{ $selectedDiscountType == 'percentage' ? 'selected' : '' }}>Percentage</option>
                            </select>
                        </div>
                          <div class="col-lg-4">
                            <label for="discount_amount">Discount Amount ( <span id="typeVal">INR / %</span> )</label>
                            <input type="text" class="form-control @error('discount_amount') is-invalid @enderror"  name="discount_amount" id="discount_amount" value="{{ currentTransaction($customer->learner_detail_id)->discount_amount ?? 0 }}" >
                            @error('discount_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                       

                        <div class="col-lg-4">
                            <label for="">Total Amount <span>*</span></label>
                            <input type="text" class="form-control @error('total_amount') is-invalid @enderror"  name="total_amount" id="new_plan_price" value="{{ currentTransaction($customer->learner_detail_id)->total_amount }}" readonly>
                            @error('total_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                            <div class="col-lg-4">
                                <label for="">Plan Starts On <span>*</span></label>
                                <input type="date" class="form-control @error('plan_start_date') is-invalid @enderror" placeholder="Plan Starts On" name="plan_start_date" id="plan_start_date" value="{{ old('plan_start_date') }}">
                                @error('plan_start_date')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            
                           
                            <div class="col-lg-4">
                                <label for="">Select Seat<span>*</span></label>
                                <select name="seat_no" id="new_seat_id2" class="form-select @error('seat_no') is-invalid @enderror">
                                    <option>Select Seat</option>
                                    <option value="">General</option>
                                    @foreach($available_seat as $id => $seat_no)
                                    <option value="{{ $seat_no }}" {{ $customer->seat_no == $seat_no ? 'selected' : '' }}>{{ $seat_no }}</option>
                                    @endforeach
                                </select>
                                @error('seat_no')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="col-lg-4">
                                <label for="">Payment Mode <span>*</span></label>
                                <select name="payment_mode" id="payment_mode" class="form-select @error('payment_mode') is-invalid @enderror">
                                    <option value="">Select Payment Mode</option>
                                    <option value="1" {{ old('payment_mode') == 1 ? 'selected' : '' }}>Online</option>
                                    <option value="2" {{ old('payment_mode') == 2 ? 'selected' : '' }}>Offline</option>
                                    <option value="3" {{ old('payment_mode') == 3 ? 'selected' : '' }}>Pay Later</option>
                                </select>
                                @error('payment_mode')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                        </div>
                        <div class="row mt-4">
                            <div class="col-lg-3">
                                <input type="submit" class="btn btn-primary btn-block button" id="submit" value="Update">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="seat--info">
              
                @if($customer->seat_no)
                <span class="d-block ">Seat No : {{ $customer->seat_no}}</span>
                @endif
                <img src="{{ asset($customer->image) }}" alt="Seat" class="seat py-3 {{$class}}">
                <p>{{ $customer->plan_name}}</p>
                <button class="mb-3"> Booked for <b>{{ $customer->plan_type_name}}</b></button>
                <!-- Expire days Info -->
                {!! getUserStatusWithSpan($customer->plan_end_date) !!}
                
            </div>
        </div>
    </div>

</div>

@else
<!-- View Customer Information -->
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
                    <div class="col-lg-6 col-6">
                        <span>Seat Owner Name</span>
                        <h5 class="uppercase">{{ $customer->name }}</h5>
                    </div>
                    <div class="col-lg-6 col-6">
                        <span>Date Of Birth </span>
                        <h5>{{ $customer->dob }}</h5>
                    </div>
                    <div class="col-lg-6 col-6">
                        <span>Mobile Number</span>
                        <h5>+91-{{ $customer->mobile }}</h5>
                    </div>
                    <div class="col-lg-6 col-6">
                        <span>Email Id</span>
                        <h5>{{ $customer->email }}</h5>
                    </div>
                </div>
            </div>
            <div class="action-box">
                <h4>Other Seat Info</h4>
                <div class="row g-4">
                    <div class="col-lg-4">
                        <span>Plan</span>
                        <h5>{{ $customer->plan_name }}</h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Plan Type</span>
                        <h5>{{ $customer->plan_type_name }}</h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Plan Price</span>
                        <h5>{{ $customer->plan_price_id }}</h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Seat Booked On</span>
                        <h5>{{ $customer->join_date }}</h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Plan Starts On</span>
                        <h5>{{ $customer->plan_start_date }}</h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Plan Ends On</span>

                        <h5>{{ $customer->plan_end_date }}</h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Payment Mode</span>
                        @if($customer->payment_mode == 1)
                        <h5>{{ 'Online' }}</h5>
                        @elseif($customer->payment_mode == 2)
                        <h5>{{ 'Offline' }}</h5>
                        @else
                        <h5>{{ 'Pay Later' }}</h5>

                        @endif
                    </div>
                    <div class="col-lg-4">
                        <span>Id Proof</span>
                        <h5>
                            @if($customer->id_proof_name==1)
                            Aadhar
                            @elseif($customer->id_proof_name==2)
                            Driving License
                            @else
                            Other
                            @endif
                            @if($customer->id_proof_file)
                            <img src="{{ asset($customer->id_proof_file) }}" width="150" height="150">
                            @else
                            <img src="">

                            @endif
                        </h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Seat Timings</span>
                        <h5>{{$customer->hours}} Hours ({{ $customer->start_time }} to {{ $customer->end_time }})</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="seat--info">
          
            @if($customer->seat_no)
            <span class="d-block ">Seat No : {{ $customer->seat_no}}</span>
            @endif
            <img src="{{ asset($customer->image) }}" alt="Seat" class="seat py-3 {{$class}}">
            <p>{{ $customer->plan_name}}</p>
            <button class="mb-3"> Booked for <b>{{ $customer->plan_type_name}}</b></button>
            <!-- Expire days Info -->
            {!! getUserStatusWithSpan($customer->plan_end_date) !!}
            
        </div>
    </div>
</div>

@endif

@include('learner.script')
@endsection