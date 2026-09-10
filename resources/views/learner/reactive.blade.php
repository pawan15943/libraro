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

{{-- Scoped CSS for Learner Reactive Plan Module --}}
<link rel="stylesheet" href="{{ asset('public/css/learner-reactive-plan.css') }}?v={{ time() }}">

<div class="learner-reactive-plan-module">
    <div class="reactive-plan-wrapper">

        {{-- 1. TOP SEAT HEADER HERO CARD (MATCHING SWAP SEAT) --}}
        <div class="learner-seat-header-card">
            <div class="seat-header-actions">
                <a href="{{ route('learners') }}" class="btn-seat-back">
                    <i class="fa-solid fa-arrow-left"></i> Go Back
                </a>
            </div>
            <div class="seat-header-main">
                <div class="seat-header-avatar-box">
                    @php
                        $learnerProfilePic = $customer->learner->profile_picture ?? ($customer->profile_picture ?? null);
                    @endphp
                    @if($learnerProfilePic && file_exists(public_path($learnerProfilePic)))
                        <img id="topSeatAvatarImg" src="{{ asset($learnerProfilePic) }}" alt="{{ $customer->name }}" class="avatar-user-photo">
                    @elseif(isset($customer->image) && $customer->image)
                        <img id="topSeatAvatarImg" src="{{ asset($customer->image) }}" alt="Seat" class="avatar-seat-chair {{ $class }}">
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
                            Seat No : {{ getSeatDisplayShortFloorName($customer->seat_no) }} : {{ $customer->name ?? ($customer->learner->name ?? 'Learner') }}
                        @else
                            General Seat : {{ $customer->name ?? ($customer->learner->name ?? 'Learner') }}
                        @endif
                    </h3>
                    <div class="seat-meta-row">
                        <span class="seat-meta-item">
                            <i class="fa-regular fa-clock"></i> <strong>Old Shift / Plan:</strong> {{ $customer->plan_name ?? ($customer->plan_type_name ?? 'N/A') }}
                        </span>
                        @if($customer->plan_end_date)
                        <span class="seat-meta-item">
                            <i class="fa-regular fa-calendar-check"></i> <strong>Expired On:</strong> {{ \Carbon\Carbon::parse($customer->plan_end_date)->format('d M, Y') }}
                        </span>
                        @endif
                        @if($customer->learner && $customer->learner->learner_no)
                        <span class="seat-meta-item">
                            <i class="fa-regular fa-id-badge"></i> <strong>Learner UID:</strong> {{ $customer->learner->learner_no }}
                        </span>
                        @elseif($customer->learner_no)
                        <span class="seat-meta-item">
                            <i class="fa-regular fa-id-badge"></i> <strong>Learner UID:</strong> {{ $customer->learner_no }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. CARD: OLD PLAN REFERENCE (READ ONLY) --}}
        <div class="plan-card">
            <div class="plan-card-header header-navy">
                <div class="plan-header-left">
                    <div class="plan-header-icon">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div>
                        <h4 class="plan-header-title">Old Plan Summary</h4>
                        <p class="plan-header-subtitle">Historical reference of learner's previous subscription details.</p>
                    </div>
                </div>
            </div>
            <div class="plan-card-body">
                <div class="row g-3">
                    <div class="col-md-4 form-group">
                        <label class="form-label">Previous Plan</label>
                        <input class="form-control" value="{{ $customer->plan_name }}" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="form-label">Previous Shift</label>
                        <input class="form-control" value="{{ $customer->plan_type_name }}" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="form-label">Plan Price</label>
                        <input class="form-control" value="{{ $customer->plan_price_id }}" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="form-label">Plan Started On</label>
                        <input type="date" class="form-control" value="{{ $customer->plan_start_date }}" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="form-label">Plan Ended On</label>
                        <input type="date" class="form-control" value="{{ $customer->plan_end_date }}" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="form-label">Locker Allotted?</label>
                        <input class="form-control" value="{{ $hasLocker === 'yes' ? 'Yes' : 'No' }}" readonly>
                    </div>
                    @if($hasLocker === 'yes')
                    <div class="col-md-4 form-group">
                        <label class="form-label">Locker Amount</label>
                        <input type="text" class="form-control" value="{{ currentTransaction($customer->learner_detail_id)->locker_amount }}" readonly>
                    </div>
                    @endif
                    @if(optional(currentTransaction($customer->learner_detail_id))->discount_amount)
                    <div class="col-md-4 form-group">
                        <label class="form-label">Discount Amount</label>
                        <input type="text" class="form-control" value="{{ currentTransaction($customer->learner_detail_id)->discount_amount }}" readonly>
                    </div>
                    @endif
                    <div class="col-md-4 form-group">
                        <label class="form-label">Total Amount</label>
                        <input type="text" class="form-control" value="{{ currentTransaction($customer->learner_detail_id)->total_amount }}" readonly>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. FORM WRAPPER FOR REACTIVE PLAN --}}
        <form action="{{ route('learner.reactive.store', $customer->id) }}" method="POST" enctype="multipart/form-data" id="reactive">
            @csrf
            @method('PUT')

            <input id="user_id" type="hidden" name="user_id" value="{{$customer->id }}">
            <input type="hidden" name="learner_id" value="{{$customer->id }}">
            <input id="learner_detail" type="hidden" name="learner_detail" value="{{$customer->learner_detail_id }}">
            <input type="hidden" name="payment_type" value="REACTIVE" id="payment_type_operation">

            {{-- 4. CARD: ACTIVATE NEW PLAN --}}
            <div class="plan-card">
                <div class="plan-card-header header-green">
                    <div class="plan-header-left">
                        <div class="plan-header-icon">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                        <div>
                            <h4 class="plan-header-title">Activate New Plan</h4>
                            <p class="plan-header-subtitle">Reactivate learner with a fresh plan, seat allotment, and fee collection.</p>
                        </div>
                    </div>
                </div>
                <div class="plan-card-body">
                    <div class="plan-tip-box">
                        <i class="fa-solid fa-gem"></i>
                        <div>
                            <strong>Note:</strong> Here you can activate an existing learner into an available seat with a new plan.
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4 form-group">
                            <label class="form-label" for="plan_id10">Plan <span class="required-star">*</span></label>
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

                        <div class="col-md-4 form-group">
                            <label class="form-label" for="plan_type_id10">Plan Type (Shift) <span class="required-star">*</span></label>
                            <select id="plan_type_id10" class="form-select @error('plan_type_id') is-invalid @enderror" name="plan_type_id">
                                <option value="">Select Plan Type</option>
                                @foreach($planTypes as $planType)
                                <option value="{{ $planType->id }}"
                                    {{ old('plan_type_id', $customer->plan_type_id) == $planType->id ? 'selected' : '' }}>
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

                        <div class="col-md-4 form-group">
                            <label class="form-label" for="plan_price10">Plan Price <span class="required-star">*</span></label>
                            <input id="plan_price10" class="form-control @error('plan_price_id') is-invalid @enderror" name="plan_price_id" value="{{ old('plan_price_id') }}" readonly placeholder="Plan Price">
                            @error('plan_price_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="form-label" for="new_seat_id2">Select Seat <span class="required-star">*</span></label>
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

                        <div class="col-md-6 form-group">
                            <label class="form-label" for="start_date10">Plan Starts On <span class="required-star">*</span></label>
                            <input type="date" class="form-control @error('plan_start_date') is-invalid @enderror" placeholder="Plan Starts On" name="plan_start_date" id="start_date10" min="{{ \Carbon\Carbon::parse($customer->plan_end_date)->format('Y-m-d') }}" value="{{ old('plan_start_date', now()->lt(\Carbon\Carbon::parse($customer->plan_end_date)) ? \Carbon\Carbon::parse($customer->plan_end_date)->format('Y-m-d') : now()->format('Y-m-d')) }}">
                            @error('plan_start_date')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- 5. CARD: PLAN ADD-ON'S (COLLAPSIBLE) --}}
            <div class="plan-card">
                <div class="plan-card-header header-amber collapsible-header" data-bs-toggle="collapse" data-bs-target="#planAddonsCollapse" aria-expanded="false" aria-controls="planAddonsCollapse">
                    <div class="plan-header-left">
                        <div class="plan-header-icon">
                            <i class="fa-solid fa-cube"></i>
                        </div>
                        <div>
                            <h4 class="plan-header-title">Plan Add-on's</h4>
                            <p class="plan-header-subtitle">Configure optional locker allotment and discount preferences.</p>
                        </div>
                    </div>
                    <div class="plan-header-toggle">
                        <i class="fa-solid fa-chevron-down toggle-icon"></i>
                    </div>
                </div>
                <div class="collapse @if($errors->has('locker_amount') || $errors->has('locker_no') || $errors->has('discount_amount')) show @endif" id="planAddonsCollapse">
                    <div class="plan-card-body">
                        <div class="row g-3">
                            @if(!in_array('3', toggleHideField()) || (in_array('3', toggleHideField()) && ($hasLocker == 'yes')))
                            <div class="col-md-4 form-group {{ !is_locker() ? 'd-none' : '' }}">
                                <label class="form-label" for="toggleFieldCheckbox10">Locker?</label>
                                <select name="locker" id="toggleFieldCheckbox10" class="form-select">
                                    <option value="no" {{ old('locker', $hasLocker) === 'no' ? 'selected' : '' }}>No</option>
                                    <option value="yes" {{ old('locker', $hasLocker) === 'yes' ? 'selected' : '' }}>Yes, I Need a Locker</option>
                                </select>
                            </div>

                            <div class="col-md-4 form-group {{ !is_locker() ? 'd-none' : '' }}">
                                <label class="form-label" for="locker_amount10">Locker Amount <span class="required-star">*</span></label>
                                <input type="text" id="locker_amount10" class="form-control @error('locker_amount') is-invalid @enderror" name="locker_amount" placeholder="0.00" value="{{ old('locker_amount', $locker_amt) }}" readonly>
                                @error('locker_amount')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="col-md-4 form-group {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer2">
                                <label class="form-label" for="locker_no10">Locker No.</label>
                                <input type="text" class="form-control digit-only @error('locker_no') is-invalid @enderror" name="locker_no" id="locker_no10" placeholder="Enter Locker No." value="{{ old('locker_no', $customer->locker_no) }}" {{$locker_read ?? ''}}>
                                @error('locker_no')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            @endif

                            @if(!in_array('6', toggleHideField()) || (in_array('6', toggleHideField()) && $discountAmount))
                            <div class="col-md-6 form-group">
                                <label class="form-label" for="discountType10">Discount Type</label>
                                <select id="discountType10" class="form-select" name="discountType">
                                    <option value="">Select Discount Type</option>
                                    <option value="amount" {{ old('discountType', $selectedDiscountType) === 'amount' ? 'selected' : '' }}>Amount</option>
                                    <option value="percentage" {{ old('discountType', $selectedDiscountType) === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                </select>
                            </div>

                            <div class="col-md-6 form-group">
                                <label class="form-label" for="discount_amount10">Discount Amount (<span id="typeVal10">INR / %</span>)</label>
                                <input type="text" id="discount_amount10" class="form-control @error('discount_amount') is-invalid @enderror" placeholder="0.00" name="discount_amount" value="{{ old('discount_amount', currentTransaction($customer->learner_detail_id)->discount_amount ?? 0) }}" readonly>
                                @error('discount_amount')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- 6. CARD: PAYMENT & SETTLEMENT --}}
            <div class="plan-card">
                <div class="plan-card-header header-blue">
                    <div class="plan-header-left">
                        <div class="plan-header-icon">
                            <i class="fa-solid fa-calculator"></i>
                        </div>
                        <div>
                            <h4 class="plan-header-title">Payment &amp; Settlement</h4>
                            <p class="plan-header-subtitle">Review total calculation, due dates, and pending balance.</p>
                        </div>
                    </div>
                </div>
                <div class="plan-card-body">
                    <div class="row g-3">
                        {{-- ROW 1: Previous Pending Amount & Total Amount in Single Row --}}
                        <div class="col-md-6 form-group">
                            <label class="form-label" for="previous_pending10">Previous Pending Amount <span class="required-star">*</span></label>
                            <input type="text" class="form-control @error('previous_pending') is-invalid @enderror" name="previous_pending" id="previous_pending10" value="{{ old('previous_pending', totalPending($customer->id)) }}" readonly>
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="form-label" for="total_amount10">Total Amount <span class="required-star">*</span></label>
                            <input type="text" id="total_amount10" class="form-control @error('paid_amount') is-invalid @enderror" name="paid_amount" placeholder="0.00" value="{{ old('paid_amount') }}" {{ (Route::currentRouteName() == 'learner.change.plan' ) ? 'readonly' : '' }}>
                            @error('paid_amount')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                            <span id="chargeable_days10" class="text-info mt-1 d-block small"></span>
                        </div>

                        {{-- ROW 2: Pending Amount, Due Date & Payment Mode in Next Row --}}
                        <div class="col-md-4 form-group">
                            <label class="form-label" for="pending_amt10">Pending Amount <span class="required-star">*</span></label>
                            <input type="text" id="pending_amt10" class="form-control" placeholder="0" value="{{ old('pending_amount') }}" readonly>
                            <span id="pending_amt_error" class="text-danger small mt-1 d-block"></span>
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="form-label" for="due_date10">Choose Due Date <span class="required-star">*</span></label>
                            <input type="date" id="due_date10" class="form-control duedate @error('due_date') is-invalid @enderror" placeholder="Enter Due Date" name="due_date" readonly>
                            @error('due_date')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="form-label" for="payment_mode">Payment Mode <span class="required-star">*</span></label>
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
                </div>
            </div>

            {{-- 7. ACTION BUTTON BAR (OUTSIDE THE BOX) --}}
            <div class="form-action-bar">
                <a href="{{ route('learners') }}" class="btn-back-form">
                    <i class="fa-solid fa-arrow-left"></i> Cancel
                </a>
                <button type="submit" class="btn-submit-operation" id="submit">
                    <i class="fa-solid fa-bolt"></i> Reactivate Plan
                </button>
            </div>

        </form>

    </div>
</div>

<script>
$(document).ready(function () {
    $("#plan_start_date_edit").on("change", function () {
        let startDate = $(this).val();
        let totalDays = parseInt($("#total_days").val()); // inclusive days
        if (!startDate || !totalDays || totalDays < 1) return;
        let start = new Date(startDate);
        start.setDate(start.getDate() + (totalDays - 1));
        let yyyy = start.getFullYear();
        let mm = ("0" + (start.getMonth() + 1)).slice(-2);
        let dd = ("0" + start.getDate()).slice(-2);
        let formatted = `${yyyy}-${mm}-${dd}`;
        $("#plan_end_date_edit").val(formatted).trigger("change");
    });
});
</script>

@endsection
