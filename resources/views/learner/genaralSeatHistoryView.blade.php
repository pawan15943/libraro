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

@if($learners->isEmpty())
<div class="info-message mb-3">
    <span class="close-btn" onclick="this.parentElement.style.display='none';">&times;</span>
    There is currently no history available for this General seat for any learners.
</div>
@else

<div class="learner-list-module">
    <div class="mb-3">
        <p class="m-0"><b>{{ $learners->total() }} Records for {{ $learners->perPage() }} per page</b></p>
    </div>

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
                                        <span class="text-secondary"><i class="fa-regular fa-clock me-1"></i> Closed Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}</span>
                                    @elseif($operation == 'deleteSeat' && $value->deleted_at != null)
                                        <span class="text-danger"><i class="fa-regular fa-clock me-1"></i> Deleted Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}</span>
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
                                <span class="avatar-status-dot {{ $planStatus['class'] == 'expired' ? 'dot-extension' : 'dot-active' }}"></span>
                            </div>
                            <div class="learner-details-text">
                                <h5 class="learner-name-title">{{ $learner->name ?? '' }}</h5>
                                <div class="detail-row">
                                    <span class="detail-label">UID :</span>
                                    <a href="{{ route('learners.show', $learner_id) }}" class="detail-value">{{ $learner->learner_no ?? '' }}</a>
                                    <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $learner->learner_no ?? '' }}" title="Copy UID">
                                        <i class="fa-regular fa-clone"></i>
                                    </button>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">M :</span>
                                    <a href="tel:+91-{{ $learner->mobile ?? '' }}" class="detail-value">+91-{{ $learner->mobile ? display_learner_mobile($learner->mobile) : '' }}</a>
                                    @if($learner && $learner->mobile)
                                    <button type="button" class="copy-action-btn" data-copy="{{ $learner->mobile }}" title="Copy Mobile">
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
                                <span class="avatar-status-dot {{ ($operation == 'closeSeat' || ($operation == 'deleteSeat' && $value->deleted_at != null)) ? 'dot-extension' : 'dot-active' }}" title="{{ $planStatus['status'] ?? '' }}"></span>
                            </div>
                            <div class="mobile-details-text">
                                <div class="mobile-name-row">
                                    <h5 class="mobile-name">{{ $learner->name ?? '' }}</h5>
                                    <span class="mobile-seat-badge">Seat GEN</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">UID :</span>
                                    <a href="{{ route('learners.show', $learner_id) }}" class="detail-value">{{ $learner->learner_no ?? '' }}</a>
                                    <button type="button" class="copy-btn copy-action-btn" data-copy="{{ $learner->learner_no ?? '' }}" title="Copy UID">
                                        <i class="fa-regular fa-clone"></i>
                                    </button>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">M :</span>
                                    <a href="tel:+91-{{ $learner->mobile ?? '' }}" class="detail-value">+91-{{ display_learner_mobile($learner->mobile) }}</a>
                                    @if($learner && $learner->mobile)
                                    <button type="button" class="copy-action-btn" data-copy="{{ $learner->mobile }}">
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
                        <a href="{{ route('learners.show', $value->id) }}" class="mobile-chevron-link" title="View Details">
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
                                @elseif($operation == 'deleteSeat' && $value->deleted_at != null)
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
});
</script>

@endsection