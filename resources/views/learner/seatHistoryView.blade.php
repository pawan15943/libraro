@extends('layouts.library')
@section('content')

<link rel="stylesheet" href="{{ asset('public/css/learner-list.css') }}?v={{ time() }}" />

{{-- Instantly suppress full-page blocking overlay loader on seat history view so content-wise skeleton shimmer & row cascade are visible --}}
<style>
    #loaderone, #loader {
        display: none !important;
        opacity: 0 !important;
        visibility: hidden !important;
        pointer-events: none !important;
        z-index: -9999 !important;
    }
</style>
<script>
    try {
        var el1 = document.getElementById('loaderone');
        if (el1) el1.remove();
        var el0 = document.getElementById('loader');
        if (el0) el0.remove();
    } catch(e) {}
</script>

<!-- Profile Image Preview Modal -->
<div id="imageViewModal" class="image-modal" style="display:none;opacity:0;" aria-hidden="true">
    <div class="image-modal-content">
        <span class="close-modal" role="button" tabindex="0" aria-label="Close">&times;</span>
        <img src="" id="modalImage" alt="Profile photo preview">
    </div>
</div>

@if($learners->isEmpty())
<div class="info-message mb-3">
    <span class="close-btn" onclick="this.parentElement.style.display='none';">&times;</span>
    There is currently no history available for this seat for any learners.
</div>
@else

<div class="learner-list-module">
    <div class="mb-3">
        <p class="m-0"><b>{{ $learners->total() }} Records for {{ $learners->perPage() }} per page</b></p>
    </div>

    {{-- Skeleton Loader for Initial Page Load --}}
    @include('learner.partials.skeleton-cards', ['count' => min(max($learners->count(), 3), 5)])

    {{-- Real Learner Cards Container (Revealed Row by Row) --}}
    <noscript>
        <style>
            .learner-list-module .learner-skeleton-container { display: none !important; }
            .learner-list-module .learner-cards-list { display: block !important; }
            .learner-list-module .learner-cards-list .learner-card { opacity: 1 !important; transform: none !important; pointer-events: auto !important; }
        </style>
    </noscript>
    <div id="learnerCardsList" class="learner-cards-list">

    @foreach($learners as $value)
    @php
        $planStatus = getPlanStatusDetails($value->plan_end_date);
        $learner_detail_id = $value->id;
        $learner = myLearner($value->learner_id);
        $learner_id = $value->learner_id;
        $transaction = learnerTransaction($value->learner_id, $learner_detail_id);

        if ($transaction && isset($transaction->pending_amount) && $transaction->due_date) {
            $due_date = $transaction->due_date;
        } else {
            $due_date = null;
        }

        $operation = optional(getLearnerOperation($learner_detail_id))->operation;
        $operationDate = optional(getLearnerOperation($learner_detail_id))->created_at;
        $formattedDueDate = !empty($due_date) ? (is_object($due_date) ? (!empty($due_date->due_date) ? date('j M', strtotime($due_date->due_date)) : '') : date('j M', strtotime($due_date))) : '';
        $totalPendingAmt = optional($transaction)->pending_amount ?? 0;
        $shvIsNonExpiry = ((int)($value->no_expiry ?? 0) === 1);
        $shvDotColor = $shvIsNonExpiry ? 'dot-non-expiry' : ($planStatus['class'] == 'expired' ? 'dot-extension' : 'dot-active');
        $shvDotTitle = $shvIsNonExpiry ? 'Non-Expired' : ($planStatus['status'] ?? 'Active');
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
                                <span class="seat-val">
                                    @if(!empty($learner) && !empty($learner->seat_no))
                                        {{ getSeatDisplayShortFloorName($learner->seat_no) }}
                                    @else
                                        GEN
                                    @endif
                                </span>
                            </div>
                            <div class="learner-meta-block">
                                <span class="learner-expiry-line">
                                    @if($operation == 'closeSeat')
                                        <span class="text-secondary"><i class="fa-regular fa-clock me-1"></i> Closed Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}</span>
                                    @elseif($operation == 'deleteSeat' && $value->deleted_at != null)
                                        <span class="text-danger"><i class="fa-regular fa-clock me-1"></i> Deleted Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}</span>
                                    @elseif($shvIsNonExpiry)
                                        <span class="non_expired_class" style="color: #c8009d !important; font-weight: 600;"><i class="fa-regular fa-clock me-1"></i> Non-Expired</span>
                                    @else
                                        {!! getUserStatusWithSpan($value->plan_end_date, $learner_id) !!}
                                    @endif
                                </span>
                            </div>
                        </div>

                        <ul class="learner-actions-strip">
                            <li>
                                <a href="{{ route('learners.show', $value->id) }}" class="action-btn-pill" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="View Seat Details">
                                    <i class="fa-solid fa-eye me-1"></i> View Seat Details
                                </a>
                            </li>
                        </ul>
                    </div>

                    {{-- Bottom Grid: 5 Columns --}}
                    <div class="learner-bottom-grid">
                        {{-- Column 1: Learner Profile --}}
                        <div class="learner-profile-col">
                            <div class="avatar-wrap">
                                <a href="{{ (!empty($learner) && !empty($learner->profile_picture)) ? asset($learner->profile_picture) : asset('public/img/student_profile.jpeg') }}" class="view-image learner-list-profile-photo" title="View profile photo">
                                    <img src="{{ (!empty($learner) && !empty($learner->profile_picture)) ? asset($learner->profile_picture) : asset('public/img/student_profile.jpeg') }}"
                                         alt="{{ $learner->name ?? 'profile' }}" class="avatar-img">
                                </a>
                                <span class="avatar-status-dot {{ $shvDotColor }}" title="{{ $shvDotTitle }}" data-bs-toggle="tooltip" data-bs-title="{{ $shvDotTitle }}"></span>
                            </div>
                            <div class="learner-details-text">
                                <h5 class="learner-name-title">{{ $learner->name ?? '' }}</h5>
                                <div class="detail-row">
                                    <span class="detail-label">UID:</span>
                                    <a href="{{ route('learners.show', $learner_id) }}" class="detail-value" title="View Profile">{{ $learner->learner_no ?? '' }}</a>
                                    <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $learner->learner_no ?? '' }}" title="Copy UID" data-bs-toggle="tooltip">
                                        <i class="fa-regular fa-clone"></i>
                                    </button>
                                    @if(!empty($learner?->mobile))
                                    <span class="contact-inline-sep"></span>
                                    <a href="tel:+91-{{ $learner->mobile }}" class="contact-call-btn" title="Call +91-{{ $learner->mobile }}" data-bs-toggle="tooltip">
                                        <i class="fa-solid fa-phone"></i>
                                    </a>
                                    <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $learner->mobile }}" title="Copy Mobile (+91-{{ $learner->mobile }})" data-bs-toggle="tooltip">
                                        <i class="fa-regular fa-copy"></i>
                                    </button>
                                    @endif
                                    @if(!empty($learner?->email))
                                        <a href="mailto:{{ $learner->email }}" class="contact-email-btn" title="Email: {{ $learner->email }}" data-bs-toggle="tooltip">
                                            <i class="fa-regular fa-envelope"></i>
                                        </a>
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
                                    {{ $value->planType->name ?? '—' }}@if(!empty($value->plan->name)) ({{ $value->plan->name }})@endif
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
                                <a href="{{ (!empty($learner) && !empty($learner->profile_picture)) ? asset($learner->profile_picture) : asset('public/img/student_profile.jpeg') }}" class="view-image learner-list-profile-photo" title="View profile photo">
                                    <img src="{{ (!empty($learner) && !empty($learner->profile_picture)) ? asset($learner->profile_picture) : asset('public/img/student_profile.jpeg') }}"
                                         alt="{{ $learner->name ?? 'profile' }}" class="avatar-img">
                                </a>
                                <span class="avatar-status-dot {{ $shvDotColor }}" title="{{ $shvDotTitle }}"></span>
                            </div>
                            <div class="mobile-details-text">
                                <div class="mobile-name-row">
                                    <h5 class="mobile-name">{{ $learner->name ?? '' }}</h5>
                                    <span class="mobile-seat-badge">Seat {{ $value->seat_no ? getSeatDisplayShortFloorName($value->seat_no) : 'GEN' }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">UID:</span>
                                    <a href="{{ route('learners.show', $learner_id) }}" class="detail-value" title="View Profile">{{ $learner->learner_no ?? '' }}</a>
                                    <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $learner->learner_no ?? '' }}" title="Copy UID" data-bs-toggle="tooltip">
                                        <i class="fa-regular fa-clone"></i>
                                    </button>
                                    @if(!empty($learner?->mobile))
                                    <span class="contact-inline-sep"></span>
                                    <a href="tel:+91-{{ $learner->mobile }}" class="contact-call-btn" title="Call +91-{{ $learner->mobile }}" data-bs-toggle="tooltip">
                                        <i class="fa-solid fa-phone"></i>
                                    </a>
                                    <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $learner->mobile }}" title="Copy Mobile (+91-{{ $learner->mobile }})" data-bs-toggle="tooltip">
                                        <i class="fa-regular fa-copy"></i>
                                    </button>
                                    @endif
                                    @if(!empty($learner?->email))
                                        <a href="mailto:{{ $learner->email }}" class="contact-email-btn" title="Email: {{ $learner->email }}" data-bs-toggle="tooltip">
                                            <i class="fa-regular fa-envelope"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('learners.show', $value->id) }}" class="mobile-chevron-link" title="View Details">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </div>

                    {{-- Collapsible Banner --}}
                    @php
                        $shvBannerClass = 'banner-warning';
                        if ($operation == 'closeSeat' || ($operation == 'deleteSeat' && $value->deleted_at != null)) {
                            $shvBannerClass = 'banner-danger';
                        } elseif ($shvIsNonExpiry) {
                            $shvBannerClass = 'banner-pink';
                        }
                    @endphp
                    <div class="mobile-expiry-banner {{ $shvBannerClass }} js-mobile-collapsible-toggle" role="button" tabindex="0">
                        <div class="mobile-expiry-text">
                            <i class="fa-regular fa-clock"></i>
                            <span>
                                @if($operation == 'closeSeat')
                                    Closed Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}
                                @elseif($operation == 'deleteSeat' && $value->deleted_at != null)
                                    Deleted Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}
                                @elseif($shvIsNonExpiry)
                                    Non-Expired
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
                                    {{ $value->planType->name ?? '—' }}@if(!empty($value->plan->name)) ({{ $value->plan->name }})@endif
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
                            <div class="mobile-action-item">
                                <a href="{{ route('learners.show', $value->id) }}" class="action-btn">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <span class="mobile-action-label">Details</span>
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

                        {{-- Full-width "View Seat Details →" button --}}
                        <a href="{{ route('learners.show', $value->id) }}" class="mobile-view-details-btn">
                            View Seat Details <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
    </div> {{-- End #learnerCardsList --}}

    {{-- Pagination --}}
    @if ($learners->lastPage() > 1)
    <ul class="paginations mt-4">
        {{-- Prev Button --}}
        <li>
            <a href="{{ $learners->onFirstPage() ? '#' : $learners->appends(request()->all())->previousPageUrl() }}" class="w-auto px-3 text-muted {{ $learners->onFirstPage() ? 'disabled' : '' }}">
                Prev
            </a>
        </li>

        {{-- Page Numbers --}}
        @for ($i = 1; $i <= $learners->lastPage(); $i++)
            <li>
                <a href="{{ $learners->appends(request()->all())->url($i) }}" class="{{ $learners->currentPage() == $i ? 'active' : '' }}">
                    {{ $i }}
                </a>
            </li>
        @endfor

        {{-- Next Button --}}
        <li>
            <a href="{{ $learners->hasMorePages() ? $learners->appends(request()->all())->nextPageUrl() : '#' }}" class="w-auto px-3 text-muted {{ $learners->hasMorePages() ? '' : 'disabled' }}">
                Next
            </a>
        </li>
    </ul>
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

    // Skeleton fade out & sequential row-by-row card entrance
    (function() {
        // Dismiss background overlay loaders
        try {
            $('#loaderone, #loader').remove();
        } catch(e) {}

        var skeletonContainer = document.getElementById('learnerSkeletonContainer');
        var cardsList = document.getElementById('learnerCardsList');
        if (!cardsList) return;

        var cards = cardsList.querySelectorAll('.learner-card');
        if (!cards.length) {
            if (skeletonContainer) skeletonContainer.style.display = 'none';
            cardsList.classList.add('is-active');
            return;
        }

        // Display crisp content-wise skeleton shimmer for 450ms, then cascade real data row by row
        setTimeout(function() {
            if (skeletonContainer) {
                skeletonContainer.classList.add('fade-out');
                setTimeout(function() {
                    skeletonContainer.style.display = 'none';
                }, 200);
            }

            cardsList.classList.add('is-active');
            void cardsList.offsetHeight; // Force reflow

            cards.forEach(function(card, index) {
                setTimeout(function() {
                    card.classList.add('is-loaded');
                }, index * 50); // 50ms per row creates a silky-smooth cascade
            });
        }, 450);

        // Safety fallback: ensure cards are never stuck hidden
        setTimeout(function() {
            if (skeletonContainer && skeletonContainer.style.display !== 'none') {
                skeletonContainer.style.display = 'none';
            }
            if (!cardsList.classList.contains('is-active')) {
                cardsList.classList.add('is-active');
            }
            cards.forEach(function(c) {
                if (!c.classList.contains('is-loaded')) {
                    c.classList.add('is-loaded');
                }
            });
        }, 1200);
    })();
});
</script>

@endsection