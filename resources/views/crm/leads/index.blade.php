@extends('layouts.admin')

@section('body_class', 'ax-custom-page crm-page')

@section('page_actions')
    @if($crm['can']['export'])
        <x-ui.button variant="gray" :href="route('crm.leads.export', request()->query())" icon="fa-solid fa-download"><span class="hidden sm:inline">Export</span></x-ui.button>
    @endif
    @if($crm['can']['create'])
        <x-ui.button :href="route('crm.leads.create')" icon="fa-solid fa-plus">Add lead</x-ui.button>
    @endif
@endsection

@section('content')
@include('crm.partials.nav')
@include('crm.partials.flash')

@php
    $status = request('status', 'open');
    $selStage = request('stage') ? $crm['stages']->firstWhere('id', (int) request('stage')) : null;
    $statusLinks = collect(['open' => 'Open', 'closed' => 'Closed', 'all' => 'All'])->map(fn ($v, $k) => [
        'label' => $v,
        'href' => route('crm.leads.index', array_merge(request()->except(['status', 'page']), ['status' => $k])),
        'active' => $status === $k,
    ])->values()->all();
@endphp

<div class="mb-4 flex flex-wrap items-center gap-2.5">
    <x-ui.segmented :links="$statusLinks" />
    <form class="crm-search order-last w-full min-w-0 sm:order-none sm:w-auto sm:flex-1" method="GET" action="{{ route('crm.leads.index') }}" role="search">
        @foreach(request()->except(['q', 'page']) as $k => $v)
            @if(!is_array($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
        @endforeach
        <div class="ui-input-wrap">
            <i class="ui-input-icon fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" name="q" value="{{ request('q') }}" class="ui-input" placeholder="Name, library, mobile, city, e-mail" aria-label="Search leads">
        </div>
    </form>
    <button class="ui-btn {{ $activeFilters ? 'ui-btn--tinted' : 'ui-btn--gray' }} ms-auto sm:ms-0" type="button" data-bs-toggle="collapse" data-bs-target="#crmFilters" aria-controls="crmFilters" aria-expanded="{{ $activeFilters ? 'true' : 'false' }}">
        <i class="fa-solid fa-sliders"></i> Filters @if($activeFilters)<span class="ui-count bg-tint">{{ $activeFilters }}</span>@endif
    </button>
</div>

<div class="collapse {{ $activeFilters ? 'show' : '' }}" id="crmFilters">
    <form method="GET" action="{{ route('crm.leads.index') }}">
        <input type="hidden" name="status" value="{{ $status }}">
        @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
        <x-ui.card flat>
            <div class="grid grid-cols-2 gap-x-3 gap-y-4 *:mb-0 lg:grid-cols-4">
                <x-ui.select name="stage" label="Stage" :value="request('stage')" placeholder="Any stage"
                    :options="$crm['stages']->pluck('name', 'id')" onchange="this.form.sub && (this.form.sub.value=''); this.form.submit()" />
                <x-ui.select name="sub" label="Sub-stage" :value="request('sub')" :placeholder="$selStage ? 'Any' : 'Pick a stage first'"
                    :options="$selStage?->children->pluck('name', 'id') ?? []" :disabled="!$selStage" />
                <x-ui.select name="follow" label="Follow-up" :value="request('follow')" placeholder="Any"
                    :options="['overdue' => 'Overdue', 'today' => 'Due today', 'upcoming' => 'Later', 'none' => 'No follow-up set', 'never' => 'Never called', 'stale' => 'Not called for 7+ days']" />
                <x-ui.select name="source" label="Source" :value="request('source')" placeholder="Any source" :options="\App\Models\Crm\CrmLead::SOURCES" />
                <x-ui.select name="priority" label="Priority" :value="request('priority')" placeholder="Any" :options="\App\Models\Crm\CrmLead::PRIORITIES" />
                <x-ui.select name="agent" label="Agent" :value="request('agent')" placeholder="Anyone"
                    :options="['me' => 'Me', 'none' => 'Not assigned'] + $crm['agents']->pluck('name', 'id')->all()" />
                <x-ui.input type="date" name="from" label="Added from" :value="request('from')" />
                <x-ui.input type="date" name="to" label="Added to" :value="request('to')" />
                <div class="col-span-2 lg:col-span-1">
                    <x-ui.select name="sort" label="Sort" :value="request('sort', 'latest')"
                        :options="['latest' => 'Newest first', 'follow' => 'Next follow-up', 'called' => 'Longest not called', 'name' => 'Name']" />
                </div>
            </div>
            <x-slot:footer>
                <x-ui.button variant="gray" :href="route('crm.leads.index')">Clear</x-ui.button>
                <x-ui.button type="submit" icon="fa-solid fa-filter">Apply</x-ui.button>
            </x-slot:footer>
        </x-ui.card>
    </form>
</div>

<div class="mb-2.5 px-1 text-[13px] text-label-3">
    <b class="font-semibold text-label">{{ number_format($leads->total()) }}</b> {{ \Illuminate\Support\Str::plural('lead', $leads->total()) }}
</div>

@if($leads->isEmpty())
    <x-ui.card>
        <x-ui.empty icon="fa-regular fa-folder-open" title="No leads found" text="Change the filters, or add / import leads." />
    </x-ui.card>
@else
    <form method="POST" action="{{ route('crm.leads.assign') }}" id="crmBulk">
        @csrf
        {{-- desktop table --}}
        <x-ui.card flush class="hidden lg:block">
            <x-ui.table>
                <x-slot:head>
                    @if($crm['can']['assign'])<th class="w-10 !pr-0"><label class="ui-check"><input type="checkbox" data-crm-check-all aria-label="Select all"></label></th>@endif
                    <th>Lead</th>
                    <th>Stage</th>
                    <th>Last call</th>
                    <th>Follow-up</th>
                    @if($crm['seesAll'])<th class="hidden 2xl:table-cell">Agent</th>@endif
                    <th class="is-end">Actions</th>
                </x-slot:head>
                @foreach($leads as $lead)
                    <tr data-lead='@json($lead->sheetData())'>
                        @if($crm['can']['assign'])<td class="w-10 !pr-0"><label class="ui-check"><input type="checkbox" name="ids[]" value="{{ $lead->id }}" data-crm-check aria-label="Select {{ $lead->display_name }}"></label></td>@endif
                        <td class="min-w-[200px] max-w-[300px]">
                            <a href="{{ route('crm.leads.show', $lead->id) }}" class="ui-table__title flex items-center gap-2"><span class="crm-prio prio-{{ $lead->priority }}"></span><span class="truncate">{{ $lead->display_name }}</span></a>
                            <div class="ui-table__sub">
                                @if($lead->library_name && $lead->contact_name){{ $lead->contact_name }} · @endif
                                <span class="crm-mono">+91 {{ $lead->mobile }}</span>@if($lead->city) · {{ $lead->city }}@endif
                                @if($lead->source === 'signup')<x-ui.badge tone="orange" pill class="ms-1 !h-5 !text-[11px]">Not paid</x-ui.badge>@endif
                            </div>
                        </td>
                        <td class="max-w-[200px]">@include('crm.partials.stage-chip')</td>
                        <td class="is-nowrap">
                            @if($lead->last_called_at)
                                <div>{{ $lead->lastDisposition->name ?? 'Called' }}</div>
                                <div class="ui-table__sub">{{ $lead->last_called_at->diffForHumans() }} · {{ $lead->calls_count }} {{ \Illuminate\Support\Str::plural('call', $lead->calls_count) }}</div>
                            @else
                                <span class="text-label-4">Never called</span>
                            @endif
                        </td>
                        <td class="is-nowrap">@include('crm.partials.follow-chip')@if(!$lead->next_follow_up_at)<span class="text-label-4">—</span>@endif
                            @if($crm['seesAll'] && $lead->agent)<div class="ui-table__sub 2xl:hidden"><i class="fa-regular fa-user text-[10px]"></i> {{ $lead->agent->name }}</div>@endif
                        </td>
                        @if($crm['seesAll'])<td class="is-nowrap hidden 2xl:table-cell">{{ $lead->agent->name ?? '—' }}</td>@endif
                        <td class="is-end">@include('crm.partials.lead-actions', ['size' => 'is-compact'])</td>
                    </tr>
                @endforeach
            </x-ui.table>
        </x-ui.card>

        {{-- phone cards --}}
        <div class="grid gap-3 md:grid-cols-2 lg:hidden">
            @foreach($leads as $lead)
                @include('crm.partials.lead-card')
            @endforeach
        </div>

        @if($crm['can']['assign'])
            <div class="crm-bulkbar" data-crm-bulkbar hidden>
                <span class="whitespace-nowrap"><b data-crm-selected>0</b> selected</span>
                <select name="agent_id" class="ui-select ui-select--sm" aria-label="Assign to">
                    <option value="">Unassign (open pool)</option>
                    @foreach($crm['agents'] as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
                </select>
                <button class="ui-btn ui-btn--sm ui-btn--pill bg-white !text-tint-text"><i class="fa-solid fa-user-check"></i> Assign</button>
            </div>
        @endif
    </form>
    {{ $leads->links('pagination::ui') }}
@endif

@include('crm.partials.sheets')
@endsection

@push('scripts')
<script>
    (function () {
        var bar = document.querySelector('[data-crm-bulkbar]');
        if (!bar) return;
        function refresh() {
            var n = document.querySelectorAll('[data-crm-check]:checked').length;
            bar.hidden = !n;
            bar.querySelector('[data-crm-selected]').textContent = n;
        }
        document.addEventListener('change', function (e) {
            if (e.target.matches('[data-crm-check-all]')) {
                document.querySelectorAll('[data-crm-check]').forEach(function (c) { c.checked = e.target.checked; });
            }
            if (e.target.matches('[data-crm-check], [data-crm-check-all]')) refresh();
        });
    })();
</script>
@endpush
