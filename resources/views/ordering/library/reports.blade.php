@extends('layouts.library')

@section('title', 'Order Reports')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-ordering.css') }}?v={{ @filemtime(public_path('css/lb-ordering.css')) }}">
@endpush

@section('content')
@php
    $inr = fn ($n) => '₹' . number_format((float) $n, (float) $n == floor((float) $n) ? 0 : 2);
    $min = fn ($v) => $v === null ? '—' : ($v < 1 ? '< 1 min' : (round($v) . ' min'));
    $maxCat = max(1, (float) $categories->max('amount'));
    $maxItem = max(1, (int) $items->max('qty'));
    $short = fn ($no) => '#' . \Illuminate\Support\Str::afterLast($no, '-');
@endphp

<div class="lpg od orp">
    <form class="orp-range" method="GET" action="{{ route('orders.reports') }}">
        <div class="zb-views" role="tablist" aria-label="Period">
            @foreach ($ranges as $k => $label)
                @if ($k !== 'custom')
                    <a class="zb-view {{ $range === $k ? 'is-on' : '' }}" href="{{ route('orders.reports', ['range' => $k]) }}">{{ $label }}</a>
                @endif
            @endforeach
        </div>
        <div class="orp-custom">
            <input type="hidden" name="range" value="custom">
            <input type="date" name="from" value="{{ $from->toDateString() }}" aria-label="From">
            <span>to</span>
            <input type="date" name="to" value="{{ $to->toDateString() }}" aria-label="To">
            <button class="zb-mute" type="submit">Show</button>
        </div>
    </form>
    <p class="orp-sub">{{ $from->format('j M Y') }}{{ $from->isSameDay($to) ? '' : ' – ' . $to->format('j M Y') }} · {{ $kpi['orders'] }} {{ \Illuminate\Support\Str::plural('order', $kpi['orders']) }} placed</p>

    <div class="orp-kpis">
        <div class="orp-kpi"><small>Orders</small><b>{{ $kpi['orders'] }}</b><span>{{ $kpi['completed'] }} done · {{ $kpi['open'] }} open · {{ $kpi['cancelled'] }} cancelled</span></div>
        <div class="orp-kpi is-acc"><small>Sales</small><b>{{ $inr($kpi['sales']) }}</b><span>Average order {{ $inr($kpi['avg_order']) }}</span></div>
        <div class="orp-kpi is-ok"><small>Money received</small><b>{{ $inr($kpi['received']) }}</b><span>after {{ $inr($kpi['refunded']) }} refunds</span></div>
        <div class="orp-kpi {{ $kpi['due'] > 0 ? 'is-warn' : '' }}"><small>Still to collect</small><b>{{ $inr($kpi['due']) }}</b><span>{{ $kpi['refund_due'] > 0 ? $inr($kpi['refund_due']) . ' to refund' : 'no refunds due' }}</span></div>
    </div>

    <div class="orp-grid">
        <section class="od-card">
            <h3><x-lb-icon name="wallet" />By payment method</h3>
            <div class="orp-scroll"><table class="orp-t">
                <thead><tr><th>Method</th><th>Orders</th><th>Amount</th><th>Received</th><th>Due</th></tr></thead>
                <tbody>
                    @foreach ($methods as $m)
                        <tr><td>{{ $m['label'] }}</td><td>{{ $m['orders'] }}</td><td>{{ $inr($m['amount']) }}</td><td>{{ $inr($m['paid']) }}</td><td class="{{ $m['due'] > 0 ? 'is-warn' : '' }}">{{ $inr($m['due']) }}</td></tr>
                    @endforeach
                </tbody>
            </table></div>
        </section>

        <section class="od-card">
            <h3><x-lb-icon name="timer" />Speed</h3>
            <div class="orp-speed">
                <div><b>{{ $min($timing['accept']) }}</b><small>to accept</small></div>
                <div><b>{{ $min($timing['prepare']) }}</b><small>to prepare</small></div>
                <div><b>{{ $min($timing['deliver']) }}</b><small>order to hand-over</small></div>
                <div><b>{{ $timing['on_time'] === null ? '—' : $timing['on_time'] . '%' }}</b><small>ready on time</small></div>
            </div>
        </section>

        <section class="od-card">
            <h3><x-lb-icon name="shapes" />By category</h3>
            @if ($categories->isEmpty())<p class="od-none">No sales in this period.</p>@else
                <div class="orp-scroll"><table class="orp-t">
                    <thead><tr><th>Category</th><th>Orders</th><th>Qty</th><th>Sales</th></tr></thead>
                    <tbody>
                        @foreach ($categories as $c)
                            <tr><td><span class="orp-bar" style="--w: {{ round($c->amount / $maxCat * 100) }}%"></span>{{ $c->name }}</td><td>{{ $c->orders }}</td><td>{{ (int) $c->qty }}</td><td>{{ $inr($c->amount) }}</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            @endif
        </section>

        <section class="od-card">
            <h3><x-lb-icon name="star" />Top items</h3>
            @if ($items->isEmpty())<p class="od-none">No sales in this period.</p>@else
                <div class="orp-scroll"><table class="orp-t">
                    <thead><tr><th>Item</th><th>Qty</th><th>Sales</th></tr></thead>
                    <tbody>
                        @foreach ($items as $i)
                            <tr><td><span class="orp-bar" style="--w: {{ round($i->qty / $maxItem * 100) }}%"></span>{{ $i->name }}<small>{{ $i->cat }}</small></td><td>{{ (int) $i->qty }}</td><td>{{ $inr($i->amount) }}</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            @endif
        </section>

        <section class="od-card">
            <h3><x-lb-icon name="users" />Staff</h3>
            @if ($staff->isEmpty())<p class="od-none">No staff actions in this period.</p>@else
                <div class="orp-scroll"><table class="orp-t">
                    <thead><tr><th>Who</th><th>Accepted</th><th>Handed over</th><th>Payments</th><th>Rejected</th></tr></thead>
                    <tbody>
                        @foreach ($staff as $s)
                            <tr><td>{{ $s['name'] }}</td><td>{{ $s['accepted'] }}</td><td>{{ $s['handed'] }}</td><td>{{ $s['payments'] }}</td><td>{{ $s['rejected'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            @endif
        </section>

        <section class="od-card">
            <h3><x-lb-icon name="hand-coins" />To collect now <span class="lpg-n">{{ $dueList->count() }}</span></h3>
            @if ($dueList->isEmpty())<p class="od-none">Nothing pending. 🎉</p>@else
                <div class="orp-scroll"><table class="orp-t">
                    <thead><tr><th>Order</th><th>Learner</th><th>Method</th><th>Due</th></tr></thead>
                    <tbody>
                        @foreach ($dueList as $o)
                            <tr><td><a href="{{ route('orders.show', $o->id) }}">{{ $short($o->order_no) }}</a><small>{{ $o->created_at->format('j M') }}</small></td><td>{{ $people[$o->learner_id] ?? '—' }}</td><td>{{ $o->paymentLabel() }}</td><td class="is-warn">{{ $inr($o->dueAmount()) }}</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            @endif
            @if ($refundList->isNotEmpty())
                <h4 class="orp-h4">To refund</h4>
                <div class="orp-scroll"><table class="orp-t">
                    <tbody>
                        @foreach ($refundList as $o)
                            <tr><td><a href="{{ route('orders.show', $o->id) }}">{{ $short($o->order_no) }}</a></td><td>{{ $people[$o->learner_id] ?? '—' }}</td><td class="is-bad">{{ $inr(app(\App\Services\Ordering\OrderPaymentService::class)->refundable($o)) }}</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            @endif
        </section>
    </div>
</div>
@endsection
