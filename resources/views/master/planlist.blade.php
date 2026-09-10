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
@if(session('error'))
<div class="alert alert-danger">
    {{ session('error') }}
</div>
@endif



<!-- Masters -->

@if($data->isEmpty())

<div class="no-data-found">
    <script
        src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js"
        type="module"></script>

    <dotlottie-wc
        src="https://lottie.host/5d973bf9-2f1d-4dd5-925f-86da95dbd7b1/t7dXaWIroC.lottie"
        style="width: 200px;height: 200px"
        autoplay
        loop></dotlottie-wc>
    <h4>You haven’t added any Plans yet.</h4>
    <span> Start by creating your first Plan to manage it here.</span>
    @can('has-permission','Add Plan Master')
    <div class="heading-list justify-content-end mb-4">
        <a href="{{ route('plan.create') }}" class="btn btn-primary export m-0">
            <i class="fa-solid fa-plus "></i> Add Plan
        </a>
    </div>
    @else
    <span class="text-danger">You don't have Permission to add Plan</span>
    @endcan

</div>

@else
<div class="plan-price-module">
    @can('has-permission','Add Plan Master')
    <div class="heading-list justify-content-end mb-4">
        <a href="{{ route('plan.create') }}" class="btn btn-primary export m-0">
            <i class="fa-solid fa-plus "></i> Add Plan
        </a>
    </div>
    @endcan

    <div class="row g-4 mb-4">
        @foreach($data as $key => $value)
        @php
            $isInactive = (bool) $value->deleted_at;
            $hasActiveLearners = ($value->active_learners_count ?? 0) > 0;
        @endphp
        <div class="col-lg-4 col-md-6">
            <div class="plan-price-card">
                <div class="plan-card-body">
                    <!-- Card Header: Title & Status -->
                    <div class="plan-card-header">
                        <h4 class="plan-card-title">Plan {{ $key + 1 }}</h4>
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

                    <!-- Plan Duration Pill Badge -->
                    <div class="plan-duration-badge mb-3">
                        <i class="fa-regular fa-calendar me-1.5" style="color: #18225f;"></i> {{ strtoupper($value->name) }}
                    </div>

                    <!-- Metadata Grid -->
                    <div class="plan-meta-grid">
                        <div class="plan-meta-item">
                            <div class="plan-meta-label">Plan Name</div>
                            <div class="plan-meta-value text-truncate" title="{{ $value->name }}">{{ $value->name }}</div>
                        </div>
                        <div class="plan-meta-item">
                            <div class="plan-meta-label">Plan Duration</div>
                            <div class="plan-meta-value">{{ $value->plan_id }} {{ $value->type }}</div>
                        </div>
                        <div class="plan-meta-item">
                            <div class="plan-meta-label">Plan Days</div>
                            <div class="plan-meta-value">{{ $value->monthdays ? ($value->monthdays . ' Days') : 'Calendar-wise' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Action Buttons Row -->
                @if($hasActiveLearners)
                <div class="text-center py-2 text-danger small font-outfit fw-bold mt-auto border-top pt-2 plan-locked-notice">
                    <i class="fa-solid fa-users me-1"></i> Active learners assigned
                </div>
                @else
                <div class="plan-card-actions">
                    @if($isInactive)
                    <a href="javascript:void(0)" class="btn-plan-action btn-action-activate active-deactive" data-id="{{ $value->id }}" data-table="Plan" title="Activate Plan">
                        <i class="fa-solid fa-check me-1"></i> Activate
                    </a>
                    @else
                    <a href="javascript:void(0)" class="btn-plan-action btn-action-deactivate active-deactive" data-id="{{ $value->id }}" data-table="Plan" title="Deactivate Plan">
                        <i class="fa-solid fa-check me-1"></i> Deactivate
                    </a>
                    @endif

                    <a href="{{ route('plan.create', $value->id) }}" class="btn-plan-action btn-action-edit" title="Edit Plan">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                    </a>

                    <a href="javascript:void(0)" class="btn-plan-action btn-action-delete delete-btn" data-id="{{ $value->id }}" data-route="{{ route('master.delete', $value->id) }}" data-table="Plan" title="Delete Plan">
                        <i class="fa-solid fa-trash me-1"></i> Delete
                    </a>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif





<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js"></script>

<script>
    (function($) {
        $(window).on("load", function() {
            $(".contents").mCustomScrollbar({
                theme: "dark",
                scrollInertia: 300,
                axis: "y",
                autoHideScrollbar: false, // Keeps
            });
        });
    })(jQuery);
</script>


<!-- /.content -->
@include('master.script')
@endsection
