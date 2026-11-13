@extends('layouts.library')

@section('content')
@php
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

$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class = $planDetails['class'];
@endphp

{{-- Scoped CSS for Learner Other Payment Module --}}
@php $opIcon = 'hand-coins'; $opKicker = 'Other payment'; $opTitle = 'Collect an extra payment from :name'; @endphp
<link rel="stylesheet" href="{{ asset('public/css/lb-swap.css') }}?v={{ @filemtime(public_path('css/lb-swap.css')) }}" />
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}" />
<link rel="stylesheet" href="{{ asset('public/css/lb-opplan.css') }}?v={{ @filemtime(public_path('css/lb-opplan.css')) }}" />
<link rel="stylesheet" href="{{ asset('public/css/lb-opui.css') }}?v={{ @filemtime(public_path('css/lb-opui.css')) }}" />

<div class="lsw lop" id="operationContent" data-op="other"
     data-cur-plan="{{ $customer->plan_name ?? optional($customer->plan ?? null)->name }}"
     data-cur-shift="{{ $customer->plan_type_name ?? optional($customer->plantype ?? null)->name }}"
     data-cur-start="{{ $customer->plan_start_date }}" data-cur-end="{{ $customer->plan_end_date }}"
     data-new-start="">
    <div class="lop-grid">
        <div class="lsw-sheet lop-main">
            @include('learner.partials.op-head')
            <div class="lsw-body lop-form-body">
        <form action="{{ route('learner.other.payment.store') }}" method="POST" enctype="multipart/form-data" id="other-payment_page" class="payment_page" novalidate>
            @csrf
            @method('POST')
            <input id="learner_id" type="hidden" name="learner_id" value="{{ $customer->learner_id }}">

            <div class="payment-card plan-card">
                <div class="payment-card-header plan-card-header header-green">
                    <div class="payment-header-left plan-header-left">
                        <div class="payment-header-icon plan-header-icon">
                            <i class="fa-solid fa-credit-card"></i>
                        </div>
                        <div>
                            <h4 class="payment-header-title plan-header-title">Other Payment</h4>
                            <p class="payment-header-subtitle plan-header-subtitle">Collect extra payment, token money, or pending refund fee.</p>
                        </div>
                    </div>
                </div>
                <div class="payment-card-body plan-card-body">
                    <div class="payment-tip-box plan-tip-box">
                        <i class="fa-solid fa-gem"></i>
                        <div>
                            <strong>Note :</strong> If you want to take any extra payment from a student, use this option. It will add the payment to your revenue.
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4 form-group">
                            <label class="form-label" for="payment_type">Payment Type <span class="required-star">*</span></label>
                            <select name="payment_type" id="payment_type" class="form-select @error('payment_type') is-invalid @enderror"
                                    data-token="{{ $tokenMoney }}" data-refund="{{ $customer->pending_refund }}">
                                <option value="">Select Payment</option>
                                @if(!$customer->token_money)
                                <option value="token_money" {{ old('payment_type') == 'token_money' ? 'selected' : '' }}>Token Money</option>
                                @endif
                                @if($customer->pending_refund)
                                <option value="pending_refund" {{ old('payment_type') == 'pending_refund' ? 'selected' : '' }}>Refund Amt. to Pay</option>
                                @endif
                                <option value="miscellaneous" {{ old('payment_type') == 'miscellaneous' ? 'selected' : '' }}>Miscellaneous fee</option>
                            </select>
                            <span class="invalid-feedback d-block" id="payment_type_error" style="{{ $errors->has('payment_type') ? '' : 'display: none !important;' }}">
                                <strong>{{ $errors->first('payment_type') }}</strong>
                            </span>
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="form-label" for="fees">Fees <span class="required-star">*</span></label>
                            <input type="text" class="form-control @error('fees') is-invalid @enderror" placeholder="Enter Fees" name="fees" id="fees" value="{{ old('fees') }}" maxlength="5" inputmode="numeric" autocomplete="off">
                            <span class="invalid-feedback d-block" id="fees_error" style="{{ $errors->has('fees') ? '' : 'display: none !important;' }}">
                                <strong>{{ $errors->first('fees') }}</strong>
                            </span>
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="form-label" for="other_payment_mode">Payment Mode <span class="required-star">*</span></label>
                            <select name="payment_mode" id="other_payment_mode" class="form-select @error('payment_mode') is-invalid @enderror">
                                <option value="">Select Payment Mode</option>
                                <option value="1" {{ (old('payment_mode') == '1' || old('payment_mode') == 'Online') ? 'selected' : '' }}>Online</option>
                                <option value="2" {{ (old('payment_mode') == '2' || old('payment_mode') == 'Offline') ? 'selected' : '' }}>Offline</option>
                            </select>
                            <span class="invalid-feedback d-block" id="payment_mode_error" style="{{ $errors->has('payment_mode') ? '' : 'display: none !important;' }}">
                                <strong>{{ $errors->first('payment_mode') }}</strong>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. ACTION BUTTON BAR (CANCEL REMOVED, PRIMARY PILL BUTTON) --}}
            <div class="form-action-bar">
                <button type="submit" class="btn-submit-operation btn-submit-payment" id="paymentsubmit">
                    <i class="fa-solid fa-credit-card"></i> Make Payment
                </button>
            </div>
        </form>
    
            </div>
        </div>
        <aside class="lop-aside" id="lopAside" aria-live="polite" aria-label="Summary"></aside>
    </div>
</div>
<script src="{{ asset('public/js/lb-opplan.js') }}?v={{ @filemtime(public_path('js/lb-opplan.js')) }}" defer></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
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

$(document).ready(function () {
    // Only allow numeric digits in fees
    $('#fees').on('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 5);
        if (this.value.trim()) {
            $(this).removeClass('is-invalid');
            $('#fees_error').hide().find('strong').text('');
        }
    });

    $('#payment_type').on('change', function () {
        let selected = $(this).val();
        let tokenMoney = $(this).data('token');
        let pending_refund = $(this).data('refund');

        if (selected) {
            $(this).removeClass('is-invalid');
            $('#payment_type_error').hide().find('strong').text('');
        }

        if (selected === 'token_money' && tokenMoney) {
            $('#fees').val(Math.round(parseFloat(tokenMoney) || 0)).prop('readonly', true).removeClass('is-invalid');
            $('#fees_error').hide().find('strong').text('');
        } else if (selected === 'token_money') {
            $('#fees').val('').prop('readonly', false);
        } else if (selected === 'pending_refund') {
            $('#fees').val(Math.round(parseFloat(pending_refund) || 0)).prop('readonly', false).removeClass('is-invalid');
            $('#fees_error').hide().find('strong').text('');
        } else {
            $('#fees').val('').prop('readonly', false);
        }
    });

    $('#other_payment_mode').on('change', function () {
        if ($(this).val()) {
            $(this).removeClass('is-invalid');
            $('#payment_mode_error').hide().find('strong').text('');
        }
    });

    // Form client-side validation on submit
    $('#other-payment_page').on('submit', function (e) {
        let hasError = false;
        const $form = $(this);

        const paymentType = $form.find('#payment_type').val();
        const fees = ($form.find('#fees').val() || '').trim();
        const paymentMode = $form.find('#other_payment_mode').val();

        // Validate Payment Type
        if (!paymentType) {
            $form.find('#payment_type').addClass('is-invalid');
            $('#payment_type_error').show().find('strong').text('Please select a payment type.');
            hasError = true;
        } else {
            $form.find('#payment_type').removeClass('is-invalid');
            $('#payment_type_error').hide().find('strong').text('');
        }

        // Validate Fees
        if (!fees) {
            $form.find('#fees').addClass('is-invalid');
            $('#fees_error').show().find('strong').text('Please enter fees amount.');
            hasError = true;
        } else if (isNaN(fees) || parseInt(fees, 10) <= 0) {
            $form.find('#fees').addClass('is-invalid');
            $('#fees_error').show().find('strong').text('Fees must be a valid number greater than 0.');
            hasError = true;
        } else {
            $form.find('#fees').removeClass('is-invalid');
            $('#fees_error').hide().find('strong').text('');
        }

        // Validate Payment Mode
        if (!paymentMode) {
            $form.find('#other_payment_mode').addClass('is-invalid');
            $('#payment_mode_error').show().find('strong').text('Please select a payment mode.');
            hasError = true;
        } else {
            $form.find('#other_payment_mode').removeClass('is-invalid');
            $('#payment_mode_error').hide().find('strong').text('');
        }

        if (hasError) {
            e.preventDefault();
            e.stopPropagation();

            const $firstError = $('.is-invalid').first();
            if ($firstError.length) {
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 140
                }, 200);
                $firstError.focus();
            }
            return false;
        }
    });
});
</script>

@endsection