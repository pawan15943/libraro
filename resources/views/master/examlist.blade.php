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
@if(session('successCount'))
<div class="alert alert-success">
    {{ session('successCount') }} records imported successfully.
</div>
@endif





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
    <h4>You haven’t added any Govt. / Private Exam names yet.</h4>
    <span> Start by adding exams name to manage it here.</span>
    <!-- Masters -->
    @can('has-permission','Add Exam Master')
    <div class="heading-list justify-content-end mb-1">
        <a href="{{ route('exam.create') }}" class="btn btn-primary export">
            <i class="fa-solid fa-plus "></i> Add Exam
        </a>
    </div>
    @else
    <span class="text-danger">You don't have Permission to add Exams</span>
    @endcan
    
</div>
@else
<div class="plan-price-module">
    @can('has-permission','Add Exam Master')
    <div class="heading-list justify-content-end mb-4">
        <a href="{{ route('exam.create') }}" class="btn btn-primary export m-0">
            <i class="fa-solid fa-plus "></i> Add Exam
        </a>
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
                            <h4 class="plan-card-title">{{ $value->name }}</h4>
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

                        <!-- Pill Badge -->
                        <div class="plan-duration-badge mb-3">
                            <i class="fa-solid fa-graduation-cap me-1.5" style="color: #18225f;"></i> EXAM / COMPETITION
                        </div>

                        <!-- Metadata Grid -->
                        <div class="plan-meta-grid single-col">
                            <div class="plan-meta-item">
                                <div class="plan-meta-label">Exam Name</div>
                                <div class="plan-meta-value text-truncate" title="{{ $value->name }}">{{ $value->name }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Action Buttons Row -->
                    <div class="plan-card-actions">
                        @if($isInactive)
                        <a href="javascript:void(0)" class="btn-plan-action btn-action-activate active-deactive" data-id="{{ $value->id }}" data-table="Exam" title="Activate Exam">
                            <i class="fa-solid fa-check me-1"></i> Activate
                        </a>
                        @else
                        <a href="javascript:void(0)" class="btn-plan-action btn-action-deactivate active-deactive" data-id="{{ $value->id }}" data-table="Exam" title="Deactivate Exam">
                            <i class="fa-solid fa-check me-1"></i> Deactivate
                        </a>
                        @endif

                        <a href="{{ route('exam.create', $value->id) }}" class="btn-plan-action btn-action-edit" title="Edit Exam">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                        </a>

                        <a href="javascript:void(0)" class="btn-plan-action btn-action-delete delete-btn" data-id="{{ $value->id }}" data-table="Exam" title="Delete Exam">
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
<script>
    $(document).ready(function() {
        function toggleCustomInput() {
            if ($('#plantype_name').val() == '0') {
                $('#custom_plan_type_input').show();
            } else {
                $('#custom_plan_type_input').hide();
            }
        }

        toggleCustomInput(); // Call on page load
        $('#plantype_name').change(toggleCustomInput);
    });
</script>

<!-- /.content -->
@include('master.script')
@endsection