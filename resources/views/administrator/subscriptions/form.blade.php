@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-arrow-left" :href="route('admin.subscriptions')" class="hidden sm:inline-flex">Plans</x-ui.button>
    <x-ui.button type="submit" form="subscriptionForm" icon="fa-solid fa-check">{{ isset($subscription) ? 'Save' : 'Create' }}</x-ui.button>
@endsection

@section('content')
<x-ui.flash />

<form action="{{ route('subscriptions.store', $subscription->id ?? null) }}" method="POST" id="subscriptionForm">
    @csrf

    <x-ui.section label="Plan">
        <x-ui.card>
            <div class="ui-fields sm:grid-cols-2">
                <x-ui.input name="name" id="name" label="Plan name" :value="$subscription->name ?? ''" placeholder="e.g. Basic, Pro, Enterprise" required />
                <x-ui.input type="number" step="0.01" name="monthly_fees" id="monthly_fees" label="Monthly price" prefix="₹" :value="$subscription->monthly_fees ?? ''" placeholder="499.00" required />
            </div>
            <div class="ui-fields sm:grid-cols-3">
                <x-ui.input type="number" step="0.01" name="yearly_fees" id="yearly_fees" label="Yearly price" prefix="₹" :value="$subscription->yearly_fees ?? ''" placeholder="4999.00" />
                <x-ui.input type="number" name="max_seats" id="max_seats" label="Max seats" :value="$subscription->max_seats ?? ''" placeholder="Unlimited" />
                <x-ui.input type="number" name="max_branches" id="max_branches" label="Max branches" :value="$subscription->max_branches ?? ''" placeholder="Unlimited" />
            </div>
            <x-ui.textarea name="plan_description" id="plan_description" label="Description" rows="2" :value="$subscription->plan_description ?? ''" placeholder="What this plan includes" />
        </x-ui.card>
    </x-ui.section>

    <div class="ui-section-label">Permissions</div>
    <div class="ui-toolbar">
        <div class="ui-toolbar__grow">
            <div class="ui-input-wrap">
                <i class="ui-input-icon fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" id="permSearchInput" class="ui-input" placeholder="Search permissions" aria-label="Search permissions">
            </div>
        </div>
        <div class="ui-btn-group">
            <x-ui.button variant="tinted" id="selectAllGlobal">Select all</x-ui.button>
            <x-ui.button variant="gray" id="clearAllGlobal">Clear</x-ui.button>
        </div>
    </div>

    <div class="grid items-start gap-4 md:grid-cols-2" id="categoriesContainer">
        @foreach($categories as $cat)
            @if($cat->permissions->count() > 0)
                <x-ui.check-group class="category-wrapper" data-category="{{ strtolower($cat->name) }}" :title="$cat->name" :count="$cat->permissions->count()"
                    :select-all="['class' => 'category-select-all', 'id' => 'cat_cb_'.$cat->id, 'data-cat-id' => $cat->id]">
                    @foreach($cat->permissions as $perm)
                        <div class="perm-item" data-perm-name="{{ strtolower($perm->name) }}">
                            <x-ui.check name="permissions[]" :value="$perm->id" id="s_perm_{{ $perm->id }}" input-class="perm-checkbox cat-perm-{{ $cat->id }}" :checked="in_array($perm->id, $assignedPermissions ?? [])">{{ $perm->name }}</x-ui.check>
                        </div>
                    @endforeach
                </x-ui.check-group>
            @endif
        @endforeach

        @if(isset($uncategorized) && $uncategorized->count() > 0)
            <x-ui.check-group class="category-wrapper" data-category="uncategorized" title="General" :count="$uncategorized->count()"
                :select-all="['class' => 'category-select-all', 'id' => 'cat_cb_0', 'data-cat-id' => 0]">
                @foreach($uncategorized as $perm)
                    <div class="perm-item" data-perm-name="{{ strtolower($perm->name) }}">
                        <x-ui.check name="permissions[]" :value="$perm->id" id="s_perm_{{ $perm->id }}" input-class="perm-checkbox cat-perm-0" :checked="in_array($perm->id, $assignedPermissions ?? [])">{{ $perm->name }}</x-ui.check>
                    </div>
                @endforeach
            </x-ui.check-group>
        @endif
    </div>

    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <x-ui.button variant="gray" :href="route('admin.subscriptions')">Cancel</x-ui.button>
        <x-ui.button type="submit" icon="fa-solid fa-check">{{ isset($subscription) ? 'Update plan' : 'Create plan' }}</x-ui.button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Category Select All Checkbox Handler
        document.querySelectorAll('.category-select-all').forEach(catCb => {
            const catId = catCb.getAttribute('data-cat-id');
            const catPerms = document.querySelectorAll('.cat-perm-' + catId);

            // Update category header checkbox on initial load
            const updateCatCbState = () => {
                const allChecked = Array.from(catPerms).length > 0 && Array.from(catPerms).every(cb => cb.checked);
                catCb.checked = allChecked;
            };
            updateCatCbState();

            catCb.addEventListener('change', function() {
                catPerms.forEach(cb => cb.checked = catCb.checked);
            });

            catPerms.forEach(cb => {
                cb.addEventListener('change', updateCatCbState);
            });
        });

        // Global Select All & Clear All
        const selectAllGlobal = document.getElementById('selectAllGlobal');
        const clearAllGlobal = document.getElementById('clearAllGlobal');

        if (selectAllGlobal) {
            selectAllGlobal.addEventListener('click', function() {
                document.querySelectorAll('.perm-checkbox, .category-select-all').forEach(cb => cb.checked = true);
            });
        }

        if (clearAllGlobal) {
            clearAllGlobal.addEventListener('click', function() {
                document.querySelectorAll('.perm-checkbox, .category-select-all').forEach(cb => cb.checked = false);
            });
        }

        // Real-time Permission Search Filter
        const searchInput = document.getElementById('permSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = searchInput.value.toLowerCase().trim();
                document.querySelectorAll('.perm-item').forEach(item => {
                    const permName = item.getAttribute('data-perm-name');
                    if (permName.includes(query)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        }
    });
</script>

@endsection
