@extends('report.layout')

@section('title', 'Seat occupancy')
@section('rpt-name', 'Seat occupancy')
@section('rpt-desc', 'How full your library is right now, which shifts sell and how much more you could earn.')

@section('rpt-actions')
    <a href="{{ route('seats') }}" class="lp-btn lp-btn--ghost"><x-lb-icon name="layout-grid" />Seat map</a>
@endsection

@section('rpt-body')
    @php
        $o = $occ;
        $tone = fn ($p) => $p >= 85 ? 'is-ok' : ($p >= 50 ? '' : ($p >= 25 ? 'is-warn' : 'is-bad'));
        $topShift = $o['shifts']->first();
        $weakShift = $o['shifts']->last();
    @endphp

    @if($o['totalSeats'] < 1)
        <div class="lpg-empty">
            <span><x-lb-icon name="armchair" /></span>
            <b>No seats set up for {{ $branchName ?: 'this branch' }}</b>
            <p>Add seats and shifts in Library setup to see occupancy.</p>
        </div>
    @else
        <div class="rpt-kpis">
            <div class="rpt-kpi is-violet">
                <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="gauge" /></span></div>
                <span>Occupancy</span>
                <b>{{ $o['pct'] }}%</b>
                <small>{{ $o['active'] }} active bookings</small>
            </div>
            <div class="rpt-kpi is-ok">
                <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="armchair" /></span></div>
                <span>Fully booked seats</span>
                <b>{{ $o['full'] }} <small>/ {{ $o['totalSeats'] }}</small></b>
                <small>{{ $o['partial'] }} have a shift still free</small>
            </div>
            <div class="rpt-kpi is-warn">
                <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="sofa" /></span></div>
                <span>Empty seats</span>
                <b>{{ $o['empty'] }}</b>
                <small>no booking in any shift</small>
            </div>
            <div class="rpt-kpi">
                <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="indian-rupee" /></span></div>
                <span>Left on the table</span>
                <b>{{ rptInr($o['potential']) }}</b>
                <small>per month if empty seats fill</small>
            </div>
        </div>

        @if($topShift && $weakShift && $topShift['id'] !== $weakShift['id'])
            <p class="rpt-insight">
                <x-lb-icon name="sparkles" />
                <span><b>{{ $topShift['name'] }}</b> is your best-selling shift ({{ $topShift['pct'] }}% full) while <b>{{ $weakShift['name'] }}</b> is only {{ $weakShift['pct'] }}% full. Promote the slow shift — a small price cut or a referral offer there costs you nothing on seats that would stay empty anyway.</span>
            </p>
        @endif

        <div class="rpt-sec">By shift <span class="lpg-n">{{ $o['shifts']->count() }}</span></div>
        <div class="rpt-list">
            @foreach($o['shifts'] as $s)
                @php
                    $time = '';
                    try { $time = \Carbon\Carbon::parse($s['start'])->format('g:i A') . ' – ' . \Carbon\Carbon::parse($s['end'])->format('g:i A'); } catch (\Throwable $e) {}
                @endphp
                <article class="rpt-row no-face">
                    <div class="rpt-who">
                        <div class="rpt-who-t"><b>{{ $s['name'] }}</b>@if($s['price'])<span class="lpg-tag">{{ rptInr($s['price']) }}</span>@endif</div>
                        <small>@if($time)<span>{{ $time }}</span>@endif<span>{{ $s['booked'] }} booked · {{ $s['free'] }} free</span></small>
                        <div class="rpt-meter {{ $tone($s['pct']) }}" style="margin-top: 6px"><i style="width: {{ $s['pct'] }}%"></i></div>
                    </div>
                    <div class="rpt-amt">
                        <b>{{ $s['pct'] }}%</b>
                        <small>{{ $s['free'] ? $s['free'] . ' seats to sell' : 'Sold out' }}</small>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="rpt-sec">Every seat <span class="lpg-n">{{ $o['totalSeats'] }}</span>
            <span class="rpt-sec-r rpt-legend"><span><i class="c-ren"></i>Full</span><span><i class="c-off"></i>Partly free</span><span><i class="c-free"></i>Empty</span></span>
        </div>
        <div class="occ-grid">
            @foreach($o['seats'] as $seat)
                @php
                    $label = (string) (getSeatDisplayByMainNo($seat['no']) ?: $seat['no']);
                    preg_match('/^\s*([^\s(]+)\s*(?:\(([^)]*)\))?/', $label, $pm);
                @endphp
                <span class="occ-seat is-{{ $seat['state'] }}" title="Seat {{ $label }} · {{ $seat['state'] === 'full' ? 'fully booked' : ($seat['state'] === 'empty' ? 'empty' : 'free in: ' . $seat['free']->pluck('name')->implode(', ')) }}">{{ $pm[1] ?? $label }}@if(!empty($pm[2]))<small>{{ $pm[2] }}</small>@endif</span>
            @endforeach
        </div>
    @endif
@endsection
