@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button icon="fa-solid fa-plus" :href="route('admin.roles.create')">New Role</x-ui.button>
@endsection

@section('content')
<x-ui.flash />

<x-ui.card flush>
    <x-ui.datatable id="datatable" stack>
        <x-slot:head>
            <th>#</th>
            <th>Role</th>
            <th>Guard</th>
            <th>Permissions</th>
            <th class="is-end">Actions</th>
        </x-slot:head>
        @foreach($roles as $key => $role)
            <tr>
                <td class="ui-table__hide-sm ui-footnote">{{ $key + 1 }}</td>
                <td class="ui-table__lead">
                    <div class="flex items-center gap-3">
                        <span class="ui-row__icon"><i class="fa-solid fa-shield-halved"></i></span>
                        <a href="{{ route('admin.roles.edit', $role->id) }}" class="ui-table__title">{{ $role->name }}</a>
                    </div>
                </td>
                <td data-label="Guard"><code class="ui-mono text-label-3">{{ $role->guard_name }}</code></td>
                <td data-label="Permissions"><x-ui.badge tone="blue" pill><i class="fa-solid fa-key"></i>{{ $role->permissions->count() }}</x-ui.badge></td>
                <td class="is-actions">
                    <div class="ui-actions" data-ax-done="1">
                        <x-ui.button size="sm" variant="gray" icon="fa-solid fa-pen" :href="route('admin.roles.edit', $role->id)">Edit</x-ui.button>
                        <x-ui.button size="sm" variant="destructive" icon="fa-regular fa-trash-can" :href="route('admin.roles.destroy', $role->id)" label="Delete role"
                            onclick="return confirm('Are you sure you want to delete this role?');" />
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
