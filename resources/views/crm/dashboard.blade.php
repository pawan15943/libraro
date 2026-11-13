@extends('layouts.admin')

@section('body_class', 'ax-custom-page crm-page')

@section('page_actions')
    <x-ui.button :href="route('crm.queue')" icon="fa-solid fa-phone-volume">Start calling</x-ui.button>
@endsection

@section('content')
@include('crm.partials.nav')
@include('crm.partials.flash')

@php
    $k = $d['kpis'];
    $fmtTalk = fn ($m) => $m >= 60 ? intdiv($m, 60).'h '.($m % 60).'m' : $m.'m';
    $periodLabel = \App\Http\Controllers\Crm\CrmDashboardController::RANGES[$range] ?? '';
    $maxDisp = max(1, $d['dispositions']->max('count') ?? 1);
    $maxSource = max(1, $d['sources']->max('total') ?? 1);
    $rangeLinks = collect(\App\Http\Controllers\Crm\CrmDashboardController::RANGES)->map(fn ($label, $key) => [
        'label' => $label,
        'href' => route('crm.dashboard', array_merge(request()->except(['range']), ['range' => $key])),
        'active' => (string) $range === (string) $key,
    ])->values()->all();
@endphp

{{-- period + agent --}}
<form method="GET" class="mb-5 flex flex-wrap items-center gap-2.5">
    <x-ui.segmented :links="$rangeLinks" />
    <input type="hidden" name="range" value="{{ $range }}">
    @if($range === 'custom')
        <input type="date" name="from" value="{{ $from->toDateString() }}" class="ui-input w-40" aria-label="From">
        <input type="date" name="to" value="{{ $to->toDateString() }}" class="ui-input w-40" aria-label="To">
        <x-ui.button type="submit">Apply</x-ui.button>
    @endif
    @if($crm['seesAll'])
        <select name="agent" class="ui-select w-auto min-w-[160px] sm:ms-auto" aria-label="Agent" onchange="this.form.submit()">
            <option value="">Whole team</option>
            @foreach($crm['agents'] as $a)<option value="{{ $a->id }}" @selected($agentId === $a->id)>{{ $a->name }}</option>@endforeach
        </select>
    @endif
</form>

{{-- KPIs --}}
<div class="ui-stats ui-stats--6">
    <x-ui.stat label="Open leads" :value="number_format($k['open'])" :note="number_format($k['new']).' new · '.$periodLabel" icon="fa-solid fa-address-book" :href="route('crm.leads.index')" />
    <x-ui.stat label="Calls" :value="number_format($k['calls'])" :note="number_format($k['leads_called']).' leads reached'" icon="fa-solid fa-phone"
        :href="route('crm.calls.index', ['range' => $range === 'custom' ? 'custom' : $range, 'from' => $from->toDateString(), 'to' => $to->toDateString()])" />
    <x-ui.stat label="Connected" :value="number_format($k['connected'])" icon="fa-solid fa-link">
        <span>{{ $k['connect_rate'] }}% connect rate</span><span class="ui-meter mt-1 !h-1 basis-full"><i style="width: {{ $k['connect_rate'] }}%"></i></span>
    </x-ui.stat>
    <x-ui.stat label="Enrolled" :value="number_format($k['enrolled'])" :note="$k['conversion_rate'].'% of leads reached'" icon="fa-solid fa-trophy" tone="green" />
    <x-ui.stat label="Talk time" :value="$fmtTalk($k['talk_minutes'])" :note="($k['calls'] ? round($k['talk_minutes'] * 60 / max(1, $k['connected'])) : 0).'s per connected call'" icon="fa-regular fa-clock" />
    <x-ui.stat label="Due today" :value="number_format($k['due_today'])" icon="fa-regular fa-bell" :tone="$k['overdue'] ? 'red' : null" :alert="(bool) $k['overdue']"
        :href="route('crm.reminders.index', ['tab' => $k['overdue'] ? 'overdue' : 'today'])">
        <span class="{{ $k['overdue'] ? 'font-semibold text-bad' : '' }}">{{ $k['overdue'] }} overdue</span>
    </x-ui.stat>
</div>

{{-- needs attention --}}
<div class="crm-attention">
    <a href="{{ route('crm.reminders.index', ['tab' => 'overdue']) }}" class="{{ $k['overdue'] ? 'is-red' : '' }}"><i class="fa-regular fa-clock"></i><b>{{ $k['overdue'] }}</b> overdue follow-ups</a>
    <a href="{{ route('crm.queue', ['tab' => 'new']) }}"><i class="fa-regular fa-circle-dot"></i><b>{{ $k['never_called'] }}</b> never called</a>
    <a href="{{ route('crm.queue', ['tab' => 'notpaid']) }}" class="is-amber"><i class="fa-solid fa-indian-rupee-sign"></i><b>{{ $k['not_paid'] }}</b> registered, not paid</a>
    <a href="{{ route('crm.leads.index', ['follow' => 'stale']) }}"><i class="fa-solid fa-hourglass-half"></i><b>{{ $k['stale'] }}</b> not called for 7+ days</a>
</div>

<div class="crm-dash">
    {{-- top performers --}}
    <x-ui.card title="Top performers" icon="fa-solid fa-trophy">
        <x-slot:actions>
            <x-ui.segmented name="crm_lb" :options="['week' => 'This week', 'month' => 'This month']" value="week" size="sm" />
        </x-slot:actions>
        @foreach(['week' => $d['top_week'], 'month' => $d['top_month']] as $key => $rows)
            <ol class="crm-leaders" data-crm-lb="{{ $key }}" @if($key === 'month') hidden @endif>
                @forelse($rows as $i => $r)
                    <li>
                        <span class="crm-rank r{{ $i + 1 }}">{{ $i + 1 }}</span>
                        <x-ui.avatar :name="$r['user']->name" size="sm" />
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-semibold">{{ $r['user']->name }}</div>
                            <div class="crm-leader-stats">
                                <span title="Calls"><i class="fa-solid fa-phone"></i>{{ $r['calls'] }}</span>
                                <span title="Connected"><i class="fa-solid fa-link"></i>{{ $r['connected'] }} <em>({{ $r['connect_rate'] }}%)</em></span>
                                <span title="Enrolled" class="is-win"><i class="fa-solid fa-trophy"></i>{{ $r['enrolled'] }}</span>
                                <span title="Talk time"><i class="fa-regular fa-clock"></i>{{ $fmtTalk($r['talk_minutes']) }}</span>
                            </div>
                            <div class="ui-meter !h-1"><i style="width: {{ round($r['score'] / max(1, $rows->first()['score']) * 100) }}%"></i></div>
                        </div>
                        <b class="crm-score" title="Score: 1 per call, 2 per connected call, 25 per enrolment">{{ $r['score'] }}</b>
                    </li>
                @empty
                    <li><x-ui.empty icon="fa-solid fa-headset" title="No calls yet" :text="$key === 'week' ? 'Nobody has called this week.' : 'Nobody has called this month.'" class="w-full !py-6" /></li>
                @endforelse
            </ol>
        @endforeach
        <p class="crm-hint mt-auto pt-3 mb-0">Score: 1 per call · 2 per connected call · 25 per enrolment.</p>
    </x-ui.card>

    {{-- calls per day --}}
    <x-ui.card title="Calls, last 14 days" class="crm-chart-card">
        <x-slot:actions><div class="crm-legend"><span class="l-all"></span>All <span class="l-con"></span>Connected</div></x-slot:actions>
        <div class="crm-bars">
            @foreach($d['daily'] as $day)
                <div class="crm-bar {{ $day['day']->isToday() ? 'is-today' : '' }}" title="{{ $day['day']->format('D j M') }}: {{ $day['calls'] }} calls, {{ $day['connected'] }} connected">
                    <span class="crm-bar-n">{{ $day['calls'] ?: '' }}</span>
                    <div class="crm-bar-col" style="height: {{ max($day['h'], $day['calls'] ? 3 : 1) }}%"><i style="height: {{ $day['calls'] ? round($day['connected'] / $day['calls'] * 100) : 0 }}%"></i></div>
                    <span class="crm-bar-d">{{ $day['day']->format('D') === 'Mon' || $loop->first || $day['day']->isToday() ? $day['day']->format('j M') : $day['day']->format('j') }}</span>
                </div>
            @endforeach
        </div>
    </x-ui.card>

    {{-- funnel --}}
    <x-ui.card title="Leads by stage">
        <x-slot:actions><x-ui.button variant="plain" size="sm" :href="route('crm.leads.index', ['status' => 'all'])">All leads</x-ui.button></x-slot:actions>
        <ul class="crm-funnel">
            @foreach($d['funnel'] as $f)
                <li style="--c: {{ $f['stage']->color }}">
                    <a href="{{ route('crm.leads.index', ['status' => 'all', 'stage' => $f['stage']->id] + ($agentId ? ['agent' => $agentId] : [])) }}" class="crm-funnel-row">
                        <span class="crm-funnel-name"><span class="crm-dot"></span>{{ $f['stage']->name }}@if($f['stage']->is_closed)<small>{{ $f['stage']->is_won ? 'won' : 'closed' }}</small>@endif</span>
                        <span class="crm-track"><i style="width: {{ max($f['percent'], $f['count'] ? 2 : 0) }}%"></i></span>
                        <b>{{ number_format($f['count']) }}</b>
                    </a>
                    @if($f['subs']->isNotEmpty())
                        <div class="crm-funnel-subs">
                            @foreach($f['subs'] as $s)<span>{{ $s['name'] }} <b>{{ $s['count'] }}</b></span>@endforeach
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    </x-ui.card>

    {{-- dispositions --}}
    <x-ui.card :title="'Call results · '.$periodLabel">
        @forelse($d['dispositions'] as $r)
            <div class="crm-hbar" style="--c: {{ $r['d']->color }}">
                <span class="crm-hbar-name">{{ $r['d']->name }}</span>
                <span class="crm-track"><i style="width: {{ round($r['count'] / $maxDisp * 100) }}%"></i></span>
                <b>{{ $r['count'] }}</b><em>{{ $r['percent'] }}%</em>
            </div>
        @empty
            <p class="ui-subhead m-0">No calls in this period.</p>
        @endforelse
    </x-ui.card>

    {{-- best time --}}
    <x-ui.card title="Best time to call" subtitle="Connect rate by hour">
        <div class="crm-hours">
            @foreach($d['hours'] as $h)
                <div class="crm-hour {{ ($h['rate'] ?? 0) >= 45 ? 'is-strong' : '' }}" title="{{ $h['calls'] }} calls{{ $h['rate'] !== null ? ', '.$h['rate'].'% connected' : '' }}" style="--a: {{ $h['rate'] !== null ? max(.08, $h['rate'] / 100) : 0 }}">
                    <span class="crm-hour-cell">{{ $h['rate'] !== null ? $h['rate'].'%' : '' }}</span>
                    <span class="crm-hour-l">{{ $h['hour'] > 12 ? $h['hour'] - 12 : $h['hour'] }}{{ $h['hour'] >= 12 ? 'p' : 'a' }}</span>
                </div>
            @endforeach
        </div>
    </x-ui.card>

    {{-- sources --}}
    <x-ui.card title="Lead sources" flush>
        <x-ui.table>
            <x-slot:head><th>Source</th><th class="is-num">Leads</th><th class="is-num">Untouched</th><th class="is-num">Enrolled</th></x-slot:head>
            @forelse($d['sources'] as $s)
                <tr>
                    <td class="min-w-[110px]">{{ $s['label'] }}<div class="ui-meter mt-1.5 !h-[3px]"><i style="width: {{ round($s['total'] / $maxSource * 100) }}%"></i></div></td>
                    <td class="is-num">{{ number_format($s['total']) }}</td>
                    <td class="is-num">{{ number_format($s['untouched']) }}</td>
                    <td class="is-num"><span class="font-semibold">{{ number_format($s['won']) }}</span><div class="ui-table__sub">{{ $s['rate'] }}%</div></td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-label-3">No leads yet.</td></tr>
            @endforelse
        </x-ui.table>
    </x-ui.card>

    {{-- team: deep tracking --}}
    @if($crm['seesAll'])
        <x-ui.card :title="'Team · '.$periodLabel" flush class="crm-dash__wide">
            <x-slot:actions><x-ui.button variant="plain" size="sm" :href="route('crm.calls.index', ['range' => $range])">Call log</x-ui.button></x-slot:actions>
            <x-ui.table>
                <x-slot:head><th>Agent</th><th class="is-num">Open</th><th class="is-num">Calls</th><th class="is-num">Connected</th><th class="is-num">Avg call</th><th class="is-num">Enrolled</th><th class="is-num">Overdue</th><th>Last call</th></x-slot:head>
                @forelse($d['agents'] as $a)
                    <tr>
                        <td class="is-nowrap"><a href="{{ route('crm.dashboard', ['range' => $range, 'agent' => $a['user']->id]) }}" class="ui-table__title">{{ $a['user']->name }}</a></td>
                        <td class="is-num"><a href="{{ route('crm.leads.index', ['agent' => $a['user']->id]) }}">{{ $a['open'] }}</a></td>
                        <td class="is-num is-nowrap">{{ $a['calls'] }}<div class="ui-table__sub">{{ $a['leads'] }} leads</div></td>
                        <td class="is-num">{{ $a['connected'] }}<div class="ui-table__sub">{{ $a['rate'] }}%</div></td>
                        <td class="is-num">{{ $a['avg'] ? gmdate('i:s', $a['avg']) : '—' }}</td>
                        <td class="is-num font-semibold">{{ $a['enrolled'] }}</td>
                        <td class="is-num">@if($a['overdue'])<x-ui.badge tone="red">{{ $a['overdue'] }}</x-ui.badge>@else 0 @endif</td>
                        <td class="is-nowrap text-label-3">{{ $a['last_call'] ? $a['last_call']->diffForHumans(null, true, true).' ago' : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-label-3">Give someone the "Calling Agent" or "CRM Manager" role to see them here.</td></tr>
                @endforelse
            </x-ui.table>
        </x-ui.card>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('change', function (e) {
        if (e.target.name !== 'crm_lb') return;
        document.querySelectorAll('[data-crm-lb]').forEach(function (l) { l.hidden = l.getAttribute('data-crm-lb') !== e.target.value; });
    });
</script>
@endpush
