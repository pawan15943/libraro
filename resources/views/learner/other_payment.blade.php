@extends('layouts.library')
@section('content')

@php
$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class=$planDetails['class'];

   
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
        
            <form action="{{route('learner.other.payment.store')}}" method="POST" enctype="multipart/form-data" id=""  class="payment_page">
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
                  
                    </h4>
                
                    <p class="text-danger">Note : Here we are displaying the active plan Payment information that has been completed. You can also make payment of Pay Later and Pending Amount.</p>
                    
                    <input id="learner_id" type="hidden" name="learner_id" value="{{ $customer->learner_id}}">
                    
                    <div class="row g-4">
                        <div class="col-lg-6 ">
                            <label for="">Payment Type</label>
                           
                            <select name="payment_type" id="payment_type" class="form-select @error('payment_type') is-invalid @enderror"
                                    data-token="{{ $tokenMoney }}">
                                <option value="">Select Payment</option>
                                <option value="token_money">Token Money</option>
                                <option value="miscellaneous">Miscellaneous fee</option>
                            </select>

                            @error('payment_type')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        
                            
                        </div>
                         
                         <div class="col-lg-6">
                            <label for="">Fees <span>*</span></label>
                            <input type="text" class="form-control @error('fees') is-invalid @enderror" placeholder="Enter Fees" name="fees" id="fees" value="">
                            @error('fees')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                     
                        <div class="col-lg-3">
                        
                            <input type="submit" class="btn btn-primary button" value="Make Payment">
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


$(document).ready(function () {
    $('#payment_type').on('change', function () {
      
        let selected = $(this).val();
        let tokenMoney = $(this).data('token');
       
        if (selected === 'token_money') {
            $('#fees').val(tokenMoney);
        } else {
            $('#fees').val('');
        }
    });
});

</script>


@endsection