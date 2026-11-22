@php
    use Carbon\Carbon;
    $m = $metrics;
    $today = Carbon::today();
    $branchName = getCurrentBranchName();
@endphp

<div class="rpt-kpis">
    <div class="rpt-kpi is-warn">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="calendar-clock" /></span></div>
        <span>Expiring in {{ $filters['days'] }} days</span>
        <b>{{ $m['total_upcoming'] }}</b>
        <small>{{ \Illuminate\Support\Str::plural('member', $m['total_upcoming']) }} to renew</small>
    </div>
    <div class="rpt-kpi is-bad">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="alarm-clock" /></span></div>
        <span>Ends today</span>
        <b>{{ $m['expiring_today'] }}</b>
        <small>call them first</small>
    </div>
    <div class="rpt-kpi">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="sunrise" /></span></div>
        <span>Ends tomorrow</span>
        <b>{{ $m['expiring_tomorrow'] }}</b>
        <small>{{ $m['expiring_later'] }} later in the window</small>
    </div>
    <div class="rpt-kpi is-ok">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="indian-rupee" /></span></div>
        <span>Renewal value</span>
        <b>{{ rptInr($m['renewal_value']) }}</b>
        <small>if everyone renews</small>
    </div>
</div>

@if($m['total_upcoming'])
    <p class="rpt-insight">
        <x-lb-icon name="sparkles" />
        <span><b>{{ rptInr($m['renewal_value']) }}</b> is up for renewal. Members reminded 2–3 days before expiry renew far more often than those reminded after — send the WhatsApp nudge now.</span>
    </p>
@endif

<div class="rpt-sec">Ending soonest first <span class="lpg-n">{{ $learners->count() }}</span></div>

@if($learners->isEmpty())
    <div class="lpg-empty">
        <span><x-lb-icon name="calendar-check" /></span>
        <b>No plans ending in the next {{ $filters['days'] }} days</b>
        <p>Try a longer window like 15 or 30 days.</p>
    </div>
@else
    <div class="rpt-list">
        @foreach($learners as $i => $l)
            @php
                $d = $l->learnerDetails->first();
                $name = ucwords(strtolower($l->name ?? 'Learner'));
                $seat = $l->seat_no ? getSeatDisplayByMainNo($l->seat_no) : null;
                $phone = rptPhone($l->mobile);
                $end = $d ? Carbon::parse($d->plan_end_date) : null;
                $left = $end ? (int) $today->diffInDays($end, false) : null;
                $tag = $left === 0 ? ['Ends today', 'is-bad'] : ($left === 1 ? ['Ends tomorrow', 'is-warn'] : ['In ' . $left . ' days', 'is-acc']);
                $plan = optional(optional($d)->plan)->name;
                $shift = optional(optional($d)->planType)->name;
                $msg = "Hi {$name},\n\nYour library plan" . ($seat ? " for seat {$seat}" : '') . ' ends on ' . optional($end)->format('j M Y') . ".\nRenew now to keep your seat" . ($shift ? " in the {$shift} shift" : '') . ".\n\n– {$branchName}";
            @endphp
            <article class="rpt-row" data-csv="{{ json_encode(['Name' => $name, 'Seat' => $seat, 'Mobile' => $phone, 'Plan' => $plan, 'Shift' => $shift, 'Ends on' => optional($end)->format('d-m-Y'), 'Plan price' => (float) optional($d)->plan_price_id]) }}">
                <span class="lp-face c{{ $i % 5 + 1 }}">{{ rptInitials($name) }}</span>
                <div class="rpt-who">
                    <div class="rpt-who-t">
                        <a href="{{ route('learners.show', $l->id) }}">{{ $name }}</a>
                        @if($seat)<span class="rpt-seat"><x-lb-icon name="armchair" />{{ $seat }}</span>@endif
                        <span class="lpg-tag {{ $tag[1] }}">{{ $tag[0] }}</span>
                    </div>
                    <small>
                        @if($plan)<span>{{ $plan }}@if($shift) · {{ $shift }}@endif</span>@endif
                        @if($end)<span>Ends {{ $end->format('j M Y') }}</span>@endif
                        @if($phone)<span class="is-ph">+91 {{ $phone }}</span>@endif
                    </small>
                </div>
                <div class="rpt-amt">
                    <b>{{ rptInr(optional($d)->plan_price_id) }}</b><small>to renew</small>
                </div>
                <div class="rpt-acts">
                    @if($phone)
                        <a href="tel:+91{{ $phone }}" class="lpg-round" title="Call" aria-label="Call {{ $name }}"><x-lb-icon name="phone" /></a>
                        <a href="https://wa.me/91{{ $phone }}?text={{ rawurlencode(appendPayLink($msg, learnerPayLink($l->id, 'renew'), 'renew')) }}" target="_blank" rel="noopener" class="lpg-round is-wa noLoader" title="Send renewal reminder" aria-label="Remind {{ $name }} on WhatsApp"><x-lb-icon name="message-circle" /></a>
                    @endif
                    @if($d)
                        <a href="{{ route('learner.payment', $d->id) }}" class="lpg-round is-pri" title="Renew now" aria-label="Renew {{ $name }}"><x-lb-icon name="refresh-cw" /></a>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
@endif
