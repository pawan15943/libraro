{{-- Shell shared by every report: head, optional sibling tabs, filter bar, AJAX body. --}}
@extends('layouts.library')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-reports.css') }}?v={{ @filemtime(public_path('css/lb-reports.css')) }}">
@endpush

@section('content')
<div class="lpg rpt @yield('rpt-class')">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb">
                <a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" />
                @hasSection('rpt-crumb')
                    @yield('rpt-crumb')
                @elseif($__env->hasSection('rpt-hub'))
                    <span>Reports</span>
                @else
                    <a href="{{ route('reports.hub') }}">Reports</a><x-lb-icon name="chevron-right" /><span>@yield('rpt-name')</span>
                @endif
            </div>
            <h1>@yield('rpt-name')</h1>
            <p>@yield('rpt-desc')</p>
        </div>
        @php
            // counts and filters sit behind icons; filters in use keep the filter bar open
            $rptVt = array_values(array_filter([$__env->hasSection('rpt-tabs') ? 'counts' : null, $__env->hasSection('rpt-filters') ? 'filters' : null]));
            $rptVtActive = collect(request()->except(['page', 'sort_by', 'sort_order', 'search']))->filter(fn ($v) => $v !== null && $v !== '')->count();
        @endphp
        @if($rptVt || $__env->hasSection('rpt-actions'))
            <div class="lpg-head-r">
                @if($rptVt)@include('partials.view-toggles', ['vt' => $rptVt, 'vtActive' => $rptVtActive])@endif
                @yield('rpt-actions')
            </div>
        @endif
    </header>

    @hasSection('rpt-tabs')
        <div class="vt-wrap" data-vt="counts">@yield('rpt-tabs')</div>
    @endif
    @hasSection('rpt-filters')
        <div class="vt-wrap {{ $rptVtActive ? 'vt-on' : '' }}" data-vt="filters">@yield('rpt-filters')</div>
    @endif

    <div class="rpt-body" id="rptBody" aria-live="polite">
        @yield('rpt-body')
    </div>

    @yield('rpt-after')
</div>

<script src="{{ asset('public/js/lb-reports.js') }}?v={{ @filemtime(public_path('js/lb-reports.js')) }}"></script>
@stack('rpt-scripts')
@endsection
