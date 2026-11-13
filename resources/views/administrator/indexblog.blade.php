@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button icon="fa-solid fa-plus" :href="route('add-blog')">New Article</x-ui.button>
@endsection

@section('content')
<x-ui.flash />

<x-ui.card flush>
    @if(count($blogs))
        <x-ui.datatable id="datatable" stack>
            <x-slot:head>
                <th>ID</th>
                <th>Article</th>
                <th>Status</th>
                <th class="is-num">Views</th>
                <th>Published</th>
                <th class="is-end">Actions</th>
            </x-slot:head>
            @foreach ($blogs as $value)
                <tr>
                    <td class="ui-table__hide-sm ui-footnote">#{{ $value->id }}</td>
                    <td class="ui-table__lead">
                        <div class="flex items-center gap-3 min-w-0">
                            <img src="{{ $value->header_image_url }}" alt="" loading="lazy"
                                onerror="this.onerror=null; this.src='https://placehold.co/120x80/18225f/ffffff?text=Blog';"
                                class="h-11 w-16 flex-none rounded-sm bg-fill-4 object-cover shadow-0">
                            <div class="min-w-0">
                                <a href="{{ route('blog.edit', $value->id) }}" class="ui-table__title line-clamp-2">{{ $value->page_title }}</a>
                                <div class="ui-table__sub max-w-80 truncate ui-mono">/blog/{{ $value->page_slug }}</div>
                            </div>
                        </div>
                    </td>
                    <td data-label="Status">
                        @if($value->status == 'published')
                            <x-ui.badge tone="green" dot>Published</x-ui.badge>
                        @elseif($value->status == 'draft')
                            <x-ui.badge tone="yellow" dot>Draft</x-ui.badge>
                        @else
                            <x-ui.badge tone="blue" dot>Scheduled</x-ui.badge>
                        @endif
                    </td>
                    <td data-label="Views" class="is-num">{{ number_format($value->views_count ?? 0) }}</td>
                    <td data-label="Published" class="is-nowrap">{{ $value->published_at ? $value->published_at->format('j M Y') : $value->created_at->format('j M Y') }}</td>
                    <td class="is-actions">
                        <div class="ui-actions" data-ax-done="1">
                            <x-ui.button size="sm" variant="gray" icon="fa-solid fa-arrow-up-right-from-square" :href="route('blog-detail', ['slug' => $value->page_slug])" target="_blank" label="View public post" />
                            <x-ui.button size="sm" variant="gray" icon="fa-solid fa-pen" :href="route('blog.edit', $value->id)">Edit</x-ui.button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-ui.datatable>
    @else
        <x-ui.empty icon="fa-regular fa-newspaper" title="No articles yet" text="Write the first blog article.">
            <x-ui.button size="sm" icon="fa-solid fa-plus" :href="route('add-blog')">New Article</x-ui.button>
        </x-ui.empty>
    @endif
</x-ui.card>
@endsection
