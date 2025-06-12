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


<div class="card">     
    <form id="planPriceForm" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="id" value="{{ $planPrice->id ?? '' }}">
        <input type="hidden" name="library_id" value="{{getLibraryId()}}">
        <input type="hidden" name="branch_id" value="{{getCurrentBranch()}}">
        <input type="hidden" name="redirect" value="{{ route('planPrice.index') }}">
        <input type="hidden" name="databasemodel" value="PlanPrice">
        <div class="row g-4">
            <div class="col-lg-4">
                
                <label for="">Plan Name <span>*</span></label>
                <select name="plan_id" id="price_plan_id" class="form-select @error('plan_id') is-invalid @enderror event">
                    <option value="">Select Plan</option>
                    @foreach ($plans as $value)
                    @if($value->plan_id==1)
                    
                    
                    <option value="{{ $value->id }}" {{ isset($planPrice) && $planPrice->plan_id == $value->id ? 'selected' : '' }}>
                        {{ $value->name }}
                    </option>
                        @endif
                    @endforeach
                </select>
                @error('plan_id')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="col-lg-4">
                <label> Plan Type<sup class="text-danger">*</sup></label>
                <select name="plan_type_id" id="plan_type_id" class="form-select @error('plan_type_id') is-invalid @enderror event">
                    <option value="">Select Plan Type</option>
                    @foreach($plantypes as $planType)
                    <option value="{{ $planType->id }}" {{ isset($planPrice) && $planPrice->plan_type_id == $planType->id ? 'selected' : '' }}>
                        {{ $planType->name }}
                    </option>
                    @endforeach

                </select>
                @error('plan_type_id')
                <span class="invalid-feedback" role="alert">
                    {{ $message }}
                </span>
                @enderror
            </div>
            <div class="col-lg-4">
                <label for="">Plan Price <span>*</span></label>
                <input type="text" name="price" class="form-control digit-only @error('price') is-invalid @enderror" id="price" placeholder="Enter Price" value="{{ old('price', isset($planPrice) ? $planPrice->price : '') }}">
                @error('price')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="col-lg-3">
                <button type="submit" class="btn btn-primary button"><i
                        class="fa fa-plus"></i>
                    Add Plan Price</button>
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


<!-- /.content -->
@include('master.script')
@endsection