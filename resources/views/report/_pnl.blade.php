@php
    $max = max(1, $months->max('income'), $months->max(fn ($m) => $m['expense'] + $m['refunds']));
    $chg = $prev && $prev['profit'] != 0 ? round(($cur['profit'] - $prev['profit']) / abs($prev['profit']) * 100) : null;
@endphp

<div class="rpt-kpis">
    <div class="rpt-kpi is-ok">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="trending-up" /></span></div>
        <span>Money in</span>
        <b>{{ rptInr($totals['income']) }}</b>
        <small>{{ $year ? 'in ' . $year : $months->first()['date']->format('M Y') . ' – now' }}</small>
    </div>
    <div class="rpt-kpi is-warn">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="receipt" /></span></div>
        <span>Expenses + refunds</span>
        <b>{{ rptInr($totals['expense'] + $totals['refunds']) }}</b>
        <small>refunds {{ rptInr($totals['refunds']) }}</small>
    </div>
    <div class="rpt-kpi {{ $totals['profit'] >= 0 ? 'is-violet' : 'is-bad' }}">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="scale" /></span>
            @if(!is_null($totals['margin']))<span class="lpg-tag {{ $totals['margin'] >= 0 ? 'is-ok' : 'is-bad' }}">{{ $totals['margin'] < -100 ? 'Loss' : $totals['margin'] . '% margin' }}</span>@endif
        </div>
        <span>Profit</span>
        <b>{{ rptInr($totals['profit']) }}</b>
        <small>avg {{ rptInr($avgProfit) }} / month</small>
    </div>
    <div class="rpt-kpi">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="calendar-check" /></span>
            @if(!is_null($chg))<span class="lpg-tag {{ $chg >= 0 ? 'is-ok' : 'is-bad' }}"><x-lb-icon name="{{ $chg >= 0 ? 'trending-up' : 'trending-down' }}" />{{ abs($chg) }}%</span>@endif
        </div>
        <span>{{ $cur['date']->format('F') }} so far</span>
        <b>{{ rptInr($cur['profit']) }}</b>
        <small>vs {{ $prev ? rptInr($prev['profit']) . ' in ' . $prev['date']->format('M') : '—' }}</small>
    </div>
</div>

<section class="rpt-chart">
    <div class="rpt-chart-h">
        <div>
            <h2>Monthly money flow</h2>
            <p>Green is money in, orange is what went out.</p>
        </div>
        <div class="rpt-legend"><span><i class="c-in"></i>In</span><span><i class="c-out"></i>Out</span></div>
    </div>
    <div class="rpt-cols">
        @foreach($months as $m)
            <div class="rpt-col {{ $m['date']->isSameMonth(now()) ? 'is-now' : '' }}" title="{{ $m['date']->format('M Y') }} · In {{ rptInr($m['income']) }} · Out {{ rptInr($m['expense'] + $m['refunds']) }} · Profit {{ rptInr($m['profit']) }}">
                <i class="c-in" style="height: {{ round($m['income'] / $max * 100, 1) }}%"></i>
                <i class="c-out" style="height: {{ round(($m['expense'] + $m['refunds']) / $max * 100, 1) }}%"></i>
                <small>{{ $m['date']->format('M') }}</small>
            </div>
        @endforeach
    </div>
</section>

@if($best && $worst && $best !== $worst)
    <p class="rpt-insight {{ $totals['profit'] < 0 ? 'is-warn' : '' }}">
        <x-lb-icon name="sparkles" />
        <span>
            Best month was <b>{{ $best['date']->format('F Y') }}</b> with {{ rptInr($best['profit']) }} profit;
            weakest was <b>{{ $worst['date']->format('F Y') }}</b> ({{ rptInr($worst['profit']) }}).
            @if($totals['income'] > 0)
                Every ₹100 collected leaves you <b>₹{{ max(0, (int) round($totals['profit'] / $totals['income'] * 100)) }}</b> after expenses.
            @endif
        </span>
    </p>
@endif

<div class="rpt-sec">Month by month</div>
<div class="rpt-tbl-wrap">
    <table class="rpt-tbl">
        <thead>
            <tr><th>Month</th><th>Money in</th><th>Expenses</th><th>Refunds</th><th>Profit</th><th>Margin</th></tr>
        </thead>
        <tbody>
            @foreach($months->reverse() as $m)
                @php $mg = $m['income'] > 0 ? round($m['profit'] / $m['income'] * 100) : null; @endphp
                <tr class="{{ $m['date']->isSameMonth(now()) ? 'is-now' : '' }}" data-csv="{{ json_encode(['Month' => $m['date']->format('M Y'), 'Money in' => $m['income'], 'Expenses' => $m['expense'], 'Refunds' => $m['refunds'], 'Profit' => $m['profit']]) }}">
                    <td><a href="{{ route('monthly.payment.collection.report', ['year' => $m['date']->year, 'month' => $m['date']->month]) }}">{{ $m['date']->format('M Y') }}</a></td>
                    <td class="{{ $m['income'] ? 'is-ok' : 'is-mut' }}">{{ rptInr($m['income']) }}</td>
                    <td class="{{ $m['expense'] ? '' : 'is-mut' }}">{{ rptInr($m['expense']) }}</td>
                    <td class="{{ $m['refunds'] ? '' : 'is-mut' }}">{{ rptInr($m['refunds']) }}</td>
                    <td class="{{ $m['profit'] < 0 ? 'is-bad' : '' }}"><b>{{ rptInr($m['profit']) }}</b></td>
                    <td class="is-mut">{{ is_null($mg) ? '—' : ($mg < -100 ? 'loss' : $mg . '%') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td class="is-ok">{{ rptInr($totals['income']) }}</td>
                <td>{{ rptInr($totals['expense']) }}</td>
                <td>{{ rptInr($totals['refunds']) }}</td>
                <td class="{{ $totals['profit'] < 0 ? 'is-bad' : '' }}">{{ rptInr($totals['profit']) }}</td>
                <td>{{ is_null($totals['margin']) ? '—' : ($totals['margin'] < -100 ? 'loss' : $totals['margin'] . '%') }}</td>
            </tr>
        </tfoot>
    </table>
</div>
