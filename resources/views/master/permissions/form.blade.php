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
            {{ isset($permission) ? 'Edit Portal Permission' : 'Add New Portal Permission' }}
        </h4>
        <a href="{{ route('permissions') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-bold">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Permissions List
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="form-card">
                <div class="form-card-header d-flex align-items-center justify-content-between">
                    <span><i class="fa-solid fa-key me-2"></i> Permission Details</span>
                </div>
                <div class="p-4">
                    <form action="{{ route('permissions.store', $permission->id ?? null) }}" method="POST">
                        @csrf

                        <div class="row g-3 mb-3">
                            <!-- Permission Category Select -->
                            <div class="col-md-6">
                                <label for="category" class="form-label">Permission Category <span class="text-danger">*</span></label>
                                <select name="permission_category_id" id="category" class="form-select @error('permission_category_id') is-invalid @enderror" required>
                                    <option value="">-- Select Category --</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ (old('permission_category_id', $permission->permission_category_id ?? '') == $cat->id) ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('permission_category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Guard Select -->
                            <div class="col-md-6">
                                <label for="guard_name" class="form-label">Target Guard <span class="text-danger">*</span></label>
                                <select name="guard_name" id="guard_name" class="form-select" required>
                                    <option value="library" {{ old('guard_name', $permission->guard_name ?? 'library') == 'library' ? 'selected' : '' }}>Library Guard (Portal & Subscriptions)</option>
                                    <option value="web" {{ old('guard_name', $permission->guard_name ?? '') == 'web' ? 'selected' : '' }}>Web Guard (Admin Panel)</option>
                                    <option value="learner" {{ old('guard_name', $permission->guard_name ?? '') == 'learner' ? 'selected' : '' }}>Learner Guard</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <!-- Permission Name -->
                            <div class="col-md-6">
                                <label for="permission" class="form-label">Permission Name <span class="text-danger">*</span></label>
                                <input 
                                    type="text" 
                                    name="name" 
                                    id="permission" 
                                    class="form-control @error('name') is-invalid @enderror" 
                                    value="{{ old('name', $permission->name ?? '') }}" 
                                    placeholder="e.g. View Financial Snapshot" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Permission Slug -->
                            <div class="col-md-6">
                                <label for="slug" class="form-label">Permission Slug Key</label>
                                <input 
                                    type="text" 
                                    name="slug" 
                                    id="slug" 
                                    readonly 
                                    class="form-control bg-light" 
                                    value="{{ old('slug', $permission->slug ?? '') }}" 
                                    placeholder="auto-generated-slug">
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <input 
                                type="text" 
                                name="description" 
                                id="description" 
                                class="form-control" 
                                value="{{ old('description', $permission->description ?? '') }}" 
                                placeholder="Describe what feature this permission unlocks...">
                        </div>

                        <hr class="my-4">

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('permissions') }}" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-bold font-outfit">Cancel</a>
                            <button type="submit" class="btn btn-primary button px-4 py-2 rounded-pill fw-bold font-outfit">
                                <i class="fa-solid fa-save me-1"></i> {{ isset($permission) ? 'Update Permission' : 'Save Permission' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const permInput = document.getElementById('permission');
        const slugInput = document.getElementById('slug');

        if (permInput && slugInput) {
            permInput.addEventListener('input', function() {
                const slug = permInput.value
                    .toLowerCase()
                    .replace(/ /g, '-')
                    .replace(/[^\w-]+/g, '');
                slugInput.value = slug;
            });
        }
    });
</script>

@endsection
