@extends('layouts.library')
@section('content')

@php

$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class=$planDetails['class'];

   
@endphp

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
                    @if(!in_array('2', toggleHideField()))
                    <li>
                        <span>DOB</span>
                        <h4>{{ $customer->learner->dob ? \Carbon\Carbon::parse($customer->dob)->format('d F, Y') : 'DOB Not Available' }}</h4>
                    </li>
                    @endif
                    <li>
                        <span>Mobile</span>
                        <h4>+91-{{ display_learner_mobile($customer->learner->mobile) }}</h4>
                    </li>
                    @if(!in_array('1', toggleHideField()))
                    <li>
                        <span>Email</span>
                        <h4><a href="mailto:{{$customer->email}}" class="text-white"> @if($customer->learner->email) {{ display_learner_email($customer->learner->email) }} @else Email ID Not Available @endif </a></h4>
                    </li>
                    @endif
                </ul>
            </div>

            <div class="form-input mb-4">
                <h4 class="inner-heading">Other Payment</h4>
                <div class="tip"><i class="fa-solid fa-gem pe-1"></i> If you want to take any extra payment from a student, use this option. It will add the payment to your revenue.</div>
                <form action="{{route('learner.other.payment.store')}}" method="POST" enctype="multipart/form-data" id="other-payment_page"  class="payment_page">
                    @csrf
                    @method('POST')
                    <input id="learner_id" type="hidden" name="learner_id" value="{{ $customer->learner_id}}">
                    
                    <div class="row g-4">
                        <div class="col-lg-4 ">
                            <label for="">Payment Type</label>
                           
                            <select name="payment_type" id="payment_type" class="form-select @error('payment_type') is-invalid @enderror"
                                    data-token="{{ $tokenMoney }}" data-refund="{{$customer->pending_refund}}">
                                <option value="">Select Payment</option>
                                @if(!$customer->token_money)
                                <option value="token_money">Token Money</option>
                                @endif
                                @if($customer->pending_refund)
                                <option value="pending_refund">Refund Amt. to Pay</option>
                                @endif
                                
                                <option value="miscellaneous">Miscellaneous fee</option>
                            </select>

                            @error('payment_type')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        
                            
                        </div>
                        <div class="col-lg-4">
                            <label for="">Fees <span>*</span></label>
                            <input type="text" class="form-control @error('fees') is-invalid @enderror" placeholder="Enter Fees" name="fees" id="fees" value="" maxlength="3" inputmode="numeric" autocomplete="off">
                            @error('fees')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-4">
                                <label for="">Payment Mode <span>*</span></label>
                                <select name="payment_mode"  class="form-select @error('payment_mode') is-invalid @enderror">
                                    <option value="">Select Payment Mode</option>
                                    <option value="Online" >Online</option>
                                    <option value="Offline" >Offline</option>
                                </select>
                                @error('payment_mode')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                        </div>
                        <div class="col-lg-3">
                            <button type="submit" class="btn btn-primary button">Make Payment</button>
                        </div>          
                    </div>

                    
                </form>
            </div>
        </div>
        
    </div>
    

    <div class="col-lg-3 order-1 order-md-2">
        <div class="seatnumber">
           @if($customer->planType && $customer->planType->image)
                <img src="{{ asset($customer->planType->image) }}" alt="Seat" class="py-3 {{$class}}" style="width:60px; display:block; margin:0 auto;">
            @else
                <img src="{{ asset('public/img/booked.png') }}" alt="Seat" class="py-3 {{$class}}" style="width:60px; display:block; margin:0 auto;">
            @endif

            @if($customer->seat_no)
            <span class="d-block ">Seat No : {{ $customer->seat_no}}</span>
            @else
            <span class="d-block ">General</span>
            @endif

            <div class="seat--plan">{{ $customer->plan->name}}</div>
        </div>

    </div>
</div>
<script>
 
document.addEventListener('DOMContentLoaded', function() {
  
    const formId = document.querySelector('form.payment_page').id;
    
    handleFormChanges(formId, {{$customer->learner->id}});
});


$(document).ready(function () {
    $('#fees').on('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 3);
    });

    $('#payment_type').on('change', function () {

        let selected = $(this).val();
        let tokenMoney = $(this).data('token');
        let pending_refund=$(this).data('refund');

        if (selected === 'token_money' && tokenMoney) {
            $('#fees').val(tokenMoney).prop('readonly', true); // ✅ readonly
        } else if (selected === 'token_money') {
            $('#fees').val('').prop('readonly', false); // token not set yet, let them enter it
        }else if (selected === 'pending_refund'){
             $('#fees').val(pending_refund).prop('readonly', false); // editable
             $('#fees').val(pending_refund);

        } else {
            $('#fees').val(pending_refund).prop('readonly', false); // editable
            $('#fees').val('');
        }


    });
});




</script>


@endsection