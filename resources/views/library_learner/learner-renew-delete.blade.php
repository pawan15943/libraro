@extends('layouts.library')

@section('title', 'Undo a renewal')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
@endpush

@section('content')

@php
    $renewDays = \App\Services\RenewTransactionDeleteService::DELETE_WINDOW_DAYS;
    $money = static fn ($v) => number_format((float) ($v ?? 0), 0);
    $search = request()->get('search');
    $faces = ['c1', 'c2', 'c3', 'c4', 'c5'];
    $fmtDate = static function ($d, $f = 'j M Y') {
        try { return $d ? \Carbon\Carbon::parse($d)->format($f) : null; } catch (\Throwable $e) { return null; }
    };
@endphp

@can('has-permission', 'Search Learner')
<div class="lpg lpg--narrow lrd">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><span>Undo a renewal</span></div>
            <h1>Undo a renewal</h1>
            <p>Renewed someone by mistake? Find them here and delete the renewal — their previous booking comes back as it was.</p>
        </div>
    </header>

    <div class="lrd-note">
        <span class="lpg-ic is-warn"><x-lb-icon name="clock-alert" /></span>
        <p><b>Only within {{ $renewDays }} days.</b> A renewal can be undone for {{ $renewDays }} days after it was recorded. After that it stays locked.</p>
    </div>

    <form action="{{ route('create.renew.delete.index') }}" method="GET" class="lrd-search">
        <label class="lpg-search @error('search') is-bad @enderror">
            <x-lb-icon name="search" />
            <input type="search" name="search" id="search-input" value="{{ $search }}" placeholder="Search by name, mobile or seat no" autocomplete="off" enterkeyhint="search">
        </label>
        <button type="submit" class="lp-btn"><x-lb-icon name="search" />Find</button>
    </form>
    @error('search')
        <p class="lrd-err">{{ $message }}</p>
    @enderror

    @if(!isset($learners))
        <div class="lpg-empty lrd-gap">
            <span><x-lb-icon name="rotate-ccw" /></span>
            <b>Search a learner to begin</b>
            <p>Only learners renewed in the last {{ $renewDays }} days can be reverted. Older renewals will show as locked.</p>
        </div>
    @elseif($learners->total() === 0)
        <div class="lpg-empty lrd-gap">
            <span><x-lb-icon name="search" /></span>
            <b>No renewal found</b>
            <p>No learner matching “{{ $search }}” has a renewal that can be reverted. Check the spelling, mobile or seat number.</p>
        </div>
    @else
        <div class="lpg-sec">
            Renewals found <span class="lpg-n">{{ $learners->total() }}</span>
            @if($learners->lastPage() > 1)<em>Page {{ $learners->currentPage() }} of {{ $learners->lastPage() }}</em>@endif
        </div>

        <div class="lrd-list">
            @foreach($learners as $i => $bundle)
                @php
                    $value = $bundle['row'];
                    $renewTx = $bundle['transaction'];
                    $canDel = $bundle['validation']['ok'];
                    $blockReason = $bundle['validation']['message'] ?? '';
                    $learnerId = (int) $value->id;

                    $shiftTime = null;
                    if (!empty($value->start_time) && !empty($value->end_time)) {
                        try {
                            $shiftTime = \Carbon\Carbon::parse($value->start_time)->format('g:i A') . ' – ' . \Carbon\Carbon::parse($value->end_time)->format('g:i A');
                        } catch (\Throwable $e) {
                            $shiftTime = trim($value->start_time . ' – ' . $value->end_time);
                        }
                    }
                    $seatLabel = $value->seat_no ? getSeatDisplayByMainNo($value->seat_no) : 'General';

                    $payMode = $value->payment_mode ?? null;
                    $payModeLabel = ($payMode === null || $payMode === '') ? null
                        : (is_numeric($payMode) ? ([1 => 'Online', 2 => 'Offline', 3 => 'Pay later'][(int) $payMode] ?? (string) $payMode) : ucfirst((string) $payMode));

                    $recordedAt = $renewTx && $renewTx->created_at ? \Carbon\Carbon::parse($renewTx->created_at) : null;
                    $lockAt = $recordedAt ? $recordedAt->copy()->addDays($renewDays) : null;
                    $left = $lockAt && $lockAt->isFuture() ? now()->diffForHumans($lockAt, ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 1]) : null;

                    $initials = collect(explode(' ', trim((string) $value->name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: '?';
                    $from = $fmtDate($value->plan_start_date);
                    $to = $fmtDate($value->plan_end_date);
                @endphp
                <article class="lpg-card lrd-card {{ $canDel ? '' : 'is-locked' }}">
                    <div class="lrd-who">
                        <span class="lp-face {{ $faces[$i % 5] }}">{{ $initials }}</span>
                        <div class="lpg-row-t">
                            <b>{{ ucwords(strtolower($value->name ?? '—')) }}</b>
                            <small>
                                <span>UID {{ $value->learner_no ?? '—' }}</span>
                                @if(!empty($value->mobile))<span>+91 {{ $value->mobile }}</span>@endif
                            </small>
                        </div>
                        <span class="lrd-seat"><x-lb-icon name="armchair" />{{ $seatLabel }}</span>
                    </div>

                    <div class="lrd-plan">
                        <div>
                            <small>Plan</small>
                            <b>{{ $value->plan_name ?? '—' }}</b>
                        </div>
                        <div>
                            <small>Shift</small>
                            <b>{{ $value->plan_type_name ?? '—' }}</b>
                            @if($shiftTime)<em>{{ $shiftTime }}</em>@endif
                        </div>
                        <div>
                            <small>Renewed period</small>
                            <b>{{ $from && $to ? $from . ' → ' . $to : '—' }}</b>
                        </div>
                    </div>

                    @if($renewTx)
                        <div class="lrd-money">
                            <div><small>Total</small><b>₹{{ $money($renewTx->total_amount) }}</b></div>
                            <div class="is-ok"><small>Paid</small><b>₹{{ $money($renewTx->paid_amount) }}</b></div>
                            <div class="{{ (float) $renewTx->pending_amount > 0 ? 'is-bad' : '' }}"><small>Pending</small><b>₹{{ $money($renewTx->pending_amount) }}</b></div>
                            <div><small>Locker / Disc.</small><b>₹{{ $money($renewTx->locker_amount) }} / ₹{{ $money($renewTx->discount_amount) }}</b></div>
                        </div>
                        <p class="lrd-meta">
                            <span><x-lb-icon name="receipt" />Paid {{ $fmtDate($renewTx->paid_date) ?? '—' }}</span>
                            @if($payModeLabel)<span><x-lb-icon name="wallet" />{{ $payModeLabel }}</span>@endif
                            @if($recordedAt)<span><x-lb-icon name="clock" />Recorded {{ $recordedAt->format('j M, g:i A') }}</span>@endif
                        </p>
                    @endif

                    <div class="lrd-foot">
                        @if($canDel && $renewTx)
                            <span class="lpg-tag is-warn"><x-lb-icon name="hourglass" />{{ $left ? $left . ' left to undo' : 'Can be undone' }}</span>
                            <form method="POST" action="{{ route('create.renew.delete.destroy', ['transaction' => $renewTx->id]) }}" class="renew-delete-confirm-form" data-swal-title="Delete this renewal?" data-swal-text="{{ ucwords(strtolower($value->name)) }}’s previous booking will be restored. This can’t be undone from here.">
                                @csrf
                                <input type="hidden" name="learner_id" value="{{ $learnerId }}">
                                <button type="submit" class="lp-btn lrd-del"><x-lb-icon name="trash" />Delete renewal</button>
                            </form>
                        @elseif($blockReason)
                            <span class="lpg-tag"><x-lb-icon name="lock" />Locked</span>
                            <p>{{ $blockReason }}</p>
                        @else
                            <span class="lpg-tag"><x-lb-icon name="lock" />Not available</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        @if($learners->lastPage() > 1)
            @php $learners->appends(request()->except('page')); @endphp
            <nav class="lpg-pages" aria-label="Pages">
                <a href="{{ $learners->previousPageUrl() ?: '#' }}" class="{{ $learners->onFirstPage() ? 'is-off' : '' }}" aria-label="Previous"><x-lb-icon name="chevron-left" /></a>
                @for ($p = 1; $p <= $learners->lastPage(); $p++)
                    <a href="{{ $learners->url($p) }}" class="{{ $learners->currentPage() == $p ? 'is-on' : '' }}">{{ $p }}</a>
                @endfor
                <a href="{{ $learners->nextPageUrl() ?: '#' }}" class="{{ $learners->hasMorePages() ? '' : 'is-off' }}" aria-label="Next"><x-lb-icon name="chevron-right" /></a>
            </nav>
        @endif
    @endif
</div>
@endcan

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form.renew-delete-confirm-form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (form.dataset.ok) return;
                e.preventDefault();
                var title = form.getAttribute('data-swal-title') || 'Confirm delete';
                var text = form.getAttribute('data-swal-text') || '';
                var go = function () {
                    form.dataset.ok = '1';
                    var b = form.querySelector('button');
                    if (b) b.classList.add('is-busy');
                    form.submit();
                };
                if (typeof Swal === 'undefined') {
                    if (window.confirm(title + (text ? '\n\n' + text : ''))) go();
                    return;
                }
                Swal.fire({
                    icon: 'warning', title: title, text: text,
                    showCancelButton: true, reverseButtons: true,
                    confirmButtonText: 'Yes, delete', cancelButtonText: 'Cancel',
                    confirmButtonColor: '#de3b3d'
                }).then(function (r) { if (r.isConfirmed) go(); });
            });
        });
    });
</script>

@endsection
