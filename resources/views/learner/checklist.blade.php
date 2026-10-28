@extends('layouts.library')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
@endpush

@section('content')

@php
    $ckInitials = fn ($name) => collect(explode(' ', trim((string) $name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: '?';
    $ckPaid = $learners->where('is_paid', 1)->count();
@endphp

<div class="lpg">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb">Members<x-lb-icon name="chevron-right" /><span>ID cards</span></div>
            <h1>Print ID cards</h1>
            <p>Pick the members you want cards for, choose one or both sides, and print them in one go.</p>
        </div>
    </header>

    @if(count($learners) > 0)
        <form method="POST" action="{{ route('learner.idcard.bulk') }}" target="_blank" id="bulkPrintForm">
            @csrf

            <div class="lpg-tools lck-tools">
                <label class="lpg-search">
                    <x-lb-icon name="search" />
                    <input type="search" id="lckSearch" placeholder="Search name, UID or mobile" autocomplete="off">
                </label>
                <label class="lck-all">
                    <span class="lck-box"><input type="checkbox" id="select_all" aria-label="Select all shown"><i></i></span>
                    Select all
                </label>
            </div>

            <h2 class="lpg-sec">Active members <span class="lpg-n" id="lckShown">{{ count($learners) }}</span><em>{{ $ckPaid }} paid · {{ count($learners) - $ckPaid }} unpaid</em></h2>

            <div class="lpg-list" id="lckList">
                @foreach($learners as $learner)
                    @php
                        $ckEnd = !empty($learner->plan_end_date) ? \Carbon\Carbon::parse($learner->plan_end_date)->format('d M Y') : '—';
                        $ckPic = $learner->profile_picture && file_exists(public_path($learner->profile_picture)) ? asset($learner->profile_picture) : null;
                    @endphp
                    <label class="lpg-row lck-row" data-q="{{ strtolower($learner->name . ' ' . $learner->learner_no . ' ' . $learner->mobile) }}">
                        <span class="lck-box"><input type="checkbox" name="learner_ids[]" value="{{ $learner->id }}" class="select_one"><i></i></span>
                        <span class="lck-ava">@if($ckPic)<img src="{{ $ckPic }}" alt="" loading="lazy">@else{{ $ckInitials($learner->name) }}@endif</span>
                        <span class="lpg-row-t">
                            <b>{{ $learner->name ?? 'N/A' }}</b>
                            <small><span>{{ $learner->learner_no }}</span><span>+91 {{ $learner->mobile }}</span></small>
                        </span>
                        <span class="lck-cols">
                            <span>{{ $learner->learner_no }}</span>
                            <span>+91 {{ $learner->mobile }}</span>
                            <span title="Father’s name">{{ $learner->father_name ?: '—' }}</span>
                            <span class="lpg-tag {{ $learner->is_paid == 1 ? 'is-ok' : 'is-bad' }}">{{ $learner->is_paid == 1 ? 'Paid' : 'Unpaid' }}</span>
                            <span>{{ $ckEnd }}</span>
                        </span>
                        <span class="lpg-row-r">
                            <span class="lpg-tag {{ $learner->is_paid == 1 ? 'is-ok' : 'is-bad' }}">{{ $learner->is_paid == 1 ? 'Paid' : 'Unpaid' }}</span>
                            <small style="font-size:12px;color:var(--lp-mut2)">Till {{ $ckEnd }}</small>
                        </span>
                    </label>
                @endforeach
                <div class="lpg-empty" id="lckNone" hidden style="border:0;border-radius:0">
                    <span><x-lb-icon name="search" /></span>
                    <b>No members match</b>
                    <p>Try a different name, UID or mobile number.</p>
                </div>
            </div>

            <div class="lck-bar">
                <div class="lck-bar-t">
                    <b><span id="selectedCountBadge">0</span> selected</b>
                    <small>Opens a print-ready sheet in a new tab</small>
                </div>
                <div class="lpg-radio is-seg" role="radiogroup" aria-label="Print sides">
                    <label><input type="radio" name="print_type" value="both" checked><span>Both sides</span></label>
                    <label><input type="radio" name="print_type" value="single"><span>Front only</span></label>
                </div>
                <button type="submit" class="lp-btn" id="lckPrint" disabled><x-lb-icon name="printer" />Print</button>
            </div>
        </form>
    @else
        <div class="lpg-empty">
            <span><x-lb-icon name="id-card" /></span>
            <b>No active members yet</b>
            <p>ID cards can be printed for members with an active seat in this branch.</p>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('bulkPrintForm');
        if (!form) return;
        var all = document.getElementById('select_all');
        var rows = Array.prototype.slice.call(form.querySelectorAll('.lck-row'));
        var count = document.getElementById('selectedCountBadge');
        var shown = document.getElementById('lckShown');
        var none = document.getElementById('lckNone');
        var print = document.getElementById('lckPrint');

        function visible() { return rows.filter(function (r) { return !r.hidden; }); }

        function sync() {
            var picked = 0;
            rows.forEach(function (r) {
                var on = r.querySelector('.select_one').checked;
                r.classList.toggle('is-on', on);
                if (on) picked++;
            });
            var vis = visible();
            var visOn = vis.filter(function (r) { return r.querySelector('.select_one').checked; }).length;
            count.textContent = picked;
            print.disabled = picked === 0;
            all.checked = vis.length > 0 && visOn === vis.length;
            all.indeterminate = visOn > 0 && visOn < vis.length;
        }

        all.addEventListener('change', function () {
            visible().forEach(function (r) { r.querySelector('.select_one').checked = all.checked; });
            sync();
        });
        form.addEventListener('change', function (e) {
            if (e.target.classList.contains('select_one')) sync();
        });

        document.getElementById('lckSearch').addEventListener('input', function () {
            var q = this.value.trim().toLowerCase();
            var n = 0;
            rows.forEach(function (r) {
                var hit = !q || r.dataset.q.indexOf(q) !== -1;
                r.hidden = !hit;
                if (hit) n++;
            });
            shown.textContent = n;
            none.hidden = n > 0;
            sync();
        });

        form.addEventListener('submit', function (e) {
            if (!form.querySelector('.select_one:checked')) {
                e.preventDefault();
                if (window.toastr) toastr.warning('Select at least one member to print.');
            }
        });
        sync();
    });
</script>
@endsection
