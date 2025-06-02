@extends('layouts.library')
<!-- CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@section('content')
<div class="card card-default">
    <!-- Add Library User Form -->
    <div class="card-body">
        <form id="submit">
            @csrf
            <input type="hidden" name="id" id="user_id">
            <div class="row">
                <div class="col-lg-4">
                    <label>Name <sup class="text-danger">*</sup></label>
                    <input type="text" name="name" id="name" class="form-control char-only" >
                </div>
                <div class="col-lg-4">
                    <label>Email <sup class="text-danger">*</sup></label>
                    <input type="email" name="email" id="email" class="form-control"  autocomplete="off">
                </div>
                <div class="col-lg-4">
                    <label>Mobile</label>
                    <input type="text" name="mobile" id="mobile" class="form-control digit-only" autocomplete="off" maxlength="10" minlength="8">
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-lg-4">
                    <label>Password</label>
                    <input type="password" name="password" id="password" class="form-control" autocomplete="off">
                </div>
                <div class="col-lg-4">
                    <label>Select Branch</label>
                    <select name="branch_id[]" id="branch_id" class="form-control" multiple>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                
                <div class="col-lg-4">
                    <label>Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-lg-12">
                    <label>Permissions</label>
                    <div class="d-flex flex-wrap">
                  @foreach($permissions as $key => $value)
                    <div class="form-check mr-3">
                        <input class="form-check-input permission"
                            type="checkbox"
                            name="permissions[]"
                            value="{{ $value }}"
                            id="perm_{{ $value }}"
                            data-permission-name="{{ $key }}">
                        <label class="form-check-label" for="perm_{{ $value }}">
                            {{ $key }}
                        </label>
                    </div>
                @endforeach



                    </div>
                </div>
            </div>
            

            <div class="row mt-3">
                <div class="col-lg-3">
                    <button type="submit" class="btn btn-primary button" id="submit_id">Save User</button>
                </div>
            </div>
        </form>
    </div>

    <!-- List of Users -->
    <div class="card-body p-0">
        <h4 class="px-3 py-2">All Library Users</h4>
        <div class="table-responsive">
            <table class="table table-hover dataTable m-0" id="datatable">
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
                                <p>{{ $branchName }}</p>
                            @endforeach
                        </td>
                        

                        <td><span class="status-column">{{ $user->status ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            @foreach($user->getPermissionNames() as $perm)
                            <span class="badge badge-info">{{ $perm }}</span>
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
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    
    $(document).ready(function () {
        $('#branch_id').select2({
            placeholder: "Select branches",
            allowClear: true
        });

   // Edit user
    $('.edit_user').on('click', function () {
        let user = $(this).data('user');
        console.log('user',user);
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


        // Form submit
     $('#submit').on('submit', function (e) {
        e.preventDefault();

        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        var form = this;
        var formData = new FormData(form);

        $.ajax({
            url: "{{ route('library-users.store') }}",
            method: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                if (response.success) {
                     toastr.success(response.message);
                    form.reset();
                    $('#datatable').DataTable().ajax.reload(null, false); 
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;

                    $.each(errors, function (key, value) {
                        let field = $(`[name="${key}"]`);

                        if (key.includes('.')) {
                            const [baseKey, index] = key.split('.');
                            field = $(`[name="${baseKey}[]"]`).eq(index);
                        }

                        field.addClass('is-invalid');
                        field.after(`<span class="invalid-feedback" role="alert"><strong>${value[0]}</strong></span>`);
                    });
                } else {
                    alert('An unexpected error occurred.');
                }
            }
        });
    });


    $('.toggle-status').on('click', function (e) {
        e.preventDefault();
        let id = $(this).data('id');
        $.post("{{ url('library-users/toggle-status') }}/" + id, {_token: "{{ csrf_token() }}"}, function (res) {
            alert(res.message);
            location.reload();
        });
    });
    });
</script>
@endsection
