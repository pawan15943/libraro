@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button icon="fa-solid fa-plus" :href="route('admin-permissions.create')">New Permission</x-ui.button>
@endsection

@section('content')
<x-ui.flash />

<x-ui.card flush>
    <x-ui.datatable id="datatable" stack>
        <x-slot:head>
            <th>#</th>
            <th>Permission</th>
            <th>Guard</th>
            <th>Created</th>
            <th class="is-end">Actions</th>
        </x-slot:head>
        @foreach($permissions as $key => $perm)
            <tr>
                <td class="ui-table__hide-sm ui-footnote">{{ $key + 1 }}</td>
                <td class="ui-table__lead">
                    <span class="ui-table__title ui-mono">{{ $perm->name }}</span>
                    @if($perm->description)
                        <div class="ui-table__sub">{{ ucfirst(strtolower($perm->description)) }}</div>
                    @endif
                </td>
                <td data-label="Guard"><code class="ui-mono text-label-3">web</code></td>
                <td data-label="Created" class="is-nowrap" data-order="{{ optional($perm->created_at)->timestamp }}">{{ $perm->created_at ? $perm->created_at->format('j M Y') : '—' }}</td>
                <td class="is-actions">
                    <div class="ui-actions" data-ax-done="1">
                        <x-ui.button size="sm" variant="gray" icon="fa-solid fa-pen" :href="route('admin-permissions.edit', $perm->id)">Edit</x-ui.button>
                        <x-ui.button size="sm" variant="destructive" icon="fa-regular fa-trash-can" :href="route('admin-permissions.destroy', $perm->id)" label="Delete permission"
                            onclick="return confirm('Are you sure you want to delete this permission?');" />
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
                "pageLength": 20,
                "ordering": true,
                "responsive": true
            });
        }
    });
</script>
@endsection
