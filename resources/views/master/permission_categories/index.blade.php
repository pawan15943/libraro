@extends('layouts.admin')

@section('content')

<div class="row">
    <div class="col-lg-12">
        <!-- Heading List matching Library List UI -->
        <div class="heading-list py-4">
            <h4 class="">Permission Categories List</h4>
            <a href="{{ route('permission-categories.create') }}" class="btn btn-primary button w-15"><i class="fa-solid fa-plus"></i> Add Category</a>
        </div>

        <!-- Alert Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Table Responsive matching Library List UI -->
        <div class="table-responsive mb-4">
            <table class="table text-center" id="datatable">
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Category Name</th>
                        <th>Associated Permissions</th>
                        <th>Created Date</th>
                        <th style="width:20%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $key => $cat)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>
                            <span class="truncate d-block m-auto fw-bold" data-bs-toggle="tooltip" data-bs-title="{{ $cat->name }}" data-bs-placement="bottom">
                                <i class="fa-solid fa-folder-open me-1 text-primary"></i> {{ $cat->name }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-info text-dark rounded-pill px-3 py-1">
                                <i class="fa-solid fa-key me-1"></i> {{ $cat->permissions_count }} Perms
                            </span>
                        </td>
                        <td>
                            <span class="d-block m-auto">{{ $cat->created_at ? $cat->created_at->format('M d, Y') : 'N/A' }}</span>
                        </td>
                        <td>
                            <ul class="actionalbls">
                                <li>
                                    <a href="{{ route('permission-categories.edit', $cat->id) }}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit Category"><i class="fas fa-edit"></i></a>
                                </li>
                                <li>
                                    <form action="{{ route('permission-categories.delete', $cat->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background:none;border:none;color:red;" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete Category" onclick="return confirm('Are you sure you want to delete this category?');"><i class="fas fa-trash"></i></button>
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
                "pageLength": 15,
                "ordering": true,
                "responsive": true
            });
        }
    });
</script>

@endsection
