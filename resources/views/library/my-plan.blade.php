@extends('layouts.library')

@section('title', 'My Plan')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
@endpush

@section('content')

@php
    use Carbon\Carbon;

    $planName = $plan ? $plan->name : ($month?->plan ?? 'Standard Plan');
    $planSlug = strtolower(str_replace(' ', '', $planName));
    $tier = str_contains($planSlug, 'premium') ? 'premium' : (str_contains($planSlug, 'basic') ? 'basic' : 'standard');
    $tierIcon = ['premium' => 'crown', 'standard' => 'star', 'basic' => 'zap'][$tier];

    $premiumSub = \App\Models\Subscription::where('id', 3)->first();
    $subscribedPermissions = ($data && $data->subscription) ? $data->subscription->permissions->pluck('name')->toArray() : [];
    $allPermissions = $premiumSub ? $premiumSub->permissions : collect();
    $included = collect($allPermissions)->filter(fn ($p) => in_array($p->name, $subscribedPermissions))->values();
    $missing = collect($allPermissions)->reject(fn ($p) => in_array($p->name, $subscribedPermissions))->values();

    $currentLibType = (int) ($data->library_type ?? 1);
    $daysLeft = isset($librarydiffInDays) ? (int) $librarydiffInDays : 0;
    $isPlanActive = ($month && $month->status == 1) || (optional(getLibrary())->status == 1 && optional(getLibrary())->is_paid == 1);
    $canRenew = isset($librarydiffInDays) && $librarydiffInDays <= 5;

    $start = $month?->start_date ? Carbon::parse($month->start_date) : null;
    $end = $month?->end_date ? Carbon::parse($month->end_date) : null;
    $totalDays = $start && $end ? max(1, $start->diffInDays($end)) : 0;
    $usedPct = $totalDays ? max(0, min(100, round(($totalDays - max(0, $daysLeft)) / $totalDays * 100))) : 0;

    $cycle = !$month || !$month->month ? 'Subscription'
        : ($month->month == 12 ? 'Yearly' : ($month->month == 1 ? 'Monthly' : $month->month . ' months'));
    $tone = $daysLeft <= 0 ? 'bad' : ($daysLeft <= 5 ? 'warn' : 'ok');
@endphp

<div class="lpg lmp">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><span>My plan</span></div>
            <h1>My plan</h1>
            <p>Your Libraro subscription, validity and everything it includes.</p>
        </div>
        <div class="lpg-head-r">
            <a href="{{ route('library.transaction') }}" class="lp-btn lp-btn--ghost"><x-lb-icon name="receipt-text" />Invoices</a>
        </div>
    </header>

    <div class="lmp-grid">
        {{-- plan hero --}}
        <section class="lmp-hero t-{{ $tier }}">
            <div class="lmp-hero-top">
                <span class="lmp-tier"><x-lb-icon name="{{ $tierIcon }}" /></span>
                <div class="lmp-hero-t">
                    <small>Current plan</small>
                    <h2>{{ $planName }}</h2>
                </div>
                @if($isPlanActive)
                    <span class="lmp-state is-ok"><i></i>Active</span>
                @else
                    <span class="lmp-state is-bad"><i></i>Inactive</span>
                @endif
            </div>

            <div class="lmp-price">
                <b>₹{{ number_format($month->amount ?? 0) }}</b>
                <span>/ {{ strtolower($cycle) }}</span>
            </div>

            <div class="lmp-meter is-{{ $tone }}">
                <div class="lmp-meter-t">
                    <b>{{ $daysLeft > 0 ? $daysLeft . ' ' . \Illuminate\Support\Str::plural('day', $daysLeft) . ' left' : 'Plan expired' }}</b>
                    @if($end)<span>{{ $daysLeft > 0 ? 'Renews' : 'Ended' }} {{ $end->format('j M Y') }}</span>@endif
                </div>
                <div class="lmp-bar"><i style="width: {{ $usedPct }}%"></i></div>
            </div>

            <dl class="lmp-facts">
                <div><dt>Started</dt><dd>{{ $start ? $start->format('j M Y') : '—' }}</dd></div>
                <div><dt>Ends</dt><dd>{{ $end ? $end->format('j M Y') : '—' }}</dd></div>
                <div><dt>Billing</dt><dd>{{ $cycle }}</dd></div>
            </dl>

            @if($canRenew)
                <div class="lmp-acts">
                    <a href="{{ route('subscriptions.choosePlan', ['action' => 'renew']) }}" class="lp-btn lp-btn--white"><x-lb-icon name="refresh-cw" />Renew plan</a>
                    @if($currentLibType < 3)
                        <a href="{{ route('subscriptions.choosePlan', ['action' => 'upgrade']) }}" class="lp-btn lmp-up"><x-lb-icon name="arrow-up-right" />Upgrade</a>
                    @endif
                </div>
            @else
                <p class="lmp-note"><x-lb-icon name="info" />Renew and upgrade open 5 days before your plan ends.</p>
            @endif
        </section>

        {{-- features --}}
        <section class="lpg-card lmp-feat">
            <div class="lpg-card-h">
                <span class="lpg-ic is-ok"><x-lb-icon name="list-checks" /></span>
                <div>
                    <h2>What’s included</h2>
                    <p>{{ $included->count() }} of {{ collect($allPermissions)->count() }} features on your plan</p>
                </div>
            </div>

            @if(collect($allPermissions)->isEmpty())
                <div class="lpg-empty"><span><x-lb-icon name="list" /></span><b>No features listed</b></div>
            @else
                <label class="lpg-search lmp-search">
                    <x-lb-icon name="search" />
                    <input type="search" id="lmpSearch" placeholder="Search features" autocomplete="off">
                </label>
                <div class="lmp-wrap {{ collect($allPermissions)->count() > 14 ? 'is-clamped' : '' }}" id="lmpWrap">
                <ul class="lmp-list">
                    @foreach($included as $p)
                        <li><x-lb-icon name="circle-check" />{{ $p->name }}</li>
                    @endforeach
                </ul>

                @if($missing->isNotEmpty())
                    <div class="lpg-sec">More with Premium <span class="lpg-n">{{ $missing->count() }}</span></div>
                    <ul class="lmp-list is-off">
                        @foreach($missing as $p)
                            <li><x-lb-icon name="lock" />{{ $p->name }}</li>
                        @endforeach
                    </ul>
                @endif
                </div>
                <p class="lmp-none" id="lmpNone" hidden>No feature matches that.</p>
                @if(collect($allPermissions)->count() > 14)
                    <button type="button" class="lmp-more" id="lmpMore"><span>Show all {{ collect($allPermissions)->count() }} features</span><x-lb-icon name="chevron-down" /></button>
                @endif
            @endif
        </section>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var wrap = document.getElementById('lmpWrap');
        if (!wrap) return;
        var more = document.getElementById('lmpMore'), search = document.getElementById('lmpSearch');
        var items = wrap.querySelectorAll('li'), secs = wrap.querySelectorAll('.lpg-sec');
        if (more) more.addEventListener('click', function () {
            var open = wrap.classList.toggle('is-clamped') === false;
            more.classList.toggle('is-open', open);
            more.querySelector('span').textContent = open ? 'Show less' : 'Show all ' + items.length + ' features';
        });
        search.addEventListener('input', function () {
            var q = this.value.trim().toLowerCase(), n = 0;
            items.forEach(function (li) { var hit = !q || li.textContent.toLowerCase().indexOf(q) > -1; li.hidden = !hit; if (hit) n++; });
            secs.forEach(function (s) { var ul = s.nextElementSibling; s.hidden = ul && !ul.querySelector('li:not([hidden])'); });
            wrap.classList.toggle('is-searching', !!q);
            if (more) more.hidden = !!q;
            document.getElementById('lmpNone').hidden = n > 0;
        });
    });
</script>

@endsection
