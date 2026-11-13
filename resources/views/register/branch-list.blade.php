@extends('layouts.library')

@section('title', 'Branches')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-branchlist.css') }}?v={{ @filemtime(public_path('css/lb-branchlist.css')) }}">
@endpush

@section('content')
@php
    $canAdd = Gate::allows('has-permission', 'Add Branch Master');
    $currentBranch = (int) getCurrentBranch();
    $initials = fn ($n) => collect(explode(' ', trim((string) $n)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: 'B';
    $time = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('g:i A') : '—';
@endphp

<div class="lpg lbl">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><span>Branches</span></div>
            <h1>Branches</h1>
        </div>
        @if($canAdd && !$branches->isEmpty())
            <div class="lpg-head-r">
                <a href="{{ route('branch.configure.create') }}" class="lp-btn"><x-lb-icon name="plus" />Add branch</a>
            </div>
        @endif
    </header>

    @if($branches->isEmpty())
        <section class="lpg-card lbl-empty">
            <span class="lbl-empty-ic"><x-lb-icon name="building" /></span>
            <h2>Add your first branch</h2>
            <p>Seats, shifts, fees and the booking QR are set up per branch.</p>
            @if($canAdd)
                <a href="{{ route('branch.configure.create') }}" class="lp-btn"><x-lb-icon name="plus" />Add branch</a>
            @else
                <p class="lbl-note">You don't have permission to add branches.</p>
            @endif
        </section>
    @else
        <div class="lbl-grid">
            @foreach($branches as $key => $value)
                @php
                    $displayName = $value->display_name ?: $value->name;
                    $seats = $value->hour->seats ?? $value->seats ?? 0;
                    $hours = $value->hour->hour ?? null;
                    $isCurrent = $currentBranch === (int) $value->id;
                    $logo = $value->library_logo ? asset('public/' . $value->library_logo) : null;
                    $branchOn = (int) $value->status === 1 && !$value->deleted_at;
                    $shifts = $value->planTypes;
                    $activeShifts = $shifts->whereNull('deleted_at')->count();
                    $branchLearners = (int) (($learnerCounts ?? collect())[$value->id] ?? 0);
                @endphp
                <article class="lbl-card {{ $isCurrent ? 'is-current' : '' }}">
                    <div class="lbl-head">
                        <span class="lbl-ava" data-initials="{{ $initials($displayName) }}">@if($logo)<img src="{{ $logo }}" alt="" loading="lazy" onerror="this.parentNode.textContent = this.parentNode.dataset.initials">@else{{ $initials($displayName) }}@endif</span>
                        <div class="lbl-title">
                            <h3 data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $displayName }}">{{ $displayName }}</h3>
                            <div class="lbl-tags">
                                <span>Branch {{ $key + 1 }}</span>
                                @if($isCurrent)<span class="lbl-tag is-acc">Current</span>@endif
                                <span class="lbl-status {{ $branchOn ? 'is-on' : 'is-off' }}">{{ $branchOn ? 'Active' : 'Inactive' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="lbl-body">
                        <ul class="lbl-contact">
                            <li><x-lb-icon name="map-pin" /><span>{{ $value->library_address ?: 'Address not added yet' }}</span></li>
                            <li><x-lb-icon name="phone" /><span>{{ $value->mobile ? '+91 ' . $value->mobile : '—' }}</span></li>
                            <li><x-lb-icon name="mail" /><span>{{ $value->email ?: '—' }}</span></li>
                        </ul>
                        @if($value->uuid)
                            <a href="{{ route('branch.qr.pdf', $value->uuid) }}" target="_blank" class="lbl-qr noLoader" title="Download booking QR">
                                {!! QrCode::size(64)->margin(0)->generate(route('qr.branch', $value->uuid)) !!}
                                <span>Booking QR</span>
                            </a>
                        @endif
                    </div>

                    {{-- each number with its own edit link (permission-gated), two rows of three --}}
                    @php
                        $tiles = [
                            ['armchair', $seats, 'Seats', 'Add Library Seats', route('seat.create', $value->id)],
                            ['clock-3', $hours ? $hours . ' hrs' : '—', 'Open daily', 'Add Operating Hours', route('hour.create', $value->id)],
                            ['hourglass', (int) ($value->extend_days ?? 0) . ' days', 'Grace (Extend)', 'Add Extend Days', route('extendDay.create', $value->id)],
                            ['lock', '₹' . number_format($value->locker_amount ?? 0), 'Locker / month', 'Add Locker Amount', route('lockeramount.create', $value->id)],
                            ['hand-coins', '₹' . number_format($value->token_money ?? 0), 'Token money', 'Add Token Money', route('tokenAmount.create', $value->id)],
                            // the shifts page edits the current branch only
                            ['layers', $activeShifts, 'Shifts', null, $isCurrent ? route('library.configration') : null],
                        ];
                    @endphp
                    <div class="lbl-stats">
                        @foreach($tiles as [$ic, $val, $label, $perm, $url])
                            <div>
                                <span class="lbl-stat-top">
                                    <x-lb-icon :name="$ic" />
                                    @if($url && (!$perm || Gate::allows('has-permission', $perm)))
                                        <a href="{{ $url }}" class="lbl-edit" title="Edit {{ strtolower($label) }}" aria-label="Edit {{ strtolower($label) }} of {{ $displayName }}"><x-lb-icon name="pencil" /></a>
                                    @endif
                                </span>
                                <b>{{ $val }}</b><span>{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>

                    @if(isset($plans) && $plans->isNotEmpty())
                        <div class="lbl-plans">
                            <span class="lbl-plans-l"><x-lb-icon name="calendar-days" />Plans</span>
                            @foreach($plans as $plan)
                                <span class="lbl-chip">{{ $plan->name }}</span>
                            @endforeach
                            @if(!empty($value->fixed_billing_date))<span class="lbl-chip is-soft">Billing on {{ $value->fixed_billing_date }}</span>@endif
                            @can('has-permission', 'Add Plan Master')
                                <a href="{{ route('plan.index') }}" class="lbl-edit lbl-plans-edit" title="Edit plans" aria-label="Edit plans"><x-lb-icon name="pencil" /></a>
                            @endcan
                        </div>
                    @endif

                    <details class="lbl-shifts">
                        <summary><x-lb-icon name="clock" />Shifts &amp; fees<em>{{ $activeShifts }}</em><x-lb-icon name="chevron-down" /></summary>
                        <ul>
                            @forelse($shifts as $shift)
                                @php $shiftOn = !$shift->deleted_at; @endphp
                                <li class="{{ $shiftOn ? '' : 'is-off' }}">
                                    <div><b>{{ $shift->name }}</b><small>{{ $shift->isAnyTime() ? 'Library hours' : $time($shift->start_time) . ' – ' . $time($shift->end_time) }}</small></div>
                                    <span class="lbl-shift-r"><span class="lbl-status {{ $shiftOn ? 'is-on' : 'is-off' }}">{{ $shiftOn ? 'Active' : 'Inactive' }}</span><b>₹{{ number_format($shift->price->price ?? 0) }}</b></span>
                                </li>
                            @empty
                                <li class="is-empty">No shifts yet.</li>
                            @endforelse
                        </ul>
                    </details>

                    <div class="lbl-actions">
                        @can('has-permission','Edit Branch')
                            <a href="{{ route('branch.edit', $value->id) }}" class="lp-btn lbl-act-main"><x-lb-icon name="pencil" />Edit branch</a>
                        @endcan
                        @if($value->uuid)
                            <a href="{{ route('branch.qr.pdf', ['uuid' => $value->uuid, 'download' => 1]) }}" class="lp-btn lp-btn--ghost noLoader" title="Download booking QR"><x-lb-icon name="download" /><span>Download QR</span></a>
                            <a href="{{ route('branch.qr.pdf', $value->uuid) }}" target="_blank" class="lp-btn lp-btn--ghost noLoader" title="Print booking QR"><x-lb-icon name="printer" /><span>Print QR</span></a>
                        @endif
                        {{-- delete only an empty branch that isn't the open one or the only one (the delete checks the same) --}}
                        @if($branchLearners > 0)
                            <span class="lbl-inuse" data-bs-toggle="tooltip" title="{{ $branchLearners }} learner{{ $branchLearners === 1 ? '' : 's' }} in this branch, so it can't be deleted">In use</span>
                        @elseif(!$isCurrent && $branches->count() > 1)
                            <button type="button" class="lbl-del js-branch-del" data-id="{{ $value->id }}" data-name="{{ $displayName }}" data-bs-toggle="tooltip" title="Delete branch" aria-label="Delete {{ $displayName }}"><x-lb-icon name="trash" /></button>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // "Seats saved." etc. from a branch setting page
        try {
            var saved = sessionStorage.getItem('lbs_saved');
            if (saved) { sessionStorage.removeItem('lbs_saved'); if (window.lpToast) lpToast(saved, 'success'); else if (window.toastr) toastr.success(saved); }
        } catch (x) {}
        if (!window.bootstrap || !bootstrap.Tooltip) return;
        document.querySelectorAll('.lbl [data-bs-toggle="tooltip"]').forEach(function (el) {
            // the name only needs its tooltip when it is cut off
            if (el.tagName === 'H3' && el.scrollWidth <= el.clientWidth) { el.removeAttribute('title'); return; }
            bootstrap.Tooltip.getOrCreateInstance(el);
        });
    });
</script>

@include('lb.partials.confirm-modal')
<script>
    // Delete a branch: confirm first, then the same delete as before (master.delete, permanent)
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.js-branch-del');
        if (!btn) return;
        var name = btn.getAttribute('data-name');
        lbConfirm({
            title: 'Delete ' + name + '?',
            html: '<b>' + $('<i>').text(name).html() + '</b> will be deleted for good and won\'t show anywhere in your panel. This can\'t be undone.',
            confirm: 'Delete branch'
        }).then(function (ok) {
            if (!ok) return;
            btn.disabled = true;
            $.ajax({
                url: '{{ route("master.delete", ":id") }}'.replace(':id', btn.getAttribute('data-id')),
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}', table: 'Branch', deleteType: 'permanent' }
            }).done(function () {
                try { sessionStorage.setItem('lbs_saved', name + ' is deleted.'); } catch (x) {}
                location.reload();
            }).fail(function (xhr) {
                btn.disabled = false;
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Couldn\'t delete this branch. Please try again.';
                if (window.lpToast) lpToast(msg, 'error'); else if (window.toastr) toastr.error(msg); else alert(msg);
            });
        });
    });
</script>

@include('master.script')
@include('library.script')
@endsection
