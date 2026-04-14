@extends('layouts.library')
@section('content')

@php
$planDetails = !empty($customer?->plan_end_date)
    ? getPlanStatusDetails($customer->plan_end_date)
    : ['status' => 'Active', 'class' => 'actives', 'diff_in_days' => 0, 'diff_extend_day' => 0, 'extend_days' => 0];
$class = $planDetails['class'] ?? 'actives';
$learnerIdForStatus = $customer?->learner_id ?? optional($customer?->learner)->id ?? $tran?->learner_id;
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
                        <h4>{{ optional($customer?->learner)->learner_no ?? '—' }}</h4>
                    </li>
                    <li>
                        <span>Full Name</span>
                        <h4>{{ optional($customer?->learner)->name ?? '—' }}</h4>
                    </li>
                    <li>
                        <span>DOB</span>
                        <h4>@if(optional($customer?->learner)->dob){{ \Carbon\Carbon::parse(optional($customer?->learner)->dob)->format('d F, Y') }}@else DOB Not Available @endif</h4>
                    </li>
                    <li>
                        <span>Mobile</span>
                        <h4>@if(optional($customer?->learner)->mobile)+91-{{ display_learner_mobile(optional($customer?->learner)->mobile) }}@else — @endif</h4>
                    </li>
                    <li>
                        <span>Email</span>
                        <h4><a href="mailto:{{ optional($customer?->learner)->email ?? '' }}" class="text-white"> @if(optional($customer?->learner)->email) {{ display_learner_email(optional($customer?->learner)->email) }} @else Email ID Not Available @endif </a></h4>
                    </li>
                </ul>
            </div>

            <div class="form-input mb-4">
                <h4 class="inner-heading">Make Payment</h4>
                <div class="tip"><i class="fa-solid fa-gem pe-1"></i> Note : Here you can receive the pending payment of learners.</div>
                <form action="{{route('learner.pending.payment.store')}}" method="POST" enctype="multipart/form-data" class="pending_payment">
                    @csrf
                    @method('POST')
                    
                        

                        <div class="row g-4">
                            <div class="col-lg-4">
                                <label for="">Last Due Date <span>*</span>
                                    @if($tran?->due_date && \Carbon\Carbon::now()->gt(\Carbon\Carbon::parse($tran->due_date)))
                                    <small class="text-danger"><strong>Overdue</strong></small>
                                    @endif

                                </label>
                                <input type="date" class="form-control @error('due_date') is-invalid @enderror" id="due_date" value="{{ $tran?->due_date ?? '' }}" readonly>
                                @error('due_date')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror

                            </div>
                            <div class="col-lg-4">
                                <label for="">Pending Payment </label>
                                <input id="for_pending_amount" name="total_pending" class="form-control @error('pending_amount') is-invalid @enderror" value="{{ intval($pendingPayment) }}" @readonly(true) >
                               
                            </div>

                            
                            <div class="col-lg-4">
                                <label for="">Amount want to Pay <span>*</span></label>
                                <input id="amount_to_pay" class="form-control @error('amount_to_pay') is-invalid @enderror" name="amount_to_pay" value="{{ intval($pendingPayment) }}">
                                @error('amount_to_pay')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                                <input type="hidden" name="transaction_id" value="{{ $tran?->id ?? '' }}">
                                <input type="hidden" name="learner_id" value="{{ $tran?->learner_id ?? '' }}">
                            </div>
                            <div class="col-lg-6 due-date-wrapper">
                                <label for="">Next Due Date <span>*</span></label>
                                <input type="date" class="form-control" name="due_date" id="for_pending_due_date">
                            </div>
                            <div class="col-lg-6">
                                <label for="">Payment Mode <span>*</span></label>
                                <select name="payment_mode" class="form-select @error('payment_mode') is-invalid @enderror">
                                    <option value="">Select Payment Mode</option>
                                    <option value="1">Online</option>
                                    <option value="2">Offline</option>
                                    
                                </select>
                                @error('payment_mode')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            



                        </div>

                        @if($pendingPayment && $pendingPayment > 0)

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

            @if($customer?->seat_no)
            <span class="d-block ">Seat No : {{ $customer->seat_no}}</span>
            @else
            <span class="d-block ">General</span>
            @endif
            @if(optional($customer?->planType)->image)
            <img src="{{ asset($customer->planType->image) }}" alt="Seat" class="seat py-3 {{$class}}">
            @else
            <img src="{{ asset('public/img/booked.png') }}" alt="Seat" class="seat py-3 {{$class}}">
            @endif
            <p>{{ optional($customer?->plan)->name ?? '' }}</p>
            <button>Booked for <b>{{ optional($customer?->planType)->name ?? '—' }}</b></button>
            @if($learnerIdForStatus && !empty($customer?->plan_end_date))
                {!! getUserStatusWithSpan($customer->plan_end_date, $learnerIdForStatus) !!}
            @else
                <span class="text-muted fs-10 d-block"></span>
            @endif
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        handleFormChanges('pendingPayment', @json(optional($customer?->learner)->id));
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