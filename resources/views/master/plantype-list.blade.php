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

<!-- Masters -->

<div class="heading-list justify-content-end">
    <a href="{{ route('planType.create') }}" class="btn btn-primary export">
        <i class="fa-solid fa-plus "></i> Add Plan Type
    </a>

</div>
@if($data->isEmpty())
  <p class="not-found info-message">
<span class="close-btn" onclick="this.parentElement.style.display='none';">&times;</span>

There is currently no Data available </p>  
@else
<div class="row g-4 mt-4">
    @foreach($data as $key => $value)
        <div class="col-lg-4 col-md-6">
            <div class="planBox">
                <div class="heading d-flex justify-content-between align-items-center">
                    <h4>Shift {{ $key + 1 }}</h4>
                    @if($value->deleted_at)
                        <span class="inactive text-danger">Inactive</span>
                    @else
                        <span class="active">Active</span>
                    @endif
                </div>

                <div class="plan border-top mt-2">
                    <ul>
                        <li>
                            <span>Shift Name</span>
                            <p class="m-0">{{ $value->name }}</p>
                        </li>
                        <li>
                            <span>Shift Hrs</span>
                            <p class="m-0">{{ $value->slot_hours }}</p>
                        </li>
                        <li>
                            <span>Start Time</span>
                            <p class="m-0">{{ \Carbon\Carbon::parse($value->start_time)->format('h:i A') }}</p>
                        </li>
                        <li>
                            <span>End Time</span>
                            <p class="m-0">{{ \Carbon\Carbon::parse($value->end_time)->format('h:i A') }}</p>
                        </li>
                    </ul>
                </div>

                <ul class="actionalbles mt-3">
                    <li>
                        <a href="javascript:void(0)" 
                           class="active-deactive" 
                           data-id="{{ $value->id }}" 
                           data-table="PlanType" 
                           title="Active/Deactive">
                            @if($value->deleted_at)
                                <i class="fas fa-ban"></i>
                            @else
                                <i class="fa fa-check"></i>
                            @endif
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('planType.create', $value->id) }}" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" 
                           class="delete-btn" 
                           data-id="{{ $value->id }}" 
                           data-table="PlanType" 
                           title="Delete">
                            <i class="fa fa-trash"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    @endforeach
</div>
@endif


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js"></script>


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