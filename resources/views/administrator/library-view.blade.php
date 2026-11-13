@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-arrow-left" href="javascript:void(0)" class="hidden sm:inline-flex" onclick="window.history.back();">Back</x-ui.button>
    <x-ui.button variant="tinted" icon="fa-solid fa-code-branch" :href="route('library.branches', $library->id)">Branches</x-ui.button>
    <x-ui.button icon="fa-solid fa-credit-card" :href="route('library.payment', $library->id)">Make Payment</x-ui.button>
@endsection

@section('content')
@php
    use Carbon\Carbon;
    $fmtDate = fn ($d, $f = 'j M Y') => $d ? Carbon::parse($d)->format($f) : null;
    $tx = $library_transaction;
    $daysLeft = ($tx && $tx->end_date) ? Carbon::today()->diffInDays(Carbon::parse($tx->end_date), false) : null;
@endphp

{{-- Identity --}}
<x-ui.card>
    <div class="flex flex-wrap items-center gap-x-4 gap-y-3">
        <x-ui.avatar :name="$library->library_name" size="lg" />
        <div class="min-w-0 flex-1">
            <h2 class="ui-title-2 truncate">{{ $library->library_name }}</h2>
            <div class="ui-subhead mt-1">Joined {{ $fmtDate($library->created_at) }} · {{ $plan->name ?? 'No plan' }}</div>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($library->status == 1)
                <x-ui.badge tone="green" dot pill>Active</x-ui.badge>
            @else
                <x-ui.badge tone="red" dot pill>Inactive</x-ui.badge>
            @endif
            @if($library->deleted_at)
                <x-ui.badge tone="red" pill>Deleted {{ $fmtDate($library->deleted_at) }}</x-ui.badge>
            @endif
        </div>
    </div>
</x-ui.card>

<div class="grid gap-x-5 lg:grid-cols-2">
    {{-- Plan --}}
    <x-ui.section label="Current plan">
        <x-ui.card flush>
            <x-ui.kv>
                <x-ui.kv-row label="Plan">{{ $plan->name ?? '' }}</x-ui.kv-row>
                <x-ui.kv-row label="Duration">@if($tx && $tx->month){{ $tx->month }} {{ \Illuminate\Support\Str::plural('month', (int) $tx->month) }}@endif</x-ui.kv-row>
                <x-ui.kv-row label="Price">@if($tx && $tx->amount !== null)₹{{ number_format((float) $tx->amount) }}@endif</x-ui.kv-row>
                <x-ui.kv-row label="Booked on">{{ $tx ? $fmtDate($tx->transaction_date) : '' }}</x-ui.kv-row>
                <x-ui.kv-row label="Starts on">{{ $tx ? $fmtDate($tx->start_date) : '' }}</x-ui.kv-row>
                <x-ui.kv-row label="Ends on">
                    @if($tx && $tx->end_date)
                        {{ $fmtDate($tx->end_date) }}
                        @if($daysLeft > 0)
                            <x-ui.badge tone="green" class="ml-1">{{ $daysLeft }} days left</x-ui.badge>
                        @elseif($daysLeft < 0)
                            <x-ui.badge tone="red" class="ml-1">Expired</x-ui.badge>
                        @else
                            <x-ui.badge tone="orange" class="ml-1">Ends today</x-ui.badge>
                        @endif
                    @endif
                </x-ui.kv-row>
            </x-ui.kv>
        </x-ui.card>
    </x-ui.section>

    {{-- Owner & contact --}}
    <div>
        <x-ui.section label="Owner & contact">
            <x-ui.card flush>
                <x-ui.kv>
                    <x-ui.kv-row label="Owner">{{ $library->library_owner }}</x-ui.kv-row>
                    <x-ui.kv-row label="Mobile">@if($library->library_mobile)<a href="tel:+91{{ $library->library_mobile }}">+91 {{ $library->library_mobile }}</a>@endif</x-ui.kv-row>
                    <x-ui.kv-row label="Email">@if($library->email)<a href="mailto:{{ $library->email }}">{{ $library->email }}</a>@endif</x-ui.kv-row>
                </x-ui.kv>
            </x-ui.card>
        </x-ui.section>
        <x-ui.section label="Record">
            <x-ui.card flush>
                <x-ui.kv>
                    <x-ui.kv-row label="Created">{{ $fmtDate($library->created_at, 'j M Y, g:i A') }}</x-ui.kv-row>
                    <x-ui.kv-row label="Last changed">{{ $fmtDate($library->updated_at, 'j M Y, g:i A') }}</x-ui.kv-row>
                    <x-ui.kv-row label="Deleted">{{ $library->deleted_at ? $fmtDate($library->deleted_at, 'j M Y, g:i A') : 'No' }}</x-ui.kv-row>
                </x-ui.kv>
            </x-ui.card>
        </x-ui.section>
    </div>
</div>

{{-- Payments & renewals --}}
<x-ui.section label="Payments & renewals">
    <x-ui.card flush>
        @if(count($library_all_transaction))
            <x-ui.datatable stack>
                <x-slot:head>
                    <th>Plan</th>
                    <th>Starts</th>
                    <th>Ends</th>
                    <th class="is-num">Paid</th>
                    <th>Mode</th>
                    <th>Status</th>
                    <th>Transaction</th>
                    <th>Paid on</th>
                </x-slot:head>
                @foreach($library_all_transaction as $value)
                    <tr>
                        <td class="ui-table__lead">
                            <span class="ui-table__title">{{ $value->month }} {{ \Illuminate\Support\Str::plural('month', (int) $value->month) }}</span>
                        </td>
                        <td data-label="Starts" class="is-nowrap">{{ $fmtDate($value->start_date) ?? '—' }}</td>
                        <td data-label="Ends" class="is-nowrap">{{ $fmtDate($value->end_date) ?? '—' }}</td>
                        <td data-label="Paid" class="is-num">{{ $value->paid_amount !== null ? '₹'.number_format((float) $value->paid_amount) : '—' }}</td>
                        <td data-label="Mode">{{ $value->payment_mode == 1 ? 'Online' : 'Offline' }}</td>
                        <td data-label="Status">
                            @if($value->is_paid == 1)
                                <x-ui.badge tone="green" dot>Paid</x-ui.badge>
                            @else
                                <x-ui.badge tone="orange" dot>Pending</x-ui.badge>
                            @endif
                        </td>
                        <td data-label="Transaction" class="ui-mono ui-footnote">{{ $value->transaction_id ?: '—' }}</td>
                        <td data-label="Paid on" class="is-nowrap">{{ $fmtDate($value->transaction_date) ?? '—' }}</td>
                    </tr>
                @endforeach
            </x-ui.datatable>
        @else
            <x-ui.empty icon="fa-regular fa-credit-card" title="No payments yet" text="Payments and renewals of this library show up here.">
                <x-ui.button size="sm" :href="route('library.payment', $library->id)">Make Payment</x-ui.button>
            </x-ui.empty>
        @endif
    </x-ui.card>
</x-ui.section>
@endsection
