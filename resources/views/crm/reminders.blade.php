@extends('layouts.admin')

@section('body_class', 'ax-custom-page crm-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-regular fa-bell" data-crm-enable-alerts>Turn on alerts</x-ui.button>
@endsection

@section('content')
@include('crm.partials.nav')
@include('crm.partials.flash')

@php
    $remTabs = collect(\App\Http\Controllers\Crm\CrmReminderController::TABS)->map(fn ($label, $key) => [
        'label' => $label,
        'href' => route('crm.reminders.index', ['tab' => $key] + request()->only('agent')),
        'active' => $tab === $key,
        'count' => $counts[$key],
    ])->values()->all();
@endphp

<div class="mb-4 flex flex-wrap items-center gap-2.5">
    <x-ui.segmented :links="$remTabs" />
    @if($crm['seesAll'])
        <form method="GET" class="sm:ms-auto">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <select name="agent" class="ui-select w-auto min-w-[160px]" onchange="this.form.submit()" aria-label="Agent">
                <option value="">Everyone</option>
                @foreach($crm['agents'] as $a)<option value="{{ $a->id }}" @selected(request('agent') == $a->id)>{{ $a->name }}</option>@endforeach
            </select>
        </form>
    @endif
</div>

@if($reminders->isEmpty())
    <x-ui.card>
        <x-ui.empty icon="fa-regular fa-calendar-check" :title="$tab === 'overdue' ? 'Nothing overdue' : 'No follow-ups here'" text="Follow-ups appear here when you set a next call after a call." />
    </x-ui.card>
@else
    <x-ui.card flush>
        @foreach($reminders as $r)
            @php $lead = $r->lead; @endphp
            <div class="crm-rem {{ $r->status === 'pending' && $r->remind_at->isPast() ? 'is-overdue' : '' }} {{ $r->status !== 'pending' ? 'is-done' : '' }}" data-lead='@json($lead->sheetData())'>
                <div class="crm-rem__time">
                    <b>{{ $r->remind_at->format('g:i A') }}</b>
                    <span>{{ $r->remind_at->isToday() ? 'Today' : ($r->remind_at->isTomorrow() ? 'Tomorrow' : ($r->remind_at->isYesterday() ? 'Yesterday' : $r->remind_at->format('D, j M'))) }}</span>
                </div>
                <div class="min-w-0">
                    <a href="{{ route('crm.leads.show', $lead->id) }}" class="ui-table__title flex items-center gap-2 text-[15px]"><span class="crm-prio prio-{{ $lead->priority }}"></span><span class="truncate">{{ $lead->display_name }}</span></a>
                    <div class="ui-table__sub"><span class="crm-mono">+91 {{ $lead->mobile }}</span>@if($crm['seesAll'] && $r->agent) · {{ $r->agent->name }}@endif</div>
                    @if($r->note)<div class="crm-rem__note">{{ $r->note }}</div>@endif
                    <div class="mt-1.5">@include('crm.partials.stage-chip')</div>
                </div>
                <div class="crm-rem__actions crm-actions">
                    @if($r->status === 'pending')
                        @if($crm['can']['call'])<a href="tel:+91{{ $lead->mobile }}" class="ui-btn crm-call" data-crm-call><i class="fa-solid fa-phone"></i><span>Call</span></a>@endif
                        @if($crm['can']['message'])<button type="button" class="ui-btn ui-btn--icon crm-wa" data-crm-open="message" data-channel="whatsapp" title="WhatsApp" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></button>@endif
                        @if($crm['can']['remind'])
                            <button type="button" class="ui-btn ui-btn--gray ui-btn--icon" data-crm-open="reminder" title="Reschedule" aria-label="Reschedule"><i class="fa-regular fa-clock"></i></button>
                            <form method="POST" action="{{ route('crm.reminders.update', [$r->id, 'done']) }}">@csrf<button class="ui-btn ui-btn--gray ui-btn--icon" title="Mark done" aria-label="Mark done"><i class="fa-solid fa-check"></i></button></form>
                        @endif
                    @else
                        <x-ui.badge tone="green" pill><i class="fa-solid fa-check"></i> Done {{ optional($r->done_at)->diffForHumans() }}</x-ui.badge>
                    @endif
                </div>
            </div>
        @endforeach
    </x-ui.card>
    {{ $reminders->links('pagination::ui') }}
@endif

@include('crm.partials.sheets')
@endsection
