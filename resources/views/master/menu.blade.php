@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('content')
@php
    $editing = isset($menu) && $menu->id;
    $guards = ['web' => 'Admin (web)', 'library' => 'Library', 'learner' => 'Learner'];
    $guardTone = ['web' => 'indigo', 'library' => 'teal', 'learner' => 'purple'];
@endphp

@if(session('message'))<x-ui.banner tone="success" dismissible>{{ session('message') }}</x-ui.banner>@endif
<x-ui.flash />

<x-ui.card :title="$editing ? 'Edit menu' : 'Add menu'" subtitle="Sidebar entries for each portal. Order decides the position." icon="fa-solid fa-bars-staggered">
    <form id="submit" action="{{ $editing ? route('menu.update', $menu->id) : route('menu.store') }}" method="post">
        @csrf
        @if($editing)
            @method('PUT')
            <input type="hidden" name="id" value="{{ $menu->id }}" id="menu_id">
        @endif

        <div class="grid gap-x-4 gap-y-1 sm:grid-cols-2 lg:grid-cols-3">
            <x-ui.input name="name" id="name" label="Menu name" required class="char-only" :value="$editing ? $menu->name : null" placeholder="e.g. Reports" />
            <x-ui.input name="url" id="url" label="Menu link" required :value="$editing ? $menu->url : null" placeholder="route or /path" />
            <x-ui.input name="icon" id="icon" label="Icon class" class="char-only-sps" :value="$editing ? $menu->icon : null" placeholder="fa-solid fa-chart-line" />
            <x-ui.select name="parent_id" id="parent_id" label="Parent" placeholder="No parent (top level)" :options="$menusParent" :value="$editing ? $menu->parent_id : null" />
            <x-ui.input name="order" id="order" label="Menu order" required class="digit-only" inputmode="numeric" :value="$editing ? $menu->order : null" placeholder="1" />
            <x-ui.select name="guard" id="guard" label="Guard" required placeholder="Select guard" :options="$guards" :value="$editing ? $menu->guard : null" />
            <x-ui.select name="has_permissions" id="has_permissions" label="Permission" placeholder="Everyone (no permission)" :options="$permissions->pluck('name', 'name')" :value="$editing ? $menu->has_permissions : null" />
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <x-ui.button type="submit" icon="fa-solid fa-check" class="max-sm:flex-1">{{ $editing ? 'Update menu' : 'Add menu' }}</x-ui.button>
            @if($editing)
                <x-ui.button variant="gray" :href="route('menu.create')">Cancel</x-ui.button>
            @endif
        </div>
    </form>
</x-ui.card>

<x-ui.section label="All menus">
    <x-ui.card flush>
        <div class="ui-table-wrap">
            <table class="ui-table" id="datatable">
                <thead>
                    <tr>
                        <th class="w-12 max-sm:hidden">#</th>
                        <th>Menu</th>
                        <th class="max-md:hidden">Route</th>
                        <th class="max-sm:hidden">Guard</th>
                        <th class="max-md:hidden">Permission</th>
                        <th>Status</th>
                        <th class="is-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($menus as $i => $value)
                        <tr>
                            <td class="text-label-3 ui-num max-sm:hidden">{{ $i + 1 }}</td>
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <span class="grid size-8 shrink-0 place-items-center rounded-sm bg-tint-soft text-[13px] text-tint">@if($value->icon)<i class="{{ $value->icon }}"></i>@else<i class="fa-solid fa-bars"></i>@endif</span>
                                    <div class="min-w-0">
                                        <div class="ui-table__title">{{ $value->name }}</div>
                                        <div class="ui-table__sub md:hidden"><span class="ui-mono">{{ $value->url }}</span><span class="sm:hidden"> · {{ $value->guard }}</span></div>
                                    </div>
                                </div>
                            </td>
                            <td class="max-md:hidden"><span class="ui-mono text-label-2">{{ $value->url }}</span></td>
                            <td class="max-sm:hidden"><x-ui.badge :tone="$guardTone[$value->guard] ?? 'gray'">{{ $value->guard }}</x-ui.badge></td>
                            <td class="max-md:hidden">@if($value->has_permissions)<span class="text-[13px] text-label-2">{{ $value->has_permissions }}</span>@else<span class="text-label-4">—</span>@endif</td>
                            <td>
                                <x-ui.badge :tone="$value->trashed() ? 'gray' : 'green'" dot pill><span class="status-column">{{ $value->trashed() ? 'Inactive' : 'Active' }}</span></x-ui.badge>
                            </td>
                            <td class="is-end is-nowrap">
                                <div class="inline-flex gap-1.5" data-ax-done="1">
                                    <x-ui.button size="sm" variant="gray" icon="fa-solid fa-pen" label="Edit {{ $value->name }}" :href="route('menu.edit', $value->id)" />
                                    <x-ui.button size="sm" variant="gray" :icon="$value->trashed() ? 'fa-solid fa-check' : 'fa-solid fa-ban'" :label="$value->trashed() ? 'Activate' : 'Deactivate'"
                                        class="active-deactive" data-id="{{ $value->id }}" data-table="Menu" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>
</x-ui.section>

@include('master.script')
@endsection
