@extends('layouts.admin')

@section('content')

<div class="row">
    <div class="col-lg-12">
        <!-- Heading List matching Library List UI -->
        <div class="heading-list py-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <h4 class="mb-0 fw-bold" style="color: #18225f; font-family: 'Outfit', sans-serif;">Subscription Library Permissions List</h4>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('permission-categories.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-bold font-outfit" style="width: auto !important;">
                    <i class="fa-solid fa-folder me-1"></i> Manage Categories
                </a>
                <a href="{{ route('permissions.create') }}" class="btn btn-primary button rounded-pill px-4 py-2 fw-bold font-outfit" style="width: auto !important; background-color: #18225f !important; border-color: #18225f !important;">
                    <i class="fa-solid fa-plus me-1"></i> Add Permission
                </a>
            </div>
        </div>

        <!-- Alert Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @elseif(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Table Responsive matching Library List UI -->
        <div class="table-responsive mb-4">
            <table class="table text-center" id="datatable">
                <thead>
                    <tr>
                        <th style="width:60px;">S.No.</th>
                        <th>Permission Name & Description</th>
                        <th>Category</th>
                        <th>Guard</th>
                        <th>Slug Key</th>
                        <th style="width:120px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($permissions as $key => $p)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td class="text-start">
                            <span class="d-block" style="color: #18225f;">{{ $p->name }}</span>
                            @if($p->description)
                                <code>{{ ucfirst(strtolower($p->description)) }}</code>
                            @endif
                        </td>
                        <td>
                            @if($p->category)
                                <span class="badge text-white rounded-pill px-3 py-1" style="background-color: #18225f;">
                                    <i class="fa-solid fa-folder me-1"></i> {{ $p->category->name }}
                                </span>
                            @else
                                <small class="text-muted">Uncategorized</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border px-3 py-1 rounded-pill" style="font-family: monospace;">{{ $p->guard_name }}</span>
                        </td>
                        <td>
                            <code class="text-secondary small">{{ $p->slug ?? Str::slug($p->name) }}</code>
                        </td>
                        <td>
                            <ul class="actionalbls justify-content-center">
                                <li>
                                    <a href="{{ route('permissions.edit', $p->id) }}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit Permission"><i class="fas fa-edit"></i></a>
                                </li>
                                <li>
                                    <form action="{{ route('permissions.delete', $p->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background:none;border:none;color:red;" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete Permission" onclick="return confirm('Are you sure you want to delete this permission?');"><i class="fas fa-trash"></i></button>
                                    </form>
                                </li>
                            </ul>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        if ($('#datatable').length && !$.fn.DataTable.isDataTable('#datatable')) {
            $('#datatable').DataTable({
                "pageLength": 20,
                "ordering": true,
                "responsive": true
            });
        }
    });
</script>

@endsection
