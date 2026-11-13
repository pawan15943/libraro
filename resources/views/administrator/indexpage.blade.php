@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button icon="fa-solid fa-plus" :href="route('add-page')">New Page</x-ui.button>
@endsection

@section('content')
<x-ui.flash />

<x-ui.card flush>
    @if(count($pages))
        <x-ui.datatable id="datatable" stack>
            <x-slot:head>
                <th>ID</th>
                <th>Title</th>
                <th>Slug</th>
                <th class="is-end">Actions</th>
            </x-slot:head>
            @foreach ($pages as $page)
                <tr>
                    <td class="ui-table__hide-sm ui-footnote">{{ $page->id }}</td>
                    <td class="ui-table__lead"><a href="{{ route('page.edit', $page) }}" class="ui-table__title">{{ $page->page_title }}</a></td>
                    <td data-label="Slug"><span class="ui-mono text-label-3">/{{ $page->page_slug }}</span></td>
                    <td class="is-actions">
                        <div class="ui-actions" data-ax-done="1">
                            <x-ui.button size="sm" variant="gray" icon="fa-solid fa-pen" :href="route('page.edit', $page)">Edit</x-ui.button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-ui.datatable>
    @else
        <x-ui.empty icon="fa-regular fa-file-lines" title="No pages yet" text="Website pages you create show up here.">
            <x-ui.button size="sm" icon="fa-solid fa-plus" :href="route('add-page')">New Page</x-ui.button>
        </x-ui.empty>
    @endif
</x-ui.card>
@endsection
