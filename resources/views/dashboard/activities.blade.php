@extends('layouts.library')

@section('title', 'Activities Logs')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
@endpush

@section('content')

@php
    $acIcons = [
        'edit' => 'pencil', 'renewSeat' => 'refresh-cw', 'renewDelete' => 'refresh-cw', 'learnerUpgrade' => 'circle-arrow-up',
        'changePlan' => 'repeat', 'swapseat' => 'arrow-left-right', 'reactive' => 'rotate-ccw', 'closeSeat' => 'circle-x',
        'deleteSeat' => 'trash', 'restoreSeat' => 'rotate-cw', 'freezePlan' => 'snowflake', 'unfreezePlan' => 'sun', 'giftDays' => 'gift',
    ];
    $acFiltered = request()->hasAny(['search', 'operation', 'date']) && (request()->filled('search') || request()->filled('operation') || request()->filled('date'));
@endphp

<div class="lpg lac">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><span>Activity</span></div>
            <h1>Activity log</h1>
            <p>Every renewal, upgrade, swap, freeze and edit made on your members — newest first.</p>
        </div>
    </header>

    @if($filterLearnerId)
        <div class="lpg-filter">
            <x-lb-icon name="user-round" />
            <span>Showing activity for <b>{{ $filterLearnerName ?? ('Learner #' . $filterLearnerId) }}</b></span>
            <a href="{{ route('activities.all', request()->except(['learner_id', 'page'])) }}"><x-lb-icon name="x" />Clear</a>
        </div>
    @endif

    <form action="{{ route('activities.all') }}" method="GET" class="lpg-tools lac-tools" id="lacForm" role="search">
        @if($filterLearnerId)<input type="hidden" name="learner_id" value="{{ $filterLearnerId }}">@endif
        <label class="lpg-search">
            <x-lb-icon name="search" />
            <input type="search" name="search" id="search" placeholder="Search name, mobile or seat" value="{{ request('search') }}" autocomplete="off">
        </label>
        <label class="lpg-field is-select" title="Activity type">
            <x-lb-icon name="funnel" />
            <select name="operation" id="operation" aria-label="Activity type">
                <option value="">All activity</option>
                @foreach($operationOptions as $key => $label)
                    <option value="{{ $key }}" @selected(request('operation') == $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="lpg-field" title="Date">
            <x-lb-icon name="calendar-days" />
            <input type="date" name="date" id="date" value="{{ request('date') }}" aria-label="Date">
        </label>
        @if($acFiltered)
            <a href="{{ route('activities.all', array_filter(['learner_id' => $filterLearnerId])) }}" class="lpg-ibtn" title="Clear filters" aria-label="Clear filters"><x-lb-icon name="x" /></a>
        @endif
        <button type="submit" class="lp-btn" hidden>Search</button>
    </form>

    @forelse($activities as $dateHeader => $items)
        <section class="lac-day">
            <h2 class="lac-day-h"><x-lb-icon name="calendar-days" />{{ $dateHeader }}<span class="lpg-n">{{ count($items) }}</span></h2>
            <div class="lpg-list">
                @foreach($items as $item)
                    <article class="lpg-row lac-item" style="--c: {{ $item['color_code'] }}">
                        <span class="lac-ic"><x-lb-icon :name="$acIcons[$item['operation'] ?? ''] ?? 'activity'" /></span>
                        <div class="lpg-row-t">
                            <div class="lac-top">
                                @if(!empty($item['learner_id']))
                                    <a href="{{ route('learners.show', $item['learner_id']) }}" class="lac-who">{{ $item['learner_name'] }}</a>
                                @else
                                    <span class="lac-who">{{ $item['learner_name'] }}</span>
                                @endif
                                <span class="lac-op">{{ $item['operation_type'] }}</span>
                            </div>
                            <p class="lac-msg">{!! $item['message'] !!}</p>
                            <small><span>Seat {{ $item['seat'] }}</span><span>By {{ $item['updated_by_name'] }}</span></small>
                        </div>
                        <time class="lac-time">{{ $item['time'] }}</time>
                    </article>
                @endforeach
            </div>
        </section>
    @empty
        <div class="lpg-empty">
            <span><x-lb-icon name="activity" /></span>
            <b>No activity found</b>
            <p>{{ $acFiltered || $filterLearnerId ? 'Nothing matches these filters. Try another date or activity type.' : 'Renewals, upgrades, swaps and other member changes will show up here.' }}</p>
            @if($acFiltered)
                <a href="{{ route('activities.all', array_filter(['learner_id' => $filterLearnerId])) }}" class="lp-btn lp-btn--ghost"><x-lb-icon name="x" />Clear filters</a>
            @endif
        </div>
    @endforelse

    @if($logs->lastPage() > 1)
        <ul class="lpg-pages">
            <li><a href="{{ $logs->previousPageUrl() ?? '#' }}" class="{{ $logs->onFirstPage() ? 'is-off' : '' }}" aria-label="Previous page"><x-lb-icon name="chevron-left" /></a></li>
            @for($i = max(1, $logs->currentPage() - 2); $i <= min($logs->lastPage(), $logs->currentPage() + 2); $i++)
                <li><a href="{{ $logs->url($i) }}" class="{{ $logs->currentPage() == $i ? 'is-on' : '' }}" @if($logs->currentPage() == $i) aria-current="page" @endif>{{ $i }}</a></li>
            @endfor
            <li><a href="{{ $logs->nextPageUrl() ?? '#' }}" class="{{ $logs->hasMorePages() ? '' : 'is-off' }}" aria-label="Next page"><x-lb-icon name="chevron-right" /></a></li>
        </ul>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('lacForm');
        if (!form) return;
        form.querySelectorAll('select, input[type="date"]').forEach(function (el) {
            el.addEventListener('change', function () { form.submit(); });
        });
    });
</script>

@endsection
