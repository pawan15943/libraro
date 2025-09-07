@extends('layouts.library')
@section('content')

@php
$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class=$planDetails['class'];

$route=route('learner.payment.store');
$id='payment';
$transaction = currentTransaction($customer->learner_detail_id);


$discountAmount = currentTransaction($customer->learner_detail_id)->discount_amount ?? null;
$selectedDiscountType = $discountAmount ? 'amount' : '';
$oneWeekLater = \Carbon\Carbon::parse($customer->plan_start_date)->addWeek();
$today = \Carbon\Carbon::now();
if ($transaction && $transaction->locker_amount > 0) {
$hasLocker = currentTransaction($customer->learner_detail_id)->locker_amount > 0 ? 'yes' : 'no';

$locker_amt=currentTransaction($customer->learner_detail_id)->locker_amount;
}else{
$hasLocker='no';

$locker_amt=0;
}
if($customer->locker_no){
$locker_read='';
}else{
$locker_read='readonly';
}
$paymentType='SEAT ASSIGNMENT';
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
                        <h4>{{ $customer->learner->learner_no }}</h4>
                    </li>
                    <li>
                        <span>Full Name</span>
                        <h4>{{ $customer->learner->name }}</h4>
                    </li>
                    <li>
                        <span>DOB</span>
                        <h4>{{ $customer->learner->dob ? \Carbon\Carbon::parse($customer->learner->dob)->format('d F, Y') : 'DOB Not Available' }}</h4>
                    </li>
                    <li>
                        <span>Mobile</span>
                        <h4>+91-{{ $customer->learner->mobile }}</h4>
                    </li>
                    <li>
                        <span>Email</span>
                        <h4><a href="mailto:{{$customer->learner->email}}" class="text-white"> {!! $customer->learner->email ? $customer->learner->email : 'Email ID Not Available' !!} </a></h4>
                    </li>
                </ul>
            </div>

            <form action="{{$route}}" method="POST" enctype="multipart/form-data" id="{{$id}}" class="payment_page">
                @csrf
                @method('POST')
                <div class="form-input mb-4">
                    <h4 class="inner-heading">Make Payment</h4>
                    <div class="tip"><i class="fa-solid fa-gem pe-1"></i> Note : Here we are displaying the active plan Payment information that has been completed. You can also make payment of Pay Later and Pending Amount.</div>

                    <input type="hidden" name="learner_id" value="{{ $customer->learner->id}}">
                    <input type="hidden" name="user_id" value="{{ $customer->learner->id}}">
                    <input id="library_id" type="hidden" name="library_id" value="{{ $customer->library_id}}">
                    <input type="hidden" name="learner_transaction_id" value="{{ $pending_payment->id ?? ''}}">


                    <h4 class="mt-4 mb-3">Current Plan Info</h4>

                    <div class="row g-4">
                        <div class="col-lg-4">
                            <label>Plan <span>*</span></label>
                            <select id="plan_id10" class="form-control form-select @error('plan_id') is-invalid @enderror" name="plan_id" readonly>
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
                            <select id="plan_type_id10" class="form-control form-select  @error('plan_type_id') is-invalid @enderror" name="plan_type_id" readonly>
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
                            <input id="plan_price10" class="form-control @error('plan_price_id') is-invalid @enderror" value="{{ old('plan_price_id', $customer->plan_price_id) }}" name="plan_price_id" readonly>
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
                            <div class="col-lg-4 col-6 {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer2">
                                <label for="locker_no">Locker No.</label>
                                <input type="text" class="form-control digit-only @error('locker_no') is-invalid @enderror" name="locker_no" id="locker_no10" placeholder="Enter Locker No." value="{{$customer->locker_no}}" {{$locker_read}}>
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
                                <input type="text" id="discount_amount10" class="form-control @error('discount_amount') is-invalid @enderror" placeholder="0.00" name="discount_amount" value="{{ currentTransaction($customer->learner_detail_id)->discount_amount ?? 0 }}" readonly>
                                @error('discount_amount')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            @endif

                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-4">
                            <label>Total Amount <span>*</span></label>
                            <input type="text" id="total_amount10" class="form-control @error('paid_amount') is-invalid @enderror" name="paid_amount" value="{{ old('paid_amount', optional(currentTransaction($customer->learner_detail_id))->total_amount) }}" readonly>
                            @error('paid_amount')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label for="pending_amt10">Pending Amount <span>*</span></label>
                            <input type="text" id="pending_amt10" class="form-control" name="pending_amount" placeholder="0" value="{{ old('pending_amount') }}" readonly>
                            <span id="pending_amt_error" class="text-danger"></span>
                        </div>


                        <div class="col-lg-4">
                            <label for="">Transaction Date <span>*</span></label>
                            <input type="date" class="form-control @error('paid_date') is-invalid @enderror" placeholder="Transaction Date" name="paid_date" id="paid_date" value="">
                            @error('paid_date')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col-lg-4 ">
                            <label for="">Payment Mode</label>

                            <select name="payment_mode" id="payment_mode" class="form-select @error('payment_mode') is-invalid @enderror">
                                <option value="">Select Payment Mode</option>
                                <option value="1" {{ $customer->payment_mode == 1 ? 'selected' : '' }}>Online</option>
                                <option value="2" {{ $customer->payment_mode == 2 ? 'selected' : '' }}>Offline</option>
                            </select>
                            @error('payment_mode')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror


                        </div>

                    </div>

                    <div class="row g-4 mt-3">
                        <div class="col-lg-3">
                            @if(($is_payment_pending && $pending_payment->pending_amount) || paylater($customer->learner_detail_id) )
                            <input type="submit" class="btn btn-primary button" value="Make Payment">
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
            {!! getUserStatusWithSpan($customer->plan_end_date,$customer->id) !!}
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const formId = document.querySelector('form.payment_page').id;

        handleFormChanges(formId, {
            {
                $customer - > learner - > id
            }
        });
    });
</script>


@endsection