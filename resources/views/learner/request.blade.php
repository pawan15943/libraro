@extends('layouts.learner')

@section('title', 'Requests')

@section('content')
@php
    $types = [
        'renewPlan' => ['Renew plan', 'refresh-cw'],
        'changePlan' => ['Change plan', 'repeat'],
        'upgradePlan' => ['Upgrade / downgrade', 'arrow-up-down'],
        'swapSeat' => ['Change seat', 'armchair'],
        'closePlan' => ['Close plan', 'circle-x'],
    ];
    $picked = old('request_name', request('type'));
    $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('j M Y') : '';
@endphp

<div class="lr-head">
    <div>
        <h1>Requests</h1>
        <p>Ask the library to renew, change your plan or move your seat.</p>
    </div>
</div>

<div class="lr-grid is-main">
    <div class="lr-stack">
        <form action="{{ route('requaet.store') }}" method="POST" class="lr-card lr-form" data-lr-form novalidate>
            @csrf
            <div class="lr-field">
                <span class="lr-label">What do you need?</span>
                <div class="lr-choices" role="radiogroup">
                    @foreach ($types as $value => [$label, $icon])
                        <label class="lr-choice">
                            <input type="radio" name="request_name" value="{{ $value }}" @checked($picked === $value) required>
                            <span><x-lb-icon :name="$icon" /> {{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('request_name')<span class="lr-err">{{ $message }}</span>@enderror
            </div>

            <div class="lr-field">
                <label for="rq-desc">Details</label>
                <textarea id="rq-desc" name="description" class="lr-textarea @error('description') is-bad @enderror" maxlength="1000" required
                    placeholder="e.g. I want to move to the morning shift from next month">{{ old('description') }}</textarea>
                @error('description')<span class="lr-err">{{ $message }}</span>@enderror
            </div>

            <button type="submit" class="lr-btn is-block"><x-lb-icon name="send" /> Send request</button>
        </form>
    </div>

    <div class="lr-stack">
        <div class="lr-card">
            <div class="lr-card-title">
                <h2><x-lb-icon name="list" /> My requests</h2>
                @if ($learner_request->isNotEmpty())<span class="lr-chip mut no-dot">{{ $learner_request->count() }}</span>@endif
            </div>
            @if ($learner_request->isEmpty())
                <div class="lr-empty">
                    <div class="lr-empty-ic"><x-lb-icon name="inbox" /></div>
                    <b>No requests yet</b>
                    <p>Requests you send will show here with their status.</p>
                </div>
            @else
                <div class="lr-rows">
                    @foreach ($learner_request as $r)
                        @php [$label, $icon] = $types[$r->request_name] ?? [$r->request_name, 'send']; @endphp
                        <div class="lr-row" style="align-items:flex-start">
                            <span class="lr-row-ic {{ $r->request_status == 0 ? 'warn' : ($r->request_status == 2 ? 'bad' : 'ok') }}"><x-lb-icon :name="$icon" /></span>
                            <div class="lr-row-main">
                                <b>{{ $label }}</b>
                                <p>{{ $r->description }}</p>
                                @if (!empty($r->reply))<p class="lr-req-reply"><b>Library:</b> {{ $r->reply }}</p>@endif
                                <p style="font-size:12.5px;margin-top:2px">{{ $fmt($r->request_date ?? $r->created_at) }}</p>
                            </div>
                            @if ($r->request_status == 0)
                                <span class="lr-chip warn">Waiting</span>
                            @elseif ($r->request_status == 2)
                                <span class="lr-chip bad">Not possible</span>
                            @else
                                <span class="lr-chip ok">Done</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
