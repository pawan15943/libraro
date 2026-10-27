@extends('layouts.library')
@section('content')
<link rel="stylesheet" href="{{ asset('public/css/lib-admin.css') }}?v={{ time() }}">

<div class="lib-admin">
@if($branches->isEmpty())
    <div class="la-card la-empty">
        <span class="la-empty-icon"><i class="fa-solid fa-building"></i></span>
        <p class="la-empty-title">No branches yet</p>
        <p class="la-empty-text">Create your first branch to set seats, shifts and prices for it.</p>
        @can('has-permission','Add Branch Master')
            <a href="{{ route('branch.create') }}" class="la-btn la-btn-primary">
                <i class="fa-solid fa-plus"></i> Add Branch
            </a>
        @else
            <p class="la-note">You don't have permission to add branches.</p>
        @endcan
    </div>
@else
    <div class="la-toolbar">
        <p class="la-hint">Every branch has its own seats, shifts, prices and booking QR.</p>
        @can('has-permission','Add Branch Master')
            <a href="{{ route('branch.configure.create') }}" class="la-btn la-btn-primary">
                <i class="fa-solid fa-plus"></i> Add Branch
            </a>
        @endcan
    </div>

    <div class="la-grid is-branches">
        @foreach($branches as $key => $value)
            @php
                $displayName = $value->display_name ?: $value->name;
                $shiftCount = $value->planTypes->count();
            @endphp
            <article class="la-card la-branch">
                <div class="la-branch-head">
                    <span class="la-icon-tile tone-navy"><i class="fa-solid fa-building"></i></span>
                    <div class="la-branch-title">
                        <h4 class="la-branch-name">{{ $displayName }}</h4>
                        <div class="la-branch-sub">
                            <span>Branch {{ $key + 1 }}@if($value->display_name && $value->display_name !== $value->name) · {{ $value->name }}@endif</span>
                            <span class="la-pill is-dot {{ $value->deleted_at ? 'is-off' : 'is-on' }}">{{ $value->deleted_at ? 'Inactive' : 'Active' }}</span>
                        </div>
                    </div>

                    <div class="dropdown">
                        <button class="la-icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More options for {{ $displayName }}">
                            <i class="fa-solid fa-ellipsis"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end la-menu">
                            @can('has-permission','Add Library Seats')
                                <li><a class="dropdown-item" href="{{ route('seat.create', $value->id) }}"><i class="fa-solid fa-chair"></i> Total seats</a></li>
                            @endcan
                            @can('has-permission','Add Operating Hours')
                                <li><a class="dropdown-item" href="{{ route('hour.create', $value->id) }}"><i class="fa-solid fa-clock"></i> Operating hours</a></li>
                            @endcan
                            @can('has-permission','Add Extend Days')
                                <li><a class="dropdown-item" href="{{ route('extendDay.create', $value->id) }}"><i class="fa-solid fa-calendar-plus"></i> Extend days</a></li>
                            @endcan
                            @can('has-permission','Add Locker Amount')
                                <li><a class="dropdown-item" href="{{ route('lockeramount.create', $value->id) }}"><i class="fa-solid fa-lock"></i> Locker amount</a></li>
                            @endcan
                            @can('has-permission','Add Token Money')
                                <li><a class="dropdown-item" href="{{ route('tokenAmount.create', $value->id) }}"><i class="fa-solid fa-coins"></i> Token money</a></li>
                            @endcan
                            @if($value->uuid)
                                <li><a class="dropdown-item" href="{{ route('branch.qr.pdf', $value->uuid) }}" target="_blank"><i class="fa-solid fa-file-pdf"></i> Download QR PDF</a></li>
                            @endif
                        </ul>
                    </div>
                </div>

                <div class="la-branch-info">
                    <ul class="la-info-list">
                        <li><i class="fa-solid fa-location-dot"></i><span>{{ $value->library_address ?: 'Address not added yet' }}</span></li>
                        <li><i class="fa-solid fa-phone"></i><span>{{ $value->mobile ? '+91-' . $value->mobile : '—' }}</span></li>
                        <li><i class="fa-solid fa-envelope"></i><span>{{ $value->email ?: '—' }}</span></li>
                    </ul>
                    @if($value->uuid)
                        <a href="{{ route('branch.qr.pdf', $value->uuid) }}" target="_blank" class="la-qr" title="Open booking QR">
                            {!! QrCode::size(72)->generate(route('qr.branch', $value->uuid)) !!}
                            <span>Booking QR</span>
                        </a>
                    @endif
                </div>

                <div class="la-branch-stats">
                    <div class="la-mini-stat">
                        <span class="la-icon-tile tone-navy"><i class="fa-solid fa-chair"></i></span>
                        <div><span class="la-mini-stat-value">{{ $value->hour->seats ?? $value->seats ?? 0 }}</span><span class="la-mini-stat-label">Seats</span></div>
                    </div>
                    <div class="la-mini-stat">
                        <span class="la-icon-tile tone-green"><i class="fa-solid fa-calendar-days"></i></span>
                        <div><span class="la-mini-stat-value">{{ $value->extend_days ?? 0 }} <small>days</small></span><span class="la-mini-stat-label">Extend</span></div>
                    </div>
                    <div class="la-mini-stat">
                        <span class="la-icon-tile tone-amber"><i class="fa-solid fa-lock"></i></span>
                        <div><span class="la-mini-stat-value">₹{{ number_format($value->locker_amount ?? 0) }} <small>/ mo</small></span><span class="la-mini-stat-label">Locker</span></div>
                    </div>
                    <div class="la-mini-stat">
                        <span class="la-icon-tile tone-indigo"><i class="fa-solid fa-coins"></i></span>
                        <div><span class="la-mini-stat-value">₹{{ number_format($value->token_money ?? 0) }}</span><span class="la-mini-stat-label">Token money</span></div>
                    </div>
                </div>

                @if(isset($plans) && $plans->isNotEmpty())
                    <div class="la-disclosure">
                        <button type="button" class="la-disclosure-btn" data-bs-toggle="collapse" data-bs-target="#plansCollapse{{ $value->id }}" aria-expanded="false" aria-controls="plansCollapse{{ $value->id }}">
                            <span class="la-icon-tile tone-indigo"><i class="fa-solid fa-layer-group"></i></span>
                            Plans <span class="la-count">{{ $plans->count() }}</span>
                            <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                        </button>
                        <div class="collapse" id="plansCollapse{{ $value->id }}">
                            <ul class="la-rows">
                                @foreach($plans as $plan)
                                    <li>
                                        <div>
                                            <p class="la-row-title">{{ $plan->name }}</p>
                                            <p class="la-row-sub">{{ $plan->monthdays ? $plan->monthdays . ' days' : ($plan->plan_id . ' ' . strtolower($plan->type ?? '')) }}</p>
                                        </div>
                                        <span class="la-pill is-accent">{{ ucfirst(strtolower($plan->type ?? '—')) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <div class="la-disclosure">
                    <button type="button" class="la-disclosure-btn" data-bs-toggle="collapse" data-bs-target="#shiftsCollapse{{ $value->id }}" aria-expanded="false" aria-controls="shiftsCollapse{{ $value->id }}">
                        <span class="la-icon-tile tone-amber"><i class="fa-regular fa-clock"></i></span>
                        Shifts <span class="la-count">{{ $shiftCount }}</span>
                        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="collapse" id="shiftsCollapse{{ $value->id }}">
                        <ul class="la-rows">
                            @forelse($value->planTypes as $shift)
                                @php
                                    $start = !empty($shift->start_time) ? \Carbon\Carbon::parse($shift->start_time)->format('h:i A') : '—';
                                    $end = !empty($shift->end_time) ? \Carbon\Carbon::parse($shift->end_time)->format('h:i A') : '—';
                                @endphp
                                <li>
                                    <div>
                                        <p class="la-row-title">{{ $shift->name }}</p>
                                        <p class="la-row-sub">{{ $start }} – {{ $end }}</p>
                                    </div>
                                    <span class="la-row-value">₹{{ number_format($shift->price->price ?? 0) }}</span>
                                </li>
                            @empty
                                <li><p class="la-row-sub m-0">No shifts yet.</p></li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="la-branch-actions">
                    @can('has-permission','Edit Branch')
                        <a href="{{ route('branch.edit', $value->id) }}" class="la-btn la-btn-soft la-btn-sm"><i class="fa-solid fa-pen"></i> Edit</a>
                    @endcan
                    @can('has-permission','Add Library Seats')
                        <a href="{{ route('seat.create', $value->id) }}" class="la-btn la-btn-ghost la-btn-sm"><i class="fa-solid fa-chair"></i> Seats</a>
                    @endcan
                    <a href="{{ route('seats.history') }}" class="la-btn la-btn-ghost la-btn-sm"><i class="fa-solid fa-clock-rotate-left"></i> History</a>
                    @if($value->uuid)
                        <a href="{{ route('branch.qr.pdf', $value->uuid) }}" target="_blank" class="la-btn la-btn-ghost la-btn-sm"><i class="fa-solid fa-print"></i> Print QR</a>
                    @endif
                    <span class="la-spacer"></span>
                    <a href="javascript:void(0)" class="la-icon-btn is-danger delete-btn" data-id="{{ $value->id }}" data-table="Branch"
                       data-bs-toggle="tooltip" title="Delete branch" aria-label="Delete {{ $displayName }}">
                        <i class="fa-solid fa-trash"></i>
                    </a>
                </div>
            </article>
        @endforeach
    </div>
@endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.bootstrap && bootstrap.Tooltip) {
            document.querySelectorAll('.lib-admin [data-bs-toggle="tooltip"]').forEach(function (el) {
                bootstrap.Tooltip.getOrCreateInstance(el);
            });
        }
    });
</script>

@include('master.script')
@include('library.script')

@endsection
