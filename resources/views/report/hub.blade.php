@extends('report.layout')

@section('title', 'Reports')
@section('rpt-hub', '1')
@section('rpt-name', 'Reports')
@section('rpt-desc', now()->format('l, j F') . ' — your library at a glance, and every report in one place.')

@section('rpt-body')
    @php
        $o = $k['occ'];
        $cards = [
            'Money' => [
                ['payment.collection.report', 'Payment Collection Report', 'wallet', 'is-ok', 'Collections', 'Every fee received — by day, mode and member.', rptInr($k['in']), 'this month'],
                ['monthly.payment.collection.report', '', 'receipt-text', '', 'Cash book', 'Daily money in and out, including expenses and refunds.', rptInr($k['net']), 'net this month'],
                ['report.monthly', 'Monthly Revenue Report', 'scale', 'is-violet', 'Profit & loss', 'Month-by-month income, expenses and what you kept.', null, null],
                ['partial.payment.collection.report', 'Partial Payment Report', 'hand-coins', 'is-bad', 'Dues to collect', 'Balances members owe, oldest first, with reminders.', rptInr($k['dues']), $k['dues_count'] . ' members'],
            ],
            'Members' => [
                ['upcoming.payment.report', 'Upcoming Payment Report', 'calendar-clock', 'is-warn', 'Renewals', 'Expiring soon, in grace period and expired — act before seats go empty.', $k['exp_count'], 'ending in 7 days'],
                ['learner.report', 'Learner Report', 'users', '', 'Members', 'The booking register — seat, plan, dates and payment.', $k['active'], 'active'],
                ['reports.growth', 'Manage Report', 'trending-up', 'is-ok', 'Growth & retention', 'New members, renewals and leavers each month; renewal rate.', $k['new'], 'joined this month'],
            ],
            'Operations' => [
                ['reports.occupancy', 'Manage Report', 'gauge', 'is-violet', 'Seat occupancy', 'How full each shift is and how much empty seats cost you.', $o ? $o['pct'] . '%' : null, $o ? 'occupied' : null],
                ['attendance.report', 'Attendance Report', 'calendar-days', '', 'Attendance', 'Daily register and members who have stopped coming.', $k['present'], 'checked in today'],
                ['activities.all', '', 'activity', '', 'Activity log', 'Every swap, renewal, plan change and edit, with who did it.', null, null],
            ],
        ];
        $chg = $k['in_change'];
    @endphp

    <div class="rpt-kpis">
        <a class="rpt-kpi is-ok" href="{{ route('payment.collection.report') }}">
            <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="wallet" /></span>
                @if(!is_null($chg))<span class="lpg-tag {{ $chg >= 0 ? 'is-ok' : 'is-bad' }}"><x-lb-icon name="{{ $chg >= 0 ? 'trending-up' : 'trending-down' }}" />{{ abs($chg) }}%</span>@endif
            </div>
            <span>Collected this month</span>
            <b>{{ rptInr($k['in']) }}</b>
            <small>{{ rptInr($k['today_in']) }} today · vs same day last month</small>
        </a>
        <a class="rpt-kpi {{ $k['net'] >= 0 ? 'is-violet' : 'is-bad' }}" href="{{ route('report.monthly') }}">
            <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="scale" /></span><span class="rpt-kpi-go"><x-lb-icon name="arrow-up-right" /></span></div>
            <span>Net this month</span>
            <b>{{ rptInr($k['net']) }}</b>
            <small>after {{ rptInr($k['out']) }} spent</small>
        </a>
        <a class="rpt-kpi {{ $k['dues'] > 0 ? 'is-bad' : '' }}" href="{{ route('partial.payment.collection.report') }}">
            <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="hand-coins" /></span><span class="rpt-kpi-go"><x-lb-icon name="arrow-up-right" /></span></div>
            <span>Dues outstanding</span>
            <b>{{ rptInr($k['dues']) }}</b>
            <small>{{ $k['dues_count'] }} {{ \Illuminate\Support\Str::plural('member', $k['dues_count']) }} owe</small>
        </a>
        @if($o)
            <a class="rpt-kpi" href="{{ route('reports.occupancy') }}">
                <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="gauge" /></span><span class="rpt-kpi-go"><x-lb-icon name="arrow-up-right" /></span></div>
                <span>Occupancy</span>
                <b>{{ $o['pct'] }}%</b>
                <small>{{ $o['empty'] }} empty · {{ $k['active'] }} active members</small>
            </a>
        @else
            <a class="rpt-kpi" href="{{ route('learner.report') }}">
                <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="users" /></span><span class="rpt-kpi-go"><x-lb-icon name="arrow-up-right" /></span></div>
                <span>Active members</span>
                <b>{{ $k['active'] }}</b>
                <small>{{ $k['new'] }} joined this month</small>
            </a>
        @endif
    </div>

    @php
        $tasks = array_filter([
            $k['exp_count'] ? ['upcoming.payment.report', 'calendar-clock', 'is-warn', $k['exp_count'] . ' ' . \Illuminate\Support\Str::plural('plan', $k['exp_count']) . ($k['exp_count'] === 1 ? ' ends' : ' end') . ' in the next 7 days', 'Send renewal reminders before the seats go empty', rptInr($k['exp_value'])] : null,
            $k['dues'] > 0 ? ['partial.payment.collection.report', 'hand-coins', 'is-bad', $k['dues_count'] . ' ' . \Illuminate\Support\Str::plural('member', $k['dues_count']) . ($k['dues_count'] === 1 ? ' has' : ' have') . ' a pending balance', 'Call the oldest dues first', rptInr($k['dues'])] : null,
            $o && $o['empty'] > 0 ? ['reports.occupancy', 'sofa', '', $o['empty'] . ' ' . \Illuminate\Support\Str::plural('seat', $o['empty']) . ($o['empty'] === 1 ? ' is' : ' are') . ' completely empty', 'See which shifts have room to sell', rptInr($o['potential']) . '/mo'] : null,
        ]);
    @endphp

    @if(count($tasks))
        <section class="rh-att">
            <div class="rh-att-h"><span class="lpg-ic is-warn"><x-lb-icon name="bell-ring" /></span><h2>Needs your attention</h2></div>
            @foreach($tasks as [$route, $icon, $toneCls, $title, $sub, $val])
                <a class="rh-task" href="{{ route($route) }}">
                    <span class="lpg-ic {{ $toneCls }}"><x-lb-icon name="{{ $icon }}" /></span>
                    <div><b>{{ $title }}</b><small>{{ $sub }}</small></div>
                    <em>{{ $val }}</em>
                    <x-lb-icon name="chevron-right" />
                </a>
            @endforeach
        </section>
    @endif

    @foreach($cards as $group => $items)
        <div class="rpt-sec">{{ $group }}</div>
        <div class="rh-grid">
            @foreach($items as [$route, $perm, $icon, $toneCls, $title, $desc, $val, $valSub])
                @can('has-permission', $perm)
                    <a class="rh-card" href="{{ route($route) }}">
                        <span class="lpg-ic {{ $toneCls }}"><x-lb-icon name="{{ $icon }}" /></span>
                        <div>
                            <h3>{{ $title }}</h3>
                            <p>{{ $desc }}</p>
                            @if(!is_null($val))<strong>{{ $val }}<small>{{ $valSub }}</small></strong>@endif
                        </div>
                        <x-lb-icon name="chevron-right" />
                    </a>
                @endcan
            @endforeach
        </div>
    @endforeach
@endsection
