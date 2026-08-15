@extends('layouts.library')
@section('content')

<!-- Add Library User Form -->

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <form id="userSubmitForm">
                @csrf
                <input type="hidden" name="id" value="{{ $editUser->id ?? '' }}">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control char-only my-input" value="{{ old('name', $editUser->name ?? '') }}">
                    </div>
                    <div class="col-lg-6">
                        <label>Email ID <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" autocomplete="off" value="{{ old('email', $editUser->email ?? '') }}">
                    </div>
                    <div class="col-lg-6">
                        <label>Mobile No <span class="text-danger">*</span></label>
                        <input type="text" name="mobile" class="form-control digit-only" autocomplete="off" maxlength="10" minlength="8" value="{{ old('mobile', $editUser->mobile ?? '') }}">
                    </div>
                  

                    <div class="col-lg-6">
                        <label>Select Role <span class="text-danger">*</span></label>
                      <select name="role" class="form-select">
                            <option value="">-- Select Role --</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}"
                                    @if(isset($editUser) && $editUser->roles->isNotEmpty() && $editUser->roles->first()->name === $role->name)
                                        selected
                                    @endif>
                                    {{ $role->name }}
                                </option>
                            @endforeach
         
                        </select>
                    </div>


                    <div class="col-lg-6">
                        <label>Select Branch <span class="text-danger">*</span></label>

                        @if($branches->isEmpty())
                        <div class="alert alert-danger mt-1 p-2 small">
                            <i class="fa fa-exclamation-triangle"></i> No active branch found. <a href="{{ route('branch.create') }}" class="alert-link">Click here to create a branch</a>.
                        </div>
                        @else
                        <select name="branch[]" id="my-select" class="form-select" multiple>
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
                        @endif
                    </div>
                    <div class="col-lg-6">
                        <label>Upload Photo </label>
                        <input type="file" name="profile_picture" class="form-control " autocomplete="off"  value="{{ old('profile_picture', $editUser->profile_picture ?? '') }}">
                    </div>
                    <div class="col-lg-6">
                        <label>Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" autocomplete="off" value="{{ old('password') }}">
                    </div>
                    <div class="col-lg-6">
                        <label>Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control" autocomplete="off" value="{{ old('password_confirmation') }}">
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-lg-3">
                        <button type="submit" class="btn btn-primary button" id="submit_id">
                            @if(isset($editUser))
                                Update User
                            @else
                                Add User
                            @endif
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="photo text-center">
                 <img src="{{ !empty($editUser->profile_picture) 
                        ? asset('storage/app/public/' . $editUser->profile_picture) 
                        : asset('public/img/user.png') }}" id="photo" alt="user" style="width: 150px; display:block; margin:0 auto;">
                @if(empty($editUser->profile_picture) )
               
                <p class="mt-4">Upload User Photo</p>
                @endif
            </div>
           
            <small class="text-danger">Guideline to Upload</small>
            <ol class="upload_guideline" type="1">
             
               <li> Upload a recent passport-size photo</li>
                <li> Face should be centered</li>
               <li> Avoid selfies, group photos with accessories like sunglasses or caps</li>
             
               <li> Eligible file types: JPG / JPEG / PNG / WEBP</li>
                <li> File size must not exceed 1 MB</li>
            </ol>
            
        </div>
    </div>
</div>





<!-- JS -->
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

        // Form submit
        $('#userSubmitForm').on('submit', function(e) {
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
                    console.log("res", response);
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
                            let field;

                            if (key.includes('.')) {
                                const baseKey = key.split('.')[0];
                                field = $(`[name="${baseKey}[]"]`);
                            } else {
                                field = $(`[name="${key}"], [name="${key}[]"]`);
                            }

                            field.addClass('is-invalid');
                            if (!field.next('.invalid-feedback').length) {
                                field.after(`<span class="invalid-feedback" role="alert"><strong>${value[0]}</strong></span>`);
                            }
                        });

                    } else {
                        alert('An unexpected error occurred.');
                    }
                }
            });
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