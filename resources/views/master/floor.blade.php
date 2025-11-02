@extends('layouts.library')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}" />
<!-- Main content -->



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
            <b>Important :</b> Here you can @if(isset($plans)) Add @else Edit @endif Floor for your library.
            
        </p>
    </div>
    <div class="col-lg-12">
        <h4 style="font-size: 1rem !important; margin-bottom:1.5rem;">Total Seat : {{$total_seats}} | Used Seats : {{$total_floor}}| Remaining Seats : {{$total_seats-$total_floor}}</h4>
    </div>
</div>
<div class="card">
<form id="floorForm" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="id" value="{{ $floor->id ?? '' }}">
   
    <input type="hidden" name="branch_id" value="{{ getCurrentBranch() }}">
    <input type="hidden" name="databasemodel" value="Floor">
    <input type="hidden" name="redirect" value="{{ route('floor.index') }}">

    <div class="row g-3 mb-3">
        <div class="col-lg-2">
            <label>Floor No <span>*</span></label>
            <input type="text" name="floor_no" class="form-control numeric-only" value="{{ old('floor_no', $floor->floor_no ?? '') }}" required>
            <span class="text text-danger">Ex : 1 | 2 etc.</span>
        </div>

        <div class="col-lg-4">
            <label>Floor Name  <span>*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $floor->name ?? '') }}">
            <span class="text text-danger">Ex : Floor 1, Floor I, First Floor etc.</span>
        </div>

        <div class="col-lg-2">
            <label>From Seat <span>*</span></label>
            <input type="text" name="from_seat" class="form-control numeric-only seat-from" value="{{ old('from_seat', $floor->from_seat ?? '') }}" required>
        </div>

        <div class="col-lg-2">
            <label>To Seat <span>*</span></label>
            <input type="text" name="to_seat" class="form-control numeric-only seat-to" value="{{ old('to_seat', $floor->to_seat ?? '') }}" required>
        </div>

        <div class="col-lg-2">
            <label>Total Seats </label>
            <input type="text" name="total_seats" class="form-control total-seats" value="{{ old('total_seats', $floor->total_seats ?? '') }}" readonly>
        </div>
    </div>
    <div class="row mt-4"> 
        <div class="col-lg-3"> 
            <button type="submit" id="saveFloorBtn" class="btn btn-primary button"> 
                <i class="fa fa-save"></i> 
                {{ isset($floor) ? 'Update Floor' : 'Save Floor(s)' }} 
            </button> 
        </div> 
    </div>
   
</form>
</div>


{{-- Scripts --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<script>
$(document).ready(function() {
    // Numeric only inputs
    $(document).on('input', '.numeric-only', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Auto-calc total seats
    $(document).on('input', '.seat-from, .seat-to', function () {
        let from = parseInt($('input[name="from_seat"]').val() || 0, 10);
        let to = parseInt($('input[name="to_seat"]').val() || 0, 10);

        if (from > 0 && to > 0 && to >= from) {
            $('input[name="total_seats"]').val(to - from + 1);
        } else {
            $('input[name="total_seats"]').val('');
        }
    });
});
</script>
@include('master.script')
@endsection
