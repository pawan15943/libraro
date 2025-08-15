@extends('layouts.library')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}" />
<!-- Main content -->



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
            <b>Important :</b> You can add your library <em><b>Token Money</b></em> here that will show in booking form automatically.
            <br> <b>Note:</b> If you want to change the <em><b>Token Money</b></em>, you can edit it here.
        </p>
    </div>
</div>


<div class="card">

    <form id="token_money" class="validateForm" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="id" value="{{$token_amount->id}}">
        <input type="hidden" name="library_id" value="{{getLibraryId()}}">
        <input type="hidden" name="databasemodel" value="Branch">
        <input type="hidden" name="redirect" value="{{ route('branch.list') }}">
        <div class="row g-3">
            <div class="col-lg-12">
                <label for="">Library Token Money <span>*</span></label>
                <input type="text" name="token_money" class="form-control digit-only @error('token_money') is-invalid @enderror" id="" placeholder="Enter Amt." value="{{ old('token_money', $token_amount->token_money ?? '') }}">
                @error('token_money')
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
        </div>
    </form>
</div>


<!-- /.content -->
@include('master.script')
@endsection