@extends('layouts.admin')
@section('content')
@if (session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

@if (session('error'))
<div class="alert alert-danger">
    {{ session('error') }}
</div>
@endif

<div class="card mb-4">
    <form action="{{ url('change-password') }}" class="validateForm" method="POST">
        @csrf
        <div class="row g-4">
            <div class="col-lg-12">
                <label for="">Current Password</label>
                <input type="password" name="current_password" class="form-control  @error('current_password') is-invalid @enderror" placeholder="Current Password" >
                @error('current_password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="col-lg-12">
                <label for="">New Password</label>
                <input type="password" name="new_password" class="form-control  @error('new_password') is-invalid @enderror" placeholder="New Password" >
                @error('new_password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="col-lg-12">
                <label for="">Confirm Password</label>
                <input type="password" class="form-control" name="new_password_confirmation" placeholder="Confirm New Password">
                @error('new_password_confirmation')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="col-lg-3">
                <button type="submit" class="btn btn-primary button">Change Password</button>
            </div>
        </div>
    </form>
</div>










@endsection