@extends('layouts.library')

@section('content')
<link rel="stylesheet" href="{{ asset('public/css/learner-list.css') }}?v={{ time() }}" />

<!-- Profile Image Preview Modal -->
<div id="imageViewModal" class="image-modal" style="display:none;opacity:0;" aria-hidden="true">
    <div class="image-modal-content">
        <span class="close-modal" role="button" tabindex="0" aria-label="Close">&times;</span>
        <img src="" id="modalImage" alt="Profile photo preview">
    </div>
</div>

@php
    $hasActiveFilters = request()->filled('search') || request()->filled('status');
@endphp

{{-- Top Actions Bar (Aligned & Spaced) --}}
<div class="learner-top-action-bar">
    <a href="javascript:void(0)" class="btn btn-primary export btn-icon-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Filter" id="learnerFilterToggleBtn">
        <i class="fa-solid fa-filter"></i>
    </a>
    <a href="{{ route('demo-users.create') }}" class="btn btn-primary export">
        <i class="fa-solid fa-plus"></i> Add Inquiry
    </a>
</div>

{{-- Modern Tile Bar Filter --}}
<div class="row mb-3 learner-filter-module" id="learnerFilterContainer" style="{{ $hasActiveFilters ? '' : 'display: none;' }}">
    <div class="col-lg-12">
        <div class="learner-filter-card">
            <form action="{{ route('demo-users.index') }}" method="GET" class="learner-filter-form" id="learnerFilterForm">
                <div class="learner-filter-grid">
                    <!-- Search Learner -->
                    <div class="filter-field-box filter-search-box" id="filterSearchBox">
                        <div class="filter-field-icon icon-slate">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>
                        <div class="filter-field-content">
                            <span class="filter-field-label">Search Inquiry</span>
                            <input type="text" 
                                   class="filter-input" 
                                   name="search" 
                                   id="search_inquiry_input"
                                   placeholder="Enter Name, Mobile, Email or ID" 
                                   value="{{ request()->get('search') }}"
                                   autocomplete="off">
                        </div>
                    </div>

                    <!-- Payment Status -->
                    @php
                        $selectedStatusName = 'All Inquiries';
                        if (request()->get('status') == 'paid') {
                            $selectedStatusName = 'Paid';
                        } elseif (request()->get('status') == 'unpaid') {
                            $selectedStatusName = 'Unpaid';
                        }
                    @endphp
                    <div class="filter-field-box custom-dropdown" id="dropdownStatus" data-dropdown="status">
                        <div class="filter-field-icon icon-green">
                            <i class="fa-solid fa-credit-card"></i>
                        </div>
                        <div class="filter-field-content">
                            <span class="filter-field-label">Payment Status</span>
                            <span class="filter-field-value" id="status_display">{{ $selectedStatusName }}</span>
                            <input type="hidden" name="status" id="status" value="{{ request()->get('status') }}">
                        </div>
                        <div class="filter-field-chevron">
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>

                        <div class="custom-dropdown-menu" id="status_dropdown_menu">
                            <div class="dropdown-item-option {{ !request()->filled('status') ? 'active' : '' }}" data-value="">
                                <i class="fa-solid fa-list item-icon text-primary"></i>
                                <span>All Inquiries</span>
                            </div>
                            <div class="dropdown-item-option {{ request()->get('status') == 'paid' ? 'active' : '' }}" data-value="paid">
                                <i class="fa-solid fa-circle-check item-icon text-success"></i>
                                <span>Paid</span>
                            </div>
                            <div class="dropdown-item-option {{ request()->get('status') == 'unpaid' ? 'active' : '' }}" data-value="unpaid">
                                <i class="fa-solid fa-circle-xmark item-icon text-danger"></i>
                                <span>Unpaid</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="filter-actions-group">
                        <button type="submit" class="filter-btn-search" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <span>Search</span>
                        </button>
                        <button type="button" id="clearFilter" class="filter-btn-clear" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Clear Filter">
                            <i class="fa-solid fa-rotate-right"></i>
                            <span>Clear</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@if($qrbookings?->count() > 0)

<div class="learner-list-module demo-inquiry-list-section">
    <div class="mb-3 set-table">
        <p class="m-0"><b>{{ $qrbookings->count() }} Records</b></p>
    </div>

    @foreach($qrbookings as $key => $value)
    @php
        $startDate = !empty($value->plan_start_date) ? \Carbon\Carbon::parse($value->plan_start_date) : null;
        $isPaid = !empty($value->payment_screenshot);
    @endphp
    <div class="row">
        <div class="col-lg-12">
            <div class="learner-card">
                
                {{-- ================= DESKTOP LAYOUT ================= --}}
                <div class="desktop-only-section">
                    {{-- Top Row: Seat Box + Status on left, Actions on right --}}
                    <div class="learner-top-row">
                        <div class="learner-top-left">
                            <div class="seat-badge-box">
                                <span class="seat-label">Seat No. :</span>
                                <span class="seat-val">{{ $value->seat_no ? getSeatDisplayByMainNo($value->seat_no) : 'GEN' }}</span>
                            </div>
                            <div class="learner-meta-block">
                                <div class="learner-expiry-line">
                                    @if($startDate)
                                        <span style="color: #d97706 !important;">
                                            <i class="fa-regular fa-clock me-1"></i> Starts on {{ $startDate->format('j M Y') }}
                                        </span>
                                    @else
                                        <span class="text-muted"><i class="fa-regular fa-clock me-1"></i> Demo Booking</span>
                                    @endif
                                </div>

                                @if(!$isPaid)
                                    <span class="refund-due-badge ms-2">
                                        <i class="fa-regular fa-clock me-1"></i> Unpaid (₹{{ number_format($value->total_amount ?? 0, 0) }})
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Desktop Action Strip --}}
                        <ul class="learner-actions-strip">
                            {{-- Approve --}}
                            @if($value->payment_screenshot && $value->payment_mode == 'online')
                            <li>
                                <form action="{{ route('booking.details.approve') }}" method="POST" class="approve-form d-inline">
                                    @csrf
                                    <input type="hidden" name="booking_id" value="{{ $value->id }}">
                                    <input type="hidden" name="direct_validate" value="1">
                                    <button type="submit" class="action-btn text-success noLoader" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Approve Booking">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </form>
                            </li>
                            @endif

                            {{-- View Details --}}
                            <li>
                                <a href="{{ route('booking.details', $value->id) }}" class="action-btn" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="View Inquiry Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </li>

                            {{-- WhatsApp --}}
                            @if(!empty($value->mobile))
                            <li>
                                <a href="https://wa.me/+91{{ $value->mobile }}?text={{ urlencode('Your demo plan is about to expire. Please book your monthly seat to experience the Library.\n\n– Team ' . (getCurrentBranchName() ?: 'Library')) }}" target="_blank" class="action-btn" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Send WhatsApp Message">
                                    <i class="fab fa-whatsapp text-success"></i>
                                </a>
                            </li>
                            @endif

                            {{-- Delete --}}
                            <li>
                                <a href="javascript:void(0)" class="action-btn btn-danger-hover delete-booking" data-id="{{ $value->id }}" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Delete Inquiry">
                                    <i class="fas fa-trash text-danger"></i>
                                </a>
                            </li>
                        </ul>
                    </div>

                    {{-- Bottom Grid: 5 Columns (identical to Learner List) --}}
                    <div class="learner-bottom-grid">
                        {{-- Column 1: Learner Identity & Contact Info --}}
                        <div class="learner-profile-col">
                            <div class="avatar-wrap">
                                <a href="{{ $value->profile_picture ? asset($value->profile_picture) : asset('public/img/student_profile.jpeg') }}" class="view-image learner-list-profile-photo" title="View profile photo">
                                    <img src="{{ $value->profile_picture ? asset($value->profile_picture) : asset('public/img/student_profile.jpeg') }}" alt="{{ $value->name }}" class="avatar-img">
                                </a>
                                <span class="avatar-status-dot {{ $isPaid ? 'dot-active' : 'dot-extension' }}" title="{{ $isPaid ? 'Paid' : 'Unpaid' }}"></span>
                            </div>
                            <div class="learner-details-text">
                                <h5 class="learner-name-title">{{ $value->name }}</h5>
                                <div class="detail-row">
                                    <span class="detail-label">UID :</span>
                                    <a href="{{ route('booking.details', $value->id) }}" class="detail-value">#{{ $value->id }}</a>
                                    <button type="button" class="copy-btn copy-action-btn" data-copy="#{{ $value->id }}" title="Copy UID">
                                        <i class="fa-regular fa-clone"></i>
                                    </button>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">M :</span>
                                    <a href="tel:+91-{{ $value->mobile }}" class="detail-value">+91-{{ display_learner_mobile($value->mobile) }}</a>
                                    @if($value->mobile)
                                    <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $value->mobile }}" title="Copy Mobile">
                                        <i class="fa-regular fa-clone"></i>
                                    </button>
                                    @endif
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">E :</span>
                                    @if($value->email)
                                        <a href="mailto:{{ $value->email }}" class="detail-value detail-email">{{ display_learner_email($value->email) }}</a>
                                    @else
                                        <span class="text-danger detail-email" style="font-size: 11.5px;"><i class="fa-solid fa-xmark"></i> Email ID Not Available</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Column 2: Subscription Info --}}
                        <div class="info-stat-block">
                            <div class="info-icon-box info-icon-purple">
                                <i class="fa-regular fa-file-lines"></i>
                            </div>
                            <div class="info-text">
                                <span class="info-label">Subscription Info</span>
                                <span class="info-value">
                                    {{ $value->planType->name ?? 'Demo Plan' }}@if(!empty($value->plan->name)) ({{ $value->plan->name }})@endif
                                </span>
                            </div>
                        </div>

                        {{-- Column 3: Plan Timing & Duration --}}
                        <div class="info-stat-block">
                            <div class="info-icon-box info-icon-blue">
                                <i class="fa-regular fa-calendar-days"></i>
                            </div>
                            <div class="info-text">
                                <span class="info-label">Plan Duration</span>
                                <span class="info-value">
                                    @if($startDate)
                                        Starts on {{ $startDate->format('j M Y') }}
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>
                        </div>

                        {{-- Column 4: Payment Status --}}
                        <div class="info-stat-block">
                            <div class="info-icon-box info-icon-green">
                                <i class="fa-regular fa-credit-card"></i>
                            </div>
                            <div class="info-text">
                                <span class="info-label">Payment Status</span>
                                <div class="d-flex align-items-center">
                                    @if($isPaid)
                                        <span class="text-success fw-bold payment-status-value">Paid ₹{{ number_format($value->total_amount ?? 0, 0) }}</span>
                                        @if($value->payment_screenshot)
                                            <a href="{{ asset($value->payment_screenshot) }}" target="_blank" class="receipt-btn noLoader ms-1" title="View Receipt Screenshot">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                        @endif
                                    @else
                                        <span class="text-danger fw-bold payment-status-value">Unpaid ₹{{ number_format($value->total_amount ?? 0, 0) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Column 5: Payment Mode --}}
                        <div class="info-stat-block">
                            <div class="info-icon-box info-icon-amber">
                                <i class="fa-solid fa-receipt"></i>
                            </div>
                            <div class="info-text">
                                <span class="info-label">Payment Mode</span>
                                <span class="info-value text-capitalize">
                                    {{ $value->payment_mode ? ucfirst($value->payment_mode) : 'Offline' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ================= MOBILE LAYOUT ================= --}}
                <div class="mobile-only-section">
                    {{-- Top Profile Header --}}
                    <div class="mobile-profile-header">
                        <div class="mobile-profile-left">
                            <div class="avatar-wrap">
                                <a href="{{ $value->profile_picture ? asset($value->profile_picture) : asset('public/img/student_profile.jpeg') }}" class="view-image learner-list-profile-photo" title="View profile photo">
                                    <img src="{{ $value->profile_picture ? asset($value->profile_picture) : asset('public/img/student_profile.jpeg') }}" alt="{{ $value->name }}" class="avatar-img">
                                </a>
                                <span class="avatar-status-dot {{ $isPaid ? 'dot-active' : 'dot-extension' }}" title="{{ $isPaid ? 'Paid' : 'Unpaid' }}"></span>
                            </div>
                            <div class="mobile-details-text">
                                <div class="mobile-name-row">
                                    <h5 class="mobile-name">{{ $value->name }}</h5>
                                    <span class="mobile-seat-badge">Seat {{ $value->seat_no ? getSeatDisplayByMainNo($value->seat_no) : 'GEN' }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">UID :</span>
                                    <a href="{{ route('booking.details', $value->id) }}" class="detail-value">#{{ $value->id }}</a>
                                    <button type="button" class="copy-btn copy-action-btn" data-copy="#{{ $value->id }}" title="Copy UID">
                                        <i class="fa-regular fa-clone"></i>
                                    </button>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">M :</span>
                                    <a href="tel:+91-{{ $value->mobile }}" class="detail-value">+91-{{ display_learner_mobile($value->mobile) }}</a>
                                    @if($value->mobile)
                                    <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $value->mobile }}" title="Copy Mobile">
                                        <i class="fa-regular fa-clone"></i>
                                    </button>
                                    @endif
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">E :</span>
                                    @if($value->email)
                                        <a href="mailto:{{ $value->email }}" class="detail-value detail-email">{{ display_learner_email($value->email) }}</a>
                                    @else
                                        <span class="text-danger detail-email" style="font-size: 11.5px;"><i class="fa-solid fa-xmark"></i> Email ID Not Available</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('booking.details', $value->id) }}" class="mobile-chevron-link" title="View Details">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </div>

                    {{-- Collapsible Banner --}}
                    <div class="mobile-expiry-banner {{ $isPaid ? 'banner-warning' : 'banner-danger' }} js-mobile-collapsible-toggle" role="button" tabindex="0">
                        <div class="mobile-expiry-text">
                            <i class="fa-regular fa-clock"></i>
                            <span>
                                @if($startDate)
                                    Starts on {{ $startDate->format('j M Y') }}
                                @else
                                    Demo Booking
                                @endif
                            </span>
                        </div>
                        <i class="fa-solid fa-chevron-right mobile-expand-icon"></i>
                    </div>

                    {{-- Collapsible Subscription Info Body --}}
                    <div class="mobile-collapsible-content">
                        {{-- 1. Subscription Info --}}
                        <div class="mobile-info-item">
                            <div class="info-icon-box info-icon-purple">
                                <i class="fa-regular fa-file-lines"></i>
                            </div>
                            <div class="info-text">
                                <span class="info-label">Subscription Info</span>
                                <span class="info-value">
                                    {{ $value->planType->name ?? 'Demo Plan' }}@if(!empty($value->plan->name)) ({{ $value->plan->name }})@endif
                                </span>
                            </div>
                        </div>

                        {{-- 2. Plan Duration --}}
                        <div class="mobile-info-item">
                            <div class="info-icon-box info-icon-blue">
                                <i class="fa-regular fa-calendar-days"></i>
                            </div>
                            <div class="info-text">
                                <span class="info-label">Plan Duration</span>
                                <span class="info-value">
                                    @if($startDate)
                                        Starts on {{ $startDate->format('j M Y') }}
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>
                        </div>

                        {{-- 3. Payment Status --}}
                        <div class="mobile-info-item">
                            <div class="info-icon-box info-icon-green">
                                <i class="fa-regular fa-credit-card"></i>
                            </div>
                            <div class="info-text">
                                <span class="info-label">Payment Status</span>
                                @if($isPaid)
                                    <span class="text-success fw-bold payment-status-value">Paid ₹{{ number_format($value->total_amount ?? 0, 0) }}</span>
                                @else
                                    <span class="text-danger fw-bold payment-status-value">Unpaid ₹{{ number_format($value->total_amount ?? 0, 0) }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- 4. Payment Mode --}}
                        <div class="mobile-info-item">
                            <div class="info-icon-box info-icon-amber">
                                <i class="fa-solid fa-receipt"></i>
                            </div>
                            <div class="info-text">
                                <span class="info-label">Payment Mode</span>
                                <span class="info-value text-capitalize">
                                    {{ $value->payment_mode ? ucfirst($value->payment_mode) : 'Offline' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Actions Section with Horizontally Scrollable Row --}}
                    <div class="mobile-actions-container">
                        <div class="mobile-actions-header">
                            <h6 class="mobile-actions-title">Actions</h6>
                            <span class="mobile-actions-seeall">See All <i class="fa-solid fa-chevron-right" style="font-size: 11px;"></i></span>
                        </div>
                        <div class="mobile-actions-scroll">
                            @if($value->payment_screenshot && $value->payment_mode == 'online')
                            <div class="mobile-action-item">
                                <form action="{{ route('booking.details.approve') }}" method="POST" class="approve-form d-inline">
                                    @csrf
                                    <input type="hidden" name="booking_id" value="{{ $value->id }}">
                                    <input type="hidden" name="direct_validate" value="1">
                                    <button type="submit" class="action-btn text-success border-0 bg-transparent" title="Approve">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </form>
                                <span class="mobile-action-label">Approve</span>
                            </div>
                            @endif

                            <div class="mobile-action-item">
                                <a href="{{ route('booking.details', $value->id) }}" class="action-btn" title="View Details">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <span class="mobile-action-label">Details</span>
                            </div>

                            @if(!empty($value->mobile))
                            <div class="mobile-action-item">
                                <a href="tel:+91-{{ $value->mobile }}" class="action-btn" title="Call">
                                    <i class="fa-solid fa-phone"></i>
                                </a>
                                <span class="mobile-action-label">Call</span>
                            </div>
                            <div class="mobile-action-item">
                                <a href="https://wa.me/+91{{ $value->mobile }}?text={{ urlencode('Your demo plan is about to expire. Please book your monthly seat to experience the Library.\n\n– Team ' . (getCurrentBranchName() ?: 'Library')) }}" target="_blank" class="action-btn" title="WhatsApp">
                                    <i class="fab fa-whatsapp text-success"></i>
                                </a>
                                <span class="mobile-action-label">WhatsApp</span>
                            </div>
                            @endif

                            <div class="mobile-action-item">
                                <a href="javascript:void(0)" class="action-btn btn-danger-hover delete-booking" data-id="{{ $value->id }}" title="Delete">
                                    <i class="fa-solid fa-trash text-danger"></i>
                                </a>
                                <span class="mobile-action-label">Delete</span>
                            </div>
                        </div>
                        <div class="mobile-scroll-indicator"></div>

                        {{-- Full-width "View Inquiry Details →" button --}}
                        <a href="{{ route('booking.details', $value->id) }}" class="mobile-view-details-btn">
                            View Inquiry Details <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
    @endforeach
</div>

@else

<div class="no-data-found text-center py-5">
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>
    <dotlottie-wc src="https://lottie.host/5d973bf9-2f1d-4dd5-925f-86da95dbd7b1/t7dXaWIroC.lottie" style="width: 200px;height: 200px" autoplay loop></dotlottie-wc>
    @if($hasActiveFilters)
        <h4>No Inquiries Found</h4>
        <span>No demo inquiries match the selected search or filter criteria.</span>
        <div class="heading-list justify-content-center mt-3">
            <a href="{{ route('demo-users.index') }}" class="btn btn-primary export m-0" style="background: #18225f !important; border-color: #18225f !important;">
                <i class="fa-solid fa-rotate-right me-1"></i> Clear Filters
            </a>
        </div>
    @else
        <h4>No Demo Inquiries Found</h4>
        <span>You haven’t added any demo inquiries yet. Start receiving demo bookings by adding an inquiry.</span>
        <div class="heading-list justify-content-center mt-3">
            <a href="{{ route('demo-users.create') }}" class="btn btn-primary export m-0" style="background: #18225f !important; border-color: #18225f !important;">
                <i class="fa-solid fa-plus"></i> Add Inquiry
            </a>
        </div>
    @endif
</div>

@endif

<script>
$(document).ready(function() {
    // Filter Drawer Toggle
    $('#learnerFilterToggleBtn').on('click', function(e) {
        e.preventDefault();
        $('#learnerFilterContainer').slideToggle(200);
    });

    // Custom Dropdown Open/Close
    $('.learner-filter-module .custom-dropdown').on('click', function(e) {
        if ($(e.target).closest('.custom-dropdown-menu').length) return;
        var wasOpen = $(this).hasClass('open');
        $('.learner-filter-module .custom-dropdown').removeClass('open');
        if (!wasOpen) $(this).addClass('open');
    });

    // Dropdown Option Select
    $('.learner-filter-module .dropdown-item-option').on('click', function(e) {
        e.stopPropagation();
        var $dropdown = $(this).closest('.custom-dropdown');
        var val = $(this).data('value');
        var text = $(this).find('span').text();

        $dropdown.find('input[type="hidden"]').val(val);
        $dropdown.find('.filter-field-value').text(text);
        $dropdown.find('.dropdown-item-option').removeClass('active');
        $(this).addClass('active');
        $dropdown.removeClass('open');
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.custom-dropdown').length) {
            $('.learner-filter-module .custom-dropdown').removeClass('open');
        }
    });

    // Clear Filter
    $('#clearFilter').on('click', function() {
        window.location.href = "{{ route('demo-users.index') }}";
    });

    // Copy to clipboard
    $(document).on('click', '.copy-action-btn', function(e) {
        e.preventDefault();
        var val = $(this).data('copy');
        if (!val) return;
        var $btn = $(this);
        navigator.clipboard.writeText(val).then(function() {
            var origHtml = $btn.html();
            $btn.html('<i class="fa-solid fa-check text-success"></i>');
            setTimeout(function() {
                $btn.html(origHtml);
            }, 1500);
        });
    });

    // Image Modal Preview
    var modal = $('#imageViewModal');
    var modalImg = $('#modalImage');

    $(document).on('click', '.view-image', function() {
        var src = $(this).attr('src') || $(this).attr('href') || $(this).find('img').attr('src');
        if (src) {
            modalImg.attr('src', src);
            modal.css({'display': 'flex', 'opacity': '1'}).attr('aria-hidden', 'false');
        }
    });

    $('.close-modal, #imageViewModal').on('click', function(e) {
        if (e.target === this || $(this).hasClass('close-modal')) {
            modal.css({'opacity': '0', 'display': 'none'}).attr('aria-hidden', 'true');
            modalImg.attr('src', '');
        }
    });

    // Mobile Collapsible Toggle
    $(document).on('click', '.js-mobile-collapsible-toggle', function() {
        var $banner = $(this);
        var $content = $banner.next('.mobile-collapsible-content');
        $content.slideToggle(200);
        $banner.toggleClass('is-open');
    });

    // Delete Confirmation
    $(document).on('click', '.delete-booking', function(e) {
        e.preventDefault();
        let bookingId = $(this).data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: "This demo inquiry will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#18225f',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('booking') }}/" + bookingId,
                    type: "DELETE",
                    data: {
                        _token: "{{ csrf_token() }}",
                    },
                    success: function(response) {
                        Swal.fire({
                            title: 'Deleted!',
                            text: 'Demo inquiry has been deleted.',
                            icon: 'success',
                            confirmButtonColor: '#18225f'
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Something went wrong. Please try again.',
                            icon: 'error',
                            confirmButtonColor: '#18225f'
                        });
                    }
                });
            }
        });
    });
});
</script>

@endsection
