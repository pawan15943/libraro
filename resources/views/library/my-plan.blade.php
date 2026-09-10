@extends('layouts.library')

@section('content')

{{-- Dedicated Scoped Stylesheet for My Plan Module --}}
<link rel="stylesheet" href="{{ asset('public/css/my-plan.css') }}?v={{ time() }}" />

@php
    $planName = $plan ? $plan->name : ($month?->plan ?? 'Standard Plan');
    $planSlug = strtolower(str_replace(' ', '', $planName));
    $premiumSub = \App\Models\Subscription::where('id', 3)->first();
    $subscribedPermissions = ($data && $data->subscription) ? $data->subscription->permissions->pluck('name')->toArray() : [];
    $allPermissions = $premiumSub ? $premiumSub->permissions : [];
    $currentLibType = (int) ($data->library_type ?? 1);
    $daysLeft = isset($librarydiffInDays) ? (int)$librarydiffInDays : 0;
    $isPlanActive = ($month && $month->status == 1) || (optional(getLibrary())->status == 1 && optional(getLibrary())->is_paid == 1);
@endphp

<div class="my-plan-module">

    {{-- Main 2-Column Grid --}}
    <div class="my-plan-grid">

        {{-- Left Column: Active Plan Details Card --}}
        <div class="plan-detail-card">
            <div>
                {{-- Plan Card Header --}}
                <div class="plan-card-top">
                    <div>
                        @if(str_contains($planSlug, 'basic'))
                            <span class="plan-tier-badge tier-basic">
                                <i class="fa-solid fa-bolt"></i> Basic Plan
                            </span>
                        @elseif(str_contains($planSlug, 'standard'))
                            <span class="plan-tier-badge tier-standard">
                                <i class="fa-solid fa-star"></i> Standard Plan
                            </span>
                        @elseif(str_contains($planSlug, 'premium'))
                            <span class="plan-tier-badge tier-premium">
                                <i class="fa-solid fa-crown"></i> Premium Plan
                            </span>
                        @else
                            <span class="plan-tier-badge tier-standard">
                                <i class="fa-solid fa-layer-group"></i> {{ $planName }}
                            </span>
                        @endif
                    </div>

                    <div>
                        @if($isPlanActive)
                            <span class="plan-status-chip active">
                                <i class="fa-solid fa-circle text-success" style="font-size: 8px;"></i> Active Subscription
                            </span>
                        @else
                            <span class="plan-status-chip inactive">
                                <i class="fa-solid fa-circle-exclamation text-danger"></i> Plan Inactive
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Price & Validity Banner --}}
                <div class="price-banner-box">
                    <div class="price-left">
                        <p class="price-label-text">Subscription Investment</p>
                        <h2 class="price-main-display">
                            <span class="price-amount-val">₹{{ number_format($month->amount ?? 0, 2) }}</span>
                            <span class="price-cycle-text">
                                @if($month && $month->month == 12)
                                    / Yearly
                                @elseif($month && $month->month)
                                    / {{ $month->month }} Month(s)
                                @else
                                    / Subscription
                                @endif
                            </span>
                        </h2>
                    </div>

                    <div>
                        <span class="validity-pill">
                            <i class="fa-solid fa-shield-check"></i>
                            @if($daysLeft > 0)
                                {{ $daysLeft }} Days Left
                            @else
                                Plan Expired
                            @endif
                        </span>
                    </div>
                </div>

                {{-- Plan Specs Grid --}}
                <div class="plan-info-grid">
                    <div class="info-spec-item">
                        <p class="spec-label">
                            <i class="fa-solid fa-calendar-plus text-primary"></i> Subscription Start Date
                        </p>
                        <h5 class="spec-value">
                            {{ $month?->start_date ? \Carbon\Carbon::parse($month->start_date)->format('d M Y') : 'N/A' }}
                        </h5>
                    </div>

                    <div class="info-spec-item">
                        <p class="spec-label">
                            <i class="fa-solid fa-calendar-xmark text-danger"></i> Subscription End Date
                        </p>
                        <h5 class="spec-value">
                            {{ $month?->end_date ? \Carbon\Carbon::parse($month->end_date)->format('d M Y') : 'N/A' }}
                        </h5>
                    </div>

                    <div class="info-spec-item">
                        <p class="spec-label">
                            <i class="fa-solid fa-rotate text-info"></i> Billing Cycle Type
                        </p>
                        <h5 class="spec-value">
                            @if($month && $month->month == 1)
                                Monthly Billing
                            @elseif($month && $month->month == 12)
                                1 Year (Annual Billing)
                            @elseif($month && $month->month)
                                {{ $month->month }} Months
                            @else
                                Subscription
                            @endif
                        </h5>
                    </div>

                    <div class="info-spec-item">
                        <p class="spec-label">
                            <i class="fa-solid fa-bell text-warning"></i> Next Renewal Date
                        </p>
                        <h5 class="spec-value">
                            {{ $month?->end_date ? \Carbon\Carbon::parse($month->end_date)->format('d M Y') : 'N/A' }}
                        </h5>
                    </div>
                </div>
            </div>

            {{-- Action Buttons & Notice --}}
            <div>
                <div class="plan-action-row">
                    @if(isset($librarydiffInDays) && $librarydiffInDays <= 5)
                        @if($currentLibType < 3)
                        <a href="{{ route('subscriptions.choosePlan', ['action' => 'upgrade']) }}" class="btn-upgrade">
                            <i class="fa-solid fa-arrow-up-right-dots"></i> Upgrade Plan
                        </a>
                        @endif

                        <a href="{{ route('subscriptions.choosePlan', ['action' => 'renew']) }}" class="btn-renew">
                            <i class="fa-solid fa-arrows-rotate"></i> Renew Plan
                        </a>
                    @endif

                    <a href="{{ route('library.transaction') }}" class="btn-invoices">
                        <i class="fa-solid fa-receipt"></i> View Invoices & Receipts
                    </a>
                </div>

                @if(!isset($librarydiffInDays) || $librarydiffInDays > 5)
                <div class="notice-banner">
                    <i class="fa-solid fa-circle-info fs-5"></i>
                    <span>Plan upgrade and renewal actions will be automatically unlocked <b>5 days before expiration</b>.</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Right Column: Features & Permissions Checklist Card --}}
        <div class="plan-features-card">
            <div class="features-header">
                <div>
                    <h5 class="features-header-title">
                        <i class="fa-solid fa-list-check"></i> Plan Inclusions & Features
                    </h5>
                </div>
                <div>
                    <span class="features-count-chip">
                        {{ count($subscribedPermissions) }} Active
                    </span>
                </div>
            </div>

            <div class="features-list-scroll">
                @if(!empty($allPermissions) && count($allPermissions) > 0)
                    @foreach($allPermissions as $permission)
                        @php
                            $isSubscribed = in_array($permission->name, $subscribedPermissions);
                        @endphp
                        <div class="feature-item {{ $isSubscribed ? 'included' : 'not-included' }}">
                            <div class="feature-left">
                                @if($isSubscribed)
                                    <i class="fa-solid fa-circle-check feature-icon check"></i>
                                @else
                                    <i class="fa-solid fa-circle-xmark feature-icon cross"></i>
                                @endif
                                <span class="feature-name">{{ $permission->name }}</span>
                            </div>

                            <div>
                                @if($isSubscribed)
                                    <span class="feature-status-tag active-tag">Included</span>
                                @else
                                    <span class="feature-status-tag upgrade-tag">Upgrade</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-4 text-muted">
                        <p class="mb-0">No permissions found.</p>
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>

@endsection