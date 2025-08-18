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
<input id="plan_type_id" type="hidden" name="plan_type_id" value="{{$customer->plan_type_id }}">

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
                    @else
                    Upgrade Plan
                    @endif
                </h4>
                <div class="tip text-danger">
                    <b>Note:</b> Any learner can upgrade their plan only renewing seat in their extend period.
                    If the seat not have that plan type available then first need to perform swap seat operation then you do change plan.
                </div>

                <form action="{{route('learner.upgrade.renew.store')}}" method="POST" enctype="multipart/form-data" id="{{$ids}}">
                    @csrf
                    @method('POST')

                    <input type="hidden" name="learner_detail" value="{{$customer->learner_detail_id }}">
                    <input type="hidden" name="learner_id" value="{{ $customer->id}}">
                    <input type="hidden" name="user_id" value="{{ $customer->id}}">
                    <input type="hidden" name="library_id" value="{{ $customer->library_id}}">

                    <div class="row g-4">
                        <div class="col-lg-4">
                            <label>Plan <span>*</span></label>
                            <select id="plan_id" class="form-control form-select @error('plan_id') is-invalid @enderror" name="plan_id" {{ Route::currentRouteName() == 'learner.renew.plan' ? 'disabled' : '' }}>
                                <option value="">Select Plan</option>
                                @foreach($plans as $key => $value)
                                <option value="{{ $value->id }}" {{ old('plan_id', $customer->plan_id) == $value->id ? 'selected' : '' }}>{{ $value->name }}</option>
                                @endforeach
                            </select>
                            @if(Route::currentRouteName() == 'learner.renew.plan')
                            <input type="hidden" name="plan_id" value="{{ old('plan_id', $customer->plan_id) }}">
                            @endif
                            @error('plan_id')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label>Plan Type <span>*</span></label>
                            <select id="plan_type_id2" class="form-control form-select  @error('plan_type_id') is-invalid @enderror" name="plan_type_id" {{ Route::currentRouteName() == 'learner.renew.plan' ? 'disabled' : '' }}>
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
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label>Plan Price <span>*</span></label>
                            <input id="plan_price" class="form-control @error('plan_price_id') is-invalid @enderror" value="{{ old('plan_price_id', $customer->plan_price_id) }}" readonly name="plan_price_id">
                            @error('plan_price_id')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        @php
                        $hasLocker = currentTransaction($customer->learner_detail_id)->locker_amount > 0 ? 'yes' : 'no';
                        $discountAmount = currentTransaction($customer->learner_detail_id)->discount_amount ?? null;
                        $selectedDiscountType = $discountAmount ? 'amount' : '';
                        @endphp

                        <div class="col-lg-4">
                            <label>Locker?</label>
                            <select name="locker" id="toggleFieldCheckbox" class="form-control">
                                <option value="no" {{ $hasLocker === 'no' ? 'selected' : '' }}>No</option>
                                <option value="yes" {{ $hasLocker === 'yes' ? 'selected' : '' }}>Yes, I Need a Locker</option>
                            </select>
                        </div>

                        <div class="col-lg-4">
                            <label>Locker Amount <span>*</span></label>
                            <input type="text" class="form-control @error('locker_amount') is-invalid @enderror" name="locker_amount" id="locker_amount" value="{{ currentTransaction($customer->learner_detail_id)->locker_amount }}" readonly>
                            @error('locker_amount')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label>Discount Type</label>
                            <select id="discountType2" class="form-control form-select" name="discountType">
                                <option value="">Select Discount Type</option>
                                <option value="amount" {{ $selectedDiscountType == 'amount' ? 'selected' : '' }}>Amount</option>
                                <option value="percentage" {{ $selectedDiscountType == 'percentage' ? 'selected' : '' }}>Percentage</option>
                            </select>
                        </div>

                        <div class="col-lg-4">
                            <label>Discount Amount ( <span id="typeVal">INR / %</span> )</label>
                            <input type="text" class="form-control @error('discount_amount') is-invalid @enderror" name="discount_amount" id="discount_amount2" value="{{ currentTransaction($customer->learner_detail_id)->discount_amount ?? 0 }}">
                            @error('discount_amount')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label>Total Amount <span>*</span></label>
                            <input type="text" class="form-control @error('total_amount') is-invalid @enderror" name="total_amount" id="new_plan_price" value="{{ currentTransaction($customer->learner_detail_id)->total_amount }}" readonly>
                            @error('total_amount')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label>Payment Mode <span>*</span></label>
                            <select name="payment_mode" id="payment_mode" class="form-control form-select @error('payment_mode') is-invalid @enderror">
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
                        @if($planDetails['diff_in_days'] <= 5 && $planDetails['diff_extend_day']> 0 && !$is_renew && !$isalreadyRenew)
                        <input type="submit" class="btn btn-primary btn-block button w-25" value="{{ Route::currentRouteName() == 'learner.renew.plan' ? 'Renew Plan' : 'Upgrade Plan' }}">
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
        const formId = "{{ $ids }}"; // Dynamically injected form ID
        const formElement = document.getElementById(formId);

        if (formId == 'learnerUpgrade') {
            handleFormChanges('learnerUpgrade', {
                {
                    $customer - > id
                }
            });
        } else if (formId == 'renewSeat') {
            handleFormChanges('renewSeat', {
                {
                    $customer - > id
                }
            });
        }
    });
</script>




@endsection