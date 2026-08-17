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

<div id="imageViewModal" class="image-modal" style="display:none;">
    <div class="image-modal-content">
        <span class="close-modal">&times;</span>
        <img src="" id="modalImage">
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-9 order-2 order-md-1">
        <form id="changePlan" action="{{ $route }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('POST')

            <input type="hidden" name="learner_detail" value="{{ $customer->learner_detail_id }}">
            <input type="hidden" name="learner_id" value="{{ $customer->id }}">
            <input type="hidden" name="user_id" value="{{ $customer->id }}" id="user_id">
            <input type="hidden" name="library_id" value="{{ $customer->library_id }}">
            <input type="hidden" name="payment_type" value="CHANGE PLAN" id="payment_type_operation">
            <input type="hidden" id="start_date10" value="{{ $start_date }}">

            <div class="library-operations mt-4">
                {{-- 1. Learner Info --}}
                <div class="info__section">
                    <h4 class="inner-heading">Learner Info</h4>
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <label for="" class="text-white">Seat Owner Name <span>*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" placeholder="Full Name" name="name" id="name" value="{{ old('name', $customer->name) }}">
                            @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-6">
                            <label for="" class="text-white">DOB <span>*</span></label>
                            <input type="date" class="form-control @error('dob') is-invalid @enderror" placeholder="DOB" name="dob" id="dob" value="{{ old('dob', $customer->dob) }}">
                            @error('dob')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-6">
                            <label for="" class="text-white">Mobile Number <span>*</span></label>
                            <input type="text" class="form-control @error('mobile') is-invalid @enderror digit-only" maxlength="10" minlength="10" placeholder="Mobile Number" name="mobile" id="mobile" value="{{ old('mobile', $customer->mobile) }}">
                            @error('mobile')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-6">
                            <label for="" class="text-white">Email Id</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" placeholder="Email Id" name="email" id="email" value="{{ old('email', $customer->email) }}">
                            @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- 2. Change Plan Info --}}
                <div class="form-input mb-4">
                    <h4 class="inner-heading">Change Plan</h4>
                    <p class="text-danger">⚠️ <b>Note:</b> You can change the learner's plan & plan type (shift) within 7 days of seat booking. After that, use the upgrade plan option for any changes.</p>
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <label>Plan <span>*</span></label>
                            <select id="plan_id10" class="form-control form-select @error('plan_id') is-invalid @enderror" name="plan_id">
                                <option value="">Select Plan</option>
                                @foreach($plans as $key => $value)
                                <option value="{{ $value->id }}" {{ old('plan_id', $customer->plan_id) == $value->id ? 'selected' : '' }}>{{ $value->name }}</option>
                                @endforeach
                            </select>
                            @error('plan_id')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label>Plan Type (Shift) <span>*</span></label>
                            <select id="plan_type_id10" class="form-control form-select @error('plan_type_id') is-invalid @enderror" name="plan_type_id">
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

                        <div class="col-lg-4">
                            <label>Plan Price <span>*</span></label>
                            <input id="plan_price10" class="form-control @error('plan_price_id') is-invalid @enderror" value="{{ old('plan_price_id', $customer->plan_price_id) }}" name="plan_price_id" readonly>
                            @error('plan_price_id')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- 3. Plan Add-on's --}}
                <div class="form-input mb-4">
                    <h4 class="inner-heading">Plan Add-on's</h4>
                    <div>
                        <div class="row g-4">
                            @if(!in_array('3', toggleHideField()) || (in_array('3', toggleHideField()) && ($hasLocker == 'yes')))
                            <div class="col-lg-4 {{ !is_locker() ? 'd-none' : '' }}">
                                <label>Locker?</label>
                                <select name="locker" id="toggleFieldCheckbox10" class="form-control form-select">
                                    <option value="no" {{ old('locker', $hasLocker) === 'no' ? 'selected' : '' }}>No</option>
                                    <option value="yes" {{ old('locker', $hasLocker) === 'yes' ? 'selected' : '' }}>Yes, I Need a Locker</option>
                                </select>
                            </div>

                            <div class="col-lg-4 {{ !is_locker() ? 'd-none' : '' }}">
                                <label>Locker Amount<span>*</span></label>
                                <input type="text" id="locker_amount10" class="form-control @error('locker_amount') is-invalid @enderror" name="locker_amount" placeholder="0.00" value="{{ old('locker_amount', $locker_amt) }}" readonly>
                                @error('locker_amount')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-lg-4 col-6 {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer2">
                                <label for="locker_no">Locker No.</label>
                                <input type="text" class="form-control digit-only @error('locker_no') is-invalid @enderror" name="locker_no" id="locker_no10" placeholder="Enter Locker No." value="{{ old('locker_no', $customer->locker_no) }}" {{$locker_read}}>
                                @error('locker_no')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            @endif

                            @if(!in_array('6', toggleHideField()) || (in_array('6', toggleHideField()) && $discountAmount))
                            <div class="col-lg-6">
                                <label>Discount Type</label>
                                <select id="discountType10" class="form-control form-select" name="discountType">
                                    <option value="">Select Discount Type</option>
                                    <option value="amount" {{ old('discountType', $selectedDiscountType) == 'amount' ? 'selected' : '' }}>Amount</option>
                                    <option value="percentage" {{ old('discountType', $selectedDiscountType) == 'percentage' ? 'selected' : '' }}>Percentage</option>
                                </select>
                            </div>

                            <div class="col-lg-6">
                                <label>Discount Amount ( <span id="typeVal10">INR / %</span> )</label>
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

                {{-- 4. Payment & Settlement --}}
                <div class="form-input mb-4">
                    <h4 class="inner-heading">Payment &amp; Settlement</h4>
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <label for="">Previous Paid Amount <span>*</span></label>
                            <input type="text" class="form-control @error('previous_amount') is-invalid @enderror"
                                name="previous_amount" id="previous_amount10"
                                value="{{ (float) currentTransaction($customer->learner_detail_id)->paid_amount }}" readonly>
                            @error('previous_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label for="">Previous Pending Amount <span>*</span></label>
                            <input type="text" class="form-control @error('previous_pending') is-invalid @enderror" name="previous_pending" id="previous_pending10" value="{{ old('previous_pending', totalPending($customer->id)) }}" readonly>
                        </div>

                        <div class="col-lg-4">
                            <label>New Total Amount <span>*</span></label>
                            <input type="text" id="total_amount10" class="form-control @error('paid_amount') is-invalid @enderror" name="paid_amount" value="{{ old('paid_amount', optional(currentTransaction($customer->learner_detail_id))->total_amount) }}" readonly>
                            @error('paid_amount')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                            <span id="chargeable_days10" class="text-info"></span>
                        </div>

                        <div class="col-lg-4">
                            <label id="refund_pay_timing_label10" for="refund_pay_timing10">{{ $whenLabel }} <span>*</span></label>
                            <select id="refund_pay_timing10" name="refund_pay_timing" class="form-control form-select @error('refund_pay_timing') is-invalid @enderror">
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

                        <div class="col-lg-4">
                            <label for="diffrence_amount10" id="diffrence_amount_label10">{{ $diffLabel }} <span>*</span></label>
                            <input type="text" class="form-control @error('diffrence_amount') is-invalid @enderror"
                                name="diffrence_amount" id="diffrence_amount10" data-sign="{{ $diffSign }}" value="{{ $diffAbs }}" placeholder="0.00">
                            @error('diffrence_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label for="pending_amt10">{{ $pendingLabel }} <span>*</span></label>
                            <input type="text" id="pending_amt10" class="form-control" name="pending_amount" placeholder="0" value="{{ $pendingAbs }}" readonly>
                            <span id="pending_amt_error" class="text-danger"></span>
                        </div>

                        <div class="col-lg-4">
                            <label for="">Choose Due Date</label>
                            <input type="date" id="due_date10" class="form-control duedate @error('due_date') is-invalid @enderror" placeholder="Enter Due Date" name="due_date" readonly>
                            @error('due_date')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label>Payment Mode <span>*</span></label>
                            <select name="payment_mode" id="payment_mode10" class="form-control form-select @error('payment_mode') is-invalid @enderror">
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

                <div class="d-flex justify-content-end pt-3 border-top">
                    @if(!$today->greaterThanOrEqualTo($oneWeekLater))
                        <button type="submit" class="btn btn-primary button px-4">Update Plan</button>
                    @else
                        <p class="text-danger mb-0"><b>*</b> Change Plan is only available within 7 days of seat booking.</p>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Right Side: Seat Badge --}}
    <div class="col-lg-3 order-1 order-md-2">
        <div class="seatnumber">
            <img src="{{ asset($customer->image) }}" alt="Seat" class="py-3 {{$class}}" style="width:60px; display:block; margin:0 auto;">
            @if($customer->seat_no)
            <span class="d-block ">Seat No : {{ getSeatDisplayByMainNo($customer->seat_no)}}</span>
            @else
            <span class="d-block ">General</span>
            @endif
            <div class="seat--plan">{{ $customer->plan_type_name}}</div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
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