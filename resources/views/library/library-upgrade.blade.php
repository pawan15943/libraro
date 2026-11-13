@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-arrow-left" href="javascript:void(0)" onclick="window.history.back();">Back</x-ui.button>
@endsection

@section('content')
@php $ld = getLibraryData(); @endphp
<x-ui.flash />

@if($ld)
    @php
        $tx = $ld->latest_transaction ?? null;
        $daysLeft = ($tx && $tx->end_date) ? \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($tx->end_date), false) : null;
    @endphp

    <div class="grid gap-x-5 lg:grid-cols-2">
        <x-ui.section label="Library">
            <x-ui.card flush>
                <x-ui.kv>
                    <x-ui.kv-row label="Name">{{ $ld->library->library_name }}</x-ui.kv-row>
                    <x-ui.kv-row label="Joined">{{ $ld->library->join_date }}</x-ui.kv-row>
                    <x-ui.kv-row label="Mobile">@if($ld->library->library_mobile)+91 {{ $ld->library->library_mobile }}@endif</x-ui.kv-row>
                    <x-ui.kv-row label="Email">{{ $ld->library->email }}</x-ui.kv-row>
                    <x-ui.kv-row label="Status">
                        @if($ld->library->status == 1)
                            <x-ui.badge tone="green" dot>Active</x-ui.badge>
                        @else
                            <x-ui.badge tone="red" dot>Inactive</x-ui.badge>
                        @endif
                    </x-ui.kv-row>
                </x-ui.kv>
            </x-ui.card>
        </x-ui.section>

        <x-ui.section label="Current plan">
            <x-ui.card flush>
                <x-ui.kv>
                    <x-ui.kv-row label="Plan">{{ $ld->plan->name ?? '' }}</x-ui.kv-row>
                    <x-ui.kv-row label="Duration">@if($tx && $tx->month){{ $tx->month }} months @endif</x-ui.kv-row>
                    <x-ui.kv-row label="Price">@if($tx && $tx->amount !== null)₹{{ number_format((float) $tx->amount) }}@endif</x-ui.kv-row>
                    <x-ui.kv-row label="Booked on">{{ $tx->transaction_date ?? '' }}</x-ui.kv-row>
                    <x-ui.kv-row label="Starts on">{{ $tx->start_date ?? '' }}</x-ui.kv-row>
                    <x-ui.kv-row label="Ends on">
                        {{ $tx->end_date ?? '' }}
                        @if(!is_null($daysLeft))
                            <x-ui.badge :tone="$daysLeft > 0 ? 'green' : ($daysLeft < 0 ? 'red' : 'orange')" class="ml-1">{{ $daysLeft > 0 ? $daysLeft.' days left' : ($daysLeft < 0 ? 'Expired' : 'Ends today') }}</x-ui.badge>
                        @endif
                    </x-ui.kv-row>
                </x-ui.kv>
            </x-ui.card>
        </x-ui.section>
    </div>

    <x-ui.section label="Payments">
        <x-ui.card flush>
            @if(count($ld->all_transactions ?? []))
                <x-ui.table stack>
                    <x-slot:head><th>Transaction</th><th>Mode</th><th>Status</th><th>Date</th></x-slot:head>
                    @foreach($ld->all_transactions as $value)
                        <tr>
                            <td class="ui-table__lead ui-mono">{{ $value->transaction_id ?: '—' }}</td>
                            <td data-label="Mode">{{ $value->payment_mode }}</td>
                            <td data-label="Status">
                                @if($value->is_paid == 1)
                                    <x-ui.badge tone="green" dot>Paid</x-ui.badge>
                                @else
                                    <x-ui.badge tone="orange" dot>Pending</x-ui.badge>
                                @endif
                            </td>
                            <td data-label="Date" class="is-nowrap">{{ $value->transaction_date }}</td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @else
                <x-ui.empty icon="fa-regular fa-credit-card" title="No payments yet" />
            @endif
        </x-ui.card>
    </x-ui.section>

    <x-ui.section label="Change plan">
        <x-ui.card>
            <div class="ui-fields sm:grid-cols-2">
                <x-ui.select name="subscription" id="subscription" label="Plan" :options="collect($plans)->pluck('name', 'id')" :value="$ld->plan->id ?? null" placeholder="Select plan" required />
                <x-ui.select name="plan_id" id="plan_id" label="Billing" :options="['monthly_fees' => 'Monthly', 'yearly_fees' => 'Yearly']" placeholder="Select billing" required />
            </div>
        </x-ui.card>
    </x-ui.section>
@else
    <x-ui.card>
        <x-ui.empty icon="fa-solid fa-building-columns" title="No library selected" text="Open a library from the Libraries list to change its plan.">
            <x-ui.button size="sm" :href="route('library')">Libraries</x-ui.button>
        </x-ui.empty>
    </x-ui.card>
@endif
@endsection
