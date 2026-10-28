@php
    use Carbon\Carbon;
    $m = $metrics;
    $net = $totalCr - $totalDr;
    $avgDay = $m['activeDays'] ? $totalCollection / $m['activeDays'] : 0;
    $mixTotal = max(1, $cashCollection + $onlineCollection);
@endphp

<div class="rpt-step">
    <button type="button" class="lpg-ibtn" data-month="{{ $prevMonthDate->format('Y-n') }}" aria-label="Previous month"><x-lb-icon name="chevron-left" /></button>
    <b>{{ request('start_date') ? $fromDate->format('j M') . ' – ' . $toDate->format('j M Y') : $fromDate->format('F Y') }}</b>
    <button type="button" class="lpg-ibtn" data-month="{{ $nextMonthDate->format('Y-n') }}" aria-label="Next month" @disabled($isCurrentMonth || $isFutureMonth)><x-lb-icon name="chevron-right" /></button>
</div>

<div class="rpt-kpis">
    <div class="rpt-kpi is-ok">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="trending-up" /></span></div>
        <span>Money in</span>
        <b>{{ rptInr($totalCollection) }}</b>
        <small>{{ $inflowCount }} receipts · {{ rptInr($avgDay) }}/active day</small>
    </div>
    <div class="rpt-kpi is-warn">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="receipt" /></span></div>
        <span>Expenses</span>
        <b>{{ rptInr($totalExpense) }}</b>
        <small>{{ $expenseCount }} {{ \Illuminate\Support\Str::plural('entry', $expenseCount) }}</small>
    </div>
    <div class="rpt-kpi is-bad">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="rotate-ccw" /></span></div>
        <span>Refunds &amp; adjustments</span>
        <b>{{ rptInr($totalDr - $totalExpense) }}</b>
        <small>{{ $refundCount }} {{ \Illuminate\Support\Str::plural('refund', $refundCount) }}</small>
    </div>
    <div class="rpt-kpi {{ $net >= 0 ? 'is-violet' : 'is-bad' }}">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="scale" /></span></div>
        <span>Net balance</span>
        <b>{{ rptInr($net) }}</b>
        <small>money in − money out</small>
    </div>
</div>

@if($totalCollection > 0)
    <div class="rpt-mix">
        <div class="rpt-mix-bar">
            <i class="c-on" style="width: {{ round($onlineCollection / $mixTotal * 100, 2) }}%"></i>
            <i class="c-off" style="width: {{ round($cashCollection / $mixTotal * 100, 2) }}%"></i>
        </div>
        <div class="rpt-mix-key">
            <span><i class="c-on"></i>Online / UPI <b>{{ rptInr($onlineCollection) }}</b> · {{ round($onlineCollection / $mixTotal * 100) }}%</span>
            <span><i class="c-off"></i>Cash in hand <b>{{ rptInr($cashCollection) }}</b> · {{ round($cashCollection / $mixTotal * 100) }}%</span>
        </div>
    </div>
@endif

@if($net < 0)
    <p class="rpt-insight is-warn"><x-lb-icon name="clock-alert" /><span>More money went out than came in this period ({{ rptInr(abs($net)) }}). Check the expense entries below.</span></p>
@endif

<div class="rpt-sec">Ledger <span class="lpg-n">{{ $m['count'] }}</span><span class="rpt-sec-r">{{ $m['activeDays'] }} active {{ \Illuminate\Support\Str::plural('day', $m['activeDays']) }}</span></div>

@forelse($groupedTransactions as $date => $rows)
    @php
        $d = Carbon::parse($date);
        $in = $rows->where('dr_cr', 'Cr')->sum('amount');
        $out = $rows->where('dr_cr', 'Dr')->sum('amount');
    @endphp
    <section class="rpt-list rpt-day">
        <div class="rpt-day-h">
            <b>{{ $d->isToday() ? 'Today' : ($d->isYesterday() ? 'Yesterday' : $d->format('D, j M')) }}</b>
            <span>{{ count($rows) }} {{ \Illuminate\Support\Str::plural('entry', count($rows)) }}</span>
            <em class="{{ $in - $out >= 0 ? 'is-ok' : 'is-bad' }}">{{ $in - $out >= 0 ? '+' : '' }}{{ rptInr($in - $out) }}</em>
        </div>
        @foreach($rows as $row)
            @php
                $isExp = $row->payment_type === 'EXPENSE';
                $isCr = $row->dr_cr === 'Cr';
                $seat = !$isExp && $row->seat_no ? getSeatDisplayByMainNo($row->seat_no) : null;
                $who = $isExp ? ($row->particular ?: 'Expense') : ucwords(strtolower($row->name ?: ('Learner #' . $row->learner_id)));
                $what = $isExp ? 'Expense' : ucwords(strtolower(str_replace('_', ' ', $row->payment_type ?: 'Fee')));
                $mode = rptMode($row->payment_mode);
            @endphp
            <article class="rpt-row no-face" data-csv="{{ json_encode(['Date' => $d->format('d-m-Y'), 'Name / note' => $who, 'Seat' => $seat, 'Type' => $what, 'In / Out' => $isCr ? 'In' : 'Out', 'Amount' => (float) $row->amount, 'Mode' => $mode, 'Note' => $row->particular]) }}">
                <span class="rpt-flow {{ $isCr ? 'is-in' : ($isExp ? 'is-exp' : 'is-out') }}"><x-lb-icon name="{{ $isCr ? 'plus' : ($isExp ? 'receipt' : 'minus') }}" /></span>
                <div class="rpt-who">
                    <div class="rpt-who-t">
                        @if(!$isExp && $row->learner_id)
                            <a href="{{ route('learners.show', $row->learner_id) }}">{{ $who }}</a>
                        @else
                            <b>{{ $who }}</b>
                        @endif
                        @if($seat)<span class="rpt-seat"><x-lb-icon name="armchair" />{{ $seat }}</span>@endif
                    </div>
                    <small>
                        <span>{{ $what }}</span>
                        <span>{{ $mode }}</span>
                        @if(!$isExp && $row->particular)<span>{{ \Illuminate\Support\Str::limit($row->particular, 40) }}</span>@endif
                    </small>
                </div>
                <div class="rpt-amt">
                    <b class="{{ $isCr ? 'is-ok' : 'is-bad' }}">{{ $isCr ? '+' : '−' }}{{ rptInr($row->amount) }}</b>
                </div>
            </article>
        @endforeach
    </section>
@empty
    <div class="lpg-empty">
        <span><x-lb-icon name="receipt-text" /></span>
        <b>No entries for this period</b>
        <p>Use the arrows to see another month.</p>
    </div>
@endforelse
