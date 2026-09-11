@extends('layouts.library')
@section('content')

<link rel="stylesheet" href="{{ asset('public/css/learner-list.css') }}?v={{ time() }}" />
<link rel="stylesheet" href="{{ asset('public/css/learner-search.css') }}?v={{ time() }}" />

<!-- Transactions Modal -->
<div class="modal fade" id="cf-modal" tabindex="-1" aria-labelledby="cf-modal-label" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="cf-modal-label">Transactions</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="transactionPendingBadge" class="small text-muted mb-2 d-none"></p>
        <div class="table-responsive mb-4">
          <table class="table table-bordered table-sm align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Plan Price</th>
                <th>Other Addon Cost <span class="fw-normal text-muted small">(Locker | miscellaneous | token)</span></th>
                <th>Discount on Plan Price</th>
                <th>Paid Amt</th>
                <th>Pending Amt</th>
                <th>Payment Date</th>
                <th>Payment Mode</th>
                <th>Download Receipt</th>
              </tr>
            </thead>
            <tbody id="transactionTableBody">
              <tr>
                <td colspan="9" class="text-center text-muted">Open from a learner row to load data.</td>
              </tr>
            </tbody>
          </table>
        </div>
        <h6 class="mb-2">All activity for this learner</h6>
        <div class="table-responsive">
          <table class="table table-bordered table-sm align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Particular</th>
                <th>Payment Type</th>
                <th>Payment Mode</th>
                <th>Amt.</th>
                <th>Payment Date</th>
                <th>Dr / Cr</th>
              </tr>
            </thead>
            <tbody id="activityTableBody">
              <tr>
                <td colspan="6" class="text-center text-muted">—</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Large profile preview modal -->
<div id="imageViewModal" class="image-modal" style="display:none;opacity:0;" aria-hidden="true">
    <div class="image-modal-content">
        <span class="close-modal" role="button" tabindex="0" aria-label="Close">&times;</span>
        <img src="" id="modalImage" alt="Profile photo preview">
    </div>
</div>

@php
// Computed once per page load for performance
$hiddenFields = function_exists('toggleHideField') ? toggleHideField() : [];
$currentBranchName = function_exists('getCurrentBranchName') ? getCurrentBranchName() : '';
$isNotificationActive = function_exists('notificationActive') ? notificationActive() : false;
$isWabaNotificationActive = $isNotificationActive && function_exists('wabaNotificationActive') && wabaNotificationActive();
$isTextNotificationActive = $isNotificationActive && function_exists('textNotificationActive') && textNotificationActive();
@endphp

@can('has-permission', 'Search Learner')
<div class="learner-search-page-module">
    <!-- Modern New Theme Search Bar -->
    <form action="{{ route('learner.search') }}" method="GET" class="search-bar-shell" id="learnerSearchForm">
        <div class="search-input-row">
            <div class="search-field-box">
                <div class="search-field-icon">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <div class="search-field-content">
                    <label class="search-field-label" for="search-input">Search Learner</label>
                    <input type="text" 
                           name="search" 
                           class="search-real-input @error('search') is-invalid @enderror" 
                           value="{{ request()->get('search') }}" 
                           placeholder="Enter Name, Mobile Number, UID or Seat No..." 
                           id="search-input"
                           autocomplete="off">
                </div>
                <button type="button" class="search-clear-input-btn" id="clearInputBtn" title="Clear input text">
                    <i class="fa-solid fa-circle-xmark"></i>
                </button>
            </div>

            <button class="search-submit-btn" type="submit">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span>Search</span>
            </button>
        </div>

        <!-- Footer Row with Expired Toggle & Controls -->
        <div class="search-bar-footer">
            <div class="expired-toggle-group">
                <div class="form-check form-switch m-0 d-flex align-items-center gap-2">
                    <input class="form-check-input" 
                           type="checkbox" 
                           role="switch" 
                           id="includeExpiredToggle" 
                           name="include_expired" 
                           value="1" 
                           {{ request()->boolean('include_expired') ? 'checked' : '' }} 
                           onchange="this.form.submit()">
                    <label class="toggle-label-text" for="includeExpiredToggle">
                        Include Expired &amp; Closed Seats
                    </label>
                </div>
                @if(request()->boolean('include_expired'))
                    <span class="status-tag tag-all">Including All</span>
                @else
                    <span class="status-tag tag-active">Active Only</span>
                @endif
            </div>

            <div class="search-footer-right">
                @if(request()->filled('search') || request()->boolean('include_expired'))
                <a href="{{ route('learner.search') }}" class="search-clear-btn">
                    <i class="fa-solid fa-rotate-right"></i> Reset Search
                </a>
                @else
                <div class="search-hints-group">
                    <span class="text-muted small me-1">Search by:</span>
                    <span class="search-hint-pill"><i class="fa-solid fa-user text-primary"></i> Name</span>
                    <span class="search-hint-pill"><i class="fa-solid fa-phone text-success"></i> Mobile</span>
                    <span class="search-hint-pill"><i class="fa-solid fa-id-badge" style="color:#a855f7;"></i> UID</span>
                    <span class="search-hint-pill"><i class="fa-solid fa-chair text-warning"></i> Seat No.</span>
                </div>
                @endif
            </div>
        </div>
    </form>

    <!-- Results Section -->
    @if($learners === null)
    <!-- Initial State: Nothing in search bar yet (Do not show records) -->
    <div class="search-initial-card">
        <div class="search-initial-icon-box">
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>
        <h4 class="search-initial-title">Find a Learner</h4>
        <p class="search-initial-desc">
            Enter a learner's name, mobile number, UID, or seat number in the search bar above to view their details, subscription status, and payment history.
        </p>
        <div class="search-chips-row">
            <span class="search-quick-chip" onclick="document.getElementById('search-input').focus();"><i class="fa-solid fa-font text-primary"></i> By Name</span>
            <span class="search-quick-chip" onclick="document.getElementById('search-input').focus();"><i class="fa-solid fa-phone text-success"></i> By Mobile Number</span>
            <span class="search-quick-chip" onclick="document.getElementById('search-input').focus();"><i class="fa-solid fa-id-badge" style="color:#a855f7;"></i> By UID</span>
            <span class="search-quick-chip" onclick="document.getElementById('search-input').focus();"><i class="fa-solid fa-chair text-warning"></i> By Seat Number</span>
        </div>
    </div>

    @elseif(isset($learners) && method_exists($learners, 'total') && $learners->total() == 0)
    <!-- Empty State: Search executed but 0 matches found -->
    <div class="no-data-found text-center py-5">
        <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>
        <dotlottie-wc src="https://lottie.host/2bd4f1dd-bce9-44cb-b8a4-f5acd681c123/sHuYyTQ6uD.lottie" style="width: 180px; height: 180px; margin: 0 auto;" autoplay loop></dotlottie-wc>
        <h4 class="mt-3 fw-bold" style="color: #18225f;">No Learners Found</h4>
        <span class="text-muted">No learners match your search query "{{ request()->get('search') }}". Try searching with a different name, mobile number or seat no.</span>
    </div>

    @else
    <div class="learner-list-module">
        <!-- Records Count & Sort Bar -->
        <div class="mb-3 set-table">
            <p class="m-0">
                <b>{{ method_exists($learners, 'total') ? $learners->total() : count($learners) }} Records</b>
                @if(method_exists($learners, 'perPage'))
                for {{ $learners->perPage() }} per page
                @endif
            </p>
            <a class="sort" href="{{ request()->fullUrlWithQuery([
                'sort_by' => 'seat_no',
                'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'
            ]) }}">
                Sort by Seat No.
                @if(request('sort_by') == 'seat_no')
                ({{ request('sort_order') == 'asc' ? '↑' : '↓' }})
                @endif
            </a>
        </div>

        <!-- Learner Cards Loop (Exact Learner List UI) -->
        @foreach($learners ?? [] as $key => $value)

        @php
        $learner_detail_id = $value->learner_detail_id;
        $planStatus = getPlanStatusDetails($value->plan_end_date);
        $rowContextData = $rowContext[$learner_detail_id] ?? [];
        $transaction = $rowContextData['transaction'] ?? (function_exists('learnerTransaction') && !empty($value->id) && !empty($learner_detail_id) ? learnerTransaction($value->id, $learner_detail_id) : null);
        $totalPendingAmt = $rowContextData['total_pending'] ?? (optional($transaction)->pending_amount ?? 0);
        $totalExtraAmt = $rowContextData['total_extra'] ?? 0;
        $paylaterFlag = $rowContextData['paylater'] ?? false;
        $hasPendingAmtFlag = $rowContextData['has_pending_amt'] ?? false;
        $paybleRefundAmt = $rowContextData['payble_refund'] ?? 0;
        $overdueFlag = $rowContextData['overdue'] ?? false;
        $isRenewUpdateFlag = $rowContextData['is_renew_update'] ?? false;
        $canRenewFlag = $rowContextData['can_renew'] ?? true;
        $statusPrecomputed = $rowContextData['status_precomputed'] ?? null;

        $oneWeekLater = \Carbon\Carbon::parse($value->plan_start_date)->addWeek();
        $due_date = $rowContextData['due_date'] ?? ($transaction->due_date ?? null);

        $today = \Carbon\Carbon::now();
        $threeDaysAfterStart = \Carbon\Carbon::parse($value->plan_start_date)->addDays(3);
        $operationRow = $rowContextData['operation'] ?? null;
        $operation = optional($operationRow)->operation;
        $operationDate = optional($operationRow)->created_at;
        $learner_id = $value->id;

        // Determine status dot color & title based on plan status
        $dotColorClass = 'dot-active';
        $dotTitle = 'Active';

        if ($operation == 'closeSeat') {
            $dotColorClass = 'dot-closed';
            $dotTitle = 'Closed';
        } elseif ($operation == 'deleteSeat' && $value->deleted_at != null) {
            $dotColorClass = 'dot-extension';
            $dotTitle = 'Deleted';
        } elseif ($value->frozen_status == 1) {
            $dotColorClass = 'dot-frozen';
            $dotTitle = 'Frozen';
        } elseif ((!empty($value->plan_start_date) && \Carbon\Carbon::parse($value->plan_start_date)->isFuture() && (int)($value->status ?? 0) === 0) || (($statusPrecomputed['has_future_start'] ?? false) && !($statusPrecomputed['has_past_plan'] ?? false))) {
            $dotColorClass = 'dot-upcoming';
            $dotTitle = 'Upcoming';
        } elseif ($planStatus['status'] == 'About to Expire' || str_contains(strtolower($planStatus['status'] ?? ''), 'about to expire') || str_contains(strtolower($planStatus['status'] ?? ''), 'today')) {
            $dotColorClass = 'dot-about-to-expire';
            $dotTitle = 'About to Expire';
        } elseif ($planStatus['class'] == 'extedned' || str_contains(strtolower($planStatus['status'] ?? ''), 'extension') || str_contains(strtolower($planStatus['status'] ?? ''), 'expired')) {
            $dotColorClass = 'dot-extension';
            $dotTitle = 'In Extension';
        } else {
            $dotColorClass = 'dot-active';
            $dotTitle = 'Active';
        }

        // Determine expiry status string & banner styling
        if ($operation == 'closeSeat') {
            $expiryHtml = '<span class="text-secondary"><i class="fa-regular fa-clock me-1"></i> Closed Seat on ' . ($operationDate ? date('j M Y', strtotime($operationDate)) : '') . '</span>';
            $bannerClass = 'banner-danger';
            $expiryTextOnly = 'Closed Seat on ' . ($operationDate ? date('j M Y', strtotime($operationDate)) : '');
        } elseif ($operation == 'deleteSeat' && $value->deleted_at != null) {
            $expiryHtml = '<span class="text-danger"><i class="fa-regular fa-clock me-1"></i> Deleted Seat on ' . ($operationDate ? date('j M Y', strtotime($operationDate)) : '') . '</span>';
            $bannerClass = 'banner-danger';
            $expiryTextOnly = 'Deleted Seat on ' . ($operationDate ? date('j M Y', strtotime($operationDate)) : '');
        } else {
            $rawExpiry = getUserStatusWithSpan($value->plan_end_date, $learner_id, $statusPrecomputed);
            $expiryTextOnly = trim(strip_tags($rawExpiry));
            $lowerExpiry = strtolower($expiryTextOnly);
            if (str_contains($lowerExpiry, 'extension') || str_contains($lowerExpiry, 'expired')) {
                $bannerClass = 'banner-danger';
                $expiryHtml = '<span class="text-danger"><i class="fa-regular fa-clock me-1"></i> ' . $expiryTextOnly . '</span>';
            } elseif (str_contains($lowerExpiry, 'about to expire') || str_contains($lowerExpiry, 'today')) {
                $bannerClass = 'banner-warning';
                $expiryHtml = '<span style="color: #d97706 !important;"><i class="fa-regular fa-clock me-1"></i> ' . $expiryTextOnly . '</span>';
            } else {
                $bannerClass = '';
                $expiryHtml = '<span class="text-success"><i class="fa-regular fa-clock me-1"></i> ' . $expiryTextOnly . '</span>';
            }
        }

        $formattedDueDate = !empty($due_date) ? (is_object($due_date) ? (!empty($due_date->due_date) ? date('j M', strtotime($due_date->due_date)) : '') : date('j M', strtotime($due_date))) : '';
        $hasPendingBalance = ($paylaterFlag && $transaction?->pending_amount != 0) || $hasPendingAmtFlag;
        @endphp

        <div class="row">
            <div class="col-lg-12">
                <div class="learner-card">
                    
                    {{-- ================= DESKTOP LAYOUT ================= --}}
                    <div class="desktop-only-section">
                        {{-- Top Row: Seat Box + Expiry on left, Action buttons on right --}}
                        <div class="learner-top-row">
                            <div class="learner-top-left">
                                {{-- Seat Badge Box (Single Line) --}}
                                <div class="seat-badge-box">
                                    <span class="seat-label">Seat No. :</span>
                                    <span class="seat-val">{{ $value->seat_no ? getSeatDisplayShortFloorName($value->seat_no) : 'GEN' }}</span>
                                </div>

                                {{-- Status & Expiry Meta Block (No chips) --}}
                                <div class="learner-meta-block">
                                    <div class="learner-expiry-line">
                                        {!! $expiryHtml !!}
                                    </div>
                                </div>
                            </div>

                            {{-- Action buttons strip --}}
                            <ul class="learner-actions-strip">
                                @include('learner.partials.learner-actions', ['isMobile' => false])
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
                                    <span class="avatar-status-dot {{ $dotColorClass }}" title="{{ $dotTitle }}" data-bs-toggle="tooltip" data-bs-title="{{ $dotTitle }}"></span>
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
                                        @if(!empty($value->plan_start_date))
                                            {{ date('j M Y', strtotime($value->plan_start_date)) }} to 
                                            @if($value->frozen_status == 1)
                                                Frozen
                                            @elseif(!empty($value->plan_end_date))
                                                {{ date('j M Y', strtotime($value->plan_end_date)) }}
                                            @else
                                                —
                                            @endif
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
                                        @if($hasPendingBalance)
                                            <a href="javascript:void(0)" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" class="text-danger fw-bold settlement-learner text-decoration-none">
                                                Due ₹{{ rtrim(rtrim(number_format(($totalPendingAmt), 2, '.', ''), '0'), '.') }}@if(!empty($formattedDueDate)) ({{ $formattedDueDate }})@endif
                                            </a>
                                            <a href="javascript:;" class="open-transaction-modal ms-1 text-muted" data-learner_id="{{ $learner_id }}" data-bs-toggle="modal" data-bs-target="#cf-modal" style="font-size: .8rem;">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                        @elseif(!empty($transaction?->pending_amount) && $transaction?->pending_amount == 0)
                                            <span class="text-success fw-bold payment-status-value">Fully Paid</span>
                                        @elseif(empty($transaction?->pending_amount))
                                            <span class="text-muted payment-status-value">—</span>
                                        @else
                                            <span class="text-success fw-bold payment-status-value">Fully Paid</span>
                                        @endif

                                        @if ($transaction?->id)
                                            <form action="{{ route('learner.receipt.download') }}" method="POST" enctype="multipart/form-data" target="_blank" class="d-inline ms-1">
                                                @csrf
                                                <input type="hidden" name="learner_id" value="{{$learner_id}}">
                                                <input type="hidden" name="id" value="{{($transaction->id ?? 0)}}">
                                                <input type="hidden" name="learner_detail_id" value="{{$learner_detail_id}}">
                                                <input type="hidden" name="type" value="learner">
                                                <button type="submit" class="receipt-btn noLoader" title="Download Receipt">
                                                    <i class="fa-solid fa-download"></i>
                                                </button>
                                            </form>
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
                                        @if($transaction && $transaction->locker_amount)
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
                                    <span class="avatar-status-dot {{ $dotColorClass }}" title="{{ $dotTitle }}"></span>
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
                        <div class="mobile-expiry-banner {{ $bannerClass }} js-mobile-collapsible-toggle" role="button" tabindex="0">
                            <div class="mobile-expiry-text">
                                <i class="fa-regular fa-clock"></i>
                                <span>{{ $expiryTextOnly }}</span>
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
                                        @if(!empty($value->plan_start_date))
                                            {{ date('j M Y', strtotime($value->plan_start_date)) }} to 
                                            @if($value->frozen_status == 1)
                                                Frozen
                                            @elseif(!empty($value->plan_end_date))
                                                {{ date('j M Y', strtotime($value->plan_end_date)) }}
                                            @else
                                                —
                                            @endif
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
                                        @if($hasPendingBalance)
                                            <a href="javascript:void(0)" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" class="text-danger fw-bold settlement-learner text-decoration-none">
                                                Due ₹{{ rtrim(rtrim(number_format(($totalPendingAmt), 2, '.', ''), '0'), '.') }}@if(!empty($formattedDueDate)) ({{ $formattedDueDate }})@endif
                                            </a>
                                            <a href="javascript:;" class="open-transaction-modal ms-1 text-muted" data-learner_id="{{ $learner_id }}" data-bs-toggle="modal" data-bs-target="#cf-modal" style="font-size: .8rem;">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                        @elseif(!empty($transaction?->pending_amount) && $transaction?->pending_amount == 0)
                                            <span class="text-success fw-bold payment-status-value">Fully Paid</span>
                                        @elseif(empty($transaction?->pending_amount))
                                            <span class="text-muted payment-status-value">—</span>
                                        @else
                                            <span class="text-success fw-bold payment-status-value">Fully Paid</span>
                                        @endif

                                        @if ($transaction?->id)
                                            <form action="{{ route('learner.receipt.download') }}" method="POST" enctype="multipart/form-data" target="_blank" class="d-inline ms-1">
                                                @csrf
                                                <input type="hidden" name="learner_id" value="{{$learner_id}}">
                                                <input type="hidden" name="id" value="{{($transaction->id ?? 0)}}">
                                                <input type="hidden" name="learner_detail_id" value="{{$learner_detail_id}}">
                                                <input type="hidden" name="type" value="learner">
                                                <button type="submit" class="receipt-btn noLoader" title="Download Receipt">
                                                    <i class="fa-solid fa-download"></i>
                                                </button>
                                            </form>
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
                                        @if($transaction && $transaction->locker_amount)
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
                                @include('learner.partials.learner-actions', ['isMobile' => true])
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
    </div>

    {{-- Pagination --}}
    @if(isset($learners) && method_exists($learners, 'lastPage') && $learners->lastPage() > 1)
    <ul class="paginations mt-4">
        {{-- Prev --}}
        <li>
            <a href="{{ $learners->onFirstPage() ? '#' : $learners->appends(request()->all())->previousPageUrl() }}" class="w-auto px-3 text-muted">Prev</a>
        </li>

        {{-- Page Numbers (shortened: 1 ... current ... last) --}}
        @if ($learners->currentPage() > 3)
        <li><a href="{{ $learners->appends(request()->all())->url(1) }}">1</a></li>
        <li><span>...</span></li>
        @endif

        @for ($i = max(1, $learners->currentPage() - 2); $i <= min($learners->lastPage(), $learners->currentPage() + 2); $i++)
            <li>
                <a href="{{ $learners->appends(request()->all())->url($i) }}" class="{{ $learners->currentPage() == $i ? 'active' : '' }}">
                    {{ $i }}
                </a>
            </li>
        @endfor

        @if ($learners->currentPage() < $learners->lastPage() - 2)
            <li><span>...</span></li>
            <li><a href="{{ $learners->appends(request()->all())->url($learners->lastPage()) }}">{{ $learners->lastPage() }}</a></li>
        @endif

        {{-- Next --}}
        <li>
            <a href="{{ $learners->hasMorePages() ? $learners->appends(request()->all())->nextPageUrl() : '#' }}" class="w-auto px-3 text-muted">Next</a>
        </li>
    </ul>
    @endif

    @endif
</div> {{-- Close .learner-search-page-module --}}

@endcan

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    var learnerTransactionsDataBase = @json(url('library/learners/transactions-data'));

    $(document).on('click', '.open-transaction-modal', function () {
        var learnerId = $(this).data('learner_id');
        var requestUrl = learnerTransactionsDataBase.replace(/\/+$/, '') + '/' + encodeURIComponent(learnerId);

        $('#transactionPendingBadge').addClass('d-none').text('');
        $('#transactionTableBody').html(
            '<tr><td colspan="9" class="text-center">Loading...</td></tr>'
        );
        $('#activityTableBody').html(
            '<tr><td colspan="6" class="text-center">Loading...</td></tr>'
        );

        $.ajax({
            url: requestUrl,
            type: 'GET',
            success: function (res) {
                if (!res.status) {
                    $('#transactionTableBody').html(
                        '<tr><td colspan="9" class="text-center text-danger">Unable to load transactions.</td></tr>'
                    );
                    $('#activityTableBody').html(
                        '<tr><td colspan="6" class="text-center text-danger">—</td></tr>'
                    );
                    return;
                }

                var badgeText = '';
                if (!res.transactions || res.transactions.length === 0) {
                    badgeText = 'No transaction records for this learner.';
                } else if (res.has_pending) {
                    badgeText = 'Showing rows with pending balance.';
                } else {
                    badgeText = 'No pending balance — showing latest transaction summary.';
                }
                $('#transactionPendingBadge').removeClass('d-none').text(badgeText);

                if (res.transactions && res.transactions.length > 0) {
                    var rows = '';
                    res.transactions.forEach(function (item, index) {
                        var receiptCell = item.receipt_url
                            ? '<a href="' + item.receipt_url + '" target="_blank" rel="noopener">Download</a>'
                            : '<span class="text-muted">—</span>';
                        rows += '<tr>' +
                            '<td>' + (index + 1) + '</td>' +
                            '<td>' + (item.plan_price ?? '—') + '</td>' +
                            '<td>' + (item.other_addon_label ?? '—') + '</td>' +
                            '<td>' + (item.discount_amount ?? '—') + '</td>' +
                            '<td>' + (item.paid_amount ?? '—') + '</td>' +
                            '<td>' + (item.pending_amount ?? '—') + '</td>' +
                            '<td>' + (item.paid_date ?? '—') + '</td>' +
                            '<td>' + (item.payment_mode ?? '—') + '</td>' +
                            '<td>' + receiptCell + '</td>' +
                            '</tr>';
                    });
                    $('#transactionTableBody').html(rows);
                } else {
                    $('#transactionTableBody').html(
                        '<tr><td colspan="9" class="text-center">No transactions found.</td></tr>'
                    );
                }

                if (res.activities && res.activities.length > 0) {
                    var aRows = '';
                    res.activities.forEach(function (a) {
                        aRows += '<tr>' +
                            '<td>' + (a.particular ?? '—') + '</td>' +
                            '<td>' + (a.payment_type ?? '—') + '</td>' +
                            '<td>' + (a.payment_mode ?? '—') + '</td>' +
                            '<td>' + (a.amount_display ?? '—') + '</td>' +
                            '<td>' + (a.payment_date ?? '—') + '</td>' +
                            '<td>' + (a.dr_cr ?? '—') + '</td>' +
                            '</tr>';
                    });
                    $('#activityTableBody').html(aRows);
                } else {
                    $('#activityTableBody').html(
                        '<tr><td colspan="6" class="text-center">No activity recorded.</td></tr>'
                    );
                }
            },
            error: function () {
                $('#transactionTableBody').html(
                    '<tr><td colspan="9" class="text-center text-danger">Failed to load. Please try again.</td></tr>'
                );
                $('#activityTableBody').html(
                    '<tr><td colspan="6" class="text-center text-danger">—</td></tr>'
                );
            }
        });
    });

    function closeProfileImageModal() {
        var $m = $('#imageViewModal');
        $m.stop(true, true).animate({ opacity: 0 }, 150, function () {
            $m.css({ display: 'none' });
            $m.attr('aria-hidden', 'true');
            $('#modalImage').attr('src', '');
        });
    }

    function openProfileImageModal(imageUrl) {
        var $m = $('#imageViewModal');
        $('#modalImage').attr('src', imageUrl);
        $m.attr('aria-hidden', 'false');
        $m.css({ display: 'flex', opacity: 0, zIndex: 99999 }).stop(true, true).animate({ opacity: 1 }, 200);
    }

    // Profile photo: open large preview on click of avatar image or link
    $(document).on('click', 'a.view-image, .view-image, .learner-list-profile-photo, .learner-card .avatar-wrap img', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var imageUrl = $(this).attr('href') || $(this).attr('src') || $(this).find('img').attr('src');
        if (imageUrl && imageUrl !== '#' && imageUrl !== 'javascript:;') {
            openProfileImageModal(imageUrl);
        }
    });

    $('#imageViewModal .close-modal').on('click', function (e) {
        e.stopPropagation();
        closeProfileImageModal();
    });

    $('#imageViewModal').on('click', function (e) {
        if ($(e.target).is(this)) {
            closeProfileImageModal();
        }
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $('#imageViewModal').css('display') === 'flex') {
            closeProfileImageModal();
        }
    });

    $(document).ready(function() {
        var $searchInput = $('#search-input');
        var $clearBtn = $('#clearInputBtn');

        function toggleClearBtn() {
            if ($searchInput.val().trim().length > 0) {
                $clearBtn.show();
            } else {
                $clearBtn.hide();
            }
        }

        toggleClearBtn();

        $searchInput.on('input', function() {
            toggleClearBtn();
        });

        $clearBtn.on('click', function(e) {
            e.preventDefault();
            $searchInput.val('').focus();
            toggleClearBtn();
        });

        // Mobile collapsible subscription toggle
        $(document).on('click', '.js-mobile-collapsible-toggle', function() {
            var $banner = $(this);
            var $content = $banner.next('.mobile-collapsible-content');
            $content.slideToggle(200);
            $banner.toggleClass('is-open');
        });

        // Copy button micro-interaction
        $(document).on('click', '.copy-action-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var val = $(this).data('copy');
            if (val && navigator.clipboard) {
                navigator.clipboard.writeText(val).then(function() {
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Copied: ' + val);
                    }
                }).catch(function() {
                    if (typeof toastr !== 'undefined') {
                        toastr.info('Copied: ' + val);
                    }
                });
            }
        });

        // Mobile Actions "See All >" smooth scroll
        $(document).on('click', '.mobile-actions-seeall', function(e) {
            e.preventDefault();
            var $scroll = $(this).closest('.mobile-actions-container').find('.mobile-actions-scroll');
            if ($scroll.length) {
                var maxScroll = $scroll[0].scrollWidth - $scroll.innerWidth();
                if ($scroll.scrollLeft() >= maxScroll - 15) {
                    $scroll.animate({ scrollLeft: 0 }, 300);
                } else {
                    $scroll.animate({ scrollLeft: maxScroll }, 400);
                }
            }
        });
    });
</script>

@endsection
