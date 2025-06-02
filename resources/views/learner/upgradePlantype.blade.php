@extends('layouts.library')
@section('content')
@php
$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class=$planDetails['class'];

if (Route::currentRouteName() == 'learner.change.plan') {
$displayNone = 'style="display: none;"';

$readonlyStyle = 'pointer-events: none; background-color: #e9ecef;';

} else {
$displayNone = '';

$readonlyStyle = '';
}
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



<form action="{{ route('learners.update.changePlan', $customer->id) }}" method="POST" enctype="multipart/form-data" id="changePlan">
    @csrf
    @method('PUT')
    <input type="hidden" value="{{$customer->learner_detail_id}}" name="learner_detail_id">
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
                            <label for="">Seat Owner Name <span>*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror char-only" placeholder="Full Name" name="name" id="name" value="{{ old('name', $customer->name) }}" readonly>
                            @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-6">
                            <label for="">DOB <span>*</span></label>
                            <input type="date" class="form-control @error('dob') is-invalid @enderror" placeholder="DOB" name="dob" id="dob" value="{{ old('dob', $customer->dob) }}" readonly>
                            @error('dob')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-6">
                            <label for="">Mobile Number <span>*</span></label>
                            <input type="text" class="form-control @error('mobile') is-invalid @enderror digit-only" maxlength="10" minlength="10" placeholder="Mobile Number" name="mobile" id="mobile" value="{{ old('mobile', $customer->mobile) }}" readonly>
                            @error('mobile')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-6">
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
                    <h4 class="mb-4">Change Plan
                        <div class="info-container">
                            <i class="fa-solid fa-circle-info info-icon"></i>
                            <div class="info-card">
                                <h3 class="info-title">Change Plan</h3>
                                <p class="info-details">The Change Plan option lets you upgrade to a higher plan by checking seat availability, paying the difference, and allocating a new seat. Your remaining time adjusts to the new plan, and the old reservation is closed seamlessly. If the seat not have that paln type available then first need to perform swap seat operation then you do change plan.</p>
                            </div>
                        </div>
                    </h4>
                    <p class="text-danger"><b>important Note:</b> Seat Change Plan are allowed only if the learner's seat is newly booked, and the option is available only within 7 days of starting the current plan.</p>
                    <div class="row g-4">
                        <input id="edit_seat" type="hidden" name="seat_no" value="{{ old('seat_no', $customer->seat_no) }}">
                        <input type="hidden" name="user_id" value="{{ old('user_id', $customer->id) }}">

                        <div class="col-lg-4">
                            <label for=""> Plan <span>*</span></label>
                            <select id="plan_id" class="form-control @error('plan_id') is-invalid @enderror" name="plan_id" style="{{ $readonlyStyle }}">
                                <option value="">Select Plan</option>
                                @foreach($plans as $key => $value)
                                <option value="{{ $value->id }}" {{ old('plan_id', $customer->plan_id) == $value->id ? 'selected' : '' }}>{{ $value->name }}</option>
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
                            <select id="plan_type_id2" class="form-select @error('plan_type_id') is-invalid @enderror" name="plan_type_id">
                                @foreach($filteredPlanTypes as $planType)
                                    <option value="{{ $planType['id'] }}"
                                        {{ ($customer->plan_type_id == $planType['id']) ? 'selected' : (old('plan_type_id') == $planType['id'] ? 'selected' : '') }}>
                                        {{ $planType['name'] }}
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
                            <input id="plan_price_id" class="form-control @error('plan_price_id') is-invalid @enderror"  value="{{ old('plan_price_id', $customer->plan_price_id) }}" readonly name="plan_price_id">
                            @error('plan_price_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                       @php
                            $hasLocker = currentTransaction($customer->learner_detail_id)->locker_amount > 0 ? 'yes' : 'no';
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
                       @if(currentTransaction($customer->learner_detail_id)->discount_amount)
                      
                      
                          <div class="col-lg-4">
                            <label for="">Discount Amount <span>*</span></label>
                            <input type="text" class="form-control @error('discount_amount') is-invalid @enderror"  name="discount_amount" id="discount_amount2" value="{{ currentTransaction($customer->learner_detail_id)->discount_amount ?? 0 }}" readonly>
                            @error('discount_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        @endif
                        <div class="col-lg-4">
                            <label for="">Total Amount <span>*</span></label>
                            <input type="text" class="form-control @error('total_amount') is-invalid @enderror"  name="total_amount" id="total_amount2" value="{{ currentTransaction($customer->learner_detail_id)->total_amount }}" readonly>
                            @error('total_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                       
                        <div class="col-lg-4">
                            <label for="">Diffrence Amount <span>*</span></label>
                            <input type="text" class="form-control @error('diffrence_amount') is-invalid @enderror"  name="diffrence_amount" id="diffrence_amount" value="" readonly placeholder="0.00">
                            @error('diffrence_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                          
                        </div>
                        
                         
                          <div class="col-lg-4 col-6">
                            <label for="">Payment Mode</label>
                            <select name="payment_mode" id="payment_mode" class="form-select @error('payment_mode') is-invalid @enderror">
                                <option value="">Select Payment Mode</option>
                                <option value="1" >Online</option>
                                <option value="2" >Offline</option>
                                <option value="3" >Pay Later</option>
                            </select>
                            @error('payment_mode')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                         <input type="hidden" id="new_plan_price" value="" name="new_plan_price" class="form-control @error('new_plan_price') is-invalid @enderror">
                            @error('new_plan_price')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        {{-- <div class="col-lg-4">
                            <label for="">Plan Starts On <span>*</span></label>
                            <input type="date" class="form-control @error('plan_start_date') is-invalid @enderror" placeholder="Plan Starts On" name="plan_start_date" id="plan_start_date" value="{{ old('plan_start_date', $customer->plan_start_date) }}" readonly disabled>
                            @error('plan_start_date')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label for="">Plan End On <span>*</span></label>
                            <input type="date" class="form-control @error('plan_end_date') is-invalid @enderror" placeholder="Plan Starts On" name="plan_end_date" id="plan_end_date" value="{{ old('plan_end_date', $customer->plan_end_date) }}" readonly>
                            @error('plan_end_date')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div> --}}

                    </div>
                    @php
                        $oneWeekLater = \Carbon\Carbon::parse($customer->plan_start_date)->addWeek();
                        $today = \Carbon\Carbon::now();
                    @endphp
                    <div class="row mt-4">
                        @if(!$today->greaterThanOrEqualTo($oneWeekLater))
                        <div class="col-lg-3">
                            <input type="submit" class="btn btn-primary btn-block button" id="submit" value="Update Seat Info">
                        </div>
                        @endif
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
            
                {!! getUserStatusWithSpan($customer->plan_end_date) !!}
                
            </div>
        </div>
    </div>

</form>

<script>
  // Call the handleFormChanges function for the specific form when the DOM is fully loaded
  document.addEventListener('DOMContentLoaded', function() {
        handleFormChanges('changePlan', {{$customer->id}});
    });
</script>

@include('learner.script')
@endsection