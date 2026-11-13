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
@endphp

<div class="lr-head">
    <div>
        <h1>Subscription</h1>
        <p>Your plan, seat and renewal in one place.</p>
    </div>
</div>

<div class="lr-grid is-main">
    <div class="lr-stack">
        @include('learner.partials.plan-hero', ['lr' => $lr])

        {{-- What needs attention --}}
        <div class="lrs-alerts">
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
                            <a href="{{ route('learner.request') }}?type=renewPlan" class="lr-btn is-sm">Ask to renew</a>
                            @if ($phone)<a href="tel:{{ preg_replace('/\s+/', '', $phone) }}" class="lr-btn is-sm is-ghost"><x-lb-icon name="phone" /> Call library</a>@endif
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

        @if ($c)
            <div class="lr-card">
                <div class="lr-card-title"><h2><x-lb-icon name="calendar-days" /> Plan details</h2></div>
                <dl class="lr-kv">
                    <div><dt>Plan</dt><dd>{{ $c->plan_name ?? '—' }}</dd></div>
                    <div><dt>Shift</dt><dd>{{ $c->plan_type_name ?? '—' }}</dd></div>
                    @if ($p && $p['time'])<div><dt>Time</dt><dd>{{ $p['time'] }}</dd></div>@endif
                    <div><dt>Seat</dt><dd>{{ $p['seat'] ?? ($c->seat_no ? ($c->floor_seat_no ?? $c->seat_no) : 'General') }}</dd></div>
                    <div><dt>Joined on</dt><dd>{{ $fmt($c->join_date ?? null) }}</dd></div>
                    <div><dt>Starts</dt><dd>{{ $fmt($c->plan_start_date ?? null) }}</dd></div>
                    <div><dt>Ends</dt><dd>{{ $fmt($c->plan_end_date ?? null) }}</dd></div>
                    @if (!empty($c->locker_no))<div><dt>Locker</dt><dd>No. {{ $c->locker_no }}</dd></div>@endif
                </dl>
            </div>
        @endif

        @if ($transaction)
            <div class="lr-card">
                <div class="lr-card-title"><h2><x-lb-icon name="wallet" /> Payment for this plan</h2><a href="{{ route('my-transactions') }}">All payments</a></div>
                <dl class="lr-kv">
                    <div><dt>Plan price</dt><dd>{{ $money($transaction->total_amount) }}</dd></div>
                    @if ((float) $transaction->discount_amount > 0)<div><dt>Discount</dt><dd class="ok">− {{ $money($transaction->discount_amount) }}</dd></div>@endif
                    @if ((float) $transaction->locker_amount > 0)<div><dt>Locker</dt><dd>{{ $money($transaction->locker_amount) }}</dd></div>@endif
                    <div><dt>Paid</dt><dd class="ok">{{ $money($transaction->paid_amount) }}</dd></div>
                    @if ((float) $transaction->pending_amount > 0)
                        <div><dt>Due</dt><dd class="{{ $dues['overdue'] ? 'bad' : 'warn' }}">{{ $money($transaction->pending_amount) }}@if($transaction->due_date) · by {{ $fmt($transaction->due_date) }}@endif</dd></div>
                    @endif
                    <div><dt>Paid on</dt><dd>{{ $fmt($transaction->paid_date) }}</dd></div>
                    @if ($paymentMode)<div><dt>Mode</dt><dd>{{ $paymentMode }}</dd></div>@endif
                </dl>
            </div>
        @endif
    </div>

    <div class="lr-stack">
        <div class="lr-card">
            <div class="lr-card-title"><h2><x-lb-icon name="layers" /> Plan history</h2></div>
            @if ($renew_detail->isEmpty())
                <div class="lr-empty"><p>No plans yet.</p></div>
            @else
                <div class="lr-rows">
                    @foreach ($renew_detail->sortByDesc('plan_start_date') as $h)
                        <div class="lr-row">
                            <span class="lr-row-ic {{ $h->status == 1 ? 'ok' : '' }}"><x-lb-icon name="calendar-range" /></span>
                            <div class="lr-row-main">
                                <b>{{ $h->plan->name ?? 'Plan' }}@if($h->planType) · {{ $h->planType->name }}@endif</b>
                                <p>{{ $fmt($h->plan_start_date) }} – {{ $fmt($h->plan_end_date) }}</p>
                            </div>
                            @if ($h->status == 1)<span class="lr-chip ok">Current</span>@endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="lr-card">
            <div class="lr-card-title"><h2><x-lb-icon name="send" /> Plan requests</h2><a href="{{ route('learner.request') }}">New request</a></div>
            @forelse ($learner_request->sortByDesc('created_at')->take(5) as $r)
                <div class="lr-row">
                    <div class="lr-row-main"><b>{{ $requestNames[$r->request_name] ?? $r->request_name }}</b><p>{{ $fmt($r->request_date ?? $r->created_at) }}</p></div>
                    @if ($r->request_status == 0)<span class="lr-chip warn">Waiting</span>@else<span class="lr-chip ok">Done</span>@endif
                </div>
            @empty
                <div class="lr-empty"><p>Want to renew or change your plan? Send a request.</p></div>
            @endforelse
        </div>
    </div>
</div>
@endsection
