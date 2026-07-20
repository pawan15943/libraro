@extends('layouts.library')
@section('content')
@php

$current_route = Route::currentRouteName();
$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class=$planDetails['class'];
if($customer->locker_no){
    $locker_read='';
}else{
    $locker_read='readonly';
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
                <h4 class="inner-heading">Old Plan Info</h4>
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
                        <input type="date" class="form-control" placeholder="Plan Starts On" name="plan_start_date" value="{{ $customer->plan_start_date }}" readonly>

                    </div>

                    <div class="col-lg-4">
                        <label for="">Plan End On <span>*</span></label>
                        <input type="date" class="form-control" placeholder="Plan Starts On" name="plan_end_date" value="{{$customer->plan_end_date}}" readonly>

                    </div>
           
                    <div class="col-lg-4">
                        <label for="locker">Locker?</label>
                        <select name="locker" class="form-select" disabled>
                            <option value="no" {{ $hasLocker === 'no' ? 'selected' : '' }}>No</option>
                            <option value="yes" {{ $hasLocker === 'yes' ? 'selected' : '' }}>Yes, I Need a Locker</option>
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label for="">Locker Amount <span>*</span></label>
                        <input type="text" class="form-control @error('locker_amount') is-invalid @enderror" name="locker_amount" value="{{ currentTransaction($customer->learner_detail_id)->locker_amount }}" readonly>
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
                        <input type="text" class="form-control @error('discount_amount') is-invalid @enderror" name="discount_amount" value="{{ currentTransaction($customer->learner_detail_id)->discount_amount ?? 0 }}" readonly>
                        @error('discount_amount')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>


                    <div class="col-lg-4">
                        <label for="">Total Amount <span>*</span></label>
                        <input type="text" class="form-control @error('total_amount') is-invalid @enderror" name="total_amount" value="{{ currentTransaction($customer->learner_detail_id)->total_amount }}" readonly>
                        @error('total_amount')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                </div>


            </div>

            <div class="form-input mb-4">
               
                <h4 class="inner-heading">Activate New Plan</h4>
                <form action="{{ route('learner.reactive.store', $customer->id) }}" method="POST" enctype="multipart/form-data" id="reactive">
                    @csrf
                    @method('PUT')

                    <p class="text-danger">Note : Here you can activate an existing seat learner into other seat.</p>

                    <div class="row g-4">

                        <input id="user_id" type="hidden" name="user_id" value="{{$customer->id }}">
                        <input type="hidden" name="learner_id" value="{{$customer->id }}">
                        <input id="learner_detail" type="hidden" name="learner_detail" value="{{$customer->learner_detail_id }}">
                        <input type="hidden" name="payment_type" value="REACTIVE" id="payment_type_operation">
                        <div class="col-lg-4">
                            <label for=""> Plan <span>*</span></label>
                            <select id="plan_id10" class="form-select @error('plan_id') is-invalid @enderror" name="plan_id">
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
                            <select id="plan_type_id10" class="form-select @error('plan_type_id') is-invalid @enderror" name="plan_type_id">
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
                            <input id="plan_price10" class="form-control @error('plan_price_id') is-invalid @enderror" name="plan_price_id" value="{{ old('plan_price_id') }}" readonly placeholder="Plan Price">
                            @error('plan_price_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>
                        <h4 class="mt-4 mb-3">Your plan Addon's
                        <i class="fa fa-plus toggleIcon1" style="cursor: pointer;"></i>
                        </h4>
                        <div style="display: none;" class="idProofFields1 mb-3">
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
                                        <input type="text" class="form-control digit-only @error('locker_no') is-invalid @enderror" name="locker_no" id="locker_no10"  placeholder="Enter Locker No."  value="{{ old('locker_no', $customer->locker_no) }}" {{$locker_read ?? ''}}>
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
                                             <option value="amount" {{ old('discountType', $selectedDiscountType) === 'amount' ? 'selected' : '' }}>Amount</option>
                                            <option value="percentage" {{ old('discountType', $selectedDiscountType) === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                           
                                        </select>
                                    </div>

                                    <div class="col-lg-6">
                                        <label>Discount Amount ( <span id="typeVal10">INR / %</span> )</label>
                                        <input type="text" id="discount_amount10" class="form-control @error('discount_amount') is-invalid @enderror" placeholder="0.00" name="discount_amount"   value="{{ old('discount_amount', currentTransaction($customer->learner_detail_id)->discount_amount ?? 0) }}" readonly>
                                        @error('discount_amount')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                @endif

                            </div>
                        </div>
                        <div class="g-4 row">
                            @if(totalPending($customer->id) > 0)
                             <div class="col-lg-4">
                                <label for="">Previous Pending Amount <span>*</span></label>
                                <input type="text" class="form-control @error('previous_pending') is-invalid @enderror" name="previous_pending" id="previous_pending10" value="{{ old('previous_pending', totalPending($customer->id)) }}" readonly>
                            </div> 
                            @endif        
                            <div class="col-lg-4">
                                <label>Total Amount <span>*</span></label>
                                <input type="text" id="total_amount10" class="form-control @error('paid_amount') is-invalid @enderror" name="paid_amount" placeholder="0.00"  value="{{ old('paid_amount') }}" {{ (Route::currentRouteName() == 'learner.change.plan' ) ? 'readonly' : '' }}> 
                                @error('paid_amount')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                                <span id="chargeable_days10" class="text-info"></span>
                            </div>
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
                                <label for="">Plan Starts On <span>*</span></label>
                                <input type="date" class="form-control @error('plan_start_date') is-invalid @enderror" placeholder="Plan Starts On" name="plan_start_date" id="start_date10" min="{{ \Carbon\Carbon::parse($customer->plan_end_date)->format('Y-m-d') }}" value="{{ old('plan_start_date', now()->lt(\Carbon\Carbon::parse($customer->plan_end_date)) ? \Carbon\Carbon::parse($customer->plan_end_date)->format('Y-m-d') : now()->format('Y-m-d')) }}">
                                @error('plan_start_date')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="col-lg-4">
                                <label for="">Select Seat<span>*</span></label>
                                <select name="seat_no" id="new_seat_id2" class="form-select @error('seat_no') is-invalid @enderror">
                                    
                                    <option value="">General</option>
                                    
                                     @foreach($newAvailableSeats as $key => $value)
                                   
                                    <option value="{{ $value['main'] }}" {{ $customer->seat_no == $value['main'] ? 'selected' : '' }}>{{ $value['display'] }}</option>
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
                        
                    </div>
                </form>
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
$(document).ready(function () {

    $("#plan_start_date_edit").on("change", function () {

        let startDate = $(this).val();
        let totalDays = parseInt($("#total_days").val()); // inclusive days

        // If missing data, do nothing
        if (!startDate || !totalDays || totalDays < 1) return;

        let start = new Date(startDate);

        // Inclusive means: add (days - 1)
        start.setDate(start.getDate() + (totalDays - 1));

        // Format to yyyy-mm-dd
        let yyyy = start.getFullYear();
        let mm = ("0" + (start.getMonth() + 1)).slice(-2);
        let dd = ("0" + start.getDate()).slice(-2);

        let formatted = `${yyyy}-${mm}-${dd}`;

        // UPDATE THE END DATE
        $("#plan_end_date_edit").val(formatted).trigger("change");

    });

});


</script>

@endsection
