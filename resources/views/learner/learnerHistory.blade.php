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
$current_route = Route::currentRouteName();
$hasActiveFilters = request()->filled('search') || request()->filled('plan_id') || request()->filled('is_paid')
    || request()->filled('status');
@endphp

<div class="row mb-2">
    <div class="col-lg-12 text-end">
        <a href="javascript:void(0)" class="btn btn-primary export" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Filter" id="learnerFilterToggleBtn"><i class="fa-solid fa-filter"></i></a>
    </div>
</div>

{{-- Modern Tile Bar Filter (options as is, text as is, utility as is) --}}
@can('has-permission', 'Filter')
<div class="row mb-3 learner-filter-module" id="learnerFilterContainer" style="{{ $hasActiveFilters ? '' : 'display: none;' }}">
    <div class="col-lg-12">
        <div class="learner-filter-card">
            <form action="{{ route('learnerHistory') }}" method="GET" class="learner-filter-form" id="learnerFilterForm">
                <div class="learner-filter-grid">
                    <!-- Search Learner -->
                    <div class="filter-field-box filter-search-box" id="filterSearchBox">
                        <div class="filter-field-icon icon-slate">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>
                        <div class="filter-field-content">
                            <span class="filter-field-label">Search Learner</span>
                            <input type="text" 
                                   class="filter-input" 
                                   name="search" 
                                   id="search_learner_input"
                                   placeholder="Enter Name, Mobile or Email" 
                                   value="{{ request()->get('search') }}"
                                   autocomplete="off">
                        </div>
                    </div>

                    <!-- Plan -->
                    @php
                        $selectedPlanName = 'Choose Plan';
                        if (request()->filled('plan_id')) {
                            $matchedPlan = $plans->firstWhere('id', request()->get('plan_id'));
                            if ($matchedPlan) {
                                $selectedPlanName = $matchedPlan->name;
                            }
                        }
                    @endphp
                    <div class="filter-field-box custom-dropdown" id="dropdownPlan" data-dropdown="plan">
                        <div class="filter-field-icon icon-purple">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>
                        <div class="filter-field-content">
                            <span class="filter-field-label">Plan</span>
                            <span class="filter-field-value" id="plan_display">{{ $selectedPlanName }}</span>
                            <input type="hidden" name="plan_id" id="plan_id" value="{{ request()->get('plan_id') }}">
                        </div>
                        <div class="filter-field-chevron">
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>

                        <!-- Dropdown Menu -->
                        <div class="custom-dropdown-menu scrollable-dropdown" id="plan_dropdown_menu">
                            <div class="dropdown-item-option {{ !request()->filled('plan_id') ? 'active' : '' }}" data-value="">
                                <i class="fa-solid fa-file-lines item-icon text-purple"></i>
                                <span>Choose Plan</span>
                            </div>
                            @foreach($plans as $plan)
                            <div class="dropdown-item-option {{ request()->get('plan_id') == $plan->id ? 'active' : '' }}" data-value="{{ $plan->id }}">
                                <i class="fa-solid fa-book-bookmark item-icon text-purple"></i>
                                <span>{{ $plan->name }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Payment Status (Paid / Unpaid as is) -->
                    @php
                        $selectedPaymentName = 'Choose Payment Status';
                        if (request()->get('is_paid') === '1') {
                            $selectedPaymentName = 'Paid';
                        } elseif (request()->get('is_paid') === '0') {
                            $selectedPaymentName = 'Unpaid';
                        }
                    @endphp
                    <div class="filter-field-box custom-dropdown" id="dropdownPayment" data-dropdown="payment">
                        <div class="filter-field-icon icon-green">
                            <i class="fa-solid fa-credit-card"></i>
                        </div>
                        <div class="filter-field-content">
                            <span class="filter-field-label">Payment Status</span>
                            <span class="filter-field-value" id="payment_display">{{ $selectedPaymentName }}</span>
                            <input type="hidden" name="is_paid" id="is_paid" value="{{ request()->get('is_paid') }}">
                        </div>
                        <div class="filter-field-chevron">
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>

                        <div class="custom-dropdown-menu" id="payment_dropdown_menu">
                            <div class="dropdown-item-option {{ request()->get('is_paid') === null || request()->get('is_paid') === '' ? 'active' : '' }}" data-value="">
                                <i class="fa-solid fa-credit-card item-icon text-primary"></i>
                                <span>Choose Payment Status</span>
                            </div>
                            <div class="dropdown-item-option {{ request()->get('is_paid') === '1' ? 'active' : '' }}" data-value="1">
                                <i class="fa-solid fa-circle-check item-icon text-success"></i>
                                <span>Paid</span>
                            </div>
                            <div class="dropdown-item-option {{ request()->get('is_paid') === '0' ? 'active' : '' }}" data-value="0">
                                <i class="fa-solid fa-clock-rotate-left item-icon text-danger"></i>
                                <span>Unpaid</span>
                            </div>
                        </div>
                    </div>

                    <!-- Status (Active / Expired as is) -->
                    @php
                        $selectedStatusName = 'Choose Status';
                        if (request()->get('status') === 'active') {
                            $selectedStatusName = 'Active';
                        } elseif (request()->get('status') === 'expired') {
                            $selectedStatusName = 'Expired';
                        }
                    @endphp
                    <div class="filter-field-box custom-dropdown" id="dropdownStatus" data-dropdown="status">
                        <div class="filter-field-icon icon-blue">
                            <i class="fa-solid fa-toggle-on"></i>
                        </div>
                        <div class="filter-field-content">
                            <span class="filter-field-label">Status</span>
                            <span class="filter-field-value" id="status_display">{{ $selectedStatusName }}</span>
                            <input type="hidden" name="status" id="status" value="{{ request()->get('status') }}">
                        </div>
                        <div class="filter-field-chevron">
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>

                        <div class="custom-dropdown-menu" id="status_dropdown_menu">
                            <div class="dropdown-item-option {{ !request()->filled('status') ? 'active' : '' }}" data-value="">
                                <i class="fa-solid fa-toggle-on item-icon text-blue"></i>
                                <span>Choose Status</span>
                            </div>
                            <div class="dropdown-item-option {{ request()->get('status') === 'active' ? 'active' : '' }}" data-value="active">
                                <i class="fa-solid fa-circle-check item-icon text-success"></i>
                                <span>Active</span>
                            </div>
                            <div class="dropdown-item-option {{ request()->get('status') === 'expired' ? 'active' : '' }}" data-value="expired">
                                <i class="fa-solid fa-circle-xmark item-icon text-danger"></i>
                                <span>Expired</span>
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
@endcan

@if ($learnerHistory->total() == 0)
    <div class="no-data-found text-center py-5">
        <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>
        <dotlottie-wc
            src="https://lottie.host/2bd4f1dd-bce9-44cb-b8a4-f5acd681c123/sHuYyTQ6uD.lottie"
            style="width: 200px;height: 200px"
            autoplay
            loop
        ></dotlottie-wc>

        @if($hasActiveFilters)
            <h4>No Learners Found</h4>
            <span>No learners match the selected filters. Try adjusting or clearing the filters above.</span>
            <div class="heading-list justify-content-center mt-3">
                <a href="{{ route('learnerHistory') }}" class="btn btn-primary export">
                    <i class="fa-solid fa-rotate-right me-1"></i> Clear Filters
                </a>
            </div>
        @else
            <h4>No Learners in This Category</h4>
            <span>You don’t have any learners that are expired, deleted, or closed yet.</span>
            <div class="heading-list justify-content-end mb-1">
                @if(getCurrentBranch() != 0)
                    <a href="javascript:;" class="btn btn-primary export noseat_popup">
                        <i class="fa-solid fa-plus"></i> Book Seat
                    </a>
                @else
                    <h4>To add Plan Prices, first select your Branch.</h4>
                    <span>Plan names remain the same across all branches, but prices can be different. That’s why you need to choose the branch before adding plan prices.</span>
                @endif
            </div>
        @endif
    </div>
@else

<div class="learner-list-module">
    <div class="mb-3">
        <p class="m-0"><b>{{ $learnerHistory->total() }} Records for {{ $learnerHistory->perPage() }} per page</b></p>
    </div>

    @foreach($learnerHistory as $key => $value)
    @php
        $learner_detail_id = $value->learner_detail_id;
        $learner_id = $value->id;
        $planStatus = getPlanStatusDetails($value->plan_end_date);
        $transaction = learnerTransaction($value->id, $value->learner_detail_id);
        $due_date = ($transaction && isset($transaction->pending_amount) && $transaction->due_date) ? $transaction->due_date : null;
        $operation = optional(getLearnerOperation($learner_detail_id))->operation;
        $operationDate = optional(getLearnerOperation($learner_detail_id))->created_at;
        $refundAmount = round(learnerTransaction($value->id, $value->learner_detail_id)?->refund ?? 0);
        $lhIsNonExpiry = ((int)($value->no_expiry ?? 0) === 1);
        $lhDotColor = $lhIsNonExpiry ? 'dot-non-expiry' : ($planStatus['class'] == 'expired' ? 'dot-extension' : 'dot-closed');
        $lhDotTitle = $lhIsNonExpiry ? 'Non-Expired' : ($planStatus['status'] ?? 'Expired');
    @endphp

    <div class="row">
        <div class="col-lg-12">
            <div class="learner-card">
                
                {{-- DESKTOP LAYOUT --}}
                <div class="desktop-only-section">
                    <div class="learner-top-row">
                        <div class="learner-top-left">
                            <div class="seat-badge-box">
                                <span class="seat-label">Seat No. :</span>
                                <span class="seat-val">{{ $value->seat_no ? getSeatDisplayShortFloorName($value->seat_no) : 'GEN' }}</span>
                            </div>
                            <div class="learner-meta-block">
                                <span class="learner-expiry-line">
                                    @if($operation == 'closeSeat')
                                        <span class="text-secondary"><i class="fa-regular fa-clock me-1"></i> Closed Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}</span>
                                    @elseif($operation == 'deleteSeat' && $value->deleted_at != null)
                                        <span class="text-danger"><i class="fa-regular fa-clock me-1"></i> Deleted Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}</span>
                                    @elseif($lhIsNonExpiry)
                                        <span class="non_expired_class" style="color: #c8009d !important; font-weight: 600;"><i class="fa-regular fa-clock me-1"></i> Non-Expired</span>
                                    @else
                                        {!! getUserStatusWithSpan($value->plan_end_date, $learner_id) !!}
                                    @endif
                                </span>

                                @if($refundAmount > 0)
                                    <span class="refund-due-badge">
                                        <i class="fa-solid fa-circle-exclamation me-1"></i> Refund due : ₹{{ $refundAmount }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <ul class="learner-actions-strip">
                            {{-- Reactivate Seat --}}
                            @can('has-permission', 'Reactive Seat')
                                <li>
                                    <a href="{{ route('learners.reactive', $value->id) }}" class="action-btn-pill" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Reactivate Learner">
                                        <i class="fa-solid fa-arrows-rotate me-1"></i> Reactivate Seat
                                    </a>
                                </li>
                            @endcan

                            {{-- Refund Amount --}}
                            @if(refund($value->id) != 0 && learnerTransaction($value->id, $value->learner_detail_id)?->refund > 0)
                                <li>
                                    <a href="{{ route('learner.other.payment', $value->learner_detail_id) }}" class="action-btn-pill payment-learner" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Learner Refund">
                                        <i class="fa-solid fa-money-bill me-1"></i> Refund
                                    </a>
                                </li>
                            @endif

                            {{-- View Seat Info --}}
                            @can('has-permission', 'View Seat')
                                <li>
                                    <a href="{{ route('learners.show', $value->id) }}" class="action-icon-btn" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="View Seat Booking Full Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </li>
                            @endcan

                            {{-- Permanently Delete Seat --}}
                            @can('has-permission', 'Delete Seat')
                                @if($value->status == 0 && (empty($transaction?->refund) || $transaction->refund == 0))
                                    <li>
                                        <a href="#" data-id="{{ $learner_id }}" data-learnerDetail="{{ $value->learner_detail_id }}" data-seat="{{ $value->seat_no }}" data-permanent="1" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Permanent Delete Learners" class="action-icon-btn delete-permanent-customer">
                                            <i class="fas fa-trash text-danger"></i>
                                        </a>
                                    </li>
                                @endif
                            @endcan

                            {{-- Restore Learner --}}
                            @if($operation == 'deleteSeat' && $value->deleted_at != null)
                                <li>
                                    <a href="#" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" data-seat="{{ $value->seat_no }}" data-bs-toggle="tooltip" title="Restore Learner" class="action-icon-btn restore-customer">
                                        <i class="fas fa-trash-restore text-success"></i>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>

                    {{-- Bottom Grid: 5 Columns --}}
                    <div class="learner-bottom-grid">
                        {{-- Column 1: Learner Identity & Contact Info --}}
                        <div class="learner-profile-col">
                            <div class="avatar-wrap">
                                <a href="{{ asset($value->profile_picture ? $value->profile_picture : 'public/img/student_profile.jpeg') }}" class="view-image learner-list-profile-photo" title="View profile photo">
                                    <img src="{{ asset($value->profile_picture ? $value->profile_picture : 'public/img/student_profile.jpeg') }}" alt="{{ $value->name }}" class="avatar-img">
                                </a>
                                <span class="avatar-status-dot {{ $lhDotColor }}" title="{{ $lhDotTitle }}" data-bs-toggle="tooltip" data-bs-title="{{ $lhDotTitle }}"></span>
                            </div>
                            <div class="learner-details-text">
                                <h5 class="learner-name-title">{{ $value->name }}</h5>
                                <div class="detail-row">
                                    <span class="detail-label">UID :</span>
                                    <a href="{{ route('learners.show', $value->id) }}" class="detail-value">{{ $value->learner_no }}</a>
                                    <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $value->learner_no }}" title="Copy UID">
                                        <i class="fa-regular fa-clone"></i>
                                    </button>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">M :</span>
                                    <a href="tel:+91-{{ $value->mobile }}" class="detail-value">+91-{{ display_learner_mobile($value->mobile) }}</a>
                                    <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $value->mobile }}" title="Copy Mobile">
                                        <i class="fa-regular fa-clone"></i>
                                    </button>
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
                                    {{ $value->plan_type_name ?? '—' }}@if(!empty($value->plan_name)) ({{ $value->plan_name }})@endif
                                </span>
                            </div>
                        </div>

                        {{-- Column 3: Plan Duration --}}
                        <div class="info-stat-block">
                            <div class="info-icon-box info-icon-blue">
                                <i class="fa-regular fa-calendar-days"></i>
                            </div>
                            <div class="info-text">
                                <span class="info-label">Plan Duration</span>
                                <span class="info-value">
                                    @if(!empty($value->plan_start_date) && !empty($value->plan_end_date))
                                        {{ date('j M Y', strtotime($value->plan_start_date)) }} to {{ date('j M Y', strtotime($value->plan_end_date)) }}
                                    @elseif(!empty($value->plan_start_date))
                                        From {{ date('j M Y', strtotime($value->plan_start_date)) }}
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>
                        </div>

                        {{-- Column 4: Payment Status (Refunded) --}}
                        <div class="info-stat-block">
                            <div class="info-icon-box info-icon-green">
                                <i class="fa-regular fa-credit-card"></i>
                            </div>
                            <div class="info-text">
                                <span class="info-label">Payment Status</span>
                                <span class="info-value text-danger">
                                    Refunded : ₹{{ round(refund($value->id)) }}
                                </span>
                            </div>
                        </div>

                        {{-- Column 5: Locker --}}
                        <div class="info-stat-block">
                            <div class="info-icon-box info-icon-amber">
                                <i class="fa-solid fa-lock"></i>
                            </div>
                            <div class="info-text">
                                <span class="info-label">Locker</span>
                                <span class="info-value">
                                    @if(optional($transaction)->locker_amount)
                                        Yes – ₹{{ $transaction->locker_amount }} Paid
                                    @else
                                        No
                                    @endif
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
                                <a href="{{ asset($value->profile_picture ? $value->profile_picture : 'public/img/student_profile.jpeg') }}" class="view-image learner-list-profile-photo" title="View profile photo">
                                    <img src="{{ asset($value->profile_picture ? $value->profile_picture : 'public/img/student_profile.jpeg') }}" alt="{{ $value->name }}" class="avatar-img">
                                </a>
                                <span class="avatar-status-dot {{ $lhIsNonExpiry ? 'dot-non-expiry' : 'dot-extension' }}" title="{{ $lhDotTitle }}"></span>
                            </div>
                            <div class="mobile-details-text">
                                <div class="mobile-name-row">
                                    <h5 class="mobile-name">{{ $value->name }}</h5>
                                    <span class="mobile-seat-badge">Seat {{ $value->seat_no ? getSeatDisplayShortFloorName($value->seat_no) : 'GEN' }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">UID :</span>
                                    <a href="{{ route('learners.show', $value->id) }}" class="detail-value">{{ $value->learner_no }}</a>
                                    <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $value->learner_no }}" title="Copy UID">
                                        <i class="fa-regular fa-clone"></i>
                                    </button>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">M :</span>
                                    <a href="tel:+91-{{ $value->mobile }}" class="detail-value">+91-{{ display_learner_mobile($value->mobile) }}</a>
                                    <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $value->mobile }}" title="Copy Mobile">
                                        <i class="fa-regular fa-clone"></i>
                                    </button>
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
                        <a href="{{ route('learners.show', $value->id) }}" class="mobile-chevron-link" title="View Profile">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </div>

                    {{-- Collapsible Banner --}}
                    @php
                        $lhBannerClass = 'banner-danger';
                        if ($lhIsNonExpiry && $operation != 'closeSeat' && !($operation == 'deleteSeat' && $value->deleted_at != null)) {
                            $lhBannerClass = 'banner-pink';
                        }
                    @endphp
                    <div class="mobile-expiry-banner {{ $lhBannerClass }} js-mobile-collapsible-toggle" role="button" tabindex="0">
                        <div class="mobile-expiry-text">
                            <i class="fa-regular fa-clock"></i>
                            <span>
                                @if($operation == 'closeSeat')
                                    Closed Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}
                                @elseif($operation == 'deleteSeat' && $value->deleted_at != null)
                                    Deleted Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}
                                @elseif($lhIsNonExpiry)
                                    Non-Expired
                                @else
                                    {{ $planStatus['status'] ?? 'Expired' }}
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
                                    {{ $value->plan_type_name ?? '—' }}@if(!empty($value->plan_name)) ({{ $value->plan_name }})@endif
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
                                    @if(!empty($value->plan_start_date) && !empty($value->plan_end_date))
                                        {{ date('j M Y', strtotime($value->plan_start_date)) }} to {{ date('j M Y', strtotime($value->plan_end_date)) }}
                                    @elseif(!empty($value->plan_start_date))
                                        From {{ date('j M Y', strtotime($value->plan_start_date)) }}
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
                                <span class="info-value text-danger">
                                    Refunded : ₹{{ round(refund($value->id)) }}
                                </span>
                            </div>
                        </div>

                        {{-- 4. Locker --}}
                        <div class="mobile-info-item">
                            <div class="info-icon-box info-icon-amber">
                                <i class="fa-solid fa-lock"></i>
                            </div>
                            <div class="info-text">
                                <span class="info-label">Locker</span>
                                <span class="info-value">
                                    @if(optional($transaction)->locker_amount)
                                        Yes – ₹{{ $transaction->locker_amount }} Paid
                                    @else
                                        No
                                    @endif
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
                            @can('has-permission', 'Reactive Seat')
                                <div class="mobile-action-item">
                                    <a href="{{ route('learners.reactive', $value->id) }}" class="action-btn">
                                        <i class="fa-solid fa-arrows-rotate"></i>
                                    </a>
                                    <span class="mobile-action-label">Reactivate</span>
                                </div>
                            @endcan

                            @if(refund($value->id) != 0 && learnerTransaction($value->id, $value->learner_detail_id)?->refund > 0)
                                <div class="mobile-action-item">
                                    <a href="{{ route('learner.other.payment', $value->learner_detail_id) }}" class="action-btn payment-learner">
                                        <i class="fa-solid fa-money-bill"></i>
                                    </a>
                                    <span class="mobile-action-label">Refund</span>
                                </div>
                            @endif

                            @can('has-permission', 'View Seat')
                                <div class="mobile-action-item">
                                    <a href="{{ route('learners.show', $value->id) }}" class="action-btn">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <span class="mobile-action-label">View</span>
                                </div>
                            @endcan

                            @can('has-permission', 'Delete Seat')
                                @if($value->status == 0 && (empty($transaction?->refund) || $transaction->refund == 0))
                                    <div class="mobile-action-item">
                                        <a href="#" data-id="{{ $learner_id }}" data-learnerDetail="{{ $value->learner_detail_id }}" data-seat="{{ $value->seat_no }}" data-permanent="1" class="action-btn btn-danger-hover delete-permanent-customer">
                                            <i class="fas fa-trash text-danger"></i>
                                        </a>
                                        <span class="mobile-action-label">Delete</span>
                                    </div>
                                @endif
                            @endcan

                            @if($operation == 'deleteSeat' && $value->deleted_at != null)
                                <div class="mobile-action-item">
                                    <a href="#" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" data-seat="{{ $value->seat_no }}" class="action-btn restore-customer">
                                        <i class="fas fa-trash-restore text-success"></i>
                                    </a>
                                    <span class="mobile-action-label">Restore</span>
                                </div>
                            @endif
                        </div>
                        <div class="mobile-scroll-indicator"></div>

                        {{-- Full-width "View Details →" button --}}
                        <a href="{{ route('learners.show', $value->id) }}" class="mobile-view-details-btn">
                            View Details <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
    @endforeach

    {{-- Pagination --}}
    @if ($learnerHistory->lastPage() > 1)
    <ul class="paginations mt-4">
        {{-- Prev Button --}}
        <li>
            <a href="{{ $learnerHistory->onFirstPage() ? '#' : $learnerHistory->appends(request()->all())->previousPageUrl() }}" class="w-auto px-3 text-muted">
                Prev
            </a>
        </li>

        {{-- Page Numbers --}}
        @for ($i = max(1, $learnerHistory->currentPage() - 2); $i <= min($learnerHistory->lastPage(), $learnerHistory->currentPage() + 2); $i++)
            <li>
                <a href="{{ $learnerHistory->appends(request()->all())->url($i) }}" class="{{ $learnerHistory->currentPage() == $i ? 'active' : '' }}">
                    {{ $i }}
                </a>
            </li>
        @endfor
        @if ($learnerHistory->currentPage() < $learnerHistory->lastPage() - 2)
            <li><span>...</span></li>
            <li><a href="{{ $learnerHistory->appends(request()->all())->url($learnerHistory->lastPage()) }}">{{ $learnerHistory->lastPage() }}</a></li>
        @endif

        {{-- Next Button --}}
        <li>
            <a href="{{ $learnerHistory->hasMorePages() ? $learnerHistory->appends(request()->all())->nextPageUrl() : '#' }}" class="w-auto px-3 text-muted">Next</a>
        </li>
    </ul>
    @endif
</div>

@endif

<!-- /.content -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).on('click', '.delete-customer', function() {
        var id = $(this).data('id');
        var url = '{{ route('learners.destroy', ':id') }}';
        url = url.replace(':id', id);

        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire(
                            'Deleted!',
                            'User has been deleted.',
                            'success'
                        ).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr, status, error) {
                        Swal.fire(
                            'Error!',
                            'An error occurred while deleting the student.',
                            'error'
                        );
                    }
                });
            }
        });
    });
</script>

<script>
    function confirmSwap(customerId) {
        const form = document.getElementById(`swap-seat-form-${customerId}`);
        const oldSeat = document.getElementById(`old-seat-${customerId}`).value;
        const newSeatSelect = document.getElementById(`new-seat-${customerId}`);
        const newSeat = newSeatSelect.options[newSeatSelect.selectedIndex].text;

        const confirmation = confirm(`Are you sure you want to swap from seat ${oldSeat} to seat ${newSeat}?`);

        if (confirmation) {
            form.submit();
        } else {
            newSeatSelect.value = '';
        }
    }
</script>

<script>
    $(document).on('click', '.link-close-plan', function() {
        const learner_id = this.getAttribute('data-id');
        var url = '{{ route('learners.close') }}';

        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, close it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        learner_id: learner_id
                    },
                    success: function(response) {
                        Swal.fire(
                            'Closed!',
                            'The user plan has been closed.',
                            'success'
                        ).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr, status, error) {
                        Swal.fire(
                            'Error!',
                            'An error occurred while closing the plan.',
                            'error'
                        );
                    }
                });
            }
        });
    });
</script>

<script>
$(document).ready(function() {
    // Custom Filter Toggle Button (Isolated handler)
    $(document).on('click', '#learnerFilterToggleBtn, #filter', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        $('#learnerFilterContainer').stop(true, true).slideToggle(200);
    });

    // Custom dropdown logic
    $('.custom-dropdown').each(function() {
        var $dropdown = $(this);
        var $menu = $dropdown.find('.custom-dropdown-menu');

        $dropdown.on('click', function(e) {
            e.stopPropagation();
            var isOpen = $dropdown.hasClass('open');
            $('.custom-dropdown').not($dropdown).removeClass('open');
            if (isOpen) {
                $dropdown.removeClass('open');
            } else {
                $dropdown.addClass('open');
            }
        });

        $menu.on('click', '.dropdown-item-option', function(e) {
            e.stopPropagation();
            var val = $(this).data('value');
            var text = $(this).find('span').text().trim();

            $menu.find('.dropdown-item-option').removeClass('active');
            $(this).addClass('active');

            $dropdown.find('.filter-field-value').text(text);
            $dropdown.find('input[type="hidden"]').val(val);
            $dropdown.removeClass('open');
        });
    });

    $(document).on('click', function() {
        $('.custom-dropdown').removeClass('open');
    });

    // Clear filter
    $('#clearFilter').on('click', function(e) {
        e.preventDefault();
        window.location.href = "{{ route('learnerHistory') }}";
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
        var src = $(this).attr('src');
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
});
</script>
@endsection