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

@if ($finalGeneralLearners->count() == 0 && collect($seats)->sum(fn($seat) => $seat->learners->count()) == 0)
    <div class="no-data-found text-center py-5">
        <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>
        <dotlottie-wc
            src="https://lottie.host/2bd4f1dd-bce9-44cb-b8a4-f5acd681c123/sHuYyTQ6uD.lottie"
            style="width: 200px;height: 200px"
            autoplay
            loop
        ></dotlottie-wc>
        <h4>No Learner Added Yet</h4>
        <span>You haven’t added any learners to your library yet. Start adding learners by clicking the button below.</span>
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
    </div>
@else

<div class="info-message mb-3">
    <span class="close-btn" onclick="this.parentElement.style.display='none';">&times;</span>
    <b>Important :</b> The Seat History page displays a comprehensive list of all library seats, along with seat-specific booking details in a single view. If you need information about library seats, this section provides helpful details to guide you.
</div>

<div class="learner-list-module">
@foreach($seats as $seat)
    @if($seat->learners->count() > 0)
        @foreach($seat->learners as $user)
            @php
                $learner = optional($user);
                $planStatus = getPlanStatusDetails($user->plan_end_date);
                $operation = optional(getLearnerOperation($user->learner_detail_id))->operation;
                $learner_id = $learner->id;
                $learner_detail_id = $user->learner_detail_id;
                $transaction = learnerTransaction($learner_id, $learner_detail_id);      

                if ($transaction && isset($transaction->pending_amount) && $transaction->due_date) {
                    $due_date = $transaction->due_date;
                } else {
                    $due_date = null;
                }
                $operationDate = optional(getLearnerOperation($learner_detail_id))->created_at;
                $formattedDueDate = !empty($due_date) ? (is_object($due_date) ? (!empty($due_date->due_date) ? date('j M', strtotime($due_date->due_date)) : '') : date('j M', strtotime($due_date))) : '';
                $totalPendingAmt = optional($transaction)->pending_amount ?? 0;
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
                                        <span class="seat-val">{{ getSeatDisplayShortFloorName($seat->seat_no) }}</span>
                                    </div>
                                    <div class="learner-meta-block">
                                        <span class="learner-expiry-line">
                                            @if($operation == 'closeSeat')
                                                <span class="text-secondary"><i class="fa-regular fa-clock me-1"></i> Closed Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}</span>
                                            @elseif($operation == 'deleteSeat' && $user->deleted_at != null)
                                                <span class="text-danger"><i class="fa-regular fa-clock me-1"></i> Deleted Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}</span>
                                            @else
                                                {!! getUserStatusWithSpan($user->plan_end_date, $learner_id) !!}
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                <ul class="learner-actions-strip">
                                    <li>
                                        <a href="{{ url('seats/history', $seat->seat_no) }}" class="action-btn-pill">
                                            <i class="fa-solid fa-clock-rotate-left me-1"></i> View Seat Previous History
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            {{-- Bottom Grid: 4 Columns --}}
                            <div class="learner-bottom-grid grid-4-cols">
                                {{-- Column 1: Learner Profile --}}
                                <div class="learner-profile-col">
                                    <div class="avatar-wrap">
                                        <a href="{{ asset($learner?->profile_picture ? $learner->profile_picture : 'public/img/student_profile.jpeg') }}" class="view-image learner-list-profile-photo" title="View profile photo">
                                            <img src="{{ asset($learner?->profile_picture ? $learner->profile_picture : 'public/img/student_profile.jpeg') }}" alt="{{ $learner->name ?? 'profile' }}" class="avatar-img">
                                        </a>
                                        <span class="avatar-status-dot {{ $planStatus['class'] == 'expired' ? 'dot-extension' : 'dot-active' }}"></span>
                                    </div>
                                    <div class="learner-details-text">
                                        <h5 class="learner-name-title">{{ $learner->name ?? '' }}</h5>
                                        <div class="detail-row">
                                            <span class="detail-label">UID :</span>
                                            <a href="{{ route('learners.show', $user->learner_id) }}" class="detail-value">{{ $learner->learner_no ?? '' }}</a>
                                            <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $learner->learner_no ?? '' }}" title="Copy UID">
                                                <i class="fa-regular fa-clone"></i>
                                            </button>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">M :</span>
                                            <a href="tel:+91-{{ $learner->mobile ?? '' }}" class="detail-value">+91-{{ $learner->mobile ? display_learner_mobile($learner->mobile) : '' }}</a>
                                            @if($learner->mobile)
                                            <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $learner->mobile }}" title="Copy Mobile">
                                                <i class="fa-regular fa-clone"></i>
                                            </button>
                                            @endif
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">E :</span>
                                            @if($learner->email)
                                                <a href="mailto:{{ $learner->email }}" class="detail-value detail-email">{{ display_learner_email($learner->email) }}</a>
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
                                            {{ optional(myPlanType($user->plan_type_id))->name ?? '' }}@if(!empty(optional(myPlan($user->plan_id))->name)) ({{ optional(myPlan($user->plan_id))->name }})@endif
                                        </span>
                                        @if($user->join_date)
                                        <span class="text-muted" style="font-size: 11px; font-weight: 500;">
                                            Join: {{ date('j M Y', strtotime($user->join_date)) }}
                                        </span>
                                        @endif
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
                                            @if(!empty($user->plan_start_date) && !empty($user->plan_end_date))
                                                {{ date('j M Y', strtotime($user->plan_start_date)) }} to {{ date('j M Y', strtotime($user->plan_end_date)) }}
                                            @elseif(!empty($user->plan_start_date))
                                                From {{ date('j M Y', strtotime($user->plan_start_date)) }}
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
                                            @if((paylater($learner_detail_id) && $totalPendingAmt != 0) || pending_amt($learner_detail_id))
                                                <a href="javascript:void(0)" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" class="text-danger fw-bold settlement-learner text-decoration-none">
                                                    Due ₹{{ rtrim(rtrim(number_format($totalPendingAmt, 2, '.', ''), '0'), '.') }}@if(!empty($formattedDueDate)) ({{ $formattedDueDate }})@endif
                                                </a>
                                            @elseif(!empty($totalPendingAmt) && $totalPendingAmt == 0)
                                                <span class="text-success fw-bold payment-status-value">Fully Paid</span>
                                                @if(optional(learnerTransaction($learner_id, $learner_detail_id))->id)
                                                <form action="{{ route('learner.receipt.download') }}" method="POST" target="_blank" class="d-inline ms-1" enctype="multipart/form-data">
                                                    @csrf
                                                    <input type="hidden" name="learner_id" value="{{ $learner_id }}">
                                                    <input type="hidden" name="id" value="{{ optional(learnerTransaction($learner_id, $learner_detail_id))->id ?? 'NA' }}">
                                                    <input type="hidden" name="type" value="learner">
                                                    <button type="submit" class="receipt-btn noLoader" title="Download Receipt">
                                                        <i class="fa-solid fa-download"></i>
                                                    </button>
                                                </form>
                                                @endif
                                            @else
                                                <span class="text-muted payment-status-value">—</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- MOBILE LAYOUT --}}
                        <div class="mobile-only-section">
                            {{-- Top Profile Header --}}
                            <div class="mobile-profile-header">
                                <div class="mobile-profile-left">
                                    <div class="avatar-wrap">
                                        <a href="{{ asset($learner?->profile_picture ? $learner->profile_picture : 'public/img/student_profile.jpeg') }}" class="view-image learner-list-profile-photo" title="View profile photo">
                                            <img src="{{ asset($learner?->profile_picture ? $learner->profile_picture : 'public/img/student_profile.jpeg') }}" alt="{{ $learner->name ?? 'profile' }}" class="avatar-img">
                                        </a>
                                        <span class="avatar-status-dot {{ ($operation == 'closeSeat' || ($operation == 'deleteSeat' && $user->deleted_at != null)) ? 'dot-extension' : 'dot-active' }}" title="{{ $planStatus['status'] ?? '' }}"></span>
                                    </div>
                                    <div class="mobile-details-text">
                                        <div class="mobile-name-row">
                                            <h5 class="mobile-name">{{ $learner->name ?? '' }}</h5>
                                            <span class="mobile-seat-badge">Seat {{ getSeatDisplayShortFloorName($seat->seat_no) }}</span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">UID :</span>
                                            <a href="{{ route('learners.show', $user->learner_id) }}" class="detail-value">{{ $learner->learner_no ?? '' }}</a>
                                            <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $learner->learner_no ?? '' }}" title="Copy UID">
                                                <i class="fa-regular fa-clone"></i>
                                            </button>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">M :</span>
                                            <a href="tel:+91-{{ $learner->mobile ?? '' }}" class="detail-value">+91-{{ $learner->mobile ? display_learner_mobile($learner->mobile) : '' }}</a>
                                            @if($learner->mobile)
                                            <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $learner->mobile }}">
                                                <i class="fa-regular fa-copy"></i>
                                            </button>
                                            @endif
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">E :</span>
                                            @if($learner->email)
                                                <a href="mailto:{{ $learner->email }}" class="detail-value detail-email">{{ display_learner_email($learner->email) }}</a>
                                            @else
                                                <span class="text-danger detail-email" style="font-size: 11.5px;"><i class="fa-solid fa-xmark"></i> Email ID Not Available</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ route('learners.show', $user->learner_id) }}" class="mobile-chevron-link" title="View Profile">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            </div>

                            {{-- Collapsible Banner --}}
                            <div class="mobile-expiry-banner banner-warning js-mobile-collapsible-toggle" role="button" tabindex="0">
                                <div class="mobile-expiry-text">
                                    <i class="fa-regular fa-clock"></i>
                                    <span>
                                        @if($operation == 'closeSeat')
                                            Closed Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}
                                        @elseif($operation == 'deleteSeat' && $user->deleted_at != null)
                                            Deleted Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}
                                        @else
                                            {{ $planStatus['status'] ?? '' }}
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
                                            {{ optional(myPlanType($user->plan_type_id))->name ?? '' }}@if(!empty(optional(myPlan($user->plan_id))->name)) ({{ optional(myPlan($user->plan_id))->name }})@endif
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
                                            @if(!empty($user->plan_start_date) && !empty($user->plan_end_date))
                                                {{ date('j M Y', strtotime($user->plan_start_date)) }} to {{ date('j M Y', strtotime($user->plan_end_date)) }}
                                            @elseif(!empty($user->plan_start_date))
                                                From {{ date('j M Y', strtotime($user->plan_start_date)) }}
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
                                        <div class="d-flex align-items-center">
                                            @if((paylater($learner_detail_id) && $totalPendingAmt != 0) || pending_amt($learner_detail_id))
                                                <a href="javascript:void(0)" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" class="text-danger fw-bold settlement-learner text-decoration-none">
                                                    Due ₹{{ rtrim(rtrim(number_format($totalPendingAmt, 2, '.', ''), '0'), '.') }}@if(!empty($formattedDueDate)) ({{ $formattedDueDate }})@endif
                                                </a>
                                            @elseif(!empty($totalPendingAmt) && $totalPendingAmt == 0)
                                                <span class="text-success fw-bold payment-status-value">Fully Paid</span>
                                            @else
                                                <span class="text-muted payment-status-value">—</span>
                                            @endif
                                        </div>
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
                                    <div class="mobile-action-item">
                                        <a href="{{ url('seats/history', $seat->seat_no) }}" class="action-btn">
                                            <i class="fa-solid fa-clock-rotate-left"></i>
                                        </a>
                                        <span class="mobile-action-label">History</span>
                                    </div>
                                    <div class="mobile-action-item">
                                        <a href="{{ route('learners.show', $user->learner_id) }}" class="action-btn">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <span class="mobile-action-label">Profile</span>
                                    </div>
                                    @if(!empty($learner?->mobile))
                                    <div class="mobile-action-item">
                                        <a href="tel:+91-{{ $learner->mobile }}" class="action-btn">
                                            <i class="fa-solid fa-phone"></i>
                                        </a>
                                        <span class="mobile-action-label">Call</span>
                                    </div>
                                    <div class="mobile-action-item">
                                        <a href="https://wa.me/+91{{ $learner->mobile }}" target="_blank" class="action-btn">
                                            <i class="fab fa-whatsapp text-success"></i>
                                        </a>
                                        <span class="mobile-action-label">WhatsApp</span>
                                    </div>
                                    @endif
                                </div>
                                <div class="mobile-scroll-indicator"></div>

                                {{-- Full-width "View Seat Previous History →" button --}}
                                <a href="{{ url('seats/history', $seat->seat_no) }}" class="mobile-view-details-btn">
                                    View Seat Previous History <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
@endforeach

{{-- General Seats --}}
@if($finalGeneralLearners->count())
    @foreach($finalGeneralLearners as $user)
    @php
        $learner = myLearner($user->learner_id);
        $planStatus = getPlanStatusDetails($user->plan_end_date);
        $operation = optional(getLearnerOperation($user->learner_detail_id))->operation;
        $learner_id = optional($learner)->id;
        $learner_detail_id = $user->learner_detail_id;
        $transaction = learnerTransaction($learner_id, $learner_detail_id);      

        if ($transaction && isset($transaction->pending_amount) && $transaction->due_date) {
            $due_date = $transaction->due_date;
        } else {
            $due_date = null;
        }
        $formattedDueDate = !empty($due_date) ? (is_object($due_date) ? (!empty($due_date->due_date) ? date('j M', strtotime($due_date->due_date)) : '') : date('j M', strtotime($due_date))) : '';
        $totalPendingAmt = optional($transaction)->pending_amount ?? 0;
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
                                <span class="seat-val">GEN</span>
                            </div>
                            <div class="learner-meta-block">
                                <span class="learner-expiry-line">
                                    @if($operation == 'closeSeat')
                                        <span class="text-secondary"><i class="fa-regular fa-clock me-1"></i> Closed Seat on {{ $user->plan_end_date ? date('j M Y', strtotime($user->plan_end_date)) : '' }}</span>
                                    @elseif($operation == 'deleteSeat' && $user->deleted_at != null)
                                        <span class="text-danger"><i class="fa-regular fa-clock me-1"></i> Deleted Seat on {{ $user->plan_end_date ? date('j M Y', strtotime($user->plan_end_date)) : '' }}</span>
                                    @else
                                        {!! getUserStatusWithSpan($user->plan_end_date, $learner_id) !!}
                                    @endif
                                </span>
                            </div>
                        </div>

                        <ul class="learner-actions-strip">
                            <li>
                                <a href="{{ route('general.seat.history') }}" class="action-btn-pill">
                                    <i class="fa-solid fa-clock-rotate-left me-1"></i> View Seat Previous History
                                </a>
                            </li>
                        </ul>
                    </div>

                    {{-- Bottom Grid: 4 Columns --}}
                    <div class="learner-bottom-grid grid-4-cols">
                        {{-- Column 1: Learner Profile --}}
                        <div class="learner-profile-col">
                            <div class="avatar-wrap">
                                <a href="{{ asset($learner?->profile_picture ? $learner->profile_picture : 'public/img/student_profile.jpeg') }}" class="view-image learner-list-profile-photo" title="View profile photo">
                                    <img src="{{ asset($learner?->profile_picture ? $learner->profile_picture : 'public/img/student_profile.jpeg') }}" alt="{{ $learner->name ?? 'profile' }}" class="avatar-img">
                                </a>
                                <span class="avatar-status-dot {{ $planStatus['class'] == 'expired' ? 'dot-extension' : 'dot-active' }}"></span>
                            </div>
                            <div class="learner-details-text">
                                <h5 class="learner-name-title">{{ $learner->name ?? '' }}</h5>
                                <div class="detail-row">
                                    <span class="detail-label">UID :</span>
                                    <a href="{{ route('learners.show', $learner_id) }}" class="detail-value">{{ $user->learner_no ?? '' }}</a>
                                    <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $user->learner_no ?? '' }}" title="Copy UID">
                                        <i class="fa-regular fa-clone"></i>
                                    </button>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">M :</span>
                                    <a href="tel:+91-{{ $user->mobile }}" class="detail-value">+91-{{ $user->mobile ? display_learner_mobile($user->mobile) : '' }}</a>
                                    @if($user->mobile)
                                    <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $user->mobile }}" title="Copy Mobile">
                                        <i class="fa-regular fa-clone"></i>
                                    </button>
                                    @endif
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">E :</span>
                                    @if($learner && $learner->email)
                                        <a href="mailto:{{ $learner->email }}" class="detail-value detail-email">{{ display_learner_email($learner->email) }}</a>
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
                                    {{ optional(myPlanType($user->plan_type_id))->name ?? '' }}@if(!empty(optional(myPlan($user->plan_id))->name)) ({{ optional(myPlan($user->plan_id))->name }})@endif
                                </span>
                                @if($user->join_date)
                                <span class="text-muted" style="font-size: 11px; font-weight: 500;">
                                    Join: {{ date('j M Y', strtotime($user->join_date)) }}
                                </span>
                                @endif
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
                                    @if(!empty($user->plan_start_date) && !empty($user->plan_end_date))
                                        {{ date('j M Y', strtotime($user->plan_start_date)) }} to {{ date('j M Y', strtotime($user->plan_end_date)) }}
                                    @elseif(!empty($user->plan_start_date))
                                        From {{ date('j M Y', strtotime($user->plan_start_date)) }}
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
                                    @if((paylater($learner_detail_id) && $totalPendingAmt != 0) || pending_amt($learner_detail_id))
                                        <a href="javascript:void(0)" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" class="text-danger fw-bold settlement-learner text-decoration-none">
                                            Due ₹{{ rtrim(rtrim(number_format($totalPendingAmt, 2, '.', ''), '0'), '.') }}@if(!empty($formattedDueDate)) ({{ $formattedDueDate }})@endif
                                        </a>
                                    @elseif(!empty($totalPendingAmt) && $totalPendingAmt == 0)
                                        <span class="text-success fw-bold payment-status-value">Fully Paid</span>
                                        @if(optional(learnerTransaction($learner_id, $learner_detail_id))->id)
                                        <form action="{{ route('learner.receipt.download') }}" method="POST" target="_blank" class="d-inline ms-1" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="learner_id" value="{{ $learner_id }}">
                                            <input type="hidden" name="id" value="{{ optional(learnerTransaction($learner_id, $learner_detail_id))->id ?? 'NA' }}">
                                            <input type="hidden" name="type" value="learner">
                                            <button type="submit" class="receipt-btn noLoader" title="Download Receipt">
                                                <i class="fa-solid fa-download"></i>
                                            </button>
                                        </form>
                                        @endif
                                    @else
                                        <span class="text-muted payment-status-value">—</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- MOBILE LAYOUT --}}
                <div class="mobile-only-section">
                    {{-- Top Profile Header --}}
                    <div class="mobile-profile-header">
                        <div class="mobile-profile-left">
                            <div class="avatar-wrap">
                                <a href="{{ asset($learner?->profile_picture ? $learner->profile_picture : 'public/img/student_profile.jpeg') }}" class="view-image learner-list-profile-photo" title="View profile photo">
                                    <img src="{{ asset($learner?->profile_picture ? $learner->profile_picture : 'public/img/student_profile.jpeg') }}" alt="{{ $learner->name ?? 'profile' }}" class="avatar-img">
                                </a>
                                <span class="avatar-status-dot {{ ($operation == 'closeSeat' || ($operation == 'deleteSeat' && $user->deleted_at != null)) ? 'dot-extension' : 'dot-active' }}" title="{{ $planStatus['status'] ?? '' }}"></span>
                            </div>
                            <div class="mobile-details-text">
                                <div class="mobile-name-row">
                                    <h5 class="mobile-name">{{ $learner->name ?? '' }}</h5>
                                    <span class="mobile-seat-badge">Seat GEN</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">UID :</span>
                                    <a href="{{ route('learners.show', $learner_id) }}" class="detail-value">{{ $user->learner_no ?? '' }}</a>
                                    <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $user->learner_no ?? '' }}" title="Copy UID">
                                        <i class="fa-regular fa-clone"></i>
                                    </button>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">M :</span>
                                    <a href="tel:+91-{{ $user->mobile }}" class="detail-value">+91-{{ $user->mobile ? display_learner_mobile($user->mobile) : '' }}</a>
                                    @if($user->mobile)
                                    <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $user->mobile }}">
                                        <i class="fa-regular fa-copy"></i>
                                    </button>
                                    @endif
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">E :</span>
                                    @if($learner && $learner->email)
                                        <a href="mailto:{{ $learner->email }}" class="detail-value detail-email">{{ display_learner_email($learner->email) }}</a>
                                    @else
                                        <span class="text-danger detail-email" style="font-size: 11.5px;"><i class="fa-solid fa-xmark"></i> Email ID Not Available</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('learners.show', $learner_id) }}" class="mobile-chevron-link" title="View Profile">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </div>

                    {{-- Collapsible Banner --}}
                    <div class="mobile-expiry-banner banner-warning js-mobile-collapsible-toggle" role="button" tabindex="0">
                        <div class="mobile-expiry-text">
                            <i class="fa-regular fa-clock"></i>
                            <span>
                                @if($operation == 'closeSeat')
                                    Closed Seat on {{ $user->plan_end_date ? date('j M Y', strtotime($user->plan_end_date)) : '' }}
                                @elseif($operation == 'deleteSeat' && $user->deleted_at != null)
                                    Deleted Seat on {{ $user->plan_end_date ? date('j M Y', strtotime($user->plan_end_date)) : '' }}
                                @else
                                    {{ $planStatus['status'] ?? '' }}
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
                                    {{ optional(myPlanType($user->plan_type_id))->name ?? '' }}@if(!empty(optional(myPlan($user->plan_id))->name)) ({{ optional(myPlan($user->plan_id))->name }})@endif
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
                                    @if(!empty($user->plan_start_date) && !empty($user->plan_end_date))
                                        {{ date('j M Y', strtotime($user->plan_start_date)) }} to {{ date('j M Y', strtotime($user->plan_end_date)) }}
                                    @elseif(!empty($user->plan_start_date))
                                        From {{ date('j M Y', strtotime($user->plan_start_date)) }}
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
                                <div class="d-flex align-items-center">
                                    @if((paylater($learner_detail_id) && $totalPendingAmt != 0) || pending_amt($learner_detail_id))
                                        <a href="javascript:void(0)" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" class="text-danger fw-bold settlement-learner text-decoration-none">
                                            Due ₹{{ rtrim(rtrim(number_format($totalPendingAmt, 2, '.', ''), '0'), '.') }}@if(!empty($formattedDueDate)) ({{ $formattedDueDate }})@endif
                                        </a>
                                    @elseif(!empty($totalPendingAmt) && $totalPendingAmt == 0)
                                        <span class="text-success fw-bold payment-status-value">Fully Paid</span>
                                    @else
                                        <span class="text-muted payment-status-value">—</span>
                                    @endif
                                </div>
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
                            <div class="mobile-action-item">
                                <a href="{{ route('general.seat.history') }}" class="action-btn">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                </a>
                                <span class="mobile-action-label">History</span>
                            </div>
                            <div class="mobile-action-item">
                                <a href="{{ route('learners.show', $learner_id) }}" class="action-btn">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <span class="mobile-action-label">Profile</span>
                            </div>
                            @if(!empty($user->mobile))
                            <div class="mobile-action-item">
                                <a href="tel:+91-{{ $user->mobile }}" class="action-btn">
                                    <i class="fa-solid fa-phone"></i>
                                </a>
                                <span class="mobile-action-label">Call</span>
                            </div>
                            <div class="mobile-action-item">
                                <a href="https://wa.me/+91{{ $user->mobile }}" target="_blank" class="action-btn">
                                    <i class="fab fa-whatsapp text-success"></i>
                                </a>
                                <span class="mobile-action-label">WhatsApp</span>
                            </div>
                            @endif
                        </div>
                        <div class="mobile-scroll-indicator"></div>

                        {{-- Full-width "View Seat Previous History →" button --}}
                        <a href="{{ route('general.seat.history') }}" class="mobile-view-details-btn">
                            View Seat Previous History <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
@endif

</div>
@endif

<script>
$(document).ready(function() {
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
});
</script>

@endsection