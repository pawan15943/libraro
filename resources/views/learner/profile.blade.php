@extends('layouts.learner')

@section('title', 'My profile')

@section('content')
@php
    $c = $customer;
    $p = $lr['plan'] ?? null;
    $hidden = toggleHideField();
    $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('j M Y') : '—';
    $requestNames = [
        'changePlan' => 'Change plan', 'upgradePlan' => 'Upgrade / downgrade plan', 'renewPlan' => 'Renew plan',
        'closePlan' => 'Close plan', 'swapSeat' => 'Change seat',
    ];
    $idProof = [1 => 'Aadhaar card', 2 => 'Driving licence', 3 => 'Other', 4 => 'PAN card', 5 => 'Voter ID'][(int) ($c->id_proof_name ?? 0)] ?? null;
    [$stateText, $stateTone] = \App\Services\LearnerPortalService::stateLabel($p);
    $libName = $lr['library']['name'] ?? 'Library';

    // detail tiles: [icon, label, value, tone]
    $info = array_values(array_filter([
        ['phone', 'Mobile', $c->mobile ? '+91 ' . $c->mobile : null, 'teal'],
        !in_array('1', $hidden) ? ['mail', 'Email', $c->email, 'indigo'] : null,
        !in_array('2', $hidden) && $c->dob ? ['cake', 'Date of birth', $fmt($c->dob), 'rose'] : null,
        ['user-round', "Father's name", $c->father_name, 'violet'],
        ['phone-call', 'Other mobile', $c->alternate_mobile, 'sky'],
        ['id-card', 'ID proof', $idProof, 'orange'],
        ['map-pin', 'Address', $c->address, 'green'],
    ], fn ($r) => $r && $r[2]));
@endphp

<section class="lh-goal lx-hero lx-me">
    <div class="lh-goal-stars" aria-hidden="true"></div>
    <div class="lx-me-avatar">{{ $lr['initials'] ?? 'L' }}<i></i></div>
    <div class="lh-goal-main">
        <span class="lh-k"><x-lb-icon name="user-round" />My profile</span>
        <h1>{{ $c->name }}</h1>
        <p>Student at <b>{{ $libName }}</b></p>
        <div class="lh-goal-meta">
            <span><x-lb-icon name="hash" />ID {{ $c->learner_no }}</span>
            @if ($p && $p['seat'])<span><x-lb-icon name="armchair" />Seat {{ $p['seat'] }}</span>@endif
            <span><x-lb-icon name="gem" />{{ $stateText }}</span>
        </div>
    </div>
    @if (Route::has('my-library-id'))
        <div class="lx-hero-side">
            <a href="{{ route('my-library-id') }}" class="lh-goal-edit lx-hero-btn"><x-lb-icon name="id-card" /> My ID card</a>
        </div>
    @endif
</section>

<div class="lx-grid">
    <section class="lx-card lx-span">
        <div class="lx-card-head">
            <span class="lx-ic" data-tone="teal"><x-lb-icon name="user-round" /></span>
            <div><h2>My details</h2><p>Something wrong? Ask the library desk to update it.</p></div>
        </div>
        <div class="lx-info">
            @foreach ($info as [$icon, $label, $value, $tone])
                <div class="{{ $label === 'Address' ? 'is-wide' : '' }}">
                    <span class="lx-row-ic" data-tone="{{ $tone }}"><x-lb-icon :name="$icon" /></span>
                    <div><small>{{ $label }}</small><b>{{ $value }}</b></div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="lx-card">
        <div class="lx-card-head">
            <span class="lx-ic" data-tone="orange"><x-lb-icon name="send" /></span>
            <div><h2>My requests</h2><p>Latest three</p></div>
            <a href="{{ route('learner.request') }}" class="lh-link">New <x-lb-icon name="plus" /></a>
        </div>
        @forelse ($learner_request->sortByDesc('created_at')->take(3) as $r)
            <div class="lx-row">
                <span class="lx-row-ic" data-tone="{{ $r->request_status == 0 ? 'orange' : ($r->request_status == 2 ? 'rose' : 'green') }}"><x-lb-icon name="{{ $r->request_status == 0 ? 'hourglass' : ($r->request_status == 2 ? 'circle-x' : 'circle-check') }}" /></span>
                <div class="lx-row-main"><b>{{ $requestNames[$r->request_name] ?? $r->request_name }}</b><small>{{ $fmt($r->request_date ?? $r->created_at) }}</small></div>
                @if ($r->request_status == 0)<span class="lx-pill is-warn">Waiting</span>@elseif ($r->request_status == 2)<span class="lx-pill is-bad">Not possible</span>@else<span class="lx-pill is-ok">Done</span>@endif
            </div>
        @empty
            <div class="lx-empty"><x-lb-icon name="send" /><p>No requests yet.</p></div>
        @endforelse
    </section>

    <section class="lx-card">
        <div class="lx-card-head">
            <span class="lx-ic" data-tone="violet"><x-lb-icon name="activity" /></span>
            <div><h2>Activity</h2><p>Changes to your seat and plan</p></div>
        </div>
        @if ($learnerlog->count() > 0)
            <ol class="lx-timeline">
                @foreach ($learnerlog->take(8) as $item)
                    <li>
                        <b>{{ $item['operation_type'] ?: 'Update' }}</b>
                        <small>{!! strip_tags($item['message'], '<b><strong>') !!}</small>
                        <em>{{ $item['date'] }} · {{ $item['time'] }}</em>
                    </li>
                @endforeach
            </ol>
        @else
            <div class="lx-empty"><x-lb-icon name="activity" /><p>No activity yet.</p></div>
        @endif
    </section>
</div>
@endsection
