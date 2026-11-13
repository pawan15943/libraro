@extends('layouts.learner')

@section('title', 'My profile')

@section('content')
@php
    $c = $customer;
    $p = $lr['plan'] ?? null;
    $hidden = toggleHideField();
    $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('j M Y') : '—';
    $money = fn ($v) => $v !== null && $v !== '' ? '₹' . number_format((float) $v) : '—';
    $requestNames = [
        'changePlan' => 'Change plan', 'upgradePlan' => 'Upgrade / downgrade plan', 'renewPlan' => 'Renew plan',
        'closePlan' => 'Close plan', 'swapSeat' => 'Change seat',
    ];
    $idProof = [1 => 'Aadhaar card', 2 => 'Driving licence', 3 => 'Other', 4 => 'PAN card', 5 => 'Voter ID'][(int) ($c->id_proof_name ?? 0)] ?? null;
    $paymentMode = [1 => 'Online', 2 => 'Offline', 3 => 'Pay later'][(int) ($c->payment_mode ?? 0)] ?? null;
@endphp

<div class="lr-head">
    <div>
        <h1>My profile</h1>
        <p>Your details, requests and activity.</p>
    </div>
</div>

<div class="lr-grid">
    <div class="lr-stack">
        <div class="lr-card">
            <div class="lr-card-title"><h2><x-lb-icon name="user-round" /> My details</h2></div>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
                <span class="lr-avatar is-lg">{{ $lr['initials'] ?? 'L' }}</span>
                <div><b style="font-size:17px">{{ $c->name }}</b><div style="color:var(--lp-mut2);font-size:13.5px">Student ID {{ $c->learner_no }}</div></div>
            </div>
            <dl class="lr-kv">
                <div><dt>Mobile</dt><dd>{{ $c->mobile ? '+91 ' . $c->mobile : '—' }}</dd></div>
                @if (!in_array('1', $hidden) && $c->email)<div><dt>Email</dt><dd>{{ $c->email }}</dd></div>@endif
                @if (!in_array('2', $hidden) && $c->dob)<div><dt>Date of birth</dt><dd>{{ $fmt($c->dob) }}</dd></div>@endif
                @if ($c->father_name)<div><dt>Father's name</dt><dd>{{ $c->father_name }}</dd></div>@endif
                @if ($c->alternate_mobile)<div><dt>Other mobile</dt><dd>{{ $c->alternate_mobile }}</dd></div>@endif
                @if ($c->address)<div><dt>Address</dt><dd>{{ $c->address }}</dd></div>@endif
                @if ($idProof)<div><dt>ID proof</dt><dd>{{ $idProof }}</dd></div>@endif
            </dl>
            <p class="lr-hint" style="margin-top:12px">Something wrong? Ask the library desk to update it.</p>
        </div>

        @if ($learner_request->isNotEmpty())
            <div class="lr-card">
                <div class="lr-card-title">
                    <h2><x-lb-icon name="send" /> My requests</h2>
                    <a href="{{ route('learner.request') }}">New request</a>
                </div>
                <div class="lr-rows">
                    @foreach ($learner_request->sortByDesc('created_at')->take(3) as $r)
                        <div class="lr-row">
                            <div class="lr-row-main">
                                <b>{{ $requestNames[$r->request_name] ?? $r->request_name }}</b>
                                <p>{{ $fmt($r->request_date ?? $r->created_at) }}</p>
                            </div>
                            @if ($r->request_status == 0)<span class="lr-chip warn">Waiting</span>@else<span class="lr-chip ok">Done</span>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($learnerlog->count() > 0)
            <details class="lr-card lr-more">
                <summary><h2 style="font-size:16px;font-weight:650;display:flex;gap:8px;align-items:center"><x-lb-icon name="activity" /> Activity</h2><x-lb-icon name="chevron-down" /></summary>
                <div class="lr-rows">
                    @foreach ($learnerlog->take(15) as $item)
                        <div class="lr-row">
                            <div class="lr-row-main">
                                <b>{{ $item['operation_type'] ?: 'Update' }}</b>
                                <p>{!! strip_tags($item['message'], '<b><strong>') !!}</p>
                            </div>
                            <div class="lr-row-end"><small>{{ $item['date'] }}</small><small>{{ $item['time'] }}</small></div>
                        </div>
                    @endforeach
                </div>
            </details>
        @endif
    </div>
</div>
@endsection
