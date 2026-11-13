@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-arrow-left" :href="route('library.branches', $branch->library_id)">Branches</x-ui.button>
@endsection

@section('content')
<x-ui.flash />

<x-ui.card :subtitle="$branch->display_name ?? $branch->name">
    <x-slot:title><span id="priceFormTitle">Add Plan Price</span></x-slot:title>
    <x-slot:actions><x-ui.button size="sm" variant="tinted" icon="fa-solid fa-plus" id="togglePriceForm">Add Plan Price</x-ui.button></x-slot:actions>

    <form id="priceForm" method="POST" action="{{ route('library.branch.prices.save', $branch->id) }}" style="display:none;">
        @csrf
        <input type="hidden" name="id" id="price_id_field" value="">
        <div class="ui-fields sm:grid-cols-3">
            <x-ui.select name="plan_id" id="price_plan_id" label="Plan" :options="collect($branchPlansForSelect)->pluck('name', 'id')" placeholder="Select plan" required />
            <x-ui.select name="plan_type_id" id="price_plan_type_id" label="Shift" :options="collect($branchPlanTypesForSelect)->pluck('name', 'id')" placeholder="Select shift" required />
            <x-ui.input type="number" name="price" id="price_amount" label="Price" prefix="₹" min="0" step="0.01" required />
        </div>
        <div class="flex justify-end">
            <x-ui.button type="submit" id="savePriceBtn">Add Plan Price</x-ui.button>
        </div>
    </form>
</x-ui.card>

<x-ui.card flush title="Prices" :subtitle="$prices->count().' '.\Illuminate\Support\Str::plural('price', $prices->count())">
    @if($prices->isEmpty())
        <x-ui.empty icon="fa-solid fa-tags" title="No prices yet" text="Add a price for each plan and shift above." />
    @else
        <x-ui.datatable stack>
            <x-slot:head>
                <th>Plan</th>
                <th>Shift</th>
                <th class="is-num">Price</th>
                <th>Status</th>
                <th class="is-end">Actions</th>
            </x-slot:head>
            @foreach($prices as $price)
                @php
                    $isInactive = (bool) $price->deleted_at;
                    $planDuration = optional($price->plan)->name ?? '1 Month';
                    $shiftName = optional($price->planType)->name ?? '—';
                @endphp
                <tr>
                    <td class="ui-table__lead"><span class="ui-table__title">{{ $planDuration }}</span></td>
                    <td data-label="Shift">{{ $shiftName }}</td>
                    <td data-label="Price" class="is-num"><span class="font-semibold">₹{{ number_format((float) ($price->price ?? 0)) }}</span></td>
                    <td data-label="Status">
                        @if($isInactive)
                            <x-ui.badge tone="red" dot>Inactive</x-ui.badge>
                        @else
                            <x-ui.badge tone="green" dot>Active</x-ui.badge>
                        @endif
                    </td>
                    <td class="is-actions">
                        <div class="ui-actions" data-ax-done="1">
                            <x-ui.button size="sm" variant="gray" icon="fa-solid fa-pen" class="price-edit"
                                data-id="{{ $price->id }}" data-plan-id="{{ $price->plan_id }}" data-plan-type-id="{{ $price->plan_type_id }}" data-price="{{ $price->price }}">Edit</x-ui.button>
                            <x-ui.button size="sm" variant="destructive" icon="fa-regular fa-trash-can" class="price-delete"
                                data-url="{{ route('library.branch.prices.delete', $price->id) }}" label="Delete price" />
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-ui.datatable>
    @endif
</x-ui.card>

<script>
function resetPriceForm() {
    document.getElementById('price_id_field').value = '';
    document.getElementById('priceForm').reset();
    document.getElementById('priceFormTitle').textContent = 'Add Plan Price';
    document.getElementById('savePriceBtn').textContent = 'Add Plan Price';
}

document.getElementById('togglePriceForm').addEventListener('click', function () {
    const form = document.getElementById('priceForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    resetPriceForm();
});

document.querySelectorAll('.price-edit').forEach(function (link) {
    link.addEventListener('click', function () {
        document.getElementById('priceForm').style.display = 'block';
        document.getElementById('price_id_field').value = this.dataset.id;
        document.getElementById('price_plan_id').value = this.dataset.planId;
        document.getElementById('price_plan_type_id').value = this.dataset.planTypeId;
        document.getElementById('price_amount').value = this.dataset.price;
        document.getElementById('priceFormTitle').textContent = 'Edit Plan Price';
        document.getElementById('savePriceBtn').textContent = 'Update Plan Price';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});

document.querySelectorAll('.price-delete').forEach(function (link) {
    link.addEventListener('click', function () {
        const url = this.dataset.url;
        Swal.fire({
            title: 'Delete this plan price?',
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
                  .catch(function () { Swal.fire('Error', 'Failed to delete plan price.', 'error'); });
            }
        });
    });
});
</script>
@endsection
