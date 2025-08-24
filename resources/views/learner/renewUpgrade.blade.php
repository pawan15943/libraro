@extends('layouts.library')
@section('content')

@php
$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class=$planDetails['class'];

if (Route::currentRouteName() == 'learner.renew.plan') {
$displayNone = 'style="display: none;"';

$readonlyStyle = 'pointer-events: none; background-color: #e9ecef;';


} else {
$displayNone = '';

$readonlyStyle = '';

}
if($customer->locker_no){
    $locker_read='';
}else{
    $locker_read='readonly';
}


if(Route::currentRouteName() == 'learner.renew.plan'){
    $paymentType='RENEW';
    $route=route('learner.upgrade.renew.store');
    $ids='renewSeat';
}elseif(Route::currentRouteName() == 'learner.change.plan'){
    $paymentType='CHANGE PLAN';
    $route=route('learners.update.changePlan', $customer->id);
    $ids='changePlan';
}else{
    $paymentType='UPGRADE';
    $route=route('learner.upgrade.renew.store');
    $ids='learnerUpgrade';
}

@endphp


<div class="row g-4">
    <div class="col-lg-9 order-2 order-md-1">

        <div class="library-operations mt-4">

            <div class="info__section">
                <h4 class="inner-heading">Learner Info</h4>
                <ul>
                    <li>
                        <span>Learner UID</span>
                        <h4>{{ $customer->learner_no }}</h4>
                    </li>
                    <li>
                        <span>Full Name</span>
                        <h4>{{ $customer->name }}</h4>
                    </li>
                    <li>
                        <span>DOB</span>
                        <h4>{{ $customer->dob ? \Carbon\Carbon::parse($customer->dob)->format('d F, Y') : 'DOB Not Available' }}</h4>
                    </li>
                    <li>
                        <span>Mobile</span>
                        <h4>+91-{{ $customer->mobile }}</h4>
                    </li>
                    <li>
                        <span>Email</span>
                        <h4><a href="mailto:{{$customer->email}}" class="text-white"> {!! $customer->email ? $customer->email : 'Email ID Not Available' !!} </a></h4>
                    </li>
                </ul>
            </div>

            <div class="form-input mb-4">
                <h4 class="inner-heading">
                    @if(Route::currentRouteName() == 'learner.renew.plan')
                    Renew Plan
                    @elseif(Route::currentRouteName() == 'learner.change.plan')
                    Change Plan
                    @else
                    Upgrade Plan
                    @endif
                </h4>
                <div class="tip text-danger">
                    <b>Note:</b> Any learner can upgrade their plan only renewing seat in their extend period.
                    If the seat not have that plan type available then first need to perform swap seat operation then you do change plan.
                </div>

                <form action="{{$route}}" method="POST" enctype="multipart/form-data" id="{{$ids}}">
                    @csrf
                    @method('POST')

                    <input type="hidden" name="learner_detail" value="{{$customer->learner_detail_id }}">
                    <input type="hidden" name="learner_id" value="{{ $customer->id}}" >
                    <input type="hidden" name="user_id" value="{{ $customer->id}}" id="user_id">
                    <input type="hidden" name="library_id" value="{{ $customer->library_id}}">  
                    <input type="hidden" name="payment_type" value="{{ $paymentType}}">
                   
                    @php
                    
                    $hasLocker = currentTransaction($customer->learner_detail_id)->locker_amount > 0 ? 'yes' : 'no';
                    $discountAmount = currentTransaction($customer->learner_detail_id)->discount_amount ?? null;
                    $selectedDiscountType = $discountAmount ? 'amount' : '';
                    $oneWeekLater = \Carbon\Carbon::parse($customer->plan_start_date)->addWeek();
                    $today = \Carbon\Carbon::now();
                    if($hasLocker){
                        $locker_amt=currentTransaction($customer->learner_detail_id)->locker_amount;
                    }else{
                        $locker_amt=0;
                    }
                  
                    @endphp
                    <h4 class="mt-4 mb-3">Current Plan Info</h4>

                    <div class="row g-4">
                        <div class="col-lg-4">
                            <label>Plan <span>*</span></label>
                            <select id="plan_id10" class="form-control form-select @error('plan_id') is-invalid @enderror" name="plan_id" {{ (Route::currentRouteName() == 'learner.change.plan' || Route::currentRouteName() == 'learner.renew.plan') ? 'readonly' : '' }}>
                                <option value="">Select Plan</option>
                                @foreach($plans as $key => $value)
                                <option value="{{ $value->id }}" {{ old('plan_id', $customer->plan_id) == $value->id ? 'selected' : '' }}>{{ $value->name }}</option>
                                @endforeach
                            </select>
                           
                            @error('plan_id')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label>Plan Type <span>*</span></label>
                            <select id="plan_type_id10" class="form-control form-select  @error('plan_type_id') is-invalid @enderror" name="plan_type_id" {{ Route::currentRouteName() == 'learner.renew.plan' ? 'readonly' : '' }}>
                                @foreach($filteredPlanTypes as $planType)
                                <option value="{{ $planType['id'] }}"
                                    {{ ($customer->plan_type_id == $planType['id']) ? 'selected' : (old('plan_type_id') == $planType['id'] ? 'selected' : '') }}>
                                    {{ $planType['name'] }}
                                </option>
                                @endforeach
                            </select>
                           
                            @error('plan_type_id')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                   
                        <div class="col-lg-4">
                            <label>Plan Price <span>*</span></label>
                            <input id="plan_price10" class="form-control @error('plan_price_id') is-invalid @enderror" value="{{ old('plan_price_id', $customer->plan_price_id) }}"  name="plan_price_id" readonly>
                            @error('plan_price_id')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                       
                    </div>
                    <h4 class="mt-4 mb-3">Your plan Addon's
                            <i class="fa fa-plus toggleIcon1" style="cursor: pointer;"></i>
                    </h4>

                    <div style="display: none;" class="mb-3 idProofFields1">
                        <div class="row g-4">
                            @if(!in_array('3', toggleHideField()) || (in_array('3', toggleHideField()) && ($hasLocker == 'yes')))
                            <div class="col-lg-4 {{ !is_locker() ? 'd-none' : '' }}">
                                <label>Locker?</label>
                                <select name="locker" id="toggleFieldCheckbox10" class="form-control">
                                    <option value="no" {{ $hasLocker === 'no' ? 'selected' : '' }}>No</option>
                                    <option value="yes" {{ $hasLocker === 'yes' ? 'selected' : '' }}>Yes, I Need a Locker</option>
                                </select>
                            </div>

                            <div class="col-lg-4 {{ !is_locker() ? 'd-none' : '' }}">
                                <label>Locker Amount<span>*</span></label>
                                <input type="text" id="locker_amount10" class="form-control @error('locker_amount') is-invalid @enderror" name="locker_amount" placeholder="0.00" value="{{$locker_amt}}" readonly>
                                @error('locker_amount')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-lg-4 col-6 {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer2" >
                                <label for="locker_no">Locker No.</label>
                                <input type="text" class="form-control digit-only" name="locker_no" id="locker_no10"  placeholder="Enter Locker No." value="{{$customer->locker_no}}" {{$locker_read}}>
                                @error('locker_no')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            @endif
                            @if(!in_array('6', toggleHideField()) || (in_array('6', toggleHideField()) && $discountAmount) )
                            <div class="col-lg-6">
                                <label>Discount Type</label>
                                <select id="discountType10" class="form-control form-select" name="discountType">
                                    <option value="">Select Discount Type</option>
                                    <option value="amount" {{ $selectedDiscountType == 'amount' ? 'selected' : '' }}>Amount</option>
                                    <option value="percentage" {{ $selectedDiscountType == 'percentage' ? 'selected' : '' }}>Percentage</option>
                                </select>
                            </div>

                            <div class="col-lg-6">
                                <label>Discount Amount ( <span id="typeVal10">INR / %</span> )</label>
                                <input type="text" id="discount_amount10" class="form-control @error('discount_amount') is-invalid @enderror" placeholder="0.00" name="discount_amount"  value="{{ currentTransaction($customer->learner_detail_id)->discount_amount ?? 0 }}" readonly>
                                @error('discount_amount')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            @endif
                            
                        </div>
                    </div>

                    <div class="row g-4">
                        @if($paymentType=='CHANGE PLAN')
                        <div class="col-lg-4">
                            <label for="">Last paid Amount <span>*</span></label>
                            <input type="text" class="form-control @error('previous_amount') is-invalid @enderror"
                                name="previous_amount" id="previous_amount10"
                                value="{{ currentTransaction($customer->learner_detail_id)->total_amount }}" readonly>
                            @error('previous_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        @endif
                        
                        <div class="col-lg-4">
                            <label>Total Amount <span>*</span></label>
                            <input type="text" id="total_amount10" class="form-control @error('paid_amount') is-invalid @enderror" name="paid_amount"   value="{{ old('paid_amount', optional(currentTransaction($customer->learner_detail_id))->total_amount) }}" {{ (Route::currentRouteName() == 'learner.change.plan' ) ? 'readonly' : '' }}> 
                            @error('paid_amount')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                            
                        </div>
                        @if($paymentType=='CHANGE PLAN')
                        <div class="col-lg-4">
                            <label for="">Diffrence Amount <span>*</span></label>
                            <input type="text" class="form-control @error('diffrence_amount') is-invalid @enderror"
                                name="diffrence_amount" id="diffrence_amount10"  value="{{ old('diffrence_amount') }}"    placeholder="0.00">
                            @error('diffrence_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                             
                        </div>
                        @endif
                        <div class="col-lg-4">
                            <label>Pending Amount <span>*</span></label>
                            <input type="text" id="pending_amt10" class="form-control" placeholder="0" value="{{ old('pending_amount') }}"  readonly>
                            <span id="pending_amt_error" class="text-danger"></span>
                        </div>
                        <div class="col-lg-4">
                            <label for="">Choose Due Date<span>*</span></label>
                            <input type="date" id="due_date10" class="form-control duedate  @error('due_date') is-invalid @enderror" placeholder="Enter Due Date" name="due_date"  readonly>
                            @error('due_date')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        
                        <div class="col-lg-4">
                            <label>Payment Mode <span>*</span></label>
                            <select name="payment_mode" id="payment_mode10" class="form-control form-select @error('payment_mode') is-invalid @enderror">
                                <option value="">Select Payment Mode</option>
                                <option value="1" {{ $customer->payment_mode == 1 ? 'selected' : '' }}>Online</option>
                                <option value="2" {{ $customer->payment_mode == 2 ? 'selected' : '' }}>Offline</option>
                                <option value="3" {{ $customer->payment_mode == 3 ? 'selected' : '' }}>Pay Later</option>
                            </select>
                            @error('payment_mode')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    <div class="button-list mt-4">
                        @if(Route::currentRouteName() == 'learner.change.plan' && !$today->greaterThanOrEqualTo($oneWeekLater))
                       
                        <input type="submit" class="btn btn-primary btn-block button w-25" value="Update Seat Info">
                        @else  
                        
                            @if($planDetails['diff_in_days'] <= 5 && $planDetails['diff_extend_day']> 0 && !$is_renew && !$isalreadyRenew)
                            <input type="submit" class="btn btn-primary btn-block button w-25" value="{{ Route::currentRouteName() == 'learner.renew.plan' ? 'Renew Plan' : 'Upgrade Plan' }}">
                            @else
                            <p class="text-danger"><b>*</b>Button is available when you renew your Seat Booking</p>
                            @endif
                        @endif
                    </div>
                </form>
            </div>
        </div>

    </div>
    <div class="col-lg-3 order-1 order-md-2">
        <div class="seatnumber">
            <img src="{{ asset($customer->image) }}" alt="Seat" class="py-3 {{$class}}" style="width:60px; display:block; margin:0 auto;">
            @if($customer->seat_no)
            <span class="d-block ">Seat No : {{ $customer->seat_no}}</span>
            @else
            <span class="d-block ">General</span>
            @endif
            <div class="seat--plan">{{ $customer->plan_type_name}}</div>
        </div>

    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const formId = "{{ $ids }}"; // Dynamically injected form ID
        const formElement = document.getElementById(formId);

        if (formId === 'learnerUpgrade') {
            handleFormChanges('learnerUpgrade', {{ $customer->id }});
        } else if (formId === 'renewSeat') {
            handleFormChanges('renewSeat', {{ $customer->id }});
        }
        else if (formId === 'changePlan'){
            handleFormChanges('changePlan', {{ $customer->id }});
        }
    });
</script>





@endsection