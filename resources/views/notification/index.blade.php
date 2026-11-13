@extends('layouts.admin')

@section('title', 'Notification List')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button icon="fa-solid fa-plus" :href="route('create.notification')">Add notification</x-ui.button>
@endsection

@section('content')
<x-ui.flash />

@php
    $types = [
        'important' => ['red', 'fa-bell'],
        'offers' => ['blue', 'fa-tag'],
        'maintenance' => ['orange', 'fa-wrench'],
        'wishes' => ['green', 'fa-gift'],
    ];
    $guards = [
        'library' => ['Library owners', 'fa-building-columns'],
        'learner' => ['Learners', 'fa-user-graduate'],
    ];
    $rows = $notifications->map(function ($item) use ($types, $guards) {
        $data = json_decode($item->data, true) ?? [];
        $type = strtolower($data['notification_type'] ?? 'important');
        return (object) [
            'item' => $item,
            'data' => $data,
            'type' => $type,
            'tone' => $types[$type][0] ?? 'red',
            'icon' => $types[$type][1] ?? 'fa-bell',
            'guard' => $guards[$item->guard] ?? ['Website / admin', 'fa-globe'],
            'start' => \Carbon\Carbon::parse($item->start_date),
            'end' => \Carbon\Carbon::parse($item->end_date),
            'inactive' => isset($item->status) && (string) $item->status === '0',
        ];
    });
@endphp

@if($rows->isEmpty())
    <x-ui.card>
        <x-ui.empty icon="fa-solid fa-bell-slash" title="No notifications yet" text="Announcements you send show up in the library dashboard and on the website.">
            <x-ui.button icon="fa-solid fa-plus" :href="route('create.notification')" class="mt-3">Add notification</x-ui.button>
        </x-ui.empty>
    </x-ui.card>
@else
    {{-- tablet & desktop --}}
    <x-ui.card flush class="max-md:hidden">
        <div class="ui-table-wrap">
            <table class="ui-table" id="datatable">
                <thead>
                    <tr>
                        <th class="w-12 max-sm:hidden">#</th>
                        <th>Type</th>
                        <th>Title</th>
                        <th class="max-2xl:hidden">Description</th>
                        <th>Audience</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <th class="is-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $index => $r)
                        <tr>
                            <td class="text-label-3 ui-num max-sm:hidden">{{ $index + 1 }}</td>
                            <td><x-ui.badge :tone="$r->tone"><i class="fa-solid {{ $r->icon }}"></i> {{ ucfirst($r->type) }}</x-ui.badge></td>
                            <td class="min-w-[180px]">
                                <div class="ui-table__title">{{ $r->data['title'] ?? 'N/A' }}</div>
                                @if(!empty($r->data['link']))
                                    <a href="{{ $r->data['link'] }}" target="_blank" rel="noopener" class="ui-table__sub text-tint-text no-underline hover:underline"><i class="fa-solid fa-link"></i> Link</a>
                                @endif
                                <div class="ui-table__sub max-w-[260px] truncate 2xl:hidden" title="{{ $r->data['description'] ?? '' }}">{{ $r->data['description'] ?? '' }}</div>
                            </td>
                            <td class="max-2xl:hidden">
                                <span class="block max-w-[280px] truncate text-label-3" title="{{ $r->data['description'] ?? '' }}">{{ $r->data['description'] ?? '—' }}</span>
                            </td>
                            <td class="is-nowrap text-label-2"><i class="fa-solid {{ $r->guard[1] }} mr-1 text-label-4"></i>{{ $r->guard[0] }}</td>
                            <td class="is-nowrap" data-order="{{ $r->start->format('Y-m-d') }}">
                                <div class="text-[13px] font-medium">{{ $r->start->format('j M Y') }}</div>
                                <div class="ui-table__sub">to {{ $r->end->format('j M Y') }}</div>
                            </td>
                            <td>
                                @if($r->inactive)
                                    <x-ui.badge tone="gray" dot pill>Inactive</x-ui.badge>
                                @else
                                    <x-ui.badge tone="green" dot pill>Active</x-ui.badge>
                                @endif
                            </td>
                            <td class="is-end is-nowrap">
                                <div class="inline-flex gap-1.5" data-ax-done="1">
                                    <x-ui.button size="sm" variant="gray" icon="fa-solid fa-pen" label="Edit" :href="route('notifications.edit', $r->item->batch_id)" />
                                    <form action="{{ route('notifications.destroy', $r->item->batch_id) }}" method="POST" class="m-0" onsubmit="return confirm('Are you sure you want to delete this notification?');">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" size="sm" variant="destructive" icon="fa-regular fa-trash-can" label="Delete" />
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    {{-- phone: stacked rows --}}
    <x-ui.card flush class="md:hidden">
        <ul class="ui-list">
            @foreach($rows as $r)
                <li class="border-t border-sep px-4 py-3.5 first:border-t-0">
                    <div class="flex items-center justify-between gap-2">
                        <x-ui.badge :tone="$r->tone"><i class="fa-solid {{ $r->icon }}"></i> {{ ucfirst($r->type) }}</x-ui.badge>
                        @if($r->inactive)
                            <x-ui.badge tone="gray" dot pill>Inactive</x-ui.badge>
                        @else
                            <x-ui.badge tone="green" dot pill>Active</x-ui.badge>
                        @endif
                    </div>
                    <div class="ui-headline mt-2">{{ $r->data['title'] ?? 'N/A' }}</div>
                    <div class="ui-subhead mt-0.5 line-clamp-2">{{ $r->data['description'] ?? '—' }}</div>
                    <div class="mt-2.5 flex items-center justify-between gap-2">
                        <div class="ui-footnote min-w-0">
                            <div class="truncate"><i class="fa-solid {{ $r->guard[1] }} mr-1"></i>{{ $r->guard[0] }}</div>
                            <div class="truncate"><i class="fa-regular fa-calendar mr-1"></i>{{ $r->start->format('j M') }} – {{ $r->end->format('j M Y') }}</div>
                        </div>
                        <div class="flex shrink-0 gap-1.5" data-ax-done="1">
                            @if(!empty($r->data['link']))
                                <x-ui.button size="sm" variant="gray" icon="fa-solid fa-link" label="Open link" :href="$r->data['link']" target="_blank" rel="noopener" />
                            @endif
                            <x-ui.button size="sm" variant="gray" icon="fa-solid fa-pen" label="Edit" :href="route('notifications.edit', $r->item->batch_id)" />
                            <form action="{{ route('notifications.destroy', $r->item->batch_id) }}" method="POST" class="m-0" onsubmit="return confirm('Are you sure you want to delete this notification?');">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" size="sm" variant="destructive" icon="fa-regular fa-trash-can" label="Delete" />
                            </form>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </x-ui.card>
@endif

<script>
    $(document).ready(function() {
        if ($('#datatable tbody tr').length > 1) {
            $('#datatable').DataTable({
                pageLength: 10,
                order: [[0, 'asc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search notifications..."
                }
            });
        }
    });
</script>
@endsection
