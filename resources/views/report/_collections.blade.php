@php
    use Carbon\Carbon;
    $m = $metrics;
    $period = $start && $end
        ? ($start->isSameDay($end) ? $start->format('j M Y') : $start->format('j M') . ' – ' . $end->format('j M Y'))
        : 'all time';
    $mix = array_filter(['Online' => [$m['online'], 'c-on'], 'Offline' => [$m['offline'], 'c-off'], 'Pay later' => [$m['paylater'], 'c-later']], fn ($x) => $x[0] > 0);
    $mixTotal = array_sum(array_column($mix, 0)) ?: 1;
    $best = $m['best_day']->keys()->first();
@endphp

<div class="rpt-kpis">
    <div class="rpt-kpi is-ok">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="wallet" /></span>
            @if(!is_null($m['change']))<span class="lpg-tag {{ $m['change'] >= 0 ? 'is-ok' : 'is-bad' }}"><x-lb-icon name="{{ $m['change'] >= 0 ? 'trending-up' : 'trending-down' }}" />{{ abs($m['change']) }}%</span>@endif
        </div>
        <span>Collected</span>
        <b>{{ rptInr($m['collected']) }}</b>
        <small>{{ $period }}</small>
    </div>
    <div class="rpt-kpi">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="receipt-text" /></span></div>
        <span>Payments</span>
        <b>{{ number_format($m['count']) }}</b>
        <small>from {{ number_format($m['payers']) }} {{ \Illuminate\Support\Str::plural('member', $m['payers']) }}</small>
    </div>
    <div class="rpt-kpi is-violet">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="indian-rupee" /></span></div>
        <span>Average payment</span>
        <b>{{ rptInr($m['avg']) }}</b>
        <small>Discount given {{ rptInr($m['discount']) }}</small>
    </div>
    <div class="rpt-kpi {{ $m['pending'] > 0 ? 'is-bad' : '' }}">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="hourglass" /></span>
            @if($m['pending'] > 0)<a href="{{ route('partial.payment.collection.report') }}" class="rpt-kpi-go" aria-label="Open dues"><x-lb-icon name="arrow-up-right" /></a>@endif
        </div>
        <span>Still pending on these bills</span>
        <b>{{ rptInr($m['pending']) }}</b>
        <small>Collect from Dues report</small>
    </div>
</div>

@if($m['collected'] > 0)
    <div class="rpt-mix">
        <div class="rpt-mix-bar">
            @foreach($mix as $label => [$amt, $cls])
                <i class="{{ $cls }}" style="width: {{ round($amt / $mixTotal * 100, 2) }}%" title="{{ $label }} {{ rptInr($amt) }}"></i>
            @endforeach
        </div>
        <div class="rpt-mix-key">
            @foreach($mix as $label => [$amt, $cls])
                <span><i class="{{ $cls }}"></i>{{ $label }} <b>{{ rptInr($amt) }}</b> · {{ round($amt / $mixTotal * 100) }}%</span>
            @endforeach
        </div>
    </div>

    <p class="rpt-insight {{ !is_null($m['change']) && $m['change'] < 0 ? 'is-warn' : '' }}">
        <x-lb-icon name="sparkles" />
        <span>
            You collected <b>{{ rptInr($m['collected']) }}</b> {{ $start ? 'in this period' : 'so far' }}.
            @if(!is_null($m['change']))
                That’s <b>{{ abs($m['change']) }}% {{ $m['change'] >= 0 ? 'more' : 'less' }}</b> than the same number of days before ({{ rptInr($m['prev']) }}).
            @endif
            @if($best && $m['count'] > 1)
                Best day was <b>{{ Carbon::parse($best)->format('j M') }}</b> with {{ rptInr($m['best_day']->first()) }}.
            @endif
        </span>
    </p>
@endif

<div class="rpt-sec">Payments <span class="lpg-n">{{ $rows->count() }}</span></div>

@if($rows->isEmpty())
    <div class="lpg-empty">
        <span><x-lb-icon name="wallet" /></span>
        <b>No payments in this period</b>
        <p>Try a longer period like “This month” or “90 days”.</p>
    </div>
@else
    <div class="rpt-list">
        @foreach($rows as $i => $t)
            @php
                $l = $t->learner;
                $d = $t->learnerDetail;
                $name = ucwords(strtolower($l->name ?? ('Learner #' . $t->learner_id)));
                $seat = $l && $l->seat_no ? getSeatDisplayByMainNo($l->seat_no) : null;
                $mode = $modeOf($t);
                $date = $t->paid_date ? Carbon::parse($t->paid_date) : null;
                $phone = rptPhone(optional($l)->mobile);
                $plan = optional(optional($d)->plan)->name;
                $shift = optional(optional($d)->planType)->name;
                $wa = 'Hi ' . $name . ', we received your payment of ' . rptInr($t->paid_amount) . ($date ? ' on ' . $date->format('j M Y') : '') . '. Thank you!';
            @endphp
            <article class="rpt-row" data-q="{{ strtolower($name . ' ' . $seat . ' ' . $plan . ' ' . $shift . ' ' . $mode . ' ' . $phone) }}"
                data-csv="{{ json_encode(['Date' => optional($date)->format('d-m-Y'), 'Name' => $name, 'Seat' => $seat, 'Mobile' => $phone, 'Plan' => $plan, 'Shift' => $shift, 'Mode' => $mode, 'Total' => (float) $t->total_amount, 'Discount' => (float) $t->discount_amount, 'Paid' => (float) $t->paid_amount, 'Pending' => (float) $t->pending_amount]) }}">
                <span class="lp-face c{{ $i % 5 + 1 }}">{{ rptInitials($name) }}</span>
                <div class="rpt-who">
                    <div class="rpt-who-t">
                        <a href="{{ route('learners.show', $t->learner_id) }}">{{ $name }}</a>
                        @if($seat)<span class="rpt-seat"><x-lb-icon name="armchair" />{{ $seat }}</span>@endif
                    </div>
                    <small>
                        <span>{{ $date ? $date->format('j M Y') : '—' }}</span>
                        @if($plan)<span>{{ $plan }}@if($shift) · {{ $shift }}@endif</span>@endif
                        <span>{{ $mode }}</span>
                    </small>
                </div>
                <div class="rpt-amt">
                    <b class="is-ok">{{ rptInr($t->paid_amount) }}</b>
                    @if((float) $t->pending_amount > 0)
                        <small class="is-bad">{{ rptInr($t->pending_amount) }} due</small>
                    @elseif((float) $t->discount_amount > 0)
                        <small>{{ rptInr($t->discount_amount) }} off</small>
                    @endif
                </div>
                <div class="rpt-acts">
                    @can('has-permission', 'Receipt Generation')
                        <form action="{{ route('fee.generateReceipt') }}" method="POST" target="_blank">
                            @csrf
                            <input type="hidden" name="id" value="{{ $t->id }}">
                            <input type="hidden" name="type" value="learner">
                            <button type="submit" class="lpg-round noLoader" title="Print receipt" aria-label="Print receipt"><x-lb-icon name="printer" /></button>
                        </form>
                    @endcan
                    @if($phone)
                        <a href="https://wa.me/91{{ $phone }}?text={{ rawurlencode($wa) }}" target="_blank" rel="noopener" class="lpg-round is-wa noLoader" title="Send thank-you on WhatsApp" aria-label="WhatsApp {{ $name }}"><x-lb-icon name="message-circle" /></a>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
    <div class="lpg-empty" data-rpt-none hidden><span><x-lb-icon name="search" /></span><b>No match</b><p>Nobody in this list matches your search.</p></div>
@endif
