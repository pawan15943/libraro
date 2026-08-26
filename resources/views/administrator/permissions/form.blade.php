@extends('layouts.admin')

@section('content')

<style>
    .form-card {
        background: #ffffff !important;
        border-radius: 16px !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 4px 20px rgba(24, 34, 95, 0.05) !important;
        overflow: hidden !important;
    }

    .form-card-header {
        background: #18225f !important;
        color: #ffffff !important;
        padding: 1rem 1.5rem !important;
        font-family: "Outfit", sans-serif !important;
        font-weight: 700 !important;
        font-size: 1.05rem !important;
    }

    .form-label {
        font-family: "Outfit", sans-serif !important;
        font-weight: 700 !important;
        color: #334155 !important;
        font-size: 0.83rem !important;
        margin-bottom: 0.35rem !important;
    }

    .form-control, .form-select {
        font-family: "Mulish", sans-serif !important;
        border-radius: 8px !important;
        border: 1px solid #cbd5e1 !important;
        padding: 0.6rem 0.9rem !important;
        font-size: 0.9rem !important;
    }

    .form-control:focus, .form-select:focus {
        border-color: #34939F !important;
        box-shadow: 0 0 0 0.25rem rgba(52, 147, 159, 0.18) !important;
    }
</style>

<div class="container-fluid px-0 py-2">
    <!-- Top Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0" style="color: #18225f; font-family: 'Outfit', sans-serif;">
            {{ isset($permission) ? 'Edit Admin Permission' : 'Add New Admin Permission' }}
        </h4>
        <a href="{{ route('admin-permissions') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-bold">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Permissions List
        </a>
    </div>

    <div class="row">
        <div class="col-lg-6 mx-auto">
            <div class="form-card">
                <div class="form-card-header d-flex align-items-center justify-content-between">
                    <span><i class="fa-solid fa-key me-2"></i> Admin Permission Key Config</span>
                </div>
                <div class="p-4">
                    <form action="{{ route('admin-permissions.store', $permission->id ?? null) }}" method="POST">
                        @csrf

                        <!-- Permission Name Key -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Permission Name / Key <span class="text-danger">*</span></label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                class="form-control @error('name') is-invalid @enderror" 
                                value="{{ old('name', $permission->name ?? '') }}" 
                                placeholder="e.g. view-blogs, edit-blogs, manage-users" required>
                            <small class="text-muted" style="font-size: 11px;">Formatted automatically as lowercase hyphenated key (e.g. manage-blogs).</small>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr class="my-4">

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin-permissions') }}" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-bold font-outfit">Cancel</a>
                            <button type="submit" class="btn btn-primary button px-4 py-2 rounded-pill fw-bold font-outfit">
                                <i class="fa-solid fa-save me-1"></i> {{ isset($permission) ? 'Update Admin Permission' : 'Save Admin Permission' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
