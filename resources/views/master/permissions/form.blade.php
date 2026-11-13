@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-arrow-left" :href="route('permissions')">Permissions</x-ui.button>
@endsection

@section('content')
@php
    $editing = isset($permission);
    $guards = [
        'library' => 'Library (portal & plans)',
        'web' => 'Admin panel (web)',
        'learner' => 'Learner',
    ];
@endphp

<div class="mx-auto max-w-3xl">
    <x-ui.flash />

    <form action="{{ route('permissions.store', $permission->id ?? null) }}" method="POST">
        @csrf

        <x-ui.section label="Permission" note="The slug key is what the code checks. It follows the name while you type.">
            <x-ui.card>
                <div class="grid gap-x-4 gap-y-1 md:grid-cols-2">
                    <x-ui.select name="permission_category_id" id="category" label="Group" required placeholder="Select a group"
                        :options="$categories->pluck('name', 'id')" :value="$permission->permission_category_id ?? null" />
                    <x-ui.select name="guard_name" id="guard_name" label="Guard" required :options="$guards" :value="$permission->guard_name ?? 'library'" />
                    <x-ui.input name="name" id="permission" label="Permission name" required :value="$permission->name ?? null" placeholder="e.g. View Financial Snapshot" />
                    <x-ui.input name="slug" id="slug" label="Slug key" readonly class="ui-mono" :value="$permission->slug ?? null" placeholder="auto-generated-slug" icon="fa-solid fa-key" />
                    <div class="md:col-span-2">
                        <x-ui.input name="description" id="description" label="Description" :value="$permission->description ?? null" placeholder="What this permission unlocks (optional)" />
                    </div>
                </div>
            </x-ui.card>
        </x-ui.section>

        <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-ui.button variant="gray" :href="route('permissions')">Cancel</x-ui.button>
            <x-ui.button type="submit" icon="fa-solid fa-check">{{ $editing ? 'Update permission' : 'Save permission' }}</x-ui.button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const permInput = document.getElementById('permission');
        const slugInput = document.getElementById('slug');

        if (permInput && slugInput) {
            permInput.addEventListener('input', function() {
                const slug = permInput.value
                    .toLowerCase()
                    .replace(/ /g, '-')
                    .replace(/[^\w-]+/g, '');
                slugInput.value = slug;
            });
        }
    });
</script>
@endsection
