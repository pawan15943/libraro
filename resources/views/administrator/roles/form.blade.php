@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-arrow-left" :href="route('admin.roles')" class="hidden sm:inline-flex">Roles</x-ui.button>
    <x-ui.button type="submit" form="roleForm" icon="fa-solid fa-check">{{ isset($role) ? 'Save' : 'Create' }}</x-ui.button>
@endsection

@section('content')
<x-ui.flash />

<form action="{{ route('admin.roles.store', $role->id ?? null) }}" method="POST" id="roleForm">
    @csrf

    <x-ui.section label="Role">
        <x-ui.card>
            <div class="max-w-xl">
                <x-ui.input name="name" id="name" label="Role name" :value="$role->name ?? ''" placeholder="e.g. Editor, Sales, Support" required />
            </div>
        </x-ui.card>
    </x-ui.section>

    <x-ui.section label="Permissions" note="What people with this role can see and do in the admin portal.">
        @if(count($permissions))
            <div class="grid items-start gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($permissions as $group => $groupPerms)
                    <x-ui.check-group :title="ucfirst($group)" :count="count($groupPerms)" :select-all="['class' => 'js-group-all', 'aria-label' => 'Select all '.$group]">
                        @foreach($groupPerms as $perm)
                            <x-ui.check name="permissions[]" :value="$perm->name" id="perm_{{ $perm->id }}" :checked="in_array($perm->name, $rolePermissions ?? [])">{{ $perm->name }}</x-ui.check>
                        @endforeach
                    </x-ui.check-group>
                @endforeach
            </div>
        @else
            <x-ui.card><x-ui.empty icon="fa-solid fa-key" title="No permissions yet" text="Create admin permissions first, then map them to roles here." /></x-ui.card>
        @endif
    </x-ui.section>

    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <x-ui.button variant="gray" :href="route('admin.roles')">Cancel</x-ui.button>
        <x-ui.button type="submit" icon="fa-solid fa-check">{{ isset($role) ? 'Update role' : 'Create role' }}</x-ui.button>
    </div>
</form>

<script>
    // header checkbox of each group ticks / clears the whole group
    document.querySelectorAll('.js-group-all').forEach(function (all) {
        var boxes = all.closest('.ui-checkgroup').querySelectorAll('.ui-checkgroup__body input[type=checkbox]');
        var sync = function () { all.checked = boxes.length > 0 && Array.prototype.every.call(boxes, function (b) { return b.checked; }); };
        sync();
        all.addEventListener('change', function () { boxes.forEach(function (b) { b.checked = all.checked; }); });
        boxes.forEach(function (b) { b.addEventListener('change', sync); });
    });
</script>
@endsection
