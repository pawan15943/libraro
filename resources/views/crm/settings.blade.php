@extends('layouts.admin')

@section('body_class', 'ax-custom-page crm-page')

@section('content')
@include('crm.partials.nav')
@include('crm.partials.flash')

@php
    $settingTabs = collect(['stages' => 'Stages', 'dispositions' => 'Call results', 'templates' => 'Templates'])->map(fn ($v, $k) => [
        'label' => $v, 'href' => route('crm.settings.index', ['tab' => $k]), 'active' => $tab === $k,
    ])->values()->all();
@endphp

<div class="mb-4">
    <x-ui.segmented :links="$settingTabs" />
</div>

@if($tab === 'stages')
    <p class="crm-hint mb-4 px-1">Leads in <b>closed</b> stages never show in the call queue. Turn a stage or sub-stage off to hide it from pickers; leads keep it.</p>
    @foreach($allStages as $s)
        <section class="ui-card crm-stage-card p-4 ps-5 sm:p-5 sm:ps-6" style="--c: {{ $s->color }}">
            <form method="POST" action="{{ route('crm.settings.stages.update', $s->id) }}" class="crm-stage-row">
                @csrf @method('PUT')
                <input type="color" name="color" value="{{ $s->color }}" class="crm-color" aria-label="Colour">
                <input name="name" value="{{ $s->name }}" class="ui-input crm-stage-name font-semibold" required maxlength="80" aria-label="Stage name">
                <x-ui.badge :tone="$s->is_won ? 'green' : ($s->is_closed ? 'red' : 'blue')" pill>{{ $s->is_won ? 'Won · closed' : ($s->is_closed ? 'Lost · closed' : 'Open') }}</x-ui.badge>
                <span class="min-w-[64px] text-xs text-label-3 tabular-nums">{{ $leadCounts[$s->id] ?? 0 }} leads</span>
                <input type="number" name="sort" value="{{ $s->sort }}" class="ui-input w-[72px] flex-none" aria-label="Order" title="Order">
                <label class="ui-switch" title="Active"><input type="checkbox" name="is_active" value="1" @checked($s->is_active) aria-label="Active"><span class="ui-switch__track" aria-hidden="true"></span></label>
                <x-ui.button type="submit" variant="tinted" size="sm" class="ms-auto sm:ms-0">Save</x-ui.button>
            </form>
            <div class="mt-3 flex flex-col gap-2 ps-3 sm:ps-4">
                @foreach($s->children as $c)
                    <form method="POST" action="{{ route('crm.settings.stages.update', $c->id) }}" class="crm-stage-row">
                        @csrf @method('PUT')
                        <span class="crm-sub-dash"></span>
                        <input name="name" value="{{ $c->name }}" class="ui-input ui-input--sm crm-stage-name" required maxlength="80" aria-label="Sub-stage name">
                        <span class="min-w-[64px] text-xs text-label-3 tabular-nums">{{ $subCounts[$c->id] ?? 0 }} leads</span>
                        <input type="number" name="sort" value="{{ $c->sort }}" class="ui-input ui-input--sm w-[72px] flex-none" aria-label="Order">
                        <label class="ui-switch" title="Active"><input type="checkbox" name="is_active" value="1" @checked($c->is_active) aria-label="Active"><span class="ui-switch__track" aria-hidden="true"></span></label>
                        <x-ui.button type="submit" variant="gray" size="sm" class="ms-auto sm:ms-0">Save</x-ui.button>
                    </form>
                @endforeach
                <form method="POST" action="{{ route('crm.settings.stages.store') }}" class="crm-stage-row is-add">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $s->id }}">
                    <span class="crm-sub-dash"></span>
                    <input name="name" class="ui-input ui-input--sm crm-stage-name" placeholder="Add a sub-stage to {{ $s->name }}" required maxlength="80" aria-label="New sub-stage">
                    <x-ui.button type="submit" variant="plain" size="sm" icon="fa-solid fa-plus">Add</x-ui.button>
                </form>
            </div>
        </section>
    @endforeach
    <x-ui.section label="New stage">
        <x-ui.card>
            <form method="POST" action="{{ route('crm.settings.stages.store') }}" class="crm-stage-row">
                @csrf
                <input type="color" name="color" value="#475569" class="crm-color" aria-label="Colour">
                <input name="name" class="ui-input crm-stage-name" placeholder="Stage name" required maxlength="80" aria-label="Stage name">
                <select name="outcome" class="ui-select w-auto min-w-[170px]" aria-label="Outcome">
                    <option value="open">Open (in the queue)</option>
                    <option value="won">Won (closed)</option>
                    <option value="lost">Lost (closed)</option>
                </select>
                <x-ui.button type="submit" icon="fa-solid fa-plus" class="ms-auto sm:ms-0">Add stage</x-ui.button>
            </form>
        </x-ui.card>
    </x-ui.section>
@endif

@if($tab === 'dispositions')
    <p class="crm-hint mb-4 px-1">A call result (disposition) moves the lead to its stage and suggests the next call after the given minutes.</p>
    @foreach($allDispositions->push(new \App\Models\Crm\CrmDisposition(['is_connected' => true, 'is_active' => true, 'color' => '#475569'])) as $d)
        @if(!$d->exists)<div class="ui-section-label">New call result</div>@endif
        <x-ui.card>
            <form method="POST" action="{{ $d->exists ? route('crm.settings.dispositions.update', $d->id) : route('crm.settings.dispositions.store') }}" class="grid grid-cols-2 items-end gap-3 md:grid-cols-12" data-crm-disp>
                @csrf
                @if($d->exists) @method('PUT') @endif
                <div class="col-span-2 md:col-span-3">
                    <label class="ui-label">Name</label>
                    <div class="flex gap-2">
                        <input type="color" name="color" value="{{ $d->color }}" class="crm-color" aria-label="Colour">
                        <input name="name" value="{{ $d->name }}" class="ui-input" required maxlength="80" placeholder="e.g. Asked for brochure" aria-label="Name">
                    </div>
                </div>
                <div class="min-w-0 md:col-span-2">
                    <label class="ui-label">Moves to stage</label>
                    <select name="stage_id" class="ui-select" data-crm-disp-stage aria-label="Moves to stage">
                        <option value="">No change</option>
                        @foreach($allStages as $s)<option value="{{ $s->id }}" @selected($d->stage_id == $s->id)>{{ $s->name }}</option>@endforeach
                    </select>
                </div>
                <div class="min-w-0 md:col-span-2">
                    <label class="ui-label">Sub-stage</label>
                    <select name="sub_stage_id" class="ui-select" data-crm-disp-sub data-value="{{ $d->sub_stage_id }}" aria-label="Sub-stage"></select>
                </div>
                <div class="min-w-0 md:col-span-1">
                    <label class="ui-label whitespace-nowrap" title="Next call after (minutes)">Next call (min)</label>
                    <input type="number" name="follow_up_minutes" value="{{ $d->follow_up_minutes }}" min="5" max="43200" class="ui-input" placeholder="none" aria-label="Next call after minutes">
                </div>
                <div class="col-span-2 flex min-h-10 flex-wrap items-center gap-x-4 gap-y-2 md:col-span-4">
                    <label class="ui-switch"><input type="checkbox" name="is_connected" value="1" @checked($d->is_connected)><span class="ui-switch__track" aria-hidden="true"></span><span>Connected</span></label>
                    @if($d->exists)<label class="ui-switch"><input type="checkbox" name="is_active" value="1" @checked($d->is_active)><span class="ui-switch__track" aria-hidden="true"></span><span>Active</span></label>@endif
                    <x-ui.button type="submit" :variant="$d->exists ? 'tinted' : 'filled'" size="sm" class="ms-auto">{{ $d->exists ? 'Save' : 'Add' }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endforeach
@endif

@if($tab === 'templates')
    <p class="crm-hint mb-4 px-1">Placeholders: @foreach(\App\Models\Crm\CrmTemplate::PLACEHOLDERS as $p)<code>{{ $p }}</code> @endforeach — filled in for each lead when the message opens.</p>
    <div class="grid gap-x-5 lg:grid-cols-2">
        @foreach($allTemplates->push(new \App\Models\Crm\CrmTemplate(['channel' => 'whatsapp', 'is_active' => true])) as $t)
            <x-ui.card :title="$t->exists ? null : 'New template'">
                <form method="POST" action="{{ $t->exists ? route('crm.settings.templates.update', $t->id) : route('crm.settings.templates.store') }}">
                    @csrf
                    @if($t->exists) @method('PUT') @endif
                    <div class="mb-2.5 flex gap-2">
                        <input name="name" value="{{ $t->name }}" class="ui-input font-semibold" required maxlength="80" placeholder="{{ $t->exists ? '' : 'Template name' }}" aria-label="Template name">
                        <select name="channel" class="ui-select w-auto min-w-[112px] flex-none sm:min-w-[130px]" aria-label="Channel">
                            <option value="whatsapp" @selected($t->channel === 'whatsapp')>WhatsApp</option>
                            <option value="sms" @selected($t->channel === 'sms')>SMS</option>
                        </select>
                    </div>
                    <textarea name="body" rows="5" class="ui-textarea" required maxlength="2000" placeholder="Hi {name}, …" aria-label="Message">{{ $t->body }}</textarea>
                    <div class="mt-3 flex items-center gap-2">
                        @if($t->exists)<label class="ui-switch"><input type="checkbox" name="is_active" value="1" @checked($t->is_active)><span class="ui-switch__track" aria-hidden="true"></span><span>Active</span></label>@endif
                        @if($t->exists)
                            <x-ui.button type="submit" variant="destructive" size="sm" class="ms-auto" form="crmTplDel{{ $t->id }}">Delete</x-ui.button>
                            <x-ui.button type="submit" variant="tinted" size="sm">Save</x-ui.button>
                        @else
                            <x-ui.button type="submit" size="sm" icon="fa-solid fa-plus" class="ms-auto">Add template</x-ui.button>
                        @endif
                    </div>
                </form>
                @if($t->exists)
                    <form method="POST" action="{{ route('crm.settings.templates.destroy', $t->id) }}" id="crmTplDel{{ $t->id }}" hidden onsubmit="return confirm('Delete this template?')">@csrf @method('DELETE')</form>
                @endif
            </x-ui.card>
        @endforeach
    </div>
@endif
@endsection

@push('scripts')
<script>
    (function () {
        var subs = @json($allStages->mapWithKeys(fn ($s) => [$s->id => $s->children->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()]));
        document.querySelectorAll('[data-crm-disp]').forEach(function (f) {
            var st = f.querySelector('[data-crm-disp-stage]'), sub = f.querySelector('[data-crm-disp-sub]');
            function fill() {
                var list = subs[st.value] || [], v = sub.getAttribute('data-value');
                sub.innerHTML = '<option value="">—</option>' + list.map(function (s) { return '<option value="' + s.id + '"' + (String(s.id) === String(v) ? ' selected' : '') + '>' + s.name.replace(/</g, '&lt;') + '</option>'; }).join('');
            }
            st.addEventListener('change', function () { sub.setAttribute('data-value', ''); fill(); });
            fill();
        });
    })();
</script>
@endpush
