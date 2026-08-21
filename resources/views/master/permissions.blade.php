@extends('layouts.admin')

@section('content')

<style>
    .table th {
        font-size: 13px !important;
        font-weight: 700 !important;
        color: #18225f !important;
        background-color: #f8fafc !important;
        border-bottom: 2px solid #e2e8f0 !important;
        padding: 0.75rem 1rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }

    .table td {
        font-size: 0.9rem !important;
        vertical-align: middle !important;
        padding: 0.85rem 1rem !important;
        color: #334155 !important;
    }

    .actionalbls {
        list-style: none !important;
        margin: 0 !important;
        padding: 0 !important;
        display: flex !important;
        gap: 8px !important;
        align-items: center !important;
    }

    .actionalbls li a, .actionalbls li button {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 32px !important;
        height: 32px !important;
        border-radius: 8px !important;
        background: #f1f5f9 !important;
        color: #18225f !important;
        transition: all 0.2s ease !important;
        text-decoration: none !important;
        border: none !important;
    }

    .actionalbls li a:hover, .actionalbls li button:hover {
        background: #18225f !important;
        color: #ffffff !important;
    }

    .actionalbls li button.btn-delete:hover, .actionalbls li a.btn-delete:hover {
        background: #dc3545 !important;
        color: #ffffff !important;
    }

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
        padding: 0.9rem 1.25rem !important;
        font-family: "Outfit", sans-serif !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
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

<div class="container-fluid px-0">
    <!-- Header with Title -->
    <div class="heading-list py-4 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 fw-bold" style="color: #18225f; font-family: 'Outfit', sans-serif;">Portal Permissions & Category Management</h4>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @elseif(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show rounded-3 mb-4" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @elseif(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
            <i class="fa-solid fa-circle-xmark me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <!-- Add / Edit Permission Category Card (Left 4 Cols) -->
        <div class="col-lg-4">
            <div class="form-card mb-4">
                <div class="form-card-header d-flex align-items-center justify-content-between">
                    <span><i class="fa-solid fa-folder-plus me-2"></i> {{ isset($category) ? 'Edit Category' : 'Add Permission Category' }}</span>
                </div>
                <div class="p-3">
                    <form action="{{ route('permission-categories.storeOrUpdate', $category->id ?? null) }}" method="POST">
                        @csrf
                        @method('put')

                        <div class="mb-3">
                            <label for="category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input 
                                type="text" 
                                name="name" 
                                id="category_name" 
                                class="form-control" 
                                value="{{ old('name', $category->name ?? '') }}" 
                                placeholder="e.g. Finance, Reports, Attendance" required>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary button w-100 py-2 rounded-3 font-outfit fw-bold">
                                <i class="fa-solid fa-save me-1"></i> {{ isset($category) ? 'Update Category' : 'Add Category' }}
                            </button>
                            @if(isset($category))
                                <a href="{{ route('permissions') }}" class="btn btn-outline-secondary py-2 rounded-3 font-outfit fw-bold">Cancel</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <!-- Categories List Table -->
            <div class="bg-white rounded-4 border shadow-sm overflow-hidden">
                <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark" style="font-family: 'Outfit', sans-serif;">
                        <i class="fa-solid fa-folder me-1 text-primary"></i> Categories ({{ $categories->count() }})
                    </span>
                </div>
                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Category Name</th>
                                <th style="width: 90px;" class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $index => $cat)
                                <tr>
                                    <td class="fw-bold text-muted">{{ $index + 1 }}</td>
                                    <td class="fw-bold text-dark" style="font-family: 'Outfit', sans-serif;">{{ $cat->name }}</td>
                                    <td>
                                        <ul class="actionalbls justify-content-end">
                                            <li>
                                                <a href="{{ route('permissions', ['permissionId' => null, 'categoryId' => $cat->id]) }}" title="Edit Category">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </li>
                                            <li>
                                                <form action="{{ route('permission-categories.delete', $cat->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-delete" title="Delete Category" onclick="return confirm('Are you sure you want to delete this permission category?');">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-muted">No categories created yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add / Edit Portal Permission Form (Right 8 Cols) -->
        <div class="col-lg-8">
            <div class="form-card mb-4">
                <div class="form-card-header d-flex align-items-center justify-content-between">
                    <span><i class="fa-solid fa-key me-2"></i> {{ isset($permission) ? 'Edit Portal Permission' : 'Add Portal Permission' }}</span>
                </div>
                <div class="p-4">
                    <form action="{{ route('permissions.storeOrUpdate', $permission->id ?? null) }}" method="POST">
                        @csrf
                        @method('put')

                        <div class="row g-3 mb-3">
                            <!-- Category Select -->
                            <div class="col-md-6">
                                <label for="category" class="form-label">Permission Category <span class="text-danger">*</span></label>
                                <select name="permission_category_id" id="category" class="form-select" required>
                                    <option value="">-- Select Category --</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ (old('permission_category_id', $permission->permission_category_id ?? '') == $cat->id) ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Guard Select -->
                            <div class="col-md-6">
                                <label for="guard_name" class="form-label">Target Guard <span class="text-danger">*</span></label>
                                <select name="guard_name" id="guard_name" class="form-select" required>
                                    <option value="library" {{ old('guard_name', $permission->guard_name ?? 'library') == 'library' ? 'selected' : '' }}>Library Guard (Subscriptions & Portal)</option>
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
                                    class="form-control" 
                                    value="{{ old('name', $permission->name ?? '') }}" 
                                    placeholder="e.g. View Payment Collection" required>
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
                                    value="{{ old('slug', $permission->slug ?? $permission->name ?? '') }}" placeholder="auto-generated-slug">
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

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-end gap-2">
                            @if(isset($permission))
                                <a href="{{ route('permissions') }}" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-bold font-outfit">Cancel</a>
                            @endif
                            <button type="submit" class="btn btn-primary button px-4 py-2 rounded-pill fw-bold font-outfit">
                                <i class="fa-solid fa-save me-1"></i> {{ isset($permission) ? 'Update Permission' : 'Save Permission' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Permissions List Table Categorized & Searchable -->
    <div class="bg-white rounded-4 border shadow-sm overflow-hidden mb-4">
        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="fw-bold text-dark" style="font-family: 'Outfit', sans-serif;">
                <i class="fa-solid fa-list me-1 text-primary"></i> All System Permissions ({{ $permissions->count() }})
            </span>

            <!-- Filter Input -->
            <input type="text" id="permTableSearch" class="form-control form-control-sm rounded-pill px-3" placeholder="Filter permissions..." style="width: 220px;">
        </div>

        @php
            $groupedPermissions = $permissions->groupBy('permission_category_id');
            $serial = 1;
        @endphp

        <div class="table-responsive">
            <table class="table table-hover mb-0" id="permissionsMainTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Permission Name & Description</th>
                        <th>Guard</th>
                        <th>Slug Key</th>
                        <th style="width: 120px;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groupedPermissions as $catId => $catPerms)
                        @php
                            $catObj = $catId ? $categories->firstWhere('id', $catId) : null;
                        @endphp
                        <tr class="bg-light border-top border-bottom table-category-row">
                            <td colspan="5" class="fw-bold text-primary py-2 px-3" style="font-family: 'Outfit', sans-serif; background-color: #f1f5f9;">
                                <i class="fa-solid fa-folder-open me-2 text-primary"></i> {{ $catObj ? $catObj->name : 'Uncategorized' }} 
                                <span class="badge bg-secondary text-white rounded-pill ms-2" style="font-size: 10px;">{{ $catPerms->count() }} Perms</span>
                            </td>
                        </tr>

                        @foreach($catPerms as $p)
                            <tr class="perm-table-row" data-name="{{ strtolower($p->name . ' ' . $p->description . ' ' . $p->guard_name) }}">
                                <td class="fw-bold text-muted">{{ $serial++ }}</td>
                                <td>
                                    <div class="fw-bold text-dark" style="font-family: 'Outfit', sans-serif;">{{ $p->name }}</div>
                                    @if($p->description)
                                        <small class="text-muted d-block" style="font-size: 11px;">{{ $p->description }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-3 py-1 rounded-pill" style="font-family: monospace;">{{ $p->guard_name }}</span>
                                </td>
                                <td>
                                    <code class="text-secondary small">{{ $p->slug ?? Str::slug($p->name) }}</code>
                                </td>
                                <td>
                                    <ul class="actionalbls justify-content-end">
                                        <li>
                                            <a href="{{ route('permissions', ['permissionId' => $p->id]) }}" title="Edit Permission">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </li>
                                        <li>
                                            <form action="{{ route('permissions.delete', $p->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-delete" title="Delete Permission" onclick="return confirm('Are you sure you want to delete this permission?');">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-key fa-2x mb-3 text-secondary d-block"></i>
                                No permissions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-generate Slug on input
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

        // Table search filter
        const tableSearch = document.getElementById('permTableSearch');
        if (tableSearch) {
            tableSearch.addEventListener('input', function() {
                const query = tableSearch.value.toLowerCase().trim();
                document.querySelectorAll('.perm-table-row').forEach(row => {
                    const dataName = row.getAttribute('data-name');
                    if (dataName.includes(query)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    });
</script>

@endsection