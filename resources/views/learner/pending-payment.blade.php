@extends('layouts.library')
@section('content')

@php

$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class=$planDetails['class'];
@endphp

<input id="plan_type_id" type="hidden" name="plan_type_id">

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
                        <h4><a href="mailto:{{$customer->email}}" class="text-white"> {!! $customer->learner->email ? $customer->learner->email : 'Email ID Not Available' !!} </a></h4>
                    </li>
                </ul>
            </div>

            <div class="form-input mb-4">
                <h4 class="inner-heading">Pending Payment</h4>
                <div class="tip"><i class="fa-solid fa-gem pe-1"></i> Note : Here you can receive the pending payment of learners.</div>
                <form action="{{route('learner.pending.payment.store')}}" method="POST" enctype="multipart/form-data" class="pending_payment">
                    @csrf
                    @method('POST')
                    
                        

                        <div class="row g-4">
                            <div class="col-lg-6 col-6">
                                <label for="">Last Due Date <span>*</span>
                                    @if($pendingPayment?->due_date && \Carbon\Carbon::now()->gt(\Carbon\Carbon::parse($pendingPayment->due_date)))
                                    <small class="text-danger"><strong>Overdue</strong></small>
                                    @endif

                                </label>
                                <input type="date" class="form-control @error('due_date') is-invalid @enderror" id="due_date" value="{{$pendingPayment->due_date ?? 0}}" readonly>
                                @error('due_date')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror

                            </div>
                            <div class="col-lg-6 col-6">
                                <label for="">Pending Payment </label>
                                <input id="for_pending_amount" class="form-control @error('pending_amount') is-invalid @enderror"
                                    value="{{ intval($pendingPayment->pending_amount) }}" @readonly(true)>
                                <input type="hidden" name="transaction_id" value="{{ $pendingPayment->id ?? '' }}">
                            </div>

                            
                            <div class="col-lg-4 col-6">
                                <label for="">Amount want to Pay <span>*</span></label>
                                <input id="amount_to_pay" class="form-control @error('pending_amount') is-invalid @enderror"
                                    name="pending_amount" value="{{ intval($pendingPayment->pending_amount) }}">
                                <input type="hidden" name="transaction_id" value="{{ $pendingPayment->id ?? '' }}">
                            </div>
                            <div class="col-lg-4 col-6 due-date-wrapper">
                                <label for="">Next Due Date <span>*</span></label>
                                <input type="date" class="form-control" name="due_date" id="for_pending_due_date">
                            </div>
                            <div class="col-lg-4 col-6">
                                <label for="">Payment Mode <span>*</span></label>
                                <select name="payment_mode" class="form-select @error('payment_mode') is-invalid @enderror">
                                    <option value="">Select Payment Mode</option>
                                    <option value="Online">Online</option>
                                    <option value="Offline">Offline</option>
                                    <option value="Other">Pay Later</option>
                                </select>
                                @error('payment_mode')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            



                        </div>

                        @if($pendingPayment && $pendingPayment->pending_amount)

                        <div class="row mt-4">
                            <div class="col-lg-3">
                                <input type="submit" class="btn btn-primary button" value="Make Payment">
                            </div>
                        </div>
                        @endif
                    
                </form>
            </div>
        </div>

        
    </div>
    <div class="col-lg-3 order-1 order-md-2">

        <div class="seat--info">

            @if($customer->seat_no)
            <span class="d-block ">Seat No : {{ $customer->seat_no}}</span>
            @else
            <span class="d-block ">General</span>
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
        handleFormChanges('pendingPayment', {{$customer->learner->id}} );
    });
        
    $(".due-date-wrapper #for_pending_due_date").prop("readonly", true);

    $(document).ready(function() {
        let totalPending = parseFloat($("#for_pending_amount").val()) || 0;
        $("#amount_to_pay").on("input", function() {
            let amountToPay = parseFloat($(this).val()) || 0;

            // 🔴 Prevent paying more than pending
            if (amountToPay > totalPending) {
                alert("You cannot pay more than the pending amount (" + totalPending + ")");
                $(this).val(totalPending);
                amountToPay = totalPending;
            }

            let remaining = totalPending - amountToPay;

            // Show due date if still pending

            if (remaining > 0) {
                $(".due-date-wrapper #for_pending_due_date").prop("readonly", false);
            } else {
                $("#for_pending_due_date").val(""); 
                $(".due-date-wrapper #for_pending_due_date").prop("readonly", true);
            }
        });
    });
</script>


@endsection