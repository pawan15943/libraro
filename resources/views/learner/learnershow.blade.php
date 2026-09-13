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

    // Exam Name if configured
    $examName = null;
    if (!empty($customer->exam_id)) {
        $examName = \App\Models\Exam::where('id', $customer->exam_id)->value('name');
    }
@endphp

<div class="learner-profile-module">
    <div class="learner-profile-wrapper">

    {{-- Page Header --}}
    <div class="profile-page-header">
        <div>
            <h2 class="profile-page-title">Student profile</h2>
            <p class="profile-page-subtitle">Registration, contact, and exam information</p>
        </div>
        <div class="profile-header-actions">
            @can('has-permission', 'Learners Edit')
            <a href="{{ route('learners.edit', $customer->id) }}" class="btn-edit-student">
                <i class="fa-solid fa-pen"></i> Edit student
            </a>
            @endcan
            <a href="{{ route('learners') }}" class="btn-back-list">
                Back to list
            </a>
        </div>
    </div>

    {{-- Top Hero Card --}}
    <div class="hero-profile-card">
        <div class="hero-left">
            @if(!empty($customer->image) && file_exists(public_path($customer->image)))
                <img src="{{ asset($customer->image) }}" alt="{{ $customer->name }}" class="hero-avatar-img">
            @else
                <div class="hero-avatar-box">
                    {{ $initials }}
                </div>
            @endif

            <div class="hero-info">
                <span class="hero-form-label">ALLEN FORM NO.</span>
                <h3 class="hero-name">{{ $customer->name ?? 'Not available' }}</h3>
                <div class="hero-form-no">{{ $customer->learner_no ?? ($customer->id ? 'ID: '.$customer->id : 'Not available') }}</div>

                <div class="hero-badges-row">
                    @if($isPaid)
                        <span class="badge-fee-paid"><i class="fa-solid fa-circle-check"></i> Fee paid</span>
                    @elseif($pendingAmt > 0)
                        <span class="badge-fee-pending"><i class="fa-solid fa-triangle-exclamation"></i> Pending: ₹{{ number_format($pendingAmt, 2) }}</span>
                    @else
                        <span class="badge-fee-pending"><i class="fa-solid fa-circle-xmark"></i> Unpaid</span>
                    @endif

                    @if(!empty($examName))
                        <span class="badge-exam"><i class="fa-solid fa-laptop"></i> {{ $examName }}</span>
                    @else
                        <span class="badge-exam"><i class="fa-solid fa-laptop"></i> Online exam</span>
                    @endif

                    @if($customer->seat_no)
                        <span class="badge-seat-active"><i class="fa-solid fa-chair"></i> Seat {{ getSeatDisplayShortFloorName($customer->seat_no) }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="hero-right">
            <div class="hero-meta-item">
                <span class="meta-label">REGISTERED</span>
                <span class="meta-val">
                    {{ $customer->join_date ? \Carbon\Carbon::parse($customer->join_date)->format('d M Y') : ($customer->created_at ? \Carbon\Carbon::parse($customer->created_at)->format('d M Y') : 'Not available') }}
                </span>
            </div>

            <div class="hero-meta-item">
                <span class="meta-label">TRANSACTION</span>
                <span class="meta-val">
                    {{ isset($transaction->paid_date) && $transaction->paid_date ? \Carbon\Carbon::parse($transaction->paid_date)->format('d M Y') : ($customer->plan_start_date ? \Carbon\Carbon::parse($customer->plan_start_date)->format('d M Y') : 'Not available') }}
                </span>
            </div>

            <div class="hero-meta-item">
                <span class="meta-label">COUNTRY</span>
                <span class="meta-val">
                    {{ strtoupper($branchName) }}
                </span>
            </div>

            <div class="hero-meta-item">
                <span class="meta-label">GRADE</span>
                <span class="meta-val">
                    {{ strtoupper($customer->plan_type_name ?? 'GENERAL') }}
                </span>
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

                {{-- Gender --}}
                <div class="profile-field-box">
                    <span class="field-lbl">GENDER</span>
                    <span class="field-val {{ empty($customer->gender) ? 'empty' : '' }}">
                        {{ !empty($customer->gender) ? ucfirst($customer->gender) : 'Not available' }}
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

                {{-- OTP Status --}}
                <div class="profile-field-box">
                    <span class="field-lbl">OTP</span>
                    <span class="field-val empty">Not available</span>
                </div>

                {{-- Fee Receipt --}}
                <div class="profile-field-box">
                    <span class="field-lbl">FEE RECEIPT</span>
                    <span class="field-val">
                        @if(isset($transaction) && $isPaid)
                            <form action="{{ route('learner.receipt.download') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="learner_id" value="{{ $customer->id }}">
                                <input type="hidden" name="learner_detail_id" value="{{ $customer->learner_detail_id }}">
                                <input type="hidden" name="id" value="{{ $transaction->id }}">
                                <input type="hidden" name="type" value="learner">
                                <button type="submit" class="btn btn-sm btn-link p-0 fw-bold field-link text-decoration-none" style="color: #18225f;">
                                    <i class="fa-solid fa-download me-1 text-success"></i> Download Receipt (₹{{ number_format($paidAmt, 2) }})
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

@endsection