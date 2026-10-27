@extends('layouts.library')

@section('content')
<link rel="stylesheet" href="{{ asset('public/css/lib-admin.css') }}?v={{ time() }}">

<div class="lib-admin">
<div id="flash-message-placeholder"></div>

@if($users->isNotEmpty())

    <div class="la-toolbar">
        <p class="la-hint">Staff who can sign in to your library panel. Each user sees only the branches and permissions you give them.</p>
        @can('has-permission','Add User Master')
            <a href="{{ route('library-users.create') }}" class="la-btn la-btn-primary">
                <i class="fa-solid fa-plus"></i> Add User
            </a>
        @endcan
    </div>

    <div class="la-stats">
        <div class="la-card la-stat">
            <span class="la-icon-tile tone-navy"><i class="fa-solid fa-users"></i></span>
            <div><span class="la-stat-value">{{ $total_user }}</span><span class="la-stat-label">Total users</span></div>
        </div>
        <div class="la-card la-stat">
            <span class="la-icon-tile tone-green"><i class="fa-solid fa-user-check"></i></span>
            <div><span class="la-stat-value">{{ $active_user }}</span><span class="la-stat-label">Active</span></div>
        </div>
        <div class="la-card la-stat">
            <span class="la-icon-tile tone-rose"><i class="fa-solid fa-user-slash"></i></span>
            <div><span class="la-stat-value">{{ $inactive_user }}</span><span class="la-stat-label">Inactive</span></div>
        </div>
    </div>

    <div class="la-grid is-users">
        @foreach($users as $user)
            @php
                $branchNames = (!empty($user->branch_names) && is_array($user->branch_names)) ? $user->branch_names : [];
                $permissionCount = $user->permissions->count();
            @endphp
            <article class="la-card la-user {{ $user->status ? '' : 'is-inactive' }}">
                <div class="la-user-head">
                    <img src="{{ !empty($user->profile_picture) ? asset('storage/app/public/' . $user->profile_picture) : asset('public/img/user.png') }}" alt="" class="la-avatar">
                    <div class="la-user-id">
                        <p class="la-user-name">{{ $user->name }}</p>
                        <p class="la-user-sub">{{ $user->roles->first()->name ?? 'No role' }}@if($user->email) · {{ $user->email }}@endif</p>
                    </div>
                    <span class="la-pill is-dot {{ $user->status ? 'is-on' : 'is-off' }}">{{ $user->status ? 'Active' : 'Inactive' }}</span>
                </div>

                <div class="la-user-meta">
                    <div>
                        <span class="la-meta-label">Branches</span>
                        @if(count($branchNames))
                            <div class="la-chips">
                                @foreach($branchNames as $branchName)
                                    <span class="la-chip">{{ $branchName }}</span>
                                @endforeach
                            </div>
                        @else
                            <span class="la-user-sub">No branch</span>
                        @endif
                    </div>
                    <div>
                        <span class="la-meta-label">Permissions</span>
                        <button type="button" class="la-perm-link" data-bs-toggle="modal" data-bs-target="#permissionsModal_{{ $user->id }}">
                            {{ $permissionCount }} <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="la-user-actions">
                    <a href="{{ route('library-users.permissions', $user->id) }}" class="la-btn la-btn-soft la-btn-sm">
                        <i class="fa-solid fa-key"></i> Permissions
                    </a>
                    <a href="javascript:;" class="la-icon-btn {{ $user->status ? 'is-danger' : 'is-success' }} toggle-status" data-id="{{ $user->id }}"
                       data-bs-toggle="tooltip" title="{{ $user->status ? 'Deactivate' : 'Activate' }}" aria-label="{{ $user->status ? 'Deactivate' : 'Activate' }} {{ $user->name }}">
                        <i class="fa-solid {{ $user->status ? 'fa-ban' : 'fa-check' }}"></i>
                    </a>
                    <a href="{{ route('library-users.create', $user->id) }}" class="la-icon-btn" data-bs-toggle="tooltip" title="Edit" aria-label="Edit {{ $user->name }}">
                        <i class="fa-solid fa-pen"></i>
                    </a>
                    <a href="javascript:;" class="la-icon-btn is-danger user-delete-btn" data-id="{{ $user->id }}" data-table="LibraryUser"
                       data-bs-toggle="tooltip" title="Delete" aria-label="Delete {{ $user->name }}">
                        <i class="fa-solid fa-trash"></i>
                    </a>
                </div>
            </article>

            {{-- Read-only list of this user's permissions --}}
            <div class="modal fade la-modal" id="permissionsModal_{{ $user->id }}" tabindex="-1" aria-labelledby="permissionsModalLabel_{{ $user->id }}" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="permissionsModalLabel_{{ $user->id }}">{{ $user->name }} · Permissions</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @php $userPermissionIds = $user->permissions->pluck('id'); $shownGroups = 0; @endphp
                            @foreach($groupedPermissions as $categoryId => $permissions)
                                @php $granted = collect($permissions)->filter(fn ($id) => $userPermissionIds->contains($id)); @endphp
                                @if($granted->isNotEmpty())
                                    @php $shownGroups++; @endphp
                                    <div class="la-modal-group">
                                        <p class="la-modal-group-title">
                                            {{ $categoryId ? (\App\Models\PermissionCategory::where('id', $categoryId)->value('name') ?? 'Other') : 'Other' }}
                                        </p>
                                        <div class="la-modal-chips">
                                            @foreach($granted as $name => $id)
                                                <span class="la-modal-chip"><i class="fa-solid fa-check"></i>{{ ucwords(strtolower($name)) }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                            @if($shownGroups === 0)
                                <p class="la-user-sub m-0">No permissions given yet.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

@else
    <div class="la-card la-empty">
        <span class="la-empty-icon"><i class="fa-solid fa-user-plus"></i></span>
        <p class="la-empty-title">No users yet</p>
        <p class="la-empty-text">Add staff so they can mark attendance, book seats and manage learners for the branches you choose.</p>
        @can('has-permission','Add User Master')
            <a href="{{ route('library-users.create') }}" class="la-btn la-btn-primary">
                <i class="fa-solid fa-plus"></i> Add User
            </a>
        @else
            <p class="la-note">You don't have permission to add users.</p>
        @endcan
    </div>
@endif
</div>

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

        if (window.bootstrap && bootstrap.Tooltip) {
            document.querySelectorAll('.lib-admin [data-bs-toggle="tooltip"]').forEach(function (el) {
                bootstrap.Tooltip.getOrCreateInstance(el);
            });
        }
    });
</script>

<script>
    $(document).ready(function() {
        function notify(type, message) {
            if (window.toastr) {
                toastr[type](message);
            } else {
                alert(message);
            }
        }

        // Toggle status via AJAX post
        $('.toggle-status').on('click', function(e) {
            e.preventDefault();
            let id = $(this).data('id');

            $.post("{{ url('library-users/toggle-status') }}/" + id, {
                _token: "{{ csrf_token() }}"
            }, function(res) {
                notify('success', res.message);
                setTimeout(function () { location.reload(); }, 700);
            }).fail(function(xhr) {
                let msg = 'Something went wrong';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                notify('error', msg);
            });
        });

        $('.user-delete-btn').on('click', function(e) {
            e.preventDefault();

            let dataId = $(this).data('id');
            let table = $(this).data('table');
            let routeUrl ='{{ route("master.delete", ":id") }}'.replace(':id', dataId);

            Swal.fire({
                title: 'Delete this user?',
                text: "They will no longer be able to sign in.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: routeUrl,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        data: {
                            table: table
                        },
                        success: function (response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Deleted',
                                    text: response.message,
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        },
                        error: function (xhr) {
                            Swal.fire('Error', (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong.', 'error');
                        }
                    });
                }
            });
        });
    });
</script>

@include('library.script')

@endsection
