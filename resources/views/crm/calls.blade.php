@extends('layouts.admin')

@section('body_class', 'ax-custom-page crm-page')

@section('content')
@include('crm.partials.nav')
@include('crm.partials.flash')

@php
    $range = request('range', 'today');
    $talk = (int) ($summary->talk ?? 0);
    $rangeLinks = collect(['today' => 'Today', 'yesterday' => 'Yesterday', 'week' => 'This week', 'month' => 'This month', '30' => '30 days', 'custom' => 'Custom'])->map(fn ($v, $k) => [
        'label' => $v,
        'href' => route('crm.calls.index', array_merge(request()->except(['range', 'page']), ['range' => $k])),
        'active' => (string) $range === (string) $k,
    ])->values()->all();
@endphp

<form method="GET" class="mb-5 flex flex-wrap items-center gap-2.5">
    <x-ui.segmented :links="$rangeLinks" class="w-full sm:w-auto" />
    <input type="hidden" name="range" value="{{ $range }}">
    @if($range === 'custom')
        <input type="date" name="from" value="{{ request('from') }}" class="ui-input w-[calc(50%-5px)] sm:w-40" aria-label="From">
        <input type="date" name="to" value="{{ request('to') }}" class="ui-input w-[calc(50%-5px)] sm:w-40" aria-label="To">
    @endif
    <select name="disposition" onchange="this.form.submit()" class="ui-select w-[calc(50%-5px)] sm:w-auto sm:min-w-[150px]" aria-label="Result">
        <option value="">All results</option>
        @foreach($crm['dispositions'] as $d)<option value="{{ $d->id }}" @selected(request('disposition') == $d->id)>{{ $d->name }}</option>@endforeach
    </select>
    <select name="connected" onchange="this.form.submit()" class="ui-select w-[calc(50%-5px)] sm:w-auto sm:min-w-[150px]" aria-label="Connected">
        <option value="">Connected + not</option>
        <option value="1" @selected(request('connected') === '1')>Connected</option>
        <option value="0" @selected(request('connected') === '0')>Not connected</option>
    </select>
    @if($crm['seesAll'])
        <select name="agent" onchange="this.form.submit()" class="ui-select w-[calc(50%-5px)] sm:w-auto sm:min-w-[150px]" aria-label="Agent">
            <option value="">Everyone</option>
            @foreach($crm['agents'] as $a)<option value="{{ $a->id }}" @selected(request('agent') == $a->id)>{{ $a->name }}</option>@endforeach
        </select>
    @endif
    @if($range === 'custom')<x-ui.button type="submit" icon="fa-solid fa-filter">Apply</x-ui.button>@endif
    <noscript><x-ui.button type="submit" variant="tinted">Apply</x-ui.button></noscript>
</form>

<div class="ui-stats ui-stats--4">
    <x-ui.stat label="Calls" :value="number_format($summary->calls ?? 0)" icon="fa-solid fa-phone" />
    <x-ui.stat label="Connected" :value="number_format($summary->connected ?? 0)" icon="fa-solid fa-link"
        :note="(($summary->calls ?? 0) ? round($summary->connected / $summary->calls * 100) : 0).'% connect rate'" />
    <x-ui.stat label="Leads reached" :value="number_format($summary->leads ?? 0)" icon="fa-solid fa-address-book" />
    <x-ui.stat label="Talk time" :value="(intdiv($talk, 3600) ? intdiv($talk, 3600).'h ' : '').intdiv($talk % 3600, 60).'m'" icon="fa-regular fa-clock" />
</div>

@if($calls->isEmpty())
    <x-ui.card>
        <x-ui.empty icon="fa-solid fa-phone-slash" title="No calls in this period" text="Calls logged from the queue show up here." />
    </x-ui.card>
@else
    <x-ui.card flush class="hidden lg:block">
        <x-ui.table>
            <x-slot:head><th>When</th><th>Lead</th><th>Result</th><th class="is-num">Duration</th><th>Notes</th><th>Next call</th>@if($crm['seesAll'])<th>Agent</th>@endif</x-slot:head>
            @foreach($calls as $c)
                <tr>
                    <td class="is-nowrap"><div class="tabular-nums">{{ $c->called_at->format('g:i A') }}</div><div class="ui-table__sub">{{ $c->called_at->format('j M') }}</div></td>
                    <td class="min-w-[180px]">
                        @if($c->lead)
                            <a href="{{ route('crm.leads.show', $c->lead_id) }}" class="ui-table__title">{{ $c->lead->display_name }}</a>
                            <div class="ui-table__sub crm-mono">+91 {{ $c->lead->mobile }}</div>
                        @else
                            <span class="text-label-4">Deleted lead</span>
                        @endif
                    </td>
                    <td class="is-nowrap"><span class="crm-result" style="--c: {{ $c->disposition->color ?? '#8e8e93' }}">{{ $c->disposition->name ?? '—' }}</span></td>
                    <td class="is-num">{{ $c->duration_label ?? '—' }}</td>
                    <td class="max-w-[280px] text-[13px] text-label-2">{{ \Illuminate\Support\Str::limit($c->notes, 90) ?: '—' }}</td>
                    <td class="is-nowrap text-label-3">{{ $c->follow_up_at ? $c->follow_up_at->format('j M, g:i A') : '—' }}</td>
                    @if($crm['seesAll'])<td class="is-nowrap">{{ $c->agent->name ?? '—' }}</td>@endif
                </tr>
            @endforeach
        </x-ui.table>
    </x-ui.card>
    <x-ui.card flush class="lg:hidden">
        <ul class="ui-list">
            @foreach($calls as $c)
                <li>
                    <a href="{{ $c->lead ? route('crm.leads.show', $c->lead_id) : '#' }}" class="ui-row items-start !py-3">
                        <span class="ui-row__main">
                            <span class="flex items-baseline justify-between gap-3">
                                <span class="ui-row__title">{{ $c->lead->display_name ?? 'Deleted lead' }}</span>
                                <span class="shrink-0 text-xs text-label-3 tabular-nums">{{ $c->called_at->format('j M, g:i A') }}</span>
                            </span>
                            <span class="mt-1.5 flex flex-wrap items-center gap-2 text-[12.5px] text-label-3">
                                <span class="crm-result" style="--c: {{ $c->disposition->color ?? '#8e8e93' }}">{{ $c->disposition->name ?? '—' }}</span>
                                @if($c->duration_label)<span><i class="fa-regular fa-clock"></i> {{ $c->duration_label }}</span>@endif
                                @if($crm['seesAll'] && $c->agent)<span>· {{ $c->agent->name }}</span>@endif
                            </span>
                            @if($c->notes)<span class="mt-1.5 block text-[13.5px] text-label-2">{{ $c->notes }}</span>@endif
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </x-ui.card>
    {{ $calls->links('pagination::ui') }}
@endif
@endsection
