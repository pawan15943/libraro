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
        @hasSection('rpt-actions')
            <div class="lpg-head-r">@yield('rpt-actions')</div>
        @endif
    </header>

    @yield('rpt-tabs')
    @yield('rpt-filters')

    <div class="rpt-body" id="rptBody" aria-live="polite">
        @yield('rpt-body')
    </div>

    @yield('rpt-after')
</div>

<script src="{{ asset('public/js/lb-reports.js') }}?v={{ @filemtime(public_path('js/lb-reports.js')) }}"></script>
@stack('rpt-scripts')
@endsection
