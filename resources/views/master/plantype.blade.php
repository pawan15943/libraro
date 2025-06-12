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
<!-- Masters -->


<div class="card">
    <!-- Add Library User Form -->
    <form id="planTypeForm" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="id" value="{{ $planType->id ?? '' }}">
        <input type="hidden" name="library_id" value="{{ getLibraryId() }}">
        <input type="hidden" name="databasemodel" value="PlanType">
        <input type="hidden" name="redirect" value="{{ route('plantype.index') }}">
    
        <div class="row g-4">
            <div class="col-lg-4">
                <label for="plantype_name">Plan Type Name <span>*</span></label>
                <select class="form-select @error('day_type_id') is-invalid @enderror" name="day_type_id" id="plantype_name">
                    <option value="">Select Plan Type</option>
                    @can('has-permission', 'Full Day')
                        <option value="1" {{ old('day_type_id', $planType->day_type_id ?? '') == 1 ? 'selected' : '' }}>Full Day</option>
                    @endcan
                    @can('has-permission', 'First Half')
                        <option value="2" {{ old('day_type_id', $planType->day_type_id ?? '') == 2 ? 'selected' : '' }}>First Half</option>
                    @endcan
                    @can('has-permission', 'Second Half')
                        <option value="3" {{ old('day_type_id', $planType->day_type_id ?? '') == 3 ? 'selected' : '' }}>Second Half</option>
                    @endcan
                    
                    @can('has-permission', 'All Day')
                        <option value="8" {{ old('day_type_id', $planType->day_type_id ?? '') == 8 ? 'selected' : '' }}>All Day</option>
                    @endcan
                    @can('has-permission', 'Full Night')
                        <option value="9" {{ old('day_type_id', $planType->day_type_id ?? '') == 9 ? 'selected' : '' }}>Full Night</option>
                    @endcan
                    <option value="0" {{ old('day_type_id', $planType->day_type_id ?? '') == 0 ? 'selected' : '' }}>Custom</option>
                </select>
                <div id="custom_plan_type_input" style="margin-top: 10px; {{ (old('day_type_id', $planType->day_type_id ?? '') == 0) ? 'display:block;' : 'display:none;' }}">
                    <label for="custom_plan_type">Custom Plan Type Name</label>
                    <input type="text" name="custom_plan_type" id="custom_plan_type" class="form-control char-only" placeholder="Enter custom plan type name" value="{{ old('custom_plan_type', $planType->custom_plan_type ?? '') }}">
                </div>
                @error('day_type_id')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-lg-4">
                <label for="start_time">Start Time <span>*</span></label>
                <input type="text" id="start_time" class="form-control @error('start_time') is-invalid @enderror" name="start_time" value="{{ old('start_time', $planType->start_time ?? '') }}" placeholder="Select start time">
                @error('start_time')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-lg-4">
                <label for="end_time">End Time <span>*</span></label>
                <input type="text" id="end_time" class="form-control @error('end_time') is-invalid @enderror" name="end_time" value="{{ old('end_time', $planType->end_time ?? '') }}" placeholder="Select end time">
                @error('end_time')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-lg-4">
                <label for="slot_hours">Slot Duration <span>*</span></label>
                <input type="text" id="slot_hours" class="form-control @error('slot_hours') is-invalid @enderror no-validate" name="slot_hours" readonly value="{{ old('slot_hours', $planType->slot_hours ?? '') }}" placeholder="Slot duration">
                @error('slot_hours')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-lg-4">
                <label for="seat_color">Select Seat Color <span>*</span></label>
                <select name="image" id="seat_color" class="form-select no-validate">
                    <option value="">Select Color</option>
                    <option value="orange" {{ (old('image', $planType->image ?? '') == 'orange') ? 'selected' : '' }}>Orange</option>
                    <option value="light_orange" {{ (old('image', $planType->image ?? '') == 'light_orange') ? 'selected' : '' }}>Light Orange</option>
                    <option value="green" {{ (old('image', $planType->image ?? '') == 'green') ? 'selected' : '' }}>Green</option>
                    <option value="blue" {{ (old('image', $planType->image ?? '') == 'blue') ? 'selected' : '' }}>Blue</option>
                </select>
            </div>

            
        </div>
        <div class="row mt-4">
            <div class="col-lg-3">
                <button type="submit" id="savePlanTypeBtn" class="btn btn-primary button">
                    <i class="fa fa-plus"></i> {{ isset($planType) ? 'Update Plan Type' : 'Add Plan Type' }}
                </button>
            </div>
        </div>
    </form>    
</div>





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