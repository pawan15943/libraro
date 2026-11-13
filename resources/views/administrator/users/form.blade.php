@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-arrow-left" :href="route('admin.users')" class="hidden sm:inline-flex">Users</x-ui.button>
    <x-ui.button type="submit" form="userForm" icon="fa-solid fa-check">{{ isset($user) ? 'Save' : 'Create' }}</x-ui.button>
@endsection

@section('content')
<x-ui.flash />

<form action="{{ route('admin.users.store', $user->id ?? null) }}" method="POST" id="userForm">
    @csrf

    <x-ui.section label="Account">
        <x-ui.card>
            <div class="ui-fields sm:grid-cols-2">
                <x-ui.input name="name" id="name" label="Full name" :value="$user->name ?? ''" placeholder="Full name" required />
                <x-ui.input type="email" name="email" id="email" label="Email" :value="$user->email ?? ''" icon="fa-regular fa-envelope" placeholder="name@example.com" required />
                <x-ui.select name="role" id="role" label="Role" :options="collect($roles)->pluck('name', 'name')" :value="$userRole ?? ''" placeholder="No role" />
                @if(isset($user))
                    <x-ui.input type="password" name="password" id="password" label="Password" placeholder="••••••••" hint="Leave blank to keep the current password" autocomplete="new-password" />
                @else
                    <x-ui.input type="password" name="password" id="password" label="Password" placeholder="At least 6 characters" autocomplete="new-password" required />
                @endif
            </div>
        </x-ui.card>
    </x-ui.section>

    <x-ui.section label="Menu access" note="Ticked menus are shown to this user in addition to their role.">
        @if(count($permissions))
            <div class="grid items-start gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($permissions as $group => $groupPerms)
                    <x-ui.check-group :title="ucfirst($group)" :count="count($groupPerms)" :select-all="['class' => 'js-group-all', 'aria-label' => 'Select all '.$group]">
                        @foreach($groupPerms as $perm)
                            <x-ui.check name="permissions[]" :value="$perm->name" id="u_perm_{{ $perm->id }}" :checked="in_array($perm->name, $userPermissions ?? [])">{{ $perm->name }}</x-ui.check>
                        @endforeach
                    </x-ui.check-group>
                @endforeach
            </div>
        @else
            <x-ui.card><x-ui.empty icon="fa-solid fa-key" title="No menu permissions" text="Create admin permissions first." /></x-ui.card>
        @endif
    </x-ui.section>

    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <x-ui.button variant="gray" :href="route('admin.users')">Cancel</x-ui.button>
        <x-ui.button type="submit" icon="fa-solid fa-check">{{ isset($user) ? 'Update user' : 'Create user' }}</x-ui.button>
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
