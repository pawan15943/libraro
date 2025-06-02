@extends('layouts.admin')

@section('content')

<!-- Breadcrumb -->
<div class="row">
    <div class="d-flex bradcrumb">
        <h4>Add Library Plan</h4>
        
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
        <form action="{{ route('library.store') }}" class="validateForm" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row g-4">
                <!-- Library Name -->
                <input type="hidden" value="12345678" name="password">
                <div class="col-lg-12">
                    <div class="form-group">
                        <label>Library Name<sup class="text-danger">*</sup></label>
                        <input type="text" class="form-control char-only @error('library_name') is-invalid @enderror" name="library_name"
                            value="{{ old('library_name') }}">
                        @error('library_name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>

                <!-- Library Email -->
                <div class="col-lg-6">
                    <div class="form-group">
                        <label>Library Email Id<sup class="text-danger">*</sup></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                            value="{{ old('email') }}">
                        @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>

                <!-- Library Mobile -->
                <div class="col-lg-6">
                    <div class="form-group">
                        <label>Library Contact No.<sup class="text-danger">*</sup></label>
                        <input type="text" class="form-control digit-only @error('library_mobile') is-invalid @enderror" name="library_mobile" maxlength="10"
                            value="{{ old('library_mobile') }}">
                        @error('library_mobile')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>

              
                <!-- Address -->
                <div class="col-lg-12">
                    <div class="form-group">
                        <label>Address</label>
                        <textarea class="form-control h-auto @error('library_address') is-invalid @enderror no-validate" name="library_address"
                            >{{ old('library_address') }}</textarea>
                        @error('library_address')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>

                <!-- State -->
                
                <h6 class="mt-5">Library Owner Info :</h6>
                <div class="row g-4">
        
                    <div class="col-lg-12">
                        <label for="">Owner Name </label>
                        <input type="text" class="form-control char-only @error('library_owner') is-invalid @enderror no-validate" name="library_owner" value="{{ old('library_owner') }}">
                        @error('library_owner')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-lg-6">
                        <label for="">Owner Email Id </label>
                        <input type="email" class="form-control @error('library_owner_email') is-invalid @enderror no-validate" name="library_owner_email" value="{{ old('library_owner_email') }}">
                        @error('library_owner_email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-lg-6">
                        <label for="">Owner Contact Number (WhatsApp) </label>
                        <input type="text" class="form-control digit-only @error('library_owner_contact') is-invalid @enderror no-validate" name="library_owner_contact" maxlength="10" value="{{ old('library_owner_contact') }}">
                        @error('library_owner_contact')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <input type="hidden" name="terms" value="1">
               
            </div>

            <div class="row mt-3">
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-block button">
                        {{ isset($library) ? 'Update Library' : 'Add Library' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


@include('library.script')

@endsection