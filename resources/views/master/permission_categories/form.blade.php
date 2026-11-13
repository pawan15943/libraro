@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-arrow-left" :href="route('permission-categories.index')">Groups</x-ui.button>
@endsection

@section('content')
<div class="mx-auto max-w-xl">
    <x-ui.flash />

    <form action="{{ route('permission-categories.store', $category->id ?? null) }}" method="POST">
        @csrf

        <x-ui.section label="Group" note="Groups organise permissions on plan and staff screens.">
            <x-ui.card>
                <x-ui.input name="name" id="name" label="Group name" required size="lg" :value="$category->name ?? null" placeholder="e.g. Finance, Reports, Seat Operations" />
            </x-ui.card>
        </x-ui.section>

        <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-ui.button variant="gray" :href="route('permission-categories.index')">Cancel</x-ui.button>
            <x-ui.button type="submit" icon="fa-solid fa-check">{{ isset($category) ? 'Update group' : 'Save group' }}</x-ui.button>
        </div>
    </form>
</div>
@endsection
