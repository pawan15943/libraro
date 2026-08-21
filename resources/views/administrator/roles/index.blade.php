@extends('layouts.admin')

@section('content')

<div class="row">
    <div class="col-lg-12">
        <!-- Heading List matching Library List UI -->
        <div class="heading-list py-4">
            <h4 class="">Roles List</h4>
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary button w-15"><i class="fa-solid fa-plus"></i> Add Role</a>
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
                        <th>Role Name</th>
                        <th>Guard Name</th>
                        <th>Assigned Permissions</th>
                        <th style="width:20%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $key => $role)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>
                            <span class="truncate d-block m-auto fw-bold" data-bs-toggle="tooltip" data-bs-title="{{ $role->name }}" data-bs-placement="bottom">
                                <i class="fa-solid fa-shield-halved me-1 text-primary"></i> {{ $role->name }}
                            </span>
                        </td>
                        <td>
                            <code>{{ $role->guard_name }}</code>
                        </td>
                        <td>
                            <span class="badge bg-info text-dark rounded-pill px-3 py-1">
                                <i class="fa-solid fa-key me-1"></i> {{ $role->permissions->count() }} Perms
                            </span>
                        </td>
                        <td>
                            <ul class="actionalbls">
                                <li>
                                    <a href="{{ route('admin.roles.edit', $role->id) }}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit Role & Permissions"><i class="fas fa-edit"></i></a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.roles.destroy', $role->id) }}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete Role" onclick="return confirm('Are you sure you want to delete this role?');"><i class="fas fa-trash"></i></a>
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
