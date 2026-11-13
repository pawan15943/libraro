@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-arrow-left" :href="route('library.branches', $branch->library_id)">Branches</x-ui.button>
@endsection

@section('content')
<x-ui.flash />

<x-ui.card :subtitle="$branch->display_name ?? $branch->name">
    <x-slot:title><span id="planFormTitle">{{ old('id') ? 'Edit Plan' : 'Add Plan' }}</span></x-slot:title>
    <x-slot:actions><x-ui.button size="sm" variant="tinted" icon="fa-solid fa-plus" id="togglePlanForm">Add Plan</x-ui.button></x-slot:actions>

    <form id="planForm" method="POST" action="{{ route('library.branch.plans.save', $branch->id) }}" style="{{ $errors->any() ? '' : 'display:none;' }}">
        @csrf
        <input type="hidden" name="id" id="plan_id_field" value="{{ old('id') }}">
        <div class="ui-fields sm:grid-cols-3">
            <x-ui.select name="type" id="plan_type" label="Type" :options="['MONTH' => 'Month', 'YEAR' => 'Year', 'DAY' => 'Day', 'WEEK' => 'Week']" placeholder="Select type" required />
            <x-ui.input type="number" name="plan_id" id="plan_number" label="Length" min="1" placeholder="e.g. 1 for 1 month" required />
            <x-ui.select name="monthdays" id="plan_monthdays" label="Days in a month" :options="['28' => '28 days', '30' => '30 days']" placeholder="Calendar-wise (automatic)" />
        </div>
        <div class="flex justify-end">
            <x-ui.button type="submit" id="savePlanBtn">{{ old('id') ? 'Update Plan' : 'Add Plan' }}</x-ui.button>
        </div>
    </form>
</x-ui.card>

<x-ui.card flush title="Plans" :subtitle="$branchPlans->count().' '.\Illuminate\Support\Str::plural('plan', $branchPlans->count())">
    @if($branchPlans->isEmpty())
        <x-ui.empty icon="fa-solid fa-layer-group" title="No plans yet" text="Add the first plan of this branch above." />
    @else
        <x-ui.datatable stack>
            <x-slot:head>
                <th>Plan</th>
                <th>Length</th>
                <th>Days in a month</th>
                <th>Status</th>
                <th class="is-end">Actions</th>
            </x-slot:head>
            @foreach($branchPlans as $plan)
                @php $isInactive = (bool) $plan->deleted_at; @endphp
                <tr>
                    <td class="ui-table__lead"><span class="ui-table__title">{{ $plan->name }}</span></td>
                    <td data-label="Length">{{ $plan->plan_id }} {{ ucfirst(strtolower($plan->type)) }}{{ (int) $plan->plan_id > 1 ? 's' : '' }}</td>
                    <td data-label="Days in a month">{{ $plan->monthdays ? $plan->monthdays.' days' : 'Calendar-wise' }}</td>
                    <td data-label="Status">
                        @if($isInactive)
                            <x-ui.badge tone="red" dot>Inactive</x-ui.badge>
                        @else
                            <x-ui.badge tone="green" dot>Active</x-ui.badge>
                        @endif
                    </td>
                    <td class="is-actions">
                        <div class="ui-actions" data-ax-done="1">
                            <x-ui.button size="sm" variant="gray" icon="fa-solid fa-pen" class="plan-edit"
                                data-id="{{ $plan->id }}" data-type="{{ $plan->type }}" data-plan-id="{{ $plan->plan_id }}" data-monthdays="{{ $plan->monthdays }}">Edit</x-ui.button>
                            <x-ui.button size="sm" variant="destructive" icon="fa-regular fa-trash-can" class="plan-delete"
                                data-url="{{ route('library.branch.plans.delete', $plan->id) }}" label="Delete plan" />
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-ui.datatable>
    @endif
</x-ui.card>

<script>
document.getElementById('togglePlanForm').addEventListener('click', function () {
    const form = document.getElementById('planForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    document.getElementById('plan_id_field').value = '';
    document.getElementById('planForm').reset();
    document.getElementById('planFormTitle').textContent = 'Add Plan';
    document.getElementById('savePlanBtn').textContent = 'Add Plan';
});

document.querySelectorAll('.plan-edit').forEach(function (link) {
    link.addEventListener('click', function () {
        document.getElementById('planForm').style.display = 'block';
        document.getElementById('plan_id_field').value = this.dataset.id;
        document.getElementById('plan_type').value = this.dataset.type;
        document.getElementById('plan_number').value = this.dataset.planId;
        document.getElementById('plan_monthdays').value = this.dataset.monthdays || '';
        document.getElementById('planFormTitle').textContent = 'Edit Plan';
        document.getElementById('savePlanBtn').textContent = 'Update Plan';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});

document.querySelectorAll('.plan-delete').forEach(function (link) {
    link.addEventListener('click', function () {
        const url = this.dataset.url;
        Swal.fire({
            title: 'Delete this plan?',
            text: 'This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete'
        }).then(function (result) {
            if (result.isConfirmed) {
                fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                }).then(function (res) { return res.json(); })
                  .then(function () { window.location.reload(); })
                  .catch(function () { Swal.fire('Error', 'Failed to delete plan.', 'error'); });
            }
        });
    });
});
</script>
@endsection
