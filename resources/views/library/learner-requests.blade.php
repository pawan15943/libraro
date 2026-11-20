@extends('layouts.library')

@section('title', 'Learner Requests')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
@endpush

@section('content')
@php
    $icons = ['renewPlan' => 'refresh-cw', 'changePlan' => 'arrow-left-right', 'upgradePlan' => 'trending-up', 'swapSeat' => 'armchair', 'closePlan' => 'door-closed'];
@endphp

<div class="lpg lrq">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <p>What learners asked for from their portal. Make the change with the usual screens (renew, swap seat …), then mark it done here — the learner sees your answer.</p>
        </div>
    </header>

    @if ($errors->any())<div class="lef-alert" role="alert">{{ $errors->first() }}</div>@endif

    <nav class="lrq-tabs" aria-label="Requests">
        @foreach ($tabs as $k => $label)
            <a href="{{ route('learner.requests.index', ['tab' => $k]) }}" class="{{ $tab === $k ? 'is-on' : '' }}" @if ($tab === $k) aria-current="page" @endif>
                {{ $label }}@if ($k === 'pending' && $waiting)<em>{{ $waiting }}</em>@endif
            </a>
        @endforeach
    </nav>

    @if ($rows->isEmpty())
        <div class="lrq-empty">
            <span><x-lb-icon name="inbox" /></span>
            <b>{{ $tab === 'pending' ? 'No requests waiting' : 'No requests yet' }}</b>
            <p>When a learner asks to renew, change plan or seat from their portal, it shows here.</p>
        </div>
    @else
        <div class="lrq-list">
            @foreach ($rows as $r)
                @php
                    $state = (int) $r->request_status;
                    $tone = $state === 0 ? 'is-warn' : ($state === 1 ? 'is-ok' : 'is-bad');
                    $when = \Carbon\Carbon::parse($r->created_at);
                @endphp
                <article class="lrq-card {{ $state === 0 ? 'is-open' : '' }}">
                    <div class="lrq-top">
                        <span class="lrq-ic"><x-lb-icon :name="$icons[$r->request_name] ?? 'send'" /></span>
                        <div class="lrq-t">
                            <b>{{ $types[$r->request_name] ?? $r->request_name }}</b>
                            <small>
                                <a href="{{ route('learners.show', $r->learner_id) }}">{{ $r->learner_name }}</a>
                                @if ($r->learner_no) · {{ $r->learner_no }}@endif
                                @if ($r->seat_no) · Seat {{ $r->seat_no }}@endif
                                · {{ $when->format('j M') }} ({{ $when->diffForHumans() }})
                            </small>
                        </div>
                        <span class="lpg-tag {{ $tone }}">{{ $state === 0 ? 'Waiting' : ($state === 1 ? 'Done' : 'Not possible') }}</span>
                    </div>

                    @if ($r->description)<p class="lrq-msg">“{{ $r->description }}”</p>@endif

                    @if ($state === 0)
                        <form method="POST" action="{{ route('learner.requests.update', $r->id) }}" class="lrq-form">@csrf
                            <div class="lef-in"><x-lb-icon name="message-square" /><input type="text" name="reply" maxlength="1000" placeholder="Reply to the learner (needed for “Not possible”)" aria-label="Reply to {{ $r->learner_name }}"></div>
                            <div class="lrq-btns">
                                <a href="{{ route('learners.show', $r->learner_id) }}" class="lp-btn lp-btn--ghost"><x-lb-icon name="user-round" />Open learner</a>
                                <button class="lp-btn lp-btn--ghost lrq-no" name="decision" value="reject"><x-lb-icon name="circle-x" />Not possible</button>
                                <button class="lp-btn lrq-yes" name="decision" value="done"><x-lb-icon name="check" />Mark done</button>
                            </div>
                        </form>
                    @else
                        <p class="lrq-ans {{ $tone }}">
                            <x-lb-icon :name="$state === 1 ? 'circle-check' : 'circle-x'" />
                            <span>{{ $r->reply ?: ($state === 1 ? 'Done.' : 'Not possible.') }}@if ($r->handled_at)<small> · {{ \Carbon\Carbon::parse($r->handled_at)->format('j M, g:i A') }}</small>@endif</span>
                        </p>
                    @endif
                </article>
            @endforeach
        </div>
        <div class="lrq-pager">{{ $rows->links() }}</div>
    @endif
</div>

<style>
    body.lp .lrq-tabs { display: flex; gap: 6px; margin: 0 0 14px; overflow-x: auto; scrollbar-width: none; }
    body.lp .lrq-tabs a { flex: none; height: 38px; padding: 0 14px; border-radius: 999px; border: 1px solid var(--lp-line2); background: var(--lp-card); color: var(--lp-ink2) !important; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 7px; text-decoration: none; }
    body.lp .lrq-tabs a.is-on { background: var(--lp-ink); border-color: var(--lp-ink); color: var(--lp-card) !important; }
    body.lp .lrq-tabs em { font-style: normal; min-width: 22px; height: 22px; padding: 0 6px; border-radius: 999px; display: grid; place-items: center; background: #e5484d; color: #fff; font-size: 12px; font-weight: 700; }
    body.lp .lrq-list { display: grid; gap: 10px; max-width: 880px; }
    body.lp .lrq-card { padding: 14px 16px; border-radius: 18px; border: 1px solid var(--lp-line); background: var(--lp-card); }
    body.lp .lrq-card.is-open { border-color: color-mix(in srgb, var(--lp-warn) 45%, var(--lp-line)); }
    body.lp .lrq-top { display: flex; align-items: center; gap: 12px; }
    body.lp .lrq-ic { flex: none; width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: var(--lp-acc-soft2); color: var(--lp-acc-ink); }
    body.lp .lrq-ic .lb-i { width: 19px; height: 19px; }
    body.lp .lrq-t { flex: 1; min-width: 0; display: flex; flex-direction: column; }
    body.lp .lpg .lrq-t > b { font-size: 15px; font-weight: 700; color: var(--lp-ink); }
    body.lp .lpg .lrq-t > small { font-size: 12.5px; color: var(--lp-mut2); }
    body.lp .lrq-t small a { color: var(--lp-acc-ink); font-weight: 600; text-decoration: none; }
    body.lp .lpg p.lrq-msg { margin: 10px 0 0 52px; padding: 10px 12px; border-radius: 12px; background: var(--lp-soft); font-size: 14px; color: var(--lp-ink2); line-height: 1.45; }
    body.lp .lrq-form { display: grid; gap: 8px; margin: 12px 0 0 52px; }
    body.lp .lrq-btns { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
    body.lp .lrq-btns .lp-btn { text-decoration: none; }
    body.lp .lp-btn.lrq-no { color: var(--lp-bad-ink) !important; }
    body.lp .lp-btn.lrq-yes { background: var(--lp-ok); border-color: var(--lp-ok); color: #fff !important; }
    body.lp .lpg p.lrq-ans { display: flex; gap: 8px; margin: 10px 0 0 52px; font-size: 13.5px; color: var(--lp-ink2); }
    body.lp .lrq-ans .lb-i { width: 16px; height: 16px; flex: none; margin-top: 2px; }
    body.lp .lrq-ans.is-ok .lb-i { color: var(--lp-ok-ink); } body.lp .lrq-ans.is-bad .lb-i { color: var(--lp-bad-ink); }
    body.lp .lrq-ans small { color: var(--lp-mut3); }
    body.lp .lrq-empty { max-width: 880px; padding: 36px 16px; border-radius: 18px; border: 1px dashed var(--lp-line2); text-align: center; }
    body.lp .lrq-empty > span { width: 48px; height: 48px; margin: 0 auto 10px; border-radius: 14px; display: grid; place-items: center; background: var(--lp-soft3); color: var(--lp-mut2); }
    body.lp .lpg .lrq-empty b { display: block; font-size: 15px; font-weight: 700; color: var(--lp-ink); }
    body.lp .lpg .lrq-empty p { margin: 4px 0 0; font-size: 13.5px; color: var(--lp-mut2); }
    body.lp .lrq-pager { max-width: 880px; margin-top: 12px; }
    @media (max-width: 560px) {
        body.lp .lpg p.lrq-msg, body.lp .lrq-form, body.lp .lpg p.lrq-ans { margin-left: 0; }
        body.lp .lrq-btns .lp-btn { flex: 1 1 auto; justify-content: center; }
    }
</style>
@endsection
