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
@if(session('successCount'))
<div class="alert alert-success">
    {{ session('successCount') }} records imported successfully.
</div>
@endif
<!-- Masters -->

<div class="row">
    <div class="col-lg-12">
        <p class="info-message">
            <span class="close-btn" onclick="this.parentElement.style.display='none';">×</span>
            <b>Important :</b> Here you can @if(isset($plans)) Add @else Edit @endif Plan Type / Shifts for your library.
        </p>
    </div>
</div>
<div class="card">
<form id="floorForm" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="id" value="{{ $floor->id ?? '0' }}">
    <input type="hidden" name="library_id" value="{{ getLibraryId() }}">
    <input type="hidden" name="branch_id" value="{{ getCurrentBranch() }}">
    <input type="hidden" name="databasemodel" value="Floor">
    <input type="hidden" name="redirect" value="{{ route('floor.index') }}">

    <div class="row g-4">
        <div class="col-12 mb-2 d-flex justify-content-between align-items-center">
            <h5 class="m-0">Floors</h5>
            <button type="button" id="addFloorRow" class="btn btn-sm btn-success">
                <i class="fa fa-plus"></i> Add Row
            </button>
        </div>
    </div>

    <div id="floorRows">
        {{-- If editing a single floor, prefill --}}
        @if(isset($floor))
            <div class="floor-row row g-3 mb-3">
                <div class="col-lg-2">
                    <label>Floor No <span>*</span></label>
                    <input type="text" name="floor_no[]" class="form-control numeric-only" value="{{ old('floor_no.0', $floor->floor_no) }}" required>
                </div>

                <div class="col-lg-4">
                    <label>Floor Name</label>
                    <input type="text" name="name[]" class="form-control" value="{{ old('name.0', $floor->name) }}">
                </div>

                <div class="col-lg-2">
                    <label>From Seat <span>*</span></label>
                    <input type="text" name="from_seat[]" class="form-control numeric-only seat-from" value="{{ old('from_seat.0', $floor->from_seat) }}" required>
                </div>

                <div class="col-lg-2">
                    <label>To Seat <span>*</span></label>
                    <input type="text" name="to_seat[]" class="form-control numeric-only seat-to" value="{{ old('to_seat.0', $floor->to_seat) }}" required>
                </div>

                <div class="col-lg-1">
                    <label>Total Seats</label>
                    <input type="text" name="total_seats[]" class="form-control total-seats" value="{{ old('total_seats.0', $floor->total_seats) }}" readonly>
                </div>

                <div class="col-lg-1 d-flex align-items-end">
                    <button type="button" class="btn btn-danger remove-row" @if(!isset($floor)) style="display:none;" @endif>
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        @else
            {{-- Default initial row for add --}}
            <div class="floor-row row g-3 mb-3">
                <div class="col-lg-2">
                    <label>Floor No <span>*</span></label>
                    <input type="text" name="floor_no[]" class="form-control numeric-only" value="{{ old('floor_no.0', '') }}" required>
                </div>

                <div class="col-lg-4">
                    <label>Floor Name</label>
                    <input type="text" name="name[]" class="form-control" value="{{ old('name.0', '') }}">
                </div>

                <div class="col-lg-2">
                    <label>From Seat <span>*</span></label>
                    <input type="text" name="from_seat[]" class="form-control numeric-only seat-from" value="{{ old('from_seat.0', '') }}" required>
                </div>

                <div class="col-lg-2">
                    <label>To Seat <span>*</span></label>
                    <input type="text" name="to_seat[]" class="form-control numeric-only seat-to" value="{{ old('to_seat.0', '') }}" required>
                </div>

                <div class="col-lg-1">
                    <label>Total Seats</label>
                    <input type="text" name="total_seats[]" class="form-control total-seats" value="{{ old('total_seats.0', '') }}" readonly>
                </div>

                <div class="col-lg-1 d-flex align-items-end">
                    <button type="button" class="btn btn-danger remove-row" style="display:none;">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        @endif
    </div>

    <div class="row mt-4">
        <div class="col-lg-3">
            <button type="submit" id="saveFloorBtn" class="btn btn-primary button">
                <i class="fa fa-save"></i> {{ isset($floor) ? 'Update Floor' : 'Save Floor(s)' }}
            </button>
        </div>
    </div>
</form>
</div>

{{-- Template for cloning new rows (kept hidden) --}}
<template id="floorRowTemplate">
    <div class="floor-row row g-3 mb-3">
        <div class="col-lg-2">
            <label>Floor No <span>*</span></label>
            <input type="text" name="floor_no[]" class="form-control numeric-only" required>
        </div>

        <div class="col-lg-4">
            <label>Floor Name</label>
            <input type="text" name="name[]" class="form-control">
        </div>

        <div class="col-lg-2">
            <label>From Seat <span>*</span></label>
            <input type="text" name="from_seat[]" class="form-control numeric-only seat-from" required>
        </div>

        <div class="col-lg-2">
            <label>To Seat <span>*</span></label>
            <input type="text" name="to_seat[]" class="form-control numeric-only seat-to" required>
        </div>

        <div class="col-lg-1">
            <label>Total Seats</label>
            <input type="text" name="total_seats[]" class="form-control total-seats" readonly>
        </div>

        <div class="col-lg-1 d-flex align-items-end">
            <button type="button" class="btn btn-danger remove-row">
                <i class="fa fa-trash"></i>
            </button>
        </div>
    </div>
</template>

{{-- Scripts --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<script>
    $(document).ready(function () {
        // Add new row
        $('#addFloorRow').on('click', function () {
            let tpl = $('#floorRowTemplate').prop('content').cloneNode(true);
            $('#floorRows').append(tpl);
            toggleRemoveButtons();
        });

        // Remove row
        $(document).on('click', '.remove-row', function () {
            $(this).closest('.floor-row').remove();
            toggleRemoveButtons();
        });

        // Show/hide remove buttons depending on number of rows
       function toggleRemoveButtons() {
            let rows = $('#floorRows .floor-row');
            rows.find('.remove-row').toggle(rows.length > 1); // hide/show remove buttons
        }

        toggleRemoveButtons();

        // Numeric only inputs
        $(document).on('input', '.numeric-only', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Auto-calc total seats when from/to change
        $(document).on('input', '.seat-from, .seat-to', function () {
            let $row = $(this).closest('.floor-row');
            let from = parseInt($row.find('.seat-from').val() || 0, 10);
            let to = parseInt($row.find('.seat-to').val() || 0, 10);

            if (from > 0 && to > 0 && to >= from) {
                $row.find('.total-seats').val((to - from + 1));
            } else {
                $row.find('.total-seats').val('');
            }
        });

   
    });
</script>
@include('master.script')
@endsection
