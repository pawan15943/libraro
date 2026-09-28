@extends('layouts.library')
@section('content')

@php
$planDetails = getPlanStatusDetails($customer->plan_end_date ?? null);
$class = $planDetails['class'] ?? 'available';

if (($customer->type ?? '') == 'qr_renew') {
    $statusText = 'QR Renewal Request';
    $statusClass = 'status-warning';
    $statusIcon = 'fa-solid fa-rotate';
} elseif (($customer->type ?? '') == 'demo-bookings') {
    $statusText = 'Demo Inquiry';
    $statusClass = 'status-warning';
    $statusIcon = 'fa-regular fa-calendar-check';
} elseif (($customer->type ?? '') == 'learner_book') {
    $statusText = 'Learner Booking';
    $statusClass = 'status-active';
    $statusIcon = 'fa-solid fa-user-plus';
} else {
    $statusText = 'Booking Verification';
    $statusClass = 'status-active';
    $statusIcon = 'fa-solid fa-qrcode';
}

$currentSeatNo = $customer->seat_no ?? null;
$currentBranchId = $customer->branch_id ?? getCurrentBranch();
$floorDisplay = 'Ground Floor';
if ($currentSeatNo && is_numeric($currentSeatNo)) {
    $floorObj = \App\Models\Floor::withoutGlobalScopes()
        ->where('branch_id', $currentBranchId)
        ->where('from_seat', '<=', (int)$currentSeatNo)
        ->where('to_seat', '>=', (int)$currentSeatNo)
        ->whereNull('deleted_at')
        ->first();
    if ($floorObj && !empty($floorObj->name)) {
        $floorDisplay = str_ends_with(strtolower($floorObj->name), 'floor') ? $floorObj->name : ($floorObj->name . ' Floor');
    } else {
        $firstFloor = \App\Models\Floor::withoutGlobalScopes()
            ->where('branch_id', $currentBranchId)
            ->whereNull('deleted_at')
            ->first();
        if ($firstFloor && !empty($firstFloor->name)) {
            $floorDisplay = str_ends_with(strtolower($firstFloor->name), 'floor') ? $firstFloor->name : ($firstFloor->name . ' Floor');
        }
    }
}

$seatDisplayLabel = $currentSeatNo ? (function_exists('getSeatDisplayByMainNo') ? getSeatDisplayByMainNo($currentSeatNo) : ('Seat ' . $currentSeatNo)) : 'General Seat';
@endphp

{{-- Scoped CSS for Booking Details & Verification Module --}}
<link rel="stylesheet" href="{{ asset('public/css/booking-details.css') }}?v={{ time() }}" />

<div class="booking-details-module">
    <div class="booking-details-wrapper">

        {{-- Alerts --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i>
                <strong>Please check the errors below:</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

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

        {{-- TOP SEAT & LEARNER INFO HERO CARD (GLASSMORPHISM) --}}
        <div class="learner-seat-header-card">
            <div class="seat-header-main">
                <div class="seat-header-identity">
                    <div class="seat-header-avatar-box">
                        @if($customer->profile_picture && file_exists(public_path($customer->profile_picture)))
                            <img id="topSeatAvatarImg" src="{{ asset($customer->profile_picture) }}" alt="{{ $customer->name }}" class="avatar-user-photo view-image">
                        @elseif(isset($customer->image) && $customer->image)
                            <img id="topSeatAvatarImg" src="{{ asset($customer->image) }}" alt="Seat" class="avatar-seat-chair {{ $class }}">
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
                            {{ strtoupper($customer->name ?? 'Learner') }}
                        </h3>
                        <p class="seat-subtitle">
                            <span>Booking Reference: <strong class="seat-uid-tag">#{{ $customer->id }}</strong></span>
                        </p>
                    </div>
                </div>
                <div class="seat-header-actions">
                    <a href="{{ url()->previous() != url()->current() ? url()->previous() : route('demo-enquiry.index') }}" class="btn-seat-back btn-back-desktop" title="Go Back">
                        <i class="fa-solid fa-arrow-left"></i> <span class="btn-back-text">Go Back</span>
                    </a>
                    {{-- Mobile Collapse/Expand Toggle Arrow --}}
                    <button type="button" class="btn-seat-collapse is-collapsed" id="btnToggleDetails" title="Show / Hide Details" aria-expanded="false">
                        <i class="fa-solid fa-chevron-down toggle-icon"></i>
                    </button>
                </div>
            </div>

            {{-- Engaging Mobile-only Seat No & Floor Strip --}}
            <div class="seat-header-mobile-meta">
                <div class="mobile-meta-pill pill-seat">
                    <span class="meta-pill-icon"><i class="fa-solid fa-chair"></i></span>
                    <div class="meta-pill-text">
                        <span class="meta-pill-label">Seat Allotment</span>
                        <strong class="meta-pill-val">{{ $seatDisplayLabel }}</strong>
                    </div>
                </div>
                <div class="mobile-meta-divider"></div>
                <div class="mobile-meta-pill pill-floor">
                    <span class="meta-pill-icon"><i class="fa-solid fa-layer-group"></i></span>
                    <div class="meta-pill-text">
                        <span class="meta-pill-label">Floor</span>
                        <strong class="meta-pill-val">{{ $floorDisplay }}</strong>
                    </div>
                </div>
            </div>

            {{-- 4 GLASSMORPHIC DETAIL TILES --}}
            <div class="glass-info-grid is-collapsed" id="glassInfoGrid">
                {{-- Tile 1: Plan --}}
                <div class="glass-tile tile-plan">
                    <div class="glass-tile-icon">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Plan</div>
                        <div class="glass-tile-value">{{ $customer->plan->name ?? ($customer->plan_name ?? 'Standard Plan') }}</div>
                    </div>
                </div>

                {{-- Tile 2: Shift / Plan Type --}}
                <div class="glass-tile tile-shift">
                    <div class="glass-tile-icon">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Shift / Plan</div>
                        <div class="glass-tile-value">{{ $customer->planType->name ?? ($customer->plan_type_name ?? 'N/A') }}</div>
                    </div>
                </div>

                {{-- Tile 3: Starts On --}}
                <div class="glass-tile tile-date">
                    <div class="glass-tile-icon">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Starts On</div>
                        <div class="glass-tile-value">
                            {{ $customer->plan_start_date ? \Carbon\Carbon::parse($customer->plan_start_date)->format('d M, Y') : 'N/A' }}
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
                            @if($customer->mobile)
                                <a href="tel:{{ $customer->mobile }}">{{ $customer->mobile }}</a>
                            @else
                                <span>Not Provided</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- MAIN VERIFICATION & APPROVAL FORM --}}
        <form action="{{ route('booking.details.approve') }}" method="POST" enctype="multipart/form-data" id="approveBookingForm">
            @csrf

            <input type="hidden" name="booking_id" value="{{ $customer->id ?? '' }}">
            <input type="hidden" name="learner_id" value="{{ $learner->id ?? '' }}" id="renew_learner_id">
            <input type="hidden" name="branch_id" value="{{ $customer->branch_id ?? '' }}">
            <input type="hidden" id="seat_id12" value="{{ $customer->seat_no }}">
            <input type="hidden" id="plan_type_id12" value="{{ $customer->plan_type_id }}">
            <input type="hidden" id="plan_price11" class="form-control" name="plan_price_id" value="{{ old('plan_price_id', $customer->plan_price_id) }}" readonly>

            @php
                /* Normalize available seats & structure */
                $availableSeatNumbers = collect($availableseats ?? [])->filter()->values()->toArray();
                $allSeats = collect(generateSeatNumbers());
                $seatList = $allSeats->filter(function ($seat) use ($availableSeatNumbers) {
                    return in_array($seat['main'], $availableSeatNumbers);
                })->values();

                if (isset($customer) && ($customer->type ?? null) === 'qr_renew' && !empty($customer->seat_no) && !$seatList->contains('main', $customer->seat_no)) {
                    $currentSeat = $allSeats->firstWhere('main', $customer->seat_no);
                    if ($currentSeat) {
                        $seatList->prepend($currentSeat);
                    }
                }
                $seatList = $seatList->sortBy('main')->values();
            @endphp

            <div class="booking-cards-column">

                {{-- CARD 1: SEAT ALLOTMENT & LEARNER IDENTITY --}}
                <div class="booking-card">
                    <div class="booking-card-header header-navy">
                        <div class="booking-header-left">
                            <div class="booking-header-icon">
                                <i class="fa-solid fa-chair"></i>
                            </div>
                            <div>
                                <h4 class="booking-header-title">Seat Allotment &amp; Learner Info</h4>
                                <p class="booking-header-subtitle">Verify the learner details and assign or confirm their seat.</p>
                            </div>
                        </div>
                    </div>
                    <div class="booking-card-body">
                        <div class="booking-tip-box">
                            <i class="fa-solid fa-circle-info"></i>
                            <div>
                                Choose <strong>"Yes, Allot a Seat No."</strong> to assign a specific vacant seat, or choose <strong>"No"</strong> to keep as General seat.
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6 form-group">
                                <label class="form-label" for="qr_general_seat">Assign Seat No? <span class="required-star">*</span></label>
                                <select name="general_seat" id="qr_general_seat" class="form-select">
                                    <option value="yes">No</option>
                                    <option value="no">Yes, Allot a Seat No.</option>
                                </select>
                            </div>

                            <div class="col-md-6 form-group">
                                <label class="form-label" for="seat_id11">Choose Seat No. <span class="required-star">*</span></label>
                                <select name="seat_no" class="form-select @error('seat_no') is-invalid @enderror" id="seat_id11" {{ ($customer->type ?? '') == 'qr_renew' ? 'disabled' : '' }}>
                                    <option value="">GEN</option>
                                    @foreach ($seatList as $seat)
                                        <option value="{{ $seat['main'] }}" {{ ($customer->seat_no ?? '') == $seat['main'] ? 'selected' : '' }}>
                                            {{ $seat['display'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('seat_no')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="col-md-6 form-group">
                                <label class="form-label" for="name">Full Name <span class="required-star">*</span></label>
                                <input type="text" class="form-control char-only @error('name') is-invalid @enderror" name="name" id="name" placeholder="Enter full name" value="{{ old('name') ?? ($customer->name ?? '') }}">
                                @error('name')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="col-md-6 form-group">
                                <label class="form-label" for="mobile">Mobile Number <span class="required-star">*</span></label>
                                <input type="text" class="form-control digit-only @error('mobile') is-invalid @enderror" maxlength="10" minlength="10" name="mobile" id="mobile" placeholder="10-digit mobile" value="{{ old('mobile', $customer->mobile ?? '') }}">
                                @error('mobile')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            @if(!in_array('2', toggleHideField()))
                            <div class="col-md-6 form-group">
                                <label class="form-label" for="dob">DOB (Optional)</label>
                                <input type="date" class="form-control @error('dob') is-invalid @enderror" name="dob" id="dob" value="{{ old('dob') ?? (optional($customer)->dob ? \Carbon\Carbon::parse($customer->dob)->format('Y-m-d') : '') }}" max="{{ date('Y-m-d', strtotime('-5 years')) }}">
                                @error('dob')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            @endif

                            @if(!in_array('1', toggleHideField()))
                            <div class="col-md-6 form-group">
                                <label class="form-label" for="email">Email Address (Optional)</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" id="email" placeholder="example@gmail.com" value="{{ old('email', $customer->email ?? '') }}">
                                @error('email')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- CARD 2: SUBSCRIPTION PLAN & SHIFT --}}
                <div class="booking-card">
                    <div class="booking-card-header header-green">
                        <div class="booking-header-left">
                            <div class="booking-header-icon">
                                <i class="fa-solid fa-calendar-days"></i>
                            </div>
                            <div>
                                <h4 class="booking-header-title">Subscription Plan &amp; Shift</h4>
                                <p class="booking-header-subtitle">Confirm the chosen plan package and shift timings.</p>
                            </div>
                        </div>
                    </div>
                    <div class="booking-card-body">
                        <div class="row g-3">
                            <div class="col-md-4 form-group">
                                <label class="form-label" for="plan_id11">Plan Package <span class="required-star">*</span></label>
                                <select id="plan_id11" class="form-select @error('plan_id') is-invalid @enderror" name="plan_id">
                                    <option value="">Select Plan</option>
                                    @foreach($plans as $key => $value)
                                        <option value="{{ $value->id }}" {{ old('plan_id', $customer->plan_id) == $value->id ? 'selected' : '' }}>
                                            {{ $value->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('plan_id')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="col-md-4 form-group">
                                <label class="form-label" for="plan_type_id11">Plan Shift (Type) <span class="required-star">*</span></label>
                                <select id="plan_type_id11" class="form-select @error('plan_type_id') is-invalid @enderror" name="plan_type_id">
                                    @foreach($filteredPlanTypes as $planTypeItem)
                                        <option value="{{ $planTypeItem['id'] }}" {{ ($customer->plan_type_id == $planTypeItem['id']) ? 'selected' : (old('plan_type_id') == $planTypeItem['id'] ? 'selected' : '') }}>
                                            {{ $planTypeItem['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('plan_type_id')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="col-md-4 form-group">
                                <label class="form-label" for="plan_start_date">Plan Starts On <span class="required-star">*</span></label>
                                <input type="date" name="plan_start_date" id="plan_start_date" class="form-control @error('plan_start_date') is-invalid @enderror" value="{{ old('plan_start_date', $customer->plan_start_date) }}" {{ ($customer->type ?? '') == 'qr_renew' ? 'disabled' : '' }}>
                                @error('plan_start_date')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CARD 3: PLAN ADD-ONS & DISCOUNTS (COLLAPSIBLE) --}}
                @if(!in_array('3', toggleHideField()) || !in_array('6', toggleHideField()))
                <div class="booking-card">
                    <div class="booking-card-header header-amber collapsible-header" data-bs-toggle="collapse" data-bs-target="#planAddonsCollapse" aria-expanded="true" aria-controls="planAddonsCollapse">
                        <div class="booking-header-left">
                            <div class="booking-header-icon">
                                <i class="fa-solid fa-cube"></i>
                            </div>
                            <div>
                                <h4 class="booking-header-title">Plan Add-on's &amp; Discounts</h4>
                                <p class="booking-header-subtitle">Configure locker allotment and apply discount preferences.</p>
                            </div>
                        </div>
                        <div class="booking-header-toggle">
                            <i class="fa-solid fa-chevron-down toggle-icon"></i>
                        </div>
                    </div>
                    <div class="collapse show" id="planAddonsCollapse">
                        <div class="booking-card-body">
                            <div class="row g-3">
                                @if(!in_array('3', toggleHideField()))
                                <div class="col-md-4 col-sm-6 form-group {{ !is_locker() ? 'd-none' : '' }}">
                                    <label class="form-label" for="toggleFieldCheckbox11">Need a Locker?</label>
                                    <select name="locker" id="toggleFieldCheckbox11" class="form-select @error('locker') is-invalid @enderror">
                                        <option value="no" {{ old('locker', (($transaction?->locker_amount ?? 0) > 0 ? 'yes' : 'no')) == 'no' ? 'selected' : '' }}>No</option>
                                        <option value="yes" {{ old('locker', (($transaction?->locker_amount ?? 0) > 0 ? 'yes' : 'no')) == 'yes' ? 'selected' : '' }}>Yes, I Need a Locker</option>
                                    </select>
                                    @error('locker')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-6 form-group {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer">
                                    <label class="form-label" for="locker_amount11">Locker Amount (₹)</label>
                                    <input type="text" id="locker_amount11" name="locker_amount" class="form-control @error('locker_amount') is-invalid @enderror" value="{{ old('locker_amount', $transaction?->locker_amount ?? 0) }}" readonly>
                                    @error('locker_amount')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-6 form-group {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer2">
                                    <label class="form-label" for="locker_no11">Locker No.</label>
                                    <input type="text" class="form-control digit-only @error('locker_no') is-invalid @enderror" name="locker_no" id="locker_no11" placeholder="Enter Locker No." value="{{ old('locker_no', ((optional($transaction)->locker_amount > 0) && !empty(optional($learner)->locker_no)) ? $learner->locker_no : '') }}">
                                    @error('locker_no')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                                @endif

                                @if(!in_array('6', toggleHideField()))
                                <div class="col-md-6 form-group">
                                    <label class="form-label" for="discountType11">Discount Type</label>
                                    <select id="discountType11" name="discount_type" class="form-select @error('discount_type') is-invalid @enderror">
                                        <option value="">Select Discount Type</option>
                                        <option value="percentage" {{ old('discount_type') == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                        <option value="amount" {{ !empty($transaction?->discount_amount) ? 'selected' : '' }}>Amount (₹)</option>
                                    </select>
                                    @error('discount_type')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>

                                <div class="col-md-6 form-group">
                                    <label class="form-label" for="discount_amount11">Discount Amount (<span id="typeVal11">INR / %</span>)</label>
                                    <input type="text" class="form-control @error('discount_amount') is-invalid @enderror" name="discount_amount" id="discount_amount11" placeholder="0" value="{{ $transaction->discount_amount ?? 0 }}" readonly>
                                    @error('discount_amount')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- CARD 4: PAYMENT & DUE SETTLEMENT --}}
                <div class="booking-card">
                    <div class="booking-card-header header-blue">
                        <div class="booking-header-left">
                            <div class="booking-header-icon">
                                <i class="fa-solid fa-credit-card"></i>
                            </div>
                            <div>
                                <h4 class="booking-header-title">Payment &amp; Due Settlement</h4>
                                <p class="booking-header-subtitle">Record paid amount, calculate pending balances, and choose payment mode.</p>
                            </div>
                        </div>
                    </div>
                    <div class="booking-card-body">
                        <div class="row g-3">
                            <div class="col-md-4 form-group">
                                <label class="form-label" for="paid_amount11">Final Payable Amount (INR) <span class="required-star">*</span></label>
                                <input id="paid_amount11" class="form-control digit-only price-highlight-input @error('paid_amount') is-invalid @enderror" value="{{ old('paid_amount') }}" name="paid_amount" placeholder="0">
                                <span id="pending_amt11" class="text-danger fw-bold d-block mt-1" style="font-size: 0.8rem;"></span>
                                @error('paid_amount')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror

                                @if($customer->payment_screenshot)
                                    <a href="{{ asset($customer->payment_screenshot) }}" class="btn-view-screenshot view-image mt-2" title="View Payment Proof Screenshot">
                                        <i class="fa-regular fa-image me-1"></i> View Payment Screenshot
                                    </a>
                                @endif
                            </div>

                            <div class="col-md-4 form-group">
                                <label class="form-label" for="due_date11">Due Date <span class="required-star">*</span></label>
                                <input type="date" class="form-control duedate" placeholder="Enter Due Date" name="due_date" id="due_date11" value="{{ old('due_date', $customer->due_date ?? '') }}" readonly>
                            </div>

                            <div class="col-md-4 form-group">
                                <label class="form-label" for="payment_mode">Payment Mode <span class="required-star">*</span></label>
                                <select name="payment_mode" id="payment_mode" class="form-select @error('payment_mode') is-invalid @enderror">
                                    <option value="">Select Payment Mode</option>
                                    <option value="online" {{ old('payment_mode', $customer->payment_mode ?? '') == 'online' ? 'selected' : '' }}>Online</option>
                                    <option value="offline" {{ old('payment_mode', $customer->payment_mode ?? '') == 'offline' ? 'selected' : '' }}>Offline</option>
                                </select>
                                @error('payment_mode')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            @if(in_array($customer->type, ['qr_seat_book', 'learner_book']))
                            @if(notificationActive())
                            <div class="col-md-12 form-group">
                                <label class="form-label" for="sended_message_type">Send Reminders Via (Optional)</label>
                                <select class="form-select" name="sended_message_type" id="sended_message_type">
                                    <option value="">Select Reminder Channel</option>
                                    <option value="whatsapp">WhatsApp Message Only</option>
                                    <option value="text">Text Message Only</option>
                                    <option value="both">Both (WhatsApp &amp; Text Message)</option>
                                    <option value="no">No Reminders</option>
                                </select>
                            </div>
                            @endif
                            @endif
                        </div>
                    </div>
                </div>

                {{-- CARD 5: OTHER OPTIONAL & KYC FIELDS (COLLAPSIBLE) --}}
                @if(in_array($customer->type, ['qr_seat_book', 'learner_book', 'demo-bookings']))
                @if(!in_array('7', toggleHideField()))
                <div class="booking-card">
                    <div class="booking-card-header header-purple collapsible-header" data-bs-toggle="collapse" data-bs-target="#otherKycCollapse" aria-expanded="false" aria-controls="otherKycCollapse">
                        <div class="booking-header-left">
                            <div class="booking-header-icon">
                                <i class="fa-solid fa-id-card"></i>
                            </div>
                            <div>
                                <h4 class="booking-header-title">Other Optional &amp; KYC Fields</h4>
                                <p class="booking-header-subtitle">Profile photo, ID proof scan, father name, address, and remarks.</p>
                            </div>
                        </div>
                        <div class="booking-header-toggle">
                            <i class="fa-solid fa-chevron-down toggle-icon"></i>
                        </div>
                    </div>
                    <div class="collapse @if($errors->has('profile_picture') || $errors->has('id_proof_file') || $errors->has('id_proof_number')) show @endif" id="otherKycCollapse">
                        <div class="booking-card-body">
                            <div class="row g-3">
                                @if(!in_array('8', toggleHideField()))
                                <div class="col-md-6 form-group">
                                    <label class="form-label" for="profile_picture">Upload Profile Photo</label>
                                    <input type="file" class="form-control image-cropper @error('profile_picture') is-invalid @enderror" name="profile_picture" id="profile_picture" autocomplete="off" accept=".jpeg, .jpg, .png, .webp">
                                    <img class="preview-img mt-2 rounded" style="display:none; max-width:100px;">
                                    @error('profile_picture')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    @if($customer->profile_picture)
                                        <div class="mt-1">
                                            <a href="{{ asset($customer->profile_picture) }}" class="btn-view-screenshot view-image">
                                                <i class="fa-regular fa-image me-1"></i> View Current Photo
                                            </a>
                                        </div>
                                    @endif
                                </div>
                                @endif

                                @if(!in_array('30', toggleHideField()))
                                <div class="col-md-6 form-group">
                                    <label class="form-label" for="alternate_mobile">Alternate Mobile No.</label>
                                    <input type="text" class="form-control digit-only" name="alternate_mobile" id="alternate_mobile" maxlength="10" minlength="10" placeholder="Enter Alternate Mobile No." value="{{ old('alternate_mobile') ?? ($customer->alternate_mobile ?? '') }}">
                                </div>
                                @endif

                                @if(!in_array('29', toggleHideField()))
                                <div class="col-md-6 form-group">
                                    <label class="form-label" for="father_name">Father's Name</label>
                                    <input type="text" class="form-control char-only" name="father_name" id="father_name" placeholder="Enter Father's Name" value="{{ old('father_name') }}">
                                </div>
                                @endif

                                @if(!in_array('4', toggleHideField()))
                                <div class="col-md-6 form-group">
                                    <label class="form-label" for="exam_id">Preparing For Exam</label>
                                    <select name="exam_id" id="exam_id" class="form-select">
                                        <option value="">Learner is Preparing For...</option>
                                        @foreach($exams as $value)
                                            <option value="{{ $value->id }}" {{ (old('exam_id') ?? ($customer->exam_id ?? '')) == $value->id ? 'selected' : '' }}>
                                                {{ $value->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif

                                @if(!in_array('5', toggleHideField()))
                                <div class="col-md-4 form-group">
                                    <label class="form-label" for="id_proof_name">ID Proof Document</label>
                                    <select class="form-select" name="id_proof_name" id="id_proof_name">
                                        <option value="">Select ID Proof</option>
                                        <option value="1" {{ (old('id_proof_name') ?? ($customer->id_proof_name ?? '')) == '1' ? 'selected' : '' }}>Aadhar Card</option>
                                        <option value="2" {{ (old('id_proof_name') ?? ($customer->id_proof_name ?? '')) == '2' ? 'selected' : '' }}>Driving License</option>
                                        <option value="4" {{ (old('id_proof_name') ?? ($customer->id_proof_name ?? '')) == '4' ? 'selected' : '' }}>Pan Card</option>
                                        <option value="5" {{ (old('id_proof_name') ?? ($customer->id_proof_name ?? '')) == '5' ? 'selected' : '' }}>Voter ID</option>
                                        <option value="3" {{ (old('id_proof_name') ?? ($customer->id_proof_name ?? '')) == '3' ? 'selected' : '' }}>Other</option>
                                    </select>
                                </div>

                                <div class="col-md-4 form-group">
                                    <label class="form-label" for="id_proof_number">ID Proof Number</label>
                                    <input type="text" class="form-control @error('id_proof_number') is-invalid @enderror" name="id_proof_number" id="id_proof_number" placeholder="Enter ID Proof No." value="{{ old('id_proof_number') ?? ($customer->id_proof_number ?? '') }}">
                                    @error('id_proof_number')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>

                                <div class="col-md-4 form-group">
                                    <label class="form-label" for="id_proof_file">Upload Proof Scan</label>
                                    <input type="file" class="form-control id_proof_file image-cropper @error('id_proof_file') is-invalid @enderror" name="id_proof_file" id="id_proof_file" autocomplete="off">
                                    <img class="preview-img one mt-2 rounded" style="display:none; max-width:150px;">
                                    @error('id_proof_file')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    @if($customer->id_proof_file)
                                        <div class="mt-1">
                                            <a href="{{ asset($customer->id_proof_file) }}" class="btn-view-screenshot view-image">
                                                <i class="fa-regular fa-file-image me-1"></i> View Uploaded Proof
                                            </a>
                                        </div>
                                    @endif
                                </div>
                                @endif

                                @if(!in_array('32', toggleHideField()))
                                <div class="col-md-12 form-group">
                                    <label class="form-label" for="address">Address</label>
                                    <textarea class="form-control" name="address" id="address" rows="2" placeholder="Enter permanent/current address">{{ old('address') ?? ($customer->address ?? '') }}</textarea>
                                </div>
                                @endif

                                @if(!in_array('31', toggleHideField()))
                                <div class="col-md-12 form-group">
                                    <label class="form-label" for="remark">Remark</label>
                                    <textarea class="form-control" name="remark" id="remark" rows="2" placeholder="Enter any administrative remark">{{ old('remark') ?? ($customer->remark ?? '') }}</textarea>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                @endif

                {{-- SUBMIT ACTION BUTTON --}}
                <div class="form-action-bar">
                    <button type="submit" class="btn-submit-approve" id="approveSubmitBtn">
                        <i class="fa-solid fa-circle-check me-2"></i> Verify and Allot Seat
                    </button>
                </div>

            </div>
        </form>

    </div>
</div>

{{-- Profile / Image Preview Modal --}}
<div id="imageViewModal" class="image-modal" style="display:none;">
    <div class="image-modal-content">
        <span class="close-modal" role="button" aria-label="Close">&times;</span>
        <img src="" id="modalImage" alt="Preview Image">
    </div>
</div>

<script>
$(document).ready(function() {
    // Mobile Top Card Collapse Toggle
    $('#btnToggleDetails').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $grid = $('#glassInfoGrid');
        $grid.toggleClass('is-collapsed');
        $btn.toggleClass('is-collapsed');
        var isExpanded = !$grid.hasClass('is-collapsed');
        $btn.attr('aria-expanded', isExpanded ? 'true' : 'false');
    });

    // Seat Availability and Verification Scripts
    const toggleHiddenFields = @json(toggleHideField());
    const plan_id11 = $('#plan_id11').val();
    const selectedPlanType = $('#plan_type_id11').val();
    const learner_id = $('#renew_learner_id').val();
    const seatNo = $('#seat_id11').val();

    var seatDisplayMap = @json(
        collect(generateSeatNumbers())->mapWithKeys(function($seat) {
            if (!empty($seat['floor']) && !empty($seat['floor_name'])) {
                return [$seat['main'] => $seat['floor'] . ' (' . $seat['floor_name'] . ')'];
            } else {
                return [$seat['main'] => $seat['main']];
            }
        })
    );

    if (seatNo !== '' && seatNo !== 0 && seatNo !== undefined) {
        var seatDisplay = seatDisplayMap[seatNo] ?? seatNo;
        $('#qr_general_seat').val('no').trigger('change');
    } else if (toggleHiddenFields.includes('12') === true) {
        $('#qr_general_seat').val('no').trigger('change');
    } else {
        @can('has-permission', 'General Seat Booking')
            $('#qr_general_seat').val('yes').trigger('change');
        @else
            $('#qr_general_seat').val('no').trigger('change');
            $('#qr_general_seat option[value="yes"]').hide();
        @endcan
        $('#seat_id11').prop('disabled', true);
    }

    $('#qr_general_seat').on('change', function () {
        if ($(this).val() === 'no') {
            $('#seat_id11').prop('disabled', false);
            $('#seat_id11').val(seatNo);
            getTypeSeatwiseVerify(seatNo, selectedPlanType);
        } else {
            $('#seat_id11').val($('#seat_id11 option:first').val());
            $('#seat_id11').prop('disabled', true);
            getTypeSeatwiseVerify('', selectedPlanType);
        }
    });

    if (learner_id) {
        if (!seatNo || seatNo === 'gen') {
            getTypeSeatwiseVerify('', selectedPlanType);
        } else {
            getTypeSeatwiseVerify(seatNo, selectedPlanType);
        }
    } else {
        if (!seatNo || seatNo === 'gen') {
            getTypeSeatwiseVerify('', selectedPlanType);
        } else {
            getTypeSeatwiseVerify(seatNo, selectedPlanType);
        }
    }

    getPlanPriceVerify(selectedPlanType, plan_id11);
    calculatePaidTotalAmount();

    var lockerCheck = $('#toggleFieldCheckbox11').val();
    if (lockerCheck == 'yes') {
        $('#locker_no11').attr('readonly', false);
    }

    if ($('#discountType11').val() == 'percentage' || $('#discountType11').val() == 'amount') {
        $('#discount_amount11').attr('readonly', false);
    } else {
        $('#discount_amount11').attr('readonly', true);
    }

    $('#seat_id11').on('change', function() {
        const newSeat = $(this).val();
        const learner_id = $('#renew_learner_id').val();
        const selectedPlanTyp = $('#plan_type_id12').val();
        let seat_id12 = $('#seat_id12').val();

        if (learner_id) {
            if (!newSeat || newSeat === 'gen') {
                if (String(newSeat) === String(seat_id12)) {
                    getTypeSeatwiseVerify('', selectedPlanTyp);
                } else {
                    getTypeSeatwiseVerify('', null);
                }
            } else {
                if (String(newSeat) === String(seat_id12)) {
                    getTypeSeatwiseVerify(newSeat, selectedPlanTyp);
                } else {
                    getTypeSeatwiseVerify(newSeat, null);
                }
            }
        } else {
            if (!newSeat || newSeat === 'gen') {
                getTypeSeatwiseVerify('', selectedPlanTyp);
            } else {
                getTypeSeatwiseVerify(newSeat, selectedPlanTyp);
            }
        }

        seat_id12 = newSeat;
    });

    $('#plan_id11').on('change', function(event) {
        event.preventDefault();
        const plan_id11 = $(this).val();
        const plan_type_id11 = $('#plan_type_id11').val();
        var lockerCheck = $('#toggleFieldCheckbox11').val();
        if (plan_type_id11 && plan_id11) {
            getPlanPriceVerify(plan_type_id11, plan_id11);
            calculatePaidTotalAmount();
            if (lockerCheck == 'yes') {
                lockerAmtGet(plan_id11);
            }
        } else {
            $("#plan_price11").val('');
        }
    });

    $('#plan_type_id11').on('change', function(event) {
        event.preventDefault();
        const plan_type_id11 = $(this).val();
        const plan_id11 = $('#plan_id11').val();
        var lockerCheck = $('#toggleFieldCheckbox11').val();
        if (plan_type_id11 && plan_id11) {
            getPlanPriceVerify(plan_type_id11, plan_id11);
            if (lockerCheck == 'yes') {
                lockerAmtGet(plan_id11);
            }
        } else {
            $("#plan_price11").val('');
        }
    });

    $('#toggleFieldCheckbox11').on('change', function() {
        var needLocker = $(this).val();
        const plan_id11 = $('#plan_id11').val();

        if (needLocker === 'yes') {
            $('#locker_no11').removeAttr('readonly');
            lockerAmtGet(plan_id11);
        } else {
            $('#locker_amount11').attr('readonly', true);
            $('#locker_no11').attr('readonly', true);
            $('#locker_amount11').val(0);
        }
        calculatePaidTotalAmount();
        $('#pending_amt11').val("");
    });

    $('#discountType11').on('change', function() {
        const type = $(this).val();
        if (type === 'percentage') {
            $('#typeVal11').text('%');
            $('#discount_amount11').attr('readonly', false);
        } else if (type === 'amount') {
            $('#typeVal11').text('INR');
            $('#discount_amount11').attr('readonly', false);
        } else {
            $('#typeVal11').text('INR / %');
            $('#discount_amount11').attr('readonly', true);
        }
        calculatePaidTotalAmount();
        $('#pending_amt11').val("");
    });

    $('#discount_amount11').on('input', function() {
        calculatePaidTotalAmount();
    });

    $('#paid_amount11').on('input', function() {
        calculatePendingAmt($(this).val());
    });

    function getPlanPriceVerify(plan_type_id11, plan_id11) {
        if (plan_type_id11 && plan_id11) {
            $.ajax({
                url: "{{ route('getPricePlanwise') }}",
                type: 'GET',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "plan_type_id": plan_type_id11,
                    "plan_id": plan_id11,
                },
                dataType: 'json',
                success: function(html) {
                    if (html && html !== undefined) {
                        $('#pending_amt11').html('');
                        $("#plan_price11").prop("value", html);
                        calculatePaidTotalAmount();
                        $("#error-message").hide();
                    } else {
                        $("#plan_price11").val("");
                        $("#pending_amt11").html("No Plan Price Added Yet.");
                        $("#paid_amount11").val("");
                    }
                }
            });
        } else {
            $("#plan_price11").empty();
            $("#paid_amount11").empty();
        }
    }

    function getTypeSeatwiseVerify(seatId, selectedPlanType = null) {
        $('#plan_type_id11').empty().append('<option value="">Choose Shift</option>');
        $.ajax({
            url: "{{ route('getPlanTypeForRenew') }}",
            type: 'GET',
            data: {
                "_token": "{{ csrf_token() }}",
                "seatNo": seatId,
                "planType": selectedPlanType,
            },
            dataType: 'json',
            success: function(html) {
                if (html && html.length > 0) {
                    $.each(html, function(index, planType) {
                        let isSelected = (selectedPlanType && selectedPlanType == planType.id) ? 'selected' : '';
                        $("#plan_type_id11").append('<option value="' + planType.id + '" ' + isSelected + '>' + planType.name + '</option>');
                    });
                } else {
                    $("#plan_type_id11").empty().append('<option value="">No Plan Types Available</option>');
                }
                let finalPlanType = $('#plan_type_id11').val();
                let planId = $('#plan_id11').val();
                getPlanPriceVerify(finalPlanType, planId);
            },
            error: function(xhr, status, error) {
                console.error("AJAX error:", status, error);
            }
        });
    }

    function lockerAmtGet(plan_id11) {
        $.get("{{ route('locker.price') }}", { plan_id: plan_id11 })
            .done(function(json) {
                $('#locker_amount11').val(json.price);
                calculatePaidTotalAmount();
            })
            .fail(function() {
                $('#locker_amount11').val('').prop('readonly', true);
                calculatePaidTotalAmount();
            });
    }

    function calculatePaidTotalAmount() {
        const planPrice = parseFloat($('#plan_price11').val()) || 0;
        const lockerAmount = parseFloat($('#locker_amount11').val()) || 0;
        const discountRaw = parseFloat($('#discount_amount11').val()) || 0;
        const discountType = $('#discountType11').val();

        var discountAmount = 0;
        if (discountType === 'percentage') {
            discountAmount = ((planPrice + lockerAmount) * discountRaw) / 100;
        } else if (discountType === 'amount') {
            discountAmount = discountRaw;
        }

        if (discountType !== 'percentage' && discountType !== 'amount') {
            $('#discount_amount11').val("");
        }

        const autoPaid = planPrice + lockerAmount - discountAmount;
        $('#paid_amount11').val(autoPaid);
    }

    function calculatePendingAmt() {
        const planPrice = parseFloat($('#plan_price11').val()) || 0;
        const paidAmount = parseFloat($('#paid_amount11').val()) || 0;
        const lockerAmount = parseFloat($('#locker_amount11').val()) || 0;
        const discountRaw = parseFloat($('#discount_amount11').val()) || 0;
        const discountType = $('#discountType11').val();

        var discountAmount = 0;
        if (discountType === 'percentage') {
            discountAmount = ((planPrice + lockerAmount) * discountRaw) / 100;
        } else if (discountType === 'amount') {
            discountAmount = discountRaw;
        }

        const effectivePaid = planPrice + lockerAmount - discountAmount;
        const pendingAmount = effectivePaid - paidAmount;

        $('#pending_amt11').val(pendingAmount);

        if (pendingAmount > 0) {
            $('#pending_amt11').html('Pending Amount: ₹' + pendingAmount);
            $('#due_date11').removeAttr('readonly');
        } else if (pendingAmount < 0) {
            $('#pending_amt11').html('High price not allowed: ₹' + pendingAmount);
            $('#due_date11').attr('readonly', true);
        } else {
            $('#pending_amt11').html('');
            $('#due_date11').attr('readonly', true);
        }
    }

    // Modal Image Preview
    $(document).on("click", 'a.view-image, img.view-image', function (e) {
        var imageUrl = $(this).attr("href") || $(this).attr("src");
        if (!imageUrl) return;
        if ($(this).is('a') && !imageUrl.match(/\.(jpg|jpeg|png|webp)$/i)) {
            return;
        }
        e.preventDefault();
        $("#modalImage").attr("src", imageUrl);
        $("#imageViewModal").fadeIn(200);
    });

    $(".close-modal").on("click", function () {
        $("#imageViewModal").fadeOut(200);
        $("#modalImage").attr("src", "");
    });

    $("#imageViewModal").on("click", function (e) {
        if ($(e.target).is(this)) {
            $(this).fadeOut(200);
            $("#modalImage").attr("src", "");
        }
    });
});
</script>

@endsection
