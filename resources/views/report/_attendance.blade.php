@php
    $m = $metrics;
    $tone = fn ($r) => $r >= 75 ? 'is-ok' : ($r >= 40 ? 'is-warn' : 'is-bad');
    $quiet = $learnerAttendance->filter(fn ($r) => $r['rate'] < 40)->sortBy('present')->take(6);
@endphp

<div class="rpt-step">
    <button type="button" class="lpg-ibtn" data-month="{{ $prevMonth->format('Y-n') }}" aria-label="Previous month"><x-lb-icon name="chevron-left" /></button>
    <b>{{ $monthStart->format('F Y') }}</b>
    <button type="button" class="lpg-ibtn" data-month="{{ $nextMonth->format('Y-n') }}" aria-label="Next month" @disabled($isCurrent)><x-lb-icon name="chevron-right" /></button>
</div>

<div class="rpt-kpis">
    <div class="rpt-kpi">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="users" /></span></div>
        <span>Active members</span>
        <b>{{ $m['total_learners'] }}</b>
        <small>{{ $m['total_present'] }} visits this month</small>
    </div>
    <div class="rpt-kpi is-ok">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="log-in" /></span></div>
        <span>{{ is_null($m['today_in']) ? 'Regulars (75%+)' : 'In today' }}</span>
        <b>{{ is_null($m['today_in']) ? $m['high_attendance_count'] : $m['today_in'] }}</b>
        <small>{{ is_null($m['today_in']) ? 'came 3 days out of 4' : $m['high_attendance_count'] . ' regulars this month' }}</small>
    </div>
    <div class="rpt-kpi is-violet">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="activity" /></span></div>
        <span>Average attendance</span>
        <b>{{ $m['avg_rate'] }}%</b>
        <small>over {{ $m['elapsed'] }} {{ \Illuminate\Support\Str::plural('day', $m['elapsed']) }}</small>
    </div>
    <div class="rpt-kpi is-bad">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="user-x" /></span></div>
        <span>Not coming</span>
        <b>{{ $m['never'] }}</b>
        <small>no visit this month · {{ $m['low'] }} below 50%</small>
    </div>
</div>

@if($m['never'] + $m['low'] > 0 && $m['total_learners'])
    <p class="rpt-insight is-warn">
        <x-lb-icon name="clock-alert" />
        <span><b>{{ $m['never'] + $m['low'] }}</b> paying members are hardly coming. Members who stop visiting rarely renew — a quick “all good?” call now saves the renewal later.</span>
    </p>
@endif

@if($quiet->isNotEmpty())
    <div class="rpt-sec">Check in with them</div>
    <div class="rpt-list">
        @foreach($quiet as $i => $r)
            @php $phone = rptPhone($r['mobile']); $name = ucwords(strtolower($r['name'])); @endphp
            <article class="rpt-row">
                <span class="lp-face c{{ $i % 5 + 1 }}">{{ rptInitials($name) }}</span>
                <div class="rpt-who">
                    <div class="rpt-who-t">
                        <a href="{{ route('learners.show', $r['learner_id']) }}">{{ $name }}</a>
                        <span class="rpt-seat"><x-lb-icon name="armchair" />{{ $r['seat_display'] }}</span>
                    </div>
                    <small><span>{{ $r['present'] }} of {{ $m['elapsed'] }} days</span></small>
                </div>
                <div class="rpt-amt"><b class="is-bad">{{ $r['rate'] }}%</b><small>attendance</small></div>
                @if($phone)
                    <div class="rpt-acts">
                        <a href="tel:+91{{ $phone }}" class="lpg-round" title="Call" aria-label="Call {{ $name }}"><x-lb-icon name="phone" /></a>
                        <a href="https://wa.me/91{{ $phone }}?text={{ rawurlencode('Hi ' . $name . ', we have not seen you at the library for a few days. All good? Your seat is waiting for you.') }}" target="_blank" rel="noopener" class="lpg-round is-wa noLoader" title="WhatsApp" aria-label="WhatsApp {{ $name }}"><x-lb-icon name="message-circle" /></a>
                    </div>
                @endif
            </article>
        @endforeach
    </div>
@endif

<div class="rpt-sec">Daily register <span class="lpg-n">{{ $learnerAttendance->count() }}</span>
    <span class="rpt-sec-r"><span class="rpt-legend"><span><i class="c-ren"></i>Present</span><span><i class="c-free"></i>No visit</span></span></span>
</div>

@if($learnerAttendance->isEmpty())
    <div class="lpg-empty">
        <span><x-lb-icon name="calendar-days" /></span>
        <b>No active members</b>
        <p>Attendance shows up here once members start checking in.</p>
    </div>
@else
    <div class="rpt-tbl-wrap">
        <table class="rpt-tbl rpt-att">
            <thead>
                <tr>
                    <th>Member</th>
                    @foreach($daysMeta as $d)
                        <th class="{{ $d['is_sunday'] ? 'is-sun' : '' }} {{ $d['is_today'] ? 'is-today' : '' }}">{{ $d['day'] }}<small>{{ $d['short_weekday'] }}</small></th>
                    @endforeach
                    <th>Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($learnerAttendance as $r)
                    @php $name = ucwords(strtolower($r['name'])); @endphp
                    <tr data-csv="{{ json_encode(array_merge(['Name' => $name, 'Seat' => $r['seat_display']], collect($r['daily'])->mapWithKeys(fn ($v, $k) => [(string) $k => $v === '-' ? '' : $v])->all(), ['Present' => $r['present'], 'Rate %' => $r['rate']])) }}">
                        <td>
                            <div class="rpt-att-who">
                                <a href="{{ route('learners.show', $r['learner_id']) }}" title="{{ $name }}">{{ $name }}</a>
                                <small>Seat {{ $r['seat_display'] }}</small>
                            </div>
                        </td>
                        @foreach($r['daily'] as $day => $v)
                            <td><span class="d {{ $v === 'P' ? 'P' : ($v === 'A' ? 'A' : 'N') }}">{{ $v === 'P' ? 'P' : ($v === 'A' ? 'A' : '·') }}</span></td>
                        @endforeach
                        <td class="rate"><b>{{ $r['rate'] }}%</b><div class="rpt-meter {{ $tone($r['rate']) }}"><i style="width: {{ $r['rate'] }}%"></i></div></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
