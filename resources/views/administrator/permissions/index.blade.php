@extends('layouts.admin')

@section('content')

<div class="row">
    <div class="col-lg-12">
        <!-- Heading List matching Library List UI -->
        <div class="heading-list py-4">
            <h4 class="">Admin Permissions List</h4>
            <a href="{{ route('admin-permissions.create') }}" class="btn btn-primary button w-15"><i class="fa-solid fa-plus"></i> Add Permission</a>
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
                        <th style="width:60px;">S.No.</th>
                        <th>Permission Name & Description</th>
                        <th>Guard</th>
                        <th>Created Date</th>
                        <th style="width:20%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($permissions as $key => $perm)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td class="text-start">
                            <span class="d-block" style="color: #18225f;">{{ $perm->name }}</span>
                            @if($perm->description)
                                <code>{{ ucfirst(strtolower($perm->description)) }}</code>
                            @endif
                        </td>
                        <td>
                            <code>web</code>
                        </td>
                        <td>
                            <span class="d-block m-auto">{{ $perm->created_at ? $perm->created_at->format('M d, Y') : 'N/A' }}</span>
                        </td>
                        <td>
                            <ul class="actionalbls">
                                <li>
                                    <a href="{{ route('admin-permissions.edit', $perm->id) }}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit Admin Permission"><i class="fas fa-edit"></i></a>
                                </li>
                                <li>
                                    <a href="{{ route('admin-permissions.destroy', $perm->id) }}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete Admin Permission" onclick="return confirm('Are you sure you want to delete this permission?');"><i class="fas fa-trash"></i></a>
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
