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
            <b>Important :</b> You can add your library <em><b>Operating Hours</b></em> here that will show in booking form automatically.
            <br> <b>Note:</b> If you want to change the <em><b>Operating Hours</b></em>, you can edit it here.
        </p>
    </div>
</div>

<div class="card">
    <!-- Add Library User Form -->

    <form id="library_seat" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="branch_id" value="{{getCurrentBranch()}}">
        <input type="hidden" name="library_id" value="{{getLibraryId()}}">
        <input type="hidden" name="databasemodel" value="Hour">
        <input type="hidden" name="redirect" value="{{ route('branch.list') }}">
        <div class="row g-3">
            <div class="col-lg-12">
                <label for="">Operating Hours <span>*</span></label>
                <select class="form-select @error('hour') is-invalid @enderror" name="hour" id="hour">
                    <option value="">Select Hour</option>
                    <option value="16" {{ old('hour', isset($hour) ? $hour->hour : '') == 16 ? 'selected' : '' }}>16</option>
                    <option value="14" {{ old('hour', isset($hour) ? $hour->hour : '') == 14 ? 'selected' : '' }}>14</option>
                    <option value="12" {{ old('hour', isset($hour) ? $hour->hour : '') == 12 ? 'selected' : '' }}>12</option>
                    @can('has-permission','All Day')
                    <option value="24" {{ old('hour', isset($hour) ? $hour->hour : '') == 24 ? 'selected' : '' }}>24</option>
                    @endif
                </select>
                @error('hour')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="col-lg-2">
                <button class="btn btn-primary button" id="saveHourBtn"><i
                        class="fa fa-plus"></i>
                    Add Hours</button>
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