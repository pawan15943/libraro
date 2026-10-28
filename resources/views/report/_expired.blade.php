@php
    use Carbon\Carbon;
    $m = $metrics;
    $today = Carbon::today();
    $branchName = getCurrentBranchName();
@endphp

<div class="rpt-kpis">
    <div class="rpt-kpi is-bad">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="user-x" /></span></div>
        <span>Members who left</span>
        <b>{{ $m['lost'] }}</b>
        <small>{{ $window === 'all' ? 'all time' : 'in this window' }}</small>
    </div>
    <div class="rpt-kpi is-warn">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="calendar-x" /></span></div>
        <span>Left in last 30 days</span>
        <b>{{ $m['last30'] }}</b>
        <small>warmest win-back leads</small>
    </div>
    <div class="rpt-kpi is-violet">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="star" /></span></div>
        <span>Loyal leavers</span>
        <b>{{ $m['loyal'] }}</b>
        <small>renewed at least once before</small>
    </div>
    <div class="rpt-kpi">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="indian-rupee" /></span></div>
        <span>Revenue walked out</span>
        <b>{{ rptInr($m['monthly_value']) }}</b>
        <small>value of their last plans</small>
    </div>
</div>

@if($m['lost'])
    <p class="rpt-insight">
        <x-lb-icon name="sparkles" />
        <span>Winning back even <b>{{ max(1, (int) round($m['lost'] * 0.2)) }}</b> of these {{ $m['lost'] }} members brings in about <b>{{ rptInr($m['lost'] ? $m['monthly_value'] / $m['lost'] * max(1, round($m['lost'] * 0.2)) : 0) }}</b>. Start with recent and loyal leavers — a personal WhatsApp works better than a discount.</span>
    </p>
@endif

<div class="rpt-sec">Most recent first <span class="lpg-n">{{ $learners->count() }}</span></div>

@if($learners->isEmpty())
    <div class="lpg-empty">
        <span><x-lb-icon name="party-popper" /></span>
        <b>Nobody left in this period</b>
        <p>Great retention! Try a longer window to see older leavers.</p>
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
                $ago = (int) $end->diffInDays($today);
                $fs = $firstStart->get($v->learner_id);
                $stayedMonths = $fs ? max(1, (int) round(Carbon::parse($fs->first_start)->diffInDays($end) / 30)) : null;
                $plans = (int) optional($fs)->plans;
                $plan = optional($v->plan)->name;
                $shift = optional($v->planType)->name;
                $msg = "Hi {$name},\n\nWe miss you at {$branchName}! Your seat" . ($shift ? " in the {$shift} shift" : '') . " is waiting.\nCome back this week and continue your preparation with us. Reply to this message and we will keep a seat ready for you.";
            @endphp
            <article class="rpt-row" data-csv="{{ json_encode(['Name' => $name, 'Mobile' => $phone, 'Last plan' => $plan, 'Shift' => $shift, 'Left on' => $end->format('d-m-Y'), 'Days since' => $ago, 'Months with you' => $stayedMonths, 'Plans taken' => $plans, 'Last plan value' => (float) $v->plan_price_id]) }}">
                <span class="lp-face c{{ $i % 5 + 1 }}">{{ rptInitials($name) }}</span>
                <div class="rpt-who">
                    <div class="rpt-who-t">
                        <a href="{{ route('learners.show', $v->learner_id) }}">{{ $name }}</a>
                        @if($plans >= 2)<span class="lpg-tag is-violet"><x-lb-icon name="star" />{{ $plans }} plans</span>@endif
                        <span class="lpg-tag {{ $ago <= 30 ? 'is-warn' : '' }}">{{ $ago === 0 ? 'Left today' : 'Left ' . $ago . 'd ago' }}</span>
                    </div>
                    <small>
                        @if($plan)<span>{{ $plan }}@if($shift) · {{ $shift }}@endif</span>@endif
                        @if($stayedMonths)<span>With you {{ $stayedMonths }} {{ \Illuminate\Support\Str::plural('month', $stayedMonths) }}</span>@endif
                        @if($phone)<span class="is-ph">+91 {{ $phone }}</span>@endif
                    </small>
                </div>
                <div class="rpt-amt">
                    <b>{{ rptInr($v->plan_price_id) }}</b><small>last plan</small>
                </div>
                <div class="rpt-acts">
                    @if($phone)
                        <a href="tel:+91{{ $phone }}" class="lpg-round" title="Call" aria-label="Call {{ $name }}"><x-lb-icon name="phone" /></a>
                        <a href="https://wa.me/91{{ $phone }}?text={{ rawurlencode($msg) }}" target="_blank" rel="noopener" class="lpg-round is-wa noLoader" title="Send win-back message" aria-label="Message {{ $name }} on WhatsApp"><x-lb-icon name="message-circle" /></a>
                    @endif
                    <a href="{{ route('learners.edit', $v->learner_id) }}" class="lpg-round is-pri" title="Re-join / edit" aria-label="Re-join {{ $name }}"><x-lb-icon name="user-plus" /></a>
                </div>
            </article>
        @endforeach
    </div>
@endif
