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
        font-size: 0.92rem !important;
        text-transform: capitalize !important;
        border-bottom: 1px solid #cbd5e1 !important;
        padding-bottom: 0.4rem !important;
        margin-bottom: 0.65rem !important;
    }
</style>

<div class="container-fluid px-0 py-2">
    <!-- Top Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0" style="color: #18225f; font-family: 'Outfit', sans-serif;">
            {{ isset($user) ? 'Edit Admin User Account' : 'Add New Admin User Account' }}
        </h4>
        <a href="{{ route('admin.users') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-bold">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Users List
        </a>
    </div>

    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="form-card">
                <div class="form-card-header d-flex align-items-center justify-content-between">
                    <span><i class="fa-solid fa-user-gear me-2"></i> Admin User Setup & Direct Menu Access</span>
                </div>
                <div class="p-4">
                    <form action="{{ route('admin.users.store', $user->id ?? null) }}" method="POST">
                        @csrf

                        <div class="row g-3 mb-4">
                            <!-- Full Name -->
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input 
                                    type="text" 
                                    id="name" 
                                    name="name" 
                                    class="form-control @error('name') is-invalid @enderror" 
                                    value="{{ old('name', $user->name ?? '') }}" 
                                    placeholder="Enter full name..." required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Email Address -->
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    class="form-control @error('email') is-invalid @enderror" 
                                    value="{{ old('email', $user->email ?? '') }}" 
                                    placeholder="name@example.com" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <!-- Role Selection -->
                            <div class="col-md-6">
                                <label for="role" class="form-label">Assign System Role</label>
                                <select name="role" id="role" class="form-select @error('role') is-invalid @enderror">
                                    <option value="">-- Select Web Role --</option>
                                    @foreach($roles as $r)
                                        <option value="{{ $r->name }}" {{ (old('role', $userRole ?? '') == $r->name) ? 'selected' : '' }}>
                                            {{ $r->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Password -->
                            <div class="col-md-6">
                                <label for="password" class="form-label">
                                    Password {{ isset($user) ? '(Leave blank to keep unchanged)' : '*' }}
                                </label>
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    class="form-control @error('password') is-invalid @enderror" 
                                    placeholder="{{ isset($user) ? '••••••••' : 'Enter password (min 6 chars)' }}" 
                                    {{ isset($user) ? '' : 'required' }}>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Direct Web Menu Permissions (admin_user_permissions Table) -->
                        <h5 class="fw-bold mb-3" style="color: #18225f; font-family: 'Outfit', sans-serif;">
                            <i class="fa-solid fa-key me-2" style="color: #34939F;"></i> Direct Menu Permissions (Show/Hide Admin Menus)
                        </h5>
                        <p class="text-muted small mb-3">Permissions checked here are saved directly to <code>admin_user_permissions</code> table to control admin menu visibility for this user.</p>

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
                                                            id="u_perm_{{ $perm->id }}" 
                                                            value="{{ $perm->name }}" 
                                                            {{ in_array($perm->name, $userPermissions ?? []) ? 'checked' : '' }}>
                                                        <label class="form-check-label text-dark fw-semibold" for="u_perm_{{ $perm->id }}" style="font-size: 0.88rem; font-family: 'Mulish', sans-serif;">
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
                                    No web menu permissions found.
                                </div>
                            @endforelse
                        </div>

                        <hr class="my-4">

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.users') }}" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-bold font-outfit">Cancel</a>
                            <button type="submit" class="btn btn-primary button px-4 py-2 rounded-pill fw-bold font-outfit">
                                <i class="fa-solid fa-save me-1"></i> {{ isset($user) ? 'Update Admin Account & Access' : 'Create Admin Account' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
