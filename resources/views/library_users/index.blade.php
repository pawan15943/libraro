@extends('layouts.library')

@section('content')

<div id="flash-message-placeholder"></div>
@if($users->isNotEmpty())


<div class="row g-4">
    <div class="col-lg-4">
        <div class="userBox">
            <h4>15</h4>
            <span>Total User</span>
            <img src="{{ asset('public/img/users.png')}}" alt="users" class="user-icon">
        </div>
    </div>
    <div class="col-lg-4">
        <div class="userBox">
            <h4>15</h4>
            <span>Active User</span>
            <img src="{{ asset('public/img/active.png')}}" alt="Active" class="user-icon">
        </div>
    </div>
    <div class="col-lg-4">
        <div class="userBox">
            <h4>15</h4>
            <span>Inactive User</span>
            <img src="{{ asset('public/img/inactive.png')}}" alt="Inactive" class="user-icon">
        </div>
    </div>
</div>
<div class="userHeadingss mt-4">
    <h4 class="m-0">Users List </h4>
    @can('has-permission','Add User Master')
    <a href="{{ route('library-users.create') }}" class="btn btn-primary export m-0">
        <i class="fa-solid fa-plus"></i> Add User
    </a>
    @endcan
</div>

@foreach($users as $key => $user)
<div class="row mt-4">
    <div class="col-lg-12">
        <div class="user-list bg-white">
            <img src="{{ asset('public/img/user.png')}}" alt="user" class="user-profile" style="width: 60px;">
            <div class="user-infos">
                <span>Name</span>
                <h4>{{ $user->name }}</h4>
            </div>
            <div class="user-infos">
                <span>Contact</span>
                <h4>{{ $user->email ?? '-' }}</h4>
            </div>
            <div class="user-infos">
                <span>Branche</span>
                <h4 style="font-sm">
                    @if(!empty($user->branch_names) && is_array($user->branch_names))
                    {{ implode(', ', $user->branch_names) }}
                    @else
                    -
                    @endif
                </h4>
            </div>
            <div class="user-infos">
                <span>Permissions</span>
                <h4><a href="" style="font-weight: 600 !important;">20+</a></h4>
            </div>
            <div class="user-infos">
                <span>Status</span>
                <h4>{{ $user->status ? 'Active' : 'Inactive' }}</h4>
            </div>
            <div class="action">
                <ul>
                    <li><a href="javascript:;" class="toggle-status" data-id="{{ $user->id }}" title="Toggle Status">
                            <i class="fas {{ $user->status ? 'fa-ban text-danger' : 'fa-check text-success' }}"></i>
                        </a></li>
                    <li><a href=""><i class="fa fa-plus"></i></a></li>
                    <li><a href="{{ route('library-users.create', $user->id) }}"><i class="fa fa-edit"></i></a></li>
                    <li><a href=""><i class="fa fa-trash"></i></a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="card p-0 d-none">

    <div class="row gx-0">
        <div class="col-lg-12">
            <div class="revenue-info border-0">
                <ul>
                    <!-- Icon / S.No placeholder -->
                    <li style="width: 5%;">
                        <div class="icon">
                            <i class="fa fa-user"></i>
                        </div>
                    </li>

                    <!-- Name -->
                    <li style="width: 20%;">
                        <span>Name</span>
                        <p class="uppercase truncate d-block"></p>
                    </li>

                    <!-- Email -->
                    <li>
                        <span>Email</span>
                        <p class="truncate"></p>
                    </li>

                    <!-- Mobile -->
                    <li>
                        <span>Mobile</span>
                        <p>{{ $user->mobile ?? '-' }}</p>
                    </li>

                    <!-- Branch -->
                    <li>
                        <span>Branch</span>
                        <p>

                        </p>
                    </li>

                    <!-- Status -->
                    <li style="width: 7%;">
                        <span>Status</span>
                        <p></p>
                    </li>

                    <!-- Permissions -->
                    <li style="width: 18% !important">
                        <span>Permissions</span>
                        <p class="permission-chips">
                            @php
                            $perms = $user->getPermissionNames()->toArray();
                            @endphp

                            @if(!empty($perms))
                            @foreach($perms as $perm)
                            <span class="chip">{{ $perm }}</span>
                            @endforeach
                            @else
                            <span>-</span>
                            @endif
                        </p>
                    </li>

                    <!-- Action -->
                    <li style="width:8%;">
                        <ul class="actionalbles userAction">
                            <li>
                                <a href="{{ route('library-users.create', $user->id) }}" title="Edit">
                                    <i class="fa-solid fa-chair"></i>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:;" class="toggle-status" data-id="{{ $user->id }}" title="Toggle Status">
                                    <i class="fas {{ $user->status ? 'fa-ban' : 'fa-check' }}"></i>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endforeach
@else
<div class="col-12">
    <div class="no-data-found text-center p-4">
        <script
            src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js"
            type="module"></script>

        <dotlottie-wc
            src="https://lottie.host/5d973bf9-2f1d-4dd5-925f-86da95dbd7b1/t7dXaWIroC.lottie"
            style="width: 200px;height: 200px"
            autoplay
            loop></dotlottie-wc>
        <h4>You haven’t added any library users yet.</h4>
        <span>Start by creating your first user to manage it here.</span>
        <div class="heading-list justify-content-end">
            @can('has-permission','Add User Master')
            <a href="{{ route('library-users.create') }}" class="btn btn-primary export">
                <i class="fa-solid fa-plus"></i> Add Library User
            </a>
            @else
            <span class="text-danger">You don't have Permission to add Users</span>
            @endcan
        </div>
    </div>
</div>
@endif

<!-- JS -->
<script>
    // If you ever use a "check all permissions" checkbox on a form, this remains useful.
    $('#checkAllPermissions').on('change', function() {
        $('.permission').prop('checked', this.checked);
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const message = sessionStorage.getItem('flash_message');
        const type = sessionStorage.getItem('flash_type') || 'success';

        if (message) {
            const div = document.createElement('div');
            div.className = 'alert alert-' + type;
            div.innerText = message;
            document.getElementById('flash-message-placeholder').appendChild(div);
            sessionStorage.removeItem('flash_message');
            sessionStorage.removeItem('flash_type');
        }
    });
</script>

<script>
    $(document).ready(function() {
        // Toggle status via AJAX post
        $('.toggle-status').on('click', function(e) {
            e.preventDefault();
            let id = $(this).data('id');

            $.post("{{ url('library-users/toggle-status') }}/" + id, {
                _token: "{{ csrf_token() }}"
            }, function(res) {
                alert(res.message);
                location.reload();
            }).fail(function(xhr) {
                let msg = 'Something went wrong';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                alert(msg);
            });
        });
    });
</script>

@include('library.script')

@endsection