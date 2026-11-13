@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button icon="fa-solid fa-plus" :href="route('admin.subscriptions.create')">New Plan</x-ui.button>
@endsection

@section('content')
<x-ui.flash />

<x-ui.card flush>
    <x-ui.datatable id="datatable" stack>
        <x-slot:head>
            <th>#</th>
            <th>Plan</th>
            <th class="is-num">Monthly</th>
            <th class="is-num">Yearly</th>
            <th>Seats</th>
            <th>Branches</th>
            <th>Permissions</th>
            <th>Status</th>
            <th class="is-end">Actions</th>
        </x-slot:head>
        @foreach($subscriptions as $key => $sub)
            <tr>
                <td class="ui-table__hide-sm ui-footnote">{{ $key + 1 }}</td>
                <td class="ui-table__lead">
                    <a href="{{ route('subscriptions.edit', $sub->id) }}" class="ui-table__title">{{ $sub->name }}</a>
                    @if($sub->plan_description)
                        <div class="ui-table__sub max-w-72 truncate" title="{{ $sub->plan_description }}">{{ $sub->plan_description }}</div>
                    @endif
                </td>
                <td data-label="Monthly" class="is-num is-nowrap" data-order="{{ $sub->monthly_fees }}"><span class="font-semibold">₹{{ number_format($sub->monthly_fees, 2) }}</span></td>
                <td data-label="Yearly" class="is-num is-nowrap" data-order="{{ $sub->yearly_fees }}">
                    @if($sub->yearly_fees > 0)
                        ₹{{ number_format($sub->yearly_fees, 2) }}
                    @else
                        <span class="text-label-4">—</span>
                    @endif
                </td>
                <td data-label="Seats">{{ $sub->max_seats ? number_format($sub->max_seats) : 'Unlimited' }}</td>
                <td data-label="Branches">{{ $sub->max_branches ?: 'Unlimited' }}</td>
                <td data-label="Permissions"><x-ui.badge tone="blue" pill><i class="fa-solid fa-key"></i>{{ $sub->permissions->count() }}</x-ui.badge></td>
                <td data-label="Status">
                    <a href="{{ route('subscriptions.toggle-status', $sub->id) }}" title="Turn {{ $sub->trashed() ? 'on' : 'off' }}">
                        @if(!$sub->trashed())
                            <x-ui.badge tone="green" dot>Active</x-ui.badge>
                        @else
                            <x-ui.badge tone="red" dot>Inactive</x-ui.badge>
                        @endif
                    </a>
                </td>
                <td class="is-actions">
                    <div class="ui-actions" data-ax-done="1">
                        <x-ui.button size="sm" variant="gray" icon="fa-solid fa-pen" :href="route('subscriptions.edit', $sub->id)">Edit</x-ui.button>
                        <x-ui.button size="sm" variant="destructive" icon="fa-regular fa-trash-can" :href="route('subscriptions.destroy', $sub->id)" label="Delete plan"
                            onclick="return confirm('Are you sure you want to delete this subscription plan permanently?');" />
                    </div>
                </td>
            </tr>
        @endforeach
    </x-ui.datatable>
</x-ui.card>

<script>
    $(document).ready(function() {
        if ($('#datatable').length && !$.fn.DataTable.isDataTable('#datatable')) {
            $('#datatable').DataTable({
                "pageLength": 15,
                "ordering": true,
                "responsive": true
            });
        }
    });
</script>

@endsection
