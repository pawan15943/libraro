@extends('layouts.library')
@section('content')
<link rel="stylesheet" href="{{ asset('public/css/custom-management.css') }}?v={{ time() }}">

{{--
    One Custom Management master per route (floors, plans, shifts...). The layout's
    breadcrumb + heading name the master; the sidebar switches between them.
--}}
@php
    $masters = ['floors', 'plans', 'shifts', 'prices', 'expenses', 'exams'];
    if ($canManageToggles) {
        $masters[] = 'toggles';
    }
    if (!in_array($activeTab, $masters, true)) {
        $activeTab = 'floors';
    }
@endphp

<div class="custom-mgmt-module" id="customMgmt"
     data-view="grid"
     data-active-tab="{{ $activeTab }}"
     data-csrf="{{ csrf_token() }}"
     data-store-url="{{ route('master.store') }}"
     data-delete-url="{{ route('master.delete', ':id') }}"
     data-price-url="{{ route('getPricePlanwiseMaster') }}"
     data-toggle-url="{{ route('branch.update.hidefield') }}">

    @if(session('error'))
        <div class="alert alert-danger cm-alert">{{ session('error') }}</div>
    @endif

    <div class="cm-pane-body" data-pane="{{ $activeTab }}">
        @include('master.custom-management.' . $activeTab)
    </div>

    @include('master.custom-management.modals')
</div>

<script src="{{ asset('public/js/custom-management.js') }}?v={{ time() }}"></script>
@endsection
