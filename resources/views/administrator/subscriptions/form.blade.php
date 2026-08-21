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

    .category-card {
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        background: #ffffff !important;
        margin-bottom: 1.25rem !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02) !important;
        overflow: hidden !important;
    }

    .category-header {
        background: #f8fafc !important;
        border-bottom: 1px solid #e2e8f0 !important;
        padding: 0.75rem 1.25rem !important;
    }

    .category-title {
        font-family: "Outfit", sans-serif !important;
        font-weight: 700 !important;
        color: #18225f !important;
        font-size: 0.95rem !important;
    }

    .perm-item-label {
        font-size: 0.86rem !important;
        font-family: "Mulish", sans-serif !important;
        color: #334155 !important;
        font-weight: 600 !important;
        cursor: pointer !important;
    }
</style>

<div class="container-fluid px-0 py-2">
    <!-- Top Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0" style="color: #18225f; font-family: 'Outfit', sans-serif;">
            {{ isset($subscription) ? 'Edit Subscription Plan & Category Permissions' : 'Create New Subscription Plan' }}
        </h4>
        <a href="{{ route('admin.subscriptions') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-bold">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Subscriptions List
        </a>
    </div>

    <div class="row">
        <div class="col-lg-11 mx-auto">
            <div class="form-card">
                <div class="form-card-header d-flex align-items-center justify-content-between">
                    <span><i class="fa-solid fa-box-open me-2"></i> Subscription Details & Category Permissions</span>
                </div>
                <div class="p-4">
                    <form action="{{ route('subscriptions.store', $subscription->id ?? null) }}" method="POST">
                        @csrf

                        <!-- Plan Basic Info -->
                        <div class="row g-3 mb-4">
                            <!-- Subscription Name -->
                            <div class="col-md-6">
                                <label for="name" class="form-label">Subscription Name <span class="text-danger">*</span></label>
                                <input 
                                    type="text" 
                                    id="name" 
                                    name="name" 
                                    class="form-control @error('name') is-invalid @enderror" 
                                    value="{{ old('name', $subscription->name ?? '') }}" 
                                    placeholder="e.g. Basic Plan, Pro Plan, Enterprise" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Monthly Price -->
                            <div class="col-md-6">
                                <label for="monthly_fees" class="form-label">Monthly Price (₹) <span class="text-danger">*</span></label>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    id="monthly_fees" 
                                    name="monthly_fees" 
                                    class="form-control @error('monthly_fees') is-invalid @enderror" 
                                    value="{{ old('monthly_fees', $subscription->monthly_fees ?? '') }}" 
                                    placeholder="e.g. 499.00" required>
                                @error('monthly_fees')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Pricing Tiers & Limits -->
                        <div class="row g-3 mb-4">
                            <!-- Yearly Price -->
                            <div class="col-md-4">
                                <label for="yearly_fees" class="form-label">Yearly Price (₹)</label>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    id="yearly_fees" 
                                    name="yearly_fees" 
                                    class="form-control" 
                                    value="{{ old('yearly_fees', $subscription->yearly_fees ?? '') }}" 
                                    placeholder="e.g. 4999.00">
                            </div>

                            <!-- Max Seats -->
                            <div class="col-md-4">
                                <label for="max_seats" class="form-label">Max Seats Limit</label>
                                <input 
                                    type="number" 
                                    id="max_seats" 
                                    name="max_seats" 
                                    class="form-control" 
                                    value="{{ old('max_seats', $subscription->max_seats ?? '') }}" 
                                    placeholder="Leave blank for Unlimited">
                            </div>

                            <!-- Max Branches -->
                            <div class="col-md-4">
                                <label for="max_branches" class="form-label">Max Branches Limit</label>
                                <input 
                                    type="number" 
                                    id="max_branches" 
                                    name="max_branches" 
                                    class="form-control" 
                                    value="{{ old('max_branches', $subscription->max_branches ?? '') }}" 
                                    placeholder="Leave blank for Unlimited">
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="plan_description" class="form-label">Plan Description / Features Summary</label>
                            <textarea id="plan_description" name="plan_description" class="form-control" rows="2" placeholder="Briefly describe what this subscription plan includes...">{{ old('plan_description', $subscription->plan_description ?? '') }}</textarea>
                        </div>

                        <hr class="my-4">

                        <!-- Permissions Matrix Header with Category Filter & Global Actions -->
                        <div class="bg-light rounded-3 p-3 mb-4 border d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h5 class="fw-bold mb-1" style="color: #18225f; font-family: 'Outfit', sans-serif;">
                                    <i class="fa-solid fa-layer-group me-2" style="color: #34939F;"></i> Permissions Categorized Matrix
                                </h5>
                                <small class="text-muted">Select feature permissions categorized by module to attach to this subscription plan.</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <input type="text" id="permSearchInput" class="form-control form-control-sm rounded-pill px-3" placeholder="Search permission..." style="width: 220px;">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" id="selectAllGlobal">Select All</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold" id="clearAllGlobal">Clear All</button>
                            </div>
                        </div>

                        <!-- Categorized Library Permissions Grid -->
                        <div class="row" id="categoriesContainer">
                            @foreach($categories as $cat)
                                @if($cat->permissions->count() > 0)
                                    <div class="col-md-6 category-wrapper" data-category="{{ strtolower($cat->name) }}">
                                        <div class="category-card">
                                            <!-- Category Header with Checkbox -->
                                            <div class="category-header d-flex justify-content-between align-items-center">
                                                <div class="form-check mb-0">
                                                    <input type="checkbox" class="form-check-input category-select-all" id="cat_cb_{{ $cat->id }}" data-cat-id="{{ $cat->id }}">
                                                    <label class="form-check-label category-title" for="cat_cb_{{ $cat->id }}">
                                                        <i class="fa-solid fa-folder-open me-1 text-primary"></i> {{ $cat->name }}
                                                    </label>
                                                </div>
                                                <span class="badge bg-primary text-white rounded-pill px-2 py-1" style="font-size: 11px;">
                                                    {{ $cat->permissions->count() }} Perms
                                                </span>
                                            </div>

                                            <!-- Category Permissions List -->
                                            <div class="p-3 bg-white overflow-auto" style="max-height: 220px;">
                                                <div class="row g-2">
                                                    @foreach($cat->permissions as $perm)
                                                        <div class="col-12 perm-item" data-perm-name="{{ strtolower($perm->name) }}">
                                                            <div class="form-check">
                                                                <input 
                                                                    class="form-check-input perm-checkbox cat-perm-{{ $cat->id }}" 
                                                                    type="checkbox" 
                                                                    name="permissions[]" 
                                                                    id="s_perm_{{ $perm->id }}" 
                                                                    value="{{ $perm->id }}" 
                                                                    {{ in_array($perm->id, $assignedPermissions ?? []) ? 'checked' : '' }}>
                                                                <label class="form-check-label perm-item-label" for="s_perm_{{ $perm->id }}">
                                                                    {{ $perm->name }}
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach

                            <!-- Uncategorized Permissions -->
                            @if(isset($uncategorized) && $uncategorized->count() > 0)
                                <div class="col-md-6 category-wrapper" data-category="uncategorized">
                                    <div class="category-card">
                                        <div class="category-header d-flex justify-content-between align-items-center">
                                            <div class="form-check mb-0">
                                                <input type="checkbox" class="form-check-input category-select-all" id="cat_cb_0" data-cat-id="0">
                                                <label class="form-check-label category-title text-secondary" for="cat_cb_0">
                                                    <i class="fa-solid fa-layer-group me-1"></i> General / Uncategorized
                                                </label>
                                            </div>
                                            <span class="badge bg-secondary text-white rounded-pill px-2 py-1" style="font-size: 11px;">
                                                {{ $uncategorized->count() }} Perms
                                            </span>
                                        </div>
                                        <div class="p-3 bg-white overflow-auto" style="max-height: 220px;">
                                            <div class="row g-2">
                                                @foreach($uncategorized as $perm)
                                                    <div class="col-12 perm-item" data-perm-name="{{ strtolower($perm->name) }}">
                                                        <div class="form-check">
                                                            <input 
                                                                class="form-check-input perm-checkbox cat-perm-0" 
                                                                type="checkbox" 
                                                                name="permissions[]" 
                                                                id="s_perm_{{ $perm->id }}" 
                                                                value="{{ $perm->id }}" 
                                                                {{ in_array($perm->id, $assignedPermissions ?? []) ? 'checked' : '' }}>
                                                            <label class="form-check-label perm-item-label" for="s_perm_{{ $perm->id }}">
                                                                {{ $perm->name }}
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <hr class="my-4">

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.subscriptions') }}" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-bold font-outfit">Cancel</a>
                            <button type="submit" class="btn btn-primary button px-4 py-2 rounded-pill fw-bold font-outfit">
                                <i class="fa-solid fa-save me-1"></i> {{ isset($subscription) ? 'Update Subscription Plan' : 'Create Subscription Plan' }}
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
        // Category Select All Checkbox Handler
        document.querySelectorAll('.category-select-all').forEach(catCb => {
            const catId = catCb.getAttribute('data-cat-id');
            const catPerms = document.querySelectorAll('.cat-perm-' + catId);

            // Update category header checkbox on initial load
            const updateCatCbState = () => {
                const allChecked = Array.from(catPerms).length > 0 && Array.from(catPerms).every(cb => cb.checked);
                catCb.checked = allChecked;
            };
            updateCatCbState();

            catCb.addEventListener('change', function() {
                catPerms.forEach(cb => cb.checked = catCb.checked);
            });

            catPerms.forEach(cb => {
                cb.addEventListener('change', updateCatCbState);
            });
        });

        // Global Select All & Clear All
        const selectAllGlobal = document.getElementById('selectAllGlobal');
        const clearAllGlobal = document.getElementById('clearAllGlobal');

        if (selectAllGlobal) {
            selectAllGlobal.addEventListener('click', function() {
                document.querySelectorAll('.perm-checkbox, .category-select-all').forEach(cb => cb.checked = true);
            });
        }

        if (clearAllGlobal) {
            clearAllGlobal.addEventListener('click', function() {
                document.querySelectorAll('.perm-checkbox, .category-select-all').forEach(cb => cb.checked = false);
            });
        }

        // Real-time Permission Search Filter
        const searchInput = document.getElementById('permSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = searchInput.value.toLowerCase().trim();
                document.querySelectorAll('.perm-item').forEach(item => {
                    const permName = item.getAttribute('data-perm-name');
                    if (permName.includes(query)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        }
    });
</script>

@endsection
