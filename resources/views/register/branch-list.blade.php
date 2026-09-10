@extends('layouts.library')
@section('content')

@if($branches->isEmpty())
<div class="no-data-found">
    <script
        src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js"
        type="module"></script>

    <dotlottie-wc
        src="https://lottie.host/5d973bf9-2f1d-4dd5-925f-86da95dbd7b1/t7dXaWIroC.lottie"
        style="width: 200px;height: 200px"
        autoplay
        loop></dotlottie-wc>
    <h4>You haven’t added any library branches yet.</h4>
    <span> Start by creating your first branch to manage it here.</span>
    @can('has-permission','Add Branch Master')
    <div class="heading-list justify-content-end mb-4">
        <a href="{{ route('branch.create') }}" class="btn btn-primary export m-0">
            <i class="fa-solid fa-plus "></i> Add Branch
        </a>
    </div>
    @else
    <span class="text-danger">You don't have Permission to add Branch</span>
    @endcan
</div>
@else

<div class="branch-card-module">
    @can('has-permission','Add Branch Master')
    <div class="heading-list justify-content-end mb-4">
        <a href="{{ route('branch.configure.create') }}" class="btn btn-primary export m-0">
            <i class="fa-solid fa-plus "></i> Add Branch
        </a>
    </div>
    @endcan

    <div class="row g-4 mb-4">
        @foreach($branches as $key => $value)
        <div class="col-xl-6 col-lg-6 col-12">
            <div class="branch-main-card">
                <div>
                    <!-- Header: Building Icon, Branch Title & Status, 3-dots dropdown -->
                    <div class="branch-card-top">
                        <div class="branch-top-left">
                            <div class="branch-building-icon">
                                <i class="fa-solid fa-building"></i>
                            </div>
                            <div class="branch-title-group">
                                <h4>Branch {{ $key + 1 }}</h4>
                                @if($value->deleted_at)
                                <span class="branch-status-pill inactive">
                                    <span class="status-dot"></span> Inactive
                                </span>
                                @else
                                <span class="branch-status-pill active">
                                    <span class="status-dot"></span> Active
                                </span>
                                @endif
                            </div>
                        </div>

                        <div class="dropdown">
                            <button class="branch-menu-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="More Options">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end branch-dropdown-menu shadow-lg">
                                @can('has-permission','Add Library Seats')
                                <li>
                                    <a class="dropdown-item" href="{{ route('seat.create', $value->id) }}">
                                        <span class="dropdown-icon-box text-primary"><i class="fa-solid fa-chair"></i></span>
                                        <span>Update Seats</span>
                                    </a>
                                </li>
                                @endcan
                                @can('has-permission','Add Operating Hours')
                                <li>
                                    <a class="dropdown-item" href="{{ route('hour.create', $value->id) }}">
                                        <span class="dropdown-icon-box text-warning"><i class="fa-solid fa-clock-rotate-left"></i></span>
                                        <span>Operating Hours</span>
                                    </a>
                                </li>
                                @endcan
                                @can('has-permission','Add Extend Days')
                                <li>
                                    <a class="dropdown-item" href="{{ route('extendDay.create', $value->id) }}">
                                        <span class="dropdown-icon-box text-success"><i class="fa-solid fa-calendar-plus"></i></span>
                                        <span>Extend Days</span>
                                    </a>
                                </li>
                                @endcan
                                @can('has-permission','Add Locker Amount')
                                <li>
                                    <a class="dropdown-item" href="{{ route('lockeramount.create', $value->id) }}">
                                        <span class="dropdown-icon-box text-danger"><i class="fa-solid fa-lock"></i></span>
                                        <span>Locker Amount</span>
                                    </a>
                                </li>
                                @endcan
                                @can('has-permission','Add Token Money')
                                <li>
                                    <a class="dropdown-item" href="{{ route('tokenAmount.create', $value->id) }}">
                                        <span class="dropdown-icon-box text-info"><i class="fa-solid fa-credit-card"></i></span>
                                        <span>Token Money</span>
                                    </a>
                                </li>
                                @endcan
                                @if($value->uuid)
                                <li>
                                    <a class="dropdown-item" href="{{ route('branch.qr.pdf', $value->uuid) }}" target="_blank">
                                        <span class="dropdown-icon-box text-danger"><i class="fa-solid fa-file-pdf"></i></span>
                                        <span>Download QR PDF</span>
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </div>
                    </div>

                    <!-- Branch Info Banner Box (Name in CAPS, Address & Contact/Email inside) -->
                    <div class="branch-info-banner">
                        <div class="branch-info-text">
                            <div class="branch-info-title">{{ strtoupper($value->display_name ?? $value->name) }}</div>
                            <div class="branch-info-address">
                                <i class="fa-solid fa-location-dot"></i>
                                <span>Address : {{ $value->library_address ?? 'Not updated yet' }}</span>
                            </div>

                            <!-- Contact & Email inside Library Box -->
                            <div class="branch-info-contact-row">
                                <div class="branch-info-contact-pill phone">
                                    <i class="fa-solid fa-phone"></i>
                                    <span>+91-{{ $value->mobile ?? '—' }}</span>
                                </div>
                                <div class="branch-info-contact-pill email">
                                    <i class="fa-solid fa-envelope"></i>
                                    <span class="text-truncate" title="{{ $value->email ?? '—' }}">{{ $value->email ?? '—' }}</span>
                                </div>
                            </div>
                        </div>
                        @if($value->uuid)
                        <div class="branch-qr-box">
                            {!! QrCode::size(70)->generate(route('qr.branch', $value->uuid)) !!}
                            <span class="branch-qr-label">Scan Branch QR</span>
                        </div>
                        @endif
                    </div>

                    <!-- 4-Box Stats Grid: Total Seats, Extend Days, Locker Price, Token Money -->
                    <div class="branch-stats-grid">
                        <div class="branch-stat-box stat-seats">
                            <div class="branch-stat-icon">
                                <i class="fa-solid fa-user-group"></i>
                            </div>
                            <div class="branch-stat-info">
                                <div class="branch-stat-label">Total Seats</div>
                                <div class="branch-stat-value">{{ $value->hour->seats ?? $value->seats ?? 0 }}</div>
                            </div>
                        </div>

                        <div class="branch-stat-box stat-extend">
                            <div class="branch-stat-icon">
                                <i class="fa-solid fa-calendar-days"></i>
                            </div>
                            <div class="branch-stat-info">
                                <div class="branch-stat-label">Extend Days</div>
                                <div class="branch-stat-value">{{ $value->extend_days ?? 0 }} Days</div>
                            </div>
                        </div>

                        <div class="branch-stat-box stat-locker">
                            <div class="branch-stat-icon">
                                <i class="fa-solid fa-lock"></i>
                            </div>
                            <div class="branch-stat-info">
                                <div class="branch-stat-label">Locker Price</div>
                                <div class="branch-stat-value">
                                    ₹{{ number_format($value->locker_amount ?? 0) }} <span class="stat-unit">/ mo</span>
                                </div>
                            </div>
                        </div>

                        <div class="branch-stat-box stat-token">
                            <div class="branch-stat-icon">
                                <i class="fa-solid fa-coins"></i>
                            </div>
                            <div class="branch-stat-info">
                                <div class="branch-stat-label">Token Money</div>
                                <div class="branch-stat-value">
                                    ₹{{ number_format($value->token_money ?? 0) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 1. Available Plans Box (Cool Indigo / Soft Blue Theme) -->
                    @if(isset($plans) && $plans->isNotEmpty())
                    <div class="branch-plans-card">
                        <button type="button" class="branch-card-collapse-header collapsed" data-bs-toggle="collapse" data-bs-target="#plansCollapse{{ $value->id }}" aria-expanded="false" aria-controls="plansCollapse{{ $value->id }}">
                            <div class="collapse-title">
                                <i class="fa-solid fa-layer-group"></i>
                                <span>Plan Details</span>
                                <span class="collapse-count">({{ $plans->count() }} {{ $plans->count() == 1 ? 'Plan' : 'Plans' }})</span>
                            </div>
                            <i class="fa-solid fa-chevron-down collapse-icon"></i>
                        </button>
                        <div class="collapse" id="plansCollapse{{ $value->id }}">
                            <div class="branch-plans-grid-2col">
                                @foreach($plans as $plan)
                                <div class="branch-plan-item">
                                    <div class="branch-plan-meta">
                                        <div class="branch-plan-name">{{ $plan->name }}</div>
                                        <div class="branch-plan-sub">
                                            {{ $plan->monthdays ? $plan->monthdays . ' Days' : ($plan->plan_id . ' ' . ucfirst(strtolower($plan->type))) }}
                                        </div>
                                    </div>
                                    <div class="branch-plan-badge">{{ $plan->type }}</div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- 2. Shift Details Box (Warm Amber Theme, Collapsible, Closed by default) -->
                    <div class="branch-shifts-card">
                        <button type="button" class="branch-card-collapse-header collapsed" data-bs-toggle="collapse" data-bs-target="#shiftsCollapse{{ $value->id }}" aria-expanded="false" aria-controls="shiftsCollapse{{ $value->id }}">
                            <div class="collapse-title">
                                <i class="fa-regular fa-clock"></i>
                                <span>Shift Details</span>
                                <span class="collapse-count">({{ $value->planTypes->count() }} {{ $value->planTypes->count() == 1 ? 'Shift' : 'Shifts' }})</span>
                            </div>
                            <i class="fa-solid fa-chevron-down collapse-icon"></i>
                        </button>
                        <div class="collapse" id="shiftsCollapse{{ $value->id }}">
                            <div class="branch-shifts-grid-2col">
                                @forelse($value->planTypes as $shift)
                                @php
                                    $start = !empty($shift->start_time) ? \Carbon\Carbon::parse($shift->start_time)->format('h:i A') : '—';
                                    $end = !empty($shift->end_time) ? \Carbon\Carbon::parse($shift->end_time)->format('h:i A') : '—';
                                    $price = $shift->price->price ?? 0;
                                @endphp
                                <div class="branch-shift-item">
                                    <div class="branch-shift-meta">
                                        <div class="branch-shift-name">{{ $shift->name }}</div>
                                        <div class="branch-shift-time">{{ $start }} – {{ $end }}</div>
                                    </div>
                                    <div class="branch-shift-price">₹{{ number_format($price) }}</div>
                                </div>
                                @empty
                                <div class="text-muted small py-2 text-center col-12">No shifts configured</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Action Buttons -->
                <div class="branch-card-footer">
                    <div class="branch-actions-left">
                        @if($value->uuid)
                        <a href="{{ route('branch.qr.pdf', $value->uuid) }}" class="btn-branch-outline" target="_blank" title="View / Print QR">
                            <i class="fa-solid fa-print me-1"></i> View / Print
                        </a>
                        @endif
                        <a href="{{ route('seats.history') }}" class="btn-branch-outline" title="History">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i> History
                        </a>
                        @can('has-permission','Add Library Seats')
                        <a href="{{ route('seat.create', $value->id) }}" class="btn-branch-outline" title="Add Seats">
                            <i class="fa-solid fa-user-plus me-1"></i> Add Seats
                        </a>
                        @endcan
                    </div>

                    <div class="branch-actions-right">
                        @can('has-permission','Edit Branch')
                        <a href="{{ route('branch.edit', $value->id) }}" class="btn-branch-edit" title="Edit Branch">
                            <i class="fa-solid fa-pen me-1"></i> Edit
                        </a>
                        @endcan
                        <a href="javascript:void(0)" class="btn-branch-delete delete-btn" data-id="{{ $value->id }}" data-table="Branch" title="Delete Branch">
                            <i class="fa-solid fa-trash me-1"></i> Delete
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@include('master.script')
@include('library.script')

@endsection