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

$route=route('learner.upgrade.renew.store');
$ids='learnerUpgrade';
$start_date = \Carbon\Carbon::parse($customer->plan_end_date)->addDay()->format('Y-m-d');

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
                    @if(!in_array('2', toggleHideField()))
                    <li>
                        <span>DOB</span>
                        <h4>{{ $customer->dob ? \Carbon\Carbon::parse($customer->dob)->format('d F, Y') : 'DOB Not Available' }}</h4>
                    </li>
                    @endif
                    <li>
                        <span>Mobile</span>
                        <h4>+91-{{ display_learner_mobile($customer->mobile) }}</h4>
                    </li>
                    @if(!in_array('1', toggleHideField()))
                    <li>
                        <span>Email</span>
                        <h4><a href="mailto:{{$customer->email}}" class="text-white"> @if($customer->email) {{ display_learner_email($customer->email) }} @else Email ID Not Available @endif </a></h4>
                    </li>
                    @endif
                </ul>
            </div>

            <div class="form-input mb-4">
                <h4 class="inner-heading">Upgrade Plan</h4>
                <div class="tip text-danger">
                    @if(Route::currentRouteName() == 'learner.renew.plan')
                    <b>Note:</b> You can renew your plan 5 days before it expires or during the extended period. Plan and shift changes are not allowed.
                    @else
                    <b>Note:</b> You can upgrade your plan & plan type (shift) 5 days before it expires or during the extended period.
                    @endif

                </div>

                <form action="{{$route}}" method="POST" enctype="multipart/form-data" id="{{$ids}}">
                    @csrf
                    @method('POST')

                    <input type="hidden" name="learner_detail" value="{{$customer->learner_detail_id }}">
                    <input type="hidden" name="learner_id" value="{{ $customer->id}}" >
                    <input type="hidden" name="user_id" value="{{ $customer->id}}" id="user_id">
                    <input type="hidden" name="library_id" value="{{ $customer->library_id}}">
                    <input type="hidden" name="payment_type" value="UPGRADE" id="payment_type_operation">
                    <input type="hidden" id="start_date10" value="{{$start_date}}">

                    <h4 class="mt-4 mb-3">Current Plan Info</h4>

                    <div class="row g-4">
                        <div class="col-lg-4">
                            <label>Plan <span>*</span></label>
                            <select id="plan_id10" class="form-control form-select @error('plan_id') is-invalid @enderror" name="plan_id" {{ (Route::currentRouteName() == 'learner.renew.plan') ? 'readonly' : '' }}>
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

                                    <option value="no" {{ old('locker', $hasLocker) === 'no' ? 'selected' : '' }}>No</option>
                                    <option value="yes" {{ old('locker', $hasLocker) === 'yes' ? 'selected' : '' }}>Yes, I Need a Locker</option>
                                </select>
                            </div>

                            <div class="col-lg-4 {{ !is_locker() ? 'd-none' : '' }}">
                                <label>Locker Amount<span>*</span></label>
                                <input type="text" id="locker_amount10" class="form-control @error('locker_amount') is-invalid @enderror" name="locker_amount" placeholder="0.00"  value="{{ old('locker_amount', $locker_amt) }}" readonly>
                                @error('locker_amount')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-lg-4 col-6 {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer2" >
                                <label for="locker_no">Locker No.</label>
                                <input type="text" class="form-control digit-only @error('locker_no') is-invalid @enderror" name="locker_no" id="locker_no10"  placeholder="Enter Locker No." value="{{ old('locker_no', $customer->locker_no) }}"  {{$locker_read}}>
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
                                    <option value="amount" {{ old('discountType', $selectedDiscountType) == 'amount' ? 'selected' : '' }}>Amount</option>
                                    <option value="percentage" {{ old('discountType', $selectedDiscountType) == 'percentage' ? 'selected' : '' }}>Percentage</option>

                                </select>
                            </div>

                            <div class="col-lg-6">
                                <label>Discount Amount ( <span id="typeVal10">INR / %</span> )</label>
                                <input type="text" id="discount_amount10" class="form-control @error('discount_amount') is-invalid @enderror" placeholder="0.00" name="discount_amount"

                                value="{{ old('discount_amount', currentTransaction($customer->learner_detail_id)->discount_amount ?? 0) }}"
                                readonly>
                                @error('discount_amount')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            @endif

                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-4">
                             <label for="">Previous Pending Amount <span>*</span></label>
                            <input type="text" class="form-control @error('previous_pending') is-invalid @enderror" name="previous_pending" id="previous_pending10" value="{{ old('previous_pending', totalPending($customer->id)) }}" readonly>
                        </div>
                        <div class="col-lg-4">
                            <label>Total Amount <span>*</span></label>
                            <input type="text" id="total_amount10" class="form-control @error('paid_amount') is-invalid @enderror" name="paid_amount"   value="{{ old('paid_amount', optional(currentTransaction($customer->learner_detail_id))->total_amount) }}">
                            @error('paid_amount')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                            <span id="chargeable_days10" class="text-info"></span>
                        </div>
                        <div class="col-lg-4">
                            <label for="pending_amt10">Pending Amount <span>*</span></label>
                            <input type="text" id="pending_amt10" class="form-control" name="pending_amount" placeholder="0" value="{{ old('pending_amount') }}"  readonly>
                            <span id="pending_amt_error" class="text-danger"></span>
                        </div>

                        <div class="col-lg-4">
                            <label for="">Choose Due Date</label>
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
                        @if($planDetails['diff_in_days'] <= 5 && $planDetails['diff_extend_day']>= 0 && !$is_renew && !$isalreadyRenew)
                            <button type="submit" class="btn btn-primary btn-block button w-25" >{{ Route::currentRouteName() == 'learner.renew.plan' ? 'Renew Plan' : 'Upgrade Plan' }}</button>
                        @else
                        <p class="text-danger"><b>*</b>Button is available when you renew your Seat Booking</p>
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
        handleFormChanges('learnerUpgrade', {{ $customer->id }});
    });
</script>

@endsection
