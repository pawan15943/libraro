@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button icon="fa-solid fa-plus" :href="route('admin.users.create')">New User</x-ui.button>
@endsection

@section('content')
<x-ui.flash />

<x-ui.card flush>
    <x-ui.datatable id="datatable" stack>
        <x-slot:head>
            <th>#</th>
            <th>User</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Created</th>
            <th class="is-end">Actions</th>
        </x-slot:head>
        @foreach($users as $key => $user)
            <tr>
                <td class="ui-table__hide-sm ui-footnote">{{ $key + 1 }}</td>
                <td class="ui-table__lead">
                    <div class="flex items-center gap-3 min-w-0">
                        <x-ui.avatar :name="$user->name" size="sm" />
                        <div class="min-w-0">
                            <a href="{{ route('admin.users.edit', $user->id) }}" class="ui-table__title block truncate">{{ $user->name }}</a>
                            @if(Auth::id() == $user->id)<div class="ui-table__sub text-tint-text">You</div>@endif
                        </div>
                    </div>
                </td>
                <td data-label="Email"><span class="block max-w-64 truncate" title="{{ $user->email }}">{{ $user->email }}</span></td>
                <td data-label="Role">
                    @forelse($user->roles as $r)
                        <x-ui.badge tone="blue" pill>{{ $r->name }}</x-ui.badge>
                    @empty
                        <span class="text-label-4">No role</span>
                    @endforelse
                </td>
                <td data-label="Status">
                    <a href="{{ route('admin.users.toggle-status', $user->id) }}" title="Change status">
                        @if(($user->status ?? 1) == 1)
                            <x-ui.badge tone="green" dot>Active</x-ui.badge>
                        @else
                            <x-ui.badge tone="red" dot>Inactive</x-ui.badge>
                        @endif
                    </a>
                </td>
                <td data-label="Created" class="is-nowrap" data-order="{{ optional($user->created_at)->timestamp }}">{{ $user->created_at ? $user->created_at->format('j M Y') : '—' }}</td>
                <td class="is-actions">
                    <div class="ui-actions" data-ax-done="1">
                        <x-ui.button size="sm" variant="gray" icon="fa-solid fa-pen" :href="route('admin.users.edit', $user->id)">Edit</x-ui.button>
                        @if(Auth::id() != $user->id)
                            <x-ui.button size="sm" variant="destructive" icon="fa-regular fa-trash-can" :href="route('admin.users.destroy', $user->id)" label="Delete user"
                                onclick="return confirm('Are you sure you want to delete this user?');" />
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    </x-ui.datatable>
</x-ui.card>

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
