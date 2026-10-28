@php
    $max = max(1, $months->max(fn ($m) => max($m['new'] + $m['renew'], $m['lost'])));
    $last = $months->last();
@endphp

<div class="rpt-kpis is-5">
    <div class="rpt-kpi">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="users" /></span>
            <span class="lpg-tag {{ $tot['net'] >= 0 ? 'is-ok' : 'is-bad' }}"><x-lb-icon name="{{ $tot['net'] >= 0 ? 'trending-up' : 'trending-down' }}" />{{ $tot['net'] >= 0 ? '+' : '' }}{{ $tot['net'] }}</span>
        </div>
        <span>Active members now</span>
        <b>{{ $last['active'] }}</b>
        <small>net change since {{ $months->first()['date']->format('M Y') }}</small>
    </div>
    <div class="rpt-kpi is-ok">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="user-plus" /></span></div>
        <span>New members</span>
        <b>{{ $tot['new'] }}</b>
        <small>{{ round($tot['new'] / max(1, $months->count()), 1) }} per month</small>
    </div>
    <div class="rpt-kpi is-violet">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="refresh-cw" /></span></div>
        <span>Renewal rate</span>
        <b>{{ is_null($tot['rate']) ? '—' : $tot['rate'] . '%' }}</b>
        <small>{{ $tot['ended'] - $tot['lost'] }} of {{ $tot['ended'] }} plans renewed</small>
    </div>
    <div class="rpt-kpi is-bad">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="user-x" /></span></div>
        <span>Left</span>
        <b>{{ $tot['lost'] }}</b>
        <small>did not renew</small>
    </div>
    <div class="rpt-kpi is-warn">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="hourglass" /></span></div>
        <span>Average stay</span>
        <b>{{ $tot['avg_months'] }} <small>mo</small></b>
        <small>worth {{ rptInr($tot['ltv']) }} per member</small>
    </div>
</div>

<section class="rpt-chart">
    <div class="rpt-chart-h">
        <div>
            <h2>Joins vs leavers</h2>
            <p>Blue and green add members, red is members you lost.</p>
        </div>
        <div class="rpt-legend"><span><i class="c-new"></i>New</span><span><i class="c-ren"></i>Renewed</span><span><i class="c-lost"></i>Left</span></div>
    </div>
    <div class="rpt-cols">
        @foreach($months as $m)
            <div class="rpt-col {{ $m['date']->isSameMonth(now()) ? 'is-now' : '' }}" title="{{ $m['date']->format('M Y') }} · New {{ $m['new'] }} · Renewed {{ $m['renew'] }} · Left {{ $m['lost'] }}">
                <i class="c-new" style="height: {{ round($m['new'] / $max * 100, 1) }}%"></i>
                <i class="c-ren" style="height: {{ round($m['renew'] / $max * 100, 1) }}%"></i>
                <i class="c-lost" style="height: {{ round($m['lost'] / $max * 100, 1) }}%"></i>
                <small>{{ $m['date']->format($span > 12 ? 'M y' : 'M') }}</small>
            </div>
        @endforeach
    </div>
</section>

@if(!is_null($tot['rate']))
    <p class="rpt-insight {{ $tot['rate'] < 60 ? 'is-warn' : 'is-ok' }}">
        <x-lb-icon name="sparkles" />
        <span>
            <b>{{ $tot['rate'] }}%</b> of members renew when their plan ends.
            @if($tot['rate'] < 60)
                Lifting this by 10 points keeps about <b>{{ max(1, (int) round($tot['ended'] * 0.1)) }}</b> more members — reminders from the Renewals report are the fastest way.
            @else
                That’s healthy — keep reminding members 2–3 days before expiry.
            @endif
            A member stays {{ $tot['avg_months'] }} months on average.
        </span>
    </p>
@endif

<div class="rpt-sec">Month by month</div>
<div class="rpt-tbl-wrap">
    <table class="rpt-tbl">
        <thead><tr><th>Month</th><th>New</th><th>Renewed</th><th>Plans ended</th><th>Left</th><th>Renewal rate</th><th>Active at month end</th></tr></thead>
        <tbody>
            @foreach($months->reverse() as $m)
                <tr class="{{ $m['date']->isSameMonth(now()) ? 'is-now' : '' }}" data-csv="{{ json_encode(['Month' => $m['date']->format('M Y'), 'New' => $m['new'], 'Renewed' => $m['renew'], 'Plans ended' => $m['ended'], 'Left' => $m['lost'], 'Renewal rate %' => $m['rate'], 'Active' => $m['active']]) }}">
                    <td>{{ $m['date']->format('M Y') }}</td>
                    <td class="{{ $m['new'] ? '' : 'is-mut' }}">{{ $m['new'] }}</td>
                    <td class="{{ $m['renew'] ? 'is-ok' : 'is-mut' }}">{{ $m['renew'] }}</td>
                    <td class="is-mut">{{ $m['ended'] }}</td>
                    <td class="{{ $m['lost'] ? 'is-bad' : 'is-mut' }}">{{ $m['lost'] }}</td>
                    <td>{{ is_null($m['rate']) ? '—' : $m['rate'] . '%' }}</td>
                    <td><b>{{ $m['active'] }}</b></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
