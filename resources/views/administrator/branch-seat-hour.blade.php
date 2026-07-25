@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="{{ asset('public/css/library-style.css') }}">

<div class="actions mb-4">
    <div class="upper-box">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-3">{{ $branch->display_name ?? $branch->name }} — Seats & Operating Hours</h4>
            <a href="javascript:void(0);" class="go-back" onclick="window.history.back();">Go Back <i class="fa-solid fa-backward pl-2"></i></a>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger">
    <ul class="m-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card p-3">
    @if(!$hourRow)
    <p class="text-danger m-0">No operating-hour record exists yet for this branch. It's normally created when the branch is first set up.</p>
    @else
    <form method="POST" action="{{ route('library.branch.seatHour.save', $branch->id) }}">
        @csrf
        <div class="row g-4">
            <div class="col-lg-6">
                <label>Total Seats <span>*</span></label>
                <input type="number" min="1" class="form-control" name="seats" value="{{ old('seats', $hourRow->seats) }}" required>
                <small class="text-muted">Note: seat count can only be increased, not decreased, once set.</small>
            </div>
            <div class="col-lg-6">
                <label>Operating Hours <span>*</span></label>
                <select class="form-select" name="hour" required>
                    <option value="">Select Hour</option>
                    @for($h = 10; $h <= 24; $h++)
                    <option value="{{ $h }}" {{ old('hour', $hourRow->hour) == $h ? 'selected' : '' }}>{{ $h }}</option>
                    @endfor
                </select>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
    @endif
</div>
@endsection
