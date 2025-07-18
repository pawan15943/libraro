@extends('layouts.library')
<!-- CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />

@section('content')
<div class="card">
    <!-- Add Library User Form -->

    <form id="submit">
        @csrf
        <input type="hidden" name="id" id="user_id"  value="{{ $editUser->id ?? '' }}">
        <h4 class="pb-4">User Details</h4>
        <div class="row">
            <div class="col-lg-4">
                <label>Name <sup class="text-danger">*</sup></label>
                <input type="text" name="name" id="name" class="form-control char-only my-input" value="{{ old('name', $editUser->name ?? '') }}">
            </div>
            <div class="col-lg-4">
                <label>Email <sup class="text-danger">*</sup></label>
                <input type="email" name="email" id="email" class="form-control" autocomplete="off" value="{{ old('email', $editUser->email ?? '') }}">
            </div>
            <div class="col-lg-4">
                <label>Mobile</label>
                <input type="text" name="mobile" id="mobile" class="form-control digit-only" autocomplete="off" maxlength="10" minlength="8" value="{{ old('mobile', $editUser->mobile ?? '') }}">
            </div>
        </div>

        <div class="row g-4 mt-2">

            <div class="col-lg-4">
                <label>Password</label>
                <input type="password" name="password" id="password" class="form-control" autocomplete="off">
            </div>

            <div class="col-lg-4">
                <label>Select Branch</label>
               
                <select name="branch_id[]" id="my-select" class="form-select" multiple>
                        @php
                            $selectedBranches = $editUser?->branch_id ?? [];
                        @endphp

                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}"
                                {{ in_array($branch->id, $selectedBranches) ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                </select>
            </div>

            <div class="col-lg-4">
                <label>Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="1" {{ (old('status', $editUser->status ?? '') == 1) ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ (old('status', $editUser->status ?? '') == 0) ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <h4 class="py-4">User Permissions</h4>
            <!-- Check All Option -->
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="checkAllPermissions">
                <label class="form-check-label fw-bold" for="checkAllPermissions">
                    Check All
                </label>
            </div>
        </div>


        <div class="row ">
            <div class="col-lg-12">



                <!-- Permissions List -->

                {{-- @foreach($groupedPermissions as $categoryId => $permissions)
                <div class="row">
                    <div class="col-lg-12">
                        <h5 class='role-category-heading'>
                            {{ $categoryId ? \App\Models\PermissionCategory::find($categoryId)->name : 'No Category' }}
                        </h5>
                    </div>
                </div>

                <div class="row g-3 mt-1 mb-3">
                    @foreach($permissions as $name => $id)
                    <div class="col-md-3">
                        <div class="form-check">
                            <input
                                class="form-check-input permission"
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $id }}"
                                id="perm_{{ $id }}"
                                data-permission-name="{{ $name }}">
                            <label class="form-check-label" for="perm_{{ $id }}">
                                {{ strtoupper($name) }}
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endforeach --}}

            @php
                $selectedPermissionIds = old('permissions', $editUser?->permissions?->pluck('id')->toArray() ?? []);
            @endphp

            @foreach($groupedPermissions as $categoryId => $permissions)
                <div class="row">
                    <div class="col-lg-12">
                        <h5 class='role-category-heading'>
                            {{ $categoryId ? \App\Models\PermissionCategory::find($categoryId)->name : 'No Category' }}
                        </h5>
                    </div>
                </div>

                <div class="row g-3 mt-1 mb-3">
                    @foreach($permissions as $name => $id)
                    <div class="col-md-3">
                        <div class="form-check">
                            <input
                                class="form-check-input permission"
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $id }}"
                                id="perm_{{ $id }}"
                                data-permission-name="{{ $name }}"
                                {{ in_array($id, $selectedPermissionIds) ? 'checked' : '' }}>
                            <label class="form-check-label" for="perm_{{ $id }}">
                                {{ strtoupper($name) }}
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endforeach

            </div>
        </div>

        <div class="row mt-3">
            <div class="col-lg-3">
                <button type="submit" class="btn btn-primary button" id="submit_id">Save User</button>
            </div>
        </div>
    </form>

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
        $('#branch_id').select2({
            placeholder: "Select branches",
            allowClear: true
        });

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


        // Form submit
        $('#submit').on('submit', function(e) {
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
                success: function(response) {
                    console.log("res",response);
                    if (response.success && response.redirect) {
                        if (response.message) {
                            sessionStorage.setItem('flash_message', response.message);
                            sessionStorage.setItem('flash_type', 'success'); // or error, info, etc.
                        }
                        window.location.href = response.redirect;
                      
                        form.reset();
                        $('#datatable').DataTable().ajax.reload(null, false);
                    } else if (response.success) {
                        if (response.message) {
                                showFlashMessage(response.message, 'success');
                            }
                                            
                        form.reset();
                        $('#datatable').DataTable().ajax.reload(null, false);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;

                        $.each(errors, function(key, value) {
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

<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // For select
        const selectElement = document.getElementById('my-select');
        const choicesSelect = new Choices(selectElement, {
            removeItemButton: true,
        });

        // For input (tags-like input)
        const inputElement = document.getElementById('my-input');
        const choicesInput = new Choices(inputElement, {
            delimiter: ',',
            editItems: true,
            maxItemCount: 5,
            removeItemButton: true,
        });
    });
</script>
@endsection