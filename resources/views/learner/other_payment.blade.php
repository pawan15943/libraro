@extends('layouts.library')

@section('content')
@php
$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class = $planDetails['class'];
@endphp

<link rel="stylesheet" href="{{ asset('public/css/learner-other-payment.css') }}?v={{ time() }}" />

<div class="learner-other-payment-module">
    <div class="learner-other-payment-wrapper">

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- SEAT NO TOP HEADER HERO CARD (OUTSIDE FORM CARDS) --}}
        <div class="learner-seat-header-card">
            <div class="seat-header-actions">
                <a href="{{ route('learners') }}" class="btn-seat-back">
                    <i class="fa-solid fa-arrow-left"></i> Go Back
                </a>
            </div>
            <div class="seat-header-main">
                <div class="seat-header-avatar-box">
                    @if($customer->learner && $customer->learner->profile_picture)
                        <img id="topSeatAvatarImg" src="{{ asset($customer->learner->profile_picture) }}" alt="{{ $customer->learner->name }}" class="avatar-user-photo">
                    @elseif($customer->planType && $customer->planType->image)
                        <img id="topSeatAvatarImg" src="{{ asset($customer->planType->image) }}" alt="Seat" class="avatar-seat-chair {{ $class }}">
                    @else
                        <img id="topSeatAvatarImg" src="{{ asset('public/img/booked.png') }}" alt="Seat" class="avatar-seat-chair {{ $class }}">
                    @endif
                </div>
                <div class="seat-header-info">
                    <div class="seat-badge-row">
                        <span class="seat-tag-pill">
                            <i class="fa-solid fa-chair"></i> Allocated Seat
                        </span>
                        <span class="seat-status-badge">
                            {{ $planDetails['status'] ?? 'Allocated' }}
                        </span>
                    </div>
                    <h3 class="seat-title">
                        @if($customer->seat_no)
                            Seat No : {{ getSeatDisplayShortFloorName($customer->seat_no) }} : {{ $customer->learner->name ?? 'Learner' }}
                        @else
                            General Seat : {{ $customer->learner->name ?? 'Learner' }}
                        @endif
                    </h3>
                    <div class="seat-meta-row">
                        <span class="seat-meta-item">
                            <i class="fa-regular fa-clock"></i> <strong>Shift / Plan:</strong> {{ $customer->plan->name ?? ($customer->planType->name ?? 'N/A') }}
                        </span>
                        @if($customer->plan_end_date)
                        <span class="seat-meta-item">
                            <i class="fa-regular fa-calendar-check"></i> <strong>Valid Till:</strong> {{ \Carbon\Carbon::parse($customer->plan_end_date)->format('d M, Y') }}
                        </span>
                        @endif
                        @if($customer->learner && $customer->learner->learner_no)
                        <span class="seat-meta-item">
                            <i class="fa-regular fa-id-badge"></i> <strong>Learner UID:</strong> {{ $customer->learner->learner_no }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- OTHER PAYMENT FORM CARD --}}
        <form action="{{ route('learner.other.payment.store') }}" method="POST" enctype="multipart/form-data" id="other-payment_page" class="payment_page">
            @csrf
            @method('POST')
            <input id="learner_id" type="hidden" name="learner_id" value="{{ $customer->learner_id }}">

            <div class="payment-card">
                <div class="payment-card-header header-green">
                    <div class="payment-header-left">
                        <div class="payment-header-icon">
                            <i class="fa-solid fa-credit-card"></i>
                        </div>
                        <div>
                            <h4 class="payment-header-title">Other Payment</h4>
                            <p class="payment-header-subtitle">Collect extra payment, token money, or pending refund fee.</p>
                        </div>
                    </div>
                </div>
                <div class="payment-card-body">
                    <div class="payment-tip-box">
                        <i class="fa-solid fa-gem"></i>
                        <div>
                            <strong>Note :</strong> If you want to take any extra payment from a student, use this option. It will add the payment to your revenue.
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4 form-group">
                            <label class="form-label" for="payment_type">Payment Type</label>
                            <select name="payment_type" id="payment_type" class="form-select @error('payment_type') is-invalid @enderror"
                                    data-token="{{ $tokenMoney }}" data-refund="{{ $customer->pending_refund }}">
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

                        <div class="col-md-4 form-group">
                            <label class="form-label" for="fees">Fees <span class="required-star">*</span></label>
                            <input type="text" class="form-control @error('fees') is-invalid @enderror" placeholder="Enter Fees" name="fees" id="fees" value="" maxlength="3" inputmode="numeric" autocomplete="off">
                            @error('fees')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="form-label" for="payment_mode">Payment Mode <span class="required-star">*</span></label>
                            <select name="payment_mode" id="payment_mode" class="form-select @error('payment_mode') is-invalid @enderror">
                                <option value="">Select Payment Mode</option>
                                <option value="Online">Online</option>
                                <option value="Offline">Offline</option>
                            </select>
                            @error('payment_mode')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ACTION BUTTON BAR (OUTSIDE BOX) --}}
            <div class="form-action-bar">
                <a href="{{ route('learners') }}" class="btn-back-form">
                    <i class="fa-solid fa-arrow-left"></i> Cancel
                </a>
                <button type="submit" class="btn-submit-payment" id="paymentsubmit">
                    <i class="fa-solid fa-credit-card"></i> Make Payment
                </button>
            </div>
        </form>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formEl = document.querySelector('form.payment_page');
    if (formEl) {
        handleFormChanges(formEl.id, {{ $customer->learner->id }});
    }
});

$(document).ready(function () {
    $('#fees').on('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 3);
    });

    $('#payment_type').on('change', function () {
        let selected = $(this).val();
        let tokenMoney = $(this).data('token');
        let pending_refund = $(this).data('refund');

        if (selected === 'token_money' && tokenMoney) {
            $('#fees').val(tokenMoney).prop('readonly', true);
        } else if (selected === 'token_money') {
            $('#fees').val('').prop('readonly', false);
        } else if (selected === 'pending_refund') {
            $('#fees').val(pending_refund).prop('readonly', false);
        } else {
            $('#fees').val('').prop('readonly', false);
        }
    });
});
</script>

@endsection