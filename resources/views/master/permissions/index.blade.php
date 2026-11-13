@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-folder-tree" :href="route('permission-categories.index')">Groups</x-ui.button>
    <x-ui.button icon="fa-solid fa-plus" :href="route('permissions.create')">Add permission</x-ui.button>
@endsection

@section('content')
<x-ui.flash />
@if(session('warning'))<x-ui.banner tone="warning" dismissible>{{ session('warning') }}</x-ui.banner>@endif

@php $guardTone = ['web' => 'indigo', 'library' => 'teal', 'learner' => 'purple']; @endphp

<x-ui.card flush>
    <div class="ui-table-wrap">
        <table class="ui-table" id="datatable">
            <thead>
                <tr>
                    <th class="w-12 max-sm:hidden">#</th>
                    <th>Permission</th>
                    <th class="max-md:hidden">Group</th>
                    <th class="max-sm:hidden">Guard</th>
                    <th class="max-lg:hidden">Slug</th>
                    <th class="is-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($permissions as $key => $p)
                    <tr>
                        <td class="text-label-3 ui-num max-sm:hidden">{{ $key + 1 }}</td>
                        <td class="min-w-[200px]">
                            <div class="ui-table__title">{{ $p->name }}</div>
                            @if($p->description)
                                <div class="ui-table__sub">{{ ucfirst(strtolower($p->description)) }}</div>
                            @endif
                            <div class="ui-table__sub md:hidden">{{ $p->category->name ?? 'No group' }}<span class="sm:hidden"> · {{ $p->guard_name }}</span></div>
                        </td>
                        <td class="max-md:hidden">
                            @if($p->category)
                                <x-ui.badge tone="blue"><i class="fa-solid fa-folder"></i> {{ $p->category->name }}</x-ui.badge>
                            @else
                                <span class="text-label-4">No group</span>
                            @endif
                        </td>
                        <td class="max-sm:hidden"><x-ui.badge :tone="$guardTone[$p->guard_name] ?? 'gray'">{{ $p->guard_name }}</x-ui.badge></td>
                        <td class="max-lg:hidden"><span class="ui-mono text-[13px] text-label-3">{{ $p->slug ?? Str::slug($p->name) }}</span></td>
                        <td class="is-end is-nowrap">
                            <div class="inline-flex gap-1.5" data-ax-done="1">
                                <x-ui.button size="sm" variant="gray" icon="fa-solid fa-pen" label="Edit permission" :href="route('permissions.edit', $p->id)" />
                                <form action="{{ route('permissions.delete', $p->id) }}" method="POST" class="m-0" onsubmit="return confirm('Are you sure you want to delete this permission?');">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" size="sm" variant="destructive" icon="fa-regular fa-trash-can" label="Delete permission" />
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
                "pageLength": 20,
                "lengthMenu": [10, 20, 50, 100],
                "ordering": true,
                "responsive": true
            });
        }
    });
</script>
@endsection
