@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-key" :href="route('permissions')">Permissions</x-ui.button>
    <x-ui.button icon="fa-solid fa-plus" :href="route('permission-categories.create')">Add group</x-ui.button>
@endsection

@section('content')
<x-ui.flash />

<x-ui.card flush>
    <div class="ui-table-wrap">
        <table class="ui-table" id="datatable">
            <thead>
                <tr>
                    <th class="w-12 max-sm:hidden">#</th>
                    <th>Group</th>
                    <th class="is-num">Permissions</th>
                    <th class="max-md:hidden">Created</th>
                    <th class="is-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $key => $cat)
                    <tr>
                        <td class="text-label-3 ui-num max-sm:hidden">{{ $key + 1 }}</td>
                        <td>
                            <div class="flex items-center gap-2.5">
                                <span class="grid size-8 shrink-0 place-items-center rounded-sm bg-tint-soft text-[13px] text-tint"><i class="fa-solid fa-folder"></i></span>
                                <span class="ui-table__title min-w-0 truncate">{{ $cat->name }}</span>
                            </div>
                        </td>
                        <td class="is-num"><x-ui.badge tone="blue" pill>{{ $cat->permissions_count }}</x-ui.badge></td>
                        <td class="max-md:hidden text-label-3">{{ $cat->created_at ? $cat->created_at->format('j M Y') : '—' }}</td>
                        <td class="is-end is-nowrap">
                            <div class="inline-flex gap-1.5" data-ax-done="1">
                                <x-ui.button size="sm" variant="gray" icon="fa-solid fa-pen" label="Edit group" :href="route('permission-categories.edit', $cat->id)" />
                                <form action="{{ route('permission-categories.delete', $cat->id) }}" method="POST" class="m-0" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" size="sm" variant="destructive" icon="fa-regular fa-trash-can" label="Delete group" />
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-ui.card>

<script>
    $(document).ready(function() {
        if ($('#datatable').length && !$.fn.DataTable.isDataTable('#datatable')) {
            $('#datatable').DataTable({
                "pageLength": 15,
                "lengthMenu": [15, 30, 60, 100],
                "ordering": true,
                "responsive": true
            });
        }
    });
</script>
@endsection
