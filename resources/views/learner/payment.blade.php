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
            @php
               
                if(($diffInDays <= 5 && $diffExtendDay>0 && !$isRenew)){
                    $id='renewSeat';
                    $route=route('learners.renew');
                }else{
                    $route=route('learner.payment.store');
                    $id='payment';
                }
            @endphp
            <form action="{{$route}}" method="POST" enctype="multipart/form-data" id="{{$id}}"  class="payment_page">
                @csrf
                @method('POST')
                <div class="action-box">
                    @if($diffInDays < 0 && $diffExtendDay>0 && !$isRenew)
                    <h4 class="mb-4 d-block">Renew your Plan
                    <p class="mt-2 text-danger"><b>Note:</b> You can easily renew your plan!</p>
                    @else
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
                    @endif
                 
                    </h4>
                
                    <p class="text-danger">Note : Here we are displaying the active plan Payment information that has been completed.</p>
                    <input type="hidden" name="learner_id" value="{{ $customer->learner->id}}">
                    <input  type="hidden" name="user_id" value="{{ $customer->learner->id}}">
                    <input id="library_id" type="hidden" name="library_id" value="{{ $customer->library_id}}">
                    <input  type="hidden" name="learner_transaction_id" value="{{ $pending_payment->id ?? ''}}">
                    <div class="row g-4">
                        <div class="col-lg-6 col-6">
                            <label for="">Plan <span>*</span></label>
                            @if($diffInDays < 0 && $diffExtendDay>0 && !$isRenew)
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
                            @endif
                        </div>
                        <div class="col-lg-6 col-6">
                            <label for="">Plan Type <span>*</span></label>
                            @if($diffInDays < 0 && $diffExtendDay>0 && !$isRenew)
                            <select  id="updated_plan_type_id" class="form-control @error('plan_type_id') is-invalid @enderror" name="plan_type_id" readonly>
                                
                                <option value="{{ $customer->plan_type_id }}">{{ $customer->planType->name }}</option>
                                
                            </select>
                            @else
                            <input type="text" class="form-control" value="{{ $customer->planType->name  }}" readonly >

                            @endif
                           
                        </div>
                        <div class="col-lg-6 col-6">
                            <label for="">Payment Mode</label>
                           
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
     
                        <div class="col-lg-6 col-6">
                            @if($diffInDays < 0 && $diffExtendDay>0 && !$isRenew)
                            <label for="">Plan Price <span>*</span></label>
                           
                            <input id="updated_plan_price_id" class="form-control" placeholder="Plan Price" name="plan_price_id" value="{{ old('plan_price_id', $customer->plan_price_id ) }}" @readonly(true)>

                            @else
                            <label for="">Pending Payment<span>*</span></label>
                            <input type="text" class="form-control " name="paid_amount"  value="{{ old('pending_amount', $pending_payment->pending_amount ?? 0) }}" >

                            @endif
                        </div>
                        <div class="col-lg-6 col-6">
                            <label for="">Transaction Date <span>*</span></label>
                            <input type="date" class="form-control @error('paid_date') is-invalid @enderror" placeholder="Transaction Date" name="paid_date" id="paid_date" value="">
                            @error('paid_date')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>
                    <div class="row mt-3">
                        @if($customer->payment_mode==3)

                        <div class="col-lg-6 col-6">
                            <label for="">Transaction Number <span>*</span></label>
                            <input type="text" class="form-control @error('transaction_id') is-invalid @enderror digit-only" placeholder="Transaction Number" name="transaction_id" id="transaction_id" value="{{ old('transaction_id') }}">
                            @error('transaction_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        @endif

                        <div class="col-lg-6 col-12">
                            <label for="">Upload Payment Proof </label>
                            <input type="file" class="form-control @error('transaction_image') is-invalid @enderror " placeholder="Transaction Number" name="transaction_image" id="transaction_image" value="{{ old('transaction_image') }}">
                            @error('transaction_image')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                    </div>

                    <div class="row mt-3">
                        <div class="col-lg-3">
                            @if($diffInDays <= 5 && $diffExtendDay > 0 && !$isRenew)
                                <input type="submit" class="btn btn-primary btn-block button" value="Renew">
                            @elseif($is_payment_pending && $pending_payment->pending_amount)
                                <input type="submit" class="btn btn-warning btn-block button" value="Make Payment">
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