@extends('layouts.library')
@section('content')

@php
$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class = $planDetails['class'];

if ($customer->locker_no) {
    $locker_read = '';
} else {
    $locker_read = 'readonly';
}

$paymentType = 'CHANGE PLAN';
$route = route('learners.update.changePlan', $customer->id);
$ids = 'changePlan';
$start_date = \Carbon\Carbon::parse($customer->plan_start_date)->format('Y-m-d');

// diffrence_amount is submitted signed (negative = refund, positive = pay) to match
// LearnerOperationService's CHANGE PLAN branch, but is shown to the user as a plain
// positive magnitude - the sign is tracked separately via data-sign.
$oldDiff = old('diffrence_amount');
$diffSign = ($oldDiff !== null && $oldDiff !== '' && (float) $oldDiff < 0) ? -1 : 1;
$diffAbs = ($oldDiff !== null && $oldDiff !== '') ? abs((float) $oldDiff) : '';
$diffLabel = $diffSign < 0 ? 'Amount to Refund' : 'Amount to pay';

$oldPending = old('pending_amount');
$pendingSign = ($oldPending !== null && $oldPending !== '' && (float) $oldPending < 0) ? -1 : 1;
$pendingAbs = ($oldPending !== null && $oldPending !== '') ? abs((float) $oldPending) : '';
$pendingLabel = $pendingSign < 0 ? 'Pending Refund Amount' : 'Pending Amount';
$whenLabel = $pendingSign < 0 ? 'When do you want to refund this amount' : 'When do you want to pay this amount';
@endphp

{{-- Scoped CSS for Learner Change Plan Module --}}
<link rel="stylesheet" href="{{ asset('public/css/learner-change-plan.css') }}?v={{ time() }}">

<div id="imageViewModal" class="image-modal" style="display:none;">
    <div class="image-modal-content">
        <span class="close-modal">&times;</span>
        <img src="" id="modalImage">
    </div>
</div>

<div class="learner-change-plan-module">
    <div class="change-plan-wrapper">

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
                            <i class="fa-regular fa-clock"></i> <strong>Current Shift / Plan:</strong> {{ $customer->plan_name ?? ($customer->plan_type_name ?? ($customer->plan->name ?? 'N/A')) }}
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
        <form id="changePlan" action="{{ $route }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('POST')

            <input type="hidden" name="learner_detail" value="{{ $customer->learner_detail_id }}">
            <input type="hidden" name="learner_id" value="{{ $customer->id }}">
            <input type="hidden" name="user_id" value="{{ $customer->id }}" id="user_id">
            <input type="hidden" name="library_id" value="{{ $customer->library_id }}">
            <input type="hidden" name="payment_type" value="CHANGE PLAN" id="payment_type_operation">
            <input type="hidden" id="start_date10" value="{{ $start_date }}">

            <input type="hidden" name="name" value="{{ $customer->name }}">
            <input type="hidden" name="dob" value="{{ $customer->dob }}">
            <input type="hidden" name="mobile" value="{{ $customer->mobile }}">
            <input type="hidden" name="email" value="{{ $customer->email }}">

            {{-- 2. CARD: CHANGE PLAN SELECTION --}}
            <div class="plan-card">
                <div class="plan-card-header header-green">
                    <div class="plan-header-left">
                        <div class="plan-header-icon">
                            <i class="fa-solid fa-arrows-rotate"></i>
                        </div>
                        <div>
                            <h4 class="plan-header-title">Change Plan</h4>
                            <p class="plan-header-subtitle">Select the new plan duration and shift type.</p>
                        </div>
                    </div>
                </div>
                <div class="plan-card-body">
                    <div class="plan-tip-box">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <div>
                            <strong>Note:</strong> You can change the learner's plan &amp; plan type (shift) within 7 days of seat booking. After that, use the upgrade plan option for any changes.
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4 form-group">
                            <label class="form-label" for="plan_id10">Plan <span class="required-star">*</span></label>
                            <select id="plan_id10" class="form-select @error('plan_id') is-invalid @enderror" name="plan_id">
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
                            <select id="plan_type_id10" class="form-select @error('plan_type_id') is-invalid @enderror" name="plan_type_id">
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

            {{-- 4. CARD: PLAN ADD-ON'S (COLLAPSED) --}}
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

            {{-- 4. CARD: PAYMENT & SETTLEMENT --}}
            <div class="plan-card">
                <div class="plan-card-header header-navy">
                    <div class="plan-header-left">
                        <div class="plan-header-icon">
                            <i class="fa-solid fa-calculator"></i>
                        </div>
                        <div>
                            <h4 class="plan-header-title">Payment &amp; Settlement</h4>
                            <p class="plan-header-subtitle">Review previous calculations, difference amount, and finalize payment.</p>
                        </div>
                    </div>
                </div>
                <div class="plan-card-body">
                    <div class="row g-3">
                        {{-- ROW 1: Previous Paid Amount & Total Amount in SINGLE ROW --}}
                        <div class="col-md-6 form-group">
                            <label class="form-label" for="previous_amount10">Previous Paid Amount <span class="required-star">*</span></label>
                            <input type="text" class="form-control @error('previous_amount') is-invalid @enderror"
                                name="previous_amount" id="previous_amount10"
                                value="{{ (float) currentTransaction($customer->learner_detail_id)->paid_amount }}" readonly>
                            @error('previous_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="form-label" for="total_amount10">Total Amount <span class="required-star">*</span></label>
                            <input type="text" id="total_amount10" class="form-control @error('paid_amount') is-invalid @enderror" name="paid_amount" value="{{ old('paid_amount', optional(currentTransaction($customer->learner_detail_id))->total_amount) }}" readonly>
                            @error('paid_amount')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                            <span id="chargeable_days10" class="text-info mt-1 d-block small"></span>
                        </div>
                        <input type="hidden" name="previous_pending" id="previous_pending10" value="{{ old('previous_pending', totalPending($customer->id)) }}">

                    </div>
                </div>
            </div>

            {{-- 5. CARD: ACTION --}}
            <div class="plan-card">
                <div class="plan-card-header header-blue">
                    <div class="plan-header-left">
                        <div class="plan-header-icon">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                        <div>
                            <h4 class="plan-header-title">Action</h4>
                            <p class="plan-header-subtitle">Schedule payment timing and complete the transaction.</p>
                        </div>
                    </div>
                </div>
                <div class="plan-card-body">
                    <div class="row g-3">
                        {{-- When do you want to pay this amount * IN FULL WIDTH --}}
                        <div class="col-12 form-group">
                            <label class="form-label" id="refund_pay_timing_label10" for="refund_pay_timing10">{{ $whenLabel }} <span class="required-star">*</span></label>
                            <select id="refund_pay_timing10" name="refund_pay_timing" class="form-select @error('refund_pay_timing') is-invalid @enderror">
                                <option value="">Select</option>
                                <option value="now" {{ old('refund_pay_timing') == 'now' ? 'selected' : '' }}>Now</option>
                                <option value="later" {{ old('refund_pay_timing') == 'later' ? 'selected' : '' }}>Later</option>
                            </select>
                            @error('refund_pay_timing')
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        {{-- Amount to Pay (Difference Amount) in Action Section --}}
                        <div class="col-12 form-group diff-amount-col">
                            <label class="form-label" for="diffrence_amount10" id="diffrence_amount_label10">{{ $diffLabel }} <span class="required-star">*</span></label>
                            <input type="text" class="form-control @error('diffrence_amount') is-invalid @enderror"
                                name="diffrence_amount" id="diffrence_amount10" data-sign="{{ $diffSign }}" value="{{ $diffAbs }}" placeholder="0.00">
                            @error('diffrence_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        {{-- Pending, Due Date, Payment Mode in 3 equal columns --}}
                        <div class="col-md-4 form-group">
                            <label class="form-label" for="pending_amt10">{{ $pendingLabel }} <span class="required-star">*</span></label>
                            <input type="text" id="pending_amt10" class="form-control" name="pending_amount" placeholder="0" value="{{ $pendingAbs }}" readonly>
                            <span id="pending_amt_error" class="text-danger small mt-1 d-block"></span>
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="form-label" for="due_date10">Choose Due Date</label>
                            <input type="text" id="due_date10" class="form-control duedate @error('due_date') is-invalid @enderror" placeholder="YYYY-MM-DD" name="due_date" value="{{ old('due_date', $customer->due_date ?? '') }}" readonly autocomplete="off">
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

            {{-- 6. ACTION BUTTON BAR (OUTSIDE THE BOX) --}}
            <div class="form-action-bar">
                <a href="{{ route('learners') }}" class="btn-back-form">
                    <i class="fa-solid fa-arrow-left"></i> Cancel
                </a>
                @if(!$today->greaterThanOrEqualTo($oneWeekLater))
                    <button type="submit" class="btn-submit-operation" id="changePlanSubmit">
                        <i class="fa-solid fa-arrows-rotate"></i> Update Plan
                    </button>
                @else
                    <div class="text-danger fw-semibold">
                        <i class="fa-solid fa-triangle-exclamation"></i> Change Plan is only available within 7 days of seat booking.
                    </div>
                @endif
            </div>

        </form>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof flatpickr !== 'undefined') {
            flatpickr("#due_date10", {
                dateFormat: "Y-m-d",
                minDate: "today",
                disableMobile: "true",
                allowInput: true
            });
        }

        handleFormChanges('changePlan', {{ $customer->id }});

        // Amount to Refund/pay and Pending are derived from the plan already selected on
        // this page, so compute them immediately on load too - not just after the user
        // re-touches a field - otherwise the page shows 0.00 even though a real
        // refund/due already exists. Skip when old('diffrence_amount') repopulated the
        // field already (validation-failure redirect), so we don't clobber that value.
        if (!$('#diffrence_amount10').val()) {
            calculatePaidAmount();
            const $diffField = $('#diffrence_amount10');
            const sign = parseFloat($diffField.attr('data-sign')) || 1;
            const absVal = Math.abs(parseFloat($diffField.val()) || 0);
            calculatePending(sign * absVal);
        }

        // The diffrence_amount field only ever displays a positive magnitude to the
        // user; re-apply the tracked sign (negative = refund) right before submit so
        // LearnerOperationService still receives the signed value it expects.
        const formElement = document.getElementById('changePlan');
        if (formElement) {
            formElement.addEventListener('submit', function() {
                const diffField = document.getElementById('diffrence_amount10');
                if (diffField) {
                    const sign = parseFloat(diffField.getAttribute('data-sign')) || 1;
                    const absVal = Math.abs(parseFloat(diffField.value) || 0);
                    diffField.value = (sign * absVal).toFixed(2);
                }
            });
        }
    });
</script>

@endsection