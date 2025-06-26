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
@php
     $route=route('learner.payment.store');
        $id='payment';
    $transaction = currentTransaction($customer->learner_detail_id);
    $hasLocker = ($transaction && $transaction->locker_amount > 0) ? 'yes' : 'no';
    $transaction = currentTransaction($customer->learner_detail_id);
    // if(($diffInDays <= 5 && $diffExtendDay>0 && !$isRenew)){
    //     $id='renewSeat';
    //     $route=route('learners.renew');
    // }else{
    //     $route=route('learner.payment.store');
    //     $id='payment';
    // }
@endphp
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
                        <input type="text" class="form-control @error('name') is-invalid @enderror char-only" placeholder="Full Name" name="name" id="name" value="{{ old('name', $customer->learner->name) }}" readonly>
                        @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-lg-6 col-6">
                        <label for="">DOB <span>*</span></label>
                        <input type="date" class="form-control @error('dob') is-invalid @enderror" placeholder="DOB" name="dob" id="dob" value="{{ old('dob', $customer->learner->dob) }}" readonly>
                        @error('dob')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-lg-6 col-6">
                        <label for="">Mobile Number <span>*</span></label>
                        <input type="text" class="form-control @error('mobile') is-invalid @enderror digit-only" maxlength="10" minlength="10" placeholder="Mobile Number" name="mobile" id="mobile" value="{{ old('mobile', $customer->learner->mobile) }}" readonly>
                        @error('mobile')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-lg-6 col-6">
                        <label for="">Email Id <span>*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" placeholder="Email Id" name="email" id="email" value="{{ old('email', $customer->learner->email) }}" readonly>
                        @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                </div>
            </div>
        
            <form action="{{$route}}" method="POST" enctype="multipart/form-data" id="{{$id}}"  class="payment_page">
                @csrf
                @method('POST')
                <div class="action-box">
                    <h4 class="mb-4">Actionables 
                    <div class="info-container">  
                        <i class="fa-solid fa-circle-info info-icon"></i>
                        <div class="info-card">
                            <h3 class="info-title">Payment</h3>
                            <p class="info-details">Learners can request to change their current
                            seat to another available seat. If the requested seat is
                            available, the learner’s current seat will be swapped with the
                            new one.</p>
                        </div>
                    </div>
                    {{-- @if($diffInDays < 0 && $diffExtendDay>0 && !$isRenew)
                    <h4 class="mb-4 d-block">Renew your Plan
                    <p class="mt-2 text-danger"><b>Note:</b> You can easily renew your plan!</p>
                    @else
                 
                    @endif --}}
                 
                    </h4>
                
                    <p class="text-danger">Note : Here we are displaying the active plan Payment information that has been completed. You can also make payment of Pay Later and Pending Amount.</p>
                    <input type="hidden" name="learner_id" value="{{ $customer->learner->id}}">
                    <input  type="hidden" name="user_id" value="{{ $customer->learner->id}}">
                    <input id="library_id" type="hidden" name="library_id" value="{{ $customer->library_id}}">
                    <input  type="hidden" name="learner_transaction_id" value="{{ $pending_payment->id ?? ''}}">
                    <div class="row g-4">
                        <div class="col-lg-4 ">
                            <label for="">Plan <span>*</span></label>
                             <input type="text" class="form-control" value="{{ $customer->plan->name }}" readonly>
                            {{-- @if($diffInDays < 0 && $diffExtendDay>0 && !$isRenew)
                            <select  id="update_plan_id" class="form-control @error('plan_id') is-invalid @enderror" name="plan_id" >
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
                            @else
                           <input type="text" class="form-control" value="{{ $customer->plan->name }}" readonly>
                            @endif --}}
                        </div>
                        <div class="col-lg-4">
                            <label for="">Plan Type <span>*</span></label>
                             <input type="text" class="form-control" value="{{ $customer->planType->name  }}" readonly >
                            {{-- @if($diffInDays < 0 && $diffExtendDay>0 && !$isRenew)
                            <select  id="updated_plan_type_id" class="form-control @error('plan_type_id') is-invalid @enderror" name="plan_type_id" readonly>
                                
                                <option value="{{ $customer->plan_type_id }}">{{ $customer->planType->name }}</option>
                                
                            </select>
                            @else
                            <input type="text" class="form-control" value="{{ $customer->planType->name  }}" readonly >

                            @endif --}}
                           
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
                        @if($hasLocker === 'yes')
                         
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
                        @endif
                        @if($transaction && $transaction->discount_amount)                      
                       <div class="col-lg-4">
                            <label for="discount_amount">Discount Type</label>
                            <select id="discountType" class="form-select" name="discountType">
                                <option value="">Select Discount Type</option>
                                <option value="amount" {{ $selectedDiscountType == 'amount' ? 'selected' : '' }}>Amount</option>
                                <option value="percentage" {{ $selectedDiscountType == 'percentage' ? 'selected' : '' }}>Percentage</option>
                            </select>
                        </div>
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
                             <label for="">Pending Payment<span>*</span></label>
                            <input type="text" class="form-control " name="paid_amount"  value="{{ old('pending_amount', $pending_payment->pending_amount ?? 0) }}" readonly>
                            {{-- @if($diffInDays < 0 && $diffExtendDay>0 && !$isRenew)
                            <label for="">Plan Price <span>*</span></label>
                           
                            <input id="updated_plan_price_id" class="form-control" placeholder="Plan Price" name="plan_price_id" value="{{ old('plan_price_id', $customer->plan_price_id ) }}" @readonly(true)>

                            @else
                            <label for="">Pending Payment<span>*</span></label>
                            <input type="text" class="form-control " name="paid_amount"  value="{{ old('pending_amount', $pending_payment->pending_amount ?? 0) }}" >

                            @endif --}}
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
                        <div class="col-lg-4">
                            <label for="">Transaction Date <span>*</span></label>
                            <input type="date" class="form-control @error('paid_date') is-invalid @enderror" placeholder="Transaction Date" name="paid_date" id="paid_date" value="">
                            @error('paid_date')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-3">
                            @if($is_payment_pending && $pending_payment->pending_amount)
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
            {!! getUserStatusWithSpan($customer->plan_end_date) !!}
        </div>
    </div>
</div>
<script>
 
document.addEventListener('DOMContentLoaded', function() {
  
    const formId = document.querySelector('form.payment_page').id;
    
    handleFormChanges(formId, {{$customer->learner->id}});
});


</script>


@include('learner.script')

@endsection