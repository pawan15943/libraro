@extends('layouts.library')
@section('content')

{{-- Dedicated Scoped Stylesheet for Learner Profile --}}
<link rel="stylesheet" href="{{ asset('public/css/learner-profile.css') }}?v={{ time() }}" />

@php
    // Calculate Initials from Name
    $nameParts = explode(' ', trim($customer->name ?? ''));
    $initials = '';
    if (count($nameParts) >= 2) {
        $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
    } elseif (count($nameParts) == 1 && !empty($nameParts[0])) {
        $initials = strtoupper(substr($nameParts[0], 0, 2));
    } else {
        $initials = 'ST';
    }

    // ID Proof Type Label
    $idProofName = 'Not available';
    if ($customer->id_proof_name == 1) $idProofName = 'Aadhar Card';
    elseif ($customer->id_proof_name == 2) $idProofName = 'Driving License';
    elseif ($customer->id_proof_name == 4) $idProofName = 'Pan Card';
    elseif ($customer->id_proof_name == 5) $idProofName = 'Voter Id';
    elseif ($customer->id_proof_name == 3) $idProofName = 'Other';

    // Payment Status
    $isPaid = (isset($transaction->is_paid) && $transaction->is_paid == 1) || ($customer->is_paid == 1);
    $pendingAmt = isset($transaction->pending_amount) ? (float)$transaction->pending_amount : 0;
    $totalAmt = isset($transaction->total_amount) ? (float)$transaction->total_amount : ($customer->plan_price_id ?? 0);
    $paidAmt = isset($transaction->paid_amount) ? (float)$transaction->paid_amount : 0;

    // Branch Name
    $branchName = \App\Models\Branch::where('id', $customer->branch_id)->value('name') ?? 'Main Branch';

    // Plan & Status Calculation (Matching other learner pages)
    $planDetails = getPlanStatusDetails($customer->plan_end_date);
    $class = $planDetails['class'] ?? 'booked';

    if (!empty($customer->seat_no)) {
        if (isset($planDetails['diff_in_days']) && $planDetails['diff_in_days'] < 0) {
            $statusText = 'Expired';
            $statusClass = 'status-expired';
            $statusIcon = 'fa-solid fa-circle-exclamation';
        } elseif (isset($planDetails['diff_in_days']) && $planDetails['diff_in_days'] <= 5) {
            $statusText = 'Expiring Soon';
            $statusClass = 'status-warning';
            $statusIcon = 'fa-solid fa-triangle-exclamation';
        } else {
            $statusText = 'Active';
            $statusClass = 'status-active';
            $statusIcon = 'fa-solid fa-circle-check';
        }
    } else {
        $statusText = 'Active';
        $statusClass = 'status-active';
        $statusIcon = 'fa-solid fa-circle-check';
    }

    // Floor and Seat Resolution
    $currentSeatNo = $customer->seat_no ?? ($customer->learner->seat_no ?? null);
    $currentBranchId = $customer->branch_id ?? ($customer->learner->branch_id ?? getCurrentBranch());
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
@endphp

<div class="learner-profile-module">
    <div class="learner-profile-wrapper">

        {{-- TOP SEAT HEADER HERO CARD (MATCHING ALL OTHER LEARNER PAGES) --}}
        <div class="learner-seat-header-card">
            <div class="seat-header-main">
                <div class="seat-header-identity">
                    <div class="seat-header-avatar-box">
                        @php
                            $learnerProfilePic = $customer->profile_picture ?? ($customer->learner->profile_picture ?? ($customer->image ?? null));
                        @endphp
                        @if($learnerProfilePic && file_exists(public_path($learnerProfilePic)))
                            <img id="topSeatAvatarImg" src="{{ asset($learnerProfilePic) }}" alt="{{ $customer->name }}" class="avatar-user-photo">
                        @elseif(isset($customer->plantype) && $customer->plantype && $customer->plantype->image)
                            <img id="topSeatAvatarImg" src="{{ asset($customer->plantype->image) }}" alt="Seat" class="avatar-seat-chair {{ $class }}">
                        @else
                            <div class="avatar-initials-fallback">{{ $initials }}</div>
                        @endif
                    </div>
                    <div class="seat-header-info">
                        <div class="seat-badge-row">
                            <span class="seat-status-badge {{ $statusClass }}">
                                <i class="{{ $statusIcon }} me-1"></i>{{ $statusText }}
                            </span>
                            @if($isPaid)
                                <span class="seat-status-badge status-active">
                                    <i class="fa-solid fa-circle-check me-1"></i>Fee Paid
                                </span>
                            @elseif($pendingAmt > 0)
                                <span class="seat-status-badge status-warning">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i>Pending: ₹{{ number_format($pendingAmt, 2) }}
                                </span>
                            @else
                                <span class="seat-status-badge status-expired">
                                    <i class="fa-solid fa-circle-xmark me-1"></i>Unpaid
                                </span>
                            @endif
                        </div>
                        <h3 class="seat-title text-uppercase">
                            {{ strtoupper($customer->name ?? ($customer->learner->name ?? 'Learner')) }}
                        </h3>
                        <p class="seat-subtitle">
                            <span>UID: <strong class="seat-uid-tag">{{ $customer->learner_no ?? ($customer->learner->learner_no ?? ('#' . $customer->id)) }}</strong></span>
                        </p>
                    </div>
                </div>
                <div class="seat-header-actions">
                    @can('has-permission', 'Learners Edit')
                    <a href="{{ route('learners.edit', $customer->id) }}" class="btn-seat-back btn-back-desktop" title="Edit Student">
                        <i class="fa-solid fa-pen"></i> <span class="btn-back-text">Edit Student</span>
                    </a>
                    @endcan
                    <a href="{{ route('learners') }}" class="btn-seat-back btn-back-desktop" title="Back to list">
                        <i class="fa-solid fa-arrow-left"></i> <span class="btn-back-text">Back to list</span>
                    </a>
                    {{-- Mobile Collapse/Expand Toggle Arrow (Closed by default on mobile) --}}
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
                        <span class="meta-pill-label">Seat No</span>
                        <strong class="meta-pill-val">{{ $currentSeatNo ? ('#' . $currentSeatNo) : 'Not Assigned' }}</strong>
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

            {{-- 4 GLASSMORPHIC DETAIL TILES (Closed by default on mobile) --}}
            <div class="glass-info-grid is-collapsed" id="glassInfoGrid">
                {{-- Tile 1: Registered --}}
                <div class="glass-tile tile-plan">
                    <div class="glass-tile-icon">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Registered</div>
                        <div class="glass-tile-value">{{ $customer->join_date ? \Carbon\Carbon::parse($customer->join_date)->format('d M Y') : ($customer->created_at ? \Carbon\Carbon::parse($customer->created_at)->format('d M Y') : '—') }}</div>
                    </div>
                </div>

                {{-- Tile 2: Shift / Plan --}}
                <div class="glass-tile tile-shift">
                    <div class="glass-tile-icon">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Plan / Shift</div>
                        <div class="glass-tile-value">{{ $customer->plan_name ?? 'Monthly' }} ({{ $customer->plan_type_name ?? 'General' }})</div>
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
                            {{ $customer->plan_end_date ? \Carbon\Carbon::parse($customer->plan_end_date)->format('d M Y') : '—' }}
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
                            @if(!empty($customer->mobile))
                                <a href="tel:{{ $customer->mobile }}">+91-{{ $customer->mobile }}</a>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

    {{-- Main Profile Card --}}
    <div class="main-profile-card">

        {{-- Section 1: Enrollment --}}
        <div class="profile-section">
            <div class="section-header">
                <h4 class="section-title">Enrollment</h4>
                <p class="section-subtitle">Study location and academic preference</p>
            </div>

            <div class="fields-grid-3">
                {{-- Country / Study Location --}}
                <div class="profile-field-box">
                    <span class="field-lbl">COUNTRY</span>
                    <span class="field-val">{{ strtoupper($branchName) }}</span>
                </div>

                {{-- Study Center / Seat --}}
                <div class="profile-field-box">
                    <span class="field-lbl">STUDY CENTER</span>
                    <span class="field-val">
                        {{ $customer->seat_no ? 'SEAT '.strtoupper(getSeatDisplayShortFloorName($customer->seat_no)) : 'GENERAL SEAT' }}
                    </span>
                </div>

                {{-- Grade / Shift --}}
                <div class="profile-field-box">
                    <span class="field-lbl">GRADE</span>
                    <span class="field-val">
                        {{ !empty($customer->plan_type_name) ? strtoupper($customer->plan_type_name) : 'NOT AVAILABLE' }}
                    </span>
                </div>

                {{-- Preferred Stream / Plan Name --}}
                <div class="profile-field-box">
                    <span class="field-lbl">PREFERRED STREAM</span>
                    <span class="field-val">
                        {{ !empty($customer->plan_name) ? strtoupper($customer->plan_name) : 'PRE-NURTURE' }}
                    </span>
                </div>

                {{-- City / Timings --}}
                <div class="profile-field-box">
                    <span class="field-lbl">CITY</span>
                    <span class="field-val">
                        {{ $customer->hours ? $customer->start_time . ' - ' . $customer->end_time : 'Not available' }}
                    </span>
                </div>

                {{-- Date of Birth --}}
                <div class="profile-field-box">
                    <span class="field-lbl">DATE OF BIRTH</span>
                    <span class="field-val {{ empty($customer->dob) ? 'empty' : '' }}">
                        {{ $customer->dob ? \Carbon\Carbon::parse($customer->dob)->format('d M Y') : 'Not available' }}
                    </span>
                </div>

                {{-- Plan Duration --}}
                <div class="profile-field-box">
                    <span class="field-lbl">PLAN DURATION</span>
                    <span class="field-val">
                        {{ $customer->plan_start_date ? \Carbon\Carbon::parse($customer->plan_start_date)->format('d M Y') : '—' }} to {{ $customer->plan_end_date ? \Carbon\Carbon::parse($customer->plan_end_date)->format('d M Y') : '—' }}
                    </span>
                </div>

                {{-- Plan Status --}}
                <div class="profile-field-box">
                    <span class="field-lbl">PLAN STATUS</span>
                    <span class="field-val {{ $customer->status == 1 ? 'text-success' : 'text-danger' }}">
                        @if($customer->status == 1)
                            Active
                        @elseif($customer->plan_end_date)
                            Expired ({{ \Carbon\Carbon::parse($customer->plan_end_date)->format('d M Y') }})
                        @else
                            Not available
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <hr class="section-divider" />

        {{-- Section 2: Contact --}}
        <div class="profile-section">
            <div class="section-header">
                <h4 class="section-title">Contact</h4>
                <p class="section-subtitle">Phone, messaging, and email</p>
            </div>

            <div class="fields-grid-3">
                {{-- Mobile --}}
                <div class="profile-field-box">
                    <span class="field-lbl">MOBILE</span>
                    <span class="field-val {{ empty($customer->mobile) ? 'empty' : '' }}">
                        @if(!empty($customer->mobile))
                            <a href="tel:{{ $customer->mobile }}" class="field-link">+91-{{ $customer->mobile }}</a>
                        @else
                            Not available
                        @endif
                    </span>
                </div>

                {{-- WhatsApp / Alternate Mobile --}}
                <div class="profile-field-box">
                    <span class="field-lbl">WHATSAPP</span>
                    <span class="field-val {{ empty($customer->alternate_mobile) && empty($customer->mobile) ? 'empty' : '' }}">
                        @php
                            $waNumber = !empty($customer->alternate_mobile) ? $customer->alternate_mobile : $customer->mobile;
                        @endphp
                        @if(!empty($waNumber))
                            <a href="https://wa.me/91{{ $waNumber }}" target="_blank" class="field-link">+91-{{ $waNumber }}</a>
                        @else
                            Not available
                        @endif
                    </span>
                </div>

                {{-- Father / Guardian Name --}}
                <div class="profile-field-box">
                    <span class="field-lbl">FATHER / GUARDIAN</span>
                    <span class="field-val {{ empty($customer->father_name) ? 'empty' : '' }}">
                        {{ $customer->father_name ?? 'Not available' }}
                    </span>
                </div>

                {{-- Email (Spanning Full Width) --}}
                <div class="profile-field-box span-3">
                    <span class="field-lbl">EMAIL</span>
                    <span class="field-val {{ empty($customer->email) ? 'empty' : '' }}">
                        @if(!empty($customer->email))
                            <a href="mailto:{{ $customer->email }}" class="field-link">{{ $customer->email }}</a>
                        @else
                            Not available
                        @endif
                    </span>
                </div>

                {{-- Address (Spanning Full Width if available) --}}
                @if(!empty($customer->address))
                <div class="profile-field-box span-3">
                    <span class="field-lbl">ADDRESS</span>
                    <span class="field-val">{{ $customer->address }}</span>
                </div>
                @endif
            </div>
        </div>

        <hr class="section-divider" />

        {{-- Section 3: Access & documents --}}
        <div class="profile-section">
            <div class="section-header">
                <h4 class="section-title">Access & documents</h4>
                <p class="section-subtitle">Credentials and downloadable files</p>
            </div>

            <div class="fields-grid-3">
                {{-- Password --}}
                <div class="profile-field-box">
                    <span class="field-lbl">PASSWORD</span>
                    <span class="field-val">••••••••</span>
                </div>

                {{-- Fee Receipt --}}
                <div class="profile-field-box">
                    <span class="field-lbl">FEE RECEIPT</span>
                    <span class="field-val">
                        @if(isset($transaction) && $isPaid)
                            <form action="{{ route('learner.receipt.download') }}" method="POST" class="d-inline-block">
                                @csrf
                                <input type="hidden" name="learner_id" value="{{ $customer->id }}">
                                <input type="hidden" name="learner_detail_id" value="{{ $customer->learner_detail_id }}">
                                <input type="hidden" name="id" value="{{ $transaction->id }}">
                                <input type="hidden" name="type" value="learner">
                                <button type="submit" class="btn-download-receipt-pill">
                                    <i class="fa-solid fa-cloud-arrow-down download-icon"></i>
                                    <span>Download Receipt</span>
                                    <span class="receipt-amt-badge">₹{{ number_format($paidAmt, 2) }}</span>
                                </button>
                            </form>
                        @elseif($pendingAmt > 0)
                            <span class="text-danger fw-bold">Pending (₹{{ number_format($pendingAmt, 2) }})</span>
                        @else
                            <span class="empty">Not available</span>
                        @endif
                    </span>
                </div>

                {{-- Locker Number --}}
                <div class="profile-field-box">
                    <span class="field-lbl">LOCKER NUMBER</span>
                    <span class="field-val {{ empty($customer->locker_no) ? 'empty' : '' }}">
                        {{ !empty($customer->locker_no) ? 'Locker #'.$customer->locker_no : 'Not available' }}
                    </span>
                </div>

                {{-- ID Proof --}}
                <div class="profile-field-box">
                    <span class="field-lbl">ID PROOF</span>
                    <span class="field-val {{ $idProofName == 'Not available' ? 'empty' : '' }}">
                        {{ $idProofName }}
                        @if(!empty($customer->id_proof_number))
                            ({{ $customer->id_proof_number }})
                        @endif
                    </span>
                </div>

                {{-- Remark --}}
                <div class="profile-field-box">
                    <span class="field-lbl">REMARK</span>
                    <span class="field-val {{ empty($customer->remark) ? 'empty' : '' }}">
                        {{ $customer->remark ?? 'Not available' }}
                    </span>
                </div>
            </div>
        </div>

    </div>

    {{-- Section 4: Renewal History (Row-Based Cards - Mobile First) --}}
    @if(!empty($renew_detail) && count($renew_detail) > 0)
    <div class="section-card">
        <div class="section-header mb-3">
            <h4 class="section-title">Renewal History</h4>
            <p class="section-subtitle">Past subscriptions, payment receipts, and plan periods</p>
        </div>

        <div class="renewal-list">
            @foreach($renew_detail as $key => $value)
            @php
                $learner_id = $value->learner_id;
                $transactionRenew = App\Models\LearnerTransaction::where('learner_detail_id', $value->id)->where('is_paid', 1)->first();
            @endphp
            <div class="renewal-row-card">
                <div class="renewal-card-top">
                    <div class="renewal-plan-info">
                        <div class="renewal-plan-icon">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <div>
                            <h5 class="renewal-plan-title">{{ $value->plan->name ?? "N/A" }}</h5>
                            <span class="renewal-plan-sub">{{ $value->planType->name ?? "" }}</span>
                        </div>
                    </div>

                    <div class="renewal-dates-pill">
                        <i class="fa-regular fa-calendar"></i>
                        {{ $value->plan_start_date ? \Carbon\Carbon::parse($value->plan_start_date)->format('d M Y') : '—' }}
                        <i class="fa-solid fa-arrow-right mx-1 text-muted" style="font-size: 10px;"></i>
                        {{ $value->plan_end_date ? \Carbon\Carbon::parse($value->plan_end_date)->format('d M Y') : '—' }}
                    </div>
                </div>

                <div class="renewal-meta-grid">
                    <div class="renewal-meta-item">
                        <span class="item-lbl">Amount</span>
                        <span class="item-val price">₹{{ $transactionRenew->total_amount ?? '0' }}</span>
                    </div>

                    <div class="renewal-meta-item">
                        <span class="item-lbl">Payment Mode</span>
                        <span class="item-val">
                            @if($value->payment_mode == 1) Online
                            @elseif($value->payment_mode == 2) Offline
                            @else Pay Later
                            @endif
                        </span>
                    </div>

                    <div class="renewal-meta-item">
                        <span class="item-lbl">Paid On</span>
                        <span class="item-val">
                            {{ $transactionRenew && $transactionRenew->paid_date ? \Carbon\Carbon::parse($transactionRenew->paid_date)->format('d M Y') : 'NA' }}
                        </span>
                    </div>

                    <div class="renewal-meta-item">
                        <span class="item-lbl">Transaction ID</span>
                        <span class="item-val text-truncate" style="max-width: 140px;">
                            {{ $transactionRenew->transaction_id ?? 'NA' }}
                        </span>
                    </div>
                </div>

                @can('has-permission', 'Receipt Generation')
                @if($transactionRenew)
                <div class="renewal-card-bottom">
                    <form action="{{ route('learner.receipt.download') }}" method="POST" class="w-100">
                        @csrf
                        <input type="hidden" name="learner_id" value="{{ $learner_id }}">
                        <input type="hidden" name="learner_detail_id" value="{{ $value->id ?? 'NA' }}">
                        <input type="hidden" name="id" value="{{ $transactionRenew->id ?? 'NA' }}">
                        <input type="hidden" name="type" value="learner">
                        <button type="submit" class="btn-download-receipt-row">
                            <i class="fa fa-print me-1"></i> Download Receipt
                        </button>
                    </form>
                </div>
                @endif
                @endcan
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Section 5: History of Previous Seat Owners (Row-Based Cards - Mobile First) --}}
    @if(!is_null($seat_history) && $seat_history->isNotEmpty())
    <div class="section-card">
        <div class="section-header mb-3">
            <h4 class="section-title">History of Previous Seat Owners</h4>
            <p class="section-subtitle">Learners who previously occupied this seat</p>
        </div>

        <div class="history-list">
            @foreach ($seat_history as $learner)
            @php
                $firstDetail = $learner->learnerDetails->first();
                $transactionRenew = $firstDetail ? App\Models\LearnerTransaction::where('learner_detail_id', $firstDetail->id)->first() : null;
            @endphp
            <div class="seat-history-row-card">
                <div class="history-top-row">
                    <div class="history-user-info">
                        <div class="history-avatar">
                            {{ strtoupper(substr($learner->name ?? 'L', 0, 1)) }}
                        </div>
                        <div>
                            <h5 class="history-user-name">{{ $learner->name }}</h5>
                            <span class="history-seat-tag">
                                <i class="fa-solid fa-chair"></i> {{ getSeatDisplayShortFloorName($learner->seat_no) ?? 'General' }}
                            </span>
                        </div>
                    </div>

                    <div class="history-actions-row">
                        @can('has-permission', 'View Seat')
                        @if($firstDetail)
                        <a href="{{ route('learners.show', $firstDetail->learner_id) }}" class="btn-history-view" title="View Profile">
                            <i class="fas fa-eye"></i> <span>View</span>
                        </a>
                        @endif
                        @endcan

                        @can('has-permission', 'Receipt Generation')
                        @if($transactionRenew && $firstDetail)
                        <form action="{{ route('learner.receipt.download') }}" method="POST" class="d-inline flex-grow-1">
                            @csrf
                            <input type="hidden" name="learner_id" value="{{ $learner->id }}">
                            <input type="hidden" name="learner_detail_id" value="{{ $firstDetail->id ?? 'NA' }}">
                            <input type="hidden" name="id" value="{{ $transactionRenew->id ?? 'NA' }}">
                            <input type="hidden" name="type" value="learner">
                            <button type="submit" class="btn-history-receipt" title="Print Receipt">
                                <i class="fa fa-print"></i> <span>Receipt</span>
                            </button>
                        </form>
                        @endif
                        @endcan
                    </div>
                </div>

                <div class="history-details-grid">
                    <div class="renewal-meta-item">
                        <span class="item-lbl">Mobile</span>
                        <span class="item-val">
                            @if(!empty($learner->mobile))
                                <a href="tel:{{ $learner->mobile }}" class="field-link">{{ $learner->mobile }}</a>
                            @else
                                —
                            @endif
                        </span>
                    </div>

                    <div class="renewal-meta-item">
                        <span class="item-lbl">Plan & Shift</span>
                        <span class="item-val">
                            {{ $firstDetail->plan->name ?? 'N/A' }}
                            @if(!empty($firstDetail->planType->name))
                                <small class="text-muted">({{ $firstDetail->planType->name }})</small>
                            @endif
                        </span>
                    </div>

                    <div class="renewal-meta-item">
                        <span class="item-lbl">Duration</span>
                        <span class="item-val" style="font-size: 12px;">
                            {{ $firstDetail && $firstDetail->plan_start_date ? \Carbon\Carbon::parse($firstDetail->plan_start_date)->format('d M Y') : '—' }} to {{ $firstDetail && $firstDetail->plan_end_date ? \Carbon\Carbon::parse($firstDetail->plan_end_date)->format('d M Y') : '—' }}
                        </span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Section 6: Learner Activity Logs (if present) --}}
    @if(isset($learnerlog) && $learnerlog->count() > 0)
    <div class="section-card">
        <div class="section-header mb-3">
            <h4 class="section-title">Activity Logs</h4>
            <p class="section-subtitle">Audit trail of seat allotments, plan renewals, and profile modifications</p>
        </div>

        <ul class="activity-log-list">
            @foreach($learnerlog as $item)
            <li class="activity-log-item">
                <div class="activity-log-desc">
                    @if(!empty($item['operation_type']))
                        <strong style="color: #18225f;">{{ $item['operation_type'] }}</strong> —
                    @endif
                    {!! $item['message'] !!}
                </div>
                <span class="activity-log-time">
                    <i class="fa-regular fa-clock"></i> {{ $item['date'] }} {{ $item['time'] }}
                </span>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    </div>
</div>

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
</script>

@endsection