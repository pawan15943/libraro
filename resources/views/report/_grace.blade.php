@php
    use Carbon\Carbon;
    $m = $metrics;
    $today = Carbon::today();
    $branchName = getCurrentBranchName();
@endphp

<div class="rpt-kpis">
    <div class="rpt-kpi is-warn">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="hourglass" /></span></div>
        <span>In grace period</span>
        <b>{{ $m['total_pending'] }}</b>
        <small>{{ $extendDay }} grace {{ \Illuminate\Support\Str::plural('day', $extendDay) }} allowed</small>
    </div>
    <div class="rpt-kpi is-ok">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="indian-rupee" /></span></div>
        <span>Renewal value</span>
        <b>{{ rptInr($m['renewal_value']) }}</b>
        <small>on their current plans</small>
    </div>
    <div class="rpt-kpi is-bad">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="hand-coins" /></span></div>
        <span>Old balance due</span>
        <b>{{ rptInr($m['total_settlement_due']) }}</b>
        <small>{{ $m['settlement_count'] }} also owe on the last bill</small>
    </div>
    <a class="rpt-kpi" href="{{ route('upcoming.payment.report') }}">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="calendar-clock" /></span><span class="rpt-kpi-go"><x-lb-icon name="arrow-up-right" /></span></div>
        <span>Next in line</span>
        <b>Expiring soon</b>
        <small>see who ends this week</small>
    </a>
</div>

@if($m['total_pending'])
    <p class="rpt-insight is-warn">
        <x-lb-icon name="clock-alert" />
        <span>These {{ $m['total_pending'] }} {{ \Illuminate\Support\Str::plural('member', $m['total_pending']) }} still sit on their seats without a valid plan. Once the grace days run out the seat is released — renew or free it today.</span>
    </p>
@endif

<div class="rpt-sec">Least grace left first <span class="lpg-n">{{ count($learners) }}</span></div>

@if(count($learners) === 0)
    <div class="lpg-empty">
        <span><x-lb-icon name="circle-check" /></span>
        <b>Nobody is in the grace period</b>
        <p>Every member with a seat has a running plan.</p>
    </div>
@else
    <div class="rpt-list">
        @foreach($learners as $i => $v)
            @php
                $l = $v->learner;
                $name = ucwords(strtolower(optional($l)->name ?? 'Learner #' . $v->learner_id));
                $seat = optional($l)->seat_no ? getSeatDisplayByMainNo($l->seat_no) : null;
                $phone = rptPhone(optional($l)->mobile);
                $end = Carbon::parse($v->plan_end_date);
                $graceLeft = (int) $today->diffInDays($end->copy()->addDays($extendDay ?? 0), false);
                $trx = learnerTransaction($v->learner_id, $v->id);
                $owed = $trx ? (float) $trx->pending_amount : 0;
                $plan = optional($v->plan)->name;
                $shift = optional($v->planType)->name;
                $msg = "Hi {$name},\n\nYour library plan ended on " . $end->format('j M Y') . '. You are in the grace period' . ($graceLeft > 0 ? " for {$graceLeft} more " . ($graceLeft === 1 ? 'day' : 'days') : '') . ".\nPlease renew soon" . ($seat ? " to keep seat {$seat}" : '') . ".\n\n– {$branchName}";
            @endphp
            <article class="rpt-row" data-csv="{{ json_encode(['Name' => $name, 'Seat' => $seat, 'Mobile' => $phone, 'Plan' => $plan, 'Shift' => $shift, 'Ended on' => $end->format('d-m-Y'), 'Grace days left' => max(0, $graceLeft), 'Old balance' => $owed]) }}">
                <span class="lp-face c{{ $i % 5 + 1 }}">{{ rptInitials($name) }}</span>
                <div class="rpt-who">
                    <div class="rpt-who-t">
                        <a href="{{ route('learners.show', $v->learner_id) }}">{{ $name }}</a>
                        @if($seat)<span class="rpt-seat"><x-lb-icon name="armchair" />{{ $seat }}</span>@endif
                        <span class="lpg-tag {{ $graceLeft <= 1 ? 'is-bad' : 'is-warn' }}">{{ $graceLeft > 0 ? $graceLeft . 'd grace left' : 'Last day' }}</span>
                    </div>
                    <small>
                        <span>Ended {{ $end->format('j M Y') }}</span>
                        @if($plan)<span>{{ $plan }}@if($shift) · {{ $shift }}@endif</span>@endif
                        @if($phone)<span class="is-ph">+91 {{ $phone }}</span>@endif
                    </small>
                </div>
                <div class="rpt-amt">
                    <b>{{ rptInr($v->plan_price_id) }}</b>
                    @if($owed > 0)<small class="is-bad">+ {{ rptInr($owed) }} old due</small>@else<small>to renew</small>@endif
                </div>
                <div class="rpt-acts">
                    @if($phone)
                        <a href="https://wa.me/91{{ $phone }}?text={{ rawurlencode(appendPayLink($msg, learnerPayLink($v->learner_id, 'renew'), 'renew')) }}" target="_blank" rel="noopener" class="lpg-round is-wa noLoader" title="Send reminder" aria-label="Remind {{ $name }} on WhatsApp"><x-lb-icon name="message-circle" /></a>
                    @endif
                    @if($owed > 0)
                        <a href="javascript:void(0)" class="lpg-round is-pri settlement-learner" data-id="{{ $v->learner_id }}" data-learnerDetail="{{ $v->id }}" title="Collect pending balance" aria-label="Collect from {{ $name }}"><x-lb-icon name="indian-rupee" /></a>
                    @else
                        <a href="{{ route('learner.other.payment', $v->id) }}" class="lpg-round is-pri" title="Renew" aria-label="Renew {{ $name }}"><x-lb-icon name="refresh-cw" /></a>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
@endif
