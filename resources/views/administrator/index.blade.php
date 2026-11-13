@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button icon="fa-solid fa-plus" :href="route('library.create')">Add Library</x-ui.button>
@endsection

@section('content')
@php
    use Carbon\Carbon;
    $planOptions = collect($planslibrary)->pluck('name', 'id');
    $hasFilter = collect(request()->only(['plan_id', 'is_paid', 'status', 'search']))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
@endphp

<x-ui.flash />

{{-- Filters (folded away on phones until opened or in use) --}}
<div class="mb-3 sm:hidden">
    <x-ui.button variant="gray" icon="fa-solid fa-sliders" block data-bs-toggle="collapse" data-bs-target="#libFilters" aria-expanded="{{ $hasFilter ? 'true' : 'false' }}">Filters{{ $hasFilter ? ' · on' : '' }}</x-ui.button>
</div>
<x-ui.card id="libFilters" class="collapse sm:block {{ $hasFilter ? 'show' : '' }}">
    <form action="{{ route('library') }}" method="GET" class="grid gap-x-4 gap-y-0 sm:grid-cols-2 xl:grid-cols-[repeat(4,minmax(0,1fr))_auto] xl:items-end xl:[&>.ui-field]:mb-0">
        <x-ui.select name="plan_id" id="plan_id" label="Plan" :options="$planOptions" :value="request('plan_id')" placeholder="All plans" />
        <x-ui.select name="is_paid" id="is_paid" label="Payment" :options="['1' => 'Paid', '0' => 'Unpaid']" :value="request('is_paid')" placeholder="Paid and unpaid" />
        <x-ui.select name="status" id="status" label="Plan status" :options="['active' => 'Active', 'expired' => 'Expired']" :value="request('status')" placeholder="Active and expired" />
        <x-ui.input name="search" label="Search" icon="fa-solid fa-magnifying-glass" placeholder="Name, mobile or email" :value="request('search')" />
        <div class="ui-field flex gap-2">
            <x-ui.button type="submit" icon="fa-solid fa-filter" class="flex-1 xl:flex-none">Apply</x-ui.button>
            @if($hasFilter)
                <x-ui.button variant="gray" :href="route('library')" class="flex-1 xl:flex-none">Reset</x-ui.button>
            @endif
        </div>
    </form>
</x-ui.card>

{{-- List --}}
<x-ui.card flush title="All libraries" :subtitle="count($libraries).' '.\Illuminate\Support\Str::plural('library', count($libraries))">
    <x-ui.datatable id="datatable" stack class="ui-table--compact">
        <x-slot:head>
            <th>#</th>
            <th>Library</th>
            <th>Contact</th>
            <th>Plan</th>
            <th>Starts on</th>
            <th>Ends on</th>
            <th>App</th>
            <th>OTP</th>
            <th class="is-end">Actions</th>
        </x-slot:head>
        @foreach($libraries as $key => $value)
            @php
                $today = Carbon::today();
                $libraryplan = App\Models\Subscription::where('id', $value->library_type)->value('name');
                $libraryplanData = DB::table('library_transactions')->where('id', $value->latest_transaction_id)->first();
                $endDate = ($libraryplanData && $libraryplanData->end_date != null) ? Carbon::parse($libraryplanData->end_date) : null;
                $diffInDays = $endDate ? $today->diffInDays($endDate, false) : 0;
                $isPaid = !empty($libraryplanData) && $libraryplanData->is_paid == 1;
            @endphp
            <tr>
                <td class="ui-table__hide-sm ui-footnote">{{ $key + 1 }}</td>
                <td class="ui-table__lead">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="min-w-0">
                            <a href="{{ route('library.show', $value->id) }}" class="ui-table__title block truncate max-w-44" title="{{ $value->library_name }}">{{ $value->library_name }}</a>
                            <div class="mt-1"><x-ui.badge :tone="$isPaid ? 'green' : 'gray'" dot>{{ $isPaid ? 'Paid' : 'Not paid' }}</x-ui.badge></div>
                        </div>
                    </div>
                </td>
                <td data-label="Contact">
                    <div class="inline-flex items-center gap-1.5 max-w-48" title="{{ $value->email }}">
                        @if($value->email_verified_at != '')
                            <i class="fa-solid fa-circle-check text-ok" title="Email verified"></i>
                        @else
                            <i class="fa-solid fa-circle-xmark text-bad" title="Email not verified"></i>
                        @endif
                        <span class="truncate">{{ $value->email }}</span>
                    </div>
                    <div class="ui-table__sub">+91 {{ $value->library_mobile }}</div>
                </td>
                <td data-label="Plan" class="is-nowrap">
                    {{ $libraryplan ?? 'NA' }}
                    <div class="ui-table__sub">{{ $libraryplanData && $libraryplanData->month == 12 ? 'Yearly' : 'Monthly' }}</div>
                </td>
                <td data-label="Starts on" class="is-nowrap">
                    @if($libraryplanData && $libraryplanData->start_date != null)
                        {{ Carbon::parse($libraryplanData->start_date)->toFormattedDateString() }}
                    @else
                        <span class="text-label-4">NA</span>
                    @endif
                    <div class="ui-table__sub {{ $value->status == 1 ? 'text-ok' : '' }}">{{ $value->status == 1 ? 'Active' : 'Inactive' }}</div>
                </td>
                <td data-label="Ends on" class="is-nowrap">
                    {{ $endDate ? $endDate->toFormattedDateString() : 'NA' }}
                    <div class="ui-table__sub">
                        @if($diffInDays > 0)
                            <span class="text-ok">{{ $diffInDays }} days left</span>
                        @elseif($diffInDays < 0)
                            <span class="text-bad">Expired {{ abs($diffInDays) }} days ago</span>
                        @elseif($diffInDays == 0 && ($value->status > 0))
                            <span class="text-warn">Expires today</span>
                        @else
                            Not paid
                        @endif
                    </div>
                </td>
                <td data-label="App">
                    @if($value->is_app_verified)
                        <x-ui.badge tone="green"><i class="fa-solid fa-mobile-screen"></i>Verified</x-ui.badge>
                    @else
                        <x-ui.badge tone="orange"><i class="fa-solid fa-mobile-screen"></i>Unverified</x-ui.badge>
                    @endif
                    @if($value->app_verification_code)
                        <div class="ui-table__sub">Code <b class="ui-mono text-label">{{ $value->app_verification_code }}</b></div>
                    @endif
                </td>
                <td data-label="OTP" class="ui-mono">{{ $value->email_otp }}</td>
                <td class="is-actions">
                    <div class="ui-actions" data-ax-done="1">
                        <x-ui.button size="sm" variant="tinted" :href="route('library.show', $value->id)">View</x-ui.button>
                        <x-ui.menu label="More actions">
                            <x-ui.menu-item icon="fa-solid fa-code-branch" :href="route('library.branches', $value->id)">Branches</x-ui.menu-item>
                            <x-ui.menu-item icon="fa-solid fa-credit-card" :href="route('library.payment', $value->id)">Make payment</x-ui.menu-item>
                            <x-ui.menu-item icon="fa-solid fa-key" onclick="if (confirm('Generate a new app verification code for ' + @js($value->library_name) + '?')) document.getElementById('appCode{{ $value->id }}').submit();">New app code</x-ui.menu-item>
                            <div class="dropdown-divider"></div>
                            <x-ui.menu-item danger icon="fa-solid fa-user-xmark" class="delete-learners" data-id="{{ $value->id }}">Delete learners</x-ui.menu-item>
                            <x-ui.menu-item danger icon="fa-solid fa-eraser" class="delete-masters" data-id="{{ $value->id }}">Delete masters</x-ui.menu-item>
                            <x-ui.menu-item danger icon="fa-regular fa-trash-can" class="delete-library" data-id="{{ $value->id }}">Delete library</x-ui.menu-item>
                        </x-ui.menu>
                        <form id="appCode{{ $value->id }}" action="{{ route('library.generateAppVerificationCode', $value->id) }}" method="POST" hidden>@csrf</form>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-ui.datatable>
</x-ui.card>

@include('library.script')
@endsection
