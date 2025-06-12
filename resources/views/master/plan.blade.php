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
<div class="row">
    <div class="col-lg-12">
        <p class="info-message">
            <span class="close-btn" onclick="this.parentElement.style.display='none';">×</span>
            <b>Important :</b> Here you can create library plan like Monthly, weeekly and daywise.
        </p>
    </div>
</div>
<div class="card">

    <!-- Add Library User Form -->
    <form id="planForm" class="">
        @csrf

        <input type="hidden" name="id" value="{{ $plan->id ?? '' }}">
        <input type="hidden" name="library_id" value="{{getLibraryId()}}">
        <input type="hidden" name="databasemodel" value="Plan">
        <input type="hidden" name="redirect" value="{{ route('plan.index') }}">
        <div class="row g-4">
            <div class="col-lg-6">
                <label for="">Type <span>*</span></label>
                <select class="form-select @error('type') is-invalid @enderror" name="type" id="type">
                    <option value="">Select Type</option>
                    <option value="MONTH" {{ old('type', isset($plan) ? $plan->type : '') == 'MONTH' ? 'selected' : '' }}>MONTH</option>
                    <option value="YEAR" {{ old('type', isset($plan) ? $plan->type : '') == 'YEAR' ? 'selected' : '' }}>YEAR</option>
                    <option value="DAY" {{ old('type', isset($plan) ? $plan->type : '') == 'DAY' ? 'selected' : '' }}>DAY</option>
                    <option value="WEEK" {{ old('type', isset($plan) ? $plan->type : '') == 'WEEK' ? 'selected' : '' }}>WEEK</option>

                </select>
                @error('type')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror

            </div>
            <div class="col-lg-6">
                <label for="">Plan (Accept only digits)<span>*</span></label>
                <input type="text" class="form-control digit-only @error('plan_id') is-invalid @enderror" name="plan_id" id="plan_id" value="{{ old('plan_id', $plan->plan_id ?? '') }}" placeholder="Ex : 1 for 1 Month & 2 for 2 Month">
                @error('plan_id')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="col-lg-3">
                <button type="submit" class="btn btn-primary button" id="savePlanBtn"><i class="fa fa-plus"></i>
                    @if(isset($plan)) Edit Plan @else Add Plan @endif
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
                theme: "dark"
                , scrollInertia: 300
                , axis: "y"
                , autoHideScrollbar: false, // Keeps
            });
        });
    })(jQuery);

</script>


<!-- /.content -->
@include('master.script')
@endsection
