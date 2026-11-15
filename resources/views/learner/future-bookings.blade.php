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
    $hasActiveFilters = request()->filled('search') || request()->filled('plan_id') || request()->filled('status') || request()->filled('seat_no') || request()->filled('is_paid');
    $hiddenFields = toggleHideField();
    $currentBranchName = getCurrentBranchName();
    $fbStatus = request('status');
    $fbPay = request('is_paid');
    $fbMore = (int) request()->filled('plan_id') + (int) ($fbPay !== null && $fbPay !== '') + (int) request()->filled('seat_no');
    $fbSort = request('sort_by') === 'seat_no' ? (request('sort_order') === 'desc' ? 'desc' : 'asc') : '';
    $fbInitials = fn ($name) => collect(explode(' ', trim((string) $name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: '?';
    $fbMoney = fn ($v) => '₹' . rtrim(rtrim(number_format((float) $v, 2, '.', ','), '0'), '.');
@endphp

<div class="lml" id="lmlRoot" data-base="{{ route('future.bookings') }}">

    <div class="lml-head">
        <div>
            <div class="lml-crumb">Members<x-lb-icon name="chevron-right" /><span>Future bookings</span></div>
            <h1 class="lml-title">Future bookings</h1>
            <p class="lml-sub">Members whose plan starts on an upcoming date.</p>
        </div>
        @php $vtActive = collect(request()->except(['page', 'search', 'view']))->filter(fn ($v) => $v !== null && $v !== '')->count(); @endphp
        <div class="lml-head-r">@include('partials.view-toggles', ['vt' => ['counts', 'shifts', 'filters'], 'vtActive' => $vtActive])</div>
    </div>

    <div id="lmlTop">
    @if(!in_array('24', $hiddenFields))
    <div class="lml-counts" data-vt="counts">
        <span class="lml-shiftcounts-l">Seats</span>
        <span class="lml-sc">Total<b>{{ $total_seats ?? 0 }}</b></span>
        <span class="lml-sc"><i style="background:oklch(0.62 0.15 155)"></i>Available<b>{{ $availble_seats ?? 0 }}</b></span>
        <span class="lml-sc"><i style="background:oklch(0.5 0.14 264)"></i>Booked<b>{{ $booked_seats ?? 0 }}</b></span>
        <span class="lml-sc"><i style="background:oklch(0.6 0.15 245)"></i>General<b>{{ $genral_seat ?? 0 }}</b></span>
        <span class="lml-sc"><i style="background:oklch(0.5 0.17 15)"></i>Extension<b>{{ $extended_seats ?? 0 }}</b></span>
        <span class="lml-sc"><i style="background:oklch(0.62 0.21 25)"></i>Expired<b>{{ $expired_seat ?? 0 }}</b></span>
    </div>
    @if(!empty($planTypeCounts))
    <div class="lml-shiftcounts" data-vt="shifts">
        <span class="lml-shiftcounts-l">By shift</span>
        @foreach($planTypeCounts as $plan)
        <span class="lml-sc" title="{{ $plan['name'] }}">{{ $plan['abbr'] }} · {{ $plan['name'] }}<b>{{ $plan['count'] }}</b></span>
        @endforeach
    </div>
    @endif
    @endif
    </div>

    {{-- Toolbar: search · status · sort · filters · layout · export --}}
    <form action="{{ route('future.bookings') }}" method="GET" class="lml-filter learner-filter-form" id="learnerFilterForm" role="search">
        @can('has-permission', 'Filter')
        <label class="lml-search">
            <x-lb-icon name="search" />
            <input type="search" name="search" id="search_learner_input" placeholder="Search by name, mobile or email" value="{{ request()->get('search') }}" autocomplete="off">
            <span class="lml-spin" aria-hidden="true"></span>
        </label>
        <label class="lml-pick {{ $vtActive ? 'vt-on' : '' }}" data-vt="filters">
            <x-lb-icon name="clock-3" />
            <select name="status" id="status" aria-label="Status">
                <option value="">All bookings</option>
                <option value="active" @selected($fbStatus == 'active')>Active</option>
                <option value="expired" @selected($fbStatus == 'expired')>Expired</option>
            </select>
            <x-lb-icon name="chevron-down" class="lml-pick-chev" />
        </label>
        @endcan
        <label class="lml-pick {{ $vtActive ? 'vt-on' : '' }}" data-vt="filters">
            <x-lb-icon name="arrow-up-down" />
            <select id="lmlSort" aria-label="Sort">
                <option value="" @selected($fbSort === '')>Seat no. · default</option>
                <option value="asc" @selected($fbSort === 'asc')>Seat no. · 1 → 99</option>
                <option value="desc" @selected($fbSort === 'desc')>Seat no. · 99 → 1</option>
            </select>
            <x-lb-icon name="chevron-down" class="lml-pick-chev" />
            <input type="hidden" name="sort_by" value="{{ $fbSort ? 'seat_no' : '' }}">
            <input type="hidden" name="sort_order" value="{{ $fbSort }}">
        </label>
        @can('has-permission', 'Filter')
        <div class="dropdown lml-more-f {{ $vtActive ? 'vt-on' : '' }}" data-vt="filters">
            <button type="button" class="lml-fbtn {{ $fbMore ? 'is-on' : '' }}" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="More filters" title="More filters"><x-lb-icon name="funnel" /><span>Filters</span>@if($fbMore)<em>{{ $fbMore }}</em>@endif</button>
            <div class="dropdown-menu dropdown-menu-end lml-fpanel">
                <div class="lml-fpanel-h">Filter bookings</div>
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
                        <option value="1" @selected($fbPay === '1')>Paid</option>
                        <option value="0" @selected($fbPay === '0')>Unpaid</option>
                    </select>
                </label>
                <label class="lml-ff"><span>Seat</span>
                    <select name="seat_no" id="seat_no" class="lp-sel">
                        <option value="">All seats</option>
                        @for($sn = 1; $sn <= $totalSeats; $sn++)
                        <option value="{{ $sn }}" @selected(request()->get('seat_no') == $sn)>Seat {{ getSeatDisplayShortFloorName($sn) }}</option>
                        @endfor
                    </select>
                </label>
                <div class="lml-fpanel-f">
                    <button type="button" id="clearFilter" class="lml-clear">Clear all</button>
                    <button type="submit" class="lp-btn lp-btn--sm">Apply</button>
                </div>
            </div>
        </div>
        @endcan
        <div class="lml-view" role="group" aria-label="Layout">
            <button type="button" data-view="grid" class="is-on" aria-pressed="true" title="Cards" aria-label="Cards"><x-lb-icon name="layout-grid" /></button>
            <button type="button" data-view="list" aria-pressed="false" title="List" aria-label="List"><x-lb-icon name="list" /></button>
        </div>
        @php
            $fbCanExport = \Illuminate\Support\Facades\Gate::allows('has-permission', 'Export Learners') && !in_array('22', $hiddenFields);
            $fbCanImport = \Illuminate\Support\Facades\Gate::allows('has-permission', 'Import Learners') && !in_array('11', $hiddenFields);
        @endphp
        @if($fbCanExport || $fbCanImport)
        <div class="dropdown">
            <button type="button" class="lml-iconbtn" data-bs-toggle="dropdown" aria-expanded="false" title="Export / import" aria-label="Export / import"><x-lb-icon name="download" /></button>
            <div class="dropdown-menu dropdown-menu-end lp-menu">
                @if($fbCanExport)
                <a href="{{ route('learners.export-csv') }}" class="lp-mi"><x-lb-icon name="file-spreadsheet" /><span>Export to CSV<small>Every member with plan details</small></span></a>
                @endif
                @if($fbCanImport)
                @if($fbCanExport)<hr>@endif
                <a href="{{ route('library.upload.form') }}" class="lp-mi"><x-lb-icon name="upload" /><span>Import members<small>Upload a CSV to the portal</small></span></a>
                @endif
            </div>
        </div>
        @endif
        @can('has-permission', 'Book Seat')
        @if(getCurrentBranch() != 0)
        <a href="javascript:;" class="lp-btn lml-add noseat_popup"><x-lb-icon name="plus" />Book seat</a>
        @endif
        @endcan
    </form>

    <div id="lmlLive" aria-live="polite">
    @if ($learners->total() == 0)
    <div class="lp-card lp-empty lml-empty no-data-found">
        <span><x-lb-icon name="calendar-clock" /></span>
        @if($hasActiveFilters)
        <b>No bookings found</b>
        No future bookings match the selected filters. Try changing or clearing the filters.
        <button type="button" class="lp-btn lp-btn--ghost lml-empty-clear" style="margin-top:8px"><x-lb-icon name="x" />Clear filters</button>
        @else
        <b>No future bookings</b>
        You haven’t added any members for upcoming dates yet.
        @if(getCurrentBranch() != 0)
        <a href="javascript:;" class="lp-btn lml-book-first" style="margin-top:8px"><x-lb-icon name="plus" />Book seat</a>
        @else
        <span>Select your branch first from the top bar.</span>
        @endif
        @endif
    </div>
    @else

    <div class="lml-bar">
        <p><b>{{ $learners->total() }}</b> booking{{ $learners->total() == 1 ? '' : 's' }} · {{ $learners->perPage() }} per page @if($learners->lastPage() > 1) · page {{ $learners->currentPage() }} of {{ $learners->lastPage() }}@endif</p>
    </div>

    <div class="lml-thead lml-thead--inline" aria-hidden="true"><span>Member</span><span>Shift · plan</span><span>Starts on</span><span>Payment</span><span>Status</span><span></span></div>
    <div id="learnerCardsList" class="learner-cards-list lml-grid is-active">
    @foreach($learners as $value)
        @include('learner.partials.lml-future-card')
    @endforeach
    </div>

    @include('learner.partials.lml-pages', ['p' => $learners])
    @endif
    </div>
</div>

@include('learner.partials.lml-page-script')
@endsection
