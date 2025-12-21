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

@if($current_route=='learners.edit')
<div class="row g-4">
 <div class="col-lg-9 order-2 order-md-1">
<form action="{{ route('learners.update', $customer->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input id="edit_seat" type="hidden" name="seat_no" value="{{ old('seat_no', $customer->seat_no) }}">
    <input name="user_id" type="hidden" value="{{$customer->id}}">
    <input name="plan_id" type="hidden" value="{{$customer->plan_id}}">
    <input name="plan_type_id" type="hidden" value="{{$customer->plan_type_id}}">
    <input name="plan_price_id" type="hidden" value="{{$customer->plan_price_id}}">
    <input name="plan_start_date" type="hidden" value="{{$customer->plan_start_date}}">
  
    <div class="library-operations mt-4">
        <div class="info__section">
            <h4 class="inner-heading">Learner Info</h4>
            <div class="row g-4">
                <div class="col-lg-6">
                    <label for="" class="text-white">Seat Owner Name <span>*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror char-only" placeholder="Full Name" name="name" id="name" value="{{ old('name', $customer->name) }}">
                    @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-lg-6">
                    <label for="" class="text-white">DOB <span>*</span></label>
                    <input type="date" class="form-control @error('dob') is-invalid @enderror" placeholder="DOB" name="dob" id="dob" value="{{ old('dob', $customer->dob) }}">
                    @error('dob')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-lg-6">
                    <label for="" class="text-white">Mobile Number <span>*</span></label>
                    <input type="text" class="form-control @error('mobile') is-invalid @enderror digit-only" maxlength="10" minlength="10" placeholder="Mobile Number" name="mobile" id="mobile" value="{{ old('mobile', $customer->mobile) }}">
                    @error('mobile')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-lg-6 ">
                    <label for="" class="text-white">Email Id </label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" placeholder="Email Id" name="email" id="email" value="{{ old('email', $customer->email) }}">
                    @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
            </div>
        </div>
        <div class="form-input mb-4">
            <h4 class="inner-heading">Update Plan Duration</h4>
            <p class="text-danger">⚠️ Warning: Changing the start date will also affect the plan end date. Please ensure you update it carefully and with full understanding of its impact.</p>
           <div class="row g-4">

                <div class="col-lg-6">
                    <label for=""> Plan <span>*</span></label>

                    <select class="form-select" name="plan_id" disabled id="plan_id">
                        <option value="{{ $customer->plan_name }}" selected>{{ $customer->plan_name }}</option>
                    </select>

                    @error('plan_id')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-lg-6">
                    <label for="">Plan Type <span>*</span></label>
                    <select class="form-select" name="plan_id" disabled>
                        <option value="{{ $customer->plan_type_id }}" selected>{{ $customer->plan_type_name }}</option>
                    </select>

                </div>
                <div class="col-lg-6">
                    <label for="plan_start_date">Plan Start Date</label>
                    <input type="date" class="form-control datepicker @error('plan_start_date') is-invalid @enderror" 
                        name="plan_start_date" 
                        value="{{ old('plan_start_date', $customer->plan_start_date) }}" 
                        id="plan_start_date_edit">
                    @error('plan_start_date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-lg-6">
                    <label for="plan_end_date">Plan End Date</label>
                    <input type="date" class="form-control datepicker @error('plan_end_date') is-invalid @enderror" 
                        name="plan_end_date" 
                        value="{{ old('plan_end_date', $customer->plan_end_date) }}" 
                        id="plan_end_date_edit" readonly>
                    @error('plan_end_date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                @php
                $start = \Carbon\Carbon::parse($customer->plan_start_date);
                $end   = \Carbon\Carbon::parse($customer->plan_end_date);
                $inclusive_days = $start->diffInDays($end) + 1;   // always inclusive
                @endphp

                <input type="hidden" id="total_days" value="{{ $inclusive_days }}">

            </div>
            
        </div>

        <div class="form-input mb-4">
            <h4 class="inner-heading">Learner Other Info</h4>
            <p class="text-danger">Note : These details are optional. You may fill them in if you wish, or leave them blank.</p>
            <div class="row g-4">
                
                @if(!in_array('29', toggleHideField()))
                <div class="col-lg-6 ">
                    <label for="father_name">Father Name</label>
                    <input type="text" class="form-control @error('father_name') is-invalid @enderror char-only" name="father_name"  placeholder="Enter Father name" value="{{ old('father_name', $customer->father_name) }}">
                    @error('father_name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                @endif
                @if(!in_array('30', toggleHideField()))
                <div class="col-lg-6 ">
                    <label for="alternate_mobile">Alternate Mobile No.</label>
                    <input type="text" class="form-control @error('alternate_mobile') is-invalid @enderror digit-only" name="alternate_mobile"  maxlength="10" minlength="10" placeholder="Enter Alternate Mobile No." value="{{ old('alternate_mobile', $customer->alternate_mobile) }}">
                    @error('alternate_mobile')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                @endif
                @if(!in_array('4', toggleHideField()))
                <div class="col-lg-6 ">
                    <label for="prepareFor">Prepare For</label>
                    <select name="exam_id"  class="form-select @error('exam_id') is-invalid @enderror">
                        <option value="">Learner is Prepare For Exam</option>
                        @foreach($exams as $key => $value)
                        <option value="{{$value->id}}" {{ old('exam_id', $customer->exam_id) == $value->id ? 'selected' : '' }}>{{$value->name}}</option>   
                        @endforeach
                    </select>
                    @error('exam_id')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                @endif
                    @if(!in_array('32', toggleHideField()))
                <div class="col-lg-12 ">
                    <label for="address">Address</label>
                    <textarea class="form-control h-auto @error('address') is-invalid @enderror" name="address"  rows="3" placeholder="Enter address">{{ old('address', $customer->address) }}</textarea>
                    @error('address')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                @endif
                    @if(!in_array('31', toggleHideField()))
                <div class="col-lg-12 ">
                    <label for="remark">Remark</label>
                    <textarea class="form-control h-auto @error('remark') is-invalid @enderror" name="remark"  rows="3" placeholder="Enter Remark">{{ old('remark', $customer->remark) }}</textarea>
                    @error('remark')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                @endif
                    @if(!in_array('8', toggleHideField()))
                    <div class="col-lg-6">
                        <label for="profile_picture">Upload Profile Photo</label>
                        <input type="file" class="form-control @error('profile_picture') is-invalid @enderror" name="profile_picture"  value="{{ old('profile_picture', $customer->profile_picture) }}"
                            autocomplete="off" accept=".jpeg, .jpg, .png, .webp">  
                    @error('profile_picture')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                    @if($customer->profile_picture)
                    <a href="{{ asset($customer->profile_picture) }}" target="_blank">View</a>
                    @endif
                </div>
                @endif
                @if(!in_array('5', toggleHideField()))
                <div class="col-lg-6">
                    <label for="">Id Proof Received (Optional)</label>
                    <select  class="form-control @error('id_proof_name') is-invalid @enderror" name="id_proof_name" value="{{ old('id_proof_name', $customer->id_proof_name) }}">
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
                <div class="col-lg-6">
                    <label for="">Upload Scan Copy of Proof (Optional)</label>
                    <input type="file" class="form-control @error('id_proof_file') is-invalid @enderror" name="id_proof_file" autocomplete="off">
                    @error('id_proof_file')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                    @if($customer->id_proof_file)
                    <a href="{{ asset('public/'.$customer->id_proof_file) }}" target="_blank">View</a>
                    @endif
                </div>
                @endif
            </div>
            <div class="row mt-3">
                <div class="col-lg-3">
                    <input type="submit" class="btn btn-primary btn-block button"  value="Update Seat Info" autocomplete="off">
                </div>
            </div>
        </div>
    </div>
       
</form>
 </div>
<div class="col-lg-3 order-1 order-md-2">
    <div class="seatnumber">
        <img src="{{ asset($customer->image) }}" alt="Seat" class="py-3 {{$class}}" style="width:60px; display:block; margin:0 auto;">
         @if($customer->seat_no)
        <span class="d-block ">Seat No : {{ getSeatDisplayByMainNo($customer->seat_no)}}</span>
        @else
        <span class="d-block ">General</span>
        @endif
        <div class="seat--plan">{{ $customer->plan_type_name}}</div>
    </div>
</div>
</div>



@elseif($current_route=='learners.reactive')



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
                        <h4>+91-{{ $customer->mobile }}</h4>
                    </li>
                    @if(!in_array('1', toggleHideField()))
                    <li>
                        <span>Email</span>
                        <h4><a href="mailto:{{$customer->email}}" class="text-white"> {!! $customer->email ? $customer->email : 'Email ID Not Available' !!} </a></h4>
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
                    @php
                    $hasLocker = currentTransaction($customer->learner_detail_id)->locker_amount > 0 ? 'yes' : 'no';
                    $discountAmount = currentTransaction($customer->learner_detail_id)->discount_amount ?? null;
                    $selectedDiscountType = $discountAmount ? 'amount' : '';
                    @endphp


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
                <h4 class="inner-heading">Activate New Plan</h4>
                <form action="{{ route('learner.reactive.store', $customer->id) }}" method="POST" enctype="multipart/form-data" id="reactive">
                    @csrf
                    @method('PUT')

                    <p class="text-danger">Note : Here you can activate an existing seat learner into other seat.</p>

                    <div class="row g-4">

                        <input id="user_id" type="hidden" name="user_id" value="{{$customer->id }}">
                        <input id="learner_detail" type="hidden" name="learner_detail" value="{{$customer->learner_detail_id }}">

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
                            <input id="plan_price10" class="form-control @error('plan_price_id') is-invalid @enderror" name="plan_price_id" value="{{ old('plan_price_id',$customer->plan_price_id) }}" readonly placeholder="Plan Price">
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
                                        <input type="text" class="form-control digit-only @error('locker_no') is-invalid @enderror" name="locker_no" id="locker_no10"  placeholder="Enter Locker No." value="{{$customer->locker_no}}" {{$locker_read ?? ''}}>
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
                        <div class="g-4 row">         
                            <div class="col-lg-4">
                                <label>Total Amount <span>*</span></label>
                                <input type="text" id="total_amount10" class="form-control @error('paid_amount') is-invalid @enderror" name="paid_amount"   value="{{ old('paid_amount', optional(currentTransaction($customer->learner_detail_id))->total_amount) }}" {{ (Route::currentRouteName() == 'learner.change.plan' ) ? 'readonly' : '' }}> 
                                @error('paid_amount')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                                
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
                                    
                                    <option value="">General</option>
                                    {{-- @foreach($available_seat as $id => $seat_no)
                                    <option value="{{ $seat_no }}" {{ $customer->seat_no == $seat_no ? 'selected' : '' }}>{{ $seat_no }}</option>
                                    @endforeach --}}
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
            {!! getUserStatusWithSpan($customer->plan_end_date, $customer->id) !!}

        </div>
    </div>
</div>

@endif
{{-- <script>
    document.addEventListener('DOMContentLoaded', function () {
        const startInput = document.getElementById('plan_start_date_edit');
        const endInput = document.getElementById('plan_end_date_edit');
        const planSelect = document.getElementById('plan_id'); // Plan dropdown
        const planTypeSelect = document.getElementById('plan_type'); // Plan type dropdown (if needed)

        function calculateEndDate() {
            const startDate = new Date(startInput.value);
            if (!(startDate instanceof Date) || isNaN(startDate)) return;

            // Get selected plan duration
            let planText = planSelect.value; // e.g. "3 MONTH", "1 Month"
            let duration = 1; // default 1
            let type = "DAY"; // default DAY

            if (planText) {
                // Extract number and unit
                const match = planText.match(/(\d+)\s*(DAY|DAYS|WEEK|WEEKS|MONTH|MONTHS|YEAR|YEARS)/i);
                if (match) {
                    duration = parseInt(match[1]);
                    type = match[2].toUpperCase();
                }
            }

            // Copy start date
            const endDate = new Date(startDate);

            // Calculate end date like PHP Carbon
            switch (type) {
                case 'DAY':
                case 'DAYS':
                    endDate.setDate(endDate.getDate() + duration - 1);
                    break;
                case 'WEEK':
                case 'WEEKS':
                    endDate.setDate(endDate.getDate() + (duration * 7) - 1);
                    break;
                case 'MONTH':
                case 'MONTHS':
                    endDate.setMonth(endDate.getMonth() + duration);
                    endDate.setDate(endDate.getDate() - 1);
                    break;
                case 'YEAR':
                case 'YEARS':
                    endDate.setFullYear(endDate.getFullYear() + duration);
                    endDate.setDate(endDate.getDate() - 1);
                    break;
                default:
                    break;
            }

            // Format yyyy-mm-dd
            const yyyy = endDate.getFullYear();
            const mm = String(endDate.getMonth() + 1).padStart(2, '0');
            const dd = String(endDate.getDate()).padStart(2, '0');
            endInput.value = `${yyyy}-${mm}-${dd}`;
        }

        // Recalculate on start date change or plan change
        startInput.addEventListener('change', calculateEndDate);
        planSelect.addEventListener('change', calculateEndDate);

        // Optional: recalc on plan type change if needed
        if(planTypeSelect){
            planTypeSelect.addEventListener('change', calculateEndDate);
        }

        // Initial calculation if start date already filled
        if (startInput.value) calculateEndDate();
    });


</script> --}}
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