@extends('layouts.admin')

@section('content')

<div class="row">
    <div class="col-lg-12">
        <!-- Heading List matching Library List UI -->
        <div class="heading-list py-4">
            <h4 class="">Users List</h4>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary button w-15"><i class="fa-solid fa-plus"></i> Add User</a>
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
                        <th>User Name</th>
                        <th>Email Address</th>
                        <th>Assigned Role</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th style="width:20%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $key => $user)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>
                            <span class="truncate d-block m-auto fw-bold" data-bs-toggle="tooltip" data-bs-title="{{ $user->name }}" data-bs-placement="bottom">
                                {{ $user->name }}
                            </span>
                            @if(Auth::id() == $user->id)
                                <small class="text-primary fw-bold">Logged In</small>
                            @endif
                        </td>
                        <td>
                            <span class="d-block m-auto" data-bs-toggle="tooltip" data-bs-title="{{ $user->email }}" data-bs-placement="bottom">
                                <i class="fa-solid fa-envelope text-secondary me-1"></i> {{ $user->email }}
                            </span>
                        </td>
                        <td>
                            @if($user->roles->count())
                                @foreach($user->roles as $r)
                                    <span class="badge bg-primary text-white rounded-pill px-3 py-1">{{ $r->name }}</span>
                                @endforeach
                            @else
                                <small class="text-muted">No Role</small>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.users.toggle-status', $user->id) }}" class="text-decoration-none">
                                @if(($user->status ?? 1) == 1)
                                    <small class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Active</small>
                                @else
                                    <small class="text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i> Inactive</small>
                                @endif
                            </a>
                        </td>
                        <td>
                            <span class="d-block m-auto">{{ $user->created_at ? $user->created_at->format('M d, Y') : 'N/A' }}</span>
                        </td>
                        <td>
                            <ul class="actionalbls">
                                <li>
                                    <a href="{{ route('admin.users.edit', $user->id) }}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit User"><i class="fas fa-edit"></i></a>
                                </li>
                                @if(Auth::id() != $user->id)
                                <li>
                                    <a href="{{ route('admin.users.destroy', $user->id) }}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete User" onclick="return confirm('Are you sure you want to delete this user?');"><i class="fas fa-trash"></i></a>
                                </li>
                                @endif
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
