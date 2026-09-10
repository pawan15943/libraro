@extends(Auth::guard('library')->check() || Auth::guard('library_user')->check() ? 'layouts.library' : (Auth::guard('learner')->check() ? 'layouts.learner' : 'layouts.admin'))

@section('content')

{{-- Session Alerts --}}
@if (session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if (session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

{{-- Change Password Form Card --}}
<div class="row justify-content-center my-4">
    <div class="col-lg-6 col-md-8">
        <div class="card mb-4 shadow-sm border-0 rounded-3 p-4">
            <div class="card-body p-2">
                <form action="{{ url('change-password') }}" class="validateForm" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-lg-12">
                            <label class="form-label fw-semibold font-outfit" style="color: #18225f;">Current Password <span class="text-danger">*</span></label>
                            <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" placeholder="Enter Current Password" required>
                            @error('current_password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col-lg-12">
                            <label class="form-label fw-semibold font-outfit" style="color: #18225f;">New Password <span class="text-danger">*</span></label>
                            <input type="password" name="new_password" class="form-control @error('new_password') is-invalid @enderror" placeholder="Enter New Password" required>
                            @error('new_password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col-lg-12">
                            <label class="form-label fw-semibold font-outfit" style="color: #18225f;">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="new_password_confirmation" placeholder="Confirm New Password" required>
                            @error('new_password_confirmation')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col-lg-12 mt-4">
                            <button type="submit" class="btn btn-primary button w-100 py-2 font-outfit fw-bold">
                                <i class="fa-solid fa-check-circle me-1"></i> Update Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection