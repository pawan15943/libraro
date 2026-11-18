@extends('layouts.learner')

@section('title', 'Subscription')

@push('lr-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-learner-sub.css') }}?v={{ @filemtime(public_path('css/lb-learner-sub.css')) }}">
@endpush

@section('content')
@php
    $c = $customer ?: null;
    $p = $lr['plan'] ?? null;
    $dues = $lr['dues'] ?? ['amount' => 0, 'overdue' => false, 'due_date' => null];
    $phone = $lr['library']['phone'] ?? null;
    $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('j M Y') : '—';
    $money = fn ($v) => $v !== null && $v !== '' ? '₹' . number_format((float) $v) : '—';
    $paymentMode = [1 => 'Online', 2 => 'Offline', 3 => 'Pay later'][(int) ($c->payment_mode ?? 0)] ?? null;
    $requestNames = ['changePlan' => 'Change plan', 'upgradePlan' => 'Upgrade / downgrade plan', 'renewPlan' => 'Renew plan', 'closePlan' => 'Close plan', 'swapSeat' => 'Change seat'];
    [$stateText, $stateTone] = \App\Services\LearnerPortalService::stateLabel($p);
    $left = $p ? ($p['state'] === 'expired' ? 0 : max(0, 100 - (int) $p['progress'])) : 0;
    $chips = [];
    if ($p) {
        $chips[] = ['calendar-days', $p['start']->format('j M') . ' – ' . $p['end']->format('j M Y')];
        if ($p['time']) $chips[] = ['clock', $p['time']];
        if ($p['state'] === 'frozen') $chips[] = ['snowflake', $stateText]; // the ring already shows days left
    }
@endphp

<x-lr-hero icon="gem" kicker="My subscription" :title="$p ? ($p['seat'] ? 'Seat ' . $p['seat'] : 'General seat') : 'No active plan'"
           :sub="$p ? e($p['name'] . ($p['type'] ? ' · ' . $p['type'] : '')) : 'Visit the library desk to book a seat.'"
           :chips="$chips" :art="!$p" class="lx-sub-hero" data-state="{{ $p['state'] ?? 'none' }}">
    @if ($p)
        <div class="lx-score {{ in_array($p['state'], ['soon', 'grace']) ? 'is-warn' : ($p['state'] === 'expired' ? 'is-bad' : '') }}" style="--p:{{ $left }}">
            <span>
                @if ($p['state'] === 'expired')<b>Ended</b><small>{{ $p['end']->format('j M') }}</small>
                @elseif ($p['state'] === 'grace')<b>{{ max(0, $p['grace_left']) }}</b><small>extra days</small>
                @else<b>{{ $p['days_left'] }}</b><small>{{ $p['days_left'] == 1 ? 'day left' : 'days left' }}</small>
                @endif
            </span>
        </div>
    @endif
</x-lr-hero>

{{-- What needs attention --}}
<div class="lrs-alerts lx-alerts">
    @if ($dues['amount'] > 0)
        <div class="lr-alert {{ $dues['overdue'] ? 'bad' : 'warn' }}">
            <span class="lr-alert-ic"><x-lb-icon name="indian-rupee" /></span>
            <div>
                <b>₹{{ number_format($dues['amount']) }} {{ $dues['overdue'] ? 'payment is overdue' : 'is due' }}</b>
                <p>@if ($dues['due_date']){{ $dues['overdue'] ? 'It was due on' : 'Please pay by' }} {{ $dues['due_date']->format('j M Y') }}. @endif Pay at the library desk.</p>
            </div>
        </div>
    @endif

    @if ($p && in_array($p['state'], ['soon', 'grace', 'expired'], true) && !$lr['upcoming'])
        <div class="lr-alert {{ $p['state'] === 'expired' ? 'bad' : 'warn' }}">
            <span class="lr-alert-ic"><x-lb-icon name="refresh-cw" /></span>
            <div>
                <b>
                    @if ($p['state'] === 'expired') Your plan has ended
                    @elseif ($p['state'] === 'grace') Your plan ended on {{ $p['end']->format('j M') }}
                    @else Your plan ends {{ $p['days_left'] === 0 ? 'today' : 'on ' . $p['end']->format('j M') }}
                    @endif
                </b>
                <p>
                    @if ($p['state'] === 'grace') You can still come for {{ max(0, $p['grace_left']) }} more {{ max(0, $p['grace_left']) == 1 ? 'day' : 'days' }}. @endif
                    Renew to keep your seat.
                </p>
                <div class="lr-btns" style="margin-top:10px">
                    <a href="{{ route('learner.request') }}?type=renewPlan" class="lh-btn is-sm">Ask to renew</a>
                    @if ($phone)<a href="tel:{{ preg_replace('/\s+/', '', $phone) }}" class="lh-btn is-sm is-ghost"><x-lb-icon name="phone" /> Call library</a>@endif
                </div>
            </div>
        </div>
    @endif

    @if ($p && $p['state'] === 'frozen')
        <div class="lr-alert acc">
            <span class="lr-alert-ic"><x-lb-icon name="snowflake" /></span>
            <div>
                <b>Your plan is on hold</b>
                <p>@if ($p['frozen_since']) Since {{ $p['frozen_since']->format('j M Y') }}. @endif Your days are paused. Ask the library desk to start it again.</p>
            </div>
        </div>
    @endif

    @if ($lr['upcoming'] ?? null)
        <div class="lr-alert ok">
            <span class="lr-alert-ic"><x-lb-icon name="calendar-check" /></span>
            <div><b>Your next plan is ready</b><p>It starts on {{ $lr['upcoming']->format('j M Y') }}.</p></div>
        </div>
    @endif
</div>

<div class="lx-grid">
    @if ($c)
        <section class="lx-card">
            <div class="lx-card-head">
                <span class="lx-ic" data-tone="teal"><x-lb-icon name="calendar-days" /></span>
                <div><h2>Plan details</h2><p>Your current booking</p></div>
            </div>
            <div class="lx-tiles">
                <div><small>Plan</small><b>{{ $c->plan_name ?? '—' }}</b></div>
                <div><small>Shift</small><b>{{ $c->plan_type_name ?? '—' }}</b></div>
                @if ($p && $p['time'])<div><small>Time</small><b>{{ $p['time'] }}</b></div>@endif
                <div><small>Seat</small><b>{{ $p['seat'] ?? ($c->seat_no ? ($c->floor_seat_no ?? $c->seat_no) : 'General') }}</b></div>
                <div><small>Joined on</small><b>{{ $fmt($c->join_date ?? null) }}</b></div>
                <div><small>Starts</small><b>{{ $fmt($c->plan_start_date ?? null) }}</b></div>
                <div><small>Ends</small><b>{{ $fmt($c->plan_end_date ?? null) }}</b></div>
                @if (!empty($c->locker_no))<div><small>Locker</small><b>No. {{ $c->locker_no }}</b></div>@endif
            </div>
        </section>
    @endif

    @if ($transaction)
        <section class="lx-card">
            <div class="lx-card-head">
                <span class="lx-ic" data-tone="green"><x-lb-icon name="wallet" /></span>
                <div><h2>Payment for this plan</h2><p>@if ($paymentMode){{ $paymentMode }} · @endif{{ $fmt($transaction->paid_date) }}</p></div>
                <a href="{{ route('my-transactions') }}" class="lh-link">All payments <x-lb-icon name="chevron-right" /></a>
            </div>
            <div class="lx-pay">
                <div class="lx-pay-big"><small>Paid</small><b>{{ $money($transaction->paid_amount) }}</b></div>
                <dl class="lr-kv">
                    <div><dt>Plan price</dt><dd>{{ $money($transaction->total_amount) }}</dd></div>
                    @if ((float) $transaction->discount_amount > 0)<div><dt>Discount</dt><dd class="ok">− {{ $money($transaction->discount_amount) }}</dd></div>@endif
                    @if ((float) $transaction->locker_amount > 0)<div><dt>Locker</dt><dd>{{ $money($transaction->locker_amount) }}</dd></div>@endif
                    @if ((float) $transaction->pending_amount > 0)
                        <div><dt>Due</dt><dd class="{{ $dues['overdue'] ? 'bad' : 'warn' }}">{{ $money($transaction->pending_amount) }}@if($transaction->due_date) · by {{ $fmt($transaction->due_date) }}@endif</dd></div>
                    @endif
                </dl>
            </div>
        </section>
    @endif

    <section class="lx-card">
        <div class="lx-card-head">
            <span class="lx-ic" data-tone="indigo"><x-lb-icon name="layers" /></span>
            <div><h2>Plan history</h2><p>{{ $renew_detail->count() }} {{ \Illuminate\Support\Str::plural('plan', $renew_detail->count()) }}</p></div>
        </div>
        @if ($renew_detail->isEmpty())
            <div class="lx-empty"><x-lb-icon name="layers" /><p>No plans yet.</p></div>
        @else
            <ol class="lx-timeline">
                @foreach ($renew_detail->sortByDesc('plan_start_date') as $h)
                    <li class="{{ $h->status == 1 ? 'is-on' : '' }}">
                        <b>{{ $h->plan->name ?? 'Plan' }}@if($h->planType) · {{ $h->planType->name }}@endif</b>
                        <small>{{ $fmt($h->plan_start_date) }} – {{ $fmt($h->plan_end_date) }}</small>
                        @if ($h->status == 1)<span class="lx-pill is-ok">Current</span>@endif
                    </li>
                @endforeach
            </ol>
        @endif
    </section>

    <section class="lx-card">
        <div class="lx-card-head">
            <span class="lx-ic" data-tone="orange"><x-lb-icon name="send" /></span>
            <div><h2>Plan requests</h2><p>Renew, change plan or seat</p></div>
            <a href="{{ route('learner.request') }}" class="lh-link">New <x-lb-icon name="plus" /></a>
        </div>
        @forelse ($learner_request->sortByDesc('created_at')->take(5) as $r)
            <div class="lx-row">
                <span class="lx-row-ic" data-tone="{{ $r->request_status == 0 ? 'orange' : 'green' }}"><x-lb-icon name="{{ $r->request_status == 0 ? 'hourglass' : 'circle-check' }}" /></span>
                <div class="lx-row-main"><b>{{ $requestNames[$r->request_name] ?? $r->request_name }}</b><small>{{ $fmt($r->request_date ?? $r->created_at) }}</small></div>
                @if ($r->request_status == 0)<span class="lx-pill is-warn">Waiting</span>@else<span class="lx-pill is-ok">Done</span>@endif
            </div>
        @empty
            <div class="lx-empty"><x-lb-icon name="send" /><p>Want to renew or change your plan? Send a request.</p></div>
        @endforelse
    </section>
</div>
@endsection
