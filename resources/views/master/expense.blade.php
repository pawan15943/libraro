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


<div class="card card-default">
    <!-- Add Library User Form -->
    <div class="card-body">
        <form id="library_expense" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="id" value="{{ $expense->id ?? '' }}">
            
            <input type="hidden" name="databasemodel" value="Expense">
            <input type="hidden" name="redirect" value="{{ route('expense.index') }}">
            <div class="row g-3">
                <div class="col-lg-12">
                    <label for="class_name"> Expense Name<sup class="text-danger">*</sup> </label>
                    <input type="text" name="name"  class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $expense->name ?? '') }}">
                    @error('name')
                    <span class="invalid-feedback" role="alert">
                        {{ $message }}
                    </span>
                    @enderror
                </div>
                <div class="col-lg-12">
                    <button type="submit" class="btn btn-primary button"><i
                            class="fa fa-plus"></i>
                        Add Expense</button>
                </div>
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