@extends('layouts.library')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}" />
<!-- Main content -->

<div id="success-message" class="alert alert-success" style="display:none;"></div>
<div id="error-message" class="alert alert-danger" style="display:none;"></div>

@if($errors->any())
<div class="alert alert-danger">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

@if($data->isEmpty())
<div class="no-data-found">
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>

    <dotlottie-wc
        src="https://lottie.host/5d973bf9-2f1d-4dd5-925f-86da95dbd7b1/t7dXaWIroC.lottie"
        style="width: 200px;height: 200px"
        autoplay
        loop></dotlottie-wc>

    @if(getCurrentBranch() != 0)
        
        <h4>You haven’t added any Floor.</h4>
        <span>Start by creating your first Floor to manage it here.</span>
        @can('has-permission','Add Floor Master')
        <a href="{{ route('floor.create') }}" class="btn btn-primary export">
            <i class="fa-solid fa-plus"></i> Add Floor
        </a>
        @else
        <span class="text-danger">You don't have Permission to add Floor</span>
        @endcan
    @else
        <h4>To add Floor, first select your Branch.</h4>
        <span>Floors are branch-specific. Please choose a branch before adding Floor. (Choose Branch in Header Dropdown)</span>
    @endif
</div>
@else

<div class="plan-price-module">
    @can('has-permission','Add Floor Master')
    <div class="heading-list justify-content-end mb-4">
        @if(getCurrentBranch() != 0)
        <a href="{{ route('floor.create') }}" class="btn btn-primary export m-0">
            <i class="fa-solid fa-plus "></i> Add Floor
        </a>
        @endif
    </div>
    @endcan

    <div class="row g-4 mb-4">
        @foreach($data as $key => $value)
        @php
            $isInactive = (bool) $value->deleted_at;
        @endphp
        <div class="col-lg-4 col-md-6">
            <div class="plan-price-card">
                <div class="plan-card-body">
                    <!-- Card Header: Title & Status -->
                    <div class="plan-card-header">
                        <h4 class="plan-card-title">Floor {{ $value->floor_no }}</h4>
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

                    <!-- Total Seats Pill Badge -->
                    <div class="plan-duration-badge mb-3">
                        <i class="fa-solid fa-layer-group me-1.5" style="color: #18225f;"></i> {{ $value->total_seats }} SEATS CAPACITY
                    </div>

                    <!-- Metadata Grid -->
                    <div class="plan-meta-grid">
                        <div class="plan-meta-item">
                            <div class="plan-meta-label">Floor Name</div>
                            <div class="plan-meta-value text-truncate" title="{{ $value->name ?? '—' }}">{{ $value->name ?? '—' }}</div>
                        </div>
                        <div class="plan-meta-item">
                            <div class="plan-meta-label">Total Seats</div>
                            <div class="plan-meta-value">{{ $value->total_seats }}</div>
                        </div>
                        <div class="plan-meta-item">
                            <div class="plan-meta-label">From Seat</div>
                            <div class="plan-meta-value">{{ $value->from_seat }}</div>
                        </div>
                        <div class="plan-meta-item">
                            <div class="plan-meta-label">To Seat</div>
                            <div class="plan-meta-value">{{ $value->to_seat }}</div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Action Buttons Row -->
                <div class="plan-card-actions">
                    @if($isInactive)
                    <a href="javascript:void(0)" class="btn-plan-action btn-action-activate active-deactive" data-id="{{ $value->id }}" data-table="Floor" title="Activate Floor">
                        <i class="fa-solid fa-check me-1"></i> Activate
                    </a>
                    @else
                    <a href="javascript:void(0)" class="btn-plan-action btn-action-deactivate active-deactive" data-id="{{ $value->id }}" data-table="Floor" title="Deactivate Floor">
                        <i class="fa-solid fa-check me-1"></i> Deactivate
                    </a>
                    @endif

                    <a href="{{ route('floor.create', $value->id) }}" class="btn-plan-action btn-action-edit" title="Edit Floor">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                    </a>

                    <a href="javascript:void(0)" class="btn-plan-action btn-action-delete delete-btn" data-id="{{ $value->id }}" data-table="Floor" title="Delete Floor">
                        <i class="fa-solid fa-trash me-1"></i> Delete
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js"></script>

@include('master.script')
@endsection
