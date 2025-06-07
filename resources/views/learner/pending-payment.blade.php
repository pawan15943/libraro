@extends('layouts.library')
@section('content')

@php
use Carbon\Carbon;
$today = Carbon::today();
$endDate = Carbon::parse($customer->plan_end_date);
$diffInDays = $today->diffInDays($endDate, false);
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
<input id="plan_type_id" type="hidden" name="plan_type_id" >

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
           
            <form action="{{route('learner.pending.payment.store')}}" method="POST" enctype="multipart/form-data"   class="payment_page">
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
                    <p class="text-danger">Note : Here you can receive the pending payment of learners.</p>
                  
                    
                    <div class="row g-4">
                          
                            <div class="col-lg-4 col-6">
                                <label for="">Pending Payment </label>
                                
                                <input  id="pending_amount" class="form-control @error('pending_amount') is-invalid @enderror" name="pending_amount" value="{{$pendingPayment->pending_amount}}">
                                <input  type="hidden" name="transaction_id" value="{{$pendingPayment->id}}">
                            
                            </div>
                                
                            <div class="col-lg-4 col-6">
                                <label for="">Due Date <span>*</span>
                                      @if(\Carbon\Carbon::now()->gt(\Carbon\Carbon::parse($pendingPayment->due_date)))
                                            <small class="text-danger"><strong>Overdue</strong></small>
                                        @endif
                                </label>
                                <input type="date" class="form-control @error('due_date') is-invalid @enderror"  name="due_date" id="due_date" value="{{$pendingPayment->due_date}}" disabled>
                                @error('due_date')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                               
                            </div> 
                          
                        <div class="col-lg-4 col-6">
                            <label for="">Payment Mode</label>
                            <select name="payment_mode" id="payment_mode" class="form-select @error('payment_mode') is-invalid @enderror">
                                <option value="">Select Payment Mode</option>
                                <option value="Online" >Online</option>
                                <option value="Offline" >Offline</option>
                                <option value="Other" >Pay Later</option>
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
                            <input type="submit" class="btn btn-primary button" value="Make Payment">
                        </div>
                    </div>
                    

                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-3 order-1 order-md-2">
        <div class="seat--info">
            @php 
            $class='';  
           
                if($diffInDays <= 5 && $diffExtendDay>0){
                    $class='extedned';
                }elseif($diffInDays < 0 ){
                    $class='expired';
                }
            @endphp
            <span class="d-block">Seat No : {{ $customer->learner->seat_no}}</span>
            <img src="{{ asset($customer->planType->image) }}" alt="Seat" class="seat py-3 {{$class}}">
            <p>{{ $customer->plan->name}}</p>
            <button>Booked for <b>{{ $customer->planType->name}}</b></button>
           
            @if ($diffInDays > 0)
                <span class="text-success">Plan Expires in {{ $diffInDays }} days</span>
            @elseif ($diffInDays < 0 && $diffExtendDay>0)
                <span class="text-danger fs-10 d-block">Extend Days are Active Now & Remaining Days are {{ abs($diffExtendDay) }} days.</span>
            @elseif ($diffInDays < 0 && $diffExtendDay==0)
                <span class="text-warning fs-10 d-block">Plan Expires today</span>
            @else
                <span class="text-danger fs-10 d-block">Plan Expired {{ abs($diffInDays) }} days ago</span>
            @endif
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