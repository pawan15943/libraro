@extends('layouts.library')
@section('content')

@php
$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class = $planDetails['class'];

$planEndDate = $customer->plan_end_date;
$today = \Carbon\Carbon::today();

if ($planEndDate) {
    $endDate = \Carbon\Carbon::parse($planEndDate);
    $diffInDays = $today->diffInDays($endDate, false);
    $extendDays = function_exists('getExtendDays') ? getExtendDays() : 0;
    $inextendDate = $endDate->copy()->addDays($extendDays);
    $diffExtendDay = $today->diffInDays($inextendDate, false);

    if ($diffInDays < 0 && $diffExtendDay < 0) {
        $statusText = 'Expired ' . abs($diffInDays) . ' days ago';
        $statusClass = 'status-expired';
        $statusIcon = 'fa-solid fa-circle-xmark';
    } elseif ($diffInDays < 0 && $diffExtendDay > 0) {
        $statusText = 'Extension: ' . abs($diffExtendDay) . ' days left';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-clock-rotate-left';
    } elseif ($diffInDays < 0 && $diffExtendDay == 0) {
        $statusText = 'Extension ends today';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-triangle-exclamation';
    } elseif ($diffInDays == 0) {
        $statusText = 'Expires today';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-triangle-exclamation';
    } elseif ($diffInDays == 1) {
        $statusText = 'Expires in 1 day';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-clock';
    } elseif ($diffInDays <= 5) {
        $statusText = 'Expires in ' . $diffInDays . ' days';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-clock';
    } else {
        $statusText = 'Active (Expires in ' . $diffInDays . ' days)';
        $statusClass = 'status-active';
        $statusIcon = 'fa-solid fa-circle-check';
    }
} else {
    $statusText = 'Active';
    $statusClass = 'status-active';
    $statusIcon = 'fa-solid fa-circle-check';
}

if (Route::currentRouteName() == 'learner.renew.plan') {
    $displayNone = 'style="display: none;"';
    $readonlyStyle = 'pointer-events: none; background-color: #e9ecef;';
} else {
    $displayNone = '';
    $readonlyStyle = '';
}
if ($customer->locker_no) {
    $locker_read = '';
} else {
    $locker_read = 'readonly';
}

$route = route('learner.upgrade.renew.store');
$ids = Route::currentRouteName() == 'learner.renew.plan' ? 'renewSeat' : 'learnerUpgrade';
$paymentType = Route::currentRouteName() == 'learner.renew.plan' ? 'RENEW' : 'UPGRADE';
$start_date = \Carbon\Carbon::parse($customer->plan_end_date)->addDay()->format('Y-m-d');
$pageTitle = Route::currentRouteName() == 'learner.renew.plan' ? 'Renew Plan' : 'Upgrade Plan';
@endphp

{{-- Scoped CSS for Learner Upgrade Plan Module --}}
@php $opIcon = Route::currentRouteName() == 'learner.renew.plan' ? 'refresh-cw' : 'circle-arrow-up'; $opKicker = Route::currentRouteName() == 'learner.renew.plan' ? 'Renew plan' : 'Upgrade plan'; $opTitle = Route::currentRouteName() == 'learner.renew.plan' ? 'Renew :name’s seat for the next term' : 'Move :name to a new plan or shift'; @endphp
<link rel="stylesheet" href="{{ asset('public/css/lb-swap.css') }}?v={{ @filemtime(public_path('css/lb-swap.css')) }}" />
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}" />
<link rel="stylesheet" href="{{ asset('public/css/lb-opplan.css') }}?v={{ @filemtime(public_path('css/lb-opplan.css')) }}" />
<link rel="stylesheet" href="{{ asset('public/css/lb-opui.css') }}?v={{ @filemtime(public_path('css/lb-opui.css')) }}" />

<div class="lsw lop" id="operationContent" data-op="{{ Route::currentRouteName() == 'learner.renew.plan' ? 'renew' : 'upgrade' }}"
     data-cur-plan="{{ $customer->plan_name ?? optional($customer->plan ?? null)->name }}"
     data-cur-shift="{{ $customer->plan_type_name ?? optional($customer->plantype ?? null)->name }}"
     data-cur-start="{{ $customer->plan_start_date }}" data-cur-end="{{ $customer->plan_end_date }}"
     data-new-start="{{ $start_date }}">
    <div class="lop-grid">
        <div class="lsw-sheet lop-main">
            @include('learner.partials.op-head')
            <div class="lsw-body lop-form-body">
        <form action="{{ $route }}" method="POST" enctype="multipart/form-data" id="{{ $ids }}">
            @csrf
            @method('POST')

            <input type="hidden" name="learner_detail" value="{{ $customer->learner_detail_id }}">
            <input type="hidden" name="learner_id" value="{{ $customer->id }}" >
            @include('learner.partials.extra-shifts-carry', ['learnerId' => $customer->id])
            <input type="hidden" name="user_id" value="{{ $customer->id }}" id="user_id">
            <input type="hidden" name="library_id" value="{{ $customer->library_id }}">
            <input type="hidden" name="payment_type" value="{{ $paymentType }}" id="payment_type_operation">
            <input type="hidden" id="start_date10" value="{{ $start_date }}">

            <div class="upgrade-plan-cards-column">

                {{-- 2. CARD: PLAN SELECTION --}}
                <div class="plan-card">
                    <div class="plan-card-header header-green">
                        <div class="plan-header-left">
                            <div class="plan-header-icon">
                                <i class="fa-solid fa-circle-up"></i>
                            </div>
                            <div>
                                <h4 class="plan-header-title">Plan &amp; shift</h4>
                            </div>
                        </div>
                    </div>
                    <div class="plan-card-body">
                        <div class="row g-2">
                            <div class="col-md-4 form-group">
                                <label class="form-label" for="plan_id10">Plan <span class="required-star">*</span></label>
                                <select id="plan_id10" class="form-select @error('plan_id') is-invalid @enderror" name="plan_id" {{ (Route::currentRouteName() == 'learner.renew.plan') ? 'readonly' : '' }}>
                                    <option value="">Select Plan</option>
                                    @foreach($plans as $key => $value)
                                    <option value="{{ $value->id }}" {{ old('plan_id', $customer->plan_id) == $value->id ? 'selected' : '' }}>{{ $value->name }}</option>
                                    @endforeach
                                    @include('learner.partials.custom-plan-options', ['currentPlanId' => $customer->plan_id])
                                </select>
                                @error('plan_id')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="col-md-4 form-group">
                                <label class="form-label" for="plan_type_id10">Shift <span class="required-star">*</span></label>
                                <select id="plan_type_id10" class="form-select @error('plan_type_id') is-invalid @enderror" name="plan_type_id" {{ Route::currentRouteName() == 'learner.renew.plan' ? 'readonly' : '' }}>
                                    @include('learner.partials.plan-shift-options', ['available' => $filteredPlanTypes, 'selected' => $customer->plan_type_id, 'seatNo' => $customer->seat_no])
                                </select>
                                @error('plan_type_id')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="col-md-4 form-group">
                                <label class="form-label" for="plan_price10">Price <span class="required-star">*</span></label>
                                <input id="plan_price10" class="form-control price-highlight-input @error('plan_price_id') is-invalid @enderror" value="{{ old('plan_price_id') !== null ? (int) round((float) old('plan_price_id')) : ((float)$customer->plan_price_id ? (int) round((float)$customer->plan_price_id) : '') }}" name="plan_price_id" readonly>
                                @error('plan_price_id')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-12">
                                @include('learner.partials.custom-plan-dates', ['select' => '#plan_id10', 'start' => '#start_date10', 'price' => '#plan_price10', 'locked' => Route::currentRouteName() == 'learner.renew.plan', 'startValue' => $start_date])
                            </div>
                            @if(Route::currentRouteName() != 'learner.renew.plan')
                            <div class="col-12">
                                @include('learner.partials.op-shift-extras', ['learnerId' => $customer->id, 'seatNo' => $customer->seat_no, 'available' => $filteredPlanTypes])
                            </div>
                            @endif
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
                                <h4 class="plan-header-title">Locker &amp; discount</h4>
                            </div>
                        </div>
                        <div class="plan-header-toggle">
                            <i class="fa-solid fa-chevron-down toggle-icon"></i>
                        </div>
                    </div>
                    <div class="collapse @if($errors->has('locker_amount') || $errors->has('locker_no') || $errors->has('discount_amount')) show @endif" id="planAddonsCollapse">
                        <div class="plan-card-body">
                            <div class="row g-2">
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
                                    <input type="text" id="locker_amount10" class="form-control @error('locker_amount') is-invalid @enderror" name="locker_amount" placeholder="0" value="{{ old('locker_amount') !== null ? (int) round((float) old('locker_amount')) : ((float)$locker_amt ? (int) round((float)$locker_amt) : '0') }}" readonly>
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
                                    <input type="text" id="discount_amount10" class="form-control @error('discount_amount') is-invalid @enderror" placeholder="0" name="discount_amount"
                                    value="{{ old('discount_amount') !== null ? (int) round((float) old('discount_amount')) : (int) round((float) ($discountAmount ?? 0)) }}" readonly>
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
                                <h4 class="plan-header-title">Payment</h4>
                            </div>
                        </div>
                    </div>
                    <div class="plan-card-body">
                        <div class="row g-2 mb-2">
                            @include('learner.partials.advance-toggle', ['learnerId' => $customer->id])
                            <div class="col-12">@include('learner.partials.extra-as')</div>
                            {{-- ROW 1: Previous Pending Amount & Total Amount --}}
                            <div class="col-md-6 form-group">
                                <label class="form-label" for="previous_pending10">Previous Pending Amount <span class="required-star">*</span></label>
                                <input type="text" class="form-control @error('previous_pending') is-invalid @enderror" name="previous_pending" id="previous_pending10" value="{{ old('previous_pending') !== null ? (int) round((float) old('previous_pending')) : (int) round((float) totalPending($customer->id)) }}" readonly>
                            </div>

                            <div class="col-md-6 form-group">
                                <label class="form-label" for="total_amount10">Total Amount <span class="required-star">*</span></label>
                                <input type="text" id="total_amount10" class="form-control price-highlight-input @error('paid_amount') is-invalid @enderror" name="paid_amount" value="{{ old('paid_amount') !== null ? (int) round((float) old('paid_amount')) : (optional(currentTransaction($customer->learner_detail_id))->total_amount ? (int) round((float) optional(currentTransaction($customer->learner_detail_id))->total_amount) : 0) }}">
                                @error('paid_amount')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                                <span id="chargeable_days10" class="text-info mt-1 d-block small"></span>
                            </div>
                        </div>

                        {{-- ROW 2: Symmetrical Shift-Proof Architecture (Pending, Due Date, Payment Mode & Notice) --}}
                        <div class="row g-2 action-settlement-row">
                            <div class="col-md-4 form-group" id="pendingAmtCol10">
                                <label class="form-label" for="pending_amt10">Pending Amount <span class="required-star">*</span></label>
                                <input type="text" id="pending_amt10" class="form-control highlight-pending-input" name="pending_amount" placeholder="0" value="{{ old('pending_amount') !== null ? (int) round((float) old('pending_amount')) : 0 }}" readonly>
                                <span id="pending_amt_error" class="text-danger small mt-1 d-block"></span>
                            </div>

                            <div class="col-md-4 form-group" id="dueDateCol10">
                                <label class="form-label" for="due_date10">Choose Due Date <span class="required-star" id="due_date_star" style="{{ (old('payment_mode') == '3' || (float)old('pending_amount', 0) > 0) ? '' : 'display: none;' }}">*</span></label>
                                <div class="date-picker-input-wrap position-relative">
                                    <input type="text" id="due_date10" class="form-control duedate @error('due_date') is-invalid @enderror" placeholder="YYYY-MM-DD" name="due_date" value="{{ old('due_date', $customer->due_date ?? '') }}" readonly autocomplete="off">
                                    <i class="fa-regular fa-calendar-days date-input-calendar-icon"></i>
                                </div>
                                <span class="invalid-feedback d-block" id="due_date_client_error" style="{{ $errors->has('due_date') ? '' : 'display: none !important;' }}">
                                    <strong>{{ $errors->first('due_date') }}</strong>
                                </span>
                            </div>

                            <div class="col-md-4 form-group" id="paymentModeCol10">
                                <label class="form-label" for="payment_mode10">Payment Mode <span class="required-star">*</span></label>
                                <select name="payment_mode" id="payment_mode10" class="form-select @error('payment_mode') is-invalid @enderror">
                                    <option value="">Select Payment Mode</option>
                                    <option value="1" {{ old('payment_mode', $customer->payment_mode) == 1 ? 'selected' : '' }}>Online</option>
                                    <option value="2" {{ old('payment_mode', $customer->payment_mode) == 2 ? 'selected' : '' }}>Offline</option>
                                    <option value="3" {{ old('payment_mode', $customer->payment_mode) == 3 ? 'selected' : '' }}>Pay Later</option>
                                </select>
                                @error('payment_mode')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        {{-- Symmetrical Pay Later Notice Tile (Appears below without shifting columns) --}}
                        <div class="row g-2 mt-1 pay-later-info-row" id="payLaterInfoCol10" style="{{ old('payment_mode') == '3' ? '' : 'display: none;' }}">
                            <div class="col-12">
                                <div class="pay-later-status-pill">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                    <div class="pay-later-status-text">
                                        <strong>Pay Later Selected</strong> — Full amount is added to Pending Due. Please specify an agreed Due Date above.
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- 5. ACTION BUTTON BAR (CANCEL REMOVED) --}}
                <div class="form-action-bar">
                    @if($planDetails['diff_in_days'] <= 5 && $planDetails['diff_extend_day']>= 0 && !$is_renew && !$isalreadyRenew)
                        <button type="submit" class="btn-submit-operation" id="upgradePlanSubmit">
                            <i class="fa-solid fa-circle-up"></i> {{ $pageTitle }}
                        </button>
                    @else
                        <div class="text-danger fw-semibold">
                            <i class="fa-solid fa-triangle-exclamation"></i> {{ ($is_renew ?? false) || ($isalreadyRenew ?? false) ? 'Next term is already booked for this learner.' : (Route::currentRouteName() == 'learner.renew.plan' ? 'Renewal' : 'Upgrade') . ' opens 5 days before the plan ends, or during the extension days.' }}
                        </div>
                    @endif
                </div>

            </div>

        </form>

    
            </div>
        </div>
        <aside class="lop-aside" id="lopAside" aria-live="polite" aria-label="Summary"></aside>
    </div>
</div>
<script src="{{ asset('public/js/lb-opplan.js') }}?v={{ @filemtime(public_path('js/lb-opplan.js')) }}" defer></script>
{{-- the library's running global offer fills the discount fields (docs/GLOBAL_DISCOUNT.md) --}}
@include('partials.global-offer', ['forms' => [['price' => '#plan_price10', 'type' => '#discountType10', 'value' => '#discount_amount10', 'typeName' => 'discountType', 'valueName' => 'discount_amount', 'locker' => '#locker_amount10', 'lockerToggle' => '#toggleFieldCheckbox10']]])

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof flatpickr !== 'undefined') {
            flatpickr("#due_date10", {
                dateFormat: "Y-m-d",
                minDate: "today",
                disableMobile: "true",
                allowInput: true,
                onChange: function(selectedDates, dateStr) {
                    if (dateStr) {
                        $('#due_date10').removeClass('is-invalid');
                        $('#due_date_client_error').hide().find('strong').text('');
                    }
                }
            });
        }

        // Calendar icon click opens picker
        $(document).on('click', '.date-input-calendar-icon', function() {
            const input = $(this).siblings('.duedate')[0];
            if (input && input._flatpickr) {
                input._flatpickr.open();
            } else if (input) {
                input.focus();
            }
        });

        handleFormChanges('{{ $ids }}', {{ $customer->id }});

        function cleanAmountVal(val) {
            if (val === null || val === undefined || val === '') return '';
            const num = parseFloat(val);
            if (isNaN(num)) return val;
            return Math.round(num).toString();
        }

        function cleanAllInputs() {
            const fields = [
                '#total_amount10',
                '#pending_amt10',
                '#plan_price10',
                '#previous_pending10',
                '#locker_amount10',
                '#discount_amount10'
            ];
            fields.forEach(function(sel) {
                const el = $(sel);
                if (el.length && el.val() !== '') {
                    const raw = el.val().toString();
                    if (raw.indexOf('.') !== -1) {
                        el.val(cleanAmountVal(raw));
                    }
                }
            });
        }

        // Clean on initial load
        cleanAllInputs();

        // Form submit validation & pre-submission processing
        const formElement = document.getElementById('{{ $ids }}');
        if (formElement) {
            formElement.addEventListener('submit', function(e) {
                const paymentMode = $('#payment_mode10').val();
                const pendingAmt = parseFloat($('#pending_amt10').val()) || 0;
                const dueDate = ($('#due_date10').val() || '').trim();

                // Due Date is required when payment mode is Pay Later ('3') or there is a pending amount
                const isDueDateRequired = (paymentMode === '3' || pendingAmt > 0);
                if (isDueDateRequired && !dueDate) {
                    e.preventDefault();
                    $('#due_date10').addClass('is-invalid');
                    $('#due_date_client_error').show().find('strong').text('Due date is required when payment mode is Pay Later or pending amount exists.');
                    const dueDateEl = document.getElementById('due_date10');
                    if (dueDateEl) {
                        dueDateEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        if (dueDateEl._flatpickr) {
                            dueDateEl._flatpickr.open();
                        }
                    }
                    return false;
                } else {
                    $('#due_date10').removeClass('is-invalid');
                    $('#due_date_client_error').hide().find('strong').text('');
                }

                cleanAllInputs();
            });
        }

        function syncPaymentModeAndNotice() {
            const paymentMode = $('#payment_mode10').val();
            const pendingAmt = parseFloat($('#pending_amt10').val()) || 0;
            const $payLaterNotice = $('#payLaterInfoCol10');

            if (paymentMode === '3') {
                $payLaterNotice.show();
                $('#due_date_star').show();
            } else {
                $payLaterNotice.hide();
                if (pendingAmt > 0) {
                    $('#due_date_star').show();
                } else {
                    $('#due_date_star').hide();
                }
            }
        }

        function syncSettlementState() {
            syncPaymentModeAndNotice();
            cleanAllInputs();
            const prevPending = Math.round(parseFloat($('#previous_pending10').val()) || 0);
            const totalAmount = Math.round(parseFloat($('#total_amount10').val()) || 0);
            const paymentMode = $('#payment_mode10').val();

            if (paymentMode === '3') {
                const pendingVal = totalAmount + prevPending;
                $('#pending_amt10').val(pendingVal);
            }
        }

        // Initialize layout and state
        syncPaymentModeAndNotice();
        syncSettlementState();

        // Direct listener on payment_mode change
        $('#payment_mode10').on('change', function() {
            syncPaymentModeAndNotice();
            setTimeout(function() {
                cleanAllInputs();
                syncSettlementState();
            }, 30);
        });

        // Listen for price and field updates
        $(document).on('input change', '#plan_id10, #plan_type_id10, #plan_price10, #total_amount10, #discountType10, #discount_amount10, #toggleFieldCheckbox10, #locker_amount10, #payment_mode10', function() {
            syncPaymentModeAndNotice();
            setTimeout(function() {
                cleanAllInputs();
                syncSettlementState();
            }, 60);
        });

        // Heartbeat monitor to catch any programmatic updates from external scripts
        let lastTotalVal = $('#total_amount10').val();
        let lastPendingVal = $('#pending_amt10').val();
        let lastPlanPriceVal = $('#plan_price10').val();
        setInterval(function() {
            const curTotal = $('#total_amount10').val();
            const curPending = $('#pending_amt10').val();
            const curPlanPrice = $('#plan_price10').val();
            if (curTotal !== lastTotalVal || curPending !== lastPendingVal || curPlanPrice !== lastPlanPriceVal) {
                lastTotalVal = curTotal;
                lastPendingVal = curPending;
                lastPlanPriceVal = curPlanPrice;
                cleanAllInputs();
                syncSettlementState();
            }
        }, 150);

        // Mobile info details collapse toggle
        const btnToggleDetails = document.getElementById('btnToggleDetails');
        const infoGrid = document.getElementById('glassInfoGrid');

        if (btnToggleDetails && infoGrid) {
            btnToggleDetails.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const isCurrentlyCollapsed = infoGrid.classList.contains('is-collapsed');

                if (isCurrentlyCollapsed) {
                    infoGrid.classList.remove('is-collapsed');
                    btnToggleDetails.classList.remove('is-collapsed');
                    btnToggleDetails.setAttribute('aria-expanded', 'true');
                } else {
                    infoGrid.classList.add('is-collapsed');
                    btnToggleDetails.classList.add('is-collapsed');
                    btnToggleDetails.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });
</script>

@endsection
