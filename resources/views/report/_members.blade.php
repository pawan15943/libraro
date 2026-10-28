@php
    use Carbon\Carbon;
    $m = $metrics;
    $today = Carbon::today();
    $topShift = $m['by_shift']->keys()->first();
@endphp

<div class="rpt-kpis">
    <div class="rpt-kpi">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="users" /></span></div>
        <span>Bookings</span>
        <b>{{ number_format($m['total']) }}</b>
        <small>{{ number_format($m['members']) }} {{ \Illuminate\Support\Str::plural('member', $m['members']) }}</small>
    </div>
    <div class="rpt-kpi is-ok">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="user-check" /></span></div>
        <span>Active · Expired</span>
        <b>{{ $m['active'] }} <small>· {{ $m['expired'] }}</small></b>
        <small>{{ $topShift ? 'Most in ' . $topShift : 'by plan status' }}</small>
    </div>
    <div class="rpt-kpi is-violet">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="indian-rupee" /></span></div>
        <span>Plan value</span>
        <b>{{ rptInr($m['billed']) }}</b>
        <small>of the bookings shown</small>
    </div>
    <a class="rpt-kpi {{ $m['pending'] > 0 ? 'is-bad' : '' }}" href="{{ route('partial.payment.collection.report') }}">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="hand-coins" /></span><span class="rpt-kpi-go"><x-lb-icon name="arrow-up-right" /></span></div>
        <span>Balance pending</span>
        <b>{{ rptInr($m['pending']) }}</b>
        <small>{{ $m['unpaid'] }} not marked paid</small>
    </a>
</div>

@if($m['by_shift']->count() > 1)
    @php $tot = max(1, $m['by_shift']->sum()); $tones = ['c-on', 'c-ren', 'c-off', 'c-later', 'c-lost', 'c-net']; @endphp
    <div class="rpt-mix">
        <div class="rpt-mix-bar">
            @foreach($m['by_shift'] as $shift => $n)
                <i class="{{ $tones[$loop->index % 6] }}" style="width: {{ round($n / $tot * 100, 2) }}%" title="{{ $shift }}: {{ $n }}"></i>
            @endforeach
        </div>
        <div class="rpt-mix-key">
            @foreach($m['by_shift'] as $shift => $n)
                <span><i class="{{ $tones[$loop->index % 6] }}"></i>{{ $shift }} <b>{{ $n }}</b></span>
            @endforeach
        </div>
    </div>
@endif

<div class="rpt-sec">Bookings <span class="lpg-n">{{ $learners->count() }}</span></div>

@if($learners->isEmpty())
    <div class="lpg-empty">
        <span><x-lb-icon name="users" /></span>
        <b>No bookings match</b>
        <p>Change the status, month or shift filter.</p>
    </div>
@else
    <div class="rpt-list">
        @foreach($learners as $i => $v)
            @php
                $l = $v->learner;
                $name = ucwords(strtolower(optional($l)->name ?? 'Learner #' . $v->learner_id));
                $seat = $v->seat_no ? getSeatDisplayByMainNo($v->seat_no) : null;
                $phone = rptPhone(optional($l)->mobile);
                $start = $v->plan_start_date ? Carbon::parse($v->plan_start_date) : null;
                $end = $v->plan_end_date ? Carbon::parse($v->plan_end_date) : null;
                $left = $end ? (int) $today->diffInDays($end, false) : null;
                $owed = (float) optional($v->transaction)->pending_amount;
                $plan = optional($v->plan)->name;
                $shift = optional($v->planType)->name;
                $tag = $v->status == 1
                    ? ($left !== null && $left <= 5 ? ['Ends in ' . max(0, $left) . 'd', 'is-warn'] : ['Active', 'is-ok'])
                    : ['Expired', ''];
            @endphp
            <article class="rpt-row" data-csv="{{ json_encode(['Name' => $name, 'Seat' => $seat, 'Mobile' => $phone, 'Plan' => $plan, 'Shift' => $shift, 'Start' => optional($start)->format('d-m-Y'), 'End' => optional($end)->format('d-m-Y'), 'Status' => $tag[0], 'Paid' => $v->is_paid ? 'Yes' : 'No', 'Plan price' => (float) $v->plan_price_id, 'Pending' => $owed]) }}">
                <span class="lp-face c{{ $i % 5 + 1 }}">{{ rptInitials($name) }}</span>
                <div class="rpt-who">
                    <div class="rpt-who-t">
                        <a href="{{ route('learners.show', $v->learner_id) }}">{{ $name }}</a>
                        @if($seat)<span class="rpt-seat"><x-lb-icon name="armchair" />{{ $seat }}</span>@endif
                        <span class="lpg-tag {{ $tag[1] }}">{{ $tag[0] }}</span>
                        @if(!$v->is_paid)<span class="lpg-tag is-bad">Not paid</span>@endif
                    </div>
                    <small>
                        @if($plan)<span>{{ $plan }}@if($shift) · {{ $shift }}@endif</span>@endif
                        @if($start && $end)<span>{{ $start->format('j M') }} → {{ $end->format('j M Y') }}</span>@endif
                        @if($phone)<span class="is-ph">+91 {{ $phone }}</span>@endif
                    </small>
                </div>
                <div class="rpt-amt">
                    <b>{{ rptInr($v->plan_price_id) }}</b>
                    @if($owed > 0)<small class="is-bad">{{ rptInr($owed) }} due</small>@else<small>{{ $v->is_paid ? 'paid' : 'unpaid' }}</small>@endif
                </div>
                <div class="rpt-acts">
                    @if($phone)
                        <a href="https://wa.me/91{{ $phone }}" target="_blank" rel="noopener" class="lpg-round is-wa noLoader" title="WhatsApp" aria-label="WhatsApp {{ $name }}"><x-lb-icon name="message-circle" /></a>
                    @endif
                    <a href="{{ route('learners.show', $v->learner_id) }}" class="lpg-round" title="Open profile" aria-label="Open {{ $name }}"><x-lb-icon name="chevron-right" /></a>
                </div>
            </article>
        @endforeach
    </div>
@endif
