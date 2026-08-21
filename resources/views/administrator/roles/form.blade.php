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

    .permission-group-card {
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        background: #f8fafc !important;
        margin-bottom: 1rem !important;
        padding: 1rem !important;
    }

    .permission-group-title {
        font-family: "Outfit", sans-serif !important;
        font-weight: 700 !important;
        color: #18225f !important;
        font-size: 0.95rem !important;
        text-transform: capitalize !important;
        border-bottom: 1px solid #cbd5e1 !important;
        padding-bottom: 0.5rem !important;
        margin-bottom: 0.75rem !important;
    }
</style>

<div class="container-fluid px-0 py-2">
    <!-- Top Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0" style="color: #18225f; font-family: 'Outfit', sans-serif;">
            {{ isset($role) ? 'Edit Role & Web Access' : 'Create New Web Role' }}
        </h4>
        <a href="{{ route('admin.roles') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-bold">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Roles List
        </a>
    </div>

    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="form-card">
                <div class="form-card-header d-flex align-items-center justify-content-between">
                    <span><i class="fa-solid fa-shield-halved me-2"></i> Web Role Setup & Permissions Matrix</span>
                </div>
                <div class="p-4">
                    <form action="{{ route('admin.roles.store', $role->id ?? null) }}" method="POST">
                        @csrf

                        <!-- Role Name -->
                        <div class="mb-4">
                            <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                class="form-control form-control-lg @error('name') is-invalid @enderror" 
                                value="{{ old('name', $role->name ?? '') }}" 
                                placeholder="e.g. Editor, Content Manager, Administrator" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr class="my-4">

                        <h5 class="fw-bold mb-3" style="color: #18225f; font-family: 'Outfit', sans-serif;">
                            <i class="fa-solid fa-lock me-2" style="color: #34939F;"></i> Web Module Permissions (Show/Hide Administrator Options)
                        </h5>

                        <!-- Permissions Checkboxes Grouped by Module (Web Guard Only) -->
                        <div class="row">
                            @forelse($permissions as $group => $groupPerms)
                                <div class="col-md-6">
                                    <div class="permission-group-card">
                                        <div class="permission-group-title d-flex justify-content-between align-items-center">
                                            <span><i class="fa-solid fa-folder me-1 text-primary"></i> {{ ucfirst($group) }} Module</span>
                                        </div>
                                        <div class="row g-2">
                                            @foreach($groupPerms as $perm)
                                                <div class="col-12">
                                                    <div class="form-check">
                                                        <input 
                                                            class="form-check-input" 
                                                            type="checkbox" 
                                                            name="permissions[]" 
                                                            id="perm_{{ $perm->id }}" 
                                                            value="{{ $perm->name }}" 
                                                            {{ in_array($perm->name, $rolePermissions ?? []) ? 'checked' : '' }}>
                                                        <label class="form-check-label text-dark fw-semibold" for="perm_{{ $perm->id }}" style="font-size: 0.88rem; font-family: 'Mulish', sans-serif;">
                                                            {{ $perm->name }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-center text-muted py-4">
                                    No web permissions found. Create permissions in the Permissions tab to map them here.
                                </div>
                            @endforelse
                        </div>

                        <hr class="my-4">

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.roles') }}" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-bold font-outfit">Cancel</a>
                            <button type="submit" class="btn btn-primary button px-4 py-2 rounded-pill fw-bold font-outfit">
                                <i class="fa-solid fa-save me-1"></i> {{ isset($role) ? 'Update Role & Access' : 'Create Web Role' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
