@extends('layouts.library')
@section('content')

@php
$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class=$planDetails['class'];

if (Route::currentRouteName() == 'learner.renew.plan') {
$displayNone = 'style="display: none;"';

$readonlyStyle = 'pointer-events: none; background-color: #e9ecef;';
$ids='renewSeat';

} else {
$displayNone = '';

$readonlyStyle = '';
$ids='learnerUpgrade';
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

            <form action="{{route('learner.upgrade.renew.store')}}" method="POST" enctype="multipart/form-data" id="{{$ids}}" class="payment_page">
                @csrf
                @method('POST')
                <div class="action-box">

                    <h4 class="mb-4 d-block">
                        @if(Route::currentRouteName() == 'learner.renew.plan')
                              Renew Plan
                        @else
                              Upgrade Plan
                        @endif
                      
                        <p class="mt-2 text-danger"><b>Note:</b> Any learner can upgrade their plan only renewing seat in their extend period. If the seat not have that paln type available then first need to perform swap seat operation then you do change plan.</p>


                    </h4>
                    <input id="learner_detail" type="hidden" name="learner_detail" value="{{$customer->learner_detail_id }}">
                    <input id="user_id" type="hidden" name="learner_id" value="{{ $customer->id}}">
                    <input id="user_id" type="hidden" name="user_id" value="{{ $customer->id}}">
                    <input id="library_id" type="hidden" name="library_id" value="{{ $customer->library_id}}">
                    <div class="row g-4">
                       <div class="col-lg-4">
                            <label for=""> Plan <span>*</span></label>
                            <select id="plan_id" class="form-control @error('plan_id') is-invalid @enderror" name="plan_id" {{ Route::currentRouteName() == 'learner.renew.plan' ? 'disabled' : '' }}>
                                <option value="">Select Plan</option>
                                @foreach($plans as $key => $value)
                                <option value="{{ $value->id }}" {{ old('plan_id', $customer->plan_id) == $value->id ? 'selected' : '' }}>{{ $value->name }}</option>
                                @endforeach
                            </select>
                              @if(Route::currentRouteName() == 'learner.renew.plan')
                                <input type="hidden" name="plan_id" value="{{ old('plan_id', $customer->plan_id) }}">
                            @endif
                            @error('plan_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                  
                        <div class="col-lg-4">
                            <label for="">Plan Type <span>*</span></label>
                            <select id="plan_type_id2" class="form-select @error('plan_type_id') is-invalid @enderror" name="plan_type_id"  {{ Route::currentRouteName() == 'learner.renew.plan' ? 'disabled' : '' }}>
                                @foreach($filteredPlanTypes as $planType)
                                    <option value="{{ $planType['id'] }}"
                                        {{ ($customer->plan_type_id == $planType['id']) ? 'selected' : (old('plan_type_id') == $planType['id'] ? 'selected' : '') }}>
                                        {{ $planType['name'] }}
                                    </option>
                                @endforeach
                            </select>

                            @if(Route::currentRouteName() == 'learner.renew.plan')
                                <input type="hidden" name="plan_type_id" value="{{ old('plan_type_id', $customer->plan_type_id) }}">
                            @endif
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
                        <div class="col-lg-4 col-6">
                            <label for="">Payment Mode<span>*</span></label>

                            <select name="payment_mode" id="payment_mode" class="form-select @error('payment_mode') is-invalid @enderror">
                                <option value="">Select Payment Mode</option>
                                <option value="1" {{ $customer->payment_mode == 1 ? 'selected' : '' }}>Online</option>
                                <option value="2" {{ $customer->payment_mode == 2 ? 'selected' : '' }}>Offline</option>
                                <option value="3" {{ $customer->payment_mode == 3 ? 'selected' : '' }}>Pay Later</option>
                            </select>
                            @error('payment_mode')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror


                        </div>

                    </div>
               
                    <div class="row mt-3">
                        <div class="col-lg-3">
                            @if($planDetails['diff_in_days'] <=5 && $planDetails['diff_extend_day'] > 0 && !$is_renew && !$isalreadyRenew)

                                <input type="submit" class="btn btn-primary btn-block button" value="Renew Upgrade">

                            @endif
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
            <img src="{{ asset($customer->planType->image) }}" alt="Seat" class="seat py-3 {{$class}}">
            <p>{{ $customer->plan->name}}</p>
            <button>Booked for <b>{{ $customer->planType->name}}</b></button>
            {!! getUserStatusWithSpan($customer->plan_end_date) !!}
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const formId = "{{ $ids }}"; // Dynamically injected form ID
        const formElement = document.getElementById(formId);
      
        if ( formId=='learnerUpgrade') {
            handleFormChanges('learnerUpgrade', {{ $customer->id }});
        }else if(formId=='renewSeat'){
            handleFormChanges('renewSeat', {{ $customer->id }});
        }
    });
</script>



@include('learner.script')

@endsection