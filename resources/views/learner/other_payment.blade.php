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
<link rel="stylesheet" href="{{ asset('public/css/learner-other-payment.css') }}?v={{ time() }}" />

<div class="learner-other-payment-module">
    <div class="learner-other-payment-wrapper">

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <div class="d-flex align-items-start">
                    <i class="fa-solid fa-circle-exclamation me-2 mt-1"></i>
                    <div>
                        <strong>Please resolve the following error(s):</strong>
                        <ul class="mb-0 ps-3 mt-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- 1. TOP SEAT HEADER HERO CARD (MATCHING CHANGE PLAN, SWAP SEAT & UPGRADE PLAN) --}}
        <div class="learner-seat-header-card">
            <div class="seat-header-main">
                <div class="seat-header-identity">
                    <div class="seat-header-avatar-box">
                        @php
                            $learnerProfilePic = $customer->learner->profile_picture ?? ($customer->profile_picture ?? null);
                        @endphp
                        @if($learnerProfilePic && file_exists(public_path($learnerProfilePic)))
                            <img id="topSeatAvatarImg" src="{{ asset($learnerProfilePic) }}" alt="{{ $customer->learner->name ?? 'Learner' }}" class="avatar-user-photo">
                        @elseif(isset($customer->plantype) && $customer->plantype && $customer->plantype->image)
                            <img id="topSeatAvatarImg" src="{{ asset($customer->plantype->image) }}" alt="Seat" class="avatar-seat-chair {{ $class }}">
                        @else
                            <img id="topSeatAvatarImg" src="{{ asset('public/img/booked.png') }}" alt="Seat" class="avatar-seat-chair {{ $class }}">
                        @endif
                    </div>
                    <div class="seat-header-info">
                        <div class="seat-badge-row">
                            <span class="seat-status-badge {{ $statusClass }}">
                                <i class="{{ $statusIcon }} me-1"></i>{{ $statusText }}
                            </span>
                        </div>
                        <h3 class="seat-title text-uppercase">
                            {{ strtoupper($customer->learner->name ?? ($customer->name ?? 'Learner')) }}
                        </h3>
                        <p class="seat-subtitle">
                            <span>Learner UID: <strong class="seat-uid-tag">{{ $customer->learner->learner_no ?? ($customer->learner_no ?? ('#' . ($customer->learner_id ?? $customer->id))) }}</strong></span>
                        </p>
                    </div>
                </div>
                <div class="seat-header-actions">
                    <a href="{{ route('learners') }}" class="btn-seat-back btn-back-desktop" title="Go Back">
                        <i class="fa-solid fa-arrow-left"></i> <span class="btn-back-text">Go Back</span>
                    </a>
                    {{-- Mobile Collapse/Expand Toggle Arrow (Closed by default on mobile) --}}
                    <button type="button" class="btn-seat-collapse is-collapsed" id="btnToggleDetails" title="Show / Hide Details" aria-expanded="false">
                        <i class="fa-solid fa-chevron-down toggle-icon"></i>
                    </button>
                </div>
            </div>

            {{-- 4 GLASSMORPHIC DETAIL TILES (Closed by default on mobile) --}}
            <div class="glass-info-grid is-collapsed" id="glassInfoGrid">
                {{-- Tile 1: Current Plan (e.g. Monthly, Full Day) --}}
                <div class="glass-tile tile-plan">
                    <div class="glass-tile-icon">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Current Plan</div>
                        <div class="glass-tile-value">{{ $customer->plan->name ?? ($customer->plan_name ?? 'Monthly') }}</div>
                    </div>
                </div>

                {{-- Tile 2: Shift / Plan --}}
                <div class="glass-tile tile-shift">
                    <div class="glass-tile-icon">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Shift / Plan</div>
                        <div class="glass-tile-value">{{ $customer->plantype->name ?? ($customer->plan_type_name ?? 'N/A') }}</div>
                    </div>
                </div>

                {{-- Tile 3: Valid Till --}}
                <div class="glass-tile tile-date">
                    <div class="glass-tile-icon">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Valid Till</div>
                        <div class="glass-tile-value">
                            {{ $customer->plan_end_date ? \Carbon\Carbon::parse($customer->plan_end_date)->format('d M, Y') : 'N/A' }}
                        </div>
                    </div>
                </div>

                {{-- Tile 4: Contact Mobile --}}
                <div class="glass-tile tile-contact">
                    <div class="glass-tile-icon">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Mobile</div>
                        <div class="glass-tile-value">
                            @if($customer->learner && $customer->learner->mobile)
                                <a href="tel:{{ $customer->learner->mobile }}">{{ $customer->learner->mobile }}</a>
                            @elseif($customer->mobile)
                                <a href="tel:{{ $customer->mobile }}">{{ $customer->mobile }}</a>
                            @else
                                <span>Not Provided</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. OTHER PAYMENT FORM CARD (GLASSMORPHISM MATCHING CHANGE PLAN) --}}
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
                            <label class="form-label" for="payment_mode">Payment Mode <span class="required-star">*</span></label>
                            <select name="payment_mode" id="payment_mode" class="form-select @error('payment_mode') is-invalid @enderror">
                                <option value="">Select Payment Mode</option>
                                <option value="Online" {{ old('payment_mode') == 'Online' ? 'selected' : '' }}>Online</option>
                                <option value="Offline" {{ old('payment_mode') == 'Offline' ? 'selected' : '' }}>Offline</option>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formEl = document.querySelector('form.payment_page');
    if (formEl) {
        handleFormChanges(formEl.id, {{ $customer->learner->id ?? $customer->learner_id }});
    }

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

    $('#payment_mode').on('change', function () {
        if ($(this).val()) {
            $(this).removeClass('is-invalid');
            $('#payment_mode_error').hide().find('strong').text('');
        }
    });

    // Form client-side validation on submit
    $('#other-payment_page').on('submit', function (e) {
        let hasError = false;

        const paymentType = $('#payment_type').val();
        const fees = $('#fees').val().trim();
        const paymentMode = $('#payment_mode').val();

        // Validate Payment Type
        if (!paymentType) {
            $('#payment_type').addClass('is-invalid');
            $('#payment_type_error').show().find('strong').text('Please select a payment type.');
            hasError = true;
        } else {
            $('#payment_type').removeClass('is-invalid');
            $('#payment_type_error').hide().find('strong').text('');
        }

        // Validate Fees
        if (!fees) {
            $('#fees').addClass('is-invalid');
            $('#fees_error').show().find('strong').text('Please enter fees amount.');
            hasError = true;
        } else if (isNaN(fees) || parseInt(fees, 10) <= 0) {
            $('#fees').addClass('is-invalid');
            $('#fees_error').show().find('strong').text('Fees must be a valid number greater than 0.');
            hasError = true;
        } else {
            $('#fees').removeClass('is-invalid');
            $('#fees_error').hide().find('strong').text('');
        }

        // Validate Payment Mode
        if (!paymentMode) {
            $('#payment_mode').addClass('is-invalid');
            $('#payment_mode_error').show().find('strong').text('Please select a payment mode.');
            hasError = true;
        } else {
            $('#payment_mode').removeClass('is-invalid');
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