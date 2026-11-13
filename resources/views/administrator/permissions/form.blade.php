@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-arrow-left" :href="route('admin-permissions')">Permissions</x-ui.button>
@endsection

@section('content')
<x-ui.flash />

<form action="{{ route('admin-permissions.store', $permission->id ?? null) }}" method="POST" class="max-w-xl">
    @csrf
    <x-ui.section label="Permission">
        <x-ui.card>
            <x-ui.input name="name" id="name" label="Permission key" :value="$permission->name ?? ''" placeholder="e.g. manage-blogs" class="ui-mono" required
                hint="Saved as a lowercase hyphenated key, e.g. manage-blogs." />
            <x-slot:footer>
                <x-ui.button variant="gray" :href="route('admin-permissions')">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="fa-solid fa-check">{{ isset($permission) ? 'Update permission' : 'Save permission' }}</x-ui.button>
            </x-slot:footer>
        </x-ui.card>
    </x-ui.section>
</form>
@endsection
