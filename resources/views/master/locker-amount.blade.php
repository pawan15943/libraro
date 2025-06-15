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
            <b>Important :</b> You can add your library <em><b>Locker Amount</b></em> here that will show in booking form automatically.
            <br> <b>Note:</b> If you want to change the <em><b>Locker Amount</b></em>, you can edit it here.
        </p>
    </div>
</div>


<div class="card">

    <form id="extend_hour" class="validateForm" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="id" value="{{$locker_amount->id}}">
        <input type="hidden" name="library_id" value="{{getLibraryId()}}">
        <input type="hidden" name="databasemodel" value="Branch">
        <input type="hidden" name="redirect" value="{{ route('branch.list') }}">
        <div class="row g-3">
            <div class="col-lg-12">
                <label for="">Library Locker Amount <span>*</span></label>
                <input type="text" name="locker_amount" class="form-control @error('locker_amount') is-invalid @enderror" id="" placeholder="Enter Amt." value="{{ old('locker_amount', $locker_amount->locker_amount ?? '') }}">
                @error('locker_amount')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="col-lg-2">
                <button type="submit" class="btn btn-primary button"><i
                        class="fa fa-plus"></i>
                    Add Amount</button>
            </div>

    </form>
</div>


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