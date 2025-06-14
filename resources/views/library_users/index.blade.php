@extends('layouts.library')
<!-- CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@section('content')


 <div class="col-lg-4">
        
        <a href="{{ route('library-users.create') }}" class="btn btn-primary export">
            <i class="fa-solid fa-plus "></i> Add Library User 
        </a>
        
    </div>
<h4 class="py-4">All Library Users</h4>
<div class="card p-0">
    <!-- List of Users -->

    <div class="table-responsive mb-4">
        <table class="table text-center datatable" id="datatable">
            <thead>
                <tr>
                    <th>S.No.</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Branch</th>
                    <th>Status</th>
                    <th>Permissions</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $key => $user)
                <tr>
                    <td>{{ $key+1 }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->mobile }}</td>
                    <td>
                        @foreach($user->branch_names as $branchName)
                        {{ $branchName }}
                        @endforeach
                    </td>


                    <td>{{ $user->status ? 'Active' : 'Inactive' }}</td>
                    <td>
                        @foreach($user->getPermissionNames() as $perm)
                        {{ $perm }}
                        @endforeach
                    </td>
                    <td>
                        <ul class="actionalbls">
                            <li><a href="javascript:void(0)" class="edit_user" data-user='@json($user)'><i class="fas fa-edit"></i></a></li>
                            <li>
                                <a href="#" class="toggle-status" data-id="{{ $user->id }}">
                                    <i class="fas {{ $user->status ? 'fa-ban' : 'fa-check' }}"></i>
                                </a>
                            </li>
                        </ul>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $('#checkAllPermissions').on('change', function() {
        $('.permission').prop('checked', this.checked);
    });

</script>
<script>
    $(document).ready(function() {
      
        // Edit user
        $('.edit_user').on('click', function() {
            let user = $(this).data('user');
            console.log('user', user);
            $('#user_id').val(user.id);
            $('#name').val(user.name);
            $('#email').val(user.email);
            $('#mobile').val(user.mobile);
            $('#status').val(user.status);

            // Set selected branches (array of strings)
            $('#branch_id').val(user.branch_id).trigger('change');

            $('.permission').prop('checked', false);

            // Re-check based on permission names
            user.permissions_array.forEach(function(permissionName) {
                $('input.permission').each(function() {
                    if ($(this).data('permission-name') === permissionName) {
                        $(this).prop('checked', true);
                    }
                });
            });

        });


        $('.toggle-status').on('click', function(e) {
            e.preventDefault();
            let id = $(this).data('id');
            $.post("{{ url('library-users/toggle-status') }}/" + id, {
                _token: "{{ csrf_token() }}"
            }, function(res) {
                alert(res.message);
                location.reload();
            });
        });
    });

</script>
@endsection
