@extends('layouts.admin')

@section('body_class', 'ax-custom-page crm-page')

@section('page_actions')
    @if($crm['can']['create'])
        <x-ui.button :href="route('crm.leads.create')" icon="fa-solid fa-plus">Add lead</x-ui.button>
    @endif
@endsection

@section('content')
@include('crm.partials.nav')
@include('crm.partials.flash')

@php
    $queueTabs = collect(\App\Http\Controllers\Crm\CrmLeadController::QUEUE_TABS)->map(fn ($label, $key) => [
        'label' => $label,
        'href' => route('crm.queue', ['tab' => $key] + request()->only('q')),
        'active' => $tab === $key,
        'count' => number_format($counts[$key]),
    ])->values()->all();
@endphp

<div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center">
    <x-ui.segmented :links="$queueTabs" class="self-start" />
    <form class="crm-search min-w-0 lg:ms-auto lg:w-72" method="GET" action="{{ route('crm.queue') }}" role="search">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="ui-input-wrap">
            <i class="ui-input-icon fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" name="q" value="{{ request('q') }}" class="ui-input" placeholder="Name, library, mobile, city" aria-label="Search the queue">
        </div>
    </form>
</div>

@if($leads->isEmpty())
    <x-ui.card>
        <x-ui.empty icon="fa-solid fa-mug-hot" :title="$tab === 'due' ? 'No follow-ups due' : 'Nothing here'"
            :text="$tab === 'due' ? 'You are on top of your calls. Pick a fresh lead from “Never called”.' : 'Try another tab or clear the search.'">
            @if($tab !== 'new' && $counts['new'])
                <x-ui.button :href="route('crm.queue', ['tab' => 'new'])" icon="fa-solid fa-phone">Call new leads ({{ $counts['new'] }})</x-ui.button>
            @endif
        </x-ui.empty>
    </x-ui.card>
@else
    <div class="grid gap-3 md:grid-cols-2 md:gap-4 2xl:grid-cols-3">
        @foreach($leads as $lead)
            @include('crm.partials.lead-card')
        @endforeach
    </div>
    {{ $leads->links('pagination::ui') }}
@endif

@include('crm.partials.sheets')
@endsection
