@extends('layouts.library')
@section('content')

@php
$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class=$planDetails['class'];

if (Route::currentRouteName() == 'learner.renew.plan') {
    $displayNone = 'style="display: none;"';
    $readonlyStyle = 'pointer-events: none; background-color: #e9ecef;';
} else {
    $displayNone = '';
    $readonlyStyle = '';
}
if($customer->locker_no){
    $locker_read='';
}else{
    $locker_read='readonly';
}

$route=route('learner.upgrade.renew.store');
$ids= Route::currentRouteName() == 'learner.renew.plan' ? 'renewSeat' : 'learnerUpgrade';
$paymentType = Route::currentRouteName() == 'learner.renew.plan' ? 'RENEW' : 'UPGRADE';
$start_date = \Carbon\Carbon::parse($customer->plan_end_date)->addDay()->format('Y-m-d');
$pageTitle = Route::currentRouteName() == 'learner.renew.plan' ? 'Renew Plan' : 'Upgrade Plan';
@endphp

{{-- Scoped CSS for Learner Upgrade Plan Module --}}
<link rel="stylesheet" href="{{ asset('public/css/learner-upgrade-plan.css') }}?v={{ time() }}">

<div class="learner-upgrade-plan-module">
    <div class="upgrade-plan-wrapper">

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
                            <i class="fa-regular fa-clock"></i> <strong>Current Shift / Plan:</strong> {{ $customer->plan_name ?? ($customer->plan_type_name ?? 'N/A') }}
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
                        @elseif($customer->learner_no)
                        <span class="seat-meta-item">
                            <i class="fa-regular fa-id-badge"></i> <strong>Learner UID:</strong> {{ $customer->learner_no }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- FORM WRAPPER --}}
        <form action="{{$route}}" method="POST" enctype="multipart/form-data" id="{{$ids}}">
            @csrf
            @method('POST')

            <input type="hidden" name="learner_detail" value="{{$customer->learner_detail_id }}">
            <input type="hidden" name="learner_id" value="{{ $customer->id}}" >
            <input type="hidden" name="user_id" value="{{ $customer->id}}" id="user_id">
            <input type="hidden" name="library_id" value="{{ $customer->library_id}}">
            <input type="hidden" name="payment_type" value="{{ $paymentType }}" id="payment_type_operation">
            <input type="hidden" id="start_date10" value="{{$start_date}}">

            {{-- 2. CARD: PLAN SELECTION --}}
            <div class="plan-card">
                <div class="plan-card-header header-green">
                    <div class="plan-header-left">
                        <div class="plan-header-icon">
                            <i class="fa-solid fa-circle-up"></i>
                        </div>
                        <div>
                            <h4 class="plan-header-title">{{ $pageTitle }}</h4>
                            <p class="plan-header-subtitle">Choose the upcoming plan and shift type for renewal/upgrade.</p>
                        </div>
                    </div>
                </div>
                <div class="plan-card-body">
                    <div class="plan-tip-box">
                        <i class="fa-solid fa-gem"></i>
                        <div>
                            @if(Route::currentRouteName() == 'learner.renew.plan')
                            <strong>Note:</strong> You can renew your plan 5 days before it expires or during the extended period. Plan and shift changes are not allowed.
                            @else
                            <strong>Note:</strong> You can upgrade your plan &amp; plan type (shift) 5 days before it expires or during the extended period.
                            @endif
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4 form-group">
                            <label class="form-label" for="plan_id10">Plan <span class="required-star">*</span></label>
                            <select id="plan_id10" class="form-select @error('plan_id') is-invalid @enderror" name="plan_id" {{ (Route::currentRouteName() == 'learner.renew.plan') ? 'readonly' : '' }}>
                                <option value="">Select Plan</option>
                                @foreach($plans as $key => $value)
                                <option value="{{ $value->id }}" {{ old('plan_id', $customer->plan_id) == $value->id ? 'selected' : '' }}>{{ $value->name }}</option>
                                @endforeach
                            </select>
                            @error('plan_id')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="form-label" for="plan_type_id10">Plan Type (Shift) <span class="required-star">*</span></label>
                            <select id="plan_type_id10" class="form-select @error('plan_type_id') is-invalid @enderror" name="plan_type_id" {{ Route::currentRouteName() == 'learner.renew.plan' ? 'readonly' : '' }}>
                                @foreach($filteredPlanTypes as $planType)
                                <option value="{{ $planType['id'] }}"
                                    {{ ($customer->plan_type_id == $planType['id']) ? 'selected' : (old('plan_type_id') == $planType['id'] ? 'selected' : '') }}>
                                    {{ $planType['name'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('plan_type_id')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="form-label" for="plan_price10">Plan Price <span class="required-star">*</span></label>
                            <input id="plan_price10" class="form-control @error('plan_price_id') is-invalid @enderror" value="{{ old('plan_price_id', $customer->plan_price_id) }}" name="plan_price_id" readonly>
                            @error('plan_price_id')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. CARD: PLAN ADD-ON'S (COLLAPSIBLE) --}}
            <div class="plan-card">
                <div class="plan-card-header header-amber collapsible-header" data-bs-toggle="collapse" data-bs-target="#planAddonsCollapse" aria-expanded="false" aria-controls="planAddonsCollapse">
                    <div class="plan-header-left">
                        <div class="plan-header-icon">
                            <i class="fa-solid fa-cube"></i>
                        </div>
                        <div>
                            <h4 class="plan-header-title">Plan Add-on's</h4>
                            <p class="plan-header-subtitle">Configure locker allotment and discount preferences.</p>
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
                                <input type="text" class="form-control digit-only @error('locker_no') is-invalid @enderror" name="locker_no" id="locker_no10" placeholder="Enter Locker No." value="{{ old('locker_no', $customer->locker_no) }}" {{$locker_read}}>
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
                                    <option value="amount" {{ old('discountType', $selectedDiscountType) == 'amount' ? 'selected' : '' }}>Amount</option>
                                    <option value="percentage" {{ old('discountType', $selectedDiscountType) == 'percentage' ? 'selected' : '' }}>Percentage</option>
                                </select>
                            </div>

                            <div class="col-md-6 form-group">
                                <label class="form-label" for="discount_amount10">Discount Amount (<span id="typeVal10">INR / %</span>)</label>
                                <input type="text" id="discount_amount10" class="form-control @error('discount_amount') is-invalid @enderror" placeholder="0.00" name="discount_amount"
                                value="{{ old('discount_amount', currentTransaction($customer->learner_detail_id)->discount_amount ?? 0) }}" readonly>
                                @error('discount_amount')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. CARD: PAYMENT & PENDING DETAILS --}}
            <div class="plan-card">
                <div class="plan-card-header header-navy">
                    <div class="plan-header-left">
                        <div class="plan-header-icon">
                            <i class="fa-solid fa-calculator"></i>
                        </div>
                        <div>
                            <h4 class="plan-header-title">Payment &amp; Settlement</h4>
                            <p class="plan-header-subtitle">Review new total amount, pending status, and choose payment mode.</p>
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
                            <input type="text" id="total_amount10" class="form-control @error('paid_amount') is-invalid @enderror" name="paid_amount" value="{{ old('paid_amount', optional(currentTransaction($customer->learner_detail_id))->total_amount) }}">
                            @error('paid_amount')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                            <span id="chargeable_days10" class="text-info mt-1 d-block small"></span>
                        </div>

                        {{-- ROW 2: Pending Amount, Due Date & Payment Mode in Next Row --}}
                        <div class="col-md-4 form-group">
                            <label class="form-label" for="pending_amt10">Pending Amount <span class="required-star">*</span></label>
                            <input type="text" id="pending_amt10" class="form-control" name="pending_amount" placeholder="0" value="{{ old('pending_amount') }}" readonly>
                            <span id="pending_amt_error" class="text-danger small mt-1 d-block"></span>
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="form-label" for="due_date10">Choose Due Date</label>
                            <input type="date" id="due_date10" class="form-control duedate @error('due_date') is-invalid @enderror" placeholder="Enter Due Date" name="due_date" readonly>
                            @error('due_date')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="form-label" for="payment_mode10">Payment Mode <span class="required-star">*</span></label>
                            <select name="payment_mode" id="payment_mode10" class="form-select @error('payment_mode') is-invalid @enderror">
                                <option value="">Select Payment Mode</option>
                                <option value="1" {{ $customer->payment_mode == 1 ? 'selected' : '' }}>Online</option>
                                <option value="2" {{ $customer->payment_mode == 2 ? 'selected' : '' }}>Offline</option>
                                <option value="3" {{ $customer->payment_mode == 3 ? 'selected' : '' }}>Pay Later</option>
                            </select>
                            @error('payment_mode')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- 5. ACTION BUTTON BAR (OUTSIDE THE BOX) --}}
            <div class="form-action-bar">
                <a href="{{ route('learners') }}" class="btn-back-form">
                    <i class="fa-solid fa-arrow-left"></i> Cancel
                </a>
                @if($planDetails['diff_in_days'] <= 5 && $planDetails['diff_extend_day']>= 0 && !$is_renew && !$isalreadyRenew)
                    <button type="submit" class="btn-submit-operation" id="upgradePlanSubmit">
                        <i class="fa-solid fa-circle-up"></i> {{ $pageTitle }}
                    </button>
                @else
                    <div class="text-danger fw-semibold">
                        <i class="fa-solid fa-triangle-exclamation"></i> Button is available when you renew your Seat Booking
                    </div>
                @endif
            </div>

        </form>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        handleFormChanges('{{ $ids }}', {{ $customer->id }});
    });
</script>

@endsection
