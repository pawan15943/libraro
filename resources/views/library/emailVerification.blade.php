@extends('layouts.library')

@section('content')

<!-- Breadcrumb -->
<div class="row">
    <div class="d-flex bradcrumb">
        <h4>Verify Email id via OTP</h4>
        
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Library Plan</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card">
    <div class="row">
        <form method="POST" action="{{ route('library.verify.otp') }}" class="validateForm">
            @csrf
            <div class="row g-4">
                <div class="col-lg-12">
                    <label for="">Enter OTP <span>*</span></label>
                    <input type="hidden" name="email" value="{{ session('library_email') ?? session('email') }}">
                    <input type="hidden" name="user_type" value="admin">
                    <input type="text" class="form-control  @error('email_otp') is-invalid @enderror" name="email_otp" maxlength="10"
                        value="{{ old('email_otp') }}" placeholder="Enter OTP">
                    @error('email_otp')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror

                </div>
                <div class="col-lg-12">
                    <button type="submit" class="btn btn-primary button">Verify OTP</button>
                </div>
            </div>
        </form>
    </div>
</div>


@include('library.script')

@endsection