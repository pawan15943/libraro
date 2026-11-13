@extends('layouts.library')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-members.css') }}?v={{ @filemtime(public_path('css/lb-members.css')) }}" />
@endpush

@section('content')
<script>
    try { var el1 = document.getElementById('loaderone'); if (el1) el1.remove(); } catch(e) {}
</script>

<!-- Profile photo preview -->
<div id="imageViewModal" class="image-modal lml-photo" style="display:none;opacity:0;" aria-hidden="true">
    <div class="image-modal-content">
        <span class="close-modal" role="button" tabindex="0" aria-label="Close">&times;</span>
        <img src="" id="modalImage" alt="Profile photo preview">
    </div>
</div>

@php
    $hasActiveFilters = request()->filled('search') || request()->filled('plan_id') || request()->filled('is_paid') || request()->filled('status');
    $lhStatus = request('status');
    $lhPay = request('is_paid');
    $lhMore = (int) request()->filled('plan_id') + (int) (request('is_paid') !== null && request('is_paid') !== '');
    $lhInitials = fn ($name) => collect(explode(' ', trim((string) $name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: '?';
    $lhMoney = fn ($v) => '₹' . rtrim(rtrim(number_format((float) $v, 2, '.', ','), '0'), '.');
@endphp

<div class="lml" id="lmlRoot" data-base="{{ route('learnerHistory') }}">

    <div class="lml-head">
        <div>
            <div class="lml-crumb">Members<x-lb-icon name="chevron-right" /><span>History</span></div>
            <h1 class="lml-title">Members history</h1>
            <p class="lml-sub">Expired, closed and deleted seat bookings.</p>
        </div>
    </div>

    <div id="lmlTop"></div>

    {{-- Toolbar: search · status · filters · layout --}}
    @can('has-permission', 'Filter')
    <form action="{{ route('learnerHistory') }}" method="GET" class="lml-filter learner-filter-form" id="learnerFilterForm" role="search">
        <label class="lml-search">
            <x-lb-icon name="search" />
            <input type="search" name="search" id="search_learner_input" placeholder="Search by name, mobile or email" value="{{ request()->get('search') }}" autocomplete="off">
            <span class="lml-spin" aria-hidden="true"></span>
        </label>
        <label class="lml-pick">
            <x-lb-icon name="clock-3" />
            <select name="status" id="status" aria-label="Status">
                <option value="">All history</option>
                <option value="active" @selected($lhStatus === 'active')>Active</option>
                <option value="expired" @selected($lhStatus === 'expired')>Expired</option>
            </select>
            <x-lb-icon name="chevron-down" class="lml-pick-chev" />
        </label>
        <div class="dropdown lml-more-f">
            <button type="button" class="lml-fbtn {{ $lhMore ? 'is-on' : '' }}" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="More filters" title="More filters"><x-lb-icon name="funnel" /><span>Filters</span>@if($lhMore)<em>{{ $lhMore }}</em>@endif</button>
            <div class="dropdown-menu dropdown-menu-end lml-fpanel">
                <div class="lml-fpanel-h">Filter history</div>
                <label class="lml-ff"><span>Plan</span>
                    <select name="plan_id" id="plan_id" class="lp-sel">
                        <option value="">All plans</option>
                        @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" @selected(request()->get('plan_id') == $plan->id)>{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="lml-ff"><span>Payment</span>
                    <select name="is_paid" id="is_paid" class="lp-sel">
                        <option value="">Any payment</option>
                        <option value="1" @selected($lhPay === '1')>Paid</option>
                        <option value="0" @selected($lhPay === '0')>Unpaid</option>
                    </select>
                </label>
                <div class="lml-fpanel-f">
                    <button type="button" id="clearFilter" class="lml-clear">Clear all</button>
                    <button type="submit" class="lp-btn lp-btn--sm">Apply</button>
                </div>
            </div>
        </div>
        <div class="lml-view" role="group" aria-label="Layout">
            <button type="button" data-view="grid" class="is-on" aria-pressed="true" title="Cards" aria-label="Cards"><x-lb-icon name="layout-grid" /></button>
            <button type="button" data-view="list" aria-pressed="false" title="List" aria-label="List"><x-lb-icon name="list" /></button>
        </div>
    </form>
    @endcan

    <div id="lmlLive" aria-live="polite">
    @if ($learnerHistory->total() == 0)
    <div class="lp-card lp-empty lml-empty no-data-found">
        <span><x-lb-icon name="clock-3" /></span>
        @if($hasActiveFilters)
        <b>No members found</b>
        No members match the selected filters. Try changing or clearing the filters.
        <button type="button" class="lp-btn lp-btn--ghost lml-empty-clear" style="margin-top:8px"><x-lb-icon name="x" />Clear filters</button>
        @else
        <b>No members in history yet</b>
        You don’t have any members that are expired, deleted or closed yet.
        @if(getCurrentBranch() != 0)
        <a href="javascript:;" class="lp-btn noseat_popup" style="margin-top:8px"><x-lb-icon name="plus" />Book seat</a>
        @else
        <span>Select your branch first from the top bar.</span>
        @endif
        @endif
    </div>
    @else

    <div class="lml-bar">
        <p><b>{{ $learnerHistory->total() }}</b> record{{ $learnerHistory->total() == 1 ? '' : 's' }} · {{ $learnerHistory->perPage() }} per page @if($learnerHistory->lastPage() > 1) · page {{ $learnerHistory->currentPage() }} of {{ $learnerHistory->lastPage() }}@endif</p>
    </div>

    <div class="lml-thead lml-thead--inline" aria-hidden="true"><span>Member</span><span>Shift · plan</span><span>Plan duration</span><span>Refunded</span><span>Status</span><span></span></div>
    <div id="learnerCardsList" class="learner-cards-list lml-grid is-active">
    @foreach($learnerHistory as $value)
        @include('learner.partials.lml-history-card')
    @endforeach
    </div>

    @include('learner.partials.lml-pages', ['p' => $learnerHistory->appends(request()->all())])
    @endif
    </div>
</div>

@include('learner.partials.lml-page-script')
@endsection
