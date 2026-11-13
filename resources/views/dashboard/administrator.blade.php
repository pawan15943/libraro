@extends('layouts.admin')

@section('title', 'Dashboard')
@section('body_class', 'ax-custom-page')

@section('page_actions')
    @can('has-permission', ['manage-libraries'])
        <x-ui.button icon="fa-solid fa-plus" :href="route('library.create')">Add Library</x-ui.button>
    @endcan
    <x-ui.button variant="gray" icon="fa-solid fa-layer-group" :href="route('admin.subscriptions')">Plans</x-ui.button>
@endsection

@push('styles')
<style>
    /* sign-ups bar chart + paid ring — the only page-specific pieces */
    .axd-hello { font-size: 15px; color: var(--label-3); margin: -6px 0 20px; }
    .axd-hello b { color: var(--label); font-weight: 600; }
    .axd-bars { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 10px; align-items: end; height: 210px; }
    .axd-bar { display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; gap: 6px; min-width: 0; }
    .axd-bar__col { width: 100%; max-width: 32px; border-radius: 7px 7px 3px 3px; background: linear-gradient(180deg, #3b5bdb, var(--tint)); min-height: 3px; transition: opacity var(--dur-1) var(--ease); }
    .axd-bar__col.is-zero { background: var(--fill-3); }
    .axd-bar:hover .axd-bar__col { opacity: .78; }
    .axd-bar__num { font: 600 11.5px/1 var(--font-text); color: var(--label); font-variant-numeric: tabular-nums; }
    .axd-bar__num.is-zero { color: var(--label-4); font-weight: 500; }
    .axd-bar__mon { font-size: 11px; color: var(--label-3); }
    .axd-bar.is-now .axd-bar__mon { color: var(--tint-text); font-weight: 600; }
    .axd-ring { --p: 0; width: 64px; height: 64px; border-radius: 50%; background: conic-gradient(var(--tint) calc(var(--p) * 1%), var(--fill-3) 0); display: grid; place-items: center; flex: none; }
    .axd-ring span { width: 50px; height: 50px; border-radius: 50%; background: var(--surface); display: grid; place-items: center; font: 700 13px/1 var(--font-display); }
    .axd-health .ui-list { margin: 8px -20px -20px; border-top: .5px solid var(--sep); }
    .axd-plan { padding: 16px 20px; }
    .axd-plan + .axd-plan { border-top: .5px solid var(--sep); }
    @media (max-width: 575.98px) {
        .axd-bars { gap: 4px; height: 170px; }
        .axd-bar__num { font-size: 10px; }
        .axd-bar__mon { font-size: 9.5px; }
        .axd-plan { padding: 14px 16px; }
        .axd-health .ui-list { margin: 8px -16px -16px; }
    }
</style>
@endpush

@section('content')
@php
    $axHour = (int) now()->format('G');
    $axGreet = $axHour < 12 ? 'Good morning' : ($axHour < 17 ? 'Good afternoon' : 'Good evening');
    $axFirst = explode(' ', trim(Auth::guard('web')->user()->name ?? 'Admin'))[0];
    $axYearTotal = collect($monthlyProgress)->sum('count');
    $axPaidShare = $totalregistration > 0 ? round($paidregistration / $totalregistration * 100) : 0;
    $axMaxPlanRevenue = max(collect($planwiseData)->max('revenue') ?: 0, 1);
    $axYears = collect(range(date('Y') - 2, date('Y')))->map(fn ($yr) => ['label' => (string) $yr, 'href' => route('home', ['year' => $yr]), 'active' => (int) $selectedYear === $yr])->all();
@endphp

<p class="axd-hello">{{ $axGreet }}, <b>{{ $axFirst }}</b>. Here's how Libraro is doing on {{ now()->format('l, j F') }}.</p>

{{-- Key numbers --}}
<div class="ui-stats ui-stats--4">
    <x-ui.stat label="Libraries" :value="number_format($totalregistration)" :note="$axYearTotal.' joined in '.$selectedYear" icon="fa-solid fa-building-columns" :href="route('library')" />
    <x-ui.stat label="Paid" :value="number_format($paidregistration)" :note="$axPaidShare.'% of all libraries'" icon="fa-solid fa-circle-check" tone="green" :href="route('library', ['is_paid' => 1])" />
    <x-ui.stat label="Unpaid" :value="number_format($unpaidregistration)" :note="number_format($renewCount).' plans have expired'" icon="fa-solid fa-hourglass-half" tone="orange" :href="route('library', ['is_paid' => 0])" />
    <x-ui.stat label="Revenue" :value="'₹'.number_format($overallRevenue)" note="All paid subscriptions" icon="fa-solid fa-indian-rupee-sign" />
</div>

<div class="grid gap-4 mb-4 xl:grid-cols-3">
    {{-- Monthly sign-ups --}}
    <div class="xl:col-span-2">
        <x-ui.card class="h-full" title="New libraries" :subtitle="$axYearTotal.' sign-ups in '.$selectedYear">
            <x-slot:actions><x-ui.segmented size="sm" :links="$axYears" aria-label="Year" /></x-slot:actions>
            <div class="axd-bars">
                @foreach($monthlyProgress as $m)
                    @php $isNow = (int) $selectedYear === (int) date('Y') && $m['month_num'] === (int) date('n'); @endphp
                    <div class="axd-bar {{ $isNow ? 'is-now' : '' }}" title="{{ $m['month_name'] }}: {{ $m['count'] }}">
                        <span class="axd-bar__num {{ $m['count'] ? '' : 'is-zero' }}">{{ $m['count'] }}</span>
                        <div class="axd-bar__col {{ $m['count'] ? '' : 'is-zero' }}" style="height: {{ max($m['percentage'] * 0.8, 2) }}%"></div>
                        <span class="axd-bar__mon">{{ substr($m['month_name'], 0, 3) }}</span>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    </div>

    {{-- Paid share --}}
    <div>
        <x-ui.card class="h-full axd-health" title="Account health">
            <x-slot:actions><x-ui.button variant="plain" size="sm" :href="route('library')">Libraries <i class="fa-solid fa-chevron-right" style="font-size:10px"></i></x-ui.button></x-slot:actions>
            <div class="ui-inline" style="gap:14px">
                <div class="axd-ring" style="--p: {{ $axPaidShare }}"><span>{{ $axPaidShare }}%</span></div>
                <div>
                    <div class="ui-headline">Paying libraries</div>
                    <div class="ui-subhead">{{ number_format($paidregistration) }} of {{ number_format($totalregistration) }}</div>
                </div>
            </div>
            <div class="ui-list">
                <x-ui.row title="Active plans" :href="route('library', ['status' => 'active'])" chevron>
                    <x-slot:leading><span class="ui-row__icon" style="background:var(--green-bg);color:var(--green)"><i class="fa-solid fa-bolt"></i></span></x-slot:leading>
                </x-ui.row>
                <x-ui.row title="Expired plans" :subtitle="number_format($renewCount).' need renewal'" :href="route('library', ['status' => 'expired'])" chevron>
                    <x-slot:leading><span class="ui-row__icon" style="background:var(--red-bg);color:var(--red)"><i class="fa-solid fa-calendar-xmark"></i></span></x-slot:leading>
                </x-ui.row>
                <x-ui.row title="Waiting for payment" :subtitle="number_format($unpaidregistration).' libraries'" :href="route('library', ['is_paid' => 0])" chevron>
                    <x-slot:leading><span class="ui-row__icon" style="background:var(--orange-bg);color:var(--orange)"><i class="fa-solid fa-hourglass-half"></i></span></x-slot:leading>
                </x-ui.row>
            </div>
        </x-ui.card>
    </div>
</div>

{{-- Plans --}}
<x-ui.card class="mb-4" title="Plans" subtitle="Libraries and revenue by subscription plan" flush>
    <x-slot:actions><x-ui.button variant="plain" size="sm" :href="route('admin.subscriptions')">Manage plans <i class="fa-solid fa-chevron-right" style="font-size:10px"></i></x-ui.button></x-slot:actions>
    @forelse($planwiseData as $p)
        <div class="axd-plan">
            <div class="ui-between mb-2">
                <div class="min-w-0">
                    <div class="ui-headline">{{ $p['plan']->name }}</div>
                    <div class="ui-footnote">₹{{ number_format($p['plan']->monthly_fees) }}/month · ₹{{ number_format($p['plan']->yearly_fees) }}/year</div>
                </div>
                <div class="ui-headline" style="font-variant-numeric: tabular-nums">₹{{ number_format($p['revenue']) }}</div>
            </div>
            <div class="ui-meter"><i style="width: {{ round($p['revenue'] / $axMaxPlanRevenue * 100) }}%"></i></div>
            <div class="ui-inline mt-2" style="gap:6px">
                <x-ui.badge pill>{{ $p['total_registrations'] }} libraries</x-ui.badge>
                <x-ui.badge tone="green" dot pill>{{ $p['active_libraries'] }} active</x-ui.badge>
                <x-ui.badge tone="blue" pill>{{ $p['paid_libraries'] }} paid</x-ui.badge>
            </div>
        </div>
    @empty
        <x-ui.empty icon="fa-solid fa-layer-group" title="No plans yet" text="Create a subscription plan to start selling." />
    @endforelse
</x-ui.card>

<div class="grid gap-4 lg:grid-cols-2">
    {{-- Recent sign-ups --}}
    <div>
        <x-ui.card class="h-full" title="Recently joined" flush>
            <x-slot:actions><x-ui.button variant="plain" size="sm" :href="route('library')">See all <i class="fa-solid fa-chevron-right" style="font-size:10px"></i></x-ui.button></x-slot:actions>
            <div class="ui-list">
                @forelse($recent_registrations as $reg)
                    @php $regName = $reg->library_name ?? $reg->name ?? 'Library'; @endphp
                    <x-ui.row :title="$regName" :subtitle="($reg->subscription->name ?? 'No plan').' · '.optional($reg->created_at)->diffForHumans()" :href="route('library.show', $reg->id)" chevron>
                        <x-slot:leading><x-ui.avatar :name="$regName" /></x-slot:leading>
                        <x-slot:trailing>
                            @if(($reg->status ?? 0) == 1)
                                <x-ui.badge tone="green" dot pill>Active</x-ui.badge>
                            @else
                                <x-ui.badge pill>Inactive</x-ui.badge>
                            @endif
                        </x-slot:trailing>
                    </x-ui.row>
                @empty
                    <x-ui.empty icon="fa-solid fa-building-columns" title="No libraries yet" text="New sign-ups show up here." />
                @endforelse
            </div>
        </x-ui.card>
    </div>

    {{-- Renewals due --}}
    <div>
        <x-ui.card class="h-full" title="Renewals due" subtitle="Plans ending in the next 10 days" flush>
            <x-slot:actions><x-ui.button variant="plain" size="sm" :href="route('library', ['status' => 'active'])">See all <i class="fa-solid fa-chevron-right" style="font-size:10px"></i></x-ui.button></x-slot:actions>
            <div class="ui-list">
                @forelse($upcoming_registration as $up)
                    <x-ui.row :title="$up->library_name ?? $up->name ?? 'Library'" :subtitle="$up->subscription->name ?? 'No plan'" :href="route('library.show', $up->id)" chevron>
                        <x-slot:leading><span class="ui-row__icon" style="background:var(--orange-bg);color:var(--orange)"><i class="fa-solid fa-rotate"></i></span></x-slot:leading>
                        <x-slot:trailing>
                            @if(($up->is_paid ?? 0) == 1)
                                <x-ui.badge tone="green" pill>Paid</x-ui.badge>
                            @else
                                <x-ui.badge tone="red" pill>Unpaid</x-ui.badge>
                            @endif
                        </x-slot:trailing>
                    </x-ui.row>
                @empty
                    <x-ui.empty icon="fa-regular fa-calendar-check" title="All caught up" text="No plan ends in the next 10 days." />
                @endforelse
            </div>
        </x-ui.card>
    </div>
</div>
@endsection
