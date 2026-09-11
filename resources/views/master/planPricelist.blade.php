@extends('layouts.library')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}" />
<link rel="stylesheet" href="{{ asset('public/css/library-style.css') }}">

<!-- Main content -->
<div id="success-message" class="alert alert-success" style="display:none;"></div>
<div id="error-message" class="alert alert-danger" style="display:none;"></div>
@if($errors->any())
<div class="alert alert-danger">
    <ul class="m-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="plan-price-module">
    @if(collect($data)->isEmpty())
    <div class="no-data-found">
        <script
            src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js"
            type="module"></script>

        <dotlottie-wc
            src="https://lottie.host/5d973bf9-2f1d-4dd5-925f-86da95dbd7b1/t7dXaWIroC.lottie"
            style="width: 200px;height: 200px"
            autoplay
            loop></dotlottie-wc>

        @if(getCurrentBranch() !=0)
        <h4>You haven’t added any library Plan / Shift Price yet.</h4>
        <span> Start by creating your first Plan / Shift Price to manage it here.</span>
        @can('has-permission','Add Plan Price Master')
        <a href="{{ route('planPrice.create') }}" class="btn btn-primary button mt-3">
            <i class="fa-solid fa-plus me-1"></i> Add Plan Type / Shifts Price
        </a>
        @else
        <span class="text-danger d-block mt-2">You don't have Permission to add Plan Price</span>
        @endcan

        @else
        <h4>To add Plan Prices, first select your Branch.</h4>
        <span> Plan names remain the same across all branches, but prices can be different. That’s why you need to choose the branch before adding plan prices. (Choose Branch in Header Dropdown)</span>
        @endif
    </div>

    @else
    <!-- Masters -->
    @can('has-permission','Add Plan Price Master')
    <div class="heading-list justify-content-end mb-4">
        @if(getCurrentBranch() !=0)
        <a href="{{ route('planPrice.create') }}" class="btn btn-primary export m-0">
            <i class="fa-solid fa-plus "></i> Add Plan Price
        </a>
        @endif
    </div>
    @endcan

    <div class="row g-4 mb-4">
        @foreach($data as $key => $value)
        @php
            $isInactive = (bool) $value->deleted_at;
            $planDuration = $value->plan->name ?? '1 MONTH';
            $shiftName = $value->planType->name ?? '—';
            $priceFormatted = number_format((float)($value->price ?? 0));
        @endphp
        <div class="col-lg-4 col-md-6">
            <div class="plan-price-card">
                <div>
                    <!-- Card Header: Title & Status -->
                    <div class="plan-card-header">
                        <h4 class="plan-card-title">Plan {{ $key + 1 }} Price</h4>
                        @if($isInactive)
                        <span class="plan-status-badge inactive">
                            <span class="status-dot"></span> INACTIVE
                        </span>
                        @else
                        <span class="plan-status-badge active">
                            <span class="status-dot"></span> ACTIVE
                        </span>
                        @endif
                    </div>

                    <!-- Plan Duration Badge -->
                    <div class="plan-duration-badge mb-3">
                        <i class="fa-regular fa-calendar me-1.5" style="color: #18225f;"></i> {{ strtoupper($planDuration) }}
                    </div>

                    <!-- Shift Name Meta -->
                    <div class="plan-meta-section">
                        <div class="plan-meta-label">Shift Name</div>
                        <div class="plan-meta-value text-truncate" title="{{ $shiftName }}">{{ $shiftName }}</div>
                    </div>

                    <!-- Plan Price Meta -->
                    <div class="plan-price-section">
                        <div class="plan-price-label">Plan Price</div>
                        <div class="plan-price-amount">₹{{ $priceFormatted }}</div>
                    </div>
                </div>

                <!-- Bottom Action Buttons Row -->
                <div class="plan-card-actions">
                    @if($isInactive)
                    <a href="javascript:void(0)" class="btn-plan-action btn-action-activate active-deactive" data-id="{{ $value->id }}" data-table="PlanPrice" title="Activate Plan Price">
                        <i class="fa-solid fa-check me-1"></i> Activate
                    </a>
                    @else
                    <a href="javascript:void(0)" class="btn-plan-action btn-action-deactivate active-deactive" data-id="{{ $value->id }}" data-table="PlanPrice" title="Deactivate Plan Price">
                        <i class="fa-solid fa-check me-1"></i> Deactivate
                    </a>
                    @endif

                    <a href="{{ route('planPrice.create', $value->id) }}" class="btn-plan-action btn-action-edit" title="Edit Plan Price">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                    </a>

                    <a href="javascript:void(0)" class="btn-plan-action btn-action-delete delete-btn" data-id="{{ $value->id }}" data-table="PlanPrice" title="Delete Plan Price">
                        <i class="fa-solid fa-trash me-1"></i> Delete
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

<!-- /.content -->
@include('master.script')
@endsection